@extends('layouts.app')

@section('title', __('الحركات المالية والقيود - العناية المركزة للكبار (CCU)'))

@section('content')
<div class="container-fluid py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="fas fa-exchange-alt text-success me-2"></i>
                {{ __('سجل الحركات المالية لوحدة العناية المركزة (CCU Financial Movements)') }}
            </h3>
            <p class="text-muted small mb-0">{{ __('جميع الإيصالات المستلمة والمبالغ المستردة لإضبارات العناية المركزة للكبار.') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('cashier.ccu.statements') }}" class="btn btn-outline-primary btn-sm">{{ __('كشوفات CCU') }}</a>
            <a href="{{ route('cashier.index', ['tab' => 'ccu']) }}" class="btn btn-outline-secondary btn-sm">{{ __('لوحة الكاشير') }}</a>
        </div>
    </div>

    <!-- الفلاتر -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <form method="GET" action="{{ route('cashier.ccu.financial-movements') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-bold mb-1">{{ __('من تاريخ') }}</label>
                <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold mb-1">{{ __('إلى تاريخ') }}</label>
                <input type="date" name="to_date" value="{{ $toDate }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">{{ __('نوع الحركة') }}</label>
                <select name="payment_type" class="form-select form-select-sm">
                    <option value="">{{ __('الكل') }}</option>
                    @foreach(\App\Models\CcuPayment::PAYMENT_TYPES as $k => $lbl)
                        <option value="{{ $k }}" {{ $type === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold mb-1">{{ __('الوسيلة') }}</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">{{ __('الكل') }}</option>
                    <option value="cash" {{ $method === 'cash' ? 'selected' : '' }}>{{ __('نقدي') }}</option>
                    <option value="card" {{ $method === 'card' ? 'selected' : '' }}>{{ __('بطاقة') }}</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary fw-bold flex-fill">{{ __('بحث') }}</button>
                <a href="{{ route('cashier.ccu.financial-movements') }}" class="btn btn-sm btn-outline-secondary">{{ __('مسح') }}</a>
            </div>
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-xs rounded-4 p-3 bg-white border-start border-4 border-success">
                <small class="text-muted">{{ __('إجمالي المقبوضات') }}</small>
                <h4 class="fw-bold text-success mb-0 font-monospace">{{ number_format($totalInflows) }} {{ __('د.ع') }}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-xs rounded-4 p-3 bg-white border-start border-4 border-danger">
                <small class="text-muted">{{ __('إجمالي المستردات') }}</small>
                <h4 class="fw-bold text-danger mb-0 font-monospace">{{ number_format($totalRefunds) }} {{ __('د.ع') }}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-xs rounded-4 p-3 bg-white border-start border-4 border-primary">
                <small class="text-muted">{{ __('صافي التدفق النقدي') }}</small>
                <h4 class="fw-bold text-primary mb-0 font-monospace">{{ number_format($netAmount) }} {{ __('د.ع') }}</h4>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center small">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('رقم الوصل') }}</th>
                        <th>{{ __('الإضبارة والمريض') }}</th>
                        <th>{{ __('نوع الحركة') }}</th>
                        <th>{{ __('المبلغ') }}</th>
                        <th>{{ __('الوسيلة') }}</th>
                        <th>{{ __('التاريخ') }}</th>
                        <th>{{ __('أمين الصندوق') }}</th>
                        <th>{{ __('الوصل') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                        <tr>
                            <td class="font-monospace fw-bold">{{ $m->receipt_number }}</td>
                            <td class="text-start">#{{ $m->icu_admission_id }} — {{ $m->admission?->patient?->user?->name ?? 'مريض' }}</td>
                            <td>
                                <span class="badge {{ $m->payment_type === 'refund' ? 'bg-danger' : 'bg-success' }}">
                                    {{ \App\Models\CcuPayment::PAYMENT_TYPES[$m->payment_type] ?? $m->payment_type }}
                                </span>
                            </td>
                            <td class="font-monospace fw-bold {{ $m->payment_type === 'refund' ? 'text-danger' : 'text-success' }}">
                                {{ number_format($m->amount) }} {{ __('د.ع') }}
                            </td>
                            <td>{{ $m->payment_method === 'card' ? __('بطاقة') : __('كاش') }}</td>
                            <td>{{ optional($m->paid_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $m->cashier?->name ?? __('النظام') }}</td>
                            <td>
                                <a href="{{ route('cashier.ccu.receipt', $m->id) }}" class="btn btn-sm btn-outline-dark py-0 px-2">{{ __('عرض') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted py-5">{{ __('لا توجد حركات مالية مسجلة للعناية المركزة حالياً.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())
            <div class="p-3 border-top">{{ $movements->links() }}</div>
        @endif
    </div>
</div>
@endsection
