@extends('layouts.app')

@section('content')
<div class="container-fluid" id="cashier-content">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-cash-register me-2 text-success"></i>
                        محطة الكاشير
                        <span class="badge bg-success" id="live-indicator">
                            <i class="fas fa-circle fa-xs"></i> مباشر
                        </span>
                    </h2>
                    <p class="text-muted">
                        إدارة المدفوعات والإيصالات - 
                        <small id="last-update">آخر تحديث: الآن</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            @if(session('payment_id'))
                <br>
                <div class="mt-2">
                    <a href="{{ route('cashier.receipt', session('payment_id')) }}" 
                       class="btn btn-sm btn-light me-2" 
                       target="_blank">
                        <i class="fas fa-eye me-1"></i>
                        عرض الإيصال
                    </a>
                    <a href="{{ route('cashier.receipt.print', session('payment_id')) }}" 
                       class="btn btn-sm btn-light" 
                       target="_blank">
                        <i class="fas fa-print me-1"></i>
                        طباعة الإيصال
                    </a>
                </div>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- لوحة التحكم الرئيسية -->
    <div class="row mb-4">
        <div class="col-12">
                    <!-- إحصائيات اليوم المحسنة -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1">إجمالي المبالغ المحصلة اليوم</p>
                                            <h3 class="mb-0 text-success">{{ number_format($todayStats['total_collected'], 2) }} IQD</h3>
                                        </div>
                                        <div class="bg-success bg-opacity-10 p-3 rounded">
                                            <i class="fas fa-money-bill-wave fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1">أجور الأطباء اليوم</p>
                                            <h3 class="mb-0 text-info">{{ number_format($todayStats['doctor_fees'], 2) }} IQD</h3>
                                        </div>
                                        <div class="bg-info bg-opacity-10 p-3 rounded">
                                            <i class="fas fa-user-md fa-2x text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1">ربح المستشفى اليوم</p>
                                            <h3 class="mb-0 text-primary">{{ number_format($todayStats['hospital_profit'], 2) }} IQD</h3>
                                        </div>
                                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                                            <i class="fas fa-building fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            @if(auth()->user()->can('view cashier appointments'))
                                <p class="text-muted mb-1">المواعيد المعلقة</p>
                                                <h3 class="mb-0 text-warning">{{ $todayStats['pending_appointments_count'] }}</h3>
                                                <small class="text-muted">
                                                    مواعيد: {{ $todayStats['pending_appointments_count'] }}
                                                    @can('view cashier medical requests')
                                                    | طلبات: {{ $todayStats['pending_requests_count'] }}
                                                    @endcan
                                                </small>
                                            @else
                                                <p class="text-muted mb-1">المعاملات المعلقة</p>
                                                <h3 class="mb-0 text-warning">{{ $todayStats['pending_appointments_count'] + $todayStats['pending_requests_count'] }}</h3>
                                                <small class="text-muted">
                                                    مواعيد: {{ $todayStats['pending_appointments_count'] }} | 
                                                    طلبات: {{ $todayStats['pending_requests_count'] }}
                                                </small>
                                            @endif
                                        </div>
                                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                                            <i class="fas fa-clock fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- عرض الأقسام المعلقة بدون تبويبات -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <!-- مواعيد -->
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fas fa-calendar-check me-2"></i>
                                        المواعيد المعلقة - بانتظار الدفع
                                        @if(isset($pendingAppointments) && is_object($pendingAppointments) && method_exists($pendingAppointments, 'total') && $pendingAppointments->total() > 0)
                                            <span class="badge bg-warning">{{ $pendingAppointments->total() }}</span>
                                        @endif
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if(isset($pendingAppointments) && is_object($pendingAppointments) && method_exists($pendingAppointments, 'total') && $pendingAppointments->total() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>رقم الموعد</th>
                                                        <th>المريض</th>
                                                        <th>الطبيب</th>
                                                        <th>التخصص</th>
                                                        <th>أجر الطبيب</th>
                                                        <th>مبلغ الكشف</th>
                                                        <th>ربح المستشفى</th>
                                                        <th>الحالة</th>
                                                        <th>الإجراءات</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($pendingAppointments as $appointment)
                                                        @php
                                                            $doctorFee = $appointment->doctor->fee_by_specialization ?? 0;
                                                            $hospitalProfit = $appointment->consultation_fee - $doctorFee;
                                                        @endphp
                                                        <tr>
                                                            <td><strong>#{{ $appointment->id }}</strong></td>
                                                            <td>
                                                                <div>{{ optional(optional($appointment->patient)->user)->name ?? '-' }}</div>
                                                                <small class="text-muted">{{ optional($appointment->patient)->national_id ?? 'غير محدد' }}</small>
                                                    </td>
                                                    <td>د. {{ optional(optional($appointment->doctor)->user)->name ?? '-' }}</td>
                                                    <td>
                                                        <span class="badge bg-secondary">{{ $appointment->doctor->specialization ?? 'غير محدد' }}</span>
                                                    </td>
                                                    <td class="text-info fw-bold">{{ number_format($doctorFee, 2) }} IQD</td>
                                                    <td class="text-success fw-bold">{{ number_format($appointment->consultation_fee, 2) }} IQD</td>
                                                    <td class="text-primary fw-bold">{{ number_format($hospitalProfit, 2) }} IQD</td>
                                                    <td>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-exclamation-circle me-1"></i>
                                                            معلق
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('cashier.payment.form', $appointment->id) }}" 
                                                           class="btn btn-success btn-sm">
                                                            <i class="fas fa-money-bill-wave me-1"></i>
                                                            تسديد
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-3">
                                    {{ $pendingAppointments->links() }}
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <p class="text-muted">لا توجد مواعيد معلقة حالياً</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @can('view cashier medical requests')
                <!-- الطلبات الطبية -->
                    
                    <!-- قائمة الطلبات المعلقة (تحاليل، أشعة، صيدلية) -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-file-medical me-2"></i>
                                الطلبات الطبية المعلقة - بانتظار الدفع
                                @if(isset($pendingMedicalRequests) && is_object($pendingMedicalRequests) && $pendingMedicalRequests->count() > 0)
                                    <span class="badge bg-warning">{{ $pendingMedicalRequests->total() }}</span>
                                @endif
                            </h5>
                        </div>
                        <div class="card-body">
                            @forelse($pendingMedicalRequests ?? [] as $request)
                                @if($loop->first)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>رقم الطلب</th>
                                                <th>النوع</th>
                                                <th>المريض</th>
                                                <th>التفاصيل</th>
                                                <th>التاريخ</th>
                                                <th>الحالة</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                @endif
                                                <tr>
                                                    <td><strong>#{{ $request->id }}</strong></td>
                                                    <td>
                                                        @if($request->type === 'lab')
                                                            <span class="badge bg-primary">
                                                                <i class="fas fa-flask"></i> تحاليل
                                                            </span>
                                                        @elseif($request->type === 'radiology')
                                                            <span class="badge bg-info">
                                                                <i class="fas fa-x-ray"></i> أشعة
                                                            </span>
                                                        @elseif($request->type === 'pharmacy')
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-pills"></i> صيدلية
                                                            </span>
                                                        @elseif($request->type === 'emergency')
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-ambulance"></i> طوارئ
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ $request->type }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div>{{ optional(optional(optional($request->visit)->patient)->user)->name ?? 'غير محدد' }}</div>
                                                        <small class="text-muted">{{ optional(optional($request->visit)->patient)->national_id ?? 'غير محدد' }}</small>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                                                        @endphp
                                                        
                                                        @if($request->type === 'lab' && isset($details['lab_test_ids']))
                                                            <small class="text-muted">
                                                                <i class="fas fa-vial"></i> 
                                                                {{ count($details['lab_test_ids']) }} تحليل
                                                                @php
                                                                    $testNames = [];
                                                                    foreach(array_slice($details['lab_test_ids'], 0, 2) as $testId) {
                                                                        $test = \App\Models\LabTest::find($testId);
                                                                        if($test) $testNames[] = $test->name;
                                                                    }
                                                                @endphp
                                                                <br>{{ implode(', ', $testNames) }}
                                                                @if(count($details['lab_test_ids']) > 2)
                                                                    <br>... و {{ count($details['lab_test_ids']) - 2 }} أخرى
                                                                @endif
                                                            </small>
                                                        @elseif($request->type === 'radiology' && isset($details['radiology_type_ids']))
                                                            <small class="text-muted">
                                                                <i class="fas fa-camera"></i> 
                                                                {{ count($details['radiology_type_ids']) }} نوع إشعة
                                                                @php
                                                                    $typeNames = [];
                                                                    foreach(array_slice($details['radiology_type_ids'], 0, 2) as $typeId) {
                                                                        $type = \App\Models\RadiologyType::find($typeId);
                                                                        if($type) $typeNames[] = $type->name;
                                                                    }
                                                                @endphp
                                                                <br>{{ implode(', ', $typeNames) }}
                                                                @if(count($details['radiology_type_ids']) > 2)
                                                                    <br>... و {{ count($details['radiology_type_ids']) - 2 }} أخرى
                                                                @endif
                                                            </small>
                                                        @elseif($request->type === 'emergency' && isset($details['emergency_priority']))
                                                            <small class="text-muted">
                                                                <i class="fas fa-exclamation-triangle"></i> 
                                                                <strong>الأولوية:</strong> 
                                                                @if($details['emergency_priority'] === 'critical')
                                                                    <span class="badge badge-sm bg-danger">حرجة</span>
                                                                @elseif($details['emergency_priority'] === 'urgent')
                                                                    <span class="badge badge-sm bg-warning">عاجلة</span>
                                                                @elseif($details['emergency_priority'] === 'semi_urgent')
                                                                    <span class="badge badge-sm bg-info">شبه عاجلة</span>
                                                                @else
                                                                    <span class="badge badge-sm bg-secondary">غير عاجلة</span>
                                                                @endif
                                                                @if(isset($details['emergency_type']))
                                                                    <br><strong>النوع:</strong> {{ \App\Models\Emergency::getEmergencyTypeText($details['emergency_type']) }}
                                                                @endif
                                                            </small>
                                                        @else
                                                            <small class="text-muted">{{ $request->description }}</small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <small>{{ $request->created_at->format('Y-m-d') }}</small>
                                                        <br>
                                                        <small class="text-muted">{{ $request->created_at->format('H:i') }}</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-exclamation-circle me-1"></i>
                                                            معلق
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('cashier.request.payment.form', $request->id) }}" 
                                                           class="btn btn-success btn-sm">
                                                            <i class="fas fa-money-bill-wave me-1"></i>
                                                            تسديد
                                                        </a>
                                                    </td>
                                                </tr>
                                @if($loop->last)
                                        </tbody>
                                    </table>
                                </div>

                                @if(isset($pendingMedicalRequests) && method_exists($pendingMedicalRequests, 'links'))
                                <div class="mt-3">
                                    {{ $pendingMedicalRequests->links('pagination::bootstrap-5') }}
                                </div>
                                @endif
                                @endif
                            @empty
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <p class="text-muted">لا توجد طلبات معلقة حالياً</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                @endcan

                @can('view cashier emergency')
                <!-- خدمات الطوارئ -->
                    
                    <!-- قائمة خدمات الطوارئ المعلقة -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-ambulance me-2"></i>
                                خدمات الطوارئ المعلقة - بانتظار الدفع
                                @if(isset($pendingEmergencyPayments) && is_object($pendingEmergencyPayments) && $pendingEmergencyPayments->count() > 0)
                                    <span class="badge bg-danger">{{ $pendingEmergencyPayments->total() }}</span>
                                @endif
                            </h5>
                        </div>
                        <div class="card-body">
                            @forelse($pendingEmergencyPayments ?? [] as $payment)
                                @if($loop->first)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>رقم الدفعة</th>
                                                <th>المريض</th>
                                                <th>حالة الطوارئ</th>
                                                <th>الخدمات</th>
                                                <th>المبلغ</th>
                                                <th>التاريخ</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                @endif
                                                <tr>
                                                    <td><strong>#{{ $payment->id }}</strong></td>
                                                    <td>
                                                        <div>
                                                            @php
                                                                $em = $payment->emergency;
                                                                if ($em->patient) {
                                                                    $pname = optional(optional($em->patient)->user)->name ?? 'غير محدد';
                                                                    $pphone = optional(optional($em->patient)->user)->phone ?? '---';
                                                                } elseif ($em->emergencyPatient) {
                                                                    $pname = $em->emergencyPatient->name;
                                                                    $pphone = $em->emergencyPatient->phone ?? '---';
                                                                } else {
                                                                    $pname = 'غير محدد';
                                                                    $pphone = '---';
                                                                }
                                                            @endphp
                                                            <strong>{{ $pname }}</strong>
                                                            <br>
                                                            <small class="text-muted">{{ $pphone }}</small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{ $payment->emergency->priority_color }}">
                                                            {{ $payment->emergency->priority_text }}
                                                        </span>
                                                        <br>
                                                        <small class="text-muted">{{ $payment->emergency->emergency_type_text }}</small>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            @if($payment->appointment_id)
                                                                <span class="badge bg-primary">استشارة طبيب</span>
                                                                @if($payment->appointment && $payment->appointment->doctor)
                                                                    <br>
                                                                    <span class="text-muted">د. {{ $payment->appointment->doctor->user->name ?? '' }}</span>
                                                                @endif
                                                            @endif
                                                            @if($payment->emergency->services->count() > 0)
                                                                @if($payment->appointment_id)<br>@endif
                                                                @foreach($payment->emergency->services as $service)
                                                                    <span class="badge bg-light text-dark">{{ $service->name }}</span>
                                                                    @if(!$loop->last) | @endif
                                                                @endforeach
                                                            @endif
                                                            @if(!$payment->appointment_id && $payment->emergency->services->count() == 0)
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <strong class="text-success">{{ number_format($payment->amount, 2) }} IQD</strong>
                                                    </td>
                                                    <td>
                                                        <small>{{ $payment->created_at->format('Y-m-d') }}</small>
                                                        <br>
                                                        <small class="text-muted">{{ $payment->created_at->format('H:i') }}</small>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('cashier.emergency.payment.form', $payment->id) }}" 
                                                           class="btn btn-success btn-sm">
                                                            <i class="fas fa-money-bill-wave me-1"></i>
                                                            تسديد
                                                        </a>
                                                    </td>
                                                </tr>
                                @if($loop->last)
                                        </tbody>
                                    </table>
                                </div>

                                @if(isset($pendingEmergencyPayments) && method_exists($pendingEmergencyPayments, 'links'))
                                <div class="mt-3">
                                    {{ $pendingEmergencyPayments->links('pagination::bootstrap-5') }}
                                </div>
                                @endif
                                @endif
                            @empty
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <p class="text-muted">لا توجد خدمات طوارئ معلقة حالياً</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>


            </div> <!-- end sections container -->
                @endcan
        </div> <!-- end col-12 -->
    </div> <!-- end row mb-4 -->

</div>

@endsection

@section('scripts')
<script>
// تحديث تلقائي للصفحة كل 5 ثواني
setInterval(function() {
    // تحديث الإحصائيات والجدول بدون إعادة تحميل كامل
    $.ajax({
        url: window.location.href,
        type: 'GET',
        success: function(response) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(response, 'text/html');
            const newContent = doc.getElementById('cashier-content');
            
            if (newContent) {
                const currentScroll = window.scrollY;
                $('#cashier-content').html($(newContent).html());
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

    // restore tab from URL hash if present
    var hash = window.location.hash;
    if (hash) {
        var btn = $('#pendingTabs button[data-bs-target="' + hash + '"]');
        if (btn.length) {
            btn.tab('show');
        }
    }
});

// keep URL hash in sync with selected tab
$(document).on('shown.bs.tab', '#pendingTabs button', function(e) {
    var target = $(e.target).data('bs-target');
    if (history.replaceState) {
        history.replaceState(null, null, target);
    } else {
        window.location.hash = target;
    }
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

/* تنسيقات الطباعة */
@media print {
    body * {
        visibility: hidden;
    }
    
    #cashier-content, #cashier-content * {
        visibility: visible;
    }
    
    #cashier-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    
    .btn, .card-header .btn {
        display: none !important;
    }
    
    .card {
        border: 1px solid #dee2e6 !important;
        margin-bottom: 20px;
    }
}
</style>
@endsection
