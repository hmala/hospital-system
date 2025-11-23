<!-- resources/views/patients/edit.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-edit me-2"></i>
                    تعديل بيانات المريض
                </h2>
                <a href="{{ route('patients.show', $patient) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>العودة للتفاصيل
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">البيانات الأساسية</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('patients.update', $patient) }}">
                        @csrf
                        @method('PUT')

                        @if($errors->has('duplicate_patient'))
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                {{ $errors->first('duplicate_patient') }}
                            </div>
                        @endif

                        <div class="row">
                            <!-- الاسم -->
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">الاسم الكامل *</label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $patient->user ? $patient->user->name : '') }}"
                                       required
                                       placeholder="ادخل الاسم الكامل">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- البريد الإلكتروني -->
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني *</label>
                                <input type="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       id="email"
                                       name="email"
                                       value="{{ old('email', $patient->user ? $patient->user->email : '') }}"
                                       required
                                       placeholder="example@email.com">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- الهاتف -->
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">رقم الهاتف *</label>
                                <input type="text"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       id="phone"
                                       name="phone"
                                       value="{{ old('phone', $patient->user ? $patient->user->phone : '') }}"
                                       required
                                       placeholder="07701234567">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- تاريخ الميلاد -->
                            <div class="col-md-6 mb-3">
                                <label for="date_of_birth" class="form-label">تاريخ الميلاد *</label>
                                <input type="date"
                                       class="form-control @error('date_of_birth') is-invalid @enderror"
                                       id="date_of_birth"
                                       name="date_of_birth"
                                       value="{{ old('date_of_birth', $patient->user && $patient->user->date_of_birth ? $patient->user->date_of_birth->format('Y-m-d') : '') }}"
                                       required>
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- النوع -->
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">النوع *</label>
                                <select class="form-select @error('gender') is-invalid @enderror"
                                        id="gender" name="gender" required>
                                    <option value="">اختر النوع</option>
                                    <option value="male" {{ old('gender', $patient->user ? $patient->user->gender : '') == 'male' ? 'selected' : '' }}>ذكر</option>
                                    <option value="female" {{ old('gender', $patient->user ? $patient->user->gender : '') == 'female' ? 'selected' : '' }}>أنثى</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- اسم الأم الثلاثي -->
                            <div class="col-md-6 mb-3">
                                <label for="mother_name" class="form-label">اسم الأم الثلاثي</label>
                                <input type="text"
                                       class="form-control @error('mother_name') is-invalid @enderror"
                                       id="mother_name"
                                       name="mother_name"
                                       value="{{ old('mother_name', $patient->mother_name) }}"
                                       placeholder="ادخل اسم الأم الثلاثي">
                                @error('mother_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- الحالة الاجتماعية -->
                            <div class="col-md-6 mb-3">
                                <label for="marital_status" class="form-label">الحالة الاجتماعية</label>
                                <select class="form-select @error('marital_status') is-invalid @enderror"
                                        id="marital_status" name="marital_status">
                                    <option value="">اختر الحالة الاجتماعية</option>
                                    <option value="أعزب" {{ old('marital_status', $patient->marital_status) == 'أعزب' ? 'selected' : '' }}>أعزب</option>
                                    <option value="متزوج" {{ old('marital_status', $patient->marital_status) == 'متزوج' ? 'selected' : '' }}>متزوج</option>
                                    <option value="مطلق" {{ old('marital_status', $patient->marital_status) == 'مطلق' ? 'selected' : '' }}>مطلق</option>
                                    <option value="أرمل" {{ old('marital_status', $patient->marital_status) == 'أرمل' ? 'selected' : '' }}>أرمل</option>
                                </select>
                                @error('marital_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">المعلومات الطبية</h6>

                        <div class="row">
                            <!-- رقم الطوارئ -->
                            <div class="col-md-6 mb-3">
                                <label for="emergency_contact" class="form-label">رقم الطوارئ *</label>
                                <input type="text"
                                       class="form-control @error('emergency_contact') is-invalid @enderror"
                                       id="emergency_contact"
                                       name="emergency_contact"
                                       value="{{ old('emergency_contact', $patient->emergency_contact) }}"
                                       required
                                       placeholder="رقم شخص للطوارئ">
                                @error('emergency_contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- فصيلة الدم -->
                            <div class="col-md-6 mb-3">
                                <label for="blood_type" class="form-label">فصيلة الدم</label>
                                <select class="form-select @error('blood_type') is-invalid @enderror"
                                        id="blood_type" name="blood_type">
                                    <option value="">اختر فصيلة الدم</option>
                                    <option value="A+" {{ old('blood_type', $patient->blood_type) == 'A+' ? 'selected' : '' }}>A+</option>
                                    <option value="A-" {{ old('blood_type', $patient->blood_type) == 'A-' ? 'selected' : '' }}>A-</option>
                                    <option value="B+" {{ old('blood_type', $patient->blood_type) == 'B+' ? 'selected' : '' }}>B+</option>
                                    <option value="B-" {{ old('blood_type', $patient->blood_type) == 'B-' ? 'selected' : '' }}>B-</option>
                                    <option value="AB+" {{ old('blood_type', $patient->blood_type) == 'AB+' ? 'selected' : '' }}>AB+</option>
                                    <option value="AB-" {{ old('blood_type', $patient->blood_type) == 'AB-' ? 'selected' : '' }}>AB-</option>
                                    <option value="O+" {{ old('blood_type', $patient->blood_type) == 'O+' ? 'selected' : '' }}>O+</option>
                                    <option value="O-" {{ old('blood_type', $patient->blood_type) == 'O-' ? 'selected' : '' }}>O-</option>
                                </select>
                                @error('blood_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- الرقم الوطني -->
                            <div class="col-md-6 mb-3">
                                <label for="national_id" class="form-label">الرقم الوطني</label>
                                <input type="text"
                                       class="form-control @error('national_id') is-invalid @enderror"
                                       id="national_id"
                                       name="national_id"
                                       value="{{ old('national_id', $patient->national_id) }}"
                                       placeholder="الرقم الوطني">
                                @error('national_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- هل مشمول بالضمان -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">هل مشمول بالضمان؟ *</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="covered_by_insurance" id="covered_yes" value="1" {{ old('covered_by_insurance', $patient->covered_by_insurance) == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="covered_yes">نعم</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="covered_by_insurance" id="covered_no" value="0" {{ old('covered_by_insurance', $patient->covered_by_insurance) == '0' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="covered_no">كلا</label>
                                </div>
                                @error('covered_by_insurance')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- رقم دفتر التأمين -->
                            <div class="col-md-6 mb-3 @if(old('covered_by_insurance', $patient->covered_by_insurance) != '1') d-none @endif" id="insurance_booklet_div">
                                <label for="insurance_booklet_number" class="form-label">رقم دفتر التأمين</label>
                                <input type="text"
                                       class="form-control @error('insurance_booklet_number') is-invalid @enderror"
                                       id="insurance_booklet_number"
                                       name="insurance_booklet_number"
                                       value="{{ old('insurance_booklet_number', $patient->insurance_booklet_number) }}"
                                       placeholder="رقم دفتر التأمين">
                                @error('insurance_booklet_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- البلد -->
                            <div class="col-md-3 mb-3">
                                <label for="country" class="form-label">البلد</label>
                                <select class="form-select @error('country') is-invalid @enderror"
                                        id="country" name="country">
                                    <option value="">اختر البلد</option>
                                    @foreach($countries as $countryItem)
                                        <option value="{{ $countryItem->id }}" {{ old('country', $patient->country_id) == $countryItem->id ? 'selected' : ($countryItem->id == $iraq_id ? 'selected' : '') }}>{{ $countryItem->name }}</option>
                                    @endforeach
                                </select>
                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- المحافظة -->
                            <div class="col-md-3 mb-3">
                                <label for="governorate" class="form-label">المحافظة</label>
                                <select class="form-select @error('governorate') is-invalid @enderror"
                                        id="governorate" name="governorate">
                                    <option value="">اختر المحافظة</option>
                                    @foreach($governorates as $governorate)
                                        <option value="{{ $governorate->name }}" {{ old('governorate', $patient->governorate) == $governorate->name ? 'selected' : '' }}>{{ $governorate->name }}</option>
                                    @endforeach
                                </select>
                                @error('governorate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- القضاء -->
                            <div class="col-md-3 mb-3">
                                <label for="district" class="form-label">القضاء</label>
                                <input type="text"
                                       class="form-control @error('district') is-invalid @enderror"
                                       id="district"
                                       name="district"
                                       value="{{ old('district', $patient->district) }}"
                                       placeholder="القضاء">
                                @error('district')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- الناحية -->
                            <div class="col-md-3 mb-3">
                                <label for="neighborhood" class="form-label">الناحية</label>
                                <input type="text"
                                       class="form-control @error('neighborhood') is-invalid @enderror"
                                       id="neighborhood"
                                       name="neighborhood"
                                       value="{{ old('neighborhood', $patient->neighborhood) }}"
                                       placeholder="الناحية">
                                @error('neighborhood')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr>


                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>حفظ التعديلات
                            </button>
                            <a href="{{ route('patients.show', $patient) }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- معلومات سريعة -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات مهمة</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <small>
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            كلمة المرور الافتراضية: <strong>password</strong>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // إظهار/إخفاء رقم دفتر التأمين
        document.querySelectorAll('input[name="covered_by_insurance"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                const insuranceDiv = document.getElementById('insurance_booklet_div');
                if (this.value === '1') {
                    insuranceDiv.classList.remove('d-none');
                } else {
                    insuranceDiv.classList.add('d-none');
                }
            });
        });
    </script>
</div>
@endsection