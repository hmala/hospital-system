@extends('layouts.app')

@section('title', 'كاشير العناية المركزة وكشف الحساب (CCU) - ' . ($admission->patient?->user?->name ?? 'مريض'))

@section('content')
<div class="container-fluid py-4" dir="rtl">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- الرأس العلوي -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="fas fa-cash-register text-success me-2"></i>
                كاشير وتسوية حساب العناية المركزة للكبار (CCU) #{{ $admission->id }}
            </h3>
            <div class="text-muted small d-flex flex-wrap gap-3">
                <span><strong>المريض:</strong> {{ $admission->patient?->user?->name ?? 'غير مسجل' }}</span>
                <span><strong>السرير:</strong> {{ $admission->bed?->bed_number }} ({{ $admission->bed?->type_name }})</span>
                <span><strong>الطبيب:</strong> {{ $admission->doctor?->user?->name ?? 'مناوب' }}</span>
                @if($stayDetails['is_insured'])
                    <span class="badge bg-success">مشمول بالضمان الصحي (فئة {{ $stayDetails['policy_category'] }})</span>
                @else
                    <span class="badge bg-secondary">نقدي (كاش)</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('icu.show', $admission->id) }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-file-medical me-1"></i> العودة لطبلة المريض
            </a>
            <a href="{{ route('cashier.ccu.itemized-receipt.print', $admission->id) }}" target="_blank" class="btn btn-dark btn-sm fw-bold">
                <i class="fas fa-print me-1"></i> طباعة كشف الحساب والمطالبة التفصيلي
            </a>
            <a href="{{ route('cashier.index', ['tab' => 'ccu']) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> لوحة الكاشير
            </a>
        </div>
    </div>

    <!-- بطاقات الملخص المالي وشريط الرقود السنوي -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white border-start border-4 border-primary">
                <div class="small text-muted mb-1">الصافي المستحق على المريض</div>
                <h4 class="fw-bold text-primary mb-1 font-monospace">{{ number_format($stayDetails['patient_grand_total']) }} د.ع</h4>
                <small class="text-muted">المدفوع حتى الآن: {{ number_format($stayDetails['total_paid']) }} د.ع</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white border-start border-4 border-danger">
                <div class="small text-muted mb-1">المتبقي بذمة المريض</div>
                <h4 class="fw-bold text-danger mb-1 font-monospace">{{ number_format($stayDetails['net_balance']) }} د.ع</h4>
                @if($stayDetails['excess_deposit'] > 0)
                    <span class="badge bg-warning text-dark">فائض قابل للاسترجاع: {{ number_format($stayDetails['excess_deposit']) }} د.ع</span>
                @else
                    <small class="text-muted">{{ $stayDetails['days_count'] }} يوم فندقي محتسب</small>
                @endif
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white border-start border-4 border-success">
                <div class="small text-muted mb-1">حصة هيئة الضمان (المطالبة الفعلية)</div>
                <h4 class="fw-bold text-success mb-1 font-monospace">{{ number_format($stayDetails['insurance_grand_total']) }} د.ع</h4>
                <small class="text-muted">
                    المُحصّل: {{ number_format($stayDetails['hia_collected_amount']) }} | الباقي بالضمان: <strong class="text-success">{{ number_format($stayDetails['hia_remaining_balance']) }} د.ع</strong>
                </small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white border-start border-4 border-info">
                <div class="small text-muted mb-1">القيمة الأصلية للفحوصات المشمولة (للمطالبة)</div>
                <h4 class="fw-bold text-info mb-1 font-monospace">{{ number_format($stayDetails['bundled_reference_total']) }} د.ع</h4>
                <small class="text-muted">تظهر 0 د.ع على المريض • الإجمالي المرجعي: {{ number_format($stayDetails['reference_gross_with_bundled']) }}</small>
            </div>
        </div>
    </div>

    @if($stayDetails['is_insured'])
        @php
            $usedPct = $stayDetails['annual_max_days'] > 0
                ? min(100, round(($stayDetails['total_annual_days_with_current'] / $stayDetails['annual_max_days']) * 100))
                : 0;
        @endphp
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2 small flex-wrap gap-2">
                <span>
                    <i class="fas fa-calendar-alt text-success me-1"></i>
                    <strong>السقف السنوي لتغطية الضمان بالردهات الحرجة:</strong>
                    أيام سابقة: <strong>{{ $stayDetails['prior_annual_used_days'] }}</strong> يوم +
                    الحالية: <strong>{{ $stayDetails['days_count'] }}</strong> يوم =
                    <strong>{{ $stayDetails['total_annual_days_with_current'] }}</strong> من أصل <strong>{{ $stayDetails['annual_max_days'] }}</strong> يوم بالسنة
                </span>
                <span class="badge bg-info text-dark">المتبقي من الرصيد السنوي: {{ $stayDetails['remaining_annual_days'] }} يوم</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar {{ $usedPct >= 90 ? 'bg-danger' : 'bg-success' }}" style="width: {{ $usedPct }}%"></div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <!-- العمود الأيمن: تفاصيل اليوميات، الشرائح، التحاليل، الأشعة، والخدمات -->
        <div class="col-lg-8">
            <!-- تبديل الباقة أو إضافة خدمة منفصلة (كاش) -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <div class="row g-3 align-items-end">
                    <div class="col-md-7">
                        <form action="{{ route('ccu.admissions.switch-service', $admission->id) }}" method="POST" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-8">
                                <label class="form-label small fw-bold text-primary mb-1">الباقة / التسعيرة اليومية المعتمدة</label>
                                <select name="ccu_service_id" class="form-select form-select-sm" required>
                                    @foreach($packageServices as $srv)
                                        <option value="{{ $srv->id }}" {{ ($stayDetails['active_service']?->id ?? $admission->ccu_service_id) == $srv->id ? 'selected' : '' }}>
                                            {{ $srv->name }} ({{ number_format($stayDetails['is_insured'] && $srv->is_hi_active ? $srv->hi_price : $srv->daily_fee) }} د.ع)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-sm btn-outline-primary fw-bold w-100">
                                    <i class="fas fa-sync-alt me-1"></i> تبديل الباقة
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-5 text-md-end">
                        <button class="btn btn-sm btn-outline-warning text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#cashierAddonCollapse">
                            <i class="fas fa-plus-circle me-1"></i> إضافة خدمة مخصصة / كاش منفصل
                        </button>
                    </div>
                </div>

                <div class="collapse mt-3 pt-3 border-top" id="cashierAddonCollapse">
                    <form action="{{ route('ccu.admissions.addon-service', $admission->id) }}" method="POST" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">الخدمة الإضافية الجاهزة</label>
                            <select name="ccu_service_id" class="form-select form-select-sm">
                                <option value="">-- خدمة حرة مخصصة --</option>
                                @foreach($addonServices as $addon)
                                    <option value="{{ $addon->id }}">{{ $addon->name }} ({{ number_format($addon->daily_fee) }} د.ع)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1">اسم مخصص</label>
                            <input type="text" name="custom_name" class="form-control form-control-sm" placeholder="مثال: جهاز منفصل">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold mb-1">السعر</label>
                            <input type="number" name="unit_price" class="form-control form-control-sm" min="0" step="1000">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small fw-bold mb-1">الكمية</label>
                            <input type="number" name="quantity" class="form-control form-control-sm" value="1" min="1">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold w-100">إضافة</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- تفاصيل الأيام والشرائح التنازلية -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-calendar-check text-primary me-1"></i> تفصيل الإقامة اليومية وشرائح التغطية
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th>اليوم</th>
                                <th>اليوم السنوي</th>
                                <th class="text-start">الباقة / نظام التسعير</th>
                                <th>الأجرة اليومية</th>
                                <th>الشريحة المطبقة</th>
                                <th>حصة الضمان</th>
                                <th>حصة المريض</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stayDetails['daily_coverage_details'] as $dayRow)
                                <tr>
                                    <td class="fw-bold">#{{ $dayRow['stay_day_number'] }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $dayRow['annual_day_number'] }} / {{ $stayDetails['annual_max_days'] }}</span></td>
                                    <td class="text-start">{{ $dayRow['service_name'] }}</td>
                                    <td class="font-monospace">{{ number_format($dayRow['daily_rate']) }}</td>
                                    <td><span class="badge bg-info-subtle text-dark border border-info-subtle">{{ $dayRow['tier_label'] }}</span></td>
                                    <td class="text-success fw-bold font-monospace">{{ number_format($dayRow['hia_share']) }}</td>
                                    <td class="text-danger fw-bold font-monospace">{{ number_format($dayRow['patient_share']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if(count($stayDetails['addon_breakdown']) > 0)
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-plus-square text-warning me-1"></i> الخدمات المنفصلة والمضافة للحساب
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">الخدمة</th>
                                <th>سعر الوحدة</th>
                                <th>الكمية</th>
                                <th>حصة الضمان</th>
                                <th>حصة المريض</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stayDetails['addon_breakdown'] as $addon)
                                <tr>
                                    <td class="text-start fw-bold">{{ $addon['service_name'] }}</td>
                                    <td class="font-monospace">{{ number_format($addon['unit_fee']) }}</td>
                                    <td>{{ $addon['quantity'] }}</td>
                                    <td class="text-success font-monospace">{{ number_format($addon['insurance_share']) }}</td>
                                    <td class="text-danger fw-bold font-monospace">{{ number_format($addon['patient_share']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- جدول تحاليل المختبر والأشعة مع زر الاحتساب بضغطة زر -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-vials text-primary me-1"></i> تحاليل المختبر والأشعة (شمول بالباقة 0 د.ع ↔ احتساب منفرد بضغطة زر)
                    </h6>
                    <span class="badge bg-info-subtle text-dark">القيمة الأصلية محفوظة تلقائياً للمطالبات</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th>النوع</th>
                                <th class="text-start">الفحص</th>
                                <th>القيمة الأصلية (للمطالبة)</th>
                                <th>المحتسب على الفاتورة</th>
                                <th>حصة المريض</th>
                                <th>حصة الضمان</th>
                                <th>تبديل بضغطة زر</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stayDetails['medical_orders'] as $ord)
                                <tr>
                                    <td>
                                        <span class="badge {{ $ord->order_type === 'lab' ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-dark' }}">
                                            {{ $ord->order_type === 'lab' ? 'مختبر' : 'أشعة' }}
                                        </span>
                                    </td>
                                    <td class="text-start fw-bold">{{ $ord->labTest?->name ?? $ord->radiologyType?->name ?? $ord->item_name }}</td>
                                    <td class="font-monospace text-muted">{{ number_format($ord->original_price_display ?? $ord->original_price ?? 0) }} د.ع</td>
                                    <td class="font-monospace fw-bold">
                                        @if($ord->is_bundled)
                                            <span class="badge bg-success-subtle text-success">0 د.ع (مشمول)</span>
                                        @else
                                            {{ number_format($ord->billed_price ?? 0) }} د.ع
                                        @endif
                                    </td>
                                    <td class="font-monospace text-danger fw-bold">{{ number_format($ord->patient_share ?? 0) }}</td>
                                    <td class="font-monospace text-success">{{ number_format($ord->insurance_share ?? 0) }}</td>
                                    <td>
                                        <form action="{{ route('cashier.ccu.orders.toggle-billing', $ord->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="billing_mode" value="{{ $ord->is_bundled ? 'extra' : 'bundled' }}">
                                            <button type="submit" class="btn btn-sm {{ $ord->is_bundled ? 'btn-outline-warning text-dark' : 'btn-outline-success' }} fw-bold py-0 px-2">
                                                <i class="fas fa-toggle-{{ $ord->is_bundled ? 'off' : 'on' }} me-1"></i>
                                                {{ $ord->is_bundled ? 'احتساب منفرد' : 'شمول (0)' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted py-4">لا توجد طلبات تحاليل أو أشعة مسجلة لهذه الإضبارة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- جدول الاستشارات الطبية -->
            @if($stayDetails['consultations']->isNotEmpty())
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-stethoscope text-primary me-1"></i> استشارات الأطباء التخصصية</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">الاستشاري / التخصص</th>
                                <th>القيمة الأصلية</th>
                                <th>الحالة</th>
                                <th>حصة الضمان</th>
                                <th>حصة المريض</th>
                                <th>بضغطة زر</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stayDetails['consultations'] as $cons)
                                <tr>
                                    <td class="text-start fw-bold">{{ $cons->consultant_display_name }} ({{ $cons->specialty }})</td>
                                    <td class="font-monospace">{{ number_format($cons->original_fee_display ?? $cons->fee) }}</td>
                                    <td><span class="badge {{ $cons->coverage_badge_class }}">{{ $cons->coverage_status_text }}</span></td>
                                    <td class="text-success font-monospace">{{ number_format($cons->insurance_share ?? 0) }}</td>
                                    <td class="text-danger fw-bold font-monospace">{{ number_format($cons->patient_share ?? 0) }}</td>
                                    <td>
                                        <form action="{{ route('cashier.ccu.consultations.toggle-billing', $cons->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="billing_mode" value="{{ ($cons->is_bundled ?? false) ? 'extra' : 'bundled' }}">
                                            <button type="submit" class="btn btn-sm {{ ($cons->is_bundled ?? false) ? 'btn-outline-warning text-dark' : 'btn-outline-success' }} py-0 px-2">
                                                {{ ($cons->is_bundled ?? false) ? 'احتساب منفرد' : 'شمول (0)' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <!-- العمود الأيسر: صندوق التحصيل / الاسترجاع + سجل الدفعات + تسوية مطالبة الضمان -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h5 class="fw-bold text-dark mb-3">
                    <i class="fas fa-wallet text-success me-1"></i> استلام دفعة / تسوية حساب
                </h5>
                <form action="{{ route('cashier.ccu.payment', $admission->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-bold">المبلغ (د.ع)</label>
                        <input type="number" name="amount" class="form-control font-monospace fw-bold fs-5 text-success"
                               value="{{ $stayDetails['net_balance'] > 0 ? $stayDetails['net_balance'] : ($stayDetails['excess_deposit'] > 0 ? $stayDetails['excess_deposit'] : 0) }}"
                               min="1" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">نوع العملية</label>
                            <select name="payment_type" class="form-select form-select-sm">
                                <option value="interim" {{ $stayDetails['net_balance'] > 0 ? 'selected' : '' }}>دفعة تحت الحساب</option>
                                <option value="deposit">سلفة / تأمينات دخول</option>
                                <option value="final_settlement" {{ $admission->status === 'discharged' ? 'selected' : '' }}>تصفية نهائية عند الخروج</option>
                                @if($stayDetails['excess_deposit'] > 0)
                                    <option value="refund">صرف استرجاع فائض ({{ number_format($stayDetails['excess_deposit']) }})</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">طريقة الدفع</label>
                            <select name="payment_method" class="form-select form-select-sm">
                                <option value="cash">نقدي (كاش)</option>
                                <option value="card">بطاقة / POS</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">البيان / ملاحظات</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="مثال: تسديد حساب الرقود للعناية المركزة">
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm">
                        <i class="fas fa-check-circle me-1"></i> تثبيت العملية وإصدار الإيصال
                    </button>
                </form>
            </div>

            @if($stayDetails['is_insured'])
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-light">
                <h6 class="fw-bold text-success mb-2">
                    <i class="fas fa-shield-alt me-1"></i> متابعة مطالبة هيئة الضمان الصحي
                </h6>
                <div class="small mb-2 d-flex justify-content-between">
                    <span>صافي المطالبة الفعلية للهيئة:</span>
                    <strong class="font-monospace">{{ number_format($stayDetails['insurance_grand_total']) }} د.ع</strong>
                </div>
                <div class="small mb-3 d-flex justify-content-between">
                    <span>الباقي بذمة الضمان الصحي:</span>
                    <strong class="text-danger font-monospace">{{ number_format($stayDetails['hia_remaining_balance']) }} د.ع</strong>
                </div>
                <form action="{{ route('cashier.ccu.hia-collection', $admission->id) }}" method="POST" class="d-flex gap-2">
                    @csrf
                    <input type="number" name="hia_collected_amount" value="{{ (float) $stayDetails['hia_collected_amount'] }}" min="0" step="1000" class="form-control form-control-sm font-monospace" placeholder="المُحصّل من الضمان">
                    <button type="submit" class="btn btn-sm btn-outline-success fw-bold text-nowrap">تحديث التحصيل</button>
                </form>
            </div>
            @endif

            <!-- سجل الإيصالات والدفعات -->
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-history text-secondary me-1"></i> سجل الحركات المالية للإضبارة</h6>
                @forelse($stayDetails['payments'] as $pay)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom small">
                        <div>
                            <div class="fw-bold font-monospace {{ $pay->payment_type === 'refund' ? 'text-danger' : 'text-success' }}">
                                {{ $pay->payment_type === 'refund' ? '-' : '+' }}{{ number_format($pay->amount) }} د.ع
                            </div>
                            <div class="text-muted" style="font-size: 11px;">{{ $pay->receipt_number }} • {{ optional($pay->paid_at)->format('Y-m-d H:i') }}</div>
                        </div>
                        <a href="{{ route('cashier.ccu.receipt', $pay->id) }}" class="btn btn-sm btn-outline-dark py-0 px-2">الوصل</a>
                    </div>
                @empty
                    <p class="text-muted small mb-0 text-center py-3">لا توجد دفعات مسجلة لهذه الإضبارة حتى الآن.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
