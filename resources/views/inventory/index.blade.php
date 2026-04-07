@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px;">
                <div class="card-body p-4 text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1 fw-bold">
                                <i class="fas fa-warehouse me-3"></i>إدارة المخزون
                            </h2>
                            <p class="mb-0 opacity-75">مراقبة رصيد المواد والمخازن في النظام</p>
                        </div>
                        <div class="text-end">
                            <div class="badge bg-white text-primary fs-6 px-3 py-2 rounded-pill">
                                <i class="fas fa-boxes me-1"></i>{{ $products->count() }} مادة
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-lg h-100" style="border-radius: 15px;">
                <div class="card-body p-4 text-center">
                    <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-boxes text-white fs-4"></i>
                    </div>
                    <h3 class="mt-3 mb-1 fw-bold text-primary">{{ $products->count() }}</h3>
                    <p class="text-muted mb-0">إجمالي المواد</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-lg h-100" style="border-radius: 15px;">
                <div class="card-body p-4 text-center">
                    <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-check-circle text-white fs-4"></i>
                    </div>
                    <h3 class="mt-3 mb-1 fw-bold text-success">{{ $products->where('stockBatches')->count() }}</h3>
                    <p class="text-muted mb-0">مواد متوفرة</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-lg h-100" style="border-radius: 15px;">
                <div class="card-body p-4 text-center">
                    <div class="bg-warning rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-exclamation-triangle text-white fs-4"></i>
                    </div>
                    <h3 class="mt-3 mb-1 fw-bold text-warning">{{ $products->filter(function($product) use ($locationId) { return $product->getAlertQuantityForLocation($locationId) > 0 && $product->stockBatches->sum('current_qty') <= $product->getAlertQuantityForLocation($locationId); })->count() }}</h3>
                    <p class="text-muted mb-0">تحت التنبيه</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-lg h-100" style="border-radius: 15px;">
                <div class="card-body p-4 text-center">
                    <div class="bg-danger rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-times-circle text-white fs-4"></i>
                    </div>
                    <h3 class="mt-3 mb-1 fw-bold text-danger">{{ $products->filter(function($product) use ($locationId) { return $product->getReorderLevelForLocation($locationId) > 0 && $product->stockBatches->sum('current_qty') <= $product->getReorderLevelForLocation($locationId); })->count() }}</h3>
                    <p class="text-muted mb-0">نقص حاد</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                <div class="card-header bg-light border-0" style="border-radius: 15px 15px 0 0;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary rounded-circle p-2 me-3">
                                <i class="fas fa-list text-white"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">مخزون المواد</h5>
                                <small class="text-muted">رصيد المخزون الحالي لكل مادة</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('inventory.low_stock') }}" class="btn btn-outline-warning px-4 py-2 rounded-pill">
                                <i class="fas fa-chart-line me-2"></i>تقرير المنخفض
                            </a>
                            <a href="{{ route('purchases.create') }}" class="btn btn-primary px-4 py-2 rounded-pill fw-bold">
                                <i class="fas fa-plus me-2"></i>توريد جديد
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Filter Section -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <form method="GET" action="{{ route('inventory.index') }}" class="d-flex gap-3 align-items-end">
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold">عرض حسب المخزن</label>
                                    <select name="location_id" class="form-select" style="border-radius: 10px;">
                                        <option value="">جميع المخازن</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" {{ $locationId == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-outline-primary px-4 py-2 rounded-pill">
                                    <i class="fas fa-filter me-2"></i>تصفية
                                </button>
                            </form>
                        </div>
                        <div class="col-md-4 text-md-end align-self-end">
                            @if($selectedLocation)
                                <div class="d-flex flex-column align-items-end gap-2">
                                    <span class="badge bg-secondary px-3 py-2 rounded-pill fs-6">
                                        {{ $selectedLocation->name }} • {{ $selectedLocation->type === 'main' ? 'مخزن رئيسي' : 'مخزن قسم' }}
                                    </span>
                                    @if($selectedLocation && $selectedLocation->type === 'sub')
                                        <div class="text-muted small">التنبيه معطل للأقسام الفرعية</div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Search Bar -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" id="inventorySearch" class="form-control border-0 bg-light" placeholder="البحث في المواد..." style="border-radius: 0 10px 10px 0;">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="inventoryTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 fw-bold">
                                        <i class="fas fa-box me-2 text-primary"></i>المادة
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="fas fa-tag me-2 text-primary"></i>التصنيف
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="fas fa-balance-scale me-2 text-primary"></i>الوحدة
                                    </th>
                                    <th class="border-0 fw-bold text-center">
                                        <i class="fas fa-hashtag me-2 text-primary"></i>الرصيد الحالي
                                    </th>
                                    <th class="border-0 fw-bold text-center">
                                        <i class="fas fa-layer-group me-2 text-primary"></i>عدد الدفعات
                                    </th>
                                    <th class="border-0 fw-bold text-center">
                                        <i class="fas fa-clock me-2 text-primary"></i>قابل للتلف
                                    </th>
                                    <th class="border-0 fw-bold text-center">
                                        <i class="fas fa-exclamation-circle me-2 text-primary"></i>الحالة
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                    @php
                                        $totalQty = $product->stockBatches->sum('current_qty');
                                        $batchesCount = $product->stockBatches->count();
                                        $rowClass = '';
                                        $statusBadge = '';

                                        if ($selectedLocation && $selectedLocation->type === 'sub') {
                                            $rowClass = 'table-light';
                                            $statusBadge = '<span class="badge bg-secondary rounded-pill px-3 py-2">بدون تنبيه</span>';
                                        } else {
                                            $alertQty = $product->getAlertQuantityForLocation($locationId) ?: 0;
                                            $reorderLevel = $product->getReorderLevelForLocation($locationId) ?: 0;

                                            if ($totalQty <= $reorderLevel) {
                                                $rowClass = 'table-danger';
                                                $statusBadge = '<span class="badge bg-danger rounded-pill px-3 py-2"><i class="fas fa-times-circle me-1"></i>نقص</span>';
                                            } elseif ($totalQty <= $alertQty) {
                                                $rowClass = 'table-warning';
                                                $statusBadge = '<span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="fas fa-exclamation-triangle me-1"></i>على الحافة</span>';
                                            } else {
                                                $rowClass = 'table-success';
                                                $statusBadge = '<span class="badge bg-success rounded-pill px-3 py-2"><i class="fas fa-check-circle me-1"></i>مستقر</span>';
                                            }
                                        }
                                    @endphp
                                    <tr class="inventory-row {{ $rowClass }}" style="transition: all 0.2s ease;">
                                        <td class="fw-semibold">{{ $product->name }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark px-3 py-1 rounded-pill">{{ $product->category }}</span>
                                        </td>
                                        <td>{{ $product->unit }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill">{{ $totalQty }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info px-3 py-2 rounded-pill">{{ $batchesCount }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($product->is_perishable)
                                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                                                    <i class="fas fa-clock me-1"></i>نعم
                                                </span>
                                            @else
                                                <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                                    <i class="fas fa-infinity me-1"></i>لا
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">{!! $statusBadge !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($products->isEmpty())
                        <div class="text-center py-5">
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-box-open text-muted fs-1"></i>
                            </div>
                            <h5 class="text-muted">لا توجد مواد في المخزون</h5>
                            <p class="text-muted">ابدأ بإضافة توريد جديد للمخزون</p>
                            <a href="{{ route('purchases.create') }}" class="btn btn-primary px-4 py-2 rounded-pill">
                                <i class="fas fa-plus me-2"></i>إضافة توريد
                            </a>
                        </div>
                    @else
                        <div class="mt-4 d-flex justify-content-center">
                            {{ $products->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Search functionality
        const searchInput = document.getElementById('inventorySearch');
        const tableRows = document.querySelectorAll('.inventory-row');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();

                tableRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const shouldShow = text.includes(searchTerm);
                    row.style.display = shouldShow ? '' : 'none';

                    // Add fade effect
                    if (shouldShow) {
                        row.style.opacity = '1';
                        row.style.transform = 'scale(1)';
                    } else {
                        row.style.opacity = '0.3';
                        row.style.transform = 'scale(0.98)';
                    }
                });
            });
        }

        // Table row hover effects
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
                this.style.transition = 'all 0.2s ease';
            });

            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });

        // Stats cards animation
        const statsCards = document.querySelectorAll('.card-body .bg-primary, .card-body .bg-success, .card-body .bg-warning, .card-body .bg-danger');
        statsCards.forEach((card, index) => {
            card.style.animation = `fadeInUp 0.6s ease-out ${index * 0.1}s both`;
        });

        // Badge animations
        const badges = document.querySelectorAll('.badge');
        badges.forEach(badge => {
            badge.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
                this.style.transition = 'all 0.2s ease';
            });

            badge.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Status color coding for better visibility
        const statusBadges = document.querySelectorAll('.badge');
        statusBadges.forEach(badge => {
            if (badge.classList.contains('bg-danger')) {
                badge.style.animation = 'pulse 2s infinite';
            } else if (badge.classList.contains('bg-warning')) {
                badge.style.animation = 'pulse 3s infinite';
            }
        });
    });
</script>

<style>
    .card {
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.05);
    }

    .btn {
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .input-group-text {
        border-radius: 10px 0 0 10px;
    }

    .form-control, .form-select {
        border-radius: 0 10px 10px 0;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        transform: translateY(-2px);
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .badge {
        transition: all 0.2s ease;
        cursor: default;
    }

    .badge:hover {
        transform: scale(1.05);
    }

    /* Status row highlighting */
    .table-danger {
        background-color: rgba(220, 53, 69, 0.1) !important;
        border-left: 4px solid #dc3545;
    }

    .table-warning {
        background-color: rgba(255, 193, 7, 0.1) !important;
        border-left: 4px solid #ffc107;
    }

    .table-success {
        background-color: rgba(25, 135, 84, 0.1) !important;
        border-left: 4px solid #198754;
    }

    .table-light {
        background-color: rgba(248, 249, 250, 0.5) !important;
        border-left: 4px solid #6c757d;
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: linear-gradient(45deg, #667eea, #764ba2);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(45deg, #5a6fd8, #6a4190);
    }
</style>
@endsection
