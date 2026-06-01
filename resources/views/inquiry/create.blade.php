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
                            <p class="text-muted">{{ optional($patient->user)->name ?? 'غير معروف' }}</p>
                        </div>
                        <div class="col-md-2">
                            <strong>العمر:</strong>
                            <p class="text-muted">{{ $patient->age }} سنة</p>
                        </div>
                        <div class="col-md-2">
                            <strong>الجنس:</strong>
                            <p class="text-muted">
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
                            <p class="text-muted">{{ optional($patient->user)->phone ?? 'غير متوفر' }}</p>
                        </div>
                        <div class="col-md-2">
                            <strong>العنوان:</strong>
                            <p class="text-muted">{{ optional($patient->user)->address ?? 'غير متوفر' }}</p>
                        </div>
                    </div>
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

            @unless($isConsultationReceptionist)
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
            @endunless

            @unless($isConsultationReceptionist)
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
            @endunless

            @unless($isConsultationReceptionist)
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
            @endunless
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
                                    <div class="col-md-6 mb-3">
                                        <label for="doctor_id" class="form-label">
                                            <i class="fas fa-user-md me-1"></i>
                                            الطبيب <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('doctor_id') is-invalid @enderror" 
                                                id="doctor_id" 
                                                name="doctor_id">
                                            <option value="">اختر الطبيب</option>
                                            @foreach($doctors as $doctor)
                                                <option value="{{ $doctor->id }}" 
                                                        data-department="{{ $doctor->department_id }}"
                                                        @selected(old('doctor_id') == $doctor->id)>
                                                    د. {{ optional($doctor->user)->name ?? 'غير معروف' }} - {{ $doctor->specialization }}
                                                    ({{ $doctor->is_available_today ? 'متوفر' : 'غير متوفر' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('doctor_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
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
                                        <div class="card border-primary">
                                            <div class="card-header bg-primary text-white">
                                                <i class="fas fa-baby me-2"></i>تفاصيل السونار
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-12 mb-3">
                                                        <label for="ultrasound_staff_id" class="form-label fw-bold">الموظف المسؤول <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="ultrasound_staff_id" name="ultrasound_staff_id">
                                                            <option value="">اختر الموظف...</option>
                                                            @foreach($ultrasoundStaff as $staff)
                                                                <option value="{{ $staff->id }}" {{ old('ultrasound_staff_id') == $staff->id ? 'selected' : '' }}>
                                                                    د. {{ optional($staff->user)->name ?? 'غير معروف' }} - {{ $staff->specialization }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('ultrasound_staff_id')
                                                            <div class="text-danger mt-1">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="alert alert-info mb-0">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <small>يجب تحديد الموظف المسؤول قبل إنشاء الطلب</small>
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


                            <div class="row mt-3">
                                <div class="col-12" id="autoReferContainer"">
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

<script>
let selectedTypes = new Set();
let selectedRadiologyCategory = @json(old('radiology_category', 'radiology'));

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
            // جعل حقل موظف السونار مطلوب
            document.getElementById('ultrasound_staff_id').required = true;
        } else {
            ultrasoundDetailsContainer.style.display = 'none';
            // إلغاء جعل حقل موظف السونار مطلوب
            document.getElementById('ultrasound_staff_id').required = false;
            // مسح القيم
            document.getElementById('ultrasound_staff_id').value = '';
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
}

function updateRadiologyCategoryInfo() {
    const category = selectedRadiologyCategory || 'radiology';
    setRadiologyCategory(category);
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
            alert('يرجى اختيار الطبيب');
            document.getElementById('doctor_id').focus();
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
        const ultrasoundStaffId = document.getElementById('ultrasound_staff_id').value;
        
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
