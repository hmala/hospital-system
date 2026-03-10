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
                                <i class="fas fa-{{ $allowedType == 'lab' ? 'flask' : ($allowedType == 'radiology' ? 'x-ray' : 'pills') }} me-2"></i>
                                {{ $allowedType == 'lab' ? 'المختبر' : ($allowedType == 'radiology' ? 'الأشعة' : 'الصيدلية') }}
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
                                        <th style="width:140px;">مريض</th>
                                        <th style="width:120px;">طبيب</th>
                                        <th style="width:80px;">نوع</th>
                                        <th>تفاصيل</th>
                                        <th style="width:70px;">حالة</th>
                                        <th style="width:100px;">إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $request)
                                    <tr>
                                        <td>#{{ $request->id }}</td>
                                        <td>{{ $request->visit?->patient?->user?->name ?? 'غير محدد' }}</td>
                                        <td>د. {{ $request->visit?->doctor?->user?->name ?? 'غير محدد' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $request->type == 'lab' ? 'primary' : ($request->type == 'radiology' ? 'info' : 'success') }}">
                                                {{ $request->type_text }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                                            @endphp
                                            @if($request->type == 'lab' && isset($details['lab_test_ids']))
                                                <small class="text-muted">
                                                    @php
                                                        $testNames = [];
                                                        foreach($details['lab_test_ids'] as $testId) {
                                                            $test = \App\Models\LabTest::find($testId);
                                                            if ($test) {
                                                                $testNames[] = $test->name;
                                                            }
                                                        }
                                                    @endphp
                                                    {{ implode('، ', $testNames) }}
                                                </small>
                                            @elseif($request->type == 'radiology' && isset($details['radiology_type_ids']))
                                                <small class="text-muted">
                                                    @php
                                                        $typeNames = [];
                                                        foreach($details['radiology_type_ids'] as $typeId) {
                                                            $type = \App\Models\RadiologyType::find($typeId);
                                                            if ($type) {
                                                                $typeNames[] = $type->name;
                                                            }
                                                        }
                                                    @endphp
                                                    {{ implode('، ', $typeNames) }}
                                                </small>
                                            @else
                                                <small class="text-muted">{{ $request->description ?? '-' }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <small>{{ $request->created_at->format('H:i') }}</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-sm bg-{{ $request->status == 'completed' ? 'success' : ($request->status == 'pending' ? 'warning' : 'info') }}">
                                                {{ $request->status == 'completed' ? 'تم' : ($request->status == 'pending' ? 'معلق' : 'جاري') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                @if($request->type == 'radiology')
                                                    @php
                                                        // البحث عن طلب الأشعة المرتبط بنفس الزيارة
                                                        $radiologyRequest = \App\Models\RadiologyRequest::where('visit_id', $request->visit_id)
                                                            ->latest('created_at')
                                                            ->first();
                                                        
                                                        // إذا لم نجد سجل في radiology_requests، نحاول إنشاءه من بيانات الطلب
                                                        if (!$radiologyRequest && $request->details && isset($request->details['radiology_type_id'])) {
                                                            try {
                                                                $radiologyRequest = \App\Models\RadiologyRequest::create([
                                                                    'visit_id' => $request->visit_id,
                                                                    'patient_id' => $request->visit->patient_id ?? null,
                                                                    'doctor_id' => $request->visit->doctor_id ?? null,
                                                                    'radiology_type_id' => $request->details['radiology_type_id'],
                                                                    'requested_date' => $request->created_at,
                                                                    'status' => $request->status,
                                                                    'priority' => $request->details['priority'] ?? 'normal',
                                                                    'clinical_indication' => $request->description ?? null,
                                                                ]);
                                                            } catch (\Exception $e) {
                                                                // في حالة فشل الإنشاء، نستمر بدون إنشاء
                                                            }
                                                        }
                                                    @endphp
                                                    @if($radiologyRequest)
                                                        <a href="{{ route('radiology.show', $radiologyRequest->id) }}"
                                                           class="btn btn-outline-primary"
                                                           title="عرض تفاصيل الأشعة">
                                                            <i class="fas fa-x-ray"></i>
                                                        </a>
                                                    @else
                                                        <a href="{{ route('staff.requests.show', $request) }}"
                                                           class="btn btn-outline-primary"
                                                           title="عرض الطلب">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    @endif
                                                @else
                                                    <a href="{{ route('staff.requests.show', $request) }}"
                                                       class="btn btn-outline-primary"
                                                       title="عرض">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endif
                                                @if($request->status == 'completed' && $request->type == 'lab')
                                                    <a href="{{ route('staff.requests.print', $request) }}"
                                                       class="btn btn-outline-success"
                                                       target="_blank"
                                                       title="طباعة النتائج">
                                                        <i class="fas fa-print"></i>
                                                    </a>
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
                                    <th style="width:140px;">مريض</th>
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
                                    <th style="width:140px;">مريض</th>
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
            <div class="modal fade" id="completeEmergencyLabModal{{ $emergencyRequest->id }}" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form action="{{ route('staff.emergency-lab.complete', $emergencyRequest) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-check-circle me-2"></i>
                                    إكمال طلب تحاليل الطوارئ #{{ $emergencyRequest->emergency_id }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <strong>المريض:</strong> {{ $emergencyRequest->patient->user->name }}
                                </div>

                                <h6 class="mb-3">نتائج التحاليل:</h6>
                                @foreach($emergencyRequest->labTests as $test)
                                <div class="mb-3">
                                    <label class="form-label">
                                        <strong>{{ $test->name }}</strong>
                                        @if($test->unit)
                                            <small class="text-muted">({{ $test->unit }})</small>
                                        @endif
                                    </label>
                                    <textarea name="results[{{ $test->id }}]"
                                              class="form-control"
                                              rows="2"
                                              placeholder="أدخل نتيجة التحليل...">{{ $test->pivot->result ?? '' }}</textarea>
                                </div>
                                @endforeach

                                <div class="mb-3">
                                    <label class="form-label">ملاحظات إضافية</label>
                                    <textarea name="notes" class="form-control" rows="2">{{ $emergencyRequest->notes }}</textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check me-2"></i>
                                    إكمال وحفظ النتائج
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

@push('scripts')
<script>
    // Poll and refresh list every 20s
    setInterval(function(){
        location.reload();
    }, 20000);
</script>
@endpush
@endsection

@section('scripts')
<script>
// تحديث تلقائي للصفحة كل 5 ثواني
setInterval(function() {
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
                
                // تحديث الوقت
                const now = new Date();
                const time = now.toLocaleTimeString('ar-IQ');
                $('#last-update').text('آخر تحديث: ' + time);
            }
        },
        error: function(error) {
            console.error('خطأ في التحديث:', error);
        }
    });
}, 5000); // 5 ثواني

$(document).ready(function() {
    const now = new Date();
    const time = now.toLocaleTimeString('ar-IQ');
    $('#last-update').text('آخر تحديث: ' + time);
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
.table-sm td, .table-sm th {
    padding: 0.4rem;
    font-size: 0.875rem;
    white-space: normal !important;
    word-break: break-word;
}

.badge-sm {
    font-size: 0.75rem;
    padding: 0.25em 0.5em;
}

.table-responsive {
    overflow-x: auto;
    max-width: 100%;
}
</style>
@endsection