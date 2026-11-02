@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h2 class="mb-1 fw-bold"><i class="fas fa-eye me-3"></i>عرض طلب مخزون #{{ $stockTransferRequest->id }}</h2>
                        <p class="mb-0 text-muted">مراجعة تفاصيل طلب المواد وحالة الاعتماد من المخزن الرئيسي.</p>
                    </div>
                    @php
                        $isSuperAdmin = auth()->user()?->hasRole(['admin', 'admin-hsop', 'super_admin']);
                        $canEditOrCancel = $stockTransferRequest->status === 'pending' && (
                            $isSuperAdmin ||
                            $stockTransferRequest->requested_by == auth()->id() ||
                            (auth()->user()?->location_id && $stockTransferRequest->to_location_id == auth()->user()->location_id)
                        );
                    @endphp
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if($canEditOrCancel)
                            <a href="{{ route('stock-transfers.requests.edit', $stockTransferRequest) }}" class="btn btn-warning rounded-pill px-3 py-2 fw-bold text-dark shadow-xs">
                                <i class="fas fa-edit me-1"></i> تعديل الطلبية
                            </a>
                            <form action="{{ route('stock-transfers.requests.destroy', $stockTransferRequest) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إلغاء وحذف هذه الطلبية نهائياً؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger rounded-pill px-3 py-2 fw-bold">
                                    <i class="fas fa-trash-alt me-1"></i> إلغاء الطلبية
                                </button>
                            </form>
                        @endif
                        @if(empty($isAdmin))
                            <a href="{{ route('nursing-station.index') }}" class="btn btn-primary rounded-pill px-3 py-2 fw-bold shadow-xs">
                                <i class="fas fa-arrow-right me-1"></i> العودة لمحطة التمريض
                            </a>
                        @endif
                        <a href="{{ route('stock-transfers.requests.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2">
                            <i class="fas fa-list me-1"></i> سجل الطلبات
                        </a>
                    </div>
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
                    <p><strong>من:</strong> {{ $stockTransferRequest->fromLocation?->name ?? 'غير محدد' }}</p>
                    <p><strong>إلى:</strong> {{ $stockTransferRequest->toLocation?->name ?? 'غير محدد' }}</p>
                    <p><strong>نوع الطلب:</strong>
                        @php
                            $typeLabel = 'نقل مخزون';
                            if ($stockTransferRequest->fromLocation?->type === 'main' && $stockTransferRequest->toLocation?->type === 'sub') {
                                $typeLabel = 'طلب تعزيز من الرئيسي';
                            } elseif ($stockTransferRequest->fromLocation?->type === 'sub' && $stockTransferRequest->toLocation?->type === 'main') {
                                $typeLabel = 'إرجاع إلى الرئيسي';
                            }
                        @endphp
                        <span class="badge bg-info text-dark">{{ $typeLabel }}</span>
                    </p>
                    <p><strong>الوضع:</strong> 
                        @if($stockTransferRequest->status === 'pending')
                            <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1 fw-bold">قيد المراجعة</span>
                        @elseif($stockTransferRequest->status === 'approved')
                            <span class="badge bg-success text-white rounded-pill px-2.5 py-1 fw-bold">معتمد وتم الصرف</span>
                        @else
                            <span class="badge bg-danger text-white rounded-pill px-2.5 py-1 fw-bold">مرفوض</span>
                        @endif
                    </p>
                    <p><strong>مقدم الطلب:</strong> {{ $stockTransferRequest->requestedBy?->name ?? 'غير معروف' }}</p>
                    <p><strong>تاريخ الطلب:</strong> {{ $stockTransferRequest->created_at ? $stockTransferRequest->created_at->format('Y-m-d H:i') : '-' }}</p>
                    @if($stockTransferRequest->approved_by)
                        <p><strong>تمت المعالجة بواسطة:</strong> {{ $stockTransferRequest->approvedBy?->name ?? 'غير معروف' }}</p>
                        <p><strong>تاريخ المعالجة:</strong> {{ $stockTransferRequest->approved_at?->format('Y-m-d H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                <div class="card-header bg-light border-0" style="border-radius: 15px 15px 0 0;">
                    <h5 class="mb-0">رصيد المواد الحالي في المخزن الفرعي</h5>
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
                                            <td>{{ $batch->product?->name ?? 'مادة' }}</td>
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
                    <h5 class="mb-0">المواد المطلوبة في هذه الشحنة</h5>
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
                                        <td class="fw-bold">{{ $item['qty'] }}</td>
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
        @if(!empty($isAdmin))
            <div class="row mt-4">
                <div class="col-12 d-flex gap-2">
                    <form action="{{ route('stock-transfers.requests.approve', $stockTransferRequest) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">
                            <i class="fas fa-check-circle me-1"></i> الموافقة على الطلب وصرف المواد
                        </button>
                    </form>
                    <form action="{{ route('stock-transfers.requests.reject', $stockTransferRequest) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                            <i class="fas fa-times-circle me-1"></i> رفض الطلب
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="alert alert-warning border-0 shadow-sm rounded-4 mt-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-hourglass-half fs-5 text-warning"></i>
                    <div>
                        <strong>الطلب قيد المراجعة:</strong> تم استلام طلبك بنجاح وهو بانتظار مراجعة وموافقة أمين المخزن الرئيسي لصرف الشحنة.
                    </div>
                </div>
                @if($canEditOrCancel)
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('stock-transfers.requests.edit', $stockTransferRequest) }}" class="btn btn-sm btn-warning rounded-pill px-3 py-1.5 fw-bold text-dark">
                            <i class="fas fa-edit me-1"></i> تعديل المواد والكميات
                        </a>
                        <form action="{{ route('stock-transfers.requests.destroy', $stockTransferRequest) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إلغاء وحذف هذه الطلبية نهائياً؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1.5 fw-bold">
                                <i class="fas fa-trash-alt me-1"></i> إلغاء الطلبية
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>
@endsection
