@extends('layouts.app')

@section('content')
@php
    // حساب التكاليف مع تتبع حالة الدفع
    $surgeryFee = $surgery->surgery_fee ?? 0;
    $additionalOpsFee = $surgery->additionalOperations->sum('fee');
    $totalSurgeryFee = $surgeryFee + $additionalOpsFee;
    $surgeryFeePaidAmount = $surgery->surgery_fee_paid_amount ?? 0;
    $remainingSurgeryFee = max(0, $totalSurgeryFee - $surgeryFeePaidAmount);
    $surgeryFeePaid = $surgery->surgery_fee_paid === 'paid' || $remainingSurgeryFee <= 0;
    
    // رسوم الغرفة (أول ليلة مجانية)
    $roomFee = $surgery->room_fee ?? 0;
    $roomFeePaidAmount = $surgery->room_fee_paid_amount ?? 0;
    $remainingRoomFee = max(0, $roomFee - $roomFeePaidAmount);
    $roomFeePaid = $remainingRoomFee <= 0;
    
    // تحاليل معلقة ومدفوعة
    $pendingLabTests = $surgery->labTests->where('payment_status', '!=', 'paid');
    $paidLabTests = $surgery->labTests->where('payment_status', 'paid');
    $pendingLabFee = $pendingLabTests->sum(function($test) {
        return $test->labTest->price ?? 0;
    });
    $paidLabFee = $paidLabTests->sum(function($test) {
        return $test->labTest->price ?? 0;
    });
    
    // أشعة معلقة ومدفوعة
    $pendingRadiologyTests = $surgery->radiologyTests->where('payment_status', '!=', 'paid');
    $paidRadiologyTests = $surgery->radiologyTests->where('payment_status', 'paid');
    $pendingRadiologyFee = $pendingRadiologyTests->sum(function($test) {
        return $test->radiologyType->base_price ?? 0;
    });
    $paidRadiologyFee = $paidRadiologyTests->sum(function($test) {
        return $test->radiologyType->base_price ?? 0;
    });
    
    // المبالغ (تشمل رسوم الغرفة وتعتمد على المبالغ المدفوعة جزئياً)
    $pendingAmount = $remainingSurgeryFee + $remainingRoomFee + $pendingLabFee + $pendingRadiologyFee;
    $paidAmount = $surgeryFeePaidAmount + $roomFeePaidAmount + $paidLabFee + $paidRadiologyFee;
    $totalAmount = $totalSurgeryFee + $roomFee + $pendingLabFee + $paidLabFee + $pendingRadiologyFee + $paidRadiologyFee;
@endphp

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-11">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        دفع رسوم العملية الجراحية
                        @if($surgery->payment_status === 'partial')
                            <span class="badge bg-warning ms-2">دفع جزئي سابق</span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
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

                    @if($pendingAmount > 0)
                    <!-- نموذج الدفع للعناصر المعلقة -->
                    <form action="{{ route('cashier.surgeries.payment.process', $surgery->id) }}" method="POST" id="paymentForm">
                        @csrf

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
                                                    @if($surgery->room && $surgery->room->room_type === 'vip')
                                                        <span class="badge bg-warning text-dark ms-1">
                                                            <i class="fas fa-star"></i> VIP
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary ms-1">عادية</span>
                                                    @endif
                                                    @if($surgery->expected_stay_days)
                                                        <br><small class="text-muted">
                                                            {{ $surgery->expected_stay_days }} يوم × {{ number_format($surgery->room->daily_fee ?? 0, 0) }} د.ع
                                                        </small>
                                                        <br><small class="text-muted">(أول ليلة مجانية)</small>
                                                    @endif
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
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="checkbox" 
                                                               class="form-check-input payment-item lab-test-item" 
                                                               name="pay_lab_tests[]" 
                                                               value="{{ $labTest->id }}"
                                                               data-amount="{{ $labTest->labTest->price ?? 0 }}"
                                                               checked>
                                                    </td>
                                                    <td class="ps-4">
                                                        <i class="fas fa-vial text-primary me-2"></i>
                                                        تحليل
                                                    </td>
                                                    <td>{{ $labTest->labTest->name ?? 'غير محدد' }} ({{ $labTest->labTest->code ?? '-' }})</td>
                                                    <td class="text-end">{{ number_format($labTest->labTest->price ?? 0, 0) }}</td>
                                                </tr>
                                            @endforeach
                                            <tr class="table-light">
                                                <td></td>
                                                <td colspan="2" class="text-end"><strong>مجموع التحاليل المعلقة:</strong></td>
                                                <td class="text-end"><strong>{{ number_format($pendingLabFee, 0) }}</strong></td>
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
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="checkbox" 
                                                               class="form-check-input payment-item radiology-test-item" 
                                                               name="pay_radiology_tests[]" 
                                                               value="{{ $radiologyTest->id }}"
                                                               data-amount="{{ $radiologyTest->radiologyType->base_price ?? 0 }}"
                                                               checked>
                                                    </td>
                                                    <td class="ps-4">
                                                        <i class="fas fa-radiation text-info me-2"></i>
                                                        أشعة
                                                    </td>
                                                    <td>{{ $radiologyTest->radiologyType->name ?? 'غير محدد' }}</td>
                                                    <td class="text-end">{{ number_format($radiologyTest->radiologyType->base_price ?? 0, 0) }}</td>
                                                </tr>
                                            @endforeach
                                            <tr class="table-light">
                                                <td></td>
                                                <td colspan="2" class="text-end"><strong>مجموع الأشعة المعلقة:</strong></td>
                                                <td class="text-end"><strong>{{ number_format($pendingRadiologyFee, 0) }}</strong></td>
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
                    @else
                    <!-- لا توجد عناصر معلقة -->
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h5>تم دفع جميع رسوم هذه العملية!</h5>
                        <p class="mb-0">لا توجد مبالغ معلقة للدفع.</p>
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

    function calculateSelectedAmount() {
        let selectedAmount = 0;
        let selectedCount = 0;

        const isIncl = inclusiveCheckbox && inclusiveCheckbox.checked;

        if (isIncl) {
            // 1. Surgery Fee portion
            const paySurgeryCheckbox = document.getElementById('pay_surgery_checkbox');
            if (paySurgeryCheckbox && paySurgeryCheckbox.checked) {
                const payFull = document.getElementById('surgery_pay_full').checked;
                if (payFull) {
                    selectedAmount += remainingSurgeryFee;
                    document.getElementById('surgery_custom_amount').value = Math.round(remainingSurgeryFee);
                    document.getElementById('surgery_custom_amount_wrapper').style.display = 'none';
                    document.getElementById('surgery_fee_display').textContent = numberFormat(remainingSurgeryFee);
                } else {
                    document.getElementById('surgery_custom_amount_wrapper').style.display = 'block';
                    let customVal = parseFloat(document.getElementById('surgery_custom_amount').value) || 0;
                    if (customVal > remainingSurgeryFee) {
                        customVal = remainingSurgeryFee;
                        document.getElementById('surgery_custom_amount').value = Math.round(remainingSurgeryFee);
                    }
                    selectedAmount += customVal;
                    document.getElementById('surgery_fee_display').textContent = numberFormat(customVal);
                }
                selectedCount++;
            }
        } else {
            // Standard non-inclusive mode:
            // 1. Surgery Fee
            const paySurgeryCheckbox = document.getElementById('pay_surgery_checkbox');
            if (paySurgeryCheckbox && paySurgeryCheckbox.checked) {
                const payFull = document.getElementById('surgery_pay_full').checked;
                if (payFull) {
                    selectedAmount += remainingSurgeryFee;
                    document.getElementById('surgery_custom_amount').value = Math.round(remainingSurgeryFee);
                    document.getElementById('surgery_custom_amount_wrapper').style.display = 'none';
                    document.getElementById('surgery_fee_display').textContent = numberFormat(remainingSurgeryFee);
                } else {
                    document.getElementById('surgery_custom_amount_wrapper').style.display = 'block';
                    let customVal = parseFloat(document.getElementById('surgery_custom_amount').value) || 0;
                    if (customVal > remainingSurgeryFee) {
                        customVal = remainingSurgeryFee;
                        document.getElementById('surgery_custom_amount').value = Math.round(remainingSurgeryFee);
                    }
                    selectedAmount += customVal;
                    document.getElementById('surgery_fee_display').textContent = numberFormat(customVal);
                }
                selectedCount++;
            } else if (paySurgeryCheckbox) {
                document.getElementById('surgery_custom_amount_wrapper').style.display = 'none';
                document.getElementById('surgery_fee_display').textContent = '0';
            }

            // 2. Room Fee
            const payRoomCheckbox = document.getElementById('pay_room_checkbox');
            if (payRoomCheckbox && payRoomCheckbox.checked) {
                const payFull = document.getElementById('room_pay_full').checked;
                if (payFull) {
                    selectedAmount += remainingRoomFee;
                    document.getElementById('room_custom_amount').value = Math.round(remainingRoomFee);
                    document.getElementById('room_custom_amount_wrapper').style.display = 'none';
                    document.getElementById('room_fee_display').textContent = numberFormat(remainingRoomFee);
                } else {
                    document.getElementById('room_custom_amount_wrapper').style.display = 'block';
                    let customVal = parseFloat(document.getElementById('room_custom_amount').value) || 0;
                    if (customVal > remainingRoomFee) {
                        customVal = remainingRoomFee;
                        document.getElementById('room_custom_amount').value = Math.round(remainingRoomFee);
                    }
                    selectedAmount += customVal;
                    document.getElementById('room_fee_display').textContent = numberFormat(customVal);
                }
                selectedCount++;
            } else if (payRoomCheckbox) {
                document.getElementById('room_custom_amount_wrapper').style.display = 'none';
                document.getElementById('room_fee_display').textContent = '0';
            }

            // 3. Lab and Radiology tests
            document.querySelectorAll('.payment-item:checked').forEach(function(item) {
                if (item.id !== 'pay_surgery_checkbox' && item.id !== 'pay_room_checkbox') {
                    selectedAmount += parseFloat(item.dataset.amount) || 0;
                    selectedCount++;
                }
            });
        }

        let deferredAmount = pendingAmount - selectedAmount;
        if (isIncl) {
            deferredAmount = 0; // inclusive covers everything else
        }

        if (deferredAmount < 0) deferredAmount = 0;

        document.getElementById('selectedAmount').textContent = numberFormat(selectedAmount) + ' IQD';
        document.getElementById('deferredAmount').textContent = numberFormat(deferredAmount) + ' IQD';
        document.getElementById('amountInput').value = selectedAmount;
        document.getElementById('submitAmount').textContent = '(' + numberFormat(selectedAmount) + ' IQD)';
        document.getElementById('selectedItemsCount').textContent = selectedCount;

        if (deferredAmount > 0) {
            document.getElementById('deferredRow').style.display = '';
        } else {
            document.getElementById('deferredRow').style.display = 'none';
        }

        document.getElementById('submitBtn').disabled = selectedCount === 0;

        updateGroupCheckbox('lab-test-item', 'selectAllLab');
        updateGroupCheckbox('radiology-test-item', 'selectAllRadiology');
    }

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
