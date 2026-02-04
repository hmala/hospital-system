@extends('layouts.app')

@section('styles')
<style>
.step-wizard {
    margin-bottom: 40px;
    padding: 25px 35px;
    background: white;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.progress-header {
    text-align: center;
    margin-bottom: 20px;
}
.progress-percent-text {
    font-size: 1.1rem;
    font-weight: 600;
    color: #667eea;
    margin-bottom: 15px;
}
.progress-bar-container {
    width: 100%;
    height: 30px;
    background: #e9ecef;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
    position: relative;
    display: block !important;
}
.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    transition: width 0.5s ease;
    position: relative;
    box-shadow: 0 2px 10px rgba(102, 126, 234, 0.5);
    display: block !important;
    min-width: 5%;
}
.progress-bar-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(180deg, rgba(255,255,255,0.3) 0%, transparent 100%);
    border-radius: 15px;
}
.progress-step-info {
    margin-top: 15px;
    text-align: center;
    color: #6c757d;
    font-size: 0.95rem;
}
.tab-content {
    padding: 30px 20px;
    min-height: 400px;
}
.section-divider {
    border-top: 2px solid #e9ecef;
    margin: 30px 0;
    position: relative;
}
.section-divider::before {
    content: attr(data-title);
    position: absolute;
    top: -12px;
    left: 20px;
    background: white;
    padding: 0 10px;
    color: #6c757d;
    font-size: 0.85rem;
    font-weight: 600;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="fas fa-procedures me-2 text-primary"></i>
                حجز عملية جراحية جديدة
            </h2>
            
            <!-- مؤشر الخطوات -->
            <div class="step-wizard" style="background: #f8f9fa; border: 2px solid #dee2e6; padding: 30px;">
                <div class="progress-header">
                    <div class="progress-percent-text" id="progressPercentText" style="color: #667eea; font-size: 1.3rem; font-weight: bold; margin-bottom: 15px;">33% اكتمال</div>
                    <div class="progress-bar-container" style="width: 100%; height: 35px; background: #dee2e6; border-radius: 20px; overflow: hidden; margin-bottom: 10px;">
                        <div class="progress-bar-fill" id="progressBarFill" style="width: 33%; height: 100%; background: linear-gradient(90deg, #667eea 0%, #1262da 100%); border-radius: 20px;"></div>
                    </div>
                    <div class="progress-step-info" style="color: #495057; font-size: 1rem;">
                        الخطوة <strong id="currentStepNum">1</strong> من <strong>3</strong>: <span id="stepName">المريض والعملية</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="{{ route('surgeries.store') }}" method="POST" id="surgeryForm">
                @csrf
                <input type="hidden" name="visit_id" value="{{ request('visit_id') }}">
                
                <!-- التبويبات المخفية -->
                <ul class="nav nav-tabs" id="surgeryTabs" role="tablist" style="display: none;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="step1-tab" data-bs-toggle="tab" data-bs-target="#step1" type="button" role="tab">الخطوة 1</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="step2-tab" data-bs-toggle="tab" data-bs-target="#step2" type="button" role="tab">الخطوة 2</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="step3-tab" data-bs-toggle="tab" data-bs-target="#step3" type="button" role="tab">الخطوة 3</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="surgeryTabContent">
                    <!-- الخطوة 1: بيانات المريض والعملية -->
                    <div class="tab-pane fade show active" id="step1" role="tabpanel">
                        <h5 class="mb-4 text-primary">
                            <i class="fas fa-user-injured me-2"></i>
                            بيانات المريض والعملية
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="patient_id" class="form-label fw-bold">
                                        <i class="fas fa-user me-1 text-primary"></i>
                                        المريض <span class="text-danger">*</span>
                                    </label>
                                    <select name="patient_id" id="patient_id" class="form-select form-select-lg @error('patient_id') is-invalid @enderror" required autofocus>
                                        <option value="">اختر المريض</option>
                                        @foreach($patients as $patient)
                                            <option value="{{ $patient->id }}" {{ (old('patient_id', request('patient_id')) == $patient->id) ? 'selected' : '' }}>
                                                {{ $patient->user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('patient_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="blood_group" class="form-label fw-bold">
                                        <i class="fas fa-tint me-1 text-danger"></i>
                                        مجموعة الدم
                                    </label>
                                    <select name="blood_group" id="blood_group" class="form-select form-select-lg @error('blood_group') is-invalid @enderror">
                                        <option value="">اختر مجموعة الدم</option>
                                        <option value="A+" {{ old('blood_group') == 'A+' ? 'selected' : '' }}>A+</option>
                                        <option value="A-" {{ old('blood_group') == 'A-' ? 'selected' : '' }}>A-</option>
                                        <option value="B+" {{ old('blood_group') == 'B+' ? 'selected' : '' }}>B+</option>
                                        <option value="B-" {{ old('blood_group') == 'B-' ? 'selected' : '' }}>B-</option>
                                        <option value="AB+" {{ old('blood_group') == 'AB+' ? 'selected' : '' }}>AB+</option>
                                        <option value="AB-" {{ old('blood_group') == 'AB-' ? 'selected' : '' }}>AB-</option>
                                        <option value="O+" {{ old('blood_group') == 'O+' ? 'selected' : '' }}>O+</option>
                                        <option value="O-" {{ old('blood_group') == 'O-' ? 'selected' : '' }}>O-</option>
                                    </select>
                                    @error('blood_group')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="surgery_category" class="form-label fw-bold">
                                        <i class="fas fa-folder me-1 text-warning"></i>
                                        صنف العملية <span class="text-danger">*</span>
                                    </label>
                                    <select name="surgery_category" id="surgery_category" 
                                            class="form-select form-select-lg @error('surgery_category') is-invalid @enderror" 
                                            required>
                                        <option value="">-- اختر صنف العملية --</option>
                                        @foreach($surgicalOperations->unique('category')->pluck('category') as $category)
                                            <option value="{{ $category }}" {{ old('surgery_category') == $category ? 'selected' : '' }}>
                                                {{ $category }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('surgery_category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="surgical_operation_id" class="form-label fw-bold">
                                        <i class="fas fa-stethoscope me-1 text-info"></i>
                                        نوع العملية <span class="text-danger">*</span>
                                    </label>
                                    <select name="surgical_operation_id" id="surgical_operation_id" 
                                            class="form-select form-select-lg @error('surgical_operation_id') is-invalid @enderror" 
                                            required>
                                        <option value="">-- اختر نوع العملية --</option>
                                        @foreach($surgicalOperations as $operation)
                                            <option value="{{ $operation->id }}" 
                                                    data-category="{{ $operation->category }}"
                                                    data-fee="{{ $operation->fee }}"
                                                    {{ old('surgical_operation_id') == $operation->id ? 'selected' : '' }}>
                                                {{ $operation->name }}
                                                @if($operation->fee > 0)
                                                    ({{ number_format($operation->fee, 0) }} د.ع)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('surgical_operation_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-user-doctor me-1 text-info"></i>
                                        الطبيب المرسل <span class="text-danger">*</span>
                                    </label>
                                    <div class="mb-3">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="referring_doctor_type" id="referring_internal" value="internal" {{ old('referring_doctor_type', 'internal') == 'internal' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="referring_internal">
                                                <i class="fas fa-hospital me-1 text-primary"></i>
                                                من المستشفى
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="referring_doctor_type" id="referring_external" value="external" {{ old('referring_doctor_type') == 'external' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="referring_external">
                                                <i class="fas fa-user-plus me-1 text-warning"></i>
                                                طبيب خارجي
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- قائمة الأطباء الداخليين (نحفظ الاسم مباشرة) -->
                                    <div id="internal_doctor_select">
                                        <select name="referring_doctor_name" id="referring_doctor_name_select" class="form-select form-select-lg @error('referring_doctor_name') is-invalid @enderror">
                                            <option value="">اختر الطبيب</option>
                                            @foreach($doctors as $doctor)
                                                <option value="{{ $doctor->user->name }}" {{ (old('referring_doctor_name', request('referring_doctor_name')) == $doctor->user->name) ? 'selected' : '' }}>
                                                    د. {{ $doctor->user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('referring_doctor_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <!-- حقل إدخال الطبيب الخارجي -->
                                    <div id="external_doctor_input" style="display: none;">
                                        <input type="text" name="referring_doctor_name" id="external_referring_doctor_name" 
                                               class="form-control form-control-lg @error('referring_doctor_name') is-invalid @enderror" 
                                               value="{{ old('referring_doctor_name') }}"
                                               placeholder="أدخل اسم الطبيب المرسل"
                                               list="external_doctors_list" disabled>
                                        <datalist id="external_doctors_list">
                                            @php
                                                $externalDoctors = \App\Models\Surgery::whereNotNull('referring_doctor_name')
                                                    ->distinct()
                                                    ->pluck('referring_doctor_name')
                                                    ->filter()
                                                    ->unique()
                                                    ->sort();
                                            @endphp
                                            @foreach($externalDoctors as $externalDoctor)
                                                <option value="{{ $externalDoctor }}">
                                            @endforeach
                                        </datalist>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            سيتم حفظ الاسم وعرضه كاقتراح في المرات القادمة
                                        </small>
                                        @error('referring_doctor_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- الخطوة 2: التفاصيل الطبية والموعد -->
                    <div class="tab-pane fade" id="step2" role="tabpanel">
                        <h5 class="mb-4 text-success">
                            <i class="fas fa-user-md me-2"></i>
                            التفاصيل الطبية والموعد
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="doctor_id" class="form-label fw-bold">
                                        <i class="fas fa-user-md me-1 text-primary"></i>
                                        الطبيب الجراح <span class="text-danger">*</span>
                                    </label>
                                    <select name="doctor_id" id="doctor_id" class="form-select form-select-lg @error('doctor_id') is-invalid @enderror" required>
                                        <option value="">اختر الطبيب</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}" {{ (old('doctor_id', request('doctor_id')) == $doctor->id) ? 'selected' : '' }}>
                                                د. {{ $doctor->user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('doctor_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="department_id" class="form-label fw-bold">
                                        <i class="fas fa-hospital me-1 text-info"></i>
                                        القسم <span class="text-danger">*</span>
                                    </label>
                                    <select name="department_id" id="department_id" class="form-select form-select-lg @error('department_id') is-invalid @enderror" required>
                                        <option value="">اختر القسم</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" {{ (old('department_id', request('department_id')) == $department->id) ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="scheduled_date" class="form-label fw-bold">
                                        <i class="fas fa-calendar me-1 text-warning"></i>
                                        تاريخ العملية <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" name="scheduled_date" id="scheduled_date" 
                                           class="form-control form-control-lg @error('scheduled_date') is-invalid @enderror" 
                                           value="{{ old('scheduled_date') }}" required>
                                    @error('scheduled_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="scheduled_time" class="form-label fw-bold">
                                        <i class="fas fa-clock me-1 text-warning"></i>
                                        وقت العملية <span class="text-danger">*</span>
                                    </label>
                                    <input type="time" name="scheduled_time" id="scheduled_time" 
                                           class="form-control form-control-lg @error('scheduled_time') is-invalid @enderror" 
                                           value="{{ old('scheduled_time', '09:00') }}" required>
                                    @error('scheduled_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        @if(Auth::user()->hasRole(['admin', 'surgery_staff', 'doctor']))
                        <div class="section-divider" data-title="أطباء التخدير (اختياري)"></div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="anesthesiologist_id" class="form-label fw-bold">
                                        <i class="fas fa-syringe me-1 text-secondary"></i>
                                        الطبيب المخدر الأول
                                    </label>
                                    <select name="anesthesiologist_id" id="anesthesiologist_id" class="form-select @error('anesthesiologist_id') is-invalid @enderror">
                                        <option value="">اختر الطبيب المخدر</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}" {{ (old('anesthesiologist_id') == $doctor->id) ? 'selected' : '' }}>
                                                د. {{ $doctor->user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('anesthesiologist_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="anesthesiologist_2_id" class="form-label fw-bold">
                                        <i class="fas fa-syringe me-1 text-secondary"></i>
                                        الطبيب المخدر الثاني
                                    </label>
                                    <select name="anesthesiologist_2_id" id="anesthesiologist_2_id" class="form-select @error('anesthesiologist_2_id') is-invalid @enderror">
                                        <option value="">اختر الطبيب المخدر الثاني</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}" {{ (old('anesthesiologist_2_id') == $doctor->id) ? 'selected' : '' }}>
                                                د. {{ $doctor->user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('anesthesiologist_2_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- الخطوة 3: الفحوصات والملاحظات -->
                    <div class="tab-pane fade" id="step3" role="tabpanel">
                        <h5 class="mb-4 text-info">
                            <i class="fas fa-flask me-2"></i>
                            الفحوصات المطلوبة والملاحظات
                        </h5>
                        
                        <!-- الفحوصات المخبرية والأشعة -->
                        <div class="card border-info mb-4">
                            <div class="card-header bg-info bg-opacity-10">
                                <h6 class="mb-0 text-info">
                                    <i class="fas fa-vial me-2"></i>
                                    الفحوصات المطلوبة قبل العملية (اختياري)
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-flask me-2"></i>
                                            التحاليل المخبرية
                                        </h6>
                                        <div class="border rounded p-3 bg-light" style="max-height: 250px; overflow-y: auto;">
                                            @foreach($labTests as $labTest)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="lab_tests[]" 
                                                           value="{{ $labTest->id }}" 
                                                           id="lab_test_{{ $labTest->id }}"
                                                           {{ in_array($labTest->id, old('lab_tests', [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="lab_test_{{ $labTest->id }}">
                                                        {{ $labTest->name }}
                                                        @if($labTest->category)
                                                            <small class="text-muted">({{ $labTest->category }})</small>
                                                        @endif
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-success mb-3">
                                            <i class="fas fa-x-ray me-2"></i>
                                            الأشعة والتصوير
                                        </h6>
                                        <div class="border rounded p-3 bg-light" style="max-height: 250px; overflow-y: auto;">
                                            @foreach($radiologyTypes as $radiologyType)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="radiology_tests[]" 
                                                           value="{{ $radiologyType->id }}" 
                                                           id="radiology_test_{{ $radiologyType->id }}"
                                                           {{ in_array($radiologyType->id, old('radiology_tests', [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="radiology_test_{{ $radiologyType->id }}">
                                                        {{ $radiologyType->name }}
                                                        @if($radiologyType->code)
                                                            <small class="text-muted">({{ $radiologyType->code }})</small>
                                                        @endif
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- الملاحظات -->
                        <div class="mb-4">
                            <label for="notes" class="form-label fw-bold">
                                <i class="fas fa-sticky-note me-1 text-warning"></i>
                                ملاحظات إضافية
                            </label>
                            <div class="alert alert-info mb-2">
                                <i class="fas fa-info-circle me-2"></i>
                                يمكنك إضافة أي ملاحظات أو تعليمات خاصة بالعملية الجراحية
                            </div>
                            <textarea name="notes" id="notes" class="form-control" rows="5" placeholder="أدخل أي ملاحظات أو تعليمات خاصة...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- أزرار التنقل -->
                <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                    <button type="button" class="btn btn-outline-secondary btn-lg" id="prevBtn" style="display: none;">
                        <i class="fas fa-arrow-right me-2"></i>
                        السابق
                    </button>
                    <div></div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('surgeries.index') }}" class="btn btn-outline-danger btn-lg">
                            <i class="fas fa-times me-2"></i>
                            إلغاء
                        </a>
                        <button type="button" class="btn btn-primary btn-lg" id="nextBtn">
                            التالي
                            <i class="fas fa-arrow-left ms-2"></i>
                        </button>
                        <button type="submit" class="btn btn-success btn-lg" id="submitBtn" style="display: none;">
                            <i class="fas fa-save me-2"></i>
                            حجز العملية
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('#surgeryTabs .nav-link');
    const tabPanes = document.querySelectorAll('.tab-pane');
    const stepItems = document.querySelectorAll('.step-item');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    let currentStep = 0;

    const stepNames = ['المريض والعملية', 'الطبيب والموعد', 'الفحوصات'];
    const progressPercentText = document.getElementById('progressPercentText');
    const progressBarFill = document.getElementById('progressBarFill');
    const currentStepNum = document.getElementById('currentStepNum');
    const stepName = document.getElementById('stepName');

    function updateUI() {
        // حساب النسبة المئوية
        const percent = Math.round(((currentStep + 1) / tabs.length) * 100);
        
        progressPercentText.textContent = percent + '% اكتمال';
        progressBarFill.style.width = percent + '%';
        currentStepNum.textContent = currentStep + 1;
        stepName.textContent = stepNames[currentStep];

        // تحديث الأزرار
        prevBtn.style.display = currentStep === 0 ? 'none' : 'inline-block';
        nextBtn.style.display = currentStep === tabs.length - 1 ? 'none' : 'inline-block';
        submitBtn.style.display = currentStep === tabs.length - 1 ? 'inline-block' : 'none';

        // تحديث التبويبات
        tabs.forEach((tab, index) => {
            if (index === currentStep) {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });

        tabPanes.forEach((pane, index) => {
            if (index === currentStep) {
                pane.classList.add('show', 'active');
            } else {
                pane.classList.remove('show', 'active');
            }
        });

        // التمرير إلى أعلى الصفحة
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    nextBtn.addEventListener('click', function() {
        if (currentStep < tabs.length - 1) {
            currentStep++;
            updateUI();
        }
    });

    prevBtn.addEventListener('click', function() {
        if (currentStep > 0) {
            currentStep--;
            updateUI();
        }
    });

    // التحكم في حقول التحويل الخارجي
    const internalRadio = document.getElementById('referral_internal');
    const externalRadio = document.getElementById('referral_external');
    const externalFields = document.getElementById('external_fields');

    function toggleExternalFields() {
        if (externalRadio && externalRadio.checked) {
            externalFields.style.display = 'block';
        } else {
            externalFields.style.display = 'none';
        }
    }

    if (internalRadio && externalRadio) {
        internalRadio.addEventListener('change', toggleExternalFields);
        externalRadio.addEventListener('change', toggleExternalFields);
        toggleExternalFields();
    }

    // التحكم في حقول الطبيب المرسل
    const referringInternalRadio = document.getElementById('referring_internal');
    const referringExternalRadio = document.getElementById('referring_external');
    const internalDoctorSelect = document.getElementById('internal_doctor_select');
    const externalDoctorInput = document.getElementById('external_doctor_input');
    const internalDoctorNameSelect = document.getElementById('referring_doctor_name_select');
    const externalDoctorNameInput = document.getElementById('external_referring_doctor_name');

    function toggleReferringDoctorFields() {
        if (referringExternalRadio && referringExternalRadio.checked) {
            internalDoctorSelect.style.display = 'none';
            externalDoctorInput.style.display = 'block';
            if (internalDoctorNameSelect) {
                internalDoctorNameSelect.disabled = true;
            }
            if (externalDoctorNameInput) {
                externalDoctorNameInput.disabled = false;
            }
        } else {
            internalDoctorSelect.style.display = 'block';
            externalDoctorInput.style.display = 'none';
            if (internalDoctorNameSelect) {
                internalDoctorNameSelect.disabled = false;
            }
            if (externalDoctorNameInput) {
                externalDoctorNameInput.disabled = true;
            }
        }
    }

    if (referringInternalRadio && referringExternalRadio) {
        referringInternalRadio.addEventListener('change', toggleReferringDoctorFields);
        referringExternalRadio.addEventListener('change', toggleReferringDoctorFields);
        toggleReferringDoctorFields();
    }

    // تصفية العمليات حسب الصنف المختار
    const categorySelect = document.getElementById('surgery_category');
    const operationSelect = document.getElementById('surgical_operation_id');

    function filterOperations() {
        if (!categorySelect || !operationSelect) return;

        const selectedCategory = categorySelect.value;
        const allOptions = operationSelect.querySelectorAll('option');

        allOptions.forEach(option => {
            if (!option.value) {
                option.style.display = 'block';
                return;
            }

            const optionCategory = option.dataset.category;
            if (!selectedCategory || optionCategory === selectedCategory) {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        });

        // إعادة تعيين الاختيار إذا كان غير متوافق
        const selectedOption = operationSelect.options[operationSelect.selectedIndex];
        if (selectedOption && selectedOption.dataset.category !== selectedCategory && selectedCategory) {
            operationSelect.value = '';
        }
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', filterOperations);
        filterOperations(); // تطبيق التصفية عند التحميل
    }

    updateUI();
});
</script>
@endsection
