@extends('layouts.app')

@section('title', 'سجل مطالبات وخدمات الضمان الصحي')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="#">الحسابات والمالية</a></li>
                    <li class="breadcrumb-item active" aria-current="page">مطالبات الضمان الصحي</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="fas fa-shield-alt text-primary"></i>
                <span>سجل مطالبات وخدمات الضمان الصحي</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-7 fw-normal">كشف تفصيلي تنفيذي</span>
            </h3>
            <p class="text-muted small mb-0">متابعة دقيقة لكل مريض وفئة ضمانه، الخدمات المقدمة، أسعار الخدمات، المبالغ المقبوضة نقداً، ومطالبات المستشفى لدى هيئة الضمان</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 d-print-none">
            <a href="{{ route('insurance.claims.export', request()->query()) }}" class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-sm">
                <i class="fas fa-file-excel me-1.5"></i> تصدير إكسيل (Excel)
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold bg-white shadow-sm">
                <i class="fas fa-print me-1.5"></i> طباعة الكشف
            </button>
            <a href="{{ route('treasury.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold bg-white shadow-sm">
                <i class="fas fa-vault me-1.5"></i> خزينة المستشفى
            </a>
        </div>
    </div>

    <!-- بطاقات المؤشرات المالية للضمان (KPI Cards) -->
    <div class="row g-3 mb-4">
        <!-- 1. إجمالي مطالبات الضمان -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 border-start border-4 border-info d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem;">إجمالي مطالبات الضمان</span>
                            <div class="rounded-circle p-1 bg-info bg-opacity-10 text-info" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-shield-halved" style="font-size: 0.85rem;"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold text-info mb-1">
                            {{ number_format($kpis->total_insurance_share ?? 0) }} <small class="fs-7 text-muted">د.ع</small>
                        </h4>
                    </div>
                    <div class="small text-muted mt-2 d-flex justify-content-between align-items-center" style="font-size: 0.73rem;">
                        <span>ذمم مدينة قيد التحصيل</span>
                        <span class="badge bg-info-subtle text-info fw-bold">حصة الضمان</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. إجمالي المدفوع نقداً من المرضى -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 border-start border-4 border-success d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem;">تحمل المرضى نقداً</span>
                            <div class="rounded-circle p-1 bg-success bg-opacity-10 text-success" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-hand-holding-dollar" style="font-size: 0.85rem;"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold text-success mb-1">
                            + {{ number_format($kpis->total_patient_paid ?? 0) }} <small class="fs-7 text-muted">د.ع</small>
                        </h4>
                    </div>
                    <div class="small text-muted mt-2 d-flex justify-content-between align-items-center" style="font-size: 0.73rem;">
                        <span>نقد مقبوض في الصندوق</span>
                        <span class="badge bg-success-subtle text-success fw-bold">نسبة التحمل (Copay)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. إجمالي القيمة الاستحقاقية للخدمات -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 border-start border-4 border-primary d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem;">إجمالي أسعار الخدمات</span>
                            <div class="rounded-circle p-1 bg-primary bg-opacity-10 text-primary" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-file-invoice-dollar" style="font-size: 0.85rem;"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold text-primary mb-1">
                            {{ number_format($kpis->total_gross ?? 0) }} <small class="fs-7 text-muted">د.ع</small>
                        </h4>
                    </div>
                    <div class="small text-muted mt-2 d-flex justify-content-between align-items-center" style="font-size: 0.73rem;">
                        <span>(نقد المريض + حصة الضمان)</span>
                        <span class="badge bg-primary-subtle text-primary fw-bold">استحقاق كلي</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. عدد المطالبات والخدمات -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 border-start border-4 border-warning d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem;">عدد الخدمات المسددة</span>
                            <div class="rounded-circle p-1 bg-warning bg-opacity-10 text-warning" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-receipt" style="font-size: 0.85rem;"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">
                            {{ number_format($kpis->total_claims ?? 0) }} <small class="fs-7 text-muted">إيصال</small>
                        </h4>
                    </div>
                    <div class="small text-muted mt-2 d-flex justify-content-between align-items-center" style="font-size: 0.73rem;">
                        <span>إجمالي حركات الضمان</span>
                        <span class="badge bg-warning-subtle text-dark fw-bold">عمليات مسجلة</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. عدد المرضى المستفيدين -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 border-start border-4 border-dark d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem;">المرضى المستفيدين</span>
                            <div class="rounded-circle p-1 bg-dark bg-opacity-10 text-dark" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-users" style="font-size: 0.85rem;"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">
                            {{ number_format($kpis->total_patients ?? 0) }} <small class="fs-7 text-muted">مريض</small>
                        </h4>
                    </div>
                    <div class="small text-muted mt-2 d-flex justify-content-between align-items-center" style="font-size: 0.73rem;">
                        <span>مرضى فريدين بالفترة</span>
                        <span class="badge bg-light text-dark border fw-bold">مشمول بالضمان</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط ملخص فئات الضمان السريع -->
    @if($categoryBreakdown->isNotEmpty())
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white d-print-none">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <span class="fw-bold text-dark small"><i class="fas fa-tags text-primary me-1"></i> توزيع المطالبات حسب فئات الضمان للمرضى:</span>
                <span class="text-muted small">انقر على أي فئة للفلترة الفورية</span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($categoryBreakdown as $cb)
                <a href="{{ route('insurance.claims.index', array_merge(request()->except(['category_id', 'page']), ['category_id' => $categories->firstWhere('name', $cb->cat_name)?->id])) }}" 
                   class="btn btn-sm {{ request('category_id') == $categories->firstWhere('name', $cb->cat_name)?->id ? 'btn-primary' : 'btn-light border' }} rounded-pill px-3 py-1 text-decoration-none d-flex align-items-center gap-2">
                    <span class="fw-bold">{{ $cb->cat_name }}</span>
                    @if($cb->cat_code && $cb->cat_code !== 'N/A')
                        <span class="badge bg-secondary-subtle text-secondary">{{ $cb->cat_code }}</span>
                    @endif
                    <span class="badge bg-primary text-white rounded-pill">{{ number_format($cb->claims_count) }} مطالبة</span>
                    <span class="fw-bold small text-info">{{ number_format($cb->total_share) }} د.ع</span>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- فلتر البحث المتقدم وشريط التحكم -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white d-print-none">
        <div class="card-body p-3">
            <form action="{{ route('insurance.claims.index') }}" method="GET" id="insuranceClaimsFilterForm">
                <!-- شريط أزرار الفترات السريعة -->
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <span class="small fw-bold text-muted me-1"><i class="fas fa-calendar-alt text-primary me-1"></i> الفترة السريعة:</span>
                    <button type="button" class="btn btn-xs rounded-pill px-2.5 py-1 fw-bold {{ request('quick_period') == 'today' ? 'btn-primary' : 'btn-light border' }}" onclick="applyQuickPeriod('today')">اليوم</button>
                    <button type="button" class="btn btn-xs rounded-pill px-2.5 py-1 fw-bold {{ request('quick_period') == 'yesterday' ? 'btn-primary' : 'btn-light border' }}" onclick="applyQuickPeriod('yesterday')">أمس</button>
                    <button type="button" class="btn btn-xs rounded-pill px-2.5 py-1 fw-bold {{ request('quick_period') == 'this_week' ? 'btn-primary' : 'btn-light border' }}" onclick="applyQuickPeriod('this_week')">هذا الأسبوع</button>
                    <button type="button" class="btn btn-xs rounded-pill px-2.5 py-1 fw-bold {{ request('quick_period') == 'this_month' ? 'btn-primary' : 'btn-light border' }}" onclick="applyQuickPeriod('this_month')">هذا الشهر</button>
                    <button type="button" class="btn btn-xs rounded-pill px-2.5 py-1 fw-bold {{ request('quick_period') == 'last_month' ? 'btn-primary' : 'btn-light border' }}" onclick="applyQuickPeriod('last_month')">الشهر السابق</button>
                    <button type="button" class="btn btn-xs rounded-pill px-2.5 py-1 fw-bold {{ request('quick_period') == 'this_year' ? 'btn-primary' : 'btn-light border' }}" onclick="applyQuickPeriod('this_year')">هذا العام</button>
                    <button type="button" class="btn btn-xs rounded-pill px-2.5 py-1 fw-bold {{ request('quick_period') == 'all' || (!request('quick_period') && !request('from_date') && !request('to_date')) ? 'btn-primary' : 'btn-light border' }}" onclick="applyQuickPeriod('all')">كل الأوقات</button>
                    <input type="hidden" name="quick_period" id="quickPeriodInput" value="{{ request('quick_period') }}">
                </div>

                <div class="row g-2 align-items-end">
                    <!-- من تاريخ -->
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label small text-muted mb-1 fw-semibold">من تاريخ</label>
                        <input type="date" name="from_date" class="form-control form-control-sm rounded-3" value="{{ request('from_date') }}">
                    </div>

                    <!-- إلى تاريخ -->
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label small text-muted mb-1 fw-semibold">إلى تاريخ</label>
                        <input type="date" name="to_date" class="form-control form-control-sm rounded-3" value="{{ request('to_date') }}">
                    </div>

                    <!-- فئة الضمان للمريض -->
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label small text-muted mb-1 fw-semibold">فئة ضمان المريض</label>
                        <select name="category_id" class="form-select form-select-sm rounded-3">
                            <option value="">جميع فئات الضمان</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }} {{ $category->code ? '(' . $category->code . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- نوع الخدمة / القسم -->
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label small text-muted mb-1 fw-semibold">نوع الخدمة / القسم</label>
                        <select name="payment_type" class="form-select form-select-sm rounded-3">
                            <option value="">جميع الأقسام</option>
                            @foreach($paymentTypes as $key => $label)
                                <option value="{{ $key }}" {{ request('payment_type') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- الطبيب المعالج / الاستشاري -->
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label small text-muted mb-1 fw-semibold">الطبيب المعالج / الاستشاري</label>
                        <select name="doctor_id" class="form-select form-select-sm rounded-3">
                            <option value="">جميع الأطباء</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}" {{ request('doctor_id') == $doc->id ? 'selected' : '' }}>
                                    د. {{ preg_replace('/^(د\.\s*)+/u', '', $doc->user?->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- البحث النصي -->
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label small text-muted mb-1 fw-semibold">بحث سريع</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control rounded-start-3" placeholder="المريض، البطاقة، الإيصال..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary rounded-end-3" title="بحث">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                    <span class="small text-muted">
                        <i class="fas fa-filter text-secondary me-1"></i> الفلاتر الحالية تعرض: 
                        <strong class="text-primary">{{ $claims->total() }}</strong> سجل مطالبة
                    </span>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">
                            <i class="fas fa-check me-1"></i> تطبيق الفلترة
                        </button>
                        <a href="{{ route('insurance.claims.index') }}" class="btn btn-light btn-sm border rounded-pill px-3 text-muted">
                            <i class="fas fa-undo me-1"></i> إعادة ضبط
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول سجلات ومطالبات الضمان الصحي التفصيلي -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="fas fa-list-check text-primary"></i>
                <span>تفاصيل مطالبات الخدمات الطبية</span>
                <span class="badge bg-light text-dark border fw-normal fs-7">عرض {{ $claims->firstItem() ?? 0 }} إلى {{ $claims->lastItem() ?? 0 }} من أصل {{ $claims->total() }}</span>
            </h5>
            <div class="d-flex gap-2 d-print-none">
                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 small">
                    <i class="fas fa-info-circle me-1"></i> يتم احتساب الأسعار استحقاقياً
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap" id="insuranceClaimsTable">
                <thead class="table-light border-top border-bottom" style="font-size: 0.8rem;">
                    <tr>
                        <th class="ps-3 py-3" style="width: 50px;">#</th>
                        <th>رقم الإيصال</th>
                        <th>التاريخ والوقت</th>
                        <th>اسم المريض</th>
                        <th>فئة الضمان</th>
                        <th>رقم البطاقة / الباج</th>
                        <th>القسم / نوع الخدمة</th>
                        <th>الطبيب المعالج / الاستشاري</th>
                        <th>تفاصيل الفحص / الخدمة</th>
                        <th class="text-end">السعر الكلي</th>
                        <th class="text-end">تحمل المريض (نقداً)</th>
                        <th class="text-end pe-3">مطالبة الضمان</th>
                        <th class="text-center">التغطية</th>
                    </tr>
                </thead>
                <tbody style="font-size: 0.84rem;">
                    @forelse($claims as $index => $payment)
                    @php
                        $gross = (float)($payment->amount + $payment->insurance_share);
                        $patientPaid = (float)$payment->amount;
                        $insuranceShare = (float)$payment->insurance_share;
                        $copayPercent = $payment->copay_percentage ?? ($gross > 0 ? round(($patientPaid / $gross) * 100, 1) : 0);
                        $coveragePercent = $gross > 0 ? round(($insuranceShare / $gross) * 100, 1) : (100 - $copayPercent);

                        $categoryName = $payment->patient?->healthInsuranceCategory?->name ?? 'غير محدد';
                        $categoryCode = $payment->patient?->healthInsuranceCategory?->code;
                        $cardNo = $payment->insurance_card_no ?: ($payment->patient?->insurance_card_no ?: '—');
                        $doctorName = $payment->attending_doctor_name;

                        // تحديد لون فئة الضمان
                        $catBadgeClass = match($categoryCode) {
                            'A' => 'bg-danger-subtle text-danger border-danger-subtle',
                            'B' => 'bg-warning-subtle text-dark border-warning-subtle',
                            'C' => 'bg-info-subtle text-info border-info-subtle',
                            'D' => 'bg-success-subtle text-success border-success-subtle',
                            'E' => 'bg-primary-subtle text-primary border-primary-subtle',
                            'H' => 'bg-purple-subtle text-purple border-purple-subtle',
                            default => 'bg-light text-dark border-secondary-subtle'
                        };

                        // تحديد شارة القسم
                        $deptBadgeClass = match($payment->payment_type) {
                            'appointment' => 'bg-primary-subtle text-primary border-primary-subtle',
                            'lab' => 'bg-teal-subtle text-teal border-teal-subtle',
                            'radiology' => 'bg-warning-subtle text-dark border-warning-subtle',
                            'emergency' => 'bg-danger-subtle text-danger border-danger-subtle',
                            'surgery' => 'bg-indigo-subtle text-indigo border-indigo-subtle',
                            default => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                        };
                    @endphp
                    <tr>
                        <td class="ps-3 fw-bold text-muted">{{ $claims->firstItem() + $index }}</td>
                        <td>
                            <span class="badge bg-light text-dark border font-monospace fw-bold">
                                {{ $payment->receipt_number ?: '#' . $payment->id }}
                            </span>
                        </td>
                        <td>
                            <div class="text-dark fw-semibold">{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d') : '—' }}</div>
                            <small class="text-muted font-monospace" style="font-size: 0.72rem;">{{ $payment->paid_at ? $payment->paid_at->format('h:i A') : '' }}</small>
                        </td>
                        <td>
                            <div class="fw-bold text-dark d-flex align-items-center gap-1.5">
                                <i class="fas fa-user-circle text-primary opacity-75"></i>
                                <span>{{ $payment->patient?->name ?? 'مريض غير محدد' }}</span>
                            </div>
                            @if($payment->patient?->phone)
                                <small class="text-muted font-monospace" style="font-size: 0.72rem;">{{ $payment->patient->phone }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge border rounded-pill px-2.5 py-1 {{ $catBadgeClass }}">
                                <i class="fas fa-id-badge me-1"></i>
                                <strong>{{ $categoryName }}</strong>
                                @if($categoryCode)
                                    <span class="ms-1 opacity-75">({{ $categoryCode }})</span>
                                @endif
                            </span>
                        </td>
                        <td>
                            @if($cardNo !== '—')
                                <span class="badge bg-light text-dark border font-monospace">
                                    <i class="fas fa-credit-card text-muted me-1"></i>{{ $cardNo }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge border rounded-pill px-2.5 py-1 {{ $deptBadgeClass }}">
                                {{ $payment->payment_type_name }}
                            </span>
                        </td>
                        <td>
                            @if($doctorName)
                                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 fw-semibold text-wrap" style="max-width: 170px;">
                                    <i class="fas fa-user-md text-primary me-1"></i>{{ $doctorName }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-wrap text-dark small" style="max-width: 280px; line-height: 1.35;" title="{{ $payment->service_details_text }}">
                                {{ $payment->service_details_text }}
                            </div>
                        </td>
                        <td class="text-end fw-bold text-dark font-monospace">
                            {{ number_format($gross) }} <small class="text-muted">د.ع</small>
                        </td>
                        <td class="text-end fw-bold text-success font-monospace">
                            {{ number_format($patientPaid) }} <small class="text-muted">د.ع</small>
                        </td>
                        <td class="text-end pe-3 fw-bold text-info font-monospace">
                            {{ number_format($insuranceShare) }} <small class="text-muted">د.ع</small>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2 py-1 font-monospace" title="تغطية الضمان: {{ $coveragePercent }}% | تحمل المريض: {{ $copayPercent }}%">
                                {{ $coveragePercent }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="text-center py-5 text-muted">
                            <div class="d-flex flex-column align-items-center justify-content-center">
                                <i class="fas fa-shield-alt fa-3x text-muted opacity-25 mb-3"></i>
                                <h6 class="fw-bold">لا توجد مطالبات أو خدمات مسجلة مطابقة للبحث المحدد</h6>
                                <p class="small text-muted mb-2">جرب تغيير الفترة الزمنية أو إزالة فلاتر البحث للعثور على السجلات المطلوبة</p>
                                <a href="{{ route('insurance.claims.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                    <i class="fas fa-undo me-1"></i> إعادة ضبط الفلاتر
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($claims->isNotEmpty())
                <tfoot class="table-light border-top fw-bold" style="font-size: 0.85rem;">
                    <tr>
                        <td colspan="9" class="ps-3 py-3 text-end">مجموع الصفحة الحالية:</td>
                        <td class="text-end text-dark font-monospace">
                            {{ number_format($claims->sum(fn($p) => $p->amount + $p->insurance_share)) }} د.ع
                        </td>
                        <td class="text-end text-success font-monospace">
                            {{ number_format($claims->sum('amount')) }} د.ع
                        </td>
                        <td class="text-end pe-3 text-info font-monospace">
                            {{ number_format($claims->sum('insurance_share')) }} د.ع
                        </td>
                        <td class="text-center text-muted">—</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        @if($claims->hasPages())
        <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 d-print-none">
            <span class="text-muted small">
                عرض <strong>{{ $claims->firstItem() }}</strong> إلى <strong>{{ $claims->lastItem() }}</strong> من أصل <strong>{{ $claims->total() }}</strong> مطالبة مسجلة
            </span>
            <div>
                {{ $claims->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

<style>
/* ألوان مخصصة للشارات */
.bg-purple-subtle { background-color: rgba(147, 51, 234, 0.1) !important; color: #9333ea !important; }
.border-purple-subtle { border-color: rgba(147, 51, 234, 0.25) !important; }
.bg-teal-subtle { background-color: rgba(20, 184, 166, 0.1) !important; color: #0d9488 !important; }
.border-teal-subtle { border-color: rgba(20, 184, 166, 0.25) !important; }
.bg-indigo-subtle { background-color: rgba(99, 102, 241, 0.1) !important; color: #4f46e5 !important; }
.border-indigo-subtle { border-color: rgba(99, 102, 241, 0.25) !important; }

/* تنسيقات الطباعة الورقية الفخمة */
@media print {
    body {
        background-color: #fff !important;
        font-size: 11px !important;
    }
    .container-fluid {
        padding: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-header, .table-responsive {
        border: none !important;
    }
    table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    th, td {
        padding: 4px 6px !important;
        border: 1px solid #ddd !important;
    }
    .badge {
        border: none !important;
        background: transparent !important;
        color: #000 !important;
        padding: 0 !important;
    }
    .d-print-none {
        display: none !important;
    }
}
</style>

<script>
function applyQuickPeriod(period) {
    document.getElementById('quickPeriodInput').value = period;
    // تفريغ حقول التاريخ اليدوية عند اختيار فترة سريعة لتجنب التضارب
    document.querySelector('input[name="from_date"]').value = '';
    document.querySelector('input[name="to_date"]').value = '';
    document.getElementById('insuranceClaimsFilterForm').submit();
}
</script>
@endsection
