<!-- resources/views/visits/create.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-file-medical me-2"></i>
                    تسجيل زيارة جديدة
                </h2>
                <a href="{{ route('visits.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>العودة للقائمة
                </a>
            </div>
        </div>
    </div>

    @if($appointment)
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-success">
                <h6><i class="fas fa-calendar-check me-2"></i>تحويل موعد إلى زيارة</h6>
                <p class="mb-0">أنت تقوم بتحويل الموعد المحدد مسبقاً إلى زيارة طبية مسجلة.</p>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">بيانات الزيارة</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('visits.store') }}">
                        @csrf

                        <!-- إذا جاء من موعد، نخفي الحقول المعبأة مسبقاً -->
                        @if($appointment)
                            <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">
                        @endif

                        <div class="row">
                            <!-- المريض -->
                            <div class="col-md-6 mb-3">
                                <label for="patient_id" class="form-label">المريض *</label>
                                @if($appointment)
                                    <input type="hidden" name="patient_id" value="{{ $appointment->patient_id }}">
                                    <input type="text" class="form-control" value="{{ $appointment->patient->user->name }} - {{ $appointment->patient->user->phone }}" readonly style="background-color: #1f7bd8;">
                                    <small class="text-muted">مأخوذ من الموعد المحدد مسبقاً</small>
                                @else
                                    <select class="form-select @error('patient_id') is-invalid @enderror" 
                                            id="patient_id" name="patient_id" required>
                                        <option value="">اختر المريض</option>
                                        @foreach($patients as $patient)
                                            <option value="{{ $patient->id }}" 
                                                    {{ ($selectedPatient && $selectedPatient->id == $patient->id) ? 'selected' : (old('patient_id') == $patient->id ? 'selected' : '') }}>
                                                {{ $patient->user->name }} - {{ $patient->user->phone }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('patient_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                            </div>

                            <!-- الطبيب -->
                            <div class="col-md-6 mb-3">
                                <label for="doctor_id" class="form-label">الطبيب *</label>
                                @if($appointment)
                                    <input type="hidden" name="doctor_id" value="{{ $appointment->doctor_id }}">
                                    <input type="text" class="form-control" value="د. {{ $appointment->doctor?->user?->name ?? 'غير محدد' }} - {{ $appointment->doctor?->specialization ?? 'غير محدد' }}" readonly style="background-color: #e9ecef;">
                                @else
                                    <select class="form-select @error('doctor_id') is-invalid @enderror" 
                                            id="doctor_id" name="doctor_id" required>
                                        <option value="">اختر الطبيب</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}" 
                                                    {{ old('doctor_id', $defaultDoctor) == $doctor->id ? 'selected' : '' }}>
                                                د. {{ $doctor->user->name }} - {{ $doctor->specialization }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('doctor_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div class="row">
                            <!-- العيادة -->
                            <div class="col-md-6 mb-3">
                                <label for="department_id" class="form-label">العيادة *</label>
                                @if($appointment)
                                    <input type="hidden" name="department_id" value="{{ $appointment->department_id }}">
                                    <input type="text" class="form-control" value="{{ $appointment->department->name }} - {{ $appointment->department->room_number }}" readonly style="background-color: #e9ecef;">
                                @else
                                    <select class="form-select @error('department_id') is-invalid @enderror" 
                                            id="department_id" name="department_id" required>
                                        <option value="">اختر العيادة</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" 
                                                    {{ old('department_id', $defaultDepartment) == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }} - {{ $department->room_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                            </div>

                            <!-- نوع الزيارة -->
                            <div class="col-md-6 mb-3">
                                <label for="visit_type" class="form-label">نوع الزيارة *</label>
                                <select class="form-select @error('visit_type') is-invalid @enderror" 
                                        id="visit_type" name="visit_type" required>
                                    <option value="">اختر نوع الزيارة</option>
                                    <option value="checkup" {{ old('visit_type') == 'checkup' ? 'selected' : '' }}>كشف دوري</option>
                                    <option value="followup" {{ old('visit_type') == 'followup' ? 'selected' : '' }}>متابعة</option>
                                    <option value="emergency" {{ old('visit_type') == 'emergency' ? 'selected' : '' }}>طوارئ</option>
                                    <option value="surgery" {{ old('visit_type') == 'surgery' ? 'selected' : '' }}>عملية جراحية</option>
                                    <option value="lab" {{ old('visit_type') == 'lab' ? 'selected' : '' }}>مختبر</option>
                                    <option value="radiology" {{ old('visit_type') == 'radiology' ? 'selected' : '' }}>أشعة</option>
                                </select>
                                @error('visit_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- تاريخ الزيارة -->
                            <div class="col-md-6 mb-3">
                                <label for="visit_date" class="form-label">تاريخ الزيارة *</label>
                                <input type="date" 
                                       class="form-control @error('visit_date') is-invalid @enderror" 
                                       id="visit_date" 
                                       name="visit_date" 
                                       value="{{ old('visit_date', $defaultDate) }}"
                                       {{ $appointment ? 'readonly style=background-color:#e9ecef' : '' }}
                                       required>
                                @error('visit_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if($appointment)
                                    <small class="text-muted">مأخوذ من الموعد المحدد مسبقاً</small>
                                @endif
                            </div>

                            <!-- وقت الزيارة -->
                            <div class="col-md-6 mb-3">
                                <label for="visit_time" class="form-label">وقت الزيارة *</label>
                                <input type="time" 
                                       class="form-control @error('visit_time') is-invalid @enderror" 
                                       id="visit_time" 
                                       name="visit_time" 
                                       value="{{ old('visit_time', $defaultTime) }}"
                                       {{ $appointment ? 'readonly style=background-color:#e9ecef' : '' }}
                                       required>
                                @error('visit_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if($appointment)
                                    <small class="text-muted">مأخوذ من الموعد المحدد مسبقاً</small>
                                @endif
                            </div>
                        </div>

                        <!-- الشكوى الرئيسية -->
                        <div class="mb-3">
                            <label for="chief_complaint" class="form-label">الشكوى الرئيسية *</label>
                            <textarea class="form-control @error('chief_complaint') is-invalid @enderror" 
                                      id="chief_complaint" 
                                      name="chief_complaint" 
                                      rows="3" 
                                      placeholder="اذكر الشكوى الرئيسية للمريض..."
                                      required>{{ old('chief_complaint', $defaultComplaint) }}</textarea>
                            @error('chief_complaint')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- باقي الحقول (التشخيص، العلاج، الوصفة، etc.) تبقى كما هي -->

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                {{ $appointment ? 'تحويل إلى زيارة' : 'حفظ الزيارة' }}
                            </button>
                            <a href="{{ route('visits.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- معلومات سريعة -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ $appointment ? 'معلومات الموعد' : 'معلومات مهمة' }}
                    </h6>
                </div>
                <div class="card-body">
                    @if($appointment)
                    <div class="alert alert-success">
                        <h6>تفاصيل الموعد:</h6>
                        <p class="mb-1"><strong>المريض:</strong> {{ $appointment->patient->user->name }}</p>
                        <p class="mb-1"><strong>الطبيب:</strong> د. {{ $appointment->doctor?->user?->name ?? 'غير محدد' }}</p>
                        <p class="mb-0"><strong>التاريخ:</strong> {{ $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : 'غير محدد' }}</p>
                    </div>
                    @endif
                    
                    @if($selectedPatient && !$appointment)
                    <div class="alert alert-warning">
                        <h6>معلومات المريض المحدد:</h6>
                        <p class="mb-1"><strong>الاسم:</strong> {{ $selectedPatient->user->name }}</p>
                        <p class="mb-1"><strong>العمر:</strong> {{ $selectedPatient->age }} سنة</p>
                        <p class="mb-1"><strong>فصيلة الدم:</strong> {{ $selectedPatient->blood_type ?? '---' }}</p>
                        <p class="mb-0"><strong>الحساسيات:</strong> {{ $selectedPatient->allergies ? Str::limit($selectedPatient->allergies, 50) : '---' }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection