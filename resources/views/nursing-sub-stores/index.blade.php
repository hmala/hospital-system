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
                            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center shadow-xs" style="width: 44px; height: 44px;">
                                <i class="fas fa-boxes-stacked fs-5"></i>
                            </div>
                            <h2 class="mb-0 fw-bold">رقابة مخازن وعُهد ردهات التمريض</h2>
                        </div>
                        <p class="mb-0 text-muted">لوحة القيادة الإشرافية لمتابعة عهد ردهات وطوابق التمريض، إدارة أمناء العهد، ورصد استهلاك وصرفيات المرضى.</p>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('nursing-station.index') }}" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-medium shadow-xs">
                            <i class="fas fa-user-nurse me-1.5"></i> محطة التمريض
                        </a>
                        <a href="{{ route('nurse-availability.index') }}" class="btn btn-outline-info rounded-pill px-3 py-2 fw-medium shadow-xs">
                            <i class="fas fa-clipboard-user me-1.5"></i> دوام التمريض
                        </a>
                        <a href="{{ route('stock-transfers.requests.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-medium shadow-xs">
                            <i class="fas fa-truck-loading me-1.5"></i> طلبيات المخزن
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
                        <span class="text-muted small fw-semibold">المخازن الفرعية النشطة</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1">{{ $stats['total_stores'] }}</h3>
                        <small class="text-success fw-medium">
                            <i class="fas fa-user-shield me-1"></i> {{ $stats['assigned_custodians'] }} معتمد العهدة
                            @if($stats['unassigned_custodians'] > 0)
                                <span class="text-danger ms-1">({{ $stats['unassigned_custodians'] }} شاغر)</span>
                            @endif
                        </small>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-hospital fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-info" style="border-radius: 16px;">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">إجمالي المواد والوجبات بالعهد</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1">{{ number_format($stats['total_items_in_stock']) }}</h3>
                        <small class="text-muted">موزعة على رصيد الطوابق والصالات</small>
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
                        <span class="text-muted small fw-semibold">صرفيات المرضى (الشهر الحالي)</span>
                        <h3 class="mb-0 fw-bold text-success mt-1">{{ number_format($stats['total_monthly_consumptions']) }}</h3>
                        <small class="text-muted">حركة سحب واستهلاك للمرضى</small>
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
                        <span class="text-muted small fw-semibold">طلبيات بانتظار الاعتماد/الاستلام</span>
                        <h3 class="mb-0 fw-bold text-warning-emphasis mt-1">{{ $stats['total_pending_requests'] }}</h3>
                        <small class="text-muted">شحنات جارية بين المخزن الرئيسي والأقسام</small>
                    </div>
                    <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-truck-loading fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول رادار المخازن الفرعية -->
    <div class="card border-0 shadow-lg" style="border-radius: 16px;">
        <div class="card-header bg-light border-0 p-3.5 d-flex align-items-center justify-content-between flex-wrap gap-3" style="border-radius: 16px 16px 0 0;">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-warehouse text-primary fs-5"></i>
                <h5 class="mb-0 fw-bold text-dark">سجل المخازن الفرعية وأمناء العهدة</h5>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1">{{ $subStores->count() }} أقسام</span>
            </div>

            <form action="{{ route('nursing-sub-stores.index') }}" method="GET" class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 rounded-end-pill px-3" placeholder="بحث باسم القسم أو الردهة..." value="{{ $search }}">
                </div>
                @if($search !== '')
                    <a href="{{ route('nursing-sub-stores.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" title="إلغاء البحث">
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
                            <th>المخزن الفرعي (القسم / الردهة)</th>
                            <th>أمين العهدة المسؤول</th>
                            <th class="text-center" style="width: 130px;">كادر التمريض</th>
                            <th class="text-center" style="width: 140px;">الرصيد الحالي</th>
                            <th class="text-center" style="width: 160px;">صرفيات المرضى (الشهر)</th>
                            <th class="text-center" style="width: 140px;">طلبيات معلقة</th>
                            <th class="text-center pe-3" style="width: 220px;">الإجراءات والتحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subStores as $index => $store)
                            @php
                                $stockItemsCount = $store->stockBatches->pluck('product_id')->unique()->count();
                                $totalStockUnits = $store->stockBatches->sum('current_qty');
                                $dispenseData = $monthlyConsumptions[$store->id] ?? null;
                                $pendingCount = $pendingTransfers[$store->id]->count ?? 0;
                            @endphp
                            <tr>
                                <td class="ps-3 text-muted fw-bold">{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center shadow-2xs" style="width: 36px; height: 36px;">
                                            <i class="fas fa-door-open"></i>
                                        </div>
                                        <div>
                                            <a href="{{ route('nursing-sub-stores.show', $store) }}" class="fw-bold text-dark text-decoration-none hover-primary">
                                                {{ $store->name }}
                                            </a>
                                            <div class="small text-muted font-monospace">LOC-{{ $store->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($store->custodian)
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center shadow-2xs" style="width: 32px; height: 32px; font-size: 13px;">
                                                <i class="fas fa-user-check"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">{{ $store->custodian->name }}</div>
                                                <small class="text-muted">{{ $store->custodian->phone ?: 'مسؤول العهدة المعتمد' }}</small>
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 fw-bold">
                                            <i class="fas fa-exclamation-circle me-1"></i> لا يوجد مسؤول عهدة محدد
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1">
                                        <i class="fas fa-users me-1 text-primary"></i> {{ $store->staff->count() }} ممرض
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="fw-bold text-dark">{{ number_format($totalStockUnits) }} وحدة</div>
                                    <small class="text-muted">({{ $stockItemsCount }} أصناف)</small>
                                </td>
                                <td class="text-center">
                                    @if($dispenseData && $dispenseData->count > 0)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-bold">
                                            {{ $dispenseData->count }} حركة ({{ $dispenseData->total_qty }} وحدة)
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($pendingCount > 0)
                                        <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1 fw-bold shadow-xs">
                                            <i class="fas fa-clock me-1"></i> {{ $pendingCount }} طلبيات
                                        </span>
                                    @else
                                        <span class="text-muted small"><i class="fas fa-check text-success me-0.5"></i> لا يوجد</span>
                                    @endif
                                </td>
                                <td class="text-center pe-3">
                                    <div class="d-flex align-items-center justify-content-center gap-1.5 flex-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-2.5 py-1 text-dark fw-bold shadow-2xs" data-bs-toggle="modal" data-bs-target="#assignCustodianModal{{ $store->id }}" title="تعيين أو تغيير مسؤول العهدة">
                                            <i class="fas fa-user-edit me-1"></i> تغيير المسؤول
                                        </button>
                                        <a href="{{ route('nursing-sub-stores.show', $store) }}" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 fw-medium" title="سجل الصرفيات والأرصدة">
                                            <i class="fas fa-eye me-1"></i> التفاصيل
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- نافذة تغيير وتعيين مسؤول العهدة (Modal) -->
                            <div class="modal fade" id="assignCustodianModal{{ $store->id }}" tabindex="-1" aria-labelledby="assignCustodianLabel{{ $store->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
                                        <form action="{{ route('nursing-sub-stores.assign-custodian', $store) }}" method="POST">
                                            @csrf
                                            <div class="modal-header bg-light border-0 p-3.5" style="border-radius: 18px 18px 0 0;">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center shadow-xs" style="width: 38px; height: 38px;">
                                                        <i class="fas fa-user-shield"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="modal-title fw-bold mb-0" id="assignCustodianLabel{{ $store->id }}">تكليف وتغيير مسؤول عهدة المخزن</h5>
                                                        <small class="text-muted">{{ $store->name }}</small>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-dark small mb-1">المسؤول الحالي عن العهدة:</label>
                                                    <div class="p-2.5 bg-light rounded-3 d-flex align-items-center justify-content-between">
                                                        <span class="fw-bold text-dark">{{ $store->custodian?->name ?? 'غير محدد حالياً' }}</span>
                                                        @if($store->custodian)
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">معين</span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">شاغر</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="custodian_id_{{ $store->id }}" class="form-label fw-bold text-dark small mb-1">
                                                        اختيار الممرض المسؤول الجديد: <span class="text-danger">*</span>
                                                    </label>
                                                    <select name="custodian_id" id="custodian_id_{{ $store->id }}" class="form-select rounded-3 py-2" required>
                                                        <option value="">-- اختر ممرضاً لتكليفه بالعهدة --</option>
                                                        @php
                                                            $wardNurses = $availableNurses->where('location_id', $store->id);
                                                            $otherNurses = $availableNurses->where('location_id', '!=', $store->id);
                                                        @endphp
                                                        @if($wardNurses->isNotEmpty())
                                                            <optgroup label="كادر تمريض {{ $store->name }} (نفس الطابق)">
                                                                @foreach($wardNurses as $nurse)
                                                                    <option value="{{ $nurse->id }}" {{ $store->custodian_id == $nurse->id ? 'selected' : '' }}>
                                                                        {{ $nurse->name }} (كادر الردهة الحالي)
                                                                    </option>
                                                                @endforeach
                                                            </optgroup>
                                                        @endif
                                                        @if($otherNurses->isNotEmpty())
                                                            <optgroup label="باقي كادر التمريض في المستشفى">
                                                                @foreach($otherNurses as $nurse)
                                                                    <option value="{{ $nurse->id }}" {{ $store->custodian_id == $nurse->id ? 'selected' : '' }}>
                                                                        {{ $nurse->name }} ({{ $nurse->location?->name ?: 'غير مقيد بردهة' }})
                                                                    </option>
                                                                @endforeach
                                                            </optgroup>
                                                        @endif
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="sync_user_location" id="syncLoc{{ $store->id }}" value="1" checked>
                                                        <label class="form-check-label text-dark small fw-medium" for="syncLoc{{ $store->id }}">
                                                            تثبيت وربط موقع عمل الممرض بهذا المخزن الفرعي تلقائياً
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="mb-0">
                                                    <label for="notes_{{ $store->id }}" class="form-label fw-bold text-dark small mb-1">
                                                        سبب التكليف / ملاحظات التسليم والاستلام (اختياري):
                                                    </label>
                                                    <textarea name="notes" id="notes_{{ $store->id }}" class="form-control rounded-3" rows="3" placeholder="مثال: استلام العهدة رسمياً، أو تبديل نوبات العمل، أو تكليف دوري..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light border-0 p-3 d-flex justify-content-between" style="border-radius: 0 0 18px 18px;">
                                                <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                                                <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark shadow-xs">
                                                    <i class="fas fa-check-circle me-1"></i> حفظ وتثبيت مسؤول العهدة
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-boxes-stacked fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-0 fw-semibold">لا توجد مخازن فرعية مسجلة بالنظام حالياً</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
