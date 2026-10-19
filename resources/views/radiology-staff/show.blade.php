@extends('layouts.app')

@section('styles')
<style>
    /* Calm, Serene Clinical Design */
    :root {
        --calm-bg: #f8fafc;
        --calm-card: #ffffff;
        --calm-border: #e2e8f0;
        --calm-text-main: #1e293b;
        --calm-text-muted: #64748b;
        --calm-accent: #0284c7;
        --calm-accent-subtle: #f0f9ff;
        --calm-success: #10b981;
        --calm-success-subtle: #ecfdf5;
    }

    body {
        background-color: #f8fafc;
    }

    .calm-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        transition: all 0.2s ease;
    }

    .calm-patient-bar {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.25rem 1.5rem;
    }

    .calm-meta-pill {
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        border-radius: 8px;
        padding: 0.5rem 0.85rem;
    }

    .calm-textarea {
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        color: #1e293b;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 0.95rem;
        line-height: 1.7;
        padding: 1rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .calm-textarea:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
        outline: none;
    }

    .calm-input {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.6rem 0.85rem;
        font-size: 0.9rem;
    }
    .calm-input:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        outline: none;
    }

    .template-chip {
        background: #f8fafc;
        color: #334155;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 0.4rem 0.85rem;
        font-size: 0.82rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .template-chip:hover {
        background: #f0f9ff;
        color: #0284c7;
        border-color: #bae6fd;
        transform: translateY(-1px);
    }

    .calm-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #f0fdf4;
        color: #059669;
        border: 1px solid #a7f3d0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
    }

    .file-drop-area {
        border: 2px dashed #cbd5e1;
        border-radius: 10px;
        background: #f8fafc;
        padding: 1.25rem;
        text-align: center;
        transition: all 0.2s ease;
    }
    .file-drop-area:hover {
        border-color: #0284c7;
        background: #f0f9ff;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" style="max-width: 1400px; margin: 0 auto;">
    
    @php
        $p = $request->visit?->patient;
        $pName = $p?->name ?? $p?->user?->name ?? 'غير محدد';
        $docName = $request->visit?->doctor?->user?->name ?? 'الاستشارية';
        $radNames = $request->radiology_names ?: [$request->description ?: 'فحص تصوير'];
        $details = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
        $images = $resultData['images'] ?? [];
        $hasResult = !empty($resultData['findings']) || $request->status === 'completed';
    @endphp

    <!-- 1. Calm Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div class="d-flex align-items-center gap-3">
            <div class="calm-avatar" style="background: #f0f9ff; color: #0284c7; border-color: #bae6fd;">
                <i class="fas fa-wave-square"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-bold text-dark" style="letter-spacing: -0.3px;">
                    تقرير الفحص الإشعاعي والتصوير الطبي
                </h4>
                <div class="d-flex align-items-center gap-2 text-muted small mt-1">
                    <span>طلب رقم <strong class="text-dark">#{{ $request->id }}</strong></span>
                    <span>•</span>
                    <span>{{ $request->created_at->format('Y-m-d H:i') }}</span>
                    <span>•</span>
                    <span class="badge {{ $request->status === 'completed' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-info-subtle text-info border border-info-subtle' }} px-2 py-1">
                        {{ $request->status === 'completed' ? 'مكتمل ومعتمد' : ($request->status === 'in_progress' ? 'قيد الفحص الآن' : $request->status_text) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            @if($request->isDirectExternal() && !in_array($request->status, ['completed', 'cancelled']))
                <button type="button" class="btn btn-outline-danger px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#cancelExternalRadModal">
                    <i class="fas fa-ban me-1"></i> إلغاء الطلب الخارجي
                </button>
            @endif
            <a href="{{ route('radiology-staff.print', $request) }}" target="_blank" class="btn {{ $hasResult ? 'btn-outline-success' : 'btn-outline-primary' }} px-3 py-2 fw-semibold">
                <i class="fas fa-print me-1"></i> {{ $hasResult ? 'طباعة التقرير الطبي المعتمد' : 'معاينة / طباعة استمارة التقرير' }}
            </a>
            <a href="{{ route('radiology-staff.index') }}" class="btn btn-light border px-3 py-2 text-secondary fw-semibold">
                <i class="fas fa-arrow-right me-1"></i> العودة لطابور الفحص
            </a>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 mb-3 py-2 px-3 small d-flex align-items-center" role="alert">
            <i class="fas fa-check-circle me-2 text-success"></i>
            <div class="flex-grow-1">{{ session('success') }}</div>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 mb-3 py-2 px-3 small d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-circle me-2 text-danger"></i>
            <div class="flex-grow-1">{{ session('error') }}</div>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- 2. Patient Information Bar (Quiet & Clean) -->
    <div class="calm-patient-bar mb-4">
        <div class="row align-items-center g-3">
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <div class="calm-avatar">
                        {{ mb_substr($pName, 0, 1) }}
                    </div>
                    <div>
                        <div class="fw-bold text-dark fs-6">{{ $pName }}</div>
                        <div class="text-muted small">
                            {{ $p?->gender === 'male' ? 'ذكر' : ($p?->gender === 'female' ? 'أنثى' : '') }}
                            @if($p?->age) <span>• {{ $p->age }} سنة</span> @endif
                            @if($p?->medical_number) <span class="font-monospace text-secondary ms-1">#{{ $p->medical_number }}</span> @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="text-muted small mb-1"><i class="fas fa-user-md text-secondary me-1"></i> الطبيب المحول</div>
                <div class="fw-semibold text-dark">د. {{ $docName }}</div>
                <div class="text-muted" style="font-size: 0.75rem;">{{ $request->visit?->department?->name ?? 'العيادات الاستشارية' }}</div>
            </div>

            <div class="col-lg-2 col-sm-6">
                <div class="text-muted small mb-1"><i class="fas fa-money-bill-wave text-secondary me-1"></i> الرسوم المالية</div>
                @if($request->payment_status === 'paid')
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="fas fa-check-circle me-1"></i> مسدد
                    </span>
                @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                        <i class="fas fa-clock me-1"></i> غير مسدد
                    </span>
                @endif
            </div>

            <div class="col-lg-3 col-sm-6 text-lg-end">
                <div class="text-muted small mb-1">الفحوصات المقررة</div>
                <div class="d-flex flex-wrap gap-1 justify-content-lg-end">
                    @foreach($radNames as $rn)
                        <span class="badge bg-light text-dark border px-2 py-1">
                            <i class="fas fa-x-ray text-primary me-1"></i> {{ $rn }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @if(!empty($request->doctor_notes))
        <div class="alert alert-warning bg-warning bg-opacity-10 border-warning border-opacity-25 rounded-3 mb-4 p-3 shadow-xs">
            <div class="d-flex align-items-center gap-2">
                <div class="badge bg-warning text-dark p-2 rounded-circle fs-6">
                    <i class="fas fa-sticky-note"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-1">
                        <i class="fas fa-user-md text-warning me-1"></i> ملاحظات وتوجيهات الطبيب المحول:
                    </h6>
                    <div class="text-dark fw-semibold fs-6">
                        {{ $request->doctor_notes }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    @php
        $needsSelection = ($request->status === 'pending_service_selection') || empty($request->radiology_names);
        $roleNameAr = match($userCategory) {
            'radiology', 'general' => 'الأشعة العادية (X-Ray)',
            'ultrasound' => 'السونار (Ultrasound)',
            'mri' => 'الرنين المغناطيسي (MRI)',
            'ct' => 'المفراس الحلزوني (CT Scan)',
            'echo' => 'الإيكو (Echocardiogram)',
            default => 'الأشعة والتصوير الطبي'
        };
    @endphp

    @if($needsSelection)
        <!-- نظام البحث الفوري الذكي وتحديد فحوصات الأشعة (Smart Autocomplete) -->
        <div class="calm-card p-3 mb-4 border-primary border-opacity-25 shadow-sm bg-white position-relative">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white p-2 rounded-circle">
                        <i class="fas fa-search-plus fs-6"></i>
                    </span>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">تحديد نوع الفحص الإشعاعي المطلوب</h6>
                        <small class="text-muted">
                            القسم المختص: <strong class="text-primary">{{ $roleNameAr }}</strong>
                            <span class="mx-1">•</span>
                            <span>إجمالي الفحوصات: {{ $availableRadiologyTypes->count() }} فحص</span>
                        </small>
                    </div>
                </div>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 small">
                    <i class="fas fa-exclamation-triangle me-1"></i> بانتظار تحديد الفحص للدفع
                </span>
            </div>

            <form action="{{ route('radiology-staff.update', $request) }}" method="POST" id="selectRadiologyTypeForm">
                @csrf
                @method('PUT')

                <!-- حقل البحث الذكي الفوري -->
                <div class="position-relative mb-2">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-primary">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="radiologyFilterSearch" class="form-control border-start-0 py-2 fs-6" 
                               placeholder="اكتب اسم الفحص بالعربي أو الإنجليزي أو الكود لاختياره مباشرة..." autocomplete="off">
                        <button class="btn btn-outline-secondary px-3" type="button" id="clearRadFilter">مسح</button>
                    </div>

                    <!-- القائمة المنسدلة للنتائج المباشرة أثناء الكتابة (Live Search Dropdown) -->
                    <div id="radiologyDropdownResults" class="position-absolute w-100 bg-white border rounded-3 shadow-lg p-1 d-none" 
                         style="top: 100%; left: 0; z-index: 1050; max-height: 280px; overflow-y: auto;">
                        <div id="dropdownListGroup" class="list-group list-group-flush">
                            <!-- يتم ملؤها ديناميكياً بواسطة JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- شريط الفحوصات المختارة التي سيتم تأكيدها -->
                <div class="p-2 rounded-2 bg-light border mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-bold text-dark">
                            <i class="fas fa-check-double text-success me-1"></i> الفحوصات المختارة للطلب:
                        </span>
                        <span class="small text-muted" id="totalPriceDisplay">الإجمالي: 0 د.ع</span>
                    </div>
                    <div id="selectedBadgesContainer" class="d-flex flex-wrap gap-2 py-1 align-items-center">
                        <span class="text-muted small fst-italic" id="noSelectedText">لم يتم اختيار أي فحص بعد. ابحث في الحقل أعلاه لاختيار الفحص.</span>
                    </div>
                    <!-- حقول الـ Hidden inputs للفحوصات المختارة -->
                    <div id="hiddenInputsContainer"></div>
                </div>

                <!-- زر الحفظ والتأكيد للكاشير -->
                <div class="d-flex justify-content-between align-items-center pt-1">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1 text-info"></i> بمجرد التأكيد، سيتم إنشاء قيد السعر تلقائياً ويتوجه المريض للكاشير.
                    </small>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-bold shadow-sm" id="submitRadSelectionBtn" disabled>
                        <i class="fas fa-check-circle me-1"></i> حفظ وتأكيد الفحوصات وتحويل للكاشير
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- 3. Form & Clinical Editor Layout -->
    <form action="{{ route('radiology-staff.update', $request) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            
            <!-- Main Report Area (Findings & Impression) -->
            <div class="col-lg-8">
                <div class="calm-card p-4 mb-4">
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="findings" class="form-label fw-bold text-dark mb-0">
                            <i class="fas fa-file-medical-alt text-primary me-1"></i> المشاهدات والنتائج الإشعاعية (Findings)
                        </label>
                        <span class="text-muted small">نص التقرير الطبي المعتمد</span>
                    </div>

                    <textarea name="findings" id="findings" class="form-control calm-textarea mb-3" rows="9" placeholder="أدخل تفاصيل المشاهدات والتقرير السريري هنا..." required>{{ old('findings', $resultData['findings'] ?? '') }}</textarea>

                    <!-- Quiet Quick Templates Chips -->
                    <div class="mb-4">
                        <div class="text-muted small mb-2 d-flex align-items-center gap-1">
                            <i class="fas fa-magic text-secondary"></i> قوالب وعبارات سريعة بنقرة واحدة:
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="template-chip" data-text="Normal study with no significant pathological findings or focal abnormalities detected.">
                                <i class="fas fa-check text-success"></i> فحص سليم طبيعي (Normal)
                            </button>
                            <button type="button" class="template-chip" data-text="Ultrasound examination reveals normal liver size and homogeneous echotexture. Gallbladder is well distended, thin-walled, with no calculi. Both kidneys show normal size and corticomedullary differentiation without hydronephrosis or stones. Spleen and urinary bladder are normal.">
                                <i class="fas fa-wave-square text-info"></i> سونار بطن وحوض سليم (Abdomen Normal)
                            </button>
                            <button type="button" class="template-chip" data-text="Chest X-Ray reveals clear lung fields bilaterally. Cardiothoracic ratio is within normal limits. Both costophrenic angles are sharp and clear. No evidence of pneumothorax or pleural effusion.">
                                <i class="fas fa-lungs text-primary"></i> أشعة صدر سليمة (Chest Normal)
                            </button>
                            <button type="button" class="template-chip" data-text="Significant findings observed requiring close clinical correlation and recommended follow-up.">
                                <i class="fas fa-exclamation-triangle text-warning"></i> ملاحظات غير طبيعية (Abnormal)
                            </button>
                        </div>
                    </div>

                    <div class="row g-3 pt-3 border-top">
                        <div class="col-md-6">
                            <label for="impression" class="form-label small fw-bold text-dark mb-1">
                                <i class="fas fa-brain text-secondary me-1"></i> الانطباع التشخيصي (Impression)
                            </label>
                            <input type="text" name="impression" id="impression" class="form-control calm-input" placeholder="مثال: Normal Abdominal Ultrasound" value="{{ old('impression', $resultData['impression'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="recommendations" class="form-label small fw-bold text-dark mb-1">
                                <i class="fas fa-lightbulb text-secondary me-1"></i> التوصيات والمتابعة (Recommendations)
                            </label>
                            <input type="text" name="recommendations" id="recommendations" class="form-control calm-input" placeholder="مثال: Routine follow-up" value="{{ old('recommendations', $resultData['recommendations'] ?? '') }}">
                        </div>
                    </div>

                </div>
            </div>

            <!-- Side Panel: Status, Images & Save -->
            <div class="col-lg-4">
                
                <!-- Status & Decision -->
                <div class="calm-card p-3 mb-3">
                    <h6 class="fw-bold text-dark mb-3">
                        <i class="fas fa-cog text-secondary me-1"></i> حالة الطلب والاعتماد
                    </h6>

                    <div class="mb-3">
                        <label for="status" class="form-label small text-muted mb-1">تحديث الحالة:</label>
                        <select name="status" id="status" class="form-select calm-input fw-semibold">
                            <option value="completed" {{ $request->status === 'completed' ? 'selected' : (!in_array($request->status, ['in_progress']) ? 'selected' : '') }}>🟢 مكتمل واعتماد التقرير</option>
                            <option value="in_progress" {{ $request->status === 'in_progress' ? 'selected' : '' }}>🔵 قيد الفحص داخل الغرفة</option>
                            <option value="pending" {{ $request->status === 'pending' ? 'selected' : '' }}>⏳ بالانتظار</option>
                        </select>
                    </div>

                    <div class="text-muted small mb-1">
                        الطبيب الإشعاعي: <strong>{{ $resultData['radiologist'] ?? auth()->user()->name }}</strong>
                    </div>
                </div>

                <!-- Attach Images / PDF -->
                <div class="calm-card p-3 mb-3">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="fas fa-images text-secondary me-1"></i> صور ومرفقات الفحص
                    </h6>

                    <div class="file-drop-area mb-2">
                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                        <div class="small fw-semibold text-dark">اختر صور الفحص أو تقرير الجهاز</div>
                        <small class="text-muted d-block mb-2" style="font-size: 0.72rem;">JPG, PNG, DICOM, PDF</small>
                        <input type="file" name="images[]" id="images" class="form-control form-control-sm" multiple accept=".dcm,.dicom,.pdf,.jpg,.jpeg,.png,.tiff">
                    </div>

                    <div id="image-preview-box" class="mt-2 text-center" style="display: none;">
                        <img id="image-preview-elem" src="#" class="img-fluid rounded border" style="max-height: 120px;" />
                    </div>

                    <!-- Existing Images List -->
                    @if(count($images) > 0)
                        <div class="mt-3 pt-2 border-top">
                            <small class="text-muted d-block mb-2">المرفقات الحالية ({{ count($images) }}):</small>
                            <div class="row g-2">
                                @foreach($images as $img)
                                    @php
                                        $imgUrl = asset('storage/' . $img);
                                        $imgExt = strtolower(pathinfo($img, PATHINFO_EXTENSION));
                                        $isImg = in_array($imgExt, ['jpg', 'jpeg', 'png', 'gif', 'tiff', 'bmp', 'webp']);
                                    @endphp
                                    <div class="col-6">
                                        <div class="p-1 border rounded bg-light text-center">
                                            @if($isImg)
                                                <img src="{{ $imgUrl }}" class="img-fluid rounded mb-1" style="height: 55px; width: 100%; object-fit: cover;">
                                            @else
                                                <div class="py-2 text-secondary"><i class="fas fa-file-pdf text-danger"></i></div>
                                            @endif
                                            <a href="{{ $imgUrl }}" target="_blank" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size: 0.7rem;">
                                                <i class="fas fa-eye"></i> معاينة
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Primary Action Button -->
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold py-3 shadow-sm" style="background-color: #0284c7; border-color: #0284c7; border-radius: 10px;">
                    <i class="fas fa-check-circle me-2"></i> حفظ واعتماد التقرير
                </button>

            </div>

        </div>
    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth Quick Template Handlers
    const findingsTextarea = document.getElementById('findings');
    const templateChips = document.querySelectorAll('.template-chip');

    templateChips.forEach(chip => {
        chip.addEventListener('click', function() {
            const templateText = this.getAttribute('data-text');
            if (findingsTextarea.value.trim() === '') {
                findingsTextarea.value = templateText;
            } else {
                if (confirm('هل تريد استبدال النص الحالي بالقالب المختار؟')) {
                    findingsTextarea.value = templateText;
                }
            }
            findingsTextarea.focus();
        });
    });

    // File Preview
    const fileInput = document.getElementById('images');
    const previewBox = document.getElementById('image-preview-box');
    const previewElem = document.getElementById('image-preview-elem');

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewElem.src = e.target.result;
                    previewBox.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                previewBox.style.display = 'none';
            }
        });
    }

    // محرك البحث الفوري المنبثق والوسوم الذكية للفحوصات الإشعاعية
    const radSearchInput = document.getElementById('radiologyFilterSearch');
    const clearRadFilter = document.getElementById('clearRadFilter');
    const radDropdown = document.getElementById('radiologyDropdownResults');
    const dropdownList = document.getElementById('dropdownListGroup');
    const selectedBadgesContainer = document.getElementById('selectedBadgesContainer');
    const hiddenInputsContainer = document.getElementById('hiddenInputsContainer');
    const noSelectedText = document.getElementById('noSelectedText');
    const submitRadBtn = document.getElementById('submitRadSelectionBtn');
    const totalPriceDisplay = document.getElementById('totalPriceDisplay');

    // مصفوفة الفحوصات المتاحة لهذا القسم
    const allRadTypes = {!! json_encode($availableRadiologyTypesArray ?? []) !!};

    // خريطة لتخزين الفحوصات المختارة حالياً (id => object)
    const selectedRadTypes = new Map();

    function normalizeArabic(text) {
        if (!text) return '';
        return text
            .toString()
            .toLowerCase()
            .replace(/[أإآ]/g, 'ا')
            .replace(/ة/g, 'ه')
            .replace(/ى/g, 'ي')
            .trim();
    }

    function renderSelectedItems() {
        if (!selectedBadgesContainer || !hiddenInputsContainer) return;

        selectedBadgesContainer.innerHTML = '';
        hiddenInputsContainer.innerHTML = '';

        if (selectedRadTypes.size === 0) {
            if (noSelectedText) noSelectedText.classList.remove('d-none');
            selectedBadgesContainer.appendChild(noSelectedText);
            if (submitRadBtn) submitRadBtn.disabled = true;
            if (totalPriceDisplay) totalPriceDisplay.textContent = 'الإجمالي: 0 د.ع';
            return;
        }

        if (noSelectedText) noSelectedText.classList.add('d-none');

        let totalPrice = 0;

        selectedRadTypes.forEach((item, id) => {
            totalPrice += item.price;

            // 1. عنصر الشارة الملونة مع زر الحذف (Badge Chip)
            const badge = document.createElement('div');
            badge.className = 'badge bg-white text-dark border border-primary border-opacity-50 p-2 d-flex align-items-center gap-2 shadow-xs';
            badge.style.fontSize = '0.82rem';
            badge.innerHTML = `
                <i class="fas fa-x-ray text-primary"></i>
                <div class="text-start">
                    <span class="fw-bold">${item.name}</span>
                    <span class="text-muted small mx-1 font-monospace">(${item.code})</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-1 py-0 ms-1">${item.price.toLocaleString()} د.ع</span>
                </div>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1 text-decoration-none remove-rad-btn" data-id="${id}" title="إزالة الفحص">
                    <i class="fas fa-times-circle fs-6"></i>
                </button>
            `;
            selectedBadgesContainer.appendChild(badge);

            // 2. Hidden input لإرساله مع الفورم
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'radiology_type_ids[]';
            hiddenInput.value = id;
            hiddenInputsContainer.appendChild(hiddenInput);
        });

        if (totalPriceDisplay) {
            totalPriceDisplay.innerHTML = `الإجمالي: <strong class="text-success">${totalPrice.toLocaleString()} د.ع</strong> (${selectedRadTypes.size} فحص)`;
        }

        if (submitRadBtn) {
            submitRadBtn.disabled = (selectedRadTypes.size === 0);
        }
    }

    // تفاعل حذف فحص مختار
    if (selectedBadgesContainer) {
        selectedBadgesContainer.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-rad-btn');
            if (removeBtn) {
                const id = parseInt(removeBtn.getAttribute('data-id'));
                selectedRadTypes.delete(id);
                renderSelectedItems();
            }
        });
    }

    // فلترة وبناء القائمة المنسدلة للبحث الفوري
    function handleRadSearch() {
        if (!radSearchInput || !radDropdown || !dropdownList) return;

        const query = normalizeArabic(radSearchInput.value);

        if (!query) {
            radDropdown.classList.add('d-none');
            dropdownList.innerHTML = '';
            return;
        }

        const matches = allRadTypes.filter(t => {
            const name = normalizeArabic(t.name);
            const code = normalizeArabic(t.code);
            return name.includes(query) || code.includes(query);
        });

        dropdownList.innerHTML = '';

        if (matches.length === 0) {
            dropdownList.innerHTML = `
                <div class="p-3 text-center text-muted small">
                    <i class="fas fa-search-minus me-1 text-secondary"></i> لا توجد فحوصات إشعاعية تطابق "<strong>${radSearchInput.value}</strong>" في قسمك
                </div>
            `;
            radDropdown.classList.remove('d-none');
            return;
        }

        matches.slice(0, 15).forEach(t => {
            const isAlreadySelected = selectedRadTypes.has(t.id);
            const a = document.createElement('a');
            a.href = 'javascript:void(0)';
            a.className = `list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 border-bottom ${isAlreadySelected ? 'bg-light text-muted' : ''}`;
            a.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <i class="fas ${isAlreadySelected ? 'fa-check text-success' : 'fa-plus text-primary'}"></i>
                    <div>
                        <div class="fw-bold small text-dark">${t.name}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">
                            <span class="font-monospace">${t.code}</span> • <span>${t.category}</span>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-success-subtle text-success border border-success-subtle">${t.price.toLocaleString()} د.ع</span>
                    ${isAlreadySelected ? '<span class="badge bg-secondary ms-1 small">محدد</span>' : ''}
                </div>
            `;

            a.addEventListener('click', function() {
                if (selectedRadTypes.has(t.id)) {
                    selectedRadTypes.delete(t.id);
                } else {
                    selectedRadTypes.set(t.id, t);
                }
                renderSelectedItems();
                radSearchInput.value = '';
                radDropdown.classList.add('d-none');
                radSearchInput.focus();
            });

            dropdownList.appendChild(a);
        });

        radDropdown.classList.remove('d-none');
    }

    if (radSearchInput) {
        radSearchInput.addEventListener('input', handleRadSearch);
        radSearchInput.addEventListener('focus', function() {
            if (this.value.trim()) handleRadSearch();
        });
    }

    if (clearRadFilter) {
        clearRadFilter.addEventListener('click', function() {
            if (radSearchInput) {
                radSearchInput.value = '';
                if (radDropdown) radDropdown.classList.add('d-none');
                radSearchInput.focus();
            }
        });
    }

    // إغلاق القائمة المنسدلة عند النقر خارجها
    document.addEventListener('click', function(e) {
        if (radDropdown && !radDropdown.contains(e.target) && e.target !== radSearchInput) {
            radDropdown.classList.add('d-none');
        }
    });
});
</script>

<!-- مودال إلغاء الطلب الخارجي المباشر للأشعة -->
<div class="modal fade" id="cancelExternalRadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i> تأكيد إلغاء طلب الأشعة والتصوير الخارجي
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('radiology-staff.cancel', $request) }}" method="POST">
                @csrf
                <div class="modal-body p-4 text-end">
                    <p class="fs-6 text-dark mb-2">
                        هل أنت متأكد من رغبتك في إلغاء طلب الأشعة والتصوير الخارجي للمريض <strong class="text-danger">{{ $pName }}</strong>؟
                    </p>
                    <div class="alert alert-warning py-2 px-3 small border-0 mb-3">
                        <i class="fas fa-info-circle me-1"></i> سيتم إلغاء الطلب فوراً وإزالته من طابور الفحص والمناداة. في حال كان الطلب مسدداً مالياً، سيتم توجيه المريض للكاشير لاسترداد المبلغ.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">سبب الإلغاء (اختياري):</label>
                        <input type="text" name="cancel_reason" class="form-control form-control-sm" placeholder="مثال: رغبة المريض، مغادرة المستشفى..." value="إلغاء الطلب الخارجي بطلب من المريض">
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-between">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">تراجع</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4">
                        <i class="fas fa-check me-1"></i> نعم، إلغاء الطلب الآن
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
