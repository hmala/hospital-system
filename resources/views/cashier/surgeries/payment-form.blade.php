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
    
    // تحاليل معلقة ومدفوعة
    $pendingLabTests = $isCancelled ? collect() : $surgery->labTests->where('payment_status', '!=', 'paid');
    $paidLabTests = $surgery->labTests->filter(function($test) use ($isCancelled) {
        return $test->payment_status === 'paid' || !empty($test->payment_id) || ($isCancelled && $test->labTest);
    });
    $pendingLabFee = $isCancelled ? 0 : $pendingLabTests->sum(function($test) {
        return $test->labTest->price ?? 0;
    });
    $paidLabFee = $paidLabTests->sum(function($test) {
        return $test->labTest->price ?? 0;
    });
    
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
    
    // المبالغ (تشمل رسوم الغرفة وتعتمد على المبالغ المدفوعة جزئياً)
    $pendingAmount = $isCancelled ? 0 : ($remainingSurgeryFee + $remainingRoomFee + $pendingLabFee + $pendingRadiologyFee);
    $paidAmount = $surgeryFeePaidAmount + $roomFeePaidAmount + $paidLabFee + $paidRadiologyFee;
    $totalAmount = $isCancelled ? 0 : ($totalSurgeryFee + $roomFee + $pendingLabFee + $paidLabFee + $pendingRadiologyFee + $paidRadiologyFee);

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
                <div class="card-header {{ $isCancelled ? 'bg-danger text-white' : 'bg-primary text-white' }}">
                    <h5 class="mb-0">
                        @if($isCancelled)
                            <i class="fas fa-undo me-2"></i>
                            استرجاع رسوم العملية الجراحية (Refund)
                            <span class="badge bg-white text-danger ms-2">عملية ملغاة</span>
                        @else
                            <i class="fas fa-money-bill-wave me-2"></i>
                            دفع رسوم العملية الجراحية
                            @if($surgery->payment_status === 'partial')
                                <span class="badge bg-warning text-dark ms-2">دفع جزئي سابق</span>
                            @endif
                        @endif
                    </h5>
                </div>
                <div class="card-body">
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

                    <!-- معلومات العملية -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="text-danger mb-3">
                                    <i class="fas fa-procedures me-2"></i>
                                    تفاصيل العملية
                                </h6>
                                <div class="mb-2">
                                    <strong>رقم العملية:</strong> #{{ $surgery->id }}
                                </div>
                                <div class="mb-2">
                                    <strong>نوع العملية:</strong> {{ $surgery->surgery_type }}
                                </div>
                                <div class="mb-2">
                                    <strong>التاريخ المحدد:</strong> {{ $surgery->scheduled_date->format('Y-m-d') }}
                                </div>
                                <div class="mb-2">
                                    <strong>الوقت:</strong> {{ $surgery->scheduled_time->format('H:i') }}
                                </div>
                                <div class="mb-2">
                                    <strong>القسم:</strong> {{ $surgery->department->name }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="text-success mb-3">
                                    <i class="fas fa-user me-2"></i>
                                    معلومات المريض
                                </h6>
                                <div class="mb-2">
                                    <strong>الاسم:</strong> {{ $surgery->patient && $surgery->patient->user ? $surgery->patient->user->name : 'غير محدد' }}
                                </div>
                                <div class="mb-2">
                                    <strong>رقم الهوية:</strong> {{ $surgery->patient ? ($surgery->patient->national_id ?? 'غير محدد') : 'غير محدد' }}
                                </div>
                                <div class="mb-2">
                                    <strong>رقم الهاتف:</strong> {{ $surgery->patient && $surgery->patient->user ? ($surgery->patient->user->phone ?? 'غير محدد') : 'غير محدد' }}
                                </div>
                                <div class="mb-2">
                                    <strong>الطبيب المعالج:</strong> د. {{ $surgery->doctor && $surgery->doctor->user ? $surgery->doctor->user->name : 'غير محدد' }}
                                </div>
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

                    @if(!$isCancelled && $pendingAmount > 0)
                    <!-- نموذج الدفع للعناصر المعلقة -->
                    <form action="{{ route('cashier.surgeries.payment.process', $surgery->id) }}" method="POST" id="paymentForm">
                        @csrf

                        <!-- قسم تحكم الكاشير بالضمان ونسب التحمل الخماسية -->
                        <div class="p-3 mb-4 rounded-3 border" style="background-color: #f8fafc;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-primary mb-0">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    تغطية الضمان ونسبة التحمل (Co-payment) للعملية الجراحية
                                </h6>
                                <span class="badge bg-secondary" id="copayBadge">تحكم مباشر للكاشير</span>
                            </div>

                            <div class="row g-3">
                                <!-- جهة الضمان -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">جهة الضمان / التأمين *</label>
                                    <select class="form-select" id="insurance_type" name="insurance_type">
                                        <option value="none" {{ old('insurance_type', $defaultInsurance) == 'none' ? 'selected' : '' }}>بدون ضمان (دفع نقدي كامل 100%)</option>
                                        <option value="moi" {{ old('insurance_type', $defaultInsurance) == 'moi' ? 'selected' : '' }}>ضمان قوى الأمن الداخلي (وزارة الداخلية)</option>
                                        <option value="hi" {{ old('insurance_type', $defaultInsurance) == 'hi' ? 'selected' : '' }}>هيئة الضمان الصحي الوطني</option>
                                    </select>
                                </div>

                                <!-- رقم بطاقة الضمان -->
                                <div class="col-md-6" id="insurance_card_col">
                                    <label class="form-label fw-bold small text-muted">رقم بطاقة / دفتر الضمان</label>
                                    <input type="text" class="form-control" id="insurance_card_no" name="insurance_card_no" 
                                           value="{{ old('insurance_card_no', $defaultCardNo) }}" placeholder="أدخل رقم الهوية أو الدفتر">
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

                                <!-- أزرار النسب الخماسية ونسبة التحمل -->
                                <div class="col-12" id="copay_section">
                                    <label class="form-label fw-bold small text-muted mb-1">
                                        نسبة التحمل على المريض (Co-payment %)
                                    </label>
                                    
                                    <div class="d-flex flex-wrap align-items-center gap-1 mb-2">
                                        <span class="small text-muted me-2">خيارات خماسية سريعة:</span>
                                        @foreach([0, 5, 10, 15, 20, 25, 30, 50, 100] as $pct)
                                            <button type="button" class="btn btn-sm btn-outline-primary copay-btn" data-pct="{{ $pct }}">{{ $pct }}%</button>
                                        @endforeach
                                    </div>

                                    <div class="input-group" style="max-width: 250px;">
                                        <span class="input-group-text bg-light fw-bold">النسبة المعتمدة:</span>
                                        <input type="number" step="1" min="0" max="100" class="form-control text-center fw-bold" 
                                               id="copay_percentage" name="copay_percentage" value="{{ old('copay_percentage', $defaultCopay) }}">
                                        <span class="input-group-text bg-light fw-bold">%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- بطاقة معاينة الحسبة المالية المباشرة -->
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

                        <!-- خيار الشمولية -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="inclusive" name="inclusive" {{ old('inclusive') ? 'checked' : '' }}>
                            <label class="form-check-label" for="inclusive">
                                ✅ <strong>شاملة</strong> – التكلفة تشمل جميع المصاريف المسبقة (التحاليل، الغرفة، الأشعة، ...)
                            </label>
                        </div>
                        <small class="text-muted">عند تفعيل هذا الخيار ستُحتسب فقط رسوم العملية وسيتم اعتباره كغطاء كامل للتكاليف الأخرى.</small>
                    </div>

                    <!-- العناصر المعلقة للدفع -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-warning mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    العناصر المعلقة - اختر ما تريد دفعه الآن
                                </h6>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-success me-2" id="selectAllBtn">
                                        <i class="fas fa-check-double me-1"></i>
                                        تحديد الكل
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllBtn">
                                        <i class="fas fa-times me-1"></i>
                                        إلغاء الكل
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-warning">
                                        <tr>
                                            <th style="width: 50px;" class="text-center">
                                                <i class="fas fa-check-square"></i>
                                            </th>
                                            <th>البند</th>
                                            <th>التفاصيل</th>
                                            <th class="text-end">التكلفة (IQD)</th>
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
                                                    <strong>
                                                        <i class="fas fa-flask me-2"></i>
                                                        التحاليل المعلقة ({{ $pendingLabTests->count() }})
                                                    </strong>
                                                </td>
                                            </tr>
                                            @foreach($pendingLabTests as $labTest)
                                                @php
                                                    $lModel = $labTest->labTest;
                                                    $lBase = (float)($lModel ? $lModel->getRegularPrice() : 0);
                                                    $lMoi = (float)($lModel && $lModel->moi_price > 0 ? $lModel->moi_price : $lBase);
                                                    $lHi = (float)($lModel && $lModel->hi_price > 0 ? $lModel->hi_price : $lBase);
                                                    $lMoiActive = (bool)($lModel ? ($lModel->is_moi_active ?? true) : true);
                                                    $lHiActive = (bool)($lModel ? ($lModel->is_hi_active ?? true) : true);
                                                @endphp
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="checkbox" 
                                                               class="form-check-input payment-item lab-test-item" 
                                                               name="pay_lab_tests[]" 
                                                               value="{{ $labTest->id }}"
                                                               data-base-price="{{ $lBase }}"
                                                               data-moi-price="{{ $lMoi }}"
                                                               data-hi-price="{{ $lHi }}"
                                                               data-is-moi-active="{{ $lMoiActive ? '1' : '0' }}"
                                                               data-is-hi-active="{{ $lHiActive ? '1' : '0' }}"
                                                               checked>
                                                    </td>
                                                    <td class="ps-4">
                                                        <i class="fas fa-vial text-primary me-2"></i>
                                                        تحليل
                                                    </td>
                                                    <td>{{ $lModel->name ?? 'غير محدد' }} ({{ $lModel->code ?? '-' }})</td>
                                                    <td class="text-end item-price-display">{{ number_format($lBase, 0) }}</td>
                                                </tr>
                                            @endforeach
                                            <tr class="table-light">
                                                <td></td>
                                                <td colspan="2" class="text-end"><strong>مجموع التحاليل المعلقة:</strong></td>
                                                <td class="text-end"><strong id="pending_lab_fee_display">{{ number_format($pendingLabFee, 0) }}</strong></td>
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
                                                    <strong>
                                                        <i class="fas fa-x-ray me-2"></i>
                                                        الفحوصات الإشعاعية المعلقة ({{ $pendingRadiologyTests->count() }})
                                                    </strong>
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
                                                <tr>
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
                                                               checked>
                                                    </td>
                                                    <td class="ps-4">
                                                        <i class="fas fa-radiation text-info me-2"></i>
                                                        أشعة
                                                    </td>
                                                    <td>{{ $rModel->name ?? 'غير محدد' }}</td>
                                                    <td class="text-end item-price-display">{{ number_format($rBase, 0) }}</td>
                                                </tr>
                                            @endforeach
                                            <tr class="table-light">
                                                <td></td>
                                                <td colspan="2" class="text-end"><strong>مجموع الأشعة المعلقة:</strong></td>
                                                <td class="text-end"><strong id="pending_radiology_fee_display">{{ number_format($pendingRadiologyFee, 0) }}</strong></td>
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
                                                   value="cash" {{ old('payment_method', 'cash') == 'cash' ? 'checked' : '' }} required>
                                            <label class="form-check-label fw-semibold" for="surgery_payment_cash">
                                                💵 نقدي (Cash)
                                            </label>
                                        </div>
                                        <div class="form-check form-check-lg mb-2">
                                            <input class="form-check-input" type="radio" name="payment_method" id="surgery_payment_card" 
                                                   value="card" {{ old('payment_method') == 'card' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="surgery_payment_card">
                                                💳 بطاقة ائتمان (Card)
                                            </label>
                                        </div>
                                        <div class="form-check form-check-lg">
                                            <input class="form-check-input" type="radio" name="payment_method" id="surgery_payment_insurance" 
                                                   value="insurance" {{ old('payment_method') == 'insurance' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="surgery_payment_insurance">
                                                🏥 تأمين صحي (Insurance)
                                            </label>
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

    const hiCategoryCopay = @json($patient ? $patient->getCopayPercentageFor('surgery') : 15.0);
    const moiCopay = @json((float)($patient->copay_percentage ?? 15.0));
    const hiBadge = document.getElementById('patient_hi_category_badge');

    function calculateSelectedAmount() {
        let selectedAmount = 0;
        let selectedCount = 0;

        const isIncl = inclusiveCheckbox && inclusiveCheckbox.checked;

        if (isIncl) {
            // 1. Surgery Fee portion
            const paySurgeryCheckbox = document.getElementById('pay_surgery_checkbox');
            if (paySurgeryCheckbox && paySurgeryCheckbox.checked) {
                const payFull = document.getElementById('surgery_pay_full') ? document.getElementById('surgery_pay_full').checked : true;
                if (payFull) {
                    selectedAmount += remainingSurgeryFee;
                    const custWrapper = document.getElementById('surgery_custom_amount_wrapper');
                    const custInput = document.getElementById('surgery_custom_amount');
                    const feeDisp = document.getElementById('surgery_fee_display');
                    if (custInput) custInput.value = Math.round(remainingSurgeryFee);
                    if (custWrapper) custWrapper.style.display = 'none';
                    if (feeDisp) feeDisp.textContent = numberFormat(remainingSurgeryFee);
                } else {
                    const custWrapper = document.getElementById('surgery_custom_amount_wrapper');
                    const custInput = document.getElementById('surgery_custom_amount');
                    const feeDisp = document.getElementById('surgery_fee_display');
                    if (custWrapper) custWrapper.style.display = 'block';
                    let customVal = parseFloat(custInput ? custInput.value : 0) || 0;
                    if (customVal > remainingSurgeryFee) {
                        customVal = remainingSurgeryFee;
                        if (custInput) custInput.value = Math.round(remainingSurgeryFee);
                    }
                    selectedAmount += customVal;
                    if (feeDisp) feeDisp.textContent = numberFormat(customVal);
                }
                selectedCount++;
            }
        } else {
            // Standard non-inclusive mode:
            // 1. Surgery Fee
            const paySurgeryCheckbox = document.getElementById('pay_surgery_checkbox');
            if (paySurgeryCheckbox && paySurgeryCheckbox.checked) {
                const payFull = document.getElementById('surgery_pay_full') ? document.getElementById('surgery_pay_full').checked : true;
                const custWrapper = document.getElementById('surgery_custom_amount_wrapper');
                const custInput = document.getElementById('surgery_custom_amount');
                const feeDisp = document.getElementById('surgery_fee_display');
                if (payFull) {
                    selectedAmount += remainingSurgeryFee;
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
                    selectedAmount += customVal;
                    if (feeDisp) feeDisp.textContent = numberFormat(customVal);
                }
                selectedCount++;
            } else if (paySurgeryCheckbox) {
                const custWrapper = document.getElementById('surgery_custom_amount_wrapper');
                const feeDisp = document.getElementById('surgery_fee_display');
                if (custWrapper) custWrapper.style.display = 'none';
                if (feeDisp) feeDisp.textContent = '0';
            }

            // 2. Room Fee
            const payRoomCheckbox = document.getElementById('pay_room_checkbox');
            if (payRoomCheckbox && payRoomCheckbox.checked) {
                const payFull = document.getElementById('room_pay_full') ? document.getElementById('room_pay_full').checked : true;
                const custWrapper = document.getElementById('room_custom_amount_wrapper');
                const custInput = document.getElementById('room_custom_amount');
                const feeDisp = document.getElementById('room_fee_display');
                if (payFull) {
                    selectedAmount += remainingRoomFee;
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
                    selectedAmount += customVal;
                    if (feeDisp) feeDisp.textContent = numberFormat(customVal);
                }
                selectedCount++;
            } else if (payRoomCheckbox) {
                const custWrapper = document.getElementById('room_custom_amount_wrapper');
                const feeDisp = document.getElementById('room_fee_display');
                if (custWrapper) custWrapper.style.display = 'none';
                if (feeDisp) feeDisp.textContent = '0';
            }

            // 3. Lab and Radiology tests
            const currentInsType = insuranceTypeSelect ? insuranceTypeSelect.value : 'none';
            document.querySelectorAll('.payment-item:checked').forEach(function(item) {
                if (item.id !== 'pay_surgery_checkbox' && item.id !== 'pay_room_checkbox') {
                    const basePrice = parseFloat(item.dataset.basePrice) || 0;
                    const moiPrice = parseFloat(item.dataset.moiPrice) || basePrice;
                    const hiPrice = parseFloat(item.dataset.hiPrice) || basePrice;
                    const isMoiActive = item.dataset.isMoiActive === '1';
                    const isHiActive = item.dataset.isHiActive === '1';

                    let itemApproved = basePrice;
                    if (currentInsType === 'moi' && isMoiActive) {
                        itemApproved = moiPrice > 0 ? moiPrice : basePrice;
                    } else if (currentInsType === 'hi' && isHiActive) {
                        itemApproved = hiPrice > 0 ? hiPrice : basePrice;
                    }

                    selectedAmount += itemApproved;
                    selectedCount++;
                }
            });
        }

        // Update row price displays for all items
        const insType = insuranceTypeSelect ? insuranceTypeSelect.value : 'none';
        let currentLabTotal = 0;
        let currentRadTotal = 0;
        document.querySelectorAll('.payment-item').forEach(function(item) {
            if (item.id !== 'pay_surgery_checkbox' && item.id !== 'pay_room_checkbox') {
                const basePrice = parseFloat(item.dataset.basePrice) || 0;
                const moiPrice = parseFloat(item.dataset.moiPrice) || basePrice;
                const hiPrice = parseFloat(item.dataset.hiPrice) || basePrice;
                const isMoiActive = item.dataset.isMoiActive === '1';
                const isHiActive = item.dataset.isHiActive === '1';

                let itemApproved = basePrice;
                if (insType === 'moi' && isMoiActive) {
                    itemApproved = moiPrice > 0 ? moiPrice : basePrice;
                } else if (insType === 'hi' && isHiActive) {
                    itemApproved = hiPrice > 0 ? hiPrice : basePrice;
                }

                const priceCell = item.closest('tr')?.querySelector('.item-price-display');
                if (priceCell) {
                    priceCell.textContent = numberFormat(itemApproved);
                }

                if (item.classList.contains('lab-test-item')) {
                    currentLabTotal += itemApproved;
                } else if (item.classList.contains('radiology-test-item')) {
                    currentRadTotal += itemApproved;
                }
            }
        });

        const labTotalDisplay = document.getElementById('pending_lab_fee_display');
        if (labTotalDisplay) labTotalDisplay.textContent = numberFormat(currentLabTotal);
        const radTotalDisplay = document.getElementById('pending_radiology_fee_display');
        if (radTotalDisplay) radTotalDisplay.textContent = numberFormat(currentRadTotal);

        let deferredAmount = pendingAmount - selectedAmount;
        if (isIncl) {
            deferredAmount = 0; // inclusive covers everything else
        }

        if (deferredAmount < 0) deferredAmount = 0;

        // حساب حصة الضمان والمريض
        let copayPct = 100;
        if (insType !== 'none') {
            copayPct = parseFloat(copayInput ? copayInput.value : 15) || 0;
        }

        let patientShare = selectedAmount;
        let insuranceShare = 0;

        if (insType !== 'none') {
            patientShare = Math.round(selectedAmount * (copayPct / 100));
            insuranceShare = Math.max(0, selectedAmount - patientShare);
        }

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

        if (dispApproved) dispApproved.textContent = numberFormat(selectedAmount) + ' د.ع';
        if (dispPatient) dispPatient.textContent = numberFormat(patientShare) + ' د.ع';
        if (dispInsurance) dispInsurance.textContent = numberFormat(insuranceShare) + ' د.ع';
        if (dispSelected) dispSelected.textContent = numberFormat(selectedAmount) + ' IQD';
        if (dispDeferred) dispDeferred.textContent = numberFormat(deferredAmount) + ' IQD';
        if (inputAmount) inputAmount.value = patientShare;
        if (submitAmountSpan) submitAmountSpan.textContent = '(' + numberFormat(patientShare) + ' IQD)';
        if (selectedCountSpan) selectedCountSpan.textContent = selectedCount;

        if (deferredRow) {
            deferredRow.style.display = deferredAmount > 0 ? '' : 'none';
        }

        if (submitBtn) {
            submitBtn.disabled = selectedCount === 0;
        }

        updateGroupCheckbox('lab-test-item', 'selectAllLab');
        updateGroupCheckbox('radiology-test-item', 'selectAllRadiology');
    }

    function updateCopayUI() {
        const insType = insuranceTypeSelect ? insuranceTypeSelect.value : 'none';
        if (insType === 'moi') {
            if (insuranceCardCol) insuranceCardCol.style.display = 'block';
            if (copaySection) copaySection.style.display = 'block';
            if (hiBadge) hiBadge.style.display = 'none';
            if (copayBadge) {
                copayBadge.className = 'badge bg-primary';
                copayBadge.textContent = 'ضمان الداخلية';
            }
        } else if (insType === 'hi') {
            if (insuranceCardCol) insuranceCardCol.style.display = 'block';
            if (copaySection) copaySection.style.display = 'block';
            if (hiBadge) hiBadge.style.display = 'block';
            if (copayBadge) {
                copayBadge.className = 'badge bg-info text-dark';
                copayBadge.textContent = 'الضمان الصحي الوطني';
            }
        } else {
            if (insuranceCardCol) insuranceCardCol.style.display = 'none';
            if (copaySection) copaySection.style.display = 'none';
            if (hiBadge) hiBadge.style.display = 'none';
            if (copayBadge) {
                copayBadge.className = 'badge bg-secondary';
                copayBadge.textContent = 'دفع نقدي كامل (100%)';
            }
        }
        calculateSelectedAmount();
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

    updateCopayUI();

    function toggleInclusive() {
        const isIncl = inclusiveCheckbox && inclusiveCheckbox.checked;
        document.querySelectorAll('tbody tr').forEach(function(row) {
            const item = row.querySelector('.payment-item');
            if (item) {
                if (item.id !== 'pay_surgery_checkbox') {
                    row.style.display = isIncl ? 'none' : '';
                } else {
                    row.style.display = '';
                }
            }
        });
        calculateSelectedAmount();
    }

    inclusiveCheckbox && inclusiveCheckbox.addEventListener('change', toggleInclusive);
    if (inclusiveCheckbox && inclusiveCheckbox.checked) {
        toggleInclusive();
    }

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
        if (selectAllLab) selectAllLab.checked = true;
        if (selectAllRadiology) selectAllRadiology.checked = true;
        calculateSelectedAmount();
    }

    function deselectAll() {
        document.querySelectorAll('.payment-item').forEach(function(item) {
            item.checked = false;
        });
        const selectAllLab = document.getElementById('selectAllLab');
        const selectAllRadiology = document.getElementById('selectAllRadiology');
        if (selectAllLab) selectAllLab.checked = false;
        if (selectAllRadiology) selectAllRadiology.checked = false;
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

    // إضافة مستمعي الأحداث
    document.getElementById('selectAllBtn').addEventListener('click', selectAll);
    document.getElementById('deselectAllBtn').addEventListener('click', deselectAll);

    // مستمعي أحداث checkboxes المجموعات
    const selectAllLab = document.getElementById('selectAllLab');
    const selectAllRadiology = document.getElementById('selectAllRadiology');

    if (selectAllLab) {
        selectAllLab.addEventListener('change', toggleLabTests);
    }

    if (selectAllRadiology) {
        selectAllRadiology.addEventListener('change', toggleRadiologyTests);
    }

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

    // حساب أولي
    calculateSelectedAmount();
});
</script>
@endif
@endsection
