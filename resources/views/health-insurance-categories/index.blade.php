@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fas fa-shield-alt text-primary me-2"></i>
                إدارة نسب استقطاع الضمان الصحي الوطني (الأهلي)
            </h3>
            <p class="text-muted mb-0">
                مصفوفة الفئات المشمولة بالضمان (A إلى I) وتحديد نسب التحمل (Co-payment %) لكل نوع خدمة طبية
            </p>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i> العودة للرئيسية
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Alert Info -->
    <div class="alert alert-info border-0 shadow-sm mb-4" style="border-radius: 10px;">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle fs-3 text-info me-3"></i>
            <div>
                <strong>تطبيق فوري وتلقائي في النظام:</strong>
                أي تعديل على نسب الفئات أدناه ينعكس فوراً وتلقائياً على حسابات الكاشير لجميع المرضى المسجلين تحت تلك الفئة دون أي تعديل برمجى.
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="fw-bold mb-0 text-primary">
                <i class="fas fa-table me-2"></i>
                جدول نسب الاستقطاع للقطاع الأهلي (الفئات A - I)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start ps-3">الفئة والتوصيف</th>
                            <th>الشرط</th>
                            <th>الاستشارية</th>
                            <th>العمليات الجراحية</th>
                            <th>المختبر</th>
                            <th>الأشعة والسونار</th>
                            <th>الخدمات الساندة</th>
                            <th>الأدوية</th>
                            <th>الطوارئ</th>
                            <th>الأسنان</th>
                            <th>الحالة</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                            <tr>
                                <td class="text-start ps-3">
                                    <div class="fw-bold text-dark fs-6">{{ $category->name }} ({{ $category->code }})</div>
                                    <small class="text-muted text-wrap d-inline-block" style="max-width: 280px;">
                                        {{ $category->description }}
                                    </small>
                                </td>
                                <td>
                                    @if($category->requires_thermal_stamp)
                                        <span class="badge bg-danger">
                                            <i class="fas fa-stamp me-1"></i> شرط ختم حراري
                                        </span>
                                    @else
                                        <span class="badge bg-light text-secondary border">عادي</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-primary fs-7">{{ $category->consultation_copay }}%</span></td>
                                <td><span class="badge bg-danger fs-7">{{ $category->surgery_copay }}%</span></td>
                                <td><span class="badge bg-info text-dark fs-7">{{ $category->lab_copay }}%</span></td>
                                <td><span class="badge bg-warning text-dark fs-7">{{ $category->radiology_copay }}%</span></td>
                                <td><span class="badge bg-secondary fs-7">{{ $category->support_services_copay }}%</span></td>
                                <td><span class="badge bg-dark fs-7">{{ $category->medication_copay }}%</span></td>
                                <td>
                                    <span class="badge {{ $category->emergency_copay == 0 ? 'bg-success' : 'bg-danger' }} fs-7">
                                        {{ $category->emergency_copay }}%
                                    </span>
                                </td>
                                <td><span class="badge bg-light text-dark border fs-7">{{ $category->dental_copay }}%</span></td>
                                <td>
                                    @if($category->is_active)
                                        <span class="badge bg-success">نشط</span>
                                    @else
                                        <span class="badge bg-secondary">معطل</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $category->id }}">
                                        <i class="fas fa-edit me-1"></i> تعديل النسب
                                    </button>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal{{ $category->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <form action="{{ route('health-insurance-categories.update', $category->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-edit me-2"></i>
                                                    تعديل نسب الاستقطاع: {{ $category->name }} ({{ $category->code }})
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4 text-start">
                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold">اسم الفئة</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold">الشرط الخاص</label>
                                                        <div class="form-check mt-2">
                                                            <input class="form-check-input" type="checkbox" name="requires_thermal_stamp" value="1" id="stamp{{ $category->id }}" {{ $category->requires_thermal_stamp ? 'checked' : '' }}>
                                                            <label class="form-check-label fw-semibold" for="stamp{{ $category->id }}">
                                                                يتطلب وجود ختم حراري
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label fw-bold">التوصيف والمشمولين</label>
                                                        <textarea name="description" class="form-control" rows="2">{{ $category->description }}</textarea>
                                                    </div>
                                                </div>

                                                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                                                    <i class="fas fa-percentage me-1"></i> نسب الاستقطاع والتحمل على المريض (Co-payment %)
                                                </h6>

                                                <div class="row g-3">
                                                    <div class="col-md-3">
                                                        <label class="form-label small fw-bold">الاستشارية (%)</label>
                                                        <input type="number" step="1" min="0" max="100" name="consultation_copay" class="form-control text-center fw-bold" value="{{ $category->consultation_copay }}" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small fw-bold">العمليات الجراحية (%)</label>
                                                        <input type="number" step="1" min="0" max="100" name="surgery_copay" class="form-control text-center fw-bold" value="{{ $category->surgery_copay }}" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small fw-bold">المختبر والتحاليل (%)</label>
                                                        <input type="number" step="1" min="0" max="100" name="lab_copay" class="form-control text-center fw-bold" value="{{ $category->lab_copay }}" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small fw-bold">الأشعة والسونار (%)</label>
                                                        <input type="number" step="1" min="0" max="100" name="radiology_copay" class="form-control text-center fw-bold" value="{{ $category->radiology_copay }}" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small fw-bold">الخدمات الساندة (%)</label>
                                                        <input type="number" step="1" min="0" max="100" name="support_services_copay" class="form-control text-center fw-bold" value="{{ $category->support_services_copay }}" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small fw-bold">الأدوية (%)</label>
                                                        <input type="number" step="1" min="0" max="100" name="medication_copay" class="form-control text-center fw-bold" value="{{ $category->medication_copay }}" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small fw-bold">الطوارئ (%)</label>
                                                        <input type="number" step="1" min="0" max="100" name="emergency_copay" class="form-control text-center fw-bold" value="{{ $category->emergency_copay }}" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small fw-bold">الأسنان (%)</label>
                                                        <input type="number" step="1" min="0" max="100" name="dental_copay" class="form-control text-center fw-bold" value="{{ $category->dental_copay }}" required>
                                                    </div>
                                                </div>

                                                <div class="form-check mt-3">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active{{ $category->id }}" {{ $category->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-bold" for="active{{ $category->id }}">
                                                        الفئة نشطة في النظام
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                <button type="submit" class="btn btn-primary px-4 fw-bold">
                                                    <i class="fas fa-save me-1"></i> حفظ التعديلات
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
