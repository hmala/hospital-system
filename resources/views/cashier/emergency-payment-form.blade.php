@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-ambulance me-2"></i>
                        تسديد خدمات الطوارئ
                    </h4>
                </div>
                <div class="card-body">
                    <!-- معلومات المريض وحالة الطوارئ -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-user me-2"></i>
                                        معلومات المريض
                                    </h6>
                                    @php
                                        $em = $payment->emergency;
                                        if ($em->patient) {
                                            $pname = $em->patient->user->name ?? 'غير محدد';
                                            $pphone = $em->patient->user->phone ?? '---';
                                            $pid = '#'.$em->patient->id;
                                        } elseif ($em->emergencyPatient) {
                                            $pname = $em->emergencyPatient->name;
                                            $pphone = $em->emergencyPatient->phone ?? '---';
                                            $pid = '(طوارئ)';
                                        } else {
                                            $pname = 'غير محدد';
                                            $pphone = '---';
                                            $pid = '-';
                                        }
                                    @endphp
                                    <p class="mb-1">
                                        <strong>الاسم:</strong> {{ $pname }}
                                    </p>
                                    <p class="mb-1">
                                        <strong>الهاتف:</strong> {{ $pphone }}
                                    </p>
                                    <p class="mb-0">
                                        <strong>رقم المريض:</strong> {{ $pid }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        حالة الطوارئ
                                    </h6>
                                    <p class="mb-1">
                                        <strong>رقم الحالة:</strong> #{{ $payment->emergency->id }}
                                    </p>
                                    <p class="mb-1">
                                        <strong>الأولوية:</strong>
                                        <span class="badge bg-{{ $payment->emergency->priority_color }}">
                                            {{ $payment->emergency->priority_text }}
                                        </span>
                                    </p>
                                    <p class="mb-0">
                                        <strong>النوع:</strong> {{ $payment->emergency->emergency_type_text }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- الخدمات المطلوبة -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                تفاصيل الدفع
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($payment->description)
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>{{ $payment->description }}</strong>
                                </div>
                            @endif

                            @if($payment->appointment_id && $payment->appointment)
                                <div class="mb-3">
                                    <h6 class="text-primary">
                                        <i class="fas fa-user-md me-2"></i>
                                        موعد استشاري
                                    </h6>
                                    <p class="mb-1">
                                        <strong>الطبيب:</strong> د. {{ $payment->appointment->doctor->user->name ?? 'غير محدد' }}
                                    </p>
                                    <p class="mb-1">
                                        <strong>التخصص:</strong> {{ $payment->appointment->doctor->specialization ?? 'غير محدد' }}
                                    </p>
                                    <p class="mb-1">
                                        <strong>التاريخ:</strong> {{ $payment->appointment->appointment_date->format('Y-m-d H:i') ?? 'غير محدد' }}
                                    </p>
                                    <p class="mb-1">
                                        <strong>رسوم الاستشارة:</strong> <span class="text-success fw-bold">{{ number_format($payment->amount, 2) }} IQD</span>
                                    </p>
                                </div>
                                <hr>
                            @endif

                            @if($payment->emergency->services->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>خدمات الطوارئ</th>
                                            <th>السعر</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($payment->emergency->services as $service)
                                        <tr>
                                            <td>{{ $service->name }}</td>
                                            <td>{{ number_format($service->price, 2) }} IQD</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif

                            <div class="mt-3 p-3 bg-light rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">المجموع الكلي:</h5>
                                    <h4 class="mb-0 text-success">{{ number_format($payment->amount, 2) }} IQD</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- نموذج الدفع -->
                    <form action="{{ route('cashier.emergency.payment.process', $payment) }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">
                                        <i class="fas fa-credit-card me-1"></i>
                                        طريقة الدفع <span class="text-danger">*</span>
                                    </label>
                                    <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                        <option value="">اختر طريقة الدفع</option>
                                        <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>نقدي</option>
                                        <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>بطاقة</option>
                                        <option value="insurance" {{ old('payment_method') == 'insurance' ? 'selected' : '' }}>تأمين</option>
                                    </select>
                                    @error('payment_method')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">
                                        <i class="fas fa-money-bill-wave me-1"></i>
                                        المبلغ المدفوع <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror"
                                               value="{{ old('amount', $payment->amount) }}" step="0.01" min="0" required>
                                        <span class="input-group-text">IQD</span>
                                    </div>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">
                                <i class="fas fa-sticky-note me-1"></i>
                                ملاحظات
                            </label>
                            <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror"
                                      rows="3" placeholder="أي ملاحظات إضافية...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('cashier.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i>
                                        العودة
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check me-1"></i>
                                        تأكيد الدفع
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // تنسيق المبلغ عند الكتابة
    $('#amount').on('input', function() {
        var value = $(this).val();
        if (value) {
            $(this).val(parseFloat(value).toFixed(2));
        }
    });
});
</script>
@endsection