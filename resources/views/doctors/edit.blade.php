@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">تعديل بيانات الطبيب: {{ $doctor->user ? $doctor->user->name : 'طبيب بدون بيانات' }}</h4>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('doctors.update', $doctor) }}">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <label for="name" class="col-md-3 col-form-label text-md-end">الاسم الكامل</label>
                            <div class="col-md-8">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                       name="name" value="{{ old('name', $doctor->user ? $doctor->user->name : '') }}" required autofocus>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="email" class="col-md-3 col-form-label text-md-end">البريد الإلكتروني</label>
                            <div class="col-md-8">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                       name="email" value="{{ old('email', $doctor->user ? $doctor->user->email : '') }}" required>
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="phone" class="col-md-3 col-form-label text-md-end">رقم الهاتف</label>
                            <div class="col-md-8">
                                <input id="phone" type="text" class="form-control @error('phone') is-invalid @enderror"
                                       name="phone" value="{{ old('phone', $doctor->phone) }}" required>
                                @error('phone')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="department_id" class="col-md-3 col-form-label text-md-end">القسم الطبي</label>
                            <div class="col-md-8">
                                <select id="department_id" class="form-control @error('department_id') is-invalid @enderror" name="department_id" required>
                                    <option value="">اختر القسم الطبي</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ old('department_id', $doctor->department_id) == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }} ({{ $department->getTypeText() }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="type" class="col-md-3 col-form-label text-md-end">نوع الطبيب</label>
                            <div class="col-md-8">
                                <select id="type" class="form-control @error('type') is-invalid @enderror" name="type" required>
                                    <option value="">اختر نوع الطبيب</option>
                                    <option value="consultant" {{ old('type', $doctor->type) == 'consultant' ? 'selected' : '' }}>استشاري</option>
                                    <option value="anesthesiologist" {{ old('type', $doctor->type) == 'anesthesiologist' ? 'selected' : '' }}>مخدر</option>
                                    <option value="surgeon" {{ old('type', $doctor->type) == 'surgeon' ? 'selected' : '' }}>جراح</option>
                                    <option value="emergency" {{ old('type', $doctor->type) == 'emergency' ? 'selected' : '' }}>طوارئ</option>
                                </select>
                                @error('type')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="specialization" class="col-md-3 col-form-label text-md-end">التخصص</label>
                            <div class="col-md-8">
                                <input id="specialization" type="text" class="form-control @error('specialization') is-invalid @enderror"
                                       name="specialization" value="{{ old('specialization', $doctor->specialization) }}" required>
                                @error('specialization')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="consultation_fee" class="col-md-3 col-form-label text-md-end">رسوم الكشف (دينار)</label>
                            <div class="col-md-8">
                                <input id="consultation_fee" type="number" step="0.01" class="form-control @error('consultation_fee') is-invalid @enderror"
                                       name="consultation_fee" value="{{ old('consultation_fee', $doctor->consultation_fee) }}" required min="0">
                                @error('consultation_fee')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- قسم أوقات العمل -->
                        <hr class="my-4">
                        <h5 class="mb-3 text-primary">
                            <i class="fas fa-clock me-2"></i>أوقات العمل
                        </h5>

                        <div class="row mb-3">
                            <label for="start_time" class="col-md-3 col-form-label text-md-end">وقت الدخول</label>
                            <div class="col-md-8">
                                <input id="start_time" type="time" class="form-control @error('start_time') is-invalid @enderror"
                                       name="start_time" value="{{ old('start_time', $doctor->start_time ? date('H:i', strtotime($doctor->start_time)) : '08:00') }}" required>
                                @error('start_time')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="end_time" class="col-md-3 col-form-label text-md-end">وقت الخروج</label>
                            <div class="col-md-8">
                                <input id="end_time" type="time" class="form-control @error('end_time') is-invalid @enderror"
                                       name="end_time" value="{{ old('end_time', $doctor->end_time ? date('H:i', strtotime($doctor->end_time)) : '16:00') }}" required>
                                @error('end_time')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-md-3 col-form-label text-md-end">أيام العمل</label>
                            <div class="col-md-8">
                                <div class="row">
                                    @php
                                        $workingDays = old('working_days', $doctor->working_days ?? []);
                                    @endphp
                                    <div class="col-md-4 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="working_days[]" value="السبت" id="day_saturday" {{ in_array('السبت', $workingDays) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="day_saturday">السبت</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="working_days[]" value="الأحد" id="day_sunday" {{ in_array('الأحد', $workingDays) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="day_sunday">الأحد</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="working_days[]" value="الإثنين" id="day_monday" {{ in_array('الإثنين', $workingDays) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="day_monday">الإثنين</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="working_days[]" value="الثلاثاء" id="day_tuesday" {{ in_array('الثلاثاء', $workingDays) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="day_tuesday">الثلاثاء</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="working_days[]" value="الأربعاء" id="day_wednesday" {{ in_array('الأربعاء', $workingDays) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="day_wednesday">الأربعاء</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="working_days[]" value="الخميس" id="day_thursday" {{ in_array('الخميس', $workingDays) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="day_thursday">الخميس</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="working_days[]" value="الجمعة" id="day_friday" {{ in_array('الجمعة', $workingDays) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="day_friday">الجمعة</label>
                                        </div>
                                    </div>
                                </div>
                                @error('working_days')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 offset-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                           value="1" {{ old('is_active', $doctor->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        الطبيب نشط
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- قسم تغيير كلمة المرور -->
                        <hr class="my-4">
                        <h5 class="mb-3 text-primary">
                            <i class="fas fa-key me-2"></i>تغيير كلمة المرور
                        </h5>
                        <div class="alert alert-info">
                            <small>
                                <i class="fas fa-info-circle me-2"></i>
                                اترك هذه الحقول فارغة إذا كنت لا تريد تغيير كلمة المرور الحالية
                            </small>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-3 col-form-label text-md-end">كلمة المرور الجديدة</label>
                            <div class="col-md-8">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                                       name="password" placeholder="أدخل كلمة مرور جديدة">
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password_confirmation" class="col-md-3 col-form-label text-md-end">تأكيد كلمة المرور</label>
                            <div class="col-md-8">
                                <input id="password_confirmation" type="password" class="form-control"
                                       name="password_confirmation" placeholder="أعد إدخال كلمة المرور">
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ التغييرات
                                </button>
                                <a href="{{ route('doctors.show', $doctor) }}" class="btn btn-info">
                                    <i class="fas fa-eye"></i> عرض الطبيب
                                </a>
                                <a href="{{ route('doctors.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> إلغاء
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// تحسين تجربة المستخدم
document.addEventListener('DOMContentLoaded', function() {
    // تنسيق رقم الهاتف
    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.startsWith('964')) {
            value = '+' + value;
        } else if (value.startsWith('07')) {
            value = '+964' + value.substring(1);
        }
        e.target.value = value;
    });
});
</script>

<style>
.form-control:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.card-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border-bottom: none;
}

.btn-primary {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #218838 0%, #1aa085 100%);
}

.btn-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    border: none;
}

.btn-info:hover {
    background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
}
</style>
@endsection