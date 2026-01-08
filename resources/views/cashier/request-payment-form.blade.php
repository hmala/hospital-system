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
                            @endphp

                            @if($request->type === 'lab' && isset($details['lab_test_ids']))
                                <div class="mb-3">
                                    <strong>التحاليل المطلوبة:</strong>
                                    <ul class="list-unstyled mt-2">
                                        @foreach($details['lab_test_ids'] as $testId)
                                            @php
                                                $test = \App\Models\LabTest::find($testId);
                                            @endphp
                                            @if($test)
                                                <li class="mb-1">
                                                    <i class="fas fa-vial text-primary me-2"></i>
                                                    {{ $test->name }} ({{ $test->code }})
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            @elseif($request->type === 'radiology' && isset($details['radiology_type_ids']))
                                <div class="mb-3">
                                    <strong>الأشعة المطلوبة:</strong>
                                    <ul class="list-unstyled mt-2">
                                        @foreach($details['radiology_type_ids'] as $typeId)
                                            @php
                                                $type = \App\Models\RadiologyType::find($typeId);
                                            @endphp
                                            @if($type)
                                                <li class="mb-1">
                                                    <i class="fas fa-camera text-info me-2"></i>
                                                    {{ $type->name }} ({{ $type->code }})
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
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
                                    <label for="payment_method" class="form-label">
                                        <i class="fas fa-credit-card me-1"></i>
                                        طريقة الدفع <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('payment_method') is-invalid @enderror" 
                                            id="payment_method" name="payment_method" required>
                                        <option value="">اختر طريقة الدفع</option>
                                        <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>
                                            نقدي
                                        </option>
                                        <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>
                                            بطاقة ائتمان
                                        </option>
                                        <option value="insurance" {{ old('payment_method') == 'insurance' ? 'selected' : '' }}>
                                            تأمين صحي
                                        </option>
                                    </select>
                                    @error('payment_method')
                                        <div class="invalid-feedback">{{ $message }}</div>
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
                                               value="{{ old('amount', 0) }}" 
                                               required>
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