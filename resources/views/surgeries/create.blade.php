@extends('layouts.app')

@section('styles')
<!-- نظام أرشفة مجاني 100% - يتصل بالسكانر مباشرة -->
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
/* Room Cards Styles */
.room-card {
    transition: all 0.3s ease;
    border-width: 2px !important;
}

/* page and input-cell backgrounds */
body {
    background: #f5f6fa;
}

#surgeryForm .mb-4 {
    background: #f0f4ff; /* light blue tone for contrast */
    border: 1px solid #ced4da;
    border-radius: 8px;
    padding: 1rem;
}

#surgeryForm .form-control,
#surgeryForm .form-select {
    border: 2px solid #4a8eff;
    border-radius: 6px;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    background: #ffffff;
}

#surgeryForm .form-control:focus,
#surgeryForm .form-select:focus {
    border-color: #0056d6;
    box-shadow: 0 0 0 0.3rem rgba(0,86,214,.25);
    background: #ffffff;
}

/* force all surgery accordion panels to stay closed initially */
#surgeryAccordion .accordion-collapse {
    display: none !important;
}

.room-card[data-available="1"]:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}
.room-card.border-4 {
    border-width: 4px !important;
}
.room-card .card-header {
    transition: all 0.3s ease;
}
.room-card[data-available="0"] {
    cursor: not-allowed !important;
}
.room-card[data-available="1"] {
    cursor: pointer;
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
                    <div class="progress-percent-text" id="progressPercentText" style="color: #667eea; font-size: 1.3rem; font-weight: bold; margin-bottom: 15px;">0% اكتمال</div>
                    <div class="progress-bar-container" style="width: 100%; height: 35px; background: #dee2e6; border-radius: 20px; overflow: hidden; margin-bottom: 10px;">
                        <div class="progress-bar-fill" id="progressBarFill" style="width: 0%; height: 100%; background: linear-gradient(90deg, #667eea 0%, #1262da 100%); border-radius: 20px;"></div>
                    </div>
                    <div class="progress-step-info" style="color: #495057; font-size: 1rem;">
                        الخطوة <strong id="currentStepNum">-</strong> من <strong>3</strong>: <span id="stepName">اضغط على خطوة للبدء</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="{{ route('surgeries.store') }}" method="POST" id="surgeryForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="visit_id" value="{{ request('visit_id') }}">
                
                <!-- التبويبات (اصبحت اكوردين) -->
                <div class="accordion" id="surgeryAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading1">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="false" aria-controls="collapse1">
                                <span class="step-number bg-primary text-white rounded-circle me-2" style="width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;">1</span>
                                بيانات المريض والعملية
                            </button>
                        </h2>
                        <div id="collapse1" class="accordion-collapse collapse" aria-labelledby="heading1" data-bs-parent="#surgeryAccordion">
                            <div class="accordion-body">
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
                                               
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('surgical_operation_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    
                                    <!-- تنبيه السعر حسب الجدول -->
                                    <div id="surgery_fee_info" class="alert alert-info mt-3" style="display: none;">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <span>سعر العملية حسب الجدول: <strong id="surgery_fee_display">0</strong> د.ع</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- حقل السعر المخصص لجميع العمليات -->
                            <div class="col-md-6" id="custom_fee_container">
                                <div class="mb-4">
                                    <label for="custom_surgery_fee" class="form-label fw-bold">
                                        <i class="fas fa-coins me-1 text-warning"></i>
                                        سعر العملية <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           name="custom_surgery_fee" 
                                           id="custom_surgery_fee" 
                                           class="form-control form-control-lg @error('custom_surgery_fee') is-invalid @enderror" 
                                           value="{{ old('custom_surgery_fee') }}"
                                           placeholder="أدخل سعر العملية بالدينار العراقي (مثال: 1,000,000)"
                                           inputmode="numeric"
                                           required>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1 text-info"></i>
                                        يجب إدخال سعر العملية يدوياً - سيتم تنسيق الأرقام تلقائياً
                                    </div>
                                    @error('custom_surgery_fee')
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
                                        <select name="referring_doctor_name" id="referring_doctor_name_select" class="form-select form-select-lg @error('referring_doctor_name') is-invalid @enderror" style="width: 100%; max-width: 500px;" required>
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
                                               list="external_doctors_list" disabled required>
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

                        <!-- نظام الأرشفة المجاني - متاح دائماً -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4">
                                    <div class="mt-3" id="referral_letter_container">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-file-medical-alt me-1 text-info"></i>
                                            ورقة التحويل
                                        </label>
                                        
                                        <!-- إرشادات النظام -->
                                        <div class="alert alert-primary mb-3" id="scan_instructions">
                                            <div class="d-flex align-items-start">
                                                <i class="fas fa-scanner fa-2x me-3 text-primary"></i>
                                                <div>
                                                    <h6 class="alert-heading mb-2">🎯 نظام الأرشفة الذكي (مجاني 100%)</h6>
                                                    <p class="mb-2"><strong>خطوة واحدة فقط:</strong></p>
                                                    <ol class="mb-2 ps-3">
                                                        <li>ضع الورقة في السكانر</li>
                                                        <li>اضغط الزر الأزرق أدناه</li>
                                                        <li>سيتم المسح تلقائياً!</li>
                                                    </ol>
                                                    <small class="text-muted">
                                                        ⚙️ <strong>يتطلب تشغيل:</strong> برنامج الأرشفة المحلي (انظر دليل التثبيت)
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- أزرار المسح -->
                                        <div class="d-grid gap-2 mb-3">
                                            <button type="button" class="btn btn-lg btn-primary" id="scan_btn" onclick="scanFromDevice()">
                                                <i class="fas fa-print me-2"></i>
                                                <i class="fas fa-arrow-down me-2"></i>
                                                مسح الورقة من السكانر الآن
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('referral_letter').click()">
                                                <i class="fas fa-upload me-2"></i>
                                                أو رفع ملف إذا مسحته مسبقاً
                                            </button>
                                        </div>

                                        <!-- رسالة تثبيت البرنامج -->
                                        <div class="alert alert-danger border-2" id="install_prompt" style="display:none;">
                                            <h6 class="mb-3">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                لم يتم الاتصال ببرنامج الأرشفة!
                                            </h6>
                                            <div class="mb-3">
                                                <p class="mb-2"><strong>يرجى التأكد من:</strong></p>
                                                <ol class="mb-0">
                                                    <li>تشغيل ملف <code>تشغيل_برنامج_الارشفة.bat</code></li>
                                                    <li>تثبيت NAPS2 على الكمبيوتر</li>
                                                    <li>توصيل السكانر بالكمبيوتر</li>
                                                </ol>
                                            </div>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <a href="file:///C:/wamp64/www/hospital-system/دليل_التثبيت_الشامل.txt" 
                                                   class="btn btn-sm btn-danger">
                                                    <i class="fas fa-book me-1"></i>
                                                    دليل التثبيت الكامل
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-secondary" 
                                                        onclick="location.reload()">
                                                    <i class="fas fa-redo me-1"></i>
                                                    أعد المحاولة
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-secondary" 
                                                        onclick="document.getElementById('referral_letter').click()">
                                                    <i class="fas fa-upload me-1"></i>
                                                    رفع ملف بدلاً
                                                </button>
                                            </div>
                                        </div>

                                        <!-- حقل الملف المخفي -->
                                        <input type="file" 
                                               name="referral_letter" 
                                               id="referral_letter" 
                                               class="d-none @error('referral_letter') is-invalid @enderror" 
                                               accept="image/*,application/pdf">
                                        
                                        <!-- textarea مخفي لاستقبال البيانات من NAPS2 -->
                                        <textarea id="scan_data_receiver" style="display:none;"></textarea>
                                        
                                        <!-- حالة المسح -->
                                        <div id="scan_status" class="alert" style="display: none;">
                                            <div class="d-flex align-items-center">
                                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                                    <span class="visually-hidden">جاري المسح...</span>
                                                </div>
                                                <span id="scan_status_text">جاري تشغيل السكانر...</span>
                                            </div>
                                        </div>

                                        @error('referral_letter')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <!-- معاينة الوثيقة -->
                                    <div id="referral_letter_preview" class="mt-3" style="display: none;">
                                        <div class="card border-success">
                                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                                <span>
                                                    <i class="fas fa-check-circle me-2"></i>
                                                    تم المسح بنجاح من السكانر
                                                </span>
                                                <button type="button" class="btn btn-sm btn-light" onclick="clearScannedDoc()">
                                                    <i class="fas fa-redo me-1"></i>إعادة المسح
                                                </button>
                                            </div>
                                            <div class="card-body text-center">
                                                <img id="preview_image" src="" class="img-fluid rounded shadow" style="max-height:400px;">
                                                <p id="preview_info" class="mt-2 text-muted small"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
            </div> <!-- /.accordion-collapse step1 -->
        </div> <!-- /.accordion-item step1 -->

                    <!-- الخطوة 2: التفاصيل الطبية والموعد -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading2">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                    <span class="step-number bg-success text-white rounded-circle me-2" style="width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;">2</span>
                    التفاصيل الطبية والموعد
                </button>
            </h2>
            <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="heading2" data-bs-parent="#surgeryAccordion">
                <div class="accordion-body">
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


                </div> <!-- /.accordion-body step2 -->
            </div> <!-- /.accordion-collapse step2 -->
        </div> <!-- /.accordion-item step2 -->

                <div class="accordion-item">
            <h2 class="accordion-header" id="heading3">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                    <span class="step-number bg-warning text-white rounded-circle me-2" style="width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;">3</span>
                    اختيار الغرفة
                </button>
            </h2>
            <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="heading3" data-bs-parent="#surgeryAccordion">
                <div class="accordion-body">
                        <h5 class="mb-4 text-danger">
                            <i class="fas fa-bed me-2"></i>
                            اختيار الغرفة (اختياري)
                        </h5>
                        
                        <!-- مدة الإقامة -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card border-info">
                                    <div class="card-body">
                                        <label for="expected_stay_days" class="form-label fw-bold">
                                            <i class="fas fa-calendar-day me-1 text-info"></i>
                                            مدة الإقامة المتوقعة (أيام)
                                        </label>
                                        <input type="number" name="expected_stay_days" id="expected_stay_days" 
                                               class="form-control form-control-lg @error('expected_stay_days') is-invalid @enderror"
                                               value="{{ old('expected_stay_days', 1) }}" min="1" max="365">
                                        @error('expected_stay_days')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card border-success h-100">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="w-100">
                                            <h6 class="text-success mb-2">
                                                <i class="fas fa-money-bill-wave me-2"></i>
                                                ملخص التكلفة
                                            </h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">الغرفة المختارة:</span>
                                                <span id="selected_room_name" class="fw-bold">لم يتم الاختيار</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <span class="text-muted">حالة الغرفة:</span>
                                                <span id="selected_room_status" class="fw-bold text-success">-</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <span class="text-muted">إجمالي أجرة الغرفة:</span>
                                                <span id="room_total_fee" class="fs-4 fw-bold text-success">0 د.ع</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- دليل الألوان -->
                        <div class="alert alert-light border mb-4">
                            <div class="row text-center">
                                <div class="col">
                                    <span class="badge bg-success me-1" style="width: 12px; height: 12px; display: inline-block;"></span>
                                    <small>متاحة</small>
                                </div>
                                <div class="col">
                                    <span class="badge bg-danger me-1" style="width: 12px; height: 12px; display: inline-block;"></span>
                                    <small>محجوزة</small>
                                </div>
                                <div class="col">
                                    <span class="badge bg-warning me-1" style="width: 12px; height: 12px; display: inline-block;"></span>
                                    <small>صيانة</small>
                                </div>
                                <div class="col">
                                    <span class="badge bg-secondary me-1" style="width: 12px; height: 12px; display: inline-block;"></span>
                                    <small>عادية</small>
                                </div>
                                <div class="col">
                                    <span class="badge bg-warning me-1" style="width: 12px; height: 12px; display: inline-block;"></span>
                                    <small>VIP</small>
                                </div>
                            </div>
                        </div>

                        <!-- حقل الغرفة المخفي -->
                        <input type="hidden" name="room_id" id="room_id" value="{{ old('room_id', '') }}">

                        <!-- لوحة الغرف -->
                        @php
                            $roomsByFloor = $rooms->groupBy('floor');
                        @endphp

                        @forelse($roomsByFloor as $floor => $floorRooms)
                        <div class="card shadow-sm mb-3">
                            <div class="card-header py-2 bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="fas fa-layer-group me-1 text-primary"></i>
                                        {{ $floor ?: 'بدون طابق' }}
                                    </h6>
                                    <span class="badge bg-primary">{{ $floorRooms->count() }} غرفة</span>
                                </div>
                            </div>
                            <div class="card-body py-2">
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($floorRooms as $room)
                                    @php
                                        // flip colors: red = available, green = occupied
                                        $borderHex = match($room->status) {
                                            'available'   => '#198754',
                                            'occupied'    => '#dc3545',
                                            'maintenance' => '#ffc107',
                                            default       => '#6c757d'
                                        };
                                        $typeBg = $room->room_type === 'vip' ? 'bg-warning bg-opacity-10' : '';
                                        $isAvailable = $room->status === 'available';
                                    @endphp
                                    <div class="room-tile room-selectable {{ $typeBg }} {{ !$room->is_active ? 'opacity-50' : '' }}"
                                         data-room-id="{{ $room->id }}"
                                         data-room-fee="{{ $room->daily_fee }}"
                                         data-room-type="{{ $room->room_type }}"
                                         data-room-number="{{ $room->room_number }}"
                                         data-available="{{ $isAvailable ? '1' : '0' }}"
                                         data-status-color="{{ $borderHex }}"
                                         data-bs-toggle="tooltip"
                                         data-bs-html="true"
                                         title="<b>{{ $room->room_number }}</b><br>{{ $room->room_type_name }}<br>{{ number_format($room->daily_fee) }} د.ع/يوم<br>{{ $room->status_name }}"
                                         style="border-color: {{ $borderHex }}; border-width: 3px; {{ !$isAvailable ? 'pointer-events:none; opacity:0.6;' : 'cursor:pointer;' }}">

                                        <div class="room-number" style="color: {{ $borderHex }};">{{ $room->room_number }}</div>

                                        <div class="room-badges">
                                            @if($room->room_type === 'vip')
                                                <span class="badge bg-warning text-dark" style="font-size: 0.6rem;">VIP</span>
                                            @endif
                                        </div>

                                        <div class="room-status">
                                            <span class="status-dot" style="background-color: {{ $borderHex }};"></span>
                                        </div>

                                        @if($isAvailable)
                                        <div class="room-actions" style="display: none;">
                                            <i class="fas fa-check-circle text-success" style="font-size: 0.8rem;"></i>
                                        </div>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                            <h5>لا توجد غرف متاحة</h5>
                            <p class="mb-0">لم يتم العثور على غرف متاحة للحجز</p>
                        </div>
                        @endforelse

                        <!-- زر إلغاء اختيار الغرفة -->
                        <div class="text-center mt-4" id="clear_room_section" style="display: none;">
                            <button type="button" class="btn btn-outline-secondary" id="clear_room_btn">
                                <i class="fas fa-times me-2"></i>
                                إلغاء اختيار الغرفة
                            </button>
                        </div>

                </div> <!-- /.accordion-body step3 -->
            </div> <!-- /.accordion-collapse step3 -->
        </div> <!-- /.accordion-item step3 -->
    </div> <!-- /#surgeryAccordion -->

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
    // تفعيل Select2 على جميع القوائم المنسدلة (ما عدا surgical_operation_id)
    if (typeof $.fn.select2 !== 'undefined') {
        $('#doctor_id, #department_id, #patient_id, #anesthesiologist_id, #anesthesiologist_2_id, #surgery_category').select2({
            theme: 'bootstrap-5',
            dir: 'rtl',
            language: {
                noResults: function() {
                    return 'لا توجد نتائج';
                },
                searching: function() {
                    return 'جاري البحث...';
                }
            },
            placeholder: function() {
                return $(this).data('placeholder') || 'اختر...';
            },
            allowClear: true
        });

        // تهيئة منفصلة للأطباء الداخليين مع البحث دائماً
        $('#referring_doctor_name_select').select2({
            theme: 'bootstrap-5',
            dir: 'rtl',
            language: {
                noResults: function() {
                    return 'لا توجد نتائج';
                },
                searching: function() {
                    return 'جاري البحث...';
                }
            },
            placeholder: 'اختر الطبيب أو ابدأ بالكتابة للبحث',
            allowClear: true,
            minimumResultsForSearch: 0,
            minimumInputLength: 1
        });

        // تفعيل Select2 على قائمة العمليات مع الفلترة حسب الصنف
        let allOperations = [];
        
        // حفظ جميع العمليات عند التحميل الأول
        $('#surgical_operation_id option').each(function() {
            const $option = $(this);
            allOperations.push({
                id: $option.val(),
                text: $option.text(),
                category: $option.data('category'),
                fee: $option.data('fee'),
                element: this
            });
        });
        
        function initOperationSelect2() {
            const selectedCategory = $('#surgery_category').val();
            
            // مسح جميع الخيارات الحالية ما عدا الأول
            $('#surgical_operation_id option:not(:first)').remove();
            
            // إضافة الخيارات المفلترة
            if (selectedCategory) {
                allOperations.forEach(function(op) {
                    if (op.id && op.category === selectedCategory) {
                        const $option = $('<option></option>')
                            .val(op.id)
                            .text(op.text)
                            .attr('data-category', op.category)
                            .attr('data-fee', op.fee);
                        $('#surgical_operation_id').append($option);
                    }
                });
            } else {
                // إضافة جميع العمليات
                allOperations.forEach(function(op) {
                    if (op.id) {
                        const $option = $('<option></option>')
                            .val(op.id)
                            .text(op.text)
                            .attr('data-category', op.category)
                            .attr('data-fee', op.fee);
                        $('#surgical_operation_id').append($option);
                    }
                });
            }
            
            // تفعيل Select2
            $('#surgical_operation_id').select2({
                theme: 'bootstrap-5',
                dir: 'rtl',
                language: {
                    noResults: function() {
                        return selectedCategory ? 'لا توجد عمليات في هذا الصنف' : 'لا توجد نتائج';
                    },
                    searching: function() {
                        return 'جاري البحث...';
                    }
                },
                placeholder: 'اختر نوع العملية',
                allowClear: true
            });
        }
        
        initOperationSelect2();
        
        // عند تغيير صنف العملية
        $('#surgery_category').on('change', function() {
            // إعادة تعيين قيمة العملية المختارة
            $('#surgical_operation_id').val(null);
            
            // إعادة تهيئة Select2 مع الفلترة الجديدة
            $('#surgical_operation_id').select2('destroy');
            initOperationSelect2();
        });
    }

    // تفعيل Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // accordion controls replace tabs
    const accordionButtons = document.querySelectorAll('#surgeryAccordion .accordion-button');
    const collapseItems = document.querySelectorAll('#surgeryAccordion .accordion-collapse');
    const stepItems = document.querySelectorAll('.step-item');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('surgeryForm'); // main form element
    let currentStep = -1;

    const stepNames = ['المريض والعملية', 'الطبيب والموعد', 'الغرفة'];
    const progressPercentText = document.getElementById('progressPercentText');
    const progressBarFill = document.getElementById('progressBarFill');
    const currentStepNum = document.getElementById('currentStepNum');
    const stepName = document.getElementById('stepName');

    function updateUI() {
        // حساب النسبة المئوية
        const percent = currentStep >= 0 ? Math.round(((currentStep + 1) / accordionButtons.length) * 100) : 0;
        
        progressPercentText.textContent = percent + '% اكتمال';
        progressBarFill.style.width = percent + '%';
        currentStepNum.textContent = currentStep >= 0 ? currentStep + 1 : '-';
        stepName.textContent = currentStep >= 0 ? stepNames[currentStep] : 'اضغط على خطوة للبدء';

        // تحديث الأزرار
        prevBtn.style.display = currentStep <= 0 ? 'none' : 'inline-block';
        nextBtn.style.display = (currentStep === accordionButtons.length - 1) ? 'none' : 'inline-block';
        submitBtn.style.display = currentStep === accordionButtons.length - 1 ? 'inline-block' : 'none';

        // تحديث أزرار الاكورديون
        accordionButtons.forEach((btn, index) => {
            if (index === currentStep) {
                btn.classList.remove('collapsed');
                btn.setAttribute('aria-expanded', 'true');
            } else {
                btn.classList.add('collapsed');
                btn.setAttribute('aria-expanded', 'false');
            }
        });

        collapseItems.forEach((collapse, index) => {
            if (index === currentStep) {
                collapse.classList.add('show');
            } else {
                collapse.classList.remove('show');
            }
        });

        // التمرير
        if (currentStep === accordionButtons.length - 1) {
            // final step: show submit button by scrolling down
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        } else {
            // other steps: scroll to top for clarity
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    // استخدام Bootstrap events للأكورديون
    collapseItems.forEach((collapse, idx) => {
        collapse.addEventListener('shown.bs.collapse', function() {
            currentStep = idx;
            updateUI();
        });
        
        collapse.addEventListener('hidden.bs.collapse', function() {
            if (currentStep === idx) {
                currentStep = -1;
                updateUI();
            }
        });
    });

    // previous button
    prevBtn.addEventListener('click', function() {
        if (currentStep > 0) {
            currentStep--;
            updateUI();
        }
    });

    // next button when no step is open yet
    nextBtn.addEventListener('click', function handleFirstClick() {
        if (currentStep < 0) {
            currentStep = 0;
            updateUI();
        }
    }, { capture: true });

    // next button simply advances the step
    nextBtn.addEventListener('click', function() {
        if (currentStep < accordionButtons.length - 1) {
            currentStep++;
            updateUI();
        }
    });

    // form submission validation
    if (form) {
        form.addEventListener('submit', function(e) {
            // إزالة الفواصل من حقل السعر قبل الإرسال
            const feeInput = document.getElementById('custom_surgery_fee');
            if (feeInput && feeInput.value) {
                feeInput.value = feeInput.value.replace(/,/g, '');
            }
            
            console.log('form submit triggered, validity=', form.checkValidity());
            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
                // find first invalid input
                const invalid = form.querySelector(':invalid');
                if (invalid) {
                    // determine step of this input
                    let stepIndex = 0;
                    if (invalid.closest('#collapse1')) stepIndex = 0;
                    else if (invalid.closest('#collapse2')) stepIndex = 1;
                    else if (invalid.closest('#collapse3')) stepIndex = 2;
                    currentStep = stepIndex;
                    updateUI();
                    invalid.focus();
                }
            }
        });
    }

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
    
    // التحكم في حقل السعر المخصص
    const customFeeContainer = document.getElementById('custom_fee_container');
    const customFeeInput = document.getElementById('custom_surgery_fee');
    const surgicalOperationSelect = document.getElementById('surgical_operation_id');
    const surgeryFeeInfo = document.getElementById('surgery_fee_info');
    const surgeryFeeDisplay = document.getElementById('surgery_fee_display');

    function toggleReferringDoctorFields() {
        const letterContainer = document.getElementById('referral_letter_container');
        const referralLetterInput = document.getElementById('referral_letter');
        
        console.log('toggleReferringDoctorFields called');
        console.log('External radio checked:', referringExternalRadio?.checked);
        
        if (referringExternalRadio && referringExternalRadio.checked) {
            console.log('Switching to EXTERNAL doctor');
            // طبيب خارجي - إظهار حقول الطبيب الخارجي وحقل الأرشفة
            if (internalDoctorSelect) {
                internalDoctorSelect.style.display = 'none';
            }
            if (externalDoctorInput) {
                externalDoctorInput.style.display = 'block';
            }
            
            // التحكم في حقول الإدخال
            if (internalDoctorNameSelect) {
                internalDoctorNameSelect.disabled = true;
                internalDoctorNameSelect.required = false;
            }
            if (externalDoctorNameInput) {
                externalDoctorNameInput.disabled = false;
                externalDoctorNameInput.required = true;
            }
            
            // لا نغير عرض حقل الأرشفة هنا (يُعرض دائماً)
            console.log('External doctor selected - referral container remains visible');
        } else {
            console.log('Switching to INTERNAL doctor');
            // طبيب داخلي - إظهار حقول الطبيب الداخلي وإخفاء حقل الأرشفة
            if (internalDoctorSelect) {
                internalDoctorSelect.style.display = 'block';
            }
            if (externalDoctorInput) {
                externalDoctorInput.style.display = 'none';
            }
            
            // التحكم في حقول الإدخال
            if (internalDoctorNameSelect) {
                internalDoctorNameSelect.disabled = false;
                internalDoctorNameSelect.required = true;
            }
            if (externalDoctorNameInput) {
                externalDoctorNameInput.disabled = true;
                externalDoctorNameInput.required = false;
            }
            
            // لا نقوم بإخفاء حقل الأرشفة عندما يكون الطبيب داخلي
            console.log('Internal doctor selected - referral container remains visible');
        }
    }

    if (referringInternalRadio && referringExternalRadio) {
        console.log('Setting up event listeners for referring doctor type');
        referringInternalRadio.addEventListener('change', toggleReferringDoctorFields);
        referringExternalRadio.addEventListener('change', toggleReferringDoctorFields);
        
        // تطبيق الحالة الأولية عند تحميل الصفحة - مع تأخير بسيط
        setTimeout(function() {
            toggleReferringDoctorFields();
        }, 100);
    } else {
        console.error('Radio buttons not found!', {
            internal: referringInternalRadio,
            external: referringExternalRadio
        });
    }

    // if server returned validation errors, jump to the appropriate step
    function focusFirstError() {
        if (!form) return;
        const invalid = form.querySelector(':invalid');
        if (invalid) {
            let stepIndex = 0;
            if (invalid.closest('#collapse2')) stepIndex = 1;
            else if (invalid.closest('#collapse3')) stepIndex = 2;
            currentStep = stepIndex;
            updateUI();
            invalid.focus();
        }
    }

    focusFirstError();

    // ===== نظام الأرشفة المجاني - الاتصال بالسكانر مباشرة =====
    const referralInput = document.getElementById('referral_letter');
    const previewContainer = document.getElementById('referral_letter_preview');
    const previewImage = document.getElementById('preview_image');
    const previewInfo = document.getElementById('preview_info');
    const scanStatus = document.getElementById('scan_status');
    const scanStatusText = document.getElementById('scan_status_text');
    const scanBtn = document.getElementById('scan_btn');
    const installPrompt = document.getElementById('install_prompt');
    const scanInstructions = document.getElementById('scan_instructions');

    // التحقق من وجود NAPS2 محلياً
    let naps2Available = false;

    // محاولة الاتصال بـ NAPS2 Server المحلي
    async function checkNaps2() {
        try {
            const response = await fetch('http://localhost:37426/scan', {
                method: 'OPTIONS',
                mode: 'cors'
            });
            naps2Available = true;
            return true;
        } catch (e) {
            // NAPS2 غير متوفر
            naps2Available = false;
            return false;
        }
    }

    // بدء المسح من السكانر
    // ═════════════════════════════════════════════════════════════════
    //  نظام المسح التلقائي - يتصل مباشرة ببرنامج الأرشفة المجاني
    // ═════════════════════════════════════════════════════════════════
    
    window.scanFromDevice = async function() {
        if (!scanBtn) return;
        
        scanBtn.disabled = true;
        showScanStatus('info', 'جاري تشغيل السكانر...');
        
        try {
            // المسح المباشر من السكانر (الطريقة الموصى بها)
            await scanViaLocalBridge();
            
        } catch (error) {
            console.error('خطأ في المسح:', error);
            handleScanError(error);
        }
    };

    // المسح باستخدام برنامج الأرشفة المحلي (Python Bridge)
    async function scanViaLocalBridge() {
        try {
            showScanStatus('info', 'جاري المسح... ضع الورقة في السكانر');
            
            // الاتصال ببرنامج الأرشفة المحلي
            const response = await fetch('http://localhost:37426/scan', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                
                // رسالة خطأ مخصصة حسب نوع المشكلة
                if (response.status === 404) {
                    throw new Error('برنامج الأرشفة غير مشغل. يرجى تشغيل البرنامج أولاً.');
                } else if (response.status === 408) {
                    throw new Error('انتهت مهلة المسح. تأكد من توصيل السكانر ووضع الورقة.');
                } else {
                    throw new Error(errorData.message || 'فشل المسح من السكانر');
                }
            }
            
            // استلام الصورة الممسوحة
            const blob = await response.blob();
            
            if (blob.size === 0) {
                throw new Error('الصورة الممسوحة فارغة');
            }
            
            // معالجة وعرض الصورة
            handleScannedImage(blob);
            
        } catch (e) {
            // إذا كان خطأ الاتصال، نعرض رسالة تثبيت
            if (e.name === 'TypeError' && e.message.includes('Failed to fetch')) {
                installPrompt.style.display = 'block';
                scanInstructions.style.display = 'none';
                throw new Error('لم يتم الاتصال ببرنامج الأرشفة. تأكد من تشغيله أولاً.');
            }
            throw e;
        }
    }

    // معالجة الصورة الممسوحة
    function handleScannedImage(blobOrFile) {
        // تحويل إلى File إذا كان Blob
        const file = blobOrFile instanceof File ? blobOrFile : 
                     new File([blobOrFile], `scanned-${Date.now()}.jpg`, { type: 'image/jpeg' });
        
        // تعيين الملف في input
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        referralInput.files = dataTransfer.files;
        
        // عرض المعاينة
        const url = URL.createObjectURL(file);
        previewImage.src = url;
        previewInfo.innerHTML = `
            <i class="fas fa-check-circle text-success me-1"></i>
            تم المسح بنجاح - ${(file.size / 1024).toFixed(0)} KB
        `;
        previewContainer.style.display = 'block';
        
        // إخفاء حالة المسح
        showScanStatus('success', 'تم المسح بنجاح!');
        setTimeout(() => {
            scanStatus.style.display = 'none';
            scanBtn.disabled = false;
        }, 2000);
    }

    // معالجة أخطاء المسح
    function handleScanError(error) {
        console.error(error);
        
        if (error.message.includes('NAPS2 غير مثبت')) {
            installPrompt.style.display = 'block';
            scanInstructions.style.display = 'none';
            scanStatus.style.display = 'none';
        } else {
            showScanStatus('danger', `
                فشل المسح: ${error.message}<br>
                <small class="d-block mt-2">
                    <strong>الحلول:</strong><br>
                    • تأكد من تشغيل السكانر<br>
                    • أو اضغط "رفع ملف" واختر الصورة يدوياً
                </small>
            `);
        }
        
        scanBtn.disabled = false;
    }

    // إعداد مستمع اللصق
    function setupPasteListener() {
        const pasteHandler = async (e) => {
            const items = e.clipboardData.items;
            for (let item of items) {
                if (item.type.indexOf('image') !== -1) {
                    const blob = item.getAsFile();
                    handleScannedImage(blob);
                    document.removeEventListener('paste', pasteHandler);
                    break;
                }
            }
        };
        document.addEventListener('paste', pasteHandler);
    }

    // عرض حالة المسح
    function showScanStatus(type, message) {
        scanStatus.className = `alert alert-${type}`;
        scanStatusText.innerHTML = message;
        scanStatus.style.display = 'block';
    }

    // حذف الوثيقة
    window.clearScannedDoc = function() {
        referralInput.value = '';
        previewContainer.style.display = 'none';
        scanStatus.style.display = 'none';
    };

    // معالجة رفع الملف العادي
    if (referralInput) {
        referralInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) {
                previewContainer.style.display = 'none';
                return;
            }
            
            const url = URL.createObjectURL(file);
            previewImage.src = url;
            previewInfo.innerHTML = `
                <i class="fas fa-file-image me-1"></i>
                ${file.name} (${(file.size / 1024).toFixed(0)} KB)
            `;
            previewContainer.style.display = 'block';
        });
    }

    // فحص NAPS2 عند التحميل
    checkNaps2().then(available => {
        if (!available) {
            console.log('NAPS2 Server غير متوفر - سيتم استخدام الطرق البديلة');
        }
    });

    // اختيار الغرفة من اللوحة
    const roomTiles = document.querySelectorAll('.room-selectable');
    const roomIdInput = document.getElementById('room_id');
    const stayDaysInput = document.getElementById('expected_stay_days');
    const roomTotalFee = document.getElementById('room_total_fee');
    const selectedRoomName = document.getElementById('selected_room_name');
    const clearRoomSection = document.getElementById('clear_room_section');
    const clearRoomBtn = document.getElementById('clear_room_btn');
    let selectedRoomFee = 0;

    function selectRoom(tile) {
        // تجاهل الغرف غير المتاحة
        if (tile.dataset.available === '0') {
            return;
        }

        // إزالة التحديد من جميع البطاقات
        document.querySelectorAll('.room-tile').forEach(t => {
            t.classList.remove('border-4', 'border-primary', 'shadow-lg');
            // إخفاء جميع أيقونات التحديد
            const checkIcon = t.querySelector('.room-actions');
            if (checkIcon) {
                checkIcon.style.display = 'none';
            }
        });

        // تحديد البطاقة الحالية
        tile.classList.add('border-4', 'border-primary', 'shadow-lg');
        
        // إظهار أيقونة التحديد
        const checkIcon = tile.querySelector('.room-actions');
        if (checkIcon) {
            checkIcon.style.display = 'block';
        }

        // تحديث القيم
        const roomId = tile.dataset.roomId;
        const roomNumber = tile.dataset.roomNumber;
        const roomType = tile.dataset.roomType;
        selectedRoomFee = parseFloat(tile.dataset.roomFee);

        roomIdInput.value = roomId;
        selectedRoomName.textContent = roomNumber + (roomType === 'vip' ? ' (VIP)' : ' (عادية)');
        // show availability text
        const statusSpan = document.getElementById('selected_room_status');
        if (statusSpan) {
            if(tile.dataset.available === '1') {
                statusSpan.textContent = 'متاحة';
                statusSpan.classList.remove('text-success');
                statusSpan.classList.add('text-danger');
            } else {
                statusSpan.textContent = 'غير متاحة';
                statusSpan.classList.remove('text-danger');
                statusSpan.classList.add('text-success');
            }
        }
        clearRoomSection.style.display = 'block';

        calculateRoomFee();

        // move to last step so submit button appears immediately
        currentStep = accordionButtons.length - 1;
        updateUI();

        // if form is now valid we can auto-submit (user already filled prior steps)
        if (form.checkValidity()) {
            console.log('form valid after room select, auto-submitting');
            form.submit();
        } else {
            // focus submit button so user can click it if validation still fails
            submitBtn.focus();
        }
    }

    function clearRoomSelection() {
        // إزالة التحديد من جميع البطاقات
        document.querySelectorAll('.room-tile').forEach(t => {
            t.classList.remove('border-4', 'border-primary', 'shadow-lg');
            // إخفاء جميع أيقونات التحديد
            const checkIcon = t.querySelector('.room-actions');
            if (checkIcon) {
                checkIcon.style.display = 'none';
            }
        });

        roomIdInput.value = '';
        selectedRoomName.textContent = 'لم يتم الاختيار';
        selectedRoomFee = 0;
        clearRoomSection.style.display = 'none';

        calculateRoomFee();
    }

    function calculateRoomFee() {
        if (!stayDaysInput || !roomTotalFee) return;

        const days = parseInt(stayDaysInput.value) || 0;
        const total = selectedRoomFee * days;

        roomTotalFee.textContent = new Intl.NumberFormat('ar-IQ').format(total) + ' د.ع';
    }

    // إضافة أحداث النقر على بطاقات الغرف
    roomTiles.forEach(tile => {
        tile.addEventListener('click', function() {
            selectRoom(this);
        });
    });

    // زر إلغاء اختيار الغرفة
    if (clearRoomBtn) {
        clearRoomBtn.addEventListener('click', clearRoomSelection);
    }

    // حساب الأجرة عند تغيير عدد الأيام
    if (stayDaysInput) {
        stayDaysInput.addEventListener('input', calculateRoomFee);
    }

    // استعادة الاختيار المحفوظ
    if (roomIdInput && roomIdInput.value) {
        const savedTile = document.querySelector('.room-tile[data-room-id="' + roomIdInput.value + '"]');
        if (savedTile) {
            selectRoom(savedTile);
        }
    }

    calculateRoomFee();

    // تنسيق حقل السعر المخصص بالفواصل
    if (customFeeInput) {
        // دالة لتنسيق الرقم بالفواصل
        function formatNumberWithCommas(value) {
            // إزالة كل الفواصل الموجودة
            let numValue = value.toString().replace(/,/g, '');
            // التحقق من أنه رقم صحيح
            if (!/^\d*$/.test(numValue)) {
                return value;
            }
            // إضافة الفواصل للآلاف
            return numValue.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        // دالة لإزالة الفواصل وإرجاع الرقم الصحيح
        function removeCommas(value) {
            return value.replace(/,/g, '');
        }

        // عند الكتابة في الحقل
        customFeeInput.addEventListener('input', function(e) {
            let cursorPosition = this.selectionStart;
            let oldValue = this.value;
            let oldLength = oldValue.length;
            
            // إزالة الفواصل أولاً
            let cleanValue = removeCommas(this.value);
            
            // السماح بالأرقام فقط
            cleanValue = cleanValue.replace(/\D/g, '');
            
            // تنسيق القيمة بالفواصل
            let formattedValue = formatNumberWithCommas(cleanValue);
            
            // تحديث القيمة
            this.value = formattedValue;
            
            // حساب الموقع الجديد للمؤشر
            let newLength = formattedValue.length;
            let diff = newLength - oldLength;
            
            // ضبط موقع المؤشر
            this.setSelectionRange(cursorPosition + diff, cursorPosition + diff);
        });

        // تنسيق القيمة الأولية إذا كانت موجودة
        if (customFeeInput.value) {
            customFeeInput.value = formatNumberWithCommas(removeCommas(customFeeInput.value));
        }
    }

    updateUI();
});
</script>
@endsection

<style>
/* Room tiles styles */
.room-tile {
    width: 80px;
    height: 70px;
    border-width: 2px;
    border-style: solid;
    border-color: #dee2e6;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    background: white;
    transition: all 0.3s ease;
    margin: 2px;
}

.room-tile:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}


.room-tile[data-available="0"] {
    background: #f8f9fa;
    opacity: 0.6;
}

.room-number {
    font-weight: bold;
    font-size: 1rem;
    text-align: center;
    color: #333;
    margin-bottom: 2px;
}

.room-badges {
    position: absolute;
    top: 2px;
    right: 2px;
}

.room-status {
    position: absolute;
    bottom: 2px;
    left: 2px;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.room-actions {
    position: absolute;
    top: 2px;
    left: 2px;
}

.room-selectable {
    cursor: pointer;
}

.room-selectable[data-available="0"] {
    cursor: not-allowed;
}
</style>
<!--
====================================
إعداد نظام الأرشفة بالسكانر
====================================

هذا النموذج يدعم المسح المباشر من أجهزة السكانر المتصلة بالحاسوب.

الخيارات المتاحة:

1. Dynamsoft Dynamic Web TWAIN (موصى به)
   - يدعم معظم أجهزة السكانر TWAIN/WIA/SANE
   - يعمل على Windows, Mac, Linux
   - يتطلب تثبيت Service على الحاسوب
   - التحميل: https://www.dynamsoft.com/web-twain/downloads
   - مجاني للتطوير (يحتاج ترخيص للإنتاج)

2. Asprise Scanning (بديل مجاني)
   - أخف وزناً
   - يدعم Windows, Mac, Linux
   - التحميل: https://asprise.com/document-scan-upload-image-browser/direct-to-web-browser-scan-upload.html

3. البديل اليدوي (متوفر دائماً)
   - يمكن للمستخدم المسح يدوياً ثم رفع الملف
   - لا يتطلب أي إعدادات إضافية

التثبيت:
1. اختر إحدى المكتبتين أعلاه
2. حمّل وثبت البرنامج المساعد على أجهزة المستخدمين
3. تأكد من تشغيل خدمة المسح (Service)
4. تأكد من توصيل السكانر وتثبيت تعريفاته

ملاحظات:
- النظام يحتوي على fallback تلقائي لرفع الملفات إذا لم يتوفر السكانر
- يمكن تخصيص إعدادات الدقة (Resolution) والألوان
- يدعم المسح متعدد الصفحات
- يحفظ الملفات بصيغة JPG أو PDF حسب الإعدادات
-->