@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-xs" style="width: 44px; height: 44px;">
                                <i class="fas fa-warehouse fs-5"></i>
                            </div>
                            <h2 class="mb-0 fw-bold">مخزن وعُهد صالات العمليات</h2>
                        </div>
                        <p class="mb-0 text-muted">لوحة القيادة الميدانية لإدارة مخزن العمليات الرئيسي، صالات العمليات الجراحية، متابعة عُهد التجهيز الجراحي وصرفيات المرضى.</p>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('surgeries.index') }}" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-medium shadow-xs">
                            <i class="fas fa-procedures me-1.5"></i> جدول العمليات
                        </a>
                        <a href="{{ route('surgeries.waiting') }}" class="btn btn-outline-warning rounded-pill px-3 py-2 fw-medium shadow-xs">
                            <i class="fas fa-clock me-1.5"></i> قائمة الانتظار
                        </a>
                        <a href="{{ route('stock-transfers.requests.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-medium shadow-xs">
                            <i class="fas fa-truck-loading me-1.5"></i> طلبيات المخزن
                        </a>
                        <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-xs">
                            <i class="fas fa-plus me-1.5"></i> طلب تعزيز رصيد
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- رسائل النجاح والتحذيرات -->
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    <!-- بطاقات المؤشرات الإحصائية العامة KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary" style="border-radius: 16px;">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">مخازن وصالات العمليات</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1">{{ $stats['total_stores'] }}</h3>
                        <small class="text-success fw-medium">
                            <i class="fas fa-user-shield me-1"></i> {{ $stats['assigned_custodians'] }} معتمد العهدة
                            @if($stats['unassigned_custodians'] > 0)
                                <span class="text-danger ms-1">({{ $stats['unassigned_custodians'] }} شاغر)</span>
                            @endif
                        </small>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-warehouse fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-info" style="border-radius: 16px;">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">المواد والأدوات المتاحة بالرصيد</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1">{{ number_format($stats['total_items_in_stock']) }}</h3>
                        <small class="text-muted">سيتات، أدوية، ومستهلكات جراحية</small>
                    </div>
                    <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-cubes fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-success" style="border-radius: 16px;">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">صرفيات العمليات (الشهر الحالي)</span>
                        <h3 class="mb-0 fw-bold text-success mt-1">{{ number_format($stats['total_monthly_consumptions']) }}</h3>
                        <small class="text-muted">حركة صرف سريري واستهلاك جراحي</small>
                    </div>
                    <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-hand-holding-medical fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning" style="border-radius: 16px;">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">العمليات المجدولة / الجارية</span>
                        <h3 class="mb-0 fw-bold text-warning-emphasis mt-1">{{ $stats['today_surgeries_count'] }}</h3>
                        <small class="text-muted">
                            <i class="fas fa-truck-loading me-1"></i> {{ $stats['total_pending_requests'] }} طلبيات تزويد جارية
                        </small>
                    </div>
                    <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-procedures fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول مخازن وصالات العمليات -->
    <div class="card border-0 shadow-lg mb-4" style="border-radius: 16px;">
        <div class="card-header bg-light border-0 p-3.5 d-flex align-items-center justify-content-between flex-wrap gap-3" style="border-radius: 16px 16px 0 0;">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-door-open text-primary fs-5"></i>
                <h5 class="mb-0 fw-bold text-dark">سجل مخازن العمليات وصالات الجراحة وأمناء العهدة</h5>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1">{{ $subStores->count() }} مواقع</span>
            </div>

            <form action="{{ route('surgery-store.index') }}" method="GET" class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 rounded-end-pill px-3" placeholder="بحث باسم الموقع أو الصالة..." value="{{ $search }}">
                </div>
                @if($search !== '')
                    <a href="{{ route('surgery-store.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" title="إلغاء البحث">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 45px;">#</th>
                            <th>الموقع الجراحي (المخزن / الصالة)</th>
                            <th>أمين العهدة المسؤول</th>
                            <th class="text-center" style="width: 140px;">الكادر الجراحي</th>
                            <th class="text-center" style="width: 140px;">الرصيد المتاح</th>
                            <th class="text-center" style="width: 160px;">صرفيات العمليات (الشهر)</th>
                            <th class="text-center" style="width: 140px;">طلبيات معلقة</th>
                            <th class="text-center pe-3" style="width: 220px;">الإجراءات والتحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subStores as $index => $store)
                            @php
                                $stockCount = $store->stockBatches->sum('current_qty');
                                $monthly = $monthlyConsumptions[$store->id] ?? null;
                                $pending = $pendingTransfers[$store->id] ?? null;
                                $isMainSurgeryStore = str_contains($store->name, 'رئيسي') || str_contains($store->name, 'مخزن');
                            @endphp
                            <tr>
                                <td class="ps-3 text-muted fw-bold">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-3 {{ $isMainSurgeryStore ? 'bg-primary text-white' : 'bg-info-subtle text-info' }} d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                            <i class="fas {{ $isMainSurgeryStore ? 'fa-warehouse' : 'fa-procedures' }}"></i>
                                        </div>
                                        <div>
                                            <a href="{{ route('surgery-store.show', $store) }}" class="fw-bold text-dark text-decoration-none">
                                                {{ $store->name }}
                                            </a>
                                            <div class="small text-muted">
                                                <span class="badge {{ $isMainSurgeryStore ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info' }} rounded-pill px-2 py-0.5">
                                                    {{ $isMainSurgeryStore ? 'المخزن الجراحي الرئيسي' : 'صالة عمليات جراحية' }}
                                                </span>
                                                <span class="ms-1 font-monospace">LOC-{{ $store->id }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($store->custodian)
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <i class="fas fa-user-shield"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark">{{ $store->custodian->name }}</div>
                                                <small class="text-muted">{{ $store->custodian->phone ?: 'موظف عمليات مسؤول' }}</small>
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1">
                                            <i class="fas fa-exclamation-triangle me-1"></i> شاغر (بدون مسؤول)
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1">
                                        <i class="fas fa-users-cog me-1 text-primary"></i> {{ $store->staff->count() }} كادر
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($stockCount > 0)
                                        <span class="badge bg-success-subtle text-success fs-6 fw-bold px-2.5 py-1">
                                            {{ number_format($stockCount) }} وحدة
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1">
                                            رصيد فارغ
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($monthly && $monthly->count > 0)
                                        <span class="fw-bold text-success">{{ number_format($monthly->count) }} حركة</span>
                                        <div class="small text-muted">({{ number_format($monthly->total_qty) }} مستهلك)</div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($pending && $pending->count > 0)
                                        <span class="badge bg-warning-subtle text-warning-emphasis fw-bold rounded-pill px-2.5 py-1">
                                            <i class="fas fa-truck-loading me-1"></i> {{ $pending->count }} جارية
                                        </span>
                                    @else
                                        <span class="text-muted small">لا توجد</span>
                                    @endif
                                </td>
                                <td class="text-center pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('surgery-store.show', $store) }}" class="btn btn-outline-primary rounded-start-pill px-2.5" title="عرض الأرصدة وحركات الاستهلاك">
                                            <i class="fas fa-eye me-1"></i> التفاصيل
                                        </a>
                                        <button type="button" class="btn btn-outline-secondary rounded-end-pill px-2.5"
                                                onclick="openAssignCustodianModal({{ $store->id }}, '{{ addslashes($store->name) }}', '{{ $store->custodian_id ?? '' }}', '{{ addslashes($store->custodian?->name ?? 'شاغر') }}')"
                                                title="تكليف أو تغيير أمين العهدة">
                                            <i class="fas fa-user-edit me-1"></i> تكليف مسؤول
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-warehouse fs-1 mb-2 text-secondary opacity-50"></i>
                                    <p class="mb-0 fw-medium">لا توجد مخازن أو صالات عمليات مطابقة لمعايير البحث.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- جدول تتبع العمليات الجراحية المجدولة لليوم والتجهيز المسبق -->
    <div class="card border-0 shadow-lg" style="border-radius: 16px;">
        <div class="card-header bg-light border-0 p-3.5 d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-radius: 16px 16px 0 0;">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-calendar-check text-info fs-5"></i>
                <h5 class="mb-0 fw-bold text-dark">جدول العمليات الجراحية لليوم (تجهيز المستلزمات والصرف السريري)</h5>
                <span class="badge bg-info-subtle text-info rounded-pill px-2.5 py-1">{{ $todaySurgeries->count() }} عمليات</span>
            </div>
            <a href="{{ route('surgeries.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                <i class="fas fa-external-link-alt me-1"></i> فتح السجل الكامل
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 50px;">#</th>
                            <th>المريض</th>
                            <th>العملية الجراحية</th>
                            <th>الجراح المسؤول</th>
                            <th>صالة العمليات</th>
                            <th class="text-center">الموعد</th>
                            <th class="text-center">الحالة</th>
                            <th class="text-center pe-3">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($todaySurgeries as $surgery)
                            <tr>
                                <td class="ps-3 text-muted fw-bold">#{{ $surgery->id }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $surgery->patient?->name ?? 'غير محدد' }}</div>
                                    <small class="text-muted">{{ $surgery->patient?->phone ?: ($surgery->patient?->national_id ? 'هوية: '.$surgery->patient->national_id : '') }}</small>
                                </td>
                                <td>
                                    <span class="fw-semibold text-primary">{{ $surgery->surgery_type ?: ($surgery->surgicalOperation?->name ?? 'عملية جراحية') }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1.5">
                                        <i class="fas fa-user-md text-secondary"></i>
                                        <span>{{ $surgery->doctor?->name ?: ($surgery->surgeon_name ?: 'جراح غير محدد') }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1">
                                        <i class="fas fa-procedures text-info me-1"></i>
                                        {{ $surgery->operationTheater?->name ?: 'صالة عامة' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="fw-medium text-dark">{{ $surgery->scheduled_date ? \Carbon\Carbon::parse($surgery->scheduled_date)->format('Y-m-d') : 'اليوم' }}</div>
                                    <small class="text-muted">{{ $surgery->scheduled_time ?: '' }}</small>
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusBadges = [
                                            'scheduled' => 'bg-info-subtle text-info border-info-subtle',
                                            'in_progress' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                            'completed' => 'bg-success-subtle text-success border-success-subtle',
                                            'cancelled' => 'bg-danger-subtle text-danger border-danger-subtle',
                                            'waiting' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                        ];
                                        $badgeClass = $statusBadges[$surgery->status] ?? 'bg-light text-dark';
                                    @endphp
                                    <span class="badge {{ $badgeClass }} border rounded-pill px-2.5 py-1">
                                        {{ __($surgery->status) }}
                                    </span>
                                </td>
                                <td class="text-center pe-3">
                                    <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-outline-info rounded-pill px-2.5">
                                        <i class="fas fa-file-medical me-1"></i> تفاصيل العملية
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-procedures fs-2 mb-2 text-secondary opacity-50"></i>
                                    <p class="mb-0">لا توجد عمليات جراحية مجدولة اليوم.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal تكليف أو تغيير أمين عهدة صالات ومخزن العمليات -->
<div class="modal fade" id="assignCustodianModal" tabindex="-1" aria-labelledby="assignCustodianModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-0 p-3.5 rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="assignCustodianModalLabel">تكليف مسؤول عهدة جراحية</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="assignCustodianForm" method="POST" action="">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-light border rounded-3 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">الموقع الجراحي:</span>
                            <span class="fw-bold text-dark" id="modalStoreName">—</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">المسؤول الحالي:</span>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1" id="modalCurrentCustodian">—</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="custodian_id" class="form-label fw-bold">اختيار موظف العمليات المكلف بالعهدة <span class="text-danger">*</span></label>
                        <select name="custodian_id" id="custodian_id" class="form-select form-select-lg rounded-3" required>
                            <option value="">-- حدد موظف العمليات المسؤول --</option>
                            @foreach($availableStaff as $staff)
                                <option value="{{ $staff->id }}">
                                    {{ $staff->name }} ({{ $staff->email }}) {{ $staff->location ? '— موقع حالي: '.$staff->location->name : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">يتولى أمين العهدة إدارة ومطابقة الرصيد واستلام الشحنات الرسمية وتجهيز العمليات.</div>
                    </div>

                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="sync_user_location" id="sync_user_location" value="1" checked>
                        <label class="form-check-label fw-medium text-dark" for="sync_user_location">
                            تحديث وتثبيت موقع الموظف بالنظام ليكون هذا الموقع الجراحي
                        </label>
                    </div>

                    <div class="mb-2">
                        <label for="notes" class="form-label fw-medium">ملاحظات التكليف أو سبب التسليم والتسلم</label>
                        <textarea name="notes" id="notes" class="form-control rounded-3" rows="3" placeholder="اكتب أي ملاحظات إدارية، رقم أمر إداري، أو محضر جرد..."></textarea>
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

<script>
function openAssignCustodianModal(storeId, storeName, currentCustodianId, currentCustodianName) {
    const form = document.getElementById('assignCustodianForm');
    form.action = `/surgery-store/${storeId}/assign-custodian`;

    document.getElementById('modalStoreName').textContent = storeName;
    document.getElementById('modalCurrentCustodian').textContent = currentCustodianName;

    const select = document.getElementById('custodian_id');
    select.value = currentCustodianId || '';

    const modal = new bootstrap.Modal(document.getElementById('assignCustodianModal'));
    modal.show();
}
</script>
@endsection
