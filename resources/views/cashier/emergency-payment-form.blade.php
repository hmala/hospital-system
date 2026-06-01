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
                            <div class="table-responsive mb-3">
                                <h6>خدمات الطوارئ</h6>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>خدمة</th>
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

                            @if($payment->emergency->labRequests->count() > 0)
                            <div class="table-responsive mb-3">
                                <h6>طلبات التحاليل</h6>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>طلب #</th>
                                            <th>تحاليل</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($payment->emergency->labRequests as $labReq)
                                        <tr>
                                            <td>#{{ $labReq->id }}</td>
                                            <td>
                                                @foreach($labReq->labTests as $test)
                                                    <span class="badge bg-primary me-1">{{ $test->name }}</span>
                                                @endforeach
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $labReq->status == 'completed' ? 'success' : ($labReq->status == 'in_progress' ? 'info' : 'warning') }}">
                                                    {{ $labReq->status_text }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif

                            @if($payment->emergency->radiologyRequests->count() > 0)
                            <div class="table-responsive mb-3">
                                <h6>طلبات الأشعة</h6>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>طلب #</th>
                                            <th>أشعة</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($payment->emergency->radiologyRequests as $radReq)
                                        <tr>
                                            <td>#{{ $radReq->id }}</td>
                                            <td>
                                                @foreach($radReq->radiologyTypes as $type)
                                                    <span class="badge bg-info me-1">{{ $type->name }}</span>
                                                @endforeach
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $radReq->status == 'completed' ? 'success' : ($radReq->status == 'in_progress' ? 'info' : 'warning') }}">
                                                    {{ $radReq->status_text }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif

                            @php
                                $invoiceItems = [];
                                $invoiceLines = [];

                                // الحصول على IDs الخدمات غير المدفوعة فقط
                                $unpaidServiceIds = \DB::table('emergency_emergency_service')
                                    ->where('emergency_id', $payment->emergency_id)
                                    ->whereNull('payment_id')
                                    ->pluck('emergency_service_id');

                                // إضافة الخدمات غير المدفوعة فقط
                                foreach($payment->emergency->services->whereIn('id', $unpaidServiceIds) as $service) {
                                    $invoiceItems[] = [
                                        'name' => $service->name,
                                        'qty' => 1,
                                        'price' => $service->price,
                                        'total' => $service->price
                                    ];
                                }

                                // إضافة طلبات التحاليل غير المدفوعة فقط
                                foreach($payment->emergency->labRequests->whereNull('payment_id') as $labReq) {
                                    foreach($labReq->labTests as $test) {
                                        $invoiceItems[] = [
                                            'name' => '[تحاليل] ' . $test->name,
                                            'qty' => 1,
                                            'price' => $test->price,
                                            'total' => $test->price
                                        ];
                                    }
                                }

                                // إضافة طلبات الأشعة غير المدفوعة فقط
                                foreach($payment->emergency->radiologyRequests->whereNull('payment_id') as $radReq) {
                                    foreach($radReq->radiologyTypes as $type) {
                                        $invoiceItems[] = [
                                            'name' => '[أشعة] ' . $type->name,
                                            'qty' => 1,
                                            'price' => $type->base_price ?? 0,
                                            'total' => $type->base_price ?? 0
                                        ];
                                    }
                                }

                                // إضافة المواعيد غير المدفوعة
                                foreach($payment->emergency->appointments as $ap) {
                                    if($ap->payment_status === 'pending' && $ap->status !== 'cancelled') {
                                        $invoiceItems[] = [
                                            'name' => '[استشارة] ' . ($ap->reason ?: 'استشارة طوارئ'),
                                            'qty' => 1,
                                            'price' => $ap->consultation_fee ?? 0,
                                            'total' => $ap->consultation_fee ?? 0
                                        ];
                                    }
                                }

                                // إضافة رسوم متابعة الطبيب إذا كانت موجودة ولم تُدفع بعد
                                if($payment->emergency->doctor_follow_up_fee > 0 && !$payment->emergency->follow_up_payment_id) {
                                    $invoiceItems[] = [
                                        'name' => 'رسوم متابعة الطبيب',
                                        'qty' => 1,
                                        'price' => $payment->emergency->doctor_follow_up_fee,
                                        'total' => $payment->emergency->doctor_follow_up_fee
                                    ];
                                }

                                $subtotal = array_sum(array_column($invoiceItems, 'total'));
                                $paidAmount = $payment->paid_at ? $payment->amount : 0;
                                $dueAmount = max(0, $subtotal - $paidAmount);
                                $totalInvoiceAmount = $subtotal; // نعرض المجموع من العناصر الفعلية
                            @endphp

                            <div class="card mt-4 border-dark">
                                <div class="card-header bg-dark text-white">
                                    <h5 class="mb-0">فاتورة طلبات الطوارئ (نمطي)</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>المنتج</th>
                                                    <th style="width:90px;">الكمية</th>
                                                    <th style="width:120px;">السعر</th>
                                                    <th style="width:120px;">الإجمالي</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($invoiceItems as $item)
                                                <tr>
                                                    <td>{{ $item['name'] }}</td>
                                                    <td class="text-center">{{ $item['qty'] }}</td>
                                                    <td class="text-end">{{ number_format($item['price'], 2) }} IQD</td>
                                                    <td class="text-end">{{ number_format($item['total'], 2) }} IQD</td>
                                                </tr>
                                                @endforeach
                                                @if(empty($invoiceItems))
                                                <tr>
                                                    <td colspan="4" class="text-center">لا توجد بنود الفاتورة حالياً</td>
                                                </tr>
                                                @endif
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="3" class="text-end">المجموع الفرعي</th>
                                                    <th class="text-end">{{ number_format($subtotal, 2) }} IQD</th>
                                                </tr>
                                                <tr>
                                                    <th colspan="3" class="text-end">المدفوع</th>
                                                    <th class="text-end">{{ number_format($paidAmount, 2) }} IQD</th>
                                                </tr>
                                                <tr>
                                                    <th colspan="3" class="text-end">المتبقي</th>
                                                    <th class="text-end">{{ number_format($dueAmount, 2) }} IQD</th>
                                                </tr>
                                                <tr class="table-success">
                                                    <th colspan="3" class="text-end">الإجمالي</th>
                                                    <th class="text-end">{{ number_format($totalInvoiceAmount, 2) }} IQD</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>


                            <div class="mt-3 p-3 bg-light rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">المجموع الكلي:</h5>
                                    <h4 class="mb-0 text-success">{{ number_format($totalInvoiceAmount, 2) }} IQD</h4>
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