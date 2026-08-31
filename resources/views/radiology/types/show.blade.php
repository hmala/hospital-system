@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-x-ray me-2"></i>تفاصيل نوع الأشعة: {{ $type->name }}
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('radiology.types.edit', $type) }}" class="btn btn-light btn-sm">
                            <i class="fas fa-edit me-1"></i>تعديل
                        </a>
                        <a href="{{ route('radiology.types.index') }}" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-arrow-right me-1"></i>رجوع للقائمة
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small">اسم نوع الأشعة</label>
                            <div class="fw-bold fs-5">{{ $type->name }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small">الكود</label>
                            <div><span class="badge bg-secondary fs-6">{{ $type->code ?? '-' }}</span></div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small">قسم الأشعة</label>
                            <div>
                                <span class="badge bg-info text-dark fs-6">{{ $type->subcategory ?? 'أشعة عامة' }}</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small">الحالة</label>
                            <div>
                                @if($type->is_active)
                                    <span class="badge bg-success">مُفعّل</span>
                                @else
                                    <span class="badge bg-danger">معطّل</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small">السعر الأساسي</label>
                            <div class="fw-bold text-success fs-5">{{ number_format($type->base_price, 2) }} دينار</div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small">المدة التقديرية</label>
                            <div class="fw-bold">{{ $type->estimated_duration ?? '-' }} دقيقة</div>
                        </div>

                        <div class="col-12">
                            <label class="text-muted small">الوصف</label>
                            <div class="p-3 bg-light rounded">{{ $type->description ?: 'لا يوجد وصف' }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small">يتطلب صبغة (Contrast)</label>
                            <div>
                                @if($type->requires_contrast)
                                    <span class="badge bg-warning text-dark">نعم</span>
                                @else
                                    <span class="badge bg-light text-dark">لا</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small">يتطلب تحضير مسبق</label>
                            <div>
                                @if($type->requires_preparation)
                                    <span class="badge bg-warning text-dark">نعم</span>
                                @else
                                    <span class="badge bg-light text-dark">لا</span>
                                @endif
                            </div>
                        </div>

                        @if($type->preparation_instructions)
                        <div class="col-12">
                            <label class="text-muted small">تعليمات التحضير</label>
                            <div class="p-3 bg-warning bg-opacity-10 border border-warning rounded">
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
