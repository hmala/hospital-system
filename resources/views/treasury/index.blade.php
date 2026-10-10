@extends('layouts.app')

@section('title', 'خزينة المستشفى المركزية والمصروفات')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="#">الحسابات والمالية</a></li>
                    <li class="breadcrumb-item active" aria-current="page">خزينة المستشفى والمصروفات</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">
                <i class="fas fa-vault text-warning me-2"></i>
                خزينة المستشفى المركزية وحركة النقدية
            </h3>
            <p class="text-muted small mb-0">دفتر الخزينة العام (General Cash Flow Ledger) لربط كافة المقبوضات بمصروفات الأطباء والمشتريات والنثريات</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-danger d-flex align-items-center gap-2 shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#expenseModal">
                <i class="fas fa-minus-circle"></i>
                <span>+ سند صرف مصروفات</span>
            </button>
            <button type="button" class="btn btn-success d-flex align-items-center gap-2 shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#incomeModal">
                <i class="fas fa-plus-circle"></i>
                <span>+ سند قبض إيراد</span>
            </button>
            <a href="{{ route('treasury.export', request()->query()) }}" class="btn btn-outline-success d-flex align-items-center gap-2 shadow-sm">
                <i class="fas fa-file-excel"></i>
                <span>تصدير Excel</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- بطاقات الإحصائيات المالية المتقدمة (KPI Cards) -->
    <div class="row g-3 mb-4">
        <!-- رصيد الخزينة الإجمالي اللحظي -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff;">
                <div class="card-body p-4 position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase fw-bold text-white-50 small">الرصيد الفعلي بالخزينة</span>
                        <div class="rounded-circle p-2 bg-white bg-opacity-10 text-warning">
                            <i class="fas fa-vault fa-lg"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold mb-1 {{ $currentTreasuryBalance >= 0 ? 'text-warning' : 'text-danger' }}">
                        {{ number_format($currentTreasuryBalance) }} <small class="fs-6 text-white-50">د.ع</small>
                    </h2>
                    <div class="small text-white-50 mt-2 d-flex justify-content-between">
                        <span>إجمالي الوارد: {{ number_format($totalAllTimeInflow) }}</span>
                        <span>المنصرف: {{ number_format($totalAllTimeOutflow) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- إجمالي المقبوضات للفترة -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4 border-start border-4 border-success">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase fw-bold text-muted small">إجمالي الوارد (الفترة)</span>
                        <div class="rounded-circle p-2 bg-success bg-opacity-10 text-success">
                            <i class="fas fa-arrow-down fa-lg"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-success mb-1">
                        + {{ number_format($periodInflow) }} <small class="fs-6 text-muted">د.ع</small>
                    </h3>
                    <span class="small text-muted">كافة مقبوضات الكاشير والإيرادات</span>
                </div>
            </div>
        </div>

        <!-- إجمالي المصروفات للفترة -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4 border-start border-4 border-danger">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase fw-bold text-muted small">إجمالي المنصرف (الفترة)</span>
                        <div class="rounded-circle p-2 bg-danger bg-opacity-10 text-danger">
                            <i class="fas fa-arrow-up fa-lg"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-danger mb-1">
                        - {{ number_format($periodOutflow) }} <small class="fs-6 text-muted">د.ع</small>
                    </h3>
                    <span class="small text-muted">مستحقات أطباء + مشتريات + مصاريف</span>
                </div>
            </div>
        </div>

        <!-- توزيع المنصرفات -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 d-flex flex-column justify-content-around">
                    <div class="d-flex justify-content-between align-items-center small">
                        <span class="text-muted"><i class="fas fa-user-md text-primary me-1"></i> صرفيات الأطباء:</span>
                        <span class="fw-bold text-dark">{{ number_format($periodDoctorPayouts) }} د.ع</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mt-2">
                        <span class="text-muted"><i class="fas fa-boxes text-warning me-1"></i> فواتير المشتريات:</span>
                        <span class="fw-bold text-dark">{{ number_format($periodPurchases) }} د.ع</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mt-2">
                        <span class="text-muted"><i class="fas fa-cogs text-info me-1"></i> نثريات وتشغيلية:</span>
                        <span class="fw-bold text-dark">{{ number_format($periodOperational) }} د.ع</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط الفلاتر والبحث -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('treasury.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">من تاريخ</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">إلى تاريخ</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">نوع الحركة</label>
                    <select name="voucher_type" class="form-select form-select-sm">
                        <option value="all">كافة الحركات</option>
                        <option value="inflow" {{ request('voucher_type') === 'inflow' ? 'selected' : '' }}>وارد فقط (+)</option>
                        <option value="outflow" {{ request('voucher_type') === 'outflow' ? 'selected' : '' }}>صادر فقط (-)</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">البند / التصنيف</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="all">كافة التصنيفات</option>
                        @foreach($categories as $key => $name)
                            <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">بحث برقم السند أو البيان</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="رقم السند، الملاحظات..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary px-3">
                            <i class="fas fa-search me-1"></i> تصفية
                        </button>
                    </div>
                </div>
                <div class="col-12 col-md-1">
                    <a href="{{ route('treasury.index') }}" class="btn btn-sm btn-outline-secondary w-100" title="إعادة ضبط">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول كشف حركة الخزينة -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fas fa-list-check text-primary me-2"></i>
                سجل القيود وحركات الخزينة
            </h5>
            <span class="badge bg-light text-dark border px-3 py-2">
                إجمالي السجلات: {{ $transactions->total() }}
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">#</th>
                        <th>رقم السند</th>
                        <th>التاريخ والوقت</th>
                        <th>الاتجاه</th>
                        <th>البند / التصنيف</th>
                        <th>البيان والوصف</th>
                        <th>المبلغ</th>
                        <th>طريقة الدفع</th>
                        <th>المستخدم</th>
                        <th class="text-center pe-4">الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $index => $trx)
                    <tr>
                        <td class="ps-4 text-muted small">{{ $transactions->firstItem() + $index }}</td>
                        <td>
                            <span class="badge bg-light text-dark border fw-bold px-2 py-1">
                                {{ $trx->voucher_number ?: ('#' . $trx->id) }}
                            </span>
                        </td>
                        <td class="small text-muted">
                            <i class="far fa-clock me-1"></i>
                            {{ $trx->performed_at ? $trx->performed_at->format('Y-m-d H:i') : $trx->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td>
                            @if($trx->isInflow())
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="fas fa-arrow-down me-1"></i> وارد (+)
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                    <i class="fas fa-arrow-up me-1"></i> صادر (-)
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                {{ $trx->category_label }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $trx->description }}</div>
                            @if($trx->notes)
                                <div class="text-muted small text-truncate" style="max-width: 320px;" title="{{ $trx->notes }}">
                                    <i class="far fa-comment-dots me-1"></i>{{ $trx->notes }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold fs-6 {{ $trx->isInflow() ? 'text-success' : 'text-danger' }}">
                                {{ $trx->isInflow() ? '+' : '-' }} {{ number_format($trx->amount) }} <small class="text-muted fs-7">د.ع</small>
                            </span>
                        </td>
                        <td class="small">
                            <span class="badge bg-light text-muted border">
                                <i class="fas fa-money-bill-wave me-1"></i>{{ $trx->payment_method_label }}
                            </span>
                        </td>
                        <td class="small text-muted">
                            <i class="far fa-user me-1"></i>
                            {{ optional($trx->performer)->name ?: 'النظام' }}
                        </td>
                        <td class="text-center pe-4">
                            @if($trx->related_type !== \App\Models\Payment::class)
                                <form method="POST" action="{{ route('treasury.destroy', $trx) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا السند؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2" title="حذف السند">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            @else
                                <span class="text-muted small" title="مرتبط بإيصال كاشير">
                                    <i class="fas fa-lock"></i>
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <div class="py-4">
                                <i class="fas fa-vault text-muted fa-3x mb-3 opacity-50"></i>
                                <h6 class="text-muted fw-bold">لا توجد حركات مسجلة في الخزينة حالياً</h6>
                                <p class="text-muted small mb-0">يمكنك تسجيل سند صرف أو قبض جديد عبر الأزرار بالأعلى.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>

<!-- ================= مودال: سند صرف مصروفات (Outflow) ================= -->
<div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-danger text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="expenseModalLabel">
                    <i class="fas fa-minus-circle me-2"></i> تسجيل سند صرف (خروج نقدية)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('treasury.expense.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">بند الصرف / التصنيف <span class="text-danger">*</span></label>
                        <select name="category" id="expenseCategorySelect" class="form-select" required>
                            <option value="" disabled selected>-- اختر بند الصرف --</option>
                            <option value="doctor_payout">👨‍⚕️ صرف مستحقات أطباء (استشارية / طوارئ / عمليات)</option>
                            <option value="purchase">📦 فواتير مشتريات ومورّدين (أدوية ومستلزمات)</option>
                            <option value="salary">💼 رواتب ومكافآت موظفين</option>
                            <option value="maintenance">🔧 صيانة وأجهزة طبية</option>
                            <option value="utilities">⚡ كهرباء، ماء، وقود ومولدات</option>
                            <option value="medical_supplies">💉 مستلزمات ومستهلكات طبية</option>
                            <option value="operational">☕ نثريات ومصاريف تشغيلية عامة</option>
                            <option value="other">📌 أخرى</option>
                        </select>
                    </div>

                    <!-- حقل اختيار الطبيب (يظهر فقط عند اختيار صرف مستحقات طبيب) -->
                    <div class="mb-3 d-none" id="doctorSelectDiv">
                        <label class="form-label fw-bold text-dark small">اختر الطبيب المستفيد <span class="text-danger">*</span></label>
                        <select name="doctor_id" id="doctorIdInput" class="form-select">
                            <option value="">-- اختر الطبيب --</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}">{{ optional($doc->user)->name }} ({{ $doc->specialization ?: 'طبيب' }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted">سيتم تسجيل الدفعة وخصمها من الرصيد المالي المستحق للطبيب تلقائياً.</small>
                    </div>

                    <!-- حقل اختيار المورد (يظهر فقط عند اختيار فواتير مشتريات) -->
                    <div class="mb-3 d-none" id="supplierSelectDiv">
                        <label class="form-label fw-bold text-dark small">اختر المورد / الشركة</label>
                        <select name="supplier_id" id="supplierIdInput" class="form-select">
                            <option value="">-- اختر المورد (اختياري) --</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-7">
                            <label class="form-label fw-bold text-dark small">المبلغ المطلوب صرفه (د.ع) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="any" min="1" name="amount" class="form-control fw-bold fs-5 text-danger" placeholder="0" required>
                                <span class="input-group-text bg-light text-muted">د.ع</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label fw-bold text-dark small">طريقة الدفع</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash" selected>نقدي (القاصة)</option>
                                <option value="bank_transfer">تحويل مصرفي</option>
                                <option value="cheque">صك بنكي</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">البيان / الوصف <span class="text-danger">*</span></label>
                        <input type="text" name="description" class="form-control" placeholder="مثال: صرف مستحقات الطبيب فلان / شراء وقود للمولدات..." required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold text-dark small">تاريخ ووقت الصرف</label>
                            <input type="datetime-local" name="performed_at" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold text-dark small">ملاحظات إضافية</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="ملاحظات المحاسب...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold">
                        <i class="fas fa-check me-1"></i> حفظ وتأكيد سند الصرف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= مودال: سند قبض إيراد (Inflow) ================= -->
<div class="modal fade" id="incomeModal" tabindex="-1" aria-labelledby="incomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-success text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="incomeModalLabel">
                    <i class="fas fa-plus-circle me-2"></i> تسجيل سند قبض (دخول نقدية)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('treasury.income.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">بند الإيراد / التصنيف <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="general_income" selected>💵 إيرادات وتحصيلات عامة</option>
                            <option value="consultation">🩺 إيرادات العيادات الاستشارية</option>
                            <option value="emergency">🚨 إيرادات خدمات الطوارئ</option>
                            <option value="lab">🔬 إيرادات المختبر</option>
                            <option value="radiology">🩻 إيرادات الأشعة والتصوير</option>
                            <option value="surgery">🏥 إيرادات العمليات الجراحية</option>
                            <option value="other">📌 أخرى</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-7">
                            <label class="form-label fw-bold text-dark small">المبلغ المقبوض (د.ع) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="any" min="1" name="amount" class="form-control fw-bold fs-5 text-success" placeholder="0" required>
                                <span class="input-group-text bg-light text-muted">د.ع</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label fw-bold text-dark small">طريقة القبض</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash" selected>نقدي (القاصة)</option>
                                <option value="bank_transfer">تحويل مصرفي</option>
                                <option value="cheque">صك بنكي</option>
                                <option value="card">بطاقة / POS</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">البيان / الوصف <span class="text-danger">*</span></label>
                        <input type="text" name="description" class="form-control" placeholder="مثال: توريد دفعة تأمين صحي / إيداع رصيد قاصة..." required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold text-dark small">تاريخ ووقت القبض</label>
                            <input type="datetime-local" name="performed_at" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold text-dark small">ملاحظات إضافية</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="ملاحظات المحاسب...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold">
                        <i class="fas fa-check me-1"></i> حفظ وتأكيد سند القبض
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const expenseCategorySelect = document.getElementById('expenseCategorySelect');
    const doctorSelectDiv = document.getElementById('doctorSelectDiv');
    const doctorIdInput = document.getElementById('doctorIdInput');
    const supplierSelectDiv = document.getElementById('supplierSelectDiv');
    const supplierIdInput = document.getElementById('supplierIdInput');

    if (expenseCategorySelect) {
        expenseCategorySelect.addEventListener('change', function() {
            if (this.value === 'doctor_payout') {
                doctorSelectDiv.classList.remove('d-none');
                doctorIdInput.setAttribute('required', 'required');
            } else {
                doctorSelectDiv.classList.add('d-none');
                doctorIdInput.removeAttribute('required');
                doctorIdInput.value = '';
            }

            if (this.value === 'purchase') {
                supplierSelectDiv.classList.remove('d-none');
            } else {
                supplierSelectDiv.classList.add('d-none');
                if (supplierIdInput) supplierIdInput.value = '';
            }
        });
    }
});
</script>
@endpush
@endsection
