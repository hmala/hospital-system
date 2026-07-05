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
                                $additionalOpsFee = $surgery->additionalOperations->sum('fee');
                                $surgeryFee = ($surgery->surgery_fee ?? 0) + $additionalOpsFee;
                                $surgeryFeePaidAmount = $surgery->surgery_fee_paid_amount ?? 0;
                                $remainingSurgeryFee = max(0, $surgeryFee - $surgeryFeePaidAmount);
                                
                                // رسوم الغرفة
                                $roomFee = $surgery->room_fee ?? 0;
                                $roomFeePaidAmount = $surgery->room_fee_paid_amount ?? 0;
                                $remainingRoomFee = max(0, $roomFee - $roomFeePaidAmount);
                                
                                // تحاليل
                                $pendingLabFee = $surgery->labTests->where('payment_status', '!=', 'paid')->sum(function($test) {
                                    return $test->labTest ? ($test->labTest->price ?? 0) : 0;
                                });
                                $paidLabFee = $surgery->labTests->where('payment_status', 'paid')->sum(function($test) {
                                    return $test->labTest ? ($test->labTest->price ?? 0) : 0;
                                });
                                
                                // أشعة
                                $pendingRadiologyFee = $surgery->radiologyTests->where('payment_status', '!=', 'paid')->sum(function($test) {
                                    return $test->radiologyType ? ($test->radiologyType->base_price ?? 0) : 0;
                                });
                                $paidRadiologyFee = $surgery->radiologyTests->where('payment_status', 'paid')->sum(function($test) {
                                    return $test->radiologyType ? ($test->radiologyType->base_price ?? 0) : 0;
                                });
                                
                                $patientPendingAmount += $remainingSurgeryFee + $remainingRoomFee + $pendingLabFee + $pendingRadiologyFee;
                                $patientPaidAmount += $surgeryFeePaidAmount + $roomFeePaidAmount + $paidLabFee + $paidRadiologyFee;
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
                                                <strong>{{ $patient && $patient->user ? $patient->user->name : 'غير محدد' }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-id-card me-1"></i>
                                                    {{ $patient ? ($patient->national_id ?? 'غير محدد') : 'غير محدد' }}
                                                    @if($patient && $patient->user && $patient->user->phone)
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
                                <div class="accordion-body bg-white p-3">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0 text-right" style="direction: rtl;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>رقم العملية</th>
                                                    <th>نوع العملية</th>
                                                    <th>الجراح</th>
                                                    <th>القسم</th>
                                                    <th>التكلفة الإجمالية</th>
                                                    <th>المدفوع</th>
                                                    <th>المتبقي</th>
                                                    <th class="text-center">الإجراءات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($surgeries as $surgery)
                                                    @php
                                                        $additionalOpsFee = $surgery->additionalOperations->sum('fee');
                                                        $surgeryFee = ($surgery->surgery_fee ?? 0) + $additionalOpsFee;
                                                        $surgeryFeePaidAmount = $surgery->surgery_fee_paid_amount ?? 0;
                                                        $remainingSurgeryFee = max(0, $surgeryFee - $surgeryFeePaidAmount);

                                                        $roomFee = $surgery->room_fee ?? 0;
                                                        $roomFeePaidAmount = $surgery->room_fee_paid_amount ?? 0;
                                                        $remainingRoomFee = max(0, $roomFee - $roomFeePaidAmount);

                                                        $pendingLabFee = $surgery->labTests->where('payment_status', '!=', 'paid')->sum(function($test) {
                                                            return $test->labTest ? ($test->labTest->price ?? 0) : 0;
                                                        });
                                                        $paidLabFee = $surgery->labTests->where('payment_status', 'paid')->sum(function($test) {
                                                            return $test->labTest ? ($test->labTest->price ?? 0) : 0;
                                                        });

                                                        $pendingRadiologyFee = $surgery->radiologyTests->where('payment_status', '!=', 'paid')->sum(function($test) {
                                                            return $test->radiologyType ? ($test->radiologyType->base_price ?? 0) : 0;
                                                        });
                                                        $paidRadiologyFee = $surgery->radiologyTests->where('payment_status', 'paid')->sum(function($test) {
                                                            return $test->radiologyType ? ($test->radiologyType->base_price ?? 0) : 0;
                                                        });

                                                        $pendingAmount = $remainingSurgeryFee + $remainingRoomFee + $pendingLabFee + $pendingRadiologyFee;
                                                        $paidAmount = $surgeryFeePaidAmount + $roomFeePaidAmount + $paidLabFee + $paidRadiologyFee;
                                                        $totalAmount = $surgeryFee + $roomFee + $pendingLabFee + $paidLabFee + $pendingRadiologyFee + $paidRadiologyFee;
                                                        $excessAmount = $surgeryFeePaidAmount > $surgeryFee ? ($surgeryFeePaidAmount - $surgeryFee) : 0;
                                                    @endphp
                                                    <tr>
                                                        <td><span class="badge bg-secondary">#{{ $surgery->id }}</span></td>
                                                        <td>
                                                            <div class="fw-bold">{{ $surgery->surgery_type }}</div>
                                                            @if($surgery->payment_status === 'partial')
                                                                <small class="text-warning"><i class="fas fa-adjust"></i> دفع جزئي</small>
                                                            @endif
                                                        </td>
                                                        <td>{{ $surgery->doctor->user->name ?? $surgery->surgeon_name ?? 'غير محدد' }}</td>
                                                        <td>{{ $surgery->department->name ?? 'غير محدد' }}</td>
                                                        <td>{{ number_format($totalAmount, 0) }} IQD</td>
                                                        <td class="text-success">{{ number_format($paidAmount, 0) }} IQD</td>
                                                        <td class="text-danger fw-bold">{{ number_format($pendingAmount, 0) }} IQD</td>
                                                        <td class="text-center">
                                                            @if($pendingAmount > 0)
                                                                <a href="{{ route('cashier.surgeries.payment.form', $surgery->id) }}" class="btn btn-success btn-sm px-3">
                                                                    <i class="fas fa-money-bill-wave me-1"></i> تسديد الرسوم
                                                                </a>
                                                            @elseif($excessAmount > 0)
                                                                <a href="{{ route('cashier.surgeries.payment.form', $surgery->id) }}" class="btn btn-warning btn-sm px-3 text-dark fw-bold">
                                                                    <i class="fas fa-undo me-1"></i> إرجاع الفارق ({{ number_format($excessAmount, 0) }} د.ع)
                                                                </a>
                                                            @else
                                                                <span class="text-success"><i class="fas fa-check-double me-1"></i> مدفوع بالكامل</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
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
