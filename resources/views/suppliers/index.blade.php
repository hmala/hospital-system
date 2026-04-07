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
                                <i class="fas fa-truck me-3"></i>إدارة الموردين
                            </h2>
                            <p class="mb-0 opacity-75">عرض وإدارة قائمة الموردين في النظام</p>
                        </div>
                        <div class="text-end">
                            <div class="badge bg-white text-primary fs-6 px-3 py-2 rounded-pill">
                                <i class="fas fa-users me-1"></i>{{ $suppliers->count() }} مورد
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-lg h-100" style="border-radius: 15px;">
                <div class="card-body p-4 text-center">
                    <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-users text-white fs-4"></i>
                    </div>
                    <h3 class="mt-3 mb-1 fw-bold text-primary">{{ $suppliers->count() }}</h3>
                    <p class="text-muted mb-0">إجمالي الموردين</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-lg h-100" style="border-radius: 15px;">
                <div class="card-body p-4 text-center">
                    <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-phone text-white fs-4"></i>
                    </div>
                    <h3 class="mt-3 mb-1 fw-bold text-success">{{ $suppliers->where('phone', '!=', null)->count() }}</h3>
                    <p class="text-muted mb-0">لديهم رقم هاتف</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-lg h-100" style="border-radius: 15px;">
                <div class="card-body p-4 text-center">
                    <div class="bg-info rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-envelope text-white fs-4"></i>
                    </div>
                    <h3 class="mt-3 mb-1 fw-bold text-info">{{ $suppliers->where('email', '!=', null)->count() }}</h3>
                    <p class="text-muted mb-0">لديهم بريد إلكتروني</p>
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
                                <h5 class="mb-0 fw-bold">قائمة الموردين</h5>
                                <small class="text-muted">جميع الموردين المسجلين في النظام</small>
                            </div>
                        </div>
                        <a href="{{ route('suppliers.create') }}" class="btn btn-primary px-4 py-2 rounded-pill fw-bold">
                            <i class="fas fa-plus me-2"></i>إضافة مورد جديد
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success border-0 shadow-sm" style="border-radius: 10px;">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    <!-- Search Bar -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" id="supplierSearch" class="form-control border-0 bg-light" placeholder="البحث في الموردين..." style="border-radius: 0 10px 10px 0;">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="suppliersTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 fw-bold">
                                        <i class="fas fa-building me-2 text-primary"></i>اسم المورد
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="fas fa-user me-2 text-primary"></i>المسؤول
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="fas fa-phone me-2 text-primary"></i>الهاتف
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="fas fa-envelope me-2 text-primary"></i>البريد الإلكتروني
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>العنوان
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($suppliers as $supplier)
                                    <tr class="supplier-row" style="transition: all 0.2s ease;">
                                        <td class="fw-semibold">{{ $supplier->name }}</td>
                                        <td>{{ $supplier->contact_person ?: '-' }}</td>
                                        <td>
                                            @if($supplier->phone)
                                                <a href="tel:{{ $supplier->phone }}" class="text-decoration-none">
                                                    <i class="fas fa-phone text-success me-1"></i>{{ $supplier->phone }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($supplier->email)
                                                <a href="mailto:{{ $supplier->email }}" class="text-decoration-none">
                                                    <i class="fas fa-envelope text-primary me-1"></i>{{ $supplier->email }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $supplier->address ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($suppliers->isEmpty())
                        <div class="text-center py-5">
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-users text-muted fs-1"></i>
                            </div>
                            <h5 class="text-muted">لا توجد موردين مسجلين</h5>
                            <p class="text-muted">ابدأ بإضافة مورد جديد للنظام</p>
                            <a href="{{ route('suppliers.create') }}" class="btn btn-primary px-4 py-2 rounded-pill">
                                <i class="fas fa-plus me-2"></i>إضافة أول مورد
                            </a>
                        </div>
                    @else
                        <div class="mt-4 d-flex justify-content-center">
                            {{ $suppliers->links() }}
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
        const searchInput = document.getElementById('supplierSearch');
        const tableRows = document.querySelectorAll('.supplier-row');

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
        const statsCards = document.querySelectorAll('.card-body .bg-primary, .card-body .bg-success, .card-body .bg-info');
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

    .form-control {
        border-radius: 0 10px 10px 0;
        transition: all 0.3s ease;
    }

    .form-control:focus {
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
