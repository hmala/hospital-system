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

    <!-- Tabs Navigation -->
    <ul class="nav nav-pills mb-4 gap-2" id="insuranceTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ session('active_tab', 'categories') === 'categories' ? 'active' : '' }} fw-bold px-4 py-2 rounded-pill shadow-sm" id="categories-tab" data-bs-toggle="pill" data-bs-target="#categories-pane" type="button" role="tab">
                <i class="fas fa-layer-group me-1"></i> نسب الفئات الطبية (A إلى I)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ session('active_tab') === 'stay' ? 'active' : '' }} fw-bold px-4 py-2 rounded-pill shadow-sm" id="stay-tab" data-bs-toggle="pill" data-bs-target="#stay-pane" type="button" role="tab">
                <i class="fas fa-bed me-1"></i> شرائح إقامة وغرف العمليات (الضمان)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="insuranceTabsContent">
        <!-- Tab 1: Categories -->
        <div class="tab-pane fade {{ session('active_tab', 'categories') === 'categories' ? 'show active' : '' }}" id="categories-pane" role="tabpanel">
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

        <!-- Tab 2: Stay & Room Tiers for Insurance -->
        <div class="tab-pane fade {{ session('active_tab') === 'stay' ? 'show active' : '' }}" id="stay-pane" role="tabpanel">
            <!-- Tariff & Upgrade Reference Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 bg-primary-subtle text-primary border-primary">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold small">سعر العادية المعتمد للضمان</span>
                                <button type="button" class="btn btn-sm btn-primary py-0 px-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#editStandardPriceModal">
                                    <i class="fas fa-edit small me-1"></i>تعديل
                                </button>
                            </div>
                            <h3 class="fw-bold mb-0 text-primary">{{ number_format($standardRoomPrice, 0) }} <small class="fs-6">د.ع / يوم</small></h3>
                            <small class="text-muted d-block mt-1">سقف تغطية الضمان لليوم الواحد</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 bg-white">
                        <div class="card-body p-3">
                            <span class="text-muted small fw-bold d-block mb-1">الغرفة العادية (تسعيرة المستشفى)</span>
                            <h4 class="fw-bold mb-0 text-dark">{{ number_format($roomSamples['regular'] ?? 250000, 0) }} <small class="fs-6">د.ع</small></h4>
                            <span class="badge bg-light text-secondary border mt-1">مطابقة معيارية</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 bg-white">
                        <div class="card-body p-3">
                            <span class="text-muted small fw-bold d-block mb-1">غرفة VIP (سعر المستشفى)</span>
                            <h4 class="fw-bold mb-0 text-dark">{{ number_format($roomSamples['vip'] ?? 350000, 0) }} <small class="fs-6">د.ع</small></h4>
                            @php
                                $vipDiff = max(0, ($roomSamples['vip'] ?? 350000) - $standardRoomPrice);
                            @endphp
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning mt-1">
                                <i class="fas fa-crown me-1"></i>فرق ترقية: +{{ number_format($vipDiff, 0) }} د.ع / يوم
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 bg-white">
                        <div class="card-body p-3">
                            <span class="text-muted small fw-bold d-block mb-1">غرفة VVIP (سعر المستشفى)</span>
                            <h4 class="fw-bold mb-0 text-dark">{{ number_format($roomSamples['vvip'] ?? 450000, 0) }} <small class="fs-6">د.ع</small></h4>
                            @php
                                $vvipDiff = max(0, ($roomSamples['vvip'] ?? 450000) - $standardRoomPrice);
                            @endphp
                            <span class="badge bg-dark text-white border mt-1">
                                <i class="fas fa-gem text-warning me-1"></i>فرق ترقية: +{{ number_format($vvipDiff, 0) }} د.ع / يوم
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rule Alert -->
            <div class="alert alert-warning border-0 shadow-sm mb-4" style="border-radius: 10px;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fs-3 text-warning me-3"></i>
                    <div>
                        <strong>حوكمة التغطية التأمينية للإقامة وغرف العمليات:</strong>
                        هيئة الضمان مسؤولة حصراً عن سعر الغرفة العادية ({{ number_format($standardRoomPrice, 0) }} د.ع) وتدفع حصتها المقررة أدناه. 
                        <strong>أي رغبة من المريض في غرفة خاصة (VIP أو VVIP) يتحمل المريض فارق السعر الفعلي بالكامل نقداً عن كل يوم إقامة</strong> دون تحميل الضمان أي فلس إضافي.
                    </div>
                </div>
            </div>

            <!-- Tiers Table Card -->
            <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-primary">
                        <i class="fas fa-layer-group me-2"></i>
                        جدول شرائح نسب تحمل المريض للإقامة الفندقية (Inpatient Stay Tiers)
                    </h5>
                    <span class="badge bg-primary-subtle text-primary border border-primary px-3 py-2">
                        معادلة بغداد 12:00 ظهراً
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light">
                                <tr>
                                    <th width="40" class="text-center">#</th>
                                    <th class="text-start ps-3">الشريحة</th>
                                    <th>نطاق الأيام</th>
                                    <th>نسبة تحمل المريض</th>
                                    <th>حصة المريض اليومية (للعادية)</th>
                                    <th>حصة الضمان اليومية (للعادية)</th>
                                    <th class="text-start">الملاحظات والتوصيف</th>
                                    <th>الحالة</th>
                                    <th>الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stayTiers as $idx => $tier)
                                    @php
                                        $pShareStandard = round($standardRoomPrice * ($tier->copay_percentage / 100), 2);
                                        $iShareStandard = max(0, $standardRoomPrice - $pShareStandard);
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted fw-bold">{{ $idx + 1 }}</td>
                                        <td class="text-start ps-3">
                                            <strong class="text-dark fs-6">{{ $tier->name }}</strong>
                                        </td>
                                        <td>
                                            @if($tier->day_to >= 999)
                                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                                    من اليوم {{ $tier->day_from }} فما بعد
                                                </span>
                                            @elseif($tier->day_from == $tier->day_to)
                                                <span class="badge bg-info-subtle text-info border px-2 py-1">
                                                    اليوم {{ $tier->day_from }} فقط
                                                </span>
                                            @else
                                                <span class="badge bg-light text-dark border px-2 py-1">
                                                    من {{ $tier->day_from }} إلى {{ $tier->day_to }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $tier->copay_percentage == 0 ? 'bg-success' : ($tier->copay_percentage <= 25 ? 'bg-primary' : 'bg-danger') }} fs-6 px-3 py-1">
                                                {{ (float)$tier->copay_percentage }}%
                                            </span>
                                        </td>
                                        <td class="text-success fw-bold fs-6">
                                            {{ number_format($pShareStandard, 0) }} د.ع
                                        </td>
                                        <td class="text-primary fw-bold fs-6">
                                            {{ number_format($iShareStandard, 0) }} د.ع
                                        </td>
                                        <td class="text-start small text-muted">
                                            {{ $tier->notes }}
                                        </td>
                                        <td>
                                            @if($tier->is_active)
                                                <span class="badge bg-success">نشط</span>
                                            @else
                                                <span class="badge bg-secondary">معطل</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTierModal{{ $tier->id }}">
                                                <i class="fas fa-edit me-1"></i> تعديل
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Edit Tier Modal -->
                                    <div class="modal fade" id="editTierModal{{ $tier->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow">
                                                <form action="{{ route('health-insurance-categories.stay-tiers.update', $tier->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-edit me-2"></i>
                                                            تعديل شريحة الإقامة: {{ $tier->name }}
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-4 text-start">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">اسم الشريحة</label>
                                                            <input type="text" name="name" class="form-control" value="{{ $tier->name }}" required>
                                                        </div>

                                                        <div class="row g-3 mb-3">
                                                            <div class="col-6">
                                                                <label class="form-label fw-bold small">من اليوم (بداية الشريحة)</label>
                                                                <input type="number" name="day_from" class="form-control text-center fw-bold" value="{{ $tier->day_from }}" min="1" required>
                                                            </div>
                                                            <div class="col-6">
                                                                <label class="form-label fw-bold small">إلى اليوم (نهاية الشريحة)</label>
                                                                <input type="number" name="day_to" class="form-control text-center fw-bold" value="{{ $tier->day_to }}" min="1" required>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">نسبة تحمل المريض (%)</label>
                                                            <div class="input-group">
                                                                <input type="number" step="0.5" min="0" max="100" name="copay_percentage" class="form-control text-center fw-bold fs-5 text-primary" value="{{ (float)$tier->copay_percentage }}" required>
                                                                <span class="input-group-text fw-bold">%</span>
                                                            </div>
                                                            <small class="text-muted">النسبة المتبقية ({{ 100 - (float)$tier->copay_percentage }}%) تطالب بها هيئة الضمان تلقائياً</small>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">الملاحظات والتوصيف</label>
                                                            <textarea name="notes" class="form-control" rows="2">{{ $tier->notes }}</textarea>
                                                        </div>

                                                        <div class="form-check mt-2">
                                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="tierActive{{ $tier->id }}" {{ $tier->is_active ? 'checked' : '' }}>
                                                            <label class="form-check-label fw-bold" for="tierActive{{ $tier->id }}">
                                                                الشريحة نشطة ومفعلة في النظام
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

            <!-- Edit Standard Room Price Modal -->
            <div class="modal fade" id="editStandardPriceModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <form action="{{ route('health-insurance-categories.stay-tiers.standard-price') }}" method="POST">
                            @csrf
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-dollar-sign me-2"></i>
                                    تعديل السعر اليومي المعتمد للغرفة العادية لدى الضمان
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4 text-start">
                                <label class="form-label fw-bold">السعر اليومي للغرفة العادية (د.ع)</label>
                                <div class="input-group input-group-lg mb-2">
                                    <input type="number" name="standard_room_daily_price" class="form-control text-center fw-bold fs-4 text-primary" value="{{ (int)$standardRoomPrice }}" step="1000" min="0" required>
                                    <span class="input-group-text fw-bold">د.ع</span>
                                </div>
                                <small class="text-muted d-block">
                                    هذا السعر هو السقف المعياري الذي تحتسب بموجبه نسب استقطاع ومطالبات الضمان لكافة أيام الإقامة.
                                </small>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                <button type="submit" class="btn btn-primary px-4 fw-bold">
                                    <i class="fas fa-save me-1"></i> حفظ السعر الجديد
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
