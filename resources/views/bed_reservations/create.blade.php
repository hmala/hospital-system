@extends('layouts.app')

@section('styles')
<style>
/* specialized bed reservation look */
.bed-reservations-page {
    background-color: #f0f8ff;
}
.bed-reservations-page .card {
    border: 2px solid #17a2b8;
}
.bed-reservations-page h2 {
    color: #17a2b8;
}

/* نظام أرشفة مجاني 100% - يتصل بالسكانر مباشرة */

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

#bedReservationForm .mb-4 {
    background: #f0f4ff; /* light blue tone for contrast */
    border: 1px solid #ced4da;
    border-radius: 8px;
    padding: 1rem;
}

#bedReservationForm .form-control,
#bedReservationForm .form-select {
    border: 2px solid #4a8eff;
    border-radius: 6px;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    background: #ffffff;
}

#bedReservationForm .form-control:focus,
#bedReservationForm .form-select:focus {
    border-color: #0056d6;
    box-shadow: 0 0 0 0.3rem rgba(0,86,214,.25);
    background: #ffffff;
}

/* force all bed reservation accordion panels to stay closed initially */
#bedReservationAccordion .accordion-collapse {
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

/* Room tiles styles */
.room-tile {
    width: 80px;
    height: 70px;
    /* default border but color can be overridden by utilities or inline styles */
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
@endsection

@section('content')
<div class="container-fluid bed-reservations-page">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="fas fa-bed me-2"></i>
                حجز رقود مبدئي
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
            <form action="{{ route('bed-reservations.store') }}" method="POST" id="bedReservationForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="visit_id" value="{{ request('visit_id') }}">

                <!-- التبويبات (اصبحت اكوردين) -->
                <div class="accordion" id="bedReservationAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading1">
                            <button class="accordion-button collapsed" type="button" aria-expanded="false" aria-controls="collapse1">
                                <span class="step-number bg-primary text-white rounded-circle me-2" style="width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;">1</span>
                                بيانات المريض والطبيب المرسل
                            </button>
                        </h2>
                        <div id="collapse1" class="accordion-collapse collapse" aria-labelledby="heading1">
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
                                                    <option value="{{ $patient->id }}" {{ (old('patient_id') == $patient->id || (isset($selectedPatient) && $selectedPatient->id == $patient->id)) ? 'selected' : '' }}>
                                                        {{ optional($patient->user)->name ?? 'غير معروف' }}
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
                                            <label for="doctor_id" class="form-label fw-bold">
                                                <i class="fas fa-user-md me-1 text-info"></i>
                                                الطبيب المرسل
                                            </label>
                                            <select name="doctor_id" id="doctor_id" class="form-select form-select-lg @error('doctor_id') is-invalid @enderror">
                                                <option value="">اختر الطبيب (اختياري)</option>
                                                @foreach($doctors as $doctor)
                                                    <option value="{{ $doctor->id }}" {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                                        د. {{ optional($doctor->user)->name ?? 'غير معروف' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('doctor_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading2">
                            <button class="accordion-button collapsed" type="button" aria-expanded="false" aria-controls="collapse2">
                                <span class="step-number bg-warning text-white rounded-circle me-2" style="width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;">2</span>
                                التاريخ والوقت والقسم
                            </button>
                        </h2>
                        <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="heading2">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label for="scheduled_date" class="form-label fw-bold">
                                                <i class="fas fa-calendar me-1 text-warning"></i>
                                                تاريخ الرقود <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" name="scheduled_date" id="scheduled_date" class="form-control form-control-lg @error('scheduled_date') is-invalid @enderror" value="{{ old('scheduled_date') }}" required>
                                            @error('scheduled_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label for="scheduled_time" class="form-label fw-bold">
                                                <i class="fas fa-clock me-1 text-warning"></i>
                                                وقت الرقود <span class="text-danger">*</span>
                                            </label>
                                            <input type="time" name="scheduled_time" id="scheduled_time" class="form-control form-control-lg @error('scheduled_time') is-invalid @enderror" value="{{ old('scheduled_time', '09:00') }}" required>
                                            @error('scheduled_time')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label for="department_id" class="form-label fw-bold">
                                                <i class="fas fa-hospital me-1 text-info"></i>
                                                القسم
                                            </label>
                                            <select name="department_id" id="department_id" class="form-select form-select-lg @error('department_id') is-invalid @enderror">
                                                <option value="">اختر القسم (اختياري)</option>
                                                @foreach($departments as $department)
                                                    <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
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
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading3">
                            <button class="accordion-button collapsed" type="button" aria-expanded="false" aria-controls="collapse3">
                                <span class="step-number bg-info text-white rounded-circle me-2" style="width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;">3</span>
                                اختيار الغرفة
                            </button>
                        </h2>
                        <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="heading3">
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
                </div> <!-- /#bedReservationAccordion -->

                <!-- أزرار التنقل -->
                <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                    <button type="button" class="btn btn-outline-secondary btn-lg" id="prevBtn" style="display: none;">
                        <i class="fas fa-arrow-right me-2"></i>
                        السابق
                    </button>
                    <div></div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('bed-reservations.index') }}" class="btn btn-outline-danger btn-lg">
                            <i class="fas fa-times me-2"></i>
                            إلغاء
                        </a>
                        <button type="button" class="btn btn-primary btn-lg" id="nextBtn">
                            التالي
                            <i class="fas fa-arrow-left ms-2"></i>
                        </button>
                        <button type="submit" class="btn btn-success btn-lg" id="submitBtn" style="display: none;">
                            <i class="fas fa-save me-2"></i>
                            حجز الرقود
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
// نظام الأكورديون للحجز
document.addEventListener('DOMContentLoaded', function() {
    const accordion = document.getElementById('bedReservationAccordion');
    const progressBarFill = document.getElementById('progressBarFill');
    const progressPercentText = document.getElementById('progressPercentText');
    const currentStepNum = document.getElementById('currentStepNum');
    const stepName = document.getElementById('stepName');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');

    let currentStep = 0;
    const totalSteps = 3;
    const stepNames = [
        'اضغط على خطوة للبدء',
        'بيانات المريض والطبيب المرسل',
        'التاريخ والوقت والقسم',
        'اختيار الغرفة'
    ];

    // إخفاء جميع الأكورديون في البداية
    const collapses = accordion.querySelectorAll('.accordion-collapse');
    collapses.forEach(collapse => {
        collapse.style.display = 'none';
    });

    // تحديث شريط التقدم
    function updateProgress() {
        const percent = (currentStep / totalSteps) * 100;
        progressBarFill.style.width = percent + '%';
        progressPercentText.textContent = Math.round(percent) + '% اكتمال';
        currentStepNum.textContent = currentStep;
        stepName.textContent = stepNames[currentStep];

        // إظهار/إخفاء الأزرار
        prevBtn.style.display = currentStep > 0 ? 'block' : 'none';
        nextBtn.style.display = currentStep < totalSteps ? 'block' : 'none';
        submitBtn.style.display = currentStep === totalSteps ? 'block' : 'none';
    }

    // فتح خطوة معينة
    function openStep(step) {
        // إغلاق جميع الخطوات
        collapses.forEach(collapse => {
            collapse.style.display = 'none';
            collapse.classList.remove('show');
        });

        // إزالة الفئة النشطة من جميع الأزرار
        const buttons = accordion.querySelectorAll('.accordion-button');
        buttons.forEach(button => {
            button.classList.add('collapsed');
            button.setAttribute('aria-expanded', 'false');
        });

        // فتح الخطوة المحددة
        if (step > 0 && step <= totalSteps) {
            const targetCollapse = document.getElementById('collapse' + step);
            const targetButton = document.getElementById('heading' + step).querySelector('.accordion-button');

            targetCollapse.style.display = 'block';
            targetCollapse.classList.add('show');
            targetButton.classList.remove('collapsed');
            targetButton.setAttribute('aria-expanded', 'true');
        }

        currentStep = step;
        updateProgress();
    }

    // ربط الأحداث بالأزرار
    const buttons = accordion.querySelectorAll('.accordion-button');
    buttons.forEach((button, index) => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const step = index + 1;
            openStep(step);
        });
    });

    // زر التالي
    nextBtn.addEventListener('click', function() {
        if (currentStep < totalSteps) {
            openStep(currentStep + 1);
        }
    });

    // زر السابق
    prevBtn.addEventListener('click', function() {
        if (currentStep > 0) {
            openStep(currentStep - 1);
        }
    });

    // التحقق من صحة الخطوة الحالية قبل الانتقال
    function validateCurrentStep() {
        if (currentStep === 1) {
            const patientId = document.getElementById('patient_id').value;
            if (!patientId) {
                alert('يرجى اختيار المريض');
                return false;
            }
        } else if (currentStep === 2) {
            const scheduledDate = document.getElementById('scheduled_date').value;
            const scheduledTime = document.getElementById('scheduled_time').value;
            if (!scheduledDate || !scheduledTime) {
                alert('يرجى إدخال التاريخ والوقت');
                return false;
            }
        }
        return true;
    }

    // تعديل زر التالي للتحقق من الصحة
    nextBtn.addEventListener('click', function(e) {
        if (!validateCurrentStep()) {
            e.preventDefault();
            return;
        }
        if (currentStep < totalSteps) {
            openStep(currentStep + 1);
        }
    });

    // نظام اختيار الغرفة
    const roomIdInput = document.getElementById('room_id');
    const stayDaysInput = document.getElementById('expected_stay_days');
    const roomTotalFeeEl = document.getElementById('room_total_fee');
    const selectedRoomNameEl = document.getElementById('selected_room_name');
    const clearRoomSection = document.getElementById('clear_room_section');
    let selectedRoomFee = 0;

    // حفظ لون الإطار الأصلي من data-status-color
    document.querySelectorAll('.room-tile').forEach(t => {
        t.dataset.origBorder = t.dataset.statusColor || t.style.borderColor || '#dee2e6';
        t.dataset.origWidth  = '3px';
    });

    function calculateRoomFee() {
        if (!stayDaysInput || !roomTotalFeeEl) return;
        const days = parseInt(stayDaysInput.value) || 0;
        roomTotalFeeEl.textContent = new Intl.NumberFormat('ar-IQ').format(selectedRoomFee * days) + ' د.ع';
    }

    function selectRoom(tile) {
        if (tile.dataset.available === '0') return;

        // إعادة جميع البطاقات إلى لونها الأصلي
        document.querySelectorAll('.room-tile').forEach(t => {
            t.style.borderColor = t.dataset.origBorder;
            t.style.borderWidth = t.dataset.origWidth;
            t.style.boxShadow   = '';
            const icon = t.querySelector('.room-actions');
            if (icon) icon.style.display = 'none';
        });

        // تلوين الغرفة المختارة بلون مميز
        tile.style.borderColor = '#0d6efd';
        tile.style.borderWidth = '4px';
        tile.style.boxShadow   = '0 0 0 3px rgba(13,110,253,.35)';
        const icon = tile.querySelector('.room-actions');
        if (icon) icon.style.display = 'block';

        // تحديث الحقول
        roomIdInput.value = tile.dataset.roomId;
        selectedRoomFee  = parseFloat(tile.dataset.roomFee) || 0;
        if (selectedRoomNameEl)
            selectedRoomNameEl.textContent = tile.dataset.roomNumber + (tile.dataset.roomType === 'vip' ? ' (VIP)' : ' (عادية)');

        const statusSpan = document.getElementById('selected_room_status');
        if (statusSpan) {
            statusSpan.textContent = 'متاحة';
            statusSpan.className   = 'fw-bold text-danger';
        }

        clearRoomSection.style.display = 'block';
        calculateRoomFee();
    }

    document.querySelectorAll('.room-selectable').forEach(tile => {
        tile.addEventListener('click', function() { selectRoom(this); });
    });

    if (stayDaysInput) {
        stayDaysInput.addEventListener('input', calculateRoomFee);
    }

    // زر إلغاء اختيار الغرفة
    document.getElementById('clear_room_btn').addEventListener('click', function() {
        document.querySelectorAll('.room-tile').forEach(t => {
            t.style.borderColor = t.dataset.origBorder;
            t.style.borderWidth = t.dataset.origWidth;
            t.style.boxShadow   = '';
            const icon = t.querySelector('.room-actions');
            if (icon) icon.style.display = 'none';
        });
        roomIdInput.value = '';
        selectedRoomFee   = 0;
        if (selectedRoomNameEl) selectedRoomNameEl.textContent = 'لم يتم الاختيار';
        clearRoomSection.style.display = 'none';
        calculateRoomFee();
    });

    // تهيئة tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // البدء بالخطوة الأولى
    updateProgress();
});
</script>
@endsection