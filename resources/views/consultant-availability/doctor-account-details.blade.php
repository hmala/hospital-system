@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- ترويسة الصفحة ومعلومات الطبيب --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 bg-white p-3 rounded-4 shadow-sm border">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                        <i class="fas fa-user-md fa-2x"></i>
                    </div>
                    <div>
                        <h2 class="h4 fw-bold text-dark mb-1">
                            كشف حساب الطبيب الاستشاري: د. {{ optional($doctor->user)->name ?? 'غير محدد' }}
                        </h2>
                        <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                            <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill">
                                <i class="fas fa-stethoscope me-1"></i> {{ $doctor->specialization ?? 'استشاري' }}
                            </span>
                            <span>•</span>
                            <span><i class="fas fa-clinic-medical me-1 text-secondary"></i>{{ optional($doctor->department)->name ?? 'العيادات الاستشارية' }}</span>
                            @if(optional($doctor->user)->phone)
                                <span>•</span>
                                <span><i class="fas fa-phone-alt me-1 text-secondary"></i>هاتف: {{ $doctor->user->phone }}</span>
                            @endif
                            <span>•</span>
                            <span class="font-monospace text-secondary">ID: #{{ $doctor->id }}</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('consultant-availability.doctor-accounts') }}" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> العودة لحسابات الأطباء
                    </a>
                    <a href="{{ route('consultant-availability.doctor-account.export', ['doctor' => $doctor]) }}?{{ http_build_query(array_filter(['from_date' => $fromDate, 'to_date' => $toDate, 'payout_search' => $payoutSearch])) }}" 
                       class="btn btn-outline-success rounded-pill px-3 shadow-sm">
                        <i class="fas fa-file-excel me-1"></i> تصدير Excel
                    </a>
                    <button type="button" class="btn btn-outline-dark rounded-pill px-3 shadow-sm" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> طباعة الكشف
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- رسائل التنبيه والنجاح --}}
    @if(session('new_payout_tx_id'))
        <div class="alert alert-success border-2 border-success rounded-4 shadow-sm p-3 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success text-white p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="fas fa-receipt fs-4"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 text-success">
                        تم تسجيل العملية بنجاح برقم سند: 
                        <span class="font-monospace text-dark">{{ session('new_payout_voucher') }}</span>
                    </h6>
                    <div class="small text-muted">
                        المبلغ: <strong class="text-dark">{{ number_format(session('new_payout_amount'), 0) }} د.ع</strong> • تم تحديث رصيد الطبيب والقيد المالي بنجاح.
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('consultant-availability.doctor-payout.print', session('new_payout_tx_id')) }}" target="_blank" class="btn btn-success fw-bold px-4 rounded-pill shadow-sm">
                    <i class="fas fa-print me-1"></i> طباعة سند الصرف الآن
                </a>
            </div>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- بطاقات المؤشرات المالية التنفيذية (KPIs) --}}
    <div class="row g-3 mb-4">
        {{-- الرصيد المتبقي --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-success text-white">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-white-50 small fw-bold d-block mb-1">الرصيد المتبقي للطبيب</span>
                        <h3 class="mb-0 fw-bold">{{ number_format(optional($doctor->financialAccount)->balance ?? 0, 0) }}</h3>
                        <small class="text-white-50">د.ع بذمة المستشفى</small>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 text-white">
                        <i class="fas fa-wallet fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- إجمالي المستحقات المكتسبة --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">إجمالي المستحقات المكتسبة</span>
                        <h4 class="mb-0 fw-bold text-primary">{{ number_format(optional($doctor->financialAccount)->total_earned ?? 0, 0) }}</h4>
                        <small class="text-muted">د.ع تاريخياً</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3">
                        <i class="fas fa-hand-holding-usd fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- إجمالي المبالغ المصروفة --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">إجمالي المبالغ المسددة</span>
                        <h4 class="mb-0 fw-bold text-success">{{ number_format(optional($doctor->financialAccount)->total_paid ?? 0, 0) }}</h4>
                        <small class="text-success">د.ع بسندات صرف</small>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3">
                        <i class="fas fa-receipt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- إجمالي الكشفيات المنفذة --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">كشفيات المراجعين</span>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalExaminations ?? $revenues->total()) }}</h4>
                        <small class="text-muted">كشفية مسجلة</small>
                    </div>
                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-3">
                        <i class="fas fa-user-injured fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- شريط البحث والفلترة الذكي لحساب الطبيب --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('consultant-availability.doctor-account', $doctor) }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" 
                               name="revenue_search" 
                               class="form-control form-control-sm border-start-0 rounded-end-pill" 
                               placeholder="ابحث باسم المريض أو رقم الإيصال..." 
                               value="{{ $revenueSearch ?? '' }}">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0 rounded-start-pill"><i class="fas fa-calendar-alt"></i></span>
                        <input type="date" name="from_date" class="form-control form-control-sm border-start-0 rounded-end-pill" value="{{ $fromDate ?? '' }}" title="من تاريخ">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0 rounded-start-pill"><i class="fas fa-calendar-alt"></i></span>
                        <input type="date" name="to_date" class="form-control form-control-sm border-start-0 rounded-end-pill" value="{{ $toDate ?? '' }}" title="إلى تاريخ">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select name="filter_type" class="form-select form-select-sm rounded-pill">
                        <option value="">كل الوصولات</option>
                        <option value="pending" {{ ($filterType ?? '') === 'pending' ? 'selected' : '' }}>غير مقبوضة (معلقة)</option>
                        <option value="paid" {{ ($filterType ?? '') === 'paid' ? 'selected' : '' }}>مقبوضة (مسددة للطبيب)</option>
                        <option value="refund" {{ ($filterType ?? '') === 'refund' ? 'selected' : '' }}>مسترجعة للمريض</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill w-100 fw-bold shadow-sm">
                        <i class="fas fa-filter me-1"></i> تصفية
                    </button>
                    @if($hasRevenueFilter)
                        <a href="{{ route('consultant-availability.doctor-account', $doctor) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3" title="إلغاء الفلترة والعودة للحساب الإجمالي">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>

            {{-- شريط ملخص نتائج البحث والفترة المحددة --}}
            @if($hasRevenueFilter)
                <div class="alert alert-primary bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-3 mb-0 mt-3 p-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fas fa-info"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-primary">نتائج الفلترة المحددة:</span>
                                @if($fromDate && $toDate)
                                    <span>الفترة من <strong class="font-monospace text-dark">{{ $fromDate }}</strong> إلى <strong class="font-monospace text-dark">{{ $toDate }}</strong></span>
                                @elseif($fromDate)
                                    <span>من تاريخ <strong class="font-monospace text-dark">{{ $fromDate }}</strong></span>
                                @elseif($toDate)
                                    <span>لغاية تاريخ <strong class="font-monospace text-dark">{{ $toDate }}</strong></span>
                                @endif
                                @if($revenueSearch)
                                    <span class="ms-1">(بحث: "<strong class="text-dark">{{ $revenueSearch }}</strong>")</span>
                                @endif
                                <span class="badge bg-primary text-white rounded-pill ms-2 px-2 py-1">{{ $filteredCount }} حركة</span>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-3 font-monospace small">
                            <span class="text-secondary">إجمالي الكشفيات: <strong>{{ number_format($filteredTotalAmount, 0) }} د.ع</strong></span>
                            <span class="badge bg-primary text-white px-3 py-2 rounded-pill fs-6">
                                <i class="fas fa-coins me-1"></i> مستحقات الطبيب للفترة: {{ number_format($filteredDoctorShare, 0) }} د.ع
                            </span>
                            <span class="text-muted">حصة المستشفى: <strong>{{ number_format($filteredHospitalShare, 0) }} د.ع</strong></span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- بطاقة صرف دفعة وتسوية رصيد الطبيب --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light border">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-hand-holding-usd text-success"></i>
                        <span>تسجيل وصرف دفعة نقدية من مستحقات الطبيب</span>
                    </h6>
                    @if($hasRevenueFilter)
                        <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill small">
                            <i class="fas fa-calendar-check me-1"></i> مستحقات الفترة المحددة: {{ number_format($filteredDoctorShare, 0) }} د.ع
                        </span>
                    @endif
                </div>
                @if($availableBalance > 0)
                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-3 py-1 rounded-pill">
                        الرصيد المتاح للصرف: <strong>{{ number_format($availableBalance, 0) }} د.ع</strong>
                    </span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary border px-3 py-1 rounded-pill">
                        الحساب مصفى بالكامل (0 د.ع)
                    </span>
                @endif
            </div>

            @if($availableBalance > 0)
                <form action="{{ route('consultant-availability.doctor-payout', $doctor) }}" method="POST" id="payoutForm" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-12 col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-secondary mb-0">المبلغ المراد صرفه (د.ع)</label>
                            @if($hasRevenueFilter && $filteredDoctorShare > 0)
                                <small class="text-primary fw-bold" style="font-size: 0.75rem;">
                                    (تم اقتراح مستحقات الفترة تلقائياً)
                                </small>
                            @endif
                        </div>
                        <div class="input-group input-group-sm">
                            <input type="number" 
                                   name="amount" 
                                   id="payout_amount" 
                                   class="form-control form-control-sm fw-bold font-monospace {{ $hasRevenueFilter ? 'border-primary' : '' }}" 
                                   max="{{ $availableBalance }}" 
                                   min="0.01" 
                                   step="any" 
                                   value="{{ $suggestedPayoutAmount }}" 
                                   data-available-balance="{{ $availableBalance }}"
                                   data-filtered-share="{{ min($availableBalance, max(0.0, $filteredDoctorShare)) }}"
                                   required>
                            @if($hasRevenueFilter && $filteredDoctorShare > 0)
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm fw-bold" 
                                        id="fillFilteredPeriodBtn" 
                                        title="تعبئة مستحقات الفترة المحددة بالبحث">
                                    <i class="fas fa-calendar-alt me-1"></i> الفترة
                                </button>
                            @endif
                            <button type="button" 
                                    class="btn btn-outline-secondary btn-sm rounded-end-3" 
                                    id="fillMaxBalanceBtn" 
                                    title="تعبئة كامل الرصيد المتاح للطبيب">
                                <i class="fas fa-bolt me-1"></i> كامل الرصيد
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label small fw-bold text-secondary mb-1">البيان والملاحظات</label>
                        <input type="text" 
                               name="notes" 
                               id="payout_notes"
                               class="form-control form-control-sm rounded-3" 
                               value="{{ $suggestedPayoutNotes }}"
                               placeholder="مثال: صرف مستحقات كشفيات العيادة الاستشارية...">
                    </div>
                    <div class="col-12 col-md-3">
                        <button type="submit" class="btn btn-success btn-sm w-100 rounded-3 fw-bold py-1-5 shadow-sm" id="submitPayoutBtn">
                            <i class="fas fa-check-circle me-1"></i> تسجيل واعتماد سند الصرف
                        </button>
                    </div>
                </form>
            @else
                <p class="text-muted small mb-0 mt-1">
                    <i class="fas fa-info-circle me-1 text-primary"></i>
                    لا يوجد رصيد معلق بذمة المستشفى لهذا الطبيب في الوقت الحالي. عندما تُسجل كشفيات جديدة سيظهر الرصيد هنا تلقائياً.
                </p>
            @endif
        </div>
    </div>

    {{-- تبويبات تفاصيل الحساب المالي --}}
    <ul class="nav nav-pills gap-2 mb-3 bg-white p-2 rounded-4 shadow-sm border" id="doctorAccountTabs" role="tablist">
        {{-- التبويب 1: سجل كشفيات ومراجعات المرضى --}}
        <li class="nav-item" role="presentation">
            <button class="nav-link active doctor-account-tab-btn rounded-pill fw-bold px-3 py-2 d-flex align-items-center gap-2" 
                    id="revenues-tab" data-bs-toggle="tab" data-bs-target="#revenues-pane" type="button" role="tab" aria-controls="revenues-pane" aria-selected="true">
                <i class="fas fa-clipboard-list text-primary"></i>
                <span>سجل كشفيات ومراجعات المرضى</span>
                <span class="badge bg-primary text-white rounded-pill px-2">{{ $revenues->total() }}</span>
            </button>
        </li>

        {{-- التبويب 2: سجل سندات الصرف والتسويات --}}
        <li class="nav-item" role="presentation">
            <button class="nav-link doctor-account-tab-btn rounded-pill fw-bold px-3 py-2 d-flex align-items-center gap-2" 
                    id="paid-dues-tab" data-bs-toggle="tab" data-bs-target="#paid-dues-pane" type="button" role="tab" aria-controls="paid-dues-pane" aria-selected="false">
                <i class="fas fa-receipt text-success"></i>
                <span>سجل سندات الصرف والتسويات</span>
                <span class="badge bg-success text-white rounded-pill px-2">{{ $payoutTransactions->total() }}</span>
            </button>
        </li>

        {{-- التبويب 3: الحركات المالية المعلقة --}}
        <li class="nav-item" role="presentation">
            <button class="nav-link doctor-account-tab-btn rounded-pill fw-bold px-3 py-2 d-flex align-items-center gap-2" 
                    id="pending-dues-tab" data-bs-toggle="tab" data-bs-target="#pending-dues-pane" type="button" role="tab" aria-controls="pending-dues-pane" aria-selected="false">
                <i class="fas fa-clock text-warning"></i>
                <span>المستحقات المعلقة</span>
                <span class="badge {{ $pendingDues->total() > 0 ? 'bg-warning text-dark' : 'bg-light text-secondary border' }} rounded-pill px-2">
                    {{ $pendingDues->total() }}
                </span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="doctorAccountTabsContent">
        {{-- التبويب 1: جدول كشفيات ومراجعات المرضى --}}
        <div class="tab-pane fade show active" id="revenues-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                {{-- ترويسة سجل الكشفيات --}}
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="fas fa-clipboard-list text-primary"></i>
                            <span>سجل كشفيات ومراجعات المرضى</span>
                        </h5>
                        <span class="badge bg-light text-secondary border px-2 py-1 rounded-pill small">
                            معروض: {{ $revenues->count() }} من أصل {{ $revenues->total() }}
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2 font-monospace small">
                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1 rounded-pill">
                            المقبوض: {{ number_format($totalReceived, 0) }} د.ع
                        </span>
                        <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 px-2 py-1 rounded-pill">
                            المسترجع: {{ number_format($totalRefunded, 0) }} د.ع
                        </span>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light text-secondary small fw-bold">
                                <tr>
                                    <th class="ps-4"># / الإيصال</th>
                                    <th>التاريخ والوقت</th>
                                    <th>المريض</th>
                                    <th>نوع الخدمة</th>
                                    <th class="text-end">المبلغ الإجمالي</th>
                                    <th class="text-end">حصة الطبيب</th>
                                    <th class="text-end">حصة المستشفى</th>
                                    <th>الكاشير المسؤول</th>
                                    <th class="text-center pe-4">الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($revenues as $rev)
                                    @php
                                        $patient = $rev->patient ?? $rev->appointment?->patient;
                                        $patientUser = $patient?->user;
                                        $isRefund = ($rev->movement_type === 'refund' || $rev->total_amount < 0);
                                    @endphp
                                    <tr class="{{ $isRefund ? 'table-danger bg-opacity-25' : '' }}">
                                        {{-- الإيصال --}}
                                        <td class="ps-4">
                                            <span class="font-monospace fw-bold {{ $isRefund ? 'text-danger' : 'text-dark' }}">
                                                #{{ $rev->receipt_number ?? ($rev->payment?->receipt_number ?? $rev->id) }}
                                            </span>
                                        </td>

                                        {{-- التاريخ والوقت --}}
                                        <td>
                                            <div class="fw-bold text-secondary">{{ $rev->paid_at ? $rev->paid_at->format('Y-m-d') : '---' }}</div>
                                            <small class="text-muted font-monospace">{{ $rev->paid_at ? $rev->paid_at->format('h:i A') : '' }}</small>
                                        </td>

                                        {{-- المريض --}}
                                        <td>
                                            <div class="fw-bold text-dark">{{ $patientUser?->name ?? 'مريض غير محدد' }}</div>
                                            <div class="text-muted small">
                                                @if($patientUser?->phone)
                                                    <span>{{ $patientUser->phone }}</span>
                                                @endif
                                                @if($patient?->patient_id)
                                                    <span class="font-monospace ms-1">({{ $patient->patient_id }})</span>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- نوع الخدمة --}}
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $rev->serviceType?->name ?? 'كشفية استشارية' }}
                                            </span>
                                        </td>

                                        {{-- المبلغ الإجمالي --}}
                                        <td class="text-end font-monospace {{ $isRefund ? 'text-danger fw-bold' : 'text-secondary' }}">
                                            {{ number_format($rev->total_amount, 0) }} د.ع
                                        </td>

                                        {{-- حصة الطبيب --}}
                                        <td class="text-end font-monospace text-primary fw-bold fs-6">
                                            {{ number_format($rev->doctor_share, 0) }} د.ع
                                        </td>

                                        {{-- حصة المستشفى --}}
                                        <td class="text-end font-monospace text-muted small">
                                            {{ number_format($rev->hospital_share, 0) }} د.ع
                                        </td>

                                        {{-- الكاشير --}}
                                        <td class="small text-muted">
                                            <i class="fas fa-user-tag me-1"></i>
                                            {{ $rev->cashier?->name ?? 'غير محدد' }}
                                        </td>

                                        {{-- الحالة --}}
                                        <td class="text-center pe-4">
                                            @if($isRefund)
                                                <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1">
                                                    <i class="fas fa-undo me-1"></i> مسترجعة
                                                </span>
                                            @elseif($rev->doctorDue && $rev->doctorDue->status === 'paid')
                                                <span class="badge bg-success-subtle text-success border border-success border-opacity-25 rounded-pill px-3 py-1" title="تم صرف حصة الطبيب من هذا الوصل">
                                                    <i class="fas fa-check-circle me-1"></i> مقبوضة
                                                </span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning border border-warning border-opacity-25 rounded-pill px-3 py-1" title="لم يتم صرف حصة الطبيب بعد أو تم استرجاع الصرف">
                                                    <i class="fas fa-hourglass-half me-1"></i> غير مقبوضة
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="fas fa-clipboard-list fa-3x mb-3 text-secondary opacity-50"></i>
                                            <p class="mb-0 fw-bold">لا توجد حركات كشفيات مسجلة لهذا الطبيب ضمن محددات البحث الحالية.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ترقيم الكشفيات --}}
                @if($revenues->hasPages())
                    <div class="card-footer bg-white py-3 border-top d-flex justify-content-center">
                        {{ $revenues->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- التبويب 2: سجل سندات الصرف والتسويات المسددة --}}
        <div class="tab-pane fade" id="paid-dues-pane" role="tabpanel">
            {{-- جدول سندات الصرف الرسمية المعتمدة --}}
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="fas fa-file-invoice-dollar text-success"></i>
                            <span>سندات الصرف والتسويات المعتمدة في الخزينة</span>
                        </h5>
                        <small class="text-muted">السندات المالية الصادرة رسمياً لصالح الطبيب مع إمكانية الطباعة والاسترجاع</small>
                    </div>
                    <span class="badge bg-success text-white px-3 py-2 rounded-pill fs-6">
                        إجمالي المسدد: {{ number_format(optional($doctor->financialAccount)->total_paid ?? 0, 0) }} د.ع
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light text-secondary small fw-bold">
                                <tr>
                                    <th class="ps-4">رقم السند</th>
                                    <th>تاريخ ووقت السند</th>
                                    <th>نوع السند</th>
                                    <th class="text-end">المبلغ</th>
                                    <th>المحاسب المسؤول</th>
                                    <th>البيان والملاحظات</th>
                                    <th class="text-center pe-4">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payoutTransactions as $tx)
                                    @php
                                        $isTxRefund = ($tx->category === 'doctor_payout_reversal' || $tx->voucher_type === 'inflow');
                                    @endphp
                                    <tr class="{{ $isTxRefund ? 'bg-danger bg-opacity-10' : '' }}">
                                        <td class="ps-4 font-monospace fw-bold">
                                            <span class="badge {{ $isTxRefund ? 'bg-danger' : 'bg-primary' }} px-2 py-1 rounded-pill">
                                                {{ $tx->voucher_number }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-secondary">{{ $tx->performed_at ? $tx->performed_at->format('Y-m-d') : '---' }}</div>
                                            <small class="text-muted font-monospace">{{ $tx->performed_at ? $tx->performed_at->format('h:i A') : '' }}</small>
                                        </td>
                                        <td>
                                            @if($isTxRefund)
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill">
                                                    <i class="fas fa-undo-alt me-1"></i> سند استرجاع مالي
                                                </span>
                                            @else
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                                    <i class="fas fa-hand-holding-usd me-1"></i> سند صرف نقدي
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-bold fs-6 {{ $isTxRefund ? 'text-danger' : 'text-success' }}">
                                            {{ $isTxRefund ? '+' : '-' }}{{ number_format($tx->amount, 0) }} د.ع
                                        </td>
                                        <td>
                                            <i class="fas fa-user-shield text-muted me-1"></i>
                                            {{ $tx->performer?->name ?? 'محاسب الخزينة' }}
                                        </td>
                                        <td class="text-muted small" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis;" title="{{ $tx->description }} {{ $tx->notes }}">
                                            <div class="fw-bold text-dark">{{ $tx->description }}</div>
                                            @if($tx->notes)
                                                <small class="text-secondary d-block">{{ $tx->notes }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center pe-4">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="{{ route('consultant-availability.doctor-payout.print', $tx->id) }}" 
                                                   target="_blank" 
                                                   class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm fw-bold" 
                                                   title="طباعة سند الصرف الرسمي">
                                                    <i class="fas fa-print me-1"></i> طباعة السند
                                                </a>
                                                @if(! $isTxRefund)
                                                    <button type="button" 
                                                            class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm fw-bold open-reverse-modal-btn"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#reversePayoutModal"
                                                            data-tx-id="{{ $tx->id }}"
                                                            data-voucher="{{ $tx->voucher_number }}"
                                                            data-amount="{{ $tx->amount }}"
                                                            data-doctor="{{ optional($doctor->user)->name }}"
                                                            title="استرجاع أو إلغاء هذا السند كلياً أو جزئياً">
                                                        <i class="fas fa-undo-alt me-1"></i> استرجاع / إلغاء
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fas fa-file-invoice-dollar fa-3x mb-3 text-secondary opacity-50"></i>
                                            <p class="mb-0 fw-bold">لا توجد سندات صرف معتمدة مسجلة لهذا الطبيب حتى الآن.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($payoutTransactions->hasPages())
                    <div class="card-footer bg-white py-3 border-top d-flex justify-content-center">
                        {{ $payoutTransactions->links() }}
                    </div>
                @endif
            </div>

            {{-- قسم تفاصيل الكشفيات المسددة فردياً (الأرشيف التفصيلي) --}}
            <div class="card border shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-light py-2 px-4 d-flex justify-content-between align-items-center">
                    <a class="text-decoration-none text-dark fw-bold small d-flex align-items-center gap-2" 
                       data-bs-toggle="collapse" 
                       href="#individualDuesCollapse" 
                       role="button" 
                       aria-expanded="false" 
                       aria-controls="individualDuesCollapse">
                        <i class="fas fa-list-ul text-secondary"></i>
                        <span>عرض تفاصيل الكشفيات والمستحقات المسددة فردياً ({{ $paidDues->total() }} كشفية مسددة)</span>
                        <i class="fas fa-chevron-down text-muted small ms-1"></i>
                    </a>
                    <span class="badge bg-secondary-subtle text-secondary small px-2 py-1 rounded-pill">أرشيف الكشفيات المسددة</span>
                </div>
                <div class="collapse" id="individualDuesCollapse">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-nowrap">
                                <thead class="table-light text-secondary small fw-bold">
                                    <tr>
                                        <th class="ps-4">رقم القيد</th>
                                        <th>تاريخ ووقت السداد</th>
                                        <th class="text-end">المبلغ</th>
                                        <th>المحاسب المسؤول</th>
                                        <th class="pe-4">البيان والملاحظات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($paidDues as $due)
                                        <tr>
                                            <td class="ps-4 font-monospace fw-bold text-dark">
                                                #{{ $due->id }}
                                            </td>
                                            <td>
                                                <div class="fw-bold text-secondary">{{ $due->paid_at ? $due->paid_at->format('Y-m-d') : ($due->created_at ? $due->created_at->format('Y-m-d') : '---') }}</div>
                                                <small class="text-muted font-monospace">{{ $due->paid_at ? $due->paid_at->format('h:i A') : '' }}</small>
                                            </td>
                                            <td class="text-end font-monospace text-success fw-bold fs-6">
                                                {{ number_format($due->amount, 0) }} د.ع
                                            </td>
                                            <td>
                                                <i class="fas fa-user-shield text-muted me-1"></i>
                                                {{ $due->paidBy?->name ?? 'محاسب' }}
                                            </td>
                                            <td class="pe-4 text-muted small">
                                                {{ $due->notes ?? 'صرف مستحقات أتعاب استشارية' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                لا توجد قيود مسددة تفصيلية.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($paidDues->hasPages())
                        <div class="card-footer bg-white py-2 border-top d-flex justify-content-center">
                            {{ $paidDues->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- التبويب 3: الحركات المالية المعلقة بانتظار الاعتماد --}}
        <div class="tab-pane fade" id="pending-dues-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-clock text-warning"></i>
                        <span>سجل المستحقات المعلقة بانتظار الصرف</span>
                    </h5>
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                        عدد المستحقات: {{ $pendingDues->total() }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light text-secondary small fw-bold">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>تاريخ التسجيل</th>
                                    <th class="text-end">المبلغ المستحق</th>
                                    <th>الحالة</th>
                                    <th class="pe-4">الوصف والملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingDues as $due)
                                    <tr>
                                        <td class="ps-4 font-monospace text-dark">#{{ $due->id }}</td>
                                        <td>{{ $due->created_at ? $due->created_at->format('Y-m-d H:i') : '---' }}</td>
                                        <td class="text-end font-monospace text-warning fw-bold fs-6">
                                            {{ number_format($due->amount, 0) }} د.ع
                                        </td>
                                        <td>
                                            <span class="badge bg-warning-subtle text-warning border border-warning border-opacity-25 rounded-pill px-3 py-1">
                                                <i class="fas fa-hourglass-half me-1"></i> بانتظار الصرف
                                            </span>
                                        </td>
                                        <td class="pe-4 text-muted small">{{ $due->notes ?? 'كشفية استشارية مستحقة' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-check-circle fa-3x mb-3 text-success opacity-50"></i>
                                            <p class="mb-0 fw-bold">لا توجد أي مستحقات معلقة بانتظار الصرف لهذا الطبيب.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($pendingDues->hasPages())
                    <div class="card-footer bg-white py-3 border-top d-flex justify-content-center">
                        {{ $pendingDues->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- مودال: استرجاع أو إلغاء سند الصرف (Full & Partial Reversal Modal) --}}
<div class="modal fade" id="reversePayoutModal" tabindex="-1" aria-labelledby="reversePayoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white py-3 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="reversePayoutModalLabel">
                    <i class="fas fa-undo-alt"></i>
                    <span>استرجاع أو إلغاء سند الصرف</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reversePayoutForm" method="POST" action="">
                @csrf
                <div class="modal-body p-4">
                    {{-- بطاقة تفاصيل السند المراد استرجاعه --}}
                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">رقم السند:</span>
                            <span class="font-monospace fw-bold text-dark fs-6" id="modalVoucherNumber">---</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">المبلغ المسترجع بالكامل:</span>
                            <span class="font-monospace fw-bold text-danger fs-5" id="modalVoucherAmount">0 د.ع</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">الطبيب:</span>
                            <span class="fw-bold text-primary" id="modalDoctorName">{{ optional($doctor->user)->name }}</span>
                        </div>
                    </div>

                    {{-- سبب الاسترجاع --}}
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-1">
                            سبب الاسترجاع والإلغاء <span class="text-danger">*</span>
                        </label>
                        <textarea name="reversal_reason" 
                                  id="modalReversalReason" 
                                  class="form-control rounded-3" 
                                  rows="2" 
                                  required 
                                  placeholder="اكتب سبب إلغاء هذا السند (مثال: إلغاء الصرف لإعادة تدقيق الكشفيات، خطأ في الحساب، إلخ)..."></textarea>
                    </div>

                    <div class="alert alert-warning py-2 px-3 small rounded-3 mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>تنبيه محاسبي:</strong> سيتم إلغاء هذا السند بالكامل، وإعادة المبلغ كاملاً إلى رصيد الطبيب المتاح، وستعود كافة الوصولات المرتبطة به فوراً إلى حالة <strong>(غير مقبوضة)</strong>.
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold shadow-sm" id="confirmReversalSubmitBtn">
                        <i class="fas fa-undo-alt me-1"></i> تأكيد استرجاع السند بالكامل
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. أزرار تعبئة المبالغ السريعة (كامل الرصيد ومستحقات الفترة المحددة)
    const fillMaxBtn = document.getElementById('fillMaxBalanceBtn');
    const fillFilteredBtn = document.getElementById('fillFilteredPeriodBtn');
    const amountInput = document.getElementById('payout_amount');
    const notesInput = document.getElementById('payout_notes');

    if (fillMaxBtn && amountInput) {
        fillMaxBtn.addEventListener('click', function() {
            amountInput.value = this.getAttribute('data-amount') || amountInput.getAttribute('data-available-balance') || amountInput.getAttribute('max');
        });
    }

    if (fillFilteredBtn && amountInput) {
        fillFilteredBtn.addEventListener('click', function() {
            amountInput.value = this.getAttribute('data-amount') || amountInput.getAttribute('data-filtered-share') || '0';
        });
    }

    // 2. تأكيد الصرف عبر SweetAlert2
    const payoutForm = document.getElementById('payoutForm');
    if (payoutForm) {
        payoutForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const amountVal = Number(amountInput.value || 0).toLocaleString();
            const notesVal = notesInput ? notesInput.value.trim() : '';
            const notesHtml = notesVal ? `<div class="small text-muted mt-2 p-2 bg-light rounded"><i class="fas fa-file-alt me-1 text-primary"></i> ${notesVal}</div>` : '';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تأكيد صرف مستحقات الطبيب',
                    html: `هل أنت متأكد من صرف دفعة نقدية للطبيب بمبلغ <b class="text-success fs-5">${amountVal} د.ع</b>؟${notesHtml}`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-hand-holding-usd me-1"></i> نعم، تأكيد الصرف',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        payoutForm.submit();
                    }
                });
            } else {
                if (confirm(`هل أنت متأكد من صرف دفعة نقدية للطبيب بمبلغ ${amountVal} د.ع؟`)) {
                    payoutForm.submit();
                }
            }
        });
    }

    // 3. التبديل الفوري الصارم بين التبويبات (Fail-safe direct tab switching)
    const tabNavButtons = document.querySelectorAll('#doctorAccountTabs button');
    const tabPanes = document.querySelectorAll('#doctorAccountTabsContent .tab-pane');

    window.activateConsultantTab = function(targetId) {
        if (!targetId) return;
        const cleanId = targetId.startsWith('#') ? targetId : '#' + targetId;
        const targetBtn = document.querySelector(`#doctorAccountTabs button[data-bs-target="${cleanId}"]`);
        const targetPane = document.querySelector(cleanId);

        if (targetBtn && targetPane) {
            tabNavButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });
            tabPanes.forEach(pane => {
                pane.classList.remove('show', 'active');
                pane.style.display = 'none';
            });

            targetBtn.classList.add('active');
            targetBtn.setAttribute('aria-selected', 'true');
            targetPane.classList.add('show', 'active');
            targetPane.style.display = 'block';

            if (history.replaceState) {
                history.replaceState(null, null, cleanId);
            }
        }
    };

    tabNavButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetSelector = this.getAttribute('data-bs-target');
            window.activateConsultantTab(targetSelector);
        });
    });

    // 4. معالجة وتجهيز نافذة استرجاع سند الصرف بالكامل (Payout Reversal Modal)
    const reverseForm = document.getElementById('reversePayoutForm');
    const modalVoucherNumber = document.getElementById('modalVoucherNumber');
    const modalVoucherAmount = document.getElementById('modalVoucherAmount');

    document.querySelectorAll('.open-reverse-modal-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const txId = this.getAttribute('data-tx-id');
            const voucher = this.getAttribute('data-voucher');
            const amount = parseFloat(this.getAttribute('data-amount') || 0);

            if (reverseForm) {
                reverseForm.action = '{{ url("consultant-availability/doctor-payouts") }}/' + txId + '/reverse';
            }
            if (modalVoucherNumber) modalVoucherNumber.textContent = voucher;
            if (modalVoucherAmount) modalVoucherAmount.textContent = Number(amount).toLocaleString() + ' د.ع';
        });
    });

    if (reverseForm) {
        reverseForm.addEventListener('submit', function() {
            const submitBtn = document.getElementById('confirmReversalSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الاسترجاع...';
            }
        });
    }

    // دعم الانتقال المباشر للتبويب عبر الهاش
    if (window.location.hash && document.querySelector(window.location.hash)) {
        window.activateConsultantTab(window.location.hash);
    }
});
</script>
@endpush

@push('styles')
<style>
    .doctor-account-tab-btn {
        transition: all 0.2s ease-in-out;
        border: 1px solid #dee2e6 !important;
        background-color: #f8f9fa;
        color: #495057;
    }
    .doctor-account-tab-btn:hover {
        background-color: #e9ecef;
    }
    .doctor-account-tab-btn.active#revenues-tab {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
    }
    .doctor-account-tab-btn.active#revenues-tab i {
        color: #ffffff !important;
    }
    .doctor-account-tab-btn.active#revenues-tab .badge {
        background-color: #ffffff !important;
        color: #0d6efd !important;
    }
    .doctor-account-tab-btn.active#paid-dues-tab {
        background-color: #198754 !important;
        border-color: #198754 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(25, 135, 84, 0.25);
    }
    .doctor-account-tab-btn.active#paid-dues-tab i {
        color: #ffffff !important;
    }
    .doctor-account-tab-btn.active#paid-dues-tab .badge {
        background-color: #ffffff !important;
        color: #198754 !important;
    }
    .doctor-account-tab-btn.active#pending-dues-tab {
        background-color: #ffc107 !important;
        border-color: #ffc107 !important;
        color: #212529 !important;
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.25);
    }
    .doctor-account-tab-btn.active#pending-dues-tab i {
        color: #212529 !important;
    }
    .doctor-account-tab-btn.active#pending-dues-tab .badge {
        background-color: #212529 !important;
        color: #ffffff !important;
    }
</style>
@endpush
