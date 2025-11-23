@extends('layouts.app')

@php
use App\Models\RadiologyType;

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
                <a href="{{ route('staff.surgery-radiology-tests.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>
                    العودة للقائمة
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <!-- معلومات العملية -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-procedures me-2"></i>معلومات العملية</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>المريض:</strong> {{ $test->surgery->patient->user->name }}</p>
                            <p><strong>الطبيب:</strong> د. {{ $test->surgery->doctor->user->name }}</p>
                            <p><strong>نوع العملية:</strong> {{ $test->surgery->surgery_type }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>تاريخ العملية:</strong> {{ $test->surgery->scheduled_date->format('Y-m-d') }}</p>
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
                </div>
            </div>

            <!-- معلومات التصوير -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-x-ray me-2"></i>معلومات التصوير المطلوب</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>نوع التصوير:</strong> {{ $test->radiologyType->name }}</p>
                            <p><strong>الكود:</strong> {{ $test->radiologyType->code ?? 'غير محدد' }}</p>
                            <p><strong>الفئة:</strong> {{ getRadiologyCategory($test->radiologyType->name) }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>الحالة:</strong>
                                <span class="badge bg-{{ $test->status_color }}">
                                    {{ $test->status_text }}
                                </span>
                            </p>
                            <p><strong>تاريخ الطلب:</strong> {{ $test->created_at->format('Y-m-d H:i') }}</p>
                            @if($test->completed_at)
                            <p><strong>تاريخ الإكمال:</strong> {{ $test->completed_at->format('Y-m-d H:i') }}</p>
                            @endif
                        </div>
                    </div>

                    @if($test->radiologyType->description)
                    <hr>
                    <h6>وصف التصوير:</h6>
                    <p>{{ $test->radiologyType->description }}</p>
                    @endif
                </div>
            </div>

            <!-- النتائج -->
            @if($test->result || $test->result_file)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>نتائج التصوير</h5>
                </div>
                <div class="card-body">
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
                        <a href="{{ asset('storage/' . $test->result_file) }}" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-file-download me-2"></i>عرض/تحميل الملف
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- تحديث النتائج -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-edit me-2"></i>تحديث النتائج</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.surgery-radiology-tests.update', $test) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="status" class="form-label">الحالة <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="pending" {{ $test->status == 'pending' ? 'selected' : '' }}>في الانتظار</option>
                                <option value="completed" {{ $test->status == 'completed' ? 'selected' : '' }}>مكتمل</option>
                                <option value="cancelled" {{ $test->status == 'cancelled' ? 'selected' : '' }}>ملغي</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- قسم التصوير الإشعاعي -->
                        <div class="radiology-results-section">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width: 50px;">#</th>
                                            <th>نوع التصوير</th>
                                            <th style="width: 150px;">الحالة</th>
                                            <th style="width: 100px;">النتيجة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="radiology-row" data-radiology="{{ $test->radiologyType->name }}">
                                            <td class="text-center">1</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="radiology-icon me-2">
                                                        <i class="{{ getRadiologyIcon($test->radiologyType->name) }}"></i>
                                                    </div>
                                                    <div>
                                                        <strong>{{ $test->radiologyType->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ getRadiologyCategory($test->radiologyType->name) }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    <span class="status-indicator" id="radiology-indicator-0">
                                                        <i class="fas fa-circle text-muted"></i>
                                                    </span>
                                                    <small class="status-text d-block mt-1" id="radiology-status-0">
                                                        قيد الإدخال
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    <span class="result-indicator" id="result-indicator-0">
                                                        <i class="fas fa-question-circle text-muted"></i>
                                                    </span>
                                                    <small class="result-text d-block mt-1" id="result-text-0">
                                                        غير محدد
                                                    </small>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- ملخص سريع للنتائج -->
                            <div class="results-summary mt-3 p-3 bg-light rounded">
                                <h6 class="mb-2">
                                    <i class="fas fa-chart-bar text-primary me-2"></i>
                                    ملخص التصوير
                                </h6>
                                <div class="summary-stats">
                                    <span class="stat-item">
                                        <i class="fas fa-check-circle text-success"></i>
                                        <span id="normal-radiology-count">0</span> طبيعي
                                    </span>
                                    <span class="stat-item">
                                        <i class="fas fa-exclamation-triangle text-warning"></i>
                                        <span id="abnormal-radiology-count">0</span> غير طبيعي
                                    </span>
                                    <span class="stat-item">
                                        <i class="fas fa-question-circle text-muted"></i>
                                        <span id="pending-radiology-count">1</span> غير مكتمل
                                    </span>
                                </div>
                            </div>

                            <!-- تقرير الطبيب الإشعاعي -->
                            <div class="mt-3">
                                <label for="result" class="form-label">
                                    <i class="fas fa-file-medical text-primary me-2"></i>
                                    تقرير الطبيب الإشعاعي
                                </label>
                                <textarea name="result" id="result" class="form-control @error('result') is-invalid @enderror" rows="6" placeholder="أدخل تقرير الطبيب الإشعاعي التفصيلي...">{{ old('result', $test->result) }}</textarea>
                                @error('result')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                <!-- أزرار النتائج السريعة -->
                                <div class="mt-2">
                                    <small class="text-muted d-block mb-2">نتائج سريعة:</small>
                                    <div class="btn-group-sm d-flex flex-wrap gap-1">
                                        <button type="button" class="btn btn-outline-success btn-sm quick-result" data-result="طبيعي - لا توجد تغييرات مرضية">
                                            <i class="fas fa-check"></i> طبيعي
                                        </button>
                                        <button type="button" class="btn btn-outline-warning btn-sm quick-result" data-result="غير طبيعي - يتطلب مراجعة طبية">
                                            <i class="fas fa-exclamation-triangle"></i> غير طبيعي
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-sm quick-result" data-result="طبيعي مع ملاحظات طفيفة">
                                            <i class="fas fa-info-circle"></i> طبيعي مع ملاحظات
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="result_file" class="form-label">
                                    <i class="fas fa-upload text-primary me-2"></i>
                                    ملف التصوير (DICOM, PDF, أو صور)
                                </label>
                                <input type="file" name="result_file" id="result_file" class="form-control @error('result_file') is-invalid @enderror" accept=".dcm,.dicom,.pdf,.jpg,.jpeg,.png,.tiff">
                                @error('result_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if($test->result_file)
                                <div class="mt-2">
                                    <small class="text-muted">الملف الحالي: <a href="{{ asset('storage/' . $test->result_file) }}" target="_blank">{{ basename($test->result_file) }}</a></small>
                                </div>
                                @endif
                            </div>

                            <!-- قسم المعلومات المرجعية -->
                            <div class="mt-4">
                                <details class="mb-3">
                                    <summary class="text-primary fw-bold cursor-pointer">
                                        <i class="fas fa-info-circle me-2"></i>
                                        إرشادات التصوير الإشعاعي (معلومات فقط)
                                    </summary>
                                    <div class="mt-3 p-3 bg-light rounded">
                                        <small class="text-muted">
                                            <strong>ملاحظة:</strong> هذه إرشادات عامة للتصوير الإشعاعي. يجب استشارة الطبيب الإشعاعي للتقييم الدقيق.
                                        </small>
                                        <ul class="mt-2 mb-0 small">
                                            <li><strong>الأشعة العادية:</strong> للكشف عن الكسور والالتهابات</li>
                                            <li><strong>التصوير المقطعي (CT):</strong> للكشف عن الأورام والنزيف</li>
                                            <li><strong>الرنين المغناطيسي (MRI):</strong> للكشف عن مشاكل الأنسجة الرخوة</li>
                                            <li><strong>الموجات فوق الصوتية:</strong> للكشف عن السوائل والأعضاء الرخوة</li>
                                            <li><strong>تصوير القلب:</strong> لتقييم وظائف القلب والأوعية</li>
                                        </ul>
                                    </div>
                                </details>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>حفظ التحديثات
                        </button>
                    </form>
                </div>
            </div>

            <!-- معلومات إضافية -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات إضافية</h6>
                </div>
                <div class="card-body">
                    <p><strong>رقم الطلب:</strong> {{ $test->id }}</p>
                    <p><strong>تاريخ الإنشاء:</strong> {{ $test->created_at->format('Y-m-d H:i') }}</p>
                    <p><strong>آخر تحديث:</strong> {{ $test->updated_at->format('Y-m-d H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function analyzeRadiologyResult(textarea) {
    const result = textarea.value.toLowerCase();
    const radiologyRow = document.querySelector('.radiology-row');
    const indicator = radiologyRow.querySelector('.status-indicator i');
    const statusText = radiologyRow.querySelector('.status-text');
    const resultIndicator = radiologyRow.querySelector('.result-indicator i');
    const resultText = radiologyRow.querySelector('.result-text');

    if (result.trim() === '') {
        indicator.className = 'fas fa-circle text-muted';
        statusText.innerHTML = '<i class="fas fa-info-circle"></i> قيد الإدخال';
        resultIndicator.className = 'fas fa-question-circle text-muted';
        resultText.innerHTML = 'غير محدد';
        radiologyRow.classList.remove('result-normal', 'result-abnormal');
        updateRadiologySummary();
        return;
    }

    // تحليل النتيجة
    let resultType = 'normal';
    let statusMessage = '<i class="fas fa-info-circle text-info"></i> تم إدخال التقرير';
    let indicatorClass = 'fas fa-circle text-info';
    let resultIcon = 'fas fa-question-circle text-muted';
    let resultMessage = 'غير محدد';

    if (result.includes('طبيعي') || result.includes('normal') || result.includes('لا توجد تغييرات') || result.includes('no abnormalities')) {
        resultType = 'normal';
        resultIcon = 'fas fa-check-circle text-success';
        resultMessage = '<i class="fas fa-check text-success"></i> طبيعي';
        statusMessage = '<i class="fas fa-check-circle text-success"></i> تقرير مكتمل - طبيعي';
        indicatorClass = 'fas fa-check-circle text-success';
    } else if (result.includes('غير طبيعي') || result.includes('abnormal') || result.includes('تغييرات') || result.includes('changes') ||
               result.includes('ورم') || result.includes('tumor') || result.includes('كسر') || result.includes('fracture') ||
               result.includes('التهاب') || result.includes('inflammation')) {
        resultType = 'abnormal';
        resultIcon = 'fas fa-exclamation-triangle text-warning';
        resultMessage = '<i class="fas fa-exclamation-triangle text-warning"></i> غير طبيعي';
        statusMessage = '<i class="fas fa-exclamation-triangle text-warning"></i> تقرير مكتمل - يتطلب مراجعة';
        indicatorClass = 'fas fa-exclamation-triangle text-warning';
    } else {
        resultMessage = '<i class="fas fa-info-circle text-info"></i> تم إدخال التقرير';
        statusMessage = '<i class="fas fa-info-circle text-info"></i> تقرير مكتمل - راجع التفاصيل';
        indicatorClass = 'fas fa-info-circle text-info';
    }

    indicator.className = indicatorClass;
    statusText.innerHTML = statusMessage;
    resultIndicator.className = resultIcon;
    resultText.innerHTML = resultMessage;
    radiologyRow.classList.remove('result-normal', 'result-abnormal');
    radiologyRow.classList.add('result-' + resultType);

    updateRadiologySummary();
}

function updateRadiologySummary() {
    const radiologyRows = document.querySelectorAll('.radiology-row');
    let normal = 0, abnormal = 0, pending = 0;

    radiologyRows.forEach(row => {
        if (row.classList.contains('result-normal')) {
            normal++;
        } else if (row.classList.contains('result-abnormal')) {
            abnormal++;
        } else {
            pending++;
        }
    });

    document.getElementById('normal-radiology-count').textContent = normal;
    document.getElementById('abnormal-radiology-count').textContent = abnormal;
    document.getElementById('pending-radiology-count').textContent = pending;
}

document.addEventListener('DOMContentLoaded', function() {
    const resultTextarea = document.getElementById('result');
    const quickResultButtons = document.querySelectorAll('.quick-result');

    // تحليل النتيجة عند الكتابة
    resultTextarea.addEventListener('input', function() {
        analyzeRadiologyResult(this);
    });

    resultTextarea.addEventListener('blur', function() {
        analyzeRadiologyResult(this);
    });

    // أزرار النتائج السريعة
    quickResultButtons.forEach(button => {
        button.addEventListener('click', function() {
            resultTextarea.value = this.dataset.result;
            analyzeRadiologyResult(resultTextarea);
        });
    });

    // تحليل أولي
    analyzeRadiologyResult(resultTextarea);

    // تفعيل tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<style>
.radiology-results-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 20px;
    margin: 15px 0;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.results-summary {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-top: 20px;
}

.summary-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    justify-content: center;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    font-weight: 500;
    background: white;
    padding: 8px 12px;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.table {
    margin-bottom: 0;
    --bs-table-hover-bg: rgba(0, 123, 255, 0.05);
}

.table > :not(caption) > * > * {
    padding: 0.75rem;
    vertical-align: middle;
}

.table thead th {
    background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
    font-weight: 600;
    text-align: center;
    border-bottom: 2px solid #dee2e6;
}

.radiology-row {
    transition: all 0.3s ease;
}

.radiology-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.radiology-row .radiology-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0, 123, 255, 0.1);
    transition: all 0.3s ease;
}

.radiology-row:hover .radiology-icon {
    transform: scale(1.1);
    background: rgba(0, 123, 255, 0.2);
}

.status-indicator, .result-indicator {
    font-size: 1.2rem;
    display: inline-block;
    transition: all 0.3s ease;
}

.status-text, .result-text {
    font-size: 0.75rem;
    color: #6c757d;
}

.radiology-row.result-normal {
    background-color: rgba(40, 167, 69, 0.05);
}

.radiology-row.result-abnormal {
    background-color: rgba(255, 193, 7, 0.05);
}

.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
}

.btn-outline-success:hover, .btn-outline-warning:hover, .btn-outline-info:hover {
    transform: translateY(-1px);
}

.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
    border: none;
    font-weight: 600;
}

.quick-result {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    margin: 0.125rem;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.quick-result:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
</style>
@endsection