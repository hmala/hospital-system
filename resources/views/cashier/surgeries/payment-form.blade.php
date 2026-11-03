@extends('layouts.app')

@section('content')
@php
    $isCancelled = $surgery->status === 'cancelled';
    $netPatientPaidCash = (float) $surgery->payments()->sum('amount');

    // حساب التكاليف مع تتبع حالة الدفع
    $surgeryFee = $isCancelled ? 0 : ($surgery->surgery_fee ?? 0);
    $additionalOpsFee = $isCancelled ? 0 : $surgery->additionalOperations->sum('fee');
    $devicesFee = $isCancelled ? 0 : $surgery->medicalDevices->sum('pivot.price');
    $totalSurgeryFee = $isCancelled ? 0 : ($surgeryFee + $additionalOpsFee + $devicesFee);
    $surgeryFeePaidAmount = $surgery->surgery_fee_paid_amount ?? 0;
    $remainingSurgeryFee = $isCancelled ? 0 : max(0, $totalSurgeryFee - $surgeryFeePaidAmount);
    $excessSurgeryFee = $isCancelled ? ($netPatientPaidCash > 0 ? $netPatientPaidCash : $surgeryFeePaidAmount) : ($surgeryFeePaidAmount > $totalSurgeryFee ? ($surgeryFeePaidAmount - $totalSurgeryFee) : 0);
    $surgeryFeePaid = $isCancelled ? ($surgeryFeePaidAmount <= 0) : ($surgery->surgery_fee_paid === 'paid' || ($remainingSurgeryFee <= 0 && $excessSurgeryFee <= 0));
    
    // رسوم الغرفة الفندقية (الليلة الأولى + الليالي الإضافية بعد 12 ظهراً)
    $stayDetails = $surgery->calculateStayDetails();
    $roomFee = $isCancelled ? 0 : ($stayDetails['total_fee'] ?? ($surgery->room_fee ?? 0));
    $roomFeePaidAmount = (float)($surgery->room_fee_paid_amount ?? 0);
    $remainingRoomFee = $isCancelled ? 0 : max(0, $roomFee - $roomFeePaidAmount);
    $excessRoomFee = $isCancelled ? 0 : max(0, $roomFeePaidAmount - $roomFee);
    $roomFeePaid = $isCancelled ? ($roomFeePaidAmount <= 0) : ($remainingRoomFee <= 0 && $excessRoomFee <= 0);
    $totalExcess = $excessSurgeryFee + $excessRoomFee;
    
    // تحاليل معلقة ومدفوعة (مشمولة ضمن أتعاب العملية كالغرفة لأول يوم)
    $pendingLabTests = $isCancelled ? collect() : $surgery->labTests->where('payment_status', '!=', 'paid');
    $paidLabTests = $surgery->labTests->filter(function($test) use ($isCancelled) {
        return $test->payment_status === 'paid' || !empty($test->payment_id) || ($isCancelled && $test->labTest);
    });
    $pendingLabFee = 0; // مشمولة ضمن أتعاب العملية (0 د.ع إضافي)
    $paidLabFee = 0;
    
    // أشعة معلقة ومدفوعة
    $pendingRadiologyTests = $isCancelled ? collect() : $surgery->radiologyTests->where('payment_status', '!=', 'paid');
    $paidRadiologyTests = $surgery->radiologyTests->filter(function($test) use ($isCancelled) {
        return $test->payment_status === 'paid' || !empty($test->payment_id) || ($isCancelled && $test->radiologyType);
    });
    $pendingRadiologyFee = $isCancelled ? 0 : $pendingRadiologyTests->sum(function($test) {
        return $test->radiologyType->base_price ?? 0;
    });
    $paidRadiologyFee = $paidRadiologyTests->sum(function($test) {
        return $test->radiologyType->base_price ?? 0;
    });
    
    // خدمات إضافية وفندقية معلقة ومدفوعة
    $pendingExtraServices = $isCancelled ? collect() : $surgery->extraServices->where('payment_status', '!=', 'paid');
    $paidExtraServices = $surgery->extraServices->where('payment_status', 'paid');
    $pendingExtraFee = $isCancelled ? 0 : $pendingExtraServices->sum('total_amount');
    $paidExtraFee = $paidExtraServices->sum('total_amount');

    // المبالغ (تشمل رسوم الغرفة وتعتمد على المبالغ المدفوعة جزئياً والخدمات الإضافية)
    $pendingAmount = $isCancelled ? 0 : ($remainingSurgeryFee + $remainingRoomFee + $pendingLabFee + $pendingRadiologyFee + $pendingExtraFee);
    $paidAmount = $surgeryFeePaidAmount + $roomFeePaidAmount + $paidLabFee + $paidRadiologyFee + $paidExtraFee;
    $totalAmount = $isCancelled ? 0 : ($totalSurgeryFee + $roomFee + $pendingLabFee + $paidLabFee + $pendingRadiologyFee + $paidRadiologyFee + $pendingExtraFee + $paidExtraFee);

    $patient = $surgery->patient;
    $defaultInsurance = $surgery->insurance_type ?? $patient->insurance_type ?? 'none';
    $defaultCardNo = $patient->insurance_card_no ?? $patient->insurance_booklet_number ?? '';
    $hiCategory = $patient ? $patient->healthInsuranceCategory : null;
    if ($defaultInsurance === 'hi' && $patient) {
        $defaultCopay = $patient->getCopayPercentageFor('surgery');
    } else {
        $defaultCopay = (float)($patient->copay_percentage ?? 15.0);
    }
@endphp

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-11">
            <div class="card border-0 shadow-sm">
                <div class="card-header text-white" style="background: linear-gradient(135deg, {{ $isCancelled ? '#dc2626 0%, #b91c1c 100%' : '#0d6efd 0%, #0a58ca 100%' }});">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            @if($isCancelled)
                                <i class="fas fa-undo me-2"></i>
                                استرجاع رسوم العملية الجراحية (Refund)
                                <span class="badge bg-white text-danger ms-2">عملية ملغاة</span>
                            @else
                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                تسوية ودفع رسوم العملية الجراحية
                                @if($surgery->payment_status === 'partial')
                                    <span class="badge bg-warning text-dark ms-2">دفع جزئي سابق</span>
                                @endif
                            @endif
                        </h5>
                        <a href="{{ route('cashier.surgeries.index') }}" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-arrow-right me-1"></i>العودة لسجل العمليات
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="alert-heading mb-1"><i class="fas fa-times-circle me-1"></i> تعذر الدفع بسبب أخطاء في المدخلات:</h6>
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- بطاقة معلومات العملية والمريض الموحدة السريرية -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100 shadow-2xs">
                                <h6 class="text-primary fw-bold mb-3 d-flex align-items-center gap-2">
                                    <i class="fas fa-procedures"></i>
                                    تفاصيل العملية الجراحية
                                </h6>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">رقم العملية:</span>
                                    <span class="badge bg-dark font-monospace fs-7">#{{ $surgery->id }}</span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">نوع العملية:</span>
                                    <strong class="text-dark">{{ $surgery->surgery_type }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">الموقع السريري:</span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold">
                                        <i class="fas fa-bed me-1"></i>غرفة {{ $surgery->room?->room_number ?? 'غير محدد' }} ({{ $surgery->room?->floor ?? '—' }})
                                    </span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">الجراح المعالج:</span>
                                    <strong>د. {{ $surgery->doctor?->user?->name ?? 'غير محدد' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">الموعد المحدد:</span>
                                    <span class="font-monospace">{{ $surgery->scheduled_date->format('Y-m-d') }} ({{ $surgery->scheduled_time->format('H:i') }})</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100 shadow-2xs">
                                <h6 class="text-success fw-bold mb-3 d-flex align-items-center justify-content-between">
                                    <span><i class="fas fa-user-circle me-2"></i>معلومات المريض والتغطية المالية</span>
                                    <span class="badge {{ $defaultInsurance !== 'none' ? 'bg-success' : 'bg-primary' }}">
                                        {{ $defaultInsurance === 'hi' ? '🛡️ الضمان الصحي الوطني' : ($defaultInsurance === 'moi' ? '🛡️ ضمان الداخلية' : '💵 نقدي (كاش)') }}
                                    </span>
                                </h6>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">اسم المريض:</span>
                                    <strong class="text-dark fs-6">{{ $surgery->patient?->user?->name ?? 'غير محدد' }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">رقم الهوية / الملف:</span>
                                    <span class="font-monospace text-dark fw-bold">{{ $surgery->patient?->national_id ?? 'غير محدد' }}</span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">رقم الهاتف:</span>
                                    <span class="font-monospace" dir="ltr">{{ $surgery->patient?->user?->phone ?? 'غير محدد' }}</span>
                                </div>
                                @if($defaultInsurance !== 'none')
                                    <div class="mb-2 d-flex justify-content-between align-items-center">
                                        <span class="text-muted">رقم بطاقة الضمان:</span>
                                        <span class="font-monospace text-primary fw-bold">{{ $defaultCardNo ?: 'مسجل' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">فئة المريض والتحمل:</span>
                                        <span class="badge bg-info text-dark">
                                            {{ $hiCategory?->name ?? 'الفئة المعرفة' }} (استقطاع العمليات: {{ (float)$defaultCopay }}%)
                                        </span>
                                    </div>
                                @else
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">التصنيف المالي:</span>
                                        <span class="badge bg-secondary-subtle text-secondary border">دفع نقدي كامل (خارج التأمين)</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- ملخص حالة الدفع -->
                    @if($paidAmount > 0)
                    <div class="alert alert-info mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center border-end">
                                <small class="text-muted d-block">المدفوع سابقاً</small>
                                <h4 class="text-success mb-0">{{ number_format($paidAmount, 0) }} IQD</h4>
                            </div>
                            <div class="col-md-4 text-center border-end">
                                <small class="text-muted d-block">المتبقي للدفع</small>
                                <h4 class="text-warning mb-0">{{ number_format($pendingAmount, 0) }} IQD</h4>
                            </div>
                            <div class="col-md-4 text-center">
                                <small class="text-muted d-block">الإجمالي الكلي</small>
                                <h4 class="text-primary mb-0">{{ number_format($totalAmount, 0) }} IQD</h4>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- العناصر المدفوعة سابقاً -->
                    @if($paidAmount > 0)
                    <div class="mb-4">
                        <h6 class="text-success mb-3">
                            <i class="fas fa-check-circle me-2"></i>
                            العناصر المدفوعة سابقاً
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-success">
                                    <tr>
                                        <th>البند</th>
                                        <th>التفاصيل</th>
                                        <th class="text-end">التكلفة (IQD)</th>
                                        <th class="text-center">الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($surgeryFeePaidAmount > 0)
                                    <tr class="table-success">
                                        <td>
                                            <i class="fas fa-procedures text-success me-2"></i>
                                            رسوم العملية الجراحية
                                        </td>
                                        <td>
                                            {{ $surgery->surgery_type }}
                                            @if($remainingSurgeryFee > 0)
                                                <small class="text-muted d-block">(تم دفع جزء من الرسوم)</small>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($surgeryFeePaidAmount, 0) }}
                                            @if($remainingSurgeryFee > 0)
                                                <br><small class="text-muted">من إجمالي {{ number_format($surgeryFee, 0) }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($remainingSurgeryFee > 0)
                                                <span class="badge bg-warning text-dark"><i class="fas fa-adjust me-1"></i>مدفوع جزئياً</span>
                                            @else
                                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>مدفوع</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                    
                                    @if($roomFeePaidAmount > 0)
                                    <tr class="table-success">
                                        <td>
                                            <i class="fas fa-door-open text-success me-2"></i>
                                            أجور الغرفة
                                        </td>
                                        <td>
                                            الغرفة {{ $surgery->room->room_number ?? 'غير محدد' }}
                                            @if($remainingRoomFee > 0)
                                                <small class="text-muted d-block">(تم دفع جزء من الرسوم)</small>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($roomFeePaidAmount, 0) }}
                                            @if($remainingRoomFee > 0)
                                                <br><small class="text-muted">من إجمالي {{ number_format($roomFee, 0) }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($remainingRoomFee > 0)
                                                <span class="badge bg-warning text-dark"><i class="fas fa-adjust me-1"></i>مدفوع جزئياً</span>
                                            @else
                                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>مدفوع</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                    
                                    @foreach($paidLabTests as $labTest)
                                    <tr class="table-success">
                                        <td>
                                            <i class="fas fa-vial text-success me-2"></i>
                                            تحليل
                                        </td>
                                        <td>
                                            @if($labTest->labTest)
                                                {{ $labTest->labTest->name }} ({{ $labTest->labTest->code ?? '-' }})
                                            @else
                                                <em>غير محدد</em> (ID #{{ $labTest->lab_test_id }})
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format(optional($labTest->labTest)->price ?? 0, 0) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>مدفوع</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                    
                                    @foreach($paidRadiologyTests as $radiologyTest)
                                    <tr class="table-success">
                                        <td>
                                            <i class="fas fa-radiation text-success me-2"></i>
                                            أشعة
                                        </td>
                                        <td>{{ $radiologyTest->radiologyType->name ?? 'غير محدد' }}</td>
                                        <td class="text-end">{{ number_format($radiologyTest->radiologyType->base_price ?? 0, 0) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>مدفوع</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-success">
                                    <tr>
                                        <td colspan="2" class="text-end"><strong>إجمالي المدفوع:</strong></td>
                                        <td class="text-end"><strong>{{ number_format($paidAmount, 0) }} IQD</strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if($totalExcess > 0)
                    <!-- نموذج إرجاع المبلغ (Refund) -->
                    <div class="alert {{ $isCancelled ? 'alert-danger border-danger' : 'alert-info border-info' }} shadow-sm mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas {{ $isCancelled ? 'fa-ban text-danger' : 'fa-info-circle text-info' }} fa-2x me-3"></i>
                            <div>
                                <h5 class="alert-heading fw-bold mb-1">
                                    {{ $isCancelled ? 'العملية ملغاة - مستحق استرجاع مالي للمريض' : 'مسترجع مالي معلق للمريض' }}
                                </h5>
                                <p class="mb-0">
                                    @if($isCancelled)
                                        تم إلغاء حجز العملية. المبلغ النقدي الفعلي المسدد من قبل المريض والمستحق إرجاعه هو: 
                                        <strong class="text-danger fs-5">{{ number_format($totalExcess, 0) }} د.ع</strong>
                                        @if($surgery->insurance_type && $surgery->insurance_type !== 'none')
                                            <span class="badge bg-secondary ms-2">تم إلغاء مطالبة التأمين تلقائياً</span>
                                        @endif
                                    @else
                                        يوجد مبلغ مدفوع فائض للمريض يستوجب الاسترجاع بمقدار: <strong>{{ number_format($totalExcess, 0) }} د.ع</strong>
                                        @if($excessRoomFee > 0 && $excessSurgeryFee > 0)
                                            (فارق تخفيض الغرفة: {{ number_format($excessRoomFee, 0) }} د.ع + فارق العملية: {{ number_format($excessSurgeryFee, 0) }} د.ع)
                                        @elseif($excessRoomFee > 0)
                                            (بسبب تخفيض أجور الغرفة من {{ number_format($roomFeePaidAmount, 0) }} إلى {{ number_format($roomFee, 0) }} د.ع)
                                        @endif
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card {{ $isCancelled ? 'border-danger' : 'border-info' }} mb-4 shadow-sm">
                        <div class="card-header {{ $isCancelled ? 'bg-danger' : 'bg-info' }} text-white fw-bold d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-undo me-2"></i> معالجة إرجاع المبلغ (Refund)</span>
                            <span class="badge bg-white {{ $isCancelled ? 'text-danger' : 'text-info' }} fs-6">{{ number_format($totalExcess, 0) }} د.ع</span>
                        </div>
                        <div class="card-body bg-white">
                            <form action="{{ route('cashier.surgeries.payment.refund', $surgery->id) }}" method="POST">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="p-3 border rounded bg-light">
                                            <label class="form-label fw-bold">طريقة إرجاع المبلغ <span class="text-danger">*</span></label>
                                            <div class="d-flex gap-4 mt-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" id="refund_cash" value="cash" checked required>
                                                    <label class="form-check-label fw-bold" for="refund_cash">💵 نقداً (Cash)</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" id="refund_card" value="card">
                                                    <label class="form-check-label fw-bold" for="refund_card">💳 بطاقة (Card)</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 border rounded bg-light">
                                            <label for="refund_notes" class="form-label fw-bold">ملاحظات الاسترجاع</label>
                                            <textarea class="form-control" id="refund_notes" name="notes" rows="2" placeholder="ملاحظات حول سبب الاسترجاع..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="submit" class="btn {{ $isCancelled ? 'btn-danger' : 'btn-info text-white' }} btn-lg fw-bold px-5 shadow">
                                        <i class="fas fa-check-circle me-2"></i> تأكيد استرجاع {{ number_format($totalExcess, 0) }} د.ع للمريض
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    @if(!$isCancelled)
                    <!-- صندوق إضافة الخدمات الإضافية والفندقية (مرافق، تزيين، تخطيط، طبلة، غرامات...) -->
                    <div class="card border border-warning border-opacity-50 rounded-3 mb-4 shadow-2xs" style="background: linear-gradient(to bottom, #fffdf8, #fff);">
                        <div class="card-header bg-warning bg-opacity-10 border-bottom border-warning border-opacity-25 py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <i class="fas fa-concierge-bell text-warning fs-5"></i>
                                إضافة خدمات إضافية وفندقية للعملية الجراحية (تخطيط قلب، مرافق، تزيين، طبلة، غرامات...)
                            </h6>
                            <span class="badge bg-warning text-dark border border-warning small">
                                <i class="fas fa-edit me-1"></i>الكاشير يحدد ويعدل السعر مباشرة
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <form action="{{ route('cashier.surgeries.extra-services.add', $surgery) }}" method="POST" id="addExtraServiceForm">
                                @csrf
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-1 fw-bold">اسم الخدمة الإضافية *</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light text-warning"><i class="fas fa-tags"></i></span>
                                            <input type="text" class="form-control form-control-sm fw-bold" id="service_name_input" name="service_name" placeholder="اختر من المقترحات أو اكتب خدمة مخصصة..." list="extra_services_catalog_list" required autocomplete="off">
                                            <datalist id="extra_services_catalog_list">
                                                @foreach($availableExtraServices as $asrv)
                                                    <option value="{{ $asrv->name }}" data-price="{{ (float)$asrv->default_price }}" data-id="{{ $asrv->id }}">
                                                        {{ $asrv->name }} (افتراضي: {{ number_format($asrv->default_price, 0) }} د.ع)
                                                    </option>
                                                @endforeach
                                            </datalist>
                                            <input type="hidden" name="service_id" id="service_id_hidden">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1 fw-bold">السعر المعتمد للوحدة (IQD) *</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light text-success fw-bold">IQD</span>
                                            <input type="number" class="form-control form-control-sm fw-bold text-success" id="service_price_input" name="service_price" min="0" step="1000" placeholder="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted mb-1 fw-bold">الكمية</label>
                                        <input type="number" class="form-control form-control-sm text-center fw-bold" id="service_qty_input" name="service_quantity" min="1" value="1">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted mb-1 fw-bold">ملاحظات (اختياري)</label>
                                        <input type="text" class="form-control form-control-sm" name="service_notes" placeholder="ملاحظة أو تفصيل...">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold text-dark shadow-xs">
                                            <i class="fas fa-plus me-1"></i> إضافة
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    @if(!$isCancelled && $pendingAmount > 0)
                    <!-- نموذج الدفع للعناصر المعلقة -->
                    <form action="{{ route('cashier.surgeries.payment.process', $surgery->id) }}" method="POST" id="paymentForm">
                        @csrf

                        @if($defaultInsurance !== 'none')
                            <!-- قسم الضمان الصحي ونسبة التحمل التلقائية المحمية للعمليات -->
                            <div class="p-3 mb-4 rounded-3 border" style="background-color: #f8fafc;">
                                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                    <h6 class="fw-bold text-primary mb-0 d-flex align-items-center gap-2">
                                        <i class="fas fa-shield-alt"></i>
                                        تغطية الضمان ونسبة التحمل (Co-payment) للعملية الجراحية
                                    </h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success" id="copayBadge">
                                            {{ $defaultInsurance === 'hi' ? 'مشمول بالضمان الصحي الوطني' : 'مشمول بضمان قوى الأمن' }}
                                        </span>
                                        <span class="badge bg-secondary-subtle text-secondary border small py-1 px-2 rounded-pill">
                                            <i class="fas fa-lock me-1"></i>مثبتة آلياً ومحمية
                                        </span>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <!-- جهة الضمان -->
                                    <div class="col-md-6" id="insurance_type_wrapper">
                                        <label class="form-label fw-bold small text-muted">جهة الضمان / التأمين</label>
                                        <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between">
                                            <span class="fw-bold text-dark">
                                                <i class="fas fa-check-circle text-success me-1"></i>
                                                {{ $defaultInsurance === 'hi' ? 'هيئة الضمان الصحي الوطني (HI)' : 'ضمان قوى الأمن الداخلي (وزارة الداخلية)' }}
                                            </span>
                                            <input type="hidden" name="insurance_type" id="insurance_type" value="{{ $defaultInsurance }}">
                                        </div>
                                    </div>

                                    <!-- رقم بطاقة الضمان -->
                                    <div class="col-md-6" id="insurance_card_col">
                                        <label class="form-label fw-bold small text-muted">رقم بطاقة / دفتر الضمان</label>
                                        <input type="text" class="form-control" id="insurance_card_no" name="insurance_card_no" 
                                               value="{{ old('insurance_card_no', $defaultCardNo) }}" placeholder="أدخل رقم الهوية أو الدفتر" readonly>
                                    </div>

                                    @if($hiCategory)
                                    <div class="col-12" id="patient_hi_category_badge">
                                        <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div>
                                                <span class="badge bg-info text-dark me-1"><i class="fas fa-layer-group me-1"></i>{{ $hiCategory->name }} (الفئة {{ $hiCategory->code }})</span>
                                                <small class="text-muted">نسبة استقطاع العمليات الجراحية: <strong class="text-primary">{{ (float)$hiCategory->surgery_copay }}%</strong></small>
                                            </div>
                                            @if($hiCategory->requires_thermal_stamp)
                                                <span class="badge bg-danger text-white"><i class="fas fa-stamp me-1"></i>شرط الختم الحراري (0%)</span>
                                            @endif
                                        </div>
                                    </div>
                                    @endif

                                    <!-- نسبة التحمل -->
                                    <div class="col-12" id="copay_section">
                                        <div class="p-2 rounded-3 bg-white border d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-percentage text-primary"></i>
                                                <span class="small text-muted">نسبة التحمل المقررة رسمياً على المريض: <strong class="text-primary fs-6" id="display_copay_badge">{{ (float)$defaultCopay }}%</strong></span>
                                            </div>
                                            <span class="text-muted small">
                                                <i class="fas fa-shield-alt text-success me-1"></i>
                                                مقفلة ومحددة آلياً وفق فئة المريض الرسمية
                                            </span>
                                        </div>
                                        <input type="hidden" id="copay_percentage" name="copay_percentage" value="{{ (float)$defaultCopay }}">
                                    </div>
                                </div>

                                <!-- بطاقة معاينة الحسبة المالية المباشرة للضمان -->
                                <div class="row g-2 mt-3 pt-3 border-top text-center" id="live_pricing_summary">
                                    <div class="col-4">
                                        <div class="bg-white p-2 rounded border">
                                            <small class="text-muted d-block">إجمالي المبلغ المحدد</small>
                                            <strong class="text-dark fs-6" id="display_approved_price">{{ number_format($pendingAmount, 0) }} د.ع</strong>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-white p-2 rounded border border-success">
                                            <small class="text-success d-block fw-bold">حصة المريض (المستلم نقداً)</small>
                                            <strong class="text-success fs-6" id="display_patient_share">{{ number_format($pendingAmount, 0) }} د.ع</strong>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-white p-2 rounded border border-info">
                                            <small class="text-info d-block fw-bold">حصة الضمان (مطالبة)</small>
                                            <strong class="text-info fs-6" id="display_insurance_share">0 د.ع</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- قسم الدفع النقدي والخصم الخاص (عمليات خارج الضمان 100%) -->
                            <input type="hidden" name="insurance_type" id="insurance_type" value="none">
                            <input type="hidden" id="copay_percentage" name="copay_percentage" value="100">

                            <div class="p-3 mb-4 rounded-3 border" style="background-color: #f8fafc;">
                                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                        <i class="fas fa-wallet text-success"></i>
                                        حساب الدفع النقدي والخصم الخاص (عمليات جراحية)
                                    </h6>
                                    <span class="badge bg-secondary-subtle text-secondary border px-3 py-1 rounded-pill">
                                        دفع نقدي كامل (خارج الضمان)
                                    </span>
                                </div>

                                <!-- صندوق الخصم المالي بكتابة ومقترحات -->
                                <div class="p-3 rounded-3 bg-white border border-danger border-opacity-25 shadow-2xs mb-3" id="discount_section">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-tags text-danger"></i>
                                            <label class="form-label fw-bold small text-dark mb-0">
                                                خصم خاص / إداري للمريض:
                                            </label>
                                            <span class="badge bg-danger fs-6 px-3 py-1" id="display_discount_badge">
                                                0%
                                            </span>
                                        </div>
                                        <span class="text-muted small">
                                            <i class="fas fa-keyboard text-secondary me-1"></i>
                                            اكتب النسبة مباشرة أو اختر من المقترحات
                                        </span>
                                    </div>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1 fw-bold">نسبة الخصم %</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light text-danger fw-bold">%</span>
                                                <input type="number" 
                                                       class="form-control form-control-sm fw-bold text-danger" 
                                                       id="discount_percentage" 
                                                       name="discount_percentage" 
                                                       min="0" 
                                                       max="100" 
                                                       step="5" 
                                                       value="0" 
                                                       placeholder="0" 
                                                       list="surgery_discount_percentage_suggestions">
                                                <datalist id="surgery_discount_percentage_suggestions">
                                                    <option value="0">0% (بدون خصم)</option>
                                                    <option value="5">5%</option>
                                                    <option value="10">10%</option>
                                                    <option value="15">15%</option>
                                                    <option value="20">20%</option>
                                                    <option value="25">25%</option>
                                                    <option value="30">30%</option>
                                                    <option value="35">35%</option>
                                                    <option value="40">40%</option>
                                                    <option value="45">45%</option>
                                                    <option value="50">50%</option>
                                                    <option value="55">55%</option>
                                                    <option value="60">60%</option>
                                                    <option value="70">70%</option>
                                                    <option value="75">75%</option>
                                                    <option value="80">80%</option>
                                                    <option value="90">90%</option>
                                                    <option value="100">100% (إعفاء كامل)</option>
                                                </datalist>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1 fw-bold">سبب الخصم أو التوجيه</label>
                                            <input type="text" class="form-control form-control-sm" id="discount_reason" name="discount_reason" 
                                                   placeholder="سبب الخصم أو الجهة المخولة (إدارة / طبيب / إنساني)..." 
                                                   list="surgery_discount_reason_suggestions">
                                            <datalist id="surgery_discount_reason_suggestions">
                                                <option value="خصم إدارة"></option>
                                                <option value="إعفاء بتوجيه الطبيب"></option>
                                                <option value="تخفيض إنساني"></option>
                                                <option value="حالة عملية خاصة"></option>
                                                <option value="خصم موظف / ذوي موظف"></option>
                                                <option value="موافقة المدير التنفيذي"></option>
                                            </datalist>
                                        </div>
                                        <div class="col-md-4 text-md-end pt-md-3">
                                            <span class="text-danger fw-bold small" id="display_discount_amount">
                                                قيمة الخصم: 0 د.ع
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- بطاقة معاينة الحسبة المالية المباشرة للنقدي -->
                                <div class="row g-2 pt-2 border-top text-center" id="live_pricing_summary">
                                    <div class="col-6">
                                        <div class="bg-white p-2 rounded border">
                                            <small class="text-muted d-block">إجمالي المبلغ المحدد</small>
                                            <strong class="text-dark fs-6" id="display_approved_price">{{ number_format($pendingAmount, 0) }} د.ع</strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-white p-2 rounded border border-success">
                                            <small class="text-success d-block fw-bold">الصافي المطلوب قبضه نقداً</small>
                                            <strong class="text-success fs-6" id="display_patient_share">{{ number_format($pendingAmount, 0) }} د.ع</strong>
                                            <div id="summary_discount_note" class="text-danger small mt-1 fw-bold d-none">
                                                <i class="fas fa-tag me-1"></i>خصم: <span id="summary_discount_val">0 د.ع</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- خيار الشمولية العام أو الفردي للعمليات -->
                        <div class="card border-primary border-opacity-25 rounded-3 mb-4 bg-primary-subtle bg-opacity-10 shadow-2xs">
                            <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check form-switch m-0 fs-5">
                                        <input class="form-check-input" type="checkbox" id="inclusive" name="inclusive" {{ old('inclusive') ? 'checked' : '' }} style="cursor: pointer;">
                                    </div>
                                    <div>
                                        <label class="form-check-label fw-bold text-dark mb-0 d-block" for="inclusive" style="cursor: pointer;">
                                            ✨ باقة شاملة بالكامل (All-Inclusive Surgery Package)
                                        </label>
                                        <small class="text-muted">
                                            تفعيل هذا الخيار يشمل كافة التحاليل، الفحوصات، وتخطيط القلب مجاناً ضمن أجور الجراحة (0 د.ع إضافي). كما يمكنك التحكم بكل خدمة على انفراد أدناه.
                                        </small>
                                    </div>
                                </div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold px-2 py-1">
                                    <i class="fas fa-sliders-h me-1"></i>تحكم فردي متوفر لكل بند في الجدول
                                </span>
                            </div>
                        </div>

                        <!-- العناصر المعلقة للدفع -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-warning mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    العناصر المعلقة - اختر ما تريد دفعه الآن مع ضبط الشمولية بالباقة
                                </h6>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-success me-2" id="selectAllBtn">
                                        <i class="fas fa-check-double me-1"></i>
                                        تحديد الكل للدفع
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllBtn">
                                        <i class="fas fa-times me-1"></i>
                                        إلغاء الكل
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle" id="surgeryItemsTable">
                                    <thead class="table-warning">
                                        <tr>
                                            <th style="width: 50px;" class="text-center">
                                                <i class="fas fa-check-square"></i>
                                            </th>
                                            <th style="width: 220px;">البند والخدمة</th>
                                            <th>التفاصيل ومحدد الشمولية ضمن باقة الجراحة</th>
                                            <th class="text-end" style="width: 170px;">التكلفة / حصة المريض</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- رسوم العملية (إذا لم تُدفع بالكامل) -->
                                        @if(!$surgeryFeePaid && $remainingSurgeryFee > 0)
                                        <tr class="table-light align-middle">
                                            <td class="text-center">
                                                <input type="checkbox" 
                                                       class="form-check-input payment-item" 
                                                       id="pay_surgery_checkbox"
                                                       name="pay_surgery" 
                                                       value="1"
                                                       data-is-custom="true"
                                                       checked>
                                            </td>
                                            <td>
                                                <i class="fas fa-procedures text-danger me-2"></i>
                                                <strong>رسوم العمليات الجراحية</strong>
                                                <div class="small text-muted ms-4">
                                                    العملية الأساسية: {{ number_format($surgeryFee, 0) }} د.ع
                                                    @if($additionalOpsFee > 0)
                                                        + العمليات الإضافية: {{ number_format($additionalOpsFee, 0) }} د.ع
                                                    @endif
                                                    @if($surgery->medicalDevices->isNotEmpty())
                                                        <br>
                                                        + الأجهزة الطبية ({{ $surgery->medicalDevices->count() }}): 
                                                        @foreach($surgery->medicalDevices as $device)
                                                            {{ $device->name }} ({{ number_format($device->pivot->price ?? 0, 0) }} د.ع)@if(!$loop->last)، @endif
                                                        @endforeach
                                                    @endif
                                                </div>
                                                @if($surgeryFeePaidAmount > 0)
                                                    <span class="badge bg-warning text-dark ms-1">تم دفع {{ number_format($surgeryFeePaidAmount, 0) }} سابقاً</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="surgery-payment-options" id="surgery_payment_options_container">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input surgery-payment-type" type="radio" name="surgery_payment_type" id="surgery_pay_full" value="full" checked>
                                                        <label class="form-check-label text-dark" for="surgery_pay_full">دفع كامل المتبقي</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input surgery-payment-type" type="radio" name="surgery_payment_type" id="surgery_pay_part" value="partial">
                                                        <label class="form-check-label text-dark" for="surgery_pay_part">دفع جزء من الرسوم</label>
                                                    </div>
                                                    <div class="mt-2" id="surgery_custom_amount_wrapper" style="display: none; max-width: 200px;">
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" class="form-control" id="surgery_custom_amount" name="surgery_custom_amount" value="{{ $remainingSurgeryFee }}" min="1" max="{{ $remainingSurgeryFee }}">
                                                            <span class="input-group-text">IQD</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <strong id="surgery_fee_display">{{ number_format($remainingSurgeryFee, 0) }}</strong>
                                                <input type="hidden" id="surgery_fee_value" value="{{ $remainingSurgeryFee }}">
                                            </td>
                                        </tr>
                                        @endif

                                        <!-- رسوم الغرفة (إذا لم تُدفع بالكامل) -->
                                        @if(!$roomFeePaid && $remainingRoomFee > 0)
                                        <tr class="table-primary align-middle">
                                            <td class="text-center">
                                                <input type="checkbox" 
                                                       class="form-check-input payment-item" 
                                                       id="pay_room_checkbox"
                                                       name="pay_room" 
                                                       value="1"
                                                       data-is-custom="true"
                                                       checked>
                                            </td>
                                            <td>
                                                <i class="fas fa-door-open text-info me-2"></i>
                                                <strong>أجور الغرفة</strong>
                                                @if($roomFeePaidAmount > 0)
                                                    <span class="badge bg-warning text-dark ms-1">تم دفع {{ number_format($roomFeePaidAmount, 0) }} سابقاً</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="mb-2">
                                                    <strong>{{ $surgery->room->room_number ?? 'غير محدد' }}</strong>
                                                    @if($surgery->room && $surgery->room->room_type === 'vvip')
                                                        <span class="badge bg-dark text-white ms-1">
                                                            <i class="fas fa-gem text-warning me-1"></i> VVIP
                                                        </span>
                                                    @elseif($surgery->room && $surgery->room->room_type === 'vip')
                                                        <span class="badge bg-warning text-dark ms-1">
                                                            <i class="fas fa-crown text-dark me-1"></i> VIP
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light text-secondary border ms-1">عادية</span>
                                                    @endif
                                                    <br><small class="text-muted">
                                                        أجرة الليلة الأولى: {{ number_format($stayDetails['initial_fee'], 0) }} د.ع
                                                        @if($stayDetails['extra_nights'] > 0)
                                                            + {{ $stayDetails['extra_nights'] }} ليلة إضافية ({{ number_format($stayDetails['extra_nights_fee'], 0) }} د.ع)
                                                        @endif
                                                    </small>
                                                    <br><small class="text-primary" style="font-size:0.75rem;">(نظام فندقي 12:00 ظهراً)</small>
                                                </div>
                                                <div class="room-payment-options" id="room_payment_options_container">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input room-payment-type" type="radio" name="room_payment_type" id="room_pay_full" value="full" checked>
                                                        <label class="form-check-label text-dark" for="room_pay_full">دفع كامل المتبقي</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input room-payment-type" type="radio" name="room_payment_type" id="room_pay_part" value="partial">
                                                        <label class="form-check-label text-dark" for="room_pay_part">دفع جزء من الرسوم</label>
                                                    </div>
                                                    <div class="mt-2" id="room_custom_amount_wrapper" style="display: none; max-width: 200px;">
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" class="form-control" id="room_custom_amount" name="room_custom_amount" value="{{ $remainingRoomFee }}" min="1" max="{{ $remainingRoomFee }}">
                                                            <span class="input-group-text">IQD</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <strong id="room_fee_display">{{ number_format($remainingRoomFee, 0) }}</strong>
                                                <input type="hidden" id="room_fee_value" value="{{ $remainingRoomFee }}">
                                            </td>
                                        </tr>
                                        @endif

                                        <!-- التحاليل المعلقة -->
                                        @if($pendingLabTests->count() > 0)
                                            <tr class="table-info">
                                                <td class="text-center">
                                                    <input type="checkbox" 
                                                           class="form-check-input" 
                                                           id="selectAllLab">
                                                </td>
                                                <td colspan="3">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <strong>
                                                            <i class="fas fa-flask me-2"></i>
                                                            التحاليل المعلقة ({{ $pendingLabTests->count() }})
                                                        </strong>
                                                        <span class="badge bg-white text-info border small">
                                                            <i class="fas fa-info-circle me-1"></i>يمكن جعلها مشمولة بالعملية أو تسديدها كخدمة منفصلة
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @foreach($pendingLabTests as $labTest)
                                                @php
                                                    $lModel = $labTest->labTest;
                                                    $lBase = (float)($lModel->price ?? 0);
                                                    $lHi = (float)(($lModel && $lModel->hi_price > 0) ? $lModel->hi_price : $lBase);
                                                    $lMoi = (float)(($lModel && $lModel->moi_price > 0) ? $lModel->moi_price : $lBase);
                                                @endphp
                                                <tr class="align-middle item-row" data-item-group="lab" data-item-id="{{ $labTest->id }}">
                                                    <td class="text-center">
                                                        <input type="checkbox" 
                                                               class="form-check-input payment-item lab-test-item" 
                                                               name="pay_lab_tests[]" 
                                                               value="{{ $labTest->id }}"
                                                               data-base-price="{{ $lBase }}"
                                                               data-moi-price="{{ $lMoi }}"
                                                               data-hi-price="{{ $lHi }}"
                                                               data-is-moi-active="{{ ($lModel?->is_moi_active ?? true) ? '1' : '0' }}"
                                                               data-is-hi-active="{{ ($lModel?->is_hi_active ?? true) ? '1' : '0' }}"
                                                               data-item-type="lab"
                                                               checked>
                                                    </td>
                                                    <td class="ps-4">
                                                        <i class="fas fa-vial text-primary me-2"></i>
                                                        <span class="fw-semibold">تحليل روتيني للعملية</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                            <div>
                                                                <strong class="text-dark">{{ $lModel->name ?? 'غير محدد' }}</strong> 
                                                                <small class="text-muted">({{ $lModel->code ?? '-' }})</small>
                                                            </div>
                                                            <div class="form-check form-switch m-0 d-inline-flex align-items-center gap-2 bg-white px-2 py-1 rounded border shadow-2xs">
                                                                <input class="form-check-input package-inclusion-switch" type="checkbox" 
                                                                       name="package_included[lab][{{ $labTest->id }}]" 
                                                                       id="pkg_incl_lab_{{ $labTest->id }}" 
                                                                       value="1" 
                                                                       checked>
                                                                <label class="form-check-label small fw-bold mb-0" for="pkg_incl_lab_{{ $labTest->id }}" style="cursor: pointer;">
                                                                    <span class="package-status-badge badge bg-success-subtle text-success border border-success">
                                                                        <i class="fas fa-check-circle me-1"></i>مشمول ضمن باقة العملية (0 د.ع)
                                                                    </span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end item-price-display fw-bold">
                                                        0 د.ع
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr class="table-light">
                                                <td></td>
                                                <td colspan="2" class="text-end"><strong>مجموع التحاليل المعلقة:</strong></td>
                                                <td class="text-end"><strong id="pending_lab_fee_display" class="text-success">0 د.ع</strong></td>
                                            </tr>
                                        @endif

                                        <!-- الأشعة المعلقة -->
                                        @if($pendingRadiologyTests->count() > 0)
                                            <tr class="table-warning">
                                                <td class="text-center">
                                                    <input type="checkbox" 
                                                           class="form-check-input" 
                                                           id="selectAllRadiology">
                                                </td>
                                                <td colspan="3">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <strong>
                                                            <i class="fas fa-x-ray me-2"></i>
                                                            الفحوصات الإشعاعية المعلقة ({{ $pendingRadiologyTests->count() }})
                                                        </strong>
                                                        <span class="badge bg-white text-secondary border small">
                                                            <i class="fas fa-info-circle me-1"></i>يمكن جعلها مشمولة بالعملية أو منفصلة
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @foreach($pendingRadiologyTests as $radiologyTest)
                                                @php
                                                    $rModel = $radiologyTest->radiologyType;
                                                    $rBase = (float)($rModel ? $rModel->getRegularPrice() : 0);
                                                    $rMoi = (float)($rModel && $rModel->moi_price > 0 ? $rModel->moi_price : $rBase);
                                                    $rHi = (float)($rModel && $rModel->hi_price > 0 ? $rModel->hi_price : $rBase);
                                                    $rMoiActive = (bool)($rModel ? ($rModel->is_moi_active ?? true) : true);
                                                    $rHiActive = (bool)($rModel ? ($rModel->is_hi_active ?? true) : true);
                                                @endphp
                                                <tr class="align-middle item-row" data-item-group="radiology" data-item-id="{{ $radiologyTest->id }}">
                                                    <td class="text-center">
                                                        <input type="checkbox" 
                                                               class="form-check-input payment-item radiology-test-item" 
                                                               name="pay_radiology_tests[]" 
                                                               value="{{ $radiologyTest->id }}"
                                                               data-base-price="{{ $rBase }}"
                                                               data-moi-price="{{ $rMoi }}"
                                                               data-hi-price="{{ $rHi }}"
                                                               data-is-moi-active="{{ $rMoiActive ? '1' : '0' }}"
                                                               data-is-hi-active="{{ $rHiActive ? '1' : '0' }}"
                                                               data-item-type="radiology"
                                                               checked>
                                                    </td>
                                                    <td class="ps-4">
                                                        <i class="fas fa-radiation text-info me-2"></i>
                                                        <span class="fw-semibold">فحص إشعاعي</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                            <div>
                                                                <strong class="text-dark">{{ $rModel->name ?? 'غير محدد' }}</strong>
                                                            </div>
                                                            <div class="form-check form-switch m-0 d-inline-flex align-items-center gap-2 bg-white px-2 py-1 rounded border shadow-2xs">
                                                                <input class="form-check-input package-inclusion-switch" type="checkbox" 
                                                                       name="package_included[radiology][{{ $radiologyTest->id }}]" 
                                                                       id="pkg_incl_rad_{{ $radiologyTest->id }}" 
                                                                       value="1">
                                                                <label class="form-check-label small fw-bold mb-0" for="pkg_incl_rad_{{ $radiologyTest->id }}" style="cursor: pointer;">
                                                                    <span class="package-status-badge badge bg-secondary-subtle text-secondary border">
                                                                        <i class="fas fa-times-circle me-1"></i>غير مشمول (منفصل)
                                                                    </span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end item-price-display fw-bold">
                                                        {{ number_format($rBase, 0) }} د.ع
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr class="table-light">
                                                <td></td>
                                                <td colspan="2" class="text-end"><strong>مجموع الأشعة المعلقة:</strong></td>
                                                <td class="text-end"><strong id="pending_radiology_fee_display">{{ number_format($pendingRadiologyFee, 0) }}</strong></td>
                                            </tr>
                                        @endif

                                        <!-- الخدمات الإضافية والفندقية المعلقة -->
                                        @if($pendingExtraServices->count() > 0)
                                            <tr class="table-warning">
                                                <td class="text-center">
                                                    <input type="checkbox" 
                                                           class="form-check-input" 
                                                           id="selectAllExtraServices" checked>
                                                </td>
                                                <td colspan="3">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <strong>
                                                            <i class="fas fa-concierge-bell me-2 text-warning"></i>
                                                            الخدمات الإضافية والفندقية المعلقة ({{ $pendingExtraServices->count() }})
                                                        </strong>
                                                        <span class="badge bg-white text-dark border small">
                                                            <i class="fas fa-heartbeat text-danger me-1"></i>تخطيط القلب قابل للشمول بالاتفاق مع الطبيب
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @foreach($pendingExtraServices as $extraService)
                                                @php
                                                    $eTotal = (float)$extraService->total_amount;
                                                    $isEcgOrMedical = ($extraService->service?->category === 'medical')
                                                        || str_contains($extraService->name, 'تخطيط')
                                                        || str_contains(strtolower($extraService->name), 'ecg');
                                                @endphp
                                                <tr class="align-middle item-row" data-item-group="extra" data-item-id="{{ $extraService->id }}">
                                                    <td class="text-center">
                                                        <input type="checkbox" 
                                                               class="form-check-input payment-item extra-service-item" 
                                                               name="pay_extra_services[]" 
                                                               value="{{ $extraService->id }}"
                                                               data-base-price="{{ $eTotal }}"
                                                               data-moi-price="{{ $eTotal }}"
                                                               data-hi-price="{{ $eTotal }}"
                                                               data-is-medical="{{ $isEcgOrMedical ? '1' : '0' }}"
                                                               data-item-type="extra"
                                                               checked>
                                                    </td>
                                                    <td class="ps-4">
                                                        <i class="fas {{ $isEcgOrMedical ? 'fa-heartbeat text-danger' : 'fa-concierge-bell text-warning' }} me-2"></i>
                                                        <strong>{{ $isEcgOrMedical ? 'فحص سريري / تخطيط' : 'خدمة إضافية' }}</strong>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                            <div>
                                                                <span class="fw-bold text-dark fs-6">{{ $extraService->name }}</span>
                                                                @if($extraService->quantity > 1)
                                                                    <span class="badge bg-secondary ms-1">×{{ $extraService->quantity }}</span>
                                                                    <small class="text-muted ms-1">({{ number_format($extraService->price, 0) }} د.ع للوحدة)</small>
                                                                @endif
                                                                @if($extraService->notes)
                                                                    <small class="text-muted d-block mt-1"><i class="fas fa-info-circle me-1"></i>{{ $extraService->notes }}</small>
                                                                @endif
                                                            </div>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <div class="form-check form-switch m-0 d-inline-flex align-items-center gap-2 bg-white px-2 py-1 rounded border shadow-2xs">
                                                                    <input class="form-check-input package-inclusion-switch" type="checkbox" 
                                                                           name="package_included[extra][{{ $extraService->id }}]" 
                                                                           id="pkg_incl_extra_{{ $extraService->id }}" 
                                                                           value="1" 
                                                                           {{ $isEcgOrMedical ? 'checked' : '' }}>
                                                                    <label class="form-check-label small fw-bold mb-0" for="pkg_incl_extra_{{ $extraService->id }}" style="cursor: pointer;">
                                                                        <span class="package-status-badge badge {{ $isEcgOrMedical ? 'bg-success-subtle text-success border border-success' : 'bg-secondary-subtle text-secondary border' }}">
                                                                            @if($isEcgOrMedical)
                                                                                <i class="fas fa-check-circle me-1"></i>مشمول بالعملية (0 د.ع)
                                                                            @else
                                                                                <i class="fas fa-times-circle me-1"></i>غير مشمول (منفصل)
                                                                            @endif
                                                                        </span>
                                                                    </label>
                                                                </div>
                                                                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 rounded-pill delete-extra-service-btn" 
                                                                        data-url="{{ route('cashier.surgeries.extra-services.remove', [$surgery, $extraService]) }}"
                                                                        data-name="{{ $extraService->name }}"
                                                                        title="إلغاء وحذف الخدمة المعلقة">
                                                                    <i class="fas fa-trash-alt me-1"></i>حذف
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end item-price-display fw-bold">
                                                        {{ number_format($eTotal, 0) }} د.ع
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr class="table-light">
                                                <td></td>
                                                <td colspan="2" class="text-end"><strong>مجموع الخدمات الإضافية:</strong></td>
                                                <td class="text-end"><strong id="pending_extra_services_fee_display">{{ number_format($pendingExtraFee, 0) }}</strong></td>
                                            </tr>
                                        @endif
                                    </tbody>
                                    <tfoot>
                                        <!-- إجمالي المعلق -->
                                        <tr class="table-secondary">
                                            <td></td>
                                            <td colspan="2" class="text-end"><h6 class="mb-0">إجمالي المعلق:</h6></td>
                                            <td class="text-end"><h5 class="mb-0">{{ number_format($pendingAmount, 0) }} IQD</h5></td>
                                        </tr>
                                        <!-- المبلغ المحدد للدفع -->
                                        <tr class="table-success">
                                            <td></td>
                                            <td colspan="2" class="text-end">
                                                <h5 class="mb-0 text-success">
                                                    <i class="fas fa-money-bill-wave me-2"></i>
                                                    المبلغ المحدد للدفع الآن:
                                                </h5>
                                            </td>
                                            <td class="text-end">
                                                <h4 class="mb-0 text-success" id="selectedAmount">{{ number_format($pendingAmount, 0) }} IQD</h4>
                                            </td>
                                        </tr>
                                        <!-- المبلغ المؤجل -->
                                        <tr class="table-warning" id="deferredRow" style="display: none;">
                                            <td></td>
                                            <td colspan="2" class="text-end">
                                                <h6 class="mb-0 text-warning">
                                                    <i class="fas fa-clock me-2"></i>
                                                    سيبقى معلقاً (للدفع لاحقاً):
                                                </h6>
                                            </td>
                                            <td class="text-end">
                                                <h5 class="mb-0 text-warning" id="deferredAmount">0 IQD</h5>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- حقل المبلغ المخفي -->
                        <input type="hidden" name="amount" id="amountInput" value="{{ $pendingAmount }}">
                        <input type="hidden" name="total_amount" value="{{ $totalAmount }}">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-money-bill-wave me-1 text-success"></i>
                                        طريقة الدفع <span class="text-danger">*</span>
                                    </label>
                                    <div class="payment-methods-group">
                                        <div class="form-check form-check-lg mb-2">
                                            <input class="form-check-input" type="radio" name="payment_method" id="surgery_payment_cash" 
                                                   value="cash" {{ old('payment_method', 'cash') == 'cash' ? 'checked' : '' }} 
                                                   onchange="if(typeof updateSplitUI === 'function') updateSplitUI();" required>
                                            <label class="form-check-label fw-semibold" for="surgery_payment_cash" style="cursor: pointer;">
                                                💵 نقدي (Cash)
                                            </label>
                                        </div>
                                        <div class="form-check form-check-lg mb-2">
                                            <input class="form-check-input" type="radio" name="payment_method" id="surgery_payment_card" 
                                                   value="card" {{ old('payment_method') == 'card' ? 'checked' : '' }}
                                                   onchange="if(typeof updateSplitUI === 'function') updateSplitUI();">
                                            <label class="form-check-label fw-semibold" for="surgery_payment_card" style="cursor: pointer;">
                                                💳 بطاقة ائتمان (Card)
                                            </label>
                                        </div>
                                        <div class="form-check form-check-lg mb-2">
                                            <input class="form-check-input" type="radio" name="payment_method" id="surgery_payment_split" 
                                                   value="split" {{ old('payment_method') == 'split' ? 'checked' : '' }}
                                                   onchange="if(typeof updateSplitUI === 'function') updateSplitUI();">
                                            <label class="form-check-label fw-semibold text-primary" for="surgery_payment_split" style="cursor: pointer;">
                                                🔄 دفع مركب / مختلط (مقسم كاش + بطاقة)
                                            </label>
                                        </div>
                                        <div class="form-check form-check-lg">
                                            <input class="form-check-input" type="radio" name="payment_method" id="surgery_payment_insurance" 
                                                   value="insurance" {{ old('payment_method') == 'insurance' ? 'checked' : '' }}
                                                   onchange="if(typeof updateSplitUI === 'function') updateSplitUI();">
                                            <label class="form-check-label fw-semibold" for="surgery_payment_insurance" style="cursor: pointer;">
                                                🏥 تأمين صحي (Insurance)
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- صندوق الدفع المركب (كاش + بطاقة) -->
                                    <div id="split_payment_box" class="p-3 mt-3 rounded-3 border border-primary bg-primary-subtle" style="display: none;">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="fw-bold text-primary">
                                                <i class="fas fa-coins me-1"></i> تفصيل الدفع المركب:
                                            </span>
                                            <span id="split_status_badge" class="badge bg-secondary">0 / 0 IQD</span>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label class="form-label small fw-bold mb-1">
                                                    💵 المبلغ نقداً (Cash)
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="any" min="0" class="form-control fw-bold" id="cash_amount" name="cash_amount" value="{{ old('cash_amount', 0) }}" placeholder="0">
                                                    <span class="input-group-text">IQD</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-bold mb-1">
                                                    💳 المبلغ بالبطاقة (Card)
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="any" min="0" class="form-control fw-bold" id="card_amount" name="card_amount" value="{{ old('card_amount', 0) }}" placeholder="0">
                                                    <span class="input-group-text">IQD</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2 small">
                                            <span id="split_helper_text" class="text-muted">أدخل جزءاً بالكاش وسيتم إكمال الباقي بالبطاقة آلياً.</span>
                                            <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" id="split_equal_btn" style="font-size: 0.75rem;">
                                                مناصفة (50/50)
                                            </button>
                                        </div>
                                    </div>
                                    @error('payment_method')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">
                                        <i class="fas fa-sticky-note me-1"></i>
                                        ملاحظات
                                    </label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" 
                                              name="notes" 
                                              rows="2" 
                                              placeholder="أي ملاحظات إضافية...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('cashier.surgeries.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i>
                                        العودة
                                    </a>
                                    <div>
                                        <span class="me-3 text-muted" id="itemsCount">
                                            <i class="fas fa-check-circle text-success me-1"></i>
                                            تم تحديد <strong id="selectedItemsCount">0</strong> عنصر للدفع
                                        </span>
                                        <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                                            <i class="fas fa-money-bill-wave me-2"></i>
                                            تأكيد الدفع
                                            <span id="submitAmount" class="ms-2">({{ number_format($pendingAmount, 0) }} IQD)</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    @elseif($totalExcess <= 0)
                    <!-- لا توجد عناصر معلقة -->
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h5>تم دفع جميع رسوم هذه العملية!</h5>
                        <p class="mb-0">لا توجد مبالغ معلقة للدفع أو مسترجعات مالية.</p>
                        <a href="{{ route('cashier.surgeries.index') }}" class="btn btn-primary mt-3">
                            <i class="fas fa-arrow-left me-1"></i>
                            العودة لقائمة العمليات
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($pendingAmount > 0)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pendingAmount = {{ $pendingAmount }};
    const remainingSurgeryFee = {{ $remainingSurgeryFee }};
    const remainingRoomFee = {{ $remainingRoomFee }};
    const inclusiveCheckbox = document.getElementById('inclusive');

    // عناصر الضمان
    const insuranceTypeSelect = document.getElementById('insurance_type');
    const insuranceCardCol = document.getElementById('insurance_card_col');
    const copaySection = document.getElementById('copay_section');
    const copayBadge = document.getElementById('copayBadge');
    const copayInput = document.getElementById('copay_percentage');
    const paymentInsuranceRadio = document.getElementById('surgery_payment_insurance');
    const paymentCashRadio = document.getElementById('surgery_payment_cash');

    const hiCategoryCopay = @json($patient ? (float)$patient->getCopayPercentageFor('surgery') : 15.0);
    const hiCopays = {
        surgery: @json($patient ? (float)$patient->getCopayPercentageFor('surgery') : 15.0),
        lab: @json($patient ? (float)$patient->getCopayPercentageFor('lab') : 15.0),
        radiology: @json($patient ? (float)$patient->getCopayPercentageFor('radiology') : 15.0),
        ecg: @json($patient ? (float)$patient->getCopayPercentageFor('ecg') : 10.0),
    };
    const moiCopay = @json((float)($patient->copay_percentage ?? 15.0));
    const hiBadge = document.getElementById('patient_hi_category_badge');

    function numberFormat(num) {
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // --- عناصر ومنطق الدفع المركب (كاش + بطاقة) ---
    const splitPaymentBox = document.getElementById('split_payment_box');
    const cashAmountInput = document.getElementById('cash_amount');
    const cardAmountInput = document.getElementById('card_amount');
    const splitStatusBadge = document.getElementById('split_status_badge');
    const splitEqualBtn = document.getElementById('split_equal_btn');
    const surgeryPaymentSplitRadio = document.getElementById('surgery_payment_split');
    const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');

    function isSplitActive() {
        return surgeryPaymentSplitRadio && surgeryPaymentSplitRadio.checked;
    }

    function updateSplitUI() {
        if (!splitPaymentBox) return;

        const inputAmount = document.getElementById('amountInput');
        const currentTarget = parseFloat(inputAmount ? inputAmount.value : 0) || 0;

        if (isSplitActive()) {
            splitPaymentBox.style.display = 'block';

            let cashVal = parseFloat(cashAmountInput ? cashAmountInput.value : 0) || 0;
            let cardVal = parseFloat(cardAmountInput ? cardAmountInput.value : 0) || 0;

            if (cashVal === 0 && cardVal === 0 && currentTarget > 0) {
                cashVal = currentTarget;
                if (cashAmountInput) cashAmountInput.value = currentTarget;
                if (cardAmountInput) cardAmountInput.value = 0;
            }

            const totalSplit = Math.round(cashVal + cardVal);
            const targetRounded = Math.round(currentTarget);

            if (splitStatusBadge) {
                if (totalSplit === targetRounded && targetRounded > 0) {
                    splitStatusBadge.className = 'badge bg-success';
                    splitStatusBadge.innerHTML = '<i class="fas fa-check-circle me-1"></i>متطابق: ' + numberFormat(totalSplit) + ' IQD';
                } else {
                    splitStatusBadge.className = 'badge bg-danger';
                    splitStatusBadge.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>المجموع: ' + numberFormat(totalSplit) + ' / المطلوب: ' + numberFormat(targetRounded) + ' IQD';
                }
            }
        } else {
            splitPaymentBox.style.display = 'none';
        }
    }

    function syncSplitAmounts(source) {
        const inputAmount = document.getElementById('amountInput');
        const currentTarget = parseFloat(inputAmount ? inputAmount.value : 0) || 0;
        let cashVal = parseFloat(cashAmountInput ? cashAmountInput.value : 0) || 0;
        let cardVal = parseFloat(cardAmountInput ? cardAmountInput.value : 0) || 0;

        if (source === 'cash') {
            if (cashVal > currentTarget) {
                cashVal = currentTarget;
                if (cashAmountInput) cashAmountInput.value = cashVal;
            }
            cardVal = Math.max(0, currentTarget - cashVal);
            if (cardAmountInput) cardAmountInput.value = Math.round(cardVal);
        } else if (source === 'card') {
            if (cardVal > currentTarget) {
                cardVal = currentTarget;
                if (cardAmountInput) cardAmountInput.value = cardVal;
            }
            cashVal = Math.max(0, currentTarget - cardVal);
            if (cashAmountInput) cashAmountInput.value = Math.round(cashVal);
        }

        updateSplitUI();
    }

    window.updateSplitUI = updateSplitUI;
    window.syncSplitAmounts = syncSplitAmounts;

    function calculateSelectedAmount() {
        let totalApprovedPayable = 0;
        let totalPatientShare = 0;
        let totalInsuranceShare = 0;
        let selectedCount = 0;

        const currentInsType = insuranceTypeSelect ? insuranceTypeSelect.value : 'none';
        const isIns = (currentInsType === 'hi' || currentInsType === 'moi');

        // Dynamic copays:
        let surgeryCopayPct = 100;
        let labCopayPct = 100;
        let radCopayPct = 100;
        let ecgCopayPct = 100;

        if (currentInsType === 'hi') {
            surgeryCopayPct = hiCopays.surgery;
            labCopayPct = hiCopays.lab;
            radCopayPct = hiCopays.radiology;
            ecgCopayPct = hiCopays.ecg;
        } else if (currentInsType === 'moi') {
            surgeryCopayPct = moiCopay;
            labCopayPct = moiCopay;
            radCopayPct = moiCopay;
            ecgCopayPct = moiCopay;
        }

        // 1. Surgery Fee portion
        const paySurgeryCheckbox = document.getElementById('pay_surgery_checkbox');
        let surgeryPaidThisTime = 0;
        if (paySurgeryCheckbox && paySurgeryCheckbox.checked) {
            const payFull = document.getElementById('surgery_pay_full') ? document.getElementById('surgery_pay_full').checked : true;
            const custWrapper = document.getElementById('surgery_custom_amount_wrapper');
            const custInput = document.getElementById('surgery_custom_amount');
            const feeDisp = document.getElementById('surgery_fee_display');

            if (payFull) {
                surgeryPaidThisTime = remainingSurgeryFee;
                if (custInput) custInput.value = Math.round(remainingSurgeryFee);
                if (custWrapper) custWrapper.style.display = 'none';
                if (feeDisp) feeDisp.textContent = numberFormat(remainingSurgeryFee);
            } else {
                if (custWrapper) custWrapper.style.display = 'block';
                let customVal = parseFloat(custInput ? custInput.value : 0) || 0;
                if (customVal > remainingSurgeryFee) {
                    customVal = remainingSurgeryFee;
                    if (custInput) custInput.value = Math.round(remainingSurgeryFee);
                }
                surgeryPaidThisTime = customVal;
                if (feeDisp) feeDisp.textContent = numberFormat(customVal);
            }

            if (surgeryPaidThisTime > 0) {
                totalApprovedPayable += surgeryPaidThisTime;
                if (isIns) {
                    const pShare = Math.round(surgeryPaidThisTime * (surgeryCopayPct / 100));
                    totalPatientShare += pShare;
                    totalInsuranceShare += Math.max(0, surgeryPaidThisTime - pShare);
                } else {
                    totalPatientShare += surgeryPaidThisTime;
                }
                selectedCount++;
            }
        } else if (paySurgeryCheckbox) {
            const custWrapper = document.getElementById('surgery_custom_amount_wrapper');
            const feeDisp = document.getElementById('surgery_fee_display');
            if (custWrapper) custWrapper.style.display = 'none';
            if (feeDisp) feeDisp.textContent = '0';
        }

        // 2. Room Fee portion
        const payRoomCheckbox = document.getElementById('pay_room_checkbox');
        let roomPaidThisTime = 0;
        if (payRoomCheckbox && payRoomCheckbox.checked) {
            const payFull = document.getElementById('room_pay_full') ? document.getElementById('room_pay_full').checked : true;
            const custWrapper = document.getElementById('room_custom_amount_wrapper');
            const custInput = document.getElementById('room_custom_amount');
            const feeDisp = document.getElementById('room_fee_display');

            if (payFull) {
                roomPaidThisTime = remainingRoomFee;
                if (custInput) custInput.value = Math.round(remainingRoomFee);
                if (custWrapper) custWrapper.style.display = 'none';
                if (feeDisp) feeDisp.textContent = numberFormat(remainingRoomFee);
            } else {
                if (custWrapper) custWrapper.style.display = 'block';
                let customVal = parseFloat(custInput ? custInput.value : 0) || 0;
                if (customVal > remainingRoomFee) {
                    customVal = remainingRoomFee;
                    if (custInput) custInput.value = Math.round(remainingRoomFee);
                }
                roomPaidThisTime = customVal;
                if (feeDisp) feeDisp.textContent = numberFormat(customVal);
            }

            if (roomPaidThisTime > 0) {
                totalApprovedPayable += roomPaidThisTime;
                if (isIns) {
                    const pShare = Math.round(roomPaidThisTime * (surgeryCopayPct / 100));
                    totalPatientShare += pShare;
                    totalInsuranceShare += Math.max(0, roomPaidThisTime - pShare);
                } else {
                    totalPatientShare += roomPaidThisTime;
                }
                selectedCount++;
            }
        } else if (payRoomCheckbox) {
            const custWrapper = document.getElementById('room_custom_amount_wrapper');
            const feeDisp = document.getElementById('room_fee_display');
            if (custWrapper) custWrapper.style.display = 'none';
            if (feeDisp) feeDisp.textContent = '0';
        }

        // 3. Other items: Lab Tests, Radiology, Extra Services
        let currentLabTotal = 0;
        let currentRadTotal = 0;
        let currentExtraTotal = 0;

        document.querySelectorAll('.item-row').forEach(function(row) {
            const itemCheckbox = row.querySelector('.payment-item');
            const pkgSwitch = row.querySelector('.package-inclusion-switch');
            const statusBadge = row.querySelector('.package-status-badge');
            const priceCell = row.querySelector('.item-price-display');

            if (!itemCheckbox) return;

            const isCheckedForPay = itemCheckbox.checked;
            const isPkgIncluded = pkgSwitch ? pkgSwitch.checked : false;
            const itemType = itemCheckbox.dataset.itemType; // 'lab', 'radiology', 'extra'
            const basePrice = parseFloat(itemCheckbox.dataset.basePrice) || 0;
            const moiPrice = parseFloat(itemCheckbox.dataset.moiPrice) || basePrice;
            const hiPrice = parseFloat(itemCheckbox.dataset.hiPrice) || basePrice;
            const isMedical = itemCheckbox.dataset.isMedical === '1';

            // Update badge UI
            if (statusBadge) {
                if (isPkgIncluded) {
                    statusBadge.className = 'package-status-badge badge bg-success-subtle text-success border border-success';
                    statusBadge.innerHTML = '<i class="fas fa-check-circle me-1"></i>مشمول ضمن باقة العملية (0 د.ع)';
                } else {
                    statusBadge.className = 'package-status-badge badge bg-secondary-subtle text-secondary border';
                    statusBadge.innerHTML = '<i class="fas fa-times-circle me-1"></i>غير مشمول (منفصل)';
                }
            }

            // Calculate item financial figures
            let itemApproved = 0;
            let itemPatientShare = 0;
            let itemInsuranceShare = 0;

            if (isPkgIncluded) {
                // Included in surgery package -> 0 IQD impact to patient & insurance
                itemApproved = 0;
                itemPatientShare = 0;
                itemInsuranceShare = 0;

                if (priceCell) {
                    priceCell.innerHTML = '<span class="text-success fw-bold">0 د.ع</span> <small class="text-muted">(مشمول)</small>';
                }
            } else {
                // Unbundled item:
                if (itemType === 'lab') {
                    if (currentInsType === 'hi') {
                        itemApproved = hiPrice > 0 ? hiPrice : basePrice;
                        itemPatientShare = Math.round(itemApproved * (labCopayPct / 100));
                        itemInsuranceShare = Math.max(0, itemApproved - itemPatientShare);
                    } else if (currentInsType === 'moi') {
                        itemApproved = moiPrice > 0 ? moiPrice : basePrice;
                        itemPatientShare = Math.round(itemApproved * (labCopayPct / 100));
                        itemInsuranceShare = Math.max(0, itemApproved - itemPatientShare);
                    } else {
                        itemApproved = basePrice;
                        itemPatientShare = basePrice;
                        itemInsuranceShare = 0;
                    }

                    if (priceCell) {
                        if (isIns) {
                            priceCell.innerHTML = '<strong>' + numberFormat(itemPatientShare) + ' د.ع</strong><br><small class="text-muted">(مطالبة: ' + numberFormat(itemInsuranceShare) + ')</small>';
                        } else {
                            priceCell.innerHTML = '<strong>' + numberFormat(itemPatientShare) + ' د.ع</strong>';
                        }
                    }
                } else if (itemType === 'radiology') {
                    if (currentInsType === 'hi') {
                        itemApproved = hiPrice > 0 ? hiPrice : basePrice;
                        itemPatientShare = Math.round(itemApproved * (radCopayPct / 100));
                        itemInsuranceShare = Math.max(0, itemApproved - itemPatientShare);
                    } else if (currentInsType === 'moi') {
                        itemApproved = moiPrice > 0 ? moiPrice : basePrice;
                        itemPatientShare = Math.round(itemApproved * (radCopayPct / 100));
                        itemInsuranceShare = Math.max(0, itemApproved - itemPatientShare);
                    } else {
                        itemApproved = basePrice;
                        itemPatientShare = basePrice;
                        itemInsuranceShare = 0;
                    }

                    if (priceCell) {
                        if (isIns) {
                            priceCell.innerHTML = '<strong>' + numberFormat(itemPatientShare) + ' د.ع</strong><br><small class="text-muted">(مطالبة: ' + numberFormat(itemInsuranceShare) + ')</small>';
                        } else {
                            priceCell.innerHTML = '<strong>' + numberFormat(itemPatientShare) + ' د.ع</strong>';
                        }
                    }
                } else if (itemType === 'extra') {
                    if (isIns && isMedical) {
                        itemApproved = basePrice;
                        itemPatientShare = Math.round(itemApproved * (ecgCopayPct / 100));
                        itemInsuranceShare = Math.max(0, itemApproved - itemPatientShare);
                    } else {
                        itemApproved = basePrice;
                        itemPatientShare = basePrice;
                        itemInsuranceShare = 0;
                    }

                    if (priceCell) {
                        if (isIns && isMedical) {
                            priceCell.innerHTML = '<strong>' + numberFormat(itemPatientShare) + ' د.ع</strong><br><small class="text-muted">(تحمل ' + ecgCopayPct + '%)</small>';
                        } else {
                            priceCell.innerHTML = '<strong>' + numberFormat(itemPatientShare) + ' د.ع</strong>';
                        }
                    }
                }
            }

            if (isCheckedForPay) {
                totalApprovedPayable += itemApproved;
                totalPatientShare += itemPatientShare;
                totalInsuranceShare += itemInsuranceShare;
                selectedCount++;

                if (itemType === 'lab') currentLabTotal += itemPatientShare;
                if (itemType === 'radiology') currentRadTotal += itemPatientShare;
                if (itemType === 'extra') currentExtraTotal += itemPatientShare;
            }
        });

        const labTotalDisplay = document.getElementById('pending_lab_fee_display');
        if (labTotalDisplay) labTotalDisplay.textContent = numberFormat(currentLabTotal) + ' د.ع';
        const radTotalDisplay = document.getElementById('pending_radiology_fee_display');
        if (radTotalDisplay) radTotalDisplay.textContent = numberFormat(currentRadTotal) + ' د.ع';
        const extraTotalDisplay = document.getElementById('pending_extra_services_fee_display');
        if (extraTotalDisplay) extraTotalDisplay.textContent = numberFormat(currentExtraTotal) + ' د.ع';

        // Discounts for cash
        const discountInput = document.getElementById('discount_percentage');
        const discountSection = document.getElementById('discount_section');
        const displayDiscountBadge = document.getElementById('display_discount_badge');
        const displayDiscountAmount = document.getElementById('display_discount_amount');
        const summaryDiscountNote = document.getElementById('summary_discount_note');
        const summaryDiscountVal = document.getElementById('summary_discount_val');

        let discountPct = 0;
        if (!isIns) {
            if (discountSection) discountSection.style.display = 'block';
            discountPct = parseFloat(discountInput ? discountInput.value : 0) || 0;
            if (discountPct < 0) discountPct = 0;
            if (discountPct > 100) discountPct = 100;
        } else {
            if (discountSection) discountSection.style.display = 'none';
            if (discountInput) discountInput.value = '0';
            discountPct = 0;
        }

        let discountAmount = 0;
        let netPatientShare = totalPatientShare;
        if (!isIns && discountPct > 0) {
            discountAmount = Math.round(totalPatientShare * (discountPct / 100));
            netPatientShare = Math.max(0, totalPatientShare - discountAmount);
        }

        if (displayDiscountBadge) displayDiscountBadge.textContent = discountPct + '%';
        if (displayDiscountAmount) displayDiscountAmount.textContent = 'قيمة الخصم: ' + numberFormat(discountAmount) + ' د.ع';
        if (summaryDiscountNote) {
            if (discountAmount > 0) {
                summaryDiscountNote.classList.remove('d-none');
                if (summaryDiscountVal) summaryDiscountVal.textContent = '-' + numberFormat(discountAmount) + ' د.ع';
            } else {
                summaryDiscountNote.classList.add('d-none');
            }
        }

        // Update displays
        const dispApproved = document.getElementById('display_approved_price');
        const dispPatient = document.getElementById('display_patient_share');
        const dispInsurance = document.getElementById('display_insurance_share');
        const dispSelected = document.getElementById('selectedAmount');
        const dispDeferred = document.getElementById('deferredAmount');
        const inputAmount = document.getElementById('amountInput');
        const submitAmountSpan = document.getElementById('submitAmount');
        const selectedCountSpan = document.getElementById('selectedItemsCount');
        const submitBtn = document.getElementById('submitBtn');
        const deferredRow = document.getElementById('deferredRow');

        let deferredAmount = Math.max(0, pendingAmount - totalApprovedPayable);
        if (inclusiveCheckbox && inclusiveCheckbox.checked) {
            deferredAmount = 0;
        }

        if (dispApproved) dispApproved.textContent = numberFormat(totalApprovedPayable) + ' د.ع';
        if (dispPatient) dispPatient.textContent = numberFormat(netPatientShare) + ' د.ع';
        if (dispInsurance) dispInsurance.textContent = numberFormat(totalInsuranceShare) + ' د.ع';
        if (dispSelected) dispSelected.textContent = numberFormat(netPatientShare) + ' IQD';
        if (dispDeferred) dispDeferred.textContent = numberFormat(deferredAmount) + ' IQD';
        if (inputAmount) inputAmount.value = netPatientShare;
        if (submitAmountSpan) submitAmountSpan.textContent = '(' + numberFormat(netPatientShare) + ' IQD)';
        if (selectedCountSpan) selectedCountSpan.textContent = selectedCount;

        if (deferredRow) {
            deferredRow.style.display = deferredAmount > 0 ? '' : 'none';
        }

        if (submitBtn) {
            submitBtn.disabled = selectedCount === 0;
        }

        updateGroupCheckbox('lab-test-item', 'selectAllLab');
        updateGroupCheckbox('radiology-test-item', 'selectAllRadiology');
        updateGroupCheckbox('extra-service-item', 'selectAllExtraServices');
        updateSplitUI();
    }

    function updateCopayUI() {
        const insType = insuranceTypeSelect ? insuranceTypeSelect.value : 'none';
        const discountSection = document.getElementById('discount_section');
        const discountSelect = document.getElementById('discount_percentage');
        if (insType === 'moi') {
            if (insuranceCardCol) insuranceCardCol.style.display = 'block';
            if (copaySection) copaySection.style.display = 'block';
            if (hiBadge) hiBadge.style.display = 'none';
            if (discountSection) discountSection.style.display = 'none';
            if (discountSelect) discountSelect.value = '0';
            if (copayBadge) {
                copayBadge.className = 'badge bg-primary';
                copayBadge.textContent = 'ضمان الداخلية';
            }
        } else if (insType === 'hi') {
            if (insuranceCardCol) insuranceCardCol.style.display = 'block';
            if (copaySection) copaySection.style.display = 'block';
            if (hiBadge) hiBadge.style.display = 'block';
            if (discountSection) discountSection.style.display = 'none';
            if (discountSelect) discountSelect.value = '0';
            if (copayBadge) {
                copayBadge.className = 'badge bg-info text-dark';
                copayBadge.textContent = 'الضمان الصحي الوطني';
            }
        } else {
            if (insuranceCardCol) insuranceCardCol.style.display = 'none';
            if (copaySection) copaySection.style.display = 'none';
            if (hiBadge) hiBadge.style.display = 'none';
            if (discountSection) discountSection.style.display = 'block';
            if (copayBadge) {
                copayBadge.className = 'badge bg-secondary';
                copayBadge.textContent = 'دفع نقدي كامل (100%)';
            }
        }
        calculateSelectedAmount();
    }

    const discountInputEl = document.getElementById('discount_percentage');
    if (discountInputEl) {
        discountInputEl.addEventListener('input', calculateSelectedAmount);
        discountInputEl.addEventListener('change', calculateSelectedAmount);
    }

    if (insuranceTypeSelect) {
        insuranceTypeSelect.addEventListener('change', function() {
            if (this.value === 'hi') {
                if (paymentInsuranceRadio) paymentInsuranceRadio.checked = true;
                if (copayInput) copayInput.value = hiCategoryCopay;
            } else if (this.value === 'moi') {
                if (paymentInsuranceRadio) paymentInsuranceRadio.checked = true;
                if (copayInput) copayInput.value = moiCopay;
            } else {
                if (paymentCashRadio) paymentCashRadio.checked = true;
            }
            updateCopayUI();
        });
    }

    if (copayInput) {
        copayInput.addEventListener('input', calculateSelectedAmount);
    }

    document.querySelectorAll('.copay-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const pct = this.getAttribute('data-pct');
            if (copayInput) {
                copayInput.value = pct;
                calculateSelectedAmount();
            }
            document.querySelectorAll('.copay-btn').forEach(b => b.classList.remove('active', 'btn-primary'));
            document.querySelectorAll('.copay-btn').forEach(b => b.classList.add('btn-outline-primary'));
            this.classList.remove('btn-outline-primary');
            this.classList.add('active', 'btn-primary');
        });
    });

    function toggleInclusive() {
        const isIncl = inclusiveCheckbox && inclusiveCheckbox.checked;
        document.querySelectorAll('.package-inclusion-switch').forEach(function(sw) {
            sw.checked = isIncl;
        });
        calculateSelectedAmount();
    }

    if (inclusiveCheckbox) {
        inclusiveCheckbox.addEventListener('change', toggleInclusive);
    }

    document.querySelectorAll('.package-inclusion-switch').forEach(function(sw) {
        sw.addEventListener('change', function() {
            if (!this.checked && inclusiveCheckbox && inclusiveCheckbox.checked) {
                inclusiveCheckbox.checked = false;
            }
            calculateSelectedAmount();
        });
    });

    function updateGroupCheckbox(itemClass, groupCheckboxId) {
        const items = document.querySelectorAll('.' + itemClass);
        const groupCheckbox = document.getElementById(groupCheckboxId);

        if (!groupCheckbox || items.length === 0) return;

        const checkedItems = document.querySelectorAll('.' + itemClass + ':checked');

        if (checkedItems.length === 0) {
            groupCheckbox.checked = false;
            groupCheckbox.indeterminate = false;
        } else if (checkedItems.length === items.length) {
            groupCheckbox.checked = true;
            groupCheckbox.indeterminate = false;
        } else {
            groupCheckbox.checked = false;
            groupCheckbox.indeterminate = true;
        }
    }

    function numberFormat(num) {
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function selectAll() {
        document.querySelectorAll('.payment-item').forEach(function(item) {
            item.checked = true;
        });
        const selectAllLab = document.getElementById('selectAllLab');
        const selectAllRadiology = document.getElementById('selectAllRadiology');
        const selectAllExtra = document.getElementById('selectAllExtraServices');
        if (selectAllLab) selectAllLab.checked = true;
        if (selectAllRadiology) selectAllRadiology.checked = true;
        if (selectAllExtra) selectAllExtra.checked = true;
        calculateSelectedAmount();
    }

    function deselectAll() {
        document.querySelectorAll('.payment-item').forEach(function(item) {
            item.checked = false;
        });
        const selectAllLab = document.getElementById('selectAllLab');
        const selectAllRadiology = document.getElementById('selectAllRadiology');
        const selectAllExtra = document.getElementById('selectAllExtraServices');
        if (selectAllLab) selectAllLab.checked = false;
        if (selectAllRadiology) selectAllRadiology.checked = false;
        if (selectAllExtra) selectAllExtra.checked = false;
        calculateSelectedAmount();
    }

    function toggleLabTests() {
        const selectAllLab = document.getElementById('selectAllLab');
        document.querySelectorAll('.lab-test-item').forEach(function(item) {
            item.checked = selectAllLab.checked;
        });
        calculateSelectedAmount();
    }

    function toggleRadiologyTests() {
        const selectAllRadiology = document.getElementById('selectAllRadiology');
        document.querySelectorAll('.radiology-test-item').forEach(function(item) {
            item.checked = selectAllRadiology.checked;
        });
        calculateSelectedAmount();
    }

    function toggleExtraServices() {
        const selectAllExtra = document.getElementById('selectAllExtraServices');
        document.querySelectorAll('.extra-service-item').forEach(function(item) {
            item.checked = selectAllExtra.checked;
        });
        calculateSelectedAmount();
    }

    // إضافة مستمعي الأحداث
    document.getElementById('selectAllBtn').addEventListener('click', selectAll);
    document.getElementById('deselectAllBtn').addEventListener('click', deselectAll);

    // مستمعي أحداث checkboxes المجموعات
    const selectAllLab = document.getElementById('selectAllLab');
    const selectAllRadiology = document.getElementById('selectAllRadiology');
    const selectAllExtraServices = document.getElementById('selectAllExtraServices');

    if (selectAllLab) {
        selectAllLab.addEventListener('change', toggleLabTests);
    }

    if (selectAllRadiology) {
        selectAllRadiology.addEventListener('change', toggleRadiologyTests);
    }

    if (selectAllExtraServices) {
        selectAllExtraServices.addEventListener('change', toggleExtraServices);
    }

    // التعبئة التلقائية لسعر الخدمة الإضافية من الكتالوج
    const serviceNameInput = document.getElementById('service_name_input');
    const servicePriceInput = document.getElementById('service_price_input');
    const serviceIdHidden = document.getElementById('service_id_hidden');
    const catalogOptions = document.querySelectorAll('#extra_services_catalog_list option');

    if (serviceNameInput) {
        const updatePriceFromCatalog = function() {
            const val = serviceNameInput.value.trim().toLowerCase();
            let matched = false;
            catalogOptions.forEach(opt => {
                if (opt.value.trim().toLowerCase() === val) {
                    if (servicePriceInput) servicePriceInput.value = opt.getAttribute('data-price') || 0;
                    if (serviceIdHidden) serviceIdHidden.value = opt.getAttribute('data-id') || '';
                    matched = true;
                }
            });
            if (!matched && serviceIdHidden) {
                serviceIdHidden.value = '';
            }
        };

        serviceNameInput.addEventListener('input', updatePriceFromCatalog);
        serviceNameInput.addEventListener('change', updatePriceFromCatalog);
    }

    // تأكيد وحذف خدمة إضافية معلقة
    document.querySelectorAll('.delete-extra-service-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('data-url');
            const name = this.getAttribute('data-name');
            if (confirm('هل أنت متأكد من حذف خدمة "' + name + '" من العملية؟')) {
                const delForm = document.createElement('form');
                delForm.method = 'POST';
                delForm.action = url;
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                delForm.appendChild(csrf);
                delForm.appendChild(method);
                document.body.appendChild(delForm);
                delForm.submit();
            }
        });
    });

    // مستمعي أحداث تغيير الراديو للعملية
    document.querySelectorAll('.surgery-payment-type').forEach(function(radio) {
        radio.addEventListener('change', calculateSelectedAmount);
    });
    const surgeryCustomAmount = document.getElementById('surgery_custom_amount');
    if (surgeryCustomAmount) {
        surgeryCustomAmount.addEventListener('input', calculateSelectedAmount);
    }

    // مستمعي أحداث تغيير الراديو للغرفة
    document.querySelectorAll('.room-payment-type').forEach(function(radio) {
        radio.addEventListener('change', calculateSelectedAmount);
    });
    const roomCustomAmount = document.getElementById('room_custom_amount');
    if (roomCustomAmount) {
        roomCustomAmount.addEventListener('input', calculateSelectedAmount);
    }

    // إضافة مستمعي الأحداث لجميع checkboxes العناصر
    document.querySelectorAll('.payment-item').forEach(function(item) {
        item.addEventListener('change', calculateSelectedAmount);
    });

    if (cashAmountInput) {
        cashAmountInput.addEventListener('input', function() { syncSplitAmounts('cash'); });
        cashAmountInput.addEventListener('change', function() { syncSplitAmounts('cash'); });
    }
    if (cardAmountInput) {
        cardAmountInput.addEventListener('input', function() { syncSplitAmounts('card'); });
        cardAmountInput.addEventListener('change', function() { syncSplitAmounts('card'); });
    }
    if (splitEqualBtn) {
        splitEqualBtn.addEventListener('click', function() {
            const inputAmount = document.getElementById('amountInput');
            const currentTarget = parseFloat(inputAmount ? inputAmount.value : 0) || 0;
            const half = Math.floor(currentTarget / 2);
            const remaining = currentTarget - half;
            if (cashAmountInput) cashAmountInput.value = half;
            if (cardAmountInput) cardAmountInput.value = remaining;
            updateSplitUI();
        });
    }

    paymentMethodRadios.forEach(radio => {
        radio.addEventListener('change', updateSplitUI);
        radio.addEventListener('click', updateSplitUI);
    });

    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            if (isSplitActive()) {
                const inputAmount = document.getElementById('amountInput');
                const currentTarget = parseFloat(inputAmount ? inputAmount.value : 0) || 0;
                const cashVal = parseFloat(cashAmountInput ? cashAmountInput.value : 0) || 0;
                const cardVal = parseFloat(cardAmountInput ? cardAmountInput.value : 0) || 0;
                const totalSplit = Math.round(cashVal + cardVal);
                const targetRounded = Math.round(currentTarget);

                if (totalSplit !== targetRounded) {
                    e.preventDefault();
                    alert('تنبيه: مجموع مبالغ الدفع المركب (كاش: ' + numberFormat(cashVal) + ' + بطاقة: ' + numberFormat(cardVal) + ' = ' + numberFormat(totalSplit) + ' د.ع) لا يتطابق مع المبلغ المطلوب قبضه (' + numberFormat(targetRounded) + ' د.ع). يرجى ضبط المبالغ قبل التأكيد.');
                    return false;
                }
            }
        });
    }

    // حساب أولي
    calculateSelectedAmount();
});
</script>
@endif
@endsection
