@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h4 class="mb-1">المخزون المنخفض</h4>
                <p class="text-muted mb-0">عرض المواد التي وصلت إلى حد التنبيه أو أقل منها.</p>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 mt-3 mt-md-0">
                <a href="{{ route('inventory.index') }}" class="btn btn-outline-primary">عرض مخزون كامل</a>
                <a href="{{ route('purchases.create') }}" class="btn btn-primary">إضافة توريد جديد</a>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <form method="GET" action="{{ route('inventory.low_stock') }}">
                        <label class="form-label fw-semibold">عرض حسب المخزن</label>
                        <div class="d-flex gap-2">
                            <select name="location_id" class="form-control">
                                <option value="">جميع المخازن</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ $locationId == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-outline-primary">تصفية</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 text-md-end align-self-end">
                    @if($selectedLocation)
                        <span class="badge bg-secondary">
                            {{ $selectedLocation->name }} • {{ $selectedLocation->type === 'main' ? 'مخزن رئيسي' : 'مخزن قسم' }}
                        </span>
                    @endif
                    @if($selectedLocation && $selectedLocation->type === 'sub')
                        <div class="mt-2 text-end text-muted small">التنبيه يعتمد على إعدادات المخزن الفرعي المحدد.</div>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المادة</th>
                            <th>التصنيف</th>
                            <th>الوحدة</th>
                            <th>الرصيد الحالي</th>
                            <th>حد التنبيه</th>
                            <th>مستوى إعادة الطلب</th>
                            <th>الفرق</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            @php
                                $totalQty = $product->stockBatches->sum('current_qty');
                                $alertQty = $product->getAlertQuantityForLocation($locationId) ?: 0;
                                $reorderLevel = $product->getReorderLevelForLocation($locationId) ?: 0;
                                $shortfall = max($alertQty - $totalQty, 0);
                                $rowClass = $totalQty <= $reorderLevel ? 'table-danger' : 'table-warning';
                                $statusLabel = $totalQty <= $reorderLevel
                                    ? '<span class="badge bg-danger">نقص كبير</span>'
                                    : '<span class="badge bg-warning text-dark">قريب من النقص</span>';
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->category }}</td>
                                <td>{{ $product->unit }}</td>
                                <td>{{ $totalQty }}</td>
                                <td>{{ $alertQty }}</td>
                                <td>{{ $reorderLevel }}</td>
                                <td>{{ $shortfall }}</td>
                                <td>{!! $statusLabel !!}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">لا توجد مواد منخفضة المخزون في الوقت الحالي.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
