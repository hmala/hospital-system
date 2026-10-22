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
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                        <p class="text-muted mb-0 small">
                            إدارة المدفوعات والإيصالات - 
                            <span id="last-update">آخر تحديث: الآن</span>
                        </p>
                        <span class="text-muted d-none d-sm-inline">|</span>
                        <!-- أداة التحكم بوقت التحديث التلقائي والتحديث الفوري -->
                        <div class="d-inline-flex align-items-center gap-1 bg-white px-2 py-1 rounded-pill border shadow-sm small">
                            <i class="fas fa-stopwatch text-primary"></i>
                            <span class="text-secondary small fw-bold">التحديث:</span>
                            <select id="refreshIntervalSelect" class="form-select form-select-sm border-0 bg-transparent py-0 ps-1 pe-4 fw-bold text-dark" style="font-size: 0.8rem; width: auto; cursor: pointer;">
                                <option value="15000">15 ثانية</option>
                                <option value="30000" selected>30 ثانية</option>
                                <option value="60000">دقيقة واحدة</option>
                                <option value="120000">دقيقتين</option>
                                <option value="0">إيقاف التلقائي</option>
                            </select>
                            <button type="button" id="manualRefreshBtn" class="btn btn-sm btn-outline-primary rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px;" title="تحديث فوري الآن">
                                <i class="fas fa-sync-alt fa-xs" id="refreshIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- فلتر نطاق التاريخ للمقبوضات المعلقة والبحث المباشر -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <form method="GET" action="{{ route('cashier.index') }}" class="d-flex align-items-center gap-2">
                        <input type="hidden" name="date_filter" value="{{ $dateFilter ?? 'today' }}">
                        <div class="input-group input-group-sm shadow-sm" style="min-width: 240px;">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="بحث باسم المريض، الهاتف، رقم الوصل..." value="{{ $search ?? '' }}">
                            @if(!empty($search))
                                <a href="{{ route('cashier.index', ['date_filter' => $dateFilter ?? 'today', 'custom_date' => $customDate ?? '']) }}" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                            @endif
                            <button type="submit" class="btn btn-primary fw-bold px-3">بحث</button>
                        </div>
                    </form>

                    <div class="btn-group bg-white p-1 rounded-pill shadow-sm border" role="group">
                        <a href="{{ route('cashier.index', ['date_filter' => 'today', 'search' => $search ?? '']) }}" 
                           class="btn btn-sm rounded-pill px-3 fw-bold {{ ($dateFilter ?? 'today') === 'today' ? 'btn-primary text-white' : 'btn-light text-dark' }}">
                            <i class="fas fa-calendar-day me-1"></i> معلقات اليوم
                        </a>
                        <a href="{{ route('cashier.index', ['date_filter' => 'previous', 'search' => $search ?? '']) }}" 
                           class="btn btn-sm rounded-pill px-3 fw-bold {{ ($dateFilter ?? '') === 'previous' ? 'btn-warning text-dark' : 'btn-light text-dark' }}">
                            <i class="fas fa-history me-1"></i> المعلقات السابقة
                            @if(($previousPendingCount ?? 0) > 0)
                                <span class="badge bg-danger rounded-pill ms-1">{{ $previousPendingCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('cashier.index', ['date_filter' => 'all', 'search' => $search ?? '']) }}" 
                           class="btn btn-sm rounded-pill px-3 fw-bold {{ ($dateFilter ?? '') === 'all' ? 'btn-secondary text-white' : 'btn-light text-dark' }}">
                            <i class="fas fa-infinity me-1"></i> كافة التواريخ
                        </a>
                    </div>

                    <!-- اختيار تأريخ محدد -->
                    <form method="GET" action="{{ route('cashier.index') }}" class="d-flex align-items-center gap-1 bg-white p-1 rounded-pill border shadow-sm">
                        <input type="hidden" name="date_filter" value="custom">
                        @if(!empty($search))
                            <input type="hidden" name="search" value="{{ $search }}">
                        @endif
                        <span class="ps-2 text-muted small"><i class="fas fa-calendar-alt text-primary"></i></span>
                        <input type="date" name="custom_date" class="form-control form-control-sm border-0 bg-transparent" style="max-width: 140px; font-size: 0.8rem;" value="{{ $customDate ?? request('custom_date') }}" onchange="this.form.submit()">
                        @if(($dateFilter ?? '') === 'custom' && !empty($customDate))
                            <span class="badge bg-success rounded-pill me-1"><i class="fas fa-check"></i></span>
                        @endif
                    </form>
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

                            @if(($pendingRefundCount ?? 0) > 0)
                                <div class="alert alert-danger border-danger d-flex align-items-center justify-content-between p-3 mb-3 rounded-3 shadow-sm bg-danger bg-opacity-10">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-danger text-white rounded-circle p-2 me-3 shadow-xs d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                            <i class="fas fa-undo-alt fa-lg"></i>
                                        </div>
                                        <div>
                                            <strong class="text-danger d-block fs-6 mb-0">تنبيه الكاشير: يوجد ({{ $pendingRefundCount }}) طلبات طبية ملغاة بعد السداد بانتظار استرجاع المبالغ للمرضى!</strong>
                                            <span class="small text-muted">يرجى تسليم المبالغ للمرضى وإصدار سندات الاسترجاع المالية الرسمية.</span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-danger btn-sm fw-bold px-3 shadow-sm" onclick="document.getElementById('tab-refunds-btn').click();">
                                        <i class="fas fa-arrow-down me-1"></i> فتح مستحقات الاسترجاع الآن
                                    </button>
                                </div>
                            @endif

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
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link fw-bold px-3 py-1-5 rounded-pill" id="tab-nicu-btn" data-bs-toggle="pill" data-bs-target="#tab-nicu" type="button" role="tab">
                                                    <i class="fas fa-baby me-1 text-primary"></i> الخدج والحاضنات
                                                    <span class="badge bg-primary ms-1 rounded-pill">{{ $pendingNicuCount ?? 0 }}</span>
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link fw-bold px-3 py-1-5 rounded-pill {{ ($pendingRefundCount ?? 0) > 0 ? 'text-danger border border-danger' : '' }}" id="tab-refunds-btn" data-bs-toggle="pill" data-bs-target="#tab-refunds" type="button" role="tab">
                                                    <i class="fas fa-undo-alt me-1 text-danger"></i> مستحقات الاسترجاع
                                                    @if(($pendingRefundCount ?? 0) > 0)
                                                        <span class="badge bg-danger ms-1 rounded-pill">{{ $pendingRefundCount }}</span>
                                                    @else
                                                        <span class="badge bg-secondary ms-1 rounded-pill">0</span>
                                                    @endif
                                                </button>
                                            </li>
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
                                                                @php $seenVisitsTabAll = []; @endphp
                                                                @foreach($pendingMedicalRequests ?? [] as $request)
                                                                    @if($request->visit_id && in_array($request->visit_id, $seenVisitsTabAll))
                                                                        @continue
                                                                    @endif
                                                                    @if($request->visit_id)
                                                                        @php $seenVisitsTabAll[] = $request->visit_id; @endphp
                                                                    @endif

                                                                    @php
                                                                        $siblingRequests = $request->visit_id 
                                                                            ? \App\Models\Request::where('visit_id', $request->visit_id)
                                                                                ->where('payment_status', 'pending')
                                                                                ->where('status', '!=', 'cancelled')
                                                                                ->get()
                                                                            : collect([$request]);
                                                                        if ($siblingRequests->isEmpty()) {
                                                                            $siblingRequests = collect([$request]);
                                                                        }

                                                                        $reqPatient = optional(optional($request->visit)->patient);
                                                                        $reqAppt = optional(optional($request->visit)->appointment);
                                                                        $docName = optional(optional(optional($request->visit)->doctor)->user)->name;

                                                                        $totalComputedApproved = 0;
                                                                        $totalComputedPatient = 0;
                                                                        $hasAnyComputedItems = false;
                                                                        $typesPresent = [];
                                                                        $detailsSummaries = [];
                                                                        $primaryInsType = 'none';
                                                                        $primaryCopayPct = 15;

                                                                        foreach ($siblingRequests as $subReq) {
                                                                            $subDetails = is_string($subReq->details) ? json_decode($subReq->details, true) : $subReq->details;
                                                                            $subIsDirect = empty($reqAppt->doctor_id) && (in_array(optional($subReq->visit)->visit_type, ['lab', 'radiology', 'external'], true) || (!empty($subDetails['direct_lab_visit']) || !empty($subDetails['direct_radiology_visit']) || !empty($subDetails['created_at_inquiry'])));
                                                                            $subInsType = $subIsDirect ? 'none' : ($subReq->insurance_type ?? optional($reqAppt)->insurance_type ?? optional($reqPatient)->insurance_type ?? 'none');
                                                                            $primaryInsType = $subInsType;
                                                                            $subIsIns = in_array($subInsType, ['hi', 'moi']);
                                                                            $subCopayPct = ($subInsType === 'hi' && $reqPatient) ? $reqPatient->getCopayPercentageFor($subReq->type) : (float)($reqPatient->copay_percentage ?? 15);
                                                                            $primaryCopayPct = $subCopayPct;

                                                                            if (!in_array($subReq->type, $typesPresent)) {
                                                                                $typesPresent[] = $subReq->type;
                                                                            }

                                                                            if ($subReq->type === 'lab') {
                                                                                $pkgId = $subDetails['package_id'] ?? null;
                                                                                $pkg = $pkgId ? \App\Models\Package::find($pkgId) : null;
                                                                                if ($pkg) {
                                                                                    $hasAnyComputedItems = true;
                                                                                    $pkgPrice = (float)$pkg->price;
                                                                                    $totalComputedApproved += $pkgPrice;
                                                                                    $totalComputedPatient += $pkgPrice;
                                                                                    $pkgTestIds = $pkg->labTests()->pluck('lab_tests.id')->toArray();
                                                                                    $allTestIds = $subDetails['lab_test_ids'] ?? [];
                                                                                    $extraTestIds = array_diff($allTestIds, $pkgTestIds);
                                                                                    foreach ($extraTestIds as $tId) {
                                                                                        $t = \App\Models\LabTest::find($tId);
                                                                                        if ($t) {
                                                                                            $p = $t->calculateInsurancePricing($subInsType, $subCopayPct);
                                                                                            $totalComputedApproved += $p['approved_price'];
                                                                                            $totalComputedPatient += $p['patient_share'];
                                                                                        }
                                                                                    }
                                                                                    $detailsSummaries[] = '<span class="text-primary fw-semibold"><i class="fas fa-box text-warning me-1"></i> ' . ($subDetails['package_name'] ?? $pkg->name) . '</span>';
                                                                                } else {
                                                                                    $testIds = $subDetails['lab_test_ids'] ?? [];
                                                                                    if (!empty($testIds)) {
                                                                                        $hasAnyComputedItems = true;
                                                                                        foreach ($testIds as $tId) {
                                                                                            $t = \App\Models\LabTest::find($tId);
                                                                                            if ($t) {
                                                                                                $p = $t->calculateInsurancePricing($subInsType, $subCopayPct);
                                                                                                $totalComputedApproved += $p['approved_price'];
                                                                                                $totalComputedPatient += $p['patient_share'];
                                                                                            }
                                                                                        }
                                                                                        $detailsSummaries[] = '<span class="text-primary"><i class="fas fa-vial me-1"></i> ' . count($testIds) . ' تحليل</span>';
                                                                                    } elseif (!empty($subDetails['tests'])) {
                                                                                        $hasAnyComputedItems = true;
                                                                                        foreach ($subDetails['tests'] as $tName) {
                                                                                            $t = \App\Models\LabTest::where('name', $tName)->orWhere('code', $tName)->first();
                                                                                            if ($t) {
                                                                                                $p = $t->calculateInsurancePricing($subInsType, $subCopayPct);
                                                                                                $totalComputedApproved += $p['approved_price'];
                                                                                                $totalComputedPatient += $p['patient_share'];
                                                                                            }
                                                                                        }
                                                                                        $detailsSummaries[] = '<span class="text-primary"><i class="fas fa-vial me-1"></i> ' . count($subDetails['tests']) . ' تحليل</span>';
                                                                                    } else {
                                                                                        $detailsSummaries[] = e($subReq->description ?? 'تحاليل مخبرية');
                                                                                    }
                                                                                }
                                                                            } elseif ($subReq->type === 'radiology') {
                                                                                $typeIds = $subDetails['radiology_type_ids'] ?? $subDetails['radiology_types'] ?? $subDetails['radiology_type_id'] ?? $subDetails['ultrasound_type_id'] ?? [];
                                                                                if (!is_array($typeIds)) $typeIds = [$typeIds];
                                                                                if (!empty($typeIds)) {
                                                                                    $hasAnyComputedItems = true;
                                                                                    foreach ($typeIds as $rId) {
                                                                                        $r = \App\Models\RadiologyType::find($rId);
                                                                                        if ($r) {
                                                                                            $p = $r->calculateInsurancePricing($subInsType, $subCopayPct);
                                                                                            $totalComputedApproved += $p['approved_price'];
                                                                                            $totalComputedPatient += $p['patient_share'];
                                                                                        }
                                                                                    }
                                                                                    $detailsSummaries[] = '<span class="text-info"><i class="fas fa-camera me-1"></i> ' . count($typeIds) . ' فحص أشعة</span>';
                                                                                } else {
                                                                                    $detailsSummaries[] = e($subReq->description ?? 'فحص شعاعي');
                                                                                }
                                                                            } else {
                                                                                if ($subReq->total_amount > 0) {
                                                                                    $totalComputedApproved += $subReq->total_amount;
                                                                                    $totalComputedPatient += $subReq->total_amount;
                                                                                }
                                                                                $detailsSummaries[] = e($subReq->description ?? $subReq->type);
                                                                            }
                                                                        }
                                                                        $isIns = in_array($primaryInsType, ['hi', 'moi']);
                                                                    @endphp
                                                                    <tr>
                                                                        <td>
                                                                            <strong>#{{ $siblingRequests->pluck('id')->implode(', #') }}</strong>
                                                                        </td>
                                                                        <td>
                                                                            @foreach($typesPresent as $tp)
                                                                                @if($tp === 'lab')
                                                                                    <span class="badge bg-primary mb-1"><i class="fas fa-flask me-1"></i> تحاليل</span>
                                                                                @elseif($tp === 'radiology')
                                                                                    <span class="badge bg-info text-dark mb-1"><i class="fas fa-x-ray me-1"></i> أشعة</span>
                                                                                @elseif($tp === 'pharmacy')
                                                                                    <span class="badge bg-success mb-1"><i class="fas fa-pills me-1"></i> صيدلية</span>
                                                                                @else
                                                                                    <span class="badge bg-secondary mb-1">{{ $tp }}</span>
                                                                                @endif
                                                                            @endforeach
                                                                            @if($siblingRequests->count() > 1)
                                                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 d-block mt-1" style="font-size: 0.7rem;"><i class="fas fa-link me-1"></i> طلب موحد ({{ $siblingRequests->count() }})</span>
                                                                            @endif
                                                                        </td>
                                                                        <td>
                                                                            <div class="fw-semibold">{{ optional(optional($reqPatient)->user)->name ?? 'غير محدد' }}
                                                                                @if($primaryInsType === 'moi')
                                                                                    <span class="badge bg-primary fs-8 ms-1"><i class="fas fa-shield-alt"></i> داخليّة</span>
                                                                                @elseif($primaryInsType === 'hi')
                                                                                    <span class="badge bg-info text-dark fs-8 ms-1"><i class="fas fa-heartbeat"></i> ضمان صحي</span>
                                                                                @endif
                                                                            </div>
                                                                            <small class="text-muted">{{ optional($reqPatient)->national_id ?? 'غير محدد' }}</small>
                                                                        </td>
                                                                        <td>
                                                                            {!! implode(' <span class="text-muted mx-1">+</span> ', $detailsSummaries) !!}
                                                                            @if($docName) <br><small class="text-muted">طلب: د. {{ $docName }}</small> @endif
                                                                        </td>
                                                                        <td class="text-end">
                                                                            @if($isIns && $hasAnyComputedItems)
                                                                                <div class="text-success fw-bold">{{ number_format($totalComputedPatient) }} د.ع <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-0 px-1" style="font-size:0.68rem;">تحمل {{ (float)$primaryCopayPct }}%</span></div>
                                                                                <small class="text-muted d-block" style="font-size:0.72rem;">معتمد: {{ number_format($totalComputedApproved) }} د.ع</small>
                                                                            @else
                                                                                <span class="text-success fw-bold">{{ $hasAnyComputedItems ? number_format($totalComputedApproved) . ' د.ع' : 'يحدد عند التسديد' }}</span>
                                                                            @endif
                                                                        </td>
                                                                        <td>
                                                                            <small>{{ $request->created_at->format('Y-m-d') }}</small><br>
                                                                            <small class="text-muted">{{ $request->created_at->format('H:i') }}</small>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <a href="{{ route('cashier.request.payment.form', $request->id) }}" class="btn btn-success btn-sm px-3 shadow-sm">
                                                                                <i class="fas fa-money-bill-wave me-1"></i> {{ $siblingRequests->count() > 1 ? 'تسديد شامل (' . $siblingRequests->count() . ')' : 'تسديد' }}
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
                                                <div class="p-3 border-top bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                    <div class="small text-muted">
                                                        <i class="fas fa-info-circle text-primary me-1"></i>
                                                        يعرض تبويب <strong>الكل</strong> أحدث المعاملات المجمعة. لتصفح كامل الصفحات المقسمة والمفهرسة، اختر التبويب المطلوب:
                                                    </div>
                                                    <div class="d-flex gap-2">
                                                        @if($canConsultation)
                                                            <button type="button" class="btn btn-outline-warning btn-sm fw-bold px-2 py-1 text-dark" onclick="document.getElementById('tab-appointments-btn').click()">
                                                                <i class="fas fa-calendar-check me-1"></i> كشفية الاستشارية ({{ $appointmentsCount }})
                                                            </button>
                                                        @endif
                                                        @if($canMedicalRequests)
                                                            <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-2 py-1" onclick="document.getElementById('tab-requests-btn').click()">
                                                                <i class="fas fa-flask me-1"></i> التحاليل والأشعة ({{ $requestsCount }})
                                                            </button>
                                                        @endif
                                                        @if($canEmergency)
                                                            <button type="button" class="btn btn-outline-danger btn-sm fw-bold px-2 py-1" onclick="document.getElementById('tab-emergency-btn').click()">
                                                                <i class="fas fa-ambulance me-1"></i> الطوارئ ({{ $emergencyCount }})
                                                            </button>
                                                        @endif
                                                    </div>
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
                                                @if(isset($pendingAppointments) && $pendingAppointments->hasPages())
                                                    <div class="p-3 border-top bg-light d-flex justify-content-between align-items-center">
                                                        <span class="small text-muted">عرض {{ $pendingAppointments->firstItem() }} إلى {{ $pendingAppointments->lastItem() }} من أصل {{ $pendingAppointments->total() }} موعد</span>
                                                        {{ $pendingAppointments->links() }}
                                                    </div>
                                                @endif
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
                                                            @php $seenVisitsTabRequests = []; @endphp
                                                            @foreach($pendingMedicalRequests ?? [] as $request)
                                                                @if($request->visit_id && in_array($request->visit_id, $seenVisitsTabRequests))
                                                                    @continue
                                                                @endif
                                                                @if($request->visit_id)
                                                                    @php $seenVisitsTabRequests[] = $request->visit_id; @endphp
                                                                @endif

                                                                @php
                                                                    $siblingRequests = $request->visit_id 
                                                                        ? \App\Models\Request::where('visit_id', $request->visit_id)
                                                                            ->where('payment_status', 'pending')
                                                                            ->where('status', '!=', 'cancelled')
                                                                            ->get()
                                                                        : collect([$request]);
                                                                    if ($siblingRequests->isEmpty()) {
                                                                        $siblingRequests = collect([$request]);
                                                                    }
                                                                    $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                                                                    $reqPatient = optional(optional($request->visit)->patient);
                                                                    $reqAppt = optional(optional($request->visit)->appointment);
                                                                    $isDirectVisit = empty($reqAppt->doctor_id) && (in_array(optional($request->visit)->visit_type, ['lab', 'radiology', 'external'], true) || (!empty($details['direct_lab_visit']) || !empty($details['direct_radiology_visit']) || !empty($details['created_at_inquiry'])));
                                                                    
                                                                    if ($isDirectVisit) {
                                                                        $insType = 'none';
                                                                    } else {
                                                                        $insType = $request->insurance_type ?? optional($reqAppt)->insurance_type ?? optional($reqPatient)->insurance_type ?? 'none';
                                                                    }
                                                                    
                                                                    $docName = optional(optional(optional($request->visit)->doctor)->user)->name ?? '---';
                                                                    $isIns = in_array($insType, ['hi', 'moi']);
                                                                    
                                                                    $computedApproved = 0;
                                                                    $computedPatient = 0;
                                                                    $hasComputedItems = false;
                                                                    $copayPct = ($insType === 'hi' && $reqPatient) ? $reqPatient->getCopayPercentageFor($request->type) : (float)($reqPatient->copay_percentage ?? 15);

                                                                    if ($request->type === 'lab') {
                                                                        $packageId = $details['package_id'] ?? null;
                                                                        $pkg = $packageId ? \App\Models\Package::find($packageId) : null;

                                                                        if ($pkg) {
                                                                            $hasComputedItems = true;
                                                                            $pkgPrice = (float)$pkg->price;
                                                                            $computedApproved += $pkgPrice;
                                                                            $computedPatient += $pkgPrice;

                                                                            $pkgTestIds = $pkg->labTests()->pluck('lab_tests.id')->toArray();
                                                                            $allTestIds = $details['lab_test_ids'] ?? [];
                                                                            $extraTestIds = array_diff($allTestIds, $pkgTestIds);
                                                                            foreach ($extraTestIds as $tId) {
                                                                                $t = \App\Models\LabTest::find($tId);
                                                                                if ($t) {
                                                                                    $p = $t->calculateInsurancePricing($insType, $copayPct);
                                                                                    $computedApproved += $p['approved_price'];
                                                                                    $computedPatient += $p['patient_share'];
                                                                                }
                                                                            }
                                                                        } else {
                                                                            $testIds = $details['lab_test_ids'] ?? [];
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
                                                                    <td><strong>#{{ $siblingRequests->pluck('id')->implode(', #') }}</strong></td>
                                                                    <td>
                                                                        @if($siblingRequests->count() > 1)
                                                                            @foreach($siblingRequests->pluck('type')->unique() as $stp)
                                                                                @if($stp === 'lab')
                                                                                    <span class="badge bg-primary px-2 py-1 mb-1"><i class="fas fa-flask me-1"></i> المختبر</span>
                                                                                @elseif($stp === 'radiology')
                                                                                    <span class="badge bg-info text-dark px-2 py-1 mb-1"><i class="fas fa-x-ray me-1"></i> الأشعة</span>
                                                                                @else
                                                                                    <span class="badge bg-secondary px-2 py-1 mb-1">{{ $stp }}</span>
                                                                                @endif
                                                                            @endforeach
                                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 d-block mt-1" style="font-size: 0.7rem;"><i class="fas fa-link me-1"></i> طلب موحد</span>
                                                                        @else
                                                                            @if($request->type === 'lab')
                                                                                <span class="badge bg-primary px-2 py-1"><i class="fas fa-flask me-1"></i> المختبر</span>
                                                                            @elseif($request->type === 'radiology')
                                                                                <span class="badge bg-info text-dark px-2 py-1"><i class="fas fa-x-ray me-1"></i> الأشعة</span>
                                                                            @elseif($request->type === 'pharmacy')
                                                                                <span class="badge bg-success px-2 py-1"><i class="fas fa-pills me-1"></i> الصيدلية</span>
                                                                            @else
                                                                                <span class="badge bg-secondary px-2 py-1">{{ $request->type }}</span>
                                                                            @endif
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
                                                                        @if($request->type === 'lab')
                                                                            @if(!empty($details['package_id']))
                                                                                @php $pkgName = $details['package_name'] ?? optional(\App\Models\Package::find($details['package_id']))->name ?? 'باقة مختبر'; @endphp
                                                                                <div>
                                                                                    <span class="text-primary fw-bold"><i class="fas fa-box text-warning me-1"></i> {{ $pkgName }}</span>
                                                                                    <small class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-1">{{ count($details['lab_test_ids'] ?? []) }} تحليل مشمول</small>
                                                                                </div>
                                                                            @elseif(isset($details['lab_test_ids']))
                                                                                <span class="text-dark fw-bold"><i class="fas fa-vial text-primary me-1"></i> {{ count($details['lab_test_ids']) }} تحليل</span>
                                                                            @else
                                                                                <span>{{ $request->description }}</span>
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
                                                                            <i class="fas fa-money-bill-wave me-1"></i> {{ $siblingRequests->count() > 1 ? 'تسديد شامل (' . $siblingRequests->count() . ')' : 'تسديد الفحوصات' }}
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                @if(isset($pendingMedicalRequests) && $pendingMedicalRequests->hasPages())
                                                    <div class="p-3 border-top bg-light d-flex justify-content-between align-items-center">
                                                        <span class="small text-muted">عرض {{ $pendingMedicalRequests->firstItem() }} إلى {{ $pendingMedicalRequests->lastItem() }} من أصل {{ $pendingMedicalRequests->total() }} طلب</span>
                                                        {{ $pendingMedicalRequests->links() }}
                                                    </div>
                                                @endif
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
                                                 @if(isset($pendingEmergencyPayments) && $pendingEmergencyPayments->hasPages())
                                                     <div class="p-3 border-top bg-light d-flex justify-content-between align-items-center">
                                                         <span class="small text-muted">عرض {{ $pendingEmergencyPayments->firstItem() }} إلى {{ $pendingEmergencyPayments->lastItem() }} من أصل {{ $pendingEmergencyPayments->total() }} حالة</span>
                                                         {{ $pendingEmergencyPayments->links() }}
                                                     </div>
                                                 @endif
                                            @else
                                                <div class="text-center py-5">
                                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                                    <p class="text-muted mb-0">لا توجد خدمات طوارئ معلقة بانتظار الدفع</p>
                                                </div>
                                            @endif
                                        </div>
                                        @endif

                                        <!-- تبويب الخدج والحاضنات (NICU Consolidated Inpatient Folios) -->
                                        <div class="tab-pane fade" id="tab-nicu" role="tabpanel">
                                            @if(isset($nicuAccounts) && count($nicuAccounts) > 0)
                                                <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                    <div>
                                                        <strong class="text-dark"><i class="fas fa-baby text-primary me-1"></i> الحسابات المالية الموحدة للحاضنات والخدج</strong>
                                                        <span class="text-muted small d-block">نظام القيد المالي الديناميكي: تُجمع تلقائياً كافة أجور الإقامة، الفحوصات المخبرية، والأشعة، مع خصم الأمانات المسددة مسبقاً.</span>
                                                    </div>
                                                    <span class="badge bg-primary fs-7 px-3 py-2">
                                                        <i class="fas fa-user-clock me-1"></i> {{ count($nicuAccounts) }} حاضنة نشطة
                                                    </span>
                                                </div>

                                                <div class="table-responsive">
                                                    <table class="table table-hover align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th># الحجز</th>
                                                                <th>الطفل / المسؤول</th>
                                                                <th>الحاضنة والباقة</th>
                                                                <th>تاريخ الدخول</th>
                                                                <th class="text-end">إجمالي الفاتورة</th>
                                                                <th class="text-end">المسدد (أمانات)</th>
                                                                <th class="text-end">الرصيد المتبقي</th>
                                                                <th class="text-center">الإجراء المالي</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($nicuAccounts as $acc)
                                                                @php
                                                                    $res = $acc['reservation'];
                                                                    $stay = $acc['stay_details'];
                                                                    $babyName = $res->baby_name ?: 'طفل ' . ($res->patient?->name ?? 'غير محدد');
                                                                    $parentName = $res->patient?->name ?? 'غير محدد';
                                                                    $phone = $res->patient?->phone ?? '-';
                                                                    $isSettled = $acc['net_balance'] <= 0;
                                                                @endphp
                                                                <tr class="{{ $isSettled ? 'table-success bg-opacity-25' : '' }}">
                                                                    <td>
                                                                        <strong>#{{ $res->id }}</strong>
                                                                        <span class="badge bg-light text-dark border ms-1 font-monospace">ملف: #{{ $res->patient?->national_id ?: $res->patient_id }}</span>
                                                                    </td>
                                                                    <td>
                                                                        <div class="fw-bold text-dark">{{ $babyName }}</div>
                                                                        <small class="text-muted">المسؤول: {{ $parentName }} • {{ $phone }}</small>
                                                                        @if($res->status === 'discharged')
                                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle d-inline-block mt-1" style="font-size: 0.68rem;">
                                                                                <i class="fas fa-exclamation-triangle me-1"></i> خروج معلق مالياً
                                                                            </span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle fw-bold">
                                                                            <i class="fas fa-baby me-1"></i> #{{ $res->incubator?->name ?? $res->incubator_id }}
                                                                        </span>
                                                                        <div class="small text-muted mt-1">{{ $res->nicuService?->name ?? 'باقة الخدج' }}</div>
                                                                    </td>
                                                                    <td>
                                                                        <span class="small font-monospace">{{ optional($res->admission_date)->format('Y-m-d') ?: optional($res->created_at)->format('Y-m-d') }}</span>
                                                                        <small class="text-muted d-block">({{ $stay['billable_days'] ?? 1 }} يوم فندقي)</small>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <strong class="text-dark">{{ number_format($acc['gross_total'], 0) }} د.ع</strong>
                                                                        @if(!empty($res->insurance_type) && $res->insurance_type !== 'none')
                                                                            <small class="text-info d-block" style="font-size:0.75rem;">(حصة المريض بعد الضمان)</small>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <strong class="text-success">{{ number_format($acc['total_paid'], 0) }} د.ع</strong>
                                                                        @if($acc['total_paid'] > 0)
                                                                            <small class="text-muted d-block" style="font-size:0.72rem;">({{ $res->payments->count() }} دفعات)</small>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-end">
                                                                        @if($acc['excess_deposit'] > 0)
                                                                            <span class="badge bg-warning-subtle text-dark border border-warning px-2 py-1 fs-7">
                                                                                <i class="fas fa-hand-holding-usd text-warning me-1"></i> فائض: +{{ number_format($acc['excess_deposit'], 0) }} د.ع
                                                                            </span>
                                                                            <small class="text-primary d-block fw-bold" style="font-size:0.72rem;">مستحق الصرف للمريض</small>
                                                                        @elseif($isSettled)
                                                                            <span class="badge bg-success px-2 py-1 fs-7">
                                                                                <i class="fas fa-check-circle me-1"></i> خالص (0 د.ع)
                                                                            </span>
                                                                        @else
                                                                            <strong class="text-danger fs-6">{{ number_format($acc['net_balance'], 0) }} د.ع</strong>
                                                                            <small class="text-danger d-block" style="font-size:0.72rem;">مستحق بالصندوق</small>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <div class="btn-group btn-group-sm">
                                                                            @if($acc['excess_deposit'] > 0)
                                                                                <button type="button" class="btn btn-warning text-dark fw-bold px-2 py-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#nicuRefundModal{{ $res->id }}" title="صرف واسترجاع فائض الأمانات للمريض">
                                                                                    <i class="fas fa-undo-alt me-1 text-danger"></i> إرجاع الأمانات
                                                                                </button>
                                                                            @endif
                                                                            <button type="button" class="btn btn-success fw-bold px-2 py-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#nicuDepositModal{{ $res->id }}" title="قبض سلفة أو تسديد">
                                                                                <i class="fas fa-hand-holding-usd me-1"></i> قبض دفعة
                                                                            </button>
                                                                            <a href="{{ route('cashier.nicu.itemized-receipt.print', $res->id) }}" class="btn btn-outline-info px-2 py-1" target="_blank" title="طباعة كشف حراري تفصيلي بالتحاليل والأشعة">
                                                                                <i class="fas fa-receipt me-1"></i> وصل تفصيلي
                                                                            </a>
                                                                            <a href="{{ route('nicu.statement', $res) }}" class="btn btn-outline-primary px-2 py-1" target="_blank" title="عرض كشف الحساب التراكمي">
                                                                                <i class="fas fa-file-invoice-dollar me-1"></i> كشف
                                                                            </a>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="text-center py-5">
                                                    <i class="fas fa-baby fa-3x text-muted opacity-50 mb-3"></i>
                                                    <p class="text-muted mb-0">لا توجد حجوزات حاضنات نشطة حالياً في قسم الخدج</p>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- تبويب مستحقات الاسترجاع (Refunds) -->
                                        <div class="tab-pane fade" id="tab-refunds" role="tabpanel">
                                            @php
                                                $hasAnyRefunds = (isset($pendingRefundRequests) && $pendingRefundRequests->count() > 0) 
                                                              || (isset($pendingEmergencyRefunds) && $pendingEmergencyRefunds->count() > 0)
                                                              || (isset($nicuRefundAccounts) && $nicuRefundAccounts->count() > 0);
                                            @endphp
                                            @if($hasAnyRefunds)
                                                <div class="table-responsive">
                                                    <table class="table table-hover align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>رقم الطلب / الحالة</th>
                                                                <th>القسم / الخدمة</th>
                                                                <th>المريض</th>
                                                                <th>الوصل الأصلي</th>
                                                                <th>سبب وتاريخ الإلغاء</th>
                                                                <th class="text-end">المبلغ المستحق إرجاعه</th>
                                                                <th class="text-center">الإجراء</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            {{-- استرجاع طلبات الفحوصات الطبية (مختبر / أشعة) --}}
                                                            @foreach($pendingRefundRequests ?? [] as $req)
                                                                @php
                                                                    $details = is_string($req->details) ? json_decode($req->details, true) : $req->details;
                                                                    $pName = $req->visit?->patient?->user?->name ?? $req->payments->first()?->patient?->user?->name ?? 'غير محدد';
                                                                    $pPhone = $req->visit?->patient?->user?->phone ?? $req->payments->first()?->patient?->user?->phone ?? ($req->visit?->patient?->national_id ?? '---');
                                                                    $paidAmount = (float)($details['paid_amount'] ?? $req->payments()->where('amount', '>', 0)->sum('amount'));
                                                                    $alreadyRefunded = abs((float)$req->payments()->where('amount', '<', 0)->sum('amount'));
                                                                    $netRefund = max(0, $paidAmount - $alreadyRefunded);
                                                                    $receiptNo = $details['paid_receipt_number'] ?? optional($req->payments()->where('amount', '>', 0)->latest()->first())->receipt_number;
                                                                    $cancelledAt = $details['cancelled_at'] ?? $req->updated_at->format('Y-m-d H:i');
                                                                    $cancelledBy = $details['cancelled_by_name'] ?? 'موظف القسم';
                                                                    $cancelReason = $details['cancel_reason'] ?? 'بطلب من المريض';
                                                                @endphp
                                                                <tr>
                                                                    <td>
                                                                        <strong class="text-danger">طلب #{{ $req->id }}</strong>
                                                                        <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 ms-1">ملغي بعد الدفع</span>
                                                                    </td>
                                                                    <td>
                                                                        @if($req->type === 'lab')
                                                                            <span class="badge bg-primary text-white"><i class="fas fa-flask me-1"></i> مختبر</span>
                                                                            @if(!empty($details['package_name']))
                                                                                <div class="small fw-semibold text-dark mt-1">باقة: {{ $details['package_name'] }}</div>
                                                                            @endif
                                                                        @else
                                                                            <span class="badge bg-info text-dark"><i class="fas fa-x-ray me-1"></i> أشعة / تصوير</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <div class="fw-bold text-dark">{{ $pName }}</div>
                                                                        <small class="text-muted">{{ $pPhone }}</small>
                                                                    </td>
                                                                    <td>
                                                                        @if($receiptNo)
                                                                            <span class="badge bg-secondary font-monospace">#{{ $receiptNo }}</span>
                                                                        @else
                                                                            <span class="text-muted small">مسدد مسبقاً</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <div class="small text-danger fw-semibold"><i class="fas fa-ban me-1"></i> {{ $cancelReason }}</div>
                                                                        <small class="text-muted">بواسطة: {{ $cancelledBy }} ({{ $cancelledAt }})</small>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <span class="fs-6 fw-bold text-danger">{{ number_format($netRefund, 0) }} د.ع</span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <button type="button" 
                                                                                class="btn btn-danger btn-sm px-3 fw-bold shadow-sm btn-open-refund"
                                                                                data-id="{{ $req->id }}"
                                                                                data-patient="{{ $pName }}"
                                                                                data-receipt="{{ $receiptNo ?? '---' }}"
                                                                                data-amount="{{ $netRefund }}"
                                                                                data-service="{{ $req->type === 'lab' ? 'تحاليل المختبر' : 'الأشعة والتصوير' }}"
                                                                                data-action="{{ route('cashier.request.refund.process', $req->id) }}">
                                                                            <i class="fas fa-undo-alt me-1"></i> صرف استرجاع (Refund)
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach

                                                            {{-- استرجاع حالات الطوارئ الملغاة بعد السداد --}}
                                                            @foreach($pendingEmergencyRefunds ?? [] as $emg)
                                                                @php
                                                                    $emgPatientName = $emg->patient?->user?->name ?? $emg->emergencyPatient?->name ?? 'غير محدد';
                                                                    $emgPatientPhone = $emg->patient?->user?->phone ?? $emg->emergencyPatient?->phone ?? '---';
                                                                    $emgPaidAmount = (float)$emg->payments()->where('amount', '>', 0)->sum('amount');
                                                                    $emgAlreadyRefunded = abs((float)$emg->payments()->where('amount', '<', 0)->sum('amount'));
                                                                    $emgNetRefund = max(0, $emgPaidAmount - $emgAlreadyRefunded);
                                                                    $emgReceiptNo = optional($emg->payments()->where('amount', '>', 0)->latest()->first())->receipt_number;
                                                                    $emgCancelledAt = $emg->updated_at->format('Y-m-d H:i');
                                                                @endphp
                                                                <tr>
                                                                    <td>
                                                                        <strong class="text-danger">طوارئ #{{ $emg->id }}</strong>
                                                                        <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 ms-1">ملغي بعد الدفع</span>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-danger text-white"><i class="fas fa-ambulance me-1"></i> طوارئ وإسعاف</span>
                                                                        @if($emg->doctor)
                                                                            <div class="small fw-semibold text-dark mt-1">د. {{ $emg->doctor->user?->name ?? $emg->doctor->name }}</div>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <div class="fw-bold text-dark">{{ $emgPatientName }}</div>
                                                                        <small class="text-muted">{{ $emgPatientPhone }}</small>
                                                                    </td>
                                                                    <td>
                                                                        @if($emgReceiptNo)
                                                                            <span class="badge bg-secondary font-monospace">#{{ $emgReceiptNo }}</span>
                                                                        @else
                                                                            <span class="text-muted small">مسدد مسبقاً</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <div class="small text-danger fw-semibold"><i class="fas fa-ban me-1"></i> حالة طوارئ ملغاة</div>
                                                                        <small class="text-muted">بتأريخ: {{ $emgCancelledAt }}</small>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <span class="fs-6 fw-bold text-danger">{{ number_format($emgNetRefund, 0) }} د.ع</span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <button type="button" 
                                                                                class="btn btn-danger btn-sm px-3 fw-bold shadow-sm btn-open-refund"
                                                                                data-id="{{ $emg->id }}"
                                                                                data-patient="{{ $emgPatientName }}"
                                                                                data-receipt="{{ $emgReceiptNo ?? '---' }}"
                                                                                data-amount="{{ $emgNetRefund }}"
                                                                                data-service="طوارئ وإسعاف"
                                                                                data-action="{{ route('cashier.emergency.refund.process', $emg->id) }}">
                                                                            <i class="fas fa-undo-alt me-1"></i> صرف استرجاع (Refund)
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach

                                                            {{-- استرجاع فائض أمانات حاضنات الخدج (NICU Excess Deposits) --}}
                                                            @foreach($nicuRefundAccounts ?? [] as $acc)
                                                                @php
                                                                    $res = $acc['reservation'];
                                                                    $babyName = $res->baby_name ?: 'طفل ' . ($res->patient?->name ?? 'غير محدد');
                                                                    $parentName = $res->patient?->name ?? 'غير محدد';
                                                                    $phone = $res->patient?->phone ?? '-';
                                                                @endphp
                                                                <tr class="table-warning bg-opacity-10">
                                                                    <td>
                                                                        <strong class="text-primary">حاضنة #{{ $res->id }}</strong>
                                                                        <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 ms-1">
                                                                            <i class="fas fa-baby me-1"></i> الخدج
                                                                        </span>
                                                                        <span class="badge bg-light text-dark border ms-1 font-monospace">ملف: #{{ $res->patient?->national_id ?: $res->patient_id }}</span>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-info text-dark">
                                                                            <i class="fas fa-procedures me-1"></i> #{{ $res->incubator?->name ?? $res->incubator_id }}
                                                                        </span>
                                                                        <div class="small fw-semibold text-dark mt-1">{{ $res->nicuService?->name ?? 'باقة الخدج' }}</div>
                                                                    </td>
                                                                    <td>
                                                                        <div class="fw-bold text-dark">{{ $babyName }}</div>
                                                                        <small class="text-muted">المسؤول: {{ $parentName }} • {{ $phone }}</small>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace">
                                                                            أمانات: {{ number_format($acc['total_paid'], 0) }} د.ع
                                                                        </span>
                                                                        <small class="text-muted d-block" style="font-size: 0.72rem;">المطالبة: {{ number_format($acc['gross_total'], 0) }} د.ع</small>
                                                                    </td>
                                                                    <td>
                                                                        <div class="small text-danger fw-semibold"><i class="fas fa-undo-alt me-1"></i> فائض أمانات بعد تصفية الحساب</div>
                                                                        <small class="text-muted">الحالة: {{ $res->status_arabic ?? $res->status }}</small>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <span class="fs-6 fw-bold text-danger">{{ number_format($acc['excess_deposit'], 0) }} د.ع</span>
                                                                        <small class="text-muted d-block" style="font-size: 0.72rem;">فائض أمانات</small>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <button type="button" class="btn btn-warning text-dark fw-bold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#nicuRefundModal{{ $res->id }}" title="صرف واسترجاع فائض الأمانات">
                                                                            <i class="fas fa-undo-alt me-1 text-danger"></i> إرجاع الأمانات
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="text-center py-5">
                                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                                    <p class="text-muted mb-0">لا توجد طلبات أو حالات ملغاة بانتظار استرجاع المبالغ حالياً</p>
                                                </div>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- end sections container -->
        </div> <!-- end col-12 -->
    </div> <!-- end row mb-4 -->

</div> <!-- end container-fluid #cashier-content -->

<!-- مودال معالجة استرجاع المبالغ للمرضى (Medical Request Refund Modal) - يوضع خارج حاوية cashier-content لحمايته من التحديث الدوري -->
<div class="modal fade" id="medicalRefundModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-danger text-white p-3">
                <div class="d-flex align-items-center">
                    <div class="bg-white text-danger rounded-circle p-2 me-2 shadow-xs d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">صرف استرجاع مالي للمريض (Refund)</h5>
                        <small class="text-white-50">إشعار دائن وتسليم المبلغ للمريض</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="medicalRefundForm" method="POST" onsubmit="var btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.innerHTML = '<i class=\'fas fa-spinner fa-spin me-1\'></i> جاري الصرف...';">
                @csrf
                <div class="modal-body p-4 text-end bg-light">
                    <div class="card border-0 shadow-xs p-3 bg-white mb-3 rounded-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">المريض:</span>
                            <strong id="refundModalPatientName" class="text-dark">---</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">نوع الفحص:</span>
                            <span id="refundModalServiceType" class="badge bg-secondary-subtle text-secondary">---</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">رقم الوصل الأصلي:</span>
                            <span id="refundModalReceiptNo" class="font-monospace fw-bold text-primary">---</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark">المبلغ المستحق إرجاعه:</span>
                            <span id="refundModalAmount" class="fs-5 fw-bold text-danger">0 د.ع</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">طريقة إرجاع المبلغ:</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="ref_method_cash" value="cash" checked>
                                <label class="form-check-label fw-semibold" for="ref_method_cash">💵 نقداً (Cash)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="ref_method_card" value="card">
                                <label class="form-check-label fw-semibold" for="ref_method_card">💳 بطاقة (Card)</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark small">ملاحظات الاسترجاع (اختياري):</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="مثال: تم تسليم المبلغ نقداً للمريض بسبب تعذر الفحص...">
                    </div>
                </div>
                <div class="modal-footer bg-white justify-content-between p-3">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">إلغاء وتراجع</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4 shadow-sm">
                        <i class="fas fa-check-circle me-1"></i> تأكيد صرف الاسترجاع وتوليد السند
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودالات قبض دفعات وأمانات الخدج والحاضنات (NICU Deposit Modals) -->
@if(isset($nicuAccounts) && count($nicuAccounts) > 0)
    @foreach($nicuAccounts as $acc)
        @php
            $res = $acc['reservation'];
            $stay = $acc['stay_details'];
            $babyName = $res->baby_name ?: 'طفل ' . ($res->patient?->name ?? 'غير محدد');
            $parentName = $res->patient?->name ?? 'غير محدد';
            $defaultAmount = $acc['net_balance'] > 0 ? $acc['net_balance'] : 50000;
        @endphp
        <div class="modal fade" id="nicuDepositModal{{ $res->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                    <div class="modal-header bg-success text-white p-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-white text-success rounded-circle p-2 me-2 shadow-xs d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold mb-0">استلام دفعة / سلفة لحاضنة الخدج</h5>
                                <small class="text-white-50">قيد مالي تراكمي وإصدار وصل رسمي</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('cashier.nicu.payment', $res) }}" method="POST">
                        @csrf
                        <div class="modal-body p-4 text-end bg-light">
                            <div class="card border-0 shadow-xs p-3 bg-white mb-3 rounded-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong class="text-dark fs-6">{{ $babyName }}</strong>
                                    <span class="badge bg-primary">حاضنة #{{ $res->incubator?->name ?? $res->incubator_id }}</span>
                                </div>
                                <div class="small text-muted mb-2">المسؤول: {{ $parentName }} • ملف: #{{ $res->patient?->national_id ?: $res->patient_id }}</div>
                                <div class="row g-2 text-center pt-2 border-top">
                                    <div class="col-4">
                                        <small class="text-muted d-block">إجمالي الحساب</small>
                                        <span class="fw-bold text-dark font-monospace">{{ number_format($acc['gross_total'], 0) }}</span>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">المسدد (أمانات)</small>
                                        <span class="fw-bold text-success font-monospace">{{ number_format($acc['total_paid'], 0) }}</span>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">الرصيد المتبقي</small>
                                        <span class="fw-bold {{ $acc['net_balance'] > 0 ? 'text-danger' : 'text-success' }} font-monospace">{{ number_format($acc['net_balance'], 0) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark small">المبلغ المستلم نقداً (IQD) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="amount" class="form-control form-control-lg fw-bold font-monospace text-success text-center" value="{{ $defaultAmount }}" min="1" step="any" required>
                                    <span class="input-group-text fw-bold">د.ع</span>
                                </div>
                                <div class="d-flex gap-1 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="this.closest('.modal-body').querySelector('input[name=amount]').value = '25000'">25,000</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="this.closest('.modal-body').querySelector('input[name=amount]').value = '50000'">50,000</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="this.closest('.modal-body').querySelector('input[name=amount]').value = '100000'">100,000</button>
                                    @if($acc['net_balance'] > 0)
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 fw-bold" onclick="this.closest('.modal-body').querySelector('input[name=amount]').value = '{{ $acc['net_balance'] }}'">كامل المتبقي ({{ number_format($acc['net_balance'], 0) }})</button>
                                    @endif
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark small">طريقة الدفع</label>
                                <select name="payment_method" class="form-select">
                                    <option value="cash" selected>💵 نقداً (Cash)</option>
                                    <option value="card">💳 بطاقة إلكترونية / POS</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark small">نوع الحركة المالية / السند</label>
                                <select name="payment_type" class="form-select">
                                    <option value="deposit" selected>أمانات وسلفة دخول (Advance Deposit)</option>
                                    <option value="interim">دفعة تحت الحساب أثناء الرقود (Interim Payment)</option>
                                    <option value="final_settlement">تسديد نهائي وتصفية حساب (Final Settlement)</option>
                                    @if($acc['excess_deposit'] > 0)
                                        <option value="refund">↩️ صرف استرجاع فائض أمانات (Refund)</option>
                                    @endif
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold text-dark small">ملاحظات إضافية (اختياري)</label>
                                <input type="text" name="notes" class="form-control" placeholder="مثال: دفعة من والد الطفل / استلام مع الدخول">
                            </div>
                        </div>
                        <div class="modal-footer bg-white justify-content-between p-3">
                            <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm">
                                <i class="fas fa-check-circle me-1"></i> تأكيد القبض وطباعة الوصل
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- مودال صرف واسترجاع فائض أمانات الخدج --}}
        @if($acc['excess_deposit'] > 0)
            <div class="modal fade" id="nicuRefundModal{{ $res->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                        <div class="modal-header bg-warning text-dark p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-white text-warning rounded-circle p-2 me-2 shadow-xs d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                    <i class="fas fa-undo-alt text-dark fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title fw-bold mb-0">صرف واسترجاع فائض أمانات حاضنة الخدج</h5>
                                    <small class="text-dark-50">إشعار مدين / صرف مالي وطباعة سند استرجاع رسمي</small>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('cashier.nicu.payment', $res) }}" method="POST" onsubmit="var btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.innerHTML = '<i class=\'fas fa-spinner fa-spin me-1\'></i> جاري الصرف...';">
                            @csrf
                            <input type="hidden" name="payment_type" value="refund">
                            <div class="modal-body p-4 text-end bg-light">
                                <div class="card border-0 shadow-xs p-3 bg-white mb-3 rounded-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong class="text-dark fs-6">{{ $babyName }}</strong>
                                        <span class="badge bg-primary">حاضنة #{{ $res->incubator?->name ?? $res->incubator_id }}</span>
                                    </div>
                                    <div class="small text-muted mb-2">المسؤول: {{ $parentName }} • ملف: #{{ $res->patient?->national_id ?: $res->patient_id }}</div>
                                    <div class="row g-2 text-center pt-2 border-top">
                                        <div class="col-4">
                                            <small class="text-muted d-block">إجمالي الفاتورة</small>
                                            <span class="fw-bold text-dark font-monospace">{{ number_format($acc['gross_total'], 0) }}</span>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted d-block">المسدد (أمانات)</small>
                                            <span class="fw-bold text-success font-monospace">{{ number_format($acc['total_paid'], 0) }}</span>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted d-block">فائض الأمانات</small>
                                            <span class="fw-bold text-danger font-monospace fs-6">+{{ number_format($acc['excess_deposit'], 0) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-warning border-warning border-opacity-50 py-2 px-3 small d-flex align-items-center mb-3">
                                    <i class="fas fa-info-circle me-2 fs-5 text-warning"></i>
                                    <div>
                                        يوجد فائض أمانات مسددة بذمة المستشفى بمبلغ <strong class="text-danger">{{ number_format($acc['excess_deposit'], 0) }} د.ع</strong> مستحق الصرف والإرجاع للمريض فوراً.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold text-dark small">المبلغ المراد صرفه واسترجاعه للمريض (IQD) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="amount" class="form-control form-control-lg fw-bold font-monospace text-danger text-center" value="{{ $acc['excess_deposit'] }}" min="1" max="{{ $acc['excess_deposit'] }}" step="any" required>
                                        <span class="input-group-text fw-bold">د.ع</span>
                                    </div>
                                    <div class="d-flex gap-1 mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 fw-bold" onclick="this.closest('.modal-body').querySelector('input[name=amount]').value = '{{ $acc['excess_deposit'] }}'">
                                            صرف كامل الفائض ({{ number_format($acc['excess_deposit'], 0) }} د.ع)
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold text-dark small">طريقة صرف الاسترجاع</label>
                                    <select name="payment_method" class="form-select">
                                        <option value="cash" selected>💵 نقداً من صندوق الكاشير (Cash)</option>
                                        <option value="card">💳 تحويل إلكتروني / بطاقة</option>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-bold text-dark small">ملاحظات وسبب الصرف</label>
                                    <input type="text" name="notes" class="form-control" value="صرف واسترجاع فائض أمانات لحساب الطفل {{ $babyName }} بعد تصفية الحساب" placeholder="سبب الصرف أو الشخص المستلم">
                                </div>
                            </div>
                            <div class="modal-footer bg-white justify-content-between p-3">
                                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">إلغاء</button>
                                <button type="submit" class="btn btn-warning text-dark fw-bold px-4 shadow-sm">
                                    <i class="fas fa-undo-alt me-1 text-danger"></i> تأكيد صرف واسترجاع المبلغ وطباعة السند
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endif

@endsection

@section('scripts')
<script>
// إدارة التحديث التلقائي لمحطة الكاشير (الافتراضي 30 ثانية مع تحكم مرن)
const defaultRefreshInterval = 30000;
let savedRefreshInterval = localStorage.getItem('cashier_refresh_interval');
let refreshInterval = (savedRefreshInterval !== null) ? parseInt(savedRefreshInterval, 10) : defaultRefreshInterval;
let refreshTimer = null;
let isRefreshing = false;

function updateLiveIndicatorUI() {
    const indicator = $('#live-indicator');
    const select = $('#refreshIntervalSelect');
    
    if (select.length) {
        select.val(refreshInterval);
    }
    
    if (refreshInterval === 0) {
        indicator.removeClass('bg-success').addClass('bg-secondary')
                 .html('<i class="fas fa-pause fa-xs me-1"></i> متوقف (يدوي)');
    } else {
        const seconds = Math.round(refreshInterval / 1000);
        const label = seconds >= 60 ? (seconds / 60) + ' د' : seconds + ' ث';
        indicator.removeClass('bg-secondary').addClass('bg-success')
                 .html('<i class="fas fa-circle fa-xs me-1"></i> مباشر (' + label + ')');
    }
}

function scheduleNextRefresh(delay) {
    if (refreshTimer) {
        clearTimeout(refreshTimer);
        refreshTimer = null;
    }
    if (refreshInterval > 0) {
        refreshTimer = setTimeout(performCashierRefresh, delay !== undefined ? delay : refreshInterval);
    }
}

function performCashierRefresh(isManual) {
    if (isRefreshing) return;

    // إيقاف التحديث اللحظي إذا كان هناك أي مودال مفتوح أو المستند غير مرئي
    if (!isManual) {
        if ($('.modal.show').length > 0 || $('body').hasClass('modal-open') || document.querySelector('.modal.show')) {
            scheduleNextRefresh(10000); // تأجيل في حال وجود نافذة منبثقة مفتوحة
            return;
        }
        if (document.hidden) {
            scheduleNextRefresh(15000); // تأجيل في حال كان التبويب في الخلفية
            return;
        }
        // إيقاف التحديث إذا كان الكاشير يكتب في حقل بحث أو نموذج
        if ($('input:focus, textarea:focus, select:focus').length > 0) {
            scheduleNextRefresh(10000);
            return;
        }
    }

    isRefreshing = true;
    const refreshIcon = $('#refreshIcon');
    refreshIcon.addClass('fa-spin text-primary');

    $.ajax({
        url: window.location.href,
        type: 'GET',
        cache: false,
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

                // مزامنة أداة الاختيار ومؤشر المباشر بعد حقن المحتوى
                updateLiveIndicatorUI();
            }
        },
        error: function(error) {
            console.error('خطأ في تحديث الكاشير:', error);
        },
        complete: function() {
            isRefreshing = false;
            refreshIcon.removeClass('fa-spin text-primary');
            scheduleNextRefresh();
        }
    });
}

// استجابة لتغيير مدة التحديث من القائمة
$(document).on('change', '#refreshIntervalSelect', function() {
    const val = parseInt($(this).val(), 10);
    refreshInterval = isNaN(val) ? defaultRefreshInterval : val;
    localStorage.setItem('cashier_refresh_interval', refreshInterval);
    updateLiveIndicatorUI();
    scheduleNextRefresh();
});

// زر التحديث اليدوي الفوري
$(document).on('click', '#manualRefreshBtn', function(e) {
    e.preventDefault();
    performCashierRefresh(true);
});

// استئناف التحديث الذكي عند العودة للتبويب
document.addEventListener('visibilitychange', function() {
    if (!document.hidden && refreshInterval > 0) {
        scheduleNextRefresh(2000);
    }
});

function openRefundModal(requestId, patientName, receiptNo, amount, serviceType, customActionUrl) {
    const modalEl = document.getElementById('medicalRefundModal');
    const form = document.getElementById('medicalRefundForm');
    
    document.getElementById('refundModalPatientName').textContent = patientName || 'غير محدد';
    document.getElementById('refundModalServiceType').textContent = serviceType || '---';
    document.getElementById('refundModalReceiptNo').textContent = '#' + (receiptNo || '---');
    document.getElementById('refundModalAmount').textContent = Number(amount || 0).toLocaleString('en-US') + ' د.ع';
    
    form.action = customActionUrl || `{{ url('/cashier/request-refund') }}/${requestId}`;
    
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    } else {
        $(modalEl).modal('show');
    }
}

$(document).on('click', '.btn-open-refund', function(e) {
    e.preventDefault();
    const btn = $(this);
    openRefundModal(
        btn.data('id'),
        btn.data('patient'),
        btn.data('receipt'),
        btn.data('amount'),
        btn.data('service'),
        btn.data('action')
    );
});

$(document).ready(function() {
    const now = new Date();
    const time = now.toLocaleTimeString('ar-IQ');
    $('#last-update').text('آخر تحديث: ' + time);

    updateLiveIndicatorUI();
    scheduleNextRefresh();

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
