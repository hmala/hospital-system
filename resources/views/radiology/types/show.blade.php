@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-x-ray me-2"></i> تفاصيل نوع الأشعة: {{ $type->name }}
                    </h5>
                    <div class="d-flex gap-2">
                        @can('manage radiology types')
                        <a href="{{ route('radiology.types.edit', $type) }}" class="btn btn-light btn-sm rounded-pill px-3 fw-bold">
                            <i class="fas fa-edit me-1"></i> تعديل
                        </a>
                        @endcan
                        <a href="{{ route('radiology.types.index') }}" class="btn btn-outline-light btn-sm rounded-pill px-3">
                            <i class="fas fa-arrow-right me-1"></i> رجوع للقائمة
                        </a>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold">اسم نوع الأشعة</label>
                            <div class="fw-bold fs-5 text-dark">{{ $type->name }}</div>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small fw-bold">الرمز / الكود</label>
                            <div><code class="bg-light px-2 py-1 rounded text-primary fs-6">{{ $type->code ?? '-' }}</code></div>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small fw-bold">قسم الفحص</label>
                            <div>
                                <span class="badge bg-info text-dark fs-6 rounded-pill px-3">{{ $type->subcategory ?? 'أشعة عامة' }}</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small fw-bold">السعر الأساسي (نقدي كاش)</label>
                            <div class="fw-bold text-success fs-4">{{ number_format($type->base_price) }} <small class="fs-6 text-muted">د.ع</small></div>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small fw-bold">المدة المقدرة</label>
                            <div class="fw-bold text-dark fs-5"><i class="fas fa-clock text-secondary me-1"></i> {{ $type->estimated_duration ?? '-' }} دقيقة</div>
                        </div>

                        <div class="col-md-3">
                            <label class="text-muted small fw-bold">الحالة التشغيلية</label>
                            <div>
                                @if($type->is_active)
                                    <span class="badge bg-success rounded-pill px-3 py-1">مُفعّل ومتاح</span>
                                @else
                                    <span class="badge bg-danger rounded-pill px-3 py-1">معطّل مؤقتاً</span>
                                @endif
                            </div>
                        </div>

                        <!-- قسم تسعير الضمان الصحي والداخلية -->
                        <div class="col-12">
                            <div class="card border-primary border-opacity-25 bg-light bg-opacity-25 rounded-3">
                                <div class="card-header bg-primary bg-opacity-10 py-2">
                                    <h6 class="mb-0 text-primary fw-bold">
                                        <i class="fas fa-shield-alt me-1"></i> تسعير وتغطية الضمان الصحي وضمان الداخلية
                                    </h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-3">
                                        <!-- وزارة الداخلية -->
                                        <div class="col-md-6 border-start">
                                            <div class="p-3 bg-white rounded border">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong class="text-dark"><i class="fas fa-id-badge text-primary me-1"></i> ضمان وزارة الداخلية (MOI)</strong>
                                                    @if($type->is_moi_active)
                                                        <span class="badge bg-primary">مشمول بالضمان</span>
                                                    @else
                                                        <span class="badge bg-secondary">غير مشمول</span>
                                                    @endif
                                                </div>
                                                <div class="fs-5 fw-bold text-primary">
                                                    {{ number_format($type->moi_price ?: $type->base_price) }} د.ع
                                                </div>
                                                <small class="text-muted">{{ $type->moi_price ? 'سعر مخصص للداخلية' : 'مطابق لسعر الكاش الأساسي' }}</small>
                                            </div>
                                        </div>

                                        <!-- هيئة الضمان الصحي -->
                                        <div class="col-md-6">
                                            <div class="p-3 bg-white rounded border">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong class="text-dark"><i class="fas fa-heartbeat text-success me-1"></i> هيئة الضمان الصحي (HI)</strong>
                                                    @if($type->is_hi_active)
                                                        <span class="badge bg-success">مشمول بالضمان</span>
                                                    @else
                                                        <span class="badge bg-secondary">غير مشمول</span>
                                                    @endif
                                                </div>
                                                <div class="fs-5 fw-bold text-success">
                                                    {{ number_format($type->hi_price ?: $type->base_price) }} د.ع
                                                </div>
                                                <small class="text-muted">{{ $type->hi_price ? 'سعر مخصص للضمان' : 'مطابق لسعر الكاش الأساسي' }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="text-muted small fw-bold">الوصف والتفاصيل</label>
                            <div class="p-3 bg-light rounded-3 text-dark">{{ $type->description ?: 'لا يوجد وصف مضاف لهذا الفحص.' }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small fw-bold">مادة الصبغة (Contrast)</label>
                            <div>
                                @if($type->requires_contrast)
                                    <span class="badge bg-warning text-dark px-3 py-1">يتطلب مادة تباين / صبغة</span>
                                @else
                                    <span class="badge bg-light text-muted border px-3 py-1">لا يتطلب صبغة</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small fw-bold">التحضير المسبق</label>
                            <div>
                                @if($type->requires_preparation)
                                    <span class="badge bg-info text-dark px-3 py-1">يتطلب تحضير مسبق</span>
                                @else
                                    <span class="badge bg-light text-muted border px-3 py-1">لا يتطلب تحضير خاص</span>
                                @endif
                            </div>
                        </div>

                        @if($type->preparation_instructions)
                        <div class="col-12">
                            <label class="text-muted small fw-bold">تعليمات التحضير للمريض</label>
                            <div class="p-3 bg-warning bg-opacity-10 border border-warning rounded-3 text-dark">
                                <i class="fas fa-exclamation-circle text-warning me-1"></i>
                                {{ $type->preparation_instructions }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
