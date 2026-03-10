@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-procedures me-2 text-danger"></i>
                        كاشير العمليات الجراحية
                    </h2>
                    <p class="text-muted">
                        إدارة مدفوعات العمليات الجراحية والفحوصات المطلوبة
                    </p>
                </div>
                <div>
                    <a href="{{ route('cashier.surgeries.paid') }}" class="btn btn-success me-2">
                        <i class="fas fa-file-invoice-dollar me-2"></i>
                        المدفوعات والإيصالات
                    </a>
                    <a href="{{ route('cashier.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-right me-2"></i>
                        العودة للكاشير الرئيسي
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- إحصائيات العمليات -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">المرضى المعلقين</p>
                            <h3 class="mb-0 text-danger">{{ $surgeryStats['patients_count'] ?? 0 }}</h3>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded">
                            <i class="fas fa-users fa-2x text-danger"></i>
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
                            <p class="text-muted mb-1">العمليات المعلقة</p>
                            <h3 class="mb-0 text-warning">{{ $surgeryStats['pending_count'] ?? 0 }}</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="fas fa-clock fa-2x text-warning"></i>
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
                            <p class="text-muted mb-1">عمليات مدفوعة اليوم</p>
                            <h3 class="mb-0 text-success">{{ $surgeryStats['today_paid'] ?? 0 }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
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
                            <p class="text-muted mb-1">إيرادات اليوم</p>
                            <h3 class="mb-0 text-primary">{{ number_format($surgeryStats['today_revenue'] ?? 0, 0) }} IQD</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="fas fa-money-bill-wave fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- قائمة المرضى والعمليات المعلقة -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">
                <i class="fas fa-procedures me-2"></i>
                العمليات الجراحية بانتظار الدفع
                @if($surgeriesByPatient && $surgeriesByPatient->count() > 0)
                    <span class="badge bg-light text-danger">{{ $surgeriesByPatient->count() }} مريض</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if($surgeriesByPatient && $surgeriesByPatient->count() > 0)
                <div class="accordion" id="patientsAccordion">
                    @foreach($surgeriesByPatient as $patientId => $surgeries)
                        @php
                            $patient = $surgeries->first()->patient;
                            $patientTotalAmount = 0;
                            $patientPendingAmount = 0;
                            $patientPaidAmount = 0;
                            foreach($surgeries as $surgery) {
                                $surgeryFee = $surgery->surgery_fee ?? 0; // store value when operation was created/edited
                                $surgeryFeePaid = $surgery->surgery_fee_paid === 'paid';
                                
                                // رسوم الغرفة
                                $roomFee = $surgery->room_fee ?? 0;
                                $roomFeePaid = $surgery->payment_status === 'paid';
                                
                                // تحاليل
                                $pendingLabFee = $surgery->labTests->where('payment_status', '!=', 'paid')->sum(function($test) {
                                    return $test->labTest->price ?? 0;
                                });
                                $paidLabFee = $surgery->labTests->where('payment_status', 'paid')->sum(function($test) {
                                    return $test->labTest->price ?? 0;
                                });
                                
                                // أشعة
                                $pendingRadiologyFee = $surgery->radiologyTests->where('payment_status', '!=', 'paid')->sum(function($test) {
                                    return $test->radiologyType->base_price ?? 0;
                                });
                                $paidRadiologyFee = $surgery->radiologyTests->where('payment_status', 'paid')->sum(function($test) {
                                    return $test->radiologyType->base_price ?? 0;
                                });
                                
                                $patientPendingAmount += ($surgeryFeePaid ? 0 : $surgeryFee) + ($roomFeePaid ? 0 : $roomFee) + $pendingLabFee + $pendingRadiologyFee;
                                $patientPaidAmount += ($surgeryFeePaid ? $surgeryFee : 0) + ($roomFeePaid ? $roomFee : 0) + $paidLabFee + $paidRadiologyFee;
                                $patientTotalAmount += $surgeryFee + $roomFee + $pendingLabFee + $paidLabFee + $pendingRadiologyFee + $paidRadiologyFee;
                            }
                        @endphp
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading{{ $patientId }}">
                                <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#collapse{{ $patientId }}" 
                                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}" 
                                        aria-controls="collapse{{ $patientId }}">
                                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-danger bg-opacity-10 p-2 rounded-circle me-3">
                                                <i class="fas fa-user text-danger"></i>
                                            </div>
                                            <div>
                                                <strong>{{ $patient->user->name ?? 'غير محدد' }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-id-card me-1"></i>
                                                    {{ $patient->national_id ?? 'غير محدد' }}
                                                    @if($patient->user->phone)
                                                        <span class="mx-2">|</span>
                                                        <i class="fas fa-phone me-1"></i>
                                                        {{ $patient->user->phone }}
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-warning me-2">
                                                <i class="fas fa-procedures me-1"></i>
                                                {{ $surgeries->count() }} عملية
                                            </span>
                                            @if($patientPaidAmount > 0)
                                            <span class="badge bg-success me-1">
                                                <i class="fas fa-check me-1"></i>
                                                مدفوع: {{ number_format($patientPaidAmount, 0) }}
                                            </span>
                                            @endif
                                            @if($patientPendingAmount > 0)
                                            <span class="badge bg-danger">
                                                <i class="fas fa-clock me-1"></i>
                                                معلق: {{ number_format($patientPendingAmount, 0) }} IQD
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse{{ $patientId }}" 
                                 class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" 
                                 aria-labelledby="heading{{ $patientId }}" 
                                 data-bs-parent="#patientsAccordion">
                                <div class="accordion-body bg-light">
                                    @foreach($surgeries as $surgery)
                                        @php
                                            // حساب الرسوم مع تتبع ما تم دفعه
                                            $surgeryFee = $surgery->surgery_fee ?? 0; // use stored fee
                                            $surgeryFeePaid = $surgery->surgery_fee_paid === 'paid';
                                            
                                            // حساب رسوم الغرفة
                                            $roomFee = $surgery->room_fee ?? 0;
                                            $roomFeePaid = $surgery->payment_status === 'paid'; // أو حقل منفصل إذا كان موجوداً
                                            
                                            // حساب تحاليل معلقة ومدفوعة
                                            $pendingLabTests = $surgery->labTests->where('payment_status', '!=', 'paid');
                                            $paidLabTests = $surgery->labTests->where('payment_status', 'paid');
                                            $pendingLabFee = $pendingLabTests->sum(function($test) {
                                                return $test->labTest->price ?? 0;
                                            });
                                            $paidLabFee = $paidLabTests->sum(function($test) {
                                                return $test->labTest->price ?? 0;
                                            });
                                            
                                            // حساب أشعة معلقة ومدفوعة
                                            $pendingRadiologyTests = $surgery->radiologyTests->where('payment_status', '!=', 'paid');
                                            $paidRadiologyTests = $surgery->radiologyTests->where('payment_status', 'paid');
                                            $pendingRadiologyFee = $pendingRadiologyTests->sum(function($test) {
                                                return $test->radiologyType->base_price ?? 0;
                                            });
                                            $paidRadiologyFee = $paidRadiologyTests->sum(function($test) {
                                                return $test->radiologyType->base_price ?? 0;
                                            });
                                            
                                            // المبلغ المعلق (يشمل رسوم الغرفة)
                                            $pendingAmount = ($surgeryFeePaid ? 0 : $surgeryFee) + ($roomFeePaid ? 0 : $roomFee) + $pendingLabFee + $pendingRadiologyFee;
                                            // المبلغ المدفوع (يشمل رسوم الغرفة)
                                            $paidAmount = ($surgeryFeePaid ? $surgeryFee : 0) + ($roomFeePaid ? $roomFee : 0) + $paidLabFee + $paidRadiologyFee;
                                            // الإجمالي (يشمل رسوم الغرفة)
                                            $totalAmount = $surgeryFee + $roomFee + $pendingLabFee + $paidLabFee + $pendingRadiologyFee + $paidRadiologyFee;
                                        @endphp
                                        <div class="card mb-3 border-0 shadow-sm">
                                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0">
                                                        <span class="badge bg-danger me-2">#{{ $surgery->id }}</span>
                                                        {{ $surgery->surgery_type }}
                                                        @if($surgery->payment_status === 'partial')
                                                            <span class="badge bg-warning ms-2">دفع جزئي</span>
                                                        @endif
                                                    </h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-user-md me-1"></i>
                                                        د. {{ $surgery->doctor->user->name ?? 'غير محدد' }}
                                                        <span class="mx-2">|</span>
                                                        <i class="fas fa-hospital me-1"></i>
                                                        {{ $surgery->department->name ?? 'غير محدد' }}
                                                        <span class="mx-2">|</span>
                                                        <i class="fas fa-calendar me-1"></i>
                                                        {{ $surgery->scheduled_date->format('Y-m-d') }}
                                                        <i class="fas fa-clock me-1 ms-1"></i>
                                                        {{ $surgery->scheduled_time->format('H:i') }}
                                                    </small>
                                                </div>
                                                @if($pendingAmount > 0)
                                                <a href="{{ route('cashier.surgeries.payment.form', $surgery->id) }}" 
                                                   class="btn btn-success btn-sm">
                                                    <i class="fas fa-money-bill-wave me-1"></i>
                                                    تسديد المتبقي ({{ number_format($pendingAmount, 0) }} IQD)
                                                </a>
                                                @endif
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>البند</th>
                                                                <th>التفاصيل</th>
                                                                <th class="text-end">التكلفة (IQD)</th>
                                                                <th class="text-center">حالة الدفع</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- رسوم العملية -->
                                                            <tr class="{{ $surgeryFeePaid ? 'table-success' : '' }}">
                                                                <td>
                                                                    <i class="fas fa-procedures text-danger me-2"></i>
                                                                    رسوم العملية الجراحية
                                                                </td>
                                                                <td>{{ $surgery->surgery_type }}</td>
                                                                <td class="text-end">{{ number_format($surgeryFee, 0) }}</td>
                                                                <td class="text-center">
                                                                    @if($surgeryFeePaid)
                                                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>مدفوع</span>
                                                                    @else
                                                                        <span class="badge bg-warning"><i class="fas fa-clock me-1"></i>معلق</span>
                                                                    @endif
                                                                </td>
                                                            </tr>

                                                            <!-- رسوم الغرفة -->
                                                            @if($surgery->room_id && $surgery->room)
                                                                @php
                                                                    $roomFee = $surgery->room_fee ?? 0;
                                                                    $roomFeePaid = $surgery->payment_status === 'paid'; // يمكن تعديله حسب طريقة تتبع دفع الغرفة
                                                                @endphp
                                                                <tr class="table-primary">
                                                                    <td colspan="4">
                                                                        <strong>
                                                                            <i class="fas fa-door-open me-2"></i>
                                                                            تفاصيل الغرفة
                                                                        </strong>
                                                                    </td>
                                                                </tr>
                                                                <tr class="{{ $roomFeePaid ? 'table-success' : '' }}">
                                                                    <td class="ps-4">
                                                                        <i class="fas fa-bed text-info me-2"></i>
                                                                        أجور الغرفة
                                                                    </td>
                                                                    <td>
                                                                        <strong>{{ $surgery->room->room_number }}</strong>
                                                                        @if($surgery->room->room_type === 'vip')
                                                                            <span class="badge bg-warning text-dark ms-1">
                                                                                <i class="fas fa-star"></i> VIP
                                                                            </span>
                                                                        @else
                                                                            <span class="badge bg-secondary ms-1">عادية</span>
                                                                        @endif
                                                                        @if($surgery->room->floor)
                                                                            <br><small class="text-muted">{{ $surgery->room->floor }}</small>
                                                                        @endif
                                                                        @if($surgery->expected_stay_days)
                                                                            <br><small class="text-muted">
                                                                                مدة الإقامة: {{ $surgery->expected_stay_days }} يوم
                                                                                ({{ number_format($surgery->room->daily_fee ?? 0, 0) }} د.ع/يوم)
                                                                            </small>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-end">
                                                                        {{ number_format($roomFee, 0) }}
                                                                        @if($surgery->expected_stay_days && $surgery->room)
                                                                            <br><small class="text-muted">(أول ليلة مجانية، التكلفة الفعلية {{ number_format($surgery->room->daily_fee * $surgery->expected_stay_days, 0) }} د.ع)</small>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-center">
                                                                        @if($roomFeePaid)
                                                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>مدفوع</span>
                                                                        @else
                                                                            <span class="badge bg-warning"><i class="fas fa-clock me-1"></i>معلق</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endif

                                                            <!-- التحاليل المطلوبة -->
                                                            @if($surgery->labTests->count() > 0)
                                                                <tr class="table-info">
                                                                    <td colspan="4">
                                                                        <strong>
                                                                            <i class="fas fa-flask me-2"></i>
                                                                            التحاليل المطلوبة ({{ $surgery->labTests->count() }})
                                                                            @if($paidLabTests->count() > 0)
                                                                                - <span class="text-success">{{ $paidLabTests->count() }} مدفوع</span>
                                                                            @endif
                                                                            @if($pendingLabTests->count() > 0)
                                                                                - <span class="text-warning">{{ $pendingLabTests->count() }} معلق</span>
                                                                            @endif
                                                                        </strong>
                                                                    </td>
                                                                </tr>
                                                                @foreach($surgery->labTests as $labTest)
                                                                    @php $isPaid = $labTest->payment_status === 'paid'; @endphp
                                                                    <tr class="{{ $isPaid ? 'table-success' : '' }}">
                                                                        <td class="ps-4">
                                                                            <i class="fas fa-vial text-primary me-2"></i>
                                                                            تحليل
                                                                        </td>
                                                                        <td>
                                                                            @if($labTest->labTest)
                                                                                {{ $labTest->labTest->name }} ({{ $labTest->labTest->code ?? '-' }})
                                                                            @else
                                                                                <em>غير محدد</em> (ID #{{ $labTest->lab_test_id }})
                                                                            @endif
                                                                        </td>
                                                                        <td class="text-end">
                                                                            {{ number_format(optional($labTest->labTest)->price ?? 0, 0) }}
                                                                        </td>
                                                                        <td class="text-center">
                                                                            @if($isPaid)
                                                                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>مدفوع</span>
                                                                            @else
                                                                                <span class="badge bg-warning"><i class="fas fa-clock me-1"></i>معلق</span>
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            @endif

                                                            <!-- الأشعة المطلوبة -->
                                                            @if($surgery->radiologyTests->count() > 0)
                                                                <tr class="table-warning">
                                                                    <td colspan="4">
                                                                        <strong>
                                                                            <i class="fas fa-x-ray me-2"></i>
                                                                            الفحوصات الإشعاعية ({{ $surgery->radiologyTests->count() }})
                                                                            @if($paidRadiologyTests->count() > 0)
                                                                                - <span class="text-success">{{ $paidRadiologyTests->count() }} مدفوع</span>
                                                                            @endif
                                                                            @if($pendingRadiologyTests->count() > 0)
                                                                                - <span class="text-warning">{{ $pendingRadiologyTests->count() }} معلق</span>
                                                                            @endif
                                                                        </strong>
                                                                    </td>
                                                                </tr>
                                                                @foreach($surgery->radiologyTests as $radiologyTest)
                                                                    @php $isPaid = $radiologyTest->payment_status === 'paid'; @endphp
                                                                    <tr class="{{ $isPaid ? 'table-success' : '' }}">
                                                                        <td class="ps-4">
                                                                            <i class="fas fa-radiation text-info me-2"></i>
                                                                            أشعة
                                                                        </td>
                                                                        <td>{{ $radiologyTest->radiologyType->name ?? 'غير محدد' }}</td>
                                                                        <td class="text-end">{{ number_format($radiologyTest->radiologyType->base_price ?? 0, 0) }}</td>
                                                                        <td class="text-center">
                                                                            @if($isPaid)
                                                                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>مدفوع</span>
                                                                            @else
                                                                                <span class="badge bg-warning"><i class="fas fa-clock me-1"></i>معلق</span>
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            @endif
                                                        </tbody>
                                                        <tfoot>
                                                            @if($paidAmount > 0)
                                                            <tr class="table-success">
                                                                <td colspan="2" class="text-end">
                                                                    <strong><i class="fas fa-check-circle me-1"></i>المبلغ المدفوع:</strong>
                                                                </td>
                                                                <td class="text-end">
                                                                    <strong class="text-success">{{ number_format($paidAmount, 0) }} IQD</strong>
                                                                </td>
                                                                <td></td>
                                                            </tr>
                                                            @endif
                                                            @if($pendingAmount > 0)
                                                            <tr class="table-warning">
                                                                <td colspan="2" class="text-end">
                                                                    <strong><i class="fas fa-clock me-1"></i>المبلغ المعلق:</strong>
                                                                </td>
                                                                <td class="text-end">
                                                                    <strong class="text-warning">{{ number_format($pendingAmount, 0) }} IQD</strong>
                                                                </td>
                                                                <td></td>
                                                            </tr>
                                                            @endif
                                                            <tr class="table-secondary">
                                                                <td colspan="2" class="text-end">
                                                                    <strong>الإجمالي الكلي:</strong>
                                                                </td>
                                                                <td class="text-end">
                                                                    <strong>{{ number_format($totalAmount, 0) }} IQD</strong>
                                                                </td>
                                                                <td></td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    <!-- إجمالي المريض -->
                                    @if($surgeries->count() > 1 || $patientPaidAmount > 0)
                                        <div class="row mt-3">
                                            @if($patientPaidAmount > 0)
                                            <div class="col-md-4">
                                                <div class="alert alert-success mb-0 py-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <strong>
                                                            <i class="fas fa-check-circle me-2"></i>
                                                            المدفوع:
                                                        </strong>
                                                        <h5 class="mb-0">{{ number_format($patientPaidAmount, 0) }} IQD</h5>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                            @if($patientPendingAmount > 0)
                                            <div class="col-md-4">
                                                <div class="alert alert-warning mb-0 py-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <strong>
                                                            <i class="fas fa-clock me-2"></i>
                                                            المعلق:
                                                        </strong>
                                                        <h5 class="mb-0">{{ number_format($patientPendingAmount, 0) }} IQD</h5>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                            <div class="col-md-4">
                                                <div class="alert alert-secondary mb-0 py-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <strong>
                                                            <i class="fas fa-calculator me-2"></i>
                                                            الإجمالي:
                                                        </strong>
                                                        <h5 class="mb-0">{{ number_format($patientTotalAmount, 0) }} IQD</h5>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">لا توجد عمليات معلقة حالياً</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
