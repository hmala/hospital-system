@extends('layouts.app')

@section('styles')
<style>
    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #f8f9fa !important;
    }
    .table-hover tbody tr:hover {
        background-color: #e3f2fd;
        cursor: pointer;
    }
    .radiology-type-item:hover {
        background-color: #e8f4f8;
    }
    .card-header {
        border-radius: 10px 10px 0 0 !important;
    }
    .card {
        border: none;
        border-radius: 10px;
    }
    .info-item {
        background: #f8fafc;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 0.95rem;
    }
    .info-item small {
        color: #6b7280;
    }
    .info-item strong {
        font-size: 1rem;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-x-ray me-2"></i>
                    تفاصيل طلب الإشعة
                </h2>
                <div>
                    @if($radiology->status === 'completed' && $radiology->result)
                        <a href="{{ route('radiology.print', $radiology) }}" target="_blank" class="btn btn-success me-2">
                            <i class="fas fa-print me-1"></i>
                            طباعة النتيجة
                        </a>
                    @endif
                    @if(Auth::user()->isAdmin() || Auth::user()->isReceptionist())
                        @if($radiology->status === 'pending')
                        <a href="{{ route('radiology.edit', $radiology) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit me-1"></i>
                            تعديل
                        </a>
                        @endif
                    @endif
                    <a href="{{ route('radiology.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- معلومات المريض والطبيب -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-injured me-2"></i>
                        معلومات المريض
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="info-item p-3 rounded-3 bg-light">
                                <small class="text-secondary d-block mb-1">الاسم</small>
                                <strong>{{ $radiology->patient->user->name }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            </div>
        </div>
    </div>

    <!-- التفاصيل الطبية -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-notes-medical me-2"></i>
                        التفاصيل الطبية
                    </h5>
                </div>
                <div class="card-body">
                    @if($radiology->clinical_indication)
                    <div class="mb-3">
                        <p class="mb-2"><strong>المؤشرات السريرية:</strong></p>
                        <p class="text-muted">{{ $radiology->clinical_indication }}</p>
                    </div>
                    @endif

                    @if($radiology->specific_instructions)
                    <div class="mb-3">
                        <p class="mb-2"><strong>تعليمات خاصة:</strong></p>
                        <p class="text-muted">{{ $radiology->specific_instructions }}</p>
                    </div>
                    @endif

                    @if($radiology->notes)
                    <div class="mb-3">
                        <p class="mb-2"><strong>ملاحظات:</strong></p>
                        <p class="text-muted">{{ $radiology->notes }}</p>
                    </div>
                    @endif

                    @if(!$radiology->clinical_indication && !$radiology->specific_instructions && !$radiology->notes)
                    <p class="text-center text-muted">لا توجد تفاصيل طبية إضافية</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- عرض النتائج المكتملة -->
    @if($radiology->status === 'completed' && $radiology->result)
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        نتائج التصوير الإشعاعي
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- النتائج الطبية -->
                        <div class="col-lg-6">
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3"><i class="fas fa-file-medical text-primary me-2"></i>النتائج:</h6>
                                <div class="bg-light p-3 rounded">
                                    <p class="mb-0">{{ $radiology->result->findings }}</p>
                                </div>
                            </div>

                            @if($radiology->result->impression)
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3"><i class="fas fa-brain text-primary me-2"></i>الانطباع:</h6>
                                <div class="bg-light p-3 rounded">
                                    <p class="mb-0">{{ $radiology->result->impression }}</p>
                                </div>
                            </div>
                            @endif

                            @if($radiology->result->recommendations)
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3"><i class="fas fa-lightbulb text-primary me-2"></i>التوصيات:</h6>
                                <div class="bg-light p-3 rounded">
                                    <p class="mb-0">{{ $radiology->result->recommendations }}</p>
                                </div>
                            </div>
                            @endif

                            <div class="mb-4">
                                <h6 class="fw-bold mb-3"><i class="fas fa-user-md text-primary me-2"></i>الطبيب الإشعاعي:</h6>
                                <div class="bg-light p-3 rounded">
                                    <p class="mb-0">{{ $radiology->result->radiologist->name ?? 'غير محدد' }}</p>
                                    <small class="text-muted">تاريخ التقرير: {{ $radiology->result->reported_at ? $radiology->result->reported_at->format('Y-m-d H:i') : 'غير محدد' }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- الصور المرفقة -->
                        <div class="col-lg-6">
                            <h6 class="fw-bold mb-3"><i class="fas fa-images text-primary me-2"></i>الصور المرفقة:</h6>
                            @if($radiology->result->images && count($radiology->result->images) > 0)
                                <div class="row g-3">
                                    @foreach($radiology->result->images as $image)
                                        @php
                                            $imageUrl = asset('storage/' . $image);
                                            $imageExt = strtolower(pathinfo($image, PATHINFO_EXTENSION));
                                            $isImage = in_array($imageExt, ['jpg', 'jpeg', 'png', 'gif', 'tiff', 'bmp', 'webp']);
                                        @endphp
                                        <div class="col-md-6 col-sm-12">
                                            <div class="card h-100">
                                                <div class="card-body p-2">
                                                    @if($isImage)
                                                        <img src="{{ $imageUrl }}" alt="صورة تصوير إشعاعي" class="img-fluid rounded" style="width: 100%; height: 200px; object-fit: cover;">
                                                    @else
                                                        <div class="text-center py-4">
                                                            <i class="fas fa-file-alt fa-3x text-secondary mb-2"></i>
                                                            <p class="mb-0 small">{{ basename($image) }}</p>
                                                        </div>
                                                    @endif
                                                    <div class="mt-2 text-center">
                                                        <a href="{{ $imageUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye me-1"></i>عرض
                                                        </a>
                                                        <a href="{{ $imageUrl }}" download class="btn btn-sm btn-outline-secondary ms-1">
                                                            <i class="fas fa-download me-1"></i>تحميل
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5 bg-light rounded">
                                    <i class="fas fa-image fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">لا توجد صور مرفقة</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- تحديث/إدخال النتائج -->
    @if(Auth::user()->hasRole('radiology_staff') && in_array($radiology->status, ['scheduled', 'in_progress', 'completed']))
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>تحديث نتائج التصوير</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('radiology.saveResults', $radiology) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-4">
                            <div class="col-lg-4 col-12">
                                <div class="card h-100 border-secondary">
                                    <div class="card-body">
                                        <h6 class="card-title fw-bold mb-3"><i class="fas fa-file-medical text-primary me-2"></i>معلومات التصوير</h6>
                                        <div class="table-responsive mb-3">
                                            <table class="table table-hover table-bordered align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-center" style="width: 50px;">#</th>
                                                        <th>نوع التصوير</th>
                                                        <th style="width: 150px;" class="text-center">الحالة</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-center">1</td>
                                                        <td>
                                                            <strong>{{ $radiology->radiologyType->name ?? 'غير محدد' }}</strong>
                                                        </td>
                                                        <td class="text-center">
                                                            <select name="status" id="status" class="form-select form-select-sm @error('status') is-invalid @enderror" required>
                                                                <option value="pending" {{ $radiology->status == 'pending' ? 'selected' : '' }}>في الانتظار</option>
                                                                <option value="scheduled" {{ $radiology->status == 'scheduled' ? 'selected' : '' }}>مجدولة</option>
                                                                <option value="in_progress" {{ $radiology->status == 'in_progress' ? 'selected' : '' }}>قيد التنفيذ</option>
                                                                <option value="completed" {{ $radiology->status == 'completed' ? 'selected' : '' }}>مكتملة</option>
                                                            </select>
                                                            @error('status')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-12">
                                <div class="card h-100 border-secondary">
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title fw-bold mb-3"><i class="fas fa-upload text-primary me-2"></i>ملف التصوير</h6>

                                        <div class="mb-3">
                                            <input type="file" name="images[]" id="result_file" class="form-control @error('result_file') is-invalid @enderror" accept=".dcm,.dicom,.pdf,.jpg,.jpeg,.png,.tiff">
                                            <small class="form-text text-muted">الصيغ المدعومة: DICOM (.dcm), PDF (.pdf), صور (.jpg, .jpeg, .png, .tiff)</small>
                                            @error('result_file')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div id="resultFilePreview" class="mb-3" style="display: none;">
                                            <div class="text-center mb-3">
                                                <img id="resultFilePreviewImage" src="#" alt="معاينة الملف" class="img-fluid rounded border" style="max-height: 320px; width: auto; display: none;" />
                                                <div id="resultFilePreviewMessage" class="alert alert-secondary mb-0" style="display: none;"></div>
                                            </div>
                                        </div>

                                        @php
                                            $resultFileUrl = optional($radiology->result)->result_file ? asset('storage/' . $radiology->result->result_file) : null;
                                            $resultFileExt = optional($radiology->result)->result_file ? strtolower(pathinfo($radiology->result->result_file, PATHINFO_EXTENSION)) : null;
                                            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'tiff', 'bmp', 'webp'];
                                            $hasImagePreview = $resultFileUrl && in_array($resultFileExt, $imageExtensions);
                                        @endphp

                                        @if($resultFileUrl)
                                            <div class="mt-2">
                                                <div class="alert alert-info py-2 mb-0">
                                                    <i class="fas fa-file-alt me-1"></i>
                                                    <small>الملف الحالي: <a href="{{ $resultFileUrl }}" target="_blank" class="alert-link">{{ basename(optional($radiology->result)->result_file) }}</a></small>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-12">
                                <div class="card h-100 border-secondary">
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title fw-bold mb-3"><i class="fas fa-notes-medical text-primary me-2"></i>تقرير الطبيب الإشعاعي</h6>

                                        <div class="mb-3 flex-grow-1">
                                            <label for="findings" class="form-label"><strong>النتائج:</strong></label>
                                            <textarea name="findings" id="findings" class="form-control @error('findings') is-invalid @enderror" rows="8" placeholder="أدخل النتائج...">{{ old('findings', optional($radiology->result)->findings) }}</textarea>
                                            @error('findings')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mt-auto">
                                            <small class="text-muted d-block mb-2"><i class="fas fa-bolt me-1"></i>نتائج سريعة:</small>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button type="button" class="btn btn-outline-success btn-sm quick-result" data-result="طبيعي - لا توجد تغييرات مرضية">
                                                    <i class="fas fa-check"></i> طبيعي
                                                </button>
                                                <button type="button" class="btn btn-outline-warning btn-sm quick-result" data-result="غير طبيعي - يتطلب مراجعة طبية">
                                                    <i class="fas fa-exclamation-triangle"></i> غير طبيعي
                                                </button>
                                                <button type="button" class="btn btn-outline-info btn-sm quick-result" data-result="طبيعي مع ملاحظات طفيفة">
                                                    <i class="fas fa-info-circle"></i> طبيعي مع ملاحظات
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm quick-result" data-result="يحتاج لفحص إضافي - غير حاسم">
                                                    <i class="fas fa-redo"></i> يحتاج لفحص إضافي
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-save me-2"></i>حفظ نتائج التصوير
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resultTextarea = document.getElementById('findings');
    const quickResultButtons = document.querySelectorAll('.quick-result');
    const resultFileInput = document.getElementById('result_file');
    const resultFilePreview = document.getElementById('resultFilePreview');
    const resultFilePreviewImage = document.getElementById('resultFilePreviewImage');
    const resultFilePreviewMessage = document.getElementById('resultFilePreviewMessage');

    if (quickResultButtons.length > 0 && resultTextarea) {
        quickResultButtons.forEach(button => {
            button.addEventListener('click', function() {
                resultTextarea.value = this.dataset.result;
            });
        });
    }

    if (resultFileInput) {
        resultFileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) {
                resultFilePreview.style.display = 'none';
                resultFilePreviewImage.style.display = 'none';
                resultFilePreviewMessage.style.display = 'none';
                return;
            }

            const imageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/tiff', 'image/bmp', 'image/webp'];
            const isImage = imageTypes.includes(file.type.toLowerCase());

            resultFilePreview.style.display = '';
            resultFilePreviewImage.style.display = 'none';
            resultFilePreviewMessage.style.display = 'none';

            if (isImage) {
                const reader = new window.FileReader();
                reader.onload = function(event) {
                    resultFilePreviewImage.src = event.target.result;
                    resultFilePreviewImage.style.display = '';
                };
                reader.readAsDataURL(file);
            } else {
                resultFilePreviewMessage.textContent = 'تم اختيار ملف غير صورة، سيتم عرضه كملف عند الحفظ.';
                resultFilePreviewMessage.style.display = '';
            }
        });
    }
});
</script>
@endsection
