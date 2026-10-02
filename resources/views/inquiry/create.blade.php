@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-hospital-user me-2"></i>
                        إنشاء طلب جديد - الاستعلامات
                    </h2>
                    <p class="text-muted">اختر نوع الخدمة المطلوبة للمريض</p>
                </div>
                <a href="{{ route('inquiry.search') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>
                    العودة
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <script>
            // بعد عرض رسالة النجاح، إعادة تعيين الحالة حتى يمكن إنشاء طلب جديد بسهولة
            document.addEventListener('DOMContentLoaded', function() {
                // تأخير قصير للسماح بعرض الرسالة قبل المسح
                setTimeout(() => {
                    // إلغاء تحديد البطاقات
                    document.querySelectorAll('.request-card').forEach(card => card.classList.remove('selected'));
                    // إخفاء قسم التفاصيل
                    const details = document.getElementById('requestDetails');
                    if (details) details.style.display = 'none';
                    // مسح الأنواع المختارة
                    selectedTypes.clear();
                    updateFormFields();
                }, 500);
            });
        </script>
    @endif

    <!-- معلومات المريض -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i>
                        بيانات المريض
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>الاسم:</strong>
                            <p class="text-muted mb-1">{{ optional($patient->user)->name ?? 'غير معروف' }}</p>
                        </div>
                        <div class="col-md-2">
                            <strong>العمر:</strong>
                            <p class="text-muted mb-1">{{ $patient->age }} سنة</p>
                        </div>
                        <div class="col-md-2">
                            <strong>الجنس:</strong>
                            <p class="text-muted mb-1">
                                @if(optional($patient->user)->gender == 'male')
                                    <i class="fas fa-mars text-primary"></i> ذكر
                                @elseif(optional($patient->user)->gender == 'female')
                                    <i class="fas fa-venus text-danger"></i> أنثى
                                @else
                                    غير محدد
                                @endif
                            </p>
                        </div>
                        <div class="col-md-3">
                            <strong>رقم الهاتف:</strong>
                            <p class="text-muted mb-1">{{ optional($patient->user)->phone ?? 'غير متوفر' }}</p>
                        </div>
                        <div class="col-md-2">
                            <strong>العنوان:</strong>
                            <p class="text-muted mb-1">{{ optional($patient->user)->address ?? 'غير متوفر' }}</p>
                        </div>
                    </div>

                    @if($patient->insurance_type && $patient->insurance_type !== 'none')
                        <div class="row mt-2 pt-2 border-top">
                            <div class="col-12">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <span class="badge bg-success fs-7">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        مشمول بالضمان:
                                        @if($patient->insurance_type === 'moi')
                                            ضمان قوى الأمن الداخلي (وزارة الداخلية)
                                        @elseif($patient->insurance_type === 'hi')
                                            هيئة الضمان الصحي الوطني
                                        @endif
                                    </span>
                                    @if($patient->insurance_type === 'hi' && $patient->healthInsuranceCategory)
                                        <span class="badge bg-info text-dark fs-7">
                                            <i class="fas fa-layer-group me-1"></i>
                                            الفئة {{ $patient->healthInsuranceCategory->code }} - {{ $patient->healthInsuranceCategory->name }}
                                        </span>
                                        @if($patient->healthInsuranceCategory->requires_thermal_stamp)
                                            <span class="badge bg-danger fs-7">
                                                <i class="fas fa-stamp me-1"></i>شرط الختم الحراري (0%)
                                            </span>
                                        @endif
                                    @endif
                                    @if($patient->insurance_card_no || $patient->insurance_booklet_number)
                                        <span class="badge bg-secondary fs-7">
                                            <i class="fas fa-id-card me-1"></i>
                                            رقم الهوية / الدفتر: {{ $patient->insurance_card_no ?: $patient->insurance_booklet_number }}
                                        </span>
                                    @endif
                                    <small class="text-muted"><i class="fas fa-check-circle text-success me-1"></i>سيتم تحويل الحجز للكاشير بنظام التغطية والتسعير المعتمد</small>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="row mt-2 pt-2 border-top">
                            <div class="col-12">
                                <span class="badge bg-light text-secondary border">
                                    <i class="fas fa-money-bill-wave me-1"></i> دفع نقدي مباشر (بدون ضمان)
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- أنواع الطلبات -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="fas fa-list-check me-2"></i>
                اختر نوع الخدمة المطلوبة
                <small class="text-muted d-block mt-1">انقر على البطاقات لاختيار الخدمات (يمكن اختيار أكثر من خدمة)</small>
            </h4>
        </div>
    </div>

    <form action="{{ route('inquiry.store') }}" method="POST" id="requestForm">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
        <div id="requestTypesContainer">
            <!-- سيتم إضافة حقول request_type[] هنا عبر JavaScript -->
        </div>
        <input type="hidden" name="radiology_category" id="radiology_category" value="{{ old('radiology_category', 'radiology') }}">

        <div class="row g-4 mb-4">
            @foreach($requestTypes as $type => $config)
                @php
                    // إخفاء بطاقة الأشعة إذا لم يكن لدى المستخدم أي صلاحيات للأشعة
                    if ($type === 'radiology' && !$radiologyPermissions['general'] && !$radiologyPermissions['ultrasound'] && !$radiologyPermissions['mri'] && !$radiologyPermissions['echo']) {
                        continue;
                    }
                @endphp
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 shadow-sm request-card" data-type="{{ $type }}" onclick="toggleRequestType('{{ $type }}')">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas {{ $config['icon'] }} fa-4x text-{{ $config['color'] }}"></i>
                            </div>
                            <h5 class="card-title">{{ $config['label'] }}</h5>
                            <p class="card-text text-muted small">
                                @switch($type)
                                    @case('lab')
                                        فحوصات مخبرية وتحاليل الدم والبول
                                        @break
                                    @case('radiology')
                                        أشعة عادية، مقطعية، وتصوير بالرنين
                                        @break
                                    @case('pharmacy')
                                        صرف أدوية ومستلزمات طبية
                                        @break
                                    @case('checkup')
                                        حجز موعد للطبيب والاستشارة الطبية
                                        @break
                                    @case('blood_bank')
                                        طلب كروس ماتش أو تحضير وحدات دم
                                        @break
                                    @default
                                        خدمة طبية
                                @endswitch
                            </p>
                            <div class="departments-list small text-muted" style="display: none;">
                                @if($config['departments']->count() > 0)
                                    <strong>الأقسام المتاحة:</strong>
                                    <ul class="list-unstyled mt-2">
                                        @foreach($config['departments'] as $dept)
                                            <li><i class="fas fa-check-circle text-success"></i> {{ $dept->name }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-danger">لا توجد أقسام متاحة</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-center" style="display: none;">
                            <span class="badge bg-{{ $config['color'] }}">محدد</span>
                        </div>
                    </div>
                </div>
            @endforeach

            @can('create surgeries')
                <!-- بطاقة حجز عملية جراحية -->
                <div class="col-md-6 col-lg-3">
                    <a href="{{ route('surgeries.create', [
                        'patient_id' => $patient->id,
                        'visit_id' => $visit->id ?? null,
                        'doctor_id' => $visit->doctor_id ?? null,
                        'department_id' => $visit->department_id ?? null
                    ]) }}" class="text-decoration-none">
                        <div class="card h-100 shadow-sm request-card surgery-card" style="cursor: pointer;">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <i class="fas fa-procedures fa-4x text-danger"></i>
                                </div>
                                <h5 class="card-title text-dark">حجز عملية جراحية</h5>
                                <p class="card-text text-muted small">
                                    حجز موعد لإجراء عملية جراحية
                                </p>
                                <div class="mt-2">
                                    <span class="badge bg-danger">
                                        <i class="fas fa-external-link-alt me-1"></i>
                                        انتقال لنموذج الحجز
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endcan

            @can('create bed reservations')
                <!-- بطاقة رقود مبدئي -->
                <div class="col-md-6 col-lg-3">
                    <a href="{{ route('bed-reservations.create', ['patient_id' => $patient->id]) }}" class="text-decoration-none">
                        <div class="card h-100 shadow-sm request-card surgery-card" style="cursor: pointer;">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <i class="fas fa-bed fa-4x text-info"></i>
                                </div>
                                <h5 class="card-title text-dark">حجز رقود مبدئي</h5>
                                <p class="card-text text-muted small">
                                    احجز سريراً للإقامة أو التحضير للعملية
                                </p>
                                <div class="mt-2">
                                    <span class="badge bg-info">
                                        <i class="fas fa-external-link-alt me-1"></i>
                                        انتقال لنموذج الحجز
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endcan

            @can('create incubator reservations')
                <!-- بطاقة حجز حاضنة خدج -->
                <div class="col-md-6 col-lg-3">
                    @if($patient->age < 1)
                        <a href="{{ route('incubator-reservations.create', ['patient_id' => $patient->id]) }}" class="text-decoration-none">
                            <div class="card h-100 shadow-sm request-card surgery-card" style="cursor: pointer;">
                                <div class="card-body text-center p-4">
                                    <div class="mb-3">
                                        <i class="fas fa-baby fa-4x text-pink"></i>
                                    </div>
                                    <h5 class="card-title text-dark">حجز حاضنة خدج</h5>
                                    <p class="card-text text-muted small">
                                        حجز حاضنة في قسم العناية المركزة بالخدج
                                    </p>
                                    <div class="mt-2">
                                        <span class="badge" style="background-color: #e91e63; color: white;">
                                            <i class="fas fa-external-link-alt me-1"></i>
                                            انتقال لنموذج الحجز
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @else
                        <div class="card h-100 shadow-sm" style="opacity: 0.6; cursor: not-allowed;">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <i class="fas fa-baby fa-4x text-muted"></i>
                                </div>
                                <h5 class="card-title text-muted">حجز حاضنة خدج</h5>
                                <p class="card-text text-muted small">
                                    خدمة مخصصة للأطفال حديثي الولادة فقط
                                </p>
                                <div class="mt-2">
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-ban me-1"></i>
                                        غير متاح (عمر المريض {{ $patient->age }} سنة)
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endcan
        </div>

        <!-- نموذج التفاصيل -->
        <div id="requestDetails" style="display: none;">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card shadow-sm">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-edit me-2"></i>
                                تفاصيل الطلب
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- حقول عامة - تظهر للكشف الطبي والصيدلية فقط -->
                            <div id="generalFields" style="display: none;">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label for="description" class="form-label">
                                            <i class="fas fa-comment-medical me-1"></i>
                                            وصف الحالة / التفاصيل <span class="text-danger general-required">*</span>
                                        </label>
                                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                                  id="description" 
                                                  name="description" 
                                                  rows="4" 
                                                  placeholder="اكتب وصفاً تفصيلياً للحالة أو الخدمة المطلوبة...">{{ old('description') }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- حقول خاصة بالكشف الطبي -->
                            <div id="checkupFields" style="display: none;">
                                <div class="row">
                                    @php
                                        $selectedDoctor = old('doctor_id') ? $doctors->firstWhere('id', old('doctor_id')) : null;
                                        $selectedDoctorName = $selectedDoctor ? ('د. ' . (optional($selectedDoctor->user)->name ?? 'طبيب') . ' - ' . ($selectedDoctor->specialization ?? '')) : '';
                                        $selectedDoctorDept = $selectedDoctor ? optional($selectedDoctor->department)->name : '';
                                    @endphp
                                    <div class="col-md-6 mb-3 position-relative">
                                        <label for="doctor_search_input" class="form-label fw-bold">
                                            <i class="fas fa-user-md me-1 text-primary"></i>
                                            الطبيب الاستشاري <span class="text-danger">*</span>
                                        </label>
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

                                        <!-- قائمة الاقتراحات الذكية: تظهر بعد كتابة أول حرف -->
                                        <div id="doctor_suggestions" 
                                             class="position-absolute w-100 bg-white shadow-lg rounded-3 border mt-1" 
                                             style="display: none; max-height: 280px; overflow-y: auto; z-index: 1055; left: 0;">
                                        </div>

                                        <!-- بطاقة تأكيد اختيار الطبيب -->
                                        <div id="selectedDoctorBox" class="mt-2 p-2 rounded-2 bg-light border border-success border-opacity-25 align-items-center justify-content-between" style="{{ $selectedDoctor ? 'display: flex;' : 'display: none;' }}">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-user-check text-success fs-5"></i>
                                                <div>
                                                    <strong class="text-success d-block" id="selectedDoctorNameText">{{ $selectedDoctorName }}</strong>
                                                    <small class="text-muted" id="selectedDoctorDeptText">{{ $selectedDoctorDept ? 'العيادة: ' . $selectedDoctorDept : '' }}</small>
                                                </div>
                                            </div>
                                            <span class="badge bg-success rounded-pill px-2 py-1">محدد</span>
                                        </div>

                                        @error('doctor_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="appointment_date" class="form-label">
                                            <i class="fas fa-calendar me-1"></i>
                                            تاريخ الموعد
                                        </label>
                                        <input type="date" 
                                               class="form-control @error('appointment_date') is-invalid @enderror" 
                                               id="appointment_date" 
                                               name="appointment_date"
                                               value="{{ old('appointment_date', date('Y-m-d')) }}"
                                               min="{{ date('Y-m-d') }}">
                                        @error('appointment_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- حقول خاصة بالتحاليل -->
                            <div id="labFields" style="display: none;">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>ملاحظة:</strong> سيتم إنشاء طلب تحويل عام للمختبر. 
                                            سيقوم موظف المختبر لاحقاً بتحديد التحاليل المطلوبة بالتفصيل قبل الدفع.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- حقول خاصة بالأشعة -->
                            <div id="radiologyFields" style="display: none;">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>ملاحظة:</strong> سيتم إنشاء طلب تحويل عام لقسم الأشعة. 
                                            سيقوم موظف الأشعة لاحقاً بتحديد أنواع الأشعة المطلوبة بالتفصيل قبل الدفع.
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label fw-bold">نوع الأشعة</label>
                                        <div class="btn-group" role="group" aria-label="خيارات الأشعة">
                                            @if($radiologyPermissions['general'])
                                                <button type="button" class="btn btn-outline-secondary radiology-category-btn" data-category="radiology">أشعة عامة</button>
                                            @endif
                                            @if($radiologyPermissions['ultrasound'])
                                                <button type="button" class="btn btn-outline-secondary radiology-category-btn" data-category="ultrasound">سونار</button>
                                            @endif
                                            @if($radiologyPermissions['mri'])
                                                <button type="button" class="btn btn-outline-secondary radiology-category-btn" data-category="mri">رنين مغناطيسي</button>
                                            @endif
                                            @if($radiologyPermissions['echo'])
                                                <button type="button" class="btn btn-outline-secondary radiology-category-btn" data-category="echo">إيكو</button>
                                            @endif
                                        </div>
                                        <div class="form-text">اختر فئة الأشعة المناسبة لهذا الطلب.</div>
                                        @if(!$radiologyPermissions['general'] && !$radiologyPermissions['ultrasound'] && !$radiologyPermissions['mri'] && !$radiologyPermissions['echo'])
                                            <div class="alert alert-warning mt-2">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                ليس لديك صلاحية لحجز أي نوع من الأشعة. يرجى التواصل مع المدير لمنحك الصلاحيات المناسبة.
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- حقول خاصة بالسونار -->
                                    <div class="col-12 mb-3" id="ultrasoundDetailsContainer" style="display: none;">
                                        <div class="card border-primary shadow-sm">
                                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-wave-square me-2"></i>تفاصيل وتحديد نوع فحص السونار</span>
                                                <span class="badge bg-light text-primary fw-bold"><i class="fas fa-cash-register me-1"></i>دفع مسبق في الكاشير</span>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-7 mb-3 position-relative">
                                                        <label for="ultrasound_search_input" class="form-label fw-bold">نوع فحص السونار <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-primary"></i></span>
                                                            <input type="text" 
                                                                   class="form-control border-start-0" 
                                                                   id="ultrasound_search_input" 
                                                                   placeholder="ابحث عن نوع السونار (اكتب حرفاً للبدء)..." 
                                                                   autocomplete="off"
                                                                   value="">
                                                            <button class="btn btn-outline-secondary" type="button" id="clearUltrasoundSearch" style="display: none;" title="مسح">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                        <input type="hidden" id="ultrasound_type_id" name="ultrasound_type_id" value="{{ old('ultrasound_type_id') }}">

                                                        <!-- قائمة الاقتراحات المنسدلة: لا تظهر أي نتائج إلا بعد إدخال أول حرف -->
                                                        <div id="ultrasound_suggestions" 
                                                             class="position-absolute w-100 bg-white shadow-lg rounded-3 border mt-1" 
                                                             style="display: none; max-height: 260px; overflow-y: auto; z-index: 1050; left: 0;">
                                                        </div>

                                                        @error('ultrasound_type_id')
                                                            <div class="text-danger mt-1">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="col-md-5 mb-3">
                                                        <label for="ultrasound_staff_id" class="form-label fw-bold">أخصائي / موظف السونار <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="ultrasound_staff_id" name="ultrasound_staff_id">
                                                            <option value="">اختر الموظف...</option>
                                                            @foreach($ultrasoundStaff as $staff)
                                                                <option value="{{ $staff->id }}" {{ old('ultrasound_staff_id') == $staff->id ? 'selected' : '' }}>
                                                                    {{ $staff->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('ultrasound_staff_id')
                                                            <div class="text-danger mt-1">{{ $message }}</div>
                                                        @enderror
                                                    </div>

                                                    <!-- شريط معاينة السعر المعتمد -->
                                                    <div class="col-12 mb-3" id="ultrasoundPriceBox" style="display: none;">
                                                        <div class="p-2 px-3 bg-light border border-primary border-opacity-25 rounded d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <i class="fas fa-tag text-primary me-2"></i>
                                                                <span class="text-muted small">سعر فحص السونار المعتمد:</span>
                                                                <strong class="text-primary fs-6 me-2" id="ultrasoundPriceText">0 د.ع</strong>
                                                            </div>
                                                            <span class="badge bg-warning text-dark"><i class="fas fa-receipt me-1"></i>جاهز للدفع في الكاشير فور تأكيد الحجز</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="alert alert-info mb-0 py-2">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <small>سيتم إرسال الطلب فوراً إلى الكاشير لدفع الأجور، ولا يُسمح بدخول المريض لغرفة السونار إلا بعد إتمام الدفع.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- حقول خاصة بالإيكو -->
                                    <div class="col-12 mb-3" id="echoDetailsContainer" style="display: none;">
                                        <div class="card border-info">
                                            <div class="card-header bg-info text-white">
                                                <i class="fas fa-heartbeat me-2"></i>تفاصيل الإيكو
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="echo_type_id" class="form-label fw-bold">نوع الإيكو <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="echo_type_id" name="echo_type_id">
                                                            <option value="">اختر نوع الإيكو...</option>
                                                            @foreach($radiologyTypes->where('subcategory', 'إيكو') as $type)
                                                                <option value="{{ $type->id }}" {{ old('echo_type_id') == $type->id ? 'selected' : '' }}>
                                                                    {{ $type->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('echo_type_id')
                                                            <div class="text-danger mt-1">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="echo_staff_id" class="form-label fw-bold">الموظف المسؤول <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="echo_staff_id" name="echo_staff_id">
                                                            <option value="">اختر الموظف...</option>
                                                            @foreach($echoStaff as $staff)
                                                                <option value="{{ $staff->id }}" {{ old('echo_staff_id') == $staff->id ? 'selected' : '' }}>
                                                                    {{ $staff->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('echo_staff_id')
                                                            <div class="text-danger mt-1">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="alert alert-info mb-0">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <small>يجب تحديد نوع الإيكو والموظف المسؤول قبل إنشاء الطلب</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            @if($patient->insurance_type && $patient->insurance_type !== 'none')
                            <div class="row mt-3" id="insuranceCoverageSection">
                                <div class="col-12">
                                    <div class="p-3 border rounded-3 bg-light">
                                        <label class="form-label fw-bold d-flex align-items-center gap-2 mb-2">
                                            <i class="fas fa-shield-alt text-primary"></i>
                                            تغطية الضمان لهذا الحجز:
                                        </label>
                                        <div class="d-flex flex-wrap gap-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="apply_insurance" id="apply_insurance_yes" value="1" {{ old('apply_insurance', '1') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold text-success" for="apply_insurance_yes">
                                                    <i class="fas fa-check-circle me-1"></i>
                                                    حجز تحت مظلة الضمان ({{ $patient->insurance_type === 'moi' ? 'ضمان قوى الأمن الداخلي' : 'هيئة الضمان الصحي' }})
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="apply_insurance" id="apply_insurance_no" value="0" {{ old('apply_insurance') === '0' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold text-secondary" for="apply_insurance_no">
                                                    <i class="fas fa-money-bill-wave me-1"></i>
                                                    حجز نقدي خاص (دون استهلاك رصيد الضمان للمريض)
                                                </label>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            <i class="fas fa-info-circle me-1"></i>
                                            سيظهر اختيارك هذا تلقائياً في شاشة الكاشير عند المحاسبة.
                                        </small>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="row mt-3">
                                <div class="col-12" id="autoReferContainer">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               value="1" 
                                               id="autoRefer" 
                                               name="auto_refer"
                                               {{ old('auto_refer') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="autoRefer">
                                            <strong>التحويل التلقائي</strong> - الانتقال مباشرة لصفحة التحويل بعد إنشاء الطلب
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <span id="submitBtnText">إنشاء الطلب</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ملخص العملية -->
                    <div class="alert alert-info mt-3" role="alert" id="infoAlert">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>
                            ملاحظة
                        </h6>
                        <ul class="mb-0" id="infoList">
                            <li>سيتم إنشاء طلب جديد في قسم الاستعلامات</li>
                            <li>يمكنك بعد ذلك تحويل المريض للقسم المناسب</li>
                            <li>أو اختر "التحويل التلقائي" للانتقال مباشرة</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.request-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.request-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.request-card.selected {
    border-color: #0d6efd;
    background-color: #e3f2fd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.request-card.selected .card-footer {
    display: block !important;
}

.request-card.selected .departments-list {
    display: block !important;
}

.surgery-card:hover {
    border-color: #dc3545;
    background-color: #fff5f5;
}

.surgery-card:hover .fa-procedures {
    transform: scale(1.1);
    transition: transform 0.3s ease;
}

.radiology-category-btn.active {
    background-color: #0d6efd;
    color: #ffffff;
    border-color: #0d6efd;
}

.radiology-category-btn:hover {
    border-color: #0d6efd;
}

/* لون خاص لأيقونة حاضنة الخدج */
.text-pink {
    color: #e91e63 !important;
}

.surgery-card:hover .fa-baby {
    transform: scale(1.1);
    transition: transform 0.3s ease;
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

function setRadiologyCategory(category) {
    selectedRadiologyCategory = category;
    const radiologyCategoryInput = document.getElementById('radiology_category');
    if (radiologyCategoryInput) {
        radiologyCategoryInput.value = category;
    }
    document.querySelectorAll('.radiology-category-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.category === category);
    });
    
    // إظهار/إخفاء قسم السونار
    const ultrasoundDetailsContainer = document.getElementById('ultrasoundDetailsContainer');
    if (ultrasoundDetailsContainer) {
        if (category === 'ultrasound') {
            ultrasoundDetailsContainer.style.display = 'block';
            document.getElementById('ultrasound_staff_id').required = true;
            document.getElementById('ultrasound_type_id').required = true;
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
        }
    }
    
    // إظهار/إخفاء قسم الإيكو
    const echoDetailsContainer = document.getElementById('echoDetailsContainer');
    if (echoDetailsContainer) {
        if (category === 'echo') {
            echoDetailsContainer.style.display = 'block';
            // جعل حقول الإيكو مطلوبة
            document.getElementById('echo_type_id').required = true;
            document.getElementById('echo_staff_id').required = true;
        } else {
            echoDetailsContainer.style.display = 'none';
            // إلغاء جعل حقول الإيكو مطلوبة
            document.getElementById('echo_type_id').required = false;
            document.getElementById('echo_staff_id').required = false;
            // مسح القيم
            document.getElementById('echo_type_id').value = '';
            document.getElementById('echo_staff_id').value = '';
        }
    }

    updateInsuranceVisibility();
}

function updateInsuranceVisibility() {
    const insuranceRow = document.getElementById('insuranceCoverageSection');
    if (!insuranceRow) return;

    // السونار المباشر من الاستعلامات حجز عادي نقدي فقط (إخفاء خيار الضمان بالكامل)
    const isDirectUltrasound = selectedTypes.has('radiology') && (selectedRadiologyCategory === 'ultrasound');
    if (isDirectUltrasound) {
        insuranceRow.style.display = 'none';
        const noRadio = document.getElementById('apply_insurance_no');
        if (noRadio) noRadio.checked = true;
    } else {
        insuranceRow.style.display = 'block';
    }
}

function updateRadiologyCategoryInfo() {
    const category = selectedRadiologyCategory || defaultRadiologyCategory;
    if (category) {
        setRadiologyCategory(category);
    }
}

function toggleRequestType(type) {
    const card = document.querySelector(`.request-card[data-type="${type}"]`);
    
    if (selectedTypes.has(type)) {
        // إلغاء التحديد
        selectedTypes.delete(type);
        card.classList.remove('selected');
        console.log('تم إلغاء اختيار:', type);
    } else {
        // إضافة التحديد
        selectedTypes.add(type);
        card.classList.add('selected');
        console.log('تم اختيار:', type);
    }
    
    // تحديث حقول النموذج
    updateFormFields();
    
    // عرض/إخفاء نموذج التفاصيل
    const details = document.getElementById('requestDetails');
    if (selectedTypes.size > 0) {
        details.style.display = 'block';
        updateDetailsForm();
    } else {
        details.style.display = 'none';
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
    
    // إخفاء جميع الحقول الخاصة أولاً
    checkupFields.style.display = 'none';
    labFields.style.display = 'none';
    radiologyFields.style.display = 'none';
    generalFields.style.display = 'none';
    autoReferContainer.style.display = 'none';
    
    // إظهار الحقول حسب الأنواع المحددة
    if (selectedTypes.has('checkup')) {
        checkupFields.style.display = 'block';
    }
    
    if (selectedTypes.has('lab')) {
        labFields.style.display = 'block';
        autoReferContainer.style.display = 'block';
    }
    
    if (selectedTypes.has('radiology')) {
        radiologyFields.style.display = 'block';
        autoReferContainer.style.display = 'block';
        updateRadiologyCategoryInfo();
    }
    
    if (selectedTypes.has('pharmacy')) {
        generalFields.style.display = 'block';
        autoReferContainer.style.display = 'block';
    }
    
    if (selectedTypes.has('emergency')) {
        // حقول الطوارئ
        document.getElementById('emergencyFields').style.display = 'block';
    }

    // تحديث نص الزر والملاحظات
    if (selectedTypes.size === 1) {
        const type = Array.from(selectedTypes)[0];
        if (type === 'checkup') {
            submitBtnText.textContent = 'حجز موعد';
            infoList.innerHTML = `
                <li>سيتم حجز موعد للمريض مع الطبيب المحدد</li>
                <li>يمكن تحديد تاريخ الموعد أو اختيار اليوم</li>
                <li>سيتم إنشاء موعد في نظام المواعيد</li>
            `;
        } else if (type === 'lab') {
            submitBtnText.textContent = 'طلب تحاليل';
            infoList.innerHTML = `
                <li>سيتم إنشاء طلب تحاليل للمريض</li>
                <li>المريض يذهب للكاشير لدفع الأجور</li>
                <li>بعد الدفع، يتوجه للمختبر لإجراء التحاليل</li>
            `;
        } else if (type === 'radiology') {
            const category = selectedRadiologyCategory || 'radiology';
            if (category === 'echo') {
                submitBtnText.textContent = 'طلب إيكو';
                infoList.innerHTML = `
                    <li>سيتم إنشاء طلب إيكو للمريض</li>
                    <li>المريض يذهب للكاشير لدفع الأجور</li>
                    <li>بعد الدفع، يتوجه لقسم الأشعة لإجراء الإيكو</li>
                `;
            } else if (category === 'ultrasound') {
                submitBtnText.textContent = 'طلب سونار';
                infoList.innerHTML = `
                    <li>سيتم إنشاء طلب سونار للمريض</li>
                    <li>المريض يذهب للكاشير لدفع الأجور</li>
                    <li>بعد الدفع، يتوجه لقسم الأشعة لإجراء السونار</li>
                `;
            } else if (category === 'mri') {
                submitBtnText.textContent = 'طلب رنين مغناطيسي';
                infoList.innerHTML = `
                    <li>سيتم إنشاء طلب رنين مغناطيسي للمريض</li>
                    <li>المريض يذهب للكاشير لدفع الأجور</li>
                    <li>بعد الدفع، يتوجه لقسم الأشعة لإجراء الرنين</li>
                `;
            } else {
                submitBtnText.textContent = 'طلب أشعة';
                infoList.innerHTML = `
                    <li>سيتم إنشاء طلب أشعة للمريض</li>
                    <li>المريض يذهب للكاشير لدفع الأجور</li>
                    <li>بعد الدفع، يتوجه لقسم الأشعة لإجراء التصوير</li>
                `;
            }
        } else if (type === 'blood_bank') {
            submitBtnText.textContent = 'طلب مصرف الدم';
            infoList.innerHTML = `
                <li>سيتم إنشاء طلب مصرف الدم للمريض</li>
                <li>المريض يذهب للكاشير لدفع الرسوم أولاً ثم ينتقل إلى مصرف الدم</li>
                <li>سيتم حفظ بيانات الكروس ماتش ونتيجة التوافق</li>
            `;
        } else {
            submitBtnText.textContent = 'إنشاء الطلب';
            infoList.innerHTML = `
                <li>سيتم إنشاء طلب جديد في قسم الاستعلامات</li>
                <li>يمكنك بعد ذلك تحويل المريض للقسم المناسب</li>
                <li>أو اختر "التحويل التلقائي" للانتقال مباشرة</li>
            `;
        }
    } else {
        submitBtnText.textContent = `إنشاء ${selectedTypes.size} طلبات`;
        infoList.innerHTML = `
            <li>سيتم إنشاء ${selectedTypes.size} طلبات مختلفة للمريض</li>
            <li>كل طلب سيتم معالجته حسب نوعه</li>
            <li>المريض سيحتاج للدفع لكل خدمة على حدة</li>
        `;
    }
    
    // التمرير السلس للنموذج
    setTimeout(() => {
        document.getElementById('requestDetails').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    }, 100);
}



// التحقق قبل الإرسال
document.getElementById('requestForm').addEventListener('submit', function(e) {
    if (selectedTypes.size === 0) {
        e.preventDefault();
        alert('يرجى اختيار نوع الخدمة أولاً');
        return false;
    }
    
    // التحقق من وصف الحالة للخدمات التي تحتاجها
    if (selectedTypes.has('pharmacy')) {
        const description = document.getElementById('description').value.trim();
        if (!description) {
            e.preventDefault();
            alert('يرجى كتابة وصف للحالة');
            document.getElementById('description').focus();
            return false;
        }
    }
    
    // إذا كان كشف طبي، التحقق من الطبيب وتاريخ الموعد
    if (selectedTypes.has('checkup')) {
        const doctorId = document.getElementById('doctor_id').value;
        const appointmentDate = document.getElementById('appointment_date').value;
        
        if (!doctorId) {
            e.preventDefault();
            alert('يرجى البحث واختيار الطبيب الاستشاري');
            const searchInput = document.getElementById('doctor_search_input');
            if (searchInput) searchInput.focus();
            return false;
        }
        
        if (!appointmentDate) {
            e.preventDefault();
            alert('يرجى اختيار تاريخ الموعد');
            document.getElementById('appointment_date').focus();
            return false;
        }
    }
    
    // التحقق من حقول الطوارئ إذا تم اختيارها
    if (selectedTypes.has('emergency')) {
        const priority = document.getElementById('emergency_priority').value;
        const type = document.getElementById('emergency_type').value;
        const symptoms = document.getElementById('emergency_symptoms').value.trim();
        
        if (!priority || !type || !symptoms) {
            e.preventDefault();
            alert('يرجى ملء جميع حقول الطوارئ');
            return false;
        }
    }
    
    // التحقق من حقول السونار إذا تم اختيارها
    if (selectedTypes.has('radiology') && selectedRadiologyCategory === 'ultrasound') {
        const ultrasoundTypeId = document.getElementById('ultrasound_type_id').value;
        const ultrasoundStaffId = document.getElementById('ultrasound_staff_id').value;
        
        if (!ultrasoundTypeId) {
            e.preventDefault();
            alert('يرجى اختيار نوع فحص السونار');
            document.getElementById('ultrasound_type_id').focus();
            return false;
        }

        if (!ultrasoundStaffId) {
            e.preventDefault();
            alert('يرجى اختيار الموظف المسؤول عن السونار');
            document.getElementById('ultrasound_staff_id').focus();
            return false;
        }
    }
    
    // التحقق من حقول الإيكو إذا تم اختيارها
    if (selectedTypes.has('radiology') && selectedRadiologyCategory === 'echo') {
        const echoTypeId = document.getElementById('echo_type_id').value;
        const echoStaffId = document.getElementById('echo_staff_id').value;
        
        if (!echoTypeId) {
            e.preventDefault();
            alert('يرجى اختيار نوع الإيكو');
            document.getElementById('echo_type_id').focus();
            return false;
        }
        
        if (!echoStaffId) {
            e.preventDefault();
            alert('يرجى اختيار الموظف المسؤول عن الإيكو');
            document.getElementById('echo_staff_id').focus();
            return false;
        }
    }
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('radiology-category-btn')) {
        setRadiologyCategory(e.target.dataset.category);
        updateDetailsForm();
    }
});

// تحديث عداد التحاليل المختارة
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('lab-test-checkbox')) {
        const checkedCount = document.querySelectorAll('.lab-test-checkbox:checked').length;
        const counter = document.getElementById('labSelectedCount');
        if (checkedCount > 0) {
            counter.innerHTML = `<i class="fas fa-check-circle text-success"></i> تم اختيار ${checkedCount} تحليل`;
        } else {
            counter.innerHTML = '';
        }
    }
});

// وظيفة البحث الذكي في أنواع السونار (لا تقترح إلا بعد كتابة أول حرف)
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
    // 1. لا تظهر أي نتائج إلا بعد إدخال أول حرف (>= 1)
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

        // البحث بالاسم أو الرمز
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

    // 2. اختيار الفحص عند الضغط عليه
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

    // 3. مسح البحث
    sonarClearBtn.addEventListener('click', function() {
        sonarSearchInput.value = '';
        sonarHiddenId.value = '';
        sonarSuggestions.style.display = 'none';
        sonarClearBtn.style.display = 'none';
        sonarPriceBox.style.display = 'none';
        sonarSearchInput.focus();
    });

    // 4. إغلاق القائمة عند النقر في الخارج
    document.addEventListener('click', function(e) {
        if (!sonarSearchInput.contains(e.target) && !sonarSuggestions.contains(e.target)) {
            sonarSuggestions.style.display = 'none';
        }
    });

    // 5. استعادة القيمة السابقة عند وجود old('ultrasound_type_id')
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

// وظيفة البحث الذكي في قائمة الأطباء الاستشاريين (Autocomplete / Typeahead)
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
    // 1. إظهار النتائج التنبؤية فور كتابة أول حرف (>= 1)
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

        // مطابقة ذكية بالاسم، الاسم الصافي، التخصص، أو اسم العيادة
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
                ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><i class="fas fa-check-circle me-1"></i>متوفر اليوم</span>'
                : '<span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1"><i class="fas fa-times-circle me-1"></i>غير متاح اليوم</span>';

            html += `
                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 doctor-suggestion-item" 
                        data-id="${d.id}" 
                        data-name="${escapeDoctorHtml(d.name)}" 
                        data-specialization="${escapeDoctorHtml(d.specialization)}" 
                        data-dept="${escapeDoctorHtml(d.department_name)}"
                        data-available="${d.is_available ? '1' : '0'}">
                    <div>
                        <strong class="text-dark d-block"><i class="fas fa-user-md me-1 text-primary"></i>${escapeDoctorHtml(d.name)}</strong>
                        <small class="text-muted"><i class="fas fa-stethoscope me-1"></i>${escapeDoctorHtml(d.specialization)} ${d.department_name ? '• ' + escapeDoctorHtml(d.department_name) : ''}</small>
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

    // 2. اختيار الطبيب عند النقر على المقترح
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
    });

    // 3. مسح اختيار الطبيب
    doctorClearBtn.addEventListener('click', function() {
        doctorSearchInput.value = '';
        doctorHiddenId.value = '';
        doctorSuggestions.style.display = 'none';
        doctorSuggestions.innerHTML = '';
        doctorClearBtn.style.display = 'none';
        if (selectedDoctorBox) selectedDoctorBox.style.display = 'none';
        doctorSearchInput.focus();
    });

    // 4. إغلاق القائمة عند النقر خارجها
    document.addEventListener('click', function(e) {
        if (!doctorSearchInput.contains(e.target) && !doctorSuggestions.contains(e.target)) {
            doctorSuggestions.style.display = 'none';
        }
    });

    // 5. استعادة القيمة في حال وجود old('doctor_id')
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
        }
    }
}

// وظيفة البحث في التحاليل
const labSearchInput = document.getElementById('labSearchInput');
const clearLabSearch = document.getElementById('clearLabSearch');

if (labSearchInput) {
    labSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim().toLowerCase();
        const labItems = document.querySelectorAll('#labTestsContainer .form-check');
        const labCategories = document.querySelectorAll('#labTestsContainer > div');
        
        labItems.forEach(item => {
            const label = item.querySelector('label');
            const text = label ? label.textContent.toLowerCase() : '';
            
            if (text.includes(searchTerm) || searchTerm === '') {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
        
        // إخفاء/إظهار الفئات الفارغة
        labCategories.forEach(category => {
            const visibleItems = category.querySelectorAll('.form-check:not([style*="display: none"])');
            if (visibleItems.length === 0 && searchTerm !== '') {
                category.style.display = 'none';
            } else {
                category.style.display = '';
            }
        });
    });
    
    clearLabSearch.addEventListener('click', function() {
        labSearchInput.value = '';
        labSearchInput.dispatchEvent(new Event('input'));
        labSearchInput.focus();
    });
}

// تشغيل التحقق من حالة خيار الضمان عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    updateInsuranceVisibility();
});

// إذا كان هناك خطأ في الصيغة، عرض النموذج مباشرة
@if($errors->any())
    window.addEventListener('DOMContentLoaded', function() {
        const oldTypes = @json(old('request_type', []));
        const oldRadiologyCategory = @json(old('radiology_category', 'radiology'));
        selectedRadiologyCategory = oldRadiologyCategory;
        if (oldTypes && oldTypes.length > 0) {
            oldTypes.forEach(type => {
                toggleRequestType(type);
            });
        }
        if (oldTypes.includes('radiology')) {
            setRadiologyCategory(oldRadiologyCategory);
            updateRadiologyCategoryInfo();
        }
    });
@endif
</script>

<style>

</style>
@endsection
