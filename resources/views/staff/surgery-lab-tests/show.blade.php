@extends('layouts.app')

@php
use App\Models\LabTest;

// الحصول على جميع التحاليل النشطة للاستخدام في JavaScript
$labTests = LabTest::active()->get()->keyBy('name');

function getTestIcon($testName) {
    $name = strtolower($testName);

    if (strpos($name, 'سكر') !== false || strpos($name, 'glucose') !== false) {
        return 'fas fa-tint text-danger';
    } elseif (strpos($name, 'ضغط') !== false || strpos($name, 'pressure') !== false) {
        return 'fas fa-heartbeat text-danger';
    } elseif (strpos($name, 'كوليسترول') !== false || strpos($name, 'cholesterol') !== false) {
        return 'fas fa-oil-can text-warning';
    } elseif (strpos($name, 'دم') !== false || strpos($name, 'blood') !== false) {
        return 'fas fa-tint text-danger';
    } elseif (strpos($name, 'بول') !== false || strpos($name, 'urine') !== false) {
        return 'fas fa-flask text-warning';
    } elseif (strpos($name, 'كبد') !== false || strpos($name, 'liver') !== false) {
        return 'fas fa-lungs text-success';
    } elseif (strpos($name, 'كلى') !== false || strpos($name, 'kidney') !== false) {
        return 'fas fa-kidney text-info';
    } elseif (strpos($name, 'هرمون') !== false || strpos($name, 'hormone') !== false) {
        return 'fas fa-atom text-purple';
    } elseif (strpos($name, 'فيروس') !== false || strpos($name, 'virus') !== false) {
        return 'fas fa-virus text-danger';
    } elseif (strpos($name, 'بكتيريا') !== false || strpos($name, 'bacteria') !== false) {
        return 'fas fa-bacterium text-success';
    } else {
        return 'fas fa-vial text-primary';
    }
}

function getTestUnit($testName, $labTests) {
    // البحث في قاعدة البيانات أولاً
    if (isset($labTests[$testName]) && !empty($labTests[$testName]->unit)) {
        return $labTests[$testName]->unit;
    }

    // الاحتياطي للفحوصات غير الموجودة في قاعدة البيانات
    $name = strtolower($testName);

    if (strpos($name, 'سكر') !== false || strpos($name, 'glucose') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'ضغط') !== false || strpos($name, 'pressure') !== false) {
        return 'mmHg';
    } elseif (strpos($name, 'كوليسترول') !== false || strpos($name, 'cholesterol') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'بيليروبين') !== false || strpos($name, 'bilirubin') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'كرياتينين') !== false || strpos($name, 'creatinine') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'يوريا') !== false || strpos($name, 'urea') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'sgot') !== false || strpos($name, 'ast') !== false) {
        return 'U/L';
    } elseif (strpos($name, 'sgpt') !== false || strpos($name, 'alt') !== false) {
        return 'U/L';
    } elseif (strpos($name, 'الصفائح') !== false || strpos($name, 'platelets') !== false) {
        return '/µL';
    } elseif (strpos($name, 'الهيموغلوبين') !== false || strpos($name, 'hemoglobin') !== false) {
        return 'g/dL';
    } elseif (strpos($name, 'الكرات البيضاء') !== false || strpos($name, 'wbc') !== false) {
        return '/µL';
    } elseif (strpos($name, 'الكرات الحمراء') !== false || strpos($name, 'rbc') !== false) {
        return 'million/µL';
    } elseif (strpos($name, 'الهيماتوكريت') !== false || strpos($name, 'hematocrit') !== false) {
        return '%';
    } elseif (strpos($name, 'هرمون') !== false || strpos($name, 'hormone') !== false) {
        return 'mIU/mL';
    } elseif (strpos($name, 'فيتامين') !== false || strpos($name, 'vitamin') !== false) {
        return 'ng/mL';
    } elseif (strpos($name, 'حديد') !== false || strpos($name, 'iron') !== false) {
        return 'µg/dL';
    } elseif (strpos($name, 'كالسيوم') !== false || strpos($name, 'calcium') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'صوديوم') !== false || strpos($name, 'sodium') !== false) {
        return 'mEq/L';
    } elseif (strpos($name, 'بوتاسيوم') !== false || strpos($name, 'potassium') !== false) {
        return 'mEq/L';
    } else {
        return '';
    }
}
@endphp

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-flask me-2"></i>
                    تفاصيل طلب التحليل المخبري للعملية
                </h2>
                <a href="{{ route('staff.surgery-lab-tests.index') }}" class="btn btn-secondary">
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

            <!-- معلومات التحليل -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-vial me-2"></i>معلومات التحليل المطلوب</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>اسم التحليل:</strong> {{ $test->labTest->name }}</p>
                            <p><strong>الفئة:</strong> {{ $test->labTest->category ?? 'غير محدد' }}</p>
                            <p><strong>الوحدة:</strong> {{ $test->labTest->unit ?? 'غير محدد' }}</p>
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

                    @if($test->labTest->description)
                    <hr>
                    <h6>وصف التحليل:</h6>
                    <p>{{ $test->labTest->description }}</p>
                    @endif
                </div>
            </div>

            <!-- النتائج -->
            @if($test->result || $test->result_file)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>نتائج التحليل</h5>
                </div>
                <div class="card-body">
                    @if($test->result)
                    <div class="mb-3">
                        <h6>النتيجة:</h6>
                        <div class="bg-light p-3 rounded">
                            {{ $test->result }}
                        </div>
                    </div>
                    @endif

                    @if($test->result_file)
                    <div class="mb-3">
                        <h6>ملف النتيجة:</h6>
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
                    <form action="{{ route('staff.surgery-lab-tests.update', $test) }}" method="POST" enctype="multipart/form-data">
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

                        <!-- جدول إدخال نتائج التحليل -->
                        <div class="lab-results-section">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width: 50px;">#</th>
                                            <th>التحليل</th>
                                            <th style="width: 200px;">القيمة</th>
                                            <th style="width: 120px;">الوحدة</th>
                                            <th style="width: 100px;">الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="test-row" data-test="{{ $test->labTest->name }}">
                                            <td class="text-center">1</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="test-icon me-2">
                                                        <i class="{{ getTestIcon($test->labTest->name) }}"></i>
                                                    </div>
                                                    <div>
                                                        <strong>{{ $test->labTest->name }}</strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="fas fa-tachometer-alt text-success"></i>
                                                    </span>
                                                    <input type="text"
                                                           class="form-control test-value"
                                                           name="result"
                                                           value="{{ old('result', $test->result) }}"
                                                           placeholder="أدخل القيمة"
                                                           data-test="{{ $test->labTest->name }}">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center unit-display" data-test="{{ $test->labTest->name }}">
                                                    <i class="fas fa-balance-scale text-info me-2"></i>
                                                    <span class="unit-value">{{ getTestUnit($test->labTest->name, $labTests) }}</span>
                                                    <span class="unit-tooltip ms-1" data-bs-toggle="tooltip" 
                                                          data-bs-placement="top" 
                                                          title="وحدة القياس المعيارية للتحليل">
                                                        <i class="fas fa-info-circle text-info"></i>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    <span class="status-indicator" id="indicator-0">
                                                        <i class="fas fa-circle text-muted"></i>
                                                    </span>
                                                    <small class="status-text d-block mt-1" id="status-0">
                                                        قيد الإدخال
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
                                    ملخص النتائج
                                </h6>
                                <div class="summary-stats">
                                    <span class="stat-item">
                                        <i class="fas fa-check-circle text-success"></i>
                                        <span id="normal-count">0</span> طبيعي
                                    </span>
                                    <span class="stat-item">
                                        <i class="fas fa-arrow-up text-danger"></i>
                                        <span id="high-count">0</span> مرتفع
                                    </span>
                                    <span class="stat-item">
                                        <i class="fas fa-arrow-down text-warning"></i>
                                        <span id="low-count">0</span> منخفض
                                    </span>
                                    <span class="stat-item">
                                        <i class="fas fa-question-circle text-muted"></i>
                                        <span id="pending-count">1</span> غير مكتمل
                                    </span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="result_file" class="form-label">ملف النتيجة (PDF أو صورة)</label>
                                <input type="file" name="result_file" id="result_file" class="form-control @error('result_file') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
                                @error('result_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if($test->result_file)
                                <div class="mt-2">
                                    <small class="text-muted">الملف الحالي: <a href="{{ asset('storage/' . $test->result_file) }}" target="_blank">{{ basename($test->result_file) }}</a></small>
                                </div>
                                @endif
                            </div>

                            <!-- قسم المراجع المرجعية -->
                            <div class="mt-4">
                                <details class="mb-3">
                                    <summary class="text-primary fw-bold cursor-pointer">
                                        <i class="fas fa-info-circle me-2"></i>
                                        القيم المرجعية الطبيعية (معلومات فقط)
                                    </summary>
                                    <div class="mt-3 p-3 bg-light rounded">
                                        <small class="text-muted">
                                            <strong>ملاحظة:</strong> هذه القيم تقريبية عامة وتختلف حسب المختبر، عمر المريض، وجنسه. استشر الطبيب للقيم الصحيحة.
                                        </small>
                                        <ul class="mt-2 mb-0 small">
                                            <li><strong>سكر الدم:</strong> 70-140 mg/dL</li>
                                            <li><strong>ضغط الدم:</strong> أقل من 140 mmHg</li>
                                            <li><strong>الكوليسترول:</strong> أقل من 200 mg/dL</li>
                                            <li><strong>البيليروبين:</strong> أقل من 1.2 mg/dL</li>
                                            <li><strong>الكرياتينين:</strong> 0.6-1.2 mg/dL</li>
                                            <li><strong>اليوريا:</strong> 7-50 mg/dL</li>
                                            <li><strong>SGOT (AST):</strong> أقل من 40 U/L</li>
                                            <li><strong>SGPT (ALT):</strong> أقل من 41 U/L</li>
                                            <li><strong>الهيموغلوبين:</strong> 12-16 g/dL (نساء)، 14-18 g/dL (رجال)</li>
                                            <li><strong>الكرات البيضاء:</strong> 4,000-11,000 /µL</li>
                                            <li><strong>الصفائح:</strong> 150,000-450,000 /µL</li>
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
function analyzeResult(input, index) {
    const value = parseFloat(input.value);
    const testRow = input.closest('.test-row');
    const indicator = testRow.querySelector('.status-indicator i');
    const statusText = testRow.querySelector('.status-text');
    const testName = input.dataset.test ? input.dataset.test.toLowerCase() : '';

    if (isNaN(value) || input.value.trim() === '' || value === 0) {
        indicator.className = 'fas fa-circle text-muted';
        statusText.innerHTML = '<i class="fas fa-info-circle"></i> أدخل قيمة صحيحة';
        testRow.classList.remove('result-normal', 'result-high', 'result-low');
        return;
    }

    let resultType = 'normal';
    let statusMessage = '<i class="fas fa-info-circle text-info"></i> تم إدخال القيمة - راجع المرجع الطبي';
    let indicatorClass = 'fas fa-circle text-info';

    if (testName.includes('سكر') || testName.includes('glucose')) {
        if (value < 70) {
            resultType = 'low';
            statusMessage = '<i class="fas fa-arrow-down text-warning"></i> قيمة منخفضة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-down text-warning';
        } else if (value > 140) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (70-140 mg/dL)';
        }
    } else if (testName.includes('ضغط') || testName.includes('pressure')) {
        if (value > 140) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<140 mmHg)';
        }
    } else if (testName.includes('كوليسترول') || testName.includes('cholesterol')) {
        if (value > 200) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<200 mg/dL)';
        }
    } else if (testName.includes('بيليروبين') || testName.includes('bilirubin')) {
        if (value > 1.2) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<1.2 mg/dL)';
        }
    } else if (testName.includes('كرياتينين') || testName.includes('creatinine')) {
        if (value > 1.2) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<1.2 mg/dL)';
        }
    } else if (testName.includes('يوريا') || testName.includes('urea')) {
        if (value > 50) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<50 mg/dL)';
        }
    } else if (testName.includes('sgot') || testName.includes('ast')) {
        if (value > 40) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<40 U/L)';
        }
    } else if (testName.includes('sgpt') || testName.includes('alt')) {
        if (value > 41) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<41 U/L)';
        }
    } else if (testName.includes('الصفائح') || testName.includes('platelets')) {
        if (value < 150000) {
            resultType = 'low';
            statusMessage = '<i class="fas fa-arrow-down text-warning"></i> قيمة منخفضة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-down text-warning';
        } else if (value > 450000) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (150k-450k /µL)';
        }
    } else if (testName.includes('الهيموغلوبين') || testName.includes('hemoglobin')) {
        if (value < 12) {
            resultType = 'low';
            statusMessage = '<i class="fas fa-arrow-down text-warning"></i> قيمة منخفضة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-down text-warning';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (>12 g/dL)';
        }
    } else if (testName.includes('الكرات البيضاء') || testName.includes('wbc')) {
        if (value < 4000) {
            resultType = 'low';
            statusMessage = '<i class="fas fa-arrow-down text-warning"></i> قيمة منخفضة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-down text-warning';
        } else if (value > 11000) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (4k-11k /µL)';
        }
    } else {
        statusMessage = '<i class="fas fa-info-circle text-info"></i> تم إدخال القيمة - راجع المرجع الطبي';
    }

    indicator.className = indicatorClass;
    statusText.innerHTML = statusMessage;
    testRow.classList.remove('result-normal', 'result-high', 'result-low');
    testRow.classList.add('result-' + resultType);

    updateSummary();
}

function updateSummary() {
    const testRows = document.querySelectorAll('.test-row');
    let normal = 0, high = 0, low = 0, pending = 0;

    testRows.forEach(row => {
        const input = row.querySelector('.test-value');
        const value = parseFloat(input.value);

        if (isNaN(value) || value === 0) {
            pending++;
        } else if (row.classList.contains('result-high')) {
            high++;
        } else if (row.classList.contains('result-low')) {
            low++;
        } else {
            normal++;
        }
    });

    document.getElementById('normal-count').textContent = normal;
    document.getElementById('high-count').textContent = high;
    document.getElementById('low-count').textContent = low;
    document.getElementById('pending-count').textContent = pending;
}

document.addEventListener('DOMContentLoaded', function() {
    const testInputs = document.querySelectorAll('.test-value');

    testInputs.forEach((input, index) => {
        input.addEventListener('keyup', function() {
            analyzeResult(this, index);
        });

        input.addEventListener('input', function() {
            analyzeResult(this, index);
        });

        input.addEventListener('blur', function() {
            analyzeResult(this, index);
        });

        analyzeResult(input, index);
    });

    // تفعيل tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<style>
.lab-results-section {
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

.test-row {
    transition: all 0.3s ease;
}

.test-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.test-row .test-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0, 123, 255, 0.1);
    transition: all 0.3s ease;
}

.test-row:hover .test-icon {
    transform: scale(1.1);
    background: rgba(0, 123, 255, 0.2);
}

.test-row .form-control {
    border-radius: 0 4px 4px 0;
}

.test-row .input-group-text {
    border-radius: 4px 0 0 4px;
}

.status-indicator {
    font-size: 1.2rem;
    display: inline-block;
    transition: all 0.3s ease;
}

.status-text {
    font-size: 0.75rem;
    color: #6c757d;
}

.unit-display {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 4px 8px;
    border-radius: 0 4px 4px 0;
    transition: all 0.3s ease;
}

.unit-display:hover {
    background: #e9ecef;
}

.unit-value {
    font-weight: 500;
    color: #495057;
    font-size: 0.9rem;
}

.unit-tooltip {
    cursor: help;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.unit-tooltip:hover {
    opacity: 1;
}

.test-row.result-normal {
    background-color: rgba(40, 167, 69, 0.05);
}

.test-row.result-high {
    background-color: rgba(220, 53, 69, 0.05);
}

.test-row.result-low {
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
</style>
@endsection