@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" style="background-color: #f8fafc; min-height: 100vh;">
    <div class="row justify-content-center">
        <div class="col-xl-9 col-lg-10">

            <!-- Breadcrumb & Back -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-primary mb-1">
                        <i class="fas fa-user-edit me-2"></i>تعديل بيانات وجدول دوام د. {{ $doctor->user->name ?? 'طبيب العيون' }}
                    </h3>
                    <p class="text-muted small mb-0">تحديث أيام وساعات العيادة، الاختصاص الدقيق، وأجور الكشفية في مركز العيون</p>
                </div>
                <a href="{{ route('eye.availability.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i> العودة لدليل توفر الأطباء
                </a>
            </div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('eye.availability.doctors.updateSettings', $doctor) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- بطاقة 1: البيانات الأساسية -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-id-card text-primary me-2"></i>البيانات الأساسية للطبيب
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">اسم الطبيب <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $doctor->user->name ?? '') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">البريد الإلكتروني</label>
                                <input type="email" class="form-control bg-light" value="{{ $doctor->user->email ?? '' }}" readonly>
                                <div class="form-text small text-muted">حساب تسجيل الدخول ثابت</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">رقم الهاتف <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $doctor->phone ?? ($doctor->user->phone ?? '')) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">الدرجة المهنية <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" required>
                                    <option value="consultant" {{ old('type', $doctor->type) == 'consultant' ? 'selected' : '' }}>استشاري طب وجراحة عيون (Consultant)</option>
                                    <option value="surgeon" {{ old('type', $doctor->type) == 'surgeon' ? 'selected' : '' }}>أخصائي / جراح عيون (Specialist Surgeon)</option>
                                    <option value="resident" {{ old('type', $doctor->type) == 'resident' ? 'selected' : '' }}>مقيم أقدم عيون (Senior Resident)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- بطاقة 2: التخصص الدقيق -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-eye text-primary me-2"></i>التخصص العيني الدقيق
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">التخصص الدقيق بالمركز <span class="text-danger">*</span></label>
                                <input type="text" name="specialization" class="form-control" value="{{ old('specialization', $doctor->specialization) }}" required list="specializationList">
                                <datalist id="specializationList">
                                    @foreach($eyeSpecializations as $spec)
                                        <option value="{{ $spec }}">
                                    @endforeach
                                </datalist>
                                <div class="form-text small text-muted">يمكنك الاختيار من القائمة أو كتابة تخصص دقيق مخصص</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- بطاقة 3: الأجور والتأمين -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-coins text-warning me-2"></i>أجور كشفية العيون وتغطية التأمين
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">أجر الكشف كاش (د.ع) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="consultation_fee" class="form-control fw-bold" value="{{ old('consultation_fee', $doctor->consultation_fee) }}" min="0" step="500" required>
                                    <span class="input-group-text bg-light">د.ع</span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold mb-0 text-success">الضمان الصحي</label>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" name="is_hi_active" value="1" {{ old('is_hi_active', $doctor->is_hi_active) ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="hi_price" class="form-control" value="{{ old('hi_price', $doctor->hi_price ?? $doctor->consultation_fee) }}" min="0" step="500">
                                        <span class="input-group-text">د.ع</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold mb-0 text-primary">قوى الأمن الداخلي</label>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" name="is_moi_active" value="1" {{ old('is_moi_active', $doctor->is_moi_active) ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="moi_price" class="form-control" value="{{ old('moi_price', $doctor->moi_price ?? ($doctor->consultation_fee * 0.8)) }}" min="0" step="500">
                                        <span class="input-group-text">د.ع</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- بطاقة 4: جدول الدوام وساعات العيادة -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-calendar-alt text-primary me-2"></i>أيام وساعات دوام عيادة الطبيب
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        @php
                            $currentDays = is_array($doctor->working_days) ? $doctor->working_days : (json_decode($doctor->working_days, true) ?? []);
                        @endphp
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-2">أيام العمل المعتمدة بالمركز <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-2 p-3 bg-light rounded-3 border">
                                @foreach($weekDays as $day)
                                    <div class="form-check form-check-inline m-0 me-3">
                                        <input class="form-check-input" type="checkbox" name="working_days[]" value="{{ $day }}" id="edit_day_{{ $loop->index }}" {{ in_array($day, old('working_days', $currentDays)) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="edit_day_{{ $loop->index }}">{{ $day }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">وقت بدء دوام العيادة <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $doctor->start_time ? \Illuminate\Support\Carbon::parse($doctor->start_time)->format('H:i') : '08:30') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">وقت انتهاء دوام العيادة <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $doctor->end_time ? \Illuminate\Support\Carbon::parse($doctor->end_time)->format('H:i') : '14:30') }}" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-center">
                                <div class="form-check form-switch p-3 bg-light rounded-3 border w-100 mt-md-4">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="is_active" value="1" id="activeStatusSwitch" {{ old('is_active', $doctor->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="activeStatusSwitch">
                                        طبيب نشط بالمركز
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- أزرار الإجراء -->
                <div class="d-flex justify-content-end gap-3 mb-5">
                    <a href="{{ route('eye.availability.index') }}" class="btn btn-light px-4 py-2 border">إلغاء</a>
                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm fw-bold">
                        <i class="fas fa-save me-2"></i>حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
