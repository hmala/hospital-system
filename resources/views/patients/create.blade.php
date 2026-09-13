<!-- resources/views/patients/create.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-user-plus me-2"></i>
                    إضافة مريض جديد
                </h2>
                <a href="{{ route('patients.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>العودة للقائمة
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
                    <form method="POST" action="{{ route('patients.store') }}">
                        @csrf

                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <h6 class="alert-heading fw-bold mb-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    يرجى تصحيح الأخطاء التالية قبل الحفظ:
                                </h6>
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                                       value="{{ old('name') }}"
                                       required 
                                       placeholder="ادخل الاسم الكامل">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- البريد الإلكتروني -->
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}"
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
                                       value="{{ old('phone') }}"
                                       required 
                                       placeholder="ادخل رقم الهاتف">
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
                                       value="{{ old('date_of_birth') }}"
                                       required>
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- النوع -->
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">الجنس *</label>
                                <select class="form-select @error('gender') is-invalid @enderror" 
                                        id="gender" name="gender" required>
                                    <option value="">اختر النوع</option>
                                    <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>ذكر</option>
                                    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>أنثى</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- العنوان -->
                           
                        </div>

                        <div class="row">
                            <!-- اسم الأم الثلاثي -->
                            <div class="col-md-6 mb-3">
                                <label for="mother_name" class="form-label">اسم الأم الثلاثي *</label>
                                <input type="text" 
                                       class="form-control @error('mother_name') is-invalid @enderror" 
                                       id="mother_name" 
                                       name="mother_name" 
                                       value="{{ old('mother_name') }}"
                                       required
                                       placeholder="ادخل اسم الأم الثلاثي">
                                @error('mother_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- الحالة الاجتماعية -->
                            <div class="col-md-6 mb-3">
                                <label for="marital_status" class="form-label">الحالة الاجتماعية *</label>
                                <select class="form-select @error('marital_status') is-invalid @enderror" 
                                        id="marital_status" name="marital_status" required>
                                    <option value="">اختر الحالة الاجتماعية</option>
                                    <option value="أعزب" {{ old('marital_status') == 'أعزب' ? 'selected' : '' }}>أعزب</option>
                                    <option value="متزوج" {{ old('marital_status') == 'متزوج' ? 'selected' : '' }}>متزوج</option>
                                    <option value="مطلق" {{ old('marital_status') == 'مطلق' ? 'selected' : '' }}>مطلق</option>
                                    <option value="أرمل" {{ old('marital_status') == 'أرمل' ? 'selected' : '' }}>أرمل</option>
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
                                <label for="emergency_contact" class="form-label">رقم الطوارئ</label>
                                <input type="text" 
                                       class="form-control @error('emergency_contact') is-invalid @enderror" 
                                       id="emergency_contact" 
                                       name="emergency_contact" 
                                       value="{{ old('emergency_contact') }}"
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
                                    <option value="A+" {{ old('blood_type') == 'A+' ? 'selected' : '' }}>A+</option>
                                    <option value="A-" {{ old('blood_type') == 'A-' ? 'selected' : '' }}>A-</option>
                                    <option value="B+" {{ old('blood_type') == 'B+' ? 'selected' : '' }}>B+</option>
                                    <option value="B-" {{ old('blood_type') == 'B-' ? 'selected' : '' }}>B-</option>
                                    <option value="AB+" {{ old('blood_type') == 'AB+' ? 'selected' : '' }}>AB+</option>
                                    <option value="AB-" {{ old('blood_type') == 'AB-' ? 'selected' : '' }}>AB-</option>
                                    <option value="O+" {{ old('blood_type') == 'O+' ? 'selected' : '' }}>O+</option>
                                    <option value="O-" {{ old('blood_type') == 'O-' ? 'selected' : '' }}>O-</option>
                                </select>
                                @error('blood_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- الرقم الوطني -->
                            <div class="col-md-6 mb-3">
                                <label for="national_id" class="form-label">الرقم الوطني *</label>
                                <input type="text" 
                                       class="form-control @error('national_id') is-invalid @enderror" 
                                       id="national_id" 
                                       name="national_id" 
                                       value="{{ old('national_id') }}"
                                       required
                                       placeholder="الرقم الوطني">
                                @error('national_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- جهة الضمان / التأمين -->
                            <div class="col-md-6 mb-3">
                                <label for="insurance_type" class="form-label fw-bold">جهة الضمان / التأمين *</label>
                                <select class="form-select @error('insurance_type') is-invalid @enderror" id="insurance_type" name="insurance_type" required>
                                    <option value="none" {{ old('insurance_type', 'none') == 'none' ? 'selected' : '' }}>بدون ضمان (دفع نقدي كامل)</option>
                                    <option value="moi" {{ old('insurance_type') == 'moi' ? 'selected' : '' }}>ضمان قوى الأمن الداخلي (وزارة الداخلية)</option>
                                    <option value="hi" {{ old('insurance_type') == 'hi' ? 'selected' : '' }}>هيئة الضمان الصحي الوطني</option>
                                </select>
                                @error('insurance_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- تفاصيل الضمان الصحي في حال تم اختياره -->
                        <div class="row p-3 mb-3 bg-light rounded border" id="insurance_details_box" style="display: none;">
                            <div class="col-12 mb-2">
                                <span class="badge bg-primary"><i class="fas fa-shield-alt me-1"></i> بيانات بطاقة / دفتر الضمان</span>
                            </div>
                            <div class="col-md-12 mb-2">
                                <label for="insurance_card_no" class="form-label fw-bold">رقم بطاقة / دفتر الضمان *</label>
                                <input type="text" 
                                       class="form-control @error('insurance_card_no') is-invalid @enderror" 
                                       id="insurance_card_no" 
                                       name="insurance_card_no" 
                                       value="{{ old('insurance_card_no', old('insurance_booklet_number')) }}"
                                       placeholder="أدخل رقم الهوية أو الدفتر التأميني">
                                <small class="text-muted d-block mt-1">ملاحظة: نسبة التحمل (Co-payment) والخصم يتم تحديدها والتحكم بها بالخيارات الخماسية مباشرة بواسطة الكاشير عند الدفع.</small>
                                @error('insurance_card_no')
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
                                        <option value="{{ $countryItem->id }}" {{ old('country') == $countryItem->id ? 'selected' : ($countryItem->id == $iraq_id ? 'selected' : '') }}>{{ $countryItem->name }}</option>
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
                                        <option value="{{ $governorate->name }}" {{ old('governorate') == $governorate->name ? 'selected' : '' }}>{{ $governorate->name }}</option>
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
                                       value="{{ old('district') }}"
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
                                       value="{{ old('neighborhood') }}"
                                       placeholder="الناحية">
                                @error('neighborhood')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr>

                    
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>حفظ المريض
                            </button>
                            <a href="{{ route('patients.index') }}" class="btn btn-secondary">
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
                    
                    <hr>
                    <h6>إرشادات:</h6>
                    <ul class="small text-muted">
                        <li>جميع الحقول المميزة بـ * إجبارية</li>
                        <li>يجب إدخال اسم الأم، الحالة الاجتماعية، حالة الضمان، والرقم الوطني</li>
                        <li>البريد الإلكتروني ورقم الطوارئ غير مطلوبين لكن يُستحسن إدخالهما</li>
                        <li>رقم الطوارئ مهم للحالات الطارئة</li>
                        <li>الايميل مهم للتواصل مع المريض</li>
                        <li>تاريخ الميلاد مهم لتحديد العمر</li>
                        <li>يرجى التأكد من صحة المعلومات قبل الحفظ</li>

                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // حساب العمر تلقائياً
    const dateOfBirthInput = document.getElementById('date_of_birth');
    
    dateOfBirthInput.addEventListener('change', function() {
        const birthDate = new Date(this.value);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        
        // التحقق من عيد الميلاد لهذا العام
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        // يمكن إظهار العمر في مكان ما إذا أردت
        console.log('العمر:', age, 'سنة');
    });

    // التحكم في إظهار حقول الضمان الصحي
    const insuranceTypeSelect = document.getElementById('insurance_type');
    const insuranceDetailsBox = document.getElementById('insurance_details_box');
    const insuranceCardNoInput = document.getElementById('insurance_card_no');

    function toggleInsuranceDetails() {
        if (insuranceTypeSelect.value !== 'none') {
            insuranceDetailsBox.style.display = 'flex';
            insuranceCardNoInput.setAttribute('required', 'required');
        } else {
            insuranceDetailsBox.style.display = 'none';
            insuranceCardNoInput.removeAttribute('required');
        }
    }

    insuranceTypeSelect.addEventListener('change', toggleInsuranceDetails);
    toggleInsuranceDetails();
});
</script>
@endsection
@endsection