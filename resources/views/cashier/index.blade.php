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
            <!-- إحصائيات اليوم المُبسطة -->
            <div class="row gy-3">
                <div class="col-md-6 col-xl-3">
                    <div class="card border rounded-3 shadow-sm h-100">
                        <div class="card-body py-3 px-3 d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted small mb-1">المحصلة اليوم</p>
                                <h5 class="mb-0 text-success fw-bold">{{ number_format($todayStats['total_collected'], 2) }} IQD</h5>
                            </div>
                            <div class="bg-success bg-opacity-15 p-2 rounded-circle">
                                <i class="fas fa-money-bill-wave fa-lg text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card border rounded-3 shadow-sm h-100">
                        <div class="card-body py-3 px-3 d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted small mb-1">أجور الأطباء</p>
                                <h5 class="mb-0 text-info fw-bold">{{ number_format($todayStats['doctor_fees'], 2) }} IQD</h5>
                            </div>
                            <div class="bg-info bg-opacity-15 p-2 rounded-circle">
                                <i class="fas fa-user-md fa-lg text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card border rounded-3 shadow-sm h-100">
                        <div class="card-body py-3 px-3 d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted small mb-1">ربح المستشفى</p>
                                <h5 class="mb-0 text-primary fw-bold">{{ number_format($todayStats['hospital_profit'], 2) }} IQD</h5>
                            </div>
                            <div class="bg-primary bg-opacity-15 p-2 rounded-circle">
                                <i class="fas fa-building fa-lg text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card border rounded-3 shadow-sm h-100">
                        <div class="card-body py-3 px-3 d-flex align-items-center justify-content-between gap-3">
                            <div>
                                @if(auth()->user()->can('view cashier appointments'))
                                    <p class="text-muted small mb-1">المعاملات المعلقة</p>
                                    <h5 class="mb-0 text-warning fw-bold">{{ $todayStats['pending_appointments_count'] + $todayStats['pending_requests_count'] }}</h5>
                                    <small class="text-muted">مواعيد: {{ $todayStats['pending_appointments_count'] }} | طلبات: {{ $todayStats['pending_requests_count'] }}</small>
                                @else
                                    <p class="text-muted small mb-1">المعاملات المعلقة</p>
                                    <h5 class="mb-0 text-warning fw-bold">{{ $todayStats['pending_appointments_count'] + $todayStats['pending_requests_count'] }}</h5>
                                @endif
                            </div>
                            <div class="bg-warning bg-opacity-15 p-2 rounded-circle">
                                <i class="fas fa-clock fa-lg text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<!-- عرض المعاملات المعلقة بجدول واحد -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fas fa-table me-2"></i>
                                        المعاملات المعلقة - بانتظار الدفع
                                        @php
                                            $combinedCount = 0;
                                            if(auth()->user()->can('view cashier appointments') && isset($pendingAppointments)) {
                                                $combinedCount += $pendingAppointments->total();
                                            }
                                            if(auth()->user()->can('view cashier medical requests') && isset($pendingMedicalRequests)) {
                                                $combinedCount += $pendingMedicalRequests->total();
                                            }
                                            if(auth()->user()->can('view cashier emergency') && isset($pendingEmergencyPayments)) {
                                                $combinedCount += $pendingEmergencyPayments->total();
                                            }
                                        @endphp
                                        <span class="badge bg-warning">{{ $combinedCount }}</span>
                                    </h5>
                                    <p class="text-muted small mb-0">الجدول الموحد يعرض المواعيد والطلبات الطبية وخدمات الطوارئ في صف واحد.</p>
                                </div>
                                <div class="card-body">
                                    @php
                                        $hasRows = false;
                                        if(auth()->user()->can('view cashier appointments') && isset($pendingAppointments) && $pendingAppointments->count() > 0) {
                                            $hasRows = true;
                                        }
                                        if(auth()->user()->can('view cashier medical requests') && isset($pendingMedicalRequests) && $pendingMedicalRequests->count() > 0) {
                                            $hasRows = true;
                                        }
                                        if(auth()->user()->can('view cashier emergency') && isset($pendingEmergencyPayments) && $pendingEmergencyPayments->count() > 0) {
                                            $hasRows = true;
                                        }
                                    @endphp

                                    @if($hasRows)
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>رقم</th>
                                                        <th>النوع</th>
                                                        <th>المريض</th>
                                                        <th>التفاصيل</th>
                                                        <th class="text-end">السعر</th>
                                                        <th>التاريخ</th>
                                                        <th>الإجراءات</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @if(auth()->user()->can('view cashier appointments'))
                                                        @foreach($pendingAppointments ?? [] as $appointment)
                                                            @php
                                                                $patientName = optional(optional($appointment->patient)->user)->name ?? 'غير محدد';
                                                                $patientId = optional($appointment->patient)->national_id ?? '---';
                                                                $doctorName = optional(optional($appointment->doctor)->user)->name ?? 'غير محدد';
                                                                $department = optional($appointment->department)->name ?? 'غير محدد';
                                                                $amount = $appointment->consultation_fee ?? 0;
                                                            @endphp
                                                            <tr>
                                                                <td><strong>#{{ $appointment->id }}</strong></td>
                                                                <td>
                                                                    <span class="badge bg-warning">
                                                                        <i class="fas fa-calendar-check"></i> موعد
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <div>{{ $patientName }}</div>
                                                                    <small class="text-muted">{{ $patientId }}</small>
                                                                </td>
                                                                <td>
                                                                    <small class="text-muted">
                                                                        @if($doctorName !== 'غير محدد')
                                                                            د. {{ $doctorName }}
                                                                            <br>
                                                                        @endif
                                                                        {{ $department }}
                                                                    </small>
                                                                </td>
                                                                <td class="text-end text-success fw-bold">{{ number_format($amount, 2) }} IQD</td>
                                                                <td>
                                                                    <small>{{ $appointment->created_at->format('Y-m-d') }}</small>
                                                                    <br>
                                                                    <small class="text-muted">{{ $appointment->created_at->format('H:i') }}</small>
                                                                </td>
                                                                <td>
                                                                    <a href="{{ route('cashier.payment.form', $appointment->id) }}" class="btn btn-success btn-sm">
                                                                        <i class="fas fa-money-bill-wave me-1"></i>
                                                                        تسديد
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endif

                                                    @if(auth()->user()->can('view cashier medical requests'))
                                                        @foreach($pendingMedicalRequests ?? [] as $request)
                                                            @php
                                                                $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                                                                $amount = $request->total_amount;
                                                            @endphp
                                                            <tr>
                                                                <td><strong>#{{ $request->id }}</strong></td>
                                                                <td>
                                                                    @if($request->type === 'lab')
                                                                        <span class="badge bg-primary"><i class="fas fa-flask"></i> تحاليل</span>
                                                                    @elseif($request->type === 'radiology')
                                                                        <span class="badge bg-info"><i class="fas fa-x-ray"></i> أشعة</span>
                                                                    @elseif($request->type === 'pharmacy')
                                                                        <span class="badge bg-success"><i class="fas fa-pills"></i> صيدلية</span>
                                                                    @elseif($request->type === 'emergency')
                                                                        <span class="badge bg-danger"><i class="fas fa-ambulance"></i> طوارئ</span>
                                                                    @else
                                                                        <span class="badge bg-secondary">{{ $request->type }}</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <div>{{ optional(optional(optional($request->visit)->patient)->user)->name ?? 'غير محدد' }}</div>
                                                                    <small class="text-muted">{{ optional(optional($request->visit)->patient)->national_id ?? 'غير محدد' }}</small>
                                                                </td>
                                                                <td>
                                                                    @if($request->type === 'lab' && isset($details['lab_test_ids']))
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-vial"></i>
                                                                            عدد الخدمات: {{ count($details['lab_test_ids']) }} تحليل
                                                                            @if(!empty($details['package_id']))
                                                                                <br>باقة: #{{ $details['package_id'] }}
                                                                            @endif
                                                                        </small>
                                                                    @elseif($request->type === 'radiology' && isset($details['radiology_type_ids']))
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-camera"></i>
                                                                            عدد الخدمات: {{ count($details['radiology_type_ids']) }} نوع إشعة
                                                                        </small>
                                                                    @elseif($request->type === 'pharmacy' && isset($details['tests']))
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-pills"></i>
                                                                            عدد الخدمات: {{ count($details['tests']) }} منتج
                                                                            @if(is_array($details['tests']) && count($details['tests']) > 0)
                                                                                <br>{{ implode(', ', array_slice($details['tests'], 0, 3)) }}@if(count($details['tests']) > 3) ... @endif
                                                                            @endif
                                                                        </small>
                                                                    @elseif($request->type === 'emergency' && isset($details['emergency_priority']))
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-exclamation-triangle"></i>
                                                                            <strong>الأولوية:</strong>
                                                                            {{ $details['emergency_priority'] }}
                                                                            @if(isset($details['emergency_type']))
                                                                                <br><strong>النوع:</strong> {{ \App\Models\Emergency::getEmergencyTypeText($details['emergency_type']) }}
                                                                            @endif
                                                                            @if(isset($details['services']) && is_array($details['services']))
                                                                                <br>عدد الخدمات: {{ count($details['services']) }}
                                                                            @endif
                                                                        </small>
                                                                    @else
                                                                        <small class="text-muted">{{ $request->description }}</small>
                                                                    @endif
                                                                </td>
                                                                <td class="text-end text-success fw-bold">
                                                                    @if($amount !== null)
                                                                        {{ number_format($amount, 2) }} IQD
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <small>{{ $request->created_at->format('Y-m-d') }}</small>
                                                                    <br>
                                                                    <small class="text-muted">{{ $request->created_at->format('H:i') }}</small>
                                                                </td>
                                                                <td>
                                                                    <a href="{{ route('cashier.request.payment.form', $request->id) }}" class="btn btn-success btn-sm">
                                                                        <i class="fas fa-money-bill-wave me-1"></i>
                                                                        تسديد
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endif

                                                    @if(auth()->user()->can('view cashier emergency'))
                                                        @foreach($pendingEmergencyPayments ?? [] as $payment)
                                                            @php
                                                                $em = $payment->emergency;
                                                                if ($em->patient) {
                                                                    $patientName = optional(optional($em->patient)->user)->name ?? 'غير محدد';
                                                                    $patientId = optional(optional($em->patient)->user)->phone ?? '---';
                                                                } elseif ($em->emergencyPatient) {
                                                                    $patientName = $em->emergencyPatient->name;
                                                                    $patientId = $em->emergencyPatient->phone ?? '---';
                                                                } else {
                                                                    $patientName = 'غير محدد';
                                                                    $patientId = '---';
                                                                }
                                                            @endphp
                                                            <tr>
                                                                <td><strong>#{{ $payment->id }}</strong></td>
                                                                <td>
                                                                    <span class="badge bg-danger"><i class="fas fa-ambulance"></i> طوارئ</span>
                                                                </td>
                                                                <td>
                                                                    <div>{{ $patientName }}</div>
                                                                    <small class="text-muted">{{ $patientId }}</small>
                                                                </td>
                                                                <td>
                                                                    <small>
                                                                        <span class="badge bg-{{ $payment->emergency->priority_color }}">{{ $payment->emergency->priority_text }}</span>
                                                                        @php
                                                                            $serviceCount = $payment->emergency->services->count();
                                                                        @endphp
                                                                        @if($serviceCount > 0)
                                                                            <br>
                                                                            عدد الخدمات: {{ $serviceCount }}
                                                                        @else
                                                                            <br>
                                                                            <span class="text-muted">لا توجد خدمات محددة</span>
                                                                        @endif
                                                                    </small>
                                                                </td>
                                                                <td class="text-end text-success fw-bold">{{ number_format($payment->amount, 2) }} IQD</td>
                                                                <td>
                                                                    <small>{{ $payment->created_at->format('Y-m-d') }}</small>
                                                                    <br>
                                                                    <small class="text-muted">{{ $payment->created_at->format('H:i') }}</small>
                                                                </td>
                                                                <td>
                                                                    <a href="{{ route('cashier.emergency.payment.form', $payment->id) }}" class="btn btn-success btn-sm">
                                                                        <i class="fas fa-money-bill-wave me-1"></i>
                                                                        تسديد
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-5">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                            <p class="text-muted">لا توجد معاملات معلقة حالياً</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- end sections container -->
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

#cashier-content table.table {
    font-size: 1.04rem;
    font-weight: 700;
}

#cashier-content table.table th,
#cashier-content table.table td {
    vertical-align: middle;
    font-weight: 700;
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
