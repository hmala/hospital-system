@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h4 class="mb-1">مخزن: {{ $location->name }}</h4>
                <p class="text-muted mb-0">عرض رصيد المواد داخل هذا المخزن.</p>
            </div>
            <div class="text-md-end">
                <a href="{{ route('locations.index') }}" class="btn btn-outline-secondary">رجوع إلى المخازن</a>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="border rounded-4 p-3">
                        <small class="text-muted">نوع المخزن</small>
                        <div class="fw-semibold">{{ $location->type === 'main' ? 'رئيسي' : 'قسم' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-4 p-3">
                        <small class="text-muted">عدد الدفعات</small>
                        <div class="fw-semibold">{{ $location->stockBatches->count() }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-4 p-3">
                        <small class="text-muted">الرصيد الكلي</small>
                        <div class="fw-semibold">{{ $location->stockBatches->sum('current_qty') }}</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المادة</th>
                            <th>الرصيد</th>
                            <th>عدد الدفعات</th>
                            <th>أقدم دفعة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            @php
                                $currentQty = $product->stockBatches->sum('current_qty');
                                $batchCount = $product->stockBatches->count();
                                $firstBatch = $product->stockBatches->sortBy('received_at')->first();
                            @endphp
                            @if($currentQty > 0)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $currentQty }}</td>
                                    <td>{{ $batchCount }}</td>
                                    <td>{{ $firstBatch ? $firstBatch->received_at->format('Y-m-d') : '-' }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
