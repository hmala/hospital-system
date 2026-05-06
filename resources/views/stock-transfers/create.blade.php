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
                                <i class="fas fa-truck-loading me-3"></i>طلب مخزون فرعي
                            </h2>
                            <p class="mb-0 opacity-75">اطلب مواد من المخزن الرئيسي إلى المخزن الفرعي مع الحفاظ على FIFO.</p>
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

                    <form method="POST" action="{{ route('stock-transfers.requests.store') }}" id="transferForm">
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
                                                <h6 class="text-primary fw-bold">من المخزن الرئيسي</h6>
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
                                                <h6 class="text-info fw-bold">إلى المخزن الفرعي</h6>
                                                <small class="text-muted">المخزن المستلم</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Fields -->
                        <div class="row g-4">
                            <!-- From Location (automatic main warehouse) -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                                    <div class="card-header bg-primary text-white border-0" style="border-radius: 15px 15px 0 0;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-warehouse me-2"></i>
                                            <span class="fw-bold">من المخزن الرئيسي</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @if($mainLocations->isNotEmpty())
                                            <input type="hidden" name="from_location_id" value="{{ $mainLocations->first()->id }}">
                                            <div class="mb-2 text-muted">ستُرسل المواد تلقائياً من:</div>
                                            <div class="badge bg-white text-dark px-3 py-2 rounded-pill shadow-sm">
                                                {{ $mainLocations->first()->name }}
                                            </div>
                                        @else
                                            <div class="alert alert-warning mb-0">لم يتم العثور على مخزن رئيسي. الرجاء إنشاء مخزن رئيسي أولاً.</div>
                                        @endif
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
                                                <option value="">اختر المخزن الفرعي</option>
                                                @foreach($subLocations as $location)
                                                    <option value="{{ $location->id }}" {{ old('to_location_id') == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <label for="toLocationSelect">المخزن الفرعي <span class="text-danger">*</span></label>
                                            @error('to_location_id') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Items List -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                                    <div class="card-header bg-success text-white border-0" style="border-radius: 15px 15px 0 0;">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-box me-2"></i>
                                                <span class="fw-bold">المواد المطلوبة</span>
                                            </div>
                                            <button type="button" class="btn btn-light btn-sm" id="addItemRow">
                                                <i class="fas fa-plus me-1"></i>إضافة مادة
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle mb-0" id="itemsTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>المادة</th>
                                                        <th>الكمية</th>
                                                        <th class="text-center">حذف</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                </tbody>
                                            </table>
                                        </div>
                                        @error('items') <div class="text-danger mt-2 small">{{ $message }}</div> @enderror
                                        @error('items.*.product_id') <div class="text-danger mt-2 small">{{ $message }}</div> @enderror
                                        @error('items.*.qty') <div class="text-danger mt-2 small">{{ $message }}</div> @enderror
                                        <div class="form-text mt-3">
                                            <i class="fas fa-info-circle me-1"></i>
                                            أضف كل مادة في صف منفصل وسيتم طلبها من المخزن الرئيسي إلى المخزن الفرعي.
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
                                        <i class="fas fa-paper-plane me-2"></i>أرسل الطلب
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
        const addItemRowBtn = document.getElementById('addItemRow');
        const itemsTableBody = document.querySelector('#itemsTable tbody');
        let itemIndex = 0;

        function createItemRow(index) {
            return `
                <tr id="row${index}">
                    <td>
                        <select name="items[${index}][product_id]" class="form-select" required>
                            <option value="">اختر المادة</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->unit }})</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" name="items[${index}][qty]" class="form-control" min="1" value="1" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-item-row">حذف</button>
                    </td>
                </tr>
            `;
        }

        addItemRowBtn.addEventListener('click', function () {
            itemsTableBody.insertAdjacentHTML('beforeend', createItemRow(itemIndex));
            itemIndex++;
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-item-row')) {
                const row = e.target.closest('tr');
                if (row) {
                    row.remove();
                }
            }
        });

        // Add one initial row automatically
        if (itemsTableBody.children.length === 0) {
            itemsTableBody.insertAdjacentHTML('beforeend', createItemRow(itemIndex));
            itemIndex++;
        }

        // Form validation enhancement
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            let hasRow = itemsTableBody.querySelectorAll('tr').length > 0;

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

            if (!hasRow) {
                isValid = false;
                alert('يجب إضافة مادة واحدة على الأقل.');
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