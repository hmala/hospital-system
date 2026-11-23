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
                            <a href="{{ route('nursing-sub-stores.index') }}" class="btn btn-sm btn-light rounded-circle shadow-2xs me-1" title="العودة لسجل المخازن">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                            <h2 class="mb-0 fw-bold">{{ $location->name }}</h2>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 font-monospace">LOC-{{ $location->id }}</span>
                        </div>
                        <p class="mb-0 text-muted">تفاصيل العهدة، الأرصدة المتوفرة، حركات صرف واستهلاك المرضى، وسجل تسليم العهدة.</p>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-warning rounded-pill px-3 py-2 fw-bold text-dark shadow-xs" data-bs-toggle="modal" data-bs-target="#changeCustodianModal">
                            <i class="fas fa-user-shield me-1"></i> تغيير مسؤول العهدة
                        </button>
                        <a href="{{ route('nursing-sub-stores.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2">
                            <i class="fas fa-list me-1"></i> قائمة المخازن
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- رسائل التنبيه -->
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    <!-- بطاقات تلخيصية سريعة -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 border-primary">
                <span class="text-muted small">أمين العهدة المعتمد:</span>
                <h5 class="fw-bold text-dark mt-1 mb-0">{{ $location->custodian?->name ?? 'غير محدد' }}</h5>
                <small class="text-muted">{{ $location->custodian?->phone ?: 'مسؤول العهدة الفعلي' }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 border-info">
                <span class="text-muted small">طاقم التمريض التابع:</span>
                <h5 class="fw-bold text-dark mt-1 mb-0">{{ $location->staff->count() }} ممرضين</h5>
                <small class="text-muted">مرتبطين بـ {{ $location->name }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 border-success">
                <span class="text-muted small">إجمالي رصيد المواد المتاحة:</span>
                <h5 class="fw-bold text-success mt-1 mb-0">{{ number_format($activeBatches->sum('current_qty')) }} وحدة</h5>
                <small class="text-muted">موزعة على {{ $activeBatches->pluck('product_id')->unique()->count() }} صنفاً</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 border-warning">
                <span class="text-muted small">طلبيات التعزيز الحديثة:</span>
                <h5 class="fw-bold text-warning-emphasis mt-1 mb-0">{{ $transferRequests->count() }} طلبيات</h5>
                <small class="text-muted">سجل الشحنات والتحويلات</small>
            </div>
        </div>
    </div>

    <!-- التبويبات التفاعلية -->
    <div class="card border-0 shadow-lg" style="border-radius: 16px;">
        <div class="card-header bg-light border-0 p-3" style="border-radius: 16px 16px 0 0;">
            <ul class="nav nav-pills card-header-pills gap-2" id="subStoreTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4 fw-bold shadow-xs" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stock-pane" type="button" role="tab">
                        <i class="fas fa-boxes me-1.5"></i> الأرصدة والوجبات المتوفرة ({{ $activeBatches->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 fw-bold shadow-xs" id="movements-tab" data-bs-toggle="tab" data-bs-target="#movements-pane" type="button" role="tab">
                        <i class="fas fa-hand-holding-medical me-1.5"></i> سجل صرفيات واستهلاك المرضى
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 fw-bold shadow-xs" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button" role="tab">
                        <i class="fas fa-history me-1.5"></i> أرشيف تسليم واستلام العهدة ({{ $location->custodianHistories->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 fw-bold shadow-xs" id="transfers-tab" data-bs-toggle="tab" data-bs-target="#transfers-pane" type="button" role="tab">
                        <i class="fas fa-truck-loading me-1.5"></i> طلبيات المخزن ({{ $transferRequests->count() }})
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="subStoreTabsContent">
                <!-- تبويب 1: الأرصدة الحالية بالعهدة -->
                <div class="tab-pane fade show active" id="stock-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>المادة أو الدواء</th>
                                    <th>رقم الوجبة / الباركود</th>
                                    <th class="text-center">الرصيد المتاح</th>
                                    <th class="text-center">تاريخ انتهاء الصلاحية</th>
                                    <th class="text-center">تاريخ الإيداع</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeBatches as $idx => $batch)
                                    <tr>
                                        <td class="text-muted fw-bold">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $batch->product?->name ?? 'غير معروف' }}</div>
                                            <small class="text-muted font-monospace">{{ $batch->product?->code ?? 'PRD-'.$batch->product_id }} ({{ $batch->product?->unit ?? 'وحدة' }})</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                                {{ $batch->internal_barcode ?: $batch->original_barcode }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill fs-6 px-3 py-1 fw-bold">
                                                {{ $batch->current_qty }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($batch->expiry_date)
                                                @php
                                                    $isNearExpiry = \Carbon\Carbon::parse($batch->expiry_date)->diffInDays(now()) < 60;
                                                @endphp
                                                <span class="badge {{ $isNearExpiry ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-light text-dark border' }} rounded-pill px-2.5 py-1">
                                                    {{ $batch->expiry_date }}
                                                </span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center small text-muted">
                                            {{ optional($batch->received_at ?? $batch->original_received_at)->format('Y-m-d') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            لا توجد وجبات أو أرصدة متوفرة حالياً في عهدة هذا المخزن الفرعي.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- تبويب 2: سجل سحوبات وصرفيات المرضى -->
                <div class="tab-pane fade" id="movements-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>المادة المصروفة</th>
                                    <th class="text-center">الكمية المستهلكة</th>
                                    <th>الممرض الصارف</th>
                                    <th>نوع الحركة والملاحظات</th>
                                    <th class="text-center">تاريخ ووقت الصرف</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movements as $movement)
                                    <tr>
                                        <td class="text-muted fw-bold">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $movement->batch?->product?->name ?? 'مادة' }}</div>
                                            <small class="text-muted font-monospace">{{ $movement->batch?->internal_barcode }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 fw-bold fs-6">
                                                -{{ $movement->quantity }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-medium text-dark"><i class="fas fa-user-circle text-muted me-1"></i> {{ $movement->user?->name ?? 'غير معروف' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2 py-0.5 small me-1">
                                                {{ $movement->type === 'dispense' ? 'صرف مريض' : $movement->type }}
                                            </span>
                                            <span class="small text-muted">{{ $movement->notes }}</span>
                                        </td>
                                        <td class="text-center small text-muted font-monospace">
                                            {{ $movement->created_at ? $movement->created_at->format('Y-m-d H:i') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            لم تسجل أي حركات صرف للمرضى من عهدة هذا القسم بعد.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($movements->hasPages())
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $movements->links() }}
                        </div>
                    @endif
                </div>

                <!-- تبويب 3: أرشيف تسليم واستلام العهدة -->
                <div class="tab-pane fade" id="history-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>الممرض المسؤول (المستلم للعهدة)</th>
                                    <th>المشرف الآمر بالتكليف</th>
                                    <th>تاريخ ووقت التكليف</th>
                                    <th>سبب التكليف والملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($location->custodianHistories as $history)
                                    <tr>
                                        <td class="text-muted fw-bold">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center shadow-2xs" style="width: 32px; height: 32px; font-size: 13px;">
                                                    <i class="fas fa-user-shield"></i>
                                                </div>
                                                <span class="fw-bold text-dark">{{ $history->user?->name ?? 'غير معروف' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-medium text-dark"><i class="fas fa-user-tie text-secondary me-1"></i> {{ $history->assignedBy?->name ?? 'النظام' }}</span>
                                        </td>
                                        <td class="font-monospace small text-muted">
                                            <i class="far fa-clock me-1"></i> {{ $history->assigned_at ? $history->assigned_at->format('Y-m-d H:i') : '-' }}
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border p-2 text-wrap text-start">
                                                {{ $history->notes ?: 'تكليف واستلام رسمي للعهدة' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            لا يوجد سجل تاريخي سابق لتسليم العهدة لهذا المخزن.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- تبويب 4: طلبيات المخزن -->
                <div class="tab-pane fade" id="transfers-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>رقم الطلبية</th>
                                    <th>مقدم الطلب</th>
                                    <th>تاريخ الطلب</th>
                                    <th class="text-center">حالة الطلبية</th>
                                    <th>موظف الصرف</th>
                                    <th>مستلم الشحنة</th>
                                    <th class="text-center">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transferRequests as $req)
                                    <tr>
                                        <td><span class="badge bg-light text-dark border font-monospace px-2.5 py-1">#{{ $req->id }}</span></td>
                                        <td>{{ $req->requestedBy?->name ?? 'غير معروف' }}</td>
                                        <td class="small text-muted font-monospace">{{ $req->created_at ? $req->created_at->format('Y-m-d H:i') : '-' }}</td>
                                        <td class="text-center">
                                            @if($req->status === 'pending')
                                                <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1">قيد المراجعة</span>
                                            @elseif($req->status === 'approved')
                                                <span class="badge bg-primary text-white rounded-pill px-2.5 py-1">بانتظار الاستلام</span>
                                            @elseif($req->status === 'completed')
                                                <span class="badge bg-success text-white rounded-pill px-2.5 py-1"><i class="fas fa-check-double me-1"></i> مستلم</span>
                                            @else
                                                <span class="badge bg-danger text-white rounded-pill px-2.5 py-1">مرفوض</span>
                                            @endif
                                        </td>
                                        <td class="small">{{ $req->approvedBy?->name ?? '-' }}</td>
                                        <td class="small">{{ $req->receivedBy?->name ?? '-' }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('stock-transfers.requests.show', $req) }}" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-0.5">
                                                عرض
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            لا توجد طلبيات مخزن مسجلة لهذا القسم.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal تغيير مسؤول العهدة من صفحة التفاصيل -->
<div class="modal fade" id="changeCustodianModal" tabindex="-1" aria-labelledby="changeCustodianModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
            <form action="{{ route('nursing-sub-stores.assign-custodian', $location) }}" method="POST">
                @csrf
                <div class="modal-header bg-light border-0 p-3.5" style="border-radius: 18px 18px 0 0;">
                    <h5 class="modal-title fw-bold mb-0" id="changeCustodianModalLabel">تكليف وتغيير مسؤول عهدة {{ $location->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small mb-1">المسؤول الحالي:</label>
                        <div class="p-2.5 bg-light rounded-3 fw-bold text-dark">{{ $location->custodian?->name ?? 'غير محدد' }}</div>
                    </div>
                    <div class="mb-3">
                        <label for="custodian_select" class="form-label fw-bold text-dark small mb-1">الممرض الجديد: <span class="text-danger">*</span></label>
                        <select name="custodian_id" id="custodian_select" class="form-select rounded-3 py-2" required>
                            <option value="">-- اختر ممرضاً لتكليفه بالعهدة --</option>
                            @php
                                $wardNurses = $availableNurses->where('location_id', $location->id);
                                $otherNurses = $availableNurses->where('location_id', '!=', $location->id);
                            @endphp
                            @if($wardNurses->isNotEmpty())
                                <optgroup label="كادر تمريض {{ $location->name }} (نفس الطابق)">
                                    @foreach($wardNurses as $nurse)
                                        <option value="{{ $nurse->id }}" {{ $location->custodian_id == $nurse->id ? 'selected' : '' }}>
                                            {{ $nurse->name }} (كادر الردهة الحالي)
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if($otherNurses->isNotEmpty())
                                <optgroup label="باقي كادر التمريض في المستشفى">
                                    @foreach($otherNurses as $nurse)
                                        <option value="{{ $nurse->id }}" {{ $location->custodian_id == $nurse->id ? 'selected' : '' }}>
                                            {{ $nurse->name }} ({{ $nurse->location?->name ?: 'غير مقيد بردهة' }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="sync_user_location" id="syncUserLoc" value="1" checked>
                            <label class="form-check-label text-dark small fw-medium" for="syncUserLoc">
                                تثبيت وربط موقع عمل الممرض بهذا المخزن الفرعي تلقائياً
                            </label>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label for="change_notes" class="form-label fw-bold text-dark small mb-1">سبب التكليف وملاحظات التسليم والاستلام:</label>
                        <textarea name="notes" id="change_notes" class="form-control rounded-3" rows="3" placeholder="ملاحظات التسليم..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3 d-flex justify-content-between" style="border-radius: 0 0 18px 18px;">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark shadow-xs">
                        <i class="fas fa-check-circle me-1"></i> حفظ وتثبيت
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
