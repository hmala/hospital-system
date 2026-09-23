@extends('layouts.app')

@section('styles')
<style>
body {
    background-color: #f1f5f9 !important;
    color: #0f172a;
    font-size: 0.88rem;
}

/* كروت مدمجة وأنيقة */
.compact-card {
    background: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04) !important;
    margin-bottom: 12px;
    overflow: hidden;
}

.compact-card-header {
    background: #ffffff !important;
    border-bottom: 1px solid #e2e8f0 !important;
    padding: 8px 14px !important;
    cursor: pointer;
    user-select: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.compact-card-header:hover {
    background: #f8fafc !important;
}

.compact-card-body {
    padding: 12px 14px !important;
    background: #ffffff !important;
}

/* تصغير وتنسيق الحقول بدقة وتباعد عالي */
.form-label {
    color: #1e293b !important;
    font-weight: 700 !important;
    font-size: 0.82rem !important;
    margin-bottom: 3px !important;
}

.form-label .text-danger {
    font-size: 0.9rem;
}

#surgeryForm .form-control,
#surgeryForm .form-select {
    background-color: #ffffff !important;
    border: 1.5px solid #94a3b8 !important;
    border-radius: 5px !important;
    color: #0f172a !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;
    padding: 0.3rem 0.55rem !important;
    min-height: 34px !important;
    height: 34px !important;
    transition: border-color 0.2s, box-shadow 0.2s !important;
}

#surgeryForm .form-control:focus,
#surgeryForm .form-select:focus {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 2.5px rgba(37, 99, 235, 0.15) !important;
    outline: none !important;
}

/* تنسيق Select2 المدمج */
.select2-container--bootstrap-5 .select2-selection {
    background-color: #ffffff !important;
    border: 1.5px solid #94a3b8 !important;
    border-radius: 5px !important;
    min-height: 34px !important;
    height: 34px !important;
    display: flex !important;
    align-items: center !important;
    padding: 0.15rem 0.45rem !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;
    color: #0f172a !important;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    color: #0f172a !important;
    font-weight: 700 !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    line-height: 30px !important;
}

.select2-container--bootstrap-5.select2-container--focus .select2-selection,
.select2-container--bootstrap-5.select2-container--open .select2-selection {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 2.5px rgba(37, 99, 235, 0.15) !important;
}

.select2-dropdown {
    border: 1.5px solid #2563eb !important;
    border-radius: 6px !important;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.12) !important;
    font-size: 0.84rem !important;
    z-index: 1060 !important;
}

.select2-results__options {
    max-height: 200px !important;
    overflow-y: auto !important;
}

.select2-results__options::-webkit-scrollbar {
    width: 6px;
}
.select2-results__options::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 4px;
}
.select2-results__options::-webkit-scrollbar-thumb:hover {
    background-color: #94a3b8;
}

.select2-results__group {
    padding: 4px 10px !important;
    font-size: 0.74rem !important;
    background: #f1f5f9 !important;
    color: #475569 !important;
    font-weight: 700 !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-top: 1px solid #e2e8f0 !important;
}

.select2-results__option {
    padding: 6px 8px !important;
    font-weight: 600 !important;
    color: #0f172a !important;
    border-bottom: 1px solid #f1f5f9 !important;
}

.select2-results__option--highlighted[aria-selected] {
    background-color: #2563eb !important;
    color: #ffffff !important;
}

.select2-new-tag {
    background: #f0fdf4;
    padding: 5px 8px;
    border-radius: 4px;
    border: 1.5px dashed #16a34a;
    margin: 2px 0;
    font-size: 0.82rem;
}

/* تبويبات وأزرار الغرف الميكرو */
.nav-pills .nav-link {
    border-radius: 5px;
    color: #475569;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    font-size: 0.8rem;
    padding: 3px 8px;
    transition: all 0.2s ease;
}
.nav-pills .nav-link.active {
    background-color: #2563eb !important;
    color: #ffffff !important;
    border-color: #2563eb;
}

.room-tile {
    width: 54px;
    height: 40px;
    border-width: 2px;
    border-style: solid;
    border-color: #dee2e6;
    border-radius: 5px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    background: #ffffff;
    transition: all 0.15s ease;
    margin: 2px;
    padding: 1px;
}

.room-tile:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}

.room-number {
    font-weight: 800;
    font-size: 0.82rem;
    text-align: center;
    line-height: 1;
}

.room-badges { position: absolute; top: 1px; right: 2px; }
.room-status { position: absolute; bottom: 2px; left: 2px; }
.room-actions { position: absolute; top: 1px; left: 2px; }
.status-dot { width: 5px; height: 5px; border-radius: 50%; display: inline-block; }
.room-selectable { cursor: pointer; }
.room-selectable[data-available="0"] { cursor: not-allowed; opacity: 0.45; }

.accordion-toggle-icon {
    transition: transform 0.2s ease;
}
.collapsed .accordion-toggle-icon {
    transform: rotate(-90deg);
}

/* جدول العمليات الجراحية الموحد */
#surgicalOperationsTable {
    table-layout: fixed;
    width: 100%;
}
#surgicalOperationsTable th {
    vertical-align: middle;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 8px 10px;
    background-color: #f1f5f9;
    border-bottom: 2px solid #cbd5e1;
}
#surgicalOperationsTable td {
    vertical-align: middle;
    padding: 6px 8px;
}
#surgicalOperationsTable .select2-container {
    width: 100% !important;
}
#surgicalOperationsTable .select2-selection--single {
    height: 31px !important;
    padding: 2px 8px !important;
    font-size: 0.85rem !important;
    border-radius: 4px !important;
    display: flex !important;
    align-items: center !important;
}
#surgicalOperationsTable .select2-selection__rendered {
    line-height: 27px !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
}
#surgicalOperationsTable .select2-selection__arrow {
    height: 29px !important;
}
</style>
@endsection

@php
    $categoriesList = $surgicalOperations->pluck('category')->filter()->unique()->values();
@endphp
@section('content')
<div class="container-fluid py-2">
    <!-- ترويسة مدمجة -->
    <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
        <h1 class="h5 mb-0 text-dark fw-bold">
            <i class="fas fa-edit text-warning me-2"></i>
            تعديل حجز العملية الجراحية #{{ $surgery->id }}
        </h1>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" id="toggleAllSectionsBtn" style="font-size:0.8rem;">
                <i class="fas fa-layer-group me-1"></i> طي / توسيع
            </button>
            <a href="{{ route('surgeries.index') }}" class="btn btn-xs btn-outline-secondary py-1 px-2" style="font-size:0.8rem;">
                <i class="fas fa-arrow-left me-1"></i> العمليات
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-2 py-2" role="alert">
            <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-1"></i> تعذر تحديث العملية بسبب الأخطاء التالية:</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- نموذج تعديل العملية المدمج كلياً في كارتين -->
    <form action="{{ route('surgeries.update', $surgery) }}" method="POST" id="surgeryForm" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @php
            $currentOp = $surgicalOperations->firstWhere('name', $surgery->surgery_type);
            $selectedOpId = old('surgical_operation_id', optional($currentOp)->id);
            $selectedCategory = old('surgery_category', $surgery->surgery_category ?: optional($currentOp)->category);
            $selectedFee = old('custom_surgery_fee', number_format($surgery->surgery_fee ?: ($surgery->custom_surgery_fee ?: optional($currentOp)->fee)));
            $selectedRoomId = old('room_id', $surgery->room_id);
            $selectedStayDays = old('expected_stay_days', $surgery->expected_stay_days ?: 1);
            $currentRoom = $rooms->firstWhere('id', $selectedRoomId);
            $currentRoomFee = $currentRoom ? $currentRoom->daily_fee : 0;
            $currentTotalRoomFee = $currentRoomFee * $selectedStayDays;
        @endphp

        <!-- الكارت 1: دمج بيانات المريض، العملية، والفريق الطبي -->
        <div class="compact-card">
            <div class="compact-card-header" data-bs-toggle="collapse" data-bs-target="#collapseSurgeryInfo" aria-expanded="true">
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary rounded-circle p-1 me-2" style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;">1</span>
                    <h6 class="mb-0 fw-bold text-dark" style="font-size:0.9rem;">
                        <i class="fas fa-notes-medical text-primary me-1"></i>
                        بيانات المريض، العملية، والفريق الطبي
                    </h6>
                </div>
                <i class="fas fa-chevron-down text-muted accordion-toggle-icon"></i>
            </div>
            <div id="collapseSurgeryInfo" class="collapse show">
                <div class="compact-card-body">
                    <!-- سطر ملخص المريض المختار -->
                    <div id="selectedPatientInfo" class="mb-2">
                        <div class="bg-light border rounded px-2 py-1 text-dark small d-flex justify-content-between align-items-center" style="font-size:0.82rem;">
                            <div>
                                <span class="badge bg-primary me-1"><i class="fas fa-id-card"></i></span>
                                <strong id="patientNameHeader" class="text-primary me-2">{{ optional(optional($surgery->patient)->user)->name ?? '-' }}</strong>
                                <span class="text-muted me-2">العمر: <b id="patientAge">{{ optional($surgery->patient)->age ?? '-' }} سنة</b></span>
                                <span class="text-muted me-2">الجنس: <b id="patientGender">{{ optional($surgery->patient)->gender ?? '-' }}</b></span>
                                <span class="text-muted">الهاتف: <b id="patientPhone" dir="ltr">{{ optional(optional($surgery->patient)->user)->phone ?? '-' }}</b></span>
                            </div>
                            <span class="badge bg-success py-1"><i class="fas fa-check me-1"></i> تم التحميل</span>
                        </div>
                    </div>

                    <!-- السطر 1: البيانات الأساسية (المريض، الجراح، المرسل) -->
                    <div class="row g-2 mb-2">
                        <!-- المريض -->
                        <div class="col-md-4">
                            <label for="patient_id" class="form-label">
                                المريض <span class="text-danger">*</span>
                            </label>
                            <select name="patient_id" id="patient_id" class="form-select @error('patient_id') is-invalid @enderror" required>
                                <option value="">اختر المريض</option>
                                @foreach($patients as $patient)
                                    @php 
                                        $patientName = optional($patient->user)->name ?? 'غير معروف';
                                        $patientAge = optional($patient)->age ?? '-';
                                        $patientGender = optional($patient)->gender ?? '-';
                                        $patientPhone = optional($patient->user)->phone ?? '-';
                                        $patientInsuranceType = $patient->insurance_type ?? 'none';
                                        $patientInsuranceLabel = $patientInsuranceType === 'moi' ? 'ضمان قوى الأمن الداخلي (وزارة الداخلية)' : ($patientInsuranceType === 'hi' ? 'هيئة الضمان الصحي الوطني' : 'بدون ضمان');
                                        $patientCardNo = $patient->insurance_card_no ?: $patient->insurance_booklet_number ?: '';
                                        $patientData = [
                                            'id' => $patient->id,
                                            'name' => $patientName,
                                            'age' => $patientAge,
                                            'gender' => $patientGender,
                                            'phone' => $patientPhone,
                                            'insurance_type' => $patientInsuranceType,
                                            'insurance_label' => $patientInsuranceLabel,
                                            'insurance_card_no' => $patientCardNo
                                        ];
                                    @endphp
                                    <option value="{{ $patient->id }}" 
                                            data-patient='@json($patientData)'
                                            {{ (old('patient_id', $surgery->patient_id) == $patient->id) ? 'selected' : '' }}>
                                        {{ $patientName }} ({{ $patientAge }} سنة)
                                    </option>
                                @endforeach
                            </select>
                            @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- الطبيب الجراح -->
                        <div class="col-md-4">
                            <label for="doctor_id" class="form-label">
                                الطبيب الجراح <span class="text-danger">*</span>
                            </label>
                            <select name="doctor_id" id="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror" style="width: 100%;" required>
                                <option value="">اختر الجراح</option>
                                @foreach($doctors as $doctor)
                                    @php $doctorName = optional($doctor->user)->name ?? 'غير معروف'; @endphp
                                    <option value="{{ $doctor->id }}" {{ (old('doctor_id', $surgery->doctor_id) == $doctor->id) ? 'selected' : '' }}>
                                        د. {{ $doctorName }}
                                    </option>
                                @endforeach
                            </select>
                            @error('doctor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- الطبيب المرسل -->
                        <div class="col-md-4">
                            <label class="form-label">
                                الطبيب المرسل <span class="text-danger">*</span>
                            </label>
                            <select name="referring_doctor_name" id="referring_doctor_name_select" class="form-select @error('referring_doctor_name') is-invalid @enderror" style="width: 100%;" required>
                                <option value="">اختر الطبيب المرسل أو اكتب اسماً جديداً</option>
                                <optgroup label="أطباء المستشفى">
                                    @foreach($doctors as $doctor)
                                        @php $referringDoctorName = optional($doctor->user)->name ?? ''; @endphp
                                        <option value="{{ $referringDoctorName }}" {{ (old('referring_doctor_name', $surgery->referring_doctor_name) == $referringDoctorName) ? 'selected' : '' }}>
                                            د. {{ $referringDoctorName ?: 'غير معروف' }}
                                        </option>
                                    @endforeach
                                </optgroup>
                                @php
                                    $externalDoctors = \App\Models\Surgery::whereNotNull('referring_doctor_name')
                                        ->distinct()
                                        ->pluck('referring_doctor_name')
                                        ->filter()
                                        ->unique()
                                        ->reject(function($name) use ($doctors) {
                                            return $doctors->pluck('user.name')->contains($name);
                                        })
                                        ->sort();
                                @endphp
                                @if($externalDoctors->isNotEmpty())
                                    <optgroup label="أطباء خارجيين سابقين">
                                        @foreach($externalDoctors as $externalDoctor)
                                            <option value="{{ $externalDoctor }}" {{ (old('referring_doctor_name', $surgery->referring_doctor_name) == $externalDoctor) ? 'selected' : '' }}>
                                                {{ $externalDoctor }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if($surgery->referring_doctor_name && !$doctors->pluck('user.name')->contains($surgery->referring_doctor_name) && !$externalDoctors->contains($surgery->referring_doctor_name))
                                    <option value="{{ $surgery->referring_doctor_name }}" selected>{{ $surgery->referring_doctor_name }}</option>
                                @endif
                            </select>
                            @error('referring_doctor_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <!-- السطر 2: تاريخ ومستند العملية -->
                    <div class="row g-2 mb-3 align-items-center">
                        <!-- تاريخ العملية -->
                        <div class="col-md-4">
                            <label for="scheduled_date" class="form-label">
                                تاريخ العملية <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="scheduled_date" id="scheduled_date" 
                                   class="form-control @error('scheduled_date') is-invalid @enderror" 
                                   value="{{ old('scheduled_date', $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                            @error('scheduled_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- ورقة التحويل الطبي بالسكانر -->
                        <div class="col-md-8">
                            <label class="form-label text-muted">ورقة التحويل الطبي (سكانر / رفع ملف)</label>
                            <div class="d-flex gap-1" id="referral_letter_container">
                                <button type="button" class="btn btn-sm btn-primary flex-grow-1" id="scan_btn" onclick="scanFromDevice()" style="height:34px;">
                                    <i class="fas fa-print me-1"></i> مسح بالسكانر
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('referral_letter').click()" style="height:34px;">
                                    <i class="fas fa-upload me-1"></i> رفع ملف
                                </button>
                                <input type="file" name="referral_letter" id="referral_letter" class="d-none @error('referral_letter') is-invalid @enderror" accept="image/*,application/pdf">
                                <textarea id="scan_data_receiver" name="scanned_referral_letter" style="display:none;"></textarea>
                            </div>
                            <!-- معاينة الوثيقة الممسوحة -->
                            <div id="referral_letter_preview" class="mt-2" style="{{ $surgery->referral_letter_path ? '' : 'display: none;' }}">
                                <div class="alert alert-success py-1 px-2 d-flex justify-content-between align-items-center mb-0 small">
                                    <span id="preview_text">
                                        <i class="fas fa-check-circle me-1"></i> 
                                        @if($surgery->referral_letter_path)
                                            <a href="{{ asset('storage/' . $surgery->referral_letter_path) }}" target="_blank" class="text-success fw-bold text-decoration-underline">
                                                عرض ورقة التحويل الحالية
                                            </a>
                                        @else
                                            تم مسح/رفع ورقة التحويل بنجاح
                                        @endif
                                    </span>
                                    <button type="button" class="btn btn-xs btn-outline-danger py-0" onclick="clearScannedDoc()">
                                        <i class="fas fa-times me-1"></i> إلغاء
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- السطر 3: بطاقة العمليات الجراحية الموحدة بالكامل -->
                    <div class="card mb-3 border-0 rounded-3 shadow-none overflow-hidden" style="border: 1.5px solid #0284c7 !important; background-color: #f8fafc;">
                        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary text-white rounded-pill px-2 py-1" style="font-size: 0.8rem;">
                                    <i class="fas fa-procedures me-1"></i>العمليات
                                </span>
                                <span class="fw-bold text-dark" style="font-size: 0.9rem;">
                                    جدول العمليات الجراحية المطلوبة
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small">عدد العمليات: <strong id="totalOpsCount" class="text-primary">1</strong></span>
                                <span id="summaryTotalFee" class="badge bg-success px-3 py-1 fs-7">0 د.ع</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle mb-0" id="surgicalOperationsTable" style="min-width: 860px;">
                                    <thead>
                                        <tr class="text-secondary text-center small">
                                            <th style="width: 100px;">العملية</th>
                                            <th style="width: 23%;">صنف العملية <span class="text-danger">*</span></th>
                                            <th style="width: 27%;">نوع العملية <span class="text-danger">*</span></th>
                                            <th style="width: 160px;">السعر (د.ع) <span class="text-danger">*</span></th>
                                            <th>ملاحظات / توصيف</th>
                                            <th style="width: 75px;">إجراء</th>
                                        </tr>
                                    </thead>
                                    <tbody id="operationsTableBody">
                                        <!-- العملية (1) - العملية الأولى الأساسية -->
                                        <tr id="op_row_primary" class="align-middle bg-white">
                                            <td class="text-center bg-light">
                                                <span class="badge bg-primary text-white px-2 py-1" style="font-size: 0.8rem;">العملية (1)</span>
                                            </td>
                                            <td>
                                                <select name="surgery_category" id="surgery_category" class="form-select form-select-sm op-category-select @error('surgery_category') is-invalid @enderror" required style="width: 100%;">
                                                    <option value="">-- اختر الصنف --</option>
                                                    @foreach($categoriesList as $category)
                                                        <option value="{{ $category }}" {{ $selectedCategory == $category ? 'selected' : '' }}>
                                                            {{ $category }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('surgery_category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>
                                            <td>
                                                <select name="surgical_operation_id" id="surgical_operation_id" class="form-select form-select-sm op-name-select @error('surgical_operation_id') is-invalid @enderror" required style="width: 100%;">
                                                    <option value="">-- اختر العملية --</option>
                                                    @foreach($surgicalOperations as $operation)
                                                        <option value="{{ $operation->id }}" 
                                                                data-category="{{ $operation->category }}"
                                                                data-fee="{{ $operation->fee }}"
                                                                {{ $selectedOpId == $operation->id ? 'selected' : '' }}>
                                                            {{ $operation->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('surgical_operation_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" 
                                                           name="custom_surgery_fee" 
                                                           id="custom_surgery_fee" 
                                                           class="form-control form-control-sm op-fee-input text-end fw-bold @error('custom_surgery_fee') is-invalid @enderror" 
                                                           value="{{ $selectedFee }}"
                                                           placeholder="0"
                                                           inputmode="numeric"
                                                           required>
                                                    <span class="input-group-text small px-1 bg-light text-muted" style="font-size: 0.72rem;">د.ع</span>
                                                </div>
                                                @error('custom_surgery_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>
                                            <td>
                                                <input type="text" 
                                                       name="description" 
                                                       id="description" 
                                                       class="form-control form-control-sm" 
                                                       value="{{ old('description', $surgery->description) }}"
                                                       placeholder="ملاحظات العملية الأولى...">
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" title="العملية (1) أساسية مطلوبة">
                                                    <i class="fas fa-lock me-1"></i> أساسية
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- زر الإضافة المباشر أسفل الجدول مع الملخص المالي -->
                            <div class="p-2 px-3 bg-light border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-primary fw-bold px-3 py-1 shadow-xs" id="addAnotherOpBtn">
                                    <i class="fas fa-plus-circle me-1"></i> ➕ إضافة عملية أخرى
                                </button>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="text-muted small">عدد العمليات: <strong id="totalOpsCountBottom" class="text-primary">1</strong></span>
                                    <div class="d-flex align-items-center">
                                        <span class="text-dark small me-2 fw-bold">إجمالي سعر العمليات:</span>
                                        <span id="summaryTotalFeeBottom" class="badge bg-success fs-7 px-3 py-1">0 د.ع</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الكارت 2: حجز الغرفة والإقامة الإجباري -->
        <div class="compact-card">
            <div class="compact-card-header" data-bs-toggle="collapse" data-bs-target="#collapseRoomStay" aria-expanded="true">
                <div class="d-flex align-items-center">
                    <span class="badge bg-warning text-dark rounded-circle p-1 me-2" style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;">2</span>
                    <h6 class="mb-0 fw-bold text-dark" style="font-size:0.9rem;">
                        <i class="fas fa-door-open text-warning me-1"></i>
                        اختيار الغرفة والإقامة <span class="text-danger">*</span>
                    </h6>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span id="header_room_badge" class="badge {{ $currentRoom ? 'bg-success text-white' : 'bg-light text-muted border' }}" style="font-size:0.75rem;">
                        {{ $currentRoom ? 'غرفة ' . $currentRoom->room_number : 'لم يتم الاختيار' }}
                    </span>
                    <i class="fas fa-chevron-down text-muted accordion-toggle-icon"></i>
                </div>
            </div>
            <div id="collapseRoomStay" class="collapse show">
                <div class="compact-card-body">
                    <input type="hidden" name="expected_stay_days" id="expected_stay_days" value="1">
                    <div class="row g-2 mb-2 align-items-center">
                        <div class="col-md-9">
                            <div class="px-3 py-2 bg-light rounded border d-flex justify-content-between align-items-center small flex-wrap gap-2">
                                <div>
                                    <span class="text-muted">الغرفة:</span> <b id="selected_room_name" class="text-dark">{{ $currentRoom ? 'غرفة ' . $currentRoom->room_number . ($currentRoom->room_type === 'vvip' ? ' (💎 VVIP)' : ($currentRoom->room_type === 'vip' ? ' (⭐ VIP)' : ' (عادية)')) : 'لم يتم الاختيار' }}</b>
                                    <span class="mx-2 text-muted">|</span>
                                    <span class="text-muted">الحالة:</span> <b id="selected_room_status" class="text-success">{{ $currentRoom ? 'محددة' : '-' }}</b>
                                </div>
                                <div>
                                    <span class="text-muted">أجرة الليلة الأولى:</span> 
                                    <b id="room_total_fee" class="badge {{ $currentRoom && $currentRoom->room_type === 'vvip' ? 'bg-dark text-white' : ($currentRoom && $currentRoom->room_type === 'vip' ? 'bg-warning text-dark' : 'bg-success text-white') }} fs-6 ms-1">
                                        {{ $currentRoom && $currentRoom->room_type === 'vvip' ? '200,000 د.ع (VVIP)' : ($currentRoom && $currentRoom->room_type === 'vip' ? '100,000 د.ع (VIP)' : '0 د.ع (مجانية أول ليلة)') }}
                                    </b>
                                </div>
                            </div>
                            <div class="mt-1 text-muted small" style="font-size: 0.76rem;">
                                <i class="fas fa-info-circle text-primary me-1"></i>
                                <strong>النظام الفندقي:</strong> الليلة الأولى تغطي حتى <strong>12:00 ظهراً</strong> من اليوم التالي. أي ليالٍ إضافية تُحسب تلقائياً عند إجراء الخروج (عادية: مجانية أول ليلة | VIP: 100,000 د.ع | VVIP: 200,000 د.ع).
                            </div>
                        </div>
                        <div class="col-md-3 text-end" id="clear_room_section" style="{{ $currentRoom ? '' : 'display: none;' }}">
                            <button type="button" class="btn btn-sm btn-outline-danger w-100" id="clear_room_btn" style="height:38px;">
                                <i class="fas fa-times me-1"></i> إلغاء اختيار الغرفة
                            </button>
                        </div>
                    </div>

                    <!-- دليل ألوان مدمج -->
                    <div class="d-flex gap-3 py-1 mb-2 small text-muted border-top border-bottom" style="font-size:0.75rem;">
                        <div><span class="badge bg-success me-1" style="width:7px;height:7px;display:inline-block;padding:0;"></span> متاحة</div>
                        <div><span class="badge bg-danger me-1" style="width:7px;height:7px;display:inline-block;padding:0;"></span> محجوزة</div>
                        <div><span class="badge bg-warning me-1" style="width:7px;height:7px;display:inline-block;padding:0;"></span> صيانة</div>
                        <div><span class="badge bg-warning text-dark me-1" style="width:7px;height:7px;display:inline-block;padding:0;"></span> VIP</div>
                    </div>

                    <input type="hidden" name="room_id" id="room_id" value="{{ $selectedRoomId }}">

                    <!-- رسالة خطأ عند عدم اختيار غرفة -->
                    <div id="room_validation_error" class="alert alert-danger py-1 mb-2 d-none fw-bold" style="border-right: 4px solid #dc3545; font-size:0.82rem;">
                        <i class="fas fa-exclamation-triangle me-1"></i> يرجى النقر على إحدى الغرف المتاحة (باللون الأخضر) لاختيارها للمريض قبل الحجز.
                    </div>

                    <!-- تبويبات الطوابق المدمجة لاختيار الغرفة بأقل مساحة -->
                    @php $roomsByFloor = $rooms->groupBy('floor'); @endphp
                    @if($roomsByFloor->isNotEmpty())
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <ul class="nav nav-pills gap-1" id="floorPills" role="tablist">
                            @foreach($roomsByFloor as $floor => $floorRooms)
                            @php
                                $hasCurrentRoom = $floorRooms->contains('id', $selectedRoomId);
                                $isFirst = $loop->first;
                            @endphp
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ ($hasCurrentRoom || (!$rooms->contains('id', $selectedRoomId) && $isFirst)) ? 'active' : '' }}" 
                                        id="pills-floor-{{ $loop->index }}-tab" 
                                        data-bs-toggle="pill" 
                                        data-bs-target="#pills-floor-{{ $loop->index }}" 
                                        type="button" role="tab">
                                    <i class="fas fa-layer-group me-1"></i> {{ $floor ?: 'عام' }}
                                    <span class="badge bg-secondary bg-opacity-75 ms-1" style="font-size:0.65rem;">{{ $floorRooms->count() }}</span>
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="tab-content" id="floorPillsContent">
                        @foreach($roomsByFloor as $floor => $floorRooms)
                        @php
                            $hasCurrentRoom = $floorRooms->contains('id', $selectedRoomId);
                            $isFirst = $loop->first;
                        @endphp
                        <div class="tab-pane fade {{ ($hasCurrentRoom || (!$rooms->contains('id', $selectedRoomId) && $isFirst)) ? 'show active' : '' }}" id="pills-floor-{{ $loop->index }}" role="tabpanel">
                            <div class="d-flex flex-wrap gap-1 p-1 bg-light rounded border">
                                @foreach($floorRooms as $room)
                                @php
                                    $isThisRoom = ($selectedRoomId == $room->id);
                                    $borderHex = match($room->status) {
                                        'available'   => '#16a34a',
                                        'occupied'    => ($isThisRoom ? '#0d6efd' : '#dc2626'),
                                        'maintenance' => '#d97706',
                                        default       => '#64748b'
                                    };
                                    $typeBg = $room->room_type === 'vvip' ? 'bg-dark bg-opacity-10 border-dark' : ($room->room_type === 'vip' ? 'bg-warning bg-opacity-10' : '');
                                    $isAvailable = ($room->status === 'available' || $isThisRoom);
                                @endphp
                                <div class="room-tile room-selectable {{ $typeBg }} {{ !$room->is_active ? 'opacity-50' : '' }}"
                                     data-room-id="{{ $room->id }}"
                                     data-room-fee="{{ $room->daily_fee }}"
                                     data-room-type="{{ $room->room_type }}"
                                     data-room-number="{{ $room->room_number }}"
                                     data-available="{{ $isAvailable ? '1' : '0' }}"
                                     data-status-color="{{ $isThisRoom ? '#16a34a' : $borderHex }}"
                                     data-bs-toggle="tooltip"
                                     title="غرفة {{ $room->room_number }} ({{ $room->room_type_name }}) - {{ number_format($room->daily_fee) }} د.ع"
                                     style="border-color: {{ $isThisRoom ? '#0d6efd' : $borderHex }}; border-width: {{ $isThisRoom ? '3px' : '2px' }}; box-shadow: {{ $isThisRoom ? '0 0 0 3px rgba(13,110,253,.25)' : '' }}; {{ !$isAvailable ? 'pointer-events:none; opacity:0.45;' : '' }}">

                                    <div class="room-number" style="color: {{ $isThisRoom ? '#0d6efd' : $borderHex }};">{{ $room->room_number }}</div>
                                    <div class="room-badges">
                                        @if($room->room_type === 'vvip')
                                            <span class="badge bg-dark text-white" style="font-size: 0.45rem; padding: 1px 2px; line-height:1;">VVIP</span>
                                        @elseif($room->room_type === 'vip')
                                            <span class="badge bg-warning text-dark" style="font-size: 0.45rem; padding: 1px 2px; line-height:1;">VIP</span>
                                        @endif
                                    </div>
                                    <div class="room-status">
                                        <span class="status-dot" style="background-color: {{ $isThisRoom ? '#0d6efd' : $borderHex }};"></span>
                                    </div>
                                    <div class="room-actions" style="{{ $isThisRoom ? 'display:block;' : 'display:none;' }}">
                                        <i class="fas fa-check-circle text-primary" style="font-size: 0.7rem;"></i>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="alert alert-info py-1 small text-center mb-0">لا توجد غرف متاحة</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- زر الحفظ النهائي المدمج -->
        <div class="d-flex justify-content-between align-items-center p-2 bg-white border rounded shadow-sm mb-4">
            <a href="{{ route('surgeries.index') }}" class="btn btn-sm btn-outline-danger px-3">
                <i class="fas fa-times me-1"></i> إلغاء
            </a>
            <button type="submit" class="btn btn-sm btn-success px-4 fw-bold" id="submitBtn" style="font-size:0.9rem;">
                <i class="fas fa-save me-1"></i> حفظ وتحديث حجز العملية الجراحية
            </button>
        </div>
    </form>
</div>

<!-- Modal إضافة طبيب جديد -->
<div class="modal fade" id="addDoctorModal" tabindex="-1" aria-labelledby="addDoctorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title mb-0 fw-bold" id="addDoctorModalLabel" style="font-size:0.9rem;">
                    <i class="fas fa-user-plus me-1"></i> إضافة طبيب مرسل جديد
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <form id="addDoctorForm">
                    <div class="mb-2">
                        <label for="new_doctor_name" class="form-label">اسم الطبيب <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new_doctor_name" required>
                    </div>
                    <div class="mb-2">
                        <label for="new_doctor_specialization" class="form-label">التخصص <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new_doctor_specialization" placeholder="مثال: جراحة عامة" required>
                    </div>
                    <div class="mb-2">
                        <label for="new_doctor_phone" class="form-label">رقم الهاتف</label>
                        <input type="text" class="form-control" id="new_doctor_phone" placeholder="اختياري">
                    </div>
                    <div class="mb-0">
                        <label for="new_doctor_notes" class="form-label">ملاحظات</label>
                        <textarea class="form-control" id="new_doctor_notes" rows="2" placeholder="اختياري"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-sm btn-primary" id="saveDoctorBtn">
                    <i class="fas fa-save me-1"></i> <span id="saveDoctorBtnText">حفظ وإضافة</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@php
    $opsData = $surgicalOperations->map(function($op) {
        return [
            'id' => (string) $op->id,
            'text' => $op->name,
            'category' => $op->category ?? '',
            'fee' => $op->fee,
        ];
    })->values();
@endphp

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // قائمة جميع العمليات والأصناف المتاحة بالنظام
    const allCategories = {!! json_encode($categoriesList) !!};
    const allOperations = {!! json_encode($opsData) !!};
    allOperations.sort(function(a, b) {
        return a.text.localeCompare(b.text, 'ar');
    });
    // زر طي/توسيع كل الأقسام
    const toggleAllBtn = document.getElementById('toggleAllSectionsBtn');
    if (toggleAllBtn) {
        let allExpanded = true;
        toggleAllBtn.addEventListener('click', function() {
            const collapses = document.querySelectorAll('.compact-card .collapse');
            allExpanded = !allExpanded;
            collapses.forEach(c => {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(c, { toggle: false });
                if (allExpanded) {
                    bsCollapse.show();
                } else {
                    bsCollapse.hide();
                }
            });
        });
    }

    // تهيئة Select2
    if (typeof $.fn.select2 !== 'undefined') {
        $('#doctor_id, #patient_id, #surgery_category').select2({
            theme: 'bootstrap-5',
            dir: 'rtl',
            width: '100%',
            language: {
                noResults: function() { return 'لا توجد نتائج'; },
                searching: function() { return 'جاري البحث...'; }
            },
            placeholder: function() { return $(this).data('placeholder') || 'اختر...'; },
            allowClear: true
        });

        // قائمة الطبيب المرسل
        let pendingDoctorName = '';
        let isNewDoctor = false;
        
        $('#referring_doctor_name_select').select2({
            theme: 'bootstrap-5',
            dir: 'rtl',
            width: '100%',
            dropdownParent: $('body'),
            tags: true,
            createTag: function (params) {
                const term = $.trim(params.term);
                if (term === '') return null;
                return {
                    id: term,
                    text: '➕ إضافة طبيب جديد: ' + term,
                    newTag: true,
                    originalName: term
                };
            },
            templateResult: function(data) {
                if (!data.id) return data.text;
                if (data.newTag) {
                    return $('<div class="select2-new-tag"><i class="fas fa-user-plus text-success me-1"></i><strong style="color: #16a34a;">' + data.text + '</strong></div>');
                }
                return data.text;
            },
            templateSelection: function(data) {
                if (data.newTag && data.originalName) return data.originalName;
                return data.text;
            },
            escapeMarkup: function(markup) { return markup; },
            placeholder: 'اختر الطبيب أو أدخل اسماً جديداً',
            allowClear: true
        });

        const addDoctorModalElement = document.getElementById('addDoctorModal');
        if (addDoctorModalElement && addDoctorModalElement.parentElement !== document.body) {
            document.body.appendChild(addDoctorModalElement);
        }
        const addDoctorModalInstance = new bootstrap.Modal(addDoctorModalElement);

        $('#referring_doctor_name_select').on('select2:select', function(e) {
            const data = e.params.data;
            if (data.newTag && data.originalName) {
                isNewDoctor = true;
                pendingDoctorName = data.originalName;
                $(this).select2('close');
                $('.select2-container--open').removeClass('select2-container--open');
                $('.select2-container--open .select2-dropdown').hide();
                $('.select2-dropdown').remove();
                if (document.activeElement) document.activeElement.blur();
                $('#new_doctor_name').val(data.originalName);
                $('#new_doctor_specialization').val('');
                $('#new_doctor_phone').val('');
                $('#new_doctor_notes').val('');
                setTimeout(function() { addDoctorModalInstance.show(); }, 50);
            }
        });

        $('#saveDoctorBtn').on('click', function() {
            const name = $('#new_doctor_name').val().trim();
            const specialization = $('#new_doctor_specialization').val().trim();
            const phone = $('#new_doctor_phone').val().trim();
            const notes = $('#new_doctor_notes').val().trim();
            
            if (!name || !specialization) {
                alert('الرجاء إدخال اسم الطبيب والتخصص');
                return;
            }
            
            const $btn = $(this);
            $btn.prop('disabled', true);
            $('#saveDoctorBtnText').html('<i class="fas fa-spinner fa-spin me-1"></i>جاري الحفظ...');
            
            $.ajax({
                url: '{{ route("doctors.store-referring") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    name: name,
                    specialization: specialization,
                    phone: phone,
                    notes: notes
                },
                success: function(response) {
                    if (response.success) {
                        $('#referring_doctor_name_select option[value="' + name + '"]').remove();
                        const newOption = new Option(name, name, true, true);
                        $('#referring_doctor_name_select').append(newOption).trigger('change');
                        isNewDoctor = false;
                        bootstrap.Modal.getInstance(document.getElementById('addDoctorModal')).hide();
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'حدث خطأ أثناء حفظ الطبيب');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $('#saveDoctorBtnText').text('حفظ وإضافة');
                }
            });
        });

        const initialSelectedOpId = '{{ $selectedOpId }}';

        function initPrimaryOperationSelect2() {
            const selectedCategory = $('#surgery_category').val();
            const currentVal = $('#surgical_operation_id').val() || initialSelectedOpId;
            $('#surgical_operation_id option:not(:first)').remove();
            
            allOperations.forEach(function(op) {
                if (!selectedCategory || op.category === selectedCategory) {
                    const $option = $('<option></option>')
                        .val(op.id)
                        .text(op.text)
                        .attr('data-category', op.category)
                        .attr('data-fee', op.fee);
                    if (String(op.id) === String(currentVal)) {
                        $option.attr('selected', 'selected');
                    }
                    $('#surgical_operation_id').append($option);
                }
            });
            
            $('#surgical_operation_id').select2({
                theme: 'bootstrap-5',
                dir: 'rtl',
                width: '100%',
                placeholder: 'اختر نوع العملية',
                allowClear: true
            });

            if (currentVal) {
                $('#surgical_operation_id').val(String(currentVal)).trigger('change.select2');
            }
        }

        initPrimaryOperationSelect2();

        $('#surgery_category').on('change', function() {
            $('#surgical_operation_id').val(null).trigger('change');
            $('#surgical_operation_id').select2('destroy');
            initPrimaryOperationSelect2();
        });

        // تفاصيل المريض المختار
        $('#patient_id').on('change', function() {
            const selectedOption = $(this).find(':selected');
            const patientData = selectedOption.data('patient');
            
            if (patientData && patientData.name) {
                $('#patientNameHeader').text(patientData.name);
                $('#patientAge').text((patientData.age || '-') + ' سنة');
                $('#patientGender').text(patientData.gender || '-');
                $('#patientPhone').text(patientData.phone || '-');
                $('#selectedPatientInfo').slideDown(150);
            }
        });

        // تفاصيل العملية والسعر المقترح
        $('#surgical_operation_id').on('change', function() {
            const selectedOption = $(this).find(':selected');
            const opId = selectedOption.val();
            const fee = selectedOption.data('fee');

            if (opId && fee !== undefined && fee !== null) {
                if (!$('#custom_surgery_fee').val()) {
                    $('#custom_surgery_fee').val(parseInt(fee, 10).toLocaleString('en-US'));
                }
            }
            updateOpsFinancialSummary();
        });

        if ($('#surgical_operation_id').val()) {
            $('#surgical_operation_id').trigger('change');
        }
    }

    // تنسيق فواصل الآلاف
    const customFeeInput = document.getElementById('custom_surgery_fee');
    function formatWithCommas(val) {
        if (!val && val !== 0) return '';
        let str = val.toString().trim();
        if (str.includes('.')) {
            str = str.split('.')[0];
        }
        const digits = str.replace(/[^0-9]/g, '');
        if (!digits) return '';
        return parseInt(digits, 10).toLocaleString('en-US');
    }

    if (customFeeInput) {
        customFeeInput.addEventListener('input', function() {
            const pos = customFeeInput.selectionStart;
            const len = customFeeInput.value.length;
            customFeeInput.value = formatWithCommas(customFeeInput.value);
            const diff = customFeeInput.value.length - len;
            customFeeInput.setSelectionRange(pos + diff, pos + diff);
            updateOpsFinancialSummary();
        });
    }

    // إدارة العمليات الجراحية الموحدة والمتعددة
    let addOpIndex = 0;

    function renumberAllOpRows() {
        let currentNumber = 2;
        $('#operationsTableBody .additional-op-row').each(function() {
            $(this).find('.op-number-badge').text('العملية (' + currentNumber + ')');
            currentNumber++;
        });
        const totalCount = 1 + $('#operationsTableBody .additional-op-row').length;
        $('#totalOpsCount').text(totalCount);
        $('#totalOpsCountBottom').text(totalCount);
    }

    function updateOpsFinancialSummary() {
        const primaryVal = $('#custom_surgery_fee').val() || '0';
        const primaryFee = parseInt(primaryVal.replace(/[^0-9]/g, ''), 10) || 0;
        
        let additionalTotal = 0;
        $('.add-op-fee').each(function() {
            const rowFeeVal = $(this).val() || '0';
            const rowFee = parseInt(rowFeeVal.replace(/[^0-9]/g, ''), 10) || 0;
            additionalTotal += rowFee;
        });

        renumberAllOpRows();
        const formattedTotal = (primaryFee + additionalTotal).toLocaleString('en-US') + ' د.ع';
        $('#summaryTotalFee').text(formattedTotal);
        $('#summaryTotalFeeBottom').text(formattedTotal);
    }

    function addAdditionalOpRow(selectedOpId = null, initialFee = null, initialNotes = '', selectedCategory = null) {
        const idx = addOpIndex++;

        if (selectedOpId && !selectedCategory) {
            const found = allOperations.find(o => String(o.id) === String(selectedOpId));
            if (found) selectedCategory = found.category;
        }

        let catOptions = '<option value="">-- كل الأصناف --</option>';
        allCategories.forEach(function(cat) {
            const isCatSelected = selectedCategory && selectedCategory === cat ? 'selected' : '';
            catOptions += `<option value="${cat}" ${isCatSelected}>${cat}</option>`;
        });

        let opOptions = '<option value="">-- اختر العملية --</option>';
        allOperations.forEach(function(op) {
            if (!selectedCategory || op.category === selectedCategory) {
                const isSelected = selectedOpId && String(op.id) === String(selectedOpId) ? 'selected' : '';
                opOptions += `<option value="${op.id}" data-category="${op.category || ''}" data-fee="${op.fee || 0}" ${isSelected}>${op.text}</option>`;
            }
        });

        let feeValue = '';
        if (initialFee !== null && initialFee !== undefined && initialFee !== '') {
            feeValue = formatWithCommas(initialFee.toString());
        }

        const nextNumber = 2 + $('#operationsTableBody .additional-op-row').length;

        const rowHtml = `
            <tr class="additional-op-row align-middle bg-white" id="add_op_row_${idx}">
                <td class="text-center bg-light">
                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 op-number-badge" style="font-size: 0.8rem;">العملية (${nextNumber})</span>
                </td>
                <td>
                    <select class="form-select form-select-sm add-op-category" style="width: 100%;">
                        ${catOptions}
                    </select>
                </td>
                <td>
                    <select name="additional_operations[${idx}][surgical_operation_id]" class="form-select form-select-sm select2-add-op" required style="width: 100%;">
                        ${opOptions}
                    </select>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="text" name="additional_operations[${idx}][fee]" class="form-control form-control-sm add-op-fee text-end fw-bold" value="${feeValue}" placeholder="0" inputmode="numeric" required>
                        <span class="input-group-text small px-1 bg-light text-muted" style="font-size: 0.72rem;">د.ع</span>
                    </div>
                </td>
                <td>
                    <input type="text" name="additional_operations[${idx}][notes]" class="form-control form-control-sm" value="${initialNotes || ''}" placeholder="تكميلية، جهة يسرى...">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-add-op-btn py-1 px-2" title="حذف هذه العملية">
                        <i class="fas fa-trash-alt me-1"></i> حذف
                    </button>
                </td>
            </tr>
        `;

        $('#operationsTableBody').append(rowHtml);

        const $row = $(`#add_op_row_${idx}`);
        const $catSelect = $row.find('.add-op-category');
        const $opSelect = $row.find('.select2-add-op');

        if (typeof $.fn.select2 !== 'undefined') {
            $opSelect.select2({
                theme: 'bootstrap-5',
                dir: 'rtl',
                width: '100%',
                placeholder: 'اختر نوع العملية',
                allowClear: true
            });
            if (selectedOpId) {
                $opSelect.val(String(selectedOpId)).trigger('change.select2');
            }
        }

        $catSelect.on('change', function() {
            const cat = $(this).val();
            let newOpOptions = '<option value="">-- اختر العملية --</option>';
            allOperations.forEach(function(op) {
                if (!cat || op.category === cat) {
                    newOpOptions += `<option value="${op.id}" data-category="${op.category || ''}" data-fee="${op.fee || 0}">${op.text}</option>`;
                }
            });

            if (typeof $.fn.select2 !== 'undefined') {
                $opSelect.select2('destroy');
            }
            $opSelect.html(newOpOptions);
            if (typeof $.fn.select2 !== 'undefined') {
                $opSelect.select2({
                    theme: 'bootstrap-5',
                    dir: 'rtl',
                    width: '100%',
                    placeholder: 'اختر نوع العملية',
                    allowClear: true
                });
            }
            $opSelect.val('').trigger('change');
        });

        $opSelect.on('change', function() {
            const selected = $(this).find(':selected');
            const fee = selected.data('fee');
            const opCat = selected.data('category');
            const $feeInput = $row.find('.add-op-fee');
            
            if (opCat && !$catSelect.val()) {
                $catSelect.val(opCat);
            }

            if (!$feeInput.val() && fee !== undefined && fee !== null) {
                $feeInput.val(parseInt(fee, 10).toLocaleString('en-US'));
            }
            updateOpsFinancialSummary();
        });

        const $feeInput = $row.find('.add-op-fee');
        $feeInput.on('input', function() {
            const formatted = formatWithCommas($(this).val());
            $(this).val(formatted);
            updateOpsFinancialSummary();
        });

        updateOpsFinancialSummary();
    }

    $(document).on('click', '#addAnotherOpBtn, #addAnotherOpBtnBottom', function(e) {
        e.preventDefault();
        addAdditionalOpRow();
    });

    $(document).on('click', '.remove-add-op-btn', function(e) {
        e.preventDefault();
        $(this).closest('.additional-op-row').remove();
        renumberAllOpRows();
        updateOpsFinancialSummary();
    });

    @php
        $existingAdditionalOps = [];
        if (old('additional_operations')) {
            $existingAdditionalOps = old('additional_operations');
        } elseif (isset($surgery->additionalOperations) && $surgery->additionalOperations->isNotEmpty()) {
            foreach ($surgery->additionalOperations as $addOp) {
                $existingAdditionalOps[] = [
                    'surgical_operation_id' => (string) $addOp->surgical_operation_id,
                    'fee' => (int) round($addOp->fee),
                    'notes' => $addOp->notes,
                    'category' => optional($addOp->surgicalOperation)->category,
                ];
            }
        }
    @endphp
    const existingAdditionalOps = {!! json_encode($existingAdditionalOps) !!};
    if (Array.isArray(existingAdditionalOps) && existingAdditionalOps.length > 0) {
        existingAdditionalOps.forEach(function(item) {
            if (item && item.surgical_operation_id) {
                addAdditionalOpRow(item.surgical_operation_id, item.fee, item.notes, item.category);
            }
        });
    }

    updateOpsFinancialSummary();

    // نظام اختيار الغرفة المدمج والتحقق
    const roomIdInput = document.getElementById('room_id');
    const stayDaysInput = document.getElementById('expected_stay_days');
    const roomTotalFeeEl = document.getElementById('room_total_fee');
    const selectedRoomNameEl = document.getElementById('selected_room_name');
    const clearRoomSection = document.getElementById('clear_room_section');
    const clearRoomBtn = document.getElementById('clear_room_btn');
    const headerRoomBadge = document.getElementById('header_room_badge');
    const roomValidationError = document.getElementById('room_validation_error');
    const floorPillsContent = document.getElementById('floorPillsContent');
    let selectedRoomFee = {{ $currentRoomFee }};

    function validateRoomSelection() {
        const val = roomIdInput ? roomIdInput.value : '';
        if (!val) {
            if (roomValidationError) roomValidationError.classList.remove('d-none');
            if (floorPillsContent) {
                floorPillsContent.style.border = '2.5px solid #dc3545';
                floorPillsContent.style.borderRadius = '8px';
            }

            const roomCollapse = document.getElementById('collapseRoomStay');
            if (roomCollapse) {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(roomCollapse, { toggle: false });
                bsCollapse.show();
            }

            const oldToast = document.querySelector('.room-required-toast');
            if (oldToast) oldToast.remove();
            const toast = document.createElement('div');
            toast.className = 'room-required-toast alert alert-danger position-fixed shadow-lg';
            toast.style.cssText = 'top:25px;left:50%;transform:translateX(-50%);z-index:9999;border-right:6px solid #dc3545;font-weight:bold;font-size:0.95rem;background:#fff;';
            toast.innerHTML = '<i class="fas fa-exclamation-triangle text-danger me-2"></i> يرجى اختيار غرفة للمريض من قائمة الغرف المتاحة.';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4500);

            document.getElementById('collapseRoomStay')?.scrollIntoView({ behavior: 'smooth' });
            return false;
        }

        if (roomValidationError) roomValidationError.classList.add('d-none');
        if (floorPillsContent) floorPillsContent.style.border = '';
        return true;
    }

    const form = document.getElementById('surgeryForm');
    const submitBtn = document.getElementById('submitBtn');

    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            if (!validateRoomSelection()) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateRoomSelection()) {
                e.preventDefault();
                return false;
            }

            if (customFeeInput && customFeeInput.value) {
                customFeeInput.value = customFeeInput.value.replace(/,/g, '');
            }
            $('.add-op-fee').each(function() {
                if ($(this).val()) {
                    $(this).val($(this).val().replace(/,/g, ''));
                }
            });
        });
    }

    document.querySelectorAll('.room-tile').forEach(t => {
        t.dataset.origBorder = t.dataset.statusColor || t.style.borderColor || '#dee2e6';
        t.dataset.origWidth  = '2px';
    });

    let selectedRoomType = '{{ $currentRoom ? $currentRoom->room_type : "regular" }}';

    function calculateRoomFee() {
        if (!roomTotalFeeEl) return;
        if (!roomIdInput || !roomIdInput.value) {
            roomTotalFeeEl.textContent = '0 د.ع';
            roomTotalFeeEl.className = 'badge bg-secondary fs-6 ms-1';
            return;
        }

        if (selectedRoomType === 'vvip') {
            roomTotalFeeEl.textContent = '200,000 د.ع (VVIP)';
            roomTotalFeeEl.className = 'badge bg-dark text-white fs-6 ms-1';
        } else if (selectedRoomType === 'vip') {
            roomTotalFeeEl.textContent = '100,000 د.ع (VIP)';
            roomTotalFeeEl.className = 'badge bg-warning text-dark fs-6 ms-1';
        } else {
            roomTotalFeeEl.textContent = '0 د.ع (مجانية أول ليلة)';
            roomTotalFeeEl.className = 'badge bg-success text-white fs-6 ms-1';
        }
    }

    function selectRoom(tile) {
        if (tile.dataset.available === '0') return;

        if (roomValidationError) roomValidationError.classList.add('d-none');
        if (floorPillsContent) floorPillsContent.style.border = '';

        document.querySelectorAll('.room-tile').forEach(t => {
            t.style.borderColor = t.dataset.origBorder;
            t.style.borderWidth = t.dataset.origWidth;
            t.style.boxShadow   = '';
            const icon = t.querySelector('.room-actions');
            if (icon) icon.style.display = 'none';
        });

        tile.style.borderColor = '#0d6efd';
        tile.style.borderWidth = '3px';
        tile.style.boxShadow   = '0 0 0 3px rgba(13,110,253,.25)';
        const icon = tile.querySelector('.room-actions');
        if (icon) icon.style.display = 'block';

        if (roomIdInput) roomIdInput.value = tile.dataset.roomId;
        selectedRoomFee  = parseFloat(tile.dataset.roomFee) || 0;
        selectedRoomType = tile.dataset.roomType || 'regular';

        const typeLabel = selectedRoomType === 'vvip' ? ' (💎 VVIP)' : (selectedRoomType === 'vip' ? ' (⭐ VIP)' : ' (عادية)');
        const roomName = 'غرفة ' + tile.dataset.roomNumber + typeLabel;
        if (selectedRoomNameEl) selectedRoomNameEl.textContent = roomName;
        if (headerRoomBadge) {
            headerRoomBadge.textContent = roomName;
            headerRoomBadge.className = selectedRoomType === 'vvip' ? 'badge bg-dark text-white' : (selectedRoomType === 'vip' ? 'badge bg-warning text-dark' : 'badge bg-success text-white');
        }

        const statusSpan = document.getElementById('selected_room_status');
        if (statusSpan) {
            statusSpan.textContent = 'محددة';
            statusSpan.className   = 'fw-bold text-success';
        }

        if (clearRoomSection) clearRoomSection.style.display = 'block';
        calculateRoomFee();
    }

    document.querySelectorAll('.room-selectable').forEach(tile => {
        tile.addEventListener('click', function() { selectRoom(this); });
    });

    if (stayDaysInput) {
        stayDaysInput.addEventListener('input', calculateRoomFee);
    }

    if (clearRoomBtn) {
        clearRoomBtn.addEventListener('click', function() {
            document.querySelectorAll('.room-tile').forEach(t => {
                t.style.borderColor = t.dataset.origBorder;
                t.style.borderWidth = t.dataset.origWidth;
                t.style.boxShadow   = '';
                const icon = t.querySelector('.room-actions');
                if (icon) icon.style.display = 'none';
            });
            if (roomIdInput) roomIdInput.value = '';
            selectedRoomFee   = 0;
            if (selectedRoomNameEl) selectedRoomNameEl.textContent = 'لم يتم الاختيار';
            if (headerRoomBadge) {
                headerRoomBadge.textContent = 'لم يتم الاختيار';
                headerRoomBadge.className = 'badge bg-light text-muted border';
            }
            if (clearRoomSection) clearRoomSection.style.display = 'none';
            calculateRoomFee();
        });
    }

    // تهيئة Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // ===== نظام الأرشفة التلقائي - يعمل مع أي سكانر =====
    const referralInput = document.getElementById('referral_letter');
    const previewContainer = document.getElementById('referral_letter_preview');

    window.scanFromDevice = async function() {
        const btn = document.getElementById('scan_btn');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري المسح...';

        try {
            const response = await fetch('http://127.0.0.1:5000/scan', { method: 'POST', mode: 'cors' });
            if (!response.ok) {
                throw new Error('فشل المسح من السكانر');
            }
            const blob = await response.blob();
            const reader = new FileReader();
            reader.onloadend = function() {
                const base64data = reader.result;
                const hiddenInput = document.getElementById('scan_data_receiver');
                if (hiddenInput) hiddenInput.value = base64data;
                if (previewContainer) previewContainer.style.display = 'block';
            };
            reader.readAsDataURL(blob);
        } catch (err) {
            console.error(err);
            alert('برنامج السكانر غير مشغل أو غير متصل. يمكنك استخدام زر "رفع ملف" بدلاً منه.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    };

    window.clearScannedDoc = function() {
        if (referralInput) referralInput.value = '';
        const hiddenInput = document.getElementById('scan_data_receiver');
        if (hiddenInput) hiddenInput.value = '';
        if (previewContainer) previewContainer.style.display = 'none';
    };

    if (referralInput) {
        referralInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                if (previewContainer) previewContainer.style.display = 'block';
            }
        });
    }
});
</script>
@endsection
