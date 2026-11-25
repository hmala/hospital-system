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
                            <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center shadow-xs" style="width: 44px; height: 44px;">
                                <i class="fas fa-baby-carriage fs-5"></i>
                            </div>
                            <div>
                                <h2 class="mb-0 fw-bold">مخزن وعُهدة قسم الخدج والحاضنات (NICU)</h2>
                                @if($mainLocation)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-0.5 small mt-1">
                                        <i class="fas fa-link me-1"></i> مربوط بالمخزن الرئيسي: {{ $mainLocation->name }} (صرف عهدة بدون أثر مالي على الفاتورة)
                                    </span>
                                @endif
                            </div>
                        </div>
                        <p class="mb-0 text-muted mt-1">إدارة مخزن مستلزمات الخدج الفرعي، متابعة الرصيد المتوفر، طلبات التعزيز والإرجاع للمخزن الرئيسي، وأمناء العهدة.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @php $primaryNicuStore = $subStores->first(); @endphp
                        @if($primaryNicuStore)
                            <a href="{{ route('stock-transfers.create', ['to_location_id' => $primaryNicuStore->id]) }}" class="btn btn-warning rounded-pill px-3 py-2 fw-bold text-dark shadow-sm">
                                <i class="fas fa-plus-circle me-1"></i> طلب تعزيز من المخزن الرئيسي
                            </a>
                            <a href="{{ route('stock-transfers.returns.create', ['from_location_id' => $primaryNicuStore->id]) }}" class="btn btn-outline-danger rounded-pill px-3 py-2 fw-bold">
                                <i class="fas fa-undo-alt me-1"></i> إرجاع فائض للمخزن الرئيسي
                            </a>
                        @endif
                        <a href="{{ route('stock-transfers.requests.index', ['location_id' => $primaryNicuStore?->id]) }}" class="btn btn-outline-secondary rounded-pill px-3 py-2">
                            <i class="fas fa-history me-1"></i> سجل طلبيات المخزن
                        </a>
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

    <!-- بطاقات المؤشرات الإحصائية KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-info" style="border-radius: 16px;">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">مخازن وعُهد الخدج</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1">{{ $stats['total_stores'] }}</h3>
                        <small class="text-success fw-medium">
                            <i class="fas fa-user-shield me-1"></i> {{ $stats['assigned_custodians'] }} مكلف بالعهدة
                            @if($stats['unassigned_custodians'] > 0)
                                <span class="text-danger ms-1">({{ $stats['unassigned_custodians'] }} شاغر)</span>
                            @endif
                        </small>
                    </div>
                    <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-warehouse fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary" style="border-radius: 16px;">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">المستلزمات المتاحة بالرصيد</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1">{{ number_format($stats['total_items_in_stock']) }}</h3>
                        <small class="text-muted">مستهلكات ومستلزمات رعاية الخدج</small>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-cubes fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-success" style="border-radius: 16px;">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">صرفيات الخدج (الشهر الحالي)</span>
                        <h3 class="mb-0 fw-bold text-success mt-1">{{ number_format($stats['total_monthly_consumptions']) }}</h3>
                        <small class="text-muted">حركة صرف سريري للأطفال بالحاضنات</small>
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
                        <span class="text-muted small fw-semibold">طلبيات المخزن الرئيسي الجارية</span>
                        <h3 class="mb-0 fw-bold text-warning-emphasis mt-1">{{ $stats['total_pending_requests'] }}</h3>
                        <small class="text-muted">
                            <i class="fas fa-truck-loading me-1"></i> بانتظار الاعتماد أو تأكيد الاستلام
                        </small>
                    </div>
                    <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-truck-loading fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول مخازن وعُهد الخدج -->
    <div class="card border-0 shadow-lg" style="border-radius: 16px;">
        <div class="card-header bg-light border-0 p-3.5 d-flex align-items-center justify-content-between flex-wrap gap-3" style="border-radius: 16px 16px 0 0;">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-boxes text-info fs-5"></i>
                <h5 class="mb-0 fw-bold text-dark">سجل مخازن وعُهد قسم الخدج والحاضنات</h5>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1">{{ $subStores->count() }} مواقع</span>
            </div>

            <form action="{{ route('nicu-store.index') }}" method="GET" class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 rounded-end-pill px-3" placeholder="بحث باسم المخزن..." value="{{ $search }}">
                </div>
                @if($search !== '')
                    <a href="{{ route('nicu-store.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" title="إلغاء البحث">
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
                            <th>موقع المخزن الفرعي</th>
                            <th>أمين العهدة المسؤول</th>
                            <th class="text-center">الرصيد المتاح</th>
                            <th class="text-center">صرفيات الشهر</th>
                            <th class="text-center">طلبيات المخزن الرئيسي</th>
                            <th class="text-center pe-3">الإجراءات والتحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subStores as $store)
                            @php
                                $stockCount = $store->stockBatches->sum('current_qty');
                                $monthly = $monthlyConsumptions[$store->id] ?? null;
                                $pending = $pendingTransfers[$store->id] ?? null;
                            @endphp
                            <tr>
                                <td class="ps-3 text-muted fw-bold">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-3 bg-info text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                            <i class="fas fa-baby"></i>
                                        </div>
                                        <div>
                                            <a href="{{ route('nicu-store.show', $store) }}" class="fw-bold text-dark text-decoration-none">
                                                {{ $store->name }}
                                            </a>
                                            <div class="small text-muted">
                                                <span class="badge bg-info-subtle text-info rounded-pill px-2 py-0.5">مخزن فرعي للخدج</span>
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
                                                <small class="text-muted">{{ $store->custodian->phone ?: 'مسؤول عهدة الخدج' }}</small>
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1">
                                            <i class="fas fa-exclamation-triangle me-1"></i> شاغر (بدون مسؤول)
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($stockCount > 0)
                                        <span class="badge bg-success-subtle text-success fs-6 fw-bold px-2.5 py-1">
                                            {{ number_format($stockCount) }} وحدة
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1">رصيد فارغ</span>
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
                                    <div class="d-flex justify-content-center gap-1 flex-wrap">
                                        <a href="{{ route('nicu-store.show', $store) }}" class="btn btn-sm btn-primary rounded-pill px-3">
                                            <i class="fas fa-eye me-1"></i> الأرصدة والسجل
                                        </a>
                                        <a href="{{ route('stock-transfers.create', ['to_location_id' => $store->id]) }}" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-2.5" title="طلب تعزيز من المخزن الرئيسي">
                                            <i class="fas fa-plus"></i> طلب مواد
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5"
                                                onclick="openAssignNicuCustodianModal({{ $store->id }}, '{{ addslashes($store->name) }}', '{{ $store->custodian_id ?? '' }}', '{{ addslashes($store->custodian?->name ?? 'شاغر') }}')">
                                            <i class="fas fa-user-edit me-1"></i> تكليف
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-warehouse fs-1 mb-2 text-secondary opacity-50"></i>
                                    <p class="mb-0 fw-medium">لا توجد مواقع مخزنية معرفة لقسم الخدج حالياً.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

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

            <form id="assignNicuCustodianForm" method="POST" action="">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-light border rounded-3 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">موقع المخزن:</span>
                            <span class="fw-bold text-dark" id="nicuModalStoreName">—</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">المسؤول الحالي:</span>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1" id="nicuModalCurrentCustodian">—</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nicu_custodian_id" class="form-label fw-bold">اختيار المسؤول المكلف بعهدة الخدج <span class="text-danger">*</span></label>
                        <select name="custodian_id" id="nicu_custodian_id" class="form-select rounded-3" required>
                            <option value="">-- حدد الموظف أو الممرض المسؤول --</option>
                            @foreach($availableStaff as $staff)
                                <option value="{{ $staff->id }}">
                                    {{ $staff->name }} ({{ $staff->email }}) {{ $staff->location ? '— موقع حالي: '.$staff->location->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="sync_user_location" id="nicu_sync_user_location" value="1" checked>
                        <label class="form-check-label fw-medium text-dark" for="nicu_sync_user_location">
                            تحديث وتثبيت موقع المستخدم بالنظام ليكون مخزن الخدج
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

<script>
function openAssignNicuCustodianModal(storeId, storeName, currentCustodianId, currentCustodianName) {
    const form = document.getElementById('assignNicuCustodianForm');
    form.action = `{{ url('nicu-store') }}/${storeId}/assign-custodian`;

    document.getElementById('nicuModalStoreName').textContent = storeName;
    document.getElementById('nicuModalCurrentCustodian').textContent = currentCustodianName;

    const select = document.getElementById('nicu_custodian_id');
    select.value = currentCustodianId || '';

    const modal = new bootstrap.Modal(document.getElementById('assignNicuCustodianModal'));
    modal.show();
}
</script>
@endsection
