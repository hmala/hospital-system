<!-- resources/views/visits/edit.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-edit me-2"></i>
                    تعديل الزيارة
                </h2>
                <a href="{{ route('visits.show', $visit) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>العودة للتفاصيل
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">بيانات الزيارة</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('visits.update', $visit) }}">
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
                                                {{ old('patient_id', $visit->patient_id) == $patient->id ? 'selected' : '' }}>
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
                                                {{ old('doctor_id', $visit->doctor_id) == $doctor->id ? 'selected' : '' }}>
                                            د. {{ $doctor->user->name }} - {{ $doctor->specialization }}
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
                                                {{ old('department_id', $visit->department_id) == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }} - {{ $department->room_number }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- نوع الزيارة -->
                            <div class="col-md-6 mb-3">
                                <label for="visit_type" class="form-label">نوع الزيارة *</label>
                                <select class="form-select @error('visit_type') is-invalid @enderror"
                                        id="visit_type" name="visit_type" required>
                                    <option value="">اختر نوع الزيارة</option>
                                    <option value="checkup" {{ old('visit_type', $visit->visit_type) == 'checkup' ? 'selected' : '' }}>كشف دوري</option>
                                    <option value="followup" {{ old('visit_type', $visit->visit_type) == 'followup' ? 'selected' : '' }}>متابعة</option>
                                    <option value="emergency" {{ old('visit_type', $visit->visit_type) == 'emergency' ? 'selected' : '' }}>طوارئ</option>
                                    <option value="surgery" {{ old('visit_type', $visit->visit_type) == 'surgery' ? 'selected' : '' }}>عملية جراحية</option>
                                    <option value="lab" {{ old('visit_type', $visit->visit_type) == 'lab' ? 'selected' : '' }}>مختبر</option>
                                    <option value="radiology" {{ old('visit_type', $visit->visit_type) == 'radiology' ? 'selected' : '' }}>أشعة</option>
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
                                       value="{{ old('visit_date', $visit->visit_date ? $visit->visit_date->format('Y-m-d') : '') }}"
                                       required>
                                @error('visit_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- وقت الزيارة -->
                            <div class="col-md-6 mb-3">
                                <label for="visit_time" class="form-label">وقت الزيارة *</label>
                                <input type="time"
                                       class="form-control @error('visit_time') is-invalid @enderror"
                                       id="visit_time"
                                       name="visit_time"
                                       value="{{ old('visit_time', $visit->visit_time ? $visit->visit_time->format('H:i') : '') }}"
                                       required>
                                @error('visit_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                                      required>{{ old('chief_complaint', $visit->chief_complaint) }}</textarea>
                            @error('chief_complaint')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- باقي الحقول -->
                        <div class="row">
                            <!-- التشخيص -->
                            <div class="col-md-6 mb-3">
                                <label for="diagnosis" class="form-label">التشخيص</label>
                                <textarea class="form-control @error('diagnosis') is-invalid @enderror"
                                          id="diagnosis"
                                          name="diagnosis"
                                          rows="3"
                                          placeholder="اكتب التشخيص...">{{ old('diagnosis', is_array($visit->diagnosis) ? ($visit->diagnosis['description'] ?? '') : $visit->diagnosis) }}</textarea>
                                @error('diagnosis')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- العلاج -->
                            <div class="col-md-6 mb-3">
                                <label for="treatment" class="form-label">العلاج</label>
                                <textarea class="form-control @error('treatment') is-invalid @enderror"
                                          id="treatment"
                                          name="treatment"
                                          rows="3"
                                          placeholder="اكتب العلاج المقترح...">{{ old('treatment', $visit->treatment) }}</textarea>
                                @error('treatment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- الوصفة الطبية -->
                        <div class="mb-3">
                            <label for="prescription" class="form-label">الوصفة الطبية</label>
                            <textarea class="form-control @error('prescription') is-invalid @enderror"
                                      id="prescription"
                                      name="prescription"
                                      rows="4"
                                      placeholder="اكتب الوصفة الطبية...">{{ old('prescription', $visit->prescription) }}</textarea>
                            @error('prescription')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- الملاحظات -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">ملاحظات إضافية</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror"
                                      id="notes"
                                      name="notes"
                                      rows="3"
                                      placeholder="أي ملاحظات إضافية...">{{ old('notes', $visit->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- الحالة -->
                        <div class="mb-3">
                            <label for="status" class="form-label">حالة الزيارة</label>
                            <select class="form-select @error('status') is-invalid @enderror"
                                    id="status" name="status">
                                <option value="scheduled" {{ old('status', $visit->status) == 'scheduled' ? 'selected' : '' }}>مجدولة</option>
                                <option value="in_progress" {{ old('status', $visit->status) == 'in_progress' ? 'selected' : '' }}>قيد التنفيذ</option>
                                <option value="completed" {{ old('status', $visit->status) == 'completed' ? 'selected' : '' }}>مكتملة</option>
                                <option value="cancelled" {{ old('status', $visit->status) == 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>حفظ التغييرات
                            </button>
                            <a href="{{ route('visits.show', $visit) }}" class="btn btn-secondary">
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
                        معلومات الزيارة
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>تفاصيل الزيارة الحالية:</h6>
                        <p class="mb-1"><strong>المريض:</strong> {{ $visit->patient->user->name }}</p>
                        <p class="mb-1"><strong>الطبيب:</strong> د. {{ $visit->doctor?->user?->name ?? 'غير محدد' }}</p>
                        <p class="mb-1"><strong>العيادة:</strong> {{ $visit->department->name }}</p>
                        <p class="mb-1"><strong>التاريخ:</strong> {{ $visit->visit_date ? $visit->visit_date->format('Y-m-d') : 'غير محدد' }}</p>
                        <p class="mb-1"><strong>الوقت:</strong> {{ $visit->visit_time ? $visit->visit_time->format('H:i') : 'غير محدد' }}</p>
                        <p class="mb-0"><strong>الحالة:</strong>
                            <span class="badge bg-{{ $visit->status == 'completed' ? 'success' : ($visit->status == 'in_progress' ? 'warning' : 'secondary') }}">
                                {{ $visit->status_text }}
                            </span>
                        </p>
                    </div>

                    @if($visit->appointment)
                    <div class="alert alert-success">
                        <h6><i class="fas fa-calendar-check me-2"></i>مأخوذة من موعد</h6>
                        <p class="mb-0">هذه الزيارة تم تحويلها من موعد طبي مسبق.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection