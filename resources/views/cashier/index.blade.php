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

    <!-- نظام التبويبات -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs" id="cashierTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab" aria-controls="dashboard" aria-selected="true">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        لوحة التحكم
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="operations-tab" data-bs-toggle="tab" data-bs-target="#operations" type="button" role="tab" aria-controls="operations" aria-selected="false">
                        <i class="fas fa-history me-2"></i>
                        سجل العمليات
                        @if($todayPayments && $todayPayments->count() > 0)
                            <span class="badge bg-primary ms-2">{{ $todayPayments->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab" aria-controls="reports" aria-selected="false">
                        <i class="fas fa-chart-bar me-2"></i>
                        التقارير والمخططات
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button" role="tab" aria-controls="financial" aria-selected="false">
                        <i class="fas fa-calculator me-2"></i>
                        الملخص المالي
                    </button>
                </li>
            </ul>

            <div class="tab-content mt-4" id="cashierTabContent">

                <!-- Tab 1: لوحة التحكم الرئيسية -->
                <div class="tab-pane fade show active" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
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
                                            <p class="text-muted mb-1">المعاملات المعلقة</p>
                                            <h3 class="mb-0 text-warning">{{ $todayStats['pending_appointments_count'] + $todayStats['pending_requests_count'] }}</h3>
                                            <small class="text-muted">
                                                مواعيد: {{ $todayStats['pending_appointments_count'] }} | 
                                                طلبات: {{ $todayStats['pending_requests_count'] }}
                                            </small>
                                        </div>
                                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                                            <i class="fas fa-clock fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- قائمة المواعيد والطلبات المعلقة -->
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
                                                        <div>{{ $appointment->patient->user->name }}</div>
                                                        <small class="text-muted">{{ $appointment->patient->national_id ?? 'غير محدد' }}</small>
                                                    </td>
                                                    <td>د. {{ $appointment->doctor->user->name }}</td>
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
                                                        @else
                                                            <span class="badge bg-secondary">{{ $request->type }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div>{{ $request->visit->patient->user->name ?? 'غير محدد' }}</div>
                                                        <small class="text-muted">{{ $request->visit->patient->national_id ?? 'غير محدد' }}</small>
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

                <!-- Tab 2: سجل العمليات -->
                <div class="tab-pane fade" id="operations" role="tabpanel" aria-labelledby="operations-tab">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>
                                سجل العمليات اليومية
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($todayPayments && $todayPayments->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>الوقت</th>
                                                <th>المريض</th>
                                                <th>الطبيب</th>
                                                <th>المبلغ</th>
                                                <th>طريقة الدفع</th>
                                                <th>رقم الإيصال</th>
                                                <th>الحالة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($todayPayments as $payment)
                                                <tr>
                                                    <td>{{ $payment->paid_at->format('H:i') }}</td>
                                                    <td>{{ $payment->appointment->patient->user->name ?? 'غير محدد' }}</td>
                                                    <td>د. {{ $payment->appointment->doctor->user->name ?? 'غير محدد' }}</td>
                                                    <td class="text-success fw-bold">{{ number_format($payment->amount, 2) }} IQD</td>
                                                    <td>
                                                        <span class="badge bg-{{ $payment->payment_method == 'cash' ? 'success' : ($payment->payment_method == 'card' ? 'info' : 'warning') }}">
                                                            {{ $payment->payment_method == 'cash' ? 'نقدي' : ($payment->payment_method == 'card' ? 'بطاقة' : 'تأمين') }}
                                                        </span>
                                                    </td>
                                                    <td><code>{{ $payment->receipt_number }}</code></td>
                                                    <td>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i>
                                                            مكتمل
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">لا توجد عمليات دفع اليوم</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tab 3: التقارير والمخططات -->
                <div class="tab-pane fade" id="reports" role="tabpanel" aria-labelledby="reports-tab">
                    <div class="row mb-4">
                        <!-- مخطط الإيرادات اليومية -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-chart-pie me-2"></i>
                                        توزيع الإيرادات اليوم
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div style="position: relative; height: 300px;">
                                        <canvas id="revenueChart"></canvas>
                                    </div>
                                    <div class="mt-3">
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <div class="text-info fw-bold">{{ number_format($todayStats['doctor_fees'], 2) }} IQD</div>
                                                <small class="text-muted">أجور الأطباء</small>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-primary fw-bold">{{ number_format($todayStats['hospital_profit'], 2) }} IQD</div>
                                                <small class="text-muted">ربح المستشفى</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- مخطط الأداء الشهري -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-chart-line me-2"></i>
                                        الأداء الشهري
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div style="position: relative; height: 300px;">
                                        <canvas id="monthlyChart"></canvas>
                                    </div>
                                    <div class="mt-3">
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="text-success fw-bold">{{ number_format($monthlyStats['total_revenue'] ?? 0, 0) }}</div>
                                                <small class="text-muted">إجمالي الشهر</small>
                                            </div>
                                            <div class="col-4">
                                                <div class="text-info fw-bold">{{ number_format($monthlyStats['avg_daily'] ?? 0, 0) }}</div>
                                                <small class="text-muted">متوسط يومي</small>
                                            </div>
                                            <div class="col-4">
                                                <div class="text-primary fw-bold">{{ $monthlyStats['total_payments'] ?? 0 }}</div>
                                                <small class="text-muted">عدد العمليات</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 4: الملخص المالي -->
                <div class="tab-pane fade" id="financial" role="tabpanel" aria-labelledby="financial-tab">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-calculator me-2"></i>
                                الملخص المالي التفصيلي
                            </h5>
                            <div>
                                <button class="btn btn-sm btn-outline-dark me-2" onclick="printReport()">
                                    <i class="fas fa-print me-1"></i>
                                    طباعة
                                </button>
                                <button class="btn btn-sm btn-outline-dark" onclick="exportReport()">
                                    <i class="fas fa-download me-1"></i>
                                    تصدير
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- الإيرادات -->
                                <div class="col-md-3">
                                    <div class="border-end pe-3">
                                        <h6 class="text-success mb-3">
                                            <i class="fas fa-plus-circle me-2"></i>
                                            الإيرادات
                                        </h6>
                                        <div class="mb-2">
                                            <small class="text-muted">كشوفات اليوم:</small>
                                            <div class="fw-bold text-success">{{ number_format($todayStats['total_collected'], 2) }} IQD</div>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">متوسط الكشف:</small>
                                            <div class="fw-bold">{{ ($todayPayments && $todayPayments->count() > 0) ? number_format($todayStats['total_collected'] / $todayPayments->count(), 2) : '0.00' }} IQD</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- المصروفات -->
                                <div class="col-md-3">
                                    <div class="border-end pe-3">
                                        <h6 class="text-danger mb-3">
                                            <i class="fas fa-minus-circle me-2"></i>
                                            المصروفات
                                        </h6>
                                        <div class="mb-2">
                                            <small class="text-muted">أجور الأطباء:</small>
                                            <div class="fw-bold text-info">{{ number_format($todayStats['doctor_fees'], 2) }} IQD</div>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">نسبة الأطباء:</small>
                                            <div class="fw-bold">{{ $todayStats['total_collected'] > 0 ? number_format(($todayStats['doctor_fees'] / $todayStats['total_collected']) * 100, 1) : '0.0' }}%</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- الأرباح -->
                                <div class="col-md-3">
                                    <div class="border-end pe-3">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-chart-line me-2"></i>
                                            الأرباح
                                        </h6>
                                        <div class="mb-2">
                                            <small class="text-muted">ربح المستشفى:</small>
                                            <div class="fw-bold text-primary">{{ number_format($todayStats['hospital_profit'], 2) }} IQD</div>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">هامش الربح:</small>
                                            <div class="fw-bold">{{ $todayStats['total_collected'] > 0 ? number_format(($todayStats['hospital_profit'] / $todayStats['total_collected']) * 100, 1) : '0.0' }}%</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- الإحصائيات -->
                                <div class="col-md-3">
                                    <h6 class="text-secondary mb-3">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        الإحصائيات
                                    </h6>
                                    <div class="mb-2">
                                        <small class="text-muted">عدد المرضى:</small>
                                        <div class="fw-bold">{{ $todayPayments ? $todayPayments->count() : 0 }}</div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">متوسط لكل مريض:</small>
                                        <div class="fw-bold">{{ ($todayPayments && $todayPayments->count() > 0) ? number_format($todayStats['total_collected'] / $todayPayments->count(), 0) : '0' }} IQD</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                
                // إعادة إنشاء المخططات بعد التحديث
                createCharts();
                
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

// دوال الطباعة والتصدير
function printReport() {
    window.print();
}

function exportReport() {
    // إنشاء محتوى التقرير
    const reportData = {
        date: new Date().toLocaleDateString('ar-IQ'),
        total_collected: {{ $todayStats['total_collected'] }},
        doctor_fees: {{ $todayStats['doctor_fees'] }},
        hospital_profit: {{ $todayStats['hospital_profit'] }},
        total_payments: {{ $todayStats['total_payments'] }},
        pending_appointments: {{ $todayStats['pending_appointments_count'] }},
        pending_requests: {{ $todayStats['pending_requests_count'] }}
    };

    // تحويل البيانات إلى JSON وتنزيلها
    const dataStr = JSON.stringify(reportData, null, 2);
    const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
    
    const exportFileDefaultName = `تقرير_كاشير_${reportData.date}.json`;
    
    const linkElement = document.createElement('a');
    linkElement.setAttribute('href', dataUri);
    linkElement.setAttribute('download', exportFileDefaultName);
    linkElement.click();
}

// تحسين تفاعل التبويبات
function createCharts() {
    // مخطط توزيع الإيرادات
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        if (revenueCtx.chart) {
            revenueCtx.chart.destroy();
        }
        revenueCtx.chart = new Chart(revenueCtx, {
            type: 'doughnut',
            data: {
                labels: ['أجور الأطباء', 'ربح المستشفى'],
                datasets: [{
                    data: [
                        {{ $todayStats['doctor_fees'] }},
                        {{ $todayStats['hospital_profit'] }}
                    ],
                    backgroundColor: [
                        '#17a2b8', // أزرق للأطباء
                        '#007bff'  // أزرق داكن للمستشفى
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }

    // مخطط الأداء الشهري (بيانات تجريبية للعرض)
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx) {
        if (monthlyCtx.chart) {
            monthlyCtx.chart.destroy();
        }
        
        const daysInMonth = new Date().getDate();
        const labels = [];
        const data = [];
        
        // إنشاء بيانات تجريبية للأيام الماضية
        for (let i = 1; i <= daysInMonth; i++) {
            labels.push(i);
            // بيانات عشوائية للعرض
            data.push(Math.floor(Math.random() * 50000) + 10000);
        }
        
        monthlyCtx.chart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'الإيرادات اليومية',
                    data: data,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' IQD';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
}

$(document).ready(function() {
    createCharts();
    
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
