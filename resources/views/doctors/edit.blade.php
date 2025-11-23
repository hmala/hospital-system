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
                            <label for="qualification" class="col-md-3 col-form-label text-md-end">المؤهل العلمي</label>
                            <div class="col-md-8">
                                <input id="qualification" type="text" class="form-control @error('qualification') is-invalid @enderror"
                                       name="qualification" value="{{ old('qualification', $doctor->qualification) }}" required
                                       placeholder="مثال: بكالوريوس الطب والجراحة">
                                @error('qualification')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="license_number" class="col-md-3 col-form-label text-md-end">رقم الترخيص الطبي</label>
                            <div class="col-md-8">
                                <input id="license_number" type="text" class="form-control @error('license_number') is-invalid @enderror"
                                       name="license_number" value="{{ old('license_number', $doctor->license_number) }}" required>
                                @error('license_number')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="experience_years" class="col-md-3 col-form-label text-md-end">سنوات الخبرة</label>
                            <div class="col-md-8">
                                <input id="experience_years" type="number" class="form-control @error('experience_years') is-invalid @enderror"
                                       name="experience_years" value="{{ old('experience_years', $doctor->experience_years) }}" required min="0">
                                @error('experience_years')
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

                        <div class="row mb-3">
                            <label for="max_patients_per_day" class="col-md-3 col-form-label text-md-end">الحد الأقصى للمرضى يومياً</label>
                            <div class="col-md-8">
                                <input id="max_patients_per_day" type="number" class="form-control @error('max_patients_per_day') is-invalid @enderror"
                                       name="max_patients_per_day" value="{{ old('max_patients_per_day', $doctor->max_patients_per_day) }}" required min="1">
                                @error('max_patients_per_day')
                                    <span class="invalid-feedback" role="alert">
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

                        <div class="row mb-3">
                            <label for="bio" class="col-md-3 col-form-label text-md-end">نبذة تعريفية</label>
                            <div class="col-md-8">
                                <textarea id="bio" class="form-control @error('bio') is-invalid @enderror" name="bio" rows="3"
                                          placeholder="معلومات إضافية عن الطبيب...">{{ old('bio', $doctor->bio) }}</textarea>
                                @error('bio')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
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