@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h4 class="mb-1">تفاصيل فاتورة الشراء</h4>
                <p class="text-muted mb-0">عرض بيانات الفاتورة والمحتويات المرتبطة بها.</p>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 text-md-end">
                <a href="{{ route('stock-transfers.returns.create', ['purchase_id' => $purchase->id]) }}" class="btn btn-outline-primary">
                    <i class="fas fa-undo-alt me-2"></i>إرجاع للمخزن الرئيسي
                </a>
                <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">رجوع إلى القائمة</a>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="border rounded-4 p-3">
                        <small class="text-muted">رقم الفاتورة</small>
                        <div class="fw-semibold">{{ $purchase->invoice_number }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-4 p-3">
                        <small class="text-muted">المورد</small>
                        <div class="fw-semibold">{{ $purchase->supplier->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-4 p-3">
                        <small class="text-muted">المسؤول</small>
                        <div class="fw-semibold">{{ $purchase->user->name ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="border rounded-4 p-3">
                        <small class="text-muted">تاريخ الاستلام</small>
                        <div class="fw-semibold">{{ $purchase->received_at?->format('Y-m-d H:i') ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-4 p-3">
                        <small class="text-muted">عدد البنود</small>
                        <div class="fw-semibold">{{ $purchase->items->count() }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-4 p-3">
                        <small class="text-muted">الإجمالي</small>
                        <div class="fw-semibold">{{ number_format($purchase->total_amount, 2) }}</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المادة</th>
                            <th>الكمية</th>
                            <th>سعر التكلفة للوحدة</th>
                            <th>الاجمالي</th>
                            <th>تاريخ الانتهاء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchase->items as $item)
                            <tr>
                                <td>{{ $item->product->name ?? '-' }}</td>
                                <td>{{ $item->qty }}</td>
                                <td>{{ number_format($item->unit_cost, 2) }}</td>
                                <td>{{ number_format($item->subtotal, 2) }}</td>
                                <td>{{ $item->expiry_date?->format('Y-m-d') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection