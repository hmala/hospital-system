@extends('layouts.app')

@section('content')
@php
    // حساب التكاليف مع تتبع حالة الدفع
    $surgeryFee = $surgery->surgery_fee ?? 0; // pull from surgery record, not operation model
    $surgeryFeePaid = $surgery->surgery_fee_paid === 'paid';
    
    // رسوم الغرفة (أول ليلة مجانية)
    $roomFee = $surgery->room_fee ?? 0;
    $roomFeePaid = $surgery->payment_status === 'paid'; // يمكن تعديله حسب نظام تتبع دفع الغرفة
    
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
    
    // المبالغ (تشمل رسوم الغرفة)
    $pendingAmount = ($surgeryFeePaid ? 0 : $surgeryFee) + ($roomFeePaid ? 0 : $roomFee) + $pendingLabFee + $pendingRadiologyFee;
    $paidAmount = ($surgeryFeePaid ? $surgeryFee : 0) + ($roomFeePaid ? $roomFee : 0) + $paidLabFee + $paidRadiologyFee;
    $totalAmount = $surgeryFee + $roomFee + $pendingLabFee + $paidLabFee + $pendingRadiologyFee + $paidRadiologyFee;
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
                                    <strong>الاسم:</strong> {{ $surgery->patient->user->name }}
                                </div>
                                <div class="mb-2">
                                    <strong>رقم الهوية:</strong> {{ $surgery->patient->national_id ?? 'غير محدد' }}
                                </div>
                                <div class="mb-2">
                                    <strong>رقم الهاتف:</strong> {{ $surgery->patient->user->phone ?? 'غير محدد' }}
                                </div>
                                <div class="mb-2">
                                    <strong>الطبيب المعالج:</strong> د. {{ $surgery->doctor->user->name }}
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
                                    @if($surgeryFeePaid)
                                    <tr class="table-success">
                                        <td>
                                            <i class="fas fa-procedures text-success me-2"></i>
                                            رسوم العملية الجراحية
                                        </td>
                                        <td>{{ $surgery->surgery_type }}</td>
                                        <td class="text-end">{{ number_format($surgeryFee, 0) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>مدفوع</span>
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
                                        <!-- رسوم العملية (إذا لم تُدفع) -->
                                        @if(!$surgeryFeePaid && $surgeryFee > 0)
                                        <tr class="table-light">
                                            <td class="text-center">
                                                <input type="checkbox" 
                                                       class="form-check-input payment-item" 
                                                       name="pay_surgery" 
                                                       value="1"
                                                       data-amount="{{ $surgeryFee }}"
                                                       checked>
                                            </td>
                                            <td>
                                                <i class="fas fa-procedures text-danger me-2"></i>
                                                <strong>رسوم العملية الجراحية</strong>
                                            </td>
                                            <td>{{ $surgery->surgery_type }}</td>
                                            <td class="text-end">
                                                <strong>{{ number_format($surgeryFee, 0) }}</strong>
                                            </td>
                                        </tr>
                                        @endif

                                        <!-- رسوم الغرفة (إذا لم تُدفع) - حتى لو كانت مجانية لعرض الملاحظة -->
                                        @if(!$roomFeePaid && $surgery->room_id)
                                        <tr class="table-primary">
                                            <td class="text-center">
                                                <input type="checkbox" 
                                                       class="form-check-input payment-item" 
                                                       name="pay_room" 
                                                       value="1"
                                                       data-amount="{{ $roomFee }}"
                                                       checked>
                                            </td>
                                            <td>
                                                <i class="fas fa-door-open text-info me-2"></i>
                                                <strong>أجور الغرفة</strong>
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
                                                @if($surgery->expected_stay_days)
                                                    <br><small class="text-muted">
                                                        {{ $surgery->expected_stay_days }} يوم × {{ number_format($surgery->room->daily_fee ?? 0, 0) }} د.ع
                                                    </small>
                                                    <br><small class="text-muted">(أول ليلة مجانية)</small>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <strong>{{ number_format($roomFee, 0) }}
                                                @if($surgery->expected_stay_days && $surgery->room)
                                                    <br><small class="text-muted">التكلفة الفعلية {{ number_format($surgery->room->daily_fee * $surgery->expected_stay_days, 0) }} د.ع</small>
                                                @endif
                                                </strong>
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
    const surgeryFee = {{ $surgeryFee }};
    const inclusiveCheckbox = document.getElementById('inclusive');

    function calculateSelectedAmount() {
        let selectedAmount = 0;
        let selectedCount = 0;

        if (inclusiveCheckbox && inclusiveCheckbox.checked) {
            selectedAmount = surgeryFee;
            selectedCount = 1;
        } else {
            document.querySelectorAll('.payment-item:checked').forEach(function(item) {
                selectedAmount += parseFloat(item.dataset.amount) || 0;
                selectedCount++;
            });
        }

        let deferredAmount = pendingAmount - selectedAmount;
        if (inclusiveCheckbox && inclusiveCheckbox.checked) {
            deferredAmount = 0; // inclusive covers everything
        }

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
        const isIncl = inclusiveCheckbox.checked;
        document.querySelectorAll('tbody tr').forEach(function(row) {
            if (row.querySelector('.payment-item')) {
                row.style.display = isIncl ? 'none' : '';
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

    // حساب أولي
    calculateSelectedAmount();
});
</script>    if (selectAllRadiology) {
        selectAllRadiology.addEventListener('change', toggleRadiologyTests);
    }
    
    // إضافة مستمعي الأحداث لجميع checkboxes العناصر
    document.querySelectorAll('.payment-item').forEach(function(item) {
        item.addEventListener('change', calculateSelectedAmount);
    });
    
    // حساب المبلغ عند تحميل الصفحة
    calculateSelectedAmount();
});
</script>
@endif
@endsection
