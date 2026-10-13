@extends('layouts.app')

@section('content')
<div class="container-fluid" id="cashier-content">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-cash-register me-2 text-success"></i>
                        محطة الكاشير
                        <span class="badge bg-success" id="live-indicator">
                            <i class="fas fa-circle fa-xs"></i> مباشر
                        </span>
                    </h2>
                    <p class="text-muted mb-0">
                        إدارة المدفوعات والإيصالات - 
                        <small id="last-update">آخر تحديث: الآن</small>
                    </p>
                </div>

                <!-- فلتر نطاق التاريخ للمقبوضات المعلقة -->
                <div class="btn-group bg-white p-1 rounded-pill shadow-sm border" role="group">
                    <a href="{{ route('cashier.index', ['date_filter' => 'today']) }}" 
                       class="btn btn-sm rounded-pill px-3 fw-bold {{ ($dateFilter ?? 'today') === 'today' ? 'btn-primary text-white' : 'btn-light text-dark' }}">
                        <i class="fas fa-calendar-day me-1"></i> معلقات اليوم
                    </a>
                    <a href="{{ route('cashier.index', ['date_filter' => 'previous']) }}" 
                       class="btn btn-sm rounded-pill px-3 fw-bold {{ ($dateFilter ?? '') === 'previous' ? 'btn-warning text-dark' : 'btn-light text-dark' }}">
                        <i class="fas fa-history me-1"></i> المعلقات السابقة
                        @if(($previousPendingCount ?? 0) > 0)
                            <span class="badge bg-danger rounded-pill ms-1">{{ $previousPendingCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('cashier.index', ['date_filter' => 'all']) }}" 
                       class="btn btn-sm rounded-pill px-3 fw-bold {{ ($dateFilter ?? '') === 'all' ? 'btn-secondary text-white' : 'btn-light text-dark' }}">
                        <i class="fas fa-infinity me-1"></i> كافة التواريخ
                    </a>
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
                                <p class="text-muted small mb-1">المعاملات المعلقة</p>
                                <h5 class="mb-0 text-warning fw-bold">{{ $todayStats['pending_appointments_count'] + $todayStats['pending_requests_count'] + ($todayStats['pending_emergency_count'] ?? 0) }}</h5>
                                <small class="text-muted">مواعيد: {{ $todayStats['pending_appointments_count'] }} | طلبات: {{ $todayStats['pending_requests_count'] }}</small>
                            </div>
                            <div class="bg-warning bg-opacity-15 p-2 rounded-circle">
                                <i class="fas fa-clock fa-lg text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<!-- عرض المعاملات المعلقة بتبويبات منظمة -->
                    <div class="row mb-4">
                        <div class="col-12">
                            @php
                                $canConsultation = auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || auth()->user()->can('process consultation payments');
                                $canFullMedical = auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || auth()->user()->can('process medical requests payments');
                                $canUltrasoundOnly = !$canFullMedical && auth()->user()->can('process ultrasound payments');
                                $canMedicalRequests = $canFullMedical || $canUltrasoundOnly;
                                $canEmergency = auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || auth()->user()->can('process emergency payments');

                                $appointmentsCount = ($canConsultation && isset($pendingAppointments)) ? $pendingAppointments->total() : 0;
                                $requestsCount = ($canMedicalRequests && isset($pendingMedicalRequests)) ? $pendingMedicalRequests->total() : 0;
                                $emergencyCount = ($canEmergency && isset($pendingEmergencyPayments)) ? $pendingEmergencyPayments->total() : 0;
                                $combinedCount = $appointmentsCount + $requestsCount + $emergencyCount;
                            @endphp

                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-bottom p-3">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                        <div>
                                            <h5 class="mb-1 text-dark fw-bold">
                                                <i class="fas fa-cash-register me-2 text-primary"></i>
                                                المعاملات المعلقة بانتظار الدفع
                                            </h5>
                                            <p class="text-muted small mb-0">يمكنك استعراض كافة الطلبات معاً أو فرزها حسب التبويب المطلوب</p>
                                        </div>
                                        
                                        <ul class="nav nav-pills bg-light p-1 rounded-pill border" id="pendingTabs" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active fw-bold px-3 py-1-5 rounded-pill" id="tab-all-btn" data-bs-toggle="pill" data-bs-target="#tab-all" type="button" role="tab">
                                                    <i class="fas fa-layer-group me-1"></i> الكل
                                                    <span class="badge bg-secondary ms-1 rounded-pill">{{ $combinedCount }}</span>
                                                </button>
                                            </li>
                                            @if($canConsultation)
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link fw-bold px-3 py-1-5 rounded-pill" id="tab-appointments-btn" data-bs-toggle="pill" data-bs-target="#tab-appointments" type="button" role="tab">
                                                    <i class="fas fa-calendar-check me-1 text-warning"></i> كشفية الاستشارية
                                                    <span class="badge bg-warning text-dark ms-1 rounded-pill">{{ $appointmentsCount }}</span>
                                                </button>
                                            </li>
                                            @endif
                                            @if($canMedicalRequests)
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link fw-bold px-3 py-1-5 rounded-pill" id="tab-requests-btn" data-bs-toggle="pill" data-bs-target="#tab-requests" type="button" role="tab">
                                                    @if($canUltrasoundOnly)
                                                        <i class="fas fa-wave-square me-1 text-primary"></i> السونار الخارجي
                                                    @else
                                                        <i class="fas fa-flask me-1 text-primary"></i> التحاليل والأشعة
                                                    @endif
                                                    <span class="badge bg-primary ms-1 rounded-pill">{{ $requestsCount }}</span>
                                                </button>
                                            </li>
                                            @endif
                                            @if($canEmergency)
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link fw-bold px-3 py-1-5 rounded-pill" id="tab-emergency-btn" data-bs-toggle="pill" data-bs-target="#tab-emergency" type="button" role="tab">
                                                    <i class="fas fa-ambulance me-1 text-danger"></i> الطوارئ
                                                    <span class="badge bg-danger ms-1 rounded-pill">{{ $emergencyCount }}</span>
                                                </button>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="tab-content" id="pendingTabsContent">

                                        <!-- تبويب الكل -->
                                        <div class="tab-pane fade show active" id="tab-all" role="tabpanel">
                                            @if($combinedCount > 0)
                                                <div class="table-responsive">
                                                    <table class="table table-hover align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>رقم</th>
                                                                <th>النوع</th>
                                                                <th>المريض</th>
                                                                <th>التفاصيل والعيادة</th>
                                                                <th class="text-end">المبلغ</th>
                                                                <th>التاريخ والوقت</th>
                                                                <th class="text-center">الإجراء</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            {{-- المواعيد --}}
                                                            @if($canConsultation)
                                                                @foreach($pendingAppointments ?? [] as $appointment)
                                                                    @php
                                                                        $patientName = optional(optional($appointment->patient)->user)->name ?? 'غير محدد';
                                                                        $patientId = optional($appointment->patient)->national_id ?? '---';
                                                                        $doctorName = optional(optional($appointment->doctor)->user)->name ?? 'غير محدد';
                                                                        $department = optional($appointment->department)->name ?? 'غير محدد';
                                                                        $insType = $appointment->insurance_type ?? optional($appointment->patient)->insurance_type ?? 'none';
                                                                        $baseFee = (float)($appointment->consultation_fee > 0 ? $appointment->consultation_fee : (optional($appointment->doctor)->consultation_fee ?? 0));
                                                                        $doctor = $appointment->doctor;
                                                                        $patient = $appointment->patient;
                                                                        $isIns = in_array($insType, ['hi', 'moi']);
                                                                        if ($isIns) {
                                                                            $approvedFee = ($insType === 'moi' && $doctor && $doctor->moi_price > 0) ? (float)$doctor->moi_price : (($insType === 'hi' && $doctor && $doctor->hi_price > 0) ? (float)$doctor->hi_price : $baseFee);
                                                                            $copayPct = ($insType === 'hi' && $patient) ? $patient->getCopayPercentageFor('consultation') : (float)($patient->copay_percentage ?? 15);
                                                                            $patientShare = round($approvedFee * ($copayPct / 100));
                                                                        } else {
                                                                            $approvedFee = $baseFee;
                                                                            $copayPct = 100;
                                                                            $patientShare = $baseFee;
                                                                        }
                                                                    @endphp
                                                                    <tr>
                                                                        <td><strong>#{{ $appointment->id }}</strong></td>
                                                                        <td><span class="badge bg-warning"><i class="fas fa-calendar-check me-1"></i> كشفية</span></td>
                                                                        <td>
                                                                            <div class="fw-semibold">{{ $patientName }}
                                                                                @if($insType === 'moi')
                                                                                    <span class="badge bg-primary fs-8 ms-1"><i class="fas fa-shield-alt"></i> داخليّة</span>
                                                                                @elseif($insType === 'hi')
                                                                                    <span class="badge bg-info text-dark fs-8 ms-1"><i class="fas fa-heartbeat"></i> ضمان صحي</span>
                                                                                @endif
                                                                            </div>
                                                                            <small class="text-muted">{{ $patientId }}</small>
                                                                        </td>
                                                                        <td>
                                                                            <small class="text-muted">
                                                                                @if($doctorName !== 'غير محدد') د. {{ $doctorName }}<br> @endif
                                                                                {{ $department }}
                                                                            </small>
                                                                        </td>
                                                                        <td class="text-end">
                                                                            @if($isIns)
                                                                                <div class="text-success fw-bold">{{ number_format($patientShare) }} د.ع <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-0 px-1" style="font-size:0.68rem;">تحمل {{ (float)$copayPct }}%</span></div>
                                                                                <small class="text-muted d-block" style="font-size:0.72rem;">معتمد: {{ number_format($approvedFee) }} د.ع</small>
                                                                            @else
                                                                                <span class="text-success fw-bold">{{ number_format($baseFee) }} د.ع</span>
                                                                            @endif
                                                                        </td>
                                                                        <td>
                                                                            <small>{{ $appointment->created_at->format('Y-m-d') }}</small><br>
                                                                            <small class="text-muted">{{ $appointment->created_at->format('H:i') }}</small>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <a href="{{ route('cashier.payment.form', $appointment->id) }}" class="btn btn-success btn-sm px-3 shadow-sm">
                                                                                <i class="fas fa-money-bill-wave me-1"></i> تسديد
                                                                            </a>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            @endif

                                                            {{-- الطلبات الطبية --}}
                                                            @if($canMedicalRequests)
                                                                @foreach($pendingMedicalRequests ?? [] as $request)
                                                                    @php
                                                                        $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                                                                        $reqPatient = optional(optional($request->visit)->patient);
                                                                        $insType = $request->insurance_type ?? optional(optional($request->visit)->appointment)->insurance_type ?? optional($reqPatient)->insurance_type ?? 'none';
                                                                        $docName = optional(optional(optional($request->visit)->doctor)->user)->name;
                                                                        $isIns = in_array($insType, ['hi', 'moi']);
                                                                        
                                                                        $computedApproved = 0;
                                                                        $computedPatient = 0;
                                                                        $hasComputedItems = false;
                                                                        $copayPct = ($insType === 'hi' && $reqPatient) ? $reqPatient->getCopayPercentageFor($request->type) : (float)($reqPatient->copay_percentage ?? 15);

                                                                        if ($request->type === 'lab') {
                                                                            $testIds = $details['lab_test_ids'] ?? [];
                                                                            if (empty($testIds) && !empty($details['package_id'])) {
                                                                                $pkg = \App\Models\Package::find($details['package_id']);
                                                                                if ($pkg) $testIds = $pkg->labTests()->pluck('lab_tests.id')->toArray();
                                                                            }
                                                                            if (!empty($testIds)) {
                                                                                foreach ($testIds as $tId) {
                                                                                    $t = \App\Models\LabTest::find($tId);
                                                                                    if ($t) {
                                                                                        $hasComputedItems = true;
                                                                                        $p = $t->calculateInsurancePricing($insType, $copayPct);
                                                                                        $computedApproved += $p['approved_price'];
                                                                                        $computedPatient += $p['patient_share'];
                                                                                    }
                                                                                }
                                                                            } elseif (!empty($details['tests'])) {
                                                                                foreach ($details['tests'] as $tName) {
                                                                                    $t = \App\Models\LabTest::where('name', $tName)->orWhere('code', $tName)->first();
                                                                                    if ($t) {
                                                                                        $hasComputedItems = true;
                                                                                        $p = $t->calculateInsurancePricing($insType, $copayPct);
                                                                                        $computedApproved += $p['approved_price'];
                                                                                        $computedPatient += $p['patient_share'];
                                                                                    }
                                                                                }
                                                                            }
                                                                        } elseif ($request->type === 'radiology') {
                                                                            $typeIds = $details['radiology_type_ids'] ?? $details['radiology_types'] ?? $details['radiology_type_id'] ?? $details['ultrasound_type_id'] ?? [];
                                                                            if (!is_array($typeIds)) $typeIds = [$typeIds];
                                                                            if (!empty($typeIds)) {
                                                                                foreach ($typeIds as $rId) {
                                                                                    $r = \App\Models\RadiologyType::find($rId);
                                                                                    if ($r) {
                                                                                        $hasComputedItems = true;
                                                                                        $p = $r->calculateInsurancePricing($insType, $copayPct);
                                                                                        $computedApproved += $p['approved_price'];
                                                                                        $computedPatient += $p['patient_share'];
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    @endphp
                                                                    <tr>
                                                                        <td><strong>#{{ $request->id }}</strong></td>
                                                                        <td>
                                                                            @if($request->type === 'lab')
                                                                                <span class="badge bg-primary"><i class="fas fa-flask me-1"></i> تحاليل</span>
                                                                            @elseif($request->type === 'radiology')
                                                                                <span class="badge bg-info text-dark"><i class="fas fa-x-ray me-1"></i> أشعة</span>
                                                                            @elseif($request->type === 'pharmacy')
                                                                                <span class="badge bg-success"><i class="fas fa-pills me-1"></i> صيدلية</span>
                                                                            @else
                                                                                <span class="badge bg-secondary">{{ $request->type }}</span>
                                                                            @endif
                                                                        </td>
                                                                        <td>
                                                                            <div class="fw-semibold">{{ optional(optional($reqPatient)->user)->name ?? 'غير محدد' }}
                                                                                @if($insType === 'moi')
                                                                                    <span class="badge bg-primary fs-8 ms-1"><i class="fas fa-shield-alt"></i> داخليّة</span>
                                                                                @elseif($insType === 'hi')
                                                                                    <span class="badge bg-info text-dark fs-8 ms-1"><i class="fas fa-heartbeat"></i> ضمان صحي</span>
                                                                                @endif
                                                                            </div>
                                                                            <small class="text-muted">{{ optional($reqPatient)->national_id ?? 'غير محدد' }}</small>
                                                                        </td>
                                                                        <td>
                                                                            @if($request->type === 'lab' && isset($details['lab_test_ids']))
                                                                                <small class="text-muted">
                                                                                    <i class="fas fa-vial text-primary me-1"></i> {{ count($details['lab_test_ids']) }} تحليل
                                                                                    @if($docName) <br>طلب: د. {{ $docName }} @endif
                                                                                </small>
                                                                            @elseif($request->type === 'radiology' && isset($details['radiology_type_ids']))
                                                                                <small class="text-muted">
                                                                                    <i class="fas fa-camera text-info me-1"></i> {{ count($details['radiology_type_ids']) }} فحص شعاعي
                                                                                    @if($docName) <br>طلب: د. {{ $docName }} @endif
                                                                                </small>
                                                                            @else
                                                                                <small class="text-muted">{{ $request->description }}</small>
                                                                            @endif
                                                                        </td>
                                                                        <td class="text-end">
                                                                            @if($isIns && $hasComputedItems)
                                                                                <div class="text-success fw-bold">{{ number_format($computedPatient) }} د.ع <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-0 px-1" style="font-size:0.68rem;">تحمل {{ (float)$copayPct }}%</span></div>
                                                                                <small class="text-muted d-block" style="font-size:0.72rem;">معتمد: {{ number_format($computedApproved) }} د.ع</small>
                                                                            @elseif($isIns && $request->total_amount > 0)
                                                                                @php $pShare = round($request->total_amount * ($copayPct / 100)); @endphp
                                                                                <div class="text-success fw-bold">{{ number_format($pShare) }} د.ع <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-0 px-1" style="font-size:0.68rem;">تحمل {{ (float)$copayPct }}%</span></div>
                                                                                <small class="text-muted d-block" style="font-size:0.72rem;">معتمد: {{ number_format($request->total_amount) }} د.ع</small>
                                                                            @else
                                                                                <span class="text-success fw-bold">{{ $request->total_amount !== null ? number_format($request->total_amount) . ' د.ع' : ($hasComputedItems ? number_format($computedApproved) . ' د.ع' : 'يحدد عند التسديد') }}</span>
                                                                            @endif
                                                                        </td>
                                                                        <td>
                                                                            <small>{{ $request->created_at->format('Y-m-d') }}</small><br>
                                                                            <small class="text-muted">{{ $request->created_at->format('H:i') }}</small>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <a href="{{ route('cashier.request.payment.form', $request->id) }}" class="btn btn-success btn-sm px-3 shadow-sm">
                                                                                <i class="fas fa-money-bill-wave me-1"></i> تسديد
                                                                            </a>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            @endif

                                                            {{-- الطوارئ --}}
                                                            @if($canEmergency)
                                                                @foreach($pendingEmergencyPayments ?? [] as $payment)
                                                                    @php
                                                                        $em = $payment->emergency;
                                                                        $patientName = $em->patient ? (optional($em->patient->user)->name ?? 'غير محدد') : ($em->emergencyPatient->name ?? 'غير محدد');
                                                                        $patientId = $em->patient ? (optional($em->patient->user)->phone ?? '---') : ($em->emergencyPatient->phone ?? '---');
                                                                    @endphp
                                                                    <tr>
                                                                        <td><strong>#{{ $payment->id }}</strong></td>
                                                                        <td><span class="badge bg-danger"><i class="fas fa-ambulance me-1"></i> طوارئ</span></td>
                                                                        <td>
                                                                            <div class="fw-semibold">{{ $patientName }}</div>
                                                                            <small class="text-muted">{{ $patientId }}</small>
                                                                        </td>
                                                                        <td>
                                                                            <span class="badge bg-{{ $payment->emergency->priority_color }}">{{ $payment->emergency->priority_text }}</span>
                                                                            <small class="text-muted d-block mt-1">خدمات: {{ $payment->emergency->services->count() }}</small>
                                                                        </td>
                                                                        <td class="text-end text-success fw-bold">{{ number_format($payment->amount, 2) }} IQD</td>
                                                                        <td>
                                                                            <small>{{ $payment->created_at->format('Y-m-d') }}</small><br>
                                                                            <small class="text-muted">{{ $payment->created_at->format('H:i') }}</small>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <a href="{{ route('cashier.emergency.payment.form', $payment->id) }}" class="btn btn-success btn-sm px-3 shadow-sm">
                                                                                <i class="fas fa-money-bill-wave me-1"></i> تسديد
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
                                                    <p class="text-muted mb-0">لا توجد معاملات معلقة حالياً</p>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- تبويب كشفية الاستشارية -->
                                        @if($canConsultation)
                                        <div class="tab-pane fade" id="tab-appointments" role="tabpanel">
                                            @if($appointmentsCount > 0)
                                                <div class="table-responsive">
                                                    <table class="table table-hover align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>رقم الموعد</th>
                                                                <th>المريض</th>
                                                                <th>الطبيب المعالج</th>
                                                                <th>العيادة / التخصص</th>
                                                                <th class="text-end">أجور الكشفية</th>
                                                                <th>تاريخ الحجز</th>
                                                                <th class="text-center">الإجراء</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($pendingAppointments ?? [] as $appointment)
                                                                @php
                                                                    $patientName = optional(optional($appointment->patient)->user)->name ?? 'غير محدد';
                                                                    $patientId = optional($appointment->patient)->national_id ?? '---';
                                                                    $doctorName = optional(optional($appointment->doctor)->user)->name ?? 'غير محدد';
                                                                    $department = optional($appointment->department)->name ?? 'غير محدد';
                                                                    $insType = $appointment->insurance_type ?? optional($appointment->patient)->insurance_type ?? 'none';
                                                                    $baseFee = (float)($appointment->consultation_fee > 0 ? $appointment->consultation_fee : (optional($appointment->doctor)->consultation_fee ?? 0));
                                                                    $doctor = $appointment->doctor;
                                                                    $patient = $appointment->patient;
                                                                    $isIns = in_array($insType, ['hi', 'moi']);
                                                                    if ($isIns) {
                                                                        $approvedFee = ($insType === 'moi' && $doctor && $doctor->moi_price > 0) ? (float)$doctor->moi_price : (($insType === 'hi' && $doctor && $doctor->hi_price > 0) ? (float)$doctor->hi_price : $baseFee);
                                                                        $copayPct = ($insType === 'hi' && $patient) ? $patient->getCopayPercentageFor('consultation') : (float)($patient->copay_percentage ?? 15);
                                                                        $patientShare = round($approvedFee * ($copayPct / 100));
                                                                    } else {
                                                                        $approvedFee = $baseFee;
                                                                        $copayPct = 100;
                                                                        $patientShare = $baseFee;
                                                                    }
                                                                @endphp
                                                                <tr>
                                                                    <td><strong>#{{ $appointment->id }}</strong></td>
                                                                    <td>
                                                                        <div class="fw-semibold">{{ $patientName }}
                                                                            @if($insType === 'moi')
                                                                                <span class="badge bg-primary fs-8 ms-1"><i class="fas fa-shield-alt"></i> داخليّة</span>
                                                                            @elseif($insType === 'hi')
                                                                                <span class="badge bg-info text-dark fs-8 ms-1"><i class="fas fa-heartbeat"></i> ضمان صحي</span>
                                                                            @endif
                                                                        </div>
                                                                        <small class="text-muted">{{ $patientId }}</small>
                                                                    </td>
                                                                    <td>د. {{ $doctorName }}</td>
                                                                    <td>{{ $department }}</td>
                                                                    <td class="text-end">
                                                                        @if($isIns)
                                                                            <div class="text-success fw-bold">{{ number_format($patientShare) }} د.ع <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-0 px-1" style="font-size:0.68rem;">تحمل {{ (float)$copayPct }}%</span></div>
                                                                            <small class="text-muted d-block" style="font-size:0.72rem;">معتمد: {{ number_format($approvedFee) }} د.ع</small>
                                                                        @else
                                                                            <span class="text-success fw-bold">{{ number_format($baseFee) }} د.ع</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>{{ $appointment->created_at->format('Y-m-d H:i') }}</td>
                                                                    <td class="text-center">
                                                                        <a href="{{ route('cashier.payment.form', $appointment->id) }}" class="btn btn-success btn-sm px-3 shadow-sm">
                                                                            <i class="fas fa-money-bill-wave me-1"></i> تسديد الكشفية
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="text-center py-5">
                                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                                    <p class="text-muted mb-0">لا توجد مواعيد استشارية معلقة بانتظار الدفع</p>
                                                </div>
                                            @endif
                                        </div>
                                        @endif

                                        <!-- تبويب التحاليل والأشعة -->
                                        @if($canMedicalRequests)
                                        <div class="tab-pane fade" id="tab-requests" role="tabpanel">
                                            @if($requestsCount > 0)
                                                <div class="table-responsive">
                                                    <table class="table table-hover align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>رقم الطلب</th>
                                                                <th>القسم</th>
                                                                <th>المريض</th>
                                                                <th>تفاصيل الفحص / التحليل</th>
                                                                <th>الطبيب الطالب</th>
                                                                <th class="text-end">المبلغ المطلوب</th>
                                                                <th>التاريخ والوقت</th>
                                                                <th class="text-center">الإجراء</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($pendingMedicalRequests ?? [] as $request)
                                                                @php
                                                                    $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                                                                    $reqPatient = optional(optional($request->visit)->patient);
                                                                    $insType = $request->insurance_type ?? optional(optional($request->visit)->appointment)->insurance_type ?? optional($reqPatient)->insurance_type ?? 'none';
                                                                    $docName = optional(optional(optional($request->visit)->doctor)->user)->name ?? '---';
                                                                    $isIns = in_array($insType, ['hi', 'moi']);
                                                                    
                                                                    $computedApproved = 0;
                                                                    $computedPatient = 0;
                                                                    $hasComputedItems = false;
                                                                    $copayPct = ($insType === 'hi' && $reqPatient) ? $reqPatient->getCopayPercentageFor($request->type) : (float)($reqPatient->copay_percentage ?? 15);

                                                                    if ($request->type === 'lab') {
                                                                        $testIds = $details['lab_test_ids'] ?? [];
                                                                        if (empty($testIds) && !empty($details['package_id'])) {
                                                                            $pkg = \App\Models\Package::find($details['package_id']);
                                                                            if ($pkg) $testIds = $pkg->labTests()->pluck('lab_tests.id')->toArray();
                                                                        }
                                                                        if (!empty($testIds)) {
                                                                            foreach ($testIds as $tId) {
                                                                                $t = \App\Models\LabTest::find($tId);
                                                                                if ($t) {
                                                                                    $hasComputedItems = true;
                                                                                    $p = $t->calculateInsurancePricing($insType, $copayPct);
                                                                                    $computedApproved += $p['approved_price'];
                                                                                    $computedPatient += $p['patient_share'];
                                                                                }
                                                                            }
                                                                        } elseif (!empty($details['tests'])) {
                                                                            foreach ($details['tests'] as $tName) {
                                                                                $t = \App\Models\LabTest::where('name', $tName)->orWhere('code', $tName)->first();
                                                                                if ($t) {
                                                                                    $hasComputedItems = true;
                                                                                    $p = $t->calculateInsurancePricing($insType, $copayPct);
                                                                                    $computedApproved += $p['approved_price'];
                                                                                    $computedPatient += $p['patient_share'];
                                                                                }
                                                                            }
                                                                        }
                                                                    } elseif ($request->type === 'radiology') {
                                                                        $typeIds = $details['radiology_type_ids'] ?? $details['radiology_types'] ?? $details['radiology_type_id'] ?? $details['ultrasound_type_id'] ?? [];
                                                                        if (!is_array($typeIds)) $typeIds = [$typeIds];
                                                                        if (!empty($typeIds)) {
                                                                            foreach ($typeIds as $rId) {
                                                                                $r = \App\Models\RadiologyType::find($rId);
                                                                                if ($r) {
                                                                                    $hasComputedItems = true;
                                                                                    $p = $r->calculateInsurancePricing($insType, $copayPct);
                                                                                    $computedApproved += $p['approved_price'];
                                                                                    $computedPatient += $p['patient_share'];
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                @endphp
                                                                <tr>
                                                                    <td><strong>#{{ $request->id }}</strong></td>
                                                                    <td>
                                                                        @if($request->type === 'lab')
                                                                            <span class="badge bg-primary px-2 py-1"><i class="fas fa-flask me-1"></i> المختبر</span>
                                                                        @elseif($request->type === 'radiology')
                                                                            <span class="badge bg-info text-dark px-2 py-1"><i class="fas fa-x-ray me-1"></i> الأشعة</span>
                                                                        @elseif($request->type === 'pharmacy')
                                                                            <span class="badge bg-success px-2 py-1"><i class="fas fa-pills me-1"></i> الصيدلية</span>
                                                                        @else
                                                                            <span class="badge bg-secondary px-2 py-1">{{ $request->type }}</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <div class="fw-semibold">{{ optional(optional($reqPatient)->user)->name ?? 'غير محدد' }}
                                                                            @if($insType === 'moi')
                                                                                <span class="badge bg-primary fs-8 ms-1"><i class="fas fa-shield-alt"></i> داخليّة</span>
                                                                            @elseif($insType === 'hi')
                                                                                <span class="badge bg-info text-dark fs-8 ms-1"><i class="fas fa-heartbeat"></i> ضمان صحي</span>
                                                                            @endif
                                                                        </div>
                                                                        <small class="text-muted">{{ optional($reqPatient)->national_id ?? 'غير محدد' }}</small>
                                                                    </td>
                                                                    <td>
                                                                        @if($request->type === 'lab' && isset($details['lab_test_ids']))
                                                                            <span class="text-dark fw-bold"><i class="fas fa-vial text-primary me-1"></i> {{ count($details['lab_test_ids']) }} تحليل</span>
                                                                            @if(!empty($details['package_id']))
                                                                                <small class="badge bg-light text-dark border ms-1">باقة #{{ $details['package_id'] }}</small>
                                                                            @endif
                                                                        @elseif($request->type === 'radiology' && isset($details['radiology_type_ids']))
                                                                            <span class="text-dark fw-bold"><i class="fas fa-camera text-info me-1"></i> {{ count($details['radiology_type_ids']) }} فحص أشعة</span>
                                                                        @else
                                                                            <span>{{ $request->description }}</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>د. {{ $docName }}</td>
                                                                    <td class="text-end">
                                                                        @if($isIns && $hasComputedItems)
                                                                            <div class="text-success fw-bold">{{ number_format($computedPatient) }} د.ع <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-0 px-1" style="font-size:0.68rem;">تحمل {{ (float)$copayPct }}%</span></div>
                                                                            <small class="text-muted d-block" style="font-size:0.72rem;">معتمد: {{ number_format($computedApproved) }} د.ع</small>
                                                                        @elseif($isIns && $request->total_amount > 0)
                                                                            @php $pShare = round($request->total_amount * ($copayPct / 100)); @endphp
                                                                            <div class="text-success fw-bold">{{ number_format($pShare) }} د.ع <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-0 px-1" style="font-size:0.68rem;">تحمل {{ (float)$copayPct }}%</span></div>
                                                                            <small class="text-muted d-block" style="font-size:0.72rem;">معتمد: {{ number_format($request->total_amount) }} د.ع</small>
                                                                        @else
                                                                            <span class="text-success fw-bold">{{ $request->total_amount !== null ? number_format($request->total_amount) . ' د.ع' : ($hasComputedItems ? number_format($computedApproved) . ' د.ع' : 'يحدد عند التسديد') }}</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>{{ $request->created_at->format('Y-m-d H:i') }}</td>
                                                                    <td class="text-center">
                                                                        <a href="{{ route('cashier.request.payment.form', $request->id) }}" class="btn btn-success btn-sm px-3 shadow-sm">
                                                                            <i class="fas fa-money-bill-wave me-1"></i> تسديد الفحوصات
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="text-center py-5">
                                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                                    <p class="text-muted mb-0">لا توجد طلبات فحوصات أو تحاليل معلقة بانتظار الدفع</p>
                                                </div>
                                            @endif
                                        </div>
                                        @endif

                                        <!-- تبويب الطوارئ -->
                                        @if($canEmergency)
                                        <div class="tab-pane fade" id="tab-emergency" role="tabpanel">
                                            @if($emergencyCount > 0)
                                                <div class="table-responsive">
                                                    <table class="table table-hover align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>رقم الإشعار</th>
                                                                <th>المريض</th>
                                                                <th>درجة الخطورة</th>
                                                                <th>الخدمات</th>
                                                                <th class="text-end">المبلغ المطلوب</th>
                                                                <th>التاريخ والوقت</th>
                                                                <th class="text-center">الإجراء</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($pendingEmergencyPayments ?? [] as $payment)
                                                                @php
                                                                    $em = $payment->emergency;
                                                                    $patientName = $em->patient ? (optional($em->patient->user)->name ?? 'غير محدد') : ($em->emergencyPatient->name ?? 'غير محدد');
                                                                    $patientId = $em->patient ? (optional($em->patient->user)->phone ?? '---') : ($em->emergencyPatient->phone ?? '---');
                                                                @endphp
                                                                <tr>
                                                                    <td><strong>#{{ $payment->id }}</strong></td>
                                                                    <td>
                                                                        <div class="fw-semibold">{{ $patientName }}</div>
                                                                        <small class="text-muted">{{ $patientId }}</small>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-{{ $payment->emergency->priority_color }} px-2 py-1">
                                                                            {{ $payment->emergency->priority_text }}
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <small class="text-muted">عدد الخدمات: {{ $payment->emergency->services->count() }}</small>
                                                                    </td>
                                                                    <td class="text-end text-success fw-bold">{{ number_format($payment->amount, 2) }} IQD</td>
                                                                    <td>{{ $payment->created_at->format('Y-m-d H:i') }}</td>
                                                                    <td class="text-center">
                                                                        <a href="{{ route('cashier.emergency.payment.form', $payment->id) }}" class="btn btn-success btn-sm px-3 shadow-sm">
                                                                            <i class="fas fa-money-bill-wave me-1"></i> تسديد الطوارئ
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="text-center py-5">
                                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                                    <p class="text-muted mb-0">لا توجد خدمات طوارئ معلقة بانتظار الدفع</p>
                                                </div>
                                            @endif
                                        </div>
                                        @endif

                                    </div>
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
// تحديث تلقائي للصفحة كل 5 ثواني مع الحفاظ على التبويب النشط
setInterval(function() {
    $.ajax({
        url: window.location.href,
        type: 'GET',
        success: function(response) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(response, 'text/html');
            const newContent = doc.getElementById('cashier-content');
            
            if (newContent) {
                const activeTabBtnId = $('#pendingTabs button.active').attr('id');
                const currentScroll = window.scrollY;
                
                $('#cashier-content').html($(newContent).html());
                
                if (activeTabBtnId) {
                    var tabToActivate = document.getElementById(activeTabBtnId);
                    if (tabToActivate) {
                        var tabInstance = new bootstrap.Tab(tabToActivate);
                        tabInstance.show();
                    }
                }
                
                window.scrollTo(0, currentScroll);
                
                const now = new Date();
                const time = now.toLocaleTimeString('ar-IQ');
                $('#last-update').text('آخر تحديث: ' + time);
            }
        },
        error: function(error) {
            console.error('خطأ في التحديث:', error);
        }
    });
}, 5000);

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
