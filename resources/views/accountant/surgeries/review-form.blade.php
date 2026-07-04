@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Page Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fas fa-file-invoice-dollar text-primary me-2"></i>اعتماد وتسعير العملية
            </h5>
            <small class="text-muted">تحديد الرسوم قبل إرسالها للكاشير</small>
        </div>
        <a href="{{ route('accountant.surgeries.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-right me-1"></i>العودة
        </a>
    </div>

    {{-- Patient Strip --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                        <i class="fas fa-user-injured text-primary"></i>
                    </div>
                </div>
                <div class="col">
                    <div class="fw-bold text-dark" style="font-size:.9rem;">{{ $surgery->patient->user->name ?? 'غير معروف' }}</div>
                    <small class="text-muted">#{{ $surgery->patient_id }}</small>
                </div>
                <div class="col-md-auto border-start ps-3">
                    <small class="text-muted d-block">الجراح</small>
                    <span class="fw-semibold" style="font-size:.85rem;">{{ $surgery->doctor->user->name ?? $surgery->surgeon_name ?? '—' }}</span>
                </div>
                <div class="col-md-auto border-start ps-3">
                    <small class="text-muted d-block">القسم</small>
                    <span class="fw-semibold" style="font-size:.85rem;">{{ $surgery->department->name ?? '—' }}</span>
                </div>
                <div class="col-md-auto border-start ps-3">
                    <small class="text-muted d-block">التاريخ</small>
                    <span class="fw-semibold" style="font-size:.85rem;">{{ $surgery->scheduled_date?->format('Y-m-d') ?? '—' }}</span>
                </div>
                <div class="col-md-auto border-start ps-3">
                    <small class="text-muted d-block">الحالة</small>
                    <span class="badge bg-warning text-dark">{{ $surgery->status }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        {{-- ===== Pricing Form ===== --}}
        <div class="col-lg-8">
            <form action="{{ route('accountant.surgeries.confirm', $surgery) }}" method="POST">
                @csrf
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-2 px-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-coins text-warning me-1"></i>بنود الفاتورة
                        </h6>
                    </div>
                    <div class="card-body p-3">

                        {{-- Main Operation --}}
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="row align-items-center g-2">
                                <div class="col-md-7">
                                    <span class="badge bg-primary mb-1" style="font-size:.7rem;">العملية الأساسية</span>
                                    <div class="fw-bold text-dark" style="font-size:.9rem;">{{ $surgery->surgery_type }}</div>
                                    @if($surgery->surgicalOperation)
                                        <small class="text-success">
                                            <i class="fas fa-tags me-1"></i>السعر الافتراضي: {{ number_format($surgery->surgicalOperation->fee, 0) }} د.ع
                                        </small>
                                    @else
                                        <small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>إدخال يدوي</small>
                                    @endif
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small text-muted mb-1">السعر المعتمد (د.ع)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text"
                                               id="surgery_fee_display"
                                               class="form-control border-primary price-format fw-bold text-primary"
                                               value="{{ old('surgery_fee', 0) }}"
                                               data-target="surgery_fee"
                                               required>
                                        <span class="input-group-text bg-primary text-white border-primary" style="font-size:.75rem;">د.ع</span>
                                    </div>
                                    <input type="hidden" id="surgery_fee" name="surgery_fee" value="{{ old('surgery_fee', 0) }}">
                                </div>
                            </div>
                        </div>

                        {{-- Additional Operations --}}
                        @if($surgery->additionalOperations->isNotEmpty())
                            <div class="fw-bold text-success small mb-2">
                                <i class="fas fa-plus-circle me-1"></i>الإجراءات الإضافية
                            </div>
                            @foreach($surgery->additionalOperations as $addOp)
                                <div class="border rounded p-2 mb-2">
                                    <div class="row align-items-center g-2">
                                        <div class="col-md-7">
                                            <div class="fw-semibold text-dark" style="font-size:.85rem;">{{ $addOp->surgicalOperation->name ?? 'غير معروفة' }}</div>
                                            @if($addOp->notes)
                                                <small class="text-muted">{{ $addOp->notes }}</small>
                                            @endif
                                            <small class="text-success d-block">
                                                <i class="fas fa-tags me-1"></i>الافتراضي: {{ number_format($addOp->surgicalOperation->fee ?? 0, 0) }} د.ع
                                            </small>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="input-group input-group-sm">
                                                <input type="text"
                                                       class="form-control border-success price-format fw-bold text-success"
                                                       value="{{ old('additional_ops.'.$addOp->id, 0) }}"
                                                       data-target="add_op_{{ $addOp->id }}"
                                                       required>
                                                <span class="input-group-text bg-success text-white border-success" style="font-size:.75rem;">د.ع</span>
                                            </div>
                                            <input type="hidden"
                                                   id="add_op_{{ $addOp->id }}"
                                                   name="additional_ops[{{ $addOp->id }}]"
                                                   value="{{ old('additional_ops.'.$addOp->id, 0) }}">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        {{-- Live Total --}}
                        <div class="rounded p-3 mt-3 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg,#1e293b,#0f172a);">
                            <div>
                                <div class="text-white-50 small">الإجمالي المعتمد</div>
                                <div class="text-white" style="font-size:.75rem;">يشمل الأساسية + الإضافية</div>
                            </div>
                            <h5 class="fw-bold mb-0 text-warning" id="live_total_display">0 د.ع</h5>
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between py-2 px-3">
                        <a href="{{ route('accountant.surgeries.index') }}" class="btn btn-sm btn-light border">
                            <i class="fas fa-times me-1"></i>إلغاء
                        </a>
                        <button type="submit" class="btn btn-sm btn-success px-4">
                            <i class="fas fa-check-circle me-1"></i>اعتماد وإرسال للكاشير
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- ===== Right Panel ===== --}}
        <div class="col-lg-4">

            {{-- Change History --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-history text-warning me-1"></i>سجل تغيير نوع العملية
                    </h6>
                </div>
                <div class="card-body p-3">
                    @if($surgery->surgeryTypeChanges->isEmpty())
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-info-circle mb-1"></i>
                            <div class="small">لا توجد تغييرات سابقة</div>
                        </div>
                    @else
                        @foreach($surgery->surgeryTypeChanges as $change)
                            <div class="mb-2 p-2 bg-light rounded" style="border-right: 3px solid #f59e0b; font-size:.8rem;">
                                <div class="fw-bold text-dark">إلى: {{ $change->new_type }}</div>
                                <div class="text-muted">كانت: <span class="text-decoration-line-through text-danger">{{ $change->old_type }}</span></div>
                                <div class="text-muted mt-1">
                                    <i class="fas fa-user-edit me-1"></i>{{ $change->changedBy->name ?? '—' }}<br>
                                    <i class="fas fa-clock me-1"></i>{{ $change->created_at->format('Y-m-d H:i') }}
                                </div>
                            </div>
                        @endforeach
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
        document.querySelectorAll('input[type="hidden"][name="surgery_fee"], input[type="hidden"][name^="additional_ops"]').forEach(function (h) {
            var v = parseInt(h.value, 10);
            if (!isNaN(v)) total += v;
        });
        liveTotalDisplay.textContent = total.toLocaleString('en-US') + ' د.ع';
    }

    document.querySelectorAll('.price-format').forEach(function (input) {
        function formatValue(val) {
            var clean = String(val).replace(/[^\d]/g, '') || '0';
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
