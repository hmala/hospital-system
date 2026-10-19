@extends('layouts.app')

@section('title', 'إدارة وتسعير خدمات وباقات الخدج والحاضنات')

@section('styles')
<style>
    .service-card {
        transition: all 0.25s ease-in-out;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
    }
    .service-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
        border-color: #cbd5e1;
    }
    .price-pill {
        font-size: 1.25rem;
        font-weight: 800;
        letter-spacing: -0.5px;
    }
    .form-switch .form-check-input {
        width: 2.8em;
        height: 1.4em;
        cursor: pointer;
    }
    .service-code-badge {
        font-family: monospace;
        font-size: 0.8rem;
        background-color: #f1f5f9;
        color: #475569;
        padding: 2px 8px;
        border-radius: 6px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- رأس الصفحة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="mb-1 fw-bold text-dark">
                        <i class="fas fa-tags text-warning me-2"></i>
                        إعدادات وتسعير باقات وخدمات الخدج (NICU)
                    </h2>
                    <p class="text-muted mb-0">التحكم في الأسعار اليومية المعتمدة للحاضنات والكبسولات وأجهزة التنفس الاصطناعي والدعم السريري</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                        <i class="fas fa-plus-circle me-1"></i> إضافة باقة خدمة جديدة
                    </button>
                    <a href="{{ route('nicu.index') }}" class="btn btn-outline-secondary shadow-sm">
                        <i class="fas fa-arrow-right me-1"></i> العودة للوحة الحاضنات
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- التنبيهات -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- كروت الإحصائيات السريعة -->
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">إجمالي الباقات المعرفة</span>
                        <h3 class="mb-0 fw-bold">{{ $services->count() }}</h3>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-cubes fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">الباقات النشطة المتاحة</span>
                        <h3 class="mb-0 fw-bold">{{ $services->where('is_active', true)->count() }}</h3>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-check-double fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">أعلى تسعيرة يومية</span>
                        <h3 class="mb-0 fw-bold">{{ number_format($services->max('daily_fee')) }} <small class="fs-6">د.ع</small></h3>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-arrow-up fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-secondary text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">أدنى تسعيرة يومية</span>
                        <h3 class="mb-0 fw-bold">{{ number_format($services->min('daily_fee')) }} <small class="fs-6">د.ع</small></h3>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-arrow-down fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- بطاقة جدول الأسعار والخدمات -->
    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-list-alt text-primary me-2"></i>
                دليل باقات وتكلفة خدمات الخدج اليومية
            </h5>
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="fas fa-info-circle text-primary me-1"></i>
                الأسعار تحتسب بنظام التوقيت الفندقي (12:00 ظهراً) مع سماحية 6 ساعات
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>اسم باقة الخدمة / المطالبة</th>
                            <th>الرمز البرمجي</th>
                            <th class="text-end" style="width: 170px;">أجرة الكاش اليومية</th>
                            <th class="text-end" style="width: 170px;">تسعيرة الضمان (HIA)</th>
                            <th class="text-center" style="width: 120px;">مرات الاستخدام</th>
                            <th class="text-center" style="width: 130px;">حالة التفعيل</th>
                            <th style="width: 220px;">الوصف والتجهيزات</th>
                            <th class="text-center" style="width: 110px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $index => $service)
                            <tr id="service-row-{{ $service->id }}" class="{{ !$service->is_active ? 'opacity-75 bg-light' : '' }}">
                                <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-bold fs-6 text-dark mb-1">{{ $service->name }}</div>
                                    @if($service->official_claim_name)
                                        <div class="small text-muted mb-1">
                                            <i class="fas fa-file-contract text-primary me-1"></i>
                                            <span class="fst-italic">{{ $service->official_claim_name }}</span>
                                        </div>
                                    @endif
                                    @if(str_contains($service->code, 'ventilator'))
                                        <span class="badge bg-danger-subtle text-danger border border-danger">جهاز تنفس اصطناعي غازي</span>
                                    @elseif(str_contains($service->code, 'cpap'))
                                        <span class="badge bg-warning-subtle text-warning border border-warning">دعم CPAP غير غازي</span>
                                    @elseif(str_contains($service->code, 'phototherapy') || str_contains($service->code, 'capsule'))
                                        <span class="badge bg-info-subtle text-info border border-info">علاج ضوئي يرقان</span>
                                    @elseif(str_contains($service->code, 'o2'))
                                        <span class="badge bg-primary-subtle text-primary border border-primary">دعم أكسجين O2</span>
                                    @elseif(str_contains($service->code, 'pediatric'))
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary">رقود ردهة أطفال</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="service-code-badge">{{ $service->code }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="price-pill text-success">
                                        {{ number_format($service->daily_fee, 0) }}
                                    </span>
                                    <span class="text-muted small d-block">د.ع / يوم</span>
                                </td>
                                <td class="text-end">
                                    @if($service->hi_price > 0 && $service->is_hi_active)
                                        <span class="price-pill text-primary">
                                            {{ number_format($service->hi_price, 0) }}
                                        </span>
                                        <span class="badge bg-success-subtle text-success small d-block">معتمد الضمان</span>
                                    @else
                                        <span class="text-muted small">غير مفعل للضمان</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill px-3 py-2">
                                        {{ $service->reservations_count }} حجز
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input service-toggle-switch" type="checkbox" role="switch"
                                               data-service-id="{{ $service->id }}"
                                               id="toggle-{{ $service->id }}"
                                               {{ $service->is_active ? 'checked' : '' }}>
                                    </div>
                                    <div class="small fw-bold status-label-{{ $service->id }} {{ $service->is_active ? 'text-success' : 'text-danger' }}">
                                        {{ $service->is_active ? 'نشطة ومتاحة' : 'معطلة مؤقتاً' }}
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted d-block line-clamp-2" title="{{ $service->description }}">
                                        {{ $service->description ?: 'لا يوجد وصف تفصيلي مسجل.' }}
                                    </small>
                                </td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary px-3 py-1 shadow-sm edit-service-btn"
                                            data-service="{{ json_encode($service) }}"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editServiceModal">
                                        <i class="fas fa-edit me-1"></i> تعديل
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-tags fa-3x mb-3 text-secondary"></i>
                                    <h5>لا توجد باقات خدمات معرفة حالياً</h5>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- بطاقة الدليل واللائحة الوزارية لقواعد تغطية الضمان الصحي للخدج -->
    <div class="card border-0 shadow-sm mt-4" style="border-radius: 16px;">
        <div class="card-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-shield-alt text-primary me-2"></i>
                اللائحة الوزارية لقواعد احتساب تغطية الضمان الصحي لرعاية الخدج (NICU Gestational Age Matrix)
            </h5>
            <span class="badge bg-primary px-3 py-2">هيئة الضمان الصحي - وزارة الصحة</span>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="table-responsive border rounded-3">
                        <table class="table table-bordered mb-0 align-middle">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>العمر الحملي (Gestational Age)</th>
                                    <th>الحد الأقصى للإقامة المشمولة</th>
                                    <th>توزيع التغطية وتحمل المريض (الفئة E و H)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold text-center">≥ 34 أسبوعاً</td>
                                    <td class="text-center"><span class="badge bg-secondary">10 أيام كحد أقصى</span></td>
                                    <td>
                                        <div class="small">
                                            • <strong>الأيام (1 - 5):</strong> الضمان 75% | نسبة المريض 25%<br>
                                            • <strong>الأيام (6 - 10):</strong> الضمان 50% | نسبة المريض 50%<br>
                                            • <strong>أكثر من 10 أيام:</strong> 100% على حساب المريض
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">32 إلى أقل من 34 أسبوعاً</td>
                                    <td class="text-center"><span class="badge bg-secondary">15 يوماً كحد أقصى</span></td>
                                    <td>
                                        <div class="small">
                                            • <strong>الأيام (1 - 5):</strong> الضمان 75% | نسبة المريض 25%<br>
                                            • <strong>الأيام (6 - 10):</strong> الضمان 50% | نسبة المريض 50%<br>
                                            • <strong>الأيام (11 - 15):</strong> الضمان 25% | نسبة المريض 75%<br>
                                            • <strong>أكثر من 15 يوماً:</strong> 100% على حساب المريض
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">28 إلى أقل من 32 أسبوعاً</td>
                                    <td class="text-center"><span class="badge bg-secondary">20 يوماً كحد أقصى</span></td>
                                    <td>
                                        <div class="small">
                                            • <strong>الأيام (1 - 5):</strong> الضمان 75% | نسبة المريض 25%<br>
                                            • <strong>الأيام (6 - 10):</strong> الضمان 50% | نسبة المريض 50%<br>
                                            • <strong>الأيام (11 - 15):</strong> الضمان 25% | نسبة المريض 75%<br>
                                            • <strong>الأيام (16 - 20):</strong> الضمان 10% | نسبة المريض 90%<br>
                                            • <strong>أكثر من 20 يوماً:</strong> 100% على حساب المريض
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center text-danger">&lt; 28 أسبوعاً (خداجة شديدة)</td>
                                    <td class="text-center"><span class="badge bg-danger">استثناء خداجة شديدة</span></td>
                                    <td>
                                        <div class="small">
                                            • <strong>الأيام (1 - 5):</strong> الضمان 75% | نسبة المريض 25%<br>
                                            • <strong>الأيام (6 - 10):</strong> الضمان 50% | نسبة المريض 50%<br>
                                            • <strong>الأيام (11 - 15):</strong> الضمان 25% | نسبة المريض 75%<br>
                                            • <strong>الأيام 16 فما فوق:</strong> الضمان 10% | نسبة المريض 90% (مستمرة)
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="border rounded-3 p-3 bg-light h-100">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="fas fa-info-circle text-primary me-1"></i> قواعد استثنائية ومعتمدة
                        </h6>
                        <ul class="small text-muted ps-3 mb-0">
                            <li class="mb-2"><strong>الفئة A (الرعاية الاجتماعية، ذوو الشهداء):</strong> تغطية كاملة بنسبة 100% لكامل مدة الرقود بدون أي نسبة تحمل على ذوي الطفل.</li>
                            <li class="mb-2"><strong>رقود ردهة الأطفال الاعتيادي (Pediatric Ward):</strong> تغطية بنسبة 90% لهيئة الضمان الصحي و 10% نسبة تحمل المريض.</li>
                            <li class="mb-2"><strong>التوقيت الفندقي:</strong> تُحتسب الأيام بنظام دورة 12:00 ظهراً مع تطبيق سماحية 6 ساعات قبل الظهر للدخول و 6 ساعات بعد الظهر للخروج.</li>
                            <li><strong>أجور فتح الطبلة / الإضبارة:</strong> تُدفع في قسم الاستقبال والكاشير ولا تضاف إلى حساب الحاضنة السريري.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال تعديل باقة الخدمة -->
<div class="modal fade" id="editServiceModal" tabindex="-1" aria-labelledby="editServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <form id="editServiceForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="editServiceModalLabel">
                        <i class="fas fa-edit me-2"></i> تعديل سعر وبيانات باقة الخدمة
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">اسم باقة الخدمة <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_service_name" class="form-control form-control-lg" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">رمز الخدمة (Code)</label>
                        <input type="text" id="edit_service_code" class="form-control bg-light" disabled>
                        <small class="text-muted">الرمز يُستخدم داخلياً في النظام والمعادلات المالية.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-success">
                                <i class="fas fa-money-bill-wave me-1"></i>
                                أجرة الكاش اليومية (IQD) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" step="500" name="daily_fee" id="edit_service_daily_fee" class="form-control fw-bold text-success" required>
                                <span class="input-group-text fw-bold">د.ع</span>
                            </div>
                            <small class="text-muted">السعر اليومي الفندقي للنزلاء الخاصين (كاش).</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-primary">
                                <i class="fas fa-shield-alt me-1"></i>
                                تسعيرة الضمان الصحي (HIA)
                            </label>
                            <div class="input-group">
                                <input type="number" step="500" name="hi_price" id="edit_service_hi_price" class="form-control fw-bold text-primary">
                                <span class="input-group-text fw-bold">د.ع</span>
                            </div>
                            <small class="text-muted">السعر المعتمد للمطالبة بهيئة الضمان الصحي.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">المسمى الرسمي للمطالبة في الضمان الصحي</label>
                        <input type="text" name="official_claim_name" id="edit_service_official_claim_name" class="form-control" placeholder="اسم الخدمة كما يظهر في نظام المطالبات">
                        <small class="text-muted">المسمى المعتمد في مطالبات الضمان مثل: (رقود حديثي الولادة لليوم الواحد شامل الخدمات التمريضية...)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">الوصف وتفاصيل التجهيزات الطبية</label>
                        <textarea name="description" id="edit_service_description" class="form-control" rows="3" placeholder="وصف ما تشمله الباقة..."></textarea>
                    </div>

                    <div class="d-flex justify-content-between flex-wrap gap-2 mt-3 p-3 bg-light rounded-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="edit_service_is_active" value="1">
                            <label class="form-check-label fw-bold" for="edit_service_is_active">الخدمة نشطة عامة</label>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_hi_active" id="edit_service_is_hi_active" value="1">
                            <label class="form-check-label fw-bold text-primary" for="edit_service_is_hi_active">تفعيل تسعير الضمان الصحي</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="fas fa-save me-1"></i> حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال إضافة باقة خدمة جديدة -->
<div class="modal fade" id="createServiceModal" tabindex="-1" aria-labelledby="createServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <form action="{{ route('nicu.services.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="createServiceModalLabel">
                        <i class="fas fa-plus-circle me-2"></i> إضافة باقة خدمة خدج جديدة
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">اسم باقة الخدمة <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-lg" placeholder="مثال: حاضنة + فنتليتر عالي التردد HFOV" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">رمز الخدمة الفريد (Code) <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" placeholder="مثال: incubator_hfov" required>
                        <small class="text-muted">أحرف إنجليزية وشرطات سفلية فقط دون مسافات.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-success">
                                <i class="fas fa-money-bill-wave me-1"></i>
                                أجرة الكاش اليومية (IQD) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" step="500" name="daily_fee" class="form-control fw-bold text-success" placeholder="مثال: 400000" required>
                                <span class="input-group-text fw-bold">د.ع</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-primary">
                                <i class="fas fa-shield-alt me-1"></i>
                                تسعيرة الضمان (HIA)
                            </label>
                            <div class="input-group">
                                <input type="number" step="500" name="hi_price" class="form-control fw-bold text-primary" placeholder="مثال: 300000">
                                <span class="input-group-text fw-bold">د.ع</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">المسمى الرسمي للمطالبة في الضمان الصحي</label>
                        <input type="text" name="official_claim_name" class="form-control" placeholder="اسم المطالبة المعتمد في هيئة الضمان">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">الوصف وتفاصيل التجهيزات الطبية</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="ملاحظات حول الأجهزة أو الرعاية المشمولة..."></textarea>
                    </div>

                    <div class="d-flex justify-content-between flex-wrap gap-2 mt-3 p-3 bg-light rounded-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="create_service_is_active" value="1" checked>
                            <label class="form-check-label fw-bold" for="create_service_is_active">تفعيل الخدمة عامة</label>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_hi_active" id="create_service_is_hi_active" value="1" checked>
                            <label class="form-check-label fw-bold text-primary" for="create_service_is_hi_active">تفعيل للضمان الصحي</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold">
                        <i class="fas fa-check-circle me-1"></i> إنشاء الباقة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // تجهيز مودال التعديل بالبيانات
    const editModal = document.getElementById('editServiceModal');
    const editForm = document.getElementById('editServiceForm');
    const editName = document.getElementById('edit_service_name');
    const editCode = document.getElementById('edit_service_code');
    const editFee = document.getElementById('edit_service_daily_fee');
    const editHiPrice = document.getElementById('edit_service_hi_price');
    const editClaimName = document.getElementById('edit_service_official_claim_name');
    const editDesc = document.getElementById('edit_service_description');
    const editActive = document.getElementById('edit_service_is_active');
    const editHiActive = document.getElementById('edit_service_is_hi_active');

    document.querySelectorAll('.edit-service-btn').forEach(button => {
        button.addEventListener('click', function () {
            const service = JSON.parse(this.getAttribute('data-service'));
            editForm.action = `/nicu/services/${service.id}`;
            editName.value = service.name || '';
            editCode.value = service.code || '';
            editFee.value = parseFloat(service.daily_fee) || 0;
            editHiPrice.value = parseFloat(service.hi_price) || 0;
            editClaimName.value = service.official_claim_name || '';
            editDesc.value = service.description || '';
            editActive.checked = Boolean(service.is_active);
            editHiActive.checked = Boolean(service.is_hi_active);
        });
    });

    // التبديل اللحظي لحالة التفعيل عبر AJAX
    document.querySelectorAll('.service-toggle-switch').forEach(toggle => {
        toggle.addEventListener('change', function () {
            const serviceId = this.getAttribute('data-service-id');
            const row = document.getElementById(`service-row-${serviceId}`);
            const label = document.querySelector(`.status-label-${serviceId}`);

            fetch(`/nicu/services/${serviceId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.is_active) {
                        row.classList.remove('opacity-75', 'bg-light');
                        label.className = `small fw-bold status-label-${serviceId} text-success`;
                        label.textContent = 'نشطة ومتاحة';
                    } else {
                        row.classList.add('opacity-75', 'bg-light');
                        label.className = `small fw-bold status-label-${serviceId} text-danger`;
                        label.textContent = 'معطلة مؤقتاً';
                    }
                } else {
                    alert(data.message || 'حدث خطأ أثناء تحديث الحالة');
                    toggle.checked = !toggle.checked;
                }
            })
            .catch(err => {
                console.error(err);
                alert('فشل الاتصال بالخادم');
                toggle.checked = !toggle.checked;
            });
        });
    });
});
</script>
@endsection
