@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">

    {{-- 1. ترويسة الصفحة والتنقل السريع --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h2 class="h4 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                            <i class="fas fa-hospital-user"></i>
                        </span>
                        <span>إنشاء طلب جديد — قسم الاستعلامات والاستقبال</span>
                    </h2>
                    <p class="text-muted small mb-0">تحديد الخدمات والعيادات الطبية، التحويلات المباشرة، وتثبيت التغطية المالية</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('inquiry.search') }}" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> العودة للبحث
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- رسائل النجاح والتنبيهات العامة --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm p-3 mb-3" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-check-circle fs-5"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => {
                    document.querySelectorAll('.request-card').forEach(card => card.classList.remove('selected'));
                    const details = document.getElementById('requestDetails');
                    const emptyDetails = document.getElementById('emptyRequestDetails');
                    if (details) details.style.display = 'none';
                    if (emptyDetails) emptyDetails.style.display = 'block';
                    selectedTypes.clear();
                    updateFormFields();
                }, 500);
            });
        </script>
    @endif

    {{-- 2. شريط هوية وبيانات المريض الموحد (Executive Patient Strip) --}}
    @php
        $isInsured = $patient->insurance_type && $patient->insurance_type !== 'none';
        $missingInsuranceFields = $isInsured ? $patient->getMissingInsuranceFields() : [];
        $isInsuranceLocked = count($missingInsuranceFields) > 0;
    @endphp

    <div class="card border-0 rounded-4 shadow-sm bg-white mb-4 p-3 border">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            {{-- تفاصيل المريض --}}
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center shrink-0" style="width: 52px; height: 52px;">
                    @if(optional($patient->user)->gender == 'female')
                        <i class="fas fa-user-circle fa-2x text-danger"></i>
                    @else
                        <i class="fas fa-user-circle fa-2x text-primary"></i>
                    @endif
                </div>
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h5 class="fw-bold text-dark mb-0">{{ optional($patient->user)->name ?? 'غير معروف' }}</h5>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border font-monospace px-2 py-0.5 rounded-pill">
                            ملف #{{ $patient->id }}
                        </span>

                        @if($isInsured)
                            @if($isInsuranceLocked)
                                <span class="badge bg-danger text-white px-2.5 py-1 rounded-pill">
                                    <i class="fas fa-lock me-1"></i> مقفل 🔒 (نواقص بملف الضمان)
                                </span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    مشمول: {{ $patient->insurance_type === 'moi' ? 'ضمان قوى الأمن الداخلي' : 'هيئة الضمان الصحي الوطني' }}
                                    {{ ($patient->insurance_type === 'hi' && $patient->healthInsuranceCategory) ? ('- الفئة ' . $patient->healthInsuranceCategory->code) : '' }}
                                    ✓
                                </span>
                            @endif
                        @else
                            <span class="badge bg-light text-secondary border px-2.5 py-1 rounded-pill">
                                <i class="fas fa-money-bill-wave me-1"></i> دفع نقدي مباشر (بدون ضمان)
                            </span>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-x-3 gap-y-1 text-muted small">
                        <span><i class="fas fa-birthday-cake me-1 text-secondary"></i>{{ $patient->age }} سنة</span>
                        <span>•</span>
                        <span>
                            @if(optional($patient->user)->gender == 'male')
                                <i class="fas fa-mars text-primary me-1"></i> ذكر
                            @elseif(optional($patient->user)->gender == 'female')
                                <i class="fas fa-venus text-danger me-1"></i> أنثى
                            @else
                                غير محدد
                            @endif
                        </span>
                        <span>•</span>
                        <span><i class="fas fa-phone me-1 text-secondary"></i>{{ optional($patient->user)->phone ?? 'بدون هاتف' }}</span>
                        <span>•</span>
                        <span><i class="fas fa-map-marker-alt me-1 text-secondary"></i>{{ optional($patient->user)->address ?? 'العنوان غير مدخل' }}</span>
                        @if($patient->insurance_card_no || $patient->insurance_booklet_number)
                            <span>•</span>
                            <span class="font-monospace"><i class="fas fa-id-card me-1 text-secondary"></i>{{ $patient->insurance_card_no ?: $patient->insurance_booklet_number }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- اختصارات سجل المريض وتعديله --}}
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('inquiry.patients.history', $patient->id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm" title="عرض السجل الطبي الشامل">
                    <i class="fas fa-file-medical me-1"></i> السجل الطبي
                </a>
                <a href="{{ route('patients.edit', $patient->id) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm" title="تعديل بيانات المريض">
                    <i class="fas fa-user-edit me-1"></i> تعديل الملف
                </a>
                <a href="{{ route('inquiry.patients.dossier.print', $patient->id) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill px-3 shadow-sm" title="طباعة الإضبارة">
                    <i class="fas fa-print me-1"></i> الإضبارة
                </a>
            </div>
        </div>

        {{-- تنبيه موحد وواضح لنواقص الضمان الصحي (بدون تكرار) --}}
        @if($isInsured && $isInsuranceLocked)
            <div class="alert alert-danger border-2 border-danger rounded-4 shadow-sm p-3 mt-3 mb-0">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-start gap-2.5">
                        <div class="rounded-circle bg-danger text-white p-2 text-center d-flex align-items-center justify-content-center shrink-0" style="width: 38px; height: 38px;">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div>
                            <strong class="text-danger d-block mb-1">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                قفل حجز الضمان الصحي: بيانات المريض الأساسية غير مكتملة لدى شعبة الضمان
                            </strong>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <span class="small text-dark">النواقص الإلزامية:</span>
                                @foreach($missingInsuranceFields as $fKey => $fTitle)
                                    <span class="badge bg-danger text-white py-1 px-2 rounded-pill shadow-sm">
                                        <i class="fas fa-times-circle me-1"></i> {{ $fTitle }}
                                    </span>
                                @endforeach
                            </div>
                            <small class="text-muted d-block">
                                <i class="fas fa-arrow-circle-left text-danger me-1"></i>
                                يمكنك المتابعة الفورية باختيار <strong>(حجز نقدي كاش)</strong> من اللوحة الجانبية، أو توجيه المريض لشعبة الضمان.
                            </small>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 py-2 fw-bold shadow-sm" id="btnNotifyInsurance" onclick="sendInsuranceIncompleteAlert({{ $patient->id }})">
                            <i class="fas fa-bell me-1"></i>
                            <span id="btnNotifyInsuranceText">إرسال تنبيه عاجل لشعبة الضمان والتسجيل</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- 3. النموذج وبيئة العمل ثنائية الأعمدة (Split Responsive Workspace) --}}
    <form action="{{ route('inquiry.store') }}" method="POST" id="requestForm">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
        <input type="hidden" name="confirm_duplicate_booking" id="confirm_duplicate_booking" value="{{ old('confirm_duplicate_booking', 0) }}">
        <div id="requestTypesContainer"></div>
        <input type="hidden" name="radiology_category" id="radiology_category" value="{{ old('radiology_category', 'radiology') }}">

        {{-- تنبيه رصد حجز نشط مسبقاً --}}
        @if(session('duplicate_booking_warning'))
            @php $dupWarn = session('duplicate_booking_warning'); @endphp
            <div class="alert alert-warning border-warning shadow-sm rounded-4 p-3 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                    <div>
                        <strong class="fs-6 d-block text-dark">⚠️ تنبيه: تم رصد حجز نشط مسبقاً للمريض اليوم!</strong>
                        <span class="text-muted small">{{ $dupWarn['message'] ?? 'المريض لديه حجز نشط اليوم بالفعل.' }}</span>
                    </div>
                </div>
                <button type="button" class="btn btn-warning fw-bold px-3 py-2 rounded-pill text-nowrap" onclick="confirmAndResubmitDuplicate()">
                    <i class="fas fa-check-circle me-1"></i> تأكيد الحجز الإضافي الآن
                </button>
            </div>
        @endif

        <div class="row g-4 align-items-start">
            {{-- العمود الأيمن (7 أعمدة): بطاقات الخدمات والتحويلات --}}
            <div class="col-12 col-lg-7">

                {{-- القسم الأول: الخدمات المباشرة (تحديد متعدد) --}}
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                <span class="badge bg-primary rounded-circle p-1" style="width: 22px; height: 22px; font-size: 11px;">1</span>
                                <span>الخدمات الطبية المباشرة</span>
                            </h5>
                            <p class="text-muted small mb-0">انقر لاختيار خدمة أو دمج أكثر من خدمة في طلب واحد</p>
                        </div>
                        <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fw-bold" id="selectedCountBadge">
                            محدد: <span id="selectedServicesCountBadge">0</span>
                        </span>
                    </div>

                    <div class="row g-3">
                        @foreach($requestTypes as $type => $config)
                            @php
                                if ($type === 'radiology' && !$radiologyPermissions['general'] && !$radiologyPermissions['ultrasound'] && !$radiologyPermissions['mri'] && !$radiologyPermissions['echo']) {
                                    continue;
                                }
                            @endphp
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="card h-100 request-card p-3 position-relative" data-type="{{ $type }}" onclick="toggleRequestType('{{ $type }}')">
                                    {{-- شارة التحديد الخضراء العلوية --}}
                                    <span class="position-absolute top-0 end-0 m-2 badge rounded-circle p-1 check-badge bg-primary text-white shadow-sm" style="display: none; width: 22px; height: 22px; align-items: center; justify-content: center;">
                                        <i class="fas fa-check" style="font-size: 11px;"></i>
                                    </span>

                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <div class="rounded-3 p-2.5 text-center d-flex align-items-center justify-content-center shrink-0" 
                                             style="width: 44px; height: 44px; background-color: rgba(var(--bs-{{ $config['color'] }}-rgb, 13, 110, 253), 0.12); color: var(--bs-{{ $config['color'] }});">
                                            <i class="fas {{ $config['icon'] }} fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">{{ $config['label'] }}</h6>
                                            <small class="text-muted d-block" style="font-size: 11px;">
                                                @switch($type)
                                                    @case('checkup')
                                                        استشارة وتحديد الطبيب
                                                        @break
                                                    @case('lab')
                                                        تحاليل وفحوصات مخبرية
                                                        @break
                                                    @case('radiology')
                                                        أشعة • سونار • إيكو • رنين
                                                        @break
                                                    @case('pharmacy')
                                                        صرف علاج ومستلزمات
                                                        @break
                                                    @case('blood_bank')
                                                        كروس ماتش ووحدات دم
                                                        @break
                                                    @default
                                                        خدمة طبية مباشرة
                                                @endswitch
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- القسم الثاني: تحويلات الوحدات المستقلة (انتقال لنموذج آخر) --}}
                @php
                    $hasAnyExternalPermission = auth()->user()->can('create emergencies') || 
                                                auth()->user()->can('create surgeries') || 
                                                auth()->user()->can('create bed reservations') || 
                                                auth()->user()->can('create incubator reservations');
                @endphp

                @if($hasAnyExternalPermission)
                    <div class="card border-0 rounded-4 shadow-sm bg-white p-4">
                        <div class="mb-3 pb-2 border-bottom">
                            <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                <span class="badge bg-secondary rounded-circle p-1" style="width: 22px; height: 22px; font-size: 11px;">2</span>
                                <span>تحويلات وحجوزات الأقسام التخصصية</span>
                            </h5>
                            <p class="text-muted small mb-0">روابط سريعة تفتح نموذج الحجز المباشر في تلك الأقسام</p>
                        </div>

                        <div class="row g-2.5">
                            @can('create emergencies')
                                <div class="col-12 col-sm-6 col-md-3">
                                    <a href="{{ route('emergency.create', ['patient_id' => $patient->id, 'from_referral_id' => request('referral_id') ?? request('from_referral_id'), 'return_to' => 'inquiry']) }}" class="text-decoration-none">
                                        <div class="card p-2.5 rounded-3 ext-nav-card h-100 bg-white">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-danger bg-opacity-10 text-danger p-2 rounded-2">
                                                        <i class="fas fa-ambulance"></i>
                                                    </span>
                                                    <div>
                                                        <strong class="text-dark d-block small">حجز طوارئ</strong>
                                                        <span class="text-muted" style="font-size: 10px;">تذكرة / ضمان</span>
                                                    </div>
                                                </div>
                                                <i class="fas fa-external-link-alt text-muted small"></i>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endcan

                            @can('create surgeries')
                                <div class="col-12 col-sm-6 col-md-3">
                                    <a href="{{ route('surgeries.create', ['patient_id' => $patient->id]) }}" class="text-decoration-none">
                                        <div class="card p-2.5 rounded-3 ext-nav-card h-100 bg-white">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-danger bg-opacity-10 text-danger p-2 rounded-2">
                                                        <i class="fas fa-procedures"></i>
                                                    </span>
                                                    <div>
                                                        <strong class="text-dark d-block small">عملية جراحية</strong>
                                                        <span class="text-muted" style="font-size: 10px;">حجز صالة</span>
                                                    </div>
                                                </div>
                                                <i class="fas fa-external-link-alt text-muted small"></i>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endcan

                            @can('create bed reservations')
                                <div class="col-12 col-sm-6 col-md-3">
                                    <a href="{{ route('bed-reservations.create', ['patient_id' => $patient->id]) }}" class="text-decoration-none">
                                        <div class="card p-2.5 rounded-3 ext-nav-card h-100 bg-white">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-info bg-opacity-10 text-info p-2 rounded-2">
                                                        <i class="fas fa-bed"></i>
                                                    </span>
                                                    <div>
                                                        <strong class="text-dark d-block small">رقود مبدئي</strong>
                                                        <span class="text-muted" style="font-size: 10px;">حجز سرير</span>
                                                    </div>
                                                </div>
                                                <i class="fas fa-external-link-alt text-muted small"></i>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endcan

                            @can('create incubator reservations')
                                <div class="col-12 col-sm-6 col-md-3">
                                    <a href="{{ route('incubator-reservations.create', ['patient_id' => $patient->id]) }}" class="text-decoration-none">
                                        <div class="card p-2.5 rounded-3 ext-nav-card h-100 bg-white">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge p-2 rounded-2" style="background-color: #fce4ec; color: #e91e63;">
                                                        <i class="fas fa-baby"></i>
                                                    </span>
                                                    <div>
                                                        <strong class="text-dark d-block small">حجز حاضنة خدج</strong>
                                                        <span class="text-muted" style="font-size: 10px;">حجز واستقبال مولود بالحاضنة (مرتبط بالأم أو المسؤول)</span>
                                                    </div>
                                                </div>
                                                <i class="fas fa-external-link-alt text-muted small"></i>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endif
            </div>

            {{-- العمود الأيسر (5 أعمدة): لوحة التفاصيل والاعتماد الثابتة (Sticky Workspace) --}}
            <div class="col-12 col-lg-5">
                <div class="sticky-workspace-panel">

                    {{-- الحالة الفارغة قبل اختيار أي خدمة --}}
                    <div id="emptyRequestDetails" class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center">
                        <div class="mb-3">
                            <span class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="fas fa-hand-pointer fa-2x"></i>
                            </span>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">اختر إحدى الخدمات الطبية من القائمة</h6>
                        <p class="text-muted small mb-0">ستظهر تفاصيل الخدمة وخيارات الطبيب والدفع فوراً في هذه اللوحة دون الحاجة للتمرير.</p>
                    </div>

                    {{-- لوحة التفاصيل عند التحديد --}}
                    <div id="requestDetails" style="display: none;">
                        <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
                            <div class="card-header bg-primary text-white p-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
                                    <i class="fas fa-edit"></i>
                                    <span>تفاصيل واعتماد الطلب</span>
                                </h6>
                                <span class="badge bg-white text-primary rounded-pill px-2.5 py-1" id="activeServiceTitleBadge">
                                    طلب جديد
                                </span>
                            </div>

                            <div class="card-body p-3.5 space-y-3">

                                {{-- حقول خاصة بالكشف الطبي --}}
                                <div id="checkupFields" style="display: none;" class="mb-3 p-3 rounded-3 bg-light border">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="doctor_search_input" class="form-label fw-bold mb-0 text-dark small">
                                            <i class="fas fa-user-md me-1 text-primary"></i> الطبيب الاستشاري <span class="text-danger">*</span>
                                        </label>
                                        @php
                                            $todayAvailableDocs = $doctors->where('is_available_today', true);
                                        @endphp
                                        @if($todayAvailableDocs->count() > 0)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 11px;">
                                                <i class="fas fa-check-circle me-1"></i> {{ $todayAvailableDocs->count() }} أطباء متوفرون اليوم
                                            </span>
                                        @endif
                                    </div>

                                    {{-- أزرار سريعة للأطباء المتواجدين اليوم (Quick Selection Chips) --}}
                                    @if($todayAvailableDocs->count() > 0)
                                        <div class="mb-2">
                                            <div class="text-muted small mb-1" style="font-size: 11px;">اختيار سريع للمتواجدين اليوم:</div>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($todayAvailableDocs->take(5) as $avDoc)
                                                    <button type="button" 
                                                            class="btn btn-sm btn-white border rounded-pill px-2.5 py-0.5 text-nowrap quick-doc-btn" 
                                                            style="font-size: 11px; background: #fff;"
                                                            onclick="selectQuickDoctor({{ $avDoc->id }}, '{{ addslashes('د. ' . (optional($avDoc->user)->name ?? 'طبيب') . ' - ' . ($avDoc->specialization ?? '')) }}', '{{ addslashes(optional($avDoc->department)->name ?? '') }}')">
                                                        <i class="fas fa-circle text-success me-1" style="font-size: 7px;"></i>
                                                        د. {{ optional($avDoc->user)->name }} ({{ $avDoc->specialization }})
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @php
                                        $selectedDoctor = old('doctor_id') ? $doctors->firstWhere('id', old('doctor_id')) : null;
                                        $selectedDoctorName = $selectedDoctor ? ('د. ' . (optional($selectedDoctor->user)->name ?? 'طبيب') . ' - ' . ($selectedDoctor->specialization ?? '')) : '';
                                        $selectedDoctorDept = $selectedDoctor ? optional($selectedDoctor->department)->name : '';
                                    @endphp

                                    <div class="position-relative mb-2">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0 text-primary">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text" 
                                                   class="form-control border-start-0 @error('doctor_id') is-invalid @enderror" 
                                                   id="doctor_search_input" 
                                                   placeholder="ابحث باسم الطبيب أو التخصص (اكتب حرفاً للبدء)..." 
                                                   autocomplete="off"
                                                   value="{{ $selectedDoctorName }}">
                                            <button class="btn btn-outline-secondary" type="button" id="clearDoctorSearchBtn" style="{{ $selectedDoctor ? '' : 'display: none;' }}" title="مسح">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <input type="hidden" id="doctor_id" name="doctor_id" value="{{ old('doctor_id') }}">

                                        {{-- قائمة الاقتراحات الذكية --}}
                                        <div id="doctor_suggestions" 
                                             class="position-absolute w-100 bg-white shadow-lg rounded-3 border mt-1" 
                                             style="display: none; max-height: 250px; overflow-y: auto; z-index: 1055; left: 0;">
                                        </div>
                                    </div>

                                    {{-- بطاقة تأكيد اختيار الطبيب --}}
                                    <div id="selectedDoctorBox" class="p-2 rounded-3 bg-white border border-success border-opacity-25 align-items-center justify-content-between mb-2" style="{{ $selectedDoctor ? 'display: flex;' : 'display: none;' }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-user-check text-success fs-5"></i>
                                            <div>
                                                <strong class="text-success d-block small" id="selectedDoctorNameText">{{ $selectedDoctorName }}</strong>
                                                <small class="text-muted" id="selectedDoctorDeptText" style="font-size: 11px;">{{ $selectedDoctorDept ? 'العيادة: ' . $selectedDoctorDept : '' }}</small>
                                            </div>
                                        </div>
                                        <span class="badge bg-success rounded-pill px-2 py-0.5" style="font-size: 10px;">محدد</span>
                                    </div>

                                    {{-- تنبيه فوري لمنع تكرار الحجز لنفس الطبيب في نفس اليوم --}}
                                    <div id="duplicateDoctorWarningBox" class="p-2.5 rounded-3 bg-danger bg-opacity-10 border border-danger shadow-sm mb-2" style="display: none;">
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="fas fa-ban text-danger fs-4 mt-1"></i>
                                            <div class="flex-grow-1">
                                                <strong class="text-danger d-block small mb-1" id="duplicateDoctorTitle">⛔ غير مسموح بتكرار الحجز لنفس اليوم نهائياً</strong>
                                                <p class="text-dark small mb-1 fw-semibold" id="duplicateDoctorMessage" style="font-size: 11px;"></p>
                                                <div class="d-flex align-items-center gap-1.5 flex-wrap mb-1">
                                                    <span class="badge bg-danger text-white" id="duplicateDoctorQueueBadge" style="font-size: 10px;">تسلسل: #0</span>
                                                    <span class="badge bg-secondary text-white" id="duplicateDoctorStatusBadge" style="font-size: 10px;">الحالة</span>
                                                    <span class="badge bg-dark text-white" id="duplicateDoctorPayBadge" style="font-size: 10px;">الدفع</span>
                                                </div>
                                                <div class="alert alert-danger p-1.5 mb-0 small" style="font-size: 11px;">
                                                    <i class="fas fa-times-circle me-1"></i> المريض مسجل اليوم بالفعل لدى الطبيب ويدخل مباشرة للمراجعة.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- تاريخ الموعد --}}
                                    <div>
                                        <label for="appointment_date" class="form-label small fw-bold mb-1">
                                            <i class="fas fa-calendar-alt me-1 text-primary"></i> تاريخ الموعد
                                        </label>
                                        <input type="date" 
                                               class="form-control form-control-sm rounded-3 @error('appointment_date') is-invalid @enderror" 
                                               id="appointment_date" 
                                               name="appointment_date"
                                               value="{{ old('appointment_date', date('Y-m-d')) }}"
                                               min="{{ date('Y-m-d') }}">
                                        @error('appointment_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- حقول خاصة بالأشعة والتصوير --}}
                                <div id="radiologyFields" style="display: none;" class="mb-3 p-3 rounded-3 bg-light border">
                                    <label class="form-label fw-bold small text-dark d-block mb-1.5">
                                        <i class="fas fa-x-ray me-1 text-primary"></i> نوع فئة الأشعة
                                    </label>
                                    <div class="btn-group w-100 mb-2" role="group" aria-label="خيارات الأشعة">
                                        @if($radiologyPermissions['general'])
                                            <button type="button" class="btn btn-sm btn-outline-secondary radiology-category-btn" data-category="radiology">أشعة عامة</button>
                                        @endif
                                        @if($radiologyPermissions['ultrasound'])
                                            <button type="button" class="btn btn-sm btn-outline-secondary radiology-category-btn" data-category="ultrasound">سونار</button>
                                        @endif
                                        @if($radiologyPermissions['mri'])
                                            <button type="button" class="btn btn-sm btn-outline-secondary radiology-category-btn" data-category="mri">رنين</button>
                                        @endif
                                        @if($radiologyPermissions['echo'])
                                            <button type="button" class="btn btn-sm btn-outline-secondary radiology-category-btn" data-category="echo">إيكو</button>
                                        @endif
                                    </div>

                                    {{-- تفاصيل السونار المباشر --}}
                                    <div id="ultrasoundDetailsContainer" style="display: none;" class="mt-2 p-2.5 bg-white border border-primary border-opacity-25 rounded-3">
                                        <div class="mb-2 position-relative">
                                            <label for="ultrasound_search_input" class="form-label fw-bold small mb-1">نوع فحص السونار <span class="text-danger">*</span></label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-primary"></i></span>
                                                <input type="text" 
                                                       class="form-control border-start-0" 
                                                       id="ultrasound_search_input" 
                                                       placeholder="ابحث عن نوع السونار..." 
                                                       autocomplete="off">
                                                <button class="btn btn-outline-secondary" type="button" id="clearUltrasoundSearch" style="display: none;">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" id="ultrasound_type_id" name="ultrasound_type_id" value="{{ old('ultrasound_type_id') }}">
                                            <div id="ultrasound_suggestions" class="position-absolute w-100 bg-white shadow-lg rounded-3 border mt-1" style="display: none; max-height: 220px; overflow-y: auto; z-index: 1050; left: 0;"></div>
                                        </div>

                                        <div class="mb-2">
                                            <label for="ultrasound_staff_id" class="form-label fw-bold small mb-1">أخصائي / موظف السونار <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm" id="ultrasound_staff_id" name="ultrasound_staff_id">
                                                <option value="">اختر الموظف...</option>
                                                @foreach($ultrasoundStaff as $staff)
                                                    <option value="{{ $staff->id }}" {{ old('ultrasound_staff_id') == $staff->id ? 'selected' : '' }}>
                                                        {{ $staff->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div id="ultrasoundPriceBox" style="display: none;" class="p-2 bg-light border border-primary border-opacity-25 rounded d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">السعر المعتمد: <strong class="text-primary" id="ultrasoundPriceText">0 د.ع</strong></span>
                                            <span class="badge bg-warning text-dark"><i class="fas fa-receipt me-1"></i>دفع مسبق بالكاشير</span>
                                        </div>

                                        <div id="duplicateUltrasoundWarningBox" style="display: none;" class="p-2 rounded bg-warning bg-opacity-10 border border-warning small mb-2">
                                            <strong class="text-dark d-block">⚠️ تنبيه: يوجد حجز فحص سونار نشط اليوم لم يكتمل بعد</strong>
                                            <span id="duplicateUltrasoundMessage" class="text-muted d-block" style="font-size: 11px;"></span>
                                        </div>
                                    </div>

                                    {{-- تفاصيل الإيكو المباشر --}}
                                    <div id="echoDetailsContainer" style="display: none;" class="mt-2 p-2.5 bg-white border border-info border-opacity-25 rounded-3">
                                        <div class="mb-2">
                                            <label for="echo_type_id" class="form-label fw-bold small mb-1">نوع الإيكو <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm" id="echo_type_id" name="echo_type_id">
                                                <option value="">اختر نوع الإيكو...</option>
                                                @foreach($radiologyTypes->where('subcategory', 'إيكو') as $type)
                                                    <option value="{{ $type->id }}" {{ old('echo_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label for="echo_staff_id" class="form-label fw-bold small mb-1">الموظف المسؤول <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm" id="echo_staff_id" name="echo_staff_id">
                                                <option value="">اختر الموظف...</option>
                                                @foreach($echoStaff as $staff)
                                                    <option value="{{ $staff->id }}" {{ old('echo_staff_id') == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                {{-- حقول المختبر --}}
                                <div id="labFields" style="display: none;" class="mb-3">
                                    <div class="alert alert-info py-2 px-3 rounded-3 small mb-0">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <strong>المختبر:</strong> سيتم فتح طلب تحويل عام، ويقوم موظف المختبر بتحديد التحاليل وتفاصيلها قبل الدفع.
                                    </div>
                                </div>

                                {{-- حقول عامة / صيدلية --}}
                                <div id="generalFields" style="display: none;" class="mb-3">
                                    <label for="description" class="form-label fw-bold small">
                                        <i class="fas fa-comment-medical me-1 text-primary"></i> وصف الحالة / الأدوية المطلوبة <span class="text-danger general-required">*</span>
                                    </label>
                                    <textarea class="form-control form-control-sm rounded-3 @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="3" 
                                              placeholder="اكتب تفاصيل الخدمة أو الأدوية المطلوبة...">{{ old('description') }}</textarea>
                                </div>

                                {{-- اختيار نوع التغطية المالية (ضمان صحي أو كاش) --}}
                                @if($isInsured)
                                    <div class="mb-3" id="insuranceCoverageSection">
                                        <label class="form-label fw-bold small text-dark d-block mb-1.5">
                                            <i class="fas fa-shield-alt text-primary me-1"></i> نوع التغطية المالية للحجز:
                                        </label>
                                        <div class="row g-2">
                                            {{-- خيار الضمان --}}
                                            <div class="col-6">
                                                <div class="card p-2 rounded-3 border-2 transition-all position-relative h-100 {{ $isInsuranceLocked ? 'border-danger-subtle bg-danger-subtle bg-opacity-25 opacity-75' : (old('apply_insurance', '1') == '1' ? 'border-success bg-white shadow-sm' : 'border-light-subtle bg-white') }}" 
                                                     id="cardInsuranceYes"
                                                     style="{{ $isInsuranceLocked ? 'cursor: not-allowed;' : 'cursor: pointer;' }}"
                                                     onclick="{{ $isInsuranceLocked ? 'return false;' : 'selectInsuranceMode(1)' }}">
                                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                                        <strong class="small {{ $isInsuranceLocked ? 'text-danger' : 'text-dark' }}" style="font-size: 11px;">
                                                            <i class="fas {{ $isInsuranceLocked ? 'fa-lock text-danger' : 'fa-shield-alt text-success' }} me-1"></i> ضمان صحي
                                                        </strong>
                                                        <input class="form-check-input mt-0" type="radio" name="apply_insurance" id="apply_insurance_yes" value="1" 
                                                            {{ $isInsuranceLocked ? 'disabled' : (old('apply_insurance', '1') == '1' ? 'checked' : '') }}
                                                            onchange="selectInsuranceMode(1)">
                                                    </div>
                                                    <span class="text-muted d-block" style="font-size: 10px;">احتساب نسبة التحمل</span>
                                                    @if($isInsuranceLocked)
                                                        <span class="badge bg-danger text-white mt-1 py-0.5 px-1.5" style="font-size: 9px;">مقفل 🔒 (نواقص بملف الضمان)</span>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- خيار الكاش --}}
                                            <div class="col-6">
                                                <div class="card p-2 rounded-3 border-2 transition-all position-relative h-100 {{ ($isInsuranceLocked || old('apply_insurance') === '0') ? 'border-primary bg-white shadow-sm' : 'border-light-subtle bg-white' }}" 
                                                     id="cardInsuranceNo"
                                                     style="cursor: pointer;"
                                                     onclick="selectInsuranceMode(0)">
                                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                                        <strong class="text-dark small" style="font-size: 11px;">
                                                            <i class="fas fa-money-bill-wave text-primary me-1"></i> حجز نقدي (كاش)
                                                        </strong>
                                                        <input class="form-check-input mt-0" type="radio" name="apply_insurance" id="apply_insurance_no" value="0" 
                                                            {{ ($isInsuranceLocked || old('apply_insurance') === '0') ? 'checked' : '' }}
                                                            onchange="selectInsuranceMode(0)">
                                                    </div>
                                                    <span class="text-muted d-block" style="font-size: 10px;">سداد كامل الأجور نقدياً</span>
                                                    @if($isInsuranceLocked)
                                                        <span class="badge bg-success text-white mt-1 py-0.5 px-1.5" style="font-size: 9px;">متاح للمتابعة فوراً ✓</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- التحويل التلقائي --}}
                                <div class="mb-3" id="autoReferContainer" style="display: none;">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" value="1" id="autoRefer" name="auto_refer" {{ old('auto_refer') ? 'checked' : '' }}>
                                        <label class="form-check-label small text-muted" for="autoRefer">
                                            التحويل التلقائي المباشر لصفحة القسم بعد الحفظ
                                        </label>
                                    </div>
                                </div>

                                {{-- زر الاعتماد والحفظ --}}
                                <div class="pt-2">
                                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm" id="submitBtn">
                                        <i class="fas fa-check-circle me-1.5"></i>
                                        <span id="submitBtnText">اعتماد وإنشاء الطلب</span>
                                    </button>
                                </div>

                                {{-- ملخص مسار المعالجة --}}
                                <div class="alert alert-light border rounded-3 p-2.5 mt-2 mb-0" id="infoAlert">
                                    <div class="small text-muted" id="infoList" style="font-size: 11px; line-height: 1.6;">
                                        <i class="fas fa-info-circle text-primary me-1"></i>
                                        <span>سيتم تسجيل الطلب وإرساله إلى شاشات الاستقبال والكاشير للمتابعة.</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

@push('modals')
    {{-- نافذة تأكيد الحجز الإضافي المكرر --}}
    <div class="modal fade" id="confirmDuplicateModal" tabindex="-1" aria-labelledby="confirmDuplicateModalLabel" aria-hidden="true" style="z-index: 10999;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-warning bg-opacity-25 border-bottom border-warning border-opacity-25 p-3">
                    <h5 class="modal-title text-dark fw-bold" id="confirmDuplicateModalLabel">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i> تأكيد إنشاء حجز إضافي مكرر
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-3 bg-warning bg-opacity-10 rounded-circle text-warning fs-3">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark" id="duplicateModalTitle">تنبيه حجز مكرر لنفس اليوم</h6>
                            <p class="text-muted small mb-0" id="duplicateModalText">المريض لديه حجز نشط مسجل مسبقاً لهذا اليوم.</p>
                        </div>
                    </div>
                    <div class="alert alert-light border rounded-3 p-3 text-secondary small mb-0">
                        <i class="fas fa-info-circle text-primary me-1"></i>
                        <strong>ملاحظة للموظف:</strong> عند المتابعة، سيتم إصدار حجز إضافي جديد مع تثبيت قيد تدقيق بأن الحجز إضافي ومؤكد بمعرفة الاستعلامات.
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> تراجع وإلغاء
                    </button>
                    <button type="button" class="btn btn-warning fw-bold rounded-pill px-4" id="btnConfirmDuplicate">
                        <i class="fas fa-check-circle me-1"></i> نعم، تأكيد الحجز الإضافي
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

<style>
.request-card {
    cursor: pointer;
    transition: all 0.22s ease-in-out;
    border: 2px solid rgba(226, 232, 240, 0.9) !important;
    background-color: #ffffff;
    border-radius: 1rem !important;
}
.request-card:hover {
    transform: translateY(-3px);
    border-color: rgba(13, 110, 253, 0.35) !important;
    box-shadow: 0 0.5rem 1rem rgba(13, 110, 253, 0.08) !important;
}
.request-card.selected {
    border-color: #0d6efd !important;
    background-color: #f0f7ff !important;
    box-shadow: 0 0.5rem 1.25rem rgba(13, 110, 253, 0.15) !important;
}
.ext-nav-card {
    transition: all 0.2s ease;
    border: 1px solid rgba(226, 232, 240, 0.9);
}
.ext-nav-card:hover {
    transform: translateY(-2px);
    border-color: rgba(100, 116, 139, 0.35);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
}
.sticky-workspace-panel {
    position: -webkit-sticky;
    position: sticky;
    top: 1rem;
    z-index: 10;
}
.radiology-category-btn.active {
    background-color: #0d6efd !important;
    color: #ffffff !important;
    border-color: #0d6efd !important;
}
.quick-doc-btn:hover {
    background-color: #e8f5e9 !important;
    border-color: #4caf50 !important;
    color: #2e7d32 !important;
}
</style>

@php
    $defaultRadiologyCategory = '';
    if (!empty($radiologyPermissions['general'])) {
        $defaultRadiologyCategory = 'radiology';
    } elseif (!empty($radiologyPermissions['ultrasound'])) {
        $defaultRadiologyCategory = 'ultrasound';
    } elseif (!empty($radiologyPermissions['mri'])) {
        $defaultRadiologyCategory = 'mri';
    } elseif (!empty($radiologyPermissions['echo'])) {
        $defaultRadiologyCategory = 'echo';
    }
@endphp

<script>
let selectedTypes = new Set();
let defaultRadiologyCategory = @json(old('radiology_category', $defaultRadiologyCategory));
let selectedRadiologyCategory = defaultRadiologyCategory;

const currentPatientId = @json($patient->id);
const checkActiveBookingUrl = @json(route('inquiry.check-active-booking'));

let activeBookingState = {
    checkup: null,
    ultrasound: null
};

const serviceLabels = {
    checkup: 'كشف طبي',
    lab: 'تحاليل مخبرية',
    radiology: 'أشعة وتصوير',
    pharmacy: 'صيدلية',
    blood_bank: 'مصرف الدم'
};

function showValidationError(message, title = 'تنبيه') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: title,
            html: message.replace(/\n/g, '<br>'),
            confirmButtonText: 'حسناً',
            customClass: { confirmButton: 'btn btn-primary rounded-pill px-4' }
        });
    } else {
        alert(message);
    }
}

function selectQuickDoctor(id, fullName, dept) {
    const doctorHiddenId = document.getElementById('doctor_id');
    const doctorSearchInput = document.getElementById('doctor_search_input');
    const doctorClearBtn = document.getElementById('clearDoctorSearchBtn');
    const selectedDoctorBox = document.getElementById('selectedDoctorBox');
    const selectedDoctorNameText = document.getElementById('selectedDoctorNameText');
    const selectedDoctorDeptText = document.getElementById('selectedDoctorDeptText');

    if (doctorHiddenId) doctorHiddenId.value = id;
    if (doctorSearchInput) doctorSearchInput.value = fullName;
    if (doctorClearBtn) doctorClearBtn.style.display = 'block';

    if (selectedDoctorBox) {
        if (selectedDoctorNameText) selectedDoctorNameText.textContent = fullName;
        if (selectedDoctorDeptText) selectedDoctorDeptText.textContent = dept ? 'العيادة: ' + dept : '';
        selectedDoctorBox.style.display = 'flex';
    }

    checkDoctorActiveBooking(id);
}

function disableSubmitButton() {
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        const submitBtnText = document.getElementById('submitBtnText');
        if (submitBtnText) {
            submitBtnText.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> جاري الحفظ والاعتماد...';
        }
    }
}

function enableSubmitButton() {
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.disabled = false;
        const submitBtnText = document.getElementById('submitBtnText');
        if (submitBtnText) {
            submitBtnText.innerHTML = '<i class="fas fa-check-circle me-1.5"></i>' + (submitBtnText.dataset.originalText || 'اعتماد وإنشاء الطلب');
        }
    }
}

function openConfirmDuplicateModal(message) {
    if (typeof window.hidePageLoader === 'function') {
        window.hidePageLoader(true);
    }
    enableSubmitButton();

    const modalEl = document.getElementById('confirmDuplicateModal');
    if (!modalEl) {
        if (confirm('⚠️ تنبيه: ' + (message || 'المريض لديه حجز نشط اليوم بالفعل.') + '\n\nهل أنت متأكد من رغبتك في إنشاء حجز إضافي جديد لنفس اليوم؟')) {
            confirmAndResubmitDuplicate();
        }
        return;
    }

    if (message) {
        const modalMsgEl = document.getElementById('duplicateModalText');
        if (modalMsgEl) modalMsgEl.textContent = message;
    }

    try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.show();
            return;
        }
    } catch (err) {
        console.warn('Bootstrap modal failed, trying fallback:', err);
    }

    try {
        if (typeof $ !== 'undefined' && typeof $(modalEl).modal === 'function') {
            $(modalEl).modal('show');
            return;
        }
    } catch (err) {
        console.warn('jQuery modal failed:', err);
    }

    if (confirm('⚠️ تنبيه: ' + (message || 'المريض لديه حجز نشط اليوم بالفعل.') + '\n\nهل أنت متأكد من رغبتك في إنشاء حجز إضافي جديد لنفس اليوم؟')) {
        confirmAndResubmitDuplicate();
    }
}

function checkDoctorActiveBooking(doctorId) {
    const warningBox = document.getElementById('duplicateDoctorWarningBox');
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');

    if (!doctorId) {
        activeBookingState.checkup = null;
        if (warningBox) warningBox.style.display = 'none';
        if (submitBtn && submitBtn.dataset.blockedByDuplicate === '1') {
            submitBtn.disabled = false;
            submitBtn.dataset.blockedByDuplicate = '0';
            if (submitBtnText) submitBtnText.innerHTML = '<i class="fas fa-check-circle me-1.5"></i>' + (submitBtnText.dataset.originalText || 'اعتماد وإنشاء الطلب');
        }
        return;
    }

    fetch(`${checkActiveBookingUrl}?patient_id=${currentPatientId}&type=checkup&doctor_id=${doctorId}`)
        .then(res => res.json())
        .then(data => {
            if (data && data.has_active) {
                activeBookingState.checkup = data;
                if (warningBox) {
                    const msgEl = document.getElementById('duplicateDoctorMessage');
                    if (msgEl) msgEl.textContent = data.message;
                    const qEl = document.getElementById('duplicateDoctorQueueBadge');
                    if (qEl) qEl.textContent = 'تسلسل: #' + data.queue_number;
                    const stEl = document.getElementById('duplicateDoctorStatusBadge');
                    if (stEl) stEl.textContent = data.status_label;
                    const payEl = document.getElementById('duplicateDoctorPayBadge');
                    if (payEl) payEl.textContent = data.payment_label;
                    warningBox.style.display = 'block';
                }
                if (submitBtn && selectedTypes.has('checkup')) {
                    submitBtn.disabled = true;
                    submitBtn.dataset.blockedByDuplicate = '1';
                    if (submitBtnText) submitBtnText.innerHTML = '<i class="fas fa-ban me-1.5"></i> محظور: مسجل اليوم بالفعل لدى الطبيب';
                }
            } else {
                activeBookingState.checkup = null;
                if (warningBox) warningBox.style.display = 'none';
                if (submitBtn && submitBtn.dataset.blockedByDuplicate === '1') {
                    submitBtn.disabled = false;
                    submitBtn.dataset.blockedByDuplicate = '0';
                    if (submitBtnText) submitBtnText.innerHTML = '<i class="fas fa-check-circle me-1.5"></i>' + (submitBtnText.dataset.originalText || 'اعتماد وإنشاء الطلب');
                }
            }
        })
        .catch(err => {
            console.error('Error checking active doctor booking:', err);
        });
}

function checkUltrasoundActiveBooking() {
    const warningBox = document.getElementById('duplicateUltrasoundWarningBox');
    fetch(`${checkActiveBookingUrl}?patient_id=${currentPatientId}&type=radiology&radiology_category=ultrasound`)
        .then(res => res.json())
        .then(data => {
            if (data && data.has_active) {
                activeBookingState.ultrasound = data;
                if (warningBox) {
                    const msgEl = document.getElementById('duplicateUltrasoundMessage');
                    if (msgEl) msgEl.textContent = data.message;
                    const qEl = document.getElementById('duplicateUltrasoundQueueBadge');
                    if (qEl) qEl.textContent = 'تسلسل: #' + data.queue_number;
                    const stEl = document.getElementById('duplicateUltrasoundStatusBadge');
                    if (stEl) stEl.textContent = data.status_label;
                    const payEl = document.getElementById('duplicateUltrasoundPayBadge');
                    if (payEl) payEl.textContent = data.payment_label;
                    warningBox.style.display = 'block';
                }
            } else {
                activeBookingState.ultrasound = null;
                if (warningBox) warningBox.style.display = 'none';
            }
        })
        .catch(err => {
            console.error('Error checking active ultrasound booking:', err);
        });
}

function confirmAndResubmitDuplicate() {
    const confirmInput = document.getElementById('confirm_duplicate_booking');
    if (confirmInput) {
        confirmInput.value = '1';
    }
    const modalEl = document.getElementById('confirmDuplicateModal');
    if (modalEl) {
        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
        } catch(e) {}
        try {
            if (typeof $ !== 'undefined' && typeof $(modalEl).modal === 'function') {
                $(modalEl).modal('hide');
            }
        } catch(e) {}
    }
    disableSubmitButton();
    if (typeof window.showPageLoader === 'function') {
        window.showPageLoader();
    }
    const form = document.getElementById('requestForm');
    if (form) form.submit();
}

function setRadiologyCategory(category) {
    selectedRadiologyCategory = category;
    const radiologyCategoryInput = document.getElementById('radiology_category');
    if (radiologyCategoryInput) {
        radiologyCategoryInput.value = category;
    }
    document.querySelectorAll('.radiology-category-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.category === category);
    });
    
    const ultrasoundDetailsContainer = document.getElementById('ultrasoundDetailsContainer');
    if (ultrasoundDetailsContainer) {
        if (category === 'ultrasound') {
            ultrasoundDetailsContainer.style.display = 'block';
            document.getElementById('ultrasound_staff_id').required = true;
            document.getElementById('ultrasound_type_id').required = true;
            checkUltrasoundActiveBooking();
        } else {
            ultrasoundDetailsContainer.style.display = 'none';
            document.getElementById('ultrasound_staff_id').required = false;
            document.getElementById('ultrasound_type_id').required = false;
            document.getElementById('ultrasound_staff_id').value = '';
            document.getElementById('ultrasound_type_id').value = '';
            const searchInput = document.getElementById('ultrasound_search_input');
            if (searchInput) searchInput.value = '';
            const suggestionsBox = document.getElementById('ultrasound_suggestions');
            if (suggestionsBox) {
                suggestionsBox.style.display = 'none';
                suggestionsBox.innerHTML = '';
            }
            const clearBtn = document.getElementById('clearUltrasoundSearch');
            if (clearBtn) clearBtn.style.display = 'none';
            const priceBox = document.getElementById('ultrasoundPriceBox');
            if (priceBox) priceBox.style.display = 'none';
            const sonarWarnBox = document.getElementById('duplicateUltrasoundWarningBox');
            if (sonarWarnBox) sonarWarnBox.style.display = 'none';
            activeBookingState.ultrasound = null;
        }
    }
    
    const echoDetailsContainer = document.getElementById('echoDetailsContainer');
    if (echoDetailsContainer) {
        if (category === 'echo') {
            echoDetailsContainer.style.display = 'block';
            document.getElementById('echo_type_id').required = true;
            document.getElementById('echo_staff_id').required = true;
        } else {
            echoDetailsContainer.style.display = 'none';
            document.getElementById('echo_type_id').required = false;
            document.getElementById('echo_staff_id').required = false;
            document.getElementById('echo_type_id').value = '';
            document.getElementById('echo_staff_id').value = '';
        }
    }

    updateInsuranceVisibility();
}

function updateInsuranceVisibility() {
    const insuranceRow = document.getElementById('insuranceCoverageSection');
    if (!insuranceRow) return;

    const isDirectUltrasound = selectedTypes.has('radiology') && (selectedRadiologyCategory === 'ultrasound');
    if (isDirectUltrasound) {
        insuranceRow.style.display = 'none';
        const noRadio = document.getElementById('apply_insurance_no');
        if (noRadio) {
            noRadio.checked = true;
            selectInsuranceMode(0);
        }
    } else {
        insuranceRow.style.display = 'block';
    }
}

function selectInsuranceMode(mode) {
    const isLocked = {{ $isInsuranceLocked ? 'true' : 'false' }};
    if (mode === 1 && isLocked) return;

    const radioYes = document.getElementById('apply_insurance_yes');
    const radioNo = document.getElementById('apply_insurance_no');
    const cardYes = document.getElementById('cardInsuranceYes');
    const cardNo = document.getElementById('cardInsuranceNo');

    if (mode === 1) {
        if (radioYes) radioYes.checked = true;
        if (cardYes) {
            cardYes.classList.remove('border-light-subtle');
            cardYes.classList.add('border-success', 'shadow-sm');
        }
        if (cardNo) {
            cardNo.classList.remove('border-primary', 'shadow-sm');
            cardNo.classList.add('border-light-subtle');
        }
    } else {
        if (radioNo) radioNo.checked = true;
        if (cardNo) {
            cardNo.classList.remove('border-light-subtle');
            cardNo.classList.add('border-primary', 'shadow-sm');
        }
        if (cardYes && !isLocked) {
            cardYes.classList.remove('border-success', 'shadow-sm');
            cardYes.classList.add('border-light-subtle');
        }
    }
}

function sendInsuranceIncompleteAlert(patientId) {
    const btn = document.getElementById('btnNotifyInsurance');
    const textSpan = document.getElementById('btnNotifyInsuranceText');
    if (!btn || btn.disabled) return;

    btn.disabled = true;
    const originalText = textSpan ? textSpan.innerHTML : btn.innerHTML;
    if (textSpan) textSpan.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري إرسال التنبيه...';

    fetch('{{ route("inquiry.notify-insurance-incomplete") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ patient_id: patientId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            btn.classList.remove('btn-danger');
            btn.classList.add('btn-success');
            btn.innerHTML = '<i class="fas fa-check-circle me-1"></i> تم إرسال التنبيه بنجاح ✓';
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'تم إرسال التنبيه العاجل بنجاح!',
                    text: data.message,
                    confirmButtonText: 'حسناً',
                    customClass: { confirmButton: 'btn btn-primary rounded-pill px-4' }
                });
            } else {
                alert(data.message);
            }
        } else {
            btn.disabled = false;
            if (textSpan) textSpan.innerHTML = originalText;
            showValidationError(data.message || 'حدث خطأ أثناء إرسال التنبيه');
        }
    })
    .catch(err => {
        console.error(err);
        btn.disabled = false;
        if (textSpan) textSpan.innerHTML = originalText;
        showValidationError('تعذر الاتصال بالخادم لإرسال التنبيه، يرجى إعادة المحاولة.');
    });
}

function updateRadiologyCategoryInfo() {
    const category = selectedRadiologyCategory || defaultRadiologyCategory;
    if (category) {
        setRadiologyCategory(category);
    }
}

function toggleRequestType(type) {
    const card = document.querySelector(`.request-card[data-type="${type}"]`);
    if (!card) return;
    
    const checkBadge = card.querySelector('.check-badge');
    if (selectedTypes.has(type)) {
        selectedTypes.delete(type);
        card.classList.remove('selected');
        if (checkBadge) checkBadge.style.display = 'none';
    } else {
        selectedTypes.add(type);
        card.classList.add('selected');
        if (checkBadge) checkBadge.style.display = 'inline-flex';
    }
    
    updateFormFields();
    
    const details = document.getElementById('requestDetails');
    const emptyDetails = document.getElementById('emptyRequestDetails');
    const countBadge = document.getElementById('selectedServicesCountBadge');
    if (countBadge) countBadge.textContent = selectedTypes.size;

    if (selectedTypes.size > 0) {
        if (emptyDetails) emptyDetails.style.display = 'none';
        if (details) details.style.display = 'block';
        updateDetailsForm();
    } else {
        if (details) details.style.display = 'none';
        if (emptyDetails) emptyDetails.style.display = 'block';
    }

    if (selectedTypes.has('checkup')) {
        const docId = document.getElementById('doctor_id')?.value;
        if (docId) checkDoctorActiveBooking(docId);
    } else {
        activeBookingState.checkup = null;
    }

    if (selectedTypes.has('radiology') && selectedRadiologyCategory === 'ultrasound') {
        checkUltrasoundActiveBooking();
    } else if (!selectedTypes.has('radiology')) {
        activeBookingState.ultrasound = null;
    }

    updateInsuranceVisibility();
}

function updateFormFields() {
    const container = document.getElementById('requestTypesContainer');
    container.innerHTML = '';
    
    selectedTypes.forEach(type => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'request_type[]';
        input.value = type;
        container.appendChild(input);
    });
}

function updateDetailsForm() {
    const checkupFields = document.getElementById('checkupFields');
    const labFields = document.getElementById('labFields');
    const radiologyFields = document.getElementById('radiologyFields');
    const generalFields = document.getElementById('generalFields');
    const autoReferContainer = document.getElementById('autoReferContainer');
    const submitBtnText = document.getElementById('submitBtnText');
    const infoList = document.getElementById('infoList');
    const activeServiceTitleBadge = document.getElementById('activeServiceTitleBadge');
    
    if (checkupFields) checkupFields.style.display = 'none';
    if (labFields) labFields.style.display = 'none';
    if (radiologyFields) radiologyFields.style.display = 'none';
    if (generalFields) generalFields.style.display = 'none';
    if (autoReferContainer) autoReferContainer.style.display = 'none';
    
    if (selectedTypes.has('checkup')) {
        if (checkupFields) checkupFields.style.display = 'block';
    }
    
    if (selectedTypes.has('lab')) {
        if (labFields) labFields.style.display = 'block';
        if (autoReferContainer) autoReferContainer.style.display = 'block';
    }
    
    if (selectedTypes.has('radiology')) {
        if (radiologyFields) radiologyFields.style.display = 'block';
        if (autoReferContainer) autoReferContainer.style.display = 'block';
        updateRadiologyCategoryInfo();
    }
    
    if (selectedTypes.has('pharmacy')) {
        if (generalFields) generalFields.style.display = 'block';
        if (autoReferContainer) autoReferContainer.style.display = 'block';
    }

    const typeNames = Array.from(selectedTypes).map(t => serviceLabels[t] || t);
    if (activeServiceTitleBadge) {
        activeServiceTitleBadge.textContent = typeNames.join(' + ') || 'طلب جديد';
    }
    
    if (selectedTypes.size === 1) {
        const type = Array.from(selectedTypes)[0];
        if (type === 'checkup') {
            submitBtnText.textContent = 'اعتماد وحجز الكشفية';
            infoList.innerHTML = `<i class="fas fa-check text-success me-1"></i> سيتم إصدار تذكرة كشفية للطبيب المختار مع تسلسل الدور في العيادة.`;
        } else if (type === 'lab') {
            submitBtnText.textContent = 'اعتماد طلب التحاليل';
            infoList.innerHTML = `<i class="fas fa-check text-success me-1"></i> سيتم إنشاء طلب تحويل لقسم المختبر وتوجيه المريض للكاشير.`;
        } else if (type === 'radiology') {
            const category = selectedRadiologyCategory || 'radiology';
            if (category === 'echo') {
                submitBtnText.textContent = 'اعتماد طلب الإيكو';
                infoList.innerHTML = `<i class="fas fa-check text-success me-1"></i> سيتم إنشاء طلب فحص الإيكو وتوجيهه لقسم الأشعة.`;
            } else if (category === 'ultrasound') {
                submitBtnText.textContent = 'اعتماد طلب السونار';
                infoList.innerHTML = `<i class="fas fa-check text-success me-1"></i> سيتم إنشاء طلب فحص السونار المعتمد وتسجيله للدفع المسبق بالكاشير.`;
            } else if (category === 'mri') {
                submitBtnText.textContent = 'اعتماد طلب الرنين';
                infoList.innerHTML = `<i class="fas fa-check text-success me-1"></i> سيتم إنشاء طلب تصوير بالرنين المغناطيسي.`;
            } else {
                submitBtnText.textContent = 'اعتماد طلب الأشعة';
                infoList.innerHTML = `<i class="fas fa-check text-success me-1"></i> سيتم إنشاء طلب أشعة عامة وتوجيهه للكاشير وقسم الأشعة.`;
            }
        } else if (type === 'blood_bank') {
            submitBtnText.textContent = 'اعتماد طلب مصرف الدم';
            infoList.innerHTML = `<i class="fas fa-check text-success me-1"></i> سيتم تسجيل طلب كروس ماتش / وحدات دم وإرسال البيانات لمصرف الدم.`;
        } else {
            submitBtnText.textContent = 'اعتماد وإنشاء الطلب';
            infoList.innerHTML = `<i class="fas fa-check text-success me-1"></i> سيتم إنشاء الطلب وتوجيهه للأقسام المعنية.`;
        }
    } else {
        submitBtnText.textContent = `اعتماد وإنشاء ${selectedTypes.size} طلبات`;
        infoList.innerHTML = `<i class="fas fa-layer-group text-primary me-1"></i> سيتم إنشاء ${selectedTypes.size} طلبات مدمجة ومستقلة لهذا المريض دفعة واحدة.`;
    }
    
    // التمرير السلس فقط في الشاشات الصغيرة (Mobile)
    if (window.innerWidth < 992) {
        setTimeout(() => {
            const reqDet = document.getElementById('requestDetails');
            if (reqDet) reqDet.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }
}

// التحقق قبل الإرسال
document.getElementById('requestForm').addEventListener('submit', function(e) {
    const isInsuranceLocked = {{ $isInsuranceLocked ? 'true' : 'false' }};
    const selectedInsuranceOption = document.querySelector('input[name="apply_insurance"]:checked')?.value;
    if (isInsuranceLocked && selectedInsuranceOption === '1') {
        e.preventDefault();
        showValidationError('لا يمكن إتمام الحجز تحت مظلة الضمان لوجود نواقص ببيانات المريض الأساسية.\nيرجى توجيه المريض لموظف التسجيل والضمان لإكمال النواقص أو اختيار (حجز نقدي كاش).', '⛔ قفل الضمان الصحي');
        return false;
    }

    if (selectedTypes.size === 0) {
        e.preventDefault();
        showValidationError('يرجى اختيار نوع الخدمة الطبية المطلوبة أولاً من القائمة.', 'تنبيه');
        return false;
    }
    
    if (selectedTypes.has('pharmacy')) {
        const description = document.getElementById('description').value.trim();
        if (!description) {
            e.preventDefault();
            showValidationError('يرجى كتابة وصف أو أسماء الأدوية المطلوبة للصيدلية.', 'بيانات ناقصة');
            document.getElementById('description').focus();
            return false;
        }
    }
    
    if (selectedTypes.has('checkup')) {
        const doctorId = document.getElementById('doctor_id').value;
        const appointmentDate = document.getElementById('appointment_date').value;
        
        if (!doctorId) {
            e.preventDefault();
            showValidationError('يرجى اختيار الطبيب الاستشاري من قائمة المتواجدين أو بالبحث.', 'تحديد الطبيب');
            const searchInput = document.getElementById('doctor_search_input');
            if (searchInput) searchInput.focus();
            return false;
        }
        
        if (!appointmentDate) {
            e.preventDefault();
            showValidationError('يرجى تحديد تاريخ موعد الكشفية.', 'تاريخ الموعد');
            document.getElementById('appointment_date').focus();
            return false;
        }
    }
    
    if (selectedTypes.has('radiology') && selectedRadiologyCategory === 'ultrasound') {
        const ultrasoundTypeId = document.getElementById('ultrasound_type_id').value;
        const ultrasoundStaffId = document.getElementById('ultrasound_staff_id').value;
        
        if (!ultrasoundTypeId) {
            e.preventDefault();
            showValidationError('يرجى اختيار نوع فحص السونار المطلوب.', 'فحص السونار');
            document.getElementById('ultrasound_search_input').focus();
            return false;
        }

        if (!ultrasoundStaffId) {
            e.preventDefault();
            showValidationError('يرجى اختيار أخصائي أو موظف السونار المسؤول.', 'أخصائي السونار');
            document.getElementById('ultrasound_staff_id').focus();
            return false;
        }
    }
    
    if (selectedTypes.has('radiology') && selectedRadiologyCategory === 'echo') {
        const echoTypeId = document.getElementById('echo_type_id').value;
        const echoStaffId = document.getElementById('echo_staff_id').value;
        
        if (!echoTypeId) {
            e.preventDefault();
            showValidationError('يرجى اختيار نوع فحص الإيكو.', 'فحص الإيكو');
            document.getElementById('echo_type_id').focus();
            return false;
        }
        
        if (!echoStaffId) {
            e.preventDefault();
            showValidationError('يرجى اختيار الموظف المسؤول عن الإيكو.', 'موظف الإيكو');
            document.getElementById('echo_staff_id').focus();
            return false;
        }
    }

    // 1. الحظر الصارم لتكرار كشفية نفس اليوم لنفس الطبيب
    const docWarnBox = document.getElementById('duplicateDoctorWarningBox');
    const isDoctorDuplicate = selectedTypes.has('checkup') && (
        (activeBookingState.checkup && activeBookingState.checkup.has_active) || 
        (docWarnBox && docWarnBox.style.display !== 'none')
    );

    if (isDoctorDuplicate) {
        e.preventDefault();
        e.stopPropagation();
        if (e.stopImmediatePropagation) e.stopImmediatePropagation();
        if (typeof window.hidePageLoader === 'function') window.hidePageLoader(true);
        enableSubmitButton();
        showValidationError('⛔ غير مسموح بتكرار الحجز لنفس اليوم نهائياً!\n\nالمريض لديه كشفية مسجلة اليوم بالفعل لدى هذا الطبيب، ويدخل للعيادة مباشرة للمتابعة دون حجز جديد.', 'حظر التكرار');
        return false;
    }

    // 2. التحقق من حجز السونار النشط غير المؤكد
    const confirmInput = document.getElementById('confirm_duplicate_booking');
    if (confirmInput && confirmInput.value !== '1') {
        let duplicateMessage = '';
        if (selectedTypes.has('radiology') && selectedRadiologyCategory === 'ultrasound' && activeBookingState.ultrasound && activeBookingState.ultrasound.has_active) {
            duplicateMessage = activeBookingState.ultrasound.message;
        }

        if (!duplicateMessage) {
            const sonarWarnBox = document.getElementById('duplicateUltrasoundWarningBox');
            if (selectedTypes.has('radiology') && selectedRadiologyCategory === 'ultrasound' && sonarWarnBox && sonarWarnBox.style.display !== 'none') {
                const msgEl = document.getElementById('duplicateUltrasoundMessage');
                duplicateMessage = (msgEl && msgEl.textContent.trim()) ? msgEl.textContent.trim() : 'المريض لديه حجز سونار نشط اليوم.';
            }
        }

        if (duplicateMessage) {
            e.preventDefault();
            e.stopPropagation();
            if (e.stopImmediatePropagation) e.stopImmediatePropagation();
            if (typeof window.hidePageLoader === 'function') window.hidePageLoader(true);
            openConfirmDuplicateModal(duplicateMessage);
            return false;
        }
    }

    disableSubmitButton();
});

const btnConfirmDuplicate = document.getElementById('btnConfirmDuplicate');
if (btnConfirmDuplicate) {
    btnConfirmDuplicate.addEventListener('click', function() {
        const isInsuranceLocked = {{ $isInsuranceLocked ? 'true' : 'false' }};
        const selectedInsuranceOption = document.querySelector('input[name="apply_insurance"]:checked')?.value;
        if (isInsuranceLocked && selectedInsuranceOption === '1') {
            showValidationError('لا يمكن إتمام الحجز تحت مظلة الضمان لوجود نواقص ببيانات المريض الأساسية.\nيرجى توجيه المريض لموظف التسجيل والضمان أو اختيار (حجز نقدي كاش).');
            return;
        }

        const confirmInput = document.getElementById('confirm_duplicate_booking');
        if (confirmInput) {
            confirmInput.value = '1';
        }
        const modalEl = document.getElementById('confirmDuplicateModal');
        if (modalEl) {
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
            } catch(e) {}
            try {
                if (typeof $ !== 'undefined' && typeof $(modalEl).modal === 'function') {
                    $(modalEl).modal('hide');
                }
            } catch(e) {}
        }
        disableSubmitButton();
        if (typeof window.showPageLoader === 'function') {
            window.showPageLoader();
        }
        const form = document.getElementById('requestForm');
        if (form) form.submit();
    });
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('radiology-category-btn')) {
        setRadiologyCategory(e.target.dataset.category);
        updateDetailsForm();
    }
});

// وظيفة البحث في السونار
const sonarTypes = @json($radiologyTypes->filter(fn($t) => $t->subcategory === 'سونار' || $t->main_category === 'سونار')->values());
const sonarSearchInput = document.getElementById('ultrasound_search_input');
const sonarHiddenId = document.getElementById('ultrasound_type_id');
const sonarSuggestions = document.getElementById('ultrasound_suggestions');
const sonarClearBtn = document.getElementById('clearUltrasoundSearch');
const sonarPriceBox = document.getElementById('ultrasoundPriceBox');
const sonarPriceText = document.getElementById('ultrasoundPriceText');

function escapeSonarHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

if (sonarSearchInput && sonarSuggestions) {
    sonarSearchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();

        if (query.length < 1) {
            sonarSuggestions.style.display = 'none';
            sonarSuggestions.innerHTML = '';
            sonarClearBtn.style.display = 'none';
            sonarHiddenId.value = '';
            sonarPriceBox.style.display = 'none';
            return;
        }

        sonarClearBtn.style.display = 'block';

        const matches = sonarTypes.filter(t => {
            const name = (t.name || '').toLowerCase();
            const code = (t.code || '').toLowerCase();
            return name.includes(query) || code.includes(query);
        });

        if (matches.length === 0) {
            sonarSuggestions.innerHTML = `
                <div class="p-3 text-muted text-center small">
                    <i class="fas fa-search me-1"></i> لا توجد نتائج مطابقة لـ "<strong>${escapeSonarHtml(this.value)}</strong>"
                </div>
            `;
            sonarSuggestions.style.display = 'block';
            return;
        }

        let html = '<div class="list-group list-group-flush">';
        matches.forEach(t => {
            const formattedPrice = new Intl.NumberFormat('en-US').format(t.base_price);
            html += `
                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 sonar-suggestion-item" 
                        data-id="${t.id}" 
                        data-name="${escapeSonarHtml(t.name)}" 
                        data-price="${t.base_price}">
                    <div>
                        <strong class="text-dark d-block">${escapeSonarHtml(t.name)}</strong>
                        <small class="text-muted font-monospace"><i class="fas fa-barcode me-1"></i>${escapeSonarHtml(t.code || '-')}</small>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                        ${formattedPrice} د.ع
                    </span>
                </button>
            `;
        });
        html += '</div>';
        sonarSuggestions.innerHTML = html;
        sonarSuggestions.style.display = 'block';
    });

    sonarSuggestions.addEventListener('click', function(e) {
        const btn = e.target.closest('.sonar-suggestion-item');
        if (!btn) return;

        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const price = parseFloat(btn.dataset.price);

        sonarHiddenId.value = id;
        sonarSearchInput.value = name;
        sonarSuggestions.style.display = 'none';
        sonarClearBtn.style.display = 'block';

        sonarPriceText.textContent = new Intl.NumberFormat('en-US').format(price) + ' د.ع';
        sonarPriceBox.style.display = 'block';
    });

    sonarClearBtn.addEventListener('click', function() {
        sonarSearchInput.value = '';
        sonarHiddenId.value = '';
        sonarSuggestions.style.display = 'none';
        sonarClearBtn.style.display = 'none';
        sonarPriceBox.style.display = 'none';
        sonarSearchInput.focus();
    });

    document.addEventListener('click', function(e) {
        if (!sonarSearchInput.contains(e.target) && !sonarSuggestions.contains(e.target)) {
            sonarSuggestions.style.display = 'none';
        }
    });

    if (sonarHiddenId.value) {
        const oldType = sonarTypes.find(t => t.id == sonarHiddenId.value);
        if (oldType) {
            sonarSearchInput.value = oldType.name;
            sonarClearBtn.style.display = 'block';
            sonarPriceText.textContent = new Intl.NumberFormat('en-US').format(oldType.base_price) + ' د.ع';
            sonarPriceBox.style.display = 'block';
        }
    }
}

// وظيفة البحث الذكي في الأطباء
const consultantDoctorsList = @json($doctorsJson ?? []);
const doctorSearchInput = document.getElementById('doctor_search_input');
const doctorHiddenId = document.getElementById('doctor_id');
const doctorSuggestions = document.getElementById('doctor_suggestions');
const doctorClearBtn = document.getElementById('clearDoctorSearchBtn');
const selectedDoctorBox = document.getElementById('selectedDoctorBox');
const selectedDoctorNameText = document.getElementById('selectedDoctorNameText');
const selectedDoctorDeptText = document.getElementById('selectedDoctorDeptText');

function escapeDoctorHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

if (doctorSearchInput && doctorSuggestions) {
    doctorSearchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();

        if (query.length < 1) {
            doctorSuggestions.style.display = 'none';
            doctorSuggestions.innerHTML = '';
            doctorClearBtn.style.display = 'none';
            doctorHiddenId.value = '';
            if (selectedDoctorBox) selectedDoctorBox.style.display = 'none';
            return;
        }

        doctorClearBtn.style.display = 'block';

        const matches = consultantDoctorsList.filter(d => {
            const name = (d.name || '').toLowerCase();
            const rawName = (d.raw_name || '').toLowerCase();
            const spec = (d.specialization || '').toLowerCase();
            const dept = (d.department_name || '').toLowerCase();
            return name.includes(query) || rawName.includes(query) || spec.includes(query) || dept.includes(query);
        });

        if (matches.length === 0) {
            doctorSuggestions.innerHTML = `
                <div class="p-3 text-muted text-center small">
                    <i class="fas fa-user-slash me-1"></i> لا يوجد أطباء مطابقين لـ "<strong>${escapeDoctorHtml(this.value)}</strong>"
                </div>
            `;
            doctorSuggestions.style.display = 'block';
            return;
        }

        let html = '<div class="list-group list-group-flush">';
        matches.forEach(d => {
            const availBadge = d.is_available 
                ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-0.5" style="font-size: 10px;"><i class="fas fa-check-circle me-1"></i>متوفر اليوم</span>'
                : '<span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-0.5" style="font-size: 10px;"><i class="fas fa-times-circle me-1"></i>غير متاح</span>';

            html += `
                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 doctor-suggestion-item" 
                        data-id="${d.id}" 
                        data-name="${escapeDoctorHtml(d.name)}" 
                        data-specialization="${escapeDoctorHtml(d.specialization)}" 
                        data-dept="${escapeDoctorHtml(d.department_name)}"
                        data-available="${d.is_available ? '1' : '0'}">
                    <div>
                        <strong class="text-dark d-block small"><i class="fas fa-user-md me-1 text-primary"></i>${escapeDoctorHtml(d.name)}</strong>
                        <small class="text-muted" style="font-size: 11px;"><i class="fas fa-stethoscope me-1"></i>${escapeDoctorHtml(d.specialization)} ${d.department_name ? '• ' + escapeDoctorHtml(d.department_name) : ''}</small>
                    </div>
                    <div>
                        ${availBadge}
                    </div>
                </button>
            `;
        });
        html += '</div>';
        doctorSuggestions.innerHTML = html;
        doctorSuggestions.style.display = 'block';
    });

    doctorSuggestions.addEventListener('click', function(e) {
        const btn = e.target.closest('.doctor-suggestion-item');
        if (!btn) return;

        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const spec = btn.dataset.specialization;
        const dept = btn.dataset.dept;

        doctorHiddenId.value = id;
        doctorSearchInput.value = `${name} - ${spec}`;
        doctorSuggestions.style.display = 'none';
        doctorClearBtn.style.display = 'block';

        if (selectedDoctorBox) {
            selectedDoctorNameText.textContent = `${name} - ${spec}`;
            selectedDoctorDeptText.textContent = dept ? `العيادة: ${dept}` : '';
            selectedDoctorBox.style.display = 'flex';
        }

        checkDoctorActiveBooking(id);
    });

    doctorClearBtn.addEventListener('click', function() {
        doctorSearchInput.value = '';
        doctorHiddenId.value = '';
        doctorSuggestions.style.display = 'none';
        doctorSuggestions.innerHTML = '';
        doctorClearBtn.style.display = 'none';
        if (selectedDoctorBox) selectedDoctorBox.style.display = 'none';
        checkDoctorActiveBooking(null);
        doctorSearchInput.focus();
    });

    document.addEventListener('click', function(e) {
        if (!doctorSearchInput.contains(e.target) && !doctorSuggestions.contains(e.target)) {
            doctorSuggestions.style.display = 'none';
        }
    });

    if (doctorHiddenId.value && !doctorSearchInput.value) {
        const oldDoc = consultantDoctorsList.find(d => d.id == doctorHiddenId.value);
        if (oldDoc) {
            doctorSearchInput.value = `${oldDoc.name} - ${oldDoc.specialization}`;
            doctorClearBtn.style.display = 'block';
            if (selectedDoctorBox) {
                selectedDoctorNameText.textContent = `${oldDoc.name} - ${oldDoc.specialization}`;
                selectedDoctorDeptText.textContent = oldDoc.department_name ? `العيادة: ${oldDoc.department_name}` : '';
                selectedDoctorBox.style.display = 'flex';
            }
            checkDoctorActiveBooking(doctorHiddenId.value);
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateInsuranceVisibility();

    const oldTypes = @json(old('request_type', []));
    const oldRadiologyCategory = @json(old('radiology_category', $defaultRadiologyCategory));
    if (oldRadiologyCategory) {
        selectedRadiologyCategory = oldRadiologyCategory;
    }
    if (oldTypes && oldTypes.length > 0) {
        oldTypes.forEach(type => {
            toggleRequestType(type);
        });
    }
    if (oldTypes && oldTypes.includes('radiology')) {
        setRadiologyCategory(oldRadiologyCategory);
        updateRadiologyCategoryInfo();
    }

    @if(session('duplicate_booking_warning'))
        const warning = @json(session('duplicate_booking_warning'));
        if (warning && warning.message) {
            setTimeout(function() {
                openConfirmDuplicateModal(warning.message);
            }, 300);
        }
    @endif
});
</script>
@endsection
