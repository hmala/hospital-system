@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-money-bill-wave me-2 text-success"></i>
                    تسديد رسوم الموعد
                </h2>
                <a href="{{ route('cashier.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>العودة
                </a>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-success text-white" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-file-invoice-dollar me-2"></i>
                        معلومات الدفع
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('cashier.payment.process', $appointment->id) }}">
                        @csrf

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-money-bill-wave me-1 text-success"></i>
                                    طريقة الدفع *
                                </label>
                                <div class="payment-methods-group">
                                    <div class="form-check form-check-lg mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" 
                                               value="cash" {{ old('payment_method', 'cash') == 'cash' ? 'checked' : '' }} required>
                                        <label class="form-check-label fw-semibold" for="payment_cash">
                                            💵 نقدي (Cash)
                                        </label>
                                    </div>
                                    <div class="form-check form-check-lg mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment_card" 
                                               value="card" {{ old('payment_method') == 'card' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="payment_card">
                                            💳 بطاقة ائتمان (Card)
                                        </label>
                                    </div>
                                    <div class="form-check form-check-lg">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment_insurance" 
                                               value="insurance" {{ old('payment_method') == 'insurance' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="payment_insurance">
                                            🏥 تأمين صحي (Insurance)
                                        </label>
                                    </div>
                                </div>
                                @error('payment_method')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">المبلغ (IQD) *</label>
                                <input type="number" 
                                       name="amount" 
                                       class="form-control @error('amount') is-invalid @enderror" 
                                       value="{{ old('amount', $appointment->consultation_fee) }}"
                                       step="0.01"
                                       min="0"
                                       required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">ملاحظات</label>
                            <textarea name="notes" 
                                      class="form-control @error('notes') is-invalid @enderror" 
                                      rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            سيتم إصدار إيصال دفع فوراً بعد إتمام العملية
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check-circle me-2"></i>
                                تأكيد الدفع
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- تفاصيل الموعد -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0">
                        <i class="fas fa-calendar-check me-2"></i>
                        تفاصيل الموعد
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">رقم الموعد:</small>
                        <div class="fw-bold">#{{ $appointment->id }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">التاريخ والوقت:</small>
                        <div class="fw-bold">{{ $appointment->appointment_date->format('Y-m-d H:i') }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">القسم:</small>
                        <div class="fw-bold">{{ $appointment->department ? $appointment->department->name : 'غير محدد' }}</div>
                    </div>
                </div>
            </div>

            <!-- معلومات المريض -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        معلومات المريض
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        $p = $appointment->patient;
                    @endphp
                    <div class="mb-3">
                        <small class="text-muted">الاسم:</small>
                        <div class="fw-bold">{{ $p ? ($p->user->name ?? 'غير محدد') : 'غير محدد' }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">الرقم الوطني:</small>
                        <div class="fw-bold">{{ $p ? ($p->national_id ?? 'غير محدد') : 'غير محدد' }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">رقم الهاتف:</small>
                        <div class="fw-bold">{{ $p ? ($p->user->phone ?? 'غير محدد') : 'غير محدد' }}</div>
                    </div>
                </div>
            </div>

            <!-- معلومات الطبيب -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user-md me-2"></i>
                        معلومات الطبيب
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        $d = $appointment->doctor;
                    @endphp
                    <div class="mb-3">
                        <small class="text-muted">الاسم:</small>
                        <div class="fw-bold">د. {{ $d ? ($d->user->name ?? 'غير محدد') : 'غير محدد' }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">التخصص:</small>
                        <div class="fw-bold">{{ $d ? $d->specialization : 'غير محدد' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
