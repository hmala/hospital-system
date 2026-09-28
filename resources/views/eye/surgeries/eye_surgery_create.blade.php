@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h3 fw-bold text-primary mb-1">
                <i class="fas fa-calendar-plus me-2"></i>حجز عملية جراحية / جلسة حقن عيون
            </h2>
            <p class="text-muted mb-0 small">تسجيل مواعيد العمليات، اختيار العدسات داخل العين (IOL) وخصمها من مخزن العيون، وحقن الشبكية</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('eye.surgeries.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i>رجوع للجدول
            </a>
            <a href="{{ route('eye.store.index') }}" class="btn btn-outline-info">
                <i class="fas fa-boxes me-1"></i>أرصدة مخزن العيون
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('eye.surgeries.store') }}" method="POST" id="eyeSurgeryForm">
        @csrf

        <div class="row g-4">
            <!-- Left Column: Patient & Basic Surgery Data -->
            <div class="col-lg-7">
                <!-- Patient Selection Card -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-user-injured text-primary me-2"></i>بيانات المريض</h5>
                    </div>
                    <div class="card-body">
                        @if($patient)
                            <input type="hidden" name="patient_id" id="selected_patient_id" value="{{ $patient->id }}">
                            <div class="alert alert-primary d-flex justify-content-between align-items-center mb-0">
                                <div>
                                    <h5 class="fw-bold mb-1">{{ $patient->name }}</h5>
                                    <div class="small text-muted">
                                        <span>MRN: <strong>{{ $patient->medical_record_number ?? 'N/A' }}</strong></span> • 
                                        <span>العمر: {{ $patient->age ?? '-' }} سنة</span> • 
                                        <span>الجنس: {{ $patient->gender === 'male' ? 'ذكر' : 'أنثى' }}</span> • 
                                        <span>الهاتف: {{ $patient->phone ?? '-' }}</span>
                                    </div>
                                </div>
                                <span class="badge bg-success px-3 py-2 fs-6">مريض محدد</span>
                            </div>
                        @else
                            <input type="hidden" name="patient_id" id="selected_patient_id" value="{{ old('patient_id') }}" required>
                            
                            <!-- Search Input -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">البحث عن المريض <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" id="patientSearchInput" class="form-control" placeholder="اكتب اسم المريض، رقم الهوية، أو الهاتف..." autocomplete="off">
                                </div>
                                <div id="patientSearchResults" class="list-group mt-1 shadow-sm position-absolute w-100" style="z-index: 1050; display: none; max-height: 250px; overflow-y: auto;"></div>
                            </div>

                            <!-- Selected Patient Display -->
                            <div id="selectedPatientCard" class="alert alert-info d-flex justify-content-between align-items-center mb-0" style="display: none !important;">
                                <div>
                                    <h6 class="fw-bold mb-1" id="patientNameText"></h6>
                                    <div class="small text-muted" id="patientDetailsText"></div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="clearPatientBtn">
                                    <i class="fas fa-times me-1"></i>تغيير
                                </button>
                            </div>
                        @endif
                        @error('patient_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Procedure Details Card -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-syringe text-primary me-2"></i>تفاصيل العملية الجراحية</h5>
                    </div>
                    <div class="card-body">
                        <!-- Quick Presets -->
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold">اختيار سريع للعملية الشائعة:</label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary preset-btn" data-procedure="سحب ماء أبيض مع زراعة عدسة مطوية (Phaco + IOL)" data-type="cataract" data-anesthesia="تخدير موضعي قطرة (Topical)">
                                    <i class="fas fa-eye me-1"></i>ماء أبيض (فاكو + عدسة)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info preset-btn" data-procedure="حقن داخل الجسم الزجاجي (Intravitreal Anti-VEGF)" data-type="injection" data-anesthesia="تخدير موضعي قطرة (Topical)">
                                    <i class="fas fa-syringe me-1"></i>حقن شبكية زجاجي
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn" data-procedure="قطع السائل الزجاجي (Pars Plana Vitrectomy - PPV)" data-type="vitrectomy" data-anesthesia="تخدير خلف المقلة (Retrobulbar)">
                                    <i class="fas fa-tools me-1"></i>قص زجاجي (PPV)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success preset-btn" data-procedure="عملية ترشيح ماء أسود (Trabeculectomy with MMC)" data-type="glaucoma" data-anesthesia="تخدير موضعي تسكيني (Sub-Tenon)">
                                    <i class="fas fa-tint me-1"></i>جراحة زرق (Trabeculectomy)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning text-dark preset-btn" data-procedure="إزالة ظفرة مع رقعة ملتحمة (Pterygium + Autograft)" data-type="pterygium" data-anesthesia="تخدير موضعي (Local)">
                                    <i class="fas fa-cut me-1"></i>إزالة ظفرة (Pterygium)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-dark preset-btn" data-procedure="تثبيت القرنية المخروطية (Corneal Cross-Linking - CXL)" data-type="cxl" data-anesthesia="تخدير موضعي قطرة (Topical)">
                                    <i class="fas fa-sun me-1"></i>تثبيت قرنية (CXL)
                                </button>
                            </div>
                        </div>

                        <!-- Procedure Name Input -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">اسم الإجراء الجراحي <span class="text-danger">*</span></label>
                            <input type="text" name="procedure_name" id="procedure_name" class="form-control form-control-lg" placeholder="مثال: سحب ماء أبيض مع زراعة عدسة..." value="{{ old('procedure_name') }}" required>
                            @error('procedure_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Target Eye Laterality Selector -->
                        <div class="mb-4">
                            <label class="form-label fw-bold d-block">العين المستهدفة للجراحة <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="target_eye" id="eye_od" value="OD" {{ old('target_eye', 'OD') === 'OD' ? 'checked' : '' }} required>
                                    <label class="btn btn-outline-success w-100 py-3 text-center fw-bold shadow-sm" for="eye_od">
                                        <i class="fas fa-eye fa-2x d-block mb-1"></i>
                                        <span>العين اليمنى</span>
                                        <div class="small fw-normal">OD (Oculus Dexter)</div>
                                    </label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="target_eye" id="eye_os" value="OS" {{ old('target_eye') === 'OS' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-info w-100 py-3 text-center fw-bold shadow-sm" for="eye_os">
                                        <i class="fas fa-eye fa-2x d-block mb-1"></i>
                                        <span>العين اليسرى</span>
                                        <div class="small fw-normal">OS (Oculus Sinister)</div>
                                    </label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="target_eye" id="eye_ou" value="OU" {{ old('target_eye') === 'OU' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary w-100 py-3 text-center fw-bold shadow-sm" for="eye_ou">
                                        <i class="fas fa-glasses fa-2x d-block mb-1"></i>
                                        <span>كلتا العينين</span>
                                        <div class="small fw-normal">OU (Both Eyes)</div>
                                    </label>
                                </div>
                            </div>
                            @error('target_eye')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Timing & Doctor -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">موعد العملية وتاريخها <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="surgery_date" class="form-control" value="{{ old('surgery_date', now()->addDay()->format('Y-m-d\T09:00')) }}" required>
                                @error('surgery_date')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">الطبيب الجراح</label>
                                <select name="doctor_id" class="form-select">
                                    <option value="">-- اختيـار الجراح المسؤول --</option>
                                    @foreach($doctors as $doc)
                                        <option value="{{ $doc->id }}" {{ old('doctor_id') == $doc->id ? 'selected' : '' }}>
                                            د. {{ $doc->user->name ?? 'طبيب' }} - {{ $doc->specialization ?? 'جراحة عيون' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Anesthesia Type -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">نوع التخدير المتبع <span class="text-danger">*</span></label>
                            <select name="anesthesia_type" id="anesthesia_type" class="form-select" required>
                                <option value="تخدير موضعي قطرة (Topical)" {{ old('anesthesia_type') === 'تخدير موضعي قطرة (Topical)' ? 'selected' : '' }}>تخدير موضعي قطرات (Topical Anesthesia)</option>
                                <option value="تخدير خلف المقلة (Retrobulbar Block)" {{ old('anesthesia_type') === 'تخدير خلف المقلة (Retrobulbar Block)' ? 'selected' : '' }}>تخدير خلف المقلة (Retrobulbar / Peribulbar Block)</option>
                                <option value="تخدير موضعي تسكيني (Sub-Tenon / Local)" {{ old('anesthesia_type') === 'تخدير موضعي تسكيني (Sub-Tenon / Local)' ? 'selected' : '' }}>تخدير موضعي تحت التينون (Sub-Tenon's Local)</option>
                                <option value="تخدير عام (General Anesthesia)" {{ old('anesthesia_type') === 'تخدير عام (General Anesthesia)' ? 'selected' : '' }}>تخدير عام كامل (General Anesthesia)</option>
                            </select>
                            @error('anesthesia_type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Implants, Drugs, and Notes -->
            <div class="col-lg-5">
                <!-- Cataract IOL Lens Selection Card -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 border-top border-4 border-teal" style="border-top-color: #0f766e !important;">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold" style="color: #0f766e;"><i class="fas fa-circle-notch me-2"></i>زراعة العدسة داخل العين (IOL)</h5>
                        <span class="badge bg-light text-muted border">خاص بعمليات الفاكو</span>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light border small text-muted mb-3">
                            <i class="fas fa-info-circle text-primary me-1"></i>عند اختيار عدسة من القائمة أدناه، سيتم <strong>خصم قطعة واحدة تلقائياً</strong> من رصيد مخزن العيون وتوليد سند الصرف فور حفظ العملية.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">اختر العدسة من رصيد مخزن العيون:</label>
                            <select name="iol_item_id" id="iol_item_id" class="form-select">
                                <option value="">-- بدون عدسة / يتم تحديدها لاحقاً --</option>
                                @foreach($iolItems as $iol)
                                    <option value="{{ $iol->id }}" data-diopter="{{ $iol->diopter }}" {{ old('iol_item_id') == $iol->id ? 'selected' : '' }}>
                                        {{ $iol->item_name }} (قوة: {{ $iol->diopter ? '+' . $iol->diopter . ' D' : 'N/A' }}) - المتاح: {{ $iol->current_stock }}
                                    </option>
                                @endforeach
                            </select>
                            @error('iol_item_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold small">قوة العدسة المطلوبة (Power)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">+</span>
                                    <input type="number" step="0.25" name="iol_power" id="iol_power" class="form-control" placeholder="21.50" value="{{ old('iol_power') }}">
                                    <span class="input-group-text bg-light">D</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold small">الرقم التسلسلي للعدسة (S/N)</label>
                                <input type="text" name="iol_serial_number" class="form-control" placeholder="Serial Number" value="{{ old('iol_serial_number') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Retinal Injection Drug Section -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 border-top border-4 border-indigo" style="border-top-color: #4338ca !important;">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold" style="color: #4338ca;"><i class="fas fa-syringe me-2"></i>عقار الحقن الشبكي (Intravitreal)</h5>
                        <span class="badge bg-light text-muted border">Anti-VEGF / ستيرويد</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">العقار المحقون</label>
                            <input type="text" list="injectionDrugsList" name="injection_drug" id="injection_drug" class="form-control" placeholder="مثال: Eylea (Aflibercept) 2mg / 0.05ml" value="{{ old('injection_drug') }}">
                            <datalist id="injectionDrugsList">
                                <option value="Eylea (Aflibercept) 2mg / 0.05ml">
                                <option value="Lucentis (Ranibizumab) 0.5mg / 0.05ml">
                                <option value="Avastin (Bevacizumab) 1.25mg / 0.05ml">
                                <option value="Triamcinolone Acetonide (Kenacort) 4mg / 0.1ml">
                                <option value="Ozurdex (Dexamethasone Implant) 0.7mg">
                                <option value="Vabysmo (Faricimab) 6mg / 0.05ml">
                            </datalist>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold small">الجرعة / الكمية المحقونة</label>
                            <input type="text" name="injection_dose" class="form-control" placeholder="0.05 ml (4mm pars plana)" value="{{ old('injection_dose', '0.05 ml') }}">
                        </div>
                    </div>
                </div>

                <!-- Pre-op & Operative Notes -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-clipboard-list text-primary me-2"></i>ملاحظات وتوجيهات ما قبل الجراحة</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="operative_notes" rows="4" class="form-control" placeholder="أي اشتراطات خاصة بالعملية، التحضير (توسيع الحدقة، إيقاف مميعات الدم، ضغط الدم، السكر)...">{{ old('operative_notes') }}</textarea>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg shadow-sm py-3 fw-bold">
                        <i class="fas fa-save me-2"></i>حفظ وحجز العملية وتأكيد الصرف
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Quick Presets
    const presetButtons = document.querySelectorAll('.preset-btn');
    const procedureInput = document.getElementById('procedure_name');
    const anesthesiaSelect = document.getElementById('anesthesia_type');

    presetButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            procedureInput.value = this.dataset.procedure;
            if (this.dataset.anesthesia) {
                anesthesiaSelect.value = this.dataset.anesthesia;
            }
        });
    });

    // Auto-fill IOL diopter when selecting lens from store
    const iolSelect = document.getElementById('iol_item_id');
    const iolPowerInput = document.getElementById('iol_power');

    iolSelect.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const diopter = selectedOption.dataset.diopter;
        if (diopter && !iolPowerInput.value) {
            iolPowerInput.value = diopter;
        }
    });

    // Patient AJAX Search (if not preselected)
    const patientSearchInput = document.getElementById('patientSearchInput');
    const patientSearchResults = document.getElementById('patientSearchResults');
    const selectedPatientId = document.getElementById('selected_patient_id');
    const selectedPatientCard = document.getElementById('selectedPatientCard');
    const patientNameText = document.getElementById('patientNameText');
    const patientDetailsText = document.getElementById('patientDetailsText');
    const clearPatientBtn = document.getElementById('clearPatientBtn');

    if (patientSearchInput) {
        let debounceTimer;
        patientSearchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            if (query.length < 2) {
                patientSearchResults.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`{{ route('eye.reception.searchPatient') }}?query=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        patientSearchResults.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(p => {
                                const item = document.createElement('a');
                                item.href = 'javascript:void(0);';
                                item.className = 'list-group-item list-group-item-action py-2';
                                item.innerHTML = `
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-primary">${p.name}</span>
                                        <span class="badge bg-light text-muted border">${p.mrn}</span>
                                    </div>
                                    <div class="small text-muted">${p.phone || 'بدون هاتف'} • ${p.age ? p.age + ' سنة' : ''}</div>
                                `;
                                item.addEventListener('click', function () {
                                    selectedPatientId.value = p.id;
                                    patientNameText.textContent = p.name;
                                    patientDetailsText.textContent = `MRN: ${p.mrn} | العمر: ${p.age || '-'} سنة | الهاتف: ${p.phone || '-'}`;
                                    selectedPatientCard.style.setProperty('display', 'flex', 'important');
                                    patientSearchResults.style.display = 'none';
                                    patientSearchInput.value = '';
                                    patientSearchInput.style.display = 'none';
                                });
                                patientSearchResults.appendChild(item);
                            });
                            patientSearchResults.style.display = 'block';
                        } else {
                            patientSearchResults.innerHTML = '<div class="list-group-item py-2 text-muted small">لم يتم العثور على مريض مطابق</div>';
                            patientSearchResults.style.display = 'block';
                        }
                    })
                    .catch(err => console.error(err));
            }, 300);
        });

        if (clearPatientBtn) {
            clearPatientBtn.addEventListener('click', function () {
                selectedPatientId.value = '';
                selectedPatientCard.style.setProperty('display', 'none', 'important');
                patientSearchInput.style.display = 'block';
                patientSearchInput.focus();
            });
        }
    }
});
</script>
@endpush
@endsection
