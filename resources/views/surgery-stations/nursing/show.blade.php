@extends('layouts.app')

@section('content')
@php
    $postOpStation = $surgery->postOpResidentStation;
    $currentStationName = $surgery->getCurrentStation();
    if ($currentStationName === 'resident_pre_op' || !$postOpStation) {
        $postOpStation = $surgery->preOpResidentStation;
    }
    $combinedReadings = $postOpStation ? $postOpStation->readings : collect();
    $combinedReadingsAsc = $combinedReadings->sortBy('created_at');

    if (!function_exists('isTempNormal')) {
        function isTempNormal($val) {
            if (empty($val)) return true;
            $num = (float) filter_var($val, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            return ($num >= 36.5 && $num <= 37.5);
        }
        function isBpNormal($val) {
            if (empty($val)) return true;
            $parts = explode('/', $val);
            if (count($parts) === 2) {
                $sys = (int) filter_var($parts[0], FILTER_SANITIZE_NUMBER_INT);
                $dia = (int) filter_var($parts[1], FILTER_SANITIZE_NUMBER_INT);
                return ($sys >= 90 && $sys <= 120 && $dia >= 60 && $dia <= 80);
            }
            return true;
        }
        function isPrNormal($val) {
            if (empty($val)) return true;
            $num = (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT);
            return ($num >= 60 && $num <= 100);
        }
        function isRrNormal($val) {
            if (empty($val)) return true;
            $num = (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT);
            return ($num >= 12 && $num <= 20);
        }
        function isSpo2Normal($val) {
            if (empty($val)) return true;
            $num = (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT);
            return ($num >= 95 && $num <= 100);
        }
        function isPainNormal($val) {
            if (empty($val)) return true;
            $num = (int) $val;
            return ($num <= 3);
        }
        function isRbsNormal($val) {
            if (empty($val)) return true;
            $num = (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT);
            return ($num >= 70 && $num <= 140);
        }
        function isGcsNormal($val) {
            if (empty($val)) return true;
            $parts = explode('/', $val);
            $num = (int) filter_var($parts[0], FILTER_SANITIZE_NUMBER_INT);
            return ($num === 15);
        }
        function isCrtNormal($val) {
            if (empty($val)) return true;
            preg_match('/\d+/', $val, $matches);
            if (!empty($matches)) {
                $num = (int) $matches[0];
                return ($num <= 2);
            }
            $clean = mb_strtolower($val);
            if (str_contains($clean, 'أقل') || str_contains($clean, 'ثانيت') || str_contains($clean, 'طبيعي') || str_contains($clean, 'normal')) {
                return true;
            }
            return false;
        }
    }
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
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link rounded-pill py-2.5 d-flex align-items-center justify-content-center" id="fluids-tab" data-bs-toggle="tab" data-bs-target="#fluids-content" type="button" role="tab" aria-selected="false">
                                            <i class="fas fa-tint me-2"></i> مخطط السوائل (Intake & Output)
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
                                                    <div class="p-3 bg-light rounded-3 border border-secondary-subtle">
                                                        <span class="fw-bold text-dark"><i class="fas fa-user-nurse text-primary me-1"></i> الممرض/ة المسؤول عن الحالة: </span>
                                                        <span class="fw-semibold text-secondary">
                                                            {{ $surgery->nursingStation?->nurse?->full_name ?? Auth::user()->full_name ?? Auth::user()->name }}
                                                        </span>
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
                                            <!-- Row 1: Traditional Vitals -->
                                            <div class="row g-3 mb-3 row-cols-1 row-cols-sm-5">
                                                <!-- BP Card -->
                                                <div class="col">
                                                    <div class="card border border-danger-subtle bg-danger bg-opacity-10 rounded-3 text-center p-3 h-100" id="card-bp">
                                                        <label class="form-label fw-bold text-danger mb-0 small"><i class="fas fa-tachometer-alt"></i> ضغط الدم BP</label>
                                                        <span class="text-muted d-block small mb-1" style="font-size: 0.75rem;">90/60 - 120/80 mmHg</span>
                                                        <input type="text" name="bp" id="input-bp" class="form-control text-center rounded-2 border-danger-subtle bg-white" placeholder="120/80" value="{{ $postOpStation?->bp ?? '' }}">
                                                    </div>
                                                </div>
                                                <!-- Temp Card -->
                                                <div class="col">
                                                    <div class="card border border-warning-subtle bg-warning bg-opacity-10 rounded-3 text-center p-3 h-100" id="card-temp">
                                                        <label class="form-label fw-bold text-warning-emphasis mb-0 small"><i class="fas fa-thermometer-half"></i> الحرارة Temp</label>
                                                        <span class="text-muted d-block small mb-1" style="font-size: 0.75rem;">36.5 - 37.5 °C</span>
                                                        <input type="text" name="temp" id="input-temp" class="form-control text-center rounded-2 border-warning-subtle bg-white" placeholder="37°C" value="{{ $postOpStation?->temp ?? '' }}">
                                                    </div>
                                                </div>
                                                <!-- Pulse Card -->
                                                <div class="col">
                                                    <div class="card border border-danger-subtle bg-danger bg-opacity-10 rounded-3 text-center p-3 h-100" id="card-pr">
                                                        <label class="form-label fw-bold text-danger mb-0 small"><i class="fas fa-heart"></i> النبض PR</label>
                                                        <span class="text-muted d-block small mb-1" style="font-size: 0.75rem;">60 - 100 bpm</span>
                                                        <input type="text" name="pr" id="input-pr" class="form-control text-center rounded-2 border-danger-subtle bg-white" placeholder="80 bpm" value="{{ $postOpStation?->pr ?? '' }}">
                                                    </div>
                                                </div>
                                                <!-- RR Card -->
                                                <div class="col">
                                                    <div class="card border border-info-subtle bg-info bg-opacity-10 rounded-3 text-center p-3 h-100" id="card-rr">
                                                        <label class="form-label fw-bold text-info-emphasis mb-0 small"><i class="fas fa-lungs"></i> التنفس RR</label>
                                                        <span class="text-muted d-block small mb-1" style="font-size: 0.75rem;">12 - 20 /min</span>
                                                        <input type="text" name="rr" id="input-rr" class="form-control text-center rounded-2 border-info-subtle bg-white" placeholder="16 /min" value="{{ $postOpStation?->rr ?? '' }}">
                                                    </div>
                                                </div>
                                                <!-- SPO2 Card -->
                                                <div class="col">
                                                    <div class="card border border-success-subtle bg-success bg-opacity-10 rounded-3 text-center p-3 h-100" id="card-spo2">
                                                        <label class="form-label fw-bold text-success mb-0 small"><i class="fas fa-wind"></i> أكسجين SPo2</label>
                                                        <span class="text-muted d-block small mb-1" style="font-size: 0.75rem;">95 - 100%</span>
                                                        <input type="text" name="spo2" id="input-spo2" class="form-control text-center rounded-2 border-success-subtle bg-white" placeholder="98%" value="{{ $postOpStation?->spo2 ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Row 2: Advanced Vitals -->
                                            <div class="row g-3 mb-4 row-cols-1 row-cols-sm-4">
                                                <!-- Pain Score Card -->
                                                <div class="col">
                                                    <div class="card border border-warning-subtle bg-warning bg-opacity-10 rounded-3 text-center p-3 h-100" id="card-pain">
                                                        <label class="form-label fw-bold text-warning-emphasis mb-0 small"><i class="fas fa-angry text-danger"></i> مستوى الألم Pain</label>
                                                        <span class="text-muted d-block small mb-1" style="font-size: 0.75rem;">0 (بدون ألم) - 10 (أشد ألم)</span>
                                                        <select name="pain_score" id="input-pain" class="form-select text-center rounded-2 border-warning-subtle bg-white">
                                                            <option value="">اختر</option>
                                                            @for($i=0; $i<=10; $i++)
                                                                <option value="{{ $i }}" {{ ($postOpStation?->pain_score ?? '') == (string)$i ? 'selected' : '' }}>{{ $i }}</option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                </div>
                                                <!-- RBS Card -->
                                                <div class="col">
                                                    <div class="card border border-danger-subtle bg-danger bg-opacity-10 rounded-3 text-center p-3 h-100" id="card-rbs">
                                                        <label class="form-label fw-bold text-danger mb-0 small"><i class="fas fa-chart-bar"></i> السكر العشوائي RBS</label>
                                                        <span class="text-muted d-block small mb-1" style="font-size: 0.75rem;">70 - 140 mg/dL</span>
                                                        <input type="text" name="rbs" id="input-rbs" class="form-control text-center rounded-2 border-danger-subtle bg-white" placeholder="100 mg/dL" value="{{ $postOpStation?->rbs ?? '' }}">
                                                    </div>
                                                </div>
                                                <!-- GCS Card -->
                                                <div class="col">
                                                    <div class="card border border-info-subtle bg-info bg-opacity-10 rounded-3 text-center p-3 h-100" id="card-gcs">
                                                        <label class="form-label fw-bold text-info-emphasis mb-0 small"><i class="fas fa-brain"></i> مقياس الوعي GCS</label>
                                                        <span class="text-muted d-block small mb-1" style="font-size: 0.75rem;">15/15 طبيعي</span>
                                                        <input type="text" name="gcs" id="input-gcs" class="form-control text-center rounded-2 border-info-subtle bg-white" placeholder="15/15" value="{{ $postOpStation?->gcs ?? '' }}">
                                                    </div>
                                                </div>
                                                <!-- CRT Card -->
                                                <div class="col">
                                                    <div class="card border border-success-subtle bg-success bg-opacity-10 rounded-3 text-center p-3 h-100" id="card-crt">
                                                        <label class="form-label fw-bold text-success mb-0 small"><i class="fas fa-hand-holding"></i> امتلاء الشعيرات CRT</label>
                                                        <span class="text-muted d-block small mb-1" style="font-size: 0.75rem;">أقل من ثانيتين</span>
                                                        <input type="text" name="crt" id="input-crt" class="form-control text-center rounded-2 border-success-subtle bg-white" placeholder="أقل من ثانيتين" value="{{ $postOpStation?->crt ?? '' }}">
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
                                                                <th class="py-2">الألم</th>
                                                                <th class="py-2">RBS</th>
                                                                <th class="py-2">GCS</th>
                                                                <th class="py-2">CRT</th>
                                                                <th class="py-2">الملاحظات السريرية / التمريضية</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($combinedReadings as $reading)
                                                            <tr>
                                                                <td class="text-dark">{{ $reading->created_at->format('Y-m-d h:i A') }}</td>
                                                                <td class="text-dark fw-bold text-info">{{ $reading->resident?->user?->full_name ?? $reading->notes }}</td>
                                                                <td class="text-dark fw-bold {{ isBpNormal($reading->bp) ? 'text-success' : 'text-danger' }}">{{ $reading->bp ?? '-' }}</td>
                                                                <td class="text-dark fw-bold {{ isTempNormal($reading->temp) ? 'text-success' : 'text-danger' }}">{{ $reading->temp ? $reading->temp . '°C' : '-' }}</td>
                                                                <td class="text-dark fw-bold {{ isPrNormal($reading->pr) ? 'text-success' : 'text-danger' }}">{{ $reading->pr ? $reading->pr . ' bpm' : '-' }}</td>
                                                                <td class="text-dark fw-bold {{ isRrNormal($reading->rr) ? 'text-success' : 'text-danger' }}">{{ $reading->rr ? $reading->rr . ' /min' : '-' }}</td>
                                                                <td class="text-dark fw-bold {{ isSpo2Normal($reading->spo2) ? 'text-success' : 'text-danger' }}">{{ $reading->spo2 ? $reading->spo2 . '%' : '-' }}</td>
                                                                <td class="text-dark fw-bold {{ isPainNormal($reading->pain_score) ? 'text-success' : 'text-danger' }}">{{ $reading->pain_score ?? '-' }}</td>
                                                                <td class="text-dark fw-bold {{ isRbsNormal($reading->rbs) ? 'text-success' : 'text-danger' }}">{{ $reading->rbs ? $reading->rbs . ' mg/dL' : '-' }}</td>
                                                                <td class="text-dark fw-bold {{ isGcsNormal($reading->gcs) ? 'text-success' : 'text-danger' }}">{{ $reading->gcs ?? '-' }}</td>
                                                                <td class="text-dark fw-bold {{ isCrtNormal($reading->crt) ? 'text-success' : 'text-danger' }}">{{ $reading->crt ?? '-' }}</td>
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
                                                                        <td class="text-dark text-center small">{{ $followUp->resident?->user?->full_name ?? $followUp->resident_name ?? 'غير محدد' }}</td>
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

                                        <!-- TAB 4: FLUID BALANCE CHART (Intake & Output) -->
                                        <div class="tab-pane fade" id="fluids-content" role="tabpanel" aria-labelledby="fluids-tab">
                                            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-tint text-primary me-1"></i> مخطط السوائل (Intake & Output Chart)</h6>
                                            
                                            <div class="row g-4">
                                                <!-- Intake Inputs -->
                                                <div class="col-md-6">
                                                    <div class="card border border-primary-subtle bg-primary bg-opacity-5 p-3 rounded-3 h-100">
                                                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="fas fa-arrow-alt-circle-down"></i> المدخلات / المتناول (Intake)</h6>
                                                        
                                                        <div class="row g-3">
                                                            <div class="col-md-12">
                                                                <div class="form-group">
                                                                    <label class="form-label fw-semibold text-dark">السوائل الوريدية (IV Fluids) <span class="text-muted small">(مل)</span></label>
                                                                    <input type="number" step="0.01" min="0" name="intake_iv_fluids" id="intake_iv_fluids" class="form-control rounded-3 border-secondary-subtle fluid-calc-input text-center" placeholder="0.00" value="{{ $postOpStation?->intake_iv_fluids ?? '' }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-12">
                                                                <div class="form-group">
                                                                    <label class="form-label fw-semibold text-dark">المدخول الفموي (Oral Intake) <span class="text-muted small">(مل)</span></label>
                                                                    <input type="number" step="0.01" min="0" name="intake_oral" id="intake_oral" class="form-control rounded-3 border-secondary-subtle fluid-calc-input text-center" placeholder="0.00" value="{{ $postOpStation?->intake_oral ?? '' }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-12">
                                                                <div class="form-group">
                                                                    <label class="form-label fw-semibold text-dark">الدم ومشتقاته (Blood & Blood Products) <span class="text-muted small">(مل)</span></label>
                                                                    <input type="number" step="0.01" min="0" name="intake_blood" id="intake_blood" class="form-control rounded-3 border-secondary-subtle fluid-calc-input text-center" placeholder="0.00" value="{{ $postOpStation?->intake_blood ?? '' }}">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Output Inputs -->
                                                <div class="col-md-6">
                                                    <div class="card border border-danger-subtle bg-danger bg-opacity-5 p-3 rounded-3 h-100">
                                                        <h6 class="fw-bold text-danger mb-3 border-bottom pb-2"><i class="fas fa-arrow-alt-circle-up"></i> المخرجات / المطروح (Output)</h6>
                                                        
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="form-label fw-semibold text-dark">البول (Urine) <span class="text-muted small">(مل)</span></label>
                                                                    <input type="number" step="0.01" min="0" name="output_urine" id="output_urine" class="form-control rounded-3 border-secondary-subtle fluid-calc-input text-center" placeholder="0.00" value="{{ $postOpStation?->output_urine ?? '' }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="form-label fw-semibold text-dark">المنزح / الدرنقة (Drain) <span class="text-muted small">(مل)</span></label>
                                                                    <input type="number" step="0.01" min="0" name="output_drain" id="output_drain" class="form-control rounded-3 border-secondary-subtle fluid-calc-input text-center" placeholder="0.00" value="{{ $postOpStation?->output_drain ?? '' }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="form-label fw-semibold text-dark">أنبوب التغذية G-Tube/NG <span class="text-muted small">(مل)</span></label>
                                                                    <input type="number" step="0.01" min="0" name="output_gtube_ng" id="output_gtube_ng" class="form-control rounded-3 border-secondary-subtle fluid-calc-input text-center" placeholder="0.00" value="{{ $postOpStation?->output_gtube_ng ?? '' }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="form-label fw-semibold text-dark">القيء (Vomiting) <span class="text-muted small">(مل)</span></label>
                                                                    <input type="number" step="0.01" min="0" name="output_vomiting" id="output_vomiting" class="form-control rounded-3 border-secondary-subtle fluid-calc-input text-center" placeholder="0.00" value="{{ $postOpStation?->output_vomiting ?? '' }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-12">
                                                                <div class="form-group">
                                                                    <label class="form-label fw-semibold text-dark">البراز (Stool) <span class="text-muted small">(مل)</span></label>
                                                                    <input type="number" step="0.01" min="0" name="output_stool" id="output_stool" class="form-control rounded-3 border-secondary-subtle fluid-calc-input text-center" placeholder="0.00" value="{{ $postOpStation?->output_stool ?? '' }}">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Summary Balance & Normal Ranges -->
                                            <div class="row g-3 mt-3">
                                                <div class="col-md-12">
                                                    <div class="card border border-info-subtle bg-info bg-opacity-5 p-3 rounded-3 h-100 d-flex flex-column justify-content-between">
                                                        <div>
                                                            <h6 class="fw-bold text-info-emphasis mb-2"><i class="fas fa-info-circle"></i> المؤشرات والمعدلات الطبيعية للسوائل</h6>
                                                            <ul class="text-secondary small ps-0 pe-3 mb-0" style="line-height: 1.6;">
                                                                <li>معدل إخراج البول الطبيعي: لا يقل عن 0.5 مل/كغم/ساعة (تقريباً 30-50 مل/ساعة للبالغ).</li>
                                                                <li>إجمالي التوازن المائي اليومي الطبيعي يكون متوازناً وقريباً من الصفر أو إيجابياً قليلاً تبعاً لحالة المريض السريرية وتوجيهات الطبيب.</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Fluids and Advanced Charts -->
                                            @if($combinedReadings->count() > 0)
                                                <div class="row g-3 mt-3">
                                                    <div class="col-md-6">
                                                        <div class="card border border-secondary-subtle rounded-3 p-3">
                                                            <h6 class="text-center fw-bold text-purple mb-2" style="color: #8b5cf6;">معدل التنفس (RR)</h6>
                                                            <div style="height: 220px; position: relative;">
                                                                <canvas id="rrChart"></canvas>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card border border-secondary-subtle rounded-3 p-3">
                                                            <h6 class="text-center fw-bold text-danger mb-2">السكر العشوائي (RBS)</h6>
                                                            <div style="height: 220px; position: relative;">
                                                                <canvas id="rbsChart"></canvas>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card border border-secondary-subtle rounded-3 p-3">
                                                            <h6 class="text-center fw-bold text-warning mb-2">مستوى الألم (Pain Score)</h6>
                                                            <div style="height: 220px; position: relative;">
                                                                <canvas id="painChart"></canvas>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card border border-secondary-subtle rounded-3 p-3">
                                                            <h6 class="text-center fw-bold text-info mb-2">مقياس الوعي (GCS)</h6>
                                                            <div style="height: 220px; position: relative;">
                                                                <canvas id="gcsChart"></canvas>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="card border border-secondary-subtle rounded-3 p-3">
                                                            <h6 class="text-center fw-bold text-primary mb-2">منحنى صافي التوازن المائي (Net Fluid Balance Trend)</h6>
                                                            <div style="height: 220px; position: relative;">
                                                                <canvas id="fluidBalanceChart"></canvas>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Historical Fluid Chart Log -->
                                            @php
                                                $fluidReadings = $combinedReadings->filter(function($r) {
                                                    return !is_null($r->intake_iv_fluids) || !is_null($r->intake_oral) || !is_null($r->intake_blood) ||
                                                           !is_null($r->output_urine) || !is_null($r->output_drain) || !is_null($r->output_gtube_ng) ||
                                                           !is_null($r->output_vomiting) || !is_null($r->output_stool);
                                                });
                                            @endphp
                                            @if($fluidReadings->count() > 0)
                                                <h6 class="fw-bold text-dark mt-4 mb-2"><i class="fas fa-history text-secondary me-1"></i> سجل قياسات السوائل التاريخي</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-bordered table-sm align-middle text-center mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th rowspan="2" class="align-middle py-2">تاريخ/وقت التسجيل</th>
                                                                <th colspan="3" class="table-primary py-1">المدخلات (Intake)</th>
                                                                <th colspan="5" class="table-danger py-1">المخرجات (Output)</th>
                                                                <th rowspan="2" class="align-middle py-2">صافي التوازن</th>
                                                                <th rowspan="2" class="align-middle py-2">سُجِّل بواسطة</th>
                                                            </tr>
                                                            <tr>
                                                                <th class="small py-1">محلول وريدي</th>
                                                                <th class="small py-1">فموي</th>
                                                                <th class="small py-1">دم</th>
                                                                <th class="small py-1">بول</th>
                                                                <th class="small py-1">درنقة</th>
                                                                <th class="small py-1">أنبوب معدة</th>
                                                                <th class="small py-1">قيء</th>
                                                                <th class="small py-1">براز</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($fluidReadings as $reading)
                                                            <tr>
                                                                <td class="text-dark">{{ $reading->created_at->format('Y-m-d h:i A') }}</td>
                                                                <td class="text-dark fw-bold text-primary">{{ !is_null($reading->intake_iv_fluids) ? $reading->intake_iv_fluids . ' مل' : '-' }}</td>
                                                                <td class="text-dark fw-bold text-primary">{{ !is_null($reading->intake_oral) ? $reading->intake_oral . ' مل' : '-' }}</td>
                                                                <td class="text-dark fw-bold text-primary">{{ !is_null($reading->intake_blood) ? $reading->intake_blood . ' مل' : '-' }}</td>
                                                                <td class="text-dark fw-bold text-danger">{{ !is_null($reading->output_urine) ? $reading->output_urine . ' مل' : '-' }}</td>
                                                                <td class="text-dark fw-bold text-danger">{{ !is_null($reading->output_drain) ? $reading->output_drain . ' مل' : '-' }}</td>
                                                                <td class="text-dark fw-bold text-danger">{{ !is_null($reading->output_gtube_ng) ? $reading->output_gtube_ng . ' مل' : '-' }}</td>
                                                                <td class="text-dark fw-bold text-danger">{{ !is_null($reading->output_vomiting) ? $reading->output_vomiting . ' مل' : '-' }}</td>
                                                                <td class="text-dark fw-bold text-danger">{{ !is_null($reading->output_stool) ? $reading->output_stool . ' مل' : '-' }}</td>
                                                                <td class="text-dark fw-bold {{ $reading->fluid_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                                                    {{ !is_null($reading->fluid_balance) ? ($reading->fluid_balance >= 0 ? '+' : '') . $reading->fluid_balance . ' مل' : '0 مل' }}
                                                                </td>
                                                                <td class="text-dark small fw-bold">{{ $reading->resident?->user?->full_name ?? $reading->notes }}</td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="alert alert-light border text-center py-4 mt-4 rounded-3">
                                                    <i class="fas fa-tint text-primary me-1 fs-5"></i> لا توجد قياسات سوائل مسجلة مسبقاً لعرضها.
                                                </div>
                                            @endif
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
<form id="nursing-follow-up-form" action="{{ route('nursing-station.follow-ups.store', $surgery) }}" method="POST" style="display: none;">
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
                        'pain_score' => $item->pain_score,
                        'rbs' => $item->rbs,
                        'gcs' => $item->gcs,
                        'fluid_balance' => $item->fluid_balance,
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
            const painData = [];
            const rbsData = [];
            const gcsData = [];
            const fluidBalanceData = [];

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
                painData.push(item.pain_score !== null && item.pain_score !== undefined && item.pain_score !== '' ? parseInt(item.pain_score) : null);
                rbsData.push(item.rbs ? parseInt(item.rbs) : null);
                fluidBalanceData.push(item.fluid_balance !== null && item.fluid_balance !== undefined && item.fluid_balance !== '' ? parseFloat(item.fluid_balance) : null);
                
                if (item.gcs) {
                    const parts = item.gcs.split('/');
                    gcsData.push(parts[0] ? parseInt(parts[0]) : null);
                } else {
                    gcsData.push(null);
                }
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

            // 6. RBS Chart
            const rbsCanvas = document.getElementById('rbsChart');
            if (rbsCanvas) {
                const rbsCtx = rbsCanvas.getContext('2d');
                new Chart(rbsCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'السكر العشوائي (RBS)',
                            data: rbsData,
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.06)',
                            borderWidth: 2.5,
                            tension: 0.3,
                            spanGaps: true,
                            pointRadius: 3.5,
                            pointBackgroundColor: '#dc3545'
                        }]
                    },
                    options: {
                        ...commonOptions,
                        scales: {
                            y: {
                                ...commonOptions.scales.y,
                                suggestedMin: 50,
                                suggestedMax: 200
                            },
                            x: commonOptions.scales.x
                        }
                    }
                });
            }

            // 7. Pain Chart
            const painCanvas = document.getElementById('painChart');
            if (painCanvas) {
                const painCtx = painCanvas.getContext('2d');
                new Chart(painCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'مستوى الألم (Pain Score)',
                            data: painData,
                            borderColor: '#ffc107',
                            backgroundColor: 'rgba(255, 193, 7, 0.06)',
                            borderWidth: 2.5,
                            tension: 0.3,
                            spanGaps: true,
                            pointRadius: 3.5,
                            pointBackgroundColor: '#ffc107'
                        }]
                    },
                    options: {
                        ...commonOptions,
                        scales: {
                            y: {
                                ...commonOptions.scales.y,
                                suggestedMin: 0,
                                suggestedMax: 10
                            },
                            x: commonOptions.scales.x
                        }
                    }
                });
            }

            // 8. GCS Chart
            const gcsCanvas = document.getElementById('gcsChart');
            if (gcsCanvas) {
                const gcsCtx = gcsCanvas.getContext('2d');
                new Chart(gcsCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'مقياس الوعي (GCS)',
                            data: gcsData,
                            borderColor: '#0dcaf0',
                            backgroundColor: 'rgba(13, 202, 240, 0.06)',
                            borderWidth: 2.5,
                            tension: 0.3,
                            spanGaps: true,
                            pointRadius: 3.5,
                            pointBackgroundColor: '#0dcaf0'
                        }]
                    },
                    options: {
                        ...commonOptions,
                        scales: {
                            y: {
                                ...commonOptions.scales.y,
                                suggestedMin: 3,
                                suggestedMax: 15
                            },
                            x: commonOptions.scales.x
                        }
                    }
                });
            }

            // 9. Fluid Balance Chart
            const fluidBalanceCanvas = document.getElementById('fluidBalanceChart');
            if (fluidBalanceCanvas) {
                const fluidBalanceCtx = fluidBalanceCanvas.getContext('2d');
                new Chart(fluidBalanceCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'صافي التوازن المائي (مل)',
                            data: fluidBalanceData,
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.06)',
                            borderWidth: 2.5,
                            tension: 0.3,
                            spanGaps: true,
                            pointRadius: 3.5,
                            pointBackgroundColor: '#0d6efd'
                        }]
                    },
                    options: {
                        ...commonOptions,
                        scales: {
                            y: {
                                ...commonOptions.scales.y,
                                suggestedMin: -500,
                                suggestedMax: 500
                            },
                            x: commonOptions.scales.x
                        }
                    }
                });
            }
        @endif

        // Dynamic Fluid Balance Calculator
        const intakeInputs = [
            document.getElementById('intake_iv_fluids'),
            document.getElementById('intake_oral'),
            document.getElementById('intake_blood')
        ];
        
        const outputInputs = [
            document.getElementById('output_urine'),
            document.getElementById('output_drain'),
            document.getElementById('output_gtube_ng'),
            document.getElementById('output_vomiting'),
            document.getElementById('output_stool')
        ];

        function calculateFluidBalance() {
            let totalIntake = 0;
            let totalOutput = 0;

            intakeInputs.forEach(input => {
                if (input && input.value) {
                    totalIntake += parseFloat(input.value) || 0;
                }
            });

            outputInputs.forEach(input => {
                if (input && input.value) {
                    totalOutput += parseFloat(input.value) || 0;
                }
            });

            const netBalance = totalIntake - totalOutput;

            const totalIntakeSpan = document.getElementById('total-intake-span');
            const totalOutputSpan = document.getElementById('total-output-span');
            const netBalanceSpan = document.getElementById('net-balance-span');

            if (totalIntakeSpan) totalIntakeSpan.textContent = totalIntake.toFixed(2) + ' مل';
            if (totalOutputSpan) totalOutputSpan.textContent = totalOutput.toFixed(2) + ' مل';
            
            if (netBalanceSpan) {
                netBalanceSpan.textContent = (netBalance >= 0 ? '+' : '') + netBalance.toFixed(2) + ' مل';
                if (netBalance >= 0) {
                    netBalanceSpan.className = 'fw-bold fs-4 text-success';
                } else {
                    netBalanceSpan.className = 'fw-bold fs-4 text-danger';
                }
            }
        }

        [...intakeInputs, ...outputInputs].forEach(input => {
            if (input) {
                input.addEventListener('input', calculateFluidBalance);
            }
        });

        // Run initial calculation on page load
        calculateFluidBalance();

        // Dynamic Vitals Range Validator
        function validateVitals() {
            // BP
            const bpInput = document.getElementById('input-bp');
            if (bpInput) {
                const val = bpInput.value.trim();
                if (val) {
                    const match = val.match(/^(\d+)\/(\d+)$/);
                    if (match) {
                        const sys = parseInt(match[1]);
                        const dia = parseInt(match[2]);
                        const isNormal = (sys >= 90 && sys <= 120 && dia >= 60 && dia <= 80);
                        setCardStatus(document.getElementById('card-bp'), isNormal);
                    } else {
                        setCardStatus(document.getElementById('card-bp'), false);
                    }
                } else {
                    resetCardStatus(document.getElementById('card-bp'));
                }
            }

            // Temp
            const tempInput = document.getElementById('input-temp');
            if (tempInput) {
                const val = parseFloat(tempInput.value) || null;
                if (val !== null) {
                    const isNormal = (val >= 36.5 && val <= 37.5);
                    setCardStatus(document.getElementById('card-temp'), isNormal);
                } else {
                    resetCardStatus(document.getElementById('card-temp'));
                }
            }

            // PR
            const prInput = document.getElementById('input-pr');
            if (prInput) {
                const val = parseInt(prInput.value) || null;
                if (val !== null) {
                    const isNormal = (val >= 60 && val <= 100);
                    setCardStatus(document.getElementById('card-pr'), isNormal);
                } else {
                    resetCardStatus(document.getElementById('card-pr'));
                }
            }

            // RR
            const rrInput = document.getElementById('input-rr');
            if (rrInput) {
                const val = parseInt(rrInput.value) || null;
                if (val !== null) {
                    const isNormal = (val >= 12 && val <= 20);
                    setCardStatus(document.getElementById('card-rr'), isNormal);
                } else {
                    resetCardStatus(document.getElementById('card-rr'));
                }
            }

            // SpO2
            const spo2Input = document.getElementById('input-spo2');
            if (spo2Input) {
                const val = parseInt(spo2Input.value) || null;
                if (val !== null) {
                    const isNormal = (val >= 95 && val <= 100);
                    setCardStatus(document.getElementById('card-spo2'), isNormal);
                } else {
                    resetCardStatus(document.getElementById('card-spo2'));
                }
            }

            // Pain
            const painInput = document.getElementById('input-pain');
            if (painInput) {
                const val = painInput.value;
                if (val !== "") {
                    const score = parseInt(val);
                    const isNormal = (score <= 3);
                    setCardStatus(document.getElementById('card-pain'), isNormal);
                } else {
                    resetCardStatus(document.getElementById('card-pain'));
                }
            }

            // RBS
            const rbsInput = document.getElementById('input-rbs');
            if (rbsInput) {
                const val = parseInt(rbsInput.value) || null;
                if (val !== null) {
                    const isNormal = (val >= 70 && val <= 140);
                    setCardStatus(document.getElementById('card-rbs'), isNormal);
                } else {
                    resetCardStatus(document.getElementById('card-rbs'));
                }
            }

            // GCS
            const gcsInput = document.getElementById('input-gcs');
            if (gcsInput) {
                const val = gcsInput.value.trim();
                if (val) {
                    const num = parseInt(val.split('/')[0]);
                    const isNormal = (num === 15);
                    setCardStatus(document.getElementById('card-gcs'), isNormal);
                } else {
                    resetCardStatus(document.getElementById('card-gcs'));
                }
            }

            // CRT
            const crtInput = document.getElementById('input-crt');
            if (crtInput) {
                const val = crtInput.value.trim();
                if (val) {
                    const match = val.match(/\d+/);
                    if (match) {
                        const num = parseInt(match[0]);
                        setCardStatus(document.getElementById('card-crt'), num <= 2);
                    } else if (val.includes('أقل') || val.includes('ثانيت') || val.includes('طبيعي') || val.includes('normal') || val.includes('<')) {
                        setCardStatus(document.getElementById('card-crt'), true);
                    } else {
                        setCardStatus(document.getElementById('card-crt'), false);
                    }
                } else {
                    resetCardStatus(document.getElementById('card-crt'));
                }
            }
        }

        function setCardStatus(card, isNormal) {
            if (!card) return;
            if (isNormal) {
                card.style.borderColor = '#198754';
                card.style.backgroundColor = 'rgba(25, 135, 84, 0.08)';
                const label = card.querySelector('label');
                if (label) {
                    label.style.color = '#198754';
                }
            } else {
                card.style.borderColor = '#dc3545';
                card.style.backgroundColor = 'rgba(220, 53, 69, 0.08)';
                const label = card.querySelector('label');
                if (label) {
                    label.style.color = '#dc3545';
                }
            }
        }

        function resetCardStatus(card) {
            if (!card) return;
            card.style.borderColor = '';
            card.style.backgroundColor = '';
            const label = card.querySelector('label');
            if (label) {
                label.style.color = '';
            }
        }

        const vitalsInputs = ['input-bp', 'input-temp', 'input-pr', 'input-rr', 'input-spo2', 'input-pain', 'input-rbs', 'input-gcs', 'input-crt'];
        vitalsInputs.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', validateVitals);
                el.addEventListener('change', validateVitals);
            }
        });

        // Run validation initially
        validateVitals();
    });
</script>
@endsection
