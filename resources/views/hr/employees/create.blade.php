@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- الترويسة -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-user-plus me-2"></i>تسجيل موظف جديد
            </h2>
            <p class="text-muted small mb-0">إدخال البيانات الشخصية والوظيفية والتراخيص المهنية والمستمسكات الرسمية</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('hr.settings.index') }}" class="btn btn-outline-info btn-sm">
                <i class="fas fa-sliders-h me-1"></i> ضبط الحقول والخيارات
            </a>
            <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <h6 class="alert-heading fw-bold mb-1"><i class="fas fa-exclamation-circle me-1"></i> يرجى تصحيح الأخطاء التالية:</h6>
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('hr.employees.store') }}" method="POST" enctype="multipart/form-data" id="employeeForm">
        @csrf

        <div class="row g-4">
            <!-- العمود الأيمن: البيانات الأساسية والوظيفية والمستمسكات -->
            <div class="col-lg-8">
                <!-- 1. البيانات الشخصية -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="card-title fw-bold text-dark mb-0">
                            <i class="fas fa-user text-primary me-2"></i>1. البيانات الشخصية والأساسية
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">الاسم الكامل (الرباعي واللقب) <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" value="{{ old('full_name') }}" required placeholder="مثال: د. علي كريم عبد المنعم الكرخي">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">
                                    الرقم الوطني / البطاقة الموحدة
                                    {!! ($reqMap['national_id'] ?? false) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <input type="text" name="national_id" class="form-control" value="{{ old('national_id') }}" {{ ($reqMap['national_id'] ?? false) ? 'required' : '' }} placeholder="رقم الهوية الوطنية">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">الجنس <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select" required>
                                    <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>ذكر</option>
                                    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>أنثى</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">
                                    تاريخ الميلاد
                                    {!! ($reqMap['date_of_birth'] ?? false) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}" {{ ($reqMap['date_of_birth'] ?? false) ? 'required' : '' }}>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">
                                    فصيلة الدم
                                    {!! ($reqMap['blood_group'] ?? false) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <select name="blood_group" class="form-select" {{ ($reqMap['blood_group'] ?? false) ? 'required' : '' }}>
                                    <option value="">غير محدد</option>
                                    @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                                        <option value="{{ $bg }}" {{ old('blood_group') == $bg ? 'selected' : '' }}>{{ $bg }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">
                                    رقم الهاتف الأساسي
                                    {!! ($reqMap['phone'] ?? true) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" {{ ($reqMap['phone'] ?? true) ? 'required' : '' }} placeholder="07xxxxxxxxx">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">
                                    هاتف الطوارئ (قريب)
                                    {!! ($reqMap['emergency_phone'] ?? false) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <input type="text" name="emergency_phone" class="form-control" value="{{ old('emergency_phone') }}" {{ ($reqMap['emergency_phone'] ?? false) ? 'required' : '' }} placeholder="07xxxxxxxxx">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">
                                    البريد الإلكتروني
                                    {!! ($reqMap['email'] ?? false) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" {{ ($reqMap['email'] ?? false) ? 'required' : '' }} placeholder="employee@hospital.com">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">
                                    عنوان السكن
                                    {!! ($reqMap['address'] ?? false) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <input type="text" name="address" class="form-control" value="{{ old('address') }}" {{ ($reqMap['address'] ?? false) ? 'required' : '' }} placeholder="المحافظة - المنطقة - أقرب نقطة دالة">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. البيانات الوظيفية والتعاقد -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="card-title fw-bold text-dark mb-0">
                            <i class="fas fa-briefcase text-primary me-2"></i>2. البيانات الوظيفية والتعاقد
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">نوع وتصنيف الكادر <span class="text-danger">*</span></label>
                                <select name="staff_type" id="staff_type_select" class="form-select border-primary" required>
                                    <option value="medical" {{ old('staff_type', 'medical') == 'medical' ? 'selected' : '' }}>🩺 كادر طبي (أطباء وجراحين)</option>
                                    <option value="nursing" {{ old('staff_type') == 'nursing' ? 'selected' : '' }}>💉 كادر تمريضي</option>
                                    <option value="technical" {{ old('staff_type') == 'technical' ? 'selected' : '' }}>🔬 كادر فني (مختبر / أشعة / صيدلة)</option>
                                    <option value="administrative" {{ old('staff_type') == 'administrative' ? 'selected' : '' }}>💼 كادر إداري ومالي</option>
                                    <option value="service" {{ old('staff_type') == 'service' ? 'selected' : '' }}>🛠️ كادر خدمات وصيانة</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">
                                    المسمى الوظيفي
                                    {!! ($reqMap['job_title'] ?? true) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <input type="text" name="job_title" class="form-control" value="{{ old('job_title') }}" {{ ($reqMap['job_title'] ?? true) ? 'required' : '' }} placeholder="طبيب اختصاص / ممرض / محاسب">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">
                                    القسم التابع له
                                    {!! ($reqMap['department_id'] ?? true) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <select name="department_id" class="form-select" {{ ($reqMap['department_id'] ?? true) ? 'required' : '' }}>
                                    <option value="">اختر القسم...</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">
                                    نوع التعيين / التعاقد
                                    {!! ($reqMap['employment_type'] ?? true) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <select name="employment_type" class="form-select" {{ ($reqMap['employment_type'] ?? true) ? 'required' : '' }}>
                                    @foreach($employmentTypes as $empType)
                                        <option value="{{ $empType->code ?? $empType->name }}" {{ old('employment_type') == ($empType->code ?? $empType->name) ? 'selected' : '' }}>
                                            {{ $empType->name }}
                                        </option>
                                    @endforeach
                                    @if($employmentTypes->isEmpty())
                                        <option value="full_time">دوام كامل (ملاك دائم)</option>
                                        <option value="contract">عقد محدد المدة</option>
                                        <option value="part_time">دوام جزئي</option>
                                        <option value="daily_shift">أجر يومي / خفارات</option>
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">
                                    تاريخ المباشرة بالعمل
                                    {!! ($reqMap['hire_date'] ?? true) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <input type="date" name="hire_date" class="form-control" value="{{ old('hire_date', date('Y-m-d')) }}" {{ ($reqMap['hire_date'] ?? true) ? 'required' : '' }}>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">
                                    تاريخ انتهاء العقد
                                    {!! ($reqMap['contract_end_date'] ?? false) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <input type="date" name="contract_end_date" class="form-control" value="{{ old('contract_end_date') }}" {{ ($reqMap['contract_end_date'] ?? false) ? 'required' : '' }}>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">
                                    الراتب الأساسي (د.ع)
                                    {!! ($reqMap['basic_salary'] ?? false) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <div class="input-group">
                                    <input type="number" step="1000" name="basic_salary" class="form-control" value="{{ old('basic_salary', 0) }}" {{ ($reqMap['basic_salary'] ?? false) ? 'required' : '' }} placeholder="0">
                                    <span class="input-group-text bg-light">د.ع</span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">الحالة الوظيفية <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>على رأس العمل</option>
                                    <option value="on_leave" {{ old('status') == 'on_leave' ? 'selected' : '' }}>في إجازة</option>
                                    <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>موقوف مؤقتاً</option>
                                    <option value="resigned" {{ old('status') == 'resigned' ? 'selected' : '' }}>مستقيل</option>
                                    <option value="terminated" {{ old('status') == 'terminated' ? 'selected' : '' }}>منهي خدماته</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">ربط بحساب مستخدم (اختياري)</label>
                                <select name="user_id" class="form-select">
                                    <option value="">بدون حساب دخول للنظام</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. البيانات المهنية والتراخيص الطبية -->
                <div class="card border-0 shadow-sm rounded-3 mb-4" id="medical_credentials_card">
                    <div class="card-header bg-info bg-opacity-10 py-3 border-bottom border-info border-opacity-25">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title fw-bold text-info mb-0">
                                <i class="fas fa-stethoscope me-2"></i>3. التراخيص المهنية والبيانات الطبية
                            </h6>
                            <span class="badge bg-info text-dark">خاص بالكوادر الطبية والفنية</span>
                        </div>
                    </div>
                    <div class="card-body p-4 bg-light bg-opacity-50">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">
                                    رقم إجازة / ترخيص ممارسة المهنة
                                    {!! ($reqMap['medical_license_number'] ?? false) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <input type="text" name="medical_license_number" class="form-control" value="{{ old('medical_license_number') }}" {{ ($reqMap['medical_license_number'] ?? false) ? 'required' : '' }} placeholder="رقم ترخيص وزارة الصحة">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">
                                    تاريخ انتهاء ترخيص الممارسة
                                    {!! ($reqMap['license_expiry_date'] ?? false) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <input type="date" name="license_expiry_date" class="form-control" value="{{ old('license_expiry_date') }}" {{ ($reqMap['license_expiry_date'] ?? false) ? 'required' : '' }}>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">
                                    رقم هوية النقابة
                                    {!! ($reqMap['syndicate_card_number'] ?? false) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <input type="text" name="syndicate_card_number" class="form-control" value="{{ old('syndicate_card_number') }}" {{ ($reqMap['syndicate_card_number'] ?? false) ? 'required' : '' }} placeholder="رقم هوية النقابة">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">
                                    المؤهل العلمي / الشهادة
                                    {!! ($reqMap['qualification'] ?? false) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <input type="text" name="qualification" class="form-control" value="{{ old('qualification') }}" {{ ($reqMap['qualification'] ?? false) ? 'required' : '' }} placeholder="مثال: بورد عربي / بكالوريوس طب">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">
                                    التخصص الدقيق
                                    {!! ($reqMap['sub_specialty'] ?? false) ? '<span class="text-danger">*</span>' : '' !!}
                                </label>
                                <input type="text" name="sub_specialty" class="form-control" value="{{ old('sub_specialty') }}" {{ ($reqMap['sub_specialty'] ?? false) ? 'required' : '' }} placeholder="مثال: جراحة ناظورية / قسطرة">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. مستمسكات ووثائق الموظف (Dynamic Document Rows) -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="card-title fw-bold text-dark mb-0">
                            <i class="fas fa-folder-open text-primary me-2"></i>4. مستمسكات ووثائق الموظف الرسمية (PDF / صور)
                            {!! ($reqMap['documents'] ?? false) ? '<span class="text-danger">* (مطلوب مستمسك واحد على الأقل)</span>' : '<span class="text-muted small fw-normal">(اختياري)</span>' !!}
                        </h6>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addDocRowBtn">
                            <i class="fas fa-plus me-1"></i> إضافة مستمسك إضافي
                        </button>
                    </div>
                    <div class="card-body p-3">
                        <div class="alert alert-light border small text-muted mb-3 py-2">
                            <i class="fas fa-info-circle me-1 text-primary"></i> يتم تسمية وتخزين كل ملف تلقائياً بوراثة: <strong>[الرمز الوظيفي]_[نوع المستمسك]_[التاريخ].pdf</strong>
                        </div>

                        <!-- حاوية أسطر المستمسكات -->
                        <div id="documentsContainer">
                            <div class="document-row border rounded-3 p-3 mb-3 bg-light bg-opacity-25" data-index="0">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold mb-1">نوع المستمسك</label>
                                        <select name="documents[0][type]" class="form-select form-select-sm doc-type-select">
                                            @foreach($documentTypes as $dt)
                                                <option value="{{ $dt->name }}">{{ $dt->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-bold mb-1">ملف المستمسك (PDF أو صورة)</label>
                                        <input type="file" name="documents[0][file]" class="form-control form-select-sm doc-file-input" accept=".pdf,.jpg,.jpeg,.png,.webp">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted mb-1">ملاحظة</label>
                                        <input type="text" name="documents[0][notes]" class="form-control form-control-sm" placeholder="اختياري">
                                    </div>
                                    <div class="col-md-1 text-center pt-3">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-doc-btn" style="display: none;" title="حذف هذا السطر">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. الملاحظات -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="card-title fw-bold text-dark mb-0">
                            <i class="fas fa-sticky-note text-secondary me-2"></i>5. ملاحظات إضافية
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <textarea name="notes" class="form-control" rows="3" placeholder="أي ملاحظات إدارية، تفاصيل العقد، أو مهارات إضافية...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- العمود الأيسر: الباجة والصورة والإجراءات -->
            <div class="col-lg-4">
                <!-- بطاقة كود الباجة -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 bg-primary text-white">
                    <div class="card-body p-4 text-center">
                        <i class="fas fa-id-badge fa-3x mb-2 opacity-75"></i>
                        <h5 class="fw-bold mb-1">الرقم الوظيفي (كود الباجة)</h5>
                        <div class="mt-3">
                            <input type="text" name="employee_code" id="employeeCodeInput" class="form-control text-center font-monospace fw-bold fs-5 bg-white text-primary border-0" value="{{ old('employee_code', $nextCode) }}" required>
                        </div>
                        <div class="small text-white-50 mt-2">يرثه كل مستمسك ومستند تابع لهذا الموظف</div>
                    </div>
                </div>

                <!-- بطاقة الصورة الشخصية -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="card-title fw-bold text-dark mb-0">
                            <i class="fas fa-camera text-primary me-2"></i>الصورة الشخصية
                            {!! ($reqMap['profile_photo'] ?? false) ? '<span class="text-danger">*</span>' : '' !!}
                        </h6>
                    </div>
                    <div class="card-body p-4 text-center">
                        <div class="mb-3">
                            <img id="photoPreview" src="https://via.placeholder.com/150?text=Photo" alt="صورة الموظف" class="rounded-circle shadow-sm border p-1" style="width: 130px; height: 130px; object-fit: cover;">
                        </div>
                        <label for="profilePhotoInput" class="btn btn-outline-primary btn-sm w-100 mb-2">
                            <i class="fas fa-upload me-1"></i> اختر صورة من الجهاز
                        </label>
                        <input type="file" name="profile_photo" id="profilePhotoInput" class="d-none" accept="image/*" onchange="previewImage(this)" {{ ($reqMap['profile_photo'] ?? false) ? 'required' : '' }}>
                        <div class="text-muted small">الصيغ المدعومة: JPG, PNG (الحد الأقصى 2MB)</div>
                    </div>
                </div>

                <!-- بطاقة أزرار الحفظ -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-3 d-grid gap-2">
                        <button type="submit" class="btn btn-primary py-2 fw-bold shadow-sm">
                            <i class="fas fa-save me-1"></i> حفظ وتسجيل الموظف
                        </button>
                        <a href="{{ route('hr.employees.index') }}" class="btn btn-light py-2 border">
                            <i class="fas fa-times me-1"></i> إلغاء
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- قوالب أنواع المستمسكات الجاهزة لـ JS -->
<template id="docTypeOptionsTemplate">
    @foreach($documentTypes as $dt)
        <option value="{{ $dt->name }}">{{ $dt->name }}</option>
    @endforeach
</template>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. التبديل التفاعلي للقسم الطبي
        const staffTypeSelect = document.getElementById('staff_type_select');
        const medicalCard = document.getElementById('medical_credentials_card');

        function toggleMedicalCard() {
            const val = staffTypeSelect.value;
            if (val === 'medical' || val === 'nursing' || val === 'technical') {
                medicalCard.style.display = 'block';
            } else {
                medicalCard.style.display = 'none';
            }
        }

        staffTypeSelect.addEventListener('change', toggleMedicalCard);
        toggleMedicalCard();

        // 2. إدارة أسطر المستمسكات التفاعلية (Dynamic Document Rows)
        const container = document.getElementById('documentsContainer');
        const addBtn = document.getElementById('addDocRowBtn');
        const docOptionsHtml = document.getElementById('docTypeOptionsTemplate').innerHTML;
        let rowIndex = 1;

        function attachRowListeners(row) {
            const fileInput = row.querySelector('.doc-file-input');
            const removeBtn = row.querySelector('.remove-doc-btn');

            // عند اختيار ملف، إذا كان هذا هو السطر الأخير يتم فتح سطر جديد تلقائياً
            fileInput.addEventListener('change', function () {
                if (fileInput.files.length > 0) {
                    const allRows = container.querySelectorAll('.document-row');
                    if (row === allRows[allRows.length - 1]) {
                        addNewDocRow();
                    }
                }
            });

            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    const allRows = container.querySelectorAll('.document-row');
                    if (allRows.length > 1) {
                        row.remove();
                    }
                });
            }
        }

        function addNewDocRow() {
            const newRow = document.createElement('div');
            newRow.className = 'document-row border rounded-3 p-3 mb-3 bg-light bg-opacity-25 animate__animated animate__fadeIn';
            newRow.setAttribute('data-index', rowIndex);

            newRow.innerHTML = `
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-1">نوع المستمسك</label>
                        <select name="documents[${rowIndex}][type]" class="form-select form-select-sm doc-type-select">
                            ${docOptionsHtml}
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold mb-1">ملف المستمسك (PDF أو صورة)</label>
                        <input type="file" name="documents[${rowIndex}][file]" class="form-control form-select-sm doc-file-input" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">ملاحظة</label>
                        <input type="text" name="documents[${rowIndex}][notes]" class="form-control form-control-sm" placeholder="اختياري">
                    </div>
                    <div class="col-md-1 text-center pt-3">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-doc-btn" title="حذف هذا السطر">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;

            container.appendChild(newRow);
            attachRowListeners(newRow);
            rowIndex++;
        }

        // إرفاق المستمعات للسطر الأول
        const firstRow = container.querySelector('.document-row');
        if (firstRow) {
            attachRowListeners(firstRow);
        }

        addBtn.addEventListener('click', function () {
            addNewDocRow();
        });
    });

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('photoPreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection
