@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-warning bg-opacity-10 border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-edit text-warning me-2"></i>تعديل بيانات العيادة: {{ $department->name }}
                    </h5>
                    <a href="{{ route('departments.admin') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fas fa-arrow-right me-1"></i>رجوع للعيادات
                    </a>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="{{ route('departments.update', $department) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold">اسم العيادة <span class="text-danger">*</span></label>
                                <input id="name" type="text" class="form-control rounded-3 @error('name') is-invalid @enderror"
                                       name="name" value="{{ old('name', $department->name) }}" required autofocus>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="type" class="form-label fw-semibold">نوع العيادة <span class="text-danger">*</span></label>
                                <select id="type" class="form-select rounded-3 @error('type') is-invalid @enderror" name="type" required>
                                    <option value="">اختر نوع العيادة</option>
                                    @php $selectedType = old('type', $department->type); @endphp
                                    <option value="internal" {{ $selectedType == 'internal' ? 'selected' : '' }}>باطنية</option>
                                    <option value="surgery" {{ $selectedType == 'surgery' ? 'selected' : '' }}>جراحة</option>
                                    <option value="pediatrics" {{ $selectedType == 'pediatrics' ? 'selected' : '' }}>أطفال</option>
                                    <option value="obstetrics" {{ $selectedType == 'obstetrics' ? 'selected' : '' }}>نسائية</option>
                                    <option value="orthopedics" {{ $selectedType == 'orthopedics' ? 'selected' : '' }}>عظام</option>
                                    <option value="cardiology" {{ $selectedType == 'cardiology' ? 'selected' : '' }}>قلب</option>
                                    <option value="dentistry" {{ $selectedType == 'dentistry' ? 'selected' : '' }}>أسنان</option>
                                    <option value="dermatology" {{ $selectedType == 'dermatology' ? 'selected' : '' }}>جلدية</option>
                                    <option value="emergency" {{ $selectedType == 'emergency' ? 'selected' : '' }}>طوارئ</option>
                                    <option value="other" {{ $selectedType == 'other' ? 'selected' : '' }}>أخرى</option>
                                </select>
                                @error('type')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="room_number" class="form-label fw-semibold">رقم الغرفة <span class="text-danger">*</span></label>
                                <input id="room_number" type="text" class="form-control rounded-3 @error('room_number') is-invalid @enderror"
                                       name="room_number" value="{{ old('room_number', $department->room_number) }}" required>
                                @error('room_number')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="consultation_fee" class="form-label fw-bold">رسوم الكشف كاش (دينار) <span class="text-danger">*</span></label>
                                <input id="consultation_fee" type="number" step="500" class="form-control rounded-3 @error('consultation_fee') is-invalid @enderror"
                                       name="consultation_fee" value="{{ old('consultation_fee', (int)$department->consultation_fee) }}" required>
                                <div class="form-text small">سعر الكشفية العادية للمراجع بدون ضمان</div>
                                @error('consultation_fee')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- قسم تسعير الضمان الصحي وضمان وزارة الداخلية -->
                        <div class="card mb-3 border-primary border-opacity-25 bg-light bg-opacity-50 rounded-3">
                            <div class="card-header bg-primary bg-opacity-10 py-2">
                                <h6 class="mb-0 text-primary fw-bold small">
                                    <i class="fas fa-shield-alt me-1"></i> تسعير وتغطية كشفية العيادة لجهات الضمان
                                </h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-3">
                                    {{-- ضمان الداخلية --}}
                                    <div class="col-md-6 border-start">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label fw-bold small mb-0"><i class="fas fa-id-badge text-primary me-1"></i>ضمان الداخلية (MOI)</label>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="is_moi_active" value="1" id="editDeptIsMoiActive" {{ old('is_moi_active', $department->is_moi_active ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="editDeptIsMoiActive">مشمول</label>
                                            </div>
                                        </div>
                                        <input type="number" step="500" name="moi_price" class="form-control form-control-sm rounded-2" value="{{ old('moi_price', $department->moi_price ? (int)$department->moi_price : '') }}" placeholder="سعر كشفية الداخلية (اتركه فارغاً لاعتماد الكاش)">
                                    </div>
                                    {{-- هيئة الضمان --}}
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label fw-bold small mb-0"><i class="fas fa-heartbeat text-success me-1"></i>هيئة الضمان (HI)</label>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="is_hi_active" value="1" id="editDeptIsHiActive" {{ old('is_hi_active', $department->is_hi_active ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="editDeptIsHiActive">مشمول</label>
                                            </div>
                                        </div>
                                        <input type="number" step="500" name="hi_price" class="form-control form-control-sm rounded-2" value="{{ old('hi_price', $department->hi_price ? (int)$department->hi_price : '') }}" placeholder="سعر كشفية هيئة الضمان (اتركه فارغاً لاعتماد الكاش)">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="working_hours_start" class="form-label fw-semibold">ساعات العمل - من <span class="text-danger">*</span></label>
                                <input id="working_hours_start" type="time" class="form-control rounded-3 @error('working_hours_start') is-invalid @enderror"
                                       name="working_hours_start" value="{{ old('working_hours_start', substr($department->working_hours_start, 0, 5)) }}" required>
                                @error('working_hours_start')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="working_hours_end" class="form-label fw-semibold">ساعات العمل - إلى <span class="text-danger">*</span></label>
                                <input id="working_hours_end" type="time" class="form-control rounded-3 @error('working_hours_end') is-invalid @enderror"
                                       name="working_hours_end" value="{{ old('working_hours_end', substr($department->working_hours_end, 0, 5)) }}" required>
                                @error('working_hours_end')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="max_patients_per_day" class="form-label fw-semibold">الحد الأقصى للمرضى يومياً <span class="text-danger">*</span></label>
                                <input id="max_patients_per_day" type="number" class="form-control rounded-3 @error('max_patients_per_day') is-invalid @enderror"
                                       name="max_patients_per_day" value="{{ old('max_patients_per_day', $department->max_patients_per_day) }}" required>
                                @error('max_patients_per_day')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                                       {{ old('is_active', $department->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active">
                                    تفعيل العيادة لاستقبال المواعيد والحجوزات
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="{{ route('departments.admin') }}" class="btn btn-light rounded-pill px-4">إلغاء</a>
                            <button type="submit" class="btn btn-warning rounded-pill px-5 fw-bold">
                                <i class="fas fa-save me-2"></i>حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
