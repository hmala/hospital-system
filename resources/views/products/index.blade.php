@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px;">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                        <div class="text-white mb-3 mb-md-0">
                            <h2 class="mb-2 fw-bold">
                                <i class="fas fa-boxes me-3"></i>إدارة المنتجات
                            </h2>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-info-circle me-2"></i>عرض وإدارة جميع مواد المخزن بكفاءة عالية
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('products.print-all') }}" target="_blank" class="btn btn-light btn-lg px-4 shadow-sm">
                                <i class="fas fa-print me-2"></i>طباعة الباركودات
                            </a>
                            <a href="{{ route('products.create') }}" class="btn btn-warning btn-lg px-4 shadow-sm">
                                <i class="fas fa-plus-circle me-2"></i>إضافة منتج جديد
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                            <i class="fas fa-box text-primary fa-2x"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold">{{ $products->total() }}</h4>
                            <p class="text-muted mb-0">إجمالي المنتجات</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                            <i class="fas fa-check-circle text-success fa-2x"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold">{{ $products->where('is_perishable', 0)->count() }}</h4>
                            <p class="text-muted mb-0">منتجات غير قابلة للتلف</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                            <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold">{{ $products->where('is_perishable', 1)->count() }}</h4>
                            <p class="text-muted mb-0">منتجات قابلة للتلف</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="card-header bg-white border-0 py-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-list me-2"></i>قائمة المنتجات
                        </h4>
                        <div class="d-flex gap-2">
                            <input type="text" id="searchInput" class="form-control form-control-lg" placeholder="البحث في المنتجات..." style="border-radius: 25px; border: 2px solid #e9ecef;">
                        </div>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success border-0 mx-4 mt-3" style="border-radius: 15px;">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    </div>
                @endif

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="productsTable">
                            <thead class="table-light">
                                <tr style="border-bottom: 2px solid #e9ecef;">
                                    <th class="py-4 px-4 fw-bold text-muted">
                                        <i class="fas fa-tag me-2"></i>الاسم
                                    </th>
                                    <th class="py-4 px-4 fw-bold text-muted">
                                        <i class="fas fa-folder me-2"></i>التصنيف
                                    </th>
                                    <th class="py-4 px-4 fw-bold text-muted">
                                        <i class="fas fa-balance-scale me-2"></i>الوحدة
                                    </th>
                                    <th class="py-4 px-4 fw-bold text-muted text-center">
                                        <i class="fas fa-barcode me-2"></i>الباركود
                                    </th>
                                    <th class="py-4 px-4 fw-bold text-muted">
                                        <i class="fas fa-clock me-2"></i>قابل للتلف
                                    </th>
                                    <th class="py-4 px-4 fw-bold text-muted">
                                        <i class="fas fa-chart-line me-2"></i>تنبيه الكمية
                                    </th>
                                    <th class="py-4 px-4 fw-bold text-muted text-center" style="width: 220px;">
                                        <i class="fas fa-cogs me-2"></i>الإجراءات
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                <tr class="product-row" style="border-bottom: 1px solid #f8f9fa; transition: all 0.2s;">
                                    <td class="py-3 px-4">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                                <i class="fas fa-box text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold">{{ $product->name }}</h6>
                                                <small class="text-muted">كود: {{ $product->code ?? $product->id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="badge bg-light text-dark px-3 py-2" style="border-radius: 20px; font-weight: 500;">
                                            {{ $product->category }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 fw-semibold">{{ $product->unit }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="d-inline-block position-relative barcode-container" style="min-width: 120px;">
                                            <svg class="product-barcode" data-code="{{ $product->code ?? $product->id }}" style="cursor: pointer;" onclick="window.open('{{ route('products.barcode', $product) }}', '_blank')" title="انقر لطباعة الباركود"></svg>
                                            <div class="small text-muted mt-1 fw-bold">{{ $product->code ?? $product->id }}</div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($product->is_perishable)
                                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2" style="border-radius: 20px;">
                                                <i class="fas fa-exclamation-triangle me-1"></i>نعم
                                            </span>
                                        @else
                                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2" style="border-radius: 20px;">
                                                <i class="fas fa-check-circle me-1"></i>لا
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="fw-bold text-primary">{{ $product->alert_quantity }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('products.barcode', $product) }}" target="_blank" class="btn btn-outline-success btn-sm me-1" title="طباعة الباركود" style="border-radius: 10px;">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-primary btn-sm me-1" title="تعديل" style="border-radius: 10px;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف المادة؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="حذف" style="border-radius: 10px;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white border-0 py-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            عرض {{ $products->firstItem() }} - {{ $products->lastItem() }} من أصل {{ $products->total() }} منتج
                        </div>
                        <div>
                            {{ $products->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Generate barcodes
        document.querySelectorAll('.product-barcode').forEach(function(svg) {
            const code = svg.dataset.code;
            if (!code) {
                return;
            }

            JsBarcode(svg, code, {
                format: 'CODE128',
                width: 1.5,
                height: 35,
                displayValue: false,
                margin: 0,
            });
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const tableRows = document.querySelectorAll('.product-row');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                    row.style.transform = 'scale(1)';
                    row.style.opacity = '1';
                } else {
                    row.style.display = 'none';
                    row.style.transform = 'scale(0.95)';
                    row.style.opacity = '0.5';
                }
            });
        });

        // Row hover effects
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
                this.style.transition = 'all 0.3s ease';
            });

            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });

        // Button hover effects
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-1px)';
                this.style.transition = 'all 0.2s ease';
            });

            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    });
</script>

<style>
    .barcode-container:hover {
        background-color: #f8f9fa;
        border-radius: 8px;
        transition: background-color 0.2s;
        padding: 5px;
    }

    .product-barcode:hover {
        opacity: 0.8;
        transform: scale(1.05);
        transition: all 0.2s ease;
    }

    .card {
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
    }

    .badge {
        transition: all 0.2s ease;
    }

    .badge:hover {
        transform: scale(1.05);
    }

    .btn-group .btn {
        transition: all 0.2s ease;
    }

    .btn-group .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        transform: scale(1.02);
        transition: all 0.2s ease;
    }

    .pagination .page-link {
        border-radius: 10px !important;
        margin: 0 2px;
        border: none;
        color: #667eea;
        transition: all 0.2s ease;
    }

    .pagination .page-link:hover {
        background-color: #667eea;
        color: white;
        transform: scale(1.1);
    }

    .pagination .page-item.active .page-link {
        background-color: #667eea;
        border-color: #667eea;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }

    .alert {
        animation: slideIn 0.5s ease-out;
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

    .rounded-circle {
        transition: all 0.3s ease;
    }

    .rounded-circle:hover {
        transform: rotate(360deg);
    }

    /* Custom scrollbar */
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: linear-gradient(45deg, #667eea, #764ba2);
        border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(45deg, #5a6fd8, #6a4190);
    }
</style>
@endsection