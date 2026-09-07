@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg text-white" style="background: linear-gradient(135deg, #e11d48 0%, #be123c 50%, #4c0519 100%); border-radius: 20px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row gap-3">
                        <div>
                            <h2 class="mb-1 fw-bold">
                                <i class="fas fa-calendar-times me-3"></i>تقرير ومتابعة صلاحية المواد (FEFO)
                            </h2>
                            <p class="mb-0 opacity-75">مراقبة الوجبات المخزنية المنتهية أو القريبة من الانتهاء لتصريفها وفق نظام FEFO.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('inventory.index') }}" class="btn btn-light text-danger px-3 py-2 rounded-pill fw-bold">
                                <i class="fas fa-boxes me-1"></i>المخزون العام
                            </a>
                            <a href="{{ route('inventory.low_stock') }}" class="btn btn-outline-light px-3 py-2 rounded-pill fw-bold">
                                <i class="fas fa-exclamation-triangle me-1"></i>المخزون المنخفض
                            </a>
                            <a href="{{ route('stock-transfers.create') }}" class="btn btn-warning text-dark px-3 py-2 rounded-pill fw-bold">
                                <i class="fas fa-exchange-alt me-1"></i>تحويل مخزني
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $status === 'expired' ? 'border border-2 border-danger' : '' }}" style="border-radius: 15px; background: #fff;">
                <div class="card-body p-3 text-center">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                        <i class="fas fa-ban fs-4"></i>
                    </div>
                    <h3 class="mb-0 fw-bold text-danger">{{ $expiredCount }}</h3>
                    <p class="text-muted small mb-1">وجبات منتهية الصلاحية</p>
                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill">
                        قيمة التلف: {{ number_format($expiredValue, 0) }} د.ع
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $status === 'critical_30' ? 'border border-2 border-warning' : '' }}" style="border-radius: 15px; background: #fff;">
                <div class="card-body p-3 text-center">
                    <div class="bg-warning bg-opacity-10 text-dark rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px; color: #d97706 !important;">
                        <i class="fas fa-hourglass-end fs-4" style="color: #d97706;"></i>
                    </div>
                    <h3 class="mb-0 fw-bold" style="color: #d97706;">{{ $criticalCount }}</h3>
                    <p class="text-muted small mb-1">حرجة (خلال 30 يوماً)</p>
                    <span class="badge rounded-pill" style="background: rgba(217, 119, 6, 0.1); color: #d97706;">
                        القيمة المعرضة: {{ number_format($criticalValue, 0) }} د.ع
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $status === 'warning_90' ? 'border border-2 border-info' : '' }}" style="border-radius: 15px; background: #fff;">
                <div class="card-body p-3 text-center">
                    <div class="bg-info bg-opacity-10 text-info rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                        <i class="fas fa-clock fs-4"></i>
                    </div>
                    <h3 class="mb-0 fw-bold text-info">{{ $warningCount }}</h3>
                    <p class="text-muted small mb-1">تنبيه مبكر (30 - 90 يوماً)</p>
                    <span class="badge bg-info bg-opacity-10 text-info rounded-pill">
                        القيمة: {{ number_format($warningValue, 0) }} د.ع
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $status === 'valid' ? 'border border-2 border-success' : '' }}" style="border-radius: 15px; background: #fff;">
                <div class="card-body p-3 text-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                        <i class="fas fa-shield-alt fs-4"></i>
                    </div>
                    <h3 class="mt-1 mb-0 fw-bold text-success">{{ $validCount }}</h3>
                    <p class="text-muted small mb-1">سليمة (أكثر من 90 يوماً)</p>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill">
                        وضع آمن ومستقر
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Table Section -->
    <div class="card border-0 shadow-lg" style="border-radius: 15px;">
        <div class="card-header bg-light border-0 p-3" style="border-radius: 15px 15px 0 0;">
            <!-- Status Navigation Pills -->
            <ul class="nav nav-pills gap-2 mb-3">
                <li class="nav-item">
                    <a class="nav-link rounded-pill {{ $status === 'all' ? 'active bg-primary' : 'bg-white text-dark shadow-sm' }}" 
                       href="{{ route('inventory.expiring', array_merge(request()->query(), ['status' => 'all'])) }}">
                        <i class="fas fa-layer-group me-1"></i>الكل
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill {{ $status === 'expired' ? 'active bg-danger' : 'bg-white text-danger shadow-sm' }}" 
                       href="{{ route('inventory.expiring', array_merge(request()->query(), ['status' => 'expired'])) }}">
                        <i class="fas fa-times-circle me-1"></i>منتهية الصلاحية ({{ $expiredCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill {{ $status === 'critical_30' ? 'active text-white' : 'bg-white shadow-sm' }}" 
                       style="{{ $status === 'critical_30' ? 'background: #d97706;' : 'color: #d97706;' }}"
                       href="{{ route('inventory.expiring', array_merge(request()->query(), ['status' => 'critical_30'])) }}">
                        <i class="fas fa-exclamation-circle me-1"></i>حرجة &lt; 30 يوم ({{ $criticalCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill {{ $status === 'warning_90' ? 'active bg-info text-white' : 'bg-white text-info shadow-sm' }}" 
                       href="{{ route('inventory.expiring', array_merge(request()->query(), ['status' => 'warning_90'])) }}">
                        <i class="fas fa-clock me-1"></i>تنبيه 30 - 90 يوم ({{ $warningCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill {{ $status === 'valid' ? 'active bg-success' : 'bg-white text-success shadow-sm' }}" 
                       href="{{ route('inventory.expiring', array_merge(request()->query(), ['status' => 'valid'])) }}">
                        <i class="fas fa-check-circle me-1"></i>أكثر من 90 يوم ({{ $validCount }})
                    </a>
                </li>
            </ul>

            <!-- Search and Location Filters -->
            <form method="GET" action="{{ route('inventory.expiring') }}" class="row g-2 align-items-center">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0 shadow-sm"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-0 shadow-sm" value="{{ $search }}" placeholder="ابحث باسم المادة، الباركود، أو رقم الفاتورة...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="location_id" class="form-select border-0 shadow-sm">
                        <option value="">جميع المخازن والأقسام</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>
                                {{ $loc->name }} ({{ $loc->type === 'main' ? 'رئيسي' : 'قسم فرعي' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4 rounded-pill fw-bold flex-grow-1">
                        <i class="fas fa-filter me-1"></i>تطبيق الفلتر
                    </button>
                    @if($search || $locationId || $status !== 'all')
                        <a href="{{ route('inventory.expiring') }}" class="btn btn-outline-secondary rounded-pill" title="إعادة تعيين">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>المادة</th>
                            <th>الباركود / الوجبة</th>
                            <th>المخزن الحالي</th>
                            <th>تاريخ الانتهاء</th>
                            <th class="text-center">الحالة / المتبقي</th>
                            <th class="text-center">الرصيد المتبقي</th>
                            <th>سعر التكلفة</th>
                            <th>القيمة المعرضة</th>
                            <th class="text-center" style="width: 140px;">إجراءات سريعة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $idx => $batch)
                            @php
                                $daysDiff = now()->startOfDay()->diffInDays($batch->expiry_date->startOfDay(), false);
                                $isExpired = $daysDiff < 0;
                                $isCritical = !$isExpired && $daysDiff <= 30;
                                $isWarning = !$isExpired && $daysDiff > 30 && $daysDiff <= 90;

                                $consumedQty = max(0, $batch->initial_qty - $batch->current_qty);
                                $consumedPercent = $batch->initial_qty > 0 ? round(($consumedQty / $batch->initial_qty) * 100) : 0;
                                $batchValue = $batch->current_qty * $batch->cost_price;
                            @endphp
                            <tr class="{{ $isExpired ? 'table-danger bg-opacity-25' : ($isCritical ? 'table-warning bg-opacity-25' : '') }}">
                                <td class="text-center fw-bold">{{ $batches->firstItem() + $idx }}</td>
                                <td>
                                    <div class="fw-bold">{{ $batch->product->name ?? 'مادة غير معروفة' }}</div>
                                    <small class="text-muted">
                                        {{ $batch->product->category ?? '-' }} • كود: <code>{{ $batch->product->code ?? '-' }}</code>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary font-monospace px-2 py-1">
                                        {{ $batch->internal_barcode }}
                                    </span>
                                    @if($batch->purchaseItem?->purchase)
                                        <div class="small mt-1">
                                            <a href="{{ route('purchases.show', $batch->purchaseItem->purchase) }}" class="text-decoration-none text-muted">
                                                <i class="fas fa-file-invoice me-1"></i>فاتورة: {{ $batch->purchaseItem->purchase->invoice_number }}
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $batch->location?->type === 'main' ? 'bg-primary' : 'bg-info' }} rounded-pill">
                                        <i class="fas fa-map-marker-alt me-1"></i>{{ $batch->location->name ?? 'غير محدد' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $batch->expiry_date->format('Y-m-d') }}</div>
                                    <small class="text-muted">وردت: {{ $batch->received_at?->format('Y-m-d') ?? '-' }}</small>
                                </td>
                                <td class="text-center">
                                    @if($isExpired)
                                        <span class="badge bg-danger rounded-pill px-3 py-2 animate-pulse">
                                            <i class="fas fa-ban me-1"></i>منتهية (منذ {{ abs($daysDiff) }} يوم)
                                        </span>
                                    @elseif($isCritical)
                                        <span class="badge text-white rounded-pill px-3 py-2" style="background: #d97706;">
                                            <i class="fas fa-hourglass-half me-1"></i>متبقي {{ $daysDiff }} يوم
                                        </span>
                                    @elseif($isWarning)
                                        <span class="badge bg-info text-white rounded-pill px-3 py-2">
                                            <i class="fas fa-clock me-1"></i>متبقي {{ $daysDiff }} يوم
                                        </span>
                                    @else
                                        <span class="badge bg-success rounded-pill px-3 py-2">
                                            <i class="fas fa-check me-1"></i>متبقي {{ $daysDiff }} يوم
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="fw-bold fs-6 text-primary">{{ $batch->current_qty }} {{ $batch->product->unit ?? 'قطعة' }}</div>
                                    @if($batch->initial_qty > $batch->current_qty)
                                        <div class="progress mt-1" style="height: 5px;" title="تم صرف {{ $consumedPercent }}%">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $consumedPercent }}%;"></div>
                                        </div>
                                        <small class="text-muted" style="font-size: 11px;">صُرف {{ $consumedQty }} من {{ $batch->initial_qty }}</small>
                                    @endif
                                </td>
                                <td>{{ number_format($batch->cost_price, 2) }} د.ع</td>
                                <td class="fw-bold {{ $isExpired ? 'text-danger' : ($isCritical ? 'text-warning' : '') }}">
                                    {{ number_format($batchValue, 2) }} د.ع
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('barcodes.show', $batch) }}" target="_blank" class="btn btn-outline-secondary" title="طباعة الباركود">
                                            <i class="fas fa-barcode"></i>
                                        </a>
                                        <a href="{{ route('stock-transfers.create', ['product_id' => $batch->product_id, 'from_location_id' => $batch->location_id]) }}" class="btn btn-outline-primary" title="تحويل / صرف فوري">
                                            <i class="fas fa-exchange-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-check-circle text-success fs-1 mb-3 d-block"></i>
                                        <h5>لا توجد وجبات تطابق معايير الفلتر المحددة</h5>
                                        <p class="mb-0">جميع المواد في وضع آمن وسليم حسب البحث المختار.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($batches->hasPages())
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $batches->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .animate-pulse {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    .table-hover tbody tr:hover {
        background-color: rgba(99, 102, 241, 0.05);
    }
</style>
@endsection
