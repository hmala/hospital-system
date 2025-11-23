@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-flask text-primary me-2"></i>
                        إنشاء زيارة مختبرية مباشرة
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.lab-visits.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <!-- اختيار المريض -->
                            <div class="col-md-6 mb-3">
                                <label for="patient_id" class="form-label">
                                    <i class="fas fa-user me-1"></i>
                                    المريض <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('patient_id') is-invalid @enderror"
                                        id="patient_id" name="patient_id" required>
                                    <option value="">اختر المريض</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->id }}"
                                                {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                                            {{ $patient->user->name }} - {{ $patient->user->phone ?? 'لا يوجد هاتف' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('patient_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- اختيار العيادة -->
                            <div class="col-md-6 mb-3">
                                <label for="department_id" class="form-label">
                                    <i class="fas fa-clinic-medical me-1"></i>
                                    العيادة <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('department_id') is-invalid @enderror"
                                        id="department_id" name="department_id" required>
                                    <option value="">اختر العيادة</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}"
                                                {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- تاريخ الزيارة -->
                            <div class="col-md-6 mb-3">
                                <label for="visit_date" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>
                                    تاريخ الزيارة <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control @error('visit_date') is-invalid @enderror"
                                       id="visit_date" name="visit_date"
                                       value="{{ old('visit_date', date('Y-m-d')) }}" required>
                                @error('visit_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- وقت الزيارة -->
                            <div class="col-md-6 mb-3">
                                <label for="visit_time" class="form-label">
                                    <i class="fas fa-clock me-1"></i>
                                    وقت الزيارة <span class="text-danger">*</span>
                                </label>
                                <input type="time" class="form-control @error('visit_time') is-invalid @enderror"
                                       id="visit_time" name="visit_time"
                                       value="{{ old('visit_time', date('H:i')) }}" required>
                                @error('visit_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- سبب الزيارة -->
                        <div class="mb-3">
                            <label for="chief_complaint" class="form-label">
                                <i class="fas fa-comment-medical me-1"></i>
                                سبب الزيارة المخبرية <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('chief_complaint') is-invalid @enderror"
                                      id="chief_complaint" name="chief_complaint" rows="3"
                                      placeholder="وصف سبب الحاجة للتحاليل المخبرية" required>{{ old('chief_complaint') }}</textarea>
                            @error('chief_complaint')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- تنبيه للمستخدم -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>ملاحظة:</strong> سيتم تحديد التحاليل المطلوبة من قبل فني المختبر عند وصول المريض.
                        </div>

                        <!-- أزرار التحكم -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('staff.requests.index', 'lab') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                العودة
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                إنشاء الزيارة المخبرية
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection