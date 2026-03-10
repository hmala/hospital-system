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
                <a href="{{ route('emergency.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>العودة للقائمة
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">بيانات حالة الطوارئ</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('emergency.store') }}">
                        @csrf

                        <div class="row">
                            <!-- اختيار المريض -->
                            <div class="col-md-6 mb-3">
                                <label for="patient_id" class="form-label">المريض *</label>
                                <div class="input-group">
                                    <select class="form-select @error('patient_id') is-invalid @enderror"
                                            id="patient_id"
                                            name="patient_id"
                                            required>
                                        <option value="">اختر المريض</option>
                                        @foreach($patients as $patient)
                                            <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                                                {{ $patient->user->name ?? 'مريض بدون بيانات' }} - {{ $patient->user->phone ?? '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" id="newPatientBtn" title="مريض جديد">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                </div>
                                @error('patient_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- حقول إنشاء مريض جديد -->
                            <div class="col-md-12 mb-3" id="newPatientFields" style="display: none;">
                                <div class="card border-info p-3">
                                    <h6 class="mb-3 text-info">بيانات المريض الجديد</h6>
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

                            <!-- الطبيب المسؤول -->
                            <div class="col-md-6 mb-3">
                                <label for="doctor_id" class="form-label">الطبيب المسؤول</label>
                                <select class="form-select @error('doctor_id') is-invalid @enderror"
                                        id="doctor_id"
                                        name="doctor_id">
                                    <option value="">اختر الطبيب (اختياري)</option>
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                            {{ $doctor->user->name ?? 'طبيب بدون بيانات' }} - {{ $doctor->specialization ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('doctor_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- description removed - not needed for quick booking -->


                        <!-- required actions removed -->

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
    document.getElementById('newPatientBtn').addEventListener('click', function() {
        const fields = document.getElementById('newPatientFields');
        if (!fields) return;
        if (fields.style.display === 'block') {
            fields.style.display = 'none';
            document.getElementById('patient_id').setAttribute('required', 'required');
        } else {
            fields.style.display = 'block';
            document.getElementById('patient_id').removeAttribute('required');
        }
    });

    document.querySelector('form').addEventListener('submit', function(e) {
        const fields = document.getElementById('newPatientFields');
        if (fields && fields.style.display === 'block') {
            const name = document.getElementById('new_patient_name').value.trim();
            if (!name) {
                e.preventDefault();
                alert('يرجى إدخال اسم المريض الجديد');
                document.getElementById('new_patient_name').focus();
            }
        }
    });
</script>
@endsection