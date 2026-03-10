@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        دفع رسوم الطلب الطبي
                    </h5>
                </div>
                <div class="card-body">
                    <!-- معلومات الطلب -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-file-medical me-2"></i>
                                    تفاصيل الطلب
                                </h6>
                                <div class="mb-2">
                                    <strong>رقم الطلب:</strong> #{{ $request->id }}
                                </div>
                                <div class="mb-2">
                                    <strong>النوع:</strong>
                                    @if($request->type === 'lab')
                                        <span class="badge bg-primary">تحاليل</span>
                                    @elseif($request->type === 'radiology')
                                        <span class="badge bg-info">أشعة</span>
                                    @elseif($request->type === 'pharmacy')
                                        <span class="badge bg-success">صيدلية</span>
                                    @elseif($request->type === 'emergency')
                                        <span class="badge bg-danger">طوارئ</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $request->type }}</span>
                                    @endif
                                </div>
                                <div class="mb-2">
                                    <strong>التاريخ:</strong> {{ $request->created_at->format('Y-m-d H:i') }}
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
                                    <strong>الاسم:</strong> {{ $request->visit->patient->user->name }}
                                </div>
                                <div class="mb-2">
                                    <strong>رقم الهوية:</strong> {{ $request->visit->patient->national_id ?? 'غير محدد' }}
                                </div>
                                <div class="mb-2">
                                    <strong>رقم الهاتف:</strong> {{ $request->visit->patient->user->phone ?? 'غير محدد' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- تفاصيل الطلب -->
                    <div class="mb-4">
                        <h6 class="text-info mb-3">
                            <i class="fas fa-list me-2"></i>
                            تفاصيل الخدمة المطلوبة
                        </h6>
                        <div class="border rounded p-3">
                            @php
                                $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                                $totalAmount = 0;
                            @endphp

                            @if($request->type === 'lab' && isset($details['lab_test_ids']))
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-primary">
                                            <tr>
                                                <th width="60">#</th>
                                                <th>اسم التحليل</th>
                                                <th>الرمز</th>
                                                <th width="150" class="text-end">السعر (IQD)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($details['lab_test_ids'] as $index => $testId)
                                                @php
                                                    $test = \App\Models\LabTest::find($testId);
                                                    if($test) {
                                                        $price = $test->price ?? 0;
                                                        $totalAmount += $price;
                                                    }
                                                @endphp
                                                @if($test)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>
                                                            <i class="fas fa-vial text-primary me-2"></i>
                                                            {{ $test->name }}
                                                        </td>
                                                        <td><code>{{ $test->code }}</code></td>
                                                        <td class="text-end">
                                                            <strong>{{ number_format($price, 2) }}</strong>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-success">
                                            <tr>
                                                <th colspan="3" class="text-end">المجموع الكلي:</th>
                                                <th class="text-end">
                                                    <h5 class="mb-0 text-success">
                                                        {{ number_format($totalAmount, 2) }} IQD
                                                    </h5>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @elseif($request->type === 'radiology' && isset($details['radiology_type_ids']))
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-info">
                                            <tr>
                                                <th width="60">#</th>
                                                <th>نوع الأشعة</th>
                                                <th>الرمز</th>
                                                <th width="150" class="text-end">السعر (IQD)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($details['radiology_type_ids'] as $index => $typeId)
                                                @php
                                                    $type = \App\Models\RadiologyType::find($typeId);
                                                    if($type) {
                                                        $price = $type->price ?? 0;
                                                        $totalAmount += $price;
                                                    }
                                                @endphp
                                                @if($type)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>
                                                            <i class="fas fa-x-ray text-info me-2"></i>
                                                            {{ $type->name }}
                                                        </td>
                                                        <td><code>{{ $type->code }}</code></td>
                                                        <td class="text-end">
                                                            <strong>{{ number_format($price, 2) }}</strong>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-success">
                                            <tr>
                                                <th colspan="3" class="text-end">المجموع الكلي:</th>
                                                <th class="text-end">
                                                    <h5 class="mb-0 text-success">
                                                        {{ number_format($totalAmount, 2) }} IQD
                                                    </h5>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @elseif($request->type === 'emergency' && isset($details['emergency_priority']))
                                @php
                                    // حساب رسوم الطوارئ بناءً على الأولوية
                                    $emergencyFees = [
                                        'critical' => 50000,    // 50,000 IQD للحالات الحرجة
                                        'urgent' => 35000,      // 35,000 IQD للحالات العاجلة
                                        'semi_urgent' => 25000, // 25,000 IQD للحالات شبه العاجلة
                                        'non_urgent' => 15000   // 15,000 IQD للحالات غير العاجلة
                                    ];
                                    $totalAmount = $emergencyFees[$details['emergency_priority']] ?? 25000;
                                @endphp
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <td width="200"><strong>الأولوية:</strong></td>
                                                <td>
                                                    @if($details['emergency_priority'] === 'critical')
                                                        <span class="badge bg-danger">حرجة</span>
                                                    @elseif($details['emergency_priority'] === 'urgent')
                                                        <span class="badge bg-warning">عاجلة</span>
                                                    @elseif($details['emergency_priority'] === 'semi_urgent')
                                                        <span class="badge bg-info">شبه عاجلة</span>
                                                    @else
                                                        <span class="badge bg-secondary">غير عاجلة</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @if(isset($details['emergency_type']))
                                            <tr>
                                                <td><strong>نوع الطوارئ:</strong></td>
                                                <td>{{ \App\Models\Emergency::getEmergencyTypeText($details['emergency_type']) }}</td>
                                            </tr>
                                            @endif
                                            @if(isset($details['symptoms_description']))
                                            <tr>
                                                <td><strong>الأعراض:</strong></td>
                                                <td>{{ $details['symptoms_description'] }}</td>
                                            </tr>
                                            @endif
                                            <tr class="table-success">
                                                <td><strong>رسوم الطوارئ:</strong></td>
                                                <td>
                                                    <h5 class="mb-0 text-success">
                                                        {{ number_format($totalAmount, 2) }} IQD
                                                    </h5>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="mb-3">
                                    <strong>الوصف:</strong> {{ $request->description }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- نموذج الدفع -->
                    <form action="{{ route('cashier.request.payment.process', $request->id) }}" method="POST">
                        @csrf
                        @method('POST')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-money-bill-wave me-1 text-success"></i>
                                        طريقة الدفع <span class="text-danger">*</span>
                                    </label>
                                    <div class="payment-methods-group">
                                        <div class="form-check form-check-lg mb-2">
                                            <input class="form-check-input" type="radio" name="payment_method" id="request_payment_cash" 
                                                   value="cash" {{ old('payment_method', 'cash') == 'cash' ? 'checked' : '' }} required>
                                            <label class="form-check-label fw-semibold" for="request_payment_cash">
                                                💵 نقدي (Cash)
                                            </label>
                                        </div>
                                        <div class="form-check form-check-lg mb-2">
                                            <input class="form-check-input" type="radio" name="payment_method" id="request_payment_card" 
                                                   value="card" {{ old('payment_method') == 'card' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="request_payment_card">
                                                💳 بطاقة ائتمان (Card)
                                            </label>
                                        </div>
                                        <div class="form-check form-check-lg">
                                            <input class="form-check-input" type="radio" name="payment_method" id="request_payment_insurance" 
                                                   value="insurance" {{ old('payment_method') == 'insurance' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="request_payment_insurance">
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
                                    <label for="amount" class="form-label">
                                        <i class="fas fa-dollar-sign me-1"></i>
                                        المبلغ المطلوب دفعة <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" 
                                               class="form-control @error('amount') is-invalid @enderror" 
                                               id="amount" 
                                               name="amount" 
                                               step="0.01" 
                                               min="0" 
                                               value="{{ old('amount', $totalAmount) }}" 
                                               required
                                               readonly
                                               style="background-color: #e9ecef;">
                                        <span class="input-group-text bg-success text-white">IQD</span>
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
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" 
                                      name="notes" 
                                      rows="3" 
                                      placeholder="أي ملاحظات إضافية...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('cashier.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i>
                                        العودة
                                    </a>
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-money-bill-wave me-2"></i>
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
    // تحديث المبلغ تلقائياً حسب نوع الطلب (يمكن تخصيصه لاحقاً)
    $('#payment_method').change(function() {
        // يمكن إضافة منطق لحساب المبلغ حسب النوع والخدمات
    });
});
</script>
@endsection