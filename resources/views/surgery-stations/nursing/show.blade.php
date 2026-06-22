@extends('layouts.app')

@section('content')
@php
    $postOpStation = $surgery->postOpResidentStation;
    $combinedReadings = $postOpStation ? $postOpStation->readings : collect();
    $combinedReadingsAsc = $combinedReadings->sortBy('created_at');
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-bottom border-light d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0 fw-bold text-dark"><i class="fas fa-user-nurse text-primary me-2"></i>محطة التمريض - العملية رقم {{ $surgery->id }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('nursing-station.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small">
                            <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4">
                            <i class="fas fa-check-circle me-1"></i>
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4">
                            <i class="fas fa-exclamation-circle me-1"></i>
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <!-- معلومات المريض والعملية -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light bg-opacity-50 rounded-3 border border-secondary-subtle">
                                <h5 class="fw-bold text-dark mb-3"><i class="fas fa-user-circle text-secondary me-1"></i> معلومات المريض</h5>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <th width="40%" class="text-muted">رقم الملف:</th>
                                        <td class="text-dark fw-bold">{{ $surgery->patient->file_number }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">الاسم:</th>
                                        <td class="text-dark fw-bold">{{ $surgery->patient->user->full_name }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">العمر:</th>
                                        <td class="text-dark fw-bold">{{ $surgery->patient->age ?? '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light bg-opacity-50 rounded-3 border border-secondary-subtle">
                                <h5 class="fw-bold text-dark mb-3"><i class="fas fa-info-circle text-secondary me-1"></i> معلومات العملية</h5>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <th width="40%" class="text-muted">اسم العملية:</th>
                                        <td class="text-dark fw-bold">{{ $surgery->surgery_name }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">الطبيب الجراح:</th>
                                        <td class="text-dark fw-bold">{{ $surgery->doctor?->user?->full_name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">الطبيب المقيم:</th>
                                        <td class="text-dark fw-bold">{{ $surgery->residentStation?->resident?->user?->full_name ?? '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- خطة العلاج المحددة من قبل الجراح وإعطاؤها -->
                    <div class="card border border-secondary-subtle shadow-sm rounded-3 mb-4">
                        <div class="card-header bg-light bg-opacity-50 border-bottom py-2 px-3">
                            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-pills text-primary me-1"></i> العلاجات والأدوية المطلوبة وإعطاؤها</h5>
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
                                                <th class="py-2 text-center">حالة العلاج</th>
                                                <th class="py-2 text-center">عدد الجرعات المعطاة</th>
                                                <th class="py-2 text-center" width="22%">الإجراء</th>
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
                                                    @if($treatment->status === 'cancelled')
                                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2.5 py-1.5"><i class="fas fa-times-circle me-1"></i>تم إيقاف العلاج</span>
                                                    @else
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2.5 py-1.5"><i class="fas fa-play-circle me-1"></i>نشط ومستمر</span>
                                                    @endif
                                                </td>
                                                <td class="text-center fw-bold text-dark">
                                                    {{ $treatment->administrations ? count($treatment->administrations) : 0 }} جرعات
                                                </td>
                                                <td class="text-center">
                                                    @if($treatment->status === 'planned')
                                                        <button type="button" class="btn btn-sm btn-success px-2.5 py-1 me-1" onclick="openAdministerModal({{ $treatment->id }}, '{{ addslashes($treatment->description) }}', 'administered')">
                                                            <i class="fas fa-plus-circle"></i> تسجيل إعطاء جرعة
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger px-2.5 py-1" onclick="openAdministerModal({{ $treatment->id }}, '{{ addslashes($treatment->description) }}', 'cancelled')">
                                                            <i class="fas fa-ban"></i> إيقاف العلاج
                                                        </button>
                                                    @else
                                                        <span class="text-muted small">
                                                            @if($treatment->admin_notes)
                                                                <span class="d-inline-block text-truncate" style="max-width: 150px;" title="{{ $treatment->admin_notes }}">
                                                                    تم الإيقاف: {{ $treatment->admin_notes }}
                                                                </span>
                                                            @else
                                                                تم إيقاف العلاج
                                                            @endif
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @if($treatment->administrations && count($treatment->administrations) > 0)
                                            <tr class="table-light">
                                                <td colspan="7" class="p-2 bg-light">
                                                    <div class="ps-4">
                                                        <span class="fw-bold text-secondary small d-block mb-2"><i class="fas fa-history me-1 text-info"></i> سجل إعطاء الجرعات الدورية:</span>
                                                        <div class="d-flex flex-wrap gap-2">
                                                            @foreach($treatment->administrations as $index => $admin)
                                                                <div class="badge bg-white text-dark border border-secondary-subtle p-2 rounded-2 text-start font-monospace shadow-sm" style="font-size: 0.85rem; font-weight: normal; line-height: 1.4;">
                                                                    <span class="badge bg-success me-1">الجرعة {{ $index + 1 }}</span>
                                                                    <span class="fw-bold text-dark">{{ $admin['administered_by_name'] }}</span>
                                                                    <span class="text-muted mx-1">|</span>
                                                                    <span class="text-secondary">{{ $admin['administered_at'] }}</span>
                                                                    @if(!empty($admin['notes']))
                                                                        <div class="mt-1 text-primary small fw-semibold"><i class="fas fa-comment-alt me-1 text-muted"></i>ملاحظة: {{ $admin['notes'] }}</div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endif
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
                                <div class="alert alert-warning border border-warning-subtle bg-warning bg-opacity-10 mb-0 rounded-3">
                                    <i class="fas fa-exclamation-triangle me-1"></i> لم يحدد الجراح خطة علاج بعد.
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- MAIN FORM WITH TABS -->
                    <form action="{{ route('nursing-station.update', $surgery) }}" method="POST" id="main-nursing-form">
                        @csrf
                        @method('PATCH')

                        <div class="card border border-light shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white border-bottom-0 p-3 pb-0 rounded-top-4">
                                <ul class="nav nav-pills nav-fill gap-2" id="nursingTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active rounded-pill py-2.5 d-flex align-items-center justify-content-center" id="care-tab" data-bs-toggle="tab" data-bs-target="#care-content" type="button" role="tab" aria-selected="true">
                                            <i class="fas fa-user-nurse me-2"></i> الرعاية والملحوظات التمريضية
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link rounded-pill py-2.5 d-flex align-items-center justify-content-center" id="vitals-tab" data-bs-toggle="tab" data-bs-target="#vitals-content" type="button" role="tab" aria-selected="false">
                                            <i class="fas fa-heartbeat me-2"></i> تسجيل وتاريخ العلامات الحيوية
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link rounded-pill py-2.5 d-flex align-items-center justify-content-center" id="follow-up-tab" data-bs-toggle="tab" data-bs-target="#follow-up-content" type="button" role="tab" aria-selected="false">
                                            <i class="fas fa-calendar-alt me-2"></i> ورقة المتابعة (Follow Up)
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body p-4">
                                <fieldset @if($surgery->nursingStation && $surgery->nursingStation->status === 'completed') disabled @endif>
                                    <div class="tab-content" id="nursingTabsContent">
                                        
                                        <!-- TAB 1: NURSING CARE & NOTES -->
                                        <div class="tab-pane fade show active" id="care-content" role="tabpanel" aria-labelledby="care-tab">
                                            <div class="row g-4">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label class="form-label fw-bold text-dark"><i class="fas fa-user-nurse text-primary me-1"></i> الممرض/ة المسؤول عن الحالة</label>
                                                        <select name="nurse_id" class="form-select form-select-lg rounded-3 border-secondary-subtle">
                                                            <option value="">-- اختر الممرض/ة المسؤول --</option>
                                                            @foreach($nurses as $nurse)
                                                                <option value="{{ $nurse->id }}" 
                                                                    {{ ($surgery->nursingStation?->nurse_id ?? old('nurse_id')) == $nurse->id ? 'selected' : '' }}>
                                                                    {{ $nurse->full_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label fw-semibold text-dark"><i class="fas fa-file-medical-alt text-info me-1"></i> ملحوظات التمريض (Nursing Notes)</label>
                                                        <textarea name="nursing_notes" class="form-control rounded-3 border-secondary-subtle" rows="6" placeholder="اكتب ملحوظات الرعاية التمريضية والتوجيهات المنفذة...">{{ $surgery->nursingStation?->nursing_notes ?? '' }}</textarea>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label fw-semibold text-dark"><i class="fas fa-sign-out-alt text-success me-1"></i> ملحوظات الخروج والتسريح (Discharge Notes)</label>
                                                        <textarea name="discharge_notes" class="form-control rounded-3 border-secondary-subtle" rows="6" placeholder="اكتب تعليمات وملاحظات الخروج والتسريح للمريض...">{{ $surgery->nursingStation?->discharge_notes ?? '' }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- TAB 2: VITALS ENTRY & HISTORY -->
                                        <div class="tab-pane fade" id="vitals-content" role="tabpanel" aria-labelledby="vitals-tab">
                                            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-heartbeat text-danger me-1"></i> تسجيل العلامات الحيوية الجديدة (Vital Signs Entry)</h6>
                                            <div class="row g-3 mb-4 row-cols-1 row-cols-sm-5">
                                                <!-- BP Card -->
                                                <div class="col">
                                                    <div class="card border border-danger-subtle bg-danger bg-opacity-10 rounded-3 text-center p-3 h-100">
                                                        <label class="form-label fw-bold text-danger mb-1 small"><i class="fas fa-tachometer-alt"></i> ضغط الدم BP</label>
                                                        <input type="text" name="bp" class="form-control text-center rounded-2 border-danger-subtle bg-white" placeholder="120/80" value="{{ $postOpStation?->bp ?? '' }}">
                                                    </div>
                                                </div>
                                                <!-- Temp Card -->
                                                <div class="col">
                                                    <div class="card border border-warning-subtle bg-warning bg-opacity-10 rounded-3 text-center p-3 h-100">
                                                        <label class="form-label fw-bold text-warning-emphasis mb-1 small"><i class="fas fa-thermometer-half"></i> الحرارة Temp</label>
                                                        <input type="text" name="temp" class="form-control text-center rounded-2 border-warning-subtle bg-white" placeholder="37°C" value="{{ $postOpStation?->temp ?? '' }}">
                                                    </div>
                                                </div>
                                                <!-- Pulse Card -->
                                                <div class="col">
                                                    <div class="card border border-danger-subtle bg-danger bg-opacity-10 rounded-3 text-center p-3 h-100">
                                                        <label class="form-label fw-bold text-danger mb-1 small"><i class="fas fa-heart"></i> النبض PR</label>
                                                        <input type="text" name="pr" class="form-control text-center rounded-2 border-danger-subtle bg-white" placeholder="80 bpm" value="{{ $postOpStation?->pr ?? '' }}">
                                                    </div>
                                                </div>
                                                <!-- RR Card -->
                                                <div class="col">
                                                    <div class="card border border-info-subtle bg-info bg-opacity-10 rounded-3 text-center p-3 h-100">
                                                        <label class="form-label fw-bold text-info-emphasis mb-1 small"><i class="fas fa-lungs"></i> التنفس RR</label>
                                                        <input type="text" name="rr" class="form-control text-center rounded-2 border-info-subtle bg-white" placeholder="16 /min" value="{{ $postOpStation?->rr ?? '' }}">
                                                    </div>
                                                </div>
                                                <!-- SPO2 Card -->
                                                <div class="col">
                                                    <div class="card border border-success-subtle bg-success bg-opacity-10 rounded-3 text-center p-3 h-100">
                                                        <label class="form-label fw-bold text-success mb-1 small"><i class="fas fa-wind"></i> أكسجين SPo2</label>
                                                        <input type="text" name="spo2" class="form-control text-center rounded-2 border-success-subtle bg-white" placeholder="98%" value="{{ $postOpStation?->spo2 ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Clinical Exam Input -->
                                            <div class="form-group mb-4">
                                                <label class="form-label fw-semibold text-dark"><i class="fas fa-stethoscope text-primary me-1"></i> الفحص السريري العام وملاحظات الحالة (Clinical Examination / Status)</label>
                                                <textarea name="clinical_examination" class="form-control rounded-3 border-secondary-subtle" rows="4" placeholder="اكتب الملاحظات السريرية ونتائج فحص المريض الحالية...">{{ $postOpStation?->clinical_examination ?? '' }}</textarea>
                                            </div>

                                            <hr class="my-4">

                                            <!-- History Chart Section -->
                                            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-chart-line text-info me-1"></i> مخططات الحالة الحيوية التاريخية (Vitals History Trends)</h6>
                                            
                                            @if($combinedReadings->count() > 0)
                                                <!-- Grid for charts -->
                                                <div class="row g-3 mb-4">
                                                    <div class="col-md-6">
                                                        <div class="card border border-secondary-subtle rounded-3 p-3">
                                                            <h6 class="text-center fw-bold text-danger mb-2">ضغط الدم (BP)</h6>
                                                            <div style="height: 220px; position: relative;">
                                                                <canvas id="bpChart"></canvas>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card border border-secondary-subtle rounded-3 p-3">
                                                            <h6 class="text-center fw-bold text-warning mb-2">درجة الحرارة (Temp)</h6>
                                                            <div style="height: 220px; position: relative;">
                                                                <canvas id="tempChart"></canvas>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card border border-secondary-subtle rounded-3 p-3">
                                                            <h6 class="text-center fw-bold text-danger mb-2">معدل نبضات القلب (PR)</h6>
                                                            <div style="height: 220px; position: relative;">
                                                                <canvas id="pulseChart"></canvas>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card border border-secondary-subtle rounded-3 p-3">
                                                            <h6 class="text-center fw-bold text-success mb-2">نسبة الأكسجين (SPo2)</h6>
                                                            <div style="height: 220px; position: relative;">
                                                                <canvas id="spo2Chart"></canvas>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Readings History Table -->
                                                <h6 class="fw-bold text-dark mb-2"><i class="fas fa-history text-secondary me-1"></i> سجل القراءات والقياسات الحيوية</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-bordered table-sm align-middle text-center mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th class="py-2">تاريخ/وقت التسجيل</th>
                                                                <th class="py-2">بواسطة</th>
                                                                <th class="py-2">ضغط الدم</th>
                                                                <th class="py-2">الحرارة</th>
                                                                <th class="py-2">النبض</th>
                                                                <th class="py-2">التنفس</th>
                                                                <th class="py-2">الأكسجين</th>
                                                                <th class="py-2">الملاحظات السريرية / التمريضية</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($combinedReadings as $reading)
                                                            <tr>
                                                                <td class="text-dark">{{ $reading->created_at->format('Y-m-d h:i A') }}</td>
                                                                <td class="text-dark fw-bold text-info">{{ $reading->resident?->user?->full_name ?? $reading->notes }}</td>
                                                                <td class="text-dark fw-bold text-danger">{{ $reading->bp ?? '-' }}</td>
                                                                <td class="text-dark fw-bold text-warning-emphasis">{{ $reading->temp ? $reading->temp . '°C' : '-' }}</td>
                                                                <td class="text-dark fw-bold text-danger">{{ $reading->pr ? $reading->pr . ' bpm' : '-' }}</td>
                                                                <td class="text-dark fw-bold text-info-emphasis">{{ $reading->rr ? $reading->rr . ' /min' : '-' }}</td>
                                                                <td class="text-dark fw-bold text-success">{{ $reading->spo2 ? $reading->spo2 . '%' : '-' }}</td>
                                                                <td class="text-start small text-dark">{{ $reading->clinical_examination ?? '-' }}</td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="alert alert-light border text-center py-4 rounded-3">
                                                    <i class="fas fa-heartbeat text-danger me-1 fs-5"></i> لا توجد علامات حيوية مسجلة مسبقاً لعرضها في الرسوم البيانية.
                                                </div>
                                            @endif
                                        </div>

                                        <!-- TAB 3: FOLLOW UP SHEET -->
                                        <div class="tab-pane fade" id="follow-up-content" role="tabpanel" aria-labelledby="follow-up-tab">
                                            <div class="row g-4">
                                                <!-- New Follow Up Entry Form Column -->
                                                <div class="col-md-5">
                                                    <div class="card border border-success-subtle bg-success bg-opacity-5 p-3 rounded-3">
                                                        <h6 class="fw-bold text-success mb-3"><i class="fas fa-plus-circle me-1"></i> تسجيل ملاحظة متابعة جديدة (New Entry)</h6>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold text-dark"><i class="fas fa-calendar-day text-secondary me-1"></i> تاريخ المتابعة</label>
                                                            <input form="nursing-follow-up-form" type="date" name="follow_up_date" class="form-control rounded-3 border-secondary-subtle" value="{{ now()->format('Y-m-d') }}">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold text-dark"><i class="fas fa-clock text-secondary me-1"></i> الوردية / الدوام</label>
                                                            <select form="nursing-follow-up-form" name="session" class="form-select rounded-3 border-secondary-subtle">
                                                                <option value="morning">صباحاً</option>
                                                                <option value="evening">مساءً</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold text-dark"><i class="fas fa-comment-dots text-secondary me-1"></i> ملاحظات وتوجيهات المتابعة</label>
                                                            <textarea form="nursing-follow-up-form" name="notes" class="form-control rounded-3 border-secondary-subtle" rows="4" placeholder="اكتب تفاصيل حالة المريض خلال المتابعة والوردية..."></textarea>
                                                        </div>

                                                        <button form="nursing-follow-up-form" type="submit" class="btn btn-success w-100 rounded-pill py-2 shadow-sm">
                                                            <i class="fas fa-save me-1"></i> حفظ وتدوين المتابعة
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Follow Up Logs Table Column -->
                                                <div class="col-md-7">
                                                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-history text-secondary me-1"></i> سجل ورقة المتابعة التاريخية (Follow Up Logs)</h6>
                                                    @php
                                                        $followUps = $postOpStation ? $postOpStation->followUps : collect();
                                                    @endphp
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-striped mb-0 align-middle">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th class="py-2 text-center" width="20%">تاريخ المتابعة</th>
                                                                    <th class="py-2 text-center" width="15%">الوردية</th>
                                                                    <th class="py-2 text-center" width="25%">الطبيب المقيم / المسجل</th>
                                                                    <th class="py-2">الملاحظات</th>
                                                                    <th class="py-2 text-center" width="20%">تاريخ التسجيل</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @forelse($followUps as $followUp)
                                                                    <tr>
                                                                        <td class="text-dark text-center fw-bold">{{ $followUp->follow_up_date->format('Y-m-d') }}</td>
                                                                        <td class="text-dark text-center">{{ $followUp->session === 'morning' ? 'صباحاً' : 'مساءً' }}</td>
                                                                        <td class="text-dark text-center small">{{ $followUp->resident?->user?->full_name ?? $followUp->resident_name ?? 'غير حدد' }}</td>
                                                                        <td class="text-dark">{!! nl2br(e($followUp->notes)) !!}</td>
                                                                        <td class="text-dark text-center small">{{ $followUp->created_at->format('Y-m-d H:i') }}</td>
                                                                    </tr>
                                                                @empty
                                                                    <tr>
                                                                        <td colspan="5" class="text-center text-muted py-4">
                                                                            <i class="fas fa-info-circle me-1"></i> لم تُسجل أي ملاحظات متابعة بعد.
                                                                        </td>
                                                                    </tr>
                                                                @endforelse
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </fieldset>
                            </div>
                            
                            <!-- Save and Complete Actions footer -->
                            @if(!$surgery->nursingStation || $surgery->nursingStation->status !== 'completed')
                                <div class="card-footer bg-light p-4 d-flex gap-3 rounded-bottom-4">
                                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2.5 shadow-sm">
                                        <i class="fas fa-save me-1"></i> حفظ البيانات الحالية
                                    </button>

                                    @if($surgery->nursingStation && $surgery->nursingStation->status !== 'completed')
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

                    <!-- نموذج الإتمام -->
                    <form id="complete-form" action="{{ route('nursing-station.complete', $surgery) }}" method="POST" style="display: none;">
                        @csrf
                    </form>

                    <!-- معلومات المحطة -->
                    @if($surgery->nursingStation)
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="p-3 bg-light rounded-3 border">
                                <h5 class="fw-bold text-dark mb-3"><i class="fas fa-info-circle text-secondary me-1"></i> معلومات الحالة للمحطة</h5>
                                <table class="table table-sm table-bordered bg-white mb-0">
                                    <tr>
                                        <th width="20%">حالة المحطة:</th>
                                        <td>
                                            @if($surgery->nursingStation->status === 'pending')
                                                <span class="badge bg-warning text-white">معلقة</span>
                                            @elseif($surgery->nursingStation->status === 'in_progress')
                                                <span class="badge bg-info text-white">جارية</span>
                                            @else
                                                <span class="badge bg-success text-white">مكتملة</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($surgery->nursingStation->started_at)
                                    <tr>
                                        <th>تاريخ البدء:</th>
                                        <td>{{ $surgery->nursingStation->started_at->format('Y-m-d h:i A') }}</td>
                                    </tr>
                                    @endif
                                    @if($surgery->nursingStation->completed_at)
                                    <tr>
                                        <th>تاريخ الإتمام:</th>
                                        <td>{{ $surgery->nursingStation->completed_at->format('Y-m-d h:i A') }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نافذة تسجيل إعطاء/إلغاء العلاج -->
<div class="modal fade" id="administerTreatmentModal" tabindex="-1" aria-labelledby="administerTreatmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="administerTreatmentForm" action="" method="POST">
                @csrf
                <input type="hidden" name="status" id="modalTreatmentStatus">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="administerTreatmentModalLabel">تسجيل جرعة علاجية</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3 fw-semibold">العلاج: <span id="modalTreatmentDescription" class="text-primary fw-bold"></span></p>
                    
                    <div class="form-group mb-3">
                        <label id="modalNotesLabel" for="modalAdminNotes" class="form-label fw-bold">ملاحظات الإعطاء (اختياري)</label>
                        <textarea class="form-control" name="admin_notes" id="modalAdminNotes" rows="3" placeholder="أدخل أي ملاحظات حول إعطاء الجرعة أو سبب إيقاف العلاج..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="submit" class="btn id-submit-btn">تأكيد</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Follow-Up Form -->
<form id="nursing-follow-up-form" action="{{ route('resident-station.follow-ups.store', $surgery) }}" method="POST" style="display: none;">
    @csrf
</form>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function openAdministerModal(treatmentId, description, status) {
        const modal = new bootstrap.Modal(document.getElementById('administerTreatmentModal'));
        
        // ضبط عنوان النافذة وزر الإرسال بناءً على الإجراء
        const label = document.getElementById('administerTreatmentModalLabel');
        const notesLabel = document.getElementById('modalNotesLabel');
        const submitBtn = document.querySelector('#administerTreatmentForm .id-submit-btn');
        const notesTextarea = document.getElementById('modalAdminNotes');
        
        if (status === 'administered') {
            label.textContent = 'تسجيل إعطاء جرعة علاجية';
            notesLabel.textContent = 'ملاحظات إعطاء الجرعة (اختياري)';
            notesTextarea.placeholder = 'أدخل ملاحظاتك حول إعطاء هذه الجرعة (مثال: تم إعطاؤها وريدياً، تناولها مع الطعام...)';
            submitBtn.textContent = 'تأكيد إعطاء الجرعة';
            submitBtn.className = 'btn btn-success';
        } else {
            label.textContent = 'إيقاف العلاج بالكامل';
            notesLabel.textContent = 'سبب إيقاف العلاج (مطلوب)';
            notesTextarea.placeholder = 'أدخل سبب إيقاف هذا العلاج للمريض (مطلوب)...';
            notesTextarea.required = true;
            submitBtn.textContent = 'تأكيد إيقاف العلاج';
            submitBtn.className = 'btn btn-danger';
        }
        
        // تعبئة البيانات
        document.getElementById('modalTreatmentDescription').textContent = description;
        document.getElementById('modalTreatmentStatus').value = status;
        document.getElementById('modalAdminNotes').value = '';
        
        // ضبط رابط النموذج
        const form = document.getElementById('administerTreatmentForm');
        form.action = `/surgery-stations/treatments/${treatmentId}/administer`;
        
        modal.show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Check if we have readings data
        @if($combinedReadings->count() > 0)
            @php
                $chartData = $combinedReadingsAsc->map(function($item) {
                    return [
                        'created_at' => $item->created_at instanceof \Carbon\Carbon ? $item->created_at->toIso8601String() : \Carbon\Carbon::parse($item->created_at)->toIso8601String(),
                        'bp' => $item->bp,
                        'temp' => $item->temp,
                        'pr' => $item->pr,
                        'rr' => $item->rr,
                        'spo2' => $item->spo2,
                    ];
                })->values();
            @endphp
            const rawData = @json($chartData);
            
            const labels = rawData.map((item, idx) => {
                const dateObj = new Date(item.created_at);
                const timeStr = dateObj.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
                return `#${idx + 1} (${timeStr})`;
            });

            const systolicData = [];
            const diastolicData = [];
            const prData = [];
            const tempData = [];
            const spo2Data = [];
            const rrData = [];

            rawData.forEach(item => {
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
                                label: 'الانقباضي',
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
                                label: 'الانبساطي',
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
        @endif
    });
</script>
@endsection
