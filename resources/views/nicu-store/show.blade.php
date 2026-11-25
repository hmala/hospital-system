@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center shadow-xs" style="width: 44px; height: 44px;">
                                <i class="fas fa-baby-carriage fs-5"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h2 class="mb-0 fw-bold">مخزن وعُهدة {{ $location->name }} (NICU)</h2>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1 font-monospace">
                                        {{ $location->code ? strtoupper($location->code) : 'NICU' }}
                                    </span>
                                    @if($mainLocation)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                                            <i class="fas fa-link me-1"></i> مرتبط بـ {{ $mainLocation->name }} (صرف عهدة بدون أثر مالي)
                                        </span>
                                    @endif
                                </div>
                                <p class="mb-0 text-muted small mt-1">الأرصدة المتوفرة في مخزن الخدج، صرفيات الأطفال والقسم (بدون أثر مالي على الفاتورة)، طلبيات المخزن الرئيسي، وأمين العهدة.</p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('stock-transfers.create', ['to_location_id' => $location->id]) }}" class="btn btn-warning rounded-pill px-3 py-2 fw-bold text-dark shadow-sm">
                            <i class="fas fa-plus-circle me-1"></i> طلب تعزيز من المخزن الرئيسي
                        </a>
                        <a href="{{ route('stock-transfers.returns.create', ['from_location_id' => $location->id]) }}" class="btn btn-outline-danger rounded-pill px-3 py-2 fw-bold">
                            <i class="fas fa-undo-alt me-1"></i> إرجاع فائض للمخزن الرئيسي
                        </a>
                        @if($activeBatches->count() > 0)
                            <button type="button" class="btn btn-success rounded-pill px-3 py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#dispenseDepartmentModal">
                                <i class="fas fa-hand-holding-medical me-1"></i> صرف مستهلك للقسم
                            </button>
                        @endif
                        <button type="button" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#assignNicuCustodianModal">
                            <i class="fas fa-user-shield me-1"></i> تكليف أمين العهدة
                        </button>
                        @if($canManageStoreLocation)
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2" data-bs-toggle="modal" data-bs-target="#changeNicuStoreLocationModal" title="ربط موقع مخزني فرعي آخر لقسم الخدج">
                                <i class="fas fa-sliders-h me-1"></i> تغيير الموقع
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4" role="alert">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- بطاقات المؤشرات التلخيصية KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-4 p-3 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small fw-semibold">أمين العهدة المعتمد</span>
                        <h5 class="fw-bold text-dark mt-1 mb-0">{{ $location->custodian?->name ?? 'شاغر (بدون مسؤول)' }}</h5>
                        <small class="{{ $location->custodian ? 'text-muted' : 'text-danger fw-medium' }}">
                            {{ $location->custodian?->phone ?: ($location->custodian ? 'مسؤول عهدة الخدج' : 'اضغط تكليف لتعيين مسؤول') }}
                        </small>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-user-shield fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-4 p-3 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small fw-semibold">إجمالي الرصيد المتاح</span>
                        <h4 class="fw-bold text-success mt-1 mb-0">{{ number_format($stats['total_items_in_stock']) }} <span class="fs-6 fw-normal text-muted">وحدة</span></h4>
                        <small class="text-muted">
                            {{ $stats['unique_products'] }} صنفاً
                            @if($stats['low_stock_count'] > 0)
                                · <span class="text-danger fw-bold">{{ $stats['low_stock_count'] }} منخفض</span>
                            @endif
                            @if($stats['expiring_soon_count'] > 0)
                                · <span class="text-warning-emphasis fw-bold">{{ $stats['expiring_soon_count'] }} قارب انتهاؤه</span>
                            @endif
                        </small>
                    </div>
                    <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-cubes fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-4 p-3 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small fw-semibold">صرفيات الخدج (الشهر الحالي)</span>
                        <h4 class="fw-bold text-info mt-1 mb-0">{{ number_format($stats['monthly_consumptions_count']) }} <span class="fs-6 fw-normal text-muted">حركة</span></h4>
                        <small class="text-muted">بإجمالي {{ number_format($stats['monthly_consumptions_qty']) }} وحدة مستهلكة</small>
                    </div>
                    <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-hand-holding-medical fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-4 p-3 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small fw-semibold">طلبيات المخزن الرئيسي</span>
                        <h4 class="fw-bold text-warning-emphasis mt-1 mb-0">{{ $stats['pending_transfers_count'] }} <span class="fs-6 fw-normal text-muted">جارية</span></h4>
                        <small class="text-muted">من أصل {{ $transferRequests->count() }} طلبيات تعزيز وإرجاع</small>
                    </div>
                    <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-truck-loading fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $activeTab = request('tab', ($fromDate !== '' || $toDate !== '') ? 'movements' : 'stock');
    @endphp

    <!-- التبويبات التفاعلية -->
    <div class="card border-0 shadow-lg" style="border-radius: 16px;">
        <div class="card-header bg-light border-0 p-3" style="border-radius: 16px 16px 0 0;">
            <ul class="nav nav-pills card-header-pills gap-2 flex-wrap" id="nicuStoreTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'stock' ? 'active' : '' }} rounded-pill px-4 fw-bold shadow-xs" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stock-pane" type="button" role="tab">
                        <i class="fas fa-boxes me-1.5"></i> الأرصدة والوجبات المتوفرة ({{ $activeBatches->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'movements' ? 'active' : '' }} rounded-pill px-4 fw-bold shadow-xs" id="movements-tab" data-bs-toggle="tab" data-bs-target="#movements-pane" type="button" role="tab">
                        <i class="fas fa-hand-holding-medical me-1.5"></i> سجل صرفيات الأطفال والقسم
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'transfers' ? 'active' : '' }} rounded-pill px-4 fw-bold shadow-xs" id="transfers-tab" data-bs-toggle="tab" data-bs-target="#transfers-pane" type="button" role="tab">
                        <i class="fas fa-truck-loading me-1.5"></i> طلبيات المخزن الرئيسي ({{ $transferRequests->count() }})
                        @if($stats['pending_transfers_count'] > 0)
                            <span class="badge bg-warning text-dark rounded-pill ms-1">{{ $stats['pending_transfers_count'] }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'history' ? 'active' : '' }} rounded-pill px-4 fw-bold shadow-xs" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button" role="tab">
                        <i class="fas fa-history me-1.5"></i> أرشيف تسليم واستلام العهدة ({{ $location->custodianHistories->count() }})
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="nicuStoreTabsContent">
                <!-- تبويب 1: الأرصدة الحالية مع بحث فوري وفلترة -->
                <div class="tab-pane fade {{ $activeTab === 'stock' ? 'show active' : '' }}" id="stock-pane" role="tabpanel">
                    @if($activeBatches->count() > 0)
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div class="input-group" style="max-width: 340px;">
                                <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="nicuBatchSearchInput" class="form-control border-start-0 rounded-end-pill" placeholder="بحث فوري باسم المادة، الكود، أو الباركود...">
                            </div>
                            <div class="btn-group btn-group-sm" role="group" aria-label="فلتر الأرصدة">
                                <button type="button" class="btn btn-outline-primary active nicu-stock-filter-btn" data-filter="all">الكل ({{ $activeBatches->count() }})</button>
                                <button type="button" class="btn btn-outline-danger nicu-stock-filter-btn" data-filter="low">منخفض الرصيد ({{ $stats['low_stock_count'] }})</button>
                                <button type="button" class="btn btn-outline-warning nicu-stock-filter-btn" data-filter="expiring">قارب الانتهاء / منتهي ({{ $stats['expiring_soon_count'] }})</button>
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="nicuBatchesTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>المادة أو المستلزم</th>
                                    <th>رقم الوجبة / الباركود</th>
                                    <th class="text-center">الرصيد المتاح</th>
                                    <th class="text-center">تاريخ انتهاء الصلاحية</th>
                                    <th class="text-center">الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeBatches as $batch)
                                    @php
                                        $isExpired = $batch->expiry_date && \Carbon\Carbon::parse($batch->expiry_date)->isPast();
                                        $isNearExpiry = $batch->expiry_date && ! $isExpired && \Carbon\Carbon::parse($batch->expiry_date)->lte(now()->addDays(60));
                                        $threshold = max(1, (int) ($batch->product?->alert_quantity ?? 5));
                                        $isLowStock = $batch->current_qty <= $threshold;
                                        $searchText = mb_strtolower(($batch->product?->name ?? '').' '.($batch->product?->code ?? '').' '.($batch->internal_barcode ?? '').' '.($batch->batch_number ?? ''));
                                    @endphp
                                    <tr class="nicu-batch-row"
                                        data-search="{{ $searchText }}"
                                        data-low="{{ $isLowStock ? '1' : '0' }}"
                                        data-expiring="{{ ($isExpired || $isNearExpiry) ? '1' : '0' }}">
                                        <td class="text-muted fw-bold">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $batch->product?->name ?? 'مستلزم غير محدد' }}</div>
                                            <small class="text-muted font-monospace">كود: {{ $batch->product?->code ?: 'PRD-'.$batch->product_id }} ({{ $batch->product?->unit ?? 'وحدة' }})</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace px-2.5 py-1">
                                                {{ $batch->internal_barcode ?: ($batch->batch_number ?: '#'.$batch->id) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $isLowStock ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} fs-6 fw-bold px-3 py-1">
                                                {{ number_format($batch->current_qty) }}
                                            </span>
                                            @if($isLowStock)
                                                <div class="small text-danger fw-medium mt-0.5">حد التنبيه: {{ $threshold }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($batch->expiry_date)
                                                <span class="fw-medium {{ $isExpired ? 'text-danger fw-bold' : ($isNearExpiry ? 'text-warning-emphasis' : 'text-dark') }}">
                                                    {{ \Carbon\Carbon::parse($batch->expiry_date)->format('Y-m-d') }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($isExpired)
                                                <span class="badge bg-danger rounded-pill px-2.5 py-1">منتهي الصلاحية</span>
                                            @elseif($isNearExpiry)
                                                <span class="badge bg-warning rounded-pill px-2.5 py-1 text-dark">قارب الانتهاء</span>
                                            @elseif($isLowStock)
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1">رصيد منخفض</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1">صالح للاستخدام</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-boxes fs-1 mb-2 text-secondary opacity-50"></i>
                                            <p class="mb-2">لا توجد مواد أو وجبات متوفرة حالياً في مخزن الخدج.</p>
                                            <a href="{{ route('stock-transfers.create', ['to_location_id' => $location->id]) }}" class="btn btn-sm btn-warning rounded-pill fw-bold text-dark">
                                                <i class="fas fa-plus-circle me-1"></i> طلب تعزيز من المخزن الرئيسي
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- تبويب 2: سجل صرفيات الأطفال الخدج والقسم مع فلتر تاريخ -->
                <div class="tab-pane fade {{ $activeTab === 'movements' ? 'show active' : '' }}" id="movements-pane" role="tabpanel">
                    <form action="{{ route('nicu-store.index') }}" method="GET" class="row g-2 align-items-end mb-3 bg-light rounded-4 p-3">
                        <input type="hidden" name="tab" value="movements">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1">من تاريخ</label>
                            <input type="date" name="from_date" class="form-control form-control-sm rounded-3" value="{{ $fromDate }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1">إلى تاريخ</label>
                            <input type="date" name="to_date" class="form-control form-control-sm rounded-3" value="{{ $toDate }}">
                        </div>
                        <div class="col-md-6 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                                <i class="fas fa-filter me-1"></i> تصفية السجل
                            </button>
                            @if($fromDate !== '' || $toDate !== '')
                                <a href="{{ route('nicu-store.index', ['tab' => 'movements']) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                    <i class="fas fa-times me-1"></i> مسح الفلتر
                                </a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>التاريخ والوقت</th>
                                    <th>الطفل الخديج / نوع الصرف</th>
                                    <th>المادة المنصرفة</th>
                                    <th class="text-center">الكمية</th>
                                    <th>القائم بالصرف</th>
                                    <th>الملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movements as $m)
                                    <tr>
                                        <td class="text-muted fw-bold">#{{ $m->id }}</td>
                                        <td>
                                            <div class="fw-medium text-dark">{{ $m->created_at->format('Y-m-d') }}</div>
                                            <small class="text-muted">{{ $m->created_at->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            @if($m->incubatorReservation)
                                                <div class="fw-bold text-primary">
                                                    <a href="{{ route('nicu.show', $m->incubatorReservation) }}" class="text-decoration-none">
                                                        {{ $m->incubatorReservation->baby_title }}
                                                    </a>
                                                </div>
                                                <small class="text-muted">حاضنة: {{ $m->incubatorReservation->incubator?->incubator_number ?? '—' }}</small>
                                            @elseif($m->type === 'transfer')
                                                <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2.5 py-1">إرجاع / تحويل مخزني</span>
                                            @else
                                                <span class="badge bg-info-subtle text-info rounded-pill px-2.5 py-1">صرف مستهلك عام للقسم</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $m->batch?->product?->name ?? 'مستلزم غير محدد' }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger-subtle text-danger fs-6 fw-bold px-2.5 py-1">
                                                -{{ number_format($m->quantity) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small fw-medium text-dark">{{ $m->user?->name ?? 'النظام' }}</div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $m->notes ?: 'توثيق صرف عهدة (بدون أثر مالي)' }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fas fa-hand-holding-medical fs-1 mb-2 text-secondary opacity-50"></i>
                                            <p class="mb-0">لا توجد حركات صرف مسجلة من مخزن الخدج في هذه الفترة.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($movements->hasPages())
                        <div class="p-3 border-top d-flex justify-content-center">
                            {{ $movements->links() }}
                        </div>
                    @endif
                </div>

                <!-- تبويب 3: طلبيات المخزن الرئيسي -->
                <div class="tab-pane fade {{ $activeTab === 'transfers' ? 'show active' : '' }}" id="transfers-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>الحالة</th>
                                    <th>نوع الشحنة</th>
                                    <th>مقدم الطلب</th>
                                    <th>تاريخ الطلب</th>
                                    <th>بيانات الاستلام</th>
                                    <th class="text-center pe-3">الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transferRequests as $tr)
                                    @php $isIncoming = $tr->to_location_id == $location->id; @endphp
                                    <tr>
                                        <td class="fw-bold text-dark">
                                            <a href="{{ route('stock-transfers.requests.show', $tr) }}" class="text-decoration-none">#{{ $tr->id }}</a>
                                        </td>
                                        <td>
                                            @php
                                                $statusBadges = [
                                                    'pending' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                                    'approved' => 'bg-info-subtle text-info border-info-subtle',
                                                    'rejected' => 'bg-danger-subtle text-danger border-danger-subtle',
                                                    'completed' => 'bg-success-subtle text-success border-success-subtle',
                                                    'cancelled' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                                ];
                                                $statusLabels = [
                                                    'pending' => 'قيد الانتظار',
                                                    'approved' => 'مصروف (بانتظار الاستلام)',
                                                    'rejected' => 'مرفوض',
                                                    'completed' => 'مكتمل ومستلم',
                                                    'cancelled' => 'ملغي',
                                                ];
                                            @endphp
                                            <span class="badge {{ $statusBadges[$tr->status] ?? 'bg-light text-dark' }} border rounded-pill px-2.5 py-1">
                                                {{ $statusLabels[$tr->status] ?? __($tr->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($isIncoming)
                                                <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1">
                                                    <i class="fas fa-arrow-down me-1"></i> تزويد وارد من المخزن الرئيسي
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger rounded-pill px-2.5 py-1">
                                                    <i class="fas fa-arrow-up me-1"></i> إرجاع للمخزن الرئيسي
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $tr->requestedBy?->name ?? 'غير محدد' }}</td>
                                        <td>
                                            <div class="text-dark">{{ $tr->created_at->format('Y-m-d') }}</div>
                                            <small class="text-muted">{{ $tr->created_at->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            @if($tr->received_at)
                                                <div class="text-success small fw-bold">
                                                    <i class="fas fa-check-circle me-1"></i> تم الاستلام بواسطة: {{ $tr->receivedBy?->name ?? 'أمين العهدة' }}
                                                </div>
                                            @elseif($tr->status === 'approved')
                                                <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2.5 py-1">بانتظار تأكيد الاستلام</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="d-flex justify-content-center gap-1 flex-wrap">
                                                <a href="{{ route('stock-transfers.requests.show', $tr) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                    <i class="fas fa-eye me-1"></i> استعراض / استلام
                                                </a>
                                                <a href="{{ route('stock-transfers.requests.print', $tr) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" title="طباعة سند التحويل A4">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fas fa-truck-loading fs-1 mb-2 text-secondary opacity-50"></i>
                                            <p class="mb-0">لا توجد طلبيات مسجلة مع المخزن الرئيسي لهذا الموقع.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- تبويب 4: أرشيف تسليم واستلام العهدة -->
                <div class="tab-pane fade {{ $activeTab === 'history' ? 'show active' : '' }}" id="history-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>أمين العهدة المكلف</th>
                                    <th>الآمر بالتكليف</th>
                                    <th>تاريخ وساعة التكليف</th>
                                    <th>البيان والملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($location->custodianHistories()->latest('assigned_at')->get() as $h)
                                    <tr>
                                        <td class="text-muted fw-bold">#{{ $h->id }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $h->user?->name ?? 'غير محدد' }}</div>
                                            <small class="text-muted">{{ $h->user?->email }}</small>
                                        </td>
                                        <td>{{ $h->assignedBy?->name ?? 'إدارة الخدج' }}</td>
                                        <td>
                                            <div class="fw-medium text-dark">{{ $h->assigned_at ? $h->assigned_at->format('Y-m-d') : $h->created_at->format('Y-m-d') }}</div>
                                            <small class="text-muted">{{ $h->assigned_at ? $h->assigned_at->format('h:i A') : $h->created_at->format('h:i A') }}</small>
                                        </td>
                                        <td><span class="text-muted">{{ $h->notes ?: 'تكليف واستلام رسمي للعهدة' }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-history fs-1 mb-2 text-secondary opacity-50"></i>
                                            <p class="mb-0">لا توجد سجلات تاريخية سابقة لتسليم واستلام العهدة.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal صرف مستهلك عام لقسم الخدج -->
@if($activeBatches->count() > 0)
<div class="modal fade" id="dispenseDepartmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-0 p-3.5 rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fas fa-hand-holding-medical"></i>
                    </div>
                    <h5 class="modal-title fw-bold">صرف مستهلك عام لقسم الخدج (NICU)</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('nicu-store.dispense', $location) }}">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        يُستخدم هذا الصرف لتوثيق استهلاك المستلزمات العامة داخل قسم الخدج (مثل الكفوف، المعقمات، الشاش، وأغطية الحاضنات) وخصمها من العهدة دون ربطها بفاتورة طفل محدد.
                    </p>
                    <div class="mb-3">
                        <label for="dept_batch_id" class="form-label fw-bold">المادة / الوجبة المتوفرة <span class="text-danger">*</span></label>
                        <select name="batch_id" id="dept_batch_id" class="form-select rounded-3" required>
                            <option value="">-- اختر المادة من رصيد مخزن الخدج --</option>
                            @foreach($activeBatches as $b)
                                <option value="{{ $b->id }}">
                                    {{ $b->product?->name ?? 'مادة' }} — المتاح: {{ number_format($b->current_qty) }} ({{ $b->product?->unit ?? 'وحدة' }}){{ $b->expiry_date ? ' | صلاحية: '.\Carbon\Carbon::parse($b->expiry_date)->format('Y-m-d') : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="dept_quantity" class="form-label fw-bold">الكمية المصروفة <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="dept_quantity" class="form-control rounded-3" min="1" value="1" required>
                    </div>
                    <div class="mb-2">
                        <label for="dept_notes" class="form-label fw-medium">ملاحظات الصرف</label>
                        <textarea name="notes" id="dept_notes" class="form-control rounded-3" rows="2" placeholder="مثلاً: مستهلكات شفت مسائي أو تعقيم حاضنات..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3 rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">
                        <i class="fas fa-check me-1"></i> تأكيد الصرف من العهدة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal تكليف أو تغيير أمين عهدة الخدج -->
<div class="modal fade" id="assignNicuCustodianModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-0 p-3.5 rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h5 class="modal-title fw-bold">تكليف مسؤول عهدة مخزن الخدج</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('nicu-store.assign-custodian', $location) }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-light border rounded-3 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">موقع المخزن:</span>
                            <span class="fw-bold text-dark">{{ $location->name }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">المسؤول الحالي:</span>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1">{{ $location->custodian?->name ?? 'شاغر' }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nicu_custodian_id" class="form-label fw-bold">اختيار المسؤول المكلف بعهدة الخدج <span class="text-danger">*</span></label>
                        <select name="custodian_id" id="nicu_custodian_id" class="form-select rounded-3" required>
                            <option value="">-- حدد الموظف أو الممرض المسؤول --</option>
                            @foreach($availableStaff as $staff)
                                <option value="{{ $staff->id }}" {{ $location->custodian_id == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->name }} ({{ $staff->email }}) {{ $staff->location ? '— موقع حالي: '.$staff->location->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="sync_user_location" id="nicu_sync_user_location" value="1" checked>
                        <label class="form-check-label fw-medium text-dark" for="nicu_sync_user_location">
                            تحديث وتثبيت موقع المستخدم بالنظام ليكون {{ $location->name }}
                        </label>
                    </div>

                    <div class="mb-2">
                        <label for="nicu_custodian_notes" class="form-label fw-medium">ملاحظات التكليف والتسليم</label>
                        <textarea name="notes" id="nicu_custodian_notes" class="form-control rounded-3" rows="3" placeholder="اكتب أي ملاحظات إدارية أو محضر جرد..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3 rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="fas fa-check me-1"></i> اعتماد وتكليف المسؤول
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal تغيير الموقع المخزني المعتمد للخدج (للإدارة العليا ومدير المخازن فقط) -->
@if($canManageStoreLocation)
<div class="modal fade" id="changeNicuStoreLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-0 p-3.5 rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fas fa-sliders-h"></i>
                    </div>
                    <h5 class="modal-title fw-bold">تعيين الموقع المخزني المعتمد للخدج</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('nicu-store.update-location') }}">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        اختر المخزن الفرعي الذي يرتبط تلقائياً بشاشة مخزن وعُهدة الخدج وصرفيات إضبارات الأطفال الخدج في قاعدة البيانات الحالية (بدون الاعتماد على رقم معرف ثابت).
                    </p>
                    <div class="mb-3">
                        <label for="nicu_store_location_select" class="form-label fw-bold">المخزن الفرعي المعتمد <span class="text-danger">*</span></label>
                        <select name="location_id" id="nicu_store_location_select" class="form-select rounded-3" required>
                            @foreach($allSubLocations as $subLoc)
                                <option value="{{ $subLoc->id }}" {{ $subLoc->id == $location->id ? 'selected' : '' }}>
                                    {{ $subLoc->name }}{{ $subLoc->code ? ' ['.strtoupper($subLoc->code).']' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3 rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-pill px-4 fw-bold">
                        <i class="fas fa-save me-1"></i> حفظ واعتماد الموقع
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('nicuBatchSearchInput');
    const filterButtons = document.querySelectorAll('.nicu-stock-filter-btn');
    const rows = document.querySelectorAll('.nicu-batch-row');
    let currentFilter = 'all';

    function applyBatchFilters() {
        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        rows.forEach(function (row) {
            const matchesSearch = query === '' || (row.dataset.search || '').includes(query);
            let matchesFilter = true;
            if (currentFilter === 'low') {
                matchesFilter = row.dataset.low === '1';
            } else if (currentFilter === 'expiring') {
                matchesFilter = row.dataset.expiring === '1';
            }
            row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyBatchFilters);
    }

    filterButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.dataset.filter || 'all';
            applyBatchFilters();
        });
    });
});
</script>
@endsection
