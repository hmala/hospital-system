@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-wallet me-2 text-danger"></i>{{ __('كشف حساب الطوارئ للطبيب') }}</h2>
                <p class="text-muted mb-0">{{ __('كشف تفصيلي بحالات المتابعة وأجور الصرف للطبيب') }} {{ __('د.') }} {{ optional($doctor->user)->name }}.</p>
            </div>
            <a href="{{ route('cashier.emergency.doctor-accounts') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>{{ __('العودة لحسابات الأطباء') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- بطاقات الملخص المالي للطبيب --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-white bg-success">
                <div class="card-body text-center">
                    <h6 class="mb-1 opacity-75">{{ __('إجمالي المستحقات المكتسبة') }}</h6>
                    <h2 class="fw-bold mb-0">{{ number_format($totalEarned, 0) }} {{ __('د.ع') }}</h2>
                    <small>{{ __('حالات الطوارئ المدفوعة بالكامل') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-white bg-secondary">
                <div class="card-body text-center">
                    <h6 class="mb-1 opacity-75">{{ __('إجمالي المبالغ المصروفة') }}</h6>
                    <h2 class="fw-bold mb-0">{{ number_format($totalPaid, 0) }} {{ __('د.ع') }}</h2>
                    <small>{{ __('المدفوعة نقداً للطبيب') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-white bg-danger animate__animated animate__pulse">
                <div class="card-body text-center">
                    <h6 class="mb-1 opacity-75">{{ __('الرصيد المتبقي مستحق الصرف') }}</h6>
                    <h2 class="fw-bold mb-0">{{ number_format($balance, 0) }} {{ __('د.ع') }}</h2>
                    <small>{{ __('جاهز للصرف') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- زر وأكشن تسجيل دفعة صرف --}}
    @if($balance > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title mb-0 fw-bold"><i class="fas fa-hand-holding-dollar text-danger me-2"></i>{{ __('تسجيل دفعة صرف مستحقات') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('cashier.emergency.doctor-payout', $doctor) }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label for="amount" class="form-label fw-bold">{{ __('المبلغ المراد صرفه للطبيب (د.ع)') }}</label>
                        <input type="number" id="amount" name="amount" class="form-control" max="{{ $balance }}" min="1000" step="250" placeholder="{{ __('مثال: 30000') }}" required>
                    </div>
                    <div class="col-md-5">
                        <label for="notes" class="form-label fw-bold">{{ __('ملاحظات الصرف') }}</label>
                        <input type="text" id="notes" name="notes" class="form-control" placeholder="{{ __('مثال: صرف مستحقات كشفية الطوارئ للفترة المنتهية') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-danger w-100 fw-bold">
                            <i class="fas fa-check-circle me-1"></i>{{ __('تسجيل صرف الدفعة') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- تفاصيل الحالات والمدفوعات --}}
    <div class="row g-4">
        {{-- الحالات المكتسبة --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark"><i class="fas fa-ambulance text-danger me-2"></i>{{ __('سجل الحالات المكتسبة') }}</h5>
                </div>
                <div class="card-body">
                    @if($earnedCases->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('التاريخ') }}</th>
                                        <th>{{ __('المريض') }}</th>
                                        <th>{{ __('رقم الإيصال') }}</th>
                                        <th>{{ __('قيمة المتابعة') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($earnedCases as $case)
                                        <tr>
                                            <td>{{ $earnedCases->firstItem() + $loop->index }}</td>
                                            <td>{{ $case->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $case->patient?->user?->name ?? $case->emergencyPatient?->name ?? __('غير معروف') }}</div>
                                            </td>
                                            <td><code>{{ optional($case->payment)->receipt_number ?? '---' }}</code></td>
                                            <td class="fw-bold text-success">{{ number_format($case->doctor_follow_up_fee, 0) }} {{ __('د.ع') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $earnedCases->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <p class="text-muted text-center py-5">{{ __('لا توجد حالات طوارئ مسجلة مكتسبة لهذا الطبيب.') }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- الحركات المالية (الصرف) --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark"><i class="fas fa-history text-secondary me-2"></i>{{ __('سجل دفعات الصرف') }}</h5>
                </div>
                <div class="card-body">
                    @if($payouts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('التاريخ') }}</th>
                                        <th>{{ __('المبلغ') }}</th>
                                        <th>{{ __('بواسطة') }}</th>
                                        <th>{{ __('ملاحظات') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payouts as $payout)
                                        <tr>
                                            <td>{{ $payouts->firstItem() + $loop->index }}</td>
                                            <td>{{ $payout->created_at->format('Y-m-d H:i') }}</td>
                                            <td class="fw-bold text-danger">{{ number_format($payout->amount, 0) }} {{ __('د.ع') }}</td>
                                            <td>{{ optional($payout->paidBy)->name ?? '-' }}</td>
                                            <td><span class="small text-muted">{{ $payout->notes }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $payouts->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <p class="text-muted text-center py-5">{{ __('لا توجد دفعات صرف مسجلة لهذا الطبيب.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
