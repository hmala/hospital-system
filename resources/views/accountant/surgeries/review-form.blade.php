@extends('layouts.app')

@section('content')
{{-- Include Google Cairo Font for professional Arabic typography --}}
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">

<style>
    .review-container {
        font-family: 'Cairo', sans-serif;
        color: #1e293b;
        background-color: #f8fafc;
    }
    .page-title {
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.5px;
    }
    .premium-card {
        background: #ffffff;
        border: none;
        border-radius: 16px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        overflow: hidden;
    }
    .premium-card:hover {
        box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.08);
    }
    .patient-strip {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        border-radius: 16px;
        color: #ffffff;
        box-shadow: 0 10px 20px -5px rgba(29, 78, 216, 0.3);
    }
    .patient-strip .info-label {
        color: rgba(255, 255, 255, 0.75);
        font-size: 0.75rem;
        font-weight: 600;
    }
    .patient-strip .info-value {
        font-size: 0.95rem;
        font-weight: 700;
    }
    .section-header {
        font-weight: 700;
        color: #1e293b;
        font-size: 1.05rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .billing-item {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.25s ease;
    }
    .billing-item:hover {
        border-color: #3b82f6;
        background-color: #f8fafc;
        transform: translateY(-2px);
    }
    .price-input-group .input-group-text {
        background-color: #f1f5f9;
        border-color: #cbd5e1;
        font-weight: 700;
        color: #64748b;
        transition: all 0.2s ease;
    }
    .price-input-group .form-control {
        border-color: #cbd5e1;
        font-weight: 700;
        font-size: 1.1rem;
        color: #1e293b;
        text-align: left;
        direction: ltr;
        transition: all 0.2s ease;
    }
    .price-input-group .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
    }
    .live-total-card {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-radius: 14px;
        box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.2);
    }
    .timeline-item {
        position: relative;
        padding-right: 24px;
        border-right: 2px solid #e2e8f0;
    }
    .timeline-item::after {
        content: '';
        position: absolute;
        right: -6px;
        top: 6px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #f59e0b;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.3);
    }
    .btn-action {
        border-radius: 10px;
        font-weight: 700;
        padding: 10px 24px;
        transition: all 0.2s ease;
    }
    .btn-action:hover {
        transform: translateY(-1px);
    }
    .btn-submit {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: none;
        color: white;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }
    .btn-submit:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
    }
    .btn-cancel {
        background-color: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    .btn-cancel:hover {
        background-color: #e2e8f0;
        color: #475569;
    }
</style>

<div class="container-fluid px-4 py-4 review-container">
    {{-- Header --}}
    <div class="row align-items-center justify-content-between mb-4">
        <div class="col-auto">
            <h4 class="page-title mb-1">
                <i class="fas fa-file-invoice-dollar text-primary me-2"></i>اعتماد وتسعير العملية الجراحية
            </h4>
            <p class="text-muted mb-0 small">مراجعة وتأكيد بنود الأسعار والأجهزة المستخدمة قبل إرسالها للصندوق</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('accountant.surgeries.index') }}" class="btn btn-sm btn-action btn-cancel">
                <i class="fas fa-arrow-right me-1"></i>العودة للقائمة
            </a>
        </div>
    </div>

    {{-- Patient Information Strip --}}
    <div class="card border-0 patient-strip p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-3 border-end border-white border-opacity-10">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-user-injured fa-lg text-white"></i>
                    </div>
                    <div>
                        <div class="info-label">اسم المريض</div>
                        <div class="info-value text-truncate" style="max-width: 180px;">{{ $surgery->patient->user->name ?? 'غير معروف' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6 border-end border-white border-opacity-10">
                <div class="info-label">رقم المريض</div>
                <div class="info-value">#{{ $surgery->patient_id }}</div>
            </div>
            <div class="col-md-2 col-6 border-end border-white border-opacity-10">
                <div class="info-label">الطبيب الجراح</div>
                <div class="info-value text-truncate" style="max-width: 140px;">{{ $surgery->doctor->user->name ?? $surgery->surgeon_name ?? '—' }}</div>
            </div>
            <div class="col-md-2 col-6 border-end border-white border-opacity-10">
                <div class="info-label">القسم المختص</div>
                <div class="info-value">{{ $surgery->department->name ?? '—' }}</div>
            </div>
            <div class="col-md-2 col-6 border-end border-white border-opacity-10">
                <div class="info-label">تاريخ العملية</div>
                <div class="info-value">{{ $surgery->scheduled_date?->format('Y-m-d') ?? '—' }}</div>
            </div>
            <div class="col-md-1 col-6 text-md-center">
                <div class="info-label mb-1">الحالة</div>
                <span class="badge bg-white text-primary fw-bold px-3 py-2" style="font-size: 0.8rem;">
                    {{ $surgery->status }}
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Main Form (Pricing Components) --}}
        <div class="col-lg-8">
            <form action="{{ route('accountant.surgeries.confirm', $surgery) }}" method="POST">
                @csrf
                <div class="card premium-card">
                    <div class="card-header bg-transparent border-bottom py-3 px-4">
                        <h5 class="section-header mb-0">
                            <i class="fas fa-coins text-warning"></i>تفاصيل وبنود التسعير
                        </h5>
                    </div>
                    
                    <div class="card-body p-4">
                        {{-- 1. Main Operation --}}
                        <div class="billing-item p-4 mb-4 bg-light bg-opacity-50">
                            <div class="row align-items-center g-3">
                                <div class="col-md-7">
                                    <span class="badge bg-primary bg-opacity-10 text-primary mb-2 px-2 py-1" style="font-size: 0.75rem;">
                                        العملية الأساسية
                                    </span>
                                    <h5 class="fw-bold text-dark mb-2">{{ $surgery->surgery_type }}</h5>
                                    @if($surgery->surgicalOperation)
                                        <div class="text-success small fw-semibold">
                                            <i class="fas fa-tag me-1"></i>السعر الافتراضي المحدد: {{ number_format($surgery->surgicalOperation->fee, 0) }} د.ع
                                        </div>
                                    @else
                                        <div class="text-warning small fw-semibold">
                                            <i class="fas fa-exclamation-triangle me-1"></i>نوع العملية مدخل يدوياً
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label text-muted small fw-bold mb-2">السعر المعتمد للعملية</label>
                                    <div class="input-group price-input-group">
                                        <input type="text"
                                               id="surgery_fee_display"
                                               class="form-control price-format fw-bold text-primary @error('surgery_fee') is-invalid @enderror"
                                               value="{{ old('surgery_fee', $surgery->surgery_fee ?? ($surgery->surgicalOperation->fee ?? 0)) }}"
                                               data-target="surgery_fee"
                                               required>
                                        <span class="input-group-text">د.ع</span>
                                    </div>
                                    @error('surgery_fee')
                                        <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                                    @enderror
                                    <input type="hidden" id="surgery_fee" name="surgery_fee" value="{{ old('surgery_fee', $surgery->surgery_fee ?? ($surgery->surgicalOperation->fee ?? 0)) }}">
                                </div>
                            </div>
                        </div>

                        {{-- 2. Additional Operations --}}
                        @if($surgery->additionalOperations->isNotEmpty())
                            <div class="border-top pt-3 mt-3 mb-4">
                                <h6 class="fw-bold text-success mb-3">
                                    <i class="fas fa-plus-circle me-1"></i>العمليات والإجراءات الإضافية المصاحبة
                                </h6>
                                <div class="d-flex flex-column gap-3">
                                    @foreach($surgery->additionalOperations as $addOp)
                                        <div class="billing-item p-3">
                                            <div class="row align-items-center g-3">
                                                <div class="col-md-7">
                                                    <div class="fw-bold text-dark mb-1">{{ $addOp->surgicalOperation->name ?? 'غير معروفة' }}</div>
                                                    @if($addOp->notes)
                                                        <p class="text-muted small mb-1">{{ $addOp->notes }}</p>
                                                    @endif
                                                    <span class="text-success small fw-semibold">
                                                        <i class="fas fa-tag me-1"></i>السعر الافتراضي: {{ number_format($addOp->surgicalOperation->fee ?? 0, 0) }} د.ع
                                                    </span>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="input-group price-input-group">
                                                        <input type="text"
                                                               class="form-control price-format fw-bold text-success @error('additional_ops.'.$addOp->id) is-invalid @enderror"
                                                               value="{{ old('additional_ops.'.$addOp->id, $addOp->fee ?? ($addOp->surgicalOperation->fee ?? 0)) }}"
                                                               data-target="add_op_{{ $addOp->id }}"
                                                               required>
                                                        <span class="input-group-text">د.ع</span>
                                                    </div>
                                                    @error('additional_ops.'.$addOp->id)
                                                        <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                                                    @enderror
                                                    <input type="hidden"
                                                           id="add_op_{{ $addOp->id }}"
                                                           name="additional_ops[{{ $addOp->id }}]"
                                                           value="{{ old('additional_ops.'.$addOp->id, $addOp->fee ?? ($addOp->surgicalOperation->fee ?? 0)) }}">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- 3. Medical Devices --}}
                        @if($surgery->medicalDevices->isNotEmpty())
                            <div class="border-top pt-3 mt-3 mb-4">
                                <h6 class="fw-bold text-info mb-3">
                                    <i class="fas fa-stethoscope me-1"></i>رسوم الأجهزة الطبية المستخدمة
                                </h6>
                                <div class="d-flex flex-column gap-3">
                                    @foreach($surgery->medicalDevices as $device)
                                        <div class="billing-item p-3">
                                            <div class="row align-items-center g-3">
                                                <div class="col-md-7">
                                                    <div class="fw-bold text-dark mb-1">{{ $device->name }}</div>
                                                    <span class="badge bg-light text-secondary mb-2">{{ $device->type }}</span>
                                                    <span class="text-info small d-block fw-semibold">
                                                        <i class="fas fa-tag me-1"></i>التكلفة الافتراضية للجهاز: {{ number_format($device->usage_price ?? 0, 0) }} د.ع
                                                    </span>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="input-group price-input-group">
                                                        <input type="text"
                                                               class="form-control price-format fw-bold text-info @error('device_prices.'.$device->id) is-invalid @enderror"
                                                               value="{{ old('device_prices.'.$device->id, $device->pivot->price ?? ($device->usage_price ?? 0)) }}"
                                                               data-target="device_price_{{ $device->id }}"
                                                               required>
                                                        <span class="input-group-text">د.ع</span>
                                                    </div>
                                                    @error('device_prices.'.$device->id)
                                                        <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                                                    @enderror
                                                    <input type="hidden"
                                                           id="device_price_{{ $device->id }}"
                                                           name="device_prices[{{ $device->id }}]"
                                                           value="{{ old('device_prices.'.$device->id, $device->pivot->price ?? ($device->usage_price ?? 0)) }}">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- 4. Live Total Summary Card --}}
                        <div class="live-total-card p-4 mt-4 text-white d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-white-50 small fw-bold">الإجمالي المعتمد الكلي</span>
                                <h6 class="mb-0 text-white opacity-75 mt-1" style="font-size: 0.8rem;">
                                    (العملية الأساسية + الإضافات + الأجهزة)
                                </h6>
                            </div>
                            <h3 class="fw-bold mb-0 text-warning" id="live_total_display" style="font-size: 1.8rem; letter-spacing: 0.5px;">0 د.ع</h3>
                        </div>
                    </div>

                    {{-- Actions Footer --}}
                    <div class="card-footer bg-light border-top d-flex justify-content-between py-3 px-4">
                        <a href="{{ route('accountant.surgeries.index') }}" class="btn btn-action btn-cancel">
                            <i class="fas fa-times me-1"></i>إلغاء المراجعة
                        </a>
                        <button type="submit" class="btn btn-action btn-submit">
                            <i class="fas fa-check-circle me-1"></i>تأكيد الحساب وإرسال للكاشير
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Side Timeline (History) --}}
        <div class="col-lg-4">
            <div class="card premium-card h-100">
                <div class="card-header bg-transparent border-bottom py-3 px-4">
                    <h5 class="section-header mb-0">
                        <i class="fas fa-history text-warning"></i>سجل تغييرات العملية
                    </h5>
                </div>
                <div class="card-body p-4">
                    @if($surgery->surgeryTypeChanges->isEmpty())
                        <div class="text-center text-muted py-5">
                            <div class="mb-3">
                                <i class="fas fa-folder-open fa-3x opacity-20"></i>
                            </div>
                            <p class="small mb-0">لا توجد أي تغييرات سابقة على هذه العملية</p>
                        </div>
                    @else
                        <div class="d-flex flex-column gap-4">
                            @foreach($surgery->surgeryTypeChanges as $change)
                                <div class="timeline-item">
                                    <div class="fw-bold text-dark mb-1">تغيير نوع العملية</div>
                                    <div class="text-muted small">
                                        من: <span class="text-decoration-line-through text-danger fw-semibold">{{ $change->old_type }}</span>
                                    </div>
                                    <div class="text-success small fw-semibold mb-2">
                                        إلى: <span>{{ $change->new_type }}</span>
                                    </div>
                                    <div class="bg-light p-2 rounded small text-secondary" style="font-size: 0.75rem;">
                                        <div><i class="fas fa-user-edit me-1"></i>بواسطة: {{ $change->changedBy->name ?? '—' }}</div>
                                        <div><i class="fas fa-clock me-1"></i>{{ $change->created_at->format('Y-m-d H:i') }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var liveTotalDisplay = document.getElementById('live_total_display');

    function calculateLiveTotal() {
        var total = 0;
        document.querySelectorAll('input[type="hidden"][name="surgery_fee"], input[type="hidden"][name^="additional_ops"], input[type="hidden"][name^="device_prices"]').forEach(function (h) {
            var v = parseInt(h.value, 10);
            if (!isNaN(v)) total += v;
        });
        liveTotalDisplay.textContent = total.toLocaleString('en-US') + ' د.ع';
    }

    document.querySelectorAll('.price-format').forEach(function (input) {
        function formatValue(val) {
            // Split by decimal point to get the integer part and ignore any decimals (.00)
            var integerPart = String(val).split('.')[0];
            var clean = integerPart.replace(/[^\d]/g, '') || '0';
            var hidden = document.getElementById(input.getAttribute('data-target'));
            if (hidden) hidden.value = clean;
            return parseInt(clean, 10).toLocaleString('en-US');
        }

        input.value = formatValue(input.value);

        input.addEventListener('input', function () {
            var sel = input.selectionStart;
            var oldLen = input.value.length;
            input.value = formatValue(input.value);
            var diff = input.value.length - oldLen;
            input.setSelectionRange(sel + diff, sel + diff);
            calculateLiveTotal();
        });
    });

    calculateLiveTotal();
});
</script>
@endsection
