@extends('layouts.app')

@section('content')
<style>
/* تحسينات checkboxes التحاليل والأشعة */
.hover-zoom {
    transition: transform 0.3s ease;
}

.hover-zoom:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

.modal-dialog-scrollable .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

.hover-lab-item {
    border-left: 3px solid #dee2e6 !important;
    background-color: #ffffff;
}

.hover-lab-item:hover {
    background-color: #f0f9ff !important;
    border-left-color: #3b82f6 !important;
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
}

.hover-lab-item:has(input:checked) {
    background-color: #dbeafe !important;
    border-left-color: #2563eb !important;
    border-left-width: 4px !important;
}

.hover-radiology-item {
    border-left: 3px solid #bae6fd !important;
    background-color: #ffffff;
}

.hover-radiology-item:hover {
    background-color: #f0fdfa !important;
    border-left-color: #14b8a6 !important;
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(20, 184, 166, 0.15);
}

.hover-radiology-item:has(input:checked) {
    background-color: #ccfbf1 !important;
    border-left-color: #0d9488 !important;
    border-left-width: 4px !important;
}

.list-group-item {
    border: 1px solid #e5e7eb;
    margin-bottom: 2px;
}

.hover-highlight {
    background-color: #ffffff;
    border: 1px solid #dee2e6 !important;
}

.hover-highlight:hover {
    background-color: #f0f9ff !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2) !important;
    transform: translateY(-1px);
}

.hover-highlight:has(input:checked) {
    background-color: #dbeafe !important;
    border-color: #2563eb !important;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3) !important;
}

.form-check-input {
    border: 2px solid #cbd5e1;
    transition: all 0.2s ease;
}

.form-check-input:checked {
    background-color: #2563eb;
    border-color: #2563eb;
}

.form-check-input:hover {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* تحسينات أزرار اختيار عدد المرات */
.frequency-selector input[type="radio"]:checked + label {
    background: linear-gradient(135deg, #0d6efd, #1976d2) !important;
    color: white !important;
    border-color: #0d6efd !important;
    box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3) !important;
    transform: translateY(-2px);
}

.frequency-selector label:hover {
    border-color: #0d6efd !important;
    background-color: rgba(13, 110, 253, 0.05) !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(13, 110, 253, 0.2) !important;
}

/* تحسينات تصميم مربع البحث */
.diagnosis-input {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    min-height: 38px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.diagnosis-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    background-color: #fff;
}

.input-group.focused .input-group-text {
    background: linear-gradient(135deg, #0d6efd, #1976d2) !important;
    transform: scale(1.05);
    transition: all 0.3s ease;
    animation: iconPulse 1s infinite;
}

@keyframes iconPulse {
    0%, 100% { transform: scale(1.05); }
    50% { transform: scale(1.1); }
}

.input-group .input-group-text {
    border: 2px solid #e9ecef;
    border-right: none;
    background: linear-gradient(135deg, #0d6efd, #1976d2);
    color: white;
    transition: all 0.3s ease;
}

.input-group .diagnosis-input {
    border-left: none;
}

.input-group .diagnosis-input:focus {
    border-left: none;
    z-index: 3;
}

/* تحسين مظهر datalist */
datalist {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
}

datalist option {
    padding: 8px 12px;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s ease;
}

datalist option:hover {
    background-color: #f8f9fa;
}

/* تحسين النص المساعد */
.text-muted small {
    font-size: 0.75rem;
    color: #6c757d !important;
    display: flex;
    align-items: center;
}

/* تأثيرات الحركة */
@keyframes searchPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

@keyframes bounceIn {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.05); }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); opacity: 1; }
}

.diagnosis-input.animate__pulse {
    animation: searchPulse 0.3s ease-in-out;
}

.diagnosis-input.animate__bounceIn {
    animation: bounceIn 0.5s ease-out;
}

/* تحسين عرض النتائج */
#icd10-list option {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-size: 0.9rem;
}

#icd10-list option[value="other"] {
    color: #fd7e14;
    font-weight: 600;
}

/* تحسينات التصميم العام */
.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.btn {
    border-radius: 6px;
    font-weight: 600;
    padding: 0.5rem 1.5rem;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* نظام الـ Accordion الجديد */
.accordion-container {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.accordion-item {
    border: none;
    border-bottom: 1px solid #e9ecef;
}

.accordion-item:last-child {
    border-bottom: none;
}

.accordion-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: none;
    padding: 0;
}

.accordion-button {
    background: transparent !important;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    padding: 1.5rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    color: #495057;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.accordion-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(13, 110, 253, 0.1), transparent);
    transition: left 0.5s;
}

.accordion-button:hover::before {
    left: 100%;
}

.accordion-button:hover {
    background-color: rgba(13, 110, 253, 0.05) !important;
    color: #0d6efd;
}

.accordion-button:not(.collapsed) {
    background: linear-gradient(135deg, #0d6efd 0%, #1976d2 100%) !important;
    color: white !important;
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
}

.accordion-button:not(.collapsed)::after {
    filter: brightness(0) invert(1);
}

.accordion-button:focus {
    box-shadow: none !important;
    border: none !important;
}

.accordion-body {
    background: white;
    padding: 2rem;
    border-top: 1px solid #e9ecef;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* أيقونات الأقسام */
.section-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-left: 1rem;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.accordion-button:not(.collapsed) .section-icon {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    transform: scale(1.1);
}

.accordion-button.collapsed .section-icon {
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}

/* شارة الإكمال */
.completion-badge {
    margin-right: 1rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
}

/* تحسين التنبيهات داخل الـ accordion */
.accordion-body .alert {
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* تحسين الجداول داخل الـ accordion */
.accordion-body .table {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

/* تحسين البطاقات داخل الـ accordion */
.accordion-body .card {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

/* تأثيرات الاستجابة */
@media (max-width: 768px) {
    .accordion-button {
        padding: 1rem 1.5rem;
        font-size: 1rem;
    }

    .accordion-body {
        padding: 1.5rem;
    }

    .section-icon {
        width: 35px;
        height: 35px;
        font-size: 1rem;
        margin-left: 0.5rem;
    }
}

/* أنماط المجموعات المنسدلة */
.main-group-header {
    transition: all 0.3s ease;
}

.main-group-header:hover {
    opacity: 0.9;
}

.toggle-icon {
    transition: transform 0.3s ease;
}

.collapsed .toggle-icon {
    transform: rotate(0deg);
}

.main-group-header:not(.collapsed) .toggle-icon {
    transform: rotate(180deg);
}
</style>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-user-md me-2"></i>
                    فحص المريض
                </h2>
                <div class="d-flex gap-2">
                    @if($visit->status == 'in_progress')
                        <form action="{{ route('doctor.visits.update', $visit) }}" method="POST" class="d-inline" id="completeVisitForm">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="btn btn-success" onclick="return confirm('هل أنت متأكد من إنهاء هذه الزيارة؟ سيتم تغيير حالتها إلى مكتملة وستظهر في التاريخ.')">
                                <i class="fas fa-check-circle me-1"></i>
                                إنهاء الزيارة
                            </button>
                        </form>
                    @endif
                    @if($visit->status == 'completed' && !$visit->needs_surgery)
                        <a href="{{ route('doctor.visits.show-surgery-form', $visit) }}" class="btn btn-warning">
                            <i class="fas fa-procedures me-1"></i>
                            تحويل لحجز عملية
                        </a>
                    @endif
                    @if($visit->needs_surgery && !$visit->surgery)
                        <span class="badge bg-warning text-dark p-2">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            في انتظار حجز العملية من الاستعلامات
                        </span>
                    @endif
                    <a href="{{ route('doctor.visits.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- شريط التقدم -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">
                            <i class="fas fa-tasks text-primary me-2"></i>
                            حالة إكمال الفحص
                        </h6>
                        <small class="text-muted">
                            @php
                                $examinationComplete = !empty($visit->vital_signs);
                                $diagnosisComplete = $visit->diagnosis && isset($visit->diagnosis['code']) && !empty($visit->diagnosis['code']);
                                $treatmentComplete = $visit->prescribedMedications->count() > 0 || !empty($visit->treatment_plan);
                                $requestsComplete = $visit->requests->count() > 0;
                                $historyComplete = true; // التاريخ الطبي دائماً متاح

                                // حساب نسبة الإكمال بناءً على العناصر الأساسية فقط
                                $totalItems = 3; // العلامات الحيوية، التشخيص، العلاج
                                $completedItems = 0;
                                
                                if($examinationComplete) $completedItems++;
                                if($diagnosisComplete) $completedItems++;
                                if($treatmentComplete) $completedItems++;
                                
                                $progress = round(($completedItems / $totalItems) * 100);
                            @endphp
                            {{ $progress }}% مكتمل
                        </small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%"
                             aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-success">
                            <i class="fas fa-check-circle me-1"></i>
                            الفحص السريري: {{ $examinationComplete ? 'مكتمل' : 'غير مكتمل' }}
                        </small>
                        <small class="text-info">
                            <i class="fas fa-check-circle me-1"></i>
                            التشخيص: {{ $diagnosisComplete ? 'مكتمل' : 'غير مكتمل' }}
                        </small>
                        <small class="text-warning">
                            <i class="fas fa-check-circle me-1"></i>
                            الطلبات الطبية: {{ $visit->requests->count() > 0 ? $visit->requests->count() . ' طلب' : 'لا توجد' }} (اختياري)
                        </small>
                        <small class="text-primary">
                            <i class="fas fa-check-circle me-1"></i>
                            خطة العلاج: {{ $treatmentComplete ? 'مكتمل' : 'غير مكتمل' }}
                        </small>
                        <small class="text-secondary">
                            <i class="fas fa-check-circle me-1"></i>
                            التاريخ الطبي: مكتمل دائماً
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- معلومات المريض -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        معلومات المريض
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>الاسم:</strong> {{ $visit->patient->user->name }}</p>
                            <p><strong>العمر:</strong> {{ $visit->patient->age }} سنة</p>
                            <p><strong>الجنس:</strong> {{ $visit->patient->gender == 'male' ? 'ذكر' : 'أنثى' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>رقم الهاتف:</strong> {{ $visit->patient->phone }}</p>
                            <p><strong>العنوان:</strong> {{ $visit->patient->address }}</p>
                            <p><strong>تاريخ الميلاد:</strong> {{ $visit->patient->date_of_birth ? $visit->patient->date_of_birth->format('Y-m-d') : 'غير محدد' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-check me-2"></i>
                        معلومات الزيارة
                    </h5>
                </div>
                <div class="card-body">
                    <p><strong>التاريخ:</strong> {{ $visit->visit_date ? $visit->visit_date->format('Y-m-d') : 'غير محدد' }}</p>
                    <p><strong>الوقت:</strong> {{ $visit->visit_time ?: 'غير محدد' }}</p>
                    <p><strong>النوع:</strong> {{ $visit->visit_type_text }}</p>
                    <p><strong>الشكوى:</strong> {{ $visit->chief_complaint }}</p>
                    <p><strong>الحالة:</strong>
                        <span class="badge bg-{{ $visit->status_color }}">
                            {{ $visit->status_text }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- المحتوى الرئيسي - نظام الـ Accordion -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="accordion-container">
                <div class="accordion" id="visitAccordion">

                    <!-- قسم الفحص السريري -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="examinationHeading">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#examinationCollapse" aria-expanded="false" aria-controls="examinationCollapse">
                                <i class="fas fa-user-md section-icon text-primary"></i>
                                <span class="ms-3">الفحص السريري</span>
                                @if($examinationComplete)
                                    <span class="badge bg-success completion-badge">
                                        <i class="fas fa-check-circle me-1"></i>
                                        مكتمل
                                    </span>
                                @else
                                    <span class="badge bg-warning completion-badge">
                                        <i class="fas fa-clock me-1"></i>
                                        غير مكتمل
                                    </span>
                                @endif
                            </button>
                        </h2>
                        <div id="examinationCollapse" class="accordion-collapse collapse" aria-labelledby="examinationHeading" data-bs-parent="#visitAccordion">
                            <div class="accordion-body">
                                <form action="{{ route('doctor.visits.update', $visit) }}" method="POST" id="examinationForm">
                                    @csrf
                                    @method('PUT')

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    <i class="fas fa-heartbeat text-danger me-2"></i>
                                                    العلامات الحيوية
                                                </label>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>العلامة</th>
                                                                <th>القيمة</th>
                                                                <th>الوحدة</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @php
                                                                $vitalSigns = $visit->vital_signs ?? [];
                                                            @endphp
                                                            <tr>
                                                                <td>ضغط الدم الانقباضي</td>
                                                                <td><input type="number" class="form-control form-control-sm" name="vital_signs[blood_pressure_systolic]" value="{{ old('vital_signs.blood_pressure_systolic', $vitalSigns['blood_pressure_systolic'] ?? '') }}" placeholder="120"></td>
                                                                <td>mmHg</td>
                                                            </tr>
                                                            <tr>
                                                                <td>ضغط الدم الانبساطي</td>
                                                                <td><input type="number" class="form-control form-control-sm" name="vital_signs[blood_pressure_diastolic]" value="{{ old('vital_signs.blood_pressure_diastolic', $vitalSigns['blood_pressure_diastolic'] ?? '') }}" placeholder="80"></td>
                                                                <td>mmHg</td>
                                                            </tr>
                                                            <tr>
                                                                <td>النبض</td>
                                                                <td><input type="number" class="form-control form-control-sm" name="vital_signs[heart_rate]" value="{{ old('vital_signs.heart_rate', $vitalSigns['heart_rate'] ?? '') }}" placeholder="72"></td>
                                                                <td>نبض/دقيقة</td>
                                                            </tr>
                                                            <tr>
                                                                <td>درجة الحرارة</td>
                                                                <td><input type="number" step="0.1" class="form-control form-control-sm" name="vital_signs[temperature]" value="{{ old('vital_signs.temperature', $vitalSigns['temperature'] ?? '') }}" placeholder="36.5"></td>
                                                                <td>°C</td>
                                                            </tr>
                                                            <tr>
                                                                <td>معدل التنفس</td>
                                                                <td><input type="number" class="form-control form-control-sm" name="vital_signs[respiratory_rate]" value="{{ old('vital_signs.respiratory_rate', $vitalSigns['respiratory_rate'] ?? '') }}" placeholder="16"></td>
                                                                <td>نفس/دقيقة</td>
                                                            </tr>
                                                            <tr>
                                                                <td>الوزن</td>
                                                                <td><input type="number" step="0.1" class="form-control form-control-sm" name="vital_signs[weight]" value="{{ old('vital_signs.weight', $vitalSigns['weight'] ?? '') }}" placeholder="70.5"></td>
                                                                <td>كجم</td>
                                                            </tr>
                                                            <tr>
                                                                <td>الطول</td>
                                                                <td><input type="number" step="0.1" class="form-control form-control-sm" name="vital_signs[height]" value="{{ old('vital_signs.height', $vitalSigns['height'] ?? '') }}" placeholder="170.0"></td>
                                                                <td>سم</td>
                                                            </tr>
                                                            <tr>
                                                                <td>مستوى الأكسجين</td>
                                                                <td><input type="number" class="form-control form-control-sm" name="vital_signs[oxygen_saturation]" value="{{ old('vital_signs.oxygen_saturation', $vitalSigns['oxygen_saturation'] ?? '') }}" placeholder="98"></td>
                                                                <td>%</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="d-flex justify-content-end align-items-center">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save me-1"></i>
                                            حفظ الفحص السريري
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <!-- قسم التشخيص -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="diagnosisHeading">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#diagnosisCollapse" aria-expanded="false" aria-controls="diagnosisCollapse">
                                <i class="fas fa-heartbeat section-icon text-info"></i>
                                <span class="ms-3">التشخيص</span>
                                @if($diagnosisComplete)
                                    <span class="badge bg-success completion-badge">
                                        <i class="fas fa-check-circle me-1"></i>
                                        مكتمل
                                    </span>
                                @else
                                    <span class="badge bg-warning completion-badge">
                                        <i class="fas fa-clock me-1"></i>
                                        غير مكتمل
                                    </span>
                                @endif
                            </button>
                        </h2>
                        <div id="diagnosisCollapse" class="accordion-collapse collapse" aria-labelledby="diagnosisHeading" data-bs-parent="#visitAccordion">
                            <div class="accordion-body">
                                <form action="{{ route('doctor.visits.update', $visit) }}" method="POST" id="diagnosisForm">
                                    @csrf
                                    @method('PUT')

                                    <div class="mb-4">
                                        <label class="form-label">
                                            <i class="fas fa-stethoscope text-primary me-2"></i>
                                            التشخيص (ICD-10)
                                        </label>
                                        @php
                                            $diagnosisData = $visit->diagnosis ?? [];
                                        @endphp
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-primary text-white">
                                                        <i class="fas fa-search"></i>
                                                    </span>
                                                    <input type="text"
                                                           class="form-control diagnosis-input"
                                                           id="diagnosis_code"
                                                           name="diagnosis[code]"
                                                           placeholder="اكتب رمز أو وصف التشخيص..."
                                                           value="{{ old('diagnosis.code', $diagnosisData['code'] ?? '') }}"
                                                           autocomplete="off"
                                                           list="icd10-list"
                                                           title="ابدأ الكتابة للبحث في رموز ICD-10 - يمكنك البحث بالرمز أو الوصف">
                                                    <input type="hidden" id="diagnosis_code_hidden" name="diagnosis[actual_code]" value="{{ old('diagnosis.actual_code', $diagnosisData['actual_code'] ?? $diagnosisData['code'] ?? '') }}">
                                                    <datalist id="icd10-list">
                                                        @foreach($icd10Codes as $code)
                                                            <option value="{{ $code->code }} - {{ $code->description_ar ?: $code->description }}" data-code="{{ $code->code }}" data-search="{{ $code->code }} {{ $code->description_ar ?: '' }} {{ $code->description }}">
                                                        @endforeach
                                                        <option value="أخرى (أدخل يدوياً)" data-code="other" data-search="other أخرى يدوياً">
                                                    </datalist>
                                                </div>
                                                <small class="text-muted mt-1">
                                                    <i class="fas fa-lightbulb text-warning me-1"></i>
                                                    <strong>نصائح البحث:</strong>
                                                    <span class="ms-2">ابدأ الكتابة للبحث فوراً</span>
                                                    <span class="ms-2">• ابحث بالرمز (مثل: A01)</span>
                                                    <span class="ms-2">• أو بالوصف (مثل: التهاب)</span>
                                                </small>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="custom-code-container {{ old('diagnosis.code', $diagnosisData['code'] ?? '') == 'other' ? '' : 'd-none' }}">
                                                    <div class="input-group mb-2">
                                                        <span class="input-group-text bg-warning text-dark">
                                                            <i class="fas fa-edit"></i>
                                                        </span>
                                                        <input type="text" class="form-control" name="diagnosis[custom_code]" id="custom_code" placeholder="أدخل رمز ICD مخصص" value="{{ old('diagnosis.custom_code', $diagnosisData['custom_code'] ?? '') }}">
                                                    </div>
                                                </div>
                                                <div class="form-floating">
                                                    <textarea class="form-control" name="diagnosis[description]" id="diagnosis_description" style="height: 80px;" placeholder="وصف التشخيص">{{ old('diagnosis.description', $diagnosisData['description'] ?? '') }}</textarea>
                                                    <label for="diagnosis_description">
                                                        <i class="fas fa-file-alt me-1"></i>وصف التشخيص
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end align-items-center">
                                      
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save me-1"></i>
                                            حفظ التشخيص
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <!-- قسم الطلبات الطبية -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="requestsHeading">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#requestsCollapse" aria-expanded="false" aria-controls="requestsCollapse">
                                <i class="fas fa-clipboard-list section-icon text-warning"></i>
                                <span class="ms-3">الطلبات الطبية</span>
                                @if($requestsComplete)
                                    <span class="badge bg-success completion-badge">
                                        <i class="fas fa-check-circle me-1"></i>
                                        مكتمل
                                    </span>
                                @else
                                    <span class="badge bg-warning completion-badge">
                                        <i class="fas fa-clock me-1"></i>
                                        غير مكتمل
                                    </span>
                                @endif
                            </button>
                        </h2>
                        <div id="requestsCollapse" class="accordion-collapse collapse" aria-labelledby="requestsHeading" data-bs-parent="#visitAccordion">
                            <div class="accordion-body">
                                <div class="mb-4">
                                    <h4 class="mb-3">
                                        <i class="fas fa-clipboard-list text-primary me-2"></i>
                                        إضافة طلب طبي جديد
                                    </h4>
                                    
                                    <!-- تبويبات اختيار نوع الطلب -->
                                    <ul class="nav nav-pills mb-3" id="requestTypeTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="lab-tab" data-bs-toggle="pill" data-bs-target="#lab-content" type="button" role="tab" aria-controls="lab-content" aria-selected="true">
                                                <i class="fas fa-flask me-2"></i>تحاليل مخبرية
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="radiology-tab" data-bs-toggle="pill" data-bs-target="#radiology-content" type="button" role="tab" aria-controls="radiology-content" aria-selected="false">
                                                <i class="fas fa-x-ray me-2"></i>أشعة وتصوير
                                            </button>
                                        </li>
                                    </ul>
                                    
                                    <!-- محتوى التبويبات -->
                                    <div class="tab-content border rounded p-4 bg-light" id="requestTypeContent">
                                        <!-- تبويب التحاليل -->
                                        <div class="tab-pane fade show active" id="lab-content" role="tabpanel" aria-labelledby="lab-tab">
                                            <form action="{{ route('doctor.requests.store') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="visit_id" value="{{ $visit->id }}">
                                                <input type="hidden" name="type" value="lab">
                                                <input type="hidden" name="priority" value="normal">
                                                
                                                <h5 class="mb-3 text-primary">
                                                    <i class="fas fa-microscope me-2"></i>
                                                    اختر التحاليل المطلوبة
                                                </h5>
                                                
                                                <!-- حقل البحث والفلترة -->
                                                <div class="row mb-3">
                                                    <div class="col-md-8">
                                                        <div class="input-group">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-search"></i>
                                                            </span>
                                                            <input type="text" id="labSearchInput" class="form-control" placeholder="ابحث عن تحليل...">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <select id="labCategoryFilter" class="form-select">
                                                            <option value="">جميع الفئات</option>
                                                            @php
                                                                $grouped = $labTests->groupBy('category');
                                                            @endphp
                                                            @foreach($grouped as $category => $tests)
                                                                <option value="{{ $category }}">{{ $category }} ({{ $tests->count() }})</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <!-- قائمة التحاليل -->
                                                <div id="labTestsContainer" style="max-height: 450px; overflow-y: auto;">
                                                    @foreach($grouped as $category => $tests)
                                                        <div class="mb-3 lab-category" data-category="{{ $category }}">
                                                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                                                <h6 class="mb-0 text-primary">
                                                                    <i class="fas fa-vial me-2"></i>{{ $category }}
                                                                    <span class="badge bg-primary ms-2">{{ $tests->count() }}</span>
                                                                </h6>
                                                                <button type="button" class="btn btn-sm btn-outline-primary select-all-category" data-category="{{ $category }}" style="font-size: 0.75rem; padding: 2px 8px;">
                                                                    <i class="fas fa-check-double me-1"></i>تحديد الكل
                                                                </button>
                                                            </div>
                                                            <div class="list-group">
                                                                @foreach($tests as $test)
                                                                    <label class="list-group-item list-group-item-action d-flex align-items-center lab-test-item hover-lab-item" data-test-name="{{ strtolower($test->name) }}" style="cursor: pointer; padding: 10px 15px; border-left: 3px solid #dee2e6; transition: all 0.2s;">
                                                                        <input class="form-check-input me-3 flex-shrink-0" type="checkbox" name="tests[]" value="{{ $test->name }}" id="inline_test_{{ $test->id }}" style="width: 20px; height: 20px; cursor: pointer;">
                                                                        <span class="flex-grow-1" style="font-size: 0.95rem;">{{ $test->name }}</span>
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                
                                                <!-- عداد التحاليل المختارة -->
                                                <div class="alert alert-info mt-3" id="selectedLabCount" style="display: none;">
                                                    <i class="fas fa-check-circle me-2"></i>
                                                    تم اختيار <strong id="labCountNumber">0</strong> تحليل
                                                </div>
                                                
                                                <div class="mt-3">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-plus me-1"></i>إضافة طلب التحاليل
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                        
                                        <!-- تبويب الأشعة -->
                                        <div class="tab-pane fade" id="radiology-content" role="tabpanel" aria-labelledby="radiology-tab">
                                            <form action="{{ route('doctor.requests.store') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="visit_id" value="{{ $visit->id }}">
                                                <input type="hidden" name="type" value="radiology">
                                                <input type="hidden" name="priority" value="normal">
                                                
                                                <h5 class="mb-3 text-info">
                                                    <i class="fas fa-x-ray me-2"></i>
                                                    اختر فحوصات الأشعة والتصوير المطلوبة
                                                </h5>
                                                
                                                @if(isset($radiologyTypes) && $radiologyTypes->count() > 0)
                                                    <!-- حقل البحث والفلترة -->
                                                    <div class="row mb-3">
                                                        <div class="col-md-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text">
                                                                    <i class="fas fa-search"></i>
                                                                </span>
                                                                <input type="text" id="radiologySearchInput" class="form-control" placeholder="ابحث عن فحص أشعة...">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <select id="radiologyCategoryFilter" class="form-select">
                                                                <option value="">جميع الفئات</option>
                                                                @php
                                                                    $radiologyGrouped = $radiologyTypes->groupBy('category');
                                                                @endphp
                                                                @foreach($radiologyGrouped as $category => $types)
                                                                    <option value="{{ $category }}">{{ $category ?: 'غير مصنف' }} ({{ $types->count() }})</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- قائمة فحوصات الأشعة -->
                                                    <div id="radiologyTypesContainer" style="max-height: 450px; overflow-y: auto;">
                                                        @foreach($radiologyGrouped as $category => $types)
                                                            <div class="mb-3 radiology-category" data-category="{{ $category }}">
                                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);">
                                                                    <h6 class="mb-0 text-info">
                                                                        <i class="fas fa-x-ray me-2"></i>{{ $category ?: 'غير مصنف' }}
                                                                        <span class="badge bg-info ms-2">{{ $types->count() }}</span>
                                                                    </h6>
                                                                    <button type="button" class="btn btn-sm btn-outline-info select-all-radiology" data-category="{{ $category }}" style="font-size: 0.75rem; padding: 2px 8px;">
                                                                        <i class="fas fa-check-double me-1"></i>تحديد الكل
                                                                    </button>
                                                                </div>
                                                                <div class="list-group">
                                                                    @foreach($types as $type)
                                                                        <label class="list-group-item list-group-item-action d-flex align-items-center radiology-type-item hover-radiology-item" data-type-name="{{ strtolower($type->name) }}" style="cursor: pointer; padding: 10px 15px; border-left: 3px solid #bae6fd; transition: all 0.2s;">
                                                                            <input class="form-check-input radiology-checkbox me-3 flex-shrink-0" type="checkbox" name="radiology_types[]" value="{{ $type->id }}" id="inline_rad_{{ $type->id }}" style="width: 20px; height: 20px; cursor: pointer;">
                                                                            <div class="flex-grow-1">
                                                                                <span style="font-size: 0.95rem;">{{ $type->name }}</span>
                                                                                @if($type->description)
                                                                                    <br><small class="text-muted" style="font-size: 0.8rem;">{{ Str::limit($type->description, 40) }}</small>
                                                                                @endif
                                                                            </div>
                                                                        </label>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    
                                                    <!-- عداد فحوصات الأشعة المختارة -->
                                                    <div class="alert alert-info mt-3" id="selectedRadiologyCount" style="display: none;">
                                                        <i class="fas fa-check-circle me-2"></i>
                                                        تم اختيار <strong id="radiologyCountNumber">0</strong> فحص أشعة
                                                    </div>
                                                @else
                                                    <div class="alert alert-warning">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        لا توجد فحوصات أشعة متاحة حالياً
                                                    </div>
                                                @endif
                                                
                                                <div class="mt-3">
                                                    <button type="submit" class="btn btn-info">
                                                        <i class="fas fa-plus me-1"></i>إضافة طلب الأشعة
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h4 class="mb-3">
                                    <i class="fas fa-list text-secondary me-2"></i>
                                    الطلبات السابقة
                                </h4>

                                @if($visit->requests->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>النوع</th>
                                                    <th>التفاصيل</th>
                                                    <th>الحالة</th>
                                                    <th>تاريخ الإنشاء</th>
                                                    <th>النتائج</th>
                                                    <th>الإجراءات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($visit->requests as $request)
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-{{ $request->type == 'lab' ? 'primary' : ($request->type == 'radiology' ? 'info' : 'success') }}">
                                                            {{ $request->type_text }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($request->status == 'completed' && $request->result)
                                                            @php
                                                                $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
                                                            @endphp
                                                            @if($request->type == 'radiology')
                                                                نتائج الأشعة جاهزة
                                                            @elseif($request->type == 'lab')
                                                                نتائج التحاليل جاهزة
                                                            @else
                                                                نتائج جاهزة
                                                            @endif
                                                        @elseif($request->type == 'radiology' && isset($request->details['radiology_types']))
                                                            @php
                                                                $radiologyNames = [];
                                                                foreach($request->details['radiology_types'] as $typeId) {
                                                                    $type = \App\Models\RadiologyType::find($typeId);
                                                                    if($type) {
                                                                        $radiologyNames[] = $type->name;
                                                                    }
                                                                }
                                                            @endphp
                                                            {{ implode(', ', $radiologyNames) }}
                                                        @else
                                                            {{ Str::limit($request->details['description'] ?? '', 50) }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{ $request->status_color }}">
                                                            {{ $request->status_text }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $request->created_at->format('Y-m-d H:i') }}</td>
                                                    <td>
                                                        @if($request->status == 'completed')
                                                            @if($request->result)
                                                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#resultModal{{ $request->id }}">
                                                                    <i class="fas fa-eye me-1"></i> عرض النتائج
                                                                </button>
                                                            @else
                                                                <span class="badge bg-warning text-dark">
                                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                                    مكتمل بدون نتائج
                                                                </span>
                                                            @endif
                                                        @elseif($request->status == 'pending')
                                                            <span class="badge bg-info">
                                                                <i class="fas fa-hourglass-half me-1"></i>
                                                                قيد الانتظار
                                                            </span>
                                                        @elseif($request->status == 'in_progress')
                                                            <span class="badge bg-primary">
                                                                <i class="fas fa-spinner fa-spin me-1"></i>
                                                                جاري المعالجة
                                                            </span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($request->status == 'pending')
                                                            <form action="{{ route('doctor.requests.update', $request) }}" method="POST" class="d-inline delete-request-form">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="status" value="cancelled">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                        onclick="return confirm('هل أنت متأكد من إلغاء هذا الطلب؟')">
                                                                    إلغاء
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @foreach($visit->requests as $request)
                                    <!-- Modal لعرض النتائج -->
                                    <div class="modal fade" id="resultModal{{ $request->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                    <h5 class="modal-title text-white">
                                                        <i class="fas fa-file-medical me-2"></i>
                                                        نتائج {{ $request->type_text }}
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    @if($request->result)
                                                        @php
                                                            $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
                                                        @endphp
                                                        
                                                        @if($request->type == 'radiology')
                                                            <!-- عرض نتائج الأشعة -->
                                                            <div class="alert alert-info mb-3">
                                                                <i class="fas fa-info-circle me-2"></i>
                                                                <strong>نوع الفحص:</strong> الأشعة والتصوير
                                                            </div>
                                                            
                                                            @if(isset($resultData['findings']))
                                                            <div class="mb-4">
                                                                <h6 class="text-primary border-bottom pb-2">
                                                                    <i class="fas fa-search me-2"></i>النتائج (Findings):
                                                                </h6>
                                                                <p class="ms-3">{{ $resultData['findings'] }}</p>
                                                            </div>
                                                            @endif
                                                            
                                                            @if(isset($resultData['impression']))
                                                            <div class="mb-4">
                                                                <h6 class="text-primary border-bottom pb-2">
                                                                    <i class="fas fa-clipboard-check me-2"></i>الانطباع (Impression):
                                                                </h6>
                                                                <p class="ms-3">{{ $resultData['impression'] }}</p>
                                                            </div>
                                                            @endif
                                                            
                                                            @if(isset($resultData['recommendations']))
                                                            <div class="mb-4">
                                                                <h6 class="text-primary border-bottom pb-2">
                                                                    <i class="fas fa-notes-medical me-2"></i>التوصيات (Recommendations):
                                                                </h6>
                                                                <p class="ms-3">{{ $resultData['recommendations'] }}</p>
                                                            </div>
                                                            @endif
                                                            
                                                            @if(isset($resultData['images']) && is_array($resultData['images']) && count($resultData['images']) > 0)
                                                            <div class="mb-4">
                                                                <h6 class="text-primary border-bottom pb-2">
                                                                    <i class="fas fa-images me-2"></i>صور الأشعة:
                                                                </h6>
                                                                <div class="row g-2 ms-2">
                                                                    @foreach($resultData['images'] as $index => $image)
                                                                    <div class="col-md-3">
                                                                        <a href="{{ Storage::url($image) }}" target="_blank" class="d-block">
                                                                            <img src="{{ Storage::url($image) }}" alt="صورة {{ $index + 1 }}" class="img-thumbnail hover-zoom" style="width: 100%; height: 150px; object-fit: cover;">
                                                                        </a>
                                                                    </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                            @endif
                                                            
                                                            @if(isset($resultData['radiologist']))
                                                            <div class="mt-4 p-3 bg-light rounded">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-user-md me-2"></i>
                                                                    <strong>أخصائي الأشعة:</strong> {{ $resultData['radiologist'] }}
                                                                    @if(isset($resultData['reported_at']))
                                                                    <br>
                                                                    <i class="fas fa-calendar me-2"></i>
                                                                    <strong>تاريخ التقرير:</strong> {{ $resultData['reported_at'] }}
                                                                    @endif
                                                                </small>
                                                            </div>
                                                            @endif
                                                        
                                                        @elseif($request->type == 'lab' && isset($resultData['test_results']) && is_array($resultData['test_results']))
                                                            <!-- عرض نتائج التحاليل -->
                                                            <div class="alert alert-primary mb-3">
                                                                <i class="fas fa-flask me-2"></i>
                                                                <strong>نوع الفحص:</strong> التحاليل المخبرية
                                                            </div>
                                                            
                                                            <div class="table-responsive">
                                                                <table class="table table-hover table-bordered">
                                                                    <thead class="table-primary">
                                                                        <tr>
                                                                            <th width="30%"><i class="fas fa-vial me-2"></i>الفحص</th>
                                                                            <th width="20%"><i class="fas fa-chart-line me-2"></i>القيمة</th>
                                                                            <th width="15%"><i class="fas fa-ruler me-2"></i>الوحدة</th>
                                                                            <th width="25%"><i class="fas fa-info-circle me-2"></i>المرجع الطبيعي</th>
                                                                            <th width="10%"><i class="fas fa-flag me-2"></i>الحالة</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($resultData['test_results'] as $testName => $testData)
                                                                        @php
                                                                            $value = is_array($testData) ? ($testData['value'] ?? '-') : $testData;
                                                                            $unit = is_array($testData) ? ($testData['unit'] ?? '-') : '-';
                                                                            $reference = is_array($testData) ? ($testData['reference'] ?? '-') : '-';
                                                                            $isAbnormal = is_array($testData) && isset($testData['abnormal']) && $testData['abnormal'];
                                                                        @endphp
                                                                        <tr class="{{ $isAbnormal ? 'table-warning' : '' }}">
                                                                            <td><strong>{{ $testName }}</strong></td>
                                                                            <td><span class="badge bg-{{ $isAbnormal ? 'warning' : 'success' }} text-dark">{{ $value }}</span></td>
                                                                            <td>{{ $unit }}</td>
                                                                            <td><small class="text-muted">{{ $reference }}</small></td>
                                                                            <td class="text-center">
                                                                                @if($isAbnormal)
                                                                                    <i class="fas fa-exclamation-triangle text-warning" title="خارج النطاق الطبيعي"></i>
                                                                                @else
                                                                                    <i class="fas fa-check-circle text-success" title="ضمن النطاق الطبيعي"></i>
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                            
                                                            @if(isset($resultData['notes']) && $resultData['notes'])
                                                            <div class="mt-3 p-3 bg-light rounded">
                                                                <h6 class="text-primary"><i class="fas fa-sticky-note me-2"></i>ملاحظات إضافية:</h6>
                                                                <p class="mb-0">{{ $resultData['notes'] }}</p>
                                                            </div>
                                                            @endif
                                                            
                                                            @if(isset($resultData['technician']))
                                                            <div class="mt-3 p-2 bg-light rounded">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-user-cog me-2"></i>
                                                                    <strong>فني المختبر:</strong> {{ $resultData['technician'] }}
                                                                    @if(isset($resultData['tested_at']))
                                                                    | <i class="fas fa-clock me-1"></i>{{ $resultData['tested_at'] }}
                                                                    @endif
                                                                </small>
                                                            </div>
                                                            @endif
                                                        
                                                        @else
                                                            <!-- عرض افتراضي للنتائج -->
                                                            <div class="alert alert-info">
                                                                <i class="fas fa-info-circle me-2"></i>
                                                                النتائج متوفرة بصيغة نصية:
                                                            </div>
                                                            <div class="p-3 bg-light rounded">
                                                                <pre class="mb-0">{{ is_array($request->result) ? json_encode($request->result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $request->result }}</pre>
                                                            </div>
                                                        @endif
                                                    @else
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            لا توجد نتائج مسجلة لهذا الطلب
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <i class="fas fa-times me-1"></i>إغلاق
                                                    </button>
                                                    <button type="button" class="btn btn-primary" onclick="window.print()">
                                                        <i class="fas fa-print me-1"></i>طباعة
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                                    @endforeach
                                @else
                                    <div class="text-center py-5">
                                        <i class="fas fa-clipboard fa-4x text-muted mb-3"></i>
                                        <h5 class="text-muted">لا توجد طلبات طبية</h5>
                                        <p class="text-muted">اختر نوع الطلب لإنشائه</p>
                                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#requestModal">
                                            <i class="fas fa-flask me-1"></i>
                                            فحوصات مختبرية
                                        </button>
                                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#radiologyModal">
                                            <i class="fas fa-x-ray me-1"></i>
                                            طلب أشعة
                                        </button>
                                    </div>
                                @endif

                               
                            </div>
                        </div>

                    <!-- قسم خطة العلاج -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="treatmentHeading">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#treatmentCollapse" aria-expanded="false" aria-controls="treatmentCollapse">
                                <i class="fas fa-pills section-icon text-success"></i>
                                <span class="ms-3">خطة العلاج</span>
                                @if($treatmentComplete)
                                    <span class="badge bg-success completion-badge">
                                        <i class="fas fa-check-circle me-1"></i>
                                        مكتمل
                                    </span>
                                @else
                                    <span class="badge bg-warning completion-badge">
                                        <i class="fas fa-clock me-1"></i>
                                        غير مكتمل
                                    </span>
                                @endif
                            </button>
                        </h2>
                        <div id="treatmentCollapse" class="accordion-collapse collapse" aria-labelledby="treatmentHeading" data-bs-parent="#visitAccordion">
                            <div class="accordion-body">
                                @php
                                    $hasCompletedRequests = $visit->requests->where('status', 'completed')->count() > 0;
                                    $hasPendingRequests = $visit->requests->where('status', 'pending')->count() > 0;
                                    $prescribedMedications = $visit->prescribedMedications->where('item_type', 'medication');
                                    $otherTreatments = $visit->prescribedMedications->where('item_type', 'treatment');
                                @endphp

                                <!-- عرض نتائج الطلبات المكتملة أولاً -->
                                @if($hasCompletedRequests)
                                    <div class="card border-primary mb-4">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0">
                                                <i class="fas fa-flask me-2"></i>
                                                نتائج التحاليل والفحوصات المكتملة
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                @foreach($visit->requests->where('status', 'completed') as $request)
                                                <div class="col-md-6 mb-3">
                                                    <div class="card border-success">
                                                        <div class="card-header bg-success text-white">
                                                            <h6 class="mb-0">
                                                                <i class="fas fa-{{ $request->type == 'lab' ? 'flask' : ($request->type == 'radiology' ? 'x-ray' : 'pills') }} me-2"></i>
                                                                {{ $request->type_text }} - {{ $request->created_at->format('Y-m-d') }}
                                                            </h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <p class="mb-2"><strong>الوصف:</strong> {{ $request->details['description'] ?? 'غير محدد' }}</p>
                                                            @if($request->result)
                                                                @php
                                                                    $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
                                                                @endphp
                                                                
                                                                @if($request->type == 'lab' && isset($resultData['test_results']) && is_array($resultData['test_results']))
                                                                    <!-- نتائج التحاليل المخبرية -->
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm table-bordered mb-0">
                                                                            <thead class="table-light">
                                                                                <tr>
                                                                                    <th>الفحص</th>
                                                                                    <th>القيمة</th>
                                                                                    <th>الوحدة</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach($resultData['test_results'] as $testName => $testData)
                                                                                <tr>
                                                                                    <td>{{ $testName }}</td>
                                                                                    <td>{{ (is_array($testData) ? ($testData['value'] ?? '-') : '-') }}</td>
                                                                                    <td>{{ (is_array($testData) ? ($testData['unit'] ?? '-') : '-') }}</td>
                                                                                </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                @elseif($request->type == 'radiology' && is_array($resultData))
                                                                    <!-- نتائج الأشعة -->
                                                                    <div class="radiology-results">
                                                                        @if(isset($resultData['findings']))
                                                                            <div class="mb-3">
                                                                                <h6 class="text-primary"><i class="fas fa-search me-2"></i>النتائج:</h6>
                                                                                <p class="mb-0 ps-3">{{ $resultData['findings'] }}</p>
                                                                            </div>
                                                                        @endif
                                                                        
                                                                        @if(isset($resultData['impression']))
                                                                            <div class="mb-3">
                                                                                <h6 class="text-info"><i class="fas fa-clipboard-check me-2"></i>الانطباع:</h6>
                                                                                <p class="mb-0 ps-3">{{ $resultData['impression'] }}</p>
                                                                            </div>
                                                                        @endif
                                                                        
                                                                        @if(isset($resultData['recommendations']))
                                                                            <div class="mb-3">
                                                                                <h6 class="text-warning"><i class="fas fa-lightbulb me-2"></i>التوصيات:</h6>
                                                                                <p class="mb-0 ps-3">{{ $resultData['recommendations'] }}</p>
                                                                            </div>
                                                                        @endif
                                                                        
                                                                        @if(isset($resultData['radiologist']))
                                                                            <div class="mb-3">
                                                                                <small class="text-muted">
                                                                                    <i class="fas fa-user-md me-1"></i>
                                                                                    أخصائي الأشعة: <strong>{{ $resultData['radiologist'] }}</strong>
                                                                                </small>
                                                                            </div>
                                                                        @endif
                                                                        
                                                                        @if(isset($resultData['images']) && is_array($resultData['images']) && count($resultData['images']) > 0)
                                                                            <div class="mb-2">
                                                                                <h6 class="text-success"><i class="fas fa-images me-2"></i>الصور:</h6>
                                                                                <div class="row g-2">
                                                                                    @foreach($resultData['images'] as $image)
                                                                                        <div class="col-6">
                                                                                            <a href="{{ asset('storage/' . $image) }}" target="_blank" class="d-block">
                                                                                                <img src="{{ asset('storage/' . $image) }}" 
                                                                                                     class="img-fluid rounded border" 
                                                                                                     style="max-height: 150px; width: 100%; object-fit: cover;"
                                                                                                     alt="صورة الأشعة">
                                                                                            </a>
                                                                                        </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @else
                                                                    <p class="text-muted mb-0">{{ is_array($resultData) ? json_encode($resultData, JSON_UNESCAPED_UNICODE) : $resultData }}</p>
                                                                @endif
                                                            @else
                                                                <p class="text-muted mb-0">لا توجد نتائج مفصلة</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @elseif(!$hasCompletedRequests && $visit->requests->count() > 0)
                                    <div class="alert alert-warning mb-4">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>تنبيه مهم:</strong> يُفضل وضع خطة العلاج بعد الحصول على نتائج التحاليل والأشعة المطلوبة.
                                        @if($hasPendingRequests)
                                            <br><small>لديك {{ $visit->requests->where('status', 'pending')->count() }} طلب قيد الانتظار.</small>
                                        @endif
                                    </div>
                                @endif

                                <form action="{{ route('doctor.visits.update', $visit) }}" method="POST" id="treatmentForm" onsubmit="clearSavedData()">
                                    @csrf
                                    @method('PUT')

                                    <!-- قسم الأدوية المحددة -->
                                    <div class="card border-success mb-4">
                                        <div class="card-header bg-success text-white">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5 class="mb-0">
                                                    <i class="fas fa-pills me-2"></i>
                                                    الأدوية الموصوفة
                                                </h5>
                                                <button type="button" class="btn btn-light btn-sm" onclick="addMedication()">
                                                    <i class="fas fa-plus me-1"></i>
                                                    إضافة دواء
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <!-- قسم الأدوية المحددة -->
                                            <!-- قسم الأدوية المحددة -->
                                            <div id="medicationsContainer">
                                                @if($prescribedMedications->count() > 0)
                                                    @foreach($prescribedMedications as $index => $medication)
                                                    <div class="medication-item card mb-3 border-0 shadow-sm">
                                                        <div class="card-body bg-light">
                                                            <div class="row g-3">
                                                                <div class="col-md-3">
                                                                    <label class="form-label fw-bold">اسم الدواء</label>
                                                                    <input type="text" class="form-control" name="prescribed_medications[{{ $index }}][name]"
                                                                           value="{{ $medication->name }}"
                                                                           placeholder="اسم الدواء" list="commonMedications" required>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label fw-bold">نوع العلاج</label>
                                                                    <select class="form-select" name="prescribed_medications[{{ $index }}][type]" required>
                                                                        <option value="tablet" {{ $medication->type == 'tablet' ? 'selected' : '' }}>حبوب</option>
                                                                        <option value="injection" {{ $medication->type == 'injection' ? 'selected' : '' }}>إبرة</option>
                                                                        <option value="syrup" {{ $medication->type == 'syrup' ? 'selected' : '' }}>شراب</option>
                                                                        <option value="cream" {{ $medication->type == 'cream' ? 'selected' : '' }}>كريم</option>
                                                                        <option value="drops" {{ $medication->type == 'drops' ? 'selected' : '' }}>قطرات</option>
                                                                        <option value="other" {{ $medication->type == 'other' ? 'selected' : '' }}>أخرى</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label fw-bold">الجرعة</label>
                                                                    <input type="text" class="form-control" name="prescribed_medications[{{ $index }}][dosage]"
                                                                           value="{{ $medication->dosage }}"
                                                                           placeholder="500mg" required>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label fw-bold d-block mb-2">التكرار يومياً</label>
                                                                    <div class="frequency-selector" style="display: flex; gap: 5px; flex-wrap: wrap;">
                                                                        @foreach(['1' => 'مرة', '2' => 'مرتين', '3' => 'ثلاث', '4' => 'أربع', 'as_needed' => 'عند الحاجة'] as $value => $label)
                                                                        <input type="radio" id="freq_{{ $index }}_{{ $value }}" name="prescribed_medications[{{ $index }}][frequency]" value="{{ $value }}" {{ $medication->frequency == $value ? 'checked' : '' }} style="display: none;">
                                                                        <label for="freq_{{ $index }}_{{ $value }}" class="frequency-btn" style="padding: 6px 10px; border: 2px solid #e9ecef; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s ease; background: white;">{{ $label }}</label>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label fw-bold">المدة</label>
                                                                    <input type="text" class="form-control" name="prescribed_medications[{{ $index }}][duration]"
                                                                           value="{{ $medication->duration }}"
                                                                           placeholder="7 أيام" required>
                                                                </div>
                                                                <div class="col-md-1 d-flex align-items-end">
                                                                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeMedication(this)" title="حذف">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="row g-3 mt-2">
                                                                <div class="col-md-4">
                                                                    <label class="form-label fw-bold">الأوقات</label>
                                                                    <input type="text" class="form-control" name="prescribed_medications[{{ $index }}][times]"
                                                                           value="{{ $medication->times }}"
                                                                           placeholder="صباح، ظهر، مساء">
                                                                </div>
                                                                <div class="col-md-8">
                                                                    <label class="form-label fw-bold">تعليمات خاصة</label>
                                                                    <input type="text" class="form-control" name="prescribed_medications[{{ $index }}][instructions]"
                                                                           value="{{ $medication->instructions }}"
                                                                           placeholder="بعد الأكل، مع الماء، إلخ...">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                @else
                                                    <div class="text-center py-4 text-muted">
                                                        <i class="fas fa-pills fa-3x mb-3 text-success opacity-25"></i>
                                                        <p class="mb-1">لا توجد أدوية موصوفة</p>
                                                        <small>اضغط على "إضافة دواء" أعلاه لبدء إضافة الأدوية</small>
                                                    </div>
                                                @endif
                                            </div>
                                            </div>

                                            <!-- قائمة الأدوية الشائعة -->
                                            <datalist id="commonMedications">
                                                <option value="أموكسيسيلين 500mg">
                                                <option value="أزيثروميسين 500mg">
                                                <option value="باراسيتامول 500mg">
                                                <option value="إيبوبروفين 400mg">
                                                <option value="أملوديبين 5mg">
                                                <option value="لوسارتان 50mg">
                                                <option value="ميتفورمين 500mg">
                                                <option value="أوميبرازول 20mg">
                                                <option value="سالبوتامول رذاذ">
                                                <option value="سيفالكسين 500mg">
                                                <option value="ديكلوفيناك 50mg">
                                                <option value="فيتامين D 1000 وحدة">
                                                <option value="كالسيوم 500mg">
                                                <option value="أسبرين 75mg">
                                            </datalist>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end align-items-center mt-3">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-save me-1"></i>
                                            حفظ خطة العلاج
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <!-- قسم التاريخ الطبي -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="historyHeading">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#historyCollapse" aria-expanded="false" aria-controls="historyCollapse">
                                <i class="fas fa-history section-icon text-primary"></i>
                                <span class="ms-3">التاريخ الطبي</span>
                                @if($historyComplete)
                                    <span class="badge bg-success completion-badge">
                                        <i class="fas fa-check-circle me-1"></i>
                                        مكتمل
                                    </span>
                                @else
                                    <span class="badge bg-warning completion-badge">
                                        <i class="fas fa-clock me-1"></i>
                                        غير مكتمل
                                    </span>
                                @endif
                            </button>
                        </h2>
                        <div id="historyCollapse" class="accordion-collapse collapse" aria-labelledby="historyHeading" data-bs-parent="#visitAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card border-info">
                                            <div class="card-header bg-info text-white">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-history me-2"></i>
                                                    الزيارات السابقة
                                                </h6>
                                            </div>
                                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                                @if($visit->patient->visits->count() > 1)
                                                    <div class="list-group list-group-flush">
                                                        @foreach($visit->patient->visits->where('id', '!=', $visit->id)->sortByDesc('visit_date')->take(10) as $previousVisit)
                                                            <a href="{{ route('doctor.visits.show', $previousVisit) }}" class="list-group-item list-group-item-action">
                                                                <div class="d-flex justify-content-between align-items-start">
                                                                    <div class="flex-grow-1">
                                                                        <h6 class="mb-1">
                                                                            <i class="fas fa-calendar-day text-primary me-2"></i>
                                                                            {{ $previousVisit->visit_date ? $previousVisit->visit_date->format('Y-m-d') : 'غير محدد' }}
                                                                        </h6>
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-stethoscope me-1"></i>
                                                                            {{ Str::limit($previousVisit->chief_complaint, 50) }}
                                                                        </small>
                                                                        @if($previousVisit->diagnosis)
                                                                            <br>
                                                                            <small class="text-success">
                                                                                <i class="fas fa-notes-medical me-1"></i>
                                                                                {{ Str::limit($previousVisit->diagnosis['description'] ?? '', 40) }}
                                                                            </small>
                                                                        @endif
                                                                    </div>
                                                                    <span class="badge bg-{{ $previousVisit->status_color }}">
                                                                        {{ $previousVisit->status_text }}
                                                                    </span>
                                                                </div>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="text-center py-4">
                                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                        <p class="text-muted mb-0">لا توجد زيارات سابقة لهذا المريض</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-warning">
                                            <div class="card-header bg-warning text-dark">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-allergies me-2"></i>
                                                    الحساسية والأدوية المزمنة
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                @if($visit->patient->allergies || $visit->patient->chronic_medications)
                                                    @if($visit->patient->allergies)
                                                        <div class="alert alert-danger mb-3">
                                                            <h6 class="alert-heading">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                                الحساسية:
                                                            </h6>
                                                            <p class="mb-0">{{ $visit->patient->allergies }}</p>
                                                        </div>
                                                    @endif
                                                    
                                                    @if($visit->patient->chronic_medications)
                                                        <div class="alert alert-info">
                                                            <h6 class="alert-heading">
                                                                <i class="fas fa-pills me-2"></i>
                                                                الأدوية المزمنة:
                                                            </h6>
                                                            <p class="mb-0">{{ $visit->patient->chronic_medications }}</p>
                                                        </div>
                                                    @endif
                                                @else
                                                    <div class="text-center py-4">
                                                        <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                                                        <p class="text-muted mb-0">لا توجد حساسية أو أدوية مزمنة مسجلة</p>
                                                        <small class="text-muted">هذا المريض ليس لديه أي حساسية معروفة</small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal لإضافة طلب -->
<div class="modal fade" id="requestModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-plus-circle me-2"></i>
                    إضافة طلب طبي جديد
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('doctor.requests.store') }}" method="POST" id="requestForm">
                @csrf
                <input type="hidden" name="visit_id" value="{{ $visit->id }}">
                <input type="hidden" name="priority" value="normal">
                <div class="modal-body p-4">
                    <!-- معلومات أساسية -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 text-primary">
                                <i class="fas fa-info-circle me-2"></i>
                                معلومات الطلب الأساسية
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- اختيار نوع الطلب: مختبر أو أشعة (راديو) -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label d-block">نوع الطلب <span class="text-danger">*</span></label>
                                    <div class="btn-group w-100" role="group" aria-label="Request Type Switch">
                                        <input type="radio" class="btn-check" name="type" id="req_type_lab" value="lab" autocomplete="off" checked>
                                        <label class="btn btn-outline-primary" for="req_type_lab">
                                            <i class="fas fa-flask me-1"></i> تحاليل مخبرية
                                        </label>
                                        <input type="radio" class="btn-check" name="type" id="req_type_radiology" value="radiology" autocomplete="off">
                                        <label class="btn btn-outline-info" for="req_type_radiology">
                                            <i class="fas fa-x-ray me-1"></i> أشعة / تصوير
                                        </label>
                                    </div>
                                    <div class="form-text mt-2">اختر نوع الطلب للتبديل بين القوائم.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="alert alert-info border-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        يمكنك التبديل بين الفحوصات المختبرية والأشعة هنا
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- قسم التحاليل -->
                    <div class="card border-0 shadow-sm" id="labTests" style="display: block;">
                        <div class="card-header bg-gradient-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-microscope me-2"></i>
                                الفحوصات المطلوبة
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info border-0">
                                <i class="fas fa-info-circle me-2"></i>
                                اختر الفحوصات المطلوبة من القائمة أدناه
                            </div>
                        @php
                            $grouped = $labTests->groupBy('category');
                            $categoryNames = [
                                'كيمياء سريرية' => 'كيمياء سريرية',
                                'أمراض الدم' => 'أمراض الدم',
                                'مصرف الدم' => 'مصرف الدم',
                                'الطفيليات' => 'الطفيليات',
                                'الأحياء المجهرية' => 'الأحياء المجهرية',
                                'المناعة السريرية' => 'المناعة السريرية',
                                'فيروسات' => 'فيروسات',
                                'هرمونات' => 'هرمونات',
                                'الخلايا' => 'الخلايا',
                                'متفرقة' => 'متفرقة',
                                'أخرى' => 'أخرى'
                            ];
                            $categoryIcons = [
                                'كيمياء سريرية' => 'fas fa-flask',
                                'أمراض الدم' => 'fas fa-tint',
                                'مصرف الدم' => 'fas fa-syringe',
                                'الطفيليات' => 'fas fa-bug',
                                'الأحياء المجهرية' => 'fas fa-microscope',
                                'المناعة السريرية' => 'fas fa-shield-alt',
                                'فيروسات' => 'fas fa-virus',
                                'هرمونات' => 'fas fa-dna',
                                'الخلايا' => 'fas fa-search',
                                'متفرقة' => 'fas fa-list',
                                'أخرى' => 'fas fa-plus'
                            ];

                            // تجميع الفئات في مجموعات أكبر
                            $mainGroups = [
                                'كيمياء سريرية' => [
                                    'categories' => ['كيمياء سريرية'],
                                    'icon' => 'fas fa-flask',
                                    'color' => 'success'
                                ],
                                'أمراض الدم والمصارف' => [
                                    'categories' => ['أمراض الدم', 'مصرف الدم'],
                                    'icon' => 'fas fa-tint',
                                    'color' => 'danger'
                                ],
                                'الميكروبيولوجيا' => [
                                    'categories' => ['الأحياء المجهرية', 'الطفيليات'],
                                    'icon' => 'fas fa-microscope',
                                    'color' => 'info'
                                ],
                                'المناعة والهرمونات' => [
                                    'categories' => ['المناعة السريرية', 'فيروسات', 'هرمونات'],
                                    'icon' => 'fas fa-shield-alt',
                                    'color' => 'warning'
                                ],
                                'الخلايا والأنسجة' => [
                                    'categories' => ['الخلايا'],
                                    'icon' => 'fas fa-search',
                                    'color' => 'secondary'
                                ],
                                'متفرقة' => [
                                    'categories' => ['متفرقة', 'أخرى'],
                                    'icon' => 'fas fa-list',
                                    'color' => 'dark'
                                ]
                            ];
                        @endphp
                        @php $groupIndex = 0; @endphp
                        @foreach($mainGroups as $mainGroupName => $mainGroupData)
                            @php $groupId = 'group_' . $groupIndex; $groupIndex++; @endphp
                            <div class="main-group-section mb-3">
                                <div class="main-group-header bg-{{ $mainGroupData['color'] }} text-white p-3 rounded-top d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#{{ $groupId }}" style="cursor: pointer;">
                                    <h5 class="mb-0 d-flex align-items-center">
                                        <i class="{{ $mainGroupData['icon'] }} me-2"></i>
                                        {{ $mainGroupName }}
                                        <span class="badge bg-white text-{{ $mainGroupData['color'] }} ms-2">
                                            @php
                                                $totalCount = 0;
                                                foreach($mainGroupData['categories'] as $cat) {
                                                    if(isset($grouped[$cat])) {
                                                        $totalCount += $grouped[$cat]->count();
                                                    }
                                                }
                                                echo $totalCount;
                                            @endphp
                                        </span>
                                    </h5>
                                    <i class="fas fa-chevron-down toggle-icon"></i>
                                </div>
                                <div id="{{ $groupId }}" class="collapse show main-group-body p-3 border border-top-0 rounded-bottom">
                                    <div class="row g-3">
                                        @foreach($mainGroupData['categories'] as $category)
                                            @if(isset($grouped[$category]) && $grouped[$category]->count() > 0)
                                                <div class="col-12">
                                                    <div class="sub-category-section mb-3 p-3 bg-light rounded">
                                                        <h6 class="text-primary mb-3 d-flex align-items-center">
                                                            <i class="{{ $categoryIcons[$category] ?? 'fas fa-list' }} me-2"></i>
                                                            {{ $categoryNames[$category] ?? ucfirst($category) }}
                                                            <span class="badge bg-primary ms-2">{{ $grouped[$category]->count() }}</span>
                                                        </h6>
                                                        <div class="row g-2">
                                                            @foreach($grouped[$category] as $test)
                                                            <div class="col-md-6 col-lg-4">
                                                                <div class="form-check test-item p-2 border rounded hover-shadow">
                                                                    <input class="form-check-input" type="checkbox" name="tests[]" value="{{ $test->name }}" id="test_{{ $test->id }}">
                                                                    <label class="form-check-label w-100" for="test_{{ $test->id }}">
                                                                        <div class="d-flex justify-content-between align-items-start">
                                                                            <div>
                                                                                <strong>{{ $test->name }}</strong>
                                                                                @if($test->description)
                                                                                    <br><small class="text-muted">{{ Str::limit($test->description, 50) }}</small>
                                                                                @endif
                                                                            </div>
                                                                            <i class="fas fa-check-circle text-success opacity-0 check-icon"></i>
                                                                        </div>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- قسم الأشعة (مخفي افتراضياً ويُعرض عند اختيار نوع الطلب = radiology) -->
                    <div class="card border-0 shadow-sm" id="radiologyTests" style="display: none;">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-x-ray me-2"></i>
                                فحوصات الأشعة المتاحة
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info border-0">
                                <i class="fas fa-info-circle me-2"></i>
                                اختر فحوصات الأشعة المطلوبة من القائمة أدناه
                            </div>
                            @if(isset($radiologyTypes) && $radiologyTypes->count() > 0)
                                <div class="row g-3">
                                    @foreach($radiologyTypes as $type)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="form-check radiology-item p-3 border rounded hover-shadow">
                                                <input class="form-check-input" type="checkbox" name="radiology_types[]" value="{{ $type->id }}" id="req_radiology_{{ $type->id }}">
                                                <label class="form-check-label w-100" for="req_radiology_{{ $type->id }}">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong>{{ $type->name }}</strong>
                                                            @if($type->description)
                                                                <br><small class="text-muted">{{ Str::limit($type->description, 60) }}</small>
                                                            @endif
                                                            @if($type->base_price)
                                                                <br><small class="text-success fw-bold">{{ number_format($type->base_price / 1000, 0) }} دينار</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    لا توجد فحوصات أشعة متاحة حالياً
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="submitRequestBtn">
                        <i class="fas fa-plus me-1"></i>
                        إضافة الطلب
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal لطلب الأشعة -->
<div class="modal fade" id="radiologyModal" tabindex="-1" aria-labelledby="radiologyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="radiologyModalLabel">
                    <i class="fas fa-x-ray me-2"></i>
                    طلب فحوصات الأشعة والتصوير
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('doctor.requests.store') }}" method="POST" id="radiologyRequestForm">
                @csrf
                <input type="hidden" name="visit_id" value="{{ $visit->id }}">
                <input type="hidden" name="type" value="radiology">
                <input type="hidden" name="priority" value="normal">
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        اختر فحوصات الأشعة المطلوبة من القائمة أدناه
                    </div>
                    @if(isset($radiologyTypes) && $radiologyTypes->count() > 0)
                        @php
                            $radiologyCategories = [
                                'أشعة عادية' => 'أشعة عادية (X-ray)',
                                'مقطعية' => 'أشعة مقطعية (CT Scan)',
                                'رنين مغناطيسي' => 'الرنين المغناطيسي (MRI)',
                                'موجات فوق صوتية' => 'الموجات فوق الصوتية (Ultrasound)',
                                'تصوير نسائي' => 'تصوير الثدي (Mammography)',
                                'أسنان' => 'أشعة الدينتال (Dental X-ray)',
                                'عظام' => 'أشعة العظام (Bone Scan)',
                                'أوعية دموية' => 'تصوير الأوعية الدموية (Angiography)'
                            ];
                        @endphp
                        @foreach($radiologyCategories as $categoryKey => $categoryName)
                            @php
                                $categoryTypes = $radiologyTypes->filter(function($type) use ($categoryName) {
                                    return str_contains($type->name, $categoryName);
                                });
                            @endphp
                            @if($categoryTypes->count() > 0)
                                <div class="radiology-category mb-4">
                                    <h6 class="text-primary mb-3 d-flex align-items-center">
                                        <i class="fas fa-folder-open me-2"></i>
                                        {{ $categoryKey }}
                                        <span class="badge bg-primary ms-2">{{ $categoryTypes->count() }}</span>
                                    </h6>
                                    <div class="row g-3">
                                        @foreach($categoryTypes as $type)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="form-check radiology-item p-3 border rounded hover-shadow">
                                                <input class="form-check-input" type="checkbox" name="radiology_types[]" value="{{ $type->id }}" id="radiology_{{ $type->id }}">
                                                <label class="form-check-label w-100" for="radiology_{{ $type->id }}">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong>{{ $type->name }}</strong>
                                                            @if($type->description)
                                                                <br><small class="text-muted">{{ Str::limit($type->description, 60) }}</small>
                                                            @endif
                                                            @if($type->base_price)
                                                                <br><small class="text-success fw-bold">{{ number_format($type->base_price / 1000, 0) }} دينار</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            لا توجد فحوصات أشعة متاحة حالياً
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-x-ray me-1"></i>
                        إرسال طلب الأشعة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // التبديل بين التحاليل والأشعة عبر أزرار الراديو
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const labCard = document.getElementById('labTests');
    const radiologyCard = document.getElementById('radiologyTests');

    function toggleRequestType(type) {
        if (!labCard || !radiologyCard) {
            console.error('Cards not found:', { labCard, radiologyCard });
            return;
        }
        
        if (type === 'radiology') {
            labCard.style.display = 'none';
            radiologyCard.style.display = 'block';
        } else {
            labCard.style.display = 'block';
            radiologyCard.style.display = 'none';
        }
    }

    // ربط الحدث لكل زر راديو
    typeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                toggleRequestType(this.value);
            }
        });
    });

    // تهيئة العرض الافتراضي (lab)
    toggleRequestType('lab');

    // التعامل مع المودال - إعادة تعيين النموذج عند الفتح
    const requestModal = document.getElementById('requestModal');
    if (requestModal) {
        requestModal.addEventListener('shown.bs.modal', function() {
            const form = document.getElementById('requestForm');
            if (form) {
                form.reset();
            }
            // إعادة تعيين الزر الافتراضي إلى lab
            const labRadio = document.getElementById('req_type_lab');
            if (labRadio) {
                labRadio.checked = true;
                toggleRequestType('lab');
            }
        });
    }

    // معالجة إرسال نموذج إضافة الطلب عبر AJAX
    const requestForm = document.getElementById('requestForm');
    const submitRequestBtn = document.getElementById('submitRequestBtn');

    if (requestForm && submitRequestBtn) {
        requestForm.addEventListener('submit', function(e) {
            e.preventDefault(); // منع الإرسال التقليدي

            // التحقق من اختيار فحوصات بناءً على نوع الطلب
            const requestFormData = new FormData(this);
            const selectedType = requestFormData.get('type');
            
            if (selectedType === 'radiology') {
                const checkedRad = this.querySelectorAll('input[name="radiology_types[]"]:checked');
                if (checkedRad.length === 0) {
                    alert('الرجاء اختيار فحص أشعة واحد على الأقل');
                    return;
                }
            } else {
                const checkedLab = this.querySelectorAll('input[name="tests[]"]:checked');
                if (checkedLab.length === 0) {
                    alert('الرجاء اختيار فحص مختبر واحد على الأقل');
                    return;
                }
            }

            // تعطيل الزر وإظهار حالة التحميل
            submitRequestBtn.disabled = true;
            submitRequestBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>جاري الإضافة...';

            fetch(this.action, {
                method: 'POST',
                body: requestFormData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // إغلاق المودال
                    const modal = bootstrap.Modal.getInstance(document.getElementById('requestModal'));
                    modal.hide();

                    // إعادة تحميل الصفحة لتحديث البيانات
                    location.reload();

                    // أو يمكن استخدام تنبيه نجاح
                    // showSuccessAlert('تم إضافة الطلب بنجاح!');
                } else {
                    // عرض رسائل الخطأ
                    if (data.errors) {
                        let errorMessage = 'حدثت أخطاء:\n';
                        for (let field in data.errors) {
                            errorMessage += '- ' + data.errors[field].join('\n') + '\n';
                        }
                        alert(errorMessage);
                    } else {
                        alert('حدث خطأ أثناء إضافة الطلب');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.');
            })
            .finally(() => {
                // إعادة تفعيل الزر
                submitRequestBtn.disabled = false;
                submitRequestBtn.innerHTML = '<i class="fas fa-plus me-1"></i>إضافة الطلب';
            });
        });
    }

    // معالجة حذف الطلبات عبر AJAX
    const deleteRequestForms = document.querySelectorAll('.delete-request-form');
    deleteRequestForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!confirm('هل أنت متأكد من إلغاء هذا الطلب؟')) {
                return;
            }

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');

            // تعطيل الزر
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>جاري الحذف...';

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // إعادة تحميل الصفحة لتحديث البيانات
                    location.reload();
                } else {
                    alert('حدث خطأ أثناء حذف الطلب');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.');
            })
            .finally(() => {
                // إعادة تفعيل الزر
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'إلغاء';
            });
        });
    });

    // معالجة إرسال نموذج طلب الأشعة عبر AJAX
    const radiologyForm = document.getElementById('radiologyRequestForm');
    if (radiologyForm) {
        radiologyForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');

            // التحقق من اختيار فحص واحد على الأقل
            const checkedBoxes = this.querySelectorAll('input[name="radiology_types[]"]:checked');
            if (checkedBoxes.length === 0) {
                alert('الرجاء اختيار فحص أشعة واحد على الأقل');
                return;
            }

            // تعطيل الزر وإظهار حالة التحميل
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>جاري الإرسال...';

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('radiologyModal'));
                    modal.hide();
                    location.reload();
                } else {
                    if (data.errors) {
                        let errorMessage = 'حدثت أخطاء:\n';
                        for (let field in data.errors) {
                            errorMessage += '- ' + data.errors[field].join('\n') + '\n';
                        }
                        alert(errorMessage);
                    } else {
                        alert('حدث خطأ أثناء إرسال الطلب');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-x-ray me-1"></i>إرسال طلب الأشعة';
            });
        });
    }

    // معالجة إنهاء الزيارة عبر AJAX
    const completeVisitForm = document.getElementById('completeVisitForm');

    if (completeVisitForm) {
        completeVisitForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!confirm('هل أنت متأكد من إنهاء هذه الزيارة؟ سيتم تغيير حالتها إلى مكتملة وستظهر في التاريخ.')) {
                return;
            }

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');

            // تعطيل الزر وإظهار حالة التحميل
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>جاري إنهاء الزيارة...';

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // إعادة تحميل الصفحة لتحديث البيانات
                    location.reload();
                } else {
                    // عرض رسائل الخطأ
                    if (data.errors) {
                        let errorMessage = 'حدثت أخطاء:\n';
                        for (let field in data.errors) {
                            errorMessage += '- ' + data.errors[field].join('\n') + '\n';
                        }
                        alert(errorMessage);
                    } else {
                        alert('حدث خطأ أثناء إنهاء الزيارة');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.');
            })
            .finally(() => {
                // إعادة تفعيل الزر
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check-circle me-1"></i>إنهاء الزيارة';
            });
        });
    }

// وظيفة البحث المباشر في قائمة ICD-10
document.getElementById('diagnosis_code').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase().trim();
    const datalist = document.getElementById('icd10-list');
    const options = datalist.querySelectorAll('option');

    if (searchTerm.length === 0) {
        // إظهار جميع الخيارات إذا لم يكن هناك بحث
        options.forEach(option => {
            option.style.display = 'block';
        });
        return;
    }

    let hasResults = false;
    let visibleCount = 0;
    const maxVisible = 10; // حد أقصى للنتائج المعروضة

    options.forEach(option => {
        const fullValue = option.value.toLowerCase();
        const searchData = (option.getAttribute('data-search') || '').toLowerCase();

        // البحث في القيمة الكاملة (رمز + وصف) والبيانات المساعدة
        const matches = fullValue.includes(searchTerm) || searchData.includes(searchTerm);

        if (matches && visibleCount < maxVisible) {
            option.style.display = 'block';
            hasResults = true;
            visibleCount++;
        } else {
            option.style.display = 'none';
        }
    });

    // إضافة تأثير بصري عند البحث
    if (searchTerm.length > 0) {
        this.classList.add('animate__animated', 'animate__pulse');
        setTimeout(() => {
            this.classList.remove('animate__animated', 'animate__pulse');
        }, 300);
    }

    // تحديث placeholder حسب النتائج
    if (hasResults) {
        this.style.borderColor = '#28a745'; // أخضر للنجاح
    } else if (searchTerm.length > 0) {
        this.style.borderColor = '#ffc107'; // أصفر للتحذير
    } else {
        this.style.borderColor = '#e9ecef'; // اللون الافتراضي
    }
});

// تحسين تجربة المستخدم - البحث التلقائي والتركيز
document.getElementById('diagnosis_code').addEventListener('focus', function() {
    // إضافة تأثير عند التركيز
    this.parentElement.classList.add('focused');

    // إذا كان فارغاً، أضف placeholder مشجع
    if (this.value === '') {
        this.placeholder = 'اكتب هنا للبحث في رموز ICD-10...';
    }
});

document.getElementById('diagnosis_code').addEventListener('blur', function() {
    // إزالة التأثير عند فقدان التركيز
    this.parentElement.classList.remove('focused');

    // إعادة الplaceholder الأصلي
    if (this.value === '') {
        this.placeholder = 'اكتب رمز أو وصف التشخيص...';
    }
});

// معالجة تغيير قيمة حقل التشخيص
document.getElementById('diagnosis_code').addEventListener('change', function() {
    const selectedValue = this.value;
    const datalist = document.getElementById('icd10-list');
    const options = datalist.querySelectorAll('option');
    const hiddenInput = document.getElementById('diagnosis_code_hidden');

    // البحث عن الخيار المحدد للحصول على الرمز الفعلي
    let actualCode = selectedValue; // افتراضياً نفس القيمة

    for (let option of options) {
        if (option.value === selectedValue) {
            actualCode = option.getAttribute('data-code') || selectedValue;
            break;
        }
    }

    // إذا لم نجد الخيار في datalist، حاول استخراج الرمز من القيمة المحددة
    if (actualCode === selectedValue && selectedValue.includes(' - ')) {
        actualCode = selectedValue.split(' - ')[0].trim();
    }

    // تحديث الحقل المخفي بالرمز الفعلي
    hiddenInput.value = actualCode;

    if (selectedValue && selectedValue !== '') {
        // إضافة تأثير نجاح
        this.classList.add('animate__animated', 'animate__bounceIn');
        setTimeout(() => {
            this.classList.remove('animate__animated', 'animate__bounceIn');
        }, 500);
    }

    const customCodeContainer = document.querySelector('.custom-code-container');
    if (actualCode === 'other') {
        customCodeContainer.style.display = 'block';
        customCodeContainer.classList.add('animate__animated', 'animate__fadeIn');
        setTimeout(() => {
            customCodeContainer.classList.remove('animate__animated', 'animate__fadeIn');
        }, 500);
    } else {
        customCodeContainer.classList.add('animate__animated', 'animate__fadeOut');
        setTimeout(() => {
            customCodeContainer.style.display = 'none';
            customCodeContainer.classList.remove('animate__animated', 'animate__fadeOut');
            document.getElementById('custom_code').value = '';
        }, 300);
    }
});
</script>

@php
$medicationCount = $prescribedMedications->count();
$treatmentCount = $otherTreatments->count();
@endphp

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize global variables
    window.medicationIndex = 0;
    window.treatmentIndex = 0;

    // Make functions global
    window.clearSavedData = function() {
        localStorage.removeItem('saved_medications');
        localStorage.removeItem('saved_treatments');
    };

    window.addMedication = function() {
    try {
        console.log('addMedication called');
        const container = document.getElementById('medicationsContainer');
        console.log('container:', container);
        if (!container) {
            console.error('Medications container not found');
            return;
        }
    const medicationHtml = `
        <div class="medication-item card mb-3 border-success">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">اسم الدواء</label>
                        <input type="text" class="form-control" name="prescribed_medications[${medicationIndex}][name]"
                               placeholder="اسم الدواء أو اختر من القائمة" list="commonMedications">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">نوع العلاج</label>
                        <select class="form-select" name="prescribed_medications[${medicationIndex}][type]">
                            <option value="tablet">حبوب</option>
                            <option value="injection">إبرة</option>
                            <option value="syrup">شراب</option>
                            <option value="cream">كريم</option>
                            <option value="drops">قطرات</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الجرعة</label>
                        <input type="text" class="form-control" name="prescribed_medications[${medicationIndex}][dosage]"
                               placeholder="مثال: 500mg">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label d-block mb-2">عدد المرات</label>
                        <div class="frequency-selector" style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <input type="radio" id="new_freq_${medicationIndex}_1" name="prescribed_medications[${medicationIndex}][frequency]" value="1" style="display: none;">
                            <label for="new_freq_${medicationIndex}_1" class="frequency-btn" style="padding: 6px 10px; border: 2px solid #e9ecef; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s ease; background: white;">مرة</label>

                            <input type="radio" id="new_freq_${medicationIndex}_2" name="prescribed_medications[${medicationIndex}][frequency]" value="2" style="display: none;">
                            <label for="new_freq_${medicationIndex}_2" class="frequency-btn" style="padding: 6px 10px; border: 2px solid #e9ecef; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s ease; background: white;">مرتين</label>

                            <input type="radio" id="new_freq_${medicationIndex}_3" name="prescribed_medications[${medicationIndex}][frequency]" value="3" style="display: none;">
                            <label for="new_freq_${medicationIndex}_3" class="frequency-btn" style="padding: 6px 10px; border: 2px solid #e9ecef; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s ease; background: white;">ثلاث</label>

                            <input type="radio" id="new_freq_${medicationIndex}_4" name="prescribed_medications[${medicationIndex}][frequency]" value="4" style="display: none;">
                            <label for="new_freq_${medicationIndex}_4" class="frequency-btn" style="padding: 6px 10px; border: 2px solid #e9ecef; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s ease; background: white;">أربع</label>

                            <input type="radio" id="new_freq_${medicationIndex}_needed" name="prescribed_medications[${medicationIndex}][frequency]" value="as_needed" style="display: none;">
                            <label for="new_freq_${medicationIndex}_needed" class="frequency-btn" style="padding: 6px 10px; border: 2px solid #e9ecef; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s ease; background: white;">عند الحاجة</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الأوقات</label>
                        <input type="text" class="form-control" name="prescribed_medications[${medicationIndex}][times]"
                               placeholder="صباح، مساء">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeMedication(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <label class="form-label">المدة</label>
                        <input type="text" class="form-control" name="prescribed_medications[${medicationIndex}][duration]"
                               placeholder="أيام">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">تعليمات خاصة</label>
                        <input type="text" class="form-control" name="prescribed_medications[${medicationIndex}][instructions]"
                               placeholder="تعليمات خاصة للمريض">
                    </div>
                </div>
            </div>
        </div>
    `;
    console.log('Inserting HTML:', medicationHtml);
    container.insertAdjacentHTML('beforeend', medicationHtml);
    console.log('HTML inserted, medicationIndex now:', medicationIndex);
    medicationIndex++;
    } catch (error) {
        console.error('Error in addMedication:', error);
    }
    }; // End of window.addMedication

    window.removeMedication = function(button) {
        button.closest('.medication-item').remove();
    };

    window.addOtherTreatment = function() {
        try {
            console.log('addOtherTreatment called');
            const container = document.getElementById('otherTreatmentsContainer');
        console.log('container:', container);
        if (!container) {
            console.error('Container not found');
            return;
        }
    const treatmentHtml = `
        <div class="treatment-item card mb-3 border-info">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">نوع العلاج</label>
                        <select class="form-select" name="prescribed_medications[other_treatments][${treatmentIndex}][type]">
                            <option value="">اختر النوع</option>
                            <option value="physical_therapy">علاج فيزيائي</option>
                            <option value="occupational_therapy">علاج وظيفي</option>
                            <option value="speech_therapy">علاج نطقي</option>
                            <option value="surgery">جراحة</option>
                            <option value="radiotherapy">علاج إشعاعي</option>
                            <option value="chemotherapy">علاج كيميائي</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">وصف العلاج</label>
                        <input type="text" class="form-control" name="prescribed_medications[other_treatments][${treatmentIndex}][description]"
                               placeholder="وصف العلاج المطلوب">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">المدة</label>
                        <input type="text" class="form-control" name="prescribed_medications[other_treatments][${treatmentIndex}][duration]"
                               placeholder="عدد الجلسات/الأيام">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">التكرار</label>
                        <input type="text" class="form-control" name="prescribed_medications[other_treatments][${treatmentIndex}][frequency]"
                               placeholder="يومياً، أسبوعياً">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTreatment(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', treatmentHtml);
    treatmentIndex++;
    } catch (error) {
        console.error('Error in addOtherTreatment:', error);
    }
}

function removeTreatment(button) {
    button.closest('.treatment-item').remove();
}

    // إدارة الأيقونات المنسدلة للمجموعات
    document.querySelectorAll('.main-group-header').forEach(header => {
        header.addEventListener('click', function() {
            const icon = this.querySelector('.toggle-icon');
            if (icon) {
                // الدوران سيتم عبر CSS باستخدام الكلاس collapsed
            }
        });
    });
    
    // البحث في التحاليل المخبرية
    const labSearchInput = document.getElementById('labSearchInput');
    const labCategoryFilter = document.getElementById('labCategoryFilter');
    
    if (labSearchInput) {
        labSearchInput.addEventListener('input', filterLabTests);
    }
    
    if (labCategoryFilter) {
        labCategoryFilter.addEventListener('change', filterLabTests);
    }
    
    function filterLabTests() {
        const searchTerm = labSearchInput ? labSearchInput.value.toLowerCase() : '';
        const selectedCategory = labCategoryFilter ? labCategoryFilter.value : '';
        
        const categories = document.querySelectorAll('.lab-category');
        
        categories.forEach(category => {
            const categoryName = category.getAttribute('data-category');
            const tests = category.querySelectorAll('.lab-test-item');
            let hasVisibleTests = false;
            
            // فلترة حسب الفئة
            if (selectedCategory && categoryName !== selectedCategory) {
                category.style.display = 'none';
                return;
            }
            
            // فلترة حسب البحث
            tests.forEach(test => {
                const testName = test.getAttribute('data-test-name');
                if (testName.includes(searchTerm)) {
                    test.style.display = 'block';
                    hasVisibleTests = true;
                } else {
                    test.style.display = 'none';
                }
            });
            
            // إخفاء الفئة إذا لم يكن فيها تحاليل ظاهرة
            category.style.display = hasVisibleTests ? 'block' : 'none';
        });
    }
    
    // عداد التحاليل المختارة
    const labCheckboxes = document.querySelectorAll('input[name="tests[]"]');
    const selectedLabCount = document.getElementById('selectedLabCount');
    const labCountNumber = document.getElementById('labCountNumber');
    
    labCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateLabCount);
    });
    
    function updateLabCount() {
        const checkedCount = document.querySelectorAll('input[name="tests[]"]:checked').length;
        if (labCountNumber) {
            labCountNumber.textContent = checkedCount;
        }
        if (selectedLabCount) {
            selectedLabCount.style.display = checkedCount > 0 ? 'block' : 'none';
        }
    }
    
    // ====== البحث والفلترة للأشعة ======
    const radiologySearchInput = document.getElementById('radiologySearchInput');
    const radiologyCategoryFilter = document.getElementById('radiologyCategoryFilter');
    
    if (radiologySearchInput) {
        radiologySearchInput.addEventListener('input', filterRadiologyTypes);
    }
    
    if (radiologyCategoryFilter) {
        radiologyCategoryFilter.addEventListener('change', filterRadiologyTypes);
    }
    
    function filterRadiologyTypes() {
        const searchTerm = radiologySearchInput ? radiologySearchInput.value.toLowerCase() : '';
        const selectedCategory = radiologyCategoryFilter ? radiologyCategoryFilter.value : '';
        
        const categories = document.querySelectorAll('.radiology-category');
        
        categories.forEach(category => {
            const categoryName = category.getAttribute('data-category');
            const types = category.querySelectorAll('.radiology-type-item');
            let hasVisibleTypes = false;
            
            // فلترة حسب الفئة
            if (selectedCategory && categoryName !== selectedCategory) {
                category.style.display = 'none';
                return;
            }
            
            // فلترة حسب البحث
            types.forEach(type => {
                const typeName = type.getAttribute('data-type-name');
                if (typeName.includes(searchTerm)) {
                    type.style.display = 'block';
                    hasVisibleTypes = true;
                } else {
                    type.style.display = 'none';
                }
            });
            
            // إخفاء الفئة إذا لم يكن فيها فحوصات ظاهرة
            category.style.display = hasVisibleTypes ? 'block' : 'none';
        });
    }
    
    // عداد فحوصات الأشعة المختارة
    const radiologyCheckboxes = document.querySelectorAll('.radiology-checkbox');
    const selectedRadiologyCount = document.getElementById('selectedRadiologyCount');
    const radiologyCountNumber = document.getElementById('radiologyCountNumber');
    
    radiologyCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateRadiologyCount);
    });
    
    function updateRadiologyCount() {
        const checkedCount = document.querySelectorAll('.radiology-checkbox:checked').length;
        if (radiologyCountNumber) {
            radiologyCountNumber.textContent = checkedCount;
        }
        if (selectedRadiologyCount) {
            selectedRadiologyCount.style.display = checkedCount > 0 ? 'block' : 'none';
        }
    }
    
    // ====== أزرار "تحديد الكل" للتحاليل ======
    document.querySelectorAll('.select-all-category').forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            const categoryDiv = this.closest('.lab-category');
            const checkboxes = categoryDiv.querySelectorAll('input[name="tests[]"]');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
            
            // تحديث النص والأيقونة
            if (allChecked) {
                this.innerHTML = '<i class="fas fa-check-double me-1"></i>تحديد الكل';
                this.classList.remove('btn-success');
                this.classList.add('btn-outline-primary');
            } else {
                this.innerHTML = '<i class="fas fa-times me-1"></i>إلغاء الكل';
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-success');
            }
            
            updateLabCount();
        });
    });
    
    // ====== أزرار "تحديد الكل" للأشعة ======
    document.querySelectorAll('.select-all-radiology').forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            const categoryDiv = this.closest('.radiology-category');
            const checkboxes = categoryDiv.querySelectorAll('.radiology-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
            
            // تحديث النص والأيقونة
            if (allChecked) {
                this.innerHTML = '<i class="fas fa-check-double me-1"></i>تحديد الكل';
                this.classList.remove('btn-success');
                this.classList.add('btn-outline-info');
            } else {
                this.innerHTML = '<i class="fas fa-times me-1"></i>إلغاء الكل';
                this.classList.remove('btn-outline-info');
                this.classList.add('btn-success');
            }
            
            updateRadiologyCount();
        });
    });
}); // End of DOMContentLoaded

function confirmSurgeryReferral() {
    const notes = document.getElementById('surgery_notes').value.trim();
    if (!notes) {
        alert('يرجى إدخال ملاحظات العملية المطلوبة');
        return false;
    }
    return confirm('هل أنت متأكد من تحويل المريض للاستعلامات لحجز عملية؟');
}
</script>

<style>
/* تحسينات المجموعات الرئيسية */
.main-group-section {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
    margin-bottom: 2rem;
}

.main-group-section:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.main-group-header {
    border-bottom: none;
    font-weight: 600;
}

.main-group-body {
    background: #f8f9fa;
}

.sub-category-section {
    border-left: 3px solid #007bff;
    background: white !important;
}

.hover-shadow:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}
</style>

<!-- Modal تحويل المريض لحجز عملية -->
<div class="modal fade" id="surgeryReferralModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-procedures me-2"></i>
                    تحويل المريض لحجز عملية جراحية
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('doctor.visits.mark-needs-surgery', $visit) }}" method="POST" onsubmit="return confirmSurgeryReferral()">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        سيتم تحويل المريض للاستعلامات لحجز العملية بعد إكمال الإجراءات المطلوبة (الدفع - التحاليل - الأشعة)
                    </div>
                    <div class="mb-3">
                        <label for="surgery_notes" class="form-label">ملاحظات العملية المطلوبة <span class="text-danger">*</span></label>
                        <textarea name="surgery_notes" id="surgery_notes" class="form-control" rows="4" required 
                                  placeholder="مثال: استئصال الزائدة الدودية - تحليل CBC ووظائف كلى مطلوبة قبل العملية"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-paper-plane me-1"></i>
                        تحويل للاستعلامات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection