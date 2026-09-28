@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-primary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 fw-bold"><i class="fas fa-calendar-plus me-2"></i>حجز موعد كشف / فحص عيون جديد</h4>
                        <a href="{{ route('eye.reception.index') }}" class="btn btn-sm btn-light text-primary fw-semibold">
                            <i class="fas fa-arrow-right me-1"></i>العودة لطابور العيون
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('eye.reception.store') }}" method="POST" id="eyeAppointmentForm">
                        @csrf

                        <!-- اختيار المريض (بحث تفاعلي) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">المريض <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                                    <input type="text" id="patientSearchInput" class="form-control" placeholder="ابحث باسم المريض أو هاتفه أو رقم الملف الطبي..." autocomplete="off">
                                </div>
                                <div id="patientSearchResults" class="list-group position-absolute w-100 shadow-sm mt-1 d-none" style="z-index: 1050; max-height: 250px; overflow-y: auto;"></div>
                            </div>
                            <input type="hidden" name="patient_id" id="selectedPatientId" required>

                            <!-- كارت تفاصيل المريض المختار -->
                            <div id="selectedPatientCard" class="card bg-light border-primary-subtle mt-2 d-none">
                                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-primary fs-5" id="displayPatientName">-</div>
                                        <div class="small text-muted" id="displayPatientDetails">-</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearPatient">
                                        <i class="fas fa-times me-1"></i>تغيير المريض
                                    </button>
                                </div>
                            </div>
                            @error('patient_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- نوع المراجعة والطبيب -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">نوع المراجعة <span class="text-danger">*</span></label>
                                <select name="visit_type" class="form-select" required id="visitTypeSelect">
                                    <option value="consultation" selected>كشف استشاري عيون (15,000 د.ع)</option>
                                    <option value="optometry">فحص بصريات وقياس نظارة (10,000 د.ع)</option>
                                    <option value="investigation">فحص أجهزة تشخيصية (OCT / ساحة بصرية)</option>
                                    <option value="procedure">إجراء / جلسة حقن شبكية</option>
                                    <option value="follow_up">مراجعة دورية مجانية</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">طبيب العيون المعالج</label>
                                <select name="doctor_id" class="form-select">
                                    <option value="">-- أي طبيب عيون متاح --</option>
                                    @foreach($doctors as $doc)
                                        <option value="{{ $doc->id }}">{{ $doc->user->name ?? 'طبيب' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- نوع التأمين والرسوم المقررة -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">فئة الدفع والتأمين <span class="text-danger">*</span></label>
                                <select name="insurance_type" class="form-select" id="insuranceTypeSelect" required>
                                    <option value="cash" selected>دفع نقدي كامل (Cash)</option>
                                    <option value="health_insurance">الضمان الصحي العراقي (10% نسبة المريض)</option>
                                    <option value="moi">قوى الأمن الداخلي / وزارة الداخلية</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">مبلغ الكشف المقدر (د.ع)</label>
                                <div class="input-group">
                                    <input type="number" name="fee_amount" id="feeAmountInput" class="form-control" value="15000" min="0" step="500">
                                    <span class="input-group-text">د.ع</span>
                                </div>
                                <div class="small text-muted mt-1" id="feeHint">يتم تحويل الفاتورة مباشرة لكاشير العيون للتحصيل</div>
                            </div>
                        </div>

                        <!-- الشكوى الرئيسية -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">الشكوى الرئيسية وأعراض المريض</label>
                            <textarea name="chief_complaint" class="form-control" rows="3" placeholder="مثال: غواش وعدم وضوح في الرؤية بالعين اليمنى منذ أسبوعين، احمرار مع ألم خفيف..."></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                            <a href="{{ route('eye.reception.index') }}" class="btn btn-light px-4">إلغاء</a>
                            <button type="submit" class="btn btn-primary px-5 fw-bold" id="btnSubmit">
                                <i class="fas fa-check-circle me-1"></i>تأكيد الحجز وتوجيه المريض للكاشير
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('patientSearchInput');
    const resultsContainer = document.getElementById('patientSearchResults');
    const patientIdInput = document.getElementById('selectedPatientId');
    const patientCard = document.getElementById('selectedPatientCard');
    const displayName = document.getElementById('displayPatientName');
    const displayDetails = document.getElementById('displayPatientDetails');
    const btnClear = document.getElementById('btnClearPatient');
    const visitTypeSelect = document.getElementById('visitTypeSelect');
    const feeInput = document.getElementById('feeAmountInput');

    // تحديث الرسوم التلقائية عند تغيير نوع المراجعة
    visitTypeSelect.addEventListener('change', function () {
        if (this.value === 'consultation') feeInput.value = 15000;
        else if (this.value === 'optometry') feeInput.value = 10000;
        else if (this.value === 'investigation') feeInput.value = 25000;
        else if (this.value === 'follow_up') feeInput.value = 0;
    });

    let timeout = null;
    searchInput.addEventListener('input', function () {
        clearTimeout(timeout);
        const term = this.value.trim();
        if (term.length < 2) {
            resultsContainer.classList.add('d-none');
            return;
        }

        timeout = setTimeout(() => {
            fetch(`{{ route('eye.reception.searchPatients') }}?term=${encodeURIComponent(term)}`)
                .then(res => res.json())
                .then(data => {
                    resultsContainer.innerHTML = '';
                    if (data.length === 0) {
                        resultsContainer.innerHTML = '<div class="list-group-item text-muted text-center py-3">لا توجد نتائج مطابقة</div>';
                    } else {
                        data.forEach(p => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2';
                            item.innerHTML = `
                                <div>
                                    <div class="fw-bold text-dark">${p.name}</div>
                                    <div class="small text-muted">${p.phone || 'بدون هاتف'} | ${p.gender === 'male' ? 'ذكر' : 'أنثى'} | ${p.age || '-'} سنة</div>
                                </div>
                                <span class="badge bg-primary-subtle text-primary">اختيار</span>
                            `;
                            item.addEventListener('click', function (e) {
                                e.preventDefault();
                                selectPatient(p);
                            });
                            resultsContainer.appendChild(item);
                        });
                    }
                    resultsContainer.classList.remove('d-none');
                });
        }, 300);
    });

    function selectPatient(patient) {
        patientIdInput.value = patient.id;
        displayName.textContent = patient.name;
        displayDetails.textContent = `الهاتف: ${patient.phone || 'غير مسجل'} | الجنس: ${patient.gender === 'male' ? 'ذكر' : 'أنثى'} | العمر: ${patient.age || '-'} سنة`;
        
        patientCard.classList.remove('d-none');
        searchInput.closest('.position-relative').classList.add('d-none');
        resultsContainer.classList.add('d-none');
    }

    btnClear.addEventListener('click', function () {
        patientIdInput.value = '';
        patientCard.classList.add('d-none');
        searchInput.closest('.position-relative').classList.remove('d-none');
        searchInput.value = '';
        searchInput.focus();
    });

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.classList.add('d-none');
        }
    });
});
</script>
@endpush
@endsection
