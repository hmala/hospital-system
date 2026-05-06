@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1 fw-bold"><i class="fas fa-eye me-3"></i>عرض طلب مخزون #{{ $stockTransferRequest->id }}</h2>
                        <p class="mb-0 text-muted">مراجعة حالة الطلب ومحتويات المخزن الفرعي قبل اتخاذ القرار.</p>
                    </div>
                    <a href="{{ route('stock-transfers.requests.index') }}" class="btn btn-outline-secondary rounded-pill">
                        <i class="fas fa-arrow-left me-2"></i>قائمة الطلبات
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                <div class="card-header bg-light border-0" style="border-radius: 15px 15px 0 0;">
                    <h5 class="mb-0">تفاصيل الطلب</h5>
                </div>
                <div class="card-body">
                    <p><strong>من:</strong> {{ $stockTransferRequest->fromLocation->name }}</p>
                    <p><strong>إلى:</strong> {{ $stockTransferRequest->toLocation->name }}</p>
                    <p><strong>نوع الطلب:</strong>
                        @php
                            $typeLabel = 'نقل مخزون';
                            if ($stockTransferRequest->fromLocation->type === 'main' && $stockTransferRequest->toLocation->type === 'sub') {
                                $typeLabel = 'طلب من الرئيسي';
                            } elseif ($stockTransferRequest->fromLocation->type === 'sub' && $stockTransferRequest->toLocation->type === 'main') {
                                $typeLabel = 'إرجاع إلى الرئيسي';
                            }
                        @endphp
                        <span class="badge bg-info text-dark">{{ $typeLabel }}</span>
                    </p>
                    <p><strong>الوضع:</strong> {{ ucfirst($stockTransferRequest->status) }}</p>
                    <p><strong>مقدم الطلب:</strong> {{ $stockTransferRequest->requestedBy->name ?? 'غير معروف' }}</p>
                    <p><strong>تاريخ الطلب:</strong> {{ $stockTransferRequest->created_at->format('Y-m-d H:i') }}</p>
                    @if($stockTransferRequest->approved_by)
                        <p><strong>تمت المعالجة بواسطة:</strong> {{ $stockTransferRequest->approvedBy->name ?? 'غير معروف' }}</p>
                        <p><strong>تاريخ المعالجة:</strong> {{ $stockTransferRequest->approved_at?->format('Y-m-d H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                <div class="card-header bg-light border-0" style="border-radius: 15px 15px 0 0;">
                    <h5 class="mb-0">مخزون الفرع الحالي</h5>
                </div>
                <div class="card-body">
                    @if($subLocationBatches->isEmpty())
                        <p class="text-muted">لا توجد كميات حالية من المواد المطلوبة في هذا المخزن الفرعي.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>المادة</th>
                                        <th>الكمية الحالية</th>
                                        <th>تاريخ الاستلام</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subLocationBatches as $batch)
                                        <tr>
                                            <td>{{ $batch->product->name }}</td>
                                            <td>{{ $batch->current_qty }}</td>
                                            <td>{{ optional($batch->original_received_at ?? $batch->received_at)->format('Y-m-d') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                <div class="card-header bg-light border-0" style="border-radius: 15px 15px 0 0;">
                    <h5 class="mb-0">عناصر الطلب</h5>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th>المادة</th>
                                    <th>الكمية المطلوبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockTransferRequest->items as $item)
                                    <tr>
                                        <td>{{ $products[$item['product_id']]->name ?? 'غير معروف' }}</td>
                                        <td>{{ $item['qty'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($stockTransferRequest->status === 'pending')
        <div class="row mt-4">
            <div class="col-12 d-flex gap-2">
                <form action="{{ route('stock-transfers.requests.approve', $stockTransferRequest) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success rounded-pill px-4">الموافقة على الطلب</button>
                </form>
                <form action="{{ route('stock-transfers.requests.reject', $stockTransferRequest) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger rounded-pill px-4">رفض الطلب</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
