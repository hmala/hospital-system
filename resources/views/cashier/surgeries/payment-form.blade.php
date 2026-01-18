@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        دفع رسوم العملية الجراحية
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

                    <!-- تفاصيل التكلفة -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-calculator me-2"></i>
                            تفصيل التكاليف
                        </h6>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>البند</th>
                                        <th>التفاصيل</th>
                                        <th class="text-end">التكلفة (IQD)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- رسوم العملية -->
                                    <tr>
                                        <td><strong>رسوم العملية الجراحية</strong></td>
                                        <td>{{ $surgery->surgery_type }}</td>
                                        <td class="text-end">{{ number_format($surgeryFee, 0) }}</td>
                                    </tr>

                                    <!-- التحاليل المطلوبة -->
                                    @if($surgery->labTests->count() > 0)
                                        <tr class="table-info">
                                            <td colspan="3"><strong><i class="fas fa-flask me-2"></i>التحاليل المطلوبة قبل العملية</strong></td>
                                        </tr>
                                        @foreach($surgery->labTests as $labTest)
                                            <tr>
                                                <td></td>
                                                <td>{{ $labTest->labTest->name }} ({{ $labTest->labTest->code }})</td>
                                                <td class="text-end">{{ number_format($labTest->labTest->price ?? 0, 0) }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="table-light">
                                            <td colspan="2" class="text-end"><strong>مجموع التحاليل:</strong></td>
                                            <td class="text-end"><strong>{{ number_format($labTestsFee, 0) }}</strong></td>
                                        </tr>
                                    @endif

                                    <!-- الأشعة المطلوبة -->
                                    @if($surgery->radiologyTests->count() > 0)
                                        <tr class="table-warning">
                                            <td colspan="3"><strong><i class="fas fa-x-ray me-2"></i>الفحوصات الإشعاعية المطلوبة</strong></td>
                                        </tr>
                                        @foreach($surgery->radiologyTests as $radiologyTest)
                                            <tr>
                                                <td></td>
                                                <td>{{ $radiologyTest->radiologyType->name }}</td>
                                                <td class="text-end">{{ number_format($radiologyTest->radiologyType->base_price ?? 0, 0) }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="table-light">
                                            <td colspan="2" class="text-end"><strong>مجموع الأشعة:</strong></td>
                                            <td class="text-end"><strong>{{ number_format($radiologyTestsFee, 0) }}</strong></td>
                                        </tr>
                                    @endif

                                    <!-- الإجمالي -->
                                    <tr class="table-success">
                                        <td colspan="2" class="text-end"><h5 class="mb-0">الإجمالي الكلي:</h5></td>
                                        <td class="text-end"><h4 class="mb-0 text-success">{{ number_format($totalAmount, 0) }} IQD</h4></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- نموذج الدفع -->
                    <form action="{{ route('cashier.surgery.payment.process', $surgery->id) }}" method="POST">
                        @csrf

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
                                        <i class="fas fa-money-bill-wave me-1"></i>
                                        المبلغ المدفوع <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('amount') is-invalid @enderror" 
                                           id="amount" 
                                           name="amount" 
                                           step="0.01" 
                                           value="{{ old('amount', $totalAmount) }}" 
                                           required>
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
                                    <a href="{{ route('cashier.surgeries.index') }}" class="btn btn-secondary">
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
