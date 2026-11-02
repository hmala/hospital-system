@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Header Section -->
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-header bg-gradient bg-primary text-white p-3 rounded-top-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px;">
                    <i class="fas fa-clipboard-list fs-5"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold">
                        @if(!empty($userLocation))
                            طلبات تعزيز مخزن الطابق — {{ $userLocation->name }}
                        @else
                            سجل طلبات تحويل المخزون العام
                        @endif
                    </h5>
                    <small class="text-white-50">
                        @if(!empty($userLocation))
                            متابعة وإدارة طلبيات المواد المرفوعة من عهدة طابقك للمستودع المركزي
                        @else
                            مراجعة واعتماد طلبات النقل والتعزيز بين المخزن الرئيسي والمخازن الفرعية
                        @endif
                    </small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if(!$isAdmin)
                    <a href="{{ route('nursing-station.index') }}" class="btn btn-light btn-sm rounded-pill px-3 py-1.5 fw-bold shadow-xs text-primary">
                        <i class="fas fa-arrow-right me-1"></i> العودة لمحطة التمريض
                    </a>
                @endif
                <a href="{{ route('stock-transfers.create') }}" class="btn btn-warning btn-sm rounded-pill px-3 py-1.5 fw-bold shadow-xs text-dark">
                    <i class="fas fa-plus me-1"></i> + طلب تعزيز جديد
                </a>
            </div>
        </div>

        <div class="card-body p-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 90px;">رقم الطلب</th>
                            <th>المصدر (من)</th>
                            <th>المستلم (إلى)</th>
                            <th class="text-center" style="width: 120px;">عدد المواد</th>
                            <th>مقدم الطلب</th>
                            <th>تاريخ وتوقيت الطلب</th>
                            <th class="text-center" style="width: 180px;">حالة الطلب</th>
                            <th class="text-center pe-3" style="width: 110px;">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                            @php
                                $itemsCount = is_array($req->items) ? count($req->items) : (json_decode($req->items, true) ? count(json_decode($req->items, true)) : 0);
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border font-monospace px-2.5 py-1.5">#{{ $req->id }}</span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark"><i class="fas fa-warehouse text-primary me-1"></i> {{ $req->fromLocation?->name ?? 'غير محدد' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2.5 py-1 fw-bold">
                                        <i class="fas fa-map-marker-alt me-1"></i> {{ $req->toLocation?->name ?? 'غير محدد' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1">
                                        {{ $itemsCount }} {{ $itemsCount === 1 ? 'مادة' : 'مواد' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1.5">
                                        <i class="fas fa-user-circle text-muted"></i>
                                        <span class="small fw-medium">{{ $req->requestedBy?->name ?? 'غير معروف' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="small text-muted">
                                        <i class="far fa-clock me-1"></i> {{ $req->created_at ? $req->created_at->format('Y-m-d H:i') : '-' }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($req->status === 'pending')
                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-1.5 fw-bold shadow-xs">
                                            <i class="fas fa-hourglass-half me-1"></i> قيد المراجعة
                                        </span>
                                    @elseif($req->status === 'approved')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold">
                                            <i class="fas fa-check-circle me-1"></i> معتمد وتم الصرف
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1.5 fw-bold">
                                            <i class="fas fa-times-circle me-1"></i> مرفوض
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center pe-3">
                                    <div class="d-flex align-items-center justify-content-center gap-1.5 flex-nowrap">
                                        <a href="{{ route('stock-transfers.requests.show', $req) }}" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 fw-medium" title="عرض التفاصيل">
                                            <i class="fas fa-eye me-1"></i> عرض
                                        </a>
                                        @if($req->status === 'pending' && (!empty($isAdmin) || $req->requested_by == auth()->id() || (auth()->user()?->location_id && $req->to_location_id == auth()->user()->location_id)))
                                            <a href="{{ route('stock-transfers.requests.edit', $req) }}" class="btn btn-sm btn-outline-warning rounded-pill px-2.5 py-1 text-dark fw-medium" title="تعديل الطلبية">
                                                <i class="fas fa-edit me-1"></i> تعديل
                                            </a>
                                            <form action="{{ route('stock-transfers.requests.destroy', $req) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إلغاء وحذف هذه الطلبية نهائياً؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1 fw-medium" title="إلغاء وحذف الطلبية">
                                                    <i class="fas fa-trash-alt me-1"></i> إلغاء
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-clipboard-check fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-2 fw-semibold">لا توجد طلبات مخزون مسجلة لعهدة هذا الطابق حالياً</p>
                                    <a href="{{ route('stock-transfers.create') }}" class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 fw-bold">
                                        <i class="fas fa-plus me-1"></i> إنشاء أول طلبية تعزيز
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())
                <div class="mt-3 pt-3 border-top d-flex justify-content-center">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
