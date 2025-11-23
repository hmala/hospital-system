@extends('layouts.app')

@section('title', 'تفاصيل رمز ICD10')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        تفاصيل رمز ICD10
                    </h4>
                    <div>
                        <a href="{{ route('icd10.edit', $icd10) }}" class="btn btn-light btn-sm">
                            <i class="fas fa-edit me-1"></i>
                            تعديل
                        </a>
                        <a href="{{ route('icd10.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>
                            العودة للقائمة
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- معلومات الرمز الأساسية -->
                            <div class="mb-4">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-hashtag me-2"></i>
                                    الرمز: {{ $icd10->code }}
                                </h5>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">الفئة:</label>
                                            @if($icd10->category)
                                                <span class="badge bg-secondary fs-6">{{ $icd10->category }}</span>
                                            @else
                                                <span class="text-muted">غير محدد</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">تاريخ الإنشاء:</label>
                                            <span class="text-muted">{{ $icd10->created_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- الوصف الإنجليزي -->
                            <div class="mb-4">
                                <h6 class="text-success mb-2">
                                    <i class="fas fa-language me-2"></i>
                                    الوصف (إنجليزي)
                                </h6>
                                <div class="border rounded p-3 bg-light">
                                    <p class="mb-0">{{ $icd10->description }}</p>
                                </div>
                            </div>

                            <!-- الوصف العربي -->
                            @if($icd10->description_ar)
                                <div class="mb-4">
                                    <h6 class="text-primary mb-2">
                                        <i class="fas fa-language me-2"></i>
                                        الوصف (عربي)
                                    </h6>
                                    <div class="border rounded p-3 bg-light">
                                        <p class="mb-0 text-end">{{ $icd10->description_ar }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <!-- معلومات إضافية -->
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-info me-2"></i>
                                        معلومات إضافية
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <small class="text-muted">آخر تحديث:</small>
                                        <div>{{ $icd10->updated_at->format('d/m/Y H:i') }}</div>
                                    </div>

                                    <div class="mb-2">
                                        <small class="text-muted">معرف السجل:</small>
                                        <div><code>{{ $icd10->id }}</code></div>
                                    </div>

                                    @if($icd10->category)
                                        <div class="mb-2">
                                            <small class="text-muted">الفئة:</small>
                                            <div>
                                                <span class="badge bg-secondary">{{ $icd10->category }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- إجراءات سريعة -->
                            <div class="card border-warning mt-3">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">
                                        <i class="fas fa-bolt me-2"></i>
                                        إجراءات سريعة
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('icd10.edit', $icd10) }}" class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-edit me-1"></i>
                                            تعديل الرمز
                                        </a>
                                        <button class="btn btn-outline-info btn-sm"
                                                onclick="copyToClipboard('{{ $icd10->code }}')">
                                            <i class="fas fa-copy me-1"></i>
                                            نسخ الرمز
                                        </button>
                                        <button class="btn btn-outline-success btn-sm"
                                                onclick="copyToClipboard('{{ $icd10->description }}')">
                                            <i class="fas fa-copy me-1"></i>
                                            نسخ الوصف الإنجليزي
                                        </button>
                                        @if($icd10->description_ar)
                                            <button class="btn btn-outline-primary btn-sm"
                                                    onclick="copyToClipboard('{{ $icd10->description_ar }}')">
                                                <i class="fas fa-copy me-1"></i>
                                                نسخ الوصف العربي
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // إظهار رسالة نجاح مؤقتة
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed';
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check me-2"></i>
                    تم نسخ النص بنجاح!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        document.body.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        setTimeout(() => {
            document.body.removeChild(toast);
        }, 3000);
    });
}
</script>
@endpush

@push('styles')
<style>
.card {
    border: none;
    border-radius: 0.5rem;
}

.card-header {
    border-radius: 0.5rem 0.5rem 0 0 !important;
}

.badge {
    font-size: 0.875em;
}

.bg-light {
    background-color: #f8f9fa !important;
}

.btn-sm {
    font-size: 0.875rem;
}
</style>
@endpush