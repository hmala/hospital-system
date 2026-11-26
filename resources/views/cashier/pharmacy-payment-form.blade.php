@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <!-- تنبيه الخطأ إن وجد -->
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-3 mb-3" role="alert">
            <i class="fas fa-times-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $patient = $sale->patient;
        $prescription = $sale->prescription;
        $doctor = $prescription?->doctor;
        $patientName = $sale->patient_name ?? optional($patient?->user)->name ?? 'مريض مباشر OTC';
        $insuranceType = $sale->insurance_type ?? 'none';
        $totalAmount = (float) $sale->total_amount;
        $patientShare = (float) $sale->patient_share;
        $insuranceShare = (float) $sale->insurance_share;
        $copayPercent = (float) ($sale->copay_percentage ?? 0);
    @endphp

    <!-- شريط معلومات الفاتورة والمريض العلوي -->
    <div class="card border-0 shadow-sm rounded-4 mb-3" style="background: linear-gradient(135deg, #065f46 0%, #064e3b 100%); color: #fff;">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <!-- المريض -->
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white text-success d-flex align-items-center justify-content-center shadow-sm" style="width: 52px; height: 52px; font-size: 1.4rem;">
                        <i class="fas fa-pills"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h4 class="fw-bold mb-0 text-white">{{ $patientName }}</h4>
                            <span class="badge bg-white text-success px-2 py-1 rounded-pill fw-bold font-monospace">{{ $sale->invoice_number }}</span>
                        </div>
                        <div class="text-white-50 small d-flex flex-wrap gap-3">
                            <span><i class="fas fa-id-card me-1"></i>{{ $patient?->national_id ?? __('بدون رقم هوية') }}</span>
                            <span><i class="fas fa-phone me-1"></i>{{ $sale->patient_phone ?? optional($patient?->user)->phone ?? __('بدون هاتف') }}</span>
                            <span><i class="fas fa-clock me-1"></i>{{ $sale->created_at->format('Y-m-d H:i') }}</span>
                        </div>
                    </div>
                </div>

                <!-- الطبيب أو نوع الصرف -->
                <div class="d-flex align-items-center gap-3 bg-white bg-opacity-10 p-2 px-3 rounded-3">
                    <div class="text-end">
                        <span class="badge bg-success mb-1">
                            {{ $sale->sale_type === 'inpatient' ? __('أدوية رقود: غرفة '.($sale->surgery?->room?->room_number ?? 'الردهة')) : ($sale->sale_type === 'prescription' ? __('وصفة طبية معتمدة') : ($sale->sale_type === 'emergency' ? __('طوارئ وإسعاف') : __('صرف مباشر OTC'))) }}
                        </span>
                        <div class="fw-bold text-white">
                            @if($doctor && $doctor->user)
                                {{ __('د.') }} {{ $doctor->user->name }}
                            @else
                                {{ __('صيدلية المستشفى') }}
                            @endif
                        </div>
                        <small class="text-white-50">
                            @if($insuranceType === 'health_insurance')
                                <i class="fas fa-shield-alt text-warning me-1"></i>{{ __('ضمان صحي') }} ({{ (float)$copayPercent }}%)
                            @elseif($insuranceType === 'interior_ministry')
                                <i class="fas fa-shield-alt text-info me-1"></i>{{ __('وزارة الداخلية') }}
                            @else
                                <i class="fas fa-money-bill-wave me-1"></i>{{ __('نقدي (كاش)') }}
                            @endif
                        </small>
                    </div>
                    <div class="rounded-circle bg-white bg-opacity-20 text-white d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.2rem;">
                        <i class="fas fa-prescription"></i>
                    </div>
                </div>

                <!-- زر الرجوع -->
                <a href="{{ route('cashier.index') }}" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fas fa-arrow-right me-1"></i> {{ __("لوحة الكاشير") }}
                </a>
            </div>
        </div>
    </div>

    <!-- نموذج الدفع الرئيسي -->
    <form method="POST" action="{{ route('cashier.pharmacy.payment.process', $sale->id) }}" id="pharmacyPaymentForm">
        @csrf

        <div class="row g-3">
            <!-- التفاصيل المالية والأدوية -->
            <div class="col-lg-8">
                <!-- شريط الأرقام المالية الثلاثية -->
                <div class="row g-3 mb-3 text-center">
                    <div class="col-md-4">
                        <div class="p-3 rounded-4 border bg-white shadow-sm">
                            <span class="text-muted small d-block mb-1">{{ __("إجمالي الفاتورة") }}</span>
                            <h4 class="fw-bold mb-0 text-dark font-monospace">{{ number_format($totalAmount) }} <small class="fs-6 text-muted">{{ __("د.ع") }}</small></h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-4 border bg-primary bg-opacity-10 border-primary shadow-sm">
                            <span class="text-primary small d-block mb-1">{{ __("حصة التأمين / الضمان") }}</span>
                            <h4 class="fw-bold mb-0 text-primary font-monospace">{{ number_format($insuranceShare) }} <small class="fs-6 text-muted">{{ __("د.ع") }}</small></h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-4 border bg-success bg-opacity-10 border-success shadow-sm">
                            <span class="text-success small d-block mb-1 fw-bold">{{ __("المطلوب دفعه من المريض") }}</span>
                            <h4 class="fw-bold mb-0 text-success font-monospace" id="displayPatientShare">{{ number_format($patientShare) }} <small class="fs-6 text-muted">{{ __("د.ع") }}</small></h4>
                        </div>
                    </div>
                </div>

                <!-- جدول بنود وأدوية الفاتورة -->
                <div class="card border-0 shadow-sm rounded-4 bg-white mb-3">
                    <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-list-ul text-success me-2"></i> {{ __("الأدوية والخدمات المشمولة في الفاتورة") }} ({{ $sale->items->count() }})
                        </h6>
                        <span class="badge bg-light text-muted border font-monospace">
                            {{ $sale->invoice_number }}
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-muted small">
                                    <th class="ps-4">#</th>
                                    <th>{{ __("الصنف الدوائي / الخدمة") }}</th>
                                    <th class="text-center">{{ __("الوحدة") }}</th>
                                    <th class="text-center">{{ __("الكمية") }}</th>
                                    <th class="text-end">{{ __("سعر الوحدة") }}</th>
                                    <th class="text-end pe-4">{{ __("الإجمالي") }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sale->items as $idx => $item)
                                    @php
                                        $itemName = $item->item_type === 'medicine' ? ($item->medicine?->name ?? __('دواء')) : ($item->service?->name ?? __('خدمة صيدلانية'));
                                        $subInfo = $item->medicine ? ($item->medicine->generic_name . ' | ' . $item->medicine->strength) : '';
                                    @endphp
                                    <tr>
                                        <td class="ps-4 text-muted small">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $itemName }}</div>
                                            @if($subInfo)
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">{{ $subInfo }}</small>
                                            @endif
                                            @if($item->dosage_instructions)
                                                <small class="text-primary d-block" style="font-size: 0.75rem;"><i class="fas fa-info-circle me-1"></i>{{ $item->dosage_instructions }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border">
                                                {{ $item->unit_type === 'sub_unit' ? __('شريط / جزء') : __('علبة كاملة') }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold font-monospace">{{ (float)$item->quantity }}</td>
                                        <td class="text-end font-monospace">{{ number_format($item->unit_price) }} {{ __('د.ع') }}</td>
                                        <td class="text-end pe-4 font-monospace fw-bold text-success">{{ number_format($item->subtotal) }} {{ __('د.ع') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- لوحة القبض والتسديد (الكاشير) -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 bg-white sticky-top" style="top: 1rem;">
                    <div class="card-header bg-white border-0 py-3 px-4">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-cash-register text-success me-2"></i> {{ __("إجراءات القبض والتحصيل") }}
                        </h6>
                    </div>
                    <div class="card-body p-4 pt-1">
                        <!-- المبلغ المطلوب -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">{{ __("المبلغ المستلم من المريض (د.ع)") }}</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-success text-white border-0"><i class="fas fa-money-bill-wave"></i></span>
                                <input type="number" step="any" min="0" class="form-control fw-bold font-monospace text-success border-success" id="paymentAmountInput" name="amount" value="{{ $patientShare }}" required>
                            </div>
                        </div>

                        <!-- طريقة الدفع -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">{{ __("طريقة الدفع") }}</label>
                            <div class="d-grid gap-2">
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="payment_method" id="method_cash" value="cash" checked onchange="togglePaymentMethod('cash')">
                                    <label class="btn btn-outline-success py-2 fw-bold" for="method_cash">
                                        <i class="fas fa-money-bill-wave me-1"></i> {{ __("نقدي (كاش)") }}
                                    </label>

                                    <input type="radio" class="btn-check" name="payment_method" id="method_card" value="card" onchange="togglePaymentMethod('card')">
                                    <label class="btn btn-outline-primary py-2 fw-bold" for="method_card">
                                        <i class="fas fa-credit-card me-1"></i> {{ __("بطاقة") }}
                                    </label>

                                    <input type="radio" class="btn-check" name="payment_method" id="method_split" value="split" onchange="togglePaymentMethod('split')">
                                    <label class="btn btn-outline-warning text-dark py-2 fw-bold" for="method_split">
                                        <i class="fas fa-columns me-1"></i> {{ __("مركب") }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- حقول الدفع المركب (كاش + بطاقة) -->
                        <div id="splitPaymentFields" class="p-3 bg-light rounded-3 mb-3 border" style="display: none;">
                            <div class="mb-2">
                                <label class="form-label small fw-semibold text-muted">{{ __("المبلغ النقدي (د.ع)") }}</label>
                                <input type="number" step="any" min="0" class="form-control form-control-sm font-monospace" name="cash_amount" id="cashAmountInput" value="{{ $patientShare }}">
                            </div>
                            <div>
                                <label class="form-label small fw-semibold text-muted">{{ __("مبلغ البطاقة (د.ع)") }}</label>
                                <input type="number" step="any" min="0" class="form-control form-control-sm font-monospace" name="card_amount" id="cardAmountInput" value="0">
                            </div>
                        </div>

                        <!-- الخصم (إن وجد) -->
                        <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 text-muted" onclick="$('#discountFieldsCollapse').slideToggle()">
                                <i class="fas fa-tag me-1"></i> {{ __("إضافة خصم مالي (اختياري)") }}
                            </button>
                            <div id="discountFieldsCollapse" style="display: none;" class="mt-2 p-3 bg-light rounded-3 border">
                                <div class="mb-2">
                                    <label class="form-label small text-muted">{{ __("مبلغ الخصم (د.ع)") }}</label>
                                    <input type="number" step="any" min="0" class="form-control form-control-sm font-monospace" name="discount_amount" id="discountAmountInput" value="0">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">{{ __("سبب الخصم") }}</label>
                                    <input type="text" class="form-control form-control-sm" name="discount_reason" placeholder="{{ __('إعفاء، توصية مدير، إلخ...') }}">
                                </div>
                            </div>
                        </div>

                        <!-- ملاحظات -->
                        <div class="mb-4">
                            <label class="form-label small text-muted">{{ __("ملاحظات الكاشير") }}</label>
                            <textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="{{ __('أي ملاحظات تدون على سند القبض...') }}"></textarea>
                        </div>

                        <!-- زر التحصيل النهائي -->
                        <button type="submit" class="btn btn-success btn-lg w-100 py-3 rounded-3 fw-bold shadow d-flex align-items-center justify-content-center gap-2" id="submitPaymentBtn">
                            <i class="fas fa-print fa-lg"></i>
                            <span>{{ __("تسديد الفاتورة وطباعة الإيصال") }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function togglePaymentMethod(method) {
    if (method === 'split') {
        $('#splitPaymentFields').slideDown();
    } else {
        $('#splitPaymentFields').slideUp();
    }
}

// تعديل المبالغ تلقائياً في حالة الدفع المركب
$('#cashAmountInput, #cardAmountInput').on('input', function() {
    const cash = parseFloat($('#cashAmountInput').val()) || 0;
    const card = parseFloat($('#cardAmountInput').val()) || 0;
    $('#paymentAmountInput').val(cash + card);
});
</script>
@endsection
