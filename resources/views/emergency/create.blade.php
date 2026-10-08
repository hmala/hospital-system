<!-- resources/views/emergency/create.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-plus-circle me-2"></i>
                    إضافة حالة طوارئ جديدة
                </h2>
                @php
                    $isFromInquiry = request('return_to') === 'inquiry' || request('from_inquiry') || request('from_referral_id') || auth()->user()->hasRole(['receptionist', 'inquiry_staff', 'consultation_receptionist']) || !auth()->user()->can('view emergencies');
                    $backUrl = $isFromInquiry ? route('inquiry.index') : route('emergency.index');
                    $backLabel = $isFromInquiry ? 'العودة للاستعلامات' : 'العودة لقائمة الطوارئ';
                @endphp
                <a href="{{ $backUrl }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>{{ $backLabel }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10">
            @if(isset($referralRequest) && $referralRequest)
                <div class="alert alert-danger border-2 border-danger shadow-sm rounded-3 p-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2 animate__animated animate__fadeIn">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-danger text-white p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-ambulance fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-danger mb-1">
                                📥 إحالة طوارئ وتمريض واردة من: د. {{ optional(optional(optional($referralRequest->visit)->doctor)->user)->name ?? 'الاستشاري' }}
                            </h6>
                            <p class="mb-0 small text-dark">
                                <strong>الخدمات والتوجيهات المطلوبة:</strong> {{ $referralRequest->description }}
                            </p>
                        </div>
                    </div>
                    <span class="badge bg-danger text-white px-3 py-2 fs-7 rounded-pill">
                        الأولوية: {{ $referralRequest->priority ?? 'عاجل' }}
                    </span>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">بيانات حالة الطوارئ</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('emergency.store') }}">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ request('return_to', $isFromInquiry ? 'inquiry' : 'emergency') }}">
                        @if(isset($referralRequest) && $referralRequest)
                            <input type="hidden" name="referral_request_id" value="{{ $referralRequest->id }}">
                        @endif

                        <div class="row">
                            <!-- اختيار المريض التفاعلي بالبحث المباشر -->
                            <div class="col-md-6 mb-3">
                                <label for="patientSearchInput" class="form-label fw-bold">المريض <span class="text-danger">*</span></label>
                                
                                <!-- الحقل المخفي لـ patient_id -->
                                <input type="hidden" name="patient_id" id="patient_id" value="{{ old('patient_id', $selectedPatient->id ?? '') }}">

                                <!-- واجهة البحث المباشر -->
                                <div id="patientSearchContainer" style="{{ (old('patient_id') || isset($selectedPatient)) && !old('new_patient_name') ? 'display: none;' : '' }}">
                                    <div class="position-relative">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-primary"></i></span>
                                            <input type="text" 
                                                   class="form-control border-start-0 @error('patient_id') is-invalid @enderror" 
                                                   id="patientSearchInput" 
                                                   placeholder="ابحث باسم المريض، رقم الهاتف، أو الرقم الطبي / الوطني..."
                                                   autocomplete="off">
                                            <button type="button" class="btn btn-outline-info" id="newPatientBtn" title="تسجيل مريض جديد غير مسجل">
                                                <i class="fas fa-user-plus me-1"></i> مريض جديد
                                            </button>
                                        </div>
                                        <div id="patientSearchSpinner" class="position-absolute" style="left: 125px; top: 10px; display: none;">
                                            <span class="spinner-border spinner-border-sm text-primary"></span>
                                        </div>
                                        <!-- قائمة نتائج البحث المنبثقة -->
                                        <div id="patientSearchResults" class="list-group position-absolute w-100 shadow-lg mt-1" style="z-index: 1050; max-height: 280px; overflow-y: auto; display: none;"></div>
                                    </div>
                                    <div class="form-text text-muted mt-1"><i class="fas fa-info-circle me-1"></i>اكتب حرفين أو أكثر للبحث السريع وعرض النتائج المطابقة فورياً.</div>
                                </div>

                                <!-- بطاقة المريض المختار -->
                                <div id="selectedPatientCard" class="card border-primary bg-light mb-2 shadow-sm" style="{{ (old('patient_id') || isset($selectedPatient)) && !old('new_patient_name') ? '' : 'display: none;' }}">
                                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                                <i class="fas fa-user fa-lg"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-primary" id="selectedPatientName">{{ $selectedPatient->user->name ?? '' }}</h6>
                                                <div class="small text-muted d-flex flex-wrap gap-2 mt-1 align-items-center">
                                                    <span><i class="fas fa-phone-alt me-1 text-secondary"></i><span id="selectedPatientPhone">{{ $selectedPatient->user->phone ?? ($selectedPatient->phone ?? 'لا يوجد') }}</span></span>
                                                    <span class="badge bg-secondary" id="selectedPatientIdBadge">#{{ $selectedPatient->id ?? '' }}</span>
                                                    @if(isset($selectedPatient) && $selectedPatient->national_id)
                                                        <span class="badge bg-info text-dark" id="selectedPatientRefBadge">{{ $selectedPatient->national_id }}</span>
                                                    @endif
                                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25" id="selectedPatientInsuranceBadge" style="{{ isset($selectedPatient) && ($selectedPatient->healthInsuranceCategory || ($selectedPatient->insurance_type && $selectedPatient->insurance_type !== 'none')) ? '' : 'display: none;' }}">
                                                        <i class="fas fa-shield-alt me-1"></i><span id="selectedPatientInsuranceText">{{ $selectedPatient->healthInsuranceCategory->name ?? 'مشمول بالضمان الصحي' }}</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger btn-sm" id="clearSelectedPatientBtn">
                                            <i class="fas fa-times me-1"></i> تغيير المريض
                                        </button>
                                    </div>
                                </div>

                                @error('patient_id')
                                    <div class="text-danger small mt-1"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- قسم تحديد تغطية الضمان للطوارئ -->
                            <div class="col-md-12 mb-3" id="emergencyInsuranceSection" style="{{ isset($selectedPatient) && ($selectedPatient->healthInsuranceCategory || ($selectedPatient->insurance_type && $selectedPatient->insurance_type !== 'none')) ? '' : 'display: none;' }}">
                                <div class="card border-success bg-success-subtle bg-opacity-10 p-3 shadow-sm rounded-3">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div>
                                            <h6 class="fw-bold text-success mb-1">
                                                <i class="fas fa-shield-alt me-1"></i> مظلة الضمان الصحي للمريض: <span id="selectedPatientInsuranceCategoryText">{{ $selectedPatient->healthInsuranceCategory->name ?? ($selectedPatient && $selectedPatient->insurance_type !== 'none' ? 'مشمول بالضمان الصحي' : '') }}</span>
                                            </h6>
                                            <p class="small text-muted mb-0">
                                                حدد ما إذا كانت هذه الحالة الطارئة والخدمات المقدمة سيتم احتسابها على ذمة الضمان الصحي أم حساب كاش مباشر للمريض.
                                            </p>
                                        </div>
                                        <div class="d-flex align-items-center gap-3 bg-white p-2 rounded-3 border shadow-xs">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="radio" name="apply_insurance" id="apply_insurance_yes" value="1" {{ old('apply_insurance', '1') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold text-success cursor-pointer" for="apply_insurance_yes">
                                                    🛡️ حجز تحت مظلة الضمان
                                                </label>
                                            </div>
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="radio" name="apply_insurance" id="apply_insurance_no" value="0" {{ old('apply_insurance') === '0' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold text-secondary cursor-pointer" for="apply_insurance_no">
                                                    💵 حجز كاش عادي (بدون ضمان)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- حقول إنشاء مريض جديد -->
                            <div class="col-md-12 mb-3" id="newPatientFields" style="{{ old('new_patient_name') ? '' : 'display: none;' }}">
                                <div class="card border-info p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0 text-info fw-bold"><i class="fas fa-user-plus me-1"></i>بيانات المريض الجديد غير المسجل</h6>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="cancelNewPatientBtn">
                                            <i class="fas fa-arrow-right me-1"></i> العودة لاختيار مريض مسجل
                                        </button>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">الاسم الكامل *</label>
                                            <input type="text" class="form-control @error('new_patient_name') is-invalid @enderror" name="new_patient_name" id="new_patient_name" value="{{ old('new_patient_name') }}">
                                            @error('new_patient_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">رقم الهاتف</label>
                                            <input type="text" class="form-control @error('new_patient_phone') is-invalid @enderror" name="new_patient_phone" id="new_patient_phone" value="{{ old('new_patient_phone') }}">
                                            @error('new_patient_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">الجنس</label>
                                            <select class="form-select @error('new_patient_gender') is-invalid @enderror" name="new_patient_gender" id="new_patient_gender">
                                                <option value="" @selected(old('new_patient_gender')=='')>غير محدد</option>
                                                <option value="male" @selected(old('new_patient_gender')=='male')>ذكر</option>
                                                <option value="female" @selected(old('new_patient_gender')=='female')>أنثى</option>
                                            </select>
                                            @error('new_patient_gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">تاريخ الميلاد</label>
                                            <input type="date" class="form-control @error('new_patient_dob') is-invalid @enderror" name="new_patient_dob" id="new_patient_dob" value="{{ old('new_patient_dob') }}">
                                            @error('new_patient_dob')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>


                        <div class="row">
                            <!-- الأولوية -->
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">الأولوية *</label>
                                <select class="form-select @error('priority') is-invalid @enderror"
                                        id="priority"
                                        name="priority"
                                        required>
                                    <option value="">اختر الأولوية</option>
                                    <option value="critical" {{ old('priority') == 'critical' ? 'selected' : '' }}>حرجة</option>
                                    <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>عاجلة</option>
                                    <option value="semi_urgent" {{ old('priority') == 'semi_urgent' ? 'selected' : '' }}>شبه عاجلة</option>
                                    <option value="non_urgent" {{ old('priority') == 'non_urgent' ? 'selected' : '' }}>غير عاجلة</option>
                                </select>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- الطبيب المسؤول (نفس المستخدم المسجل حالياً) -->
                            <input type="hidden" name="doctor_id" id="doctor_id" value="{{ $assignedDoctor->id ?? '' }}">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">الطبيب المسؤول</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-primary"><i class="fas fa-user-md"></i></span>
                                    <input type="text" class="form-control bg-light text-primary fw-bold" 
                                           value="{{ auth()->user()->name ?? 'المستخدم الحالي' }}" 
                                           readonly>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3 d-flex align-items-center">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input @error('doctor_follow_up') is-invalid @enderror" type="checkbox" id="doctor_follow_up" name="doctor_follow_up" value="1" {{ old('doctor_follow_up') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="doctor_follow_up">
                                        متابعة الطبيب <span class="text-muted">(+30,000 IQD)</span>
                                    </label>
                                </div>
                                @error('doctor_follow_up')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>ملاحظة:</strong> يمكنك إضافة العلامات الحيوية بعد إنشاء الحالة من صفحة التفاصيل
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('emergency.index') }}" class="btn btn-secondary me-2">إلغاء</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>حفظ حالة الطوارئ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('patientSearchInput');
    const searchResults = document.getElementById('patientSearchResults');
    const searchSpinner = document.getElementById('patientSearchSpinner');
    const searchContainer = document.getElementById('patientSearchContainer');
    const hiddenPatientId = document.getElementById('patient_id');
    const selectedCard = document.getElementById('selectedPatientCard');
    const selectedName = document.getElementById('selectedPatientName');
    const selectedPhone = document.getElementById('selectedPatientPhone');
    const selectedIdBadge = document.getElementById('selectedPatientIdBadge');
    const clearPatientBtn = document.getElementById('clearSelectedPatientBtn');
    
    const newPatientBtn = document.getElementById('newPatientBtn');
    const cancelNewPatientBtn = document.getElementById('cancelNewPatientBtn');
    const newPatientFields = document.getElementById('newPatientFields');
    const newPatientNameInput = document.getElementById('new_patient_name');

    let searchTimeout = null;

    // البحث المباشر
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(searchTimeout);

            if (query.length < 1) {
                searchResults.innerHTML = '';
                searchResults.style.display = 'none';
                searchSpinner.style.display = 'none';
                return;
            }

            searchSpinner.style.display = 'block';

            searchTimeout = setTimeout(() => {
                fetch(`{{ route('emergency.search-patients') }}?query=${encodeURIComponent(query)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('HTTP status ' + res.status);
                    }
                    return res.json();
                })
                .then(patients => {
                    searchSpinner.style.display = 'none';
                    if (!Array.isArray(patients) || patients.length === 0) {
                        searchResults.innerHTML = `
                            <div class="list-group-item text-center text-muted py-3">
                                <i class="fas fa-user-slash me-1"></i> لم يتم العثور على مريض مطابق. 
                                <button type="button" class="btn btn-link btn-sm p-0 text-info fw-bold" id="quickNewPatientBtn">تسجيله كمريض جديد؟</button>
                            </div>
                        `;
                        searchResults.style.display = 'block';

                        const quickBtn = document.getElementById('quickNewPatientBtn');
                        if (quickBtn) {
                            quickBtn.addEventListener('click', () => {
                                showNewPatientMode(query);
                            });
                        }
                        return;
                    }

                    // حفظ مؤقت للمرضى في خريطة لتجنب أخطاء الاقتباس
                    window.currentPatientsMap = {};
                    patients.forEach(p => {
                        window.currentPatientsMap[p.id] = p;
                    });

                    searchResults.innerHTML = patients.map(p => `
                        <button type="button" 
                                class="list-group-item list-group-item-action text-end d-flex justify-content-between align-items-center py-2 patient-result-item" 
                                data-patient-id="${p.id}">
                            <div>
                                <div class="fw-bold text-primary mb-1 d-flex align-items-center gap-2">
                                    <span><i class="fas fa-user-circle me-1"></i>${p.name}</span>
                                    ${p.is_insurance ? `<span class="badge bg-success-subtle text-success border border-success border-opacity-25 py-0 px-1" style="font-size: 0.7rem;"><i class="fas fa-shield-alt me-1"></i>${p.insurance_name || 'ضمان صحي'}</span>` : ''}
                                </div>
                                <div class="small text-muted">
                                    <i class="fas fa-phone-alt me-1 text-secondary"></i>${p.phone} 
                                    ${p.age ? ` | <i class="fas fa-birthday-cake me-1 text-secondary"></i>${p.age} سنة (${p.gender})` : ''}
                                </div>
                            </div>
                            <div class="text-start">
                                <span class="badge bg-primary">#${p.id}</span>
                                ${p.national_id ? `<span class="badge bg-secondary ms-1">${p.national_id}</span>` : ''}
                            </div>
                        </button>
                    `).join('');

                    searchResults.style.display = 'block';

                    // ربط أحداث النقر على المرضى
                    document.querySelectorAll('.patient-result-item').forEach(item => {
                        item.addEventListener('click', function() {
                            const pid = this.getAttribute('data-patient-id');
                            const patient = window.currentPatientsMap ? window.currentPatientsMap[pid] : null;
                            if (patient) {
                                selectPatient(patient);
                            }
                        });
                    });
                })
                .catch(err => {
                    console.error('Search error:', err);
                    searchSpinner.style.display = 'none';
                });
            }, 250);
        });

        // إغلاق النتائج عند النقر خارجها
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }

    // دالة اختيار المريض
    function selectPatient(patient) {
        hiddenPatientId.value = patient.id;
        selectedName.textContent = patient.name;
        selectedPhone.textContent = patient.phone;
        selectedIdBadge.textContent = `#${patient.id}`;

        let refBadge = document.getElementById('selectedPatientRefBadge');
        if (!refBadge) {
            refBadge = document.createElement('span');
            refBadge.id = 'selectedPatientRefBadge';
            refBadge.className = 'badge bg-info text-dark';
            selectedIdBadge.parentNode.appendChild(refBadge);
        }
        if (patient.national_id) {
            refBadge.textContent = patient.national_id;
            refBadge.style.display = 'inline-block';
        } else {
            refBadge.style.display = 'none';
        }

        const insBadge = document.getElementById('selectedPatientInsuranceBadge');
        const insText = document.getElementById('selectedPatientInsuranceText');
        const insuranceSection = document.getElementById('emergencyInsuranceSection');
        const insuranceCatText = document.getElementById('selectedPatientInsuranceCategoryText');
        const applyYes = document.getElementById('apply_insurance_yes');
        const applyNo = document.getElementById('apply_insurance_no');

        if (patient.is_insurance) {
            if (insText) insText.textContent = patient.insurance_name || 'مشمول بالضمان الصحي';
            if (insBadge) insBadge.style.display = 'inline-block';
            if (insuranceCatText) insuranceCatText.textContent = patient.insurance_name || 'مشمول بالضمان الصحي';
            if (insuranceSection) insuranceSection.style.display = 'block';
            if (applyYes) applyYes.checked = true;
        } else {
            if (insBadge) insBadge.style.display = 'none';
            if (insuranceSection) insuranceSection.style.display = 'none';
            if (applyNo) applyNo.checked = true;
        }

        searchResults.style.display = 'none';
        searchResults.innerHTML = '';
        searchInput.value = '';
        searchContainer.style.display = 'none';
        selectedCard.style.display = 'block';

        // إخفاء حقول المريض الجديد إن كانت مفتوحة
        if (newPatientFields) {
            newPatientFields.style.display = 'none';
            clearNewPatientInputs();
        }
    }

    // زر تغيير المريض
    if (clearPatientBtn) {
        clearPatientBtn.addEventListener('click', function() {
            hiddenPatientId.value = '';
            selectedCard.style.display = 'none';
            const insuranceSection = document.getElementById('emergencyInsuranceSection');
            if (insuranceSection) insuranceSection.style.display = 'none';
            const applyNo = document.getElementById('apply_insurance_no');
            if (applyNo) applyNo.checked = true;
            searchContainer.style.display = 'block';
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
        });
    }

    // زر مريض جديد
    function showNewPatientMode(prefillName = '') {
        hiddenPatientId.value = '';
        selectedCard.style.display = 'none';
        searchContainer.style.display = 'none';
        searchResults.style.display = 'none';
        if (newPatientFields) {
            newPatientFields.style.display = 'block';
            if (prefillName && newPatientNameInput) {
                newPatientNameInput.value = prefillName;
            }
            if (newPatientNameInput) {
                newPatientNameInput.focus();
            }
        }
    }

    if (newPatientBtn) {
        newPatientBtn.addEventListener('click', function() {
            showNewPatientMode(searchInput ? searchInput.value.trim() : '');
        });
    }

    if (cancelNewPatientBtn) {
        cancelNewPatientBtn.addEventListener('click', function() {
            if (newPatientFields) {
                newPatientFields.style.display = 'none';
                clearNewPatientInputs();
            }
            searchContainer.style.display = 'block';
            if (searchInput) {
                searchInput.focus();
            }
        });
    }

    function clearNewPatientInputs() {
        if (newPatientNameInput) newPatientNameInput.value = '';
        const phone = document.getElementById('new_patient_phone');
        if (phone) phone.value = '';
        const dob = document.getElementById('new_patient_dob');
        if (dob) dob.value = '';
        const gender = document.getElementById('new_patient_gender');
        if (gender) gender.value = '';
    }

    // التحقق عند إرسال النموذج
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const isNewPatientActive = newPatientFields && newPatientFields.style.display === 'block';
            if (isNewPatientActive) {
                const name = newPatientNameInput ? newPatientNameInput.value.trim() : '';
                if (!name) {
                    e.preventDefault();
                    alert('يرجى إدخال اسم المريض الجديد');
                    newPatientNameInput.focus();
                    return;
                }
            } else {
                if (!hiddenPatientId.value) {
                    e.preventDefault();
                    alert('يرجى اختيار المريض من نتائج البحث أو تسجيل مريض جديد');
                    if (searchContainer.style.display === 'none') {
                        searchContainer.style.display = 'block';
                    }
                    if (searchInput) searchInput.focus();
                    return;
                }
            }
        });
    }
});
</script>
@endsection