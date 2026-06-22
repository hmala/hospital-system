
@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0 text-primary fw-bold"><i class="fas fa-user-graduate me-2"></i> محطة الطبيب المقيم</h4>
            <small class="text-muted">العملية الجراحية رقم #{{ $surgery->id }}</small>
        </div>
        <a href="{{ route('resident-station.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 shadow-sm bg-white">
            <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <h6 class="fw-bold mb-2"><i class="fas fa-exclamation-triangle me-2"></i> برجاء تصحيح الأخطاء التالية:</h6>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Patient & Surgery Quick Summary Info Card -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2.5 d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                            <i class="fas fa-user-injured fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">معلومات المريض</h6>
                            <small class="text-muted">الملف الطبي للمريض</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <span class="text-muted d-block small">رقم الملف</span>
                            <span class="fw-bold text-dark">{{ $surgery->patient->file_number }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small">اسم المريض</span>
                            <span class="fw-bold text-dark">{{ $surgery->patient->user->full_name }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small">العمر</span>
                            <span class="fw-bold text-dark">{{ $surgery->patient->age ?? '-' }} سنة</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small">حالة الدفع</span>
                            @if($surgery->payment_status === 'paid')
                                <span class="badge bg-success rounded-pill px-2.5 py-1.5">مدفوع بالكامل</span>
                            @elseif($surgery->payment_status === 'partial')
                                <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1.5">مدفوع جزئياً</span>
                            @else
                                <span class="badge bg-danger rounded-pill px-2.5 py-1.5">غير مدفوع</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle p-2.5 d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                            <i class="fas fa-procedures fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">بيانات العملية الجراحية</h6>
                            <small class="text-muted">تفاصيل العملية المحجوزة</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <span class="text-muted d-block small">اسم العملية</span>
                            <span class="fw-bold text-dark">{{ $surgery->surgery_name }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small">الطبيب الجراح</span>
                            <span class="fw-bold text-dark">{{ $surgery->doctor?->user?->full_name ?? '-' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small">الطبيب المقيم المكلف</span>
                            <span class="fw-bold text-info"><i class="fas fa-user-md me-1"></i> {{ $station?->resident?->user?->full_name ?? 'لم يحدد بعد' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small">المرحلة الحالية</span>
                            <span class="badge bg-primary px-3 py-1.5 rounded-pill">{{ ($currentPhase ?? 'pre_op') === 'pre_op' ? 'تحضير ما قبل العملية' : 'متابعة ما بعد العملية' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN FORM WITH TABS -->
    <form action="{{ route('resident-station.update', $surgery) }}" method="POST" id="main-resident-form">
        @csrf
        @method('PATCH')
        <input type="hidden" name="phase" value="{{ $currentPhase ?? 'pre_op' }}">

        <!-- EMR Navigation Tabs -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 p-3 pb-0 rounded-top-4">
                <ul class="nav nav-pills nav-fill gap-2" id="emrTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill py-2.5 d-flex align-items-center justify-content-center" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-content" type="button" role="tab" aria-selected="true">
                            <i class="fas fa-history me-2"></i> التاريخ المرضي
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill py-2.5 d-flex align-items-center justify-content-center" id="examination-tab" data-bs-toggle="tab" data-bs-target="#examination-content" type="button" role="tab" aria-selected="false">
                            <i class="fas fa-stethoscope me-2"></i> الفحص السريري والحيوية
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill py-2.5 d-flex align-items-center justify-content-center" id="tests-tab" data-bs-toggle="tab" data-bs-target="#tests-content" type="button" role="tab" aria-selected="false">
                            <i class="fas fa-vials me-2"></i> نتائج المختبر والأشعة
                            @if(($surgery->labTests && $surgery->labTests->count() > 0) || ($surgery->radiologyTests && $surgery->radiologyTests->count() > 0))
                                <span class="badge bg-danger ms-2 rounded-circle p-1"><span class="visually-hidden">نتائج جديدة</span></span>
                            @endif
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill py-2.5 d-flex align-items-center justify-content-center" id="plan-tab" data-bs-toggle="tab" data-bs-target="#plan-content" type="button" role="tab" aria-selected="false">
                            <i class="fas fa-notes-medical me-2"></i> ملاحظات المقيم
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill py-2.5 d-flex align-items-center justify-content-center" id="treatment-plan-tab" data-bs-toggle="tab" data-bs-target="#treatment-plan-content" type="button" role="tab" aria-selected="false">
                            <i class="fas fa-prescription me-2"></i> خطة العلاج
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill py-2.5 d-flex align-items-center justify-content-center" id="follow-up-tab" data-bs-toggle="tab" data-bs-target="#follow-up-content" type="button" role="tab" aria-selected="false">
                            <i class="fas fa-calendar-check me-2"></i> Follow Up Sheet
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-4">
                <fieldset @if($station && $station->status === 'completed') disabled @endif>
                    <div class="tab-content" id="emrTabsContent">
                    
                    <!-- TAB 1: MEDICAL HISTORY -->
                    <div class="tab-pane fade show active" id="history-content" role="tabpanel" aria-labelledby="history-tab">
                        <!-- Select Assigned Resident -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-dark"><i class="fas fa-user-md text-primary me-1"></i> الطبيب المقيم المسؤول عن الحالة</label>
                                <select name="resident_id" class="form-select form-select-lg rounded-3 border-secondary-subtle">
                                    <option value="">-- اختر الطبيب المقيم --</option>
                                    @foreach($residents as $resident)
                                        <option value="{{ $resident->id }}" 
                                            {{ ($station?->resident_id ?? old('resident_id')) == $resident->id ? 'selected' : '' }}>
                                            {{ $resident->user->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-semibold text-dark"><i class="fas fa-comment-medical text-danger me-1"></i> الشكاية الرئيسية (Chief Complaint)</label>
                                    <textarea name="chief_complaint" class="form-control rounded-3 border-secondary-subtle" rows="4" placeholder="اكتب الشكاية الرئيسية بالتفصيل...">{{ $station?->chief_complaint ?? '' }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-semibold text-dark"><i class="fas fa-notes-medical text-info me-1"></i> تاريخ المرض الحالي (History of Present Illness)</label>
                                    <textarea name="history_present_illness" class="form-control rounded-3 border-secondary-subtle" rows="4" placeholder="اكتب تاريخ وتفاصيل المرض الحالي...">{{ $station?->history_present_illness ?? '' }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-semibold text-dark"><i class="fas fa-file-medical text-primary me-1"></i> التاريخ الطبي السابق (Past Medical History)</label>
                                    <textarea name="past_medical_hx" class="form-control rounded-3 border-secondary-subtle" rows="4" placeholder="اكتب الأمراض المزمنة أو المشاكل الصحية السابقة...">{{ $station?->past_medical_hx ?? '' }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-semibold text-dark"><i class="fas fa-cut text-warning me-1"></i> التاريخ الجراحي السابق (Past Surgical History)</label>
                                    <textarea name="past_surgical_hx" class="form-control rounded-3 border-secondary-subtle" rows="4" placeholder="اكتب العمليات الجراحية السابقة وتواريخها...">{{ $station?->past_surgical_hx ?? '' }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-semibold text-dark"><i class="fas fa-pills text-success me-1"></i> تاريخ الأدوية (Drug History)</label>
                                    <textarea name="drug_hx" class="form-control rounded-3 border-secondary-subtle" rows="4" placeholder="اكتب الأدوية الحالية التي يتناولها المريض...">{{ $station?->drug_hx ?? '' }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-semibold text-dark"><i class="fas fa-exclamation-triangle text-danger me-1"></i> حساسية الأدوية والأطعمة (Drug Allergy)</label>
                                    <textarea name="drug_allergy" class="form-control rounded-3 border-secondary-subtle text-danger fw-semibold" rows="4" placeholder="اكتب حالات الحساسية الدوائية أو الغذائية إن وجدت...">{{ $station?->drug_allergy ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: CLINICAL EXAMINATION & VITALS -->
                    <div class="tab-pane fade" id="examination-content" role="tabpanel" aria-labelledby="examination-tab">
                        <div class="alert alert-info border-info bg-info bg-opacity-10 mb-4 rounded-3 d-flex align-items-center">
                            <i class="fas fa-info-circle me-2 fs-5 text-info"></i>
                            <div>
                                <strong>تنبيه:</strong> يتم تسجيل وتحديث العلامات الحيوية والفحص السريري من قبل كادر التمريض في محطة التمريض. هذه البيانات للعرض فقط هنا.
                            </div>
                        </div>

                        <!-- Vital Signs Section -->
                        <h6 class="fw-bold text-dark mb-3"><i class="fas fa-heartbeat text-danger me-1"></i> العلامات الحيوية (Vital Signs)</h6>
                        <div class="row g-2 mb-3 row-cols-5">
                            <!-- BP Card -->
                            <div class="col">
                                <div class="card border border-danger-subtle bg-danger bg-opacity-10 rounded-3 text-center p-2 h-100">
                                    <label class="form-label fw-bold text-danger mb-1 small"><i class="fas fa-tachometer-alt"></i> ضغط الدم BP</label>
                                    <input type="text" name="bp" readonly style="background-color: #e9ecef;" class="form-control form-control-sm text-center rounded-2 border-danger-subtle" placeholder="120/80" value="{{ $station?->bp ?? '' }}">
                                </div>
                            </div>
                            <!-- Temp Card -->
                            <div class="col">
                                <div class="card border border-warning-subtle bg-warning bg-opacity-10 rounded-3 text-center p-2 h-100">
                                    <label class="form-label fw-bold text-warning-emphasis mb-1 small"><i class="fas fa-thermometer-half"></i> الحرارة Temp</label>
                                    <input type="text" name="temp" readonly style="background-color: #e9ecef;" class="form-control form-control-sm text-center rounded-2 border-warning-subtle" placeholder="37°C" value="{{ $station?->temp ?? '' }}">
                                </div>
                            </div>
                            <!-- Pulse Card -->
                            <div class="col">
                                <div class="card border border-danger-subtle bg-danger bg-opacity-10 rounded-3 text-center p-2 h-100">
                                    <label class="form-label fw-bold text-danger mb-1 small"><i class="fas fa-heart"></i> النبض PR</label>
                                    <input type="text" name="pr" readonly style="background-color: #e9ecef;" class="form-control form-control-sm text-center rounded-2 border-danger-subtle" placeholder="80 bpm" value="{{ $station?->pr ?? '' }}">
                                </div>
                            </div>
                            <!-- RR Card -->
                            <div class="col">
                                <div class="card border border-info-subtle bg-info bg-opacity-10 rounded-3 text-center p-2 h-100">
                                    <label class="form-label fw-bold text-info-emphasis mb-1 small"><i class="fas fa-lungs"></i> التنفس RR</label>
                                    <input type="text" name="rr" readonly style="background-color: #e9ecef;" class="form-control form-control-sm text-center rounded-2 border-info-subtle" placeholder="16 /min" value="{{ $station?->rr ?? '' }}">
                                </div>
                            </div>
                            <!-- SPO2 Card -->
                            <div class="col">
                                <div class="card border border-success-subtle bg-success bg-opacity-10 rounded-3 text-center p-2 h-100">
                                    <label class="form-label fw-bold text-success mb-1 small"><i class="fas fa-wind"></i> أكسجين SPo2</label>
                                    <input type="text" name="spo2" readonly style="background-color: #e9ecef;" class="form-control form-control-sm text-center rounded-2 border-success-subtle" placeholder="98%" value="{{ $station?->spo2 ?? '' }}">
                                </div>
                            </div>
                        </div>

                        <!-- Clinical Examination Textarea -->
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-semibold text-dark"><i class="fas fa-stethoscope text-primary me-1"></i> الفحص السريري العام (Clinical Examination)</label>
                                    <textarea name="clinical_examination" readonly style="background-color: #e9ecef;" class="form-control rounded-3 border-secondary-subtle" rows="5" placeholder="اكتب الملاحظات السريرية ونتائج فحص المريض...">{{ $station?->clinical_examination ?? '' }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-semibold text-dark"><i class="fas fa-notes-medical text-secondary me-1"></i> مراجعة الأنظمة الطبية الأخرى (Review of Other Systems)</label>
                                    <textarea name="review_of_other_systems" readonly style="background-color: #e9ecef;" class="form-control rounded-3 border-secondary-subtle" rows="5" placeholder="اكتب نتائج فحص الأجهزة الأخرى (القلب، الصدر، التنفس... إلخ)...">{{ $station?->review_of_other_systems ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- جدول القراءات الدورية السابقة ومخططات العلامات الحيوية -->
                        @php
                            $combinedReadings = collect();
                            if ($surgery->preOpResidentStation) {
                                $combinedReadings = $combinedReadings->concat($surgery->preOpResidentStation->readings->map(function($reading) {
                                    $reading->phase_label = 'قبل دخول الصالة';
                                    return $reading;
                                }));
                            }
                            if ($surgery->postOpResidentStation) {
                                $combinedReadings = $combinedReadings->concat($surgery->postOpResidentStation->readings->map(function($reading) {
                                    $reading->phase_label = 'بعد العملية';
                                    return $reading;
                                }));
                            }

                            // عرض القراءة الحالية كقراءة أولية إذا لم تكن هناك قراءات دورية إضافية محفوظة
                            if ($combinedReadings->isEmpty() && $station && ($station->bp || $station->temp || $station->pr || $station->rr || $station->spo2)) {
                                $fakeReading = new \stdClass();
                                $fakeReading->created_at = $station->updated_at ?: now();
                                $fakeReading->phase_label = ($station->phase === 'pre_op') ? 'قبل دخول الصالة' : 'بعد العملية';
                                $fakeReading->bp = $station->bp;
                                $fakeReading->temp = $station->temp;
                                $fakeReading->pr = $station->pr;
                                $fakeReading->rr = $station->rr;
                                $fakeReading->spo2 = $station->spo2;
                                $fakeReading->clinical_examination = $station->clinical_examination;
                                $fakeReading->notes = $station->notes ?? null;
                                $fakeReading->resident = $station->resident;
                                $combinedReadings->push($fakeReading);
                            }

                            $combinedReadingsDesc = $combinedReadings->sortByDesc('created_at');
                            $combinedReadingsAsc = $combinedReadings->sortBy('created_at');
                        @endphp

                        @if($combinedReadings->count() > 0)
                        <div class="card border border-light-subtle shadow-sm rounded-3 mt-4">
                            <div class="card-header bg-light bg-opacity-50 border-bottom p-3 d-flex align-items-center justify-content-between">
                                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-heartbeat text-danger me-2"></i>سجل قراءات العلامات الحيوية السابقة</h6>
                                <span class="badge bg-secondary rounded-pill px-2.5 py-1">{{ $combinedReadings->count() }} قراءات</span>
                            </div>
                            <div class="card-body p-3">
                                <!-- Tabs for switching between Chart and Table views -->
                                <ul class="nav nav-tabs mb-3 shadow-sm rounded bg-white p-1" id="vitalSignsTab" role="tablist" style="border: 1px solid #dee2e6;">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active fw-bold" id="charts-tab" data-bs-toggle="tab" data-bs-target="#charts-view" type="button" role="tab" aria-controls="charts-view" aria-selected="true" style="border: none; border-radius: 6px;">
                                            <i class="fas fa-chart-line me-2 text-primary"></i>المنحنى البياني للعلامات الحيوية
                                        </button>
                                    </li>
                                    <li class="nav-item ms-2" role="presentation">
                                        <button class="nav-link fw-bold" id="table-tab" data-bs-toggle="tab" data-bs-target="#table-view" type="button" role="tab" aria-controls="table-view" aria-selected="false" style="border: none; border-radius: 6px; color: #6c757d;">
                                            <i class="fas fa-table me-2"></i>جدول القراءات التفصيلي
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content" id="vitalSignsTabContent">
                                    <!-- 1. Charts Tab View -->
                                    <div class="tab-pane fade show active" id="charts-view" role="tabpanel" aria-labelledby="charts-tab">
                                        <!-- Row containing 2 charts per line -->
                                        <div class="row g-2 mb-2">
                                            <!-- Chart 1: Blood Pressure -->
                                            <div class="col-md-6 col-12">
                                                <div class="card border shadow-sm h-100" style="background: rgba(255, 255, 255, 0.9) !important; border: 1px solid #bfdbfe !important;">
                                                    <div class="card-header bg-light d-flex align-items-center justify-content-between py-1 px-2" style="border-bottom: 1px solid #bfdbfe !important; font-size: 0.8rem;">
                                                        <span class="fw-bold text-primary"><i class="fas fa-heartbeat text-danger me-1"></i>الضغط (BP)</span>
                                                        <span class="badge bg-danger p-1" style="font-size: 0.65rem;">mmHg</span>
                                                    </div>
                                                    <div class="card-body p-1" style="position: relative; height: 160px; background: transparent !important;">
                                                        <canvas id="bpChart"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Chart 2: Pulse Rate -->
                                            <div class="col-md-6 col-12">
                                                <div class="card border shadow-sm h-100" style="background: rgba(255, 255, 255, 0.9) !important; border: 1px solid #bfdbfe !important;">
                                                    <div class="card-header bg-light d-flex align-items-center justify-content-between py-1 px-2" style="border-bottom: 1px solid #bfdbfe !important; font-size: 0.8rem;">
                                                        <span class="fw-bold text-warning"><i class="fas fa-heart me-1"></i>النبض (Pulse)</span>
                                                        <span class="badge bg-warning p-1" style="font-size: 0.65rem; color: #fff;">bpm</span>
                                                    </div>
                                                    <div class="card-body p-1" style="position: relative; height: 160px; background: transparent !important;">
                                                        <canvas id="pulseChart"></canvas>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Chart 3: Temperature -->
                                            <div class="col-md-6 col-12">
                                                <div class="card border shadow-sm h-100" style="background: rgba(255, 255, 255, 0.9) !important; border: 1px solid #bfdbfe !important;">
                                                    <div class="card-header bg-light d-flex align-items-center justify-content-between py-1 px-2" style="border-bottom: 1px solid #bfdbfe !important; font-size: 0.8rem;">
                                                        <span class="fw-bold text-orange" style="color: #f97316;"><i class="fas fa-thermometer-half me-1"></i>الحرارة (Temp)</span>
                                                        <span class="badge p-1" style="background-color: #f97316; color: #fff; font-size: 0.65rem;">°C</span>
                                                    </div>
                                                    <div class="card-body p-1" style="position: relative; height: 160px; background: transparent !important;">
                                                        <canvas id="tempChart"></canvas>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Chart 4: SPO2 -->
                                            <div class="col-md-6 col-12">
                                                <div class="card border shadow-sm h-100" style="background: rgba(255, 255, 255, 0.9) !important; border: 1px solid #bfdbfe !important;">
                                                    <div class="card-header bg-light d-flex align-items-center justify-content-between py-1 px-2" style="border-bottom: 1px solid #bfdbfe !important; font-size: 0.8rem;">
                                                        <span class="fw-bold text-success"><i class="fas fa-wind me-1"></i>الأكسجين (SPO2)</span>
                                                        <span class="badge bg-success p-1" style="font-size: 0.65rem;">%</span>
                                                    </div>
                                                    <div class="card-body p-1" style="position: relative; height: 160px; background: transparent !important;">
                                                        <canvas id="spo2Chart"></canvas>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Chart 5: Respiratory Rate -->
                                            <div class="col-md-6 col-12">
                                                <div class="card border shadow-sm h-100" style="background: rgba(255, 255, 255, 0.9) !important; border: 1px solid #bfdbfe !important;">
                                                    <div class="card-header bg-light d-flex align-items-center justify-content-between py-1 px-2" style="border-bottom: 1px solid #bfdbfe !important; font-size: 0.8rem;">
                                                        <span class="fw-bold text-purple" style="color: #8b5cf6;"><i class="fas fa-lungs me-1"></i>التنفس (RR)</span>
                                                        <span class="badge text-white p-1" style="background-color: #8b5cf6; font-size: 0.65rem;">/min</span>
                                                    </div>
                                                    <div class="card-body p-1" style="position: relative; height: 160px; background: transparent !important;">
                                                        <canvas id="rrChart"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. Table Tab View -->
                                    <div class="tab-pane fade" id="table-view" role="tabpanel" aria-labelledby="table-tab">
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="py-2.5">المرحلة</th>
                                                        <th class="py-2.5">التاريخ والوقت</th>
                                                        <th class="py-2.5">الطبيب المقيم</th>
                                                        <th class="py-2.5">PR (النبض)</th>
                                                        <th class="py-2.5">Temp (الحرارة)</th>
                                                        <th class="py-2.5">BP (الضغط)</th>
                                                        <th class="py-2.5">RR (التنفس)</th>
                                                        <th class="py-2.5">SPo2 (الأكسجين)</th>
                                                        <th class="py-2.5">الفحص السريري</th>
                                                        <th class="py-2.5">ملاحظات</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($combinedReadingsDesc as $reading)
                                                    <tr>
                                                        <td class="fw-bold text-primary">{{ $reading->phase_label }}</td>
                                                        <td class="text-nowrap"><small class="fw-semibold text-dark">
                                                            @if($reading->created_at instanceof \Carbon\Carbon)
                                                                {{ $reading->created_at->format('Y-m-d h:i A') }}
                                                            @else
                                                                {{ \Carbon\Carbon::parse($reading->created_at)->format('Y-m-d h:i A') }}
                                                            @endif
                                                        </small></td>
                                                        <td>
                                                            @if(isset($reading->resident) && $reading->resident?->user?->full_name)
                                                                <small class="text-secondary"><i class="fas fa-user-md me-1"></i> {{ $reading->resident->user->full_name }}</small>
                                                            @else
                                                                <small class="text-muted">-</small>
                                                            @endif
                                                        </td>
                                                        <td><span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2.5 py-1.5">{{ $reading->pr ?? '-' }}</span></td>
                                                        <td><span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning-subtle px-2.5 py-1.5">{{ $reading->temp ?? '-' }}</span></td>
                                                        <td><span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2.5 py-1.5">{{ $reading->bp ?? '-' }}</span></td>
                                                        <td><span class="badge bg-info bg-opacity-10 text-info-emphasis border border-info-subtle px-2.5 py-1.5">{{ $reading->rr ?? '-' }}</span></td>
                                                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2.5 py-1.5">{{ $reading->spo2 ?? '-' }}</span></td>
                                                        <td>
                                                            @if(!empty($reading->clinical_examination))
                                                                <div class="text-wrap text-muted small" style="max-width: 200px; max-height: 60px; overflow-y: auto; line-height: 1.25;">
                                                                    {{ $reading->clinical_examination }}
                                                                </div>
                                                            @else
                                                                <small class="text-muted">-</small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(!empty($reading->notes))
                                                                <div class="text-wrap text-muted small" style="max-width: 150px; max-height: 60px; overflow-y: auto; line-height: 1.25;">
                                                                    {{ $reading->notes }}
                                                                </div>
                                                            @else
                                                                <small class="text-muted">-</small>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- TAB 3: MEDICAL TESTS RESULTS -->
                    <div class="tab-pane fade" id="tests-content" role="tabpanel" aria-labelledby="tests-tab">
                        <div class="row g-4">
                            <!-- Lab Results Box -->
                            <div class="col-md-6">
                                <div class="card border border-light-subtle shadow-sm rounded-3">
                                    <div class="card-header bg-light-subtle border-bottom p-3 d-flex align-items-center">
                                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-flask me-1 text-danger"></i> الفحوصات المخبرية (Lab Results)</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        @if($surgery->labTests && $surgery->labTests->count() > 0)
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>الفحص</th>
                                                            <th>الحالة</th>
                                                            <th>النتيجة</th>
                                                            <th>التاريخ</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($surgery->labTests as $labTest)
                                                        <tr>
                                                            <td class="fw-semibold">{{ $labTest->labTest?->name ?? '-' }}</td>
                                                            <td>
                                                                @if($labTest->status === 'completed')
                                                                    <span class="badge bg-success rounded-pill px-2 py-1"><i class="fas fa-check me-1"></i> مكتمل</span>
                                                                @elseif($labTest->status === 'pending')
                                                                    <span class="badge bg-warning text-dark rounded-pill px-2 py-1"><i class="fas fa-clock me-1"></i> معلق</span>
                                                                @else
                                                                    <span class="badge bg-secondary rounded-pill px-2 py-1">{{ $labTest->status }}</span>
                                                                @endif
                                                            </td>
                                                            <td class="fw-bold text-primary">{{ $labTest->result ?? '-' }}</td>
                                                            <td><small class="text-muted">{{ $labTest->completed_at?->format('Y-m-d') ?? '-' }}</small></td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center p-5 text-muted">
                                                <i class="fas fa-flask fa-2x mb-2 text-secondary opacity-50"></i>
                                                <p class="mb-0 small">لم يتم طلب فحوصات مخبرية لهذه العملية</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Radiology Results Box -->
                            <div class="col-md-6">
                                <div class="card border border-light-subtle shadow-sm rounded-3">
                                    <div class="card-header bg-light-subtle border-bottom p-3 d-flex align-items-center">
                                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-x-ray me-1 text-warning-emphasis"></i> الفحوصات التصويرية والأشعة (Radiology)</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        @if($surgery->radiologyTests && $surgery->radiologyTests->count() > 0)
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>نوع الأشعة</th>
                                                            <th>الحالة</th>
                                                            <th>النتيجة</th>
                                                            <th>التاريخ</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($surgery->radiologyTests as $radiologyTest)
                                                        <tr>
                                                            <td class="fw-semibold">{{ $radiologyTest->radiologyType?->name ?? '-' }}</td>
                                                            <td>
                                                                @if($radiologyTest->status === 'completed')
                                                                    <span class="badge bg-success rounded-pill px-2 py-1"><i class="fas fa-check me-1"></i> مكتمل</span>
                                                                @elseif($radiologyTest->status === 'pending')
                                                                    <span class="badge bg-warning text-dark rounded-pill px-2 py-1"><i class="fas fa-clock me-1"></i> معلق</span>
                                                                @else
                                                                    <span class="badge bg-secondary rounded-pill px-2 py-1">{{ $radiologyTest->status }}</span>
                                                                @endif
                                                            </td>
                                                            <td class="fw-bold text-primary">{{ $radiologyTest->result ?? '-' }}</td>
                                                            <td><small class="text-muted">{{ $radiologyTest->completed_at?->format('Y-m-d') ?? '-' }}</small></td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center p-5 text-muted">
                                                <i class="fas fa-x-ray fa-2x mb-2 text-secondary opacity-50"></i>
                                                <p class="mb-0 small">لم يتم طلب فحوصات تصويرية لهذه العملية</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: NOTES -->
                    <div class="tab-pane fade" id="plan-content" role="tabpanel" aria-labelledby="plan-tab">
                        <div class="row g-4">
                            <!-- Notes Textarea -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label fw-semibold text-dark"><i class="fas fa-comment-dots text-primary me-1"></i> ملاحظات الطبيب المقيم العامة</label>
                                    <textarea name="notes" class="form-control rounded-3 border-secondary-subtle" rows="4" placeholder="اكتب أي ملاحظات إضافية حول جاهزية المريض والعملية...">{{ $station?->notes ?? '' }}</textarea>
                                </div>
                            </div>

                            @if(($currentPhase ?? 'pre_op') === 'post_op')
                                <!-- Post-Op Specific Fields -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label fw-semibold text-dark"><i class="fas fa-notes-medical text-danger me-1"></i> ملاحظات ما بعد العملية (Post-Op Notes)</label>
                                        <textarea name="post_op_notes" class="form-control rounded-3 border-secondary-subtle" rows="5" placeholder="اكتب تقرير حالة المريض بعد انتهاء العملية والإفاقة...">{{ $station?->post_op_notes ?? $surgery->diagnosis ?? '' }}</textarea>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- TAB 5: TREATMENT PLAN -->
                    <div class="tab-pane fade" id="treatment-plan-content" role="tabpanel" aria-labelledby="treatment-plan-tab">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="card border border-secondary-subtle shadow-sm rounded-3 mb-4">
                                    <div class="card-header bg-light bg-opacity-50 border-bottom py-2 px-3">
                                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-user-md text-primary me-1"></i> خطة العلاج التي حددها الجراح</h6>
                                    </div>
                                    <div class="card-body p-3">
                                            @if($surgery->surgeryTreatments->count() > 0)
                                                <div class="table-responsive">
                                                    <table class="table table-bordered mb-0 align-middle">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th class="py-2">الوصف</th>
                                                                <th class="py-2">الجرعة</th>
                                                                <th class="py-2">التوقيت</th>
                                                                <th class="py-2">المدة</th>
                                                                <th class="py-2 text-center">الحالة</th>
                                                                <th class="py-2">تاريخ الإعطاء</th>
                                                                <th class="py-2">بواسطة</th>
                                                                <th class="py-2 text-center">ملاحظات</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($surgery->surgeryTreatments as $treatment)
                                                            <tr>
                                                                <td class="text-dark fw-bold">{{ $treatment->description }}</td>
                                                                <td class="text-dark">{{ $treatment->dosage ?? '-' }}</td>
                                                                <td class="text-dark">{{ $treatment->timing ?? '-' }}</td>
                                                                <td class="text-dark">
                                                                    @if($treatment->duration_value)
                                                                        {{ $treatment->duration_value }} 
                                                                        @php $units=['days'=>'يوم','weeks'=>'أسبوع','months'=>'شهر','hours'=>'ساعة','doses'=>'جرعة']; @endphp
                                                                        {{ $units[$treatment->duration_unit] ?? $treatment->duration_unit }}
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </td>
                                                                <td class="text-center">
                                                                    @if($treatment->status === 'administered')
                                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2.5 py-1.5"><i class="fas fa-check-circle me-1"></i>تم الإعطاء</span>
                                                                    @elseif($treatment->status === 'cancelled')
                                                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2.5 py-1.5"><i class="fas fa-times-circle me-1"></i>ملغى</span>
                                                                    @else
                                                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle px-2.5 py-1.5"><i class="fas fa-clock me-1"></i>مخطط</span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-muted small">
                                                                    {{ $treatment->administered_at ? $treatment->administered_at->format('Y-m-d H:i') : '-' }}
                                                                </td>
                                                                <td class="text-dark small">
                                                                    {{ $treatment->administeredBy?->full_name ?? $treatment->administeredBy?->name ?? '-' }}
                                                                </td>
                                                                <td class="text-muted small">
                                                                    {{ $treatment->admin_notes ?? '—' }}
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @elseif($surgery->surgeonStation?->treatment_plan)
                                                <div class="table-responsive">
                                                    <table class="table table-borderless mb-0">
                                                        <tbody>
                                                            <tr>
                                                                <th class="w-25 text-muted">الخطة المحددة من قبل الجراح</th>
                                                                <td class="text-dark">{!! nl2br(e($surgery->surgeonStation->treatment_plan)) !!}</td>
                                                            </tr>
                                                            @if($surgery->surgeonStation?->notes)
                                                            <tr>
                                                                <th class="text-muted">ملاحظات الجراح</th>
                                                                <td class="text-dark">{!! nl2br(e($surgery->surgeonStation->notes)) !!}</td>
                                                            </tr>
                                                            @endif
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="alert alert-warning border-warning-subtle bg-warning bg-opacity-10 mb-0">
                                                    <i class="fas fa-exclamation-triangle me-1"></i> لم يحدد الجراح خطة علاج بعد.
                                                </div>
                                            @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 6: FOLLOW UP SHEET -->
                    <div class="tab-pane fade" id="follow-up-content" role="tabpanel" aria-labelledby="follow-up-tab">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="card border border-secondary-subtle shadow-sm rounded-3 mb-4">
                                    <div class="card-header bg-light bg-opacity-50 border-bottom py-2 px-3">
                                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-clipboard-list text-primary me-1"></i> سجل المتابعة</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        @php $followUpStation = $surgery->postOpResidentStation; @endphp
                                        <div class="table-responsive mb-4">
                                            <table class="table table-bordered mb-0 align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="py-2">التاريخ</th>
                                                        <th class="py-2">الوردية</th>
                                                        <th class="py-2">المقيم</th>
                                                        <th class="py-2">التوثيق</th>
                                                        <th class="py-2">وقت التسجيل</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($followUpStation?->followUps ?? [] as $followUp)
                                                        <tr>
                                                            <td class="text-dark">{{ $followUp->follow_up_date->format('Y-m-d') }}</td>
                                                            <td class="text-dark">{{ $followUp->session === 'morning' ? 'صباحاً' : 'مساءً' }}</td>
                                                            <td class="text-dark">{{ $followUp->resident?->user?->full_name ?? $followUp->resident_name ?? 'غير محدد' }}</td>
                                                            <td class="text-dark">{!! nl2br(e($followUp->notes)) !!}</td>
                                                            <td class="text-dark">{{ $followUp->created_at->format('Y-m-d H:i') }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center text-muted py-4">
                                                                <i class="fas fa-info-circle me-1"></i> لم تُسجل أي متابعة بعد.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="alert alert-info border-info bg-info bg-opacity-10 mb-0 rounded-3 d-flex align-items-center">
                                            <i class="fas fa-info-circle me-2 fs-5 text-info"></i>
                                            <div>
                                                <strong>تنبيه:</strong> يتم تسجيل وتحديث المتابعة الدورية من قبل كادر التمريض في محطة التمريض.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
            
            <!-- Save and Complete Actions footer -->
            @if(!$station || $station->status !== 'completed')
                <div class="card-footer bg-light p-4 d-flex gap-3 rounded-bottom-4">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2.5 shadow-sm">
                        <i class="fas fa-save me-1"></i> حفظ البيانات الحالية
                    </button>

                    @if($station && $station->status !== 'completed')
                        <button type="button" class="btn btn-success rounded-pill px-4 py-2.5 shadow-sm" 
                            onclick="if(confirm('هل أنت متأكد من إتمام هذه المرحلة؟ لن تتمكن من تعديل البيانات بعد الإتمام.')) {
                                event.preventDefault();
                                document.getElementById('complete-form').submit();
                            }">
                            <i class="fas fa-check-circle me-1"></i> إتمام واعتماد المحطة
                        </button>
                    @endif
                </div>
            @else
                <div class="card-footer bg-light-subtle p-4 d-flex align-items-center justify-content-between rounded-bottom-4">
                    <div class="text-success fw-bold">
                        <i class="fas fa-lock me-1"></i> هذه المرحلة معتمدة ومكتملة. لا يمكن تعديل البيانات.
                    </div>
                    <span class="badge bg-success rounded-pill px-3 py-2">
                        <i class="fas fa-check-circle me-1"></i> مكتملة
                    </span>
                </div>
            @endif
        </div>
    </form>

    <!-- Hidden Follow-Up Form -->
    <form id="resident-follow-up-form" action="{{ route('resident-station.follow-ups.store', $surgery) }}" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- Hidden Complete Form -->
    <form id="complete-form" action="{{ route('resident-station.complete', $surgery) }}" method="POST" style="display: none;">
        @csrf
    </form>

</div>
@endsection

@push('styles')
<style>
    .nav-pills .nav-link {
        color: #6c757d;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        transition: all 0.25s ease;
    }
    
    .nav-pills .nav-link.active {
        color: #ffffff;
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        border-color: #0a58ca;
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.2);
    }
    
    .nav-pills .nav-link:hover:not(.active) {
        background: #e9ecef;
        color: #495057;
    }

    .form-control:focus, .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    }

    .card {
        border: none;
    }

    .col-md-2.4 {
        flex: 0 0 20%;
        max-width: 20%;
    }

    @media (max-width: 768px) {
        .col-md-2.4 {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }
</style>
@endpush

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if we have readings data
    @if($combinedReadings->count() > 0)
        // Convert to array and ensure chronological order for charts
        @php
            $chartData = $combinedReadingsAsc->map(function($item) {
                return [
                    'created_at' => $item->created_at instanceof \Carbon\Carbon ? $item->created_at->toIso8601String() : \Carbon\Carbon::parse($item->created_at)->toIso8601String(),
                    'phase_label' => $item->phase_label,
                    'bp' => $item->bp,
                    'temp' => $item->temp,
                    'pr' => $item->pr,
                    'rr' => $item->rr,
                    'spo2' => $item->spo2,
                ];
            })->values();
        @endphp
        const rawData = @json($chartData);
        
        // 1. Prepare labels (Arabic dates and times)
        const labels = rawData.map(item => {
            const dateObj = new Date(item.created_at);
            const timeStr = dateObj.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
            return `${item.phase_label} (${timeStr})`;
        });

        // 2. Extract vital sign values
        const systolicData = [];
        const diastolicData = [];
        const prData = [];
        const tempData = [];
        const spo2Data = [];
        const rrData = [];

        rawData.forEach(item => {
            // Blood pressure splitting
            if (item.bp) {
                const parts = item.bp.split('/');
                systolicData.push(parts[0] ? parseInt(parts[0]) : null);
                diastolicData.push(parts[1] ? parseInt(parts[1]) : null);
            } else {
                systolicData.push(null);
                diastolicData.push(null);
            }

            prData.push(item.pr ? parseInt(item.pr) : null);
            tempData.push(item.temp ? parseFloat(item.temp) : null);
            spo2Data.push(item.spo2 ? parseInt(item.spo2) : null);
            rrData.push(item.rr ? parseInt(item.rr) : null);
        });

        // Config variables for charts styling
        const gridColor = 'rgba(148, 163, 184, 0.12)';
        const fontConfig = { family: 'Segoe UI', size: 10 };
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    rtl: true,
                    titleFont: { family: 'Segoe UI' },
                    bodyFont: { family: 'Segoe UI' }
                }
            },
            scales: {
                y: {
                    grid: { color: gridColor },
                    ticks: { font: fontConfig }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: fontConfig }
                }
            }
        };

        // 1. BP Chart
        const bpCanvas = document.getElementById('bpChart');
        if (bpCanvas) {
            const bpCtx = bpCanvas.getContext('2d');
            new Chart(bpCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'الانقباضي (Systolic)',
                            data: systolicData,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.06)',
                            borderWidth: 2.5,
                            tension: 0.3,
                            spanGaps: true,
                            pointRadius: 3.5,
                            pointBackgroundColor: '#ef4444'
                        },
                        {
                            label: 'الانبساطي (Diastolic)',
                            data: diastolicData,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.06)',
                            borderWidth: 2.5,
                            tension: 0.3,
                            spanGaps: true,
                            pointRadius: 3.5,
                            pointBackgroundColor: '#3b82f6'
                        }
                    ]
                },
                options: {
                    ...commonOptions,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            rtl: true,
                            labels: { font: { family: 'Segoe UI', size: 10, weight: '600' } }
                        },
                        tooltip: commonOptions.plugins.tooltip
                    },
                    scales: {
                        y: {
                            ...commonOptions.scales.y,
                            suggestedMin: 50,
                            suggestedMax: 150
                        },
                        x: commonOptions.scales.x
                    }
                }
            });
        }

        // 2. Pulse Chart
        const pulseCanvas = document.getElementById('pulseChart');
        if (pulseCanvas) {
            const pulseCtx = pulseCanvas.getContext('2d');
            new Chart(pulseCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'نبض القلب',
                        data: prData,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.06)',
                        borderWidth: 2.5,
                        tension: 0.3,
                        spanGaps: true,
                        pointRadius: 3.5,
                        pointBackgroundColor: '#f59e0b'
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            ...commonOptions.scales.y,
                            suggestedMin: 50,
                            suggestedMax: 120
                        },
                        x: commonOptions.scales.x
                    }
                }
            });
        }

        // 3. Temp Chart
        const tempCanvas = document.getElementById('tempChart');
        if (tempCanvas) {
            const tempCtx = tempCanvas.getContext('2d');
            new Chart(tempCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'درجة الحرارة',
                        data: tempData,
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.06)',
                        borderWidth: 2.5,
                        tension: 0.3,
                        spanGaps: true,
                        pointRadius: 3.5,
                        pointBackgroundColor: '#f97316'
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            ...commonOptions.scales.y,
                            suggestedMin: 35,
                            suggestedMax: 40
                        },
                        x: commonOptions.scales.x
                    }
                }
            });
        }

        // 4. SPO2 Chart
        const spo2Canvas = document.getElementById('spo2Chart');
        if (spo2Canvas) {
            const spo2Ctx = spo2Canvas.getContext('2d');
            new Chart(spo2Ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'نسبة الأكسجين',
                        data: spo2Data,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.06)',
                        borderWidth: 2.5,
                        tension: 0.3,
                        spanGaps: true,
                        pointRadius: 3.5,
                        pointBackgroundColor: '#10b981'
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            ...commonOptions.scales.y,
                            suggestedMin: 80,
                            suggestedMax: 100
                        },
                        x: commonOptions.scales.x
                    }
                }
            });
        }

        // 5. RR Chart
        const rrCanvas = document.getElementById('rrChart');
        if (rrCanvas) {
            const rrCtx = rrCanvas.getContext('2d');
            new Chart(rrCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'معدل التنفس',
                        data: rrData,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.06)',
                        borderWidth: 2.5,
                        tension: 0.3,
                        spanGaps: true,
                        pointRadius: 3.5,
                        pointBackgroundColor: '#8b5cf6'
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            ...commonOptions.scales.y,
                            suggestedMin: 10,
                            suggestedMax: 30
                        },
                        x: commonOptions.scales.x
                    }
                }
            });
        }
    @endif
});
</script>

<!-- تم إزالة نموذج إعطاء العلاج من صفحة المقيم لكونها مسؤولية التمريض الحصرية -->
@endsection
