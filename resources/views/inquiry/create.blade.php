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
                            <p class="text-muted">{{ $patient->user->name }}</p>
                        </div>
                        <div class="col-md-2">
                            <strong>العمر:</strong>
                            <p class="text-muted">{{ $patient->age }} سنة</p>
                        </div>
                        <div class="col-md-2">
                            <strong>الجنس:</strong>
                            <p class="text-muted">
                                @if($patient->user->gender == 'male')
                                    <i class="fas fa-mars text-primary"></i> ذكر
                                @elseif($patient->user->gender == 'female')
                                    <i class="fas fa-venus text-danger"></i> أنثى
                                @else
                                    غير محدد
                                @endif
                            </p>
                        </div>
                        <div class="col-md-3">
                            <strong>رقم الهاتف:</strong>
                            <p class="text-muted">{{ $patient->user->phone ?? 'غير متوفر' }}</p>
                        </div>
                        <div class="col-md-2">
                            <strong>العنوان:</strong>
                            <p class="text-muted">{{ $patient->user->address ?? 'غير متوفر' }}</p>
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
            </h4>
        </div>
    </div>

    <form action="{{ route('inquiry.store') }}" method="POST" id="requestForm">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
        <input type="hidden" name="request_type" id="requestType">

        <div class="row g-4 mb-4">
            <!-- بطاقة المختبر -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm request-card" data-type="lab" onclick="selectRequestType('lab')">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-flask fa-4x text-primary"></i>
                        </div>
                        <h5 class="card-title">تحاليل طبية</h5>
                        <p class="card-text text-muted small">
                            فحوصات مخبرية وتحاليل الدم والبول
                        </p>
                        <div class="departments-list small text-muted" style="display: none;">
                            @if($requestTypes['lab']['departments']->count() > 0)
                                <strong>الأقسام المتاحة:</strong>
                                <ul class="list-unstyled mt-2">
                                    @foreach($requestTypes['lab']['departments'] as $dept)
                                        <li><i class="fas fa-check-circle text-success"></i> {{ $dept->name }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-danger">لا توجد أقسام متاحة</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center" style="display: none;">
                        <span class="badge bg-primary">محدد</span>
                    </div>
                </div>
            </div>

            <!-- بطاقة الأشعة -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm request-card" data-type="radiology" onclick="selectRequestType('radiology')">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-x-ray fa-4x text-info"></i>
                        </div>
                        <h5 class="card-title">الأشعة</h5>
                        <p class="card-text text-muted small">
                            أشعة عادية، مقطعية، وتصوير بالرنين
                        </p>
                        <div class="departments-list small text-muted" style="display: none;">
                            @if($requestTypes['radiology']['departments']->count() > 0)
                                <strong>الأقسام المتاحة:</strong>
                                <ul class="list-unstyled mt-2">
                                    @foreach($requestTypes['radiology']['departments'] as $dept)
                                        <li><i class="fas fa-check-circle text-success"></i> {{ $dept->name }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-danger">لا توجد أقسام متاحة</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center" style="display: none;">
                        <span class="badge bg-info">محدد</span>
                    </div>
                </div>
            </div>

            <!-- بطاقة الصيدلية -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm request-card" data-type="pharmacy" onclick="selectRequestType('pharmacy')">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-pills fa-4x text-success"></i>
                        </div>
                        <h5 class="card-title">الصيدلية</h5>
                        <p class="card-text text-muted small">
                            صرف أدوية ومستلزمات طبية
                        </p>
                        <div class="departments-list small text-muted" style="display: none;">
                            @if($requestTypes['pharmacy']['departments']->count() > 0)
                                <strong>الأقسام المتاحة:</strong>
                                <ul class="list-unstyled mt-2">
                                    @foreach($requestTypes['pharmacy']['departments'] as $dept)
                                        <li><i class="fas fa-check-circle text-success"></i> {{ $dept->name }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-danger">لا توجد أقسام متاحة</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center" style="display: none;">
                        <span class="badge bg-success">محدد</span>
                    </div>
                </div>
            </div>

            <!-- بطاقة الكشف الطبي -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm request-card" data-type="checkup" onclick="selectRequestType('checkup')">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-stethoscope fa-4x text-warning"></i>
                        </div>
                        <h5 class="card-title">كشف طبي</h5>
                        <p class="card-text text-muted small">
                            استشارة طبية وكشف في العيادات
                        </p>
                        <div class="departments-list small text-muted" style="display: none;">
                            @if($requestTypes['checkup']['departments']->count() > 0)
                                <strong>العيادات المتاحة:</strong>
                                <ul class="list-unstyled mt-2">
                                    @foreach($requestTypes['checkup']['departments']->take(3) as $dept)
                                        <li><i class="fas fa-check-circle text-success"></i> {{ $dept->name }}</li>
                                    @endforeach
                                    @if($requestTypes['checkup']['departments']->count() > 3)
                                        <li class="text-muted">... و {{ $requestTypes['checkup']['departments']->count() - 3 }} أخرى</li>
                                    @endif
                                </ul>
                            @else
                                <span class="text-danger">لا توجد عيادات متاحة</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center" style="display: none;">
                        <span class="badge bg-warning">محدد</span>
                    </div>
                </div>
            </div>
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
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="description" class="form-label">
                                        <i class="fas fa-comment-medical me-1"></i>
                                        وصف الحالة / التفاصيل <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="4" 
                                              required
                                              placeholder="اكتب وصفاً تفصيلياً للحالة أو الخدمة المطلوبة...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="doctor_id" class="form-label">
                                        <i class="fas fa-user-md me-1"></i>
                                        الطبيب <span class="text-danger checkup-required" style="display: none;">*</span>
                                    </label>
                                    <select class="form-select @error('doctor_id') is-invalid @enderror" 
                                            id="doctor_id" 
                                            name="doctor_id">
                                        <option value="">اختر الطبيب</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}" 
                                                    data-department="{{ $doctor->department_id }}"
                                                    @selected(old('doctor_id') == $doctor->id)>
                                                د. {{ $doctor->user->name }} - {{ $doctor->specialization }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('doctor_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- حقول خاصة بالكشف الطبي -->
                            <div id="checkupFields" style="display: none;">
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
                                            @foreach($requestTypes['checkup']['departments'] as $dept)
                                                <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>
                                                    {{ $dept->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('department_id')
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
    background-color: #f8f9fa;
}

.request-card.selected .card-footer {
    display: block !important;
}

.request-card.selected .departments-list {
    display: block !important;
}
</style>

<script>
let selectedType = null;

function selectRequestType(type) {
    // إلغاء التحديد السابق
    document.querySelectorAll('.request-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // تحديد البطاقة الجديدة
    const card = document.querySelector(`.request-card[data-type="${type}"]`);
    card.classList.add('selected');
    
    // تحديث قيمة النوع
    selectedType = type;
    document.getElementById('requestType').value = type;
    
    // عرض نموذج التفاصيل
    document.getElementById('requestDetails').style.display = 'block';
    
    // إذا كان النوع "كشف طبي" → إظهار حقول الموعد
    const checkupFields = document.getElementById('checkupFields');
    const autoReferContainer = document.getElementById('autoReferContainer');
    const checkupRequired = document.querySelectorAll('.checkup-required');
    const submitBtnText = document.getElementById('submitBtnText');
    const infoList = document.getElementById('infoList');
    const doctorSelect = document.getElementById('doctor_id');
    
    if (type === 'checkup') {
        // إظهار حقول الكشف الطبي
        checkupFields.style.display = 'block';
        autoReferContainer.style.display = 'none';
        checkupRequired.forEach(el => el.style.display = 'inline');
        
        // تغيير نص الزر
        submitBtnText.textContent = 'حجز موعد';
        
        // تغيير الملاحظات
        infoList.innerHTML = `
            <li>سيتم حجز موعد للمريض مع الطبيب المحدد</li>
            <li>يمكن تحديد تاريخ الموعد أو اختيار اليوم</li>
            <li>سيتم إنشاء موعد في نظام المواعيد</li>
        `;
        
        // جعل الطبيب والعيادة مطلوبين
        doctorSelect.setAttribute('required', 'required');
        document.getElementById('department_id').setAttribute('required', 'required');
        
    } else {
        // إخفاء حقول الكشف الطبي
        checkupFields.style.display = 'none';
        autoReferContainer.style.display = 'block';
        checkupRequired.forEach(el => el.style.display = 'none');
        
        // إرجاع نص الزر
        submitBtnText.textContent = 'إنشاء الطلب';
        
        // إرجاع الملاحظات
        infoList.innerHTML = `
            <li>سيتم إنشاء طلب جديد في قسم الاستعلامات</li>
            <li>يمكنك بعد ذلك تحويل المريض للقسم المناسب</li>
            <li>أو اختر "التحويل التلقائي" للانتقال مباشرة</li>
        `;
        
        // جعل الطبيب والعيادة اختياريين
        doctorSelect.removeAttribute('required');
        document.getElementById('department_id').removeAttribute('required');
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
    if (selectedType === 'checkup' && this.value) {
        const selectedOption = this.options[this.selectedIndex];
        const departmentId = selectedOption.getAttribute('data-department');
        
        if (departmentId) {
            document.getElementById('department_id').value = departmentId;
        }
    }
});

// التحقق قبل الإرسال
document.getElementById('requestForm').addEventListener('submit', function(e) {
    if (!selectedType) {
        e.preventDefault();
        alert('يرجى اختيار نوع الخدمة أولاً');
        return false;
    }
    
    const description = document.getElementById('description').value.trim();
    if (!description) {
        e.preventDefault();
        alert('يرجى كتابة وصف للحالة');
        document.getElementById('description').focus();
        return false;
    }
    
    // إذا كان كشف طبي، التحقق من الطبيب والعيادة
    if (selectedType === 'checkup') {
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

// إذا كان هناك خطأ في الصيغة، عرض النموذج مباشرة
@if($errors->any())
    window.addEventListener('DOMContentLoaded', function() {
        const oldType = '{{ old("request_type") }}';
        if (oldType) {
            selectRequestType(oldType);
        }
    });
@endif
</script>
@endsection
