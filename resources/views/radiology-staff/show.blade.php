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
            @if($hasResult)
                <a href="{{ route('radiology-staff.print', $request) }}" target="_blank" class="btn btn-outline-success px-3 py-2 fw-semibold">
                    <i class="fas fa-print me-1"></i> طباعة التقرير الطبي
                </a>
            @endif
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
});
</script>
@endsection
