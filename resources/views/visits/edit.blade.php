<!-- resources/views/visits/edit.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-hospital-user me-2"></i>
                        تعديل الحجز - الاستعلامات
                    </h2>
                    <p class="text-muted">اختر نوع الخدمة المطلوبة للمريض</p>
                </div>
                <a href="{{ route('visits.index') }}" class="btn btn-outline-secondary">
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
                            <p class="text-muted">{{ optional($visit->patient->user)->name ?? 'غير معروف' }}</p>
                        </div>
                        <div class="col-md-2">
                            <strong>العمر:</strong>
                            <p class="text-muted">{{ optional($visit->patient)->age ?? 'غير محدد' }} سنة</p>
                        </div>
                        <div class="col-md-2">
                            <strong>الجنس:</strong>
                            <p class="text-muted">
                                @if(optional($visit->patient->user)->gender == 'male')
                                    <i class="fas fa-mars text-primary"></i> ذكر
                                @elseif(optional($visit->patient->user)->gender == 'female')
                                    <i class="fas fa-venus text-danger"></i> أنثى
                                @else
                                    غير محدد
                                @endif
                            </p>
                        </div>
                        <div class="col-md-3">
                            <strong>رقم الهاتف:</strong>
                            <p class="text-muted">{{ optional($visit->patient->user)->phone ?? 'غير متوفر' }}</p>
                        </div>
                        <div class="col-md-2">
                            <strong>العنوان:</strong>
                            <p class="text-muted">{{ optional($visit->patient->user)->address ?? 'غير متوفر' }}</p>
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

    <form method="POST" action="{{ route('visits.update', $visit) }}" id="requestForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="patient_id" value="{{ $visit->patient_id }}">
        @php
            $selectedTypes = old('request_type', [$visit->visit_type ?? 'checkup']);
        @endphp
        <input type="hidden" id="initialSelectedTypes" value='{{ json_encode($selectedTypes) }}'>
        <div id="requestTypesContainer">
            <!-- سيتم إضافة حقول request_type[] هنا عبر JavaScript -->
        </div>

        <div class="row g-4 mb-4">
            <!-- بطاقة المختبر -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm request-card" data-type="lab" onclick="toggleRequestType('lab')">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-flask fa-4x text-primary"></i>
                        </div>
                        <h5 class="card-title">تحاليل طبية</h5>
                        <p class="card-text text-muted small">
                            فحوصات مخبرية وتحاليل الدم والبول
                        </p>
                        <div class="departments-list small text-muted" style="display: none;">
                            <span class="text-danger">لا توجد أقسام متاحة</span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center" style="display: none;">
                        <span class="badge bg-primary">محدد</span>
                    </div>
                </div>
            </div>

            <!-- بطاقة الأشعة -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm request-card" data-type="radiology" onclick="toggleRequestType('radiology')">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-x-ray fa-4x text-info"></i>
                        </div>
                        <h5 class="card-title">الأشعة</h5>
                        <p class="card-text text-muted small">
                            أشعة عادية، مقطعية، وتصوير بالرنين
                        </p>
                        <div class="departments-list small text-muted" style="display: none;">
                            <span class="text-danger">لا توجد أقسام متاحة</span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center" style="display: none;">
                        <span class="badge bg-info">محدد</span>
                    </div>
                </div>
            </div>

            <!-- بطاقة الصيدلية -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm request-card" data-type="pharmacy" onclick="toggleRequestType('pharmacy')">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-pills fa-4x text-success"></i>
                        </div>
                        <h5 class="card-title">الصيدلية</h5>
                        <p class="card-text text-muted small">
                            صرف أدوية ومستلزمات طبية
                        </p>
                        <div class="departments-list small text-muted" style="display: none;">
                            <span class="text-danger">لا توجد أقسام متاحة</span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center" style="display: none;">
                        <span class="badge bg-success">محدد</span>
                    </div>
                </div>
            </div>

            <!-- بطاقة مصرف الدم -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm request-card" data-type="blood_bank" onclick="toggleRequestType('blood_bank')">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-tint fa-4x text-danger"></i>
                        </div>
                        <h5 class="card-title">مصرف الدم</h5>
                        <p class="card-text text-muted small">
                            طلب كروس ماتش أو تحضير وحدات دم
                        </p>
                        <div class="departments-list small text-muted" style="display: none;">
                            <span class="text-danger">لا توجد أقسام متاحة</span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center" style="display: none;">
                        <span class="badge bg-danger">محدد</span>
                    </div>
                </div>
            </div>

            <!-- بطاقة كشف طبي -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm request-card" data-type="checkup" onclick="toggleRequestType('checkup')">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-stethoscope fa-4x text-warning"></i>
                        </div>
                        <h5 class="card-title">كشف طبي</h5>
                        <p class="card-text text-muted small">
                            حجز موعد للطبيب والاستشارة الطبية
                        </p>
                        <div class="departments-list small text-muted" style="display: none;">
                            <span class="text-danger">لا توجد أقسام متاحة</span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center" style="display: none;">
                        <span class="badge bg-warning">محدد</span>
                    </div>
                </div>
            </div>

            <!-- بطاقة حجز عملية جراحية -->
            <div class="col-md-6 col-lg-3">
                <a href="{{ route('surgeries.create', ['patient_id' => $visit->patient_id]) }}" class="text-decoration-none">
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

            <!-- بطاقة رقود مبدئي -->
            <div class="col-md-6 col-lg-3">
                <a href="{{ route('bed-reservations.create', ['patient_id' => $visit->patient_id]) }}" class="text-decoration-none">
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

            <!-- بطاقة حجز حاضنة خدج -->
            <div class="col-md-6 col-lg-3">
                <a href="{{ route('incubator-reservations.create', ['patient_id' => $visit->patient_id]) }}" class="text-decoration-none">
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
            </div>
        </div>

        <!-- نموذج التفاصيل -->
        <div id="requestDetails">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card shadow-sm">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-edit me-2"></i>
                                تفاصيل الحجز
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- حقول عامة - تظهر للكشف الطبي والصيدلية فقط -->
                            <div id="generalFields">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="description" class="form-label">
                                            <i class="fas fa-comment-medical me-1"></i>
                                            وصف الحالة / التفاصيل <span class="text-danger general-required">*</span>
                                        </label>
                                        <textarea class="form-control @error('chief_complaint') is-invalid @enderror"
                                                  id="description"
                                                  name="chief_complaint"
                                                  rows="4"
                                                  placeholder="اكتب وصفاً تفصيلياً للحالة أو الخدمة المطلوبة...">{{ old('chief_complaint', $visit->chief_complaint) }}</textarea>
                                        @error('chief_complaint')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="doctor_id" class="form-label">
                                            <i class="fas fa-user-md me-1"></i>
                                            الطبيب <span class="text-danger checkup-required">*</span>
                                        </label>
                                        <select class="form-select @error('doctor_id') is-invalid @enderror"
                                                id="doctor_id"
                                                name="doctor_id">
                                            <option value="">اختر الطبيب</option>
                                            @foreach($doctors as $doctor)
                                                <option value="{{ $doctor->id }}"
                                                        data-department="{{ $doctor->department_id }}"
                                                        {{ old('doctor_id', $visit->doctor_id) == $doctor->id ? 'selected' : '' }}>
                                                    د. {{ optional($doctor->user)->name ?? 'غير معروف' }} - {{ $doctor->specialization }}
                                                    ({{ $doctor->is_available_today ? 'متوفر' : 'غير متوفر' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('doctor_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- حقول خاصة بالكشف الطبي -->
                            <div id="checkupFields">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="department_id" class="form-label">
                                            <i class="fas fa-clinic-medical me-1"></i>
                                            العيادة <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('department_id') is-invalid @enderror"
                                                id="department_id"
                                                name="department_id">
                                            <option value="">اختر العيادة</option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}" {{ old('department_id', $visit->department_id) == $dept->id ? 'selected' : '' }}>
                                                    {{ $dept->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('department_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="visit_date" class="form-label">
                                            <i class="fas fa-calendar me-1"></i>
                                            تاريخ الحجز *
                                        </label>
                                        <input type="date"
                                               class="form-control @error('visit_date') is-invalid @enderror"
                                               id="visit_date"
                                               name="visit_date"
                                               value="{{ old('visit_date', $visit->visit_date ? $visit->visit_date->format('Y-m-d') : '') }}"
                                               min="{{ date('Y-m-d') }}">
                                        @error('visit_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="visit_time" class="form-label">
                                            <i class="fas fa-clock me-1"></i>
                                            وقت الحجز *
                                        </label>
                                        <input type="time"
                                               class="form-control @error('visit_time') is-invalid @enderror"
                                               id="visit_time"
                                               name="visit_time"
                                               value="{{ old('visit_time', $visit->visit_time ? $visit->visit_time->format('H:i') : '') }}">
                                        @error('visit_time')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="visit_type" class="form-label">
                                            <i class="fas fa-tag me-1"></i>
                                            نوع الحجز *
                                        </label>
                                        <select class="form-select @error('visit_type') is-invalid @enderror"
                                                id="visit_type"
                                                name="visit_type">
                                            <option value="checkup" {{ old('visit_type', $visit->visit_type) == 'checkup' ? 'selected' : '' }}>كشف دوري</option>
                                            <option value="followup" {{ old('visit_type', $visit->visit_type) == 'followup' ? 'selected' : '' }}>متابعة</option>
                                            <option value="emergency" {{ old('visit_type', $visit->visit_type) == 'emergency' ? 'selected' : '' }}>طوارئ</option>
                                            <option value="surgery" {{ old('visit_type', $visit->visit_type) == 'surgery' ? 'selected' : '' }}>عملية جراحية</option>
                                            <option value="lab" {{ old('visit_type', $visit->visit_type) == 'lab' ? 'selected' : '' }}>مختبر</option>
                                            <option value="radiology" {{ old('visit_type', $visit->visit_type) == 'radiology' ? 'selected' : '' }}>أشعة</option>
                                        </select>
                                        @error('visit_type')
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
                                            <strong>ملاحظة:</strong> سيتم إنشاء طلب تحاليل عام للمختبر.
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
                                            <strong>ملاحظة:</strong> سيتم إنشاء طلب أشعة عام لقسم الأشعة.
                                            سيقوم موظف الأشعة لاحقاً بتحديد نوع التصوير المطلوب قبل الدفع.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- حقول خاصة بالصيدلية -->
                            <div id="pharmacyFields" style="display: none;">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>ملاحظة:</strong> سيتم إنشاء طلب صرف أدوية للصيدلية.
                                            يرجى التأكد من وجود وصفة طبية صالحة.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>
                                    <span id="submitBtnText">حفظ الحجز</span>
                                </button>
                                <a href="{{ route('visits.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>إلغاء
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let selectedTypes = JSON.parse(document.getElementById('initialSelectedTypes').value || '[]');

function renderSelectedCards() {
    document.querySelectorAll('.request-card').forEach(card => {
        const type = card.dataset.type;
        const footer = card.querySelector('.card-footer');
        if (selectedTypes.includes(type)) {
            card.classList.add('selected');
            if (footer) footer.style.display = 'block';
        } else {
            card.classList.remove('selected');
            if (footer) footer.style.display = 'none';
        }
    });
}

function toggleRequestType(type) {
    const card = document.querySelector(`.request-card[data-type="${type}"]`);

    if (selectedTypes.includes(type)) {
        // إلغاء التحديد
        selectedTypes = selectedTypes.filter(t => t !== type);
        card.classList.remove('selected');
        card.querySelector('.card-footer').style.display = 'none';
        console.log('تم إلغاء اختيار:', type);
    } else {
        // إضافة التحديد
        selectedTypes.push(type);
        card.classList.add('selected');
        card.querySelector('.card-footer').style.display = 'block';
        console.log('تم اختيار:', type);
    }

    // تحديث حقول النموذج
    updateFormFields();
    renderSelectedCards();

    // عرض/إخفاء نموذج التفاصيل
    const details = document.getElementById('requestDetails');
    if (selectedTypes.length > 0) {
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
    const pharmacyFields = document.getElementById('pharmacyFields');
    const generalFields = document.getElementById('generalFields');
    const submitBtnText = document.getElementById('submitBtnText');

    // إخفاء جميع الحقول الخاصة أولاً
    checkupFields.style.display = 'none';
    labFields.style.display = 'none';
    radiologyFields.style.display = 'none';
    pharmacyFields.style.display = 'none';
    generalFields.style.display = 'none';

    // إظهار الحقول حسب الأنواع المحددة
    if (selectedTypes.includes('checkup')) {
        checkupFields.style.display = 'block';
        generalFields.style.display = 'block';
    }

    if (selectedTypes.includes('lab')) {
        labFields.style.display = 'block';
    }

    if (selectedTypes.includes('radiology')) {
        radiologyFields.style.display = 'block';
    }

    if (selectedTypes.includes('pharmacy')) {
        pharmacyFields.style.display = 'block';
        generalFields.style.display = 'block';
    }

    // تحديث نص الزر
    if (selectedTypes.length === 1) {
        const type = selectedTypes[0];
        if (type === 'checkup') {
            submitBtnText.textContent = 'حفظ الحجز';
        } else if (type === 'lab') {
            submitBtnText.textContent = 'طلب تحاليل';
        } else if (type === 'radiology') {
            submitBtnText.textContent = 'طلب أشعة';
        } else if (type === 'pharmacy') {
            submitBtnText.textContent = 'طلب صيدلية';
        } else {
            submitBtnText.textContent = 'حفظ الطلب';
        }
    } else {
        submitBtnText.textContent = `حفظ ${selectedTypes.length} طلبات`;
    }

    // التمرير السلس للنموذج
    setTimeout(() => {
        document.getElementById('requestDetails').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }, 100);
}

// عند اختيار طبيب، ملء العيادة تلقائياً
document.getElementById('doctor_id').addEventListener('change', function() {
    if (selectedTypes.includes('checkup') && this.value) {
        const selectedOption = this.options[this.selectedIndex];
        const departmentId = selectedOption.getAttribute('data-department');

        if (departmentId) {
            document.getElementById('department_id').value = departmentId;
        }
    }
});

// التحقق قبل الإرسال
document.getElementById('requestForm').addEventListener('submit', function(e) {
    if (selectedTypes.length === 0) {
        e.preventDefault();
        alert('يرجى اختيار نوع الخدمة أولاً');
        return false;
    }

    // التحقق من وصف الحالة للخدمات التي تحتاجها
    if (selectedTypes.includes('checkup') || selectedTypes.includes('pharmacy')) {
        const description = document.getElementById('description').value.trim();
        if (!description) {
            e.preventDefault();
            alert('يرجى كتابة وصف للحالة');
            document.getElementById('description').focus();
            return false;
        }
    }

    // إذا كان كشف طبي، التحقق من الطبيب والعيادة
    if (selectedTypes.includes('checkup')) {
        const doctorId = document.getElementById('doctor_id').value;
        const departmentId = document.getElementById('department_id').value;

        if (!doctorId) {
            e.preventDefault();
            alert('يرجى اختيار الطبيب');
            document.getElementById('doctor_id').focus();
            return false;
        }

        if (!departmentId) {
            e.preventDefault();
            alert('يرجى اختيار العيادة');
            document.getElementById('department_id').focus();
            return false;
        }
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
        // محاكاة حدث input
        if ('createEvent' in document) {
            var event = document.createEvent('HTMLEvents');
            event.initEvent('input', false, true);
            labSearchInput.dispatchEvent(event);
        }
        labSearchInput.focus();
    });
}

document.addEventListener('DOMContentLoaded', function() {
    updateFormFields();
    renderSelectedCards();
    updateDetailsForm();
});
</script>

<style>
.request-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.request-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.request-card.selected {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
}

.request-card.selected .card-footer {
    display: block !important;
}

.card-footer {
    display: none;
}

.surgery-card:hover .fa-procedures {
    transform: scale(1.1);
    transition: transform 0.3s ease;
}

.text-pink {
    color: #e91e63 !important;
}

.surgery-card:hover .fa-baby {
    transform: scale(1.1);
    transition: transform 0.3s ease;
}
</style>
@endsection
