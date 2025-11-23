@extends('layouts.app')

@section('title', 'استيراد رموز ICD10')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient-primary text-white">
                    <div class="d-flex align-items-center">
                        <div class="bg-white bg-opacity-20 rounded-circle p-3 me-3">
                            <i class="fas fa-upload fa-2x text-white"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">استيراد رموز ICD10 من ملف Excel أو CSV</h4>
                            <small>قم برفع ملف Excel أو CSV يحتوي على رموز ICD10</small>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- رسائل النجاح والخطأ -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- تنبيهات مهمة -->
                    <div class="alert alert-info border-0 shadow-sm mb-4">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle fa-2x text-info"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="alert-heading mb-2">متطلبات الملف:</h6>
                                <ul class="mb-0 small">
                                    <li>يجب أن يكون الملف بصيغة <strong>.xlsx</strong> أو <strong>.xls</strong> أو <strong>.csv</strong></li>
                                    <li>الصف الأول يجب أن يحتوي على عناوين الأعمدة</li>
                                    <li>الأعمدة المطلوبة: <code>code</code>, <code>description</code>, <code>description_ar</code>, <code>category</code></li>
                                    <li>حجم الملف الأقصى: 50 ميجابايت</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- نموذج الرفع -->
                    <form action="{{ route('icd10.import.store') }}" method="POST" enctype="multipart/form-data" id="importForm">
                        @csrf

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4">
                                    <label for="file" class="form-label fw-bold">
                                        اختر ملف Excel أو CSV <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="file" class="form-control @error('file') is-invalid @enderror"
                                               id="file" name="file" accept=".xlsx,.xls,.csv" required>
                                        <label class="input-group-text" for="file">
                                            <i class="fas fa-file-excel text-success"></i>
                                        </label>
                                    </div>
                                    @error('file')
                                        <div class="invalid-feedback d-block">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- معاينة الملف -->
                        <div id="filePreview" class="mb-4" style="display: none;">
                            <div class="card border-success">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success fa-2x me-3"></i>
                                        <div>
                                            <h6 class="mb-1" id="fileName"></h6>
                                            <small class="text-muted" id="fileSize"></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- أزرار التحكم -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('icd10.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        العودة للقائمة
                                    </a>
                                    <div>
                                        <button type="button" class="btn btn-outline-warning me-2" onclick="clearForm()">
                                            <i class="fas fa-eraser me-1"></i>
                                            مسح
                                        </button>
                                        <button type="submit" class="btn btn-success btn-lg" id="submitBtn" disabled>
                                            <i class="fas fa-upload me-2"></i>
                                            استيراد البيانات
                                            <small class="d-block text-muted" style="font-size: 0.75rem;">(اختر ملف Excel أو CSV أولاً)</small>
                                        </button>
                                    </div>
                                </div>

                                <!-- خيارات متقدمة -->
                                <div class="mt-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="skipValidation" onchange="toggleValidation()">
                                        <label class="form-check-label small text-muted" for="skipValidation">
                                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                            تجاهل التحقق من نوع الملف (للمستخدمين المتقدمين فقط)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- مثال على تنسيق الملف -->
                    <div class="mt-5">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-table me-2"></i>
                            مثال على تنسيق الملف:
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-dark">
                                    <tr>
                                        <th>code</th>
                                        <th>description</th>
                                        <th>description_ar</th>
                                        <th>category</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>J00</td>
                                        <td>Acute nasopharyngitis (common cold)</td>
                                        <td>التهاب الأنف والحلق الحاد (الزكام الشائع)</td>
                                        <td>Respiratory</td>
                                    </tr>
                                    <tr>
                                        <td>J01</td>
                                        <td>Acute sinusitis</td>
                                        <td>التهاب الجيوب الأنفية الحاد</td>
                                        <td>Respiratory</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ asset('storage/icd10_template.csv') }}" class="btn btn-sm btn-outline-primary me-2" download>
                                <i class="fas fa-download me-1"></i>
                                تحميل قالب CSV
                            </a>
                            <a href="{{ asset('storage/icd10_sample.xlsx') }}" class="btn btn-sm btn-outline-primary" download>
                                <i class="fas fa-download me-1"></i>
                                تحميل ملف Excel عينة
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.input-group-text {
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

.card {
    border-radius: 15px;
    overflow: hidden;
}

.card-header {
    border-bottom: none;
    padding: 2rem;
}

.card-body {
    padding: 2rem;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
}

.alert {
    border-radius: 10px;
}

#filePreview {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.btn-pulse {
    animation: pulse 1s ease-in-out;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

@media (max-width: 768px) {
    .card-header {
        padding: 1.5rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }

    .btn-group-mobile {
        width: 100%;
    }
}
</style>
@endpush

@push('scripts')
<script defer>
(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('file');
        const submitBtn = document.getElementById('submitBtn');
        if (fileInput && submitBtn) {
            fileInput.addEventListener('change', function() {
                submitBtn.disabled = false;
            });
        }
    });
})();
</script>
@endpush