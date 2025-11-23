@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="fas fa-procedures me-2"></i>
                حجز عملية جراحية جديدة
            </h2>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('surgeries.store') }}" method="POST">
                @csrf
                <input type="hidden" name="visit_id" value="{{ request('visit_id') }}">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="patient_id" class="form-label">المريض <span class="text-danger">*</span></label>
                            <select name="patient_id" id="patient_id" class="form-select @error('patient_id') is-invalid @enderror" required>
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
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="doctor_id" class="form-label">الطبيب الجراح <span class="text-danger">*</span></label>
                            <select name="doctor_id" id="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror" required>
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
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="department_id" class="form-label">القسم <span class="text-danger">*</span></label>
                            <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
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
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="surgery_type" class="form-label">نوع العملية <span class="text-danger">*</span></label>
                            <input type="text" name="surgery_type" id="surgery_type" 
                                   class="form-control @error('surgery_type') is-invalid @enderror" 
                                   value="{{ old('surgery_type') }}" 
                                   placeholder="مثال: استئصال الزائدة الدودية" required>
                            @error('surgery_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="scheduled_date" class="form-label">تاريخ العملية <span class="text-danger">*</span></label>
                            <input type="date" name="scheduled_date" id="scheduled_date" 
                                   class="form-control @error('scheduled_date') is-invalid @enderror" 
                                   value="{{ old('scheduled_date') }}" required>
                            @error('scheduled_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="scheduled_time" class="form-label">وقت العملية <span class="text-danger">*</span></label>
                            <input type="time" name="scheduled_time" id="scheduled_time" 
                                   class="form-control @error('scheduled_time') is-invalid @enderror" 
                                   value="{{ old('scheduled_time', '09:00') }}" required>
                            @error('scheduled_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <!-- مصدر التحويل -->
                <div class="card mb-3 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>مصدر التحويل</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">نوع التحويل <span class="text-danger">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="referral_source" id="referral_internal" value="internal" {{ old('referral_source', 'internal') == 'internal' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="referral_internal">
                                    <i class="fas fa-hospital me-1"></i>تحويل داخلي (من طبيب المستشفى)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="referral_source" id="referral_external" value="external" {{ old('referral_source') == 'external' ? 'checked' : '' }}>
                                <label class="form-check-label" for="referral_external">
                                    <i class="fas fa-ambulance me-1"></i>تحويل خارجي (من طبيب أو مستشفى آخر)
                                </label>
                            </div>
                        </div>

                        <!-- حقول التحويل الخارجي -->
                        <div id="external_fields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="external_doctor_name" class="form-label">اسم الطبيب المحول</label>
                                        <input type="text" name="external_doctor_name" id="external_doctor_name" 
                                               class="form-control @error('external_doctor_name') is-invalid @enderror" 
                                               value="{{ old('external_doctor_name') }}"
                                               placeholder="د. أحمد محمد">
                                        @error('external_doctor_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="external_hospital_name" class="form-label">اسم المستشفى/العيادة</label>
                                        <input type="text" name="external_hospital_name" id="external_hospital_name" 
                                               class="form-control @error('external_hospital_name') is-invalid @enderror" 
                                               value="{{ old('external_hospital_name') }}"
                                               placeholder="مستشفى الرحمة">
                                        @error('external_hospital_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="referral_notes" class="form-label">ملاحظات التحويل</label>
                                <textarea name="referral_notes" id="referral_notes" class="form-control @error('referral_notes') is-invalid @enderror" rows="2" placeholder="سبب التحويل أو أي معلومات إضافية...">{{ old('referral_notes') }}</textarea>
                                @error('referral_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">وصف العملية</label>
                    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">ملاحظات إضافية</label>
                    <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- التحاليل والأشعة المطلوبة -->
                <div class="card mb-3 border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-flask me-2"></i>الفحوصات المطلوبة قبل العملية</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3"><i class="fas fa-vial me-2"></i>التحاليل المخبرية</h6>
                                <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                    @foreach($labTests as $labTest)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="lab_tests[]" 
                                                   value="{{ $labTest->id }}" 
                                                   id="lab_test_{{ $labTest->id }}"
                                                   {{ in_array($labTest->id, old('lab_tests', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="lab_test_{{ $labTest->id }}">
                                                {{ $labTest->name }}
                                                @if($labTest->category)
                                                    <small class="text-muted">({{ $labTest->category }})</small>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('lab_tests')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success mb-3"><i class="fas fa-x-ray me-2"></i>الأشعة والتصوير</h6>
                                <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                    @foreach($radiologyTypes as $radiologyType)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="radiology_tests[]" 
                                                   value="{{ $radiologyType->id }}" 
                                                   id="radiology_test_{{ $radiologyType->id }}"
                                                   {{ in_array($radiologyType->id, old('radiology_tests', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="radiology_test_{{ $radiologyType->id }}">
                                                {{ $radiologyType->name }}
                                                @if($radiologyType->code)
                                                    <small class="text-muted">({{ $radiologyType->code }})</small>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('radiology_tests')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>ملاحظة:</strong> يمكنك اختيار الفحوصات المطلوبة قبل العملية. سيتم إنشاء طلبات منفصلة لكل فحص مختار وسيتم تتبع حالة كل فحص بشكل مستقل.
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>حجز العملية
                    </button>
                    <a href="{{ route('surgeries.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// إظهار/إخفاء حقول التحويل الخارجي
document.addEventListener('DOMContentLoaded', function() {
    const internalRadio = document.getElementById('referral_internal');
    const externalRadio = document.getElementById('referral_external');
    const externalFields = document.getElementById('external_fields');

    function toggleExternalFields() {
        if (externalRadio.checked) {
            externalFields.style.display = 'block';
        } else {
            externalFields.style.display = 'none';
        }
    }

    internalRadio.addEventListener('change', toggleExternalFields);
    externalRadio.addEventListener('change', toggleExternalFields);
    
    // التحقق عند التحميل
    toggleExternalFields();
});
</script>
@endsection
