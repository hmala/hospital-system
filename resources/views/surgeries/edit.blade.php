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
                                   value="{{ old('scheduled_time', $surgery->scheduled_time) }}" required>
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
                    <label for="post_op_notes" class="form-label">ملاحظات ما بعد العملية</label>
                    <textarea name="post_op_notes" id="post_op_notes" class="form-control @error('post_op_notes') is-invalid @enderror" rows="3">{{ old('post_op_notes', $surgery->post_op_notes) }}</textarea>
                    @error('post_op_notes')
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

                <div class="d-flex gap-2">
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
