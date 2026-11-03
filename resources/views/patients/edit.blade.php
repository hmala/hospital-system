<!-- resources/views/patients/edit.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">
    <!-- رأس الصفحة العصري -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-7 mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="header-icon-box shadow-sm">
                    <i class="fas fa-user-edit text-primary fs-3"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h3 class="fw-bold mb-0 text-dark">تعديل بيانات المريض</h3>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fs-7 fw-semibold">
                            {{ $patient->user ? $patient->user->name : 'المريض #' . $patient->id }}
                        </span>
                    </div>
                    <p class="text-muted mb-0 small">تحديث وتصحيح بيانات الإضبارة الطبية للمريض.</p>
                </div>
            </div>
        </div>
        <div class="col-md-5 text-md-start">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('patients.show', $patient) }}" class="btn btn-outline-secondary rounded-pill px-4 py-2 shadow-sm transition-all">
                <i class="fas fa-arrow-right me-2"></i>العودة
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <!-- الاستمارة الرئيسية -->
        <div class="col-xl-9 col-lg-10 col-12">
            <div class="card border-0 shadow-sm rounded-4 patient-form-card bg-white">
                <div class="card-header bg-white border-bottom border-light-subtle py-3 px-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                            <i class="fas fa-id-card-alt text-primary"></i> تعديل بيانات الإضبارة
                        </span>
                        <span class="badge bg-light text-muted border rounded-pill px-3 py-1 small">
                            الحقول بـ <span class="text-danger fw-bold">*</span> إلزامية
                        </span>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    <form method="POST" action="{{ route('patients.update', $patient) }}" id="patientEditForm" autocomplete="off">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="return_url" value="{{ old('return_url', (url()->previous() !== url()->current() ? url()->previous() : route('patients.show', $patient))) }}">

                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="fas fa-exclamation-triangle fs-5 text-danger"></i>
                                    <strong class="text-danger">يرجى تصحيح الأخطاء التالية قبل الحفظ:</strong>
                                </div>
                                <ul class="mb-0 ps-4 small">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                            </div>
                        @endif

                        <!-- القسم 1: البيانات الشخصية والاتصال -->
                        <div class="form-section mb-4 pb-3 border-bottom border-light-subtle">
                            <div class="section-badge mb-3">
                                <span class="step-num">1</span>
                                <span class="step-title">المعلومات الشخصية والاتصال</span>
                            </div>

                            <div class="row g-3">
                                <!-- الاسم الكامل -->
                                <div class="col-md-7">
                                    <label for="name" class="form-label fw-bold small text-dark mb-1">
                                        <i class="fas fa-user text-primary me-1"></i> الاسم الكامل للمريض <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $patient->user ? $patient->user->name : '') }}"
                                           required 
                                           autofocus
                                           placeholder="الاسم الثلاثي أو الرباعي للمريض">
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- الهاتف -->
                                <div class="col-md-5">
                                    <label for="phone" class="form-label fw-bold small text-dark mb-1">
                                        <i class="fas fa-phone-alt text-primary me-1"></i> رقم الهاتف المحمول <span class="text-danger">*</span>
                                    </label>
                                    <input type="tel" 
                                           class="form-control font-monospace @error('phone') is-invalid @enderror" 
                                           id="phone" 
                                           name="phone" 
                                           value="{{ old('phone', $patient->user ? $patient->user->phone : '') }}"
                                           required 
                                           inputmode="numeric"
                                           placeholder="07XXXXXXXXX">
                                    @error('phone')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- تاريخ الميلاد (إدخال سريع: يوم / شهر / سنة) -->
                                <div class="col-md-5">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-bold small text-dark mb-0">
                                            <i class="fas fa-calendar-alt text-primary me-1"></i> تاريخ الميلاد <span class="text-danger">*</span>
                                        </label>
                                        <span id="calculatedAgeBadge" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0" style="display: none; font-size: 0.72rem;">
                                            -
                                        </span>
                                    </div>
                                    <div class="row g-1">
                                        <!-- اليوم -->
                                        <div class="col-4">
                                            <select class="form-select text-center px-1" id="dob_day" title="اليوم">
                                                <option value="">اليوم</option>
                                                @for($d = 1; $d <= 31; $d++)
                                                    <option value="{{ sprintf('%02d', $d) }}">{{ $d }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <!-- الشهر -->
                                        <div class="col-4">
                                            <select class="form-select text-center px-1" id="dob_month" title="الشهر">
                                                <option value="">الشهر</option>
                                                @for($m = 1; $m <= 12; $m++)
                                                    <option value="{{ sprintf('%02d', $m) }}">{{ $m }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <!-- السنة -->
                                        <div class="col-4">
                                            <select class="form-select text-center px-1" id="dob_year" title="السنة">
                                                <option value="">السنة</option>
                                                @for($y = (int)date('Y'); $y >= 1920; $y--)
                                                    <option value="{{ $y }}">{{ $y }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <input type="hidden" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth', $patient->user && $patient->user->date_of_birth ? $patient->user->date_of_birth->format('Y-m-d') : '') }}" required>
                                    @error('date_of_birth')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- الجنس -->
                                <div class="col-md-3">
                                    <label for="gender" class="form-label fw-bold small text-dark mb-1">
                                        <i class="fas fa-venus-mars text-primary me-1"></i> الجنس <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('gender') is-invalid @enderror" 
                                            id="gender" name="gender" required>
                                        <option value="">اختر الجنس</option>
                                        <option value="male" {{ old('gender', $patient->user ? $patient->user->gender : '') == 'male' ? 'selected' : '' }}>ذكر 👨</option>
                                        <option value="female" {{ old('gender', $patient->user ? $patient->user->gender : '') == 'female' ? 'selected' : '' }}>أنثى 👩</option>
                                    </select>
                                    @error('gender')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- الحالة الاجتماعية -->
                                <div class="col-md-4">
                                    <label for="marital_status" class="form-label fw-bold small text-dark mb-1">
                                        <i class="fas fa-ring text-primary me-1"></i> الحالة الاجتماعية <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('marital_status') is-invalid @enderror" 
                                            id="marital_status" name="marital_status" required>
                                        <option value="أعزب" {{ old('marital_status', $patient->marital_status ?? 'أعزب') == 'أعزب' ? 'selected' : '' }}>أعزب</option>
                                        <option value="متزوج" {{ old('marital_status', $patient->marital_status) == 'متزوج' ? 'selected' : '' }}>متزوج</option>
                                        <option value="مطلق" {{ old('marital_status', $patient->marital_status) == 'مطلق' ? 'selected' : '' }}>مطلق</option>
                                        <option value="أرمل" {{ old('marital_status', $patient->marital_status) == 'أرمل' ? 'selected' : '' }}>أرمل</option>
                                    </select>
                                    @error('marital_status')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- القسم 2: إثبات الهوية -->
                        <div class="form-section mb-4 pb-3 border-bottom border-light-subtle">
                            <div class="section-badge mb-3">
                                <span class="step-num">2</span>
                                <span class="step-title">إثبات الهوية الوطنية</span>
                            </div>

                            <div class="row g-3">
                                <!-- رقم البطاقة الموحدة -->
                                <div class="col-md-6">
                                    <label for="national_id" class="form-label fw-bold small text-dark mb-1 d-flex justify-content-between">
                                        <span><i class="fas fa-fingerprint text-primary me-1"></i> رقم البطاقة الموحدة <span class="text-danger">*</span></span>
                                        <span class="badge bg-light text-muted border font-monospace">أرقام فقط</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control font-monospace fs-6 fw-bold @error('national_id') is-invalid @enderror" 
                                           id="national_id" 
                                           name="national_id" 
                                           value="{{ old('national_id', $patient->national_id) }}"
                                           required
                                           inputmode="numeric"
                                           pattern="[0-9]*"
                                           oninput="this.value = this.value.replace(/[٠-٩]/g, d => d.charCodeAt(0) - 1632).replace(/[^0-9]/g, '');"
                                           placeholder="مثال: 199012345678">
                                    <div class="form-text text-muted small mt-1">
                                        <i class="fas fa-shield-alt text-success me-1"></i>يمنع تكرار المريض ويُعتمد كمعرّف وحيد.
                                    </div>
                                    @error('national_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- اسم الأم (اختياري) -->
                                <div class="col-md-6">
                                    <label for="mother_name" class="form-label fw-semibold small text-dark mb-1">
                                        <i class="fas fa-female text-primary me-1"></i> اسم الأم <span class="text-muted fw-normal">(اختياري / للتمييز)</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('mother_name') is-invalid @enderror" 
                                           id="mother_name" 
                                           name="mother_name" 
                                           value="{{ old('mother_name', $patient->mother_name) }}"
                                           placeholder="اسم الأم (اختياري)">
                                    <div class="form-text text-muted small mt-1">
                                        مفيد لتمييز حالات تشابه الأسماء الرباعية.
                                    </div>
                                    @error('mother_name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- القسم 3: الضمان الصحي والتأمين -->
                        <div class="form-section mb-4">
                            <div class="section-badge mb-3">
                                <span class="step-num">3</span>
                                <span class="step-title">جهة الضمان الصحي والتأمين</span>
                            </div>

                            @php
                                $currentInsuranceType = old('insurance_type');
                                if (!$currentInsuranceType) {
                                    if (!empty($patient->health_insurance_category_id)) {
                                        $currentInsuranceType = 'hi';
                                    } elseif (!empty($patient->insurance_type) && in_array($patient->insurance_type, ['none', 'moi', 'hi'])) {
                                        $currentInsuranceType = $patient->insurance_type;
                                    } elseif (!empty($patient->covered_by_insurance)) {
                                        $currentInsuranceType = 'moi';
                                    } else {
                                        $currentInsuranceType = 'none';
                                    }
                                }
                            @endphp

                            <!-- بطاقات اختيار جهة الضمان التفاعلية -->
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <label class="insurance-card-tile p-3 rounded-3 border d-flex flex-column align-items-center text-center cursor-pointer transition-all h-100 {{ $currentInsuranceType == 'none' ? 'active-tile' : '' }}" 
                                           id="tile_none" data-insurance="none" onclick="setInsuranceType('none')">
                                        <input type="radio" name="insurance_selector" value="none" class="d-none" {{ $currentInsuranceType == 'none' ? 'checked' : '' }}>
                                        <div class="tile-icon-circle bg-success-subtle text-success mb-2">
                                            <i class="fas fa-wallet fs-5"></i>
                                        </div>
                                        <strong class="tile-title text-dark small mb-1">دفع نقدي (كاش)</strong>
                                        <span class="text-muted text-xs">غير مشمول بأي ضمان صحي</span>
                                    </label>
                                </div>

                                <div class="col-md-4">
                                    <label class="insurance-card-tile p-3 rounded-3 border d-flex flex-column align-items-center text-center cursor-pointer transition-all h-100 {{ $currentInsuranceType == 'moi' ? 'active-tile' : '' }}" 
                                           id="tile_moi" data-insurance="moi" onclick="setInsuranceType('moi')">
                                        <input type="radio" name="insurance_selector" value="moi" class="d-none" {{ $currentInsuranceType == 'moi' ? 'checked' : '' }}>
                                        <div class="tile-icon-circle bg-primary-subtle text-primary mb-2">
                                            <i class="fas fa-user-shield fs-5"></i>
                                        </div>
                                        <strong class="tile-title text-dark small mb-1">قوى الأمن الداخلي</strong>
                                        <span class="text-muted text-xs">منتسبو وزارة الداخلية (MOI)</span>
                                    </label>
                                </div>

                                <div class="col-md-4">
                                    <label class="insurance-card-tile p-3 rounded-3 border d-flex flex-column align-items-center text-center cursor-pointer transition-all h-100 {{ $currentInsuranceType == 'hi' ? 'active-tile' : '' }}" 
                                           id="tile_hi" data-insurance="hi" onclick="setInsuranceType('hi')">
                                        <input type="radio" name="insurance_selector" value="hi" class="d-none" {{ $currentInsuranceType == 'hi' ? 'checked' : '' }}>
                                        <div class="tile-icon-circle bg-info-subtle text-info mb-2">
                                            <i class="fas fa-hospital-user fs-5"></i>
                                        </div>
                                        <strong class="tile-title text-dark small mb-1">الضمان الصحي الوطني</strong>
                                        <span class="text-muted text-xs">مشمول بهيئة الضمان (A - I)</span>
                                    </label>
                                </div>
                            </div>

                            <!-- الحقل المخفي للـ Backend -->
                            <input type="hidden" id="insurance_type" name="insurance_type" value="{{ $currentInsuranceType }}">

                            <!-- صندوق تفاصيل بطاقة الضمان المنبثق -->
                            <div class="p-3 bg-light rounded-4 border border-primary-subtle mt-2 shadow-sm transition-all" id="insurance_details_box" style="display: none;">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                    <span class="fw-bold small text-primary d-flex align-items-center gap-2">
                                        <i class="fas fa-id-badge fs-6"></i> بيانات البطاقة أو الدفتر التأميني
                                    </span>
                                    <span class="badge bg-primary rounded-pill px-2 py-1 small" id="activeInsuranceBadge">ضمان صحي</span>
                                </div>

                                <div class="row g-3">
                                    <!-- فئة الضمان الصحي الوطني -->
                                    <div class="col-md-6" id="hi_category_container" style="display: none;">
                                        <label for="health_insurance_category_id" class="form-label fw-bold small text-dark mb-1">
                                            فئة المشمول بالضمان <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('health_insurance_category_id') is-invalid @enderror" 
                                                id="health_insurance_category_id" 
                                                name="health_insurance_category_id">
                                            <option value="">-- اختر فئة الضمان (A - I) --</option>
                                            @foreach($healthInsuranceCategories as $cat)
                                                <option value="{{ $cat->id }}" 
                                                        data-code="{{ $cat->code }}"
                                                        data-stamp="{{ $cat->requires_thermal_stamp ? 1 : 0 }}"
                                                        {{ old('health_insurance_category_id', $patient->health_insurance_category_id) == $cat->id ? 'selected' : '' }}>
                                                    {{ $cat->name }} ({{ $cat->code }}){{ !empty($cat->description) ? ' - ' . $cat->description : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-1" style="font-size: 0.74rem;">تُحدد نسب الاستقطاع للخدمات الطبية بحسب الفئة.</small>
                                        @error('health_insurance_category_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- رقم البطاقة التأمينية -->
                                    <div class="col-md-6" id="insurance_card_container">
                                        <label for="insurance_card_no" class="form-label fw-bold small text-dark mb-1">
                                            رقم بطاقة / دفتر الضمان <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control font-monospace @error('insurance_card_no') is-invalid @enderror" 
                                               id="insurance_card_no" 
                                               name="insurance_card_no" 
                                               value="{{ old('insurance_card_no', $patient->insurance_card_no ?? $patient->insurance_booklet_number) }}"
                                               placeholder="أدخل رقم الهوية أو الدفتر">
                                        <small class="text-muted d-block mt-1" style="font-size: 0.74rem;">الرقم المطبوع على بطاقة الضمان الصادرة.</small>
                                        @error('insurance_card_no')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- شريط الأزرار والحفظ -->
                        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center pt-3 border-top mt-4">
                            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('patients.show', $patient) }}" class="btn btn-light rounded-pill px-4 text-muted fw-semibold">
                                إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary btn-save-patient rounded-pill px-5 py-2 fw-bold shadow">
                                <i class="fas fa-save me-2"></i> حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dobDaySelect = document.getElementById('dob_day');
    const dobMonthSelect = document.getElementById('dob_month');
    const dobYearSelect = document.getElementById('dob_year');
    const dateOfBirthInput = document.getElementById('date_of_birth');
    const calculatedAgeBadge = document.getElementById('calculatedAgeBadge');

    // 1. حساب العمر وتجميع التاريخ من القوائم المنسدلة
    function calculateAge(birthDateStr) {
        if (!birthDateStr) return null;
        const birthDate = new Date(birthDateStr);
        if (isNaN(birthDate.getTime())) return null;
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age >= 0 ? age : null;
    }

    function syncDropdownDateOfBirth() {
        if (!dobYearSelect || !dateOfBirthInput) return;
        const y = dobYearSelect.value;
        const m = dobMonthSelect ? (dobMonthSelect.value || '01') : '01';
        const d = dobDaySelect ? (dobDaySelect.value || '01') : '01';

        if (y) {
            const formatted = `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
            dateOfBirthInput.value = formatted;

            const age = calculateAge(formatted);
            if (age !== null && age <= 130) {
                calculatedAgeBadge.textContent = 'العمر: ' + age + ' سنة';
                calculatedAgeBadge.style.display = 'inline-block';
            } else {
                calculatedAgeBadge.style.display = 'none';
            }
        } else {
            dateOfBirthInput.value = '';
            calculatedAgeBadge.style.display = 'none';
        }
    }

    if (dobDaySelect && dobMonthSelect && dobYearSelect) {
        dobDaySelect.addEventListener('change', syncDropdownDateOfBirth);
        dobMonthSelect.addEventListener('change', syncDropdownDateOfBirth);
        dobYearSelect.addEventListener('change', syncDropdownDateOfBirth);

        // تعبئة أولية إذا كانت القيمة مخزنة
        if (dateOfBirthInput && dateOfBirthInput.value) {
            const parts = dateOfBirthInput.value.split('-');
            if (parts.length === 3) {
                dobYearSelect.value = parts[0];
                dobMonthSelect.value = String(parseInt(parts[1], 10)).padStart(2, '0');
                dobDaySelect.value = String(parseInt(parts[2], 10)).padStart(2, '0');
                syncDropdownDateOfBirth();
            }
        }
    }

    // 2. إدارة بطاقات اختيار الضمان التفاعلية
    function setInsuranceType(val) {
        if (!val) val = 'none';

        const insuranceTypeHidden = document.getElementById('insurance_type');
        const insuranceDetailsBox = document.getElementById('insurance_details_box');
        const insuranceCardNoInput = document.getElementById('insurance_card_no');
        const hiCategoryContainer = document.getElementById('hi_category_container');
        const hiCategorySelect = document.getElementById('health_insurance_category_id');
        const activeInsuranceBadge = document.getElementById('activeInsuranceBadge');

        if (insuranceTypeHidden) {
            insuranceTypeHidden.value = val;
        }

        // تحديث تصميم البلاطات (Tiles)
        document.querySelectorAll('.insurance-card-tile').forEach(tile => {
            tile.classList.remove('active-tile');
        });

        const activeTile = document.getElementById('tile_' + val);
        if (activeTile) {
            activeTile.classList.add('active-tile');
        }

        // مزامنة أزرار الراديو
        document.querySelectorAll('input[name="insurance_selector"]').forEach(radio => {
            radio.checked = (radio.value === val);
        });

        if (val !== 'none') {
            if (insuranceDetailsBox) insuranceDetailsBox.style.display = 'block';
            if (insuranceCardNoInput) insuranceCardNoInput.setAttribute('required', 'required');

            if (val === 'hi') {
                if (hiCategoryContainer) hiCategoryContainer.style.display = 'block';
                if (hiCategorySelect) hiCategorySelect.setAttribute('required', 'required');
                if (activeInsuranceBadge) {
                    activeInsuranceBadge.textContent = 'هيئة الضمان الصحي الوطني';
                    activeInsuranceBadge.className = 'badge bg-info text-white rounded-pill px-3 py-1 small';
                }
            } else {
                if (hiCategoryContainer) hiCategoryContainer.style.display = 'none';
                if (hiCategorySelect) hiCategorySelect.removeAttribute('required');
                if (activeInsuranceBadge) {
                    activeInsuranceBadge.textContent = 'ضمان وزارة الداخلية (MOI)';
                    activeInsuranceBadge.className = 'badge bg-primary text-white rounded-pill px-3 py-1 small';
                }
            }
        } else {
            if (insuranceDetailsBox) insuranceDetailsBox.style.display = 'none';
            if (insuranceCardNoInput) insuranceCardNoInput.removeAttribute('required');
            if (hiCategoryContainer) hiCategoryContainer.style.display = 'none';
            if (hiCategorySelect) hiCategorySelect.removeAttribute('required');
        }
    }

    // إتاحتها على النطاق العام
    window.setInsuranceType = setInsuranceType;

    document.querySelectorAll('.insurance-card-tile').forEach(tile => {
        tile.addEventListener('click', function(e) {
            const val = this.getAttribute('data-insurance');
            if (val) {
                setInsuranceType(val);
            }
        });
    });

    document.querySelectorAll('input[name="insurance_selector"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                setInsuranceType(this.value);
            }
        });
    });

    // تهيئة أولية عند تحميل الصفحة
    const insuranceTypeHidden = document.getElementById('insurance_type');
    setInsuranceType(insuranceTypeHidden ? (insuranceTypeHidden.value || 'none') : 'none');
});
</script>
@endpush

@section('styles')
<style>
/* تجميل وتنسيق الواجهة */
.header-icon-box {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(59, 130, 246, 0.22));
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(37, 99, 235, 0.2);
}

.patient-form-card {
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.04) !important;
}

.patient-form-card .form-control,
.patient-form-card .form-select {
    border-radius: 10px;
    border: 1.5px solid #d1d5db;
    padding: 0.65rem 1rem;
    font-size: 0.95rem;
    transition: all 0.2s ease-in-out;
    background-color: #fbfcfe;
}

.patient-form-card .form-control:focus,
.patient-form-card .form-select:focus {
    background-color: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.section-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    background: #f1f5f9;
    border-radius: 30px;
    border: 1px solid #e2e8f0;
}

.step-num {
    width: 22px;
    height: 22px;
    background: #2563eb;
    color: #fff;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
}

.step-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #334155;
}

/* بطاقات اختيار التأمين */
.insurance-card-tile {
    background: #ffffff;
    border-color: #e2e8f0 !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.insurance-card-tile:hover {
    border-color: #93c5fd !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(37, 99, 235, 0.08);
}

.insurance-card-tile.active-tile {
    background: linear-gradient(145deg, #eff6ff 0%, #ffffff 100%);
    border-color: #2563eb !important;
    box-shadow: 0 0 0 2px #2563eb, 0 8px 20px rgba(37, 99, 235, 0.15) !important;
}

.tile-icon-circle {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease;
}

.insurance-card-tile:hover .tile-icon-circle {
    transform: scale(1.08);
}

.text-xs {
    font-size: 0.72rem;
    line-height: 1.2;
}

.btn-save-patient {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    border: none;
    transition: all 0.25s ease;
}

.btn-save-patient:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3) !important;
}

.cursor-pointer {
    cursor: pointer;
}

.transition-all {
    transition: all 0.2s ease;
}
</style>
@endsection