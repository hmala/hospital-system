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
        $patient = $emergencyRadiology->patient ?? $emergencyRadiology->emergency?->patient;
        $pName = $patient?->user?->name ?? $patient?->name ?? 'مريض غير محدد';
        $doc = $emergencyRadiology->emergency?->doctor;
        $docName = $doc?->user?->name ?? $doc?->name ?? 'طبيب الطوارئ';
        $types = $emergencyRadiology->radiologyTypes;
        $images = $resultData['images'] ?? [];
        $hasResult = !empty(trim($resultData['findings'] ?? '')) || $emergencyRadiology->status === 'completed';
    @endphp

    <!-- 1. Calm Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div class="d-flex align-items-center gap-3">
            <div class="calm-avatar" style="background: #fef2f2; color: #dc2626; border-color: #fecaca;">
                <i class="fas fa-ambulance"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-bold text-dark" style="letter-spacing: -0.3px;">
                    محطة إدخال تقرير طوارئ الأشعة (STAT Emergency Radiology)
                </h4>
                <div class="d-flex align-items-center gap-2 text-muted small mt-1">
                    <span>طلب طوارئ <strong class="text-dark">#{{ $emergencyRadiology->id }}</strong></span>
                    <span>•</span>
                    <span>الحالة: <span class="badge bg-danger text-white px-2 py-0.5">{{ $emergencyRadiology->priority_text ?? 'طوارئ STAT' }}</span></span>
                    <span>•</span>
                    <span>{{ optional($emergencyRadiology->requested_at)->format('Y-m-d H:i') }}</span>
                    <span>•</span>
                    <span class="badge {{ $emergencyRadiology->status === 'completed' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }} px-2 py-1">
                        {{ $emergencyRadiology->status === 'completed' ? 'معتمد ومكتمل' : ($emergencyRadiology->status === 'in_progress' ? 'قيد الفحص والإجراء' : 'في الانتظار') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            @if($emergencyRadiology->status === 'pending')
                <form action="{{ route('staff.emergency-radiology.start', $emergencyRadiology) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success px-3 py-2 fw-semibold shadow-xs">
                        <i class="fas fa-play me-1"></i> بدء الفحص
                    </button>
                </form>
            @endif

            <a href="{{ route('staff.emergency-radiology.print', $emergencyRadiology) }}" target="_blank" class="btn {{ $hasResult ? 'btn-outline-success' : 'btn-outline-primary' }} px-3 py-2 fw-semibold">
                <i class="fas fa-print me-1"></i> {{ $hasResult ? 'طباعة التقرير المعتمد' : 'معاينة / طباعة مسودة التقرير' }}
            </a>

            <a href="{{ route('radiology-staff.index') }}" class="btn btn-light border px-3 py-2 text-secondary fw-semibold">
                <i class="fas fa-arrow-right me-1"></i> العودة لمحطة العمل
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
    @if($errors->any())
        <div class="alert alert-danger border-0 rounded-3 mb-3 py-2 px-3 small">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 2. Patient Information Bar -->
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
                            {{ $patient?->gender === 'male' ? 'ذكر' : ($patient?->gender === 'female' ? 'أنثى' : '') }}
                            @if($patient?->age) <span>• {{ $patient->age }} سنة</span> @endif
                            @if($patient?->medical_number) <span class="font-monospace text-secondary ms-1">#{{ $patient->medical_number }}</span> @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="text-muted small mb-1"><i class="fas fa-user-md text-secondary me-1"></i> طبيب الطوارئ المحيل</div>
                <div class="fw-semibold text-dark">د. {{ $docName }}</div>
                <div class="text-muted" style="font-size: 0.75rem;">قسم الطوارئ والحوادث STAT</div>
            </div>

            <div class="col-lg-2 col-sm-6">
                <div class="text-muted small mb-1"><i class="fas fa-money-bill-wave text-secondary me-1"></i> الحالة المالية</div>
                @if($emergencyRadiology->payment_status === 'paid' || $emergencyRadiology->payment?->status === 'paid')
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="fas fa-check-circle me-1"></i> مدفوع
                    </span>
                @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                        <i class="fas fa-clock me-1"></i> غير مدفوع
                    </span>
                @endif
            </div>

            <div class="col-lg-3 col-sm-6 text-lg-end">
                <div class="text-muted small mb-1">الفحوصات المطلوبة ({{ $types->count() }}):</div>
                <div class="d-flex flex-wrap gap-1 justify-content-lg-end align-items-center">
                    @foreach($types as $type)
                        <span class="badge bg-light text-dark border px-2 py-1">
                            <i class="fas fa-x-ray text-primary me-1"></i> {{ $type->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Doctor Symptoms / Clinical Indication Alert -->
    @php
        $clinicalIndication = $emergencyRadiology->emergency?->symptoms 
            ?: ($emergencyRadiology->emergency?->hpi 
            ?: ($emergencyRadiology->notes ?: null));
    @endphp
    @if($clinicalIndication)
        <div class="alert alert-warning bg-warning bg-opacity-10 border-warning border-opacity-25 rounded-3 mb-4 p-3 shadow-xs">
            <div class="d-flex align-items-start gap-2">
                <div class="badge bg-warning text-dark p-2 rounded-circle fs-6 mt-1">
                    <i class="fas fa-notes-medical"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-1">
                        <i class="fas fa-stethoscope text-warning me-1"></i> دواعي الفحص والأعراض السريرية المسجلة من طبيب الطوارئ:
                    </h6>
                    <div class="text-dark fw-normal small" style="white-space: pre-wrap; line-height: 1.6;">
                        {{ $clinicalIndication }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 3. Form & Clinical Editor Layout -->
    <form action="{{ route('staff.emergency-radiology.complete', $emergencyRadiology) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            
            <!-- Main Report Area (Findings & Impression) -->
            <div class="col-lg-8">
                <div class="calm-card p-4 mb-4">
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="findings" class="form-label fw-bold text-dark mb-0">
                            <i class="fas fa-file-medical-alt text-primary me-1"></i> النتائج والمشاهدات التشخيصية (Clinical Findings)
                        </label>
                        <span class="text-muted small">حقل تشخيصي تفصيلي</span>
                    </div>

                    <textarea name="findings" id="findings" class="form-control calm-textarea mb-3" rows="9" placeholder="اكتب نتائج ومشاهدات فحص الأشعة هنا بدقة..." required>{{ old('findings', $resultData['findings'] ?? '') }}</textarea>

                    <!-- Quiet Quick Templates Chips -->
                    <div class="mb-4">
                        <div class="text-muted small mb-2 d-flex align-items-center gap-1">
                            <i class="fas fa-magic text-secondary"></i> قوالب سريرية سريعة بنقرة واحدة:
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="template-chip" data-text="Normal study with no significant pathological findings or focal abnormalities detected.">
                                <i class="fas fa-check text-success"></i> فحص سليم طبيعي (Normal)
                            </button>
                            <button type="button" class="template-chip" data-text="Chest X-Ray reveals clear lung fields bilaterally. Cardiothoracic ratio is within normal limits. Both costophrenic angles are sharp and clear. No evidence of pneumothorax, consolidation, or pleural effusion.">
                                <i class="fas fa-lungs text-primary"></i> أشعة صدر سليمة (Chest Normal)
                            </button>
                            <button type="button" class="template-chip" data-text="Ultrasound examination reveals normal liver size and homogeneous echotexture. Gallbladder is well distended, thin-walled, with no calculi. Both kidneys show normal size and corticomedullary differentiation without hydronephrosis or stones. Spleen and urinary bladder are normal. No free fluid.">
                                <i class="fas fa-wave-square text-info"></i> سونار بطن وحوض سليم (Abdomen Normal)
                            </button>
                            <button type="button" class="template-chip" data-text="No radiological evidence of acute fracture, dislocation, or focal bony lesion detected. Soft tissues appear unremarkable.">
                                <i class="fas fa-bone text-secondary"></i> أشعة عظام سليمة (No Fracture)
                            </button>
                            <button type="button" class="template-chip" data-text="Significant acute radiological findings detected requiring immediate emergency clinical correlation.">
                                <i class="fas fa-exclamation-triangle text-warning"></i> نتائج طارئة غير طبيعية (Abnormal)
                            </button>
                        </div>
                    </div>

                    <div class="row g-3 pt-3 border-top">
                        <div class="col-md-6">
                            <label for="impression" class="form-label small fw-bold text-dark mb-1">
                                <i class="fas fa-brain text-secondary me-1"></i> الاستنتاج الطبي (Impression / Conclusion)
                            </label>
                            <input type="text" name="impression" id="impression" class="form-control calm-input" placeholder="مثال: Normal Chest Radiograph" value="{{ old('impression', $resultData['impression'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="recommendations" class="form-label small fw-bold text-dark mb-1">
                                <i class="fas fa-lightbulb text-secondary me-1"></i> التوصيات والمتابعة (Recommendations)
                            </label>
                            <input type="text" name="recommendations" id="recommendations" class="form-control calm-input" placeholder="مثال: Clinical correlation recommended" value="{{ old('recommendations', $resultData['recommendations'] ?? '') }}">
                        </div>
                    </div>

                </div>
            </div>

            <!-- Side Panel: Status, Images & Save -->
            <div class="col-lg-4">
                
                <!-- Status & Decision -->
                <div class="calm-card p-3 mb-3">
                    <h6 class="fw-bold text-dark mb-3">
                        <i class="fas fa-cog text-secondary me-1"></i> حالة فحص الطوارئ
                    </h6>

                    <div class="mb-3">
                        <label for="status" class="form-label small text-muted mb-1">الحالة الإجرائية:</label>
                        <select name="status" id="status" class="form-select calm-input fw-semibold">
                            <option value="completed" {{ $emergencyRadiology->status === 'completed' ? 'selected' : (!in_array($emergencyRadiology->status, ['in_progress']) ? 'selected' : '') }}>✅ اعتماد التقرير كـ مكتمل نهائي</option>
                            <option value="in_progress" {{ $emergencyRadiology->status === 'in_progress' ? 'selected' : '' }}>⏳ قيد الفحص والإجراء</option>
                            <option value="pending" {{ $emergencyRadiology->status === 'pending' ? 'selected' : '' }}>⏸️ في قائمة الانتظار</option>
                        </select>
                    </div>

                    <div class="text-muted small mb-1">
                        طبيب / فني الأشعة المعتمد: <strong>{{ $resultData['radiologist'] ?? auth()->user()->name }}</strong>
                    </div>
                </div>

                <!-- Attach Diagnostic Images / PDF -->
                <div class="calm-card p-3 mb-3">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="fas fa-images text-secondary me-1"></i> صور ومرفقات الفحص
                    </h6>

                    <div class="file-drop-area mb-2">
                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                        <div class="small fw-semibold text-dark">انقر هنا أو اسحب الملفات لرفعها</div>
                        <small class="text-muted d-block mb-2" style="font-size: 0.72rem;">JPG, PNG, DICOM, PDF (حتى 10MB)</small>
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
                                        <div class="p-1 border rounded bg-light text-center position-relative">
                                            @if($isImg)
                                                <img src="{{ $imgUrl }}" class="img-fluid rounded mb-1" style="height: 55px; width: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='{{ asset('images/1.jpg') }}';">
                                            @else
                                                <div class="py-2 text-secondary"><i class="fas fa-file-pdf text-danger fa-2x"></i></div>
                                            @endif
                                            <a href="{{ $imgUrl }}" target="_blank" class="btn btn-xs btn-outline-secondary py-0 px-2 d-block text-truncate" style="font-size: 0.7rem;">
                                                <i class="fas fa-eye"></i> معاينة
                                            </a>
                                            <div class="form-check mt-1 text-center" style="font-size: 0.72rem;">
                                                <input class="form-check-input float-none" type="checkbox" name="remove_images[]" value="{{ $img }}" id="rm_{{ md5($img) }}">
                                                <label class="form-check-label text-danger" for="rm_{{ md5($img) }}">حذف</label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Emergency Notes -->
                <div class="calm-card p-3 mb-3">
                    <label for="notes" class="form-label small fw-bold text-dark mb-1">
                        <i class="fas fa-sticky-note text-secondary me-1"></i> ملاحظات إضافية داخلية:
                    </label>
                    <textarea name="notes" id="notes" class="form-control calm-input" rows="2" placeholder="أي ملاحظات داخلية لفريق التمريض أو الأطباء...">{{ old('notes', $emergencyRadiology->notes ?? '') }}</textarea>
                </div>

                <!-- Primary Action Button -->
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold py-3 shadow-sm" style="background-color: #0284c7; border-color: #0284c7; border-radius: 10px;">
                    <i class="fas fa-check-circle me-2"></i> حفظ واعتماد تقرير الأشعة
                </button>

            </div>

        </div>
    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quick Template Handlers
    const findingsTextarea = document.getElementById('findings');
    const templateChips = document.querySelectorAll('.template-chip');

    templateChips.forEach(chip => {
        chip.addEventListener('click', function() {
            const templateText = this.getAttribute('data-text');
            if (findingsTextarea.value.trim() === '') {
                findingsTextarea.value = templateText;
            } else {
                if (confirm('هل ترغب باستبدال نص النتائج الحالي بنص القالب المحدد؟')) {
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
});
</script>
@endsection
