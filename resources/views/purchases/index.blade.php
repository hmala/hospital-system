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
                                <i class="fas fa-shopping-cart me-3"></i>إدارة المشتريات
                            </h2>
                            <p class="mb-0 opacity-75">عرض وإدارة فواتير المشتريات والتوريد</p>
                        </div>
                        <div class="text-end">
                            <div class="badge bg-white text-primary fs-6 px-3 py-2 rounded-pill">
                                <i class="fas fa-file-invoice me-1"></i>{{ $purchases->count() }} فاتورة
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
                        <i class="fas fa-file-invoice text-white fs-4"></i>
                    </div>
                    <h3 class="mt-3 mb-1 fw-bold text-primary">{{ $purchases->count() }}</h3>
                    <p class="text-muted mb-0">إجمالي الفواتير</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-lg h-100" style="border-radius: 15px;">
                <div class="card-body p-4 text-center">
                    <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-calculator text-white fs-4"></i>
                    </div>
                    <h3 class="mt-3 mb-1 fw-bold text-success">{{ number_format($purchases->sum('total_amount'), 2) }}</h3>
                    <p class="text-muted mb-0">إجمالي المشتريات</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-lg h-100" style="border-radius: 15px;">
                <div class="card-body p-4 text-center">
                    <div class="bg-info rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-truck text-white fs-4"></i>
                    </div>
                    <h3 class="mt-3 mb-1 fw-bold text-info">{{ $purchases->whereNotNull('received_at')->count() }}</h3>
                    <p class="text-muted mb-0">تم التوريد</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-lg h-100" style="border-radius: 15px;">
                <div class="card-body p-4 text-center">
                    <div class="bg-warning rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-clock text-white fs-4"></i>
                    </div>
                    <h3 class="mt-3 mb-1 fw-bold text-warning">{{ $purchases->whereNull('received_at')->count() }}</h3>
                    <p class="text-muted mb-0">في الانتظار</p>
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
                                <h5 class="mb-0 fw-bold">قائمة المشتريات</h5>
                                <small class="text-muted">جميع فواتير المشتريات المسجلة</small>
                            </div>
                        </div>
                        <a href="{{ route('purchases.create') }}" class="btn btn-primary px-4 py-2 rounded-pill fw-bold">
                            <i class="fas fa-plus me-2"></i>فاتورة جديدة
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success border-0 shadow-sm" style="border-radius: 10px;">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    <!-- Search and Filter Bar -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" id="purchaseSearch" class="form-control border-0 bg-light" placeholder="البحث في الفواتير..." style="border-radius: 0 10px 10px 0;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-2 justify-content-end">
                                <select id="statusFilter" class="form-select" style="width: auto;">
                                    <option value="">جميع الحالات</option>
                                    <option value="received">تم التوريد</option>
                                    <option value="pending">في الانتظار</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="purchasesTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 fw-bold">
                                        <i class="fas fa-hashtag me-2 text-primary"></i>رقم الفاتورة
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="fas fa-building me-2 text-primary"></i>المورد
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="fas fa-user me-2 text-primary"></i>المسؤول
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="fas fa-calendar me-2 text-primary"></i>التاريخ
                                    </th>
                                    <th class="border-0 fw-bold text-end">
                                        <i class="fas fa-dollar-sign me-2 text-primary"></i>الإجمالي
                                    </th>
                                    <th class="border-0 fw-bold text-center">
                                        <i class="fas fa-cogs me-2 text-primary"></i>الإجراءات
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchases as $purchase)
                                    <tr class="purchase-row" style="transition: all 0.2s ease;">
                                        <td>
                                            <span class="badge bg-light text-dark px-3 py-2">{{ $purchase->invoice_number }}</span>
                                        </td>
                                        <td class="fw-semibold">{{ $purchase->supplier->name ?? '-' }}</td>
                                        <td>{{ $purchase->user->name ?? '-' }}</td>
                                        <td>
                                            @if($purchase->received_at)
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    <span>{{ $purchase->received_at->format('Y-m-d H:i') }}</span>
                                                </div>
                                            @else
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-clock text-warning me-2"></i>
                                                    <span class="text-muted">في الانتظار</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold text-primary">{{ number_format($purchase->total_amount, 2) }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                <i class="fas fa-eye me-1"></i>عرض
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                                <i class="fas fa-shopping-cart text-muted fs-1"></i>
                                            </div>
                                            <h5 class="text-muted">لا توجد مشتريات مسجلة</h5>
                                            <p class="text-muted">ابدأ بإضافة فاتورة مشتريات جديدة</p>
                                            <a href="{{ route('purchases.create') }}" class="btn btn-primary px-4 py-2 rounded-pill">
                                                <i class="fas fa-plus me-2"></i>إضافة فاتورة جديدة
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($purchases->hasPages())
                        <div class="mt-4 d-flex justify-content-center">
                            {{ $purchases->links() }}
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
        const searchInput = document.getElementById('purchaseSearch');
        const statusFilter = document.getElementById('statusFilter');
        const tableRows = document.querySelectorAll('.purchase-row');

        function filterPurchases() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const statusValue = statusFilter ? statusFilter.value : '';

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const hasReceived = row.querySelector('.fa-check-circle') !== null;
                const statusMatch = !statusValue ||
                    (statusValue === 'received' && hasReceived) ||
                    (statusValue === 'pending' && !hasReceived);

                const shouldShow = text.includes(searchTerm) && statusMatch;
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
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterPurchases);
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', filterPurchases);
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
        const statsCards = document.querySelectorAll('.card-body .bg-primary, .card-body .bg-success, .card-body .bg-info, .card-body .bg-warning');
        statsCards.forEach((card, index) => {
            card.style.animation = `fadeInUp 0.6s ease-out ${index * 0.1}s both`;
        });

        // Alert auto-hide
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'all 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });

        // Format currency display
        const currencyElements = document.querySelectorAll('.text-primary');
        currencyElements.forEach(element => {
            if (element.textContent.includes('.')) {
                // This is likely a currency value
                element.innerHTML = element.textContent.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
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

    .alert {
        animation: slideIn 0.5s ease-out;
        border-radius: 15px;
        border: none;
    }

    @keyframes slideIn {
        from {
            transform: translateY(-20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .badge {
        transition: all 0.2s ease;
    }

    .badge:hover {
        transform: scale(1.05);
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