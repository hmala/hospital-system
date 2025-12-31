@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="fas fa-procedures me-2"></i>
                تعديل العملية الجراحية
            </h2>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('surgeries.update', $surgery) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Nav tabs -->
                <ul class="nav nav-tabs" id="surgeryEditTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab" aria-controls="basic" aria-selected="true">
                            <i class="fas fa-info-circle me-2"></i>المعلومات الأساسية
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="false">
                            <i class="fas fa-procedures me-2"></i>تفاصيل العملية
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="referral-tab" data-bs-toggle="tab" data-bs-target="#referral" type="button" role="tab" aria-controls="referral" aria-selected="false">
                            <i class="fas fa-exchange-alt me-2"></i>مصدر التحويل
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tests-tab" data-bs-toggle="tab" data-bs-target="#tests" type="button" role="tab" aria-controls="tests" aria-selected="false">
                            <i class="fas fa-flask me-2"></i>الفحوصات المطلوبة
                        </button>
                    </li>
                </ul>

                <!-- Tab panes -->
                <div class="tab-content mt-3" id="surgeryEditTabContent">
                    <!-- Basic Information Tab -->
                    <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="patient_id" class="form-label">المريض <span class="text-danger">*</span></label>
                                    <select name="patient_id" id="patient_id" class="form-select @error('patient_id') is-invalid @enderror" required>
                                        <option value="">اختر المريض</option>
                                        @foreach($patients as $patient)
                                            <option value="{{ $patient->id }}" {{ (old('patient_id', $surgery->patient_id) == $patient->id) ? 'selected' : '' }}>
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
                                            <option value="{{ $doctor->id }}" {{ (old('doctor_id', $surgery->doctor_id) == $doctor->id) ? 'selected' : '' }}>
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
                                            <option value="{{ $department->id }}" {{ (old('department_id', $surgery->department_id) == $department->id) ? 'selected' : '' }}>
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
                                           value="{{ old('surgery_type', $surgery->surgery_type) }}" required>
                                    @error('surgery_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="scheduled_date" class="form-label">تاريخ العملية <span class="text-danger">*</span></label>
                                    <input type="date" name="scheduled_date" id="scheduled_date" 
                                           class="form-control @error('scheduled_date') is-invalid @enderror" 
                                           value="{{ old('scheduled_date', $surgery->scheduled_date->format('Y-m-d')) }}" required>
                                    @error('scheduled_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="scheduled_time" class="form-label">وقت العملية <span class="text-danger">*</span></label>
                                    <input type="time" name="scheduled_time" id="scheduled_time" 
                                           class="form-control @error('scheduled_time') is-invalid @enderror" 
                                           value="{{ old('scheduled_time', $surgery->scheduled_time ? $surgery->scheduled_time->format('H:i') : '') }}" required>
                                    @error('scheduled_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="status" class="form-label">الحالة <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                        <option value="scheduled" {{ old('status', $surgery->status) == 'scheduled' ? 'selected' : '' }}>مجدولة</option>
                                        <option value="in_progress" {{ old('status', $surgery->status) == 'in_progress' ? 'selected' : '' }}>جارية</option>
                                        <option value="completed" {{ old('status', $surgery->status) == 'completed' ? 'selected' : '' }}>مكتملة</option>
                                        <option value="cancelled" {{ old('status', $surgery->status) == 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Surgery Details Tab -->
                    <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
                        @if(Auth::user()->hasRole(['admin', 'surgery_staff', 'doctor']))
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="anesthesiologist_id" class="form-label">الطبيب المخدر</label>
                                    <select name="anesthesiologist_id" id="anesthesiologist_id" class="form-select @error('anesthesiologist_id') is-invalid @enderror">
                                        <option value="">اختر الطبيب المخدر</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}" {{ (old('anesthesiologist_id', $surgery->anesthesiologist_id) == $doctor->id) ? 'selected' : '' }}>
                                                د. {{ $doctor->user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('anesthesiologist_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="anesthesiologist_2_id" class="form-label">الطبيب المخدر الثاني</label>
                                    <select name="anesthesiologist_2_id" id="anesthesiologist_2_id" class="form-select @error('anesthesiologist_2_id') is-invalid @enderror">
                                        <option value="">اختر الطبيب المخدر الثاني</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}" {{ (old('anesthesiologist_2_id', $surgery->anesthesiologist_2_id) == $doctor->id) ? 'selected' : '' }}>
                                                د. {{ $doctor->user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('anesthesiologist_2_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="surgical_assistant_name" class="form-label">اسم مساعد الجراح</label>
                                    <input type="text" name="surgical_assistant_name" id="surgical_assistant_name" 
                                           class="form-control @error('surgical_assistant_name') is-invalid @enderror" 
                                           value="{{ old('surgical_assistant_name', $surgery->surgical_assistant_name) }}">
                                    @error('surgical_assistant_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="referring_physician" class="form-label">الطبيب المرسل</label>
                                    <input type="text" name="referring_physician" id="referring_physician" 
                                           class="form-control @error('referring_physician') is-invalid @enderror" 
                                           value="{{ old('referring_physician', $surgery->referring_physician) }}">
                                    @error('referring_physician')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">وقت بدء العملية</label>
                                    <input type="time" name="start_time" id="start_time" 
                                           class="form-control @error('start_time') is-invalid @enderror" 
                                           value="{{ old('start_time', $surgery->start_time ? $surgery->start_time->format('H:i') : '') }}">
                                    @error('start_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">وقت انتهاء العملية</label>
                                    <input type="time" name="end_time" id="end_time" 
                                           class="form-control @error('end_time') is-invalid @enderror" 
                                           value="{{ old('end_time', $surgery->end_time ? $surgery->end_time->format('H:i') : '') }}">
                                    @error('end_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="anesthesia_type" class="form-label">نوع التخدير</label>
                                    <input type="text" name="anesthesia_type" id="anesthesia_type" 
                                           class="form-control @error('anesthesia_type') is-invalid @enderror" 
                                           value="{{ old('anesthesia_type', $surgery->anesthesia_type) }}">
                                    @error('anesthesia_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="surgery_classification" class="form-label">تصنيف العملية</label>
                                    <input type="text" name="surgery_classification" id="surgery_classification" 
                                           class="form-control @error('surgery_classification') is-invalid @enderror" 
                                           value="{{ old('surgery_classification', $surgery->surgery_classification) }}">
                                    @error('surgery_classification')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="supplies" class="form-label">المستلزمات</label>
                                    <textarea name="supplies" id="supplies" class="form-control @error('supplies') is-invalid @enderror" rows="2">{{ old('supplies', $surgery->supplies) }}</textarea>
                                    @error('supplies')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="diagnosis" class="form-label">التشخيص</label>
                            <textarea name="diagnosis" id="diagnosis" class="form-control @error('diagnosis') is-invalid @enderror" rows="2">{{ old('diagnosis', $surgery->diagnosis) }}</textarea>
                            @error('diagnosis')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">وصف العملية</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $surgery->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">ملاحظات</label>
                            <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $surgery->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="surgical_notes" class="form-label">ملاحظات جراحية</label>
                            <textarea name="surgical_notes" id="surgical_notes" class="form-control @error('surgical_notes') is-invalid @enderror" rows="3">{{ old('surgical_notes', $surgery->surgical_notes) }}</textarea>
                            @error('surgical_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="post_op_notes" class="form-label">ملاحظات ما بعد العملية</label>
                            <textarea name="post_op_notes" id="post_op_notes" class="form-control @error('post_op_notes') is-invalid @enderror" rows="3">{{ old('post_op_notes', $surgery->post_op_notes) }}</textarea>
                            @error('post_op_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif
                    </div>

                    <!-- Referral Source Tab -->
                    <div class="tab-pane fade" id="referral" role="tabpanel" aria-labelledby="referral-tab">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>مصدر التحويل</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">نوع التحويل <span class="text-danger">*</span></label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="referral_source" id="referral_internal" value="internal" {{ old('referral_source', $surgery->referral_source) == 'internal' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="referral_internal">
                                            <i class="fas fa-hospital me-1"></i>تحويل داخلي (من طبيب المستشفى)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="referral_source" id="referral_external" value="external" {{ old('referral_source', $surgery->referral_source) == 'external' ? 'checked' : '' }}>
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
                                                       value="{{ old('external_doctor_name', $surgery->external_doctor_name) }}"
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
                                                       value="{{ old('external_hospital_name', $surgery->external_hospital_name) }}"
                                                       placeholder="مستشفى الرحمة">
                                                @error('external_hospital_name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="referral_notes" class="form-label">ملاحظات التحويل</label>
                                        <textarea name="referral_notes" id="referral_notes" class="form-control @error('referral_notes') is-invalid @enderror" rows="2" placeholder="سبب التحويل أو أي معلومات إضافية...">{{ old('referral_notes', $surgery->referral_notes) }}</textarea>
                                        @error('referral_notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Required Tests Tab -->
                    <div class="tab-pane fade" id="tests" role="tabpanel" aria-labelledby="tests-tab">
                        <div class="card border-info">
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
                                                           {{ in_array($labTest->id, old('lab_tests', $surgery->labTests->pluck('lab_test_id')->toArray())) ? 'checked' : '' }}>
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
                                                           {{ in_array($radiologyType->id, old('radiology_tests', $surgery->radiologyTests->pluck('radiology_type_id')->toArray())) ? 'checked' : '' }}>
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
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>تحذير:</strong> تعديل الفحوصات المطلوبة سيؤدي إلى حذف الطلبات السابقة وإنشاء طلبات جديدة. تأكد من أن النتائج السابقة قد تم حفظها بشكل منفصل إن لزم الأمر.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>حفظ التعديلات
                    </button>
                    <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

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

    if (internalRadio && externalRadio) {
        internalRadio.addEventListener('change', toggleExternalFields);
        externalRadio.addEventListener('change', toggleExternalFields);
        
        // التحقق عند التحميل
        toggleExternalFields();
    }
});
</script>
