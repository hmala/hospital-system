@extends('layouts.app')

@section('styles')
<style>
    .info-item {
        background: #f8fafc;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 0.75rem;
    }
    .info-item small {
        color: #6b7280;
    }
    .info-item strong {
        font-size: 0.95rem;
    }
    .card {
        border-radius: 10px;
        border: 1px solid #e5e7eb;
    }
    .quick-result {
        font-size: 0.8rem;
        transition: all 0.2s ease;
    }
    .quick-result:hover {
        transform: translateY(-1px);
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">
    
    <!-- 1. Header & Actions -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                    <i class="fas fa-x-ray fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold text-dark">
                        كتابة تقرير الأشعة والتصوير الطبي
                    </h3>
                    <small class="text-muted">الطلب رقم #{{ $request->id }} • {{ $request->created_at->format('Y-m-d H:i') }}</small>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if($request->status === 'completed' || !empty($resultData['findings']))
                <a href="{{ route('radiology-staff.print', $request) }}" target="_blank" class="btn btn-success fw-bold px-3">
                    <i class="fas fa-print me-1"></i> طباعة التقرير
                </a>
            @endif
            <a href="{{ route('radiology-staff.index') }}" class="btn btn-outline-secondary px-3">
                <i class="fas fa-arrow-left me-1"></i> العودة للطابور والمحطة
            </a>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- 2. Patient & Request Overview Card -->
    @php
        $p = $request->visit?->patient;
        $pName = $p?->name ?? $p?->user?->name ?? 'غير محدد';
        $docName = $request->visit?->doctor?->user?->name ?? 'الاستشارية';
        $radNames = $request->radiology_names ?: [$request->description ?: 'فحص تصوير'];
        $details = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
        $images = $resultData['images'] ?? [];
    @endphp

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <div class="info-item p-3">
                        <small class="d-block mb-1">المريض</small>
                        <strong class="text-dark">{{ $pName }}</strong>
                        @if($p?->medical_number)
                            <span class="badge bg-light text-muted border font-monospace ms-1">{{ $p->medical_number }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="info-item p-3">
                        <small class="d-block mb-1">الجنس والسن</small>
                        <strong>{{ $p?->gender === 'male' ? 'ذكر' : ($p?->gender === 'female' ? 'أنثى' : 'غير محدد') }} • {{ $p?->age ? $p->age . ' سنة' : '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="info-item p-3">
                        <small class="d-block mb-1">الطبيب المحول</small>
                        <strong>د. {{ $docName }}</strong>
                        <div class="small text-muted" style="font-size: 0.75rem;">{{ $request->visit?->department?->name ?? 'العيادات' }}</div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="info-item p-3">
                        <small class="d-block mb-1">حالة السداد</small>
                        @if($request->payment_status === 'paid')
                            <span class="badge bg-success text-white py-1 px-2 fw-bold"><i class="fas fa-check-circle me-1"></i> مدفوع</span>
                        @else
                            <span class="badge bg-danger text-white py-1 px-2 fw-bold"><i class="fas fa-clock me-1"></i> غير مدفوع</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="info-item p-3">
                        <small class="d-block mb-1">الحالة الحالية</small>
                        <span class="badge bg-{{ $request->status === 'completed' ? 'success' : ($request->status === 'in_progress' ? 'info' : ($request->status === 'calling' ? 'warning text-dark' : 'secondary')) }} py-1 px-2">
                            {{ $request->status_text }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-3 pt-3 border-top">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <strong class="text-secondary small">الفحوصات المطلوبة:</strong>
                    @foreach($radNames as $rn)
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle py-1 px-2 fs-6">
                            <i class="fas fa-x-ray me-1"></i> {{ $rn }}
                        </span>
                    @endforeach
                    @if(!empty($details['description']) && $details['description'] !== implode(', ', $radNames))
                        <small class="text-muted ms-2">({{ $details['description'] }})</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Completed Results View (إذا كانت النتائج مسجلة مسبقاً) -->
    @if(!empty($resultData['findings']) || $request->status === 'completed')
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-success text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-file-medical-alt me-2"></i> نتائج التقرير الطبي المعتمد
                </h5>
                <a href="{{ route('radiology-staff.print', $request) }}" target="_blank" class="btn btn-sm btn-light text-success fw-bold">
                    <i class="fas fa-print me-1"></i> طباعة الوصل والتقرير
                </a>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-lg-7">
                    <!-- Findings -->
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-notes-medical text-primary me-1"></i> النتائج والمشاهدات السريرية (Findings):</h6>
                        <div class="p-3 bg-light rounded-3 border text-dark font-monospace" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6;">{{ $resultData['findings'] ?? 'لا توجد نتائج مسجلة' }}</div>
                    </div>

                    <!-- Impression -->
                    @if(!empty($resultData['impression']))
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-brain text-info me-1"></i> الانطباع التشخيصي (Impression):</h6>
                        <div class="p-3 bg-info bg-opacity-10 rounded-3 border border-info-subtle text-dark">{{ $resultData['impression'] }}</div>
                    </div>
                    @endif

                    <!-- Recommendations -->
                    @if(!empty($resultData['recommendations']))
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-lightbulb text-warning me-1"></i> التوصيات والمتابعة (Recommendations):</h6>
                        <div class="p-3 bg-warning bg-opacity-10 rounded-3 border border-warning-subtle text-dark">{{ $resultData['recommendations'] }}</div>
                    </div>
                    @endif

                    <div class="d-flex align-items-center gap-3 text-muted small mt-2">
                        <div><i class="fas fa-user-md me-1"></i> الطبيب الإشعاعي: <strong>{{ $resultData['radiologist'] ?? auth()->user()->name }}</strong></div>
                        <div><i class="fas fa-clock me-1"></i> تاريخ التقرير: <strong>{{ $resultData['reported_at'] ?? $request->updated_at->format('Y-m-d H:i') }}</strong></div>
                    </div>
                </div>

                <!-- Images Gallery -->
                <div class="col-lg-5">
                    <h6 class="fw-bold text-dark mb-2"><i class="fas fa-images text-primary me-1"></i> الصور والمرفقات الإشعاعية ({{ count($images) }}):</h6>
                    @if(count($images) > 0)
                        <div class="row g-2">
                            @foreach($images as $img)
                                @php
                                    $imgUrl = asset('storage/' . $img);
                                    $imgExt = strtolower(pathinfo($img, PATHINFO_EXTENSION));
                                    $isImg = in_array($imgExt, ['jpg', 'jpeg', 'png', 'gif', 'tiff', 'bmp', 'webp']);
                                @endphp
                                <div class="col-6">
                                    <div class="card h-100 border p-2 text-center bg-light">
                                        @if($isImg)
                                            <a href="{{ $imgUrl }}" target="_blank">
                                                <img src="{{ $imgUrl }}" alt="Radiology Scan" class="img-fluid rounded mb-2" style="height: 120px; width: 100%; object-fit: cover;">
                                            </a>
                                        @else
                                            <div class="py-3 text-secondary">
                                                <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                                                <div class="small text-truncate">{{ basename($img) }}</div>
                                            </div>
                                        @endif
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="{{ $imgUrl }}" target="_blank" class="btn btn-xs btn-outline-primary py-1 px-2 font-monospace small">
                                                <i class="fas fa-eye"></i> معاينة
                                            </a>
                                            <a href="{{ $imgUrl }}" download class="btn btn-xs btn-outline-secondary py-1 px-2 small">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 bg-light rounded-3 border text-muted">
                            <i class="fas fa-image fa-2x mb-2 text-secondary opacity-50"></i>
                            <p class="mb-0 small">لم يتم إرفاق صور للفحص</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- 4. Report Writing & Editing Form (تحديث نتائج التصوير) -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-warning bg-opacity-10 border-bottom py-3">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-edit text-warning me-2"></i>
                {{ empty($resultData['findings']) ? 'كتابة نتائج وتقرير الفحص الطبي' : 'تعديل نتائج التقرير والصور' }}
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('radiology-staff.update', $request) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-4">
                    
                    <!-- Column 1: Exam Info & Status -->
                    <div class="col-lg-4 col-12">
                        <div class="card h-100 border-0 bg-light p-3">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="fas fa-info-circle text-primary me-1"></i> بيانات الفحص والحالة
                            </h6>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">الفحوصات المقررة:</label>
                                <div class="bg-white p-2 rounded border">
                                    @foreach($radNames as $rn)
                                        <div class="fw-bold text-dark mb-1">
                                            <i class="fas fa-check text-success me-1"></i> {{ $rn }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label small fw-bold text-secondary">حالة الطلب:</label>
                                <select name="status" id="status" class="form-select form-select-sm fw-bold">
                                    <option value="in_progress" {{ $request->status === 'in_progress' ? 'selected' : '' }}>🔵 قيد الفحص داخل الغرفة</option>
                                    <option value="completed" {{ $request->status === 'completed' ? 'selected' : (!in_array($request->status, ['in_progress']) ? 'selected' : '') }}>🟢 مكتمل واعتماد النتيجة</option>
                                    <option value="pending" {{ $request->status === 'pending' ? 'selected' : '' }}>⏳ بالانتظار</option>
                                </select>
                            </div>

                            <!-- Upload Image/Files -->
                            <div class="mb-2">
                                <label for="images" class="form-label small fw-bold text-secondary">
                                    <i class="fas fa-upload text-primary me-1"></i> إرفاق صور / تقرير الفحص:
                                </label>
                                <input type="file" name="images[]" id="images" class="form-control form-control-sm" multiple accept=".dcm,.dicom,.pdf,.jpg,.jpeg,.png,.tiff">
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">الصيغ المدعومة: DICOM, PDF, صور JPG, PNG, TIFF</small>
                            </div>

                            <!-- Live preview container -->
                            <div id="image-preview-box" class="mt-2 text-center" style="display: none;">
                                <img id="image-preview-elem" src="#" class="img-fluid rounded border" style="max-height: 140px;" />
                            </div>
                        </div>
                    </div>

                    <!-- Column 2 & 3: Findings, Impressions & Quick Templates -->
                    <div class="col-lg-8 col-12">
                        <div class="card h-100 border-0 bg-light p-3">
                            
                            <!-- Findings Textarea -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="findings" class="form-label small fw-bold text-dark mb-0">
                                        <i class="fas fa-notes-medical text-primary me-1"></i> المشاهدات والنتائج الطبية (Findings): <span class="text-danger">*</span>
                                    </label>
                                </div>
                                <textarea name="findings" id="findings" class="form-control font-monospace" rows="7" placeholder="أدخل تفاصيل المشاهدات الإشعاعية / نتائج السونار هنا..." required>{{ old('findings', $resultData['findings'] ?? '') }}</textarea>
                            </div>

                            <!-- Quick Templates Buttons -->
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1 fw-bold"><i class="fas fa-bolt text-warning me-1"></i> قوالب ونتائج سريعة:</small>
                                <div class="d-flex flex-wrap gap-1">
                                    <button type="button" class="btn btn-outline-success btn-sm quick-result" data-text="Normal study with no significant pathological changes or focal lesions detected.">
                                        <i class="fas fa-check"></i> طبيعي سليم (Normal)
                                    </button>
                                    <button type="button" class="btn btn-outline-info btn-sm quick-result" data-text="Ultrasound examination reveals normal liver size and echotexture. Gallbladder is well distended, thin-walled, with no calculi. Both kidneys show normal size, parenchymal thickness and corticomedullary differentiation without hydronephrosis. Spleen and urinary bladder are normal.">
                                        <i class="fas fa-wave-square"></i> سونار بطن وحوض سليم (Abdomen Normal)
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm quick-result" data-text="Chest X-Ray shows clear lung fields bilaterally. Normal cardiothoracic ratio. Costophrenic and cardiophrenic angles are clear. No evidence of active pulmonary lesion or pneumothorax.">
                                        <i class="fas fa-lungs"></i> أشعة صدر سليمة (Chest Normal)
                                    </button>
                                    <button type="button" class="btn btn-outline-warning btn-sm quick-result" data-text="Abnormal findings observed requiring clinical correlation and follow-up.">
                                        <i class="fas fa-exclamation-triangle"></i> غير طبيعي (Abnormal)
                                    </button>
                                </div>
                            </div>

                            <!-- Impression & Recommendations -->
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label for="impression" class="form-label small fw-bold text-dark mb-1">
                                        <i class="fas fa-brain text-info me-1"></i> الانطباع النهائي (Impression):
                                    </label>
                                    <input type="text" name="impression" id="impression" class="form-control form-control-sm" placeholder="مثال: Normal Abdominal Ultrasound" value="{{ old('impression', $resultData['impression'] ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="recommendations" class="form-label small fw-bold text-dark mb-1">
                                        <i class="fas fa-lightbulb text-warning me-1"></i> التوصيات (Recommendations):
                                    </label>
                                    <input type="text" name="recommendations" id="recommendations" class="form-control form-control-sm" placeholder="مثال: Clinical correlation recommended" value="{{ old('recommendations', $resultData['recommendations'] ?? '') }}">
                                </div>
                            </div>

                            <!-- Submit button -->
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-xs">
                                    <i class="fas fa-save me-2"></i> حفظ واعتماد نتائج التقرير الطبي
                                </button>
                            </div>

                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quick Template Handlers
    const findingsTextarea = document.getElementById('findings');
    const quickButtons = document.querySelectorAll('.quick-result');
    quickButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const templateText = this.getAttribute('data-text');
            if (findingsTextarea.value.trim() === '') {
                findingsTextarea.value = templateText;
            } else {
                if (confirm('هل تريد استبدال النص الحالي بالقالب المختار؟')) {
                    findingsTextarea.value = templateText;
                }
            }
        });
    });

    // Image file live preview
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
