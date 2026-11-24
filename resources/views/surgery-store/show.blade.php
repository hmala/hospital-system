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
                            <a href="{{ route('surgery-store.index') }}" class="btn btn-sm btn-light rounded-circle shadow-2xs me-1" title="العودة لسجل مخازن وصالات العمليات">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                            <h2 class="mb-0 fw-bold">{{ $location->name }}</h2>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 font-monospace">LOC-{{ $location->id }}</span>
                        </div>
                        <p class="mb-0 text-muted">تفاصيل العهدة الجراحية، الأرصدة المتوفرة، حركات صرف واستهلاك العمليات، وسجل تسليم العهدة.</p>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('surgery-store.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2">
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

    @php
        $isMainSurgeryStore = str_contains($location->name, 'رئيسي') || str_contains($location->name, 'مخزن');
    @endphp

    <!-- بطاقات تلخيصية سريعة -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 border-primary">
                <span class="text-muted small">أمين العهدة المعتمد:</span>
                <h5 class="fw-bold text-dark mt-1 mb-0">{{ $location->custodian?->name ?? 'غير محدد' }}</h5>
                <small class="text-muted">{{ $location->custodian?->phone ?: 'موظف العمليات المسؤول' }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 border-info">
                <span class="text-muted small">كادر العمليات المرتبط:</span>
                <h5 class="fw-bold text-dark mt-1 mb-0">{{ $location->staff->count() }} كادر</h5>
                <small class="text-muted">مرتبطين بـ {{ $location->name }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 border-success">
                <span class="text-muted small">إجمالي رصيد المواد المتاحة:</span>
                <h5 class="fw-bold text-success mt-1 mb-0">{{ number_format($activeBatches->sum('current_qty')) }} وحدة</h5>
                <small class="text-muted">موزعة على {{ $activeBatches->pluck('product_id')->unique()->count() }} صنفاً جراحياً</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 border-warning">
                @if($isMainSurgeryStore)
                    <span class="text-muted small">طلبيات التزويد من المستودع العام:</span>
                    <h5 class="fw-bold text-warning-emphasis mt-1 mb-0">{{ $transferRequests->count() }} طلبيات</h5>
                    <small class="text-muted">تعزيز رصيد مخزن العمليات المركزي</small>
                @else
                    <span class="text-muted small">آلية تزويد الصالة:</span>
                    <h5 class="fw-bold text-primary mt-1 mb-0">صرف مباشر</h5>
                    <small class="text-muted">يتم التجهيز من مخزن العمليات الرئيسي</small>
                @endif
            </div>
        </div>
    </div>

    <!-- التبويبات التفاعلية -->
    <div class="card border-0 shadow-lg" style="border-radius: 16px;">
        <div class="card-header bg-light border-0 p-3" style="border-radius: 16px 16px 0 0;">
            <ul class="nav nav-pills card-header-pills gap-2" id="surgeryStoreTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4 fw-bold shadow-xs" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stock-pane" type="button" role="tab">
                        <i class="fas fa-boxes me-1.5"></i> الأرصدة والوجبات المتوفرة ({{ $activeBatches->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 fw-bold shadow-xs" id="movements-tab" data-bs-toggle="tab" data-bs-target="#movements-pane" type="button" role="tab">
                        <i class="fas fa-hand-holding-medical me-1.5"></i> سجل صرفيات واستهلاك العمليات
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 fw-bold shadow-xs" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button" role="tab">
                        <i class="fas fa-history me-1.5"></i> أرشيف تسليم واستلام العهدة ({{ $location->custodianHistories->count() }})
                    </button>
                </li>
                @if($isMainSurgeryStore)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill px-4 fw-bold shadow-xs" id="transfers-tab" data-bs-toggle="tab" data-bs-target="#transfers-pane" type="button" role="tab">
                            <i class="fas fa-truck-loading me-1.5"></i> طلبيات المستودع العام ({{ $transferRequests->count() }})
                        </button>
                    </li>
                @endif
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="surgeryStoreTabsContent">
                <!-- تبويب 1: الأرصدة الحالية بالعهدة -->
                <div class="tab-pane fade show active" id="stock-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>المادة أو المستلزم الجراحي</th>
                                    <th>رقم الوجبة / الباركود</th>
                                    <th class="text-center">الرصيد المتاح</th>
                                    <th class="text-center">تاريخ انتهاء الصلاحية</th>
                                    <th class="text-center">الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeBatches as $idx => $batch)
                                    <tr>
                                        <td class="text-muted fw-bold">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $batch->product?->name ?? 'مستلزم غير محدد' }}</div>
                                            <small class="text-muted font-monospace">كود: {{ $batch->product?->code ?: 'PRD-'.$batch->product_id }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace px-2.5 py-1">
                                                {{ $batch->batch_number ?: 'وجبة افتراضية' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success fs-6 fw-bold px-3 py-1">
                                                {{ number_format($batch->current_qty) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($batch->expiry_date)
                                                @php
                                                    $isExpired = \Carbon\Carbon::parse($batch->expiry_date)->isPast();
                                                    $isNearExpiry = \Carbon\Carbon::parse($batch->expiry_date)->diffInDays(now()) < 60;
                                                @endphp
                                                <span class="fw-medium {{ $isExpired ? 'text-danger fw-bold' : ($isNearExpiry ? 'text-warning-emphasis' : 'text-dark') }}">
                                                    {{ \Carbon\Carbon::parse($batch->expiry_date)->format('Y-m-d') }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(isset($isExpired) && $isExpired)
                                                <span class="badge bg-danger rounded-pill px-2.5 py-1">منتهي الصلاحية</span>
                                            @elseif(isset($isNearExpiry) && $isNearExpiry)
                                                <span class="badge bg-warning rounded-pill px-2.5 py-1 text-dark">قارب الانتهاء</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1">صالح للاستخدام</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-boxes fs-1 mb-2 text-secondary opacity-50"></i>
                                            <p class="mb-0">لا توجد مواد أو وجبات متوفرة حالياً في رصيد هذا الموقع الجراحي.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- تبويب 2: سجل حركات الصرف والاستهلاك للعمليات -->
                <div class="tab-pane fade" id="movements-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>التاريخ والوقت</th>
                                    <th>العملية الجراحية / المريض</th>
                                    <th>الجراح المسؤول</th>
                                    <th>المادة المنصرفة</th>
                                    <th class="text-center">الكمية</th>
                                    <th>القائم بالصرف</th>
                                    <th>الملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movements as $m)
                                    <tr>
                                        <td class="text-muted fw-bold">#{{ $m->id }}</td>
                                        <td>
                                            <div class="fw-medium text-dark">{{ $m->created_at->format('Y-m-d') }}</div>
                                            <small class="text-muted">{{ $m->created_at->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            @if($m->surgery)
                                                <div class="fw-bold text-primary">
                                                    <a href="{{ route('surgeries.show', $m->surgery) }}" class="text-decoration-none">
                                                        {{ $m->surgery->surgery_type ?: ($m->surgery->surgicalOperation?->name ?? 'عملية جراحية #'.$m->surgery->id) }}
                                                    </a>
                                                </div>
                                                <small class="text-dark">المريض: {{ $m->surgery->patient?->name ?? 'غير محدد' }}</small>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1">صرف مباشر / عهدة</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($m->surgery)
                                                <span>{{ $m->surgery->doctor?->name ?: ($m->surgery->surgeon_name ?: '—') }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $m->batch?->product?->name ?? 'مستلزم غير محدد' }}</div>
                                            @if($m->batch?->batch_number)
                                                <small class="text-muted font-monospace">وجبة: {{ $m->batch->batch_number }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger-subtle text-danger fs-6 fw-bold px-2.5 py-1">
                                                -{{ number_format($m->quantity) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small fw-medium text-dark">{{ $m->user?->name ?? 'النظام' }}</div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $m->notes ?: 'صرف سريري معتمد' }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="fas fa-hand-holding-medical fs-1 mb-2 text-secondary opacity-50"></i>
                                            <p class="mb-0">لا توجد حركات صرف أو استهلاك مسجلة لهذا الموقع الجراحي حتى الآن.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($movements->hasPages())
                        <div class="p-3 border-top d-flex justify-content-center">
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
                                    <th>أمين العهدة المكلف</th>
                                    <th>الآمر بالتكليف (المسؤول)</th>
                                    <th>تاريخ وساعة التكليف</th>
                                    <th>البيان والملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($location->custodianHistories()->latest('assigned_at')->get() as $h)
                                    <tr>
                                        <td class="text-muted fw-bold">#{{ $h->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                    <i class="fas fa-user-check"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark">{{ $h->user?->name ?? 'غير محدد' }}</div>
                                                    <small class="text-muted">{{ $h->user?->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-dark fw-medium">{{ $h->assignedBy?->name ?? 'إدارة العمليات' }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-dark">{{ $h->assigned_at ? $h->assigned_at->format('Y-m-d') : $h->created_at->format('Y-m-d') }}</div>
                                            <small class="text-muted">{{ $h->assigned_at ? $h->assigned_at->format('h:i A') : $h->created_at->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $h->notes ?: 'تكليف واستلام رسمي للعهدة' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-history fs-1 mb-2 text-secondary opacity-50"></i>
                                            <p class="mb-0">لا توجد سجلات تاريخية سابقة لتسليم واستلام العهدة لهذا الموقع.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($isMainSurgeryStore)
                <!-- تبويب 4: طلبيات تعزيز المخزن المركزي -->
                <div class="tab-pane fade" id="transfers-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>الحالة</th>
                                    <th>نوع الشحنة</th>
                                    <th>مقدم الطلب</th>
                                    <th>تاريخ الطلب</th>
                                    <th>بيانات الاستلام والتسليم</th>
                                    <th class="text-center pe-3">الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transferRequests as $tr)
                                    @php
                                        $isIncoming = $tr->to_location_id == $location->id;
                                    @endphp
                                    <tr>
                                        <td class="fw-bold text-dark">
                                            <a href="{{ route('stock-transfers.requests.show', $tr) }}" class="text-decoration-none">
                                                #{{ $tr->id }}
                                            </a>
                                        </td>
                                        <td>
                                            @php
                                                $statusBadges = [
                                                    'pending' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                                    'approved' => 'bg-info-subtle text-info border-info-subtle',
                                                    'rejected' => 'bg-danger-subtle text-danger border-danger-subtle',
                                                    'completed' => 'bg-success-subtle text-success border-success-subtle',
                                                    'cancelled' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                                ];
                                                $badgeClass = $statusBadges[$tr->status] ?? 'bg-light text-dark';
                                            @endphp
                                            <span class="badge {{ $badgeClass }} border rounded-pill px-2.5 py-1">
                                                {{ __($tr->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($isIncoming)
                                                <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1">
                                                    <i class="fas fa-arrow-down me-1"></i> تزويد وارد (تعزيز رصيد)
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1">
                                                    <i class="fas fa-arrow-up me-1"></i> تحويل صادر
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-medium text-dark">{{ $tr->requestedBy?->name ?? 'غير محدد' }}</span>
                                        </td>
                                        <td>
                                            <div class="text-dark">{{ $tr->created_at->format('Y-m-d') }}</div>
                                            <small class="text-muted">{{ $tr->created_at->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            @if($tr->received_at)
                                                <div class="text-success small fw-bold">
                                                    <i class="fas fa-check-circle me-1"></i> تم الاستلام بواسطة: {{ $tr->receivedBy?->name ?? 'أمين العهدة' }}
                                                </div>
                                                <small class="text-muted">{{ \Carbon\Carbon::parse($tr->received_at)->format('Y-m-d h:i A') }}</small>
                                            @elseif($tr->status === 'approved')
                                                <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2.5 py-1">
                                                    <i class="fas fa-clock me-1"></i> بانتظار تأكيد استلام العهدة
                                                </span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center pe-3">
                                            <a href="{{ route('stock-transfers.requests.show', $tr) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                <i class="fas fa-eye me-1"></i> استعراض / استلام
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fas fa-truck-loading fs-1 mb-2 text-secondary opacity-50"></i>
                                            <p class="mb-0">لا توجد طلبيات تعزيز رصيد مسجلة لهذا الموقع الجراحي.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
