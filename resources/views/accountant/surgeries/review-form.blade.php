@extends('layouts.app')

@section('content')
<div class="container-fluid py-3" style="background-color: #f8fafc; min-height: 100vh;">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="fas fa-file-invoice-dollar text-primary me-2"></i>اعتماد وتسعير الرسوم الجراحية
            </h3>
            <p class="text-muted mb-0">مراجعة وتعديل أجور العملية الأساسية والإجراءات الإضافية</p>
        </div>
        <a href="{{ route('accountant.surgeries.index') }}" class="btn btn-sm btn-outline-secondary px-3">
            <i class="fas fa-arrow-right me-1"></i>العودة للقائمة
        </a>
    </div>

    <!-- Patient Header Strip -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <div class="row align-items-center text-center text-md-start">
                <div class="col-md-3 border-end-md mb-3 mb-md-0">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3 text-primary" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user-injured fa-lg"></i>
                        </div>
                        <div class="text-start">
                            <h5 class="fw-bold mb-0 text-dark">{{ $surgery->patient->user->name ?? 'غير معروف' }}</h5>
                            <small class="text-muted">الرقم الموحد: #{{ $surgery->patient_id }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 border-end-md mb-3 mb-md-0 ps-md-4">
                    <small class="text-muted d-block mb-1">الجراح المسؤول</small>
                    <span class="fw-bold text-dark"><i class="fas fa-user-md me-1 text-primary"></i>{{ $surgery->doctor->user->name ?? $surgery->surgeon_name ?? 'غير محدد' }}</span>
                </div>
                <div class="col-md-3 border-end-md mb-3 mb-md-0 ps-md-4">
                    <small class="text-muted d-block mb-1">القسم الطبي</small>
                    <span class="fw-bold text-dark"><i class="fas fa-clinic-medical me-1 text-success"></i>{{ $surgery->department->name ?? 'غير محدد' }}</span>
                </div>
                <div class="col-md-3 ps-md-4">
                    <small class="text-muted d-block mb-1">تاريخ الجدولة</small>
                    <span class="fw-bold text-dark"><i class="fas fa-calendar-alt me-1 text-info"></i>{{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : 'غير محدد' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Pricing Panel -->
        <div class="col-lg-8 mb-4">
            <form action="{{ route('accountant.surgeries.confirm', $surgery) }}" method="POST" id="pricingForm">
                @csrf
                <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="fas fa-coins text-warning me-2"></i>بنود تسعير الفاتورة
                        </h5>
                    </div>
                    <div class="card-body px-4 pt-0">
                        <!-- Main Operation Price Row -->
                        <div class="p-3 mb-4 border rounded shadow-xs bg-light bg-opacity-50">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <span class="badge bg-primary px-3 py-1 rounded mb-2">العملية الأساسية</span>
                                    <h5 class="fw-bold mb-1 text-dark">{{ $surgery->surgery_type }}</h5>
                                    @if($surgery->surgicalOperation)
                                        <small class="text-success fw-bold d-block mt-1">
                                            <i class="fas fa-tags me-1"></i>السعر الافتراضي بالنظام: {{ number_format($surgery->surgicalOperation->fee, 0) }} د.ع
                                        </small>
                                    @else
                                        <small class="text-warning mt-1 d-block"><i class="fas fa-exclamation-circle me-1"></i>إدخال يدوي</small>
                                    @endif
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-muted">السعر المعتمد (د.ع):</label>
                                    <div class="input-group">
                                        <input type="text" 
                                               id="surgery_fee_display" 
                                               class="form-control form-control-lg border-primary price-format fw-bold text-primary" 
                                               value="{{ old('surgery_fee', 0) }}" 
                                               data-target="surgery_fee"
                                               required>
                                        <span class="input-group-text bg-primary text-white border-primary">د.ع</span>
                                    </div>
                                    <input type="hidden" id="surgery_fee" name="surgery_fee" value="{{ old('surgery_fee', 0) }}">
                                </div>
                            </div>
                        </div>

                        <!-- Additional Operations Rows -->
                        @if($surgery->additionalOperations->isNotEmpty())
                            <h6 class="fw-bold my-3 text-success">
                                <i class="fas fa-plus-circle me-1"></i>العمليات والإجراءات الإضافية
                            </h6>
                            @foreach($surgery->additionalOperations as $addOp)
                                <div class="p-3 mb-3 border rounded shadow-xs">
                                    <div class="row align-items-center">
                                        <div class="col-md-7">
                                            <h6 class="fw-bold mb-1 text-dark">{{ $addOp->surgicalOperation->name ?? 'غير معروفة' }}</h6>
                                            <small class="text-muted d-block mb-1">ملاحظات: {{ $addOp->notes ?? 'لا يوجد' }}</small>
                                            <small class="text-success fw-bold d-block">
                                                <i class="fas fa-tags me-1"></i>السعر الافتراضي بالنظام: {{ number_format($addOp->surgicalOperation->fee ?? 0, 0) }} د.ع
                                            </small>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small fw-bold text-muted">السعر المعتمد للإجراء (د.ع):</label>
                                            <div class="input-group">
                                                <input type="text" 
                                                       class="form-control border-success price-format fw-bold text-success" 
                                                       value="{{ old('additional_ops.'.$addOp->id, 0) }}" 
                                                       data-target="add_op_{{ $addOp->id }}"
                                                       required>
                                                <span class="input-group-text bg-success text-white border-success">د.ع</span>
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

                        <!-- Live Total Card -->
                        <div class="card border-0 bg-dark text-white p-3 mt-4" style="border-radius: 8px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1 small fw-bold text-light text-opacity-75">إجمالي تكلفة العمليات المعتمدة</h6>
                                    <span class="small text-light text-opacity-50">تشمل الأساسية والإضافية</span>
                                </div>
                                <div class="text-end">
                                    <h3 class="fw-bold mb-0 text-warning" id="live_total_display">0 د.ع</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white d-flex justify-content-between py-3 px-4 border-top-0">
                        <a href="{{ route('accountant.surgeries.index') }}" class="btn btn-light px-4 border">
                            <i class="fas fa-times me-1"></i>إلغاء والعودة
                        </a>
                        <button type="submit" class="btn btn-success px-5">
                            <i class="fas fa-check-circle me-1"></i>اعتماد وإرسال للكاشير
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right Side: Change History -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="fas fa-history text-warning me-2"></i>سجل تبديل نوع العمليات
                    </h5>
                </div>
                <div class="card-body pt-0">
                    @if($surgery->surgeryTypeChanges->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-info-circle fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0 small">لا توجد تغييرات سابقة على هذه العملية.</p>
                        </div>
                    @else
                        <div class="timeline">
                            @foreach($surgery->surgeryTypeChanges as $change)
                                <div class="mb-3 p-3 bg-light rounded" style="border-right: 4px solid #f59e0b;">
                                    <div class="fw-bold text-dark small">إلى: {{ $change->new_type }}</div>
                                    <div class="small text-muted mb-1">
                                        كانت: <span class="text-decoration-line-through text-danger">{{ $change->old_type }}</span>
                                    </div>
                                    <div class="small text-muted" style="font-size: 0.75rem;">
                                        <i class="fas fa-user-edit me-1"></i>{{ $change->changedBy->name ?? 'غير معروف' }}
                                        <br>
                                        <i class="fas fa-clock me-1"></i>{{ $change->created_at->format('Y-m-d H:i') }}
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
    document.addEventListener('DOMContentLoaded', function() {
        var priceInputs = document.querySelectorAll('.price-format');
        var liveTotalDisplay = document.getElementById('live_total_display');

        function calculateLiveTotal() {
            var total = 0;
            document.querySelectorAll('input[type="hidden"][name^="surgery_fee"], input[type="hidden"][name^="additional_ops"]').forEach(function(hiddenInput) {
                var val = parseInt(hiddenInput.value, 10);
                if (!isNaN(val)) {
                    total += val;
                }
            });
            liveTotalDisplay.textContent = total.toLocaleString('en-US') + ' د.ع';
        }

        priceInputs.forEach(function(input) {
            // Function to format value
            function formatValue(val) {
                var clean = String(val).replace(/[^\d]/g, '');
                if (clean === '') clean = '0';
                
                var targetId = input.getAttribute('data-target');
                var hiddenInput = document.getElementById(targetId);
                if (hiddenInput) {
                    hiddenInput.value = clean;
                }
                
                return parseInt(clean, 10).toLocaleString('en-US');
            }

            // Initialize with default
            input.value = formatValue(input.value);

            input.addEventListener('input', function(e) {
                var selectionStart = input.selectionStart;
                var oldLength = input.value.length;
                
                var formatted = formatValue(input.value);
                input.value = formatted;
                
                var newLength = input.value.length;
                var newPosition = selectionStart + (newLength - oldLength);
                input.setSelectionRange(newPosition, newPosition);

                // Recalculate Live Total
                calculateLiveTotal();
            });
        });

        // Run total calculation on load
        calculateLiveTotal();
    });
</script>
@endsection
