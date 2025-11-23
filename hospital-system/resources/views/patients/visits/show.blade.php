@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-file-medical me-2"></i>
                    تفاصيل الزيارة الطبية
                </h2>
                <a href="{{ route('patient.visits.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>
                    العودة للقائمة
                </a>
            </div>
        </div>
    </div>

    <!-- معلومات الزيارة -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        معلومات الزيارة
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>تاريخ الزيارة:</strong> {{ $visit->visit_date ? $visit->visit_date->format('Y-m-d') : 'غير محدد' }}</p>
                            <p><strong>الوقت:</strong> {{ $visit->visit_time ? $visit->visit_time->format('H:i') : 'غير محدد' }}</p>
                            <p><strong>نوع الزيارة:</strong> {{ $visit->visit_type_text }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>الطبيب:</strong> د. {{ $visit->doctor?->user?->name ?? 'غير محدد' }}</p>
                            <p><strong>التخصص:</strong> {{ $visit->doctor?->specialization ?? 'غير محدد' }}</p>
                            <p><strong>الحالة:</strong>
                                <span class="badge bg-{{ $visit->status == 'completed' ? 'success' : 'warning' }}">
                                    {{ $visit->status_text }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <p><strong>الشكوى الرئيسية:</strong></p>
                            <p class="text-muted">{{ $visit->chief_complaint }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-md me-2"></i>
                        معلومات الطبيب
                    </h5>
                </div>
                <div class="card-body">
                    <p><strong>الاسم:</strong> د. {{ $visit->doctor?->user?->name ?? 'غير محدد' }}</p>
                    <p><strong>التخصص:</strong> {{ $visit->doctor?->specialization ?? 'غير محدد' }}</p>
                    <p><strong>رقم الهاتف:</strong> {{ $visit->doctor?->phone ?? 'غير محدد' }}</p>
                    <p><strong>القسم:</strong> {{ $visit->doctor?->department?->name ?? 'غير محدد' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- نتائج الفحص -->
    @if($visit->status == 'completed')
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-stethoscope me-2"></i>
                        نتائج الفحص والتشخيص
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if($visit->vital_signs)
                        <div class="col-md-6">
                            <h6>العلامات الحيوية</h6>
                            <p class="text-muted">{{ $visit->vital_signs }}</p>
                        </div>
                        @endif
                        @if($visit->physical_examination)
                        <div class="col-md-6">
                            <h6>الفحص السريري</h6>
                            <p class="text-muted">{{ $visit->physical_examination }}</p>
                        </div>
                        @endif
                    </div>
                    @if($visit->diagnosis)
                    <hr>
                    <h6>التشخيص</h6>
                    @php $diag = is_string($visit->diagnosis) ? json_decode($visit->diagnosis, true) : $visit->diagnosis; @endphp
                    @if($diag['code'] ?? false)
                        @if($diag['code'] === 'other' && isset($diag['custom_code']))
                            <p><strong>رمز ICD:</strong> {{ $diag['custom_code'] }}</p>
                        @elseif($diag['code'] !== 'other')
                            <p><strong>رمز ICD-10:</strong> {{ $diag['code'] }}</p>
                        @endif
                    @endif
                    <p><strong>الوصف:</strong> {{ $diag['description'] ?? $visit->diagnosis }}</p>
                    @endif
                    @if($visit->treatment_plan)
                    <hr>
                    <h6>خطة العلاج</h6>
                    <p>{{ $visit->treatment_plan }}</p>
                    @endif
                    @if($visit->notes)
                    <hr>
                    <h6>ملاحظات إضافية</h6>
                    <p class="text-muted">{{ $visit->notes }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- الطلبات الطبية -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>
                        الطلبات الطبية
                    </h5>
                </div>
                <div class="card-body">
                    @if($visit->requests->count() > 0)
                        <div class="row">
                            @foreach($visit->requests as $request)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100 border-{{ $request->status == 'completed' ? 'success' : ($request->status == 'pending' ? 'warning' : 'info') }}">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-{{ $request->type == 'lab' ? 'flask' : ($request->type == 'radiology' ? 'x-ray' : 'pills') }} me-2"></i>
                                            {{ $request->type_text }}
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2">
                                            <strong>الوصف:</strong>
                                            <small>{{ Str::limit($request->details['description'] ?? '', 50) }}</small>
                                        </p>
                                        <p class="mb-2">
                                            <strong>تاريخ الطلب:</strong>
                                            <small>{{ $request->created_at->format('Y-m-d H:i') }}</small>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-{{ $request->status == 'completed' ? 'success' : ($request->status == 'pending' ? 'warning' : 'info') }}">
                                                {{ $request->status_text }}
                                            </span>
                                        </div>
                                        @if($request->result)
                                        <hr>
                                        <p class="mb-0"><strong>النتيجة:</strong></p>
                                        <p class="text-muted small">{{ $request->result }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد طلبات طبية</h5>
                            <p class="text-muted">لم يتم طلب أي فحوصات أو أدوية في هذه الزيارة</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection