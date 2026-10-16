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
                    <form method="POST" action="{{ route('doctors.update', $doctor) }}" enctype="multipart/form-data">
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
                                     <option value="resident" {{ old('type', $doctor->type) == 'resident' ? 'selected' : '' }}>مقيم</option>
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
                            <label for="consultation_fee" class="col-md-3 col-form-label text-md-end fw-bold">رسوم الكشف كاش (دينار) <span class="text-danger">*</span></label>
                            <div class="col-md-8">
                                <input id="consultation_fee" type="number" step="500" class="form-control @error('consultation_fee') is-invalid @enderror"
                                       name="consultation_fee" value="{{ old('consultation_fee', (int)$doctor->consultation_fee) }}" required min="0">
                                <div class="form-text small">سعر الكشفية العادية للمراجع بدون ضمان</div>
                                @error('consultation_fee')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- قسم تسعير الضمان الصحي وضمان وزارة الداخلية -->
                        <div class="row mb-3">
                            <label class="col-md-3 col-form-label text-md-end fw-bold text-primary">تسعير الضمان</label>
                            <div class="col-md-8">
                                <div class="card border-primary border-opacity-25 bg-light bg-opacity-50 rounded-3">
                                    <div class="card-header bg-primary bg-opacity-10 py-2">
                                        <h6 class="mb-0 text-primary fw-bold small">
                                            <i class="fas fa-shield-alt me-1"></i> تسعير وتغطية كشفية الطبيب لجهات الضمان
                                        </h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-3">
                                            {{-- ضمان الداخلية --}}
                                            <div class="col-md-6 border-start">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <label class="form-label fw-bold small mb-0"><i class="fas fa-id-badge text-primary me-1"></i>ضمان الداخلية (MOI)</label>
                                                    <div class="form-check form-switch mb-0">
                                                        <input class="form-check-input" type="checkbox" name="is_moi_active" value="1" id="editDoctorIsMoiActive" {{ old('is_moi_active', $doctor->is_moi_active ?? true) ? 'checked' : '' }}>
                                                        <label class="form-check-label small" for="editDoctorIsMoiActive">مشمول</label>
                                                    </div>
                                                </div>
                                                <input type="number" step="500" name="moi_price" class="form-control form-control-sm rounded-2" value="{{ old('moi_price', $doctor->moi_price ? (int)$doctor->moi_price : '') }}" placeholder="سعر كشفية الداخلية (اتركه فارغاً لاعتماد الكاش)">
                                            </div>
                                            {{-- هيئة الضمان --}}
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <label class="form-label fw-bold small mb-0"><i class="fas fa-heartbeat text-success me-1"></i>هيئة الضمان (HI)</label>
                                                    <div class="form-check form-switch mb-0">
                                                        <input class="form-check-input" type="checkbox" name="is_hi_active" value="1" id="editDoctorIsHiActive" {{ old('is_hi_active', $doctor->is_hi_active ?? true) ? 'checked' : '' }}>
                                                        <label class="form-check-label small" for="editDoctorIsHiActive">مشمول</label>
                                                    </div>
                                                </div>
                                                <input type="number" step="500" name="hi_price" class="form-control form-control-sm rounded-2" value="{{ old('hi_price', $doctor->hi_price ? (int)$doctor->hi_price : '') }}" placeholder="سعر كشفية هيئة الضمان (اتركه فارغاً لاعتماد الكاش)">
                                            </div>
                                        </div>
                                    </div>
                                </div>
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

                        <!-- قسم التوقيع اليدوي الرسمي للطبيب -->
                        <hr class="my-4">
                        <div class="card border-success-subtle shadow-sm mb-4">
                            <div class="card-header bg-success-subtle text-success py-2 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold">
                                    <i class="fas fa-signature me-2"></i>التوقيع اليدوي الرسمي للطبيب (Official Medical Signature)
                                </h6>
                                <span class="badge bg-success">يظهر في تقارير الطوارئ والتقارير الطبية</span>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-light border small text-muted mb-3">
                                    <i class="fas fa-magic text-success me-1"></i>
                                    <strong>ميزة التفريغ التلقائي:</strong> يمكنك تصوير التوقيع المكتوب بقلم حبر على ورقة بيضاء بكاميرا الهاتف ورفعه مباشرة (JPG أو PNG). سيقوم النظام <strong>تلقائياً بعزل الخلفية البيضاء والظلال</strong> وقص الفراغات للحصول على توقيع شفاف ونقي بدقة 100%.
                                </div>

                                <div class="row align-items-start">
                                    <!-- عمود التوقيع الحالي إن وجد -->
                                    <div class="col-md-5 mb-3 text-center">
                                        <label class="form-label fw-bold d-block text-secondary small">التوقيع المعتمد حالياً</label>
                                        <div class="signature-display-box mx-auto position-relative" style="min-height: 120px; max-width: 280px; border: 2px dashed #0f766e; border-radius: 8px; background: repeating-conic-gradient(#f1f5f9 0% 25%, #ffffff 0% 50%) 50% / 16px 16px; display: flex; align-items: center; justify-content: center; padding: 10px;">
                                            @php
                                                $currentSigSrc = $doctor->signature_data_uri ?: ($doctor->signature && file_exists(public_path($doctor->signature)) ? route('doctors.signature', $doctor) : null);
                                            @endphp
                                            @if($currentSigSrc)
                                                <img id="currentSignatureImg" src="{{ $currentSigSrc }}" alt="توقيع الطبيب" style="max-height: 100px; max-width: 100%; object-fit: contain;">
                                            @else
                                                <div id="noSignaturePlaceholder" class="text-muted small">
                                                    <i class="fas fa-file-signature fa-2x mb-1 d-block opacity-50"></i>
                                                    لا يوجد توقيع محفوظ للطبيب حالياً
                                                </div>
                                            @endif
                                        </div>
                                        @if($currentSigSrc)
                                            <div class="form-check mt-2 d-inline-block text-danger small">
                                                <input class="form-check-input" type="checkbox" name="remove_signature" id="remove_signature" value="1">
                                                <label class="form-check-label" for="remove_signature">
                                                    <i class="fas fa-trash-alt me-1"></i> حذف التوقيع الحالي
                                                </label>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- عمود رفع وتفريغ التوقيع الجديد -->
                                    <div class="col-md-7">
                                        <label for="signatureFileInput" class="form-label fw-bold small text-dark">
                                            <i class="fas fa-camera text-primary me-1"></i> اختر صورة التوقيع (JPG / PNG من الموبايل أو الجهاز)
                                        </label>
                                        <input type="file" class="form-control mb-2" id="signatureFileInput" name="signature_file" accept="image/png,image/jpeg,image/jpg,image/webp">
                                        <input type="hidden" id="signatureDataInput" name="signature_data">

                                        <!-- حاوية المعاينة اللحظية للتفريغ الشفاف -->
                                        <div id="signaturePreviewContainer" class="d-none mt-3 p-3 border rounded-3 bg-light">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="small fw-bold text-success">
                                                    <i class="fas fa-check-circle me-1"></i> معاينة التفريغ الفوري (خلفية شفافة):
                                                </span>
                                                <span class="badge bg-primary-subtle text-primary border small" id="imageDimBadge"></span>
                                            </div>

                                            <div class="text-center p-2 rounded mb-2" style="background: repeating-conic-gradient(#e2e8f0 0% 25%, #ffffff 0% 50%) 50% / 14px 14px; border: 1px solid #cbd5e1;">
                                                <canvas id="signatureCanvas" style="max-height: 120px; max-width: 100%; height: auto; object-fit: contain;"></canvas>
                                            </div>

                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between small text-muted mb-1">
                                                    <span>حساسية عزل بياض الورقة:</span>
                                                    <span id="thresholdVal" class="fw-bold text-dark">200</span>
                                                </div>
                                                <input type="range" class="form-range" id="thresholdRange" name="signature_threshold" min="120" max="245" value="200" step="5">
                                                <div class="d-flex justify-content-between text-muted" style="font-size: 10px;">
                                                    <span>حبر أغمق / إضاءة ضعيفة</span>
                                                    <span>ورقة بيضاء نقية</span>
                                                </div>
                                            </div>

                                            <div class="d-flex gap-2 mt-3">
                                                <button type="button" class="btn btn-sm btn-success" id="ajaxSaveSignatureBtn">
                                                    <i class="fas fa-bolt me-1"></i> حفظ التوقيع فوراً الآن
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="cancelSignatureBtn">
                                                    <i class="fas fa-undo me-1"></i> تراجع
                                                </button>
                                            </div>
                                            <div id="signatureAlertBox" class="alert small mt-2 d-none py-1 px-2 text-center"></div>
                                        </div>
                                    </div>
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
// تحسين تجربة المستخدم ومعالجة التوقيع
document.addEventListener('DOMContentLoaded', function() {
    // تنسيق رقم الهاتف
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('964')) {
                value = '+' + value;
            } else if (value.startsWith('07')) {
                value = '+964' + value.substring(1);
            }
            e.target.value = value;
        });
    }

    // ==========================================
    // محرك معالجة وتفريغ التوقيع التلقائي (Signature Engine)
    // ==========================================
    const sigFileInput = document.getElementById('signatureFileInput');
    const sigDataInput = document.getElementById('signatureDataInput');
    const sigPreviewBox = document.getElementById('signaturePreviewContainer');
    const sigCanvas = document.getElementById('signatureCanvas');
    const thresholdRange = document.getElementById('thresholdRange');
    const thresholdVal = document.getElementById('thresholdVal');
    const ajaxSaveBtn = document.getElementById('ajaxSaveSignatureBtn');
    const cancelSigBtn = document.getElementById('cancelSignatureBtn');
    const sigAlert = document.getElementById('signatureAlertBox');
    const dimBadge = document.getElementById('imageDimBadge');
    const currentSigImg = document.getElementById('currentSignatureImg');
    const noSigPlaceholder = document.getElementById('noSignaturePlaceholder');

    let loadedImage = null;

    if (sigFileInput && sigCanvas) {
        sigFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(event) {
                const img = new Image();
                img.onload = function() {
                    loadedImage = img;
                    if (dimBadge) dimBadge.textContent = img.width + ' × ' + img.height + ' px';
                    if (sigPreviewBox) sigPreviewBox.classList.remove('d-none');
                    processSignatureImage();
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        });

        if (thresholdRange) {
            thresholdRange.addEventListener('input', function() {
                if (thresholdVal) thresholdVal.textContent = this.value;
                if (loadedImage) {
                    processSignatureImage();
                }
            });
        }

        if (cancelSigBtn) {
            cancelSigBtn.addEventListener('click', function() {
                sigFileInput.value = '';
                sigDataInput.value = '';
                loadedImage = null;
                if (sigPreviewBox) sigPreviewBox.classList.add('d-none');
                if (sigAlert) sigAlert.classList.add('d-none');
            });
        }

        // تفريغ الصورة عبر Canvas
        function processSignatureImage() {
            if (!loadedImage) return;

            const threshold = parseInt(thresholdRange ? thresholdRange.value : 200, 10);
            const offCanvas = document.createElement('canvas');
            const offCtx = offCanvas.getContext('2d');

            // ضبط أبعاد مناسبة للمعالجة (بحد أقصى 1200 بكسل لمنع الثقل)
            let w = loadedImage.width;
            let h = loadedImage.height;
            const maxDim = 1200;
            if (w > maxDim || h > maxDim) {
                if (w > h) {
                    h = Math.round((h * maxDim) / w);
                    w = maxDim;
                } else {
                    w = Math.round((w * maxDim) / h);
                    h = maxDim;
                }
            }

            offCanvas.width = w;
            offCanvas.height = h;
            offCtx.drawImage(loadedImage, 0, 0, w, h);

            const imgData = offCtx.getImageData(0, 0, w, h);
            const data = imgData.data;

            let minX = w, maxX = 0, minY = h, maxY = 0;
            let hasVisible = false;

            for (let i = 0; i < data.length; i += 4) {
                const r = data[i];
                const g = data[i + 1];
                const b = data[i + 2];
                const lum = 0.299 * r + 0.587 * g + 0.114 * b;

                // إذا كان البكسل أبيض أو فاتحاً (ورقة)
                if (lum >= threshold) {
                    data[i + 3] = 0; // شفافية كاملة
                } else {
                    // حواف ناعمة
                    const featherStart = Math.max(50, threshold - 45);
                    if (lum > featherStart) {
                        const ratio = (lum - featherStart) / (threshold - featherStart);
                        data[i + 3] = Math.round((1 - ratio) * 255);
                    } else {
                        data[i + 3] = 255; // معتم تماماً
                    }

                    // تعزيز خط الحبر ليكون حاداً وواضحاً
                    data[i] = Math.max(0, Math.round(r * 0.85));
                    data[i + 1] = Math.max(0, Math.round(g * 0.85));
                    data[i + 2] = Math.max(0, Math.round(b * 0.95));

                    if (data[i + 3] > 40) {
                        hasVisible = true;
                        const pixelIdx = i / 4;
                        const px = pixelIdx % w;
                        const py = Math.floor(pixelIdx / w);
                        if (px < minX) minX = px;
                        if (px > maxX) maxX = px;
                        if (py < minY) minY = py;
                        if (py > maxY) maxY = py;
                    }
                }
            }

            offCtx.putImageData(imgData, 0, 0);

            // قص الحواف الزائدة تلقائياً (Auto-Crop)
            if (hasVisible && maxX > minX && maxY > minY) {
                const pad = 12;
                const cropX = Math.max(0, minX - pad);
                const cropY = Math.max(0, minY - pad);
                const cropW = Math.min(w - cropX, (maxX - minX) + (pad * 2));
                const cropH = Math.min(h - cropY, (maxY - minY) + (pad * 2));

                sigCanvas.width = cropW;
                sigCanvas.height = cropH;
                const ctx = sigCanvas.getContext('2d');
                ctx.clearRect(0, 0, cropW, cropH);
                ctx.drawImage(offCanvas, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH);
            } else {
                sigCanvas.width = w;
                sigCanvas.height = h;
                const ctx = sigCanvas.getContext('2d');
                ctx.clearRect(0, 0, w, h);
                ctx.drawImage(offCanvas, 0, 0);
            }

            // توليد الـ Data URL الشفاف وتعيينه في الحقل المخفي
            const transparentDataUrl = sigCanvas.toDataURL('image/png');
            sigDataInput.value = transparentDataUrl;
        }

        // حفظ التوقيع فوراً عبر AJAX
        if (ajaxSaveBtn) {
            ajaxSaveBtn.addEventListener('click', function() {
                const dataUrl = sigDataInput.value;
                if (!dataUrl) {
                    alert('يرجى اختيار صورة التوقيع أولاً');
                    return;
                }

                ajaxSaveBtn.disabled = true;
                ajaxSaveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري حفظ التوقيع...';
                if (sigAlert) {
                    sigAlert.className = 'alert alert-info small mt-2 py-1 px-2 text-center';
                    sigAlert.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري معالجة وتفريغ التوقيع في السيرفر...';
                    sigAlert.classList.remove('d-none');
                }

                const formData = new FormData();
                formData.append('signature_data', dataUrl);
                formData.append('signature_threshold', thresholdRange ? thresholdRange.value : 200);
                formData.append('_token', '{{ csrf_token() }}');

                fetch("{{ route('doctors.upload-signature', $doctor) }}", {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    ajaxSaveBtn.disabled = false;
                    ajaxSaveBtn.innerHTML = '<i class="fas fa-bolt me-1"></i> حفظ التوقيع فوراً الآن';

                    if (data.success) {
                        if (sigAlert) {
                            sigAlert.className = 'alert alert-success small mt-2 py-1 px-2 text-center';
                            sigAlert.innerHTML = '<i class="fas fa-check-circle me-1"></i> ' + data.message;
                        }
                        // تحديث المعاينة الحالية فوراً
                        if (currentSigImg) {
                            currentSigImg.src = data.signature_url;
                            currentSigImg.style.display = 'block';
                        } else if (noSigPlaceholder) {
                            noSigPlaceholder.outerHTML = '<img id="currentSignatureImg" src="' + data.signature_url + '" alt="توقيع الطبيب" style="max-height: 100px; max-width: 100%; object-fit: contain;">';
                        }
                    } else {
                        if (sigAlert) {
                            sigAlert.className = 'alert alert-danger small mt-2 py-1 px-2 text-center';
                            sigAlert.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> ' + (data.message || 'حدث خطأ أثناء الحفظ');
                        }
                    }
                })
                .catch(err => {
                    ajaxSaveBtn.disabled = false;
                    ajaxSaveBtn.innerHTML = '<i class="fas fa-bolt me-1"></i> حفظ التوقيع فوراً الآن';
                    if (sigAlert) {
                        sigAlert.className = 'alert alert-danger small mt-2 py-1 px-2 text-center';
                        sigAlert.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> تعذر الاتصال بالسيرفر لحفظ التوقيع';
                    }
                });
            });
        }
    }
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