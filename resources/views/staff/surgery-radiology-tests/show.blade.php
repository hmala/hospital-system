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
    
    .category-header {
        font-weight: bold;
        background-color: #e9ecef !important;
    }
    
    .subcategory-header {
        background-color: #f8f9fa !important;
    }
    
    code {
        background-color: #e7f1ff;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 0.9em;
    }
    
    .card {
        border: none;
        border-radius: 10px;
    }
    
    .card-header {
        border-radius: 10px 10px 0 0 !important;
    }
</style>
@endsection

@php
use App\Models\RadiologyType;
use Illuminate\Support\Facades\Storage;

// الحصول على جميع أنواع التصوير النشطة للاستخدام في JavaScript
$radiologyTypes = RadiologyType::active()->get()->keyBy('name');

function getRadiologyIcon($radiologyName) {
    $name = strtolower($radiologyName);

    if (strpos($name, 'أشعة') !== false || strpos($name, 'x-ray') !== false) {
        return 'fas fa-x-ray text-primary';
    } elseif (strpos($name, 'رنين') !== false || strpos($name, 'mri') !== false) {
        return 'fas fa-magnet text-info';
    } elseif (strpos($name, 'طبقي') !== false || strpos($name, 'ct') !== false) {
        return 'fas fa-circle-notch text-warning';
    } elseif (strpos($name, 'موجات') !== false || strpos($name, 'ultrasound') !== false) {
        return 'fas fa-wave-square text-success';
    } elseif (strpos($name, 'قلب') !== false || strpos($name, 'cardiac') !== false) {
        return 'fas fa-heartbeat text-danger';
    } elseif (strpos($name, 'عظام') !== false || strpos($name, 'bone') !== false) {
        return 'fas fa-bone text-secondary';
    } elseif (strpos($name, 'صدر') !== false || strpos($name, 'chest') !== false) {
        return 'fas fa-lungs text-info';
    } elseif (strpos($name, 'مخ') !== false || strpos($name, 'brain') !== false) {
        return 'fas fa-brain text-purple';
    } elseif (strpos($name, 'بطن') !== false || strpos($name, 'abdomen') !== false) {
        return 'fas fa-user-md text-success';
    } elseif (strpos($name, 'مفاصل') !== false || strpos($name, 'joint') !== false) {
        return 'fas fa-link text-warning';
    } elseif (strpos($name, 'أوعية') !== false || strpos($name, 'vascular') !== false) {
        return 'fas fa-tint text-danger';
    } else {
        return 'fas fa-camera text-primary';
    }
}

function getRadiologyCategory($radiologyName) {
    $name = strtolower($radiologyName);

    if (strpos($name, 'أشعة') !== false || strpos($name, 'x-ray') !== false) {
        return 'أشعة عادية';
    } elseif (strpos($name, 'رنين') !== false || strpos($name, 'mri') !== false) {
        return 'الرنين المغناطيسي';
    } elseif (strpos($name, 'طبقي') !== false || strpos($name, 'ct') !== false) {
        return 'التصوير المقطعي';
    } elseif (strpos($name, 'موجات') !== false || strpos($name, 'ultrasound') !== false) {
        return 'الموجات فوق الصوتية';
    } elseif (strpos($name, 'قلب') !== false || strpos($name, 'cardiac') !== false) {
        return 'تصوير القلب';
    } elseif (strpos($name, 'عظام') !== false || strpos($name, 'bone') !== false) {
        return 'عظام ومفاصل';
    } elseif (strpos($name, 'صدر') !== false || strpos($name, 'chest') !== false) {
        return 'صدر وتنفس';
    } elseif (strpos($name, 'مخ') !== false || strpos($name, 'brain') !== false) {
        return 'جهاز عصبي';
    } elseif (strpos($name, 'بطن') !== false || strpos($name, 'abdomen') !== false) {
        return 'بطن وأحشاء';
    } elseif (strpos($name, 'أوعية') !== false || strpos($name, 'vascular') !== false) {
        return 'أوعية دموية';
    } else {
        return 'تصوير طبي';
    }
}
@endphp

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-x-ray me-2"></i>
                    تفاصيل طلب التصوير الإشعاعي للعملية
                </h2>
                <div class="d-flex gap-2">
                    @if($test->status === 'completed')
                    <a href="{{ route('staff.surgery-radiology-tests.print', $test) }}" target="_blank" class="btn btn-success">
                        <i class="fas fa-print me-1"></i>
                        طباعة
                    </a>
                    @endif
                    <a href="{{ route('staff.surgery-radiology-tests.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>

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
            <i class="fas fa-exclamation-circle me-2"></i>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- معلومات العملية -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-procedures me-2"></i>معلومات العملية</h5>
                </div>
                <div class="card-body">
                    @if($test->surgery)
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>المريض:</strong> {{ optional($test->surgery->patient?->user)->name ?? 'غير معروف' }}</p>
                            <p><strong>الطبيب:</strong> د. {{ optional($test->surgery->doctor?->user)->name ?? 'غير محدد' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>نوع العملية:</strong> {{ $test->surgery->surgery_type }}</p>
                            <p><strong>تاريخ العملية:</strong> {{ $test->surgery->scheduled_date ? $test->surgery->scheduled_date->format('Y-m-d') : 'غير محدد' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>وقت العملية:</strong> {{ $test->surgery->scheduled_time }}</p>
                            <p><strong>الحالة:</strong>
                                @if($test->surgery->status == 'scheduled')
                                    <span class="badge bg-secondary">مجدولة</span>
                                @elseif($test->surgery->status == 'in_progress')
                                    <span class="badge bg-warning">جارية</span>
                                @elseif($test->surgery->status == 'completed')
                                    <span class="badge bg-success">مكتملة</span>
                                @else
                                    <span class="badge bg-danger">ملغاة</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        معلومات العملية غير متوفرة
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="{{ $test->radiologyType ? 'col-lg-8' : 'col-12' }}">
            @if(!$test->radiologyType)
            <!-- اختيار نوع الأشعة -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-search me-2"></i>اختر نوع الأشعة المطلوب</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.surgery-radiology-tests.update', $test) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- حقل مخفي للحالة -->
                        <input type="hidden" name="status" value="pending">
                        
                        <div class="mb-3">
                            <input type="text" class="form-control form-control-lg" id="surgeryRadiologySearchInput" placeholder="🔍 ابحث عن نوع الأشعة بالاسم، الكود أو الفئة...">
                        </div>

                        @php
                            $allRadiologyTypes = \App\Models\RadiologyType::where('is_active', true)
                                ->orderBy('main_category')
                                ->orderBy('subcategory')
                                ->orderBy('name')
                                ->get()
                                ->groupBy('main_category');
                        @endphp

                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-hover table-bordered align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 60px;" class="text-center">اختر</th>
                                        <th>نوع الأشعة</th>
                                        <th style="width: 120px;">الكود</th>
                                        <th style="width: 200px;">الفئة الفرعية</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allRadiologyTypes as $category => $types)
                                        <tr class="table-secondary category-header">
                                            <td colspan="4">
                                                <strong><i class="fas fa-folder-open me-2"></i>{{ $category }}</strong>
                                            </td>
                                        </tr>

                                        @foreach($types->groupBy('subcategory') as $subcategory => $subTypes)
                                            @if($subcategory)
                                                <tr class="table-active subcategory-header">
                                                    <td colspan="4" class="ps-4">
                                                        <small class="text-secondary">
                                                            <i class="fas fa-caret-left me-1"></i>{{ $subcategory }}
                                                        </small>
                                                    </td>
                                                </tr>
                                            @endif

                                            @foreach($subTypes as $radiologyTypeItem)
                                                <tr class="radiology-type-item" 
                                                    data-name="{{ strtolower($radiologyTypeItem->name) }}"
                                                    data-code="{{ strtolower($radiologyTypeItem->code ?? '') }}"
                                                    data-category="{{ strtolower($category) }}"
                                                    data-subcategory="{{ strtolower($subcategory ?? '') }}">
                                                    <td class="text-center">
                                                        <input class="form-check-input surgery-radiology-type-radio" 
                                                               type="radio" 
                                                               name="radiology_type_id" 
                                                               value="{{ $radiologyTypeItem->id }}" 
                                                               id="surgery_radiology_{{ $radiologyTypeItem->id }}">
                                                    </td>
                                                    <td>
                                                        <label for="surgery_radiology_{{ $radiologyTypeItem->id }}" class="mb-0 w-100" style="cursor: pointer;">
                                                            <i class="{{ getRadiologyIcon($radiologyTypeItem->name) }} me-2"></i>
                                                            {{ $radiologyTypeItem->name }}
                                                        </label>
                                                    </td>
                                                    <td>
                                                        <code class="text-primary">{{ $radiologyTypeItem->code ?? '-' }}</code>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">{{ $subcategory ?? '-' }}</small>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @error('radiology_type_id')
                            <div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>
                        @enderror

                        <div class="alert alert-info mt-3 mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            تم اختيار <strong><span id="selectedRadiologyCount">0</span></strong> نوع أشعة
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">
                            <i class="fas fa-check me-2"></i>تأكيد اختيار نوع الأشعة
                        </button>
                    </form>
                </div>
            </div>
            @else

            <!-- النتائج المحفوظة -->
            @if($test->result || $test->result_file)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>نتائج التصوير المحفوظة</h5>
                </div>
                <div class="card-body">
                    @php
                        $resultFileUrl = $test->result_file ? Storage::disk('public')->url($test->result_file) : null;
                        $resultFileExt = $test->result_file ? strtolower(pathinfo($test->result_file, PATHINFO_EXTENSION)) : null;
                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'tiff', 'bmp', 'webp'];
                        $hasImagePreview = $test->result_file && in_array($resultFileExt, $imageExtensions);
                    @endphp

                    @if($hasImagePreview)
                    <div class="row align-items-start g-3">
                        <div class="col-md-4 col-12">
                            <div class="text-center h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <h6>ملف التصوير:</h6>
                                    <img src="{{ $resultFileUrl }}" alt="ملف التصوير" class="img-fluid rounded border" style="max-height: 420px; width: 100%; object-fit: contain;">
                                </div>
                                <div class="mt-3 text-center">
                                    <a href="{{ $resultFileUrl }}" target="_blank" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-expand me-2"></i>فتح الصورة بحجم كامل
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8 col-12">
                            @if($test->result)
                            <h6>تقرير الطبيب الإشعاعي:</h6>
                            <div class="bg-light p-3 rounded h-100">
                                {{ $test->result }}
                            </div>
                            @endif
                        </div>
                    </div>
                    @else
                        @if($test->result)
                        <div class="mb-3">
                            <h6>تقرير الطبيب الإشعاعي:</h6>
                            <div class="bg-light p-3 rounded">
                                {{ $test->result }}
                            </div>
                        </div>
                        @endif

                        @if($test->result_file)
                        <div class="mb-3">
                            <h6>ملف التصوير:</h6>
                            <a href="{{ $resultFileUrl }}" target="_blank" class="btn btn-outline-primary">
                                <i class="fas fa-file-download me-2"></i>عرض/تحميل الملف
                            </a>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
            @endif

            <!-- تحديث النتائج -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-edit me-2"></i>تحديث نتائج التصوير</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.surgery-radiology-tests.update', $test) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="radiology-results-section">
                            <h6 class="mb-3">
                                <i class="fas fa-file-medical text-primary me-2"></i>
                                معلومات التصوير
                            </h6>
                            
                            <div class="table-responsive mb-3">
                                <table class="table table-hover table-bordered align-middle">
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
                                                <div class="d-flex align-items-center">
                                                    <div class="radiology-icon me-3">
                                                        <i class="{{ getRadiologyIcon($test->radiologyType->name) }} fa-2x"></i>
                                                    </div>
                                                    <div>
                                                        <strong>{{ $test->radiologyType->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="fas fa-tag me-1"></i>{{ $test->radiologyType->main_category }}
                                                            @if($test->radiologyType->subcategory)
                                                                → {{ $test->radiologyType->subcategory }}
                                                            @endif
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <select name="status" id="status" class="form-select form-select-sm @error('status') is-invalid @enderror" required>
                                                    <option value="pending" {{ $test->status == 'pending' ? 'selected' : '' }}>
                                                        في الانتظار
                                                    </option>
                                                    <option value="completed" {{ $test->status == 'completed' ? 'selected' : '' }}>
                                                        مكتمل
                                                    </option>
                                                    <option value="cancelled" {{ $test->status == 'cancelled' ? 'selected' : '' }}>
                                                        ملغي
                                                    </option>
                                                </select>
                                                @error('status')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-4 col-12">
                                    <div class="card h-100 border-secondary">
                                        <div class="card-body">
                                            <h6 class="card-title fw-bold mb-3"><i class="fas fa-upload text-primary me-2"></i>ملف التصوير</h6>

                                            <div class="mb-3">
                                                <input type="file" name="result_file" id="result_file" class="form-control @error('result_file') is-invalid @enderror" accept=".dcm,.dicom,.pdf,.jpg,.jpeg,.png,.tiff">
                                                <small class="form-text text-muted">
                                                    الصيغ المدعومة: DICOM (.dcm), PDF (.pdf), صور (.jpg, .jpeg, .png, .tiff) - الحد الأقصى 10 ميجا
                                                </small>
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

                                            @if($test->result_file)
                                            <div class="mt-2">
                                                <div class="alert alert-info py-2 mb-0">
                                                    <i class="fas fa-file-alt me-1"></i>
                                                    <small>الملف الحالي: <a href="{{ Storage::disk('public')->url($test->result_file) }}" target="_blank" class="alert-link">{{ basename($test->result_file) }}</a></small>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8 col-12">
                                    <div class="card h-100 border-secondary">
                                        <div class="card-body d-flex flex-column">
                                            <h6 class="card-title fw-bold mb-3"><i class="fas fa-notes-medical text-primary me-2"></i>تقرير الطبيب الإشعاعي</h6>

                                            <div class="mb-3 flex-grow-1">
                                                <textarea name="result" id="result" class="form-control @error('result') is-invalid @enderror" rows="14" placeholder="أدخل تقرير الطبيب الإشعاعي بالتفصيل...">{{ old('result', $test->result) }}</textarea>
                                                @error('result')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mt-3">
                                                <small class="text-muted d-block mb-2">
                                                    <i class="fas fa-bolt me-1"></i>نتائج سريعة:
                                                </small>
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

                            <!-- قسم المعلومات المرجعية -->
                            <div class="mt-3">
                                <details class="mb-3">
                                    <summary class="text-primary fw-bold cursor-pointer">
                                        <i class="fas fa-info-circle me-2"></i>
                                        إرشادات التصوير الإشعاعي
                                    </summary>
                                    <div class="mt-3 p-3 bg-light rounded">
                                        <small class="text-muted">
                                            <strong>ملاحظة:</strong> هذه إرشادات عامة للتصوير الإشعاعي. يجب استشارة الطبيب الإشعاعي للتقييم الدقيق.
                                        </small>
                                        <ul class="mt-2 mb-0 small">
                                            <li><strong>الأشعة العادية (X-Ray):</strong> للكشف عن الكسور والالتهابات</li>
                                            <li><strong>التصوير المقطعي (CT):</strong> للكشف عن الأورام والنزيف</li>
                                            <li><strong>الرنين المغناطيسي (MRI):</strong> للكشف عن مشاكل الأنسجة الرخوة</li>
                                            <li><strong>الموجات فوق الصوتية:</strong> للكشف عن السوائل والأعضاء الرخوة</li>
                                            <li><strong>تصوير القلب:</strong> لتقييم وظائف القلب والأوعية الدموية</li>
                                        </ul>
                                    </div>
                                </details>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">
                            <i class="fas fa-save me-2"></i>حفظ نتائج التصوير
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resultTextarea = document.getElementById('result');
    const quickResultButtons = document.querySelectorAll('.quick-result');
    const radiologySearchInput = document.getElementById('surgeryRadiologySearchInput');
    const selectedRadiologyCount = document.getElementById('selectedRadiologyCount');
    const resultFileInput = document.getElementById('result_file');
    const resultFilePreview = document.getElementById('resultFilePreview');
    const resultFilePreviewImage = document.getElementById('resultFilePreviewImage');
    const resultFilePreviewMessage = document.getElementById('resultFilePreviewMessage');

    // أزرار النتائج السريعة
    if (quickResultButtons.length > 0 && resultTextarea) {
        quickResultButtons.forEach(button => {
            button.addEventListener('click', function() {
                resultTextarea.value = this.dataset.result;
            });
        });
    }

    // معاينة الملف عند اختيار صورة
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

    // البحث في أنواع الأشعة
    if (radiologySearchInput) {
        radiologySearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const radiologyRows = document.querySelectorAll('tr.radiology-type-item');
            const categoryHeaders = document.querySelectorAll('tr.category-header');
            const subcategoryHeaders = document.querySelectorAll('tr.subcategory-header');

            // إخفاء/إظهار صفوف الأشعة
            radiologyRows.forEach(function(row) {
                const name = row.dataset.name || '';
                const code = row.dataset.code || '';
                const category = row.dataset.category || '';
                const subcategory = row.dataset.subcategory || '';
                
                const matches = name.includes(searchTerm) || 
                               code.includes(searchTerm) || 
                               category.includes(searchTerm) || 
                               subcategory.includes(searchTerm);
                
                row.style.display = matches ? '' : 'none';
            });

            // إخفاء العناوين الفارغة
            categoryHeaders.forEach(function(header) {
                const nextRows = [];
                let currentRow = header.nextElementSibling;
                
                while (currentRow && !currentRow.classList.contains('category-header')) {
                    if (currentRow.classList.contains('radiology-type-item') && currentRow.style.display !== 'none') {
                        nextRows.push(currentRow);
                    }
                    currentRow = currentRow.nextElementSibling;
                }
                
                header.style.display = nextRows.length > 0 ? '' : 'none';
            });

            subcategoryHeaders.forEach(function(header) {
                const nextRows = [];
                let currentRow = header.nextElementSibling;
                
                while (currentRow && !currentRow.classList.contains('subcategory-header') && !currentRow.classList.contains('category-header')) {
                    if (currentRow.classList.contains('radiology-type-item') && currentRow.style.display !== 'none') {
                        nextRows.push(currentRow);
                    }
                    currentRow = currentRow.nextElementSibling;
                }
                
                header.style.display = nextRows.length > 0 ? '' : 'none';
            });
        });
    }

    // تحديث عداد الاختيارات
    function updateRadiologySelectionCount() {
        if (!selectedRadiologyCount) return;
        const selected = document.querySelectorAll('.surgery-radiology-type-radio:checked').length;
        selectedRadiologyCount.textContent = selected;
    }

    document.querySelectorAll('.surgery-radiology-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            updateRadiologySelectionCount();
        });
    });

    updateRadiologySelectionCount();

    // تفعيل tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection