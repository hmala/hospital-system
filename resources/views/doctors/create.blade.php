<!-- resources/views/doctors/create.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 px-lg-4" dir="rtl">
    <!-- Header Breadcrumb & Actions -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small text-muted">
                    <li class="breadcrumb-item"><a href="{{ route('doctors.index') }}" class="text-decoration-none text-muted">الكادر الطبي</a></li>
                    <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">تسجيل طبيب جديد</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">
                <i class="fas fa-user-md text-primary me-2"></i>إضافة طبيب جديد للكادر الطبي
            </h3>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="{{ route('doctors.index') }}" class="btn btn-outline-secondary px-3 shadow-sm rounded-pill">
                <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>
    </div>

    <!-- Quick Info Banner -->
    <div class="alert alert-primary bg-primary-subtle border-0 rounded-4 shadow-sm py-2 px-3 mb-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <span class="badge bg-primary rounded-circle p-2 me-2">
                <i class="fas fa-key text-white"></i>
            </span>
            <div class="small text-dark">
                <strong>حساب النظام الموحد:</strong> سيتم إنشاء حساب دخول تلقائي للطبيب بكلمة مرور افتراضية:
                <code class="bg-white px-2 py-1 rounded text-primary fw-bold mx-1 border">password</code>
                <span class="text-muted">(يمكن للطبيب أو الإدارة تغييرها لاحقاً من شاشة الملف الشخصي).</span>
            </div>
        </div>
        <span class="badge bg-white text-primary border shadow-xs d-none d-md-inline-block">نظام المستشفى 2026</span>
    </div>

    <form method="POST" action="{{ route('doctors.store') }}" enctype="multipart/form-data" id="createDoctorForm">
        @csrf

        <div class="row g-4">
            <!-- اليمين: استمارة بيانات الطبيب (7 أعمدة) -->
            <div class="col-lg-7">
                <!-- 1. البيانات الشخصية وبيانات الحساب -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center">
                        <span class="step-num me-2">1</span>
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-id-card text-primary me-2"></i>المعلومات الشخصية وبيانات الاتصال
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold text-secondary small">
                                    الاسم الكامل للطبيب <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-user-md"></i></span>
                                    <input type="text" class="form-control border-start-0 ps-0 @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name') }}" placeholder="د. الاسم الثلاثي" required autofocus>
                                </div>
                                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold text-secondary small">
                                    البريد الإلكتروني المهني <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror"
                                           id="email" name="email" value="{{ old('email') }}" placeholder="doctor@hospital.iq" required>
                                </div>
                                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold text-secondary small">
                                    رقم الهاتف / الواتساب <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-phone"></i></span>
                                    <input type="text" class="form-control border-start-0 ps-0 @error('phone') is-invalid @enderror"
                                           id="phone" name="phone" value="{{ old('phone') }}" placeholder="077XXXXXXXX أو +9647XXXXXXXX" required>
                                </div>
                                @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="qualification" class="form-label fw-semibold text-secondary small">
                                    المؤهل العلمي والشهادة
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-graduation-cap"></i></span>
                                    <input type="text" class="form-control border-start-0 ps-0"
                                           id="qualification" name="qualification" value="{{ old('qualification', 'بكالوريوس طب وجراحة عامة') }}" placeholder="مثال: بورد عربي / زمالة كلية الجراحين">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. التعيين والعيادة والتخصص -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center">
                        <span class="step-num me-2">2</span>
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-stethoscope text-info me-2"></i>العيادة والصفة المهنية
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="department_id" class="form-label fw-semibold text-secondary small">
                                    القسم الطبي / العيادة <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                                    <option value="">-- اختر العيادة --</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" data-name="{{ $department->name }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }} ({{ $department->getTypeText() }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label for="type" class="form-label fw-semibold text-secondary small">
                                    نوع / صفة الطبيب <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                    <option value="">-- اختر صفة الطبيب --</option>
                                    <option value="consultant" {{ old('type') == 'consultant' ? 'selected' : '' }}>🩺 طبيب استشاري (Consultant)</option>
                                    <option value="surgeon" {{ old('type') == 'surgeon' ? 'selected' : '' }}>🔪 جراح اختصاص (Surgeon)</option>
                                    <option value="anesthesiologist" {{ old('type') == 'anesthesiologist' ? 'selected' : '' }}>💉 طبيب تخدير (Anesthesiologist)</option>
                                    <option value="emergency" {{ old('type') == 'emergency' ? 'selected' : '' }}>🚨 طبيب طوارئ (Emergency)</option>
                                    <option value="resident" {{ old('type') == 'resident' ? 'selected' : '' }}>📋 طبيب مقيم (Resident)</option>
                                </select>
                                @error('type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label for="specialization" class="form-label fw-semibold text-secondary small">
                                    التخصص الدقيق <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('specialization') is-invalid @enderror"
                                       id="specialization" name="specialization" value="{{ old('specialization') }}" placeholder="مثال: باطنية وقلبية، جراحة عظام" required>
                                @error('specialization')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. الأجور والتسعيرة وتغطية الضمان الصحي -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="step-num me-2">3</span>
                            <h5 class="mb-0 fw-bold text-dark">
                                <i class="fas fa-hand-holding-usd text-success me-2"></i>أجر الكشفية وتغطيات الضمان المالي
                            </h5>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle small px-2">دينار عراقي (IQD)</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="consultation_fee" class="form-label fw-bold text-dark">
                                    أجر الكشفية العادية (كاش للمراجع) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-lg">
                                    <input type="number" class="form-control fw-bold text-success @error('consultation_fee') is-invalid @enderror"
                                           id="consultation_fee" name="consultation_fee" value="{{ old('consultation_fee', 50000) }}" min="0" step="1000" required>
                                    <span class="input-group-text bg-light fw-bold text-muted">د.ع</span>
                                </div>
                                <div class="form-text small text-muted">المبلغ الافتراضي الذي يسدده المريض عند شباك الكاشير.</div>
                                @error('consultation_fee')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <!-- كارت الضمان الصحي والداخلية المزدوج -->
                        <div class="p-3 rounded-4 border bg-light bg-opacity-50">
                            <h6 class="fw-bold text-primary mb-3 small d-flex align-items-center">
                                <i class="fas fa-shield-alt me-2"></i>تغطيات جهات الضمان المالي (اختياري - تُعتمد كاش تلقائياً إن تركت فارغة)
                            </h6>
                            <div class="row g-3">
                                <!-- ضمان وزارة الداخلية -->
                                <div class="col-md-6">
                                    <div class="bg-white p-3 rounded-3 border h-100 shadow-xs">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold small mb-0 text-dark">
                                                <i class="fas fa-id-badge text-primary me-1"></i> ضمان قوى الأمن والداخلية (MOI)
                                            </label>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="is_moi_active" value="1" id="is_moi_active" {{ old('is_moi_active', true) ? 'checked' : '' }}>
                                                <label class="form-check-label small text-muted" for="is_moi_active">مشمول</label>
                                            </div>
                                        </div>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="500" name="moi_price" id="moi_price" class="form-control"
                                                   value="{{ old('moi_price') }}" placeholder="سعر كشفية الداخلية">
                                            <span class="input-group-text bg-light small">د.ع</span>
                                        </div>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 4px;">اتركه فارغاً لاعتماد سعر الكاش نفسه.</div>
                                    </div>
                                </div>

                                <!-- هيئة الضمان الصحي -->
                                <div class="col-md-6">
                                    <div class="bg-white p-3 rounded-3 border h-100 shadow-xs">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold small mb-0 text-dark">
                                                <i class="fas fa-heartbeat text-success me-1"></i> هيئة الضمان الصحي (HI)
                                            </label>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="is_hi_active" value="1" id="is_hi_active" {{ old('is_hi_active', true) ? 'checked' : '' }}>
                                                <label class="form-check-label small text-muted" for="is_hi_active">مشمول</label>
                                            </div>
                                        </div>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="500" name="hi_price" id="hi_price" class="form-control"
                                                   value="{{ old('hi_price') }}" placeholder="سعر كشفية هيئة الضمان">
                                            <span class="input-group-text bg-light small">د.ع</span>
                                        </div>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 4px;">اتركه فارغاً لاعتماد سعر الكاش نفسه.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. جدول الدوام وأيام العمل -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="step-num me-2">4</span>
                            <h5 class="mb-0 fw-bold text-dark">
                                <i class="fas fa-calendar-alt text-warning me-2"></i>أوقات وأيام الدوام في العيادة
                            </h5>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" id="selectAllDaysBtn">تحديد كل الأيام</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" id="clearAllDaysBtn">تفريغ</button>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="start_time" class="form-label fw-semibold text-secondary small">
                                    <i class="fas fa-sun text-warning me-1"></i> وقت الحضور / بدء الدوام <span class="text-danger">*</span>
                                </label>
                                <input type="time" class="form-control @error('start_time') is-invalid @enderror"
                                       id="start_time" name="start_time" value="{{ old('start_time', '08:00') }}" required>
                                @error('start_time')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="end_time" class="form-label fw-semibold text-secondary small">
                                    <i class="fas fa-moon text-primary me-1"></i> وقت الانصراف / نهاية الدوام <span class="text-danger">*</span>
                                </label>
                                <input type="time" class="form-control @error('end_time') is-invalid @enderror"
                                       id="end_time" name="end_time" value="{{ old('end_time', '16:00') }}" required>
                                @error('end_time')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold text-secondary small d-block mb-2">
                                أيام تواجد الطبيب بالعيادة <span class="text-danger">*</span>
                            </label>
                            @php
                                $days = [
                                    'السبت' => 'day_sat',
                                    'الأحد' => 'day_sun',
                                    'الإثنين' => 'day_mon',
                                    'الثلاثاء' => 'day_tue',
                                    'الأربعاء' => 'day_wed',
                                    'الخميس' => 'day_thu',
                                    'الجمعة' => 'day_fri',
                                ];
                                $defaultDays = old('working_days', ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس']);
                            @endphp
                            <div class="d-flex flex-wrap gap-2 day-pill-selector">
                                @foreach($days as $arDay => $idKey)
                                    <input type="checkbox" class="btn-check day-checkbox" name="working_days[]" value="{{ $arDay }}" id="{{ $idKey }}"
                                           {{ in_array($arDay, $defaultDays) ? 'checked' : '' }} autocomplete="off">
                                    <label class="btn btn-outline-primary px-3 py-2 rounded-pill fw-semibold shadow-xs" for="{{ $idKey }}">
                                        {{ $arDay }}
                                    </label>
                                @endforeach
                            </div>
                            @error('working_days')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- اليسار: التوقيع اليدوي الرسمي + المعاينة الحية + التحكم (5 أعمدة) -->
            <div class="col-lg-5">
                <div class="sticky-top" style="top: 20px; z-index: 10;">
                    <!-- 5. التوقيع الرقمي واليدوي المعتمد (على اليسار بتنسيق راقٍ ومميز) -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden border-top border-4 border-success">
                        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                            <h5 class="mb-0 fw-bold text-dark">
                                <i class="fas fa-signature text-success me-2"></i>التوقيع اليدوي الرسمي للطبيب
                            </h5>
                            <span class="badge bg-success-subtle text-success border border-success-subtle small">عزل تلقائي 100%</span>
                        </div>
                        <div class="card-body p-4">
                            <div class="alert alert-light border rounded-3 p-2 mb-3">
                                <div class="small text-muted d-flex align-items-center">
                                    <i class="fas fa-magic text-success fa-lg me-2"></i>
                                    <span>صوّر التوقيع على ورقة بيضاء وارفع الصورة (JPG / PNG)، سيقوم النظام تلقائياً بعزل الخلفية وقص الهوامش.</span>
                                </div>
                            </div>

                            <label for="createSigFileInput" class="form-label fw-bold small text-dark mb-1">
                                <i class="fas fa-camera text-primary me-1"></i> اختر صورة التوقيع
                            </label>
                            <input type="file" class="form-control rounded-3 mb-2" id="createSigFileInput" name="signature_file" accept="image/png,image/jpeg,image/jpg,image/webp">
                            <input type="hidden" id="createSigDataInput" name="signature_data">

                            <!-- مساحة المعاينة الفورية للتفريغ الشفاف -->
                            <div id="createSigPreviewContainer" class="d-none mt-3 p-3 border rounded-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-bold text-success">
                                        <i class="fas fa-check-circle me-1"></i> التوقيع المفرغ (شفافية نقية):
                                    </span>
                                    <span class="badge bg-primary-subtle text-primary border small" id="createDimBadge"></span>
                                </div>

                                <div class="text-center p-3 rounded-3 mb-3" style="background: repeating-conic-gradient(#cbd5e1 0% 25%, #ffffff 0% 50%) 50% / 12px 12px; border: 1.5px dashed #0f766e;">
                                    <canvas id="createSigCanvas" style="max-height: 110px; max-width: 100%; height: auto; object-fit: contain;"></canvas>
                                </div>

                                <div class="mb-2">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>حساسية عزل بياض الورقة:</span>
                                        <span id="createThresholdVal" class="fw-bold text-dark">200</span>
                                    </div>
                                    <input type="range" class="form-range" id="createThresholdRange" name="signature_threshold" min="120" max="245" value="200" step="5">
                                    <div class="d-flex justify-content-between text-muted" style="font-size: 10px;">
                                        <span>حبر داكن / إضاءة ضعيفة</span>
                                        <span>ورقة ناصعة البياض</span>
                                    </div>
                                </div>

                                <div class="text-end mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-danger py-1" id="createCancelSigBtn">
                                        <i class="fas fa-trash-alt me-1"></i> إلغاء التوقيع
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- بطاقة هوية الطبيب الحية المباشرة (Live Identity Card) -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                        <div class="card-header bg-gradient text-white py-2 px-3 text-center" style="background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);">
                            <h6 class="mb-0 fw-bold small"><i class="fas fa-id-card-alt me-1"></i> بطاقة هوية الطبيب (معاينة حية)</h6>
                        </div>
                        <div class="card-body text-center p-3">
                            <div class="position-relative d-inline-block mb-2">
                                <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center shadow-xs" style="width: 70px; height: 70px; font-size: 28px; color: #0f766e; margin: 0 auto;">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-white rounded-circle" title="حساب نشط">
                                    <span class="visually-hidden">Active</span>
                                </span>
                            </div>

                            <h6 class="fw-bold text-dark mb-1" id="previewCardName">د. اسم الطبيب</h6>
                            <div class="text-muted small mb-2" id="previewCardSpec" style="font-size: 12px;">التخصص الطبي</div>

                            <div class="d-flex justify-content-center gap-1 mb-2">
                                <span class="badge bg-primary-subtle text-primary border" id="previewCardDept" style="font-size: 11px;">العيادة</span>
                                <span class="badge bg-secondary-subtle text-secondary border" id="previewCardType" style="font-size: 11px;">استشاري</span>
                            </div>

                            <hr class="my-2 opacity-50">

                            <!-- معاينة التوقيع المباشرة في البطاقة -->
                            <div class="mb-2 text-start">
                                <label class="small text-muted fw-bold d-block mb-1 text-center" style="font-size: 11px;">التوقيع المعتمد في التقارير:</label>
                                <div class="p-2 rounded-3 text-center" id="cardSigContainer" style="min-height: 60px; background: repeating-conic-gradient(#f8fafc 0% 25%, #ffffff 0% 50%) 50% / 10px 10px; border: 1px dashed #cbd5e1; display: flex; align-items: center; justify-content: center;">
                                    <span class="text-muted small opacity-50" id="cardSigEmptyText" style="font-size: 11px;">
                                        <i class="fas fa-pen-nib me-1"></i> يظهر هنا فور رفع التوقيع
                                    </span>
                                    <img id="cardSigPreviewImg" src="" alt="التوقيع" class="d-none" style="max-height: 50px; max-width: 100%; object-fit: contain;">
                                </div>
                            </div>

                            <div class="bg-light p-2 rounded-3 text-start small">
                                <div class="d-flex justify-content-between mb-1" style="font-size: 12px;">
                                    <span class="text-muted">كشفية الكاش:</span>
                                    <span class="fw-bold text-success" id="previewCardFee">50,000 د.ع</span>
                                </div>
                                <div class="d-flex justify-content-between" style="font-size: 12px;">
                                    <span class="text-muted">الدوام:</span>
                                    <span class="fw-semibold text-dark" id="previewCardHours">08:00 - 16:00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- بطاقة النشر والتحكم -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-3">
                            <div class="form-check form-switch mb-3 pb-2 border-bottom">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-dark small" for="is_active">
                                    تفعيل الطبيب فوراً في النظام
                                </label>
                                <div class="text-muted" style="font-size: 11px;">يظهر الطبيب في الاستعلامات والكاشير وجداول الحجوزات.</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm py-2">
                                    <i class="fas fa-check-circle me-1"></i> حفظ وتسجيل الطبيب
                                </button>
                                <a href="{{ route('doctors.index') }}" class="btn btn-outline-secondary rounded-pill py-2">
                                    <i class="fas fa-times me-1"></i> إلغاء والتراجع
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تنسيق رقم الهاتف التلقائي
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let val = e.target.value.replace(/\D/g, '');
            if (val.startsWith('964')) {
                val = '+' + val;
            } else if (val.startsWith('07')) {
                val = '+964' + val.substring(1);
            }
            e.target.value = val;
        });
    }

    // ==========================================
    // بطاقة المعاينة المباشرة التفاعلية (Live Identity Card Sync)
    // ==========================================
    const nameInput = document.getElementById('name');
    const specInput = document.getElementById('specialization');
    const deptSelect = document.getElementById('department_id');
    const typeSelect = document.getElementById('type');
    const feeInput = document.getElementById('consultation_fee');
    const startInput = document.getElementById('start_time');
    const endInput = document.getElementById('end_time');

    const cardName = document.getElementById('previewCardName');
    const cardSpec = document.getElementById('previewCardSpec');
    const cardDept = document.getElementById('previewCardDept');
    const cardType = document.getElementById('previewCardType');
    const cardFee = document.getElementById('previewCardFee');
    const cardHours = document.getElementById('previewCardHours');

    if (nameInput && cardName) {
        nameInput.addEventListener('input', () => cardName.textContent = nameInput.value.trim() || 'د. اسم الطبيب');
    }
    if (specInput && cardSpec) {
        specInput.addEventListener('input', () => cardSpec.textContent = specInput.value.trim() || 'التخصص الطبي');
    }
    if (deptSelect && cardDept) {
        deptSelect.addEventListener('change', () => {
            const opt = deptSelect.options[deptSelect.selectedIndex];
            cardDept.textContent = opt ? (opt.getAttribute('data-name') || opt.text) : 'العيادة';
        });
    }
    if (typeSelect && cardType) {
        typeSelect.addEventListener('change', () => {
            const opt = typeSelect.options[typeSelect.selectedIndex];
            cardType.textContent = opt ? opt.text.split('(')[0].trim() : 'استشاري';
        });
    }
    if (feeInput && cardFee) {
        feeInput.addEventListener('input', () => {
            const val = parseFloat(feeInput.value) || 0;
            cardFee.textContent = val.toLocaleString() + ' د.ع';
        });
    }
    function updateHours() {
        if (cardHours && startInput && endInput) {
            cardHours.textContent = (startInput.value || '08:00') + ' - ' + (endInput.value || '16:00');
        }
    }
    if (startInput) startInput.addEventListener('change', updateHours);
    if (endInput) endInput.addEventListener('change', updateHours);

    // ==========================================
    // التحكم السريع في أيام الأسبوع
    // ==========================================
    const selectAllBtn = document.getElementById('selectAllDaysBtn');
    const clearAllBtn = document.getElementById('clearAllDaysBtn');
    const dayCheckboxes = document.querySelectorAll('.day-checkbox');

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', () => {
            dayCheckboxes.forEach(cb => cb.checked = true);
        });
    }
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', () => {
            dayCheckboxes.forEach(cb => cb.checked = false);
        });
    }

    // ==========================================
    // محرك تفريغ التوقيع التلقائي والمعاينة
    // ==========================================
    const sigFileInput = document.getElementById('createSigFileInput');
    const sigDataInput = document.getElementById('createSigDataInput');
    const previewContainer = document.getElementById('createSigPreviewContainer');
    const canvas = document.getElementById('createSigCanvas');
    const thresholdRange = document.getElementById('createThresholdRange');
    const thresholdVal = document.getElementById('createThresholdVal');
    const cancelSigBtn = document.getElementById('createCancelSigBtn');
    const dimBadge = document.getElementById('createDimBadge');
    const cardSigEmptyText = document.getElementById('cardSigEmptyText');
    const cardSigPreviewImg = document.getElementById('cardSigPreviewImg');

    let loadedImg = null;

    if (sigFileInput && canvas) {
        sigFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(event) {
                const img = new Image();
                img.onload = function() {
                    loadedImg = img;
                    if (dimBadge) dimBadge.textContent = img.width + ' × ' + img.height + ' px';
                    if (previewContainer) previewContainer.classList.remove('d-none');
                    renderTransparentSignature();
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        });

        if (thresholdRange) {
            thresholdRange.addEventListener('input', function() {
                if (thresholdVal) thresholdVal.textContent = this.value;
                if (loadedImg) {
                    renderTransparentSignature();
                }
            });
        }

        if (cancelSigBtn) {
            cancelSigBtn.addEventListener('click', function() {
                sigFileInput.value = '';
                sigDataInput.value = '';
                loadedImg = null;
                if (previewContainer) previewContainer.classList.add('d-none');
                if (cardSigPreviewImg) {
                    cardSigPreviewImg.classList.add('d-none');
                    cardSigPreviewImg.src = '';
                }
                if (cardSigEmptyText) cardSigEmptyText.classList.remove('d-none');
            });
        }

        function renderTransparentSignature() {
            if (!loadedImg) return;

            const threshold = parseInt(thresholdRange ? thresholdRange.value : 200, 10);
            const off = document.createElement('canvas');
            const offCtx = off.getContext('2d');

            let w = loadedImg.width;
            let h = loadedImg.height;
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

            off.width = w;
            off.height = h;
            offCtx.drawImage(loadedImg, 0, 0, w, h);

            const imgData = offCtx.getImageData(0, 0, w, h);
            const data = imgData.data;

            let minX = w, maxX = 0, minY = h, maxY = 0;
            let hasVisible = false;

            for (let i = 0; i < data.length; i += 4) {
                const r = data[i];
                const g = data[i + 1];
                const b = data[i + 2];
                const lum = 0.299 * r + 0.587 * g + 0.114 * b;

                if (lum >= threshold) {
                    data[i + 3] = 0;
                } else {
                    const featherStart = Math.max(50, threshold - 45);
                    if (lum > featherStart) {
                        const ratio = (lum - featherStart) / (threshold - featherStart);
                        data[i + 3] = Math.round((1 - ratio) * 255);
                    } else {
                        data[i + 3] = 255;
                    }

                    // تعزيز خطوط الحبر
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

            // القص التلقائي للهوامش الزائدة
            if (hasVisible && maxX > minX && maxY > minY) {
                const pad = 12;
                const cropX = Math.max(0, minX - pad);
                const cropY = Math.max(0, minY - pad);
                const cropW = Math.min(w - cropX, (maxX - minX) + (pad * 2));
                const cropH = Math.min(h - cropY, (maxY - minY) + (pad * 2));

                canvas.width = cropW;
                canvas.height = cropH;
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, cropW, cropH);
                ctx.drawImage(off, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH);
            } else {
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, w, h);
                ctx.drawImage(off, 0, 0);
            }

            const dataUrl = canvas.toDataURL('image/png');
            sigDataInput.value = dataUrl;

            // تحديث بطاقة المعاينة الجانبية
            if (cardSigPreviewImg && cardSigEmptyText) {
                cardSigPreviewImg.src = dataUrl;
                cardSigPreviewImg.classList.remove('d-none');
                cardSigEmptyText.classList.add('d-none');
            }
        }
    }
});
</script>

<style>
.step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background-color: #e0f2fe;
    color: #0284c7;
    font-size: 13px;
    font-weight: 800;
    border-radius: 50%;
}
.day-pill-selector .btn-check:checked + .btn-outline-primary {
    background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
    border-color: #0f766e;
    color: #ffffff;
    box-shadow: 0 4px 6px -1px rgba(15, 118, 110, 0.2);
}
.day-pill-selector .btn-outline-primary {
    border-color: #cbd5e1;
    color: #475569;
    background-color: #f8fafc;
}
.shadow-xs {
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.form-control:focus, .form-select:focus {
    border-color: #0f766e;
    box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.15);
}
</style>
@endsection