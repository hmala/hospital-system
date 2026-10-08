@extends('layouts.app')

@section('content')
<style>
/* تحسينات checkboxes التحاليل والأشعة */
.hover-zoom {
    transition: transform 0.3s ease;
}

.hover-zoom:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

.modal-dialog-scrollable .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

.hover-lab-item {
    border-left: 3px solid #dee2e6 !important;
    background-color: #ffffff;
}

.hover-lab-item:hover {
    background-color: #f0f9ff !important;
    border-left-color: #3b82f6 !important;
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
}

.hover-lab-item:has(input:checked) {
    background-color: #dbeafe !important;
    border-left-color: #2563eb !important;
    border-left-width: 4px !important;
}

.hover-radiology-item {
    border-left: 3px solid #bae6fd !important;
    background-color: #ffffff;
}

.hover-radiology-item:hover {
    background-color: #f0fdfa !important;
    border-left-color: #14b8a6 !important;
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(20, 184, 166, 0.15);
}

.hover-radiology-item:has(input:checked) {
    background-color: #ccfbf1 !important;
    border-left-color: #0d9488 !important;
    border-left-width: 4px !important;
}

.list-group-item {
    border: 1px solid #e5e7eb;
    margin-bottom: 2px;
}

.hover-highlight {
    background-color: #ffffff;
    border: 1px solid #dee2e6 !important;
}

.hover-highlight:hover {
    background-color: #f0f9ff !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2) !important;
    transform: translateY(-1px);
}

.hover-highlight:has(input:checked) {
    background-color: #dbeafe !important;
    border-color: #2563eb !important;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3) !important;
}

.form-check-input {
    border: 2px solid #cbd5e1;
    transition: all 0.2s ease;
}

.form-check-input:checked {
    background-color: #2563eb;
    border-color: #2563eb;
}

.form-check-input:hover {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* تحسينات أزرار اختيار عدد المرات */
.frequency-selector input[type="radio"]:checked + label {
    background: linear-gradient(135deg, #0d6efd, #1976d2) !important;
    color: white !important;
    border-color: #0d6efd !important;
    box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3) !important;
    transform: translateY(-2px);
}

.frequency-selector label:hover {
    border-color: #0d6efd !important;
    background-color: rgba(13, 110, 253, 0.05) !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(13, 110, 253, 0.2) !important;
}

/* تحسينات تصميم مربع البحث */
.diagnosis-input {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    min-height: 38px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.diagnosis-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    background-color: #fff;
}

.input-group.focused .input-group-text {
    background: linear-gradient(135deg, #0d6efd, #1976d2) !important;
    transform: scale(1.05);
    transition: all 0.3s ease;
    animation: iconPulse 1s infinite;
}

@keyframes iconPulse {
    0%, 100% { transform: scale(1.05); }
    50% { transform: scale(1.1); }
}

.input-group .input-group-text {
    border: 2px solid #e9ecef;
    border-right: none;
    background: linear-gradient(135deg, #0d6efd, #1976d2);
    color: white;
    transition: all 0.3s ease;
}

.input-group .diagnosis-input {
    border-left: none;
}

.input-group .diagnosis-input:focus {
    border-left: none;
    z-index: 3;
}

/* تحسين مظهر datalist */
datalist {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
}

datalist option {
    padding: 8px 12px;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s ease;
}

datalist option:hover {
    background-color: #f8f9fa;
}

/* تحسين النص المساعد */
.text-muted small {
    font-size: 0.75rem;
    color: #6c757d !important;
    display: flex;
    align-items: center;
}

/* تأثيرات الحركة */
@keyframes searchPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

@keyframes bounceIn {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.05); }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); opacity: 1; }
}

.diagnosis-input.animate__pulse {
    animation: searchPulse 0.3s ease-in-out;
}

.diagnosis-input.animate__bounceIn {
    animation: bounceIn 0.5s ease-out;
}

/* تحسين عرض النتائج */
#icd10-list option {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-size: 0.9rem;
}

#icd10-list option[value="other"] {
    color: #fd7e14;
    font-weight: 600;
}

/* تحسينات التصميم العام */
.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.btn {
    border-radius: 6px;
    font-weight: 600;
    padding: 0.5rem 1.5rem;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* نظام الـ Accordion الجديد */
.visit-tabs-container {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.visit-tab-nav {
    border-bottom: 2px solid #e5e7eb;
    padding-left: 0;
    margin-bottom: 0;
    overflow-x: auto;
    white-space: nowrap;
    background: #ffffff;
}

.visit-tab-nav .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    border-radius: 0;
    margin-right: 0;
    padding: 1rem 1.5rem;
    color: #6c757d;
    background: transparent;
    font-weight: 600;
    transition: all 0.2s ease;
    position: relative;
}

.visit-tab-nav .nav-link:hover {
    color: #0d6efd;
    background: #f8fbff;
}

.visit-tab-nav .nav-link.active {
    color: #0d6efd;
    background: transparent;
    border-bottom-color: #0d6efd;
}

.tab-content {
    background: white;
    padding: 2rem;
    animation: fadeIn 0.3s ease-out;
}

.tab-pane {
    display: none;
}

.tab-pane.show {
    display: block;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* تحسين الجداول والتنبيهات والبطاقات */
.tab-content .alert {
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.tab-content .table {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.tab-content .card {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

/* تأثيرات الاستجابة */
@media (max-width: 768px) {
    .accordion-button {
        padding: 1rem 1.5rem;
        font-size: 1rem;
    }

    .accordion-body {
        padding: 1.5rem;
    }

    .section-icon {
        width: 35px;
        height: 35px;
        font-size: 1rem;
        margin-left: 0.5rem;
    }
}

/* أنماط المجموعات المنسدلة */
.main-group-header {
    transition: all 0.3s ease;
}

.main-group-header:hover {
    opacity: 0.9;
}

.toggle-icon {
    transition: transform 0.3s ease;
}

.collapsed .toggle-icon {
    transform: rotate(0deg);
}

.main-group-header:not(.collapsed) .toggle-icon {
    transform: rotate(180deg);
}
</style>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-user-md me-2"></i>
                    فحص المريض
                </h2>
                <div class="d-flex gap-2">
                    @if($visit->status == 'in_progress' && isset($availableDoctors) && $availableDoctors->count())
                        <button type="button" id="referDoctorButton" class="btn btn-primary">
                            <i class="fas fa-exchange-alt me-1"></i>
                            تحويل للطبيب الآخر
                        </button>
                    @endif
                    @if($visit->status == 'in_progress')
                        <form action="{{ route('doctor.visits.update', $visit) }}" method="POST" class="d-inline" id="completeVisitForm">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="btn btn-success" onclick="return confirm('هل أنت متأكد من إنهاء هذه الزيارة؟ سيتم تغيير حالتها إلى مكتملة وستظهر في التاريخ.')">
                                <i class="fas fa-check-circle me-1"></i>
                                إنهاء الزيارة
                            </button>
                        </form>
                        <form action="{{ route('doctor.visits.cancel', $visit) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من إلغاء هذا الحجز؟ سيتم إعلام موظف الاستعلامات والكاشير.')">
                                <i class="fas fa-times me-1"></i>
                                إلغاء الحجز
                            </button>
                        </form>
                    @endif
                    @if($visit->status == 'completed')
                        <span class="badge bg-success p-2 d-inline-flex align-items-center">
                            <i class="fas fa-check-circle me-1"></i>
                            الزيارة مكتملة
                        </span>
                        <form action="{{ route('doctor.visits.update', $visit) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="in_progress">
                            <button type="submit" class="btn btn-outline-primary" onclick="return confirm('هل تريد إعادة فتح هذه الزيارة لاستكمال الإجراءات والكشف؟')">
                                <i class="fas fa-redo me-1"></i>
                                إعادة فتح الزيارة للمتابعة
                            </button>
                        </form>
                    @endif
                    @if(!$visit->needs_surgery && $visit->status != 'cancelled')
                        <a href="{{ route('doctor.visits.show-surgery-form', $visit) }}" class="btn btn-warning">
                            <i class="fas fa-procedures me-1"></i>
                            تحويل لحجز عملية
                        </a>
                    @endif
                    @if($visit->needs_surgery && !$visit->surgery)
                        <span class="badge bg-warning text-dark p-2">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            في انتظار حجز العملية من الاستعلامات
                        </span>
                    @endif
                    <a href="{{ route('doctor.visits.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(isset($availableDoctors) && $availableDoctors->count())
        <div id="referDoctorPanel" class="card border-primary mb-4 d-none">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-exchange-alt me-2"></i>
                تحويل المريض للطبيب الآخر
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">اختر طبيباً استشارياً متاحاً اليوم لإكمال الفحص.</p>
                <form action="{{ route('doctor.visits.refer', $visit) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label for="doctor_id" class="form-label">اختر الطبيب الجديد</label>
                            <select id="doctor_id" name="doctor_id" class="form-select" required>
                                <option value="">اختر الطبيب</option>
                                @foreach($availableDoctors as $doctor)
                                    <option value="{{ $doctor->id }}">{{ optional($doctor->user)->name }} - {{ $doctor->specialization }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-1"></i>
                                تأكيد التحويل
                            </button>
                            <button type="button" id="closeReferDoctorPanel" class="btn btn-outline-secondary w-100 mt-2">
                                إغلاق
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @php
        $examinationComplete = !empty($visit->vital_signs);
        $diagnosisComplete = $visit->diagnosis && isset($visit->diagnosis['code']) && !empty($visit->diagnosis['code']);
        $treatmentComplete = $visit->prescribedMedications->count() > 0 || !empty($visit->treatment_plan);
        $requestsComplete = $visit->requests->count() > 0;
    @endphp

    <!-- بطاقة معلومات المريض الشاملة -->
    @php
        $patient = $visit->patient;
        $patientUser = optional($patient)->user;
        $gender = $patientUser->gender ?? $patient->gender ?? null;
        $age = $patient->age ?? ($patientUser && $patientUser->date_of_birth ? \Carbon\Carbon::parse($patientUser->date_of_birth)->age : null);
        $insuranceType = optional($visit->appointment)->insurance_type ?? $patient->insurance_type ?? 'none';
        $insuranceCategory = $patient?->healthInsuranceCategory;
    @endphp
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-id-card fs-5"></i>
                        <h6 class="mb-0 fw-bold">بيانات المريض والملف الطبي</h6>
                    </div>
                    <div>
                        <span class="badge bg-white text-primary fw-bold font-monospace">
                            ملف رقم: #{{ $patient->national_id ?: ($patient->id ?? $visit->patient_id) }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <!-- الاسم -->
                        <div class="col-xl-3 col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary-subtle text-primary rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">اسم المريض</small>
                                    <strong class="text-dark fs-6">{{ $patientUser->name ?? 'غير محدد' }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- العمر والجنس -->
                        <div class="col-xl-2 col-md-3 col-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-info-subtle text-info rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas {{ $gender === 'female' ? 'fa-venus text-danger' : 'fa-mars text-primary' }}"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">العمر / الجنس</small>
                                    <strong class="text-dark">
                                        {{ $age ? ($age . ' سنة') : 'غير محدد' }} / 
                                        {{ $gender === 'male' ? 'ذكر' : ($gender === 'female' ? 'أنثى' : 'غير محدد') }}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <!-- فصيلة الدم -->
                        <div class="col-xl-2 col-md-3 col-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-danger-subtle text-danger rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas fa-tint"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">فصيلة الدم</small>
                                    <strong class="text-danger fw-bold">{{ $patient->blood_type ?: 'غير محددة' }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- نوع التأمين / الضمان الصحي -->
                        <div class="col-xl-3 col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-success-subtle text-success rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">نوع التغطية والضمان</small>
                                    @if($insuranceType === 'hi')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
                                            ضمان صحي وطني {{ $insuranceCategory ? '(' . $insuranceCategory->name . ')' : '' }}
                                        </span>
                                    @elseif($insuranceType === 'insurance')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle fw-semibold">
                                            تأمين خاص ({{ $patient->insurance_company ?: 'معتمد' }})
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border fw-semibold">
                                            دفع نقدي (Cash)
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- الهاتف / الطوارئ -->
                        <div class="col-xl-2 col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-secondary-subtle text-secondary rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">الهاتف</small>
                                    <strong class="text-dark font-monospace small">{{ $patientUser->phone ?: ($patient->emergency_contact ?: 'غير مسجل') }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- شريط تنبيه الحساسية والأمراض المزمنة إن وجدت -->
                    @if(!empty($patient->allergies) || !empty($patient->medical_history))
                        <div class="d-flex flex-wrap gap-2 mt-3 pt-2 border-top">
                            @if(!empty($patient->allergies))
                                <div class="alert alert-danger py-1 px-2 mb-0 d-inline-flex align-items-center gap-1 small">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>تنبيه حساسية:</strong> {{ $patient->allergies }}
                                </div>
                            @endif
                            @if(!empty($patient->medical_history))
                                <div class="alert alert-warning py-1 px-2 mb-0 d-inline-flex align-items-center gap-1 small">
                                    <i class="fas fa-notes-medical"></i>
                                    <strong>سوابق مرضية:</strong> {{ Str::limit($patient->medical_history, 80) }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- المحتوى الرئيسي - مقسم إلى شاشة عمل الطبيب (يمين) والسجل الطبي الدائم (يسار) -->
    <div class="row g-3 mb-4">
        <!-- 1. الشاشة الرئيسية لعمل الطبيب (التبويبات الـ 5 المستقلة) -->
        <div class="col-12 col-xl-7 col-lg-7">
            <div class="visit-tabs-container bg-white rounded-3 shadow-sm border overflow-hidden">
                <!-- شريط التبويبات الـ 5 -->
                <ul class="nav nav-tabs visit-tab-nav border-bottom bg-light px-2 pt-2" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" type="button" role="tab" data-bs-target="#examinationCollapse">
                            <i class="fas fa-user-md me-1 text-primary"></i> 1. الفحص والتشخيص
                            @if($examinationComplete && $diagnosisComplete)
                                <span class="badge bg-success ms-1"><i class="fas fa-check"></i></span>
                            @endif
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" type="button" role="tab" data-bs-target="#labCollapse">
                            <i class="fas fa-microscope me-1 text-primary"></i> 2. تحاليل المختبر
                            <span class="badge bg-primary ms-1 doc-lab-selected-count">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" type="button" role="tab" data-bs-target="#radiologyCollapse">
                            <i class="fas fa-x-ray me-1 text-info"></i> 3. الأشعة والتصوير
                            <span class="badge bg-info ms-1 doc-rad-selected-count">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" type="button" role="tab" data-bs-target="#nursingCollapse">
                            <i class="fas fa-syringe me-1 text-success"></i> 4. الخدمات التمريضية
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" type="button" role="tab" data-bs-target="#treatmentCollapse">
                            <i class="fas fa-pills me-1 text-danger"></i> 5. الوصفة والعلاج
                            @if($treatmentComplete)
                                <span class="badge bg-success ms-1"><i class="fas fa-check"></i></span>
                            @endif
                        </button>
                    </li>
                </ul>

                <div class="p-3 p-md-4">
                    <!-- تاب 1: الفحص السريري والتشخيص -->
                    <div id="examinationCollapse" class="workstation-panel show">
                        <form action="{{ route('doctor.visits.update', $visit) }}" method="POST" id="examinationDiagnosisForm">
                            @csrf
                            @method('PUT')

                            <!-- العلامات الحيوية -->
                            <div class="mb-4">
                                <h6 class="mb-3 text-primary fw-bold pb-2 border-bottom d-flex align-items-center justify-content-between">
                                    <span><i class="fas fa-heartbeat text-danger me-2"></i>العلامات الحيوية (Vital Signs)</span>
                                    @if($examinationComplete)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle small">مكتملة</span>
                                    @endif
                                </h6>
                                @php
                                    $vitalSigns = $visit->vital_signs ?? [];
                                @endphp
                                <div class="row g-2">
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <label class="form-label small fw-semibold">
                                            <i class="fas fa-tint text-danger me-1"></i>ضغط الدم الانقباضي
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" name="vital_signs[blood_pressure_systolic]" value="{{ old('vital_signs.blood_pressure_systolic', $vitalSigns['blood_pressure_systolic'] ?? '') }}" placeholder="120">
                                            <span class="input-group-text">mmHg</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <label class="form-label small fw-semibold">
                                            <i class="fas fa-tint text-info me-1"></i>ضغط الدم الانبساطي
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" name="vital_signs[blood_pressure_diastolic]" value="{{ old('vital_signs.blood_pressure_diastolic', $vitalSigns['blood_pressure_diastolic'] ?? '') }}" placeholder="80">
                                            <span class="input-group-text">mmHg</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <label class="form-label small fw-semibold">
                                            <i class="fas fa-heart text-danger me-1"></i>النبض (Pulse)
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" name="vital_signs[heart_rate]" value="{{ old('vital_signs.heart_rate', $vitalSigns['heart_rate'] ?? '') }}" placeholder="72">
                                            <span class="input-group-text">bpm</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <label class="form-label small fw-semibold">
                                            <i class="fas fa-thermometer-half text-warning me-1"></i>الحرارة (Temp)
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.1" class="form-control" name="vital_signs[temperature]" value="{{ old('vital_signs.temperature', $vitalSigns['temperature'] ?? '') }}" placeholder="36.5">
                                            <span class="input-group-text">°C</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <label class="form-label small fw-semibold">
                                            <i class="fas fa-wind text-primary me-1"></i>معدل التنفس (Resp)
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" name="vital_signs[respiratory_rate]" value="{{ old('vital_signs.respiratory_rate', $vitalSigns['respiratory_rate'] ?? '') }}" placeholder="16">
                                            <span class="input-group-text">rpm</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <label class="form-label small fw-semibold">
                                            <i class="fas fa-lungs text-success me-1"></i>الأكسجين (SpO2)
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" name="vital_signs[oxygen_saturation]" value="{{ old('vital_signs.oxygen_saturation', $vitalSigns['oxygen_saturation'] ?? '') }}" placeholder="98">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-6 mb-2">
                                        <label class="form-label small fw-semibold">
                                            <i class="fas fa-weight text-secondary me-1"></i>الوزن (Weight)
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.1" class="form-control" name="vital_signs[weight]" value="{{ old('vital_signs.weight', $vitalSigns['weight'] ?? '') }}" placeholder="70.5">
                                            <span class="input-group-text">kg</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-6 mb-2">
                                        <label class="form-label small fw-semibold">
                                            <i class="fas fa-ruler-vertical text-secondary me-1"></i>الطول (Height)
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.1" class="form-control" name="vital_signs[height]" value="{{ old('vital_signs.height', $vitalSigns['height'] ?? '') }}" placeholder="170">
                                            <span class="input-group-text">cm</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- الفحص السريري والشكوى -->
                            <div class="mb-4">
                                <h6 class="mb-3 text-primary fw-bold pb-2 border-bottom">
                                    <i class="fas fa-notes-medical text-primary me-2"></i>الشكوى والفحص السريري (Physical Examination)
                                </h6>
                                <div class="form-floating mb-3">
                                    <textarea class="form-control" name="physical_examination" id="physical_examination" style="height: 100px;" placeholder="اكتب نتائج الفحص السريري والشكوى الرئيسية...">{{ old('physical_examination', $visit->physical_examination ?? '') }}</textarea>
                                    <label for="physical_examination">الشكوى الرئيسية والفحص السريري</label>
                                </div>
                            </div>

                            <!-- التشخيص ICD-10 -->
                            <div class="mb-4">
                                <h6 class="mb-3 text-primary fw-bold pb-2 border-bottom d-flex align-items-center justify-content-between">
                                    <span><i class="fas fa-stethoscope text-info me-2"></i>التشخيص الطبي (ICD-10 Diagnosis)</span>
                                    @if($diagnosisComplete)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle small">مكتمل</span>
                                    @endif
                                </h6>
                                @php
                                    $diagnosisData = $visit->diagnosis ?? [];
                                @endphp
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">رمز أو وصف التشخيص (ICD-10)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-primary text-white">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text"
                                                   class="form-control diagnosis-input"
                                                   id="diagnosis_code"
                                                   name="diagnosis[code]"
                                                   placeholder="اكتب رمز أو وصف التشخيص..."
                                                   value="{{ old('diagnosis.code', $diagnosisData['code'] ?? '') }}"
                                                   autocomplete="off"
                                                   list="icd10-list"
                                                   title="ابدأ الكتابة للبحث في رموز ICD-10">
                                            <input type="hidden" id="diagnosis_code_hidden" name="diagnosis[actual_code]" value="{{ old('diagnosis.actual_code', $diagnosisData['actual_code'] ?? $diagnosisData['code'] ?? '') }}">
                                            <datalist id="icd10-list">
                                                @foreach($icd10Codes as $code)
                                                    <option value="{{ $code->code }} - {{ $code->description_ar ?: $code->description }}" data-code="{{ $code->code }}" data-search="{{ $code->code }} {{ $code->description_ar ?: '' }} {{ $code->description }}">
                                                @endforeach
                                                <option value="أخرى (أدخل يدوياً)" data-code="other" data-search="other أخرى يدوياً">
                                            </datalist>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="custom-code-container {{ old('diagnosis.code', $diagnosisData['code'] ?? '') == 'other' ? '' : 'd-none' }} mb-2">
                                            <input type="text" class="form-control" name="diagnosis[custom_code]" id="custom_code" placeholder="أدخل رمز ICD مخصص" value="{{ old('diagnosis.custom_code', $diagnosisData['custom_code'] ?? '') }}">
                                        </div>
                                        <div class="form-floating">
                                            <textarea class="form-control" name="diagnosis[description]" id="diagnosis_description" style="height: 80px;" placeholder="تفاصيل وملاحظات التشخيص">{{ old('diagnosis.description', $diagnosisData['description'] ?? '') }}</textarea>
                                            <label for="diagnosis_description">تفاصيل وملاحظات التشخيص</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- زر الحفظ -->
                            <div class="d-flex justify-content-end pt-3 border-top">
                                <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm">
                                    <i class="fas fa-save me-1"></i> حفظ الفحص والتشخيص
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- تاب 2: تحاليل المختبر -->
                    <div id="labCollapse" class="workstation-panel" style="display: none;">
                        <form id="doctorLabRequestForm" action="{{ route('doctor.requests.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="visit_id" value="{{ $visit->id }}">
                            <input type="hidden" name="type" value="lab">
                            <input type="hidden" name="priority" value="normal">
                            
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary p-2 rounded-circle fs-6">
                                        <i class="fas fa-microscope"></i>
                                    </span>
                                    <div>
                                        <h5 class="mb-0 fw-bold text-primary">طلب فحوصات المختبر</h5>
                                        <small class="text-muted">ابحث واضغط Enter أو اختر من الباقات والمجموعات التخصصية</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="{{ route('lab-tests.groups.index') }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="إدارة باقاتي ومفضلاتي">
                                        <i class="fas fa-cog me-1"></i> إدارة باقات المفضلات
                                    </a>
                                    <button type="submit" id="btnSubmitLabRequest" class="btn btn-primary btn-sm px-3 shadow-sm fw-bold">
                                        <i class="fas fa-paper-plane me-1"></i> إرسال الطلب (<span class="doc-lab-selected-count">0</span>)
                                    </button>
                                </div>
                            </div>

                            <!-- 1. شريط باقات المفضلات السريعة بنقرة واحدة -->
                            @if(isset($labTestGroups) && $labTestGroups->isNotEmpty())
                                <div class="mb-3 p-2 bg-white rounded-3 border">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="small fw-bold text-dark">
                                            <i class="fas fa-bolt text-warning me-1"></i> باقاتي السريعة (نقرة واحدة للإضافة):
                                        </span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($labTestGroups as $pkg)
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 doc-lab-quick-pkg-btn" 
                                                    data-tests="{{ json_encode($pkg->tests) }}"
                                                    data-pkg-name="{{ $pkg->name }}">
                                                <i class="fas fa-layer-group me-1 opacity-75"></i>
                                                <strong>{{ $pkg->name }}</strong>
                                                <span class="badge bg-primary-subtle text-primary rounded-pill ms-1">{{ count($pkg->tests ?? []) }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- 2. حقل البحث الذكي بنمط Tag Autocomplete -->
                            <div class="mb-3 position-relative">
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-primary text-white">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" 
                                           id="docLabSearchInput" 
                                           class="form-control form-control-lg fs-6" 
                                           placeholder="اكتب اسم التحليل (مثل: CBC, TSH, Lipid, Glucose...) واضغط Enter..."
                                           autocomplete="off">
                                    <button type="button" class="btn btn-outline-secondary" id="docLabClearSearchBtn" title="مسح البحث">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div id="docLabAutocompleteMenu" class="dropdown-menu w-100 shadow-lg p-1 border-0" style="max-height: 280px; overflow-y: auto; display: none; position: absolute; z-index: 1050;"></div>
                            </div>

                            <!-- 3. صينية التحاليل المختارة حالياً (Selected Chips Tray) -->
                            <div id="docLabSelectedTray" class="mb-3 p-3 bg-light rounded-3 border d-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-bold text-primary">
                                        <i class="fas fa-check-circle me-1"></i> التحاليل المختارة للإرسال (<span class="doc-lab-selected-count">0</span>):
                                    </span>
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none" id="docLabClearAllBtn">
                                        <i class="fas fa-trash-alt me-1"></i> إفراغ الكل
                                    </button>
                                </div>
                                <div class="d-flex flex-wrap gap-2" id="docLabChipsContainer"></div>
                            </div>

                            <!-- 4. تبويبات تصفية الأقسام حسب المجموعات الطبية -->
                            @php
                                $catMap = [
                                    'biochemistry' => '🧪 كيمياء حيوية',
                                    'hormone' => '🧬 هرمونات',
                                    'hematology' => '🩸 أمراض الدم',
                                    'haematology' => '🩸 أمراض الدم',
                                    'immunity' => '🛡️ المناعة',
                                    'immunology' => '🛡️ المناعة',
                                    'serology' => '💉 الأمصال',
                                    'infectious disease' => '🦠 أمراض معدية',
                                    'microbiology' => '🔬 أحياء مجهرية',
                                    'virology' => '🧫 فيروسات',
                                ];

                                $grouped = $labTests->groupBy(function($t) use ($catMap) {
                                    $sub = strtolower(trim($t->subcategory ?? ''));
                                    return $catMap[$sub] ?? ($t->subcategory ?: ($t->main_category ?: 'تحاليل عامة'));
                                });
                            @endphp
                            <div class="d-flex align-items-center gap-1 overflow-x-auto pb-2 mb-3 border-bottom" id="docLabCategoryPills" style="white-space: nowrap;">
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1 doc-cat-pill active" data-category="ALL">
                                    الكل <span class="badge bg-white text-primary ms-1">{{ $labTests->count() }}</span>
                                </button>
                                @foreach($grouped as $category => $tests)
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 doc-cat-pill" data-category="{{ $category }}">
                                        {{ $category }} <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $tests->count() }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <!-- 5. شبكة بطاقات التحاليل (Compact Grid) -->
                            <div id="docLabTestsGridContainer" style="max-height: 420px; overflow-y: auto; padding-right: 4px;">
                                <div class="row g-2" id="docLabItemsGrid">
                                    @foreach($labTests as $test)
                                        @php
                                            $testCat = $catMap[strtolower(trim($test->subcategory ?? ''))] ?? ($test->subcategory ?: ($test->main_category ?: 'تحاليل عامة'));
                                        @endphp
                                        <div class="col-md-6 col-12 doc-lab-col" 
                                             data-test-name="{{ strtolower($test->name) }}"
                                             data-test-code="{{ strtolower($test->code ?? '') }}"
                                             data-category="{{ $testCat }}"
                                             data-id="{{ $test->id }}">
                                            <label for="inline_test_{{ $test->id }}" 
                                                   class="doc-lab-card p-2 rounded-3 border bg-white d-flex align-items-center justify-content-between h-100 mb-0 w-100 user-select-none" 
                                                   style="cursor: pointer; transition: all 0.15s ease;">
                                                <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
                                                    <input class="form-check-input doc-lab-chk flex-shrink-0 m-0" 
                                                           type="checkbox" 
                                                           name="tests[]" 
                                                           value="{{ $test->name }}" 
                                                           id="inline_test_{{ $test->id }}" 
                                                           data-test-id="{{ $test->id }}"
                                                           data-test-name="{{ $test->name }}"
                                                           data-test-code="{{ $test->code ?? '' }}"
                                                           data-test-category="{{ $testCat }}"
                                                           style="cursor: pointer; width: 1.1em; height: 1.1em;">
                                                    <div class="text-truncate">
                                                        <span class="fw-semibold text-dark small text-truncate d-block" title="{{ $test->name }}">{{ $test->name }}</span>
                                                        <div class="d-flex align-items-center gap-1">
                                                            @if($test->code)
                                                                <span class="badge bg-light text-muted border px-1 py-0 font-monospace" style="font-size: 0.68rem;">{{ $test->code }}</span>
                                                            @endif
                                                            <span class="text-muted" style="font-size: 0.68rem;">{{ Str::limit($testCat, 20) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="doc-lab-check-icon text-primary ms-1 d-none">
                                                    <i class="fas fa-check-circle"></i>
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- تاب 3: الأشعة والتصوير -->
                    <div id="radiologyCollapse" class="workstation-panel" style="display: none;">
                        <form id="doctorRadiologyRequestForm" action="{{ route('doctor.requests.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="visit_id" value="{{ $visit->id }}">
                            <input type="hidden" name="type" value="radiology">
                            <input type="hidden" name="priority" value="normal">
                            
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-info-subtle text-info p-2 rounded-circle fs-6">
                                        <i class="fas fa-x-ray"></i>
                                    </span>
                                    <div>
                                        <h5 class="mb-0 fw-bold text-info">طلب فحوصات الأشعة والتصوير</h5>
                                        <small class="text-muted">ابحث واضغط Enter أو اختر من الأقسام والتصنيفات (أشعة، سونار، رنين، مفراس)</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="submit" id="btnSubmitRadiologyRequest" class="btn btn-info text-white btn-sm px-3 shadow-sm fw-bold">
                                        <i class="fas fa-paper-plane me-1"></i> إرسال الطلب (<span class="doc-rad-selected-count">0</span>)
                                    </button>
                                </div>
                            </div>

                            @if(isset($radiologyTypes) && $radiologyTypes->count() > 0)
                                <!-- 1. حقل البحث الذكي بنمط Tag Autocomplete -->
                                <div class="mb-3 position-relative">
                                    <div class="input-group shadow-sm">
                                        <span class="input-group-text bg-info text-white">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input type="text" 
                                               id="docRadSearchInput" 
                                               class="form-control form-control-lg fs-6" 
                                               placeholder="اكتب اسم فحص الأشعة (مثل: Chest X-Ray, Brain MRI, Abdomen US, CT Scan...) واضغط Enter..."
                                               autocomplete="off">
                                        <button type="button" class="btn btn-outline-secondary" id="docRadClearSearchBtn" title="مسح البحث">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div id="docRadAutocompleteMenu" class="dropdown-menu w-100 shadow-lg p-1 border-0" style="max-height: 280px; overflow-y: auto; display: none; position: absolute; z-index: 1050;"></div>
                                </div>

                                <!-- 2. صينية الفحوصات المختارة حالياً (Selected Chips Tray) -->
                                <div id="docRadSelectedTray" class="mb-3 p-3 bg-light rounded-3 border d-none">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small fw-bold text-info">
                                            <i class="fas fa-check-circle me-1"></i> الفحوصات المختارة للإرسال (<span class="doc-rad-selected-count">0</span>):
                                        </span>
                                        <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none" id="docRadClearAllBtn">
                                            <i class="fas fa-trash-alt me-1"></i> إفراغ الكل
                                        </button>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2" id="docRadChipsContainer"></div>
                                </div>

                                <!-- 3. تبويبات تصفية الأقسام والتصوير -->
                                @php
                                    $radCatMap = [
                                        'xray' => '🩻 أشعة سينية (X-Ray)',
                                        'x-ray' => '🩻 أشعة سينية (X-Ray)',
                                        'x_ray' => '🩻 أشعة سينية (X-Ray)',
                                        'أشعة' => '🩻 أشعة سينية (X-Ray)',
                                        'اشعة' => '🩻 أشعة سينية (X-Ray)',
                                        'mri' => '🧲 رنين مغناطيسي (MRI)',
                                        'رنين' => '🧲 رنين مغناطيسي (MRI)',
                                        'رنين مغناطيسي' => '🧲 رنين مغناطيسي (MRI)',
                                        'ct' => '🌀 مفراس حلزوني (CT Scan)',
                                        'ct scan' => '🌀 مفراس حلزوني (CT Scan)',
                                        'ct_scan' => '🌀 مفراس حلزوني (CT Scan)',
                                        'مفراس' => '🌀 مفراس حلزوني (CT Scan)',
                                        'ultrasound' => '🔊 سونار (Ultrasound)',
                                        'u/s' => '🔊 سونار (Ultrasound)',
                                        'us' => '🔊 سونار (Ultrasound)',
                                        'سونار' => '🔊 سونار (Ultrasound)',
                                        'doppler' => '🩺 دوبلر ملون (Doppler)',
                                        'دوبلر' => '🩺 دوبلر ملون (Doppler)',
                                        'echo' => '❤️ إيكو قلب (Echo)',
                                        'إيكو' => '❤️ إيكو قلب (Echo)',
                                        'ايكو' => '❤️ إيكو قلب (Echo)',
                                        'mammogram' => '🎀 ماموجرام (Mammogram)',
                                        'ماموجرام' => '🎀 ماموجرام (Mammogram)',
                                        'fluoroscopy' => '💡 تنظير فلوري (Fluoroscopy)',
                                    ];

                                    $radGrouped = $radiologyTypes->groupBy(function($r) use ($radCatMap) {
                                        $rawCat = strtolower(trim($r->main_category ?? ''));
                                        return $radCatMap[$rawCat] ?? ($r->main_category ?: ($r->subcategory ?: 'فحوصات أشعة عامة'));
                                    });
                                @endphp
                                <div class="d-flex align-items-center gap-1 overflow-x-auto pb-2 mb-3 border-bottom" id="docRadCategoryPills" style="white-space: nowrap;">
                                    <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1 doc-rad-cat-pill active" data-category="ALL">
                                        الكل <span class="badge bg-white text-info ms-1">{{ $radiologyTypes->count() }}</span>
                                    </button>
                                    @foreach($radGrouped as $category => $types)
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 doc-rad-cat-pill" data-category="{{ $category }}">
                                            {{ $category }} <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $types->count() }}</span>
                                        </button>
                                    @endforeach
                                </div>

                                <!-- 4. شبكة بطاقات فحوصات الأشعة (Compact Grid) -->
                                <div id="docRadTestsGridContainer" style="max-height: 420px; overflow-y: auto; padding-right: 4px;">
                                    <div class="row g-2" id="docRadItemsGrid">
                                        @foreach($radiologyTypes as $type)
                                            @php
                                                $rawCat = strtolower(trim($type->main_category ?? ''));
                                                $radCat = $radCatMap[$rawCat] ?? ($type->main_category ?: ($type->subcategory ?: 'فحوصات أشعة عامة'));
                                            @endphp
                                            <div class="col-md-6 col-12 doc-rad-col" 
                                                 data-type-name="{{ strtolower($type->name) }}"
                                                 data-type-code="{{ strtolower($type->code ?? '') }}"
                                                 data-category="{{ $radCat }}"
                                                 data-id="{{ $type->id }}">
                                                <label for="inline_rad_{{ $type->id }}" 
                                                       class="doc-rad-card p-2 rounded-3 border bg-white d-flex align-items-center justify-content-between h-100 mb-0 w-100 user-select-none" 
                                                       style="cursor: pointer; transition: all 0.15s ease;">
                                                    <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
                                                        <input class="form-check-input doc-rad-chk flex-shrink-0 m-0" 
                                                               type="checkbox" 
                                                               name="radiology_types[]" 
                                                               value="{{ $type->id }}" 
                                                               id="inline_rad_{{ $type->id }}" 
                                                               data-type-id="{{ $type->id }}"
                                                               data-type-name="{{ $type->name }}"
                                                               data-type-code="{{ $type->code ?? '' }}"
                                                               data-type-category="{{ $radCat }}"
                                                               style="cursor: pointer; width: 1.1em; height: 1.1em;">
                                                        <div class="text-truncate">
                                                            <span class="fw-semibold text-dark small text-truncate d-block" title="{{ $type->name }}">{{ $type->name }}</span>
                                                            <div class="d-flex align-items-center gap-1">
                                                                @if($type->code)
                                                                    <span class="badge bg-light text-muted border px-1 py-0 font-monospace" style="font-size: 0.68rem;">{{ $type->code }}</span>
                                                                @endif
                                                                <span class="text-muted" style="font-size: 0.68rem;">{{ Str::limit($radCat, 22) }}</span>
                                                                @if($type->requires_contrast)
                                                                    <span class="badge bg-warning-subtle text-danger border border-warning-subtle" style="font-size: 0.62rem;">مع صبغة</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <span class="doc-rad-check-icon text-info ms-1 d-none">
                                                        <i class="fas fa-check-circle"></i>
                                                    </span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    لا توجد فحوصات أشعة متاحة حالياً
                                </div>
                            @endif
                        </form>
                    </div>

                    <!-- تاب 4: الخدمات التمريضية -->
                    <div id="nursingCollapse" class="workstation-panel" style="display: none;">
                        <form action="{{ route('doctor.requests.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="visit_id" value="{{ $visit->id }}">
                            <input type="hidden" name="type" value="nursing">
                            <input type="hidden" name="priority" value="normal">
                            
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success-subtle text-success p-2 rounded-circle fs-6">
                                        <i class="fas fa-syringe"></i>
                                    </span>
                                    <div>
                                        <h5 class="mb-0 fw-bold text-success">طلب الخدمات التمريضية والإجراءات</h5>
                                        <small class="text-muted">اختر الإجراءات التمريضية (حقن، تبخيرة، تضميد، سوائل وريدية...)</small>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm px-3 shadow-sm fw-bold">
                                    <i class="fas fa-paper-plane me-1"></i> إرسال الخدمات التمريضية
                                </button>
                            </div>
                            
                            <!-- حقل البحث -->
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-search text-success"></i>
                                    </span>
                                    <input type="text" id="nursingSearchInput" class="form-control" placeholder="ابحث عن خدمة تمريضية...">
                                    <button type="button" id="nursingSearchBtn" class="btn btn-success">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div id="nursingServicesContainer" style="max-height: 420px; overflow-y: auto;">
                                @forelse($emergencyServices as $category => $services)
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
                                            <h6 class="mb-0 text-success fw-bold">
                                                <i class="fas fa-heartbeat me-2"></i>{{ $category ?: 'خدمات أخرى' }}
                                                <span class="badge bg-success ms-2">{{ count($services) }}</span>
                                            </h6>
                                        </div>
                                        <div class="list-group">
                                            @foreach($services as $service)
                                                <label class="list-group-item list-group-item-action d-flex align-items-center nursing-service-item" data-service-name="{{ strtolower($service->name) }}" style="cursor: pointer; padding: 8px 12px; border-left: 3px solid #28a745;">
                                                    <input class="form-check-input me-3 flex-shrink-0" type="checkbox" name="nursing_services[]" value="{{ $service->id }}" id="nursing_service_{{ $service->id }}" style="width: 18px; height: 18px; cursor: pointer;">
                                                    <div class="flex-grow-1">
                                                        <span class="fw-semibold text-dark" style="font-size: 0.92rem;">{{ $service->name }}</span>
                                                        <br><small class="text-muted">السعر: {{ $service->price }} ر.س</small>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <div class="alert alert-warning">
                                        <i class="fas fa-info-circle me-2"></i>
                                        لا توجد خدمات تمريضية متاحة حالياً
                                    </div>
                                @endforelse
                            </div>
                            
                            <div class="alert alert-info mt-3" id="selectedNursingCount" style="display: none;">
                                <i class="fas fa-check-circle me-2"></i>
                                تم اختيار <strong id="nursingCountNumber">0</strong> خدمة تمريضية
                            </div>
                        </form>
                    </div>

                    <!-- تاب 5: الوصفة والعلاج -->
                    <div id="treatmentCollapse" class="workstation-panel" style="display: none;">
                        @php
                            $prescribedMedications = $visit->prescribedMedications->where('item_type', 'medication');
                            $otherTreatments = $visit->prescribedMedications->where('item_type', 'treatment');
                        @endphp

                        <form action="{{ route('doctor.visits.update', $visit->id) }}" method="POST" id="treatmentForm">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="is_prescription_form" value="1">

                            <!-- لوحة تنبيهات طلبات استبدال الأدوية الواردة من الصيدلية -->
                            <div id="liveSubstitutionAlertsContainer" class="mb-4">
                                @if(isset($pendingSubstitutionRequests) && $pendingSubstitutionRequests->count() > 0)
                                    @foreach($pendingSubstitutionRequests as $subReq)
                                        <div class="alert alert-warning border-2 border-warning shadow-sm rounded-4 p-3 mb-3 substitution-alert-card" id="subAlert-{{ $subReq->id }}">
                                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-warning text-dark p-3 rounded-circle fs-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                        <i class="fas fa-exchange-alt fa-bounce"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                                            <span>🔔 إشعار من الصيدلية: مقترح بديل دوائي</span>
                                                            <span class="badge bg-danger rounded-pill px-2 py-1 small">بانتظار قرارك</span>
                                                        </h6>
                                                        <div class="text-dark small mb-1">
                                                            الدواء المطلوب: <strong class="text-danger text-decoration-line-through">{{ $subReq->medicine?->name ?? $subReq->medicine_name }}</strong>
                                                            <i class="fas fa-arrow-left mx-2 text-primary"></i>
                                                            البديل المقترح: <strong class="text-success fs-6">{{ $subReq->suggestedMedicine?->name ?? 'دواء بديل' }}</strong>
                                                            <span class="text-muted">({{ $subReq->suggestedMedicine?->dosage_form }} - {{ $subReq->suggestedMedicine?->strength }})</span>
                                                        </div>
                                                        <div class="small text-secondary">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            <span>توضيح الصيدلية: {{ $subReq->substitution_reason ?? 'عدم توفر الصنف الأصلي حالياً' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button type="button" class="btn btn-success fw-bold px-3 py-2 shadow-sm rounded-3 btn-approve-sub" onclick="respondToSubstitution({{ $subReq->id }}, 'approve')">
                                                        <i class="fas fa-check-circle me-1"></i> موافقة واعتماد البديل
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger fw-bold px-3 py-2 rounded-3 btn-reject-sub" onclick="respondToSubstitution({{ $subReq->id }}, 'reject')">
                                                        <i class="fas fa-times-circle me-1"></i> رفض البديل
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <!-- قسم الأدوية والوصفة الطبية الإلكترونية -->
                            <div class="card border-success mb-4 shadow-sm">
                                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h5 class="mb-0 fs-6">
                                        <i class="fas fa-prescription me-2"></i>
                                        الأدوية الموصوفة (الوصفة الطبية الإلكترونية E-Prescription)
                                    </h5>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-warning btn-sm text-dark fw-bold" onclick="window.openSaveAsPkgModal()" title="حفظ الأدوية المكتوبة حالياً كباقة سريعة جديدة">
                                            <i class="fas fa-save me-1"></i> حفظ كباقة سريعة
                                        </button>
                                        <a href="{{ route('medicine-groups.index') }}" target="_blank" class="btn btn-light btn-sm text-success" title="إدارة باقات أدويتي المفضلة">
                                            <i class="fas fa-cog me-1"></i> إدارة الباقات
                                        </a>
                                        <button type="button" class="btn btn-light btn-sm text-success fw-bold" onclick="addMedication()">
                                            <i class="fas fa-plus me-1"></i>
                                            إضافة دواء
                                        </button>
                                        <a href="{{ route('doctor.visits.prescription.print', $visit->id) }}" target="_blank" class="btn btn-outline-light btn-sm">
                                            <i class="fas fa-print me-1"></i>
                                            طباعة (RX)
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="docQuickPkgWrapper" class="mb-3 p-2 bg-light rounded-3 border" style="{{ (isset($medicineGroups) && $medicineGroups->isNotEmpty()) ? '' : 'display:none;' }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="small fw-bold text-dark d-flex align-items-center gap-1">
                                                <i class="fas fa-bolt text-warning"></i> باقاتي السريعة (تثبيت المفضلة بالصدارة ⭐ | نقرة لإدراج الأدوية والترتيب الذكي 🔥)
                                            </span>
                                            <a href="{{ route('medicine-groups.index') }}" target="_blank" class="text-decoration-none small text-muted">
                                                <i class="fas fa-cog me-1"></i> إدارة الباقات
                                            </a>
                                        </div>
                                        <div id="docQuickPkgContainer" class="d-flex flex-wrap gap-2 py-1 align-items-center">
                                            @if(isset($medicineGroups) && $medicineGroups->isNotEmpty())
                                                @foreach($medicineGroups as $pkg)
                                                    <div class="doc-pkg-chip-item d-inline-flex align-items-center btn btn-sm {{ $pkg->is_starred ? 'btn-warning border-warning text-dark fw-bold shadow-sm' : ($pkg->is_public ? 'btn-outline-primary border-primary' : 'btn-outline-success border-success') }} rounded-pill p-1 pe-3 transition-all"
                                                         data-id="{{ $pkg->id }}"
                                                         data-is-starred="{{ $pkg->is_starred ? '1' : '0' }}"
                                                         data-usage="{{ $pkg->usage_count ?? 0 }}"
                                                         data-meds="{{ $pkg->medicines->map(function($m) {
                                                             return [
                                                                 'medicine_id' => $m->id,
                                                                 'name' => $m->name,
                                                                 'type' => $m->pivot->dosage_form,
                                                                 'dosage' => $m->pivot->dosage,
                                                                 'frequency' => $m->pivot->frequency,
                                                                 'duration' => $m->pivot->duration,
                                                                 'instructions' => $m->pivot->instructions,
                                                             ];
                                                         })->toJson() }}"
                                                         data-pkg-name="{{ $pkg->name }}"
                                                         title="نقرة لإضافة الأدوية | اضغط النجمة لتثبيت في الصدارة ⭐">
                                                        
                                                        <!-- زر تثبيت النجمة الذهبية -->
                                                        <button type="button" 
                                                                class="btn btn-sm btn-link p-0 me-2 text-decoration-none pkg-star-btn {{ $pkg->is_starred ? 'text-warning' : 'text-muted opacity-50' }}"
                                                                onclick="event.stopPropagation(); window.togglePkgStar(this, {{ $pkg->id }});"
                                                                title="{{ $pkg->is_starred ? 'إلغاء التثبيت من المفضلة' : 'تثبيت في الصدارة ⭐' }}">
                                                            <i class="{{ $pkg->is_starred ? 'fas fa-star text-dark' : 'far fa-star' }} fs-6"></i>
                                                        </button>

                                                        <!-- محتوى الباقة القابل للنقر -->
                                                        <span class="pkg-content-click d-inline-flex align-items-center cursor-pointer" onclick="window.applyMedPkg(this.closest('.doc-pkg-chip-item'), {{ $pkg->id }})">
                                                            @if($pkg->is_public)
                                                                <i class="fas fa-hospital {{ $pkg->is_starred ? 'text-dark' : 'text-primary' }} me-1"></i>
                                                                <strong>{{ $pkg->name }}</strong>
                                                                <span class="badge {{ $pkg->is_starred ? 'bg-dark text-white' : 'bg-primary text-white' }} rounded-pill ms-1">{{ $pkg->medicines->count() }} (عامة)</span>
                                                            @else
                                                                <i class="fas fa-layer-group {{ $pkg->is_starred ? 'text-dark' : 'text-success' }} me-1 opacity-75"></i>
                                                                <strong>{{ $pkg->name }}</strong>
                                                                <span class="badge {{ $pkg->is_starred ? 'bg-dark text-white' : 'bg-success-subtle text-success' }} rounded-pill ms-1">{{ $pkg->medicines->count() }}</span>
                                                            @endif

                                                            @if(($pkg->usage_count ?? 0) > 0)
                                                                <span class="badge bg-light text-dark border rounded-pill ms-1 pkg-usage-badge font-monospace" style="font-size: 0.7rem;" title="عدد مرات الاستخدام">
                                                                    <i class="fas fa-fire text-danger"></i> <span class="usage-num">{{ $pkg->usage_count }}</span>
                                                                </span>
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                    <div id="medicationsContainer">
                                        @if(count($prescribedMedications) > 0)
                                            @foreach($prescribedMedications as $index => $medication)
                                            <div class="medication-item card mb-3 border-success">
                                                <div class="card-body p-3">
                                                    <div class="row g-2">
                                                        <div class="col-md-5">
                                                            <label class="form-label small fw-bold">
                                                                <i class="fas fa-pills text-success me-1"></i>
                                                                اسم الدواء
                                                            </label>
                                                            <select class="form-select form-select-sm medicine-select2" data-index="{{ $index }}" onchange="handleMedicineSelect(this)">
                                                                <option value="">-- ابحث بالاسم التجاري أو العلمي --</option>
                                                                @php $matched = false; @endphp
                                                                @if(isset($availableMedicines))
                                                                    @foreach($availableMedicines as $availMed)
                                                                        @php
                                                                            $isSel = ($medication->name == $availMed->name || (isset($medication->medicine_id) && $medication->medicine_id == $availMed->id));
                                                                            if ($isSel) $matched = true;
                                                                        @endphp
                                                                        <option value="{{ $availMed->id }}" 
                                                                                data-id="{{ $availMed->id }}"
                                                                                data-name="{{ $availMed->name }}"
                                                                                data-generic="{{ $availMed->generic_name }}"
                                                                                data-strength="{{ $availMed->strength }}"
                                                                                data-form="{{ $availMed->dosage_form }}"
                                                                                {{ $isSel ? 'selected' : '' }}>
                                                                            {{ $availMed->name }} {{ $availMed->strength }} ({{ $availMed->generic_name ?? '' }} - {{ $availMed->dosage_form }})
                                                                        </option>
                                                                    @endforeach
                                                                @endif
                                                                <option value="custom" {{ (!$matched && !empty($medication->name)) ? 'selected' : '' }}>✏️ كتابة اسم دواء يدوي غير مدرج</option>
                                                            </select>
                                                            <input type="hidden" name="prescribed_medications[{{ $index }}][medicine_id]" class="med-id-input" value="{{ $medication->medicine_id ?? '' }}">
                                                            <input type="text" class="form-control form-control-sm med-name-input mt-2" name="prescribed_medications[{{ $index }}][name]"
                                                                   value="{{ $medication->name }}"
                                                                   placeholder="اسم الدواء الموصوف" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label small fw-bold">الشكل الدوائي</label>
                                                            <select class="form-select form-select-sm med-type-select" name="prescribed_medications[{{ $index }}][type]" required>
                                                                <option value="tablet" {{ $medication->type == 'tablet' ? 'selected' : '' }}>حبوب / أقراص</option>
                                                                <option value="injection" {{ $medication->type == 'injection' ? 'selected' : '' }}>إبرة / حقن</option>
                                                                <option value="syrup" {{ $medication->type == 'syrup' ? 'selected' : '' }}>شراب</option>
                                                                <option value="cream" {{ $medication->type == 'cream' ? 'selected' : '' }}>كريم / مرهم</option>
                                                                <option value="drops" {{ $medication->type == 'drops' ? 'selected' : '' }}>قطرات</option>
                                                                <option value="other" {{ $medication->type == 'other' ? 'selected' : '' }}>أخرى</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label small fw-bold">الجرعة / القوة</label>
                                                            <input type="text" class="form-control form-control-sm med-dosage-input" name="prescribed_medications[{{ $index }}][dosage]"
                                                                   value="{{ $medication->dosage }}"
                                                                   placeholder="مثال: 500mg" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label small fw-bold d-block mb-1">التكرار يومياً</label>
                                                            <div class="frequency-selector" style="display: flex; gap: 3px; flex-wrap: wrap;">
                                                                @foreach(['1' => '1x', '2' => '2x', '3' => '3x', '4' => '4x', 'as_needed' => 'حاجة'] as $value => $label)
                                                                <input type="radio" id="freq_{{ $index }}_{{ $value }}" name="prescribed_medications[{{ $index }}][frequency]" value="{{ $value }}" {{ $medication->frequency == $value ? 'checked' : '' }} style="display: none;">
                                                                <label for="freq_{{ $index }}_{{ $value }}" class="frequency-btn" style="padding: 2px 6px; border: 1px solid #ced4da; border-radius: 4px; cursor: pointer; font-size: 0.75rem; background: white;">{{ $label }}</label>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                        <div class="col-md-1 d-flex align-items-end justify-content-center">
                                                            <button type="button" class="btn btn-outline-danger btn-sm btn-remove-medication" onclick="window.removeMedication(this); return false;" title="حذف الدواء">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="row g-2 mt-1">
                                                        <div class="col-md-2">
                                                            <label class="form-label text-muted small fw-bold">المدة</label>
                                                            <input type="text" class="form-control form-control-sm" name="prescribed_medications[{{ $index }}][duration]"
                                                                   value="{{ $medication->duration }}"
                                                                   placeholder="7 أيام">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label text-muted small fw-bold">التوقيت</label>
                                                            <input type="text" class="form-control form-control-sm" name="prescribed_medications[{{ $index }}][times]"
                                                                   value="{{ $medication->times }}"
                                                                   placeholder="بعد الأكل...">
                                                        </div>
                                                        <div class="col-md-7">
                                                            <label class="form-label text-muted small fw-bold">تعليمات وتوصيات خاصة</label>
                                                            <input type="text" class="form-control form-control-sm" name="prescribed_medications[{{ $index }}][instructions]"
                                                                   value="{{ $medication->instructions }}"
                                                                   placeholder="يؤخذ مع كوب ماء وفير...">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        @else
                                            <div id="noMedicationsNotice" class="text-center py-4 text-muted">
                                                <i class="fas fa-pills fa-2x mb-2 text-success opacity-25"></i>
                                                <p class="mb-1 small">لا توجد أدوية موصوفة بعد</p>
                                                <small class="text-muted">اضغط على "إضافة دواء" أعلاه لبدء إضافة الأدوية</small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end align-items-center mt-3">
                                <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm">
                                    <i class="fas fa-save me-1"></i> حفظ خطة العلاج والوصفة
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. السجل الطبي الدائم ونتائج الفحوصات (العمود الأيسر الدائم) -->
        <div class="col-12 col-xl-5 col-lg-5">
            <div class="card shadow-sm border-0 sticky-top" style="top: 80px; z-index: 10;">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-notes-medical text-info"></i>
                        <h6 class="mb-0 fw-bold">السجل الطبي ونتائج الفحوصات</h6>
                    </div>
                    @if($visit->patient)
                        <a href="{{ route('doctor.patient.history', $visit->patient) }}" target="_blank" class="btn btn-xs btn-outline-info text-white py-1 px-2" style="font-size: 0.78rem;">
                            <i class="fas fa-external-link-alt me-1"></i> السجل الزمني الكامل
                        </a>
                    @endif
                </div>

                <!-- أزرار التبديل الداخلية للسجل الطبي -->
                <div class="bg-light p-2 border-bottom">
                    <ul class="nav nav-pills nav-fill gap-1" id="historySubTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active py-1 px-2 small fw-semibold" id="side-today-tab" data-bs-toggle="pill" data-bs-target="#side-today-pane" type="button" role="tab">
                                <i class="fas fa-clipboard-check me-1"></i> طلبات اليوم
                                <span class="badge bg-primary ms-1">{{ $visit->requests->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1 px-2 small fw-semibold" id="side-visits-tab" data-bs-toggle="pill" data-bs-target="#side-visits-pane" type="button" role="tab">
                                <i class="fas fa-history me-1"></i> الزيارات السابقة
                                <span class="badge bg-secondary ms-1">{{ isset($pastVisits) ? $pastVisits->count() : 0 }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1 px-2 small fw-semibold" id="side-surgeries-tab" data-bs-toggle="pill" data-bs-target="#side-surgeries-pane" type="button" role="tab">
                                <i class="fas fa-procedures me-1"></i> العمليات/الطوارئ
                                <span class="badge bg-danger ms-1">{{ (isset($pastSurgeries) ? $pastSurgeries->count() : 0) + (isset($pastEmergencies) ? $pastEmergencies->count() : 0) }}</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-3 overflow-y-auto" style="max-height: calc(100vh - 200px); min-height: 480px;">
                    <div class="tab-content" id="sideHistoryContent">
                        
                        <!-- محتوى 1: طلبات ونتائج اليوم -->
                        <div class="tab-pane fade show active" id="side-today-pane" role="tabpanel">
                            @php
                                $hasPrescriptions = (isset($prescribedMedications) && $prescribedMedications->count() > 0) || (isset($latestPrescription) && $latestPrescription && $latestPrescription->items->count() > 0);
                            @endphp
                            @if($visit->requests->count() > 0 || $hasPrescriptions)
                                <div class="d-flex flex-column gap-3">
                                    {{-- بطاقة الوصفة الطبية الإلكترونية وصرف الصيدلية --}}
                                    @if($hasPrescriptions)
                                        <div class="card border border-success shadow-sm rounded-3 overflow-hidden">
                                            <div class="card-header py-2 px-3 bg-success-subtle border-success-subtle d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-prescription me-1"></i> الوصفة الطبية (RX)
                                                    </span>
                                                    @if($latestPrescription && $latestPrescription->prescription_number)
                                                        <small class="text-dark font-monospace fw-bold">{{ $latestPrescription->prescription_number }}</small>
                                                    @endif
                                                    @if($latestPrescription && $latestPrescription->created_at)
                                                        <small class="text-muted font-monospace">{{ $latestPrescription->created_at->format('H:i') }}</small>
                                                    @endif
                                                </div>
                                                <div class="d-flex align-items-center gap-1">
                                                    @if($latestPrescription && $latestPrescription->status === 'dispensed')
                                                        <span class="badge bg-success" style="font-size: 0.72rem;">
                                                            <i class="fas fa-check-double me-1"></i> تم الصرف بالصيدلية
                                                        </span>
                                                    @elseif($latestPrescription && $latestPrescription->status === 'partially_dispensed')
                                                        <span class="badge bg-warning text-dark" style="font-size: 0.72rem;">
                                                            <i class="fas fa-hourglass-half me-1"></i> صرف جزئي
                                                        </span>
                                                    @elseif($latestPrescription && $latestPrescription->status === 'cancelled')
                                                        <span class="badge bg-danger" style="font-size: 0.72rem;">
                                                            <i class="fas fa-times-circle me-1"></i> ملغية
                                                        </span>
                                                    @else
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.72rem;">
                                                            <i class="fas fa-clock me-1"></i> بانتظار الصرف بالصيدلية
                                                        </span>
                                                    @endif

                                                    <a href="{{ route('doctor.visits.prescription.print', $visit->id) }}" target="_blank" class="btn btn-xs btn-outline-success py-0 px-2 fw-bold" style="font-size: 0.72rem;" title="طباعة الروشتة">
                                                        <i class="fas fa-print me-1"></i> طباعة
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="card-body p-2 p-md-3 bg-white">
                                                <div class="d-flex flex-column gap-2">
                                                    @php
                                                        $medList = ($latestPrescription && $latestPrescription->items->count() > 0)
                                                            ? $latestPrescription->items
                                                            : $prescribedMedications;
                                                    @endphp
                                                    @foreach($medList as $medItem)
                                                        @php
                                                            $medName = $medItem->medicine_name ?? ($medItem->name ?? ($medItem->medicine->name ?? 'دواء'));
                                                            $medDosage = $medItem->dosage_frequency ?? ($medItem->dosage ?? '');
                                                            $medDuration = !empty($medItem->duration_days) ? ($medItem->duration_days . ' يوم') : ($medItem->duration ?? '');
                                                            $medInstructions = $medItem->instructions ?? '';
                                                            $isDispensed = isset($medItem->dispensed_quantity) && $medItem->dispensed_quantity > 0;
                                                        @endphp
                                                        <div class="p-2 rounded-2 border bg-light d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                                                    <i class="fas fa-pills text-success"></i>
                                                                    <strong class="text-dark small">{{ $medName }}</strong>
                                                                    @if($medDosage)
                                                                        <span class="badge bg-secondary-subtle text-secondary border font-monospace" style="font-size: 0.7rem;">{{ $medDosage }}</span>
                                                                    @endif
                                                                    @if($medDuration)
                                                                        <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.7rem;">{{ $medDuration }}</span>
                                                                    @endif
                                                                </div>
                                                                @if($medInstructions)
                                                                    <small class="text-muted d-block mt-1" style="font-size: 0.72rem;"><i class="fas fa-info-circle me-1"></i> {{ $medInstructions }}</small>
                                                                @endif
                                                            </div>
                                                            @if($isDispensed)
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">
                                                                    <i class="fas fa-check"></i> تم الصرف
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @foreach($visit->requests as $medRequest)
                                        @php
                                            $reqDetails = is_string($medRequest->details) ? json_decode($medRequest->details, true) : ($medRequest->details ?? []);
                                            $hasAttachment = !empty($reqDetails['attachment']);
                                            $attachmentUrl = $hasAttachment ? asset('storage/' . $reqDetails['attachment']) : '';
                                            $isImageAttachment = $hasAttachment && str_starts_with($reqDetails['attachment_mime'] ?? '', 'image/');
                                            $resultData = is_string($medRequest->result) ? json_decode($medRequest->result, true) : ($medRequest->result ?? []);
                                            $testAttachments = $reqDetails['test_attachments'] ?? ($resultData['test_attachments'] ?? []);
                                            if (!is_array($testAttachments)) $testAttachments = [];
                                        @endphp
                                        <div class="card border shadow-sm rounded-3 overflow-hidden">
                                            <div class="card-header py-2 px-3 bg-light d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-{{ $medRequest->type == 'lab' ? 'primary' : ($medRequest->type == 'radiology' ? 'info' : 'success') }}">
                                                        <i class="fas fa-{{ $medRequest->type == 'lab' ? 'microscope' : ($medRequest->type == 'radiology' ? 'x-ray' : 'syringe') }} me-1"></i>
                                                        {{ $medRequest->type_text }}
                                                    </span>
                                                    <small class="text-muted font-monospace">{{ $medRequest->created_at->format('H:i') }}</small>
                                                </div>
                                                <div class="d-flex align-items-center gap-1">
                                                    @if(($medRequest->payment_status ?? 'pending') == 'paid')
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.72rem;">
                                                            <i class="fas fa-check-circle me-1"></i> مدفوع
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning-subtle text-danger border border-warning-subtle" style="font-size: 0.72rem;">
                                                            <i class="fas fa-clock me-1"></i> غير مسدد
                                                        </span>
                                                    @endif
                                                    <span class="badge bg-{{ $medRequest->status_color }}" style="font-size: 0.72rem;">
                                                        {{ $medRequest->status_text }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-body p-2 p-md-3">
                                                <!-- تفاصيل العناصر المطلوبة -->
                                                @php
                                                    $detailItems = [];
                                                    if ($medRequest->type === 'lab') {
                                                        if (!empty($reqDetails['tests']) && is_array($reqDetails['tests'])) {
                                                            $detailItems = $reqDetails['tests'];
                                                        } else {
                                                            $detailItems = [$reqDetails['description'] ?? '-'];
                                                        }
                                                    } elseif ($medRequest->type === 'radiology') {
                                                        if (!empty($reqDetails['radiology_types']) && is_array($reqDetails['radiology_types'])) {
                                                            $detailItems = \App\Models\RadiologyType::whereIn('id', $reqDetails['radiology_types'])->pluck('name')->toArray();
                                                        } else {
                                                            $detailItems = [$reqDetails['description'] ?? '-'];
                                                        }
                                                    } elseif ($medRequest->type === 'nursing') {
                                                        if (!empty($reqDetails['nursing_service_names']) && is_array($reqDetails['nursing_service_names'])) {
                                                            $detailItems = $reqDetails['nursing_service_names'];
                                                        } elseif (!empty($reqDetails['nursing_services']) && is_array($reqDetails['nursing_services'])) {
                                                            $detailItems = \App\Models\EmergencyService::whereIn('id', $reqDetails['nursing_services'])->pluck('name')->toArray();
                                                        } else {
                                                            $detailItems = [$reqDetails['description'] ?? '-'];
                                                        }
                                                    } else {
                                                        $detailItems = [$reqDetails['description'] ?? '-'];
                                                    }
                                                @endphp
                                                
                                                <div class="d-flex flex-wrap gap-1 mb-2">
                                                    @foreach($detailItems as $item)
                                                        <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.8rem;">
                                                            {{ $item }}
                                                        </span>
                                                    @endforeach
                                                </div>

                                                <!-- المرفقات والنتائج إن وجدت -->
                                                @if($medRequest->status == 'completed')
                                                    <!-- تقارير الفحوصات المرفقة لكل فحص -->
                                                    @if(!empty($testAttachments) && count($testAttachments) > 0)
                                                        <div class="d-flex flex-column gap-2 mb-2">
                                                            <div class="small fw-bold text-success d-flex align-items-center gap-1">
                                                                <i class="fas fa-paperclip"></i> تقارير الفحوصات المرفقة ({{ count($testAttachments) }}):
                                                            </div>
                                                            @foreach($testAttachments as $tName => $tAtt)
                                                                @php
                                                                    $tPath = $tAtt['path'] ?? '';
                                                                    $tUrl = $tPath ? asset('storage/' . $tPath) : '';
                                                                    $tMime = $tAtt['mime'] ?? '';
                                                                    $tIsImage = str_starts_with($tMime, 'image/');
                                                                @endphp
                                                                @if($tUrl)
                                                                    <div class="p-2 rounded-3 border bg-success-subtle border-success-subtle d-flex flex-column gap-1">
                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                            <div class="d-flex align-items-center gap-2">
                                                                                <i class="fas {{ $tIsImage ? 'fa-file-image text-primary' : 'fa-file-pdf text-danger' }} fs-5"></i>
                                                                                <div>
                                                                                    <span class="badge bg-success text-white px-2 py-1 font-monospace" style="font-size: 0.75rem;">{{ $tName }}</span>
                                                                                    <small class="text-dark fw-semibold ms-1">{{ $tAtt['name'] ?? 'تقرير جهاز الفحص' }}</small>
                                                                                </div>
                                                                            </div>
                                                                            <div class="d-flex gap-1">
                                                                                <a href="{{ $tUrl }}" target="_blank" class="btn btn-xs btn-success fw-bold px-2 py-1" style="font-size: 0.75rem;">
                                                                                    <i class="fas fa-eye me-1"></i> فتح
                                                                                </a>
                                                                                <a href="{{ $tUrl }}" download class="btn btn-xs btn-outline-secondary px-2 py-1" style="font-size: 0.75rem;">
                                                                                    <i class="fas fa-download"></i>
                                                                                </a>
                                                                            </div>
                                                                        </div>
                                                                        @if($tIsImage)
                                                                            <div class="mt-1 text-center">
                                                                                <a href="{{ $tUrl }}" target="_blank">
                                                                                    <img src="{{ $tUrl }}" alt="{{ $tName }}" style="max-height: 120px; max-width: 100%; object-fit: contain;" class="rounded border shadow-sm">
                                                                                </a>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    <!-- المرفق العام إن وجد -->
                                                    @if($hasAttachment)
                                                        <div class="alert alert-success border border-success p-2 rounded-3 mb-2">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <i class="fas {{ $isImageAttachment ? 'fa-file-image' : 'fa-file-pdf' }} fs-5 text-success"></i>
                                                                    <div class="overflow-hidden">
                                                                        <strong class="text-dark d-block small text-truncate">{{ $reqDetails['attachment_title'] ?? 'تقرير التحاليل العام' }}</strong>
                                                                    </div>
                                                                </div>
                                                                <div class="d-flex gap-1">
                                                                    <a href="{{ $attachmentUrl }}" target="_blank" class="btn btn-xs btn-success fw-bold px-2 py-1" style="font-size: 0.75rem;">
                                                                        <i class="fas fa-eye me-1"></i> فتح
                                                                    </a>
                                                                    <a href="{{ $attachmentUrl }}" download class="btn btn-xs btn-outline-secondary px-2 py-1" style="font-size: 0.75rem;">
                                                                        <i class="fas fa-download"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            @if($isImageAttachment)
                                                                <div class="mt-2 text-center">
                                                                    <a href="{{ $attachmentUrl }}" target="_blank">
                                                                        <img src="{{ $attachmentUrl }}" alt="تقرير ممسوح" style="max-height: 140px; max-width: 100%; object-fit: contain;" class="rounded border shadow-sm">
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    @if(isset($resultData['test_results']) && is_array($resultData['test_results']) && count($resultData['test_results']) > 0)
                                                        <div class="table-responsive mt-2">
                                                            <table class="table table-sm table-bordered mb-0" style="font-size: 0.78rem;">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>الفحص</th>
                                                                        <th>النتيجة</th>
                                                                        <th>الوحدة</th>
                                                                        <th>المرجع</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($resultData['test_results'] as $testName => $testData)
                                                                        @php
                                                                            $value = is_array($testData) ? ($testData['value'] ?? '-') : $testData;
                                                                            $unit = is_array($testData) ? ($testData['unit'] ?? '-') : '-';
                                                                            $reference = is_array($testData) ? ($testData['reference'] ?? ($testData['reference_range'] ?? '-')) : '-';
                                                                            $isAbnormal = is_array($testData) && isset($testData['abnormal']) && $testData['abnormal'];
                                                                            $hasTestAtt = !empty($testAttachments[$testName]['path']);
                                                                            $testAttUrl = $hasTestAtt ? asset('storage/' . $testAttachments[$testName]['path']) : null;
                                                                        @endphp
                                                                        <tr class="{{ $isAbnormal ? 'table-warning' : '' }}">
                                                                            <td>
                                                                                <strong>{{ $testName }}</strong>
                                                                                @if($hasTestAtt)
                                                                                    <a href="{{ $testAttUrl }}" target="_blank" class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none ms-1" title="معاينة تقرير الجهاز المرفق">
                                                                                        <i class="fas fa-paperclip"></i> مرفق
                                                                                    </a>
                                                                                @endif
                                                                            </td>
                                                                            <td>
                                                                                <span class="badge bg-{{ $isAbnormal ? 'warning' : 'success' }} text-dark">{{ $value }}</span>
                                                                            </td>
                                                                            <td>{{ $unit }}</td>
                                                                            <td><small class="text-muted">{{ $reference }}</small></td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @endif

                                                    @if($medRequest->type == 'radiology')
                                                        @php
                                                            $radiologyRequests = \App\Models\RadiologyRequest::where('visit_id', $medRequest->visit_id)->with('result', 'radiologyType')->get();
                                                        @endphp
                                                        @foreach($radiologyRequests as $radReq)
                                                            @if($radReq->result)
                                                                <div class="alert alert-info p-2 rounded-3 mt-2 mb-0 small">
                                                                    <strong>{{ $radReq->radiologyType->name ?? 'الأشعة' }}:</strong>
                                                                    <p class="mb-1 text-dark">{{ $radReq->result->result ?? 'تم الفحص' }}</p>
                                                                    @if(!empty($radReq->result->doctor_notes))
                                                                        <small class="text-muted d-block">ملاحظات الطبيب: {{ $radReq->result->doctor_notes }}</small>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                @elseif($medRequest->status == 'pending')
                                                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                                        <span class="text-muted small"><i class="fas fa-clock me-1"></i> بانتظار استلام العينة أو النتائج</span>
                                                        @if(($medRequest->payment_status ?? 'pending') != 'paid')
                                                            <form action="{{ route('doctor.requests.update', $medRequest) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الطلب؟');">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="status" value="cancelled">
                                                                <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size: 0.72rem;">
                                                                    <i class="fas fa-times me-1"></i> إلغاء
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-clipboard-list fa-3x opacity-25 mb-2"></i>
                                    <h6 class="fw-bold mb-1">لا توجد طلبات طبية مسجلة لهذه الزيارة</h6>
                                    <p class="small mb-0">اختر الفحوصات أو الخدمات من التبويبات أعلاه لإضافتها فوراً.</p>
                                </div>
                            @endif
                        </div>

                        <!-- محتوى 2: الزيارات السابقة -->
                        <div class="tab-pane fade" id="side-visits-pane" role="tabpanel">
                            @if(isset($pastVisits) && $pastVisits->count() > 0)
                                <div class="d-flex flex-column gap-2">
                                    @foreach($pastVisits as $pv)
                                        <div class="card border rounded-3 p-3 bg-white shadow-sm">
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                                <div>
                                                    <strong class="text-dark fs-6">{{ $pv->created_at->format('Y-m-d') }}</strong>
                                                    <small class="text-muted ms-1 font-monospace">({{ $pv->created_at->diffForHumans() }})</small>
                                                </div>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">
                                                    د. {{ $pv->doctor?->user?->name ?? 'طبيب استشاري' }}
                                                </span>
                                            </div>

                                            <!-- العلامات الحيوية السابقة -->
                                            @if(!empty($pv->vital_signs))
                                                <div class="d-flex flex-wrap gap-1 mb-2">
                                                    @if(!empty($pv->vital_signs['blood_pressure_systolic']))
                                                        <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">
                                                            BP: {{ $pv->vital_signs['blood_pressure_systolic'] }}/{{ $pv->vital_signs['blood_pressure_diastolic'] ?? '' }}
                                                        </span>
                                                    @endif
                                                    @if(!empty($pv->vital_signs['heart_rate']))
                                                        <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">
                                                            HR: {{ $pv->vital_signs['heart_rate'] }} bpm
                                                        </span>
                                                    @endif
                                                    @if(!empty($pv->vital_signs['temperature']))
                                                        <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">
                                                            Temp: {{ $pv->vital_signs['temperature'] }}°C
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif

                                            <!-- التشخيص السابق -->
                                            @php
                                                $pvDiag = $pv->diagnosis ?? [];
                                            @endphp
                                            @if(!empty($pvDiag['code']) || !empty($pvDiag['description']) || !empty($pv->physical_examination))
                                                <div class="bg-light p-2 rounded-2 mb-2 small">
                                                    @if(!empty($pvDiag['code']))
                                                        <div class="text-primary fw-bold">
                                                            <i class="fas fa-stethoscope me-1"></i> {{ $pvDiag['code'] }}
                                                        </div>
                                                    @endif
                                                    @if(!empty($pvDiag['description']))
                                                        <div class="text-dark">{{ $pvDiag['description'] }}</div>
                                                    @endif
                                                    @if(!empty($pv->physical_examination))
                                                        <div class="text-muted small mt-1"><strong>الفحص:</strong> {{ Str::limit($pv->physical_examination, 70) }}</div>
                                                    @endif
                                                </div>
                                            @endif

                                            <!-- الأدوية الموصوفة -->
                                            @if($pv->prescribedMedications->count() > 0)
                                                <div class="small">
                                                    <span class="text-success fw-bold"><i class="fas fa-pills me-1"></i> الأدوية:</span>
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        @foreach($pv->prescribedMedications as $med)
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.72rem;">
                                                                {{ $med->name }} ({{ $med->dosage }})
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-calendar-times fa-3x opacity-25 mb-2"></i>
                                    <h6 class="fw-bold mb-1">لا توجد زيارات سابقة مسجلة</h6>
                                    <p class="small mb-0">هذه هي الزيارة الأولى للمريض في النظام.</p>
                                </div>
                            @endif
                        </div>

                        <!-- محتوى 3: العمليات الجراحية ودخول الطوارئ -->
                        <div class="tab-pane fade" id="side-surgeries-pane" role="tabpanel">
                            <!-- العمليات الجراحية -->
                            <div class="mb-4">
                                <h6 class="fw-bold text-danger pb-1 border-bottom mb-2">
                                    <i class="fas fa-procedures me-1"></i> العمليات الجراحية ({{ isset($pastSurgeries) ? $pastSurgeries->count() : 0 }})
                                </h6>
                                @if(isset($pastSurgeries) && $pastSurgeries->count() > 0)
                                    <div class="d-flex flex-column gap-2">
                                        @foreach($pastSurgeries as $surg)
                                            <div class="card border-danger-subtle border p-2 rounded-2 bg-white small">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <strong class="text-danger">{{ $surg->operation_name ?? ($surg->surgicalOperation->name ?? 'عملية جراحية') }}</strong>
                                                    <span class="badge bg-danger-subtle text-danger border" style="font-size: 0.7rem;">{{ $surg->status }}</span>
                                                </div>
                                                <div class="text-muted small">
                                                    <i class="fas fa-calendar-alt me-1"></i> {{ $surg->scheduled_date ?? $surg->created_at->format('Y-m-d') }}
                                                    @if($surg->surgeon)
                                                        <span class="ms-2"><i class="fas fa-user-md me-1"></i> د. {{ $surg->surgeon->user?->name }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted small mb-0">لا توجد عمليات جراحية سابقة مسجلة.</p>
                                @endif
                            </div>

                            <!-- سجل الطوارئ -->
                            <div>
                                <h6 class="fw-bold text-warning text-dark pb-1 border-bottom mb-2">
                                    <i class="fas fa-ambulance me-1"></i> دخول الطوارئ ({{ isset($pastEmergencies) ? $pastEmergencies->count() : 0 }})
                                </h6>
                                @if(isset($pastEmergencies) && $pastEmergencies->count() > 0)
                                    <div class="d-flex flex-column gap-2">
                                        @foreach($pastEmergencies as $emg)
                                            <div class="card border-warning-subtle border p-2 rounded-2 bg-white small">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <strong class="text-dark">{{ $emg->created_at->format('Y-m-d H:i') }}</strong>
                                                    <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">{{ $emg->status }}</span>
                                                </div>
                                                <div class="text-muted small">
                                                    {{ $emg->chief_complaint ?: 'دخول قسم الطوارئ' }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted small mb-0">لا يوجد سجل دخول سابق للطوارئ.</p>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
<div class="modal fade" id="requestModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-plus-circle me-2"></i>
                    إضافة طلب طبي جديد
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('doctor.requests.store') }}" method="POST" id="requestForm">
                @csrf
                <input type="hidden" name="visit_id" value="{{ $visit->id }}">
                <input type="hidden" name="priority" value="normal">
                <div class="modal-body p-4">
                    <!-- معلومات أساسية -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 text-primary">
                                <i class="fas fa-info-circle me-2"></i>
                                معلومات الطلب الأساسية
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- اختيار نوع الطلب: مختبر أو أشعة (راديو) -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label d-block">نوع الطلب <span class="text-danger">*</span></label>
                                    <div class="btn-group w-100" role="group" aria-label="Request Type Switch">
                                        <input type="radio" class="btn-check" name="type" id="req_type_lab" value="lab" autocomplete="off" checked>
                                        <label class="btn btn-outline-primary" for="req_type_lab">
                                            <i class="fas fa-flask me-1"></i> تحاليل مخبرية
                                        </label>
                                        <input type="radio" class="btn-check" name="type" id="req_type_radiology" value="radiology" autocomplete="off">
                                        <label class="btn btn-outline-info" for="req_type_radiology">
                                            <i class="fas fa-x-ray me-1"></i> أشعة / تصوير
                                        </label>
                                    </div>
                                    <div class="form-text mt-2">اختر نوع الطلب للتبديل بين القوائم.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="alert alert-info border-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        يمكنك التبديل بين الفحوصات المختبرية والأشعة هنا
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- قسم التحاليل -->
                    <div class="card border-0 shadow-sm" id="labTests" style="display: block;">
                        <div class="card-header bg-gradient-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-microscope me-2"></i>
                                الفحوصات المطلوبة
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info border-0">
                                <i class="fas fa-info-circle me-2"></i>
                                اختر الفحوصات المطلوبة من القائمة أدناه
                            </div>
                        @php
                            $grouped = $labTests->groupBy('category');
                            $categoryNames = [
                                'كيمياء سريرية' => 'كيمياء سريرية',
                                'أمراض الدم' => 'أمراض الدم',
                                'مصرف الدم' => 'مصرف الدم',
                                'الطفيليات' => 'الطفيليات',
                                'الأحياء المجهرية' => 'الأحياء المجهرية',
                                'المناعة السريرية' => 'المناعة السريرية',
                                'فيروسات' => 'فيروسات',
                                'هرمونات' => 'هرمونات',
                                'الخلايا' => 'الخلايا',
                                'متفرقة' => 'متفرقة',
                                'أخرى' => 'أخرى'
                            ];
                            $categoryIcons = [
                                'كيمياء سريرية' => 'fas fa-flask',
                                'أمراض الدم' => 'fas fa-tint',
                                'مصرف الدم' => 'fas fa-syringe',
                                'الطفيليات' => 'fas fa-bug',
                                'الأحياء المجهرية' => 'fas fa-microscope',
                                'المناعة السريرية' => 'fas fa-shield-alt',
                                'فيروسات' => 'fas fa-virus',
                                'هرمونات' => 'fas fa-dna',
                                'الخلايا' => 'fas fa-search',
                                'متفرقة' => 'fas fa-list',
                                'أخرى' => 'fas fa-plus'
                            ];

                            // تجميع الفئات في مجموعات أكبر
                            $mainGroups = [
                                'كيمياء سريرية' => [
                                    'categories' => ['كيمياء سريرية'],
                                    'icon' => 'fas fa-flask',
                                    'color' => 'success'
                                ],
                                'أمراض الدم والمصارف' => [
                                    'categories' => ['أمراض الدم', 'مصرف الدم'],
                                    'icon' => 'fas fa-tint',
                                    'color' => 'danger'
                                ],
                                'الميكروبيولوجيا' => [
                                    'categories' => ['الأحياء المجهرية', 'الطفيليات'],
                                    'icon' => 'fas fa-microscope',
                                    'color' => 'info'
                                ],
                                'المناعة والهرمونات' => [
                                    'categories' => ['المناعة السريرية', 'فيروسات', 'هرمونات'],
                                    'icon' => 'fas fa-shield-alt',
                                    'color' => 'warning'
                                ],
                                'الخلايا والأنسجة' => [
                                    'categories' => ['الخلايا'],
                                    'icon' => 'fas fa-search',
                                    'color' => 'secondary'
                                ],
                                'متفرقة' => [
                                    'categories' => ['متفرقة', 'أخرى'],
                                    'icon' => 'fas fa-list',
                                    'color' => 'dark'
                                ]
                            ];
                        @endphp
                        @php $groupIndex = 0; @endphp
                        @foreach($mainGroups as $mainGroupName => $mainGroupData)
                            @php $groupId = 'group_' . $groupIndex; $groupIndex++; @endphp
                            <div class="main-group-section mb-3">
                                <div class="main-group-header bg-{{ $mainGroupData['color'] }} text-white p-3 rounded-top d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#{{ $groupId }}" style="cursor: pointer;">
                                    <h5 class="mb-0 d-flex align-items-center">
                                        <i class="{{ $mainGroupData['icon'] }} me-2"></i>
                                        {{ $mainGroupName }}
                                        <span class="badge bg-white text-{{ $mainGroupData['color'] }} ms-2">
                                            @php
                                                $totalCount = 0;
                                                foreach($mainGroupData['categories'] as $cat) {
                                                    if(isset($grouped[$cat])) {
                                                        $totalCount += $grouped[$cat]->count();
                                                    }
                                                }
                                                echo $totalCount;
                                            @endphp
                                        </span>
                                    </h5>
                                    <i class="fas fa-chevron-down toggle-icon"></i>
                                </div>
                                <div id="{{ $groupId }}" class="collapse show main-group-body p-3 border border-top-0 rounded-bottom">
                                    <div class="row g-3">
                                        @foreach($mainGroupData['categories'] as $category)
                                            @if(isset($grouped[$category]) && $grouped[$category]->count() > 0)
                                                <div class="col-12">
                                                    <div class="sub-category-section mb-3 p-3 bg-light rounded">
                                                        <h6 class="text-primary mb-3 d-flex align-items-center">
                                                            <i class="{{ $categoryIcons[$category] ?? 'fas fa-list' }} me-2"></i>
                                                            {{ $categoryNames[$category] ?? ucfirst($category) }}
                                                            <span class="badge bg-primary ms-2">{{ $grouped[$category]->count() }}</span>
                                                        </h6>
                                                        <div class="row g-2">
                                                            @foreach($grouped[$category] as $test)
                                                            <div class="col-md-6 col-lg-4">
                                                                <div class="form-check test-item p-2 border rounded hover-shadow">
                                                                    <input class="form-check-input" type="checkbox" name="tests[]" value="{{ $test->name }}" id="test_{{ $test->id }}" data-test-id="{{ $test->id }}">
                                                                    <label class="form-check-label w-100" for="test_{{ $test->id }}">
                                                                        <div class="d-flex justify-content-between align-items-start">
                                                                            <div>
                                                                                <strong>{{ $test->name }}</strong>
                                                                                @if($test->description)
                                                                                    <br><small class="text-muted">{{ Str::limit($test->description, 50) }}</small>
                                                                                @endif
                                                                            </div>
                                                                            <i class="fas fa-check-circle text-success opacity-0 check-icon"></i>
                                                                        </div>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- قسم الأشعة (مخفي افتراضياً ويُعرض عند اختيار نوع الطلب = radiology) -->
                    <div class="card border-0 shadow-sm" id="radiologyTests" style="display: none;">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-x-ray me-2"></i>
                                فحوصات الأشعة المتاحة
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info border-0">
                                <i class="fas fa-info-circle me-2"></i>
                                اختر فحوصات الأشعة المطلوبة من القائمة أدناه
                            </div>
                            @if(isset($radiologyTypes) && $radiologyTypes->count() > 0)
                                <div class="row g-3">
                                    @foreach($radiologyTypes as $type)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="form-check radiology-item p-3 border rounded hover-shadow">
                                                <input class="form-check-input" type="checkbox" name="radiology_types[]" value="{{ $type->id }}" id="req_radiology_{{ $type->id }}">
                                                <label class="form-check-label w-100" for="req_radiology_{{ $type->id }}">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong>{{ $type->name }}</strong>
                                                            @if($type->description)
                                                                <br><small class="text-muted">{{ Str::limit($type->description, 60) }}</small>
                                                            @endif
                                                            @if($type->base_price)
                                                                <br><small class="text-success fw-bold">{{ number_format($type->base_price / 1000, 0) }} دينار</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    لا توجد فحوصات أشعة متاحة حالياً
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="submitRequestBtn">
                        <i class="fas fa-plus me-1"></i>
                        إضافة الطلب
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal لطلب الأشعة -->
<div class="modal fade" id="radiologyModal" tabindex="-1" aria-labelledby="radiologyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="radiologyModalLabel">
                    <i class="fas fa-x-ray me-2"></i>
                    طلب فحوصات الأشعة والتصوير
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('doctor.requests.store') }}" method="POST" id="radiologyRequestForm">
                @csrf
                <input type="hidden" name="visit_id" value="{{ $visit->id }}">
                <input type="hidden" name="type" value="radiology">
                <input type="hidden" name="priority" value="normal">
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        اختر فحوصات الأشعة المطلوبة من القائمة أدناه
                    </div>
                    @if(isset($radiologyTypes) && $radiologyTypes->count() > 0)
                        @php
                            $radiologyCategories = [
                                'أشعة عادية' => 'أشعة عادية (X-ray)',
                                'مقطعية' => 'أشعة مقطعية (CT Scan)',
                                'رنين مغناطيسي' => 'الرنين المغناطيسي (MRI)',
                                'موجات فوق صوتية' => 'الموجات فوق الصوتية (Ultrasound)',
                                'تصوير نسائي' => 'تصوير الثدي (Mammography)',
                                'أسنان' => 'أشعة الدينتال (Dental X-ray)',
                                'عظام' => 'أشعة العظام (Bone Scan)',
                                'أوعية دموية' => 'تصوير الأوعية الدموية (Angiography)'
                            ];
                        @endphp
                        @foreach($radiologyCategories as $categoryKey => $categoryName)
                            @php
                                $categoryTypes = $radiologyTypes->filter(function($type) use ($categoryName) {
                                    return str_contains($type->name, $categoryName);
                                });
                            @endphp
                            @if($categoryTypes->count() > 0)
                                <div class="radiology-category mb-4">
                                    <h6 class="text-primary mb-3 d-flex align-items-center">
                                        <i class="fas fa-folder-open me-2"></i>
                                        {{ $categoryKey }}
                                        <span class="badge bg-primary ms-2">{{ $categoryTypes->count() }}</span>
                                    </h6>
                                    <div class="row g-3">
                                        @foreach($categoryTypes as $type)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="form-check radiology-item p-3 border rounded hover-shadow">
                                                <input class="form-check-input" type="checkbox" name="radiology_types[]" value="{{ $type->id }}" id="radiology_{{ $type->id }}">
                                                <label class="form-check-label w-100" for="radiology_{{ $type->id }}">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong>{{ $type->name }}</strong>
                                                            @if($type->description)
                                                                <br><small class="text-muted">{{ Str::limit($type->description, 60) }}</small>
                                                            @endif
                                                            @if($type->base_price)
                                                                <br><small class="text-success fw-bold">{{ number_format($type->base_price / 1000, 0) }} دينار</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            لا توجد فحوصات أشعة متاحة حالياً
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-x-ray me-1"></i>
                        إرسال طلب الأشعة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // التبديل بين التحاليل والأشعة عبر أزرار الراديو
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const labCard = document.getElementById('labTests');
    const radiologyCard = document.getElementById('radiologyTests');

    function toggleRequestType(type) {
        if (!labCard || !radiologyCard) {
            console.error('Cards not found:', { labCard, radiologyCard });
            return;
        }
        
        if (type === 'radiology') {
            labCard.style.display = 'none';
            radiologyCard.style.display = 'block';
        } else {
            labCard.style.display = 'block';
            radiologyCard.style.display = 'none';
        }
    }

    // ربط الحدث لكل زر راديو
    typeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                toggleRequestType(this.value);
            }
        });
    });

    // تهيئة العرض الافتراضي (lab)
    toggleRequestType('lab');

    // التعامل مع المودال - إعادة تعيين النموذج عند الفتح
    const requestModal = document.getElementById('requestModal');
    if (requestModal) {
        requestModal.addEventListener('shown.bs.modal', function() {
            const form = document.getElementById('requestForm');
            if (form) {
                form.reset();
            }
            // إعادة تعيين الزر الافتراضي إلى lab
            const labRadio = document.getElementById('req_type_lab');
            if (labRadio) {
                labRadio.checked = true;
                toggleRequestType('lab');
            }
        });
    }

    // معالجة إرسال نموذج إضافة الطلب عبر AJAX
    const requestForm = document.getElementById('requestForm');
    const submitRequestBtn = document.getElementById('submitRequestBtn');

    if (requestForm && submitRequestBtn) {
        requestForm.addEventListener('submit', function(e) {
            e.preventDefault(); // منع الإرسال التقليدي

            // التحقق من اختيار فحوصات بناءً على نوع الطلب
            const requestFormData = new FormData(this);
            const selectedType = requestFormData.get('type');
            
            if (selectedType === 'radiology') {
                const checkedRad = this.querySelectorAll('input[name="radiology_types[]"]:checked');
                if (checkedRad.length === 0) {
                    alert('الرجاء اختيار فحص أشعة واحد على الأقل');
                    return;
                }
            } else {
                const checkedLab = this.querySelectorAll('input[name="tests[]"]:checked');
                if (checkedLab.length === 0) {
                    alert('الرجاء اختيار فحص مختبر واحد على الأقل');
                    return;
                }
            }

            // تعطيل الزر وإظهار حالة التحميل
            submitRequestBtn.disabled = true;
            submitRequestBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>جاري الإضافة...';

            fetch(this.action, {
                method: 'POST',
                body: requestFormData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // التحقق إذا كان الطلب يحتاج دفع
                    if (data.requires_payment && data.cashier_url) {
                        // عرض رسالة نجاح مع توجيه للكاشير
                        const confirmPayment = confirm(data.message + '\n\nهل تريد الانتقال إلى صفحة الكاشير الآن؟');
                        if (confirmPayment) {
                            window.location.href = data.cashier_url;
                        } else {
                            // إغلاق المودال وإعادة تحميل الصفحة
                            const modal = bootstrap.Modal.getInstance(document.getElementById('requestModal'));
                            modal.hide();
                            location.reload();
                        }
                    } else {
                        // إغلاق المودال
                        const modal = bootstrap.Modal.getInstance(document.getElementById('requestModal'));
                        modal.hide();

                        // إعادة تحميل الصفحة لتحديث البيانات
                        location.reload();
                    }

                    // أو يمكن استخدام تنبيه نجاح
                    // showSuccessAlert('تم إضافة الطلب بنجاح!');
                } else {
                    // عرض رسائل الخطأ
                    if (data.errors) {
                        let errorMessage = 'حدثت أخطاء:\n';
                        for (let field in data.errors) {
                            errorMessage += '- ' + data.errors[field].join('\n') + '\n';
                        }
                        alert(errorMessage);
                    } else {
                        alert('حدث خطأ أثناء إضافة الطلب');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.');
            })
            .finally(() => {
                // إعادة تفعيل الزر
                submitRequestBtn.disabled = false;
                submitRequestBtn.innerHTML = '<i class="fas fa-plus me-1"></i>إضافة الطلب';
            });
        });
    }

    // معالجة إرسال نموذج طلب الأشعة عبر AJAX
    const radiologyForm = document.getElementById('radiologyRequestForm');
    if (radiologyForm) {
        radiologyForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');

            // التحقق من اختيار فحص واحد على الأقل
            const checkedBoxes = this.querySelectorAll('input[name="radiology_types[]"]:checked');
            if (checkedBoxes.length === 0) {
                alert('الرجاء اختيار فحص أشعة واحد على الأقل');
                return;
            }

            // تعطيل الزر وإظهار حالة التحميل
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>جاري الإرسال...';

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('radiologyModal'));
                    modal.hide();
                    location.reload();
                } else {
                    if (data.errors) {
                        let errorMessage = 'حدثت أخطاء:\n';
                        for (let field in data.errors) {
                            errorMessage += '- ' + data.errors[field].join('\n') + '\n';
                        }
                        alert(errorMessage);
                    } else {
                        alert('حدث خطأ أثناء إرسال الطلب');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-x-ray me-1"></i>إرسال طلب الأشعة';
            });
        });
    }

    // معالجة إنهاء الزيارة عبر AJAX
    const completeVisitForm = document.getElementById('completeVisitForm');

    if (completeVisitForm) {
        completeVisitForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!confirm('هل أنت متأكد من إنهاء هذه الزيارة؟ سيتم تغيير حالتها إلى مكتملة وستظهر في التاريخ.')) {
                return;
            }

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');

            // تعطيل الزر وإظهار حالة التحميل
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>جاري إنهاء الزيارة...';

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // إعادة تحميل الصفحة لتحديث البيانات
                    location.reload();
                } else {
                    // عرض رسائل الخطأ
                    if (data.errors) {
                        let errorMessage = 'حدثت أخطاء:\n';
                        for (let field in data.errors) {
                            errorMessage += '- ' + data.errors[field].join('\n') + '\n';
                        }
                        alert(errorMessage);
                    } else {
                        alert('حدث خطأ أثناء إنهاء الزيارة');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.');
            })
            .finally(() => {
                // إعادة تفعيل الزر
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check-circle me-1"></i>إنهاء الزيارة';
            });
        });
    }

// وظيفة البحث المباشر في قائمة ICD-10
document.getElementById('diagnosis_code').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase().trim();
    const datalist = document.getElementById('icd10-list');
    const options = datalist.querySelectorAll('option');

    if (searchTerm.length === 0) {
        // إظهار جميع الخيارات إذا لم يكن هناك بحث
        options.forEach(option => {
            option.style.display = 'block';
        });
        return;
    }

    let hasResults = false;
    let visibleCount = 0;
    const maxVisible = 10; // حد أقصى للنتائج المعروضة

    options.forEach(option => {
        const fullValue = option.value.toLowerCase();
        const searchData = (option.getAttribute('data-search') || '').toLowerCase();

        // البحث في القيمة الكاملة (رمز + وصف) والبيانات المساعدة
        const matches = fullValue.includes(searchTerm) || searchData.includes(searchTerm);

        if (matches && visibleCount < maxVisible) {
            option.style.display = 'block';
            hasResults = true;
            visibleCount++;
        } else {
            option.style.display = 'none';
        }
    });

    // إضافة تأثير بصري عند البحث
    if (searchTerm.length > 0) {
        this.classList.add('animate__animated', 'animate__pulse');
        setTimeout(() => {
            this.classList.remove('animate__animated', 'animate__pulse');
        }, 300);
    }

    // تحديث placeholder حسب النتائج
    if (hasResults) {
        this.style.borderColor = '#28a745'; // أخضر للنجاح
    } else if (searchTerm.length > 0) {
        this.style.borderColor = '#ffc107'; // أصفر للتحذير
    } else {
        this.style.borderColor = '#e9ecef'; // اللون الافتراضي
    }
});

// تحسين تجربة المستخدم - البحث التلقائي والتركيز
document.getElementById('diagnosis_code').addEventListener('focus', function() {
    // إضافة تأثير عند التركيز
    this.parentElement.classList.add('focused');

    // إذا كان فارغاً، أضف placeholder مشجع
    if (this.value === '') {
        this.placeholder = 'اكتب هنا للبحث في رموز ICD-10...';
    }
});

document.getElementById('diagnosis_code').addEventListener('blur', function() {
    // إزالة التأثير عند فقدان التركيز
    this.parentElement.classList.remove('focused');

    // إعادة الplaceholder الأصلي
    if (this.value === '') {
        this.placeholder = 'اكتب رمز أو وصف التشخيص...';
    }
});

// معالجة تغيير قيمة حقل التشخيص
document.getElementById('diagnosis_code').addEventListener('change', function() {
    const selectedValue = this.value;
    const datalist = document.getElementById('icd10-list');
    const options = datalist.querySelectorAll('option');
    const hiddenInput = document.getElementById('diagnosis_code_hidden');

    // البحث عن الخيار المحدد للحصول على الرمز الفعلي
    let actualCode = selectedValue; // افتراضياً نفس القيمة

    for (let option of options) {
        if (option.value === selectedValue) {
            actualCode = option.getAttribute('data-code') || selectedValue;
            break;
        }
    }

    // إذا لم نجد الخيار في datalist، حاول استخراج الرمز من القيمة المحددة
    if (actualCode === selectedValue && selectedValue.includes(' - ')) {
        actualCode = selectedValue.split(' - ')[0].trim();
    }

    // تحديث الحقل المخفي بالرمز الفعلي
    hiddenInput.value = actualCode;

    if (selectedValue && selectedValue !== '') {
        // إضافة تأثير نجاح
        this.classList.add('animate__animated', 'animate__bounceIn');
        setTimeout(() => {
            this.classList.remove('animate__animated', 'animate__bounceIn');
        }, 500);
    }

    const customCodeContainer = document.querySelector('.custom-code-container');
    if (actualCode === 'other') {
        customCodeContainer.style.display = 'block';
        customCodeContainer.classList.add('animate__animated', 'animate__fadeIn');
        setTimeout(() => {
            customCodeContainer.classList.remove('animate__animated', 'animate__fadeIn');
        }, 500);
    } else {
        customCodeContainer.classList.add('animate__animated', 'animate__fadeOut');
        setTimeout(() => {
            customCodeContainer.style.display = 'none';
            customCodeContainer.classList.remove('animate__animated', 'animate__fadeOut');
            document.getElementById('custom_code').value = '';
        }, 300);
    }
});
});
</script>

@php
$medicationCount = $prescribedMedications->count();
$treatmentCount = $otherTreatments->count();
@endphp

<script>
window.availableMedicinesData = @json($availableMedicines ?? []);

window.handleMedicineSelect = function(selectElem) {
    const row = selectElem.closest('.medication-item');
    if (!row) return;

    const idInput = row.querySelector('.med-id-input');
    const nameInput = row.querySelector('.med-name-input');
    const dosageInput = row.querySelector('.med-dosage-input');
    const typeSelect = row.querySelector('.med-type-select');

    const val = selectElem.value;
    if (!val || val === '') {
        if (idInput) idInput.value = '';
        return;
    }

    if (val === 'custom') {
        if (idInput) idInput.value = '';
        if (nameInput) {
            nameInput.value = '';
            nameInput.focus();
        }
        return;
    }

    const medId = parseInt(val, 10);
    const med = (window.availableMedicinesData || []).find(m => m.id === medId);

    if (med) {
        if (idInput) idInput.value = med.id;
        if (nameInput) nameInput.value = med.name;
        if (dosageInput && med.strength) dosageInput.value = med.strength;

        if (typeSelect && med.dosage_form) {
            const formStr = (med.dosage_form || '').toLowerCase();
            if (formStr.includes('tab') || formStr.includes('cap') || formStr.includes('حبوب') || formStr.includes('كبسول')) {
                typeSelect.value = 'tablet';
            } else if (formStr.includes('inj') || formStr.includes('amp') || formStr.includes('vial') || formStr.includes('إبر') || formStr.includes('حقن')) {
                typeSelect.value = 'injection';
            } else if (formStr.includes('syr') || formStr.includes('susp') || formStr.includes('شراب') || formStr.includes('معلق')) {
                typeSelect.value = 'syrup';
            } else if (formStr.includes('cream') || formStr.includes('oint') || formStr.includes('gel') || formStr.includes('مرهم') || formStr.includes('كريم')) {
                typeSelect.value = 'cream';
            } else if (formStr.includes('drop') || formStr.includes('قطر')) {
                typeSelect.value = 'drops';
            } else {
                typeSelect.value = 'other';
            }
        }
    }
};

window.removeMedication = function(elem) {
    try {
        if (!elem) return;
        const item = elem.closest('.medication-item');
        if (item) {
            const pkgId = item.getAttribute('data-pkg-id');
            if (typeof $ !== 'undefined' && $.fn.select2) {
                const $s = $(item).find('.medicine-select2');
                if ($s.length && $s.hasClass('select2-hidden-accessible')) {
                    $s.select2('destroy');
                }
            }
            item.remove();

            // إذا تم حذف دواء وكان يتبع باقة، نتحقق إن كانت أدوية الباقة قد حذفت جميعها لنلغي تحديد الباقة
            if (pkgId) {
                const container = document.getElementById('medicationsContainer');
                const remaining = container ? container.querySelectorAll(`.medication-item[data-pkg-id="${pkgId}"]`).length : 0;
                if (remaining === 0) {
                    const chip = document.querySelector(`.doc-pkg-chip-item[data-id="${pkgId}"]`);
                    if (chip) {
                        chip.setAttribute('data-applied', '0');
                        chip.classList.remove('pkg-is-selected', 'border-2', 'shadow-sm');
                        const checkIndicator = chip.querySelector('.pkg-check-indicator');
                        if (checkIndicator) checkIndicator.remove();
                    }
                }
            }
        }
        const container = document.getElementById('medicationsContainer');
        if (container && container.querySelectorAll('.medication-item').length === 0) {
            const notice = document.getElementById('noMedicationsNotice');
            if (notice) notice.style.display = 'block';
        }
    } catch(e) {
        console.error('Error removing medication:', e);
    }
};

window.initMedicineSelect2 = function(context) {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        const $targets = context ? $(context).find('.medicine-select2') : $('.medicine-select2');
        $targets.each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    placeholder: '-- ابحث بالاسم التجاري أو العلمي --',
                    width: '100%',
                    dir: 'rtl',
                    allowClear: true
                }).on('select2:select', function () {
                    window.handleMedicineSelect(this);
                }).on('select2:clear', function() {
                    window.handleMedicineSelect(this);
                });
            }
        });
    }
};

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-remove-medication');
    if (btn) {
        e.preventDefault();
        e.stopPropagation();
        window.removeMedication(btn);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    window.medicationIndex = {{ $medicationCount }};

    window.clearSavedData = function() {
        localStorage.removeItem('saved_medications');
    };

    window.initMedicineSelect2();

    if (typeof $ !== 'undefined') {
        $('#treatmentCollapse').on('shown.bs.collapse', function () {
            window.initMedicineSelect2();
        });
    }

    window.addMedication = function() {
        try {
            const container = document.getElementById('medicationsContainer');
            if (!container) return;

            const notice = document.getElementById('noMedicationsNotice');
            if (notice) notice.style.display = 'none';

            let optionsHtml = '<option value="">-- ابحث بالاسم التجاري أو العلمي --</option>';
            if (window.availableMedicinesData && window.availableMedicinesData.length > 0) {
                window.availableMedicinesData.forEach(med => {
                    optionsHtml += `<option value="${med.id}">${med.name} ${med.strength || ''} (${med.generic_name || ''} - ${med.dosage_form || ''})</option>`;
                });
            }
            optionsHtml += '<option value="custom">✏️ كتابة اسم دواء يدوي غير مدرج</option>';

            const idx = window.medicationIndex;
            const medicationHtml = `
                <div class="medication-item card mb-3 border-success">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-pills text-success me-1"></i>
                                    اسم الدواء (دليل الضمان الصحي)
                                </label>
                                <select class="form-select medicine-select2" data-index="${idx}" onchange="handleMedicineSelect(this)">
                                    ${optionsHtml}
                                </select>
                                <input type="hidden" name="prescribed_medications[${idx}][medicine_id]" class="med-id-input" value="">
                                <input type="text" class="form-control med-name-input mt-2" name="prescribed_medications[${idx}][name]"
                                       placeholder="اسم الدواء الموصوف" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">الشكل الدوائي</label>
                                <select class="form-select med-type-select" name="prescribed_medications[${idx}][type]" required>
                                    <option value="tablet">حبوب / أقراص</option>
                                    <option value="injection">إبرة / حقن</option>
                                    <option value="syrup">شراب</option>
                                    <option value="cream">كريم / مرهم</option>
                                    <option value="drops">قطرات</option>
                                    <option value="other">أخرى</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">الجرعة / القوة</label>
                                <input type="text" class="form-control med-dosage-input" name="prescribed_medications[${idx}][dosage]"
                                       placeholder="مثال: 500mg" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold d-block mb-2">التكرار يومياً</label>
                                <div class="frequency-selector" style="display: flex; gap: 4px; flex-wrap: wrap;">
                                    <input type="radio" id="new_freq_${idx}_1" name="prescribed_medications[${idx}][frequency]" value="1" checked style="display: none;">
                                    <label for="new_freq_${idx}_1" class="frequency-btn" style="padding: 4px 8px; border: 1px solid #ced4da; border-radius: 4px; cursor: pointer; font-size: 0.8rem; background: white;">1x</label>

                                    <input type="radio" id="new_freq_${idx}_2" name="prescribed_medications[${idx}][frequency]" value="2" style="display: none;">
                                    <label for="new_freq_${idx}_2" class="frequency-btn" style="padding: 4px 8px; border: 1px solid #ced4da; border-radius: 4px; cursor: pointer; font-size: 0.8rem; background: white;">2x</label>

                                    <input type="radio" id="new_freq_${idx}_3" name="prescribed_medications[${idx}][frequency]" value="3" style="display: none;">
                                    <label for="new_freq_${idx}_3" class="frequency-btn" style="padding: 4px 8px; border: 1px solid #ced4da; border-radius: 4px; cursor: pointer; font-size: 0.8rem; background: white;">3x</label>

                                    <input type="radio" id="new_freq_${idx}_4" name="prescribed_medications[${idx}][frequency]" value="4" style="display: none;">
                                    <label for="new_freq_${idx}_4" class="frequency-btn" style="padding: 4px 8px; border: 1px solid #ced4da; border-radius: 4px; cursor: pointer; font-size: 0.8rem; background: white;">4x</label>

                                    <input type="radio" id="new_freq_${idx}_needed" name="prescribed_medications[${idx}][frequency]" value="as_needed" style="display: none;">
                                    <label for="new_freq_${idx}_needed" class="frequency-btn" style="padding: 4px 8px; border: 1px solid #ced4da; border-radius: 4px; cursor: pointer; font-size: 0.8rem; background: white;">حاجة</label>
                                </div>
                            </div>
                            <div class="col-md-1 d-flex align-items-end justify-content-center">
                                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-medication" onclick="window.removeMedication(this)" title="حذف الدواء">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-2">
                                <label class="form-label text-muted small fw-bold">المدة</label>
                                <input type="text" class="form-control form-control-sm" name="prescribed_medications[${idx}][duration]"
                                       placeholder="مثال: 7 أيام">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted small fw-bold">التوقيت</label>
                                <input type="text" class="form-control form-control-sm" name="prescribed_medications[${idx}][times]"
                                       placeholder="صباحاً، بعد الأكل...">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label text-muted small fw-bold">تعليمات وتوصيات خاصة للصيدلي والمريض</label>
                                <input type="text" class="form-control form-control-sm" name="prescribed_medications[${idx}][instructions]"
                                       placeholder="مثال: يؤخذ بعد الطعام مباشرة مع كوب ماء وفير">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = medicationHtml.trim();
            const newRow = tempDiv.firstChild;
            container.appendChild(newRow);

            window.initMedicineSelect2(newRow);
            window.medicationIndex++;
        } catch (error) {
            console.error('Error in addMedication:', error);
        }
    };

    window.addMedicationFromPkg = function(med, pkgId) {
        try {
            window.addMedication();
            const container = document.getElementById('medicationsContainer');
            if (!container) return;
            const newRow = container.lastElementChild;
            if (!newRow) return;

            if (pkgId) {
                newRow.setAttribute('data-pkg-id', String(pkgId));
                newRow.classList.add('pkg-row-' + pkgId);
            }

            const select2Elem = newRow.querySelector('.medicine-select2');
            const idInput = newRow.querySelector('.med-id-input');
            const nameInput = newRow.querySelector('.med-name-input');
            const typeSelect = newRow.querySelector('.med-type-select');
            const dosageInput = newRow.querySelector('.med-dosage-input');
            const durationInput = newRow.querySelector('input[name*="[duration]"]');
            const instructionsInput = newRow.querySelector('input[name*="[instructions]"]');

            if (med.medicine_id && select2Elem) {
                if (typeof $ !== 'undefined' && $.fn.select2) {
                    $(select2Elem).val(String(med.medicine_id)).trigger('change.select2');
                }
                if (idInput) idInput.value = med.medicine_id;
            }
            if (nameInput) nameInput.value = med.name || '';
            if (typeSelect && med.type) typeSelect.value = med.type;
            if (dosageInput && med.dosage) dosageInput.value = med.dosage;
            if (durationInput && med.duration) durationInput.value = med.duration;
            if (instructionsInput && med.instructions) instructionsInput.value = med.instructions;

            if (med.frequency) {
                const freqRadio = newRow.querySelector(`input[name*="[frequency]"][value="${med.frequency}"]`);
                if (freqRadio) freqRadio.checked = true;
            }
        } catch (error) {
            console.error('Error in addMedicationFromPkg:', error);
        }
    };

    // إدارة باقات الأدوية السريعة (تثبيت المفضلة ⭐ + الترتيب التلقائي حسب الاستخدام 🔥)
    window.sortQuickPkgChipsInDom = function() {
        const container = document.getElementById('docQuickPkgContainer');
        if (!container) return;

        const chips = Array.from(container.querySelectorAll('.doc-pkg-chip-item'));
        if (chips.length <= 1) return;

        chips.sort((a, b) => {
            const starredA = parseInt(a.getAttribute('data-is-starred') || '0', 10);
            const starredB = parseInt(b.getAttribute('data-is-starred') || '0', 10);
            if (starredB !== starredA) return starredB - starredA;

            const usageA = parseInt(a.getAttribute('data-usage') || '0', 10);
            const usageB = parseInt(b.getAttribute('data-usage') || '0', 10);
            if (usageB !== usageA) return usageB - usageA;

            const nameA = a.getAttribute('data-pkg-name') || '';
            const nameB = b.getAttribute('data-pkg-name') || '';
            return nameA.localeCompare(nameB, 'ar');
        });

        chips.forEach(chip => container.appendChild(chip));
    };

    window.togglePkgStar = function(btn, groupId) {
        if (!groupId) return;
        const chip = btn.closest('.doc-pkg-chip-item');

        btn.disabled = true;
        fetch(`{{ url('medicine-groups') }}/${groupId}/toggle-star`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                const isStarred = data.is_starred;
                if (chip) {
                    chip.setAttribute('data-is-starred', isStarred ? '1' : '0');
                    if (isStarred) {
                        chip.className = 'doc-pkg-chip-item d-inline-flex align-items-center btn btn-sm btn-warning border-warning text-dark fw-bold shadow-sm rounded-pill p-1 pe-3 transition-all' + (chip.classList.contains('pkg-is-selected') ? ' pkg-is-selected' : '');
                        btn.className = 'btn btn-sm btn-link p-0 me-2 text-decoration-none pkg-star-btn text-warning';
                        btn.innerHTML = '<i class="fas fa-star text-dark fs-6"></i>';
                        btn.title = 'إلغاء التثبيت من المفضلة';
                    } else {
                        chip.className = 'doc-pkg-chip-item d-inline-flex align-items-center btn btn-sm btn-outline-success border-success rounded-pill p-1 pe-3 transition-all' + (chip.classList.contains('pkg-is-selected') ? ' pkg-is-selected' : '');
                        btn.className = 'btn btn-sm btn-link p-0 me-2 text-decoration-none pkg-star-btn text-muted opacity-50';
                        btn.innerHTML = '<i class="far fa-star fs-6"></i>';
                        btn.title = 'تثبيت في الصدارة ⭐';
                    }
                }

                window.sortQuickPkgChipsInDom();

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: isStarred ? 'success' : 'info',
                        title: data.message,
                        timer: 1500,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false
                    });
                }
            }
        })
        .catch(err => {
            btn.disabled = false;
            console.error('Error toggling package star:', err);
        });
    };

    window.applyMedPkg = function(chip, groupId) {
        if (!chip) return;
        try {
            const isApplied = chip.getAttribute('data-applied') === '1';
            const pkgName = chip.getAttribute('data-pkg-name') || 'الباقة';
            const container = document.getElementById('medicationsContainer');

            if (isApplied) {
                // إلغاء الاختيار (Toggle Off): إزالة أدوية هذه الباقة
                if (container && groupId) {
                    const pkgRows = container.querySelectorAll(`.medication-item[data-pkg-id="${groupId}"]`);
                    pkgRows.forEach(row => {
                        if (typeof $ !== 'undefined' && $.fn.select2) {
                            const $s = $(row).find('.medicine-select2');
                            if ($s.length && $s.hasClass('select2-hidden-accessible')) {
                                $s.select2('destroy');
                            }
                        }
                        row.remove();
                    });

                    if (container.querySelectorAll('.medication-item').length === 0) {
                        const notice = document.getElementById('noMedicationsNotice');
                        if (notice) notice.style.display = 'block';
                    }
                }

                chip.setAttribute('data-applied', '0');
                chip.classList.remove('pkg-is-selected');

                const checkIcon = chip.querySelector('.pkg-check-indicator');
                if (checkIcon) checkIcon.remove();

                // إنقاص عداد الاستخدام عند إلغاء الاختيار
                if (groupId) {
                    fetch(`{{ url('medicine-groups') }}/${groupId}/decrement-usage`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const newCount = data.usage_count;
                            chip.setAttribute('data-usage', newCount);

                            let badge = chip.querySelector('.pkg-usage-badge');
                            if (badge) {
                                if (newCount > 0) {
                                    const numSpan = badge.querySelector('.usage-num');
                                    if (numSpan) numSpan.textContent = newCount;
                                } else {
                                    badge.remove();
                                }
                            }
                            window.sortQuickPkgChipsInDom();
                        }
                    })
                    .catch(err => console.error('Error decrementing usage count:', err));
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'تم إلغاء اختيار: ' + pkgName,
                        text: 'تمت إزالة أدوية هذه الباقة من جدول الوصفة.',
                        timer: 1800,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false
                    });
                }
                return;
            }

            // تطبيق واختيار الباقة (Toggle On): إضافة أدوية الباقة
            const meds = JSON.parse(chip.getAttribute('data-meds') || '[]');
            meds.forEach(med => window.addMedicationFromPkg(med, groupId));

            chip.setAttribute('data-applied', '1');
            chip.classList.add('pkg-is-selected');

            const clickArea = chip.querySelector('.pkg-content-click');
            if (clickArea && !chip.querySelector('.pkg-check-indicator')) {
                const check = document.createElement('span');
                check.className = 'badge bg-success text-white rounded-pill ms-1 pkg-check-indicator animate__animated animate__fadeIn';
                check.style.fontSize = '0.65rem';
                check.innerHTML = '<i class="fas fa-check"></i> مختارة';
                clickArea.appendChild(check);
            }

            // زيادة عداد الاستخدام في الخلفية والترتيب التلقائي
            if (groupId) {
                fetch(`{{ url('medicine-groups') }}/${groupId}/increment-usage`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const newCount = data.usage_count;
                        chip.setAttribute('data-usage', newCount);

                        let badge = chip.querySelector('.pkg-usage-badge');
                        if (badge) {
                            const numSpan = badge.querySelector('.usage-num');
                            if (numSpan) numSpan.textContent = newCount;
                        } else {
                            if (clickArea) {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'badge bg-light text-dark border rounded-pill ms-1 pkg-usage-badge font-monospace';
                                newBadge.style.fontSize = '0.7rem';
                                newBadge.title = 'عدد مرات الاستخدام';
                                newBadge.innerHTML = `<i class="fas fa-fire text-danger"></i> <span class="usage-num">${newCount}</span>`;
                                clickArea.appendChild(newBadge);
                            }
                        }

                        window.sortQuickPkgChipsInDom();
                    }
                })
                .catch(err => console.error('Error incrementing usage count:', err));
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'تمت إضافة باقة: ' + pkgName,
                    text: 'تم إدراج الأدوية في الروشتة (اضغط مجدداً لإلغاء الاختيار).',
                    timer: 2000,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false
                });
            }
        } catch (err) {
            console.error('Error applying package medicines:', err);
        }
    };

    window.openSaveAsPkgModal = function(triggerBtn) {
        try {
            const container = document.getElementById('medicationsContainer');
            if (!container) {
                console.error('medicationsContainer not found');
                return;
            }
            
            const items = container.querySelectorAll('.medication-item');
            const meds = [];
            
            items.forEach(item => {
                const idInput = item.querySelector('.med-id-input');
                const nameInput = item.querySelector('.med-name-input');
                const selectEl = item.querySelector('.medicine-select2');
                const typeSelect = item.querySelector('.med-type-select');
                const dosageInput = item.querySelector('.med-dosage-input');
                const durationInput = item.querySelector('input[name*="[duration]"]');
                const instructionsInput = item.querySelector('input[name*="[instructions]"]');
                const checkedFreq = item.querySelector('input[name*="[frequency]"]:checked');

                let name = (nameInput ? nameInput.value : '').trim();
                let medId = idInput ? idInput.value : '';

                // إذا كان اسم الدواء فارغاً في حقل النص، نجلب الاسم من القائمة المحددة Select2
                if (!name && selectEl) {
                    if (selectEl.value && selectEl.value !== 'custom') {
                        medId = selectEl.value;
                        const opt = selectEl.options[selectEl.selectedIndex];
                        if (opt) {
                            name = opt.getAttribute('data-name') || opt.text.split('(')[0].trim();
                        }
                    }
                }

                if (!name && medId) {
                    const found = (window.availableMedicinesData || []).find(x => x.id == medId);
                    if (found) name = found.name;
                }

                if (name) {
                    meds.push({
                        medicine_id: medId,
                        name: name,
                        type: typeSelect ? typeSelect.value : 'tablet',
                        dosage: dosageInput ? dosageInput.value : '',
                        frequency: checkedFreq ? checkedFreq.value : '1',
                        duration: durationInput ? durationInput.value : '',
                        instructions: instructionsInput ? instructionsInput.value : ''
                    });
                }
            });

            if (meds.length === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'لا توجد أدوية مضافة',
                        text: 'يرجى كتابة أو اختيار دواء واحد على الأقل في الوصفة الطبية قبل حفظ الباقة.',
                        confirmButtonText: 'حسناً'
                    });
                } else {
                    alert('يرجى كتابة أو اختيار دواء واحد على الأقل في الوصفة الطبية قبل حفظ الباقة.');
                }
                return;
            }

            window._currentPendingPkgMeds = meds;

            // تحديث قائمة الأدوية في المودال
            const previewList = document.getElementById('saveRxPkgPreviewList');
            if (previewList) {
                let html = '';
                meds.forEach((m, idx) => {
                    html += `
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <div>
                                <strong class="text-dark">${idx + 1}. ${m.name}</strong>
                                <small class="text-muted d-block">${m.dosage || ''} (${m.type || ''}) - تكرار: ${m.frequency || '1'} ${m.duration ? '- لمدة ' + m.duration : ''}</small>
                            </div>
                            <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1"><i class="fas fa-check"></i> جاهز</span>
                        </li>
                    `;
                });
                previewList.innerHTML = html;
            }

            const countBadge = document.getElementById('saveRxPkgMedsCount');
            if (countBadge) countBadge.textContent = meds.length;

            const modalEl = document.getElementById('saveRxAsPkgModal');
            if (modalEl) {
                if (modalEl.parentElement !== document.body) {
                    document.body.appendChild(modalEl);
                }
                
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                } else if (typeof $ !== 'undefined' && $.fn.modal) {
                    $(modalEl).modal('show');
                } else {
                    modalEl.classList.add('show');
                    modalEl.style.display = 'block';
                }
            }
        } catch (err) {
            console.error('Error opening saveRxAsPkgModal:', err);
            alert('حدث خطأ أثناء فتح نافذة الحفظ: ' + err.message);
        }
    };

    window.submitSaveRxAsPkg = function(btn) {
        const nameInput = document.getElementById('saveRxPkgName');
        const descInput = document.getElementById('saveRxPkgDesc');
        const isPublicInput = document.getElementById('saveRxPkgIsPublic');
        const errorAlert = document.getElementById('saveRxPkgError');

        if (errorAlert) errorAlert.classList.add('d-none');

        const name = (nameInput ? nameInput.value : '').trim();
        if (!name) {
            if (nameInput) nameInput.focus();
            if (errorAlert) {
                errorAlert.textContent = 'يرجى إدخال اسم الباقة (مثال: باقة نزلات البرد)';
                errorAlert.classList.remove('d-none');
            }
            return;
        }

        const meds = window._currentPendingPkgMeds || [];
        if (meds.length === 0) {
            alert('لا توجد أدوية محددة للحفظ');
            return;
        }

        const payload = {
            name: name,
            description: descInput ? descInput.value : '',
            is_public: isPublicInput ? (isPublicInput.checked ? 1 : 0) : 0,
            medications: meds
        };

        const originalBtnHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الحفظ...';

        fetch("{{ route('medicine-groups.save-from-visit') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalBtnHtml;

            if (data.success && data.group) {
                const wrapper = document.getElementById('docQuickPkgWrapper');
                const container = document.getElementById('docQuickPkgContainer');
                if (wrapper) wrapper.style.display = 'block';

                if (container) {
                    const isPub = data.group.is_public;
                    const btnClass = isPub ? 'btn-outline-primary border-primary' : 'btn-outline-success border-success';
                    const iconClass = isPub ? 'fas fa-hospital text-primary me-1' : 'fas fa-layer-group text-success me-1 opacity-75';
                    const badgeHtml = isPub 
                        ? `<span class="badge bg-primary text-white rounded-pill ms-1">${data.group.medicines_count} (عامة)</span>`
                        : `<span class="badge bg-success-subtle text-success rounded-pill ms-1">${data.group.medicines_count}</span>`;
                    
                    const newChip = document.createElement('div');
                    newChip.className = `doc-pkg-chip-item d-inline-flex align-items-center btn btn-sm ${btnClass} rounded-pill p-1 pe-3 transition-all animate__animated animate__bounceIn`;
                    newChip.setAttribute('data-id', data.group.id);
                    newChip.setAttribute('data-is-starred', '0');
                    newChip.setAttribute('data-usage', '0');
                    newChip.setAttribute('data-meds', JSON.stringify(data.group.meds || []));
                    newChip.setAttribute('data-pkg-name', data.group.name);
                    newChip.title = 'نقرة لإضافة الأدوية | اضغط النجمة لتثبيت في الصدارة ⭐';
                    newChip.innerHTML = `
                        <button type="button" 
                                class="btn btn-sm btn-link p-0 me-2 text-decoration-none pkg-star-btn text-muted opacity-50"
                                onclick="event.stopPropagation(); window.togglePkgStar(this, ${data.group.id});"
                                title="تثبيت في الصدارة ⭐">
                            <i class="far fa-star fs-6"></i>
                        </button>
                        <span class="pkg-content-click d-inline-flex align-items-center cursor-pointer" onclick="window.applyMedPkg(this.closest('.doc-pkg-chip-item'), ${data.group.id})">
                            <i class="${iconClass}"></i>
                            <strong>${data.group.name}</strong>
                            ${badgeHtml}
                        </span>
                    `;
                    container.prepend(newChip);
                    window.sortQuickPkgChipsInDom();
                }

                // إغلاق المودال وتصفير الحقول
                const modalEl = document.getElementById('saveRxAsPkgModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
                if (nameInput) nameInput.value = '';
                if (descInput) descInput.value = '';

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم حفظ الباقة بنجاح ✅',
                        text: `تم حفظ باقة "${data.group.name}" وأصبحت متاحة فوراً في باقاتك السريعة!`,
                        timer: 3500,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false
                    });
                } else {
                    alert('تم حفظ الباقة بنجاح!');
                }
            } else {
                if (errorAlert) {
                    errorAlert.textContent = data.message || 'حدث خطأ أثناء حفظ الباقة';
                    errorAlert.classList.remove('d-none');
                }
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalBtnHtml;
            console.error('Error saving medicine group:', err);
            if (errorAlert) {
                errorAlert.textContent = 'حدث خطأ في الاتصال بالخادم';
                errorAlert.classList.remove('d-none');
            }
        });
    };

    // إدارة الأيقونات المنسدلة للمجموعات
    document.querySelectorAll('.main-group-header').forEach(header => {
        header.addEventListener('click', function() {
            const icon = this.querySelector('.toggle-icon');
            if (icon) {
                // الدوران سيتم عبر CSS باستخدام الكلاس collapsed
            }
        });
    });
    
    // ====== نظام البحث والفلترة المحسّن ======
    // دوال مساعدة
    function normalizeText(text) {
        return (text || '').toLowerCase().trim();
    }

    // ====== البحث في التحاليل ======
    function setupLabSearch() {
        const searchInput = document.getElementById('labSearchInput');
        const searchBtn = document.getElementById('labSearchBtn');
        
        if (!searchInput) {
            console.warn('Lab search input not found');
            return;
        }

        function doLabSearch() {
            const searchTerm = normalizeText(searchInput.value);
            
            const categories = document.querySelectorAll('.lab-category');
            let totalVisible = 0;
            
            categories.forEach(category => {
                const items = category.querySelectorAll('.lab-test-item');
                let categoryHasVisible = false;
                
                items.forEach(item => {
                    const testName = normalizeText(item.getAttribute('data-test-name') || '');
                    const matches = searchTerm === '' || testName.includes(searchTerm);
                    
                    // استخدام d-none بدلاً من style.display لتجاوز Bootstrap's flex !important
                    item.classList.toggle('d-none', !matches);
                    if (matches) {
                        categoryHasVisible = true;
                        totalVisible++;
                    }
                });
                
                category.classList.toggle('d-none', !categoryHasVisible);
            });
        }

        // ربط الأحداث
        searchInput.addEventListener('input', doLabSearch);
        searchInput.addEventListener('keyup', doLabSearch);
        if (searchBtn) {
            searchBtn.addEventListener('click', doLabSearch);
        }
        
        // تشغيل البحث في البداية
        doLabSearch();
    }

    // ====== البحث في الأشعة ======
    function setupRadiologySearch() {
        const searchInput = document.getElementById('radiologySearchInput');
        const searchBtn = document.getElementById('radiologySearchBtn');
        const modalityCheckboxes = document.querySelectorAll('.radiology-modality-checkbox');
        
        if (!searchInput) {
            console.warn('Radiology search input not found');
            return;
        }

        function getCategoryModality(categoryName) {
            const name = normalizeText(categoryName);
            if (name.includes('رنين') || name.includes('mri') || name.includes('magnetic')) {
                return 'mri';
            }
            if (name.includes('سونار') || name.includes('ultrasound') || name.includes('echo')) {
                return 'ultrasound';
            }
            return 'xray';
        }

        function getSelectedModalities() {
            return Array.from(modalityCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
        }

        function doRadiologySearch() {
            const searchTerm = normalizeText(searchInput.value);
            const selectedModalities = getSelectedModalities();
            const categories = document.querySelectorAll('.radiology-category');
            
            categories.forEach(category => {
                const categoryName = category.getAttribute('data-category') || '';
                const modality = getCategoryModality(categoryName);
                const modalityMatches = selectedModalities.length === 0 || selectedModalities.includes(modality);
                const items = category.querySelectorAll('.radiology-type-item');
                let categoryHasVisible = false;
                
                items.forEach(item => {
                    const typeName = normalizeText(item.getAttribute('data-type-name') || '');
                    const matches = (searchTerm === '' || typeName.includes(searchTerm)) && modalityMatches;
                    item.classList.toggle('d-none', !matches);
                    if (matches) {
                        categoryHasVisible = true;
                    }
                });

                category.classList.toggle('d-none', !categoryHasVisible);
            });
        }

        modalityCheckboxes.forEach(cb => {
            cb.addEventListener('change', doRadiologySearch);
        });

        // ربط الأحداث
        searchInput.addEventListener('input', doRadiologySearch);
        searchInput.addEventListener('keyup', doRadiologySearch);
        if (searchBtn) {
            searchBtn.addEventListener('click', doRadiologySearch);
        }
        
        // تشغيل البحث في البداية
        doRadiologySearch();
    }

    // ====== البحث في خدمات التمريض ======
    function setupNursingSearch() {
        const searchInput = document.getElementById('nursingSearchInput');
        const searchBtn = document.getElementById('nursingSearchBtn');
        
        if (!searchInput) {
            console.warn('Nursing search input not found');
            return;
        }

        function doNursingSearch() {
            const searchTerm = normalizeText(searchInput.value);
            
            const items = document.querySelectorAll('.nursing-service-item');
            
            items.forEach(item => {
                const serviceName = normalizeText(item.getAttribute('data-service-name') || '');
                const matches = searchTerm === '' || serviceName.includes(searchTerm);
                
                // استخدام d-none بدلاً من style.display لتجاوز Bootstrap's flex !important
                item.classList.toggle('d-none', !matches);
            });
        }

        // ربط الأحداث
        searchInput.addEventListener('input', doNursingSearch);
        searchInput.addEventListener('keyup', doNursingSearch);
        if (searchBtn) {
            searchBtn.addEventListener('click', doNursingSearch);
        }
        
        // تشغيل البحث في البداية
        doNursingSearch();
    }

    // تشغيل جميع أنظمة البحث
    setupLabSearch();
    setupRadiologySearch();
    setupNursingSearch();
    
    console.log('Search system initialized');
    
    // ====== نظام اختيار الفحوصات المخبرية السريع والذكي للطبيب ======
    function setupFastDoctorLabSelection() {
        const searchInput = document.getElementById('docLabFastSearch');
        const clearSearchBtn = document.getElementById('docLabClearSearch');
        const searchDropdown = document.getElementById('docLabSearchDropdown');
        const categoryPills = document.querySelectorAll('.doc-cat-pill');
        const labCols = Array.from(document.querySelectorAll('.doc-lab-col'));
        const labCheckboxes = Array.from(document.querySelectorAll('.doc-lab-chk'));
        const selectedTray = document.getElementById('docLabSelectedTray');
        const selectedChipsContainer = document.getElementById('docLabSelectedChips');
        const countBadges = document.querySelectorAll('.doc-lab-selected-count');
        const clearAllBtn = document.getElementById('docLabClearAllSelected');
        const groupBtns = document.querySelectorAll('.doc-quick-group-btn');
        const labForm = document.getElementById('doctorLabRequestForm');

        if (!searchInput || !labForm) return;

        let activeCategory = 'ALL';

        // 1. تحديث حالة البطاقة والشارة
        function updateCardVisual(chk) {
            const card = chk.closest('.doc-lab-card');
            if (!card) return;
            const icon = card.querySelector('.doc-lab-check-icon');
            if (chk.checked) {
                card.classList.add('border-primary', 'bg-primary-subtle');
                card.classList.remove('bg-white');
                if (icon) icon.classList.remove('d-none');
            } else {
                card.classList.remove('border-primary', 'bg-primary-subtle');
                card.classList.add('bg-white');
                if (icon) icon.classList.add('d-none');
            }
        }

        // 2. تحديث شريط الفحوصات المختارة والعداد
        function renderSelectedChips() {
            const checkedBoxes = labCheckboxes.filter(cb => cb.checked);
            const count = checkedBoxes.length;

            countBadges.forEach(b => b.textContent = count);

            if (count === 0) {
                if (selectedTray) selectedTray.style.display = 'none';
                if (selectedChipsContainer) selectedChipsContainer.innerHTML = '';
                return;
            }

            if (selectedTray) selectedTray.style.display = 'block';
            if (selectedChipsContainer) {
                selectedChipsContainer.innerHTML = '';
                checkedBoxes.forEach(chk => {
                    const id = chk.getAttribute('data-test-id');
                    const name = chk.getAttribute('data-test-name') || chk.value;
                    const code = chk.getAttribute('data-test-code') || '';

                    const chip = document.createElement('span');
                    chip.className = 'badge bg-white text-dark border p-1 px-2 d-inline-flex align-items-center gap-1 shadow-sm';
                    chip.style.fontSize = '0.8rem';
                    chip.innerHTML = `
                        <span class="fw-semibold text-primary">${name}</span>
                        ${code ? `<span class="text-muted small">(${code})</span>` : ''}
                        <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.55rem;" aria-label="إزالة"></button>
                    `;

                    chip.querySelector('.btn-close').addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        chk.checked = false;
                        updateCardVisual(chk);
                        renderSelectedChips();
                    });

                    selectedChipsContainer.appendChild(chip);
                });
            }
        }

        // 3. ربط تغيير مربعات الاختيار
        labCheckboxes.forEach(chk => {
            chk.addEventListener('change', function() {
                updateCardVisual(this);
                renderSelectedChips();
            });
        });

        // 4. فلترة بطاقات التحاليل حسب القسم والبحث
        function filterGrid() {
            const q = normalizeText(searchInput.value);
            labCols.forEach(col => {
                const name = normalizeText(col.getAttribute('data-test-name') || '');
                const code = normalizeText(col.getAttribute('data-test-code') || '');
                const cat = col.getAttribute('data-category') || '';

                const matchesCat = (activeCategory === 'ALL' || cat === activeCategory);
                const matchesSearch = (!q || name.includes(q) || code.includes(q));

                col.style.display = (matchesCat && matchesSearch) ? '' : 'none';
            });
        }

        // 5. اقتراحات البحث الذكية السريعة (Autocomplete Dropdown)
        function renderSearchDropdown(q) {
            if (!searchDropdown) return;
            if (!q || q.length < 1) {
                searchDropdown.classList.add('d-none');
                searchDropdown.innerHTML = '';
                return;
            }

            const matches = labCheckboxes.filter(chk => {
                const name = normalizeText(chk.getAttribute('data-test-name') || '');
                const code = normalizeText(chk.getAttribute('data-test-code') || '');
                return name.includes(q) || code.includes(q);
            }).slice(0, 8);

            if (matches.length === 0) {
                searchDropdown.innerHTML = `<div class="list-group-item text-muted small p-2 text-center">لا توجد نتائج مطابقة</div>`;
                searchDropdown.classList.remove('d-none');
                return;
            }

            searchDropdown.innerHTML = '';
            matches.forEach(chk => {
                const isChecked = chk.checked;
                const name = chk.getAttribute('data-test-name') || chk.value;
                const code = chk.getAttribute('data-test-code') || '';
                const cat = chk.getAttribute('data-test-category') || '';

                const item = document.createElement('button');
                item.type = 'button';
                item.className = `list-group-item list-group-item-action d-flex justify-content-between align-items-center p-2 px-3 ${isChecked ? 'bg-light text-muted' : ''}`;
                item.innerHTML = `
                    <div>
                        <span class="fw-bold text-dark">${name}</span>
                        ${code ? `<span class="badge bg-light text-muted border ms-1 font-monospace">${code}</span>` : ''}
                        <small class="text-muted d-block" style="font-size: 0.72rem;">${cat}</small>
                    </div>
                    <span>
                        ${isChecked 
                            ? `<span class="badge bg-success-subtle text-success"><i class="fas fa-check me-1"></i>محدد</span>` 
                            : `<span class="badge bg-primary-subtle text-primary">+ إضافة</span>`}
                    </span>
                `;

                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    chk.checked = !chk.checked;
                    updateCardVisual(chk);
                    renderSelectedChips();
                    searchInput.value = '';
                    if (clearSearchBtn) clearSearchBtn.classList.add('d-none');
                    searchDropdown.classList.add('d-none');
                    filterGrid();
                    searchInput.focus();
                });

                searchDropdown.appendChild(item);
            });

            searchDropdown.classList.remove('d-none');
        }

        // أحدث البحث
        searchInput.addEventListener('input', function() {
            const val = this.value.trim();
            if (clearSearchBtn) {
                clearSearchBtn.classList.toggle('d-none', val.length === 0);
            }
            renderSearchDropdown(normalizeText(val));
            filterGrid();
        });

        // الضغط على Enter في حقل البحث لاختيار أول نتيجة فوراً
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const q = normalizeText(this.value.trim());
                if (!q) return;

                const firstMatch = labCheckboxes.find(chk => {
                    const name = normalizeText(chk.getAttribute('data-test-name') || '');
                    const code = normalizeText(chk.getAttribute('data-test-code') || '');
                    return name.includes(q) || code.includes(q);
                });

                if (firstMatch) {
                    firstMatch.checked = true;
                    updateCardVisual(firstMatch);
                    renderSelectedChips();
                    this.value = '';
                    if (clearSearchBtn) clearSearchBtn.classList.add('d-none');
                    if (searchDropdown) searchDropdown.classList.add('d-none');
                    filterGrid();
                }
            } else if (e.key === 'Escape') {
                if (searchDropdown) searchDropdown.classList.add('d-none');
            }
        });

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                clearSearchBtn.classList.add('d-none');
                if (searchDropdown) searchDropdown.classList.add('d-none');
                filterGrid();
                searchInput.focus();
            });
        }

        // إغلاق القائمة المنسدلة عند النقر خارجها
        document.addEventListener('click', function(e) {
            if (searchDropdown && !searchDropdown.contains(e.target) && e.target !== searchInput) {
                searchDropdown.classList.add('d-none');
            }
        });

        // 6. أزرار تصفية الأقسام (Category Pills)
        categoryPills.forEach(pill => {
            pill.addEventListener('click', function() {
                categoryPills.forEach(p => {
                    p.classList.remove('btn-primary', 'active');
                    p.classList.add('btn-outline-secondary');
                    const b = p.querySelector('.badge');
                    if (b) {
                        b.classList.remove('bg-white', 'text-primary');
                        b.classList.add('bg-secondary-subtle', 'text-secondary');
                    }
                });

                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-primary', 'active');
                const badge = this.querySelector('.badge');
                if (badge) {
                    badge.classList.remove('bg-secondary-subtle', 'text-secondary');
                    badge.classList.add('bg-white', 'text-primary');
                }

                activeCategory = this.getAttribute('data-category');
                filterGrid();
            });
        });

        // 7. باقات المفضلات السريعة (Quick Groups)
        groupBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const namesStr = this.getAttribute('data-test-names') || '';
                const targetNames = namesStr.split('|||').map(s => normalizeText(s)).filter(Boolean);

                let allAlreadyChecked = true;
                targetNames.forEach(tName => {
                    const match = labCheckboxes.find(cb => normalizeText(cb.getAttribute('data-test-name') || cb.value) === tName);
                    if (match && !match.checked) allAlreadyChecked = false;
                });

                const newCheckedState = !allAlreadyChecked;

                targetNames.forEach(tName => {
                    const match = labCheckboxes.find(cb => normalizeText(cb.getAttribute('data-test-name') || cb.value) === tName);
                    if (match) {
                        match.checked = newCheckedState;
                        updateCardVisual(match);
                    }
                });

                if (newCheckedState) {
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-primary');
                } else {
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-outline-primary');
                }

                renderSelectedChips();
            });
        });

        // 8. إفراغ الكل
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function() {
                labCheckboxes.forEach(chk => {
                    chk.checked = false;
                    updateCardVisual(chk);
                });
                groupBtns.forEach(b => {
                    b.classList.remove('btn-primary');
                    b.classList.add('btn-outline-primary');
                });
                renderSelectedChips();
            });
        }

        // 9. الإرسال الفوري للطلب (Instant AJAX Submit)
        labForm.addEventListener('submit', function(e) {
            const checkedBoxes = labCheckboxes.filter(cb => cb.checked);
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('يرجى اختيار تحليل واحد على الأقل قبل إرسال الطلب');
                return;
            }

            const submitBtn = labForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جارٍ الحفظ...';
            }
        });

        // التهيئة الأولية
        labCheckboxes.forEach(chk => {
            if (chk.checked) updateCardVisual(chk);
        });
        renderSelectedChips();
    }

    setupFastDoctorLabSelection();
    
    // ====== نظام الفرز والبحث السريع للأشعة والتصوير ======
    function setupFastDoctorRadiologySelection() {
        const radForm = document.getElementById('doctorRadiologyRequestForm');
        if (!radForm) return;

        const searchInput = document.getElementById('docRadSearchInput');
        const clearSearchBtn = document.getElementById('docRadClearSearchBtn');
        const searchDropdown = document.getElementById('docRadAutocompleteMenu');
        const selectedTray = document.getElementById('docRadSelectedTray');
        const selectedChipsContainer = document.getElementById('docRadChipsContainer');
        const clearAllBtn = document.getElementById('docRadClearAllBtn');
        const countBadges = document.querySelectorAll('.doc-rad-selected-count');
        const categoryPills = document.querySelectorAll('.doc-rad-cat-pill');
        const radCols = Array.from(document.querySelectorAll('.doc-rad-col'));
        const radCheckboxes = Array.from(document.querySelectorAll('.doc-rad-chk'));

        let activeCategory = 'ALL';

        function normalizeText(str) {
            if (!str) return '';
            return str.toLowerCase().trim()
                .replace(/[أإآ]/g, 'ا')
                .replace(/ة/g, 'ه')
                .replace(/ى/g, 'ي');
        }

        // 1. تحديث حالة البطاقة والشارة
        function updateCardVisual(chk) {
            const card = chk.closest('.doc-rad-card');
            if (!card) return;
            const icon = card.querySelector('.doc-rad-check-icon');
            if (chk.checked) {
                card.classList.add('border-info', 'bg-info-subtle');
                card.classList.remove('bg-white');
                if (icon) icon.classList.remove('d-none');
            } else {
                card.classList.remove('border-info', 'bg-info-subtle');
                card.classList.add('bg-white');
                if (icon) icon.classList.add('d-none');
            }
        }

        // 2. تحديث شريط الفحوصات المختارة والعداد
        function renderSelectedChips() {
            const checkedBoxes = radCheckboxes.filter(cb => cb.checked);
            const count = checkedBoxes.length;

            countBadges.forEach(b => b.textContent = count);

            if (count === 0) {
                if (selectedTray) selectedTray.classList.add('d-none');
                if (selectedChipsContainer) selectedChipsContainer.innerHTML = '';
                return;
            }

            if (selectedTray) selectedTray.classList.remove('d-none');
            if (selectedChipsContainer) {
                selectedChipsContainer.innerHTML = '';
                checkedBoxes.forEach(chk => {
                    const name = chk.getAttribute('data-type-name') || chk.value;
                    const code = chk.getAttribute('data-type-code') || '';

                    const chip = document.createElement('span');
                    chip.className = 'badge bg-white text-dark border p-1 px-2 d-inline-flex align-items-center gap-1 shadow-sm';
                    chip.style.fontSize = '0.8rem';
                    chip.innerHTML = `
                        <span class="fw-semibold text-info">${name}</span>
                        ${code ? `<span class="text-muted small">(${code})</span>` : ''}
                        <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.55rem;" aria-label="إزالة"></button>
                    `;

                    chip.querySelector('.btn-close').addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        chk.checked = false;
                        updateCardVisual(chk);
                        renderSelectedChips();
                    });

                    selectedChipsContainer.appendChild(chip);
                });
            }
        }

        // 3. ربط تغيير مربعات الاختيار
        radCheckboxes.forEach(chk => {
            chk.addEventListener('change', function() {
                updateCardVisual(this);
                renderSelectedChips();
            });
        });

        // 4. فلترة بطاقات الأشعة حسب القسم والبحث
        function filterGrid() {
            if (!searchInput) return;
            const q = normalizeText(searchInput.value);
            radCols.forEach(col => {
                const name = normalizeText(col.getAttribute('data-type-name') || '');
                const code = normalizeText(col.getAttribute('data-type-code') || '');
                const cat = col.getAttribute('data-category') || '';

                const matchesCat = (activeCategory === 'ALL' || cat === activeCategory);
                const matchesSearch = (!q || name.includes(q) || code.includes(q));

                col.style.display = (matchesCat && matchesSearch) ? '' : 'none';
            });
        }

        // 5. اقتراحات البحث الذكية السريعة (Autocomplete Dropdown)
        function renderSearchDropdown(q) {
            if (!searchDropdown) return;
            if (!q || q.length < 1) {
                searchDropdown.classList.add('d-none');
                searchDropdown.style.display = 'none';
                searchDropdown.innerHTML = '';
                return;
            }

            const matches = radCheckboxes.filter(chk => {
                const name = normalizeText(chk.getAttribute('data-type-name') || '');
                const code = normalizeText(chk.getAttribute('data-type-code') || '');
                return name.includes(q) || code.includes(q);
            }).slice(0, 8);

            if (matches.length === 0) {
                searchDropdown.innerHTML = `<div class="list-group-item text-muted small p-2 text-center">لا توجد فحوصات أشعة مطابقة</div>`;
                searchDropdown.classList.remove('d-none');
                searchDropdown.style.display = 'block';
                return;
            }

            searchDropdown.innerHTML = '';
            matches.forEach(chk => {
                const isChecked = chk.checked;
                const name = chk.getAttribute('data-type-name') || chk.value;
                const code = chk.getAttribute('data-type-code') || '';
                const cat = chk.getAttribute('data-type-category') || '';

                const item = document.createElement('button');
                item.type = 'button';
                item.className = `list-group-item list-group-item-action d-flex justify-content-between align-items-center p-2 px-3 ${isChecked ? 'bg-light text-muted' : ''}`;
                item.innerHTML = `
                    <div>
                        <span class="fw-bold text-dark">${name}</span>
                        ${code ? `<span class="badge bg-light text-muted border ms-1 font-monospace">${code}</span>` : ''}
                        <small class="text-muted d-block" style="font-size: 0.72rem;">${cat}</small>
                    </div>
                    <span>
                        ${isChecked 
                            ? `<span class="badge bg-success-subtle text-success"><i class="fas fa-check me-1"></i>محدد</span>` 
                            : `<span class="badge bg-info-subtle text-info">+ إضافة</span>`}
                    </span>
                `;

                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    chk.checked = !chk.checked;
                    updateCardVisual(chk);
                    renderSelectedChips();
                    searchInput.value = '';
                    if (clearSearchBtn) clearSearchBtn.classList.add('d-none');
                    searchDropdown.classList.add('d-none');
                    searchDropdown.style.display = 'none';
                    filterGrid();
                    searchInput.focus();
                });

                searchDropdown.appendChild(item);
            });

            searchDropdown.classList.remove('d-none');
            searchDropdown.style.display = 'block';
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const val = this.value.trim();
                if (clearSearchBtn) {
                    clearSearchBtn.classList.toggle('d-none', val.length === 0);
                }
                renderSearchDropdown(normalizeText(val));
                filterGrid();
            });

            // الضغط على Enter في حقل البحث لاختيار أول نتيجة فوراً
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const q = normalizeText(this.value.trim());
                    if (!q) return;

                    const firstMatch = radCheckboxes.find(chk => {
                        const name = normalizeText(chk.getAttribute('data-type-name') || '');
                        const code = normalizeText(chk.getAttribute('data-type-code') || '');
                        return name.includes(q) || code.includes(q);
                    });

                    if (firstMatch) {
                        firstMatch.checked = true;
                        updateCardVisual(firstMatch);
                        renderSelectedChips();
                        this.value = '';
                        if (clearSearchBtn) clearSearchBtn.classList.add('d-none');
                        if (searchDropdown) {
                            searchDropdown.classList.add('d-none');
                            searchDropdown.style.display = 'none';
                        }
                        filterGrid();
                    }
                } else if (e.key === 'Escape') {
                    if (searchDropdown) {
                        searchDropdown.classList.add('d-none');
                        searchDropdown.style.display = 'none';
                    }
                }
            });
        }

        if (clearSearchBtn && searchInput) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                clearSearchBtn.classList.add('d-none');
                if (searchDropdown) {
                    searchDropdown.classList.add('d-none');
                    searchDropdown.style.display = 'none';
                }
                filterGrid();
                searchInput.focus();
            });
        }

        document.addEventListener('click', function(e) {
            if (searchDropdown && !searchDropdown.contains(e.target) && e.target !== searchInput) {
                searchDropdown.classList.add('d-none');
                searchDropdown.style.display = 'none';
            }
        });

        // 6. أزرار تصفية الأقسام (Category Pills)
        categoryPills.forEach(pill => {
            pill.addEventListener('click', function() {
                categoryPills.forEach(p => {
                    p.classList.remove('btn-info', 'text-white', 'active');
                    p.classList.add('btn-outline-secondary');
                    const b = p.querySelector('.badge');
                    if (b) {
                        b.classList.remove('bg-white', 'text-info');
                        b.classList.add('bg-secondary-subtle', 'text-secondary');
                    }
                });

                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-info', 'text-white', 'active');
                const badge = this.querySelector('.badge');
                if (badge) {
                    badge.classList.remove('bg-secondary-subtle', 'text-secondary');
                    badge.classList.add('bg-white', 'text-info');
                }

                activeCategory = this.getAttribute('data-category');
                filterGrid();
            });
        });

        // 7. إفراغ الكل
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function() {
                radCheckboxes.forEach(chk => {
                    chk.checked = false;
                    updateCardVisual(chk);
                });
                renderSelectedChips();
            });
        }

        // 8. التحقق عند الإرسال
        radForm.addEventListener('submit', function(e) {
            const checkedBoxes = radCheckboxes.filter(cb => cb.checked);
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('يرجى اختيار فحص أشعة واحد على الأقل قبل إرسال الطلب');
                return;
            }

            const submitBtn = radForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جارٍ الإرسال...';
            }
        });

        // التهيئة الأولية
        radCheckboxes.forEach(chk => {
            if (chk.checked) updateCardVisual(chk);
        });
        renderSelectedChips();
    }

    setupFastDoctorRadiologySelection();
}); // End of DOMContentLoaded

function confirmSurgeryReferral() {
    const notes = document.getElementById('surgery_notes').value.trim();
    if (!notes) {
        alert('يرجى إدخال ملاحظات العملية المطلوبة');
        return false;
    }
    return confirm('هل أنت متأكد من تحويل المريض للاستعلامات لحجز عملية؟');
}

</script>

<style>
/* تحسينات المجموعات الرئيسية */
.main-group-section {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
    margin-bottom: 2rem;
}

.main-group-section:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.main-group-header {
    border-bottom: none;
    font-weight: 600;
}

.main-group-body {
    background: #f8f9fa;
}

.sub-category-section {
    border-left: 3px solid #007bff;
    background: white !important;
}

.hover-shadow:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}
</style>

<!-- Modal تحويل المريض لحجز عملية -->
<div class="modal fade" id="surgeryReferralModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-procedures me-2"></i>
                    تحويل المريض لحجز عملية جراحية
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('doctor.visits.mark-needs-surgery', $visit) }}" method="POST" onsubmit="return confirmSurgeryReferral()">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        سيتم تحويل المريض للاستعلامات لحجز العملية بعد إكمال الإجراءات المطلوبة (الدفع - التحاليل - الأشعة)
                    </div>
                    <div class="mb-3">
                        <label for="surgery_notes" class="form-label">ملاحظات العملية المطلوبة <span class="text-danger">*</span></label>
                        <textarea name="surgery_notes" id="surgery_notes" class="form-control" rows="4" required 
                                  placeholder="مثال: استئصال الزائدة الدودية - تحليل CBC ووظائف كلى مطلوبة قبل العملية"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-paper-plane me-1"></i>
                        تحويل للاستعلامات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var referButton = document.getElementById('referDoctorButton');
        var referPanel = document.getElementById('referDoctorPanel');
        var closeReferPanel = document.getElementById('closeReferDoctorPanel');

        if (referButton && referPanel) {
            referButton.addEventListener('click', function(event) {
                event.preventDefault();
                referPanel.classList.toggle('d-none');
                if (!referPanel.classList.contains('d-none')) {
                    referPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        }

        if (closeReferPanel && referPanel) {
            closeReferPanel.addEventListener('click', function() {
                referPanel.classList.add('d-none');
            });
        }

        function activateVisitTab(button) {
            const targetSelector = button.getAttribute('data-bs-target');
            if (!targetSelector) return;

            // تحديث التبويب النشط
            document.querySelectorAll('.visit-tab-nav .nav-link').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            // إخفاء جميع لوحات العمل
            document.querySelectorAll('.workstation-panel').forEach(panel => {
                panel.classList.remove('show');
                panel.style.display = 'none';
            });
            
            // إظهار اللوحة المقابلة
            const targetPanel = document.querySelector(targetSelector);
            if (targetPanel) {
                targetPanel.classList.add('show');
                targetPanel.style.display = 'block';
            }
        }

        // إخفاء أزرار accordion الأصلية
        document.querySelectorAll('.accordion-header').forEach(header => {
            header.style.display = 'none';
        });

        // ربط أحداث التبويبات
        document.querySelectorAll('.visit-tab-nav .nav-link').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                activateVisitTab(this);
            });
        });

        // تفعيل التبويب الأول افتراضياً
        const firstTab = document.querySelector('.visit-tab-nav .nav-link.active');
        if (firstTab) {
            activateVisitTab(firstTab);
        }

        // فحص دوري لحظي لطلبات استبدال الأدوية من الصيدلية كل 10 ثوان
        setInterval(checkLiveSubstitutionRequests, 10000);
    });

    // استعلام لحظي عن طلبات البدائل الواردة من الصيدلية
    function checkLiveSubstitutionRequests() {
        const visitId = {{ $visit->id }};
        fetch(`{{ url('doctor/visits') }}/${visitId}/substitution-requests`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.requests) {
                    renderSubstitutionAlerts(data.requests);
                }
            })
            .catch(err => console.error('Error fetching substitution requests:', err));
    }

    // رسم بطاقات طلبات استبدال الأدوية
    function renderSubstitutionAlerts(requests) {
        const container = document.getElementById('liveSubstitutionAlertsContainer');
        if (!container) return;

        if (requests.length === 0) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        requests.forEach(req => {
            html += `
                <div class="alert alert-warning border-2 border-warning shadow-sm rounded-4 p-3 mb-3 substitution-alert-card" id="subAlert-${req.id}">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-warning text-dark p-3 rounded-circle fs-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fas fa-exchange-alt fa-bounce"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                    <span>🔔 إشعار من الصيدلية: مقترح بديل دوائي</span>
                                    <span class="badge bg-danger rounded-pill px-2 py-1 small">بانتظار قرارك</span>
                                </h6>
                                <div class="text-dark small mb-1">
                                    الدواء المطلوب: <strong class="text-danger text-decoration-line-through">${req.original_medicine_name}</strong>
                                    <i class="fas fa-arrow-left mx-2 text-primary"></i>
                                    البديل المقترح: <strong class="text-success fs-6">${req.suggested_medicine_name}</strong>
                                    <span class="text-muted">(${req.suggested_form || ''} - ${req.suggested_strength || ''})</span>
                                </div>
                                <div class="small text-secondary">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <span>توضيح الصيدلية: ${req.substitution_reason || 'عدم توفر الصنف الأصلي حالياً'}</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-success fw-bold px-3 py-2 shadow-sm rounded-3 btn-approve-sub" onclick="respondToSubstitution(${req.id}, 'approve')">
                                <i class="fas fa-check-circle me-1"></i> موافقة واعتماد البديل
                            </button>
                            <button type="button" class="btn btn-outline-danger fw-bold px-3 py-2 rounded-3 btn-reject-sub" onclick="respondToSubstitution(${req.id}, 'reject')">
                                <i class="fas fa-times-circle me-1"></i> رفض البديل
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // إرسال قرار الطبيب (موافقة أو رفض)
    function respondToSubstitution(itemId, action) {
        const card = document.getElementById(`subAlert-${itemId}`);
        if (card) {
            card.querySelectorAll('button').forEach(btn => btn.disabled = true);
        }

        fetch(`{{ url('doctor/prescriptions/items') }}/${itemId}/respond-substitution`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ action: action })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (card) {
                    card.style.transition = 'all 0.4s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => card.remove(), 400);
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: action === 'approve' ? 'success' : 'info',
                        title: action === 'approve' ? 'تم اعتماد البديل ✅' : 'تم رفض البديل ❌',
                        text: data.message,
                        timer: 3000,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false
                    });
                } else {
                    alert(data.message);
                }

                // إذا كانت موافقة، نقوم بتحديث اسم الدواء في القائمة دون الحاجة لإعادة تحميل الصفحة
                if (action === 'approve' && data.item && data.item.medicine_name) {
                    // تحديث حقل الإدخال المقابل
                    const medItems = document.querySelectorAll('.medication-item');
                    medItems.forEach(itemEl => {
                        const selectEl = itemEl.querySelector('.medicine-select2');
                        const customInput = itemEl.querySelector('.custom-medicine-input');
                        // تحديث الخيار
                        if (selectEl && selectEl.value == data.item.medicine_id) {
                            // already selected
                        }
                    });
                }

            } else {
                if (card) card.querySelectorAll('button').forEach(btn => btn.disabled = false);
                alert(data.message || 'حدث خطأ أثناء معالجة الطلب.');
            }
        })
        .catch(err => {
            if (card) card.querySelectorAll('button').forEach(btn => btn.disabled = false);
            console.error('Error responding to substitution:', err);
        });
    }
</script>

<style>
    #saveRxAsPkgModal.modal {
        z-index: 20050 !important;
    }
    #saveRxAsPkgModal .modal-dialog,
    #saveRxAsPkgModal .modal-content {
        pointer-events: auto !important;
    }
</style>

<!-- Modal: حفظ الروشتة الحالية كباقة علاجية سريعة -->
<div class="modal fade" id="saveRxAsPkgModal" tabindex="-1" aria-labelledby="saveRxAsPkgModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fs-6 fw-bold" id="saveRxAsPkgModalLabel">
                    <i class="fas fa-layer-group me-2"></i> حفظ الروشتة الحالية كباقة سريعة
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="saveRxPkgError" class="alert alert-danger d-none py-2 small mb-3"></div>

                <div class="mb-3">
                    <label for="saveRxPkgName" class="form-label fw-bold small text-dark">
                        اسم الباقة <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="saveRxPkgName" class="form-control" placeholder="مثال: باقة نزلات البرد، كورس جرثومة المعدة..." maxlength="255" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="saveRxPkgDesc" class="form-label fw-bold small text-dark">
                        وصف مختصر / ملاحظات (اختياري)
                    </label>
                    <input type="text" id="saveRxPkgDesc" class="form-control form-control-sm" placeholder="مثال: بروتوكول الباطنية للمرضى البالغين" maxlength="500">
                </div>

                @if(Auth::user()->isAdmin() || Auth::user()->hasRole('admin'))
                <div class="mb-3 form-check form-switch p-3 bg-light rounded-3 border">
                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="saveRxPkgIsPublic" value="1">
                    <label class="form-check-label fw-bold text-primary small cursor-pointer" for="saveRxPkgIsPublic">
                        <i class="fas fa-hospital me-1"></i> مشاركة كباقة عامة لجميع أطباء المستشفى
                    </label>
                    <small class="text-muted d-block mt-1">عند التفعيل، ستظهر هذه الباقة في شاشات كافة الأطباء الاستشاريين في المستشفى.</small>
                </div>
                @endif

                <div class="border rounded-3 p-2 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                        <span class="small fw-bold text-dark">
                            <i class="fas fa-pills text-success me-1"></i> الأدوية التي سيتم تضمينها بالباقة:
                        </span>
                        <span class="badge bg-success rounded-pill" id="saveRxPkgMedsCount">0</span>
                    </div>
                    <ul class="list-group list-group-flush rounded-2 border bg-white" id="saveRxPkgPreviewList" style="max-height: 180px; overflow-y: auto;">
                    </ul>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-success btn-sm px-4 fw-bold" onclick="window.submitSaveRxAsPkg(this)">
                    <i class="fas fa-save me-1"></i> حفظ وتثبيت الباقة
                </button>
            </div>
        </div>
    </div>
</div>

@endsection 