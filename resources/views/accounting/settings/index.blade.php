@extends('accounting.layout')

@section('title', 'إعدادات المنظومة المالية والعملات وفروق الصرف')

@section('accounting_content')
<div class="row g-4">
    <!-- 1. Currencies & Exchange Rate Management -->
    <div class="col-12 col-xl-7">
        <div class="bg-white rounded-4 shadow-sm border border-slate-200 p-3 p-md-4 h-100">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-3 border-bottom">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fas fa-coins text-primary me-1"></i> إعدادات العملة الأساسية وأسعار الصرف
                    </h5>
                    <p class="text-muted small mb-0">
                        العملة الأساسية المعتمدة حالياً:
                        <strong class="text-primary">{{ $defaultCurrency?->name ?? 'دينار عراقي' }} ({{ $defaultCurrency?->code ?? 'IQD' }})</strong>
                    </p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-3" data-bs-toggle="collapse" data-bs-target="#newCurrencyFormCollapse">
                    <i class="fas fa-plus-circle me-1"></i> إضافة عملة جديدة
                </button>
            </div>

            <!-- Add New Currency Collapse -->
            <div class="collapse mb-4" id="newCurrencyFormCollapse">
                <form action="{{ route('accounting.settings.currencies.store') }}" method="POST" class="bg-light border rounded-4 p-3">
                    @csrf
                    <h6 class="fw-bold text-dark mb-3">إضافة عملة جديدة</h6>
                    <div class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">الرمز (ISO)</label>
                            <input type="text" name="code" maxlength="3" class="form-control form-control-sm text-uppercase font-monospace" placeholder="EUR" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">اسم العملة</label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="يورو أوروبي" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">العلامة</label>
                            <input type="text" name="symbol" class="form-control form-control-sm" placeholder="€" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">سعر الصرف مقابل الأساس</label>
                            <input type="number" step="0.0001" min="0.0001" name="exchange_rate" class="form-control form-control-sm font-monospace" placeholder="1450" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="form-check small">
                            <input class="form-check-input" type="checkbox" name="is_official" value="1" id="newCurrOfficial" checked>
                            <label class="form-check-label" for="newCurrOfficial">سعر رسمي معتمد</label>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary rounded-3 px-3">حفظ العملة</button>
                    </div>
                </form>
            </div>

            <!-- Existing Currencies List -->
            <div class="d-flex flex-column gap-3">
                @foreach($currencies as $curr)
                    <form action="{{ route('accounting.settings.currencies.update', $curr->id) }}" method="POST" class="rounded-4 border p-3 {{ $curr->is_default ? 'bg-primary bg-opacity-10 border-primary border-opacity-25' : 'bg-light' }}">
                        @csrf
                        @method('PUT')
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge {{ $curr->is_default ? 'bg-primary' : 'bg-dark' }} fs-6 font-monospace px-3 py-1">
                                    {{ $curr->code }} ({{ $curr->symbol }})
                                </span>
                                <div>
                                    <div class="fw-bold text-dark">{{ $curr->name }}</div>
                                    <div class="small text-muted">
                                        @if($curr->is_default)
                                            <span class="text-primary fw-semibold"><i class="fas fa-star me-1"></i> العملة الأساسية للنظام (المعامل = 1)</span>
                                        @else
                                            1 {{ $curr->symbol }} = <strong class="font-monospace text-dark">{{ number_format((float) $curr->exchange_rate, 2) }}</strong> {{ $defaultCurrency?->symbol ?? 'د.ع' }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @if($curr->is_official)
                                    <span class="badge bg-info-subtle text-info border">سعر رسمي</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border">سعر سوق</span>
                                @endif
                                @if($curr->is_active)
                                    <span class="badge bg-success-subtle text-success border">فعالة</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border">معطلة</span>
                                @endif
                            </div>
                        </div>

                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">سعر الصرف الجديد</label>
                                <input type="number"
                                       step="0.0001"
                                       min="0.0001"
                                       name="exchange_rate"
                                       value="{{ number_format((float) $curr->exchange_rate, 4, '.', '') }}"
                                       class="form-control form-control-sm font-monospace fw-bold"
                                       {{ $curr->is_default ? 'readonly' : 'required' }}>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold mb-1">سبب التعديل (للأرشفة والتدقيق) <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="reason"
                                       class="form-control form-control-sm"
                                       placeholder="مثلاً: تحديث سعر الصرف اليومي المعتمد"
                                       required>
                            </div>
                            <div class="col-md-3 d-grid">
                                <button type="submit" class="btn btn-sm btn-primary rounded-3 fw-semibold">
                                    <i class="fas fa-save me-1"></i> حفظ وأرشفة
                                </button>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-3 mt-2 pt-2 border-top small">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_official" value="1" id="official_{{ $curr->id }}" {{ $curr->is_official ? 'checked' : '' }}>
                                <label class="form-check-label" for="official_{{ $curr->id }}">سعر رسمي</label>
                            </div>
                            @if(! $curr->is_default)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active_{{ $curr->id }}" {{ $curr->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label" for="active_{{ $curr->id }}">مفعّلة في السندات</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="default_{{ $curr->id }}">
                                    <label class="form-check-label text-primary fw-semibold" for="default_{{ $curr->id }}">تعيين كعملة أساسية</label>
                                </div>
                            @endif
                        </div>
                    </form>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 2. Exchange Rate Differences (FX Revaluation) -->
    <div class="col-12 col-xl-5">
        <div class="bg-white rounded-4 shadow-sm border border-slate-200 p-3 p-md-4 h-100">
            <div class="mb-3 pb-3 border-bottom">
                <h5 class="fw-bold text-dark mb-1">
                    <i class="fas fa-exchange-alt text-warning me-1"></i> فروق أسعار الصرف وإعادة التقييم
                </h5>
                <p class="text-muted small mb-0">
                    احتساب فروق الصرف على الأرصدة المفتوحة بالعملات الأجنبية وترحيلها آلياً إلى حسابات:
                    <span class="badge bg-success-subtle text-success border font-monospace">43551 أرباح فروق الصرف</span>
                    أو
                    <span class="badge bg-danger-subtle text-danger border font-monospace">33551 خسائر فروق الصرف</span>
                </p>
            </div>

            @if(session('revaluation_entry_id'))
                <div class="alert alert-info rounded-3 py-2 px-3 small d-flex justify-content-between align-items-center mb-3">
                    <span>تم ترحيل قيد التسوية بنجاح.</span>
                    <a href="{{ route('accounting.vouchers.print', session('revaluation_entry_id')) }}" class="btn btn-sm btn-primary rounded-3">
                        <i class="fas fa-print me-1"></i> طباعة قيد التسوية
                    </a>
                </div>
            @endif

            @forelse($revaluations as $reval)
                @php $curr = $reval['currency']; @endphp
                <div class="rounded-4 border p-3 mb-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-dark">
                            <span class="badge bg-dark font-monospace me-1">{{ $curr->code }}</span>
                            {{ $curr->name }}
                        </span>
                        <span class="small text-muted">السعر الحالي: <strong class="font-monospace">{{ number_format((float) $curr->exchange_rate, 2) }}</strong></span>
                    </div>

                    @if(empty($reval['accounts']))
                        <div class="text-muted small py-2">
                            <i class="fas fa-check-circle text-success me-1"></i> لا توجد أرصدة نقدية/ذمم مفتوحة بهذه العملة أو لا توجد فروق صرف مستحقة حالياً.
                        </div>
                    @else
                        <div class="table-responsive border rounded-3 bg-white mb-2">
                            <table class="table table-sm mb-0 small text-center align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start">الحساب</th>
                                        <th>الرصيد الأجنبي</th>
                                        <th>الدفتري</th>
                                        <th>المعاد تقييمه</th>
                                        <th>فرق الصرف</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reval['accounts'] as $item)
                                        <tr>
                                            <td class="text-start">
                                                <span class="font-monospace text-muted">{{ $item['account']->code }}</span>
                                                <div class="fw-semibold text-dark text-truncate" style="max-width: 130px;">{{ $item['account']->name }}</div>
                                            </td>
                                            <td class="font-monospace">{{ number_format($item['net_fx'], 2) }} {{ $curr->symbol }}</td>
                                            <td class="font-monospace">{{ number_format($item['book_base'], 0) }}</td>
                                            <td class="font-monospace">{{ number_format($item['revalued_base'], 0) }}</td>
                                            <td class="font-monospace fw-bold {{ $item['difference'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $item['difference'] >= 0 ? '+' : '' }}{{ number_format($item['difference'], 2) }}
                                                <small class="d-block fw-normal">{{ $item['difference'] >= 0 ? 'ربح صرف' : 'خسارة صرف' }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2">
                            <div class="small">
                                صافي الفارق:
                                <strong class="font-monospace {{ $reval['total_difference'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $reval['total_difference'] >= 0 ? '+' : '' }}{{ number_format($reval['total_difference'], 2) }} د.ع
                                </strong>
                            </div>
                            @if(abs($reval['total_abs_difference']) >= 0.01)
                                <form action="{{ route('accounting.settings.currencies.revalue', $curr->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning rounded-3 fw-bold">
                                        <i class="fas fa-balance-scale me-1"></i> ترحيل قيد تسوية فروق الصرف
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-muted small mb-0">لا توجد عملات أجنبية مسجلة لإعادة التقييم.</p>
            @endforelse

            <!-- Fiscal Years Quick Summary -->
            <div class="mt-4 pt-3 border-top">
                <h6 class="fw-bold text-dark mb-2"><i class="fas fa-calendar-alt text-muted me-1"></i> السنوات المالية المسجلة</h6>
                <div class="d-flex flex-column gap-2">
                    @foreach($fiscalYears as $fy)
                        <div class="d-flex justify-content-between align-items-center p-2 px-3 rounded-3 bg-light border small">
                            <div>
                                <span class="fw-bold text-dark">{{ $fy->name }}</span>
                                <span class="text-muted ms-2 font-monospace">({{ $fy->start_date->format('Y-m-d') }} ➔ {{ $fy->end_date->format('Y-m-d') }})</span>
                            </div>
                            <span class="badge {{ $fy->is_closed ? 'bg-danger' : 'bg-success' }}">
                                {{ $fy->is_closed ? 'مقفلة' : 'مفتوحة' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Currency & Exchange Rate Audit Archive -->
    <div class="col-12">
        <div class="bg-white rounded-4 shadow-sm border border-slate-200 p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fas fa-history text-secondary me-1"></i> أرشيف تغييرات العملة وأسعار الصرف والتدقيق
                    </h5>
                    <p class="text-muted small mb-0">توثيق غير قابل للتعديل لكل من قام بتعديل العملة أو سعر الصرف أو ترحيل تسوية فروق الصرف مع السعر قبل وبعد والسبب</p>
                </div>
                <span class="badge bg-secondary rounded-pill px-3 py-2">{{ $currencyLogs->count() }} سجل</span>
            </div>

            <div class="table-responsive border rounded-3 overflow-hidden">
                <table class="table table-hover align-middle mb-0 small text-center">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ والوقت</th>
                            <th>المسؤول عن التغيير</th>
                            <th>العملية</th>
                            <th>العملة</th>
                            <th>قبل التغيير</th>
                            <th>بعد التغيير</th>
                            <th>فارق السعر / الأثر</th>
                            <th class="text-start">السبب والملاحظة</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($currencyLogs as $log)
                            @php
                                $old = $log->old_values ?? [];
                                $new = $log->new_values ?? [];
                                $actionMap = [
                                    'currency_updated' => ['تعديل سعر/عملة', 'bg-primary-subtle text-primary'],
                                    'currency_created' => ['إضافة عملة', 'bg-success-subtle text-success'],
                                    'currency_revalued' => ['تسوية فروق صرف', 'bg-warning-subtle text-dark'],
                                ];
                                [$actionLabel, $actionCls] = $actionMap[$log->action] ?? [$log->action, 'bg-light text-dark'];
                            @endphp
                            <tr>
                                <td class="font-monospace text-muted">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                <td class="fw-semibold text-dark">{{ $log->user?->name ?? 'النظام' }}</td>
                                <td><span class="badge {{ $actionCls }} border">{{ $actionLabel }}</span></td>
                                <td class="font-monospace fw-bold">{{ $new['code'] ?? ($old['code'] ?? '-') }}</td>
                                <td class="font-monospace text-muted">
                                    @if(isset($old['exchange_rate']))
                                        {{ number_format((float) $old['exchange_rate'], 2) }}
                                    @elseif(isset($old['book_total']))
                                        دفتري: {{ number_format((float) $old['book_total'], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="font-monospace fw-bold text-dark">
                                    @if(isset($new['exchange_rate']) && $log->action !== 'currency_revalued')
                                        {{ number_format((float) $new['exchange_rate'], 2) }}
                                    @elseif(isset($new['revalued_total']))
                                        جديد: {{ number_format((float) $new['revalued_total'], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="font-monospace fw-bold">
                                    @if(isset($new['rate_diff']))
                                        <span class="{{ $new['rate_diff'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $new['rate_diff'] >= 0 ? '+' : '' }}{{ number_format((float) $new['rate_diff'], 2) }}
                                        </span>
                                    @elseif(isset($new['net_difference']))
                                        <span class="{{ $new['net_difference'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $new['net_difference'] >= 0 ? '+' : '' }}{{ number_format((float) $new['net_difference'], 2) }} د.ع
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-start">{{ $log->reason ?? '-' }}</td>
                                <td class="font-monospace text-muted">{{ $log->ip_address ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-muted py-4">لا توجد تغييرات مؤرشفة على أسعار الصرف حتى الآن.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
