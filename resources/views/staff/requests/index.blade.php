@extends('layouts.app')

@section('content')
<div class="container-fluid" id="requests-content">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-clipboard-list me-2"></i>
                        إدارة الطلبات الطبية
                        <span class="badge bg-success" id="live-indicator">
                            <i class="fas fa-circle fa-xs"></i> مباشر
                        </span>
                    </h2>
                    <p class="text-muted mb-0">
                        مرحباً {{ auth()->user()->name }} - 
                        <small id="last-update">آخر تحديث: الآن</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- فلاتر الطلبات -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>
                        فلترة الطلبات
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($allowedTypes as $allowedType)
                        <div class="col-md-4 mb-2">
                            <a href="{{ route('staff.requests.index', $allowedType) }}"
                               class="btn btn-outline-{{ $type == $allowedType ? 'primary' : 'secondary' }} w-100">
                                <i class="fas fa-{{ $allowedType == 'lab' ? 'flask' : ($allowedType == 'radiology' ? 'x-ray' : ($allowedType == 'pharmacy' ? 'pills' : ($allowedType == 'nursing' ? 'stethoscope' : 'tint'))) }} me-2"></i>
                                {{ $allowedType == 'lab' ? 'المختبر' : ($allowedType == 'radiology' ? 'الأشعة' : ($allowedType == 'pharmacy' ? 'الصيدلية' : ($allowedType == 'nursing' ? 'الخدمات التمريضية' : 'مصرف الدم'))) }}
                                @if($type == $allowedType)
                                    <span class="badge bg-primary ms-2">{{ $requests->total() }}</span>
                                @endif
                            </a>
                        </div>
                        @endforeach
                        <div class="col-md-4 mb-2">
                            <a href="{{ route('staff.requests.index') }}"
                               class="btn btn-outline-{{ !$type ? 'primary' : 'secondary' }} w-100">
                                <i class="fas fa-list me-2"></i>
                                الكل
                                @if(!$type)
                                    <span class="badge bg-primary ms-2">{{ $requests->total() }}</span>
                                @endif
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- إعادة تصميم كامل بصندوق طيات (Accordion) -->
    <div class="accordion" id="requestsAccordion">
        <!-- البند الأول: الطلبات العادية -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingNormal">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNormal" aria-expanded="true" aria-controls="collapseNormal">
                    الطلبات العادية ({{ $requests->total() }})
                </button>
            </h2>
            <div id="collapseNormal" class="accordion-collapse collapse show" aria-labelledby="headingNormal" data-bs-parent="#requestsAccordion">
                <div class="accordion-body">
                    @php
                        $hasEmergency = false;
                        if(isset($emergencyRadiologyRequests) && $emergencyRadiologyRequests->count() > 0) {
                            $hasEmergency = true;
                        }
                        if(isset($emergencyLabRequests) && $emergencyLabRequests->count() > 0) {
                            $hasEmergency = true;
                        }
                    @endphp

                    @if($requests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th style="width:70px;">رقم</th>
                                        <th>مريض</th>
                                        <th>طبيب</th>
                                        <th>نوع</th>
                                        <th style="width:90px;">وقت</th>
                                        <th style="width:90px;">الدفع</th>
                                        <th style="width:70px;">حالة</th>
                                        <th style="width:100px;">إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $request)
                                    <tr class="{{ $request->payment_status == 'pending' ? 'table-danger' : ($request->payment_status == 'paid' ? 'table-success' : '') }}">
                                        <td>#{{ $request->id }}</td>
                                        <td>{{ $request->visit?->patient?->user?->name ?? 'غير محدد' }}</td>
                                        <td>د. {{ $request->visit?->doctor?->user?->name ?? 'غير محدد' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $request->type == 'lab' ? 'primary' : ($request->type == 'radiology' ? 'info' : ($request->type == 'pharmacy' ? 'success' : 'danger')) }}">
                                                {{ $request->type_text }}
                                            </span>
                                        </td>
                                        <td>
                                            <small>{{ $request->created_at->format('H:i') }}</small>
                                        </td>
                                        <td>
                                            @if($request->payment_status == 'paid')
                                                <span class="badge bg-success">مدفوع</span>
                                            @elseif($request->payment_status == 'pending')
                                                <span class="badge bg-danger">غير مدفوع</span>
                                            @else
                                                <span class="badge bg-secondary">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-sm bg-{{ $request->status == 'completed' ? 'success' : ($request->status == 'pending' ? 'warning' : 'info') }}">
                                                {{ $request->status == 'completed' ? 'تم' : ($request->status == 'pending' ? 'معلق' : 'جاري') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('staff.requests.show', ['request' => $request->id]) }}"
                                                   class="btn btn-outline-primary"
                                                   title="عرض الطلب">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @php
                                                    $isBloodBankRequest = $request->type === 'blood_bank' || (is_array($request->details ?? []) && data_get($request->details, 'blood_bank', false));
                                                    $radiologyRequest = null;
                                                    if ($request->type === 'radiology') {
                                                        $radiologyRequest = \App\Models\RadiologyRequest::where('visit_id', $request->visit_id)
                                                            ->latest('created_at')
                                                            ->first();
                                                    }
                                                @endphp

                                                @if(($request->status == 'completed' || $request->status == 'in_progress') && ($request->type == 'lab' || $isBloodBankRequest || $request->type == 'blood_bank'))
                                                    @if($request->payment_status == 'paid')
                                                        <a href="{{ route('staff.requests.print', $request) }}"
                                                           class="btn btn-outline-success"
                                                           target="_blank"
                                                           title="طباعة النتائج">
                                                            <i class="fas fa-print"></i>
                                                        </a>
                                                    @else
                                                        <button class="btn btn-outline-secondary" disabled title="لا يمكن الطباعة حتى يتم الدفع في الكاشير">
                                                            <i class="fas fa-print"></i>
                                                        </button>
                                                        <span class="badge bg-danger ms-2" title="يجب سداد الفاتورة في الكاشير قبل الطباعة">
                                                            مطلوب دفع الكاشير
                                                        </span>
                                                    @endif
                                                @endif
                                                @if($request->status == 'completed' && $request->type == 'radiology' && $radiologyRequest && $radiologyRequest->result)
                                                    <a href="{{ route('radiology.print', $radiologyRequest->id) }}"
                                                       class="btn btn-outline-success"
                                                       target="_blank"
                                                       title="طباعة نتائج الأشعة">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach

                                    @if((is_null($type) || $type == 'lab') && isset($emergencyLabRequests) && $emergencyLabRequests->count() > 0)
                                        @foreach($emergencyLabRequests as $emergencyRequest)
                                        <tr class="{{ $emergencyRequest->status == 'pending' ? 'table-warning' : ($emergencyRequest->status == 'in_progress' ? 'table-info' : ($emergencyRequest->status == 'completed' ? 'table-success' : '')) }}">
                                            <td>#E{{ $emergencyRequest->id }}</td>
                                            <td>{{ optional(optional($emergencyRequest->patient)->user)->name ?? 'غير محدد' }}</td>
                                            <td>-</td>
                                            <td><span class="badge bg-primary">تحاليل طوارئ</span></td>
                                            <td>
                                                @foreach($emergencyRequest->labTests as $test)
                                                    <span class="badge bg-primary me-1">{{ $test->name }}</span>
                                                @endforeach
                                            </td>
                                            <td><small>{{ optional($emergencyRequest->requested_at)->format('H:i') }}</small></td>
                                            <td>
                                                @if($emergencyRequest->emergency && $emergencyRequest->emergency->payment_status == 'paid')
                                                    <span class="badge bg-success">مدفوع</span>
                                                @else
                                                    <span class="badge bg-danger">غير مدفوع</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $emergencyRequest->status_badge_class }}">{{ $emergencyRequest->status_text }}</span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    @if($emergencyRequest->status == 'pending')
                                                        <form action="{{ route('staff.emergency-lab.start', $emergencyRequest) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-primary" title="بدء العمل">
                                                                <i class="fas fa-play"></i>
                                                            </button>
                                                        </form>
                                                    @elseif($emergencyRequest->status == 'in_progress')
                                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#completeEmergencyLabModal{{ $emergencyRequest->id }}" title="إكمال التحليل">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    @else
                                                        <span class="badge bg-success">تم</span>
                                                    @endif

                                                    @if($emergencyRequest->status == 'completed')
                                                        <a href="{{ route('staff.emergency-lab.print', $emergencyRequest) }}" class="btn btn-outline-secondary" target="_blank" title="طباعة النتائج">
                                                            <i class="fas fa-print"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $requests->links() }}
                        </div>
                    @else
                        @if(!$hasEmergency)
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد طلبات</h5>
                            <p class="text-muted">
                                {{ $type ? 'لا توجد طلبات في هذا القسم' : 'لا توجد طلبات متاحة لك' }}
                            </p>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- طلبات الطوارئ - الأشعة -->
    @if(isset($emergencyRadiologyRequests) && $emergencyRadiologyRequests->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-ambulance me-2"></i>
                        <i class="fas fa-x-ray me-2"></i>
                        طلبات الأشعة من الطوارئ
                        <span class="badge bg-light text-danger ms-2">{{ $emergencyRadiologyRequests->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th style="width:80px;">رقم طوارئ</th>
                                    <th>مريض</th>
                                    <th>أشعة</th>
                                    <th style="width:80px;">أولوية</th>
                                    <th style="width:70px;">وقت</th>
                                    <th style="width:70px;">حالة</th>
                                    <th style="width:120px;">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emergencyRadiologyRequests as $emergencyRequest)
                                <tr>
                                    <td>
                                        <strong class="text-danger">#{{ $emergencyRequest->emergency_id }}</strong>
                                    </td>
                                    <td>{{ $emergencyRequest->patient->user->name }}</td>
                                    <td>
                                        @foreach($emergencyRequest->radiologyTypes as $type)
                                            <span class="badge bg-info me-1">{{ $type->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $emergencyRequest->priority == 'critical' ? 'danger' : 'warning' }}">
                                            {{ $emergencyRequest->priority_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $emergencyRequest->requested_at->format('Y-m-d H:i') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $emergencyRequest->status_badge_class }}">
                                            {{ $emergencyRequest->status_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @if($emergencyRequest->status == 'pending')
                                                <form action="{{ route('staff.emergency-radiology.start', $emergencyRequest) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary" title="بدء العمل">
                                                        <i class="fas fa-play"></i> بدء
                                                    </button>
                                                </form>
                                            @elseif($emergencyRequest->status == 'in_progress')
                                                <a href="{{ route('staff.emergency-radiology.show', $emergencyRequest) }}"
                                                   class="btn btn-success"
                                                   title="إكمال الفحص">
                                                    <i class="fas fa-check"></i> إكمال
                                                </a>
                                            @else
                                                <a href="{{ route('staff.emergency-radiology.print', $emergencyRequest) }}"
                                                   class="btn btn-outline-success"
                                                   target="_blank"
                                                   title="طباعة نتائج أشعة الطوارئ">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
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
    
    <!-- طلبات الطوارئ - التحاليل -->
    @if(isset($emergencyLabRequests) && $emergencyLabRequests->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-ambulance me-2"></i>
                        <i class="fas fa-flask me-2"></i>
                        طلبات التحاليل من الطوارئ
                        <span class="badge bg-light text-danger ms-2">{{ $emergencyLabRequests->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th style="width:80px;">رقم طوارئ</th>
                                    <th>مريض</th>
                                    <th>تحاليل</th>
                                    <th style="width:80px;">أولوية</th>
                                    <th style="width:70px;">حالة</th>
                                    <th style="width:120px;">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emergencyLabRequests as $emergencyRequest)
                                <tr>
                                    <td>
                                        <strong class="text-danger">#{{ $emergencyRequest->emergency_id }}</strong>
                                    </td>
                                    <td>{{ $emergencyRequest->patient->user->name }}</td>
                                    <td>
                                        @foreach($emergencyRequest->labTests as $test)
                                            <span class="badge bg-primary me-1">{{ $test->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $emergencyRequest->priority == 'critical' ? 'danger' : 'warning' }}">
                                            {{ $emergencyRequest->priority_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $emergencyRequest->requested_at->format('Y-m-d H:i') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $emergencyRequest->status_badge_class }}">
                                            {{ $emergencyRequest->status_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @if($emergencyRequest->status == 'pending')
                                                <form action="{{ route('staff.emergency-lab.start', $emergencyRequest) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary" title="بدء العمل">
                                                        <i class="fas fa-play"></i> بدء
                                                    </button>
                                                </form>
                                            @elseif($emergencyRequest->status == 'in_progress')
                                                <button type="button" class="btn btn-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#completeEmergencyLabModal{{ $emergencyRequest->id }}"
                                                        title="إكمال التحليل">
                                                    <i class="fas fa-check"></i> إكمال
                                                </button>
                                            @else
                                                <span class="badge bg-success">تم</span>
                                            @endif

                                            @if($emergencyRequest->status == 'completed')
                                                <a href="{{ route('staff.emergency-lab.print', $emergencyRequest) }}" class="btn btn-outline-secondary" target="_blank" title="طباعة النتائج">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach($emergencyLabRequests as $emergencyRequest)
        @if($emergencyRequest->status == 'in_progress')
            <div class="modal fade" id="completeEmergencyLabModal{{ $emergencyRequest->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" data-bs-focus="false">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
                        <!-- الترويسة الأنيقة -->
                        <div class="modal-header bg-gradient-dark text-white p-3" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                            <div class="d-flex align-items-center">
                                <div class="icon-shape bg-primary text-white rounded-circle p-2 me-3 shadow-sm" style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-flask fa-lg"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title fw-bold mb-0">إدخال وتدقيق تحاليل الطوارئ #{{ $emergencyRequest->emergency_id }}</h5>
                                    <small class="text-white-50">ادخل النتائج وسيتم مقارنتها تلقائياً بالمعدلات المرجعية</small>
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form action="{{ route('staff.emergency-lab.complete', $emergencyRequest) }}" method="POST">
                            @csrf
                        @method('PUT')
                            <div class="modal-body p-4" style="background-color: #f8fafc;">
                                <!-- بطاقة معلومات المريض -->
                                <div class="p-3 mb-4 rounded-3 border" style="background: #ffffff; border-right: 4px solid #3b82f6 !important;">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-injured fa-2x text-primary me-3"></i>
                                                <div>
                                                    <div class="fw-bold text-dark fs-6">{{ $emergencyRequest->patient->user->name }}</div>
                                                    <small class="text-muted">رقم المريض: #{{ $emergencyRequest->patient->id }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 text-md-end mt-2 mt-md-0">
                                            <span class="badge bg-light text-dark border me-1">
                                                <i class="fas fa-calendar-alt text-secondary me-1"></i> العمر: {{ $emergencyRequest->patient->age ?? '-' }} سنة
                                            </span>
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-venus-mars text-secondary me-1"></i> الجنس: {{ ($emergencyRequest->patient->gender ?? $emergencyRequest->patient->user->gender ?? null) === 'female' ? 'أنثى' : 'ذكر' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="fw-bold text-secondary mb-3">
                                    <i class="fas fa-list-check me-2"></i> فحوصات الطلب الحالي:
                                </h6>

                                @foreach($emergencyRequest->labTests as $test)
                                @php
                                    $patientGender = $emergencyRequest->patient->gender ?? $emergencyRequest->patient->user->gender ?? 'male';
                                    $patientAge = $emergencyRequest->patient->age ?? 30;
                                    
                                    // 1. محاولة جلب المرجع المقترن بـ LabTestReference
                                    $refObj = $test->referenceForPatient($patientGender, (int)$patientAge) ?? $test->references->first();
                                    $refRangeText = $refObj?->range_display ?? '';
                                    $refMin = $refObj?->ref_min ?? '';
                                    $refMax = $refObj?->ref_max ?? '';
                                    $unit = $test->unit ?: ($refObj?->unit ?? '');

                                    // 2. الاحتياطي من قواعد LabResult المستعملة في التحاليل العادية
                                    if (empty($refRangeText)) {
                                        $labResultHelper = new \App\Models\LabResult();
                                        $refRangeText = $labResultHelper->getReferenceRange($test->name);
                                        if (empty($unit)) {
                                            $unit = $labResultHelper->getUnit($test->name);
                                        }
                                        if (preg_match('/(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)/', $refRangeText, $matches)) {
                                            $refMin = $matches[1];
                                            $refMax = $matches[2];
                                        } elseif (preg_match('/<\s*(\d+(?:\.\d+)?)/', $refRangeText, $matches)) {
                                            $refMax = $matches[1];
                                        } elseif (preg_match('/>=\s*(\d+(?:\.\d+)?)/', $refRangeText, $matches)) {
                                            $refMin = $matches[1];
                                        }
                                    }

                                    // استخراج القيم الحالية إذا كانت مخزنة سابقاً
                                    $prevRaw = $test->pivot->result ?? '';
                                    $prevData = is_string($prevRaw) ? json_decode($prevRaw, true) : $prevRaw;
                                    $prevVal = is_array($prevData) ? ($prevData['value'] ?? '') : (is_string($prevRaw) && !str_contains($prevRaw, '{') ? $prevRaw : '');
                                    $prevUnit = is_array($prevData) ? ($prevData['unit'] ?? $unit) : $unit;
                                    $prevRef = is_array($prevData) ? ($prevData['reference'] ?? $refRangeText) : $refRangeText;
                                @endphp

                                <!-- بطاقة الفحص -->
                                <div class="test-card p-3 mb-3 bg-white rounded-3 border shadow-sm" style="transition: all 0.2s ease;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="fw-bold text-dark fs-6">
                                            <i class="fas fa-microscope text-primary me-2"></i> {{ $test->name }}
                                        </span>
                                        @if($refRangeText)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill" style="font-size: 0.85rem;">
                                                <i class="fas fa-shield-halved me-1"></i> المعدل الطبيعي: {{ $refRangeText }} {{ $unit }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border px-3 py-1 rounded-pill" style="font-size: 0.85rem;">
                                                لا يوجد مدى مرجعي مسجل
                                            </span>
                                        @endif
                                    </div>

                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">النتيجة الرقمية</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="fas fa-pen text-secondary"></i></span>
                                                <input type="text" 
                                                       name="results[{{ $test->id }}][value]" 
                                                       class="form-control test-result-input fw-bold" 
                                                       placeholder="أدخل النتيجة" 
                                                       value="{{ $prevVal }}"
                                                       data-ref-min="{{ $refMin }}" 
                                                       data-ref-max="{{ $refMax }}" 
                                                       data-ref-range="{{ $refRangeText }}"
                                                       required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1">الوحدة</label>
                                            <input type="text" name="results[{{ $test->id }}][unit]" class="form-control bg-light" placeholder="الوحدة" value="{{ $prevUnit }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted mb-1">المرجع</label>
                                            <input type="text" name="results[{{ $test->id }}][reference]" class="form-control bg-light" placeholder="المرجع" value="{{ $prevRef }}">
                                        </div>
                                    </div>

                                    <!-- إشعار التقييم التلقائي المباشر -->
                                    <div class="result-status-alert alert d-none mt-3 mb-0 py-2 px-3 border-0 rounded-3" style="font-size: 0.9rem;">
                                    </div>
                                </div>
                                @endforeach

                                <div class="mt-3">
                                    <label class="form-label fw-bold text-dark">
                                        <i class="fas fa-comment-dots text-primary me-1"></i> ملاحظات إضافية
                                    </label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="اكتب أي ملاحظات أو توصيات للمختبر..." style="border-radius: 8px;">{{ $emergencyRequest->notes }}</textarea>
                                </div>
                            </div>

                            <div class="modal-footer bg-white border-top-0 p-3">
                                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">إلغاء</button>
                                <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm" style="border-radius: 8px;">
                                    <i class="fas fa-check-circle me-1"></i> حفظ نتائج التحليل
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
    @endif

</div>

@section('scripts')
<script>
// تقييم مباشر لحظي وعرض إشعار نتيجة الفحص عند كتابة القيمة
$(document).on('input', '.test-result-input', function() {
    const val = parseFloat($(this).val());
    const minStr = $(this).data('ref-min');
    const maxStr = $(this).data('ref-max');
    const refRange = $(this).data('ref-range') || '';
    const min = minStr !== '' ? parseFloat(minStr) : NaN;
    const max = maxStr !== '' ? parseFloat(maxStr) : NaN;
    const alertBox = $(this).closest('.test-card').find('.result-status-alert');

    if (isNaN(val) || (isNaN(min) && isNaN(max))) {
        alertBox.addClass('d-none').html('');
        return;
    }

    alertBox.removeClass('d-none alert-success alert-danger alert-warning');
    
    if (!isNaN(min) && val < min) {
        alertBox.addClass('alert-warning').html(
            `<div class="d-flex align-items-center">
                <i class="fas fa-arrow-down fa-lg me-2 text-warning"></i>
                <div>
                    <strong>إشعار النتيجة: منخفض ⬇</strong>
                    <div class="small">القيمة المُدخلة (<strong>${val}</strong>) أقل من الحد الطبيعي الأدنى (<strong>${min}</strong>).</div>
                </div>
            </div>`
        );
    } else if (!isNaN(max) && val > max) {
        alertBox.addClass('alert-danger').html(
            `<div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-lg me-2 text-danger"></i>
                <div>
                    <strong>إشعار النتيجة: مرتفع ⬆</strong>
                    <div class="small">القيمة المُدخلة (<strong>${val}</strong>) أعلى من الحد الطبيعي الأعلى (<strong>${max}</strong>).</div>
                </div>
            </div>`
        );
    } else {
        alertBox.addClass('alert-success').html(
            `<div class="d-flex align-items-center">
                <i class="fas fa-check-circle fa-lg me-2 text-success"></i>
                <div>
                    <strong>إشعار النتيجة: طبيعي ✓</strong>
                    <div class="small">القيمة المُدخلة (<strong>${val}</strong>) تقع ضمن المعدل الطبيعي الأصلي (<strong>${refRange || 'المحدد'}</strong>).</div>
                </div>
            </div>`
        );
    }
});

// تحديث تلقائي للصفحة كل 5 ثواني مع إيقاف المؤقت إذا كان هناك مودال مفتوح
setInterval(function() {
    if ($('.modal.show').length > 0) {
        return;
    }
    $.ajax({
        url: window.location.href,
        type: 'GET',
        success: function(response) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(response, 'text/html');
            const newContent = doc.getElementById('requests-content');
            
            if (newContent) {
                const currentScroll = window.scrollY;
                $('#requests-content').html($(newContent).html());
                window.scrollTo(0, currentScroll);
                
                const now = new Date();
                const time = now.toLocaleTimeString('ar-IQ');
                $('#last-update').text('آخر تحديث: ' + time);
            }
        }
    });
}, 5000);

$(document).ready(function() {
    const now = new Date();
    const time = now.toLocaleTimeString('ar-IQ');
    $('#last-update').text('آخر تحديث: ' + time);
    $('.modal').appendTo('body');
});
</script>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

#live-indicator {
    animation: pulse 2s ease-in-out infinite;
}

#live-indicator i {
    color: #fff;
}

/* تنسيقات خاصة بالجداول الصغيرة */
.table-sm td {
    padding: 0.4rem;
    font-size: 0.875rem;
    white-space: normal;
    word-break: break-word;
}

.table-sm th {
    padding: 0.4rem;
    font-size: 0.875rem;
    white-space: nowrap;
    word-break: normal;
}

.table-responsive table {
    min-width: max-content;
}

.badge-sm {
    font-size: 0.75rem;
    padding: 0.25em 0.5em;
}

.table-responsive {
    overflow-x: auto;
    max-width: 100%;
}
.modal-backdrop {
    display: none !important;
    opacity: 0 !important;
    pointer-events: none !important;
    z-index: -1 !important;
}
.modal {
    background: rgba(15, 23, 42, 0.55) !important;
    z-index: 10000 !important;
}
.modal-dialog { z-index: 10001 !important; }
.modal-content { position: relative; z-index: 10002 !important; pointer-events: auto !important; }
</style>
@endsection