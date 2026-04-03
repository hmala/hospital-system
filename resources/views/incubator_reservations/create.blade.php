@extends('layouts.app')

@section('styles')
<style>
/* تحسين عرض حجز الحاضنات */
.incubator-card {
    position: relative;
    width: 240px;
    border: 2px solid #dee2e6;
    border-radius: 14px;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background-color 0.2s ease;
}

.incubator-card .selection-indicator {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #0d6efd;
    color: white;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    box-shadow: 0 2px 8px rgba(13,110,253,0.4);
    z-index: 100;
}

.incubator-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.12);
}

.incubator-card.selected,
input[type="radio"].incubator-radio:checked + label.incubator-card {
    border-color: #0d6efd !important;
    box-shadow: 0 10px 24px rgba(13,110,253,0.3) !important;
    background: rgba(13,110,253,0.1) !important;
    transform: scale(1.02) !important;
}

input[type="radio"].incubator-radio:checked + label.incubator-card .selection-indicator {
    display: flex !important;
}

/* ضمان أن البطاقة هي بلوك كامل بحيث يتم تفعيلها عند النقر */
label.incubator-card {
    display: block !important;
    margin: 0 !important;
}

.incubator-card[data-available="0"] {
    opacity: 0.55;
    cursor: not-allowed;
    background: red !important;
    color: white !important;
}
.incubator-card[data-available="0"] .card-body {
    pointer-events: none;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-baby-carriage me-2 text-primary"></i>
                        حجز حاضنة جديدة
                    </h2>
                    <p class="text-muted">إنشاء حجز جديد لحاضنة خدج</p>
                </div>
                <div>
                    <a href="{{ route('incubators.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(isset($existingReservation) && $existingReservation)
        <!-- Modal: Existing Reservation Notice -->
        <div class="modal fade" id="existingReservationModal" tabindex="-1" aria-labelledby="existingReservationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="existingReservationModalLabel">معلومة مهمة</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <p>هذا الطفل لديه حجز نشط بالفعل في الحاضنة رقم <strong>{{ $existingReservation->incubator->incubator_number }}</strong>.</p>
                        <p>يمكنك إما إكمال الحجز الحالي أو إلغاءه قبل إنشاء حجز جديد.</p>
                        <div class="mt-3">
                            <a href="{{ route('incubator-reservations.show', $existingReservation) }}" class="btn btn-primary w-100">عرض الحجز الحالي</a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($patients->count() == 0)
        <div class="alert alert-warning" role="alert">
            <h5 class="alert-heading">
                <i class="fas fa-exclamation-triangle me-2"></i>
                تنبيه هام
            </h5>
            <p class="mb-0">
                لا يوجد أطفال (أقل من سنة) مسجلين في النظام. حاضنات الخدج مخصصة للأطفال حديثي الولادة فقط.
            </p>
            <hr>
            <p class="mb-0">
                يرجى تسجيل طفل جديد أولاً من قسم المرضى، والتأكد من إدخال العمر بشكل صحيح.
            </p>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        بيانات الحجز
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('incubator-reservations.store') }}" method="POST">
                        @csrf

                        <!-- بيانات المريض (الأم) -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-user-circle me-2 text-info"></i>
                                    بيانات المريض (الأم أو المسؤول)
                                </h6>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="patient_id" class="form-label">
                                    اختر المريض <span class="text-danger">*</span>
                                </label>
                                <select name="patient_id" id="patient_id" 
                                        class="form-select @error('patient_id') is-invalid @enderror" required>
                                    <option value="">-- اختر المريض --</option>
                                    @foreach($patients as $p)
                                        <option value="{{ $p->id }}" 
                                                {{ (old('patient_id') ?? $patient?->id) == $p->id ? 'selected' : '' }}>
                                            {{ optional($p->user)->name ?? 'غير معروف' }} - {{ optional($p->user)->phone ?? 'لا يوجد هاتف' }} 
                                            @if($p->age !== null)
                                                ({{ $p->age < 1 ? 'أقل من سنة' : $p->age . ' سنة' }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('patient_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if($patients->count() == 0)
                                    <small class="text-danger">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        لا يوجد أطفال (أقل من سنة) مسجلين في النظام
                                    </small>
                                @else
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        يظهر فقط الأطفال حديثي الولادة (أقل من سنة)
                                    </small>
                                @endif
                            </div>
                        </div>

                        <!-- اختيار نوع الحاضنة -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-filter me-2 text-warning"></i>
                                    نوع الحاضنة
                                </h6>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="incubator_type" class="form-label">
                                    اختر نوع الحاضنة
                                </label>
                                <select name="incubator_type" id="incubator_type" 
                                        class="form-select @error('incubator_type') is-invalid @enderror">
                                    <option value="">-- عرض جميع الحاضنات --</option>
                                    @foreach($incubatorTypes as $typeValue => $typeLabel)
                                        <option value="{{ $typeValue }}" 
                                                {{ old('incubator_type', $selectedType ?? null) === $typeValue ? 'selected' : '' }}>
                                            {{ $typeLabel }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('incubator_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    استخدم الفلترة لتقليل الخيارات وجعل الاختيار أسرع.
                                </small>
                            </div>
                        </div>

                        <!-- اختيار الحاضنة -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-procedures me-2 text-warning"></i>
                                    حاضنات متاحة
                                </h6>
                            </div>

                            <div class="col-12">
                                <div class="d-flex flex-wrap gap-3" id="incubatorCardGrid">
                                    @forelse($incubators as $inc)
                                        @php $isAvailable = $inc->status === 'available'; @endphp
                                        <input type="radio" 
                                               name="incubator_id" 
                                               id="incubator_{{ $inc->id }}" 
                                               value="{{ $inc->id }}" 
                                               class="d-none incubator-radio"
                                               data-incubator-type="{{ $inc->incubator_type }}"
                                               data-available="{{ $isAvailable ? '1' : '0' }}"
                                               {{ old('incubator_id', request('incubator_id')) == $inc->id ? 'checked' : '' }}
                                               {{ $isAvailable ? '' : 'disabled' }}>

                                        <label for="incubator_{{ $inc->id }}" 
                                               class="card incubator-card shadow-sm"
                                               data-incubator-type="{{ $inc->incubator_type }}"
                                               data-available="{{ $isAvailable ? '1' : '0' }}">
                                            <span class="selection-indicator">
                                                <i class="fas fa-check-circle"></i>
                                            </span>
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h5 class="mb-0">{{ $inc->incubator_number }}</h5>
                                                        <small class="text-muted">{{ $inc->type_name }}</small>
                                                    </div>
                                                    <span class="badge bg-{{ $inc->status_color }}">{{ $inc->status_name }}</span>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="text-muted small">
                                                        <i class="fas fa-money-bill-wave me-1"></i>
                                                        {{ number_format($inc->daily_fee) }} د.ع/يوم
                                                    </div>
                                                    @if($inc->room)
                                                        <div class="text-muted small">
                                                            <i class="fas fa-door-open me-1"></i>
                                                            {{ $inc->room->room_number }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </label>
                                    @empty
                                        <div class="alert alert-info w-100 text-center">
                                            <i class="fas fa-info-circle me-1"></i>
                                            لا توجد حاضنات متاحة حالياً
                                        </div>
                                    @endforelse
                                </div>
                                @error('incubator_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    انقر على الحاضنة لتحديدها (الحالة يجب أن تكون "متاحة").
                                </small>
                            </div>
                        </div>

                        <!-- بيانات الدخول -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-calendar me-2 text-danger"></i>
                                    بيانات الدخول
                                </h6>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="admission_date" class="form-label">
                                    تاريخ الدخول <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="admission_date" id="admission_date" 
                                       class="form-control @error('admission_date') is-invalid @enderror" 
                                       value="{{ old('admission_date', now()->format('Y-m-d')) }}" required>
                                @error('admission_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="admission_time" class="form-label">
                                    وقت الدخول <span class="text-danger">*</span>
                                </label>
                                <input type="time" name="admission_time" id="admission_time" 
                                       class="form-control @error('admission_time') is-invalid @enderror" 
                                       value="{{ old('admission_time', now()->format('H:i')) }}" required>
                                @error('admission_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- أزرار التحكم -->
                        <div class="row">
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('incubators.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i>
                                        إلغاء
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-1"></i>
                                        حفظ الحجز
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('incubator_type');
    const radios = Array.from(document.querySelectorAll('input.incubator-radio'));

    function updateSelectionVisuals() {
        radios.forEach(radio => {
            const card = radio.nextElementSibling;
            if (!card) return;
            
            const indicator = card.querySelector('.selection-indicator');
            
            if (radio.checked) {
                card.classList.add('selected');
                if (indicator) {
                    indicator.style.display = 'flex';
                }
                console.log('Selected card:', radio.value);
            } else {
                card.classList.remove('selected');
                if (indicator) {
                    indicator.style.display = 'none';
                }
            }
        });
    }

    function applyFilter() {
        const selectedType = typeSelect ? typeSelect.value : '';
        radios.forEach(radio => {
            const matches = !selectedType || radio.dataset.incubatorType === selectedType;
            const card = radio.nextElementSibling;
            if (!card) return;
            card.style.display = matches ? '' : 'none';

            // if hidden and currently selected, uncheck
            if (!matches && radio.checked) {
                radio.checked = false;
            }
        });

        updateSelectionVisuals();
    }

    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            console.log('Radio changed:', this.value, this.checked);
            updateSelectionVisuals();
        });
    });

    if (typeSelect) {
        typeSelect.addEventListener('change', applyFilter);
    }

    // Initial update
    updateSelectionVisuals();
    applyFilter();

    // عرض تنبيه الحجز النشط إذا كان موجوداً
    @if(isset($existingReservation) && $existingReservation)
        const existingModal = new bootstrap.Modal(document.getElementById('existingReservationModal'));
        existingModal.show();
    @endif
});
</script>
@endsection
