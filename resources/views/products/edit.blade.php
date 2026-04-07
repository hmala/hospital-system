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
                                <i class="fas fa-edit me-3"></i>تعديل مادة
                            </h2>
                            <p class="mb-0 opacity-75">قم بتحديث معلومات المادة الحالية أو تعديل إعدادات المخزون</p>
                        </div>
                        <div class="text-end">
                            <div class="badge bg-white text-primary fs-6 px-3 py-2 rounded-pill">
                                <i class="fas fa-barcode me-1"></i>{{ $product->code }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Indicator -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">تقدم ملء النموذج</span>
                        <span class="text-muted small" id="progressText">0%</span>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 10px;">
                        <div class="progress-bar" id="formProgress" role="progressbar" style="width: 0%; border-radius: 10px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Section -->
    <div class="row">
        <div class="col-12">
            <form method="POST" action="{{ route('products.update', $product) }}" id="productForm">
                @csrf
                @method('PUT')

                <!-- Basic Information Card -->
                <div class="card border-0 shadow-lg mb-4" style="border-radius: 15px;">
                    <div class="card-header bg-light border-0" style="border-radius: 15px 15px 0 0;">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary rounded-circle p-2 me-3">
                                <i class="fas fa-info-circle text-white"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">المعلومات الأساسية</h5>
                                <small class="text-muted">بيانات المادة الرئيسية</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="name" class="form-control" id="nameInput" placeholder="اسم المادة" value="{{ old('name', $product->name) }}" required>
                                    <label for="nameInput">اسم المادة <span class="text-danger">*</span></label>
                                    @error('name') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    @php
                                        $currentCategory = old('category', $product->category);
                                    @endphp
                                    <select name="category" id="categorySelect" class="form-select" required>
                                        <option value="">اختر التصنيف</option>
                                        @foreach($categories as $categoryOption)
                                            <option value="{{ $categoryOption }}" {{ $currentCategory == $categoryOption ? 'selected' : '' }}>{{ $categoryOption }}</option>
                                        @endforeach
                                        @if($currentCategory && !$categories->contains($currentCategory) && $currentCategory !== '__other__')
                                            <option value="{{ $currentCategory }}" selected>{{ $currentCategory }}</option>
                                        @endif
                                        <option value="__other__" {{ $currentCategory == '__other__' ? 'selected' : '' }}>تصنيف جديد</option>
                                    </select>
                                    <label for="categorySelect">التصنيف <span class="text-danger">*</span></label>
                                    @error('category') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                                <div class="mt-2" id="customCategoryField" style="display: none;">
                                    <div class="form-floating">
                                        <input type="text" name="category_custom" class="form-control" id="customCategoryInput" placeholder="أدخل تصنيف جديد" value="{{ old('category_custom') }}">
                                        <label for="customCategoryInput">تصنيف جديد</label>
                                        @error('category_custom') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select name="unit" class="form-select" id="unitSelect" required>
                                        <option value="">اختر الوحدة</option>
                                        <option value="قطعة" {{ old('unit', $product->unit)=='قطعة' ? 'selected' : '' }}>قطعة</option>
                                        <option value="علبة" {{ old('unit', $product->unit)=='علبة' ? 'selected' : '' }}>علبة</option>
                                        <option value="كرتون" {{ old('unit', $product->unit)=='كرتون' ? 'selected' : '' }}>كرتون</option>
                                        <option value="كجم" {{ old('unit', $product->unit)=='كجم' ? 'selected' : '' }}>كجم</option>
                                        <option value="لتر" {{ old('unit', $product->unit)=='لتر' ? 'selected' : '' }}>لتر</option>
                                        <option value="قطرة" {{ old('unit', $product->unit)=='قطرة' ? 'selected' : '' }}>قطرة</option>
                                    </select>
                                    <label for="unitSelect">الوحدة <span class="text-danger">*</span></label>
                                    @error('unit') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold mb-3">قابل للتلف</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input d-none" type="radio" name="is_perishable" id="perishableYes" value="1" {{ old('is_perishable', $product->is_perishable) == '1' || old('is_perishable', $product->is_perishable) === 1 ? 'checked' : '' }}>
                                        <label class="form-check-label badge px-3 py-2 fs-6" for="perishableYes" style="cursor: pointer;">
                                            <i class="fas fa-check-circle me-1"></i>نعم
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input d-none" type="radio" name="is_perishable" id="perishableNo" value="0" {{ old('is_perishable', $product->is_perishable) == '0' || old('is_perishable', $product->is_perishable) === 0 ? 'checked' : '' }}>
                                        <label class="form-check-label badge px-3 py-2 fs-6 bg-secondary" for="perishableNo" style="cursor: pointer;">
                                            <i class="fas fa-times-circle me-1"></i>لا
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" name="alert_quantity" class="form-control" id="alertQuantityInput" value="{{ old('alert_quantity', $product->alert_quantity) }}" min="0" required>
                                    <label for="alertQuantityInput">تنبيه الكمية <span class="text-danger">*</span></label>
                                    <div class="form-text">يظهر لك تحذيراً قبل أن يصل المخزون لمستوى الخطر</div>
                                    @error('alert_quantity') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information Card -->
                <div class="card border-0 shadow-lg mb-4" style="border-radius: 15px;">
                    <div class="card-header bg-light border-0">
                        <div class="d-flex align-items-center">
                            <div class="bg-success rounded-circle p-2 me-3">
                                <i class="fas fa-cog text-white"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">معلومات إضافية</h5>
                                <small class="text-muted">إعدادات المخزون والتخزين</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" name="reorder_level" class="form-control" id="reorderLevelInput" value="{{ old('reorder_level', $product->reorder_level) }}" min="0">
                                    <label for="reorderLevelInput">حد إعادة الطلب</label>
                                    <div class="form-text">الكمية التي يجب أن تبدأ عندها في التخطيط لطلب جديد</div>
                                    @error('reorder_level') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="storage_conditions" class="form-control" id="storageConditionsInput" value="{{ old('storage_conditions', $product->storage_conditions) }}" placeholder="مثال: درجة حرارة الغرفة، بعيداً عن الرطوبة">
                                    <label for="storageConditionsInput">شروط التخزين</label>
                                    @error('storage_conditions') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="description" class="form-control" id="descriptionTextarea" rows="4" placeholder="أي تفاصيل إضافية عن المادة">{{ old('description', $product->description) }}</textarea>
                                    <label for="descriptionTextarea">ملاحظات إضافية</label>
                                    @error('description') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                                <i class="fas fa-arrow-left me-2"></i>رجوع
                            </a>
                            <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill fw-bold">
                                <i class="fas fa-save me-2"></i>حفظ التعديلات
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleCustomCategory() {
        const categorySelect = document.getElementById('categorySelect');
        const customField = document.getElementById('customCategoryField');
        if (!categorySelect || !customField) return;

        customField.style.display = categorySelect.value === '__other__' ? 'block' : 'none';
    }

    function updateProgress() {
        const form = document.getElementById('productForm');
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        const progressBar = document.getElementById('formProgress');
        const progressText = document.getElementById('progressText');

        let filledInputs = 0;
        inputs.forEach(input => {
            if (input.value.trim() !== '') {
                filledInputs++;
            }
        });

        const progress = (filledInputs / inputs.length) * 100;
        progressBar.style.width = progress + '%';
        progressText.textContent = Math.round(progress) + '%';

        // Change color based on progress
        if (progress < 33) {
            progressBar.style.backgroundColor = '#dc3545';
        } else if (progress < 66) {
            progressBar.style.backgroundColor = '#ffc107';
        } else {
            progressBar.style.backgroundColor = '#28a745';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const categorySelect = document.getElementById('categorySelect');
        if (categorySelect) {
            categorySelect.addEventListener('change', toggleCustomCategory);
            toggleCustomCategory();
        }

        // Form progress tracking
        const form = document.getElementById('productForm');
        const inputs = form.querySelectorAll('input, select, textarea');

        inputs.forEach(input => {
            input.addEventListener('input', updateProgress);
            input.addEventListener('change', updateProgress);
        });

        // Initial progress update
        updateProgress();

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

        // Radio button enhancement
        const radioButtons = document.querySelectorAll('.form-check-input');
        radioButtons.forEach(radio => {
            radio.addEventListener('change', function() {
                // Remove active class from all badges in the same group
                const group = this.closest('.d-flex');
                group.querySelectorAll('.badge').forEach(badge => {
                    badge.classList.remove('border', 'border-primary', 'shadow-sm');
                    if (badge.classList.contains('bg-secondary')) {
                        badge.classList.remove('bg-secondary');
                        badge.classList.add('bg-light', 'text-dark');
                    }
                });

                // Add active class to selected badge
                const label = this.closest('.form-check-inline').querySelector('.badge');
                label.classList.add('border', 'border-primary', 'shadow-sm');
                if (label.classList.contains('bg-light')) {
                    label.classList.remove('bg-light', 'text-dark');
                    label.classList.add('bg-primary');
                }
            });
        });

        // Initialize radio button states
        radioButtons.forEach(radio => {
            if (radio.checked) {
                const label = radio.closest('.form-check-inline').querySelector('.badge');
                label.classList.add('border', 'border-primary', 'shadow-sm');
                if (label.classList.contains('bg-light')) {
                    label.classList.remove('bg-light', 'text-dark');
                    label.classList.add('bg-primary');
                }
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

    .badge {
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .badge:hover {
        transform: scale(1.05);
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