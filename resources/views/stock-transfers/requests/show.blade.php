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

    @php
        $hasModifiedItems = collect($stockTransferRequest->items)->contains(function($it) {
            return !empty($it['modified_by_admin']) || (isset($it['original_qty']) && $it['original_qty'] != ($it['qty'] ?? 0));
        });
    @endphp

    @if($stockTransferRequest->status === 'approved' && $hasModifiedItems)
        <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width: 46px; height: 46px;">
                <i class="fas fa-exclamation-triangle fs-5"></i>
            </div>
            <div>
                <h6 class="mb-1 fw-bold text-dark">تنبيه: تم تعديل كمية المواد المصروفة من قِبل موظف المخزن الرئيسي</h6>
                <div class="small text-muted">قام أمين المخزن بتعديل كمية بعض المواد لتتوافق مع الرصيد المتاح لديه بالمخزن الرئيسي وقت المعالجة والاعتماد. تفاصيل الفروقات موضحة بالجدول أدناه.</div>
            </div>
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
                            @if($hasModifiedItems)
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning rounded-pill px-2.5 py-1 fw-bold ms-1">
                                    <i class="fas fa-pen me-1"></i> مع تعديل الكميات
                                </span>
                            @endif
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

    <!-- جدول المواد والكميات المطلوبة والمعتمدة -->
    <div class="row mt-4">
        <div class="col-12">
            @if($stockTransferRequest->status === 'pending' && !empty($isAdmin))
                <!-- نموذج موافقة أمين المخزن مع إمكانية تعديل الكميات وتنبيه النقص -->
                <form action="{{ route('stock-transfers.requests.approve', $stockTransferRequest) }}" method="POST" id="approveTransferForm">
                    @csrf
                    <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                        <div class="card-header bg-light border-0 d-flex align-items-center justify-content-between p-3" style="border-radius: 15px 15px 0 0;">
                            <div>
                                <h5 class="mb-1 fw-bold text-dark"><i class="fas fa-boxes me-2 text-primary"></i>المواد المطلوبة في هذه الشحنة (معاينة وتعديل الصرف)</h5>
                                <small class="text-muted">يمكنك مراجعة الرصيد المتوفر بالمخزن الرئيسي وتعديل الكمية المعتمدة للصرف لكل مادة حسب المتاح.</small>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 fw-bold">
                                {{ count($stockTransferRequest->items) }} مواد
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 45px;" class="text-center">#</th>
                                            <th style="min-width: 250px;">المادة أو الدواء</th>
                                            <th style="width: 140px;" class="text-center">الكمية المطلوبة</th>
                                            <th style="width: 160px;" class="text-center">المتوفر بالمخزن المصدر</th>
                                            <th style="min-width: 190px;" class="text-center">حالة الرصيد والتنبيه</th>
                                            <th style="width: 180px;" class="text-center bg-primary-subtle text-primary fw-bold">الكمية المعتمدة للصرف</th>
                                            <th style="min-width: 200px;">ملاحظات موظف المخزن (اختياري)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($stockTransferRequest->items as $index => $item)
                                            @php
                                                $pid = $item['product_id'];
                                                $requestedQty = (int) ($item['original_qty'] ?? $item['qty']);
                                                $availableInMain = (int) ($fromLocationQuantities[$pid] ?? 0);
                                                $isShortage = $requestedQty > $availableInMain;
                                                $suggestedApprovedQty = min($requestedQty, $availableInMain);
                                            @endphp
                                            <tr class="{{ $isShortage ? 'table-warning-subtle' : '' }}">
                                                <td class="text-center text-muted fw-bold">{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-bold text-dark">{{ $products[$pid]->name ?? 'غير معروف' }}</div>
                                                    <div class="small text-muted font-monospace">{{ $products[$pid]->code ?? 'PRD-'.$pid }} ({{ $products[$pid]->unit ?? 'وحدة' }})</div>
                                                    @if(!empty($item['reason']))
                                                        <div class="mt-1">
                                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-0.5 small">
                                                                <i class="fas fa-undo me-1"></i> السبب: {{ $item['reason'] }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                    @if(!empty($item['notes']))
                                                        <small class="text-muted d-block mt-1"><i class="fas fa-info-circle me-1"></i> {{ $item['notes'] }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary fs-6 px-3 py-1.5">{{ $requestedQty }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge {{ $availableInMain > 0 ? 'bg-info-subtle text-info-emphasis border border-info' : 'bg-danger-subtle text-danger border border-danger' }} fs-6 px-3 py-1.5 fw-bold">
                                                        {{ $availableInMain }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    @if($isShortage)
                                                        <span class="badge bg-danger text-white rounded-pill px-2.5 py-1.5 small shadow-xs d-inline-flex align-items-center gap-1">
                                                            <i class="fas fa-exclamation-triangle"></i> المطلوب أكثر من المتوفر (عجز {{ $requestedQty - $availableInMain }})
                                                        </span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1.5 small">
                                                            <i class="fas fa-check-circle me-1"></i> متوفر بالكامل
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="bg-primary-subtle">
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" 
                                                               name="approved_items[{{ $index }}][qty]" 
                                                               class="form-control form-control-sm text-center fw-bold fs-6 border-primary" 
                                                               value="{{ old('approved_items.'.$index.'.qty', $suggestedApprovedQty) }}" 
                                                               min="0" 
                                                               max="{{ $availableInMain }}" 
                                                               required>
                                                    </div>
                                                    <small class="text-muted d-block text-center mt-1">الحد الأقصى: {{ $availableInMain }}</small>
                                                </td>
                                                <td>
                                                    <input type="text" 
                                                           name="approved_items[{{ $index }}][admin_notes]" 
                                                           class="form-control form-control-sm" 
                                                           value="{{ old('approved_items.'.$index.'.admin_notes', $isShortage ? 'تم تعديل الكمية حسب المتوفر بالمخزن الرئيسي' : '') }}" 
                                                           placeholder="سبب التعديل أو ملاحظات الصرف...">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-light p-3 d-flex flex-wrap align-items-center justify-content-between gap-3" style="border-radius: 0 0 15px 15px;">
                            <div class="small text-muted">
                                <i class="fas fa-info-circle text-primary me-1"></i> سيتم خصم الكميات المحددة في عمود "الكمية المعتمدة للصرف" حصراً من وجبات المخزن الرئيسي ونقلها للمخزن الفرعي.
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" class="btn btn-success rounded-pill px-4 py-2 fw-bold shadow-sm">
                                    <i class="fas fa-check-circle me-1.5"></i> الموافقة على الطلب وصرف المواد
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="d-flex justify-content-end mt-3">
                    <form action="{{ route('stock-transfers.requests.reject', $stockTransferRequest) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من رفض هذا الطلب بالكامل؟');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger rounded-pill px-4 py-2 fw-bold">
                            <i class="fas fa-times-circle me-1"></i> رفض الطلب بالكامل
                        </button>
                    </form>
                </div>
            @else
                <!-- جدول العرض الاعتيادي (عند الاعتماد أو لغير مدراء المخزن) -->
                <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                    <div class="card-header bg-light border-0 d-flex align-items-center justify-content-between p-3" style="border-radius: 15px 15px 0 0;">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-boxes me-2 text-primary"></i>المواد في هذه الشحنة</h5>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">
                            {{ count($stockTransferRequest->items) }} مواد
                        </span>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 45px;" class="text-center">#</th>
                                        <th>المادة أو الدواء</th>
                                        <th style="width: 140px;" class="text-center">الكمية المطلوبة</th>
                                        @if($stockTransferRequest->status === 'approved')
                                            <th style="width: 160px;" class="text-center bg-success-subtle text-success fw-bold">الكمية المصروفة</th>
                                            <th style="min-width: 200px;" class="text-center">حالة الصرف والتعديل</th>
                                            <th style="min-width: 200px;">ملاحظات المخزن الرئيسي</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stockTransferRequest->items as $index => $item)
                                        @php
                                            $pid = $item['product_id'];
                                            $originalQty = (int) ($item['original_qty'] ?? $item['qty']);
                                            $dispensedQty = (int) $item['qty'];
                                            $isModified = !empty($item['modified_by_admin']) || ($originalQty != $dispensedQty);
                                        @endphp
                                        <tr class="{{ $isModified && $stockTransferRequest->status === 'approved' ? 'table-warning-subtle' : '' }}">
                                            <td class="text-center text-muted fw-bold">{{ $index + 1 }}</td>
                                            <td>
                                                <div class="fw-bold text-dark">{{ $products[$pid]->name ?? 'غير معروف' }}</div>
                                                <div class="small text-muted font-monospace">{{ $products[$pid]->code ?? 'PRD-'.$pid }} ({{ $products[$pid]->unit ?? 'وحدة' }})</div>
                                                @if(!empty($item['reason']))
                                                    <div class="mt-1">
                                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-0.5 small">
                                                            <i class="fas fa-undo me-1"></i> السبب: {{ $item['reason'] }}
                                                        </span>
                                                    </div>
                                                @endif
                                                @if(!empty($item['notes']))
                                                    <small class="text-muted d-block mt-1"><i class="fas fa-info-circle me-1"></i> {{ $item['notes'] }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($isModified && $stockTransferRequest->status === 'approved')
                                                    <span class="badge bg-secondary-subtle text-secondary text-decoration-line-through fs-6 px-2.5 py-1">{{ $originalQty }}</span>
                                                @else
                                                    <span class="badge bg-primary-subtle text-primary fs-6 px-3 py-1.5 fw-bold">{{ $originalQty }}</span>
                                                @endif
                                            </td>
                                            @if($stockTransferRequest->status === 'approved')
                                                <td class="text-center bg-success-subtle">
                                                    <span class="badge bg-success text-white fs-6 px-3 py-1.5 fw-bold shadow-xs">
                                                        {{ $dispensedQty }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    @if($isModified)
                                                        <span class="badge bg-warning text-dark border border-warning rounded-pill px-2.5 py-1.5 small fw-bold d-inline-flex align-items-center gap-1 shadow-xs">
                                                            <i class="fas fa-pen-fancy"></i> تم تعديل الكمية من المخزن الرئيسي
                                                        </span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1.5 small">
                                                            <i class="fas fa-check-circle me-1"></i> صُرفت بالكامل حسب الطلب
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(!empty($item['admin_notes']))
                                                        <span class="badge bg-light text-dark border p-2 text-wrap text-start">
                                                            <i class="fas fa-comment-dots text-primary me-1"></i> {{ $item['admin_notes'] }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted small">-</span>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if($stockTransferRequest->status === 'pending' && empty($isAdmin))
        <div class="alert alert-warning border-0 shadow-sm rounded-4 mt-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-hourglass-half fs-5 text-warning"></i>
                <div>
                    <strong>الطلب قيد المراجعة:</strong> تم إرسال طلبك بنجاح وهو بانتظار مراجعة وموافقة أمين المخزن الرئيسي لصرف الشحنة.
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
</div>
@endsection
