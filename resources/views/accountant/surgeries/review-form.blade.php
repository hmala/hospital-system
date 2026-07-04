@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="fas fa-edit me-2 text-primary"></i>
                مراجعة وتعديل أسعار العملية الجراحية
            </h2>
            <p class="text-muted">
                مراجعة أسعار العملية ورسوم الإجراءات الإضافية قبل إحالتها للكاشير للدفع.
            </p>
        </div>
    </div>

    <div class="row">
        <!-- Patient Info Card -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="fas fa-user-injured text-primary me-2"></i>بيانات المريض
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="fw-bold text-muted" style="width: 40%">اسم المريض:</td>
                            <td>{{ $surgery->patient->user->name ?? 'غير معروف' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">الرقم الموحد:</td>
                            <td>#{{ $surgery->patient_id }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">الطبيب الجراح:</td>
                            <td>{{ $surgery->doctor->user->name ?? $surgery->surgeon_name ?? 'غير محدد' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">القسم:</td>
                            <td>{{ $surgery->department->name ?? 'غير محدد' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">تاريخ الجدولة:</td>
                            <td>{{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : 'غير محدد' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">حالة العملية:</td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ $surgery->status }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- History Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="fas fa-history text-warning me-2"></i>سجل تغيير نوع العملية
                    </h5>
                </div>
                <div class="card-body">
                    @if($surgery->surgeryTypeChanges->isEmpty())
                        <p class="text-muted mb-0 text-center py-2">لا توجد تغييرات سابقة على هذه العملية.</p>
                    @else
                        <div class="timeline">
                            @foreach($surgery->surgeryTypeChanges as $change)
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="fw-bold text-dark">إلى: {{ $change->new_type }}</div>
                                    <div class="small text-muted mb-1">
                                        كانت: <span class="text-decoration-line-through text-danger">{{ $change->old_type }}</span>
                                    </div>
                                    <div class="small text-muted">
                                        بواسطة: {{ $change->changedBy->name ?? 'غير معروف' }}
                                        <br>
                                        الوقت: {{ $change->created_at->format('Y-m-d H:i') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pricing Form Column -->
        <div class="col-md-8 mb-4">
            <form action="{{ route('accountant.surgeries.confirm', $surgery) }}" method="POST">
                @csrf
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="fas fa-file-invoice-dollar text-primary me-2"></i>تعديل الأسعار
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Main Operation Price -->
                        <div class="bg-light p-4 rounded mb-4">
                            <h6 class="fw-bold mb-3 text-primary">
                                <i class="fas fa-procedures me-1"></i>العملية الأساسية
                            </h6>
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="fw-bold fs-5">{{ $surgery->surgery_type }}</div>
                                    @if($surgery->surgicalOperation)
                                        <small class="text-muted">الاسم البرمجي: {{ $surgery->surgicalOperation->name }}</small>
                                        <br>
                                        <small class="text-success fw-bold">السعر الافتراضي بالنظام: {{ number_format($surgery->surgicalOperation->fee, 0) }} د.ع</small>
                                    @else
                                        <small class="text-warning">عملية يدوية (غير مربوطة بقاعدة البيانات)</small>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label for="surgery_fee_display" class="form-label fw-bold">السعر المقترح للعملية (د.ع):</label>
                                    <input type="text" 
                                           id="surgery_fee_display" 
                                           class="form-control form-control-lg border-primary price-format" 
                                           value="{{ old('surgery_fee', 0) }}" 
                                           data-target="surgery_fee"
                                           required>
                                    <input type="hidden" id="surgery_fee" name="surgery_fee" value="{{ old('surgery_fee', 0) }}">
                                </div>
                            </div>
                        </div>

                        <!-- Additional Operations Prices -->
                        @if($surgery->additionalOperations->isNotEmpty())
                            <h6 class="fw-bold mb-3 text-success">
                                <i class="fas fa-plus-circle me-1"></i>العمليات الإضافية
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>العملية الإضافية</th>
                                            <th>ملاحظات العمليات</th>
                                            <th style="width: 35%">تعديل السعر (د.ع)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($surgery->additionalOperations as $addOp)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold">{{ $addOp->surgicalOperation->name ?? 'غير معروفة' }}</div>
                                                    <small class="text-success">السعر الافتراضي: {{ number_format($addOp->surgicalOperation->fee ?? 0, 0) }} د.ع</small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">{{ $addOp->notes ?? 'لا يوجد' }}</small>
                                                </td>
                                                <td>
                                                    <input type="text" 
                                                           class="form-control border-success price-format" 
                                                           value="{{ old('additional_ops.'.$addOp->id, 0) }}" 
                                                           data-target="add_op_{{ $addOp->id }}"
                                                           required>
                                                    <input type="hidden" 
                                                           id="add_op_{{ $addOp->id }}" 
                                                           name="additional_ops[{{ $addOp->id }}]" 
                                                           value="{{ old('additional_ops.'.$addOp->id, 0) }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-light text-center border">
                                <i class="fas fa-info-circle me-1 text-muted"></i>
                                لا توجد عمليات إضافية مضافة لهذه الجلسة.
                            </div>
                        @endif
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between py-3">
                        <a href="{{ route('accountant.surgeries.index') }}" class="btn btn-secondary px-4">
                            <i class="fas fa-times me-1"></i>إلغاء والعودة
                        </a>
                        <button type="submit" class="btn btn-success px-4">
                            <i class="fas fa-check me-1"></i>تأكيد الأسعار وإرسال للكاشير
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.price-format').forEach(function(input) {
            // Function to format value
            function formatValue(val) {
                // Strip everything except numbers
                var clean = String(val).replace(/[^\d]/g, '');
                if (clean === '') clean = '0';
                
                // Set clean numeric value to hidden input
                var targetId = input.getAttribute('data-target');
                var hiddenInput = document.getElementById(targetId);
                if (hiddenInput) {
                    hiddenInput.value = clean;
                }
                
                // Return formatted with commas
                return parseInt(clean, 10).toLocaleString('en-US');
            }

            // Initialize with default
            input.value = formatValue(input.value);

            input.addEventListener('input', function(e) {
                // Save cursor position
                var selectionStart = input.selectionStart;
                var oldLength = input.value.length;
                
                var formatted = formatValue(input.value);
                input.value = formatted;
                
                // Adjust cursor position after formatting
                var newLength = input.value.length;
                var newPosition = selectionStart + (newLength - oldLength);
                input.setSelectionRange(newPosition, newPosition);
            });
        });
    });
</script>
@endsection
