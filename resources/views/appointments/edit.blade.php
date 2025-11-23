<!-- resources/views/appointments/edit.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-edit me-2"></i>
                    تعديل الموعد
                </h2>
                <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>العودة للتفاصيل
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">تعديل معلومات الموعد</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('appointments.update', $appointment) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- المريض -->
                            <div class="col-md-6 mb-3">
                                <label for="patient_id" class="form-label">المريض *</label>
                                <select class="form-select @error('patient_id') is-invalid @enderror" 
                                        id="patient_id" name="patient_id" required>
                                    <option value="">اختر المريض</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->id }}" 
                                                {{ $appointment->patient_id == $patient->id ? 'selected' : '' }}>
                                            {{ $patient->user->name }} - {{ $patient->user->phone }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('patient_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- الطبيب -->
                            <div class="col-md-6 mb-3">
                                <label for="doctor_id" class="form-label">الطبيب *</label>
                                <select class="form-select @error('doctor_id') is-invalid @enderror" 
                                        id="doctor_id" name="doctor_id" required>
                                    <option value="">اختر الطبيب</option>
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" 
                                                data-fee="{{ $doctor->consultation_fee }}"
                                                {{ $appointment->doctor_id == $doctor->id ? 'selected' : '' }}>
                                            {{ $doctor->user->name }} - {{ $doctor->specialization }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('doctor_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- العيادة -->
                            <div class="col-md-6 mb-3">
                                <label for="department_id" class="form-label">العيادة *</label>
                                <select class="form-select @error('department_id') is-invalid @enderror" 
                                        id="department_id" name="department_id" required>
                                    <option value="">اختر العيادة</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" 
                                                {{ $appointment->department_id == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }} - {{ $department->room_number }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- تاريخ الموعد -->
                            <div class="col-md-6 mb-3">
                                <label for="appointment_date" class="form-label">تاريخ الموعد *</label>
                                <input type="date" 
                                       class="form-control @error('appointment_date') is-invalid @enderror" 
                                       id="appointment_date" 
                                       name="appointment_date" 
                                       value="{{ old('appointment_date', $appointment->appointment_date->format('Y-m-d')) }}"
                                       required>
                                @error('appointment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- وقت الموعد -->
                            <div class="col-md-6 mb-3">
                                <label for="appointment_time" class="form-label">وقت الموعد *</label>
                                <input type="time" 
                                       class="form-control @error('appointment_time') is-invalid @enderror" 
                                       id="appointment_time" 
                                       name="appointment_time" 
                                       value="{{ old('appointment_time', $appointment->appointment_date->format('H:i')) }}"
                                       required>
                                @error('appointment_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- أجر الكشف -->
                            <div class="col-md-6 mb-3">
                                <label for="consultation_fee" class="form-label">أجر الكشف *</label>
                                <input type="number" 
                                       class="form-control @error('consultation_fee') is-invalid @enderror" 
                                       id="consultation_fee" 
                                       name="consultation_fee" 
                                       value="{{ old('consultation_fee', $appointment->consultation_fee) }}"
                                       min="0"
                                       step="0.01"
                                       required>
                                @error('consultation_fee')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- حالة الموعد -->
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">حالة الموعد *</label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" name="status" required>
                                <option value="scheduled" {{ $appointment->status == 'scheduled' ? 'selected' : '' }}>مجدول</option>
                                <option value="confirmed" {{ $appointment->status == 'confirmed' ? 'selected' : '' }}>مؤكد</option>
                                <option value="completed" {{ $appointment->status == 'completed' ? 'selected' : '' }}>مكتمل</option>
                                <option value="cancelled" {{ $appointment->status == 'cancelled' ? 'selected' : '' }}>ملغى</option>
                                <option value="no_show" {{ $appointment->status == 'no_show' ? 'selected' : '' }}>لم يحضر</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- السبب -->
                        <div class="mb-3">
                            <label for="reason" class="form-label">سبب الزيارة *</label>
                            <select class="form-select @error('reason') is-invalid @enderror"
                                    id="reason" name="reason" required>
                                <option value="">اختر سبب الزيارة</option>
                                @foreach(\App\Models\Appointment::VISIT_REASONS as $key => $value)
                                    <option value="{{ $key }}"
                                            {{ old('reason', $appointment->reason) == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ملاحظات -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">ملاحظات إضافية</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" 
                                      name="notes" 
                                      rows="2">{{ old('notes', $appointment->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- مدة الموعد -->
                        <div class="col-md-6 mb-3">
                            <label for="duration" class="form-label">مدة الموعد (دقيقة)</label>
                            <input type="number" 
                                   class="form-control @error('duration') is-invalid @enderror" 
                                   id="duration" 
                                   name="duration" 
                                   value="{{ old('duration', $appointment->duration) }}"
                                   min="15"
                                   max="120"
                                   step="15">
                            @error('duration')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>حفظ التعديلات
                            </button>
                            <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- معلومات سريعة -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات الموعد الحالي</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">الحالة الحالية:</small>
                        <div>
                            <span class="badge bg-{{ $appointment->status_color }}">
                                {{ $appointment->status_text }}
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">المريض:</small>
                        <div class="fw-bold">{{ $appointment->patient->user->name }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">الطبيب:</small>
                        <div class="fw-bold">د. {{ $appointment->doctor->user->name }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">العيادة:</small>
                        <div class="fw-bold">{{ $appointment->department->name }}</div>
                    </div>
                    <hr>
                    <div class="alert alert-warning">
                        <small>
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            سيتم إرسال إشعارات بالتغييرات إذا لزم الأمر
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const doctorSelect = document.getElementById('doctor_id');
    const feeInput = document.getElementById('consultation_fee');

    // تحديث أجر الكشف عند اختيار الطبيب
    doctorSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const consultationFee = selectedOption.getAttribute('data-fee');
        if (consultationFee) {
            feeInput.value = consultationFee;
        }
    });
});
</script>
@endsection
@endsection