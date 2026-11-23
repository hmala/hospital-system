@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- رأس الصفحة وحالة الطلب -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        @php
                            $hasModifiedItems = collect($stockTransferRequest->items)->contains(function($it) {
                                return !empty($it['modified_by_admin']) || (isset($it['original_qty']) && $it['original_qty'] != ($it['qty'] ?? 0));
                            });
                        @endphp
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="mb-0 fw-bold"><i class="fas fa-boxes-stacked me-2 text-primary"></i>طلب مخزون #{{ $stockTransferRequest->id }}</h2>
                            @if($stockTransferRequest->status === 'pending')
                                <span class="badge bg-warning text-dark rounded-pill px-3 py-1.5 fw-bold shadow-xs">
                                    <i class="fas fa-hourglass-half me-1"></i> قيد المراجعة والاعتماد
                                </span>
                            @elseif($stockTransferRequest->status === 'approved')
                                <span class="badge bg-primary text-white rounded-pill px-3 py-1.5 fw-bold shadow-xs">
                                    <i class="fas fa-truck-loading me-1"></i> تم الصرف — بانتظار استلام القسم
                                </span>
                            @elseif($stockTransferRequest->status === 'completed')
                                <span class="badge bg-success text-white rounded-pill px-3 py-1.5 fw-bold shadow-xs">
                                    <i class="fas fa-check-double me-1"></i> مستلم ومودع بالعهدة
                                </span>
                            @else
                                <span class="badge bg-danger text-white rounded-pill px-3 py-1.5 fw-bold shadow-xs">
                                    <i class="fas fa-times-circle me-1"></i> مرفوض
                                </span>
                            @endif
                            @if($hasModifiedItems)
                                <span class="badge bg-warning text-dark border border-warning rounded-pill px-2.5 py-1 fw-bold shadow-xs" title="تم تعديل كمية بعض المواد أثناء الصرف">
                                    <i class="fas fa-pen-fancy me-1"></i> تم تعديل الكميات من المخزن
                                </span>
                            @endif
                        </div>
                        <p class="mb-0 text-muted">مراجعة تفاصيل طلب المواد وحالة الاعتماد والتسليم للعهدة الفرعية.</p>
                    </div>

                    @php
                        $user = auth()->user();
                        $isSuperAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'super_admin']);
                        $canEditOrCancel = $stockTransferRequest->status === 'pending' && (
                            $isSuperAdmin ||
                            $stockTransferRequest->requested_by == $user?->id ||
                            ($user?->location_id && $stockTransferRequest->to_location_id == $user->location_id)
                        );
                        $canConfirmReceipt = $stockTransferRequest->status === 'approved' && (
                            $isSuperAdmin ||
                            $stockTransferRequest->requested_by == $user?->id ||
                            ($user?->location_id && $stockTransferRequest->to_location_id == $user->location_id) ||
                            $user?->hasRole(['head_nurse', 'nurse', 'nicu_nurse', 'icu_nurse', 'surgery_staff', 'surgery_nurse', 'anesthesia', 'surgeon']) ||
                            in_array($user?->role, ['nurse', 'head_nurse', 'doctor', 'surgery_staff'])
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

    <!-- رسائل النجاح والأخطاء -->
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <!-- تنبيه الفروقات أو شارة الاستلام المكتمل -->
    @if($stockTransferRequest->status === 'approved' && $hasModifiedItems)
        <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width: 46px; height: 46px;">
                <i class="fas fa-exclamation-triangle fs-5"></i>
            </div>
            <div>
                <h6 class="mb-1 fw-bold text-dark">تنبيه: تم تعديل كمية المواد المصروفة من قِبل موظف المخزن الرئيسي</h6>
                <div class="small text-muted">قام أمين المخزن بتعديل كمية بعض المواد لتتوافق مع الرصيد المتاح بالمخزن الرئيسي. تفاصيل الفروقات موضحة بالجدول أدناه، يرجى مطابقتها وتأكيد الاستلام بأسفل الصفحة.</div>
            </div>
        </div>
    @elseif($stockTransferRequest->status === 'completed')
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3">
            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width: 46px; height: 46px;">
                <i class="fas fa-check-double fs-5"></i>
            </div>
            <div>
                <h6 class="mb-1 fw-bold text-dark">تم استلام هذه الشحنة وإيداع المواد بالكامل في عهدة القسم</h6>
                <div class="small text-muted">
                    المستلم: <strong>{{ $stockTransferRequest->receivedBy?->name ?? 'كادر القسم المستلم' }}</strong> |
                    توقيت الاستلام: <strong>{{ $stockTransferRequest->received_at?->format('Y-m-d H:i') }}</strong>
                    @if($stockTransferRequest->received_notes)
                        | ملاحظات الاستلام: <span class="badge bg-light text-dark border">"{{ $stockTransferRequest->received_notes }}"</span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- شبكة التفاصيل: مسار الشحنة ورصيد المخزن الفرعي -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-lg h-100" style="border-radius: 15px;">
                <div class="card-header bg-light border-0 d-flex align-items-center gap-2 p-3" style="border-radius: 15px 15px 0 0;">
                    <i class="fas fa-route text-primary"></i>
                    <h5 class="mb-0 fw-bold">مسار الشحنة والأطراف المسؤولة</h5>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-2.5">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                            <span class="text-muted"><i class="fas fa-warehouse me-1.5 text-primary"></i> المخزن المصدر:</span>
                            <span class="fw-bold text-dark">{{ $stockTransferRequest->fromLocation?->name ?? 'غير محدد' }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                            <span class="text-muted"><i class="fas fa-hospital me-1.5 text-info"></i> القسم / الردهة المستلمة:</span>
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-3 py-1 fw-bold fs-6">
                                {{ $stockTransferRequest->toLocation?->name ?? 'غير محدد' }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                            <span class="text-muted"><i class="fas fa-user-circle me-1.5 text-secondary"></i> مقدم الطلب:</span>
                            <span class="fw-semibold text-dark">{{ $stockTransferRequest->requestedBy?->name ?? 'غير معروف' }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                            <span class="text-muted"><i class="far fa-clock me-1.5 text-secondary"></i> تاريخ ووقت الطلب:</span>
                            <span class="small font-monospace">{{ $stockTransferRequest->created_at ? $stockTransferRequest->created_at->format('Y-m-d H:i') : '-' }}</span>
                        </div>
                        @if($stockTransferRequest->approved_by)
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                <span class="text-muted"><i class="fas fa-dolly me-1.5 text-primary"></i> موظف الصرف:</span>
                                <span class="fw-semibold text-primary">{{ $stockTransferRequest->approvedBy?->name ?? 'غير معروف' }} ({{ $stockTransferRequest->approved_at?->format('Y-m-d H:i') }})</span>
                            </div>
                        @endif
                        @if($stockTransferRequest->received_by)
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted"><i class="fas fa-check-circle me-1.5 text-success"></i> مستلم الشحنة:</span>
                                <span class="fw-bold text-success">{{ $stockTransferRequest->receivedBy?->name ?? 'كادر القسم' }} ({{ $stockTransferRequest->received_at?->format('Y-m-d H:i') }})</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-lg h-100" style="border-radius: 15px;">
                <div class="card-header bg-light border-0 d-flex align-items-center justify-content-between p-3" style="border-radius: 15px 15px 0 0;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-boxes text-info"></i>
                        <h5 class="mb-0 fw-bold">الرصيد اللحظي بعهدة القسم المستلم</h5>
                    </div>
                    <small class="text-muted">{{ $stockTransferRequest->toLocation?->name ?? '' }}</small>
                </div>
                <div class="card-body p-3">
                    @if($subLocationBatches->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-box-open fa-2x mb-2 text-secondary opacity-50"></i>
                            <p class="mb-0 small">لا يوجد رصيد حالي من هذه المواد في هذا القسم قبل وصول الشحنة.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>المادة</th>
                                        <th class="text-center">الرصيد المتاح بالقسم</th>
                                        <th class="text-center">تاريخ آخر توريد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subLocationBatches as $batch)
                                        <tr>
                                            <td class="fw-semibold">{{ $batch->product?->name ?? 'مادة' }}</td>
                                            <td class="text-center"><span class="badge bg-secondary rounded-pill px-2.5 py-1">{{ $batch->current_qty }}</span></td>
                                            <td class="text-center small text-muted">{{ optional($batch->original_received_at ?? $batch->received_at)->format('Y-m-d') }}</td>
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

    <!-- جدول المواد والكميات المطلوبة والمصروفة -->
    <div class="row">
        <div class="col-12">
            @if($stockTransferRequest->status === 'pending' && !empty($isAdmin))
                <!-- نموذج مراجعة واعتماد أمين المخزن مع إمكانية تعديل الكميات وتنبيه النقص -->
                <form action="{{ route('stock-transfers.requests.approve', $stockTransferRequest) }}" method="POST" id="approveTransferForm">
                    @csrf
                    <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                        <div class="card-header bg-light border-0 d-flex align-items-center justify-content-between p-3" style="border-radius: 15px 15px 0 0;">
                            <div>
                                <h5 class="mb-1 fw-bold text-dark"><i class="fas fa-dolly me-2 text-primary"></i>المواد في الشحنة (معاينة واعتماد وتعديل الصرف)</h5>
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
                                            <th style="min-width: 190px;" class="text-center">حالة الرصيد</th>
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
                                <i class="fas fa-info-circle text-primary me-1"></i> سيتم خصم الكميات المحددة في عمود "الكمية المعتمدة للصرف" حصراً من وجبات المخزن الرئيسي ونقلها للقسم المستلم.
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
                <!-- جدول العرض للردهات والعمليات والطلبات المصروفة -->
                <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                    <div class="card-header bg-light border-0 d-flex align-items-center justify-content-between p-3" style="border-radius: 15px 15px 0 0;">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-boxes-stacked me-2 text-primary"></i>المواد في هذه الشحنة</h5>
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
                                        @if(in_array($stockTransferRequest->status, ['approved', 'completed']))
                                            <th style="width: 160px;" class="text-center bg-success-subtle text-success fw-bold">
                                                {{ $stockTransferRequest->status === 'completed' ? 'الكمية المستلمة' : 'الكمية المصروفة' }}
                                            </th>
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
                                        <tr class="{{ $isModified && in_array($stockTransferRequest->status, ['approved', 'completed']) ? 'table-warning-subtle' : '' }}">
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
                                                @if($isModified && in_array($stockTransferRequest->status, ['approved', 'completed']))
                                                    <span class="badge bg-secondary-subtle text-secondary text-decoration-line-through fs-6 px-2.5 py-1">{{ $originalQty }}</span>
                                                @else
                                                    <span class="badge bg-primary-subtle text-primary fs-6 px-3 py-1.5 fw-bold">{{ $originalQty }}</span>
                                                @endif
                                            </td>
                                            @if(in_array($stockTransferRequest->status, ['approved', 'completed']))
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

    <!-- منطقة الإجراء الواحد: تأكيد استلام الشحنة وإيداعها في عهدة القسم (أسفل الجدول حصراً) -->
    @if($canConfirmReceipt)
        <div class="row mt-4" id="receiveStockCard">
            <div class="col-12">
                <div class="card border-0 shadow-lg border-start border-4 border-success" style="border-radius: 16px;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 52px; height: 52px;">
                                <i class="fas fa-boxes-packing fs-4"></i>
                            </div>
                            <div>
                                <h5 class="mb-1 fw-bold text-dark">تأكيد استلام الشحنة وإيداعها في عهدة القسم / الردهة</h5>
                                <div class="text-muted small">
                                    @if($hasModifiedItems)
                                        <span class="text-warning-emphasis fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> قام المخزن الرئيسي بتعديل بعض الكميات المصروفة.</span>
                                        يرجى فحص الشحنة ومطابقتها مع الكميات المصروفة بالجدول أعلاه، ثم الضغط على تأكيد الاستلام لإيداعها في عهدة الردهة/العمليات:
                                    @else
                                        يرجى مراجعة الصندوق والمواد ومطابقتها مع الجدول أعلاه، ثم الضغط على زر التأكيد أدناه لتوثيق الاستلام الرسمي وإيداع الشحنة في عهدة القسم:
                                    @endif
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <form action="{{ route('stock-transfers.requests.receive', $stockTransferRequest) }}" method="POST" id="receiveStockRequestForm" onsubmit="return confirm('هل تؤكد استلام هذه الشحنة بالكامل ومطابقة كمياتها وإيداعها في عهدة القسم؟');">
                            @csrf
                            <div class="row g-3 align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label fw-bold text-dark small mb-1">
                                        <i class="fas fa-pen-nib me-1 text-success"></i> ملاحظات كادر الردهة / العمليات عند الاستلام (اختياري)
                                    </label>
                                    <input type="text" 
                                           name="received_notes" 
                                           class="form-control rounded-pill px-3 py-2 border-success-subtle shadow-xs" 
                                           placeholder="مثال: تم فحص الصندوق واستلام المواد ومطابقة الكميات المصروفة بالكامل...">
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <button type="submit" class="btn btn-success btn-lg rounded-pill px-4 fw-bold shadow-sm w-100 py-2">
                                        <i class="fas fa-check-circle me-1.5"></i> تأكيد الاستلام وإيداع العهدة
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- تنبيه معلق للممرض إذا لم يُصرف الطلب بعد -->
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
