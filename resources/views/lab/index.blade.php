@extends('layouts.app')

@section('content')
<div class="container-fluid" id="lab-requests-content">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-flask me-2 text-primary"></i>
                        طلبات المختبر
                        <span class="badge bg-success" id="live-indicator">
                            <i class="fas fa-circle fa-xs"></i> مباشر
                        </span>
                    </h2>
                    <p class="text-muted mb-0">
                        مرحباً {{ auth()->user()->name }} -
                        <small id="last-update">آخر تحديث: الآن</small>
                    </p>
                </div>
                <div>
                    <a href="{{ route('staff.lab-visits.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> زيارة مختبرية مباشرة
                    </a>
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

    <!-- الطلبات العادية -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                الطلبات العادية
                <span class="badge bg-light text-primary ms-2">{{ $requests->total() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            @if($requests->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:70px;">رقم</th>
                                <th>مريض</th>
                                <th>طبيب</th>
                                <th style="width:90px;">نوع</th>
                                <th style="width:80px;">وقت</th>
                                <th style="width:90px;">الدفع</th>
                                <th style="width:70px;">حالة</th>
                                <th style="width:100px;">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $request)
                            <tr class="{{ $request->payment_status == 'pending' ? 'table-danger' : ($request->payment_status == 'paid' ? 'table-success' : '') }}">
                                <td><strong>#{{ $request->id }}</strong></td>
                                <td>{{ $request->visit?->patient?->user?->name ?? 'غير محدد' }}</td>
                                <td>د. {{ $request->visit?->doctor?->user?->name ?? 'غير محدد' }}</td>
                                <td>
                                    @php
                                        $det = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                                        $isBloodBank = $request->type === 'blood_bank' || data_get($det, 'blood_bank', false);
                                    @endphp
                                    <span class="badge bg-{{ $isBloodBank ? 'danger' : 'primary' }}">
                                        {{ $isBloodBank ? 'مصرف الدم' : 'تحاليل' }}
                                    </span>
                                </td>
                                <td><small>{{ $request->created_at->format('H:i') }}</small></td>
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
                                    <span class="badge bg-{{ $request->status == 'completed' ? 'success' : ($request->status == 'pending' ? 'warning' : ($request->status == 'pending_service_selection' ? 'secondary' : 'info')) }}">
                                        {{ $request->status == 'completed' ? 'تم' : ($request->status == 'pending' ? 'معلق' : ($request->status == 'pending_service_selection' ? 'بانتظار تحديد' : 'جاري')) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('lab.show', $request) }}"
                                           class="btn btn-outline-primary"
                                           title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($request->payment_status != 'paid')
                                            <a href="{{ route('lab.show', $request) }}"
                                               class="btn btn-outline-warning"
                                               title="تعديل التحاليل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @if(in_array($request->status, ['completed', 'in_progress']) && $request->payment_status == 'paid')
                                            <a href="{{ route('lab.print', $request) }}"
                                               class="btn btn-outline-success"
                                               target="_blank"
                                               title="طباعة النتائج">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        @elseif(in_array($request->status, ['completed', 'in_progress']) && $request->payment_status != 'paid')
                                            <button class="btn btn-outline-secondary" disabled title="يجب الدفع أولاً">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center p-3">
                    {{ $requests->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-flask fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">لا توجد طلبات مختبر</h5>
                </div>
            @endif
        </div>
    </div>

    <!-- طلبات تحاليل الطوارئ -->
    @if(isset($emergencyLabRequests) && $emergencyLabRequests->count() > 0)
    <div class="card shadow-sm border-danger">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">
                <i class="fas fa-ambulance me-2"></i>
                <i class="fas fa-flask me-2"></i>
                تحاليل الطوارئ
                <span class="badge bg-light text-danger ms-2">{{ $emergencyLabRequests->count() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:90px;">رقم طوارئ</th>
                            <th>مريض</th>
                            <th>تحاليل</th>
                            <th style="width:80px;">أولوية</th>
                            <th style="width:70px;">حالة</th>
                            <th style="width:120px;">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($emergencyLabRequests as $emergencyRequest)
                        <tr class="{{ $emergencyRequest->status == 'pending' ? 'table-warning' : ($emergencyRequest->status == 'in_progress' ? 'table-info' : 'table-success') }}">
                            <td><strong class="text-danger">#{{ $emergencyRequest->emergency_id }}</strong></td>
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
                                <span class="{{ $emergencyRequest->status_badge_class }}">{{ $emergencyRequest->status_text }}</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @if($emergencyRequest->status == 'pending')
                                        <form action="{{ route('staff.emergency-lab.start', $emergencyRequest) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm" title="بدء العمل">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </form>
                                    @elseif($emergencyRequest->status == 'in_progress')
                                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#completeEmergencyLabModal{{ $emergencyRequest->id }}" title="إكمال">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    @else
                                        <span class="badge bg-success">تم</span>
                                    @endif
                                    @if($emergencyRequest->status == 'completed')
                                        <a href="{{ route('staff.emergency-lab.print', $emergencyRequest) }}" class="btn btn-outline-secondary btn-sm" target="_blank" title="طباعة">
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

    <!-- مودالات إكمال تحاليل الطوارئ (خارج الجدول لتفادي مشاكل الـ z-index وتفاعل الإدخال) -->
    @foreach($emergencyLabRequests as $emergencyRequest)
        @if($emergencyRequest->status == 'in_progress')
        <div class="modal fade" id="completeEmergencyLabModal{{ $emergencyRequest->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">إدخال نتائج تحليل الطوارئ (#{{ $emergencyRequest->emergency_id }})</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('staff.emergency-lab.complete', $emergencyRequest) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            @foreach($emergencyRequest->labTests as $test)
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ $test->name }}</label>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <input type="text" name="results[{{ $test->id }}][value]" class="form-control" placeholder="النتيجة" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" name="results[{{ $test->id }}][unit]" class="form-control" placeholder="الوحدة" value="{{ $test->unit }}">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" name="results[{{ $test->id }}][reference]" class="form-control" placeholder="المرجع">
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            <div class="mb-3">
                                <label class="form-label">ملاحظات</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> حفظ النتائج
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

<!-- مودالات إكمال تحاليل الطوارئ بتصميم عصري وإشعارات فورية -->
<div id="emergency-lab-modals-container">
    @if(isset($emergencyLabRequests) && $emergencyLabRequests->count() > 0)
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
                                                   data-ref-min="{{ $refMin }}" 
                                                   data-ref-max="{{ $refMax }}" 
                                                   data-ref-range="{{ $refRangeText }}"
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1">الوحدة</label>
                                        <input type="text" name="results[{{ $test->id }}][unit]" class="form-control bg-light" placeholder="الوحدة" value="{{ $unit }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-1">المرجع</label>
                                        <input type="text" name="results[{{ $test->id }}][reference]" class="form-control bg-light" placeholder="المرجع" value="{{ $refRangeText }}">
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
                                <textarea name="notes" class="form-control" rows="2" placeholder="اكتب أي ملاحظات أو توصيات للمختبر..." style="border-radius: 8px;"></textarea>
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

<script>
// تقييم مباشر وعرض إشعار مرئي لحظي عند كتابة القيمة
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
setInterval(function() {
    // عدم تحديث الصفحة إذا كان هناك مودال مفتوح لمنع إغلاقه أو ضياع البيانات المدخلة
    if ($('.modal.show').length > 0) {
        return;
    }
    $.ajax({
        url: window.location.href,
        success: function(response) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(response, 'text/html');
            const newContent = doc.getElementById('lab-requests-content');
            if (newContent) {
                const scroll = window.scrollY;
                $('#lab-requests-content').html($(newContent).html());
                window.scrollTo(0, scroll);
                $('#last-update').text('آخر تحديث: ' + new Date().toLocaleTimeString('ar-IQ'));
            }
        }
    });
}, 5000);

$(document).ready(function() {
    $('#last-update').text('آخر تحديث: ' + new Date().toLocaleTimeString('ar-IQ'));
    // نقل المودالات إلى body مباشرة عند تحضير الصفحة لتجنب مشاكل stacking context مع Bootstrap
    $('.modal').appendTo('body');
});
</script>

<style>
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
#live-indicator { animation: pulse 2s ease-in-out infinite; }
.table-sm th { white-space: nowrap; }
.table-sm td { white-space: normal; word-break: break-word; }
.table-responsive table { min-width: max-content; }

/* إخفاء طبقة backdrop الخارجية تماماً ومنع تظليل/حجب الإدخال */
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
.modal-dialog {
    z-index: 10001 !important;
}
.modal-content {
    position: relative;
    z-index: 10002 !important;
    pointer-events: auto !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}
</style>
@endsection
