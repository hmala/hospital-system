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
                                <i class="fas fa-exchange-alt me-3"></i>نقل المخزون
                            </h2>
                            <p class="mb-0 opacity-75">تحويل المواد بين المخازن باستخدام مبدأ الطابور FIFO</p>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('inventory.index') }}" class="btn btn-light px-4 py-2 rounded-pill">
                                <i class="fas fa-arrow-left me-2"></i>رجوع للمخزون
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                <div class="card-header bg-light border-0" style="border-radius: 15px 15px 0 0;">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary rounded-circle p-2 me-3">
                            <i class="fas fa-exchange-alt text-white"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">تفاصيل النقل</h5>
                            <small class="text-muted">حدد المخازن والمادة والكمية المطلوب نقلها</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success border-0 shadow-sm" style="border-radius: 10px;">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger border-0 shadow-sm" style="border-radius: 10px;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('stock-transfers.store') }}" id="transferForm">
                        @csrf

                        <!-- Transfer Path Visualization -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-0 bg-light" style="border-radius: 15px;">
                                    <div class="card-body p-4 text-center">
                                        <div class="d-flex justify-content-center align-items-center flex-row-reverse">
                                            <div class="transfer-step">
                                                <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-warehouse text-white fs-4"></i>
                                                </div>
                                                <h6 class="text-primary fw-bold">من مخزن</h6>
                                                <small class="text-muted">المخزن المرسل</small>
                                            </div>
                                            <div class="transfer-arrow mx-4">
                                                <i class="fas fa-arrow-left text-primary fs-2"></i>
                                            </div>
                                            <div class="transfer-step">
                                                <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-boxes text-white fs-4"></i>
                                                </div>
                                                <h6 class="text-success fw-bold">المادة</h6>
                                                <small class="text-muted">المواد المراد نقلها</small>
                                            </div>
                                            <div class="transfer-arrow mx-4">
                                                <i class="fas fa-arrow-left text-success fs-2"></i>
                                            </div>
                                            <div class="transfer-step">
                                                <div class="bg-info rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-hospital text-white fs-4"></i>
                                                </div>
                                                <h6 class="text-info fw-bold">إلى مخزن</h6>
                                                <small class="text-muted">المخزن المستلم</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Fields -->
                        <div class="row g-4">
                            <!-- From Location -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                                    <div class="card-header bg-primary text-white border-0" style="border-radius: 15px 15px 0 0;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-warehouse me-2"></i>
                                            <span class="fw-bold">من مخزن</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-floating">
                                            <select name="from_location_id" id="fromLocationSelect" class="form-select" required>
                                                <option value="">اختر المخزن المرسل</option>
                                                @foreach($locations as $location)
                                                    <option value="{{ $location->id }}" {{ old('from_location_id') == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name }} ({{ $location->type === 'main' ? 'رئيسي' : 'قسم' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <label for="fromLocationSelect">المخزن المرسل <span class="text-danger">*</span></label>
                                            @error('from_location_id') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- To Location -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                                    <div class="card-header bg-info text-white border-0" style="border-radius: 15px 15px 0 0;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-hospital me-2"></i>
                                            <span class="fw-bold">إلى مخزن</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-floating">
                                            <select name="to_location_id" id="toLocationSelect" class="form-select" required>
                                                <option value="">اختر المخزن المستلم</option>
                                                @foreach($locations as $location)
                                                    <option value="{{ $location->id }}" {{ old('to_location_id') == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name }} ({{ $location->type === 'main' ? 'رئيسي' : 'قسم' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <label for="toLocationSelect">المخزن المستلم <span class="text-danger">*</span></label>
                                            @error('to_location_id') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Selection -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                                    <div class="card-header bg-success text-white border-0" style="border-radius: 15px 15px 0 0;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-box me-2"></i>
                                            <span class="fw-bold">المادة</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-floating">
                                            <select name="product_id" id="productSelect" class="form-select" required>
                                                <option value="">اختر المادة</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                                        {{ $product->name }} ({{ $product->unit }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <label for="productSelect">المادة المراد نقلها <span class="text-danger">*</span></label>
                                            @error('product_id') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quantity -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                                    <div class="card-header bg-warning text-dark border-0" style="border-radius: 15px 15px 0 0;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-hashtag me-2"></i>
                                            <span class="fw-bold">الكمية</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-floating">
                                            <input type="number" name="qty" id="quantityInput" class="form-control" min="1" value="{{ old('qty', 1) }}" required>
                                            <label for="quantityInput">الكمية المطلوب نقلها <span class="text-danger">*</span></label>
                                            @error('qty') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            سيتم تطبيق مبدأ الطابور FIFO (الأقدم أولاً)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card border-0 shadow-lg mt-4" style="border-radius: 15px;">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                                        <i class="fas fa-arrow-left me-2"></i>رجوع
                                    </a>
                                    <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill fw-bold">
                                        <i class="fas fa-exchange-alt me-2"></i>نفّذ النقل
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('transferForm');

        // Form validation enhancement
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    field.style.boxShadow = '0 0 0 0.2rem rgba(220, 53, 69, 0.25)';
                    isValid = false;
                } else {
                    field.style.borderColor = '#28a745';
                    field.style.boxShadow = '0 0 0 0.2rem rgba(40, 167, 69, 0.25)';
                }
            });

            // Check if from and to locations are different
            const fromLocation = document.getElementById('fromLocationSelect');
            const toLocation = document.getElementById('toLocationSelect');
            if (fromLocation.value && toLocation.value && fromLocation.value === toLocation.value) {
                e.preventDefault();
                alert('لا يمكن نقل المخزون من مخزن إلى نفسه!');
                fromLocation.style.borderColor = '#dc3545';
                toLocation.style.borderColor = '#dc3545';
                return;
            }

            if (!isValid) {
                e.preventDefault();
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger mt-3';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>يرجى ملء جميع الحقول المطلوبة';
                form.insertBefore(errorDiv, form.firstChild);

                setTimeout(() => {
                    errorDiv.remove();
                }, 5000);
            }
        });

        // Enhanced input effects
        const formControls = document.querySelectorAll('.form-control, .form-select');
        formControls.forEach(control => {
            control.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
                this.style.transition = 'all 0.2s ease';
            });

            control.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Transfer visualization animation
        const transferSteps = document.querySelectorAll('.transfer-step');
        transferSteps.forEach((step, index) => {
            step.style.animation = `fadeInUp 0.8s ease-out ${index * 0.2}s both`;
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

    .form-floating > .form-control,
    .form-floating > .form-select {
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .form-floating > .form-control:focus,
    .form-floating > .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        transform: translateY(-2px);
    }

    .form-floating > label {
        color: #6c757d;
        transition: all 0.2s ease;
    }

    .form-floating > .form-control:focus ~ label,
    .form-floating > .form-select:focus ~ label {
        color: #667eea;
        transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
    }

    .btn {
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .progress {
        background-color: #e9ecef;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
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

    .form-text {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.5rem;
    }

    .text-danger {
        animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .transfer-step {
        text-align: center;
        min-width: 120px;
    }

    .transfer-arrow {
        animation: bounce 2s infinite;
    }

    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
        60% { transform: translateY(-5px); }
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