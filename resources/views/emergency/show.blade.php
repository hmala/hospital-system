<!-- resources/views/emergency/show.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-ambulance me-2"></i>
                    تفاصيل حالة الطوارئ #{{ $emergency->id }}
                    @if($emergency->services && $emergency->services->count() > 0)
                        <span class="badge bg-success ms-2">{{ $emergency->services->count() }} خدمة</span>
                    @endif
                    @if($emergency->payment)
                        <span class="badge {{ $emergency->payment->paid_at ? 'bg-success' : 'bg-warning' }} ms-2">
                            {{ $emergency->payment->paid_at ? 'مدفوع' : 'معلق' }}
                        </span>
                    @endif
                </h2>
                <div class="no-print">
                    <button onclick="window.print()" class="btn btn-primary me-2">
                        <i class="fas fa-print me-2"></i>طباعة
                    </button>
                    <a href="{{ route('emergency.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-right me-2"></i>العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- معلومات المريض -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-injured me-2"></i>معلومات المريض
                    </h5>
                </div>
                <div class="card-body">
                    @if($emergency->patient)
                        <div class="text-center mb-3">
                            <div class="avatar-lg bg-primary rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3">
                                <span class="text-white fw-bold fs-1">
                                    {{ substr($emergency->patient->user->name ?? '؟', 0, 1) }}
                                </span>
                            </div>
                            <h5>{{ $emergency->patient->user->name ?? 'مريض بدون بيانات' }}</h5>
                            <p class="text-muted">{{ $emergency->patient->user->phone ?? 'غير متوفر' }}</p>
                            @if($emergency->services && $emergency->services->count() > 0)
                                <div class="mt-2">
                                    <small class="text-success fw-bold">
                                        <i class="fas fa-money-bill-wave me-1"></i>
                                        التكلفة: {{ number_format($emergency->services->sum('price'), 2) }} IQD
                                    </small>
                                </div>
                            @endif
                        </div>

                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted">العمر</small>
                                <br>
                                <strong>{{ $emergency->patient->age ?? 'غير محدد' }} سنة</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted">فصيلة الدم</small>
                                <br>
                                <strong>{{ $emergency->patient->blood_type ?? 'غير محدد' }}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted">الخدمات</small>
                                <br>
                                <strong class="text-success">{{ $emergency->services ? $emergency->services->count() : 0 }}</strong>
                            </div>
                        </div>
                    @elseif($emergency->emergencyPatient)
                        <div class="text-center mb-3">
                            <div class="avatar-lg bg-secondary rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3">
                                <span class="text-white fw-bold fs-1">
                                    {{ substr($emergency->emergencyPatient->name ?? '؟', 0, 1) }}
                                </span>
                            </div>
                            <h5>{{ $emergency->emergencyPatient->name }}</h5>
                            <p class="text-muted">{{ $emergency->emergencyPatient->phone ?? 'غير متوفر' }}</p>
                            <p class="text-warning"><em>سجل طوارئ مؤقت</em></p>
                        </div>
                    @else
                        <div class="text-center">
                            <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                            <p class="text-muted">معلومات المريض غير متوفرة</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- حالة الطوارئ -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>حالة الطوارئ
                        </h5>
                        <div>
                            <span class="badge {{ $emergency->priority_badge_class }} me-2">{{ $emergency->priority_text }}</span>
                            <span class="badge {{ $emergency->status_badge_class }}">{{ $emergency->status_text }}</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>نوع الطوارئ:</strong> {{ $emergency->emergency_type_text }}</p>
                            <p><strong>وقت الدخول:</strong> {{ $emergency->created_at->format('d/m/Y H:i') }}</p>
                            @if($emergency->doctor)
                                <p><strong>الطبيب المسؤول:</strong> د. {{ $emergency->doctor->user->name ?? 'غير محدد' }}</p>
                            @endif
                            @if($emergency->nurse)
                                <p><strong>الممرض المسؤول:</strong> {{ $emergency->nurse->user->name ?? 'غير محدد' }}</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <p><strong>الأولوية:</strong> <span class="badge {{ $emergency->priority_badge_class }}">{{ $emergency->priority_text }}</span></p>
                            <p><strong>الحالة:</strong> <span class="badge {{ $emergency->status_badge_class }}">{{ $emergency->status_text }}</span></p>
                            @if($emergency->discharged_at)
                                <p><strong>وقت المغادرة:</strong> {{ $emergency->discharged_at->format('d/m/Y H:i') }}</p>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <h6>وصف الحالة:</h6>
                        <p class="text-muted">{{ $emergency->description }}</p>
                    </div>

                    @if($emergency->required_actions)
                    <div class="mb-3">
                        <h6>الإجراءات المطلوبة:</h6>
                        <p class="text-muted">{{ $emergency->required_actions }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    <!-- معلومات التشخيص والخدمات -->
    <div class="row mb-4">
        <!-- التشخيص والعلاج -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-stethoscope me-2"></i>التشخيص والعلاج
                    </h5>
                </div>
                <div class="card-body">
                    @if($emergency->diagnosis)
                        <div class="mb-3">
                            <h6><i class="fas fa-diagnoses me-2"></i>التشخيص:</h6>
                            <p class="text-muted">{{ $emergency->diagnosis }}</p>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-stethoscope fa-2x text-muted mb-2"></i>
                            <p class="text-muted">لم يتم تسجيل تشخيص بعد</p>
                        </div>
                    @endif

                    @if($emergency->treatment_given)
                        <div class="mb-3">
                            <h6><i class="fas fa-pills me-2"></i>العلاج المقدم:</h6>
                            <p class="text-muted">{{ $emergency->treatment_given }}</p>
                        </div>
                    @endif

                    @if($emergency->treatment_plan)
                        <div class="mb-3">
                            <h6><i class="fas fa-clipboard-list me-2"></i>الخطة العلاجية:</h6>
                            <p class="text-muted">{{ $emergency->treatment_plan }}</p>
                        </div>
                    @endif

                    @if($emergency->required_actions)
                        <div class="mb-3">
                            <h6><i class="fas fa-tasks me-2"></i>الإجراءات المطلوبة:</h6>
                            <p class="text-muted">{{ $emergency->required_actions }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- الخدمات المقدمة -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-concierge-bell me-2"></i>الخدمات المقدمة
                    </h5>
                </div>
                <div class="card-body">
                    @if($emergency->services && $emergency->services->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>الخدمة</th>
                                        <th>السعر</th>
                                        <th>الوصف</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($emergency->services as $service)
                                    <tr>
                                        <td>
                                            <strong>{{ $service->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">{{ number_format($service->price, 2) }} IQD</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $service->description ?? 'لا يوجد وصف' }}</small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <th>المجموع الكلي</th>
                                        <th colspan="2">{{ number_format($emergency->services->sum('price'), 2) }} IQD</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-concierge-bell fa-2x text-muted mb-2"></i>
                            <p class="text-muted">لم يتم تحديد خدمات بعد</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- معلومات الدفع -->
    @if($emergency->payment)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i>معلومات الدفع
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h6>حالة الدفع</h6>
                                @if($emergency->payment->paid_at)
                                    <span class="badge bg-success fs-6">مدفوع</span>
                                @else
                                    <span class="badge bg-warning fs-6">معلق</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h6>المبلغ</h6>
                                <h4 class="text-success">{{ number_format($emergency->payment->amount, 2) }} IQD</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h6>طريقة الدفع</h6>
                                <span class="badge bg-info">{{ $emergency->payment->payment_method_name }}</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                @if($emergency->payment->paid_at)
                                    <h6>تاريخ الدفع</h6>
                                    <small>{{ $emergency->payment->paid_at->format('d/m/Y H:i') }}</small>
                                @else
                                    <h6>رقم الإيصال</h6>
                                    <small class="text-muted">غير متوفر</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- طلبات التحاليل والأشعة ونتائجها -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-flask me-2"></i>طلبات التحاليل
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $labRequests = $emergency->labRequests->sortByDesc('requested_at');
                    @endphp
                    @if($labRequests->count() > 0)
                        @foreach($labRequests as $labRequest)
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="fw-bold">طلب #{{ $labRequest->id }}</small>
                                    <span class="badge {{ $labRequest->status == 'completed' ? 'bg-success' : ($labRequest->status == 'in_progress' ? 'bg-info' : 'bg-warning text-dark') }}">
                                        {{ $labRequest->status_text }}
                                    </span>
                                </div>
                                <small class="text-muted d-block mb-1">{{ optional($labRequest->requested_at)->format('d/m/Y H:i') }}</small>
                                @if($labRequest->status == 'completed')
                                    @php
                                        $completedLabResults = $labRequest->labTests
                                            ->filter(function($test){ return !empty(trim((string)($test->pivot->result ?? ''))); });
                                    @endphp
                                    @if($completedLabResults->count())
                                        @foreach($completedLabResults as $test)
                                            <small class="d-block">{{ $test->name }}: <span class="text-muted">{{ $test->pivot->result }}</span></small>
                                        @endforeach
                                    @else
                                        <small class="text-muted">تم الإكمال بدون نتائج مدخلة</small>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0">لا توجد طلبات تحاليل لهذه الحالة</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-x-ray me-2"></i>طلبات الأشعة
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $radiologyRequests = $emergency->radiologyRequests->sortByDesc('requested_at');
                    @endphp
                    @if($radiologyRequests->count() > 0)
                        @foreach($radiologyRequests as $radiologyRequest)
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="fw-bold">طلب #{{ $radiologyRequest->id }}</small>
                                    <span class="badge {{ $radiologyRequest->status == 'completed' ? 'bg-success' : ($radiologyRequest->status == 'in_progress' ? 'bg-info' : 'bg-warning text-dark') }}">
                                        {{ $radiologyRequest->status_text }}
                                    </span>
                                </div>
                                <small class="text-muted d-block mb-1">{{ optional($radiologyRequest->requested_at)->format('d/m/Y H:i') }}</small>
                                @if($radiologyRequest->status == 'completed')
                                    @php
                                        $completedRadiologyResults = $radiologyRequest->radiologyTypes
                                            ->filter(function($type){ return !empty(trim((string)($type->pivot->result ?? ''))); });
                                    @endphp
                                    @if($completedRadiologyResults->count())
                                        @foreach($completedRadiologyResults as $type)
                                            <small class="d-block">{{ $type->name }}: <span class="text-muted">{{ $type->pivot->result }}</span></small>
                                        @endforeach
                                    @else
                                        <small class="text-muted">تم الإكمال بدون نتائج مدخلة</small>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0">لا توجد طلبات أشعة لهذه الحالة</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-heartbeat me-2"></i>العلامات الحيوية
                        </h5>
                        @if($emergency->status !== 'discharged' && $emergency->status !== 'transferred')
                        <form action="{{ route('emergency.update-vitals', $emergency) }}" method="POST" class="d-inline no-print">
                            @csrf
                            @method('POST')
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-plus me-2"></i>تحديث العلامات الحيوية
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2">
                            <div class="p-3 border rounded">
                                <i class="fas fa-tachometer-alt fa-2x text-primary mb-2"></i>
                                <h6>ضغط الدم</h6>
                                <h4 class="text-primary">{{ $emergency->blood_pressure ?? '---' }}</h4>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3 border rounded">
                                <i class="fas fa-heart fa-2x text-danger mb-2"></i>
                                <h6>معدل ضربات القلب</h6>
                                <h4 class="text-danger">{{ $emergency->heart_rate ?? '---' }} <small>bpm</small></h4>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3 border rounded">
                                <i class="fas fa-thermometer-half fa-2x text-warning mb-2"></i>
                                <h6>درجة الحرارة</h6>
                                <h4 class="text-warning">{{ $emergency->temperature ?? '---' }} <small>°C</small></h4>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3 border rounded">
                                <i class="fas fa-lungs fa-2x text-success mb-2"></i>
                                <h6>تشبع الأكسجين</h6>
                                <h4 class="text-success">{{ $emergency->oxygen_saturation ?? '---' }} <small>%</small></h4>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3 border rounded">
                                <i class="fas fa-wind fa-2x text-info mb-2"></i>
                                <h6>معدل التنفس</h6>
                                <h4 class="text-info">{{ $emergency->respiratory_rate ?? '---' }} <small>/min</small></h4>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3 border rounded">
                                <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                                <h6>آخر تحديث</h6>
                                <small class="text-muted">{{ $emergency->vitals_last_updated ? $emergency->vitals_last_updated->diffForHumans() : 'لم يتم التحديث' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الأدوية والعلاجات -->
    @if(isset($emergency->prescribed_medications) && $emergency->prescribed_medications->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-pills me-2"></i>الأدوية الموصوفة
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>الدواء</th>
                                    <th>الجرعة</th>
                                    <th>التكرار</th>
                                    <th>المدة</th>
                                    <th>تعليمات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emergency->prescribed_medications as $medication)
                                <tr>
                                    <td><strong>{{ $medication->medication->name ?? $medication->medication_name }}</strong></td>
                                    <td>{{ $medication->dosage }}</td>
                                    <td>{{ $medication->frequency }}</td>
                                    <td>{{ $medication->duration }}</td>
                                    <td><small class="text-muted">{{ $medication->instructions }}</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- الإجراءات والتاريخ -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>الإجراءات والتاريخ
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">دخول الطوارئ</h6>
                                <p class="timeline-text">{{ $emergency->created_at->format('d/m/Y H:i') }}</p>
                                <small class="text-muted">تم إنشاء حالة الطوارئ</small>
                            </div>
                        </div>

                        @if($emergency->diagnosis)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">تسجيل التشخيص</h6>
                                <p class="timeline-text">{{ $emergency->updated_at->format('d/m/Y H:i') }}</p>
                                <small class="text-muted">تم تسجيل التشخيص الطبي</small>
                            </div>
                        </div>
                        @endif

                        @if($emergency->services && $emergency->services->count() > 0)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">تحديد الخدمات</h6>
                                <p class="timeline-text">{{ $emergency->updated_at->format('d/m/Y H:i') }}</p>
                                <small class="text-muted">تم تحديد {{ $emergency->services->count() }} خدمة طبية</small>
                            </div>
                        </div>
                        @endif

                        @if($emergency->payment)
                        <div class="timeline-item">
                            <div class="timeline-marker {{ $emergency->payment->paid_at ? 'bg-success' : 'bg-warning' }}"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">{{ $emergency->payment->paid_at ? 'تسديد الدفعة' : 'إنشاء دفعة' }}</h6>
                                <p class="timeline-text">{{ $emergency->payment->created_at->format('d/m/Y H:i') }}</p>
                                <small class="text-muted">
                                    {{ $emergency->payment->paid_at ? 'تم تسديد المبلغ: ' . number_format($emergency->payment->amount, 2) . ' IQD' : 'دفعة معلقة: ' . number_format($emergency->payment->amount, 2) . ' IQD' }}
                                </small>
                            </div>
                        </div>
                        @endif

                        @if($emergency->discharged_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-secondary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">المغادرة</h6>
                                <p class="timeline-text">{{ $emergency->discharged_at->format('d/m/Y H:i') }}</p>
                                <small class="text-muted">تم مغادرة الطوارئ</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}

.timeline-title {
    margin-bottom: 5px;
    font-weight: bold;
}

.timeline-text {
    margin-bottom: 5px;
    color: #6c757d;
}
</style>

<style media="print">
    /* إخفاء جميع العناصر الأصلية */
    * {
        display: none !important;
    }

    /* إظهار الجسم فقط مع المحتوى المطبوع */
    body {
        display: block !important;
        font-family: Arial, sans-serif;
        font-size: 12pt;
        line-height: 1.6;
        color: #000;
        background: #fff !important;
        margin: 20px;
        direction: rtl;
    }

    /* إنشاء تخطيط منظم للطباعة */
    body::before {
        content: "تقرير حالة الطوارئ";
        display: block;
        font-size: 18pt;
        font-weight: bold;
        text-align: center;
        margin-bottom: 20px;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
    }

    /* إنشاء جدول للمعلومات */
    body::after {
        content: "";
        display: table;
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    /* إنشاء صفوف الجدول باستخدام pseudo-elements */
    body::after {
        content:
            "معلومات عامة" "\A"
            "رقم الحالة: <?php echo $emergency->id; ?>" "\A"
            "وقت الدخول: <?php echo $emergency->created_at->format('d/m/Y H:i'); ?>" "\A"
            "الحالة: <?php echo $emergency->status_text; ?>" "\A"
            "الأولوية: <?php echo $emergency->priority_text; ?>" "\A"
            "نوع الطوارئ: <?php echo $emergency->emergency_type_text; ?>" "\A"
            "" "\A"
            "معلومات المريض" "\A"
            "الاسم: <?php echo $emergency->patient ? $emergency->patient->user->name : ($emergency->emergencyPatient ? $emergency->emergencyPatient->name : 'غير محدد'); ?>" "\A"
            "الهاتف: <?php echo $emergency->patient ? $emergency->patient->user->phone : ($emergency->emergencyPatient ? $emergency->emergencyPatient->phone : 'غير محدد'); ?>" "\A"
            "العمر: <?php echo $emergency->patient ? $emergency->patient->age : 'غير محدد'; ?> سنة" "\A"
            "فصيلة الدم: <?php echo $emergency->patient ? $emergency->patient->blood_type : 'غير محدد'; ?>" "\A"
            "" "\A"
            "الفريق الطبي" "\A"
            "الطبيب المسؤول: <?php echo $emergency->doctor ? 'د. ' . $emergency->doctor->user->name : 'غير محدد'; ?>" "\A"
            "الممرض المسؤول: <?php echo $emergency->nurse ? $emergency->nurse->user->name : 'غير محدد'; ?>" "\A"
            "" "\A"
            "العلامات الحيوية" "\A"
            "ضغط الدم: <?php echo $emergency->blood_pressure ?? '---'; ?>" "\A"
            "معدل ضربات القلب: <?php echo $emergency->heart_rate ?? '---'; ?> bpm" "\A"
            "درجة الحرارة: <?php echo $emergency->temperature ?? '---'; ?> °C" "\A"
            "تشبع الأكسجين: <?php echo $emergency->oxygen_saturation ?? '---'; ?> %" "\A"
            "معدل التنفس: <?php echo $emergency->respiratory_rate ?? '---'; ?> /min" "\A"
            "آخر تحديث: <?php echo $emergency->vitals_last_updated ? $emergency->vitals_last_updated->format('d/m/Y H:i') : 'لم يتم التحديث'; ?>" "\A"
            "" "\A"
            "التفاصيل الطبية" "\A"
            "وصف الحالة: <?php echo $emergency->description; ?>" "\A"
            "<?php if($emergency->required_actions): ?>" "\A"
            "الإجراءات المطلوبة: <?php echo $emergency->required_actions; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->diagnosis): ?>" "\A"
            "التشخيص: <?php echo $emergency->diagnosis; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->treatment_given): ?>" "\A"
            "العلاج المقدم: <?php echo $emergency->treatment_given; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->treatment_plan): ?>" "\A"
            "الخطة العلاجية: <?php echo $emergency->treatment_plan; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->discharged_at): ?>" "\A"
            "وقت المغادرة: <?php echo $emergency->discharged_at->format('d/m/Y H:i'); ?>" "\A"
            "<?php endif; ?>";
        white-space: pre-line;
        font-family: Arial, sans-serif;
        font-size: 12pt;
        line-height: 1.8;
        display: block;
    }

    /* تنسيق العناوين في المحتوى */
    body::after {
        content: "" "\A" "═══════════════════════════════════════════════" "\A" "معلومات عامة" "\A" "═══════════════════════════════════════════════" "\A"
            "رقم الحالة: <?php echo $emergency->id; ?>" "\A"
            "وقت الدخول: <?php echo $emergency->created_at->format('d/m/Y H:i'); ?>" "\A"
            "الحالة: <?php echo $emergency->status_text; ?>" "\A"
            "الأولوية: <?php echo $emergency->priority_text; ?>" "\A"
            "نوع الطوارئ: <?php echo $emergency->emergency_type_text; ?>" "\A"
            "" "\A"
            "═══════════════════════════════════════════════" "\A" "معلومات المريض" "\A" "═══════════════════════════════════════════════" "\A"
            "الاسم: <?php echo $emergency->patient ? $emergency->patient->user->name : ($emergency->emergencyPatient ? $emergency->emergencyPatient->name : 'غير محدد'); ?>" "\A"
            "الهاتف: <?php echo $emergency->patient ? $emergency->patient->user->phone : ($emergency->emergencyPatient ? $emergency->emergencyPatient->phone : 'غير محدد'); ?>" "\A"
            "العمر: <?php echo $emergency->patient ? $emergency->patient->age : 'غير محدد'; ?> سنة" "\A"
            "فصيلة الدم: <?php echo $emergency->patient ? $emergency->patient->blood_type : 'غير محدد'; ?>" "\A"
            "" "\A"
            "═══════════════════════════════════════════════" "\A" "الفريق الطبي" "\A" "═══════════════════════════════════════════════" "\A"
            "الطبيب المسؤول: <?php echo $emergency->doctor ? 'د. ' . $emergency->doctor->user->name : 'غير محدد'; ?>" "\A"
            "الممرض المسؤول: <?php echo $emergency->nurse ? $emergency->nurse->user->name : 'غير محدد'; ?>" "\A"
            "" "\A"
            "═══════════════════════════════════════════════" "\A" "العلامات الحيوية" "\A" "═══════════════════════════════════════════════" "\A"
            "ضغط الدم: <?php echo $emergency->blood_pressure ?? '---'; ?>" "\A"
            "معدل ضربات القلب: <?php echo $emergency->heart_rate ?? '---'; ?> bpm" "\A"
            "درجة الحرارة: <?php echo $emergency->temperature ?? '---'; ?> °C" "\A"
            "تشبع الأكسجين: <?php echo $emergency->oxygen_saturation ?? '---'; ?> %" "\A"
            "معدل التنفس: <?php echo $emergency->respiratory_rate ?? '---'; ?> /min" "\A"
            "آخر تحديث: <?php echo $emergency->vitals_last_updated ? $emergency->vitals_last_updated->format('d/m/Y H:i') : 'لم يتم التحديث'; ?>" "\A"
            "" "\A"
            "═══════════════════════════════════════════════" "\A" "التفاصيل الطبية" "\A" "═══════════════════════════════════════════════" "\A"
            "وصف الحالة: <?php echo $emergency->description; ?>" "\A"
            "<?php if($emergency->required_actions): ?>" "\A"
            "الإجراءات المطلوبة: <?php echo $emergency->required_actions; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->diagnosis): ?>" "\A"
            "التشخيص: <?php echo $emergency->diagnosis; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->treatment_given): ?>" "\A"
            "العلاج المقدم: <?php echo $emergency->treatment_given; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->treatment_plan): ?>" "\A"
            "الخطة العلاجية: <?php echo $emergency->treatment_plan; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->discharged_at): ?>" "\A"
            "وقت المغادرة: <?php echo $emergency->discharged_at->format('d/m/Y H:i'); ?>" "\A"
            "<?php endif; ?>";
    }

    /* إعدادات الصفحة */
    @page {
        margin: 1cm;
        size: A4;
    }
</style>
@endsection