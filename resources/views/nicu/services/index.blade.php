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
                            <th class="text-center" style="width: 60px;">#</th>
                            <th>اسم باقة الخدمة</th>
                            <th>الرمز البرمجي</th>
                            <th class="text-end" style="width: 200px;">الأجرة اليومية (IQD)</th>
                            <th class="text-center" style="width: 140px;">مرات الاستخدام</th>
                            <th class="text-center" style="width: 140px;">حالة التفعيل</th>
                            <th style="width: 260px;">الوصف والتجهيزات</th>
                            <th class="text-center" style="width: 120px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $index => $service)
                            <tr id="service-row-{{ $service->id }}" class="{{ !$service->is_active ? 'opacity-75 bg-light' : '' }}">
                                <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-bold fs-6 text-dark mb-1">{{ $service->name }}</div>
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
                                <td colspan="8" class="text-center py-5 text-muted">
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

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">
                            <i class="fas fa-money-bill-wave me-1"></i>
                            الأجرة اليومية بالدينار العراقي (IQD) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="number" step="500" name="daily_fee" id="edit_service_daily_fee" class="form-control fw-bold text-success" required>
                            <span class="input-group-text fw-bold">د.ع / يوم</span>
                        </div>
                        <small class="text-muted">السعر الذي سيتم احتسابه آلياً لكل 24 ساعة فندقية مع مراعاة سماحية الـ 6 ساعات.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">الوصف وتفاصيل التجهيزات الطبية</label>
                        <textarea name="description" id="edit_service_description" class="form-control" rows="3" placeholder="وصف ما تشمله الباقة..."></textarea>
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="edit_service_is_active" value="1">
                        <label class="form-check-label fw-bold" for="edit_service_is_active">الخدمة نشطة ومتاحة للاختيار عند تسكين الخدج</label>
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

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">
                            <i class="fas fa-money-bill-wave me-1"></i>
                            الأجرة اليومية بالدينار العراقي (IQD) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="number" step="500" name="daily_fee" class="form-control fw-bold text-success" placeholder="مثال: 400000" required>
                            <span class="input-group-text fw-bold">د.ع / يوم</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">الوصف وتفاصيل التجهيزات الطبية</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="ملاحظات حول الأجهزة أو الرعاية المشمولة..."></textarea>
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="create_service_is_active" value="1" checked>
                        <label class="form-check-label fw-bold" for="create_service_is_active">تفعيل الخدمة فوراً</label>
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
    const editDesc = document.getElementById('edit_service_description');
    const editActive = document.getElementById('edit_service_is_active');

    document.querySelectorAll('.edit-service-btn').forEach(button => {
        button.addEventListener('click', function () {
            const service = JSON.parse(this.getAttribute('data-service'));
            editForm.action = `/nicu/services/${service.id}`;
            editName.value = service.name || '';
            editCode.value = service.code || '';
            editFee.value = parseFloat(service.daily_fee) || 0;
            editDesc.value = service.description || '';
            editActive.checked = Boolean(service.is_active);
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
