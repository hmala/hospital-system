@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>
                    <i class="fas fa-user-md me-2"></i>
                    عرض الزيارة (للاطلاع فقط)
                </h2>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>رجوع
                </a>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                هذه الزيارة ليست من زياراتك. يمكنك الاطلاع على البيانات فقط بدون إمكانية التعديل.
            </div>
        </div>
    </div>

    <!-- Patient Information -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-user me-2"></i>معلومات المريض</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>الاسم:</strong> {{ $visit->patient->user->name ?? 'N/A' }}</p>
                    <p><strong>العمر:</strong> {{ $visit->patient->age ?? 'N/A' }}</p>
                    <p><strong>فصيلة الدم:</strong> {{ $visit->patient->blood_type ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>الطبيب المعالج:</strong> {{ $visit->doctor->user->name ?? 'N/A' }}</p>
                    <p><strong>تاريخ الزيارة:</strong> {{ $visit->visit_date ? $visit->visit_date->format('Y-m-d') : 'N/A' }}</p>
                    <p><strong>نوع الزيارة:</strong> {{ $visit->visit_type_text ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Visit Details -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-notes-medical me-2"></i>تفاصيل الزيارة</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <h6>الشكوى الرئيسية:</h6>
                <p>{{ $visit->chief_complaint ?? 'لا توجد' }}</p>
            </div>

            @if($visit->vital_signs)
                <div class="mb-3">
                    <h6>العلامات الحيوية:</h6>
                    <div class="row">
                        @if(isset($visit->vital_signs['blood_pressure']))
                            <div class="col-md-3">
                                <strong>ضغط الدم:</strong> {{ $visit->vital_signs['blood_pressure'] }}
                            </div>
                        @endif
                        @if(isset($visit->vital_signs['temperature']))
                            <div class="col-md-3">
                                <strong>الحرارة:</strong> {{ $visit->vital_signs['temperature'] }}°C
                            </div>
                        @endif
                        @if(isset($visit->vital_signs['heart_rate']))
                            <div class="col-md-3">
                                <strong>نبض القلب:</strong> {{ $visit->vital_signs['heart_rate'] }} bpm
                            </div>
                        @endif
                        @if(isset($visit->vital_signs['oxygen_saturation']))
                            <div class="col-md-3">
                                <strong>الأوكسجين:</strong> {{ $visit->vital_signs['oxygen_saturation'] }}%
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($visit->physical_examination)
                <div class="mb-3">
                    <h6>الفحص السريري:</h6>
                    <p>{{ $visit->physical_examination }}</p>
                </div>
            @endif

            @if($visit->diagnosis)
                <div class="mb-3">
                    <h6>التشخيص:</h6>
                    <p>{{ is_array($visit->diagnosis) ? ($visit->diagnosis['description'] ?? '') : $visit->diagnosis }}</p>
                </div>
            @endif

            @if($visit->treatment_plan)
                <div class="mb-3">
                    <h6>خطة العلاج:</h6>
                    <p>{{ $visit->treatment_plan }}</p>
                </div>
            @endif

            @if($visit->notes)
                <div class="mb-3">
                    <h6>ملاحظات:</h6>
                    <p>{{ $visit->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Medications -->
    @if($prescribedMedications->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-pills me-2"></i>الأدوية الموصوفة</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>الدواء</th>
                                <th>الجرعة</th>
                                <th>التكرار</th>
                                <th>المدة</th>
                                <th>التعليمات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prescribedMedications as $med)
                                <tr>
                                    <td>{{ $med->name }}</td>
                                    <td>{{ $med->dosage ?? '-' }}</td>
                                    <td>{{ $med->frequency ?? '-' }}</td>
                                    <td>{{ $med->duration ?? '-' }}</td>
                                    <td>{{ $med->instructions ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Medical Requests -->
    @if($visit->requests->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-vial me-2"></i>الطلبات الطبية</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>النوع</th>
                                <th>التفاصيل</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($visit->requests as $request)
                                <tr>
                                    <td>
                                        @if($request->type === 'lab')
                                            <i class="fas fa-flask me-1"></i>مختبر
                                        @elseif($request->type === 'radiology')
                                            <i class="fas fa-x-ray me-1"></i>أشعة
                                        @else
                                            {{ $request->type }}
                                        @endif
                                    </td>
                                    <td>{{ $request->details ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $request->status === 'completed' ? 'success' : 'warning' }}">
                                            {{ $request->status }}
                                        </span>
                                    </td>
                                    <td>{{ $request->created_at->format('Y-m-d') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
