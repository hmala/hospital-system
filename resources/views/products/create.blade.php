@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-white">
                            <h2 class="mb-2 fw-bold">
                                <i class="fas fa-plus-circle me-3"></i>إضافة منتج جديد
                            </h2>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-info-circle me-2"></i>أدخل بيانات المنتج الجديد بدقة لضمان إدارة فعالة
                            </p>
                        </div>
                        <a href="{{ route('products.index') }}" class="btn btn-light btn-lg px-4 shadow-sm">
                            <i class="fas fa-arrow-right me-2"></i>العودة للقائمة
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="card-body p-5">
                    <form method="POST" action="{{ route('products.store') }}" id="productForm">
                        @csrf

                        <!-- Progress Indicator -->
                        <div class="mb-4">
                            <div class="progress" style="height: 8px; border-radius: 10px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 0%; border-radius: 10px;" id="formProgress"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <small class="text-muted">معلومات أساسية</small>
                                <small class="text-muted">إعدادات إضافية</small>
                            </div>
                        </div>

                        <div class="row gy-4">
                            <!-- Basic Information Section -->
                            <div class="col-12">
                                <div class="card border-0 bg-light" style="border-radius: 15px;">
                                    <div class="card-header bg-white border-0 py-4" style="border-radius: 15px 15px 0 0;">
                                        <h5 class="mb-0 fw-bold text-primary">
                                            <i class="fas fa-info-circle me-2"></i>المعلومات الأساسية
                                        </h5>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row g-4">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" name="name" class="form-control form-control-lg" id="name" placeholder="اسم المنتج" value="{{ old('name') }}" required style="border-radius: 12px; border: 2px solid #e9ecef;">
                                                    <label for="name" class="fw-semibold">
                                                        <i class="fas fa-tag me-2 text-primary"></i>اسم المنتج
                                                    </label>
                                                </div>
                                                @error('name')
                                                    <div class="text-danger mt-2">
                                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select name="category" id="categorySelect" class="form-select form-select-lg" style="border-radius: 12px; border: 2px solid #e9ecef;">
                                                        <option value="">اختر التصنيف</option>
                                                        @foreach($categories as $categoryOption)
                                                            <option value="{{ $categoryOption }}" {{ old('category') == $categoryOption ? 'selected' : '' }}>{{ $categoryOption }}</option>
                                                        @endforeach
                                                        <option value="__other__" {{ old('category') == '__other__' ? 'selected' : '' }}>تصنيف جديد</option>
                                                    </select>
                                                    <label for="categorySelect" class="fw-semibold">
                                                        <i class="fas fa-folder me-2 text-primary"></i>التصنيف
                                                    </label>
                                                </div>
                                                <div class="form-text mt-2">
                                                    <i class="fas fa-lightbulb text-warning me-1"></i>اختر تصنيفاً موجوداً أو أضف تصنيفاً جديداً
                                                </div>
                                                @error('category')
                                                    <div class="text-danger mt-2">
                                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                                    </div>
                                                @enderror

                                                <div class="mt-3" id="customCategoryField" style="display: none;">
                                                    <div class="form-floating">
                                                        <input type="text" name="category_custom" class="form-control" id="category_custom" placeholder="أدخل تصنيف جديد" value="{{ old('category_custom') }}" style="border-radius: 12px; border: 2px solid #e9ecef;">
                                                        <label for="category_custom" class="fw-semibold">
                                                            <i class="fas fa-plus me-2 text-success"></i>التصنيف الجديد
                                                        </label>
                                                    </div>
                                                    @error('category_custom')
                                                        <div class="text-danger mt-2">
                                                            <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                                        </div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select name="unit" class="form-select form-select-lg" required style="border-radius: 12px; border: 2px solid #e9ecef;">
                                                        <option value="">اختر الوحدة</option>
                                                        <option value="قطعة" {{ old('unit')=='قطعة' ? 'selected' : '' }}>قطعة</option>
                                                        <option value="علبة" {{ old('unit')=='علبة' ? 'selected' : '' }}>علبة</option>
                                                        <option value="كرتون" {{ old('unit')=='كرتون' ? 'selected' : '' }}>كرتون</option>
                                                        <option value="كجم" {{ old('unit')=='كجم' ? 'selected' : '' }}>كجم</option>
                                                        <option value="لتر" {{ old('unit')=='لتر' ? 'selected' : '' }}>لتر</option>
                                                        <option value="قطرة" {{ old('unit')=='قطرة' ? 'selected' : '' }}>قطرة</option>
                                                    </select>
                                                    <label for="unit" class="fw-semibold">
                                                        <i class="fas fa-balance-scale me-2 text-primary"></i>الوحدة
                                                    </label>
                                                </div>
                                                @error('unit')
                                                    <div class="text-danger mt-2">
                                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold mb-3">
                                                    <i class="fas fa-clock me-2 text-primary"></i>قابل للتلف
                                                </label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="is_perishable" id="perishable_yes" value="1" {{ old('is_perishable') == '1' ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-semibold" for="perishable_yes">
                                                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2" style="border-radius: 20px; cursor: pointer;">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>نعم
                                                            </span>
                                                        </label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="is_perishable" id="perishable_no" value="0" {{ old('is_perishable') === '0' ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-semibold" for="perishable_no">
                                                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2" style="border-radius: 20px; cursor: pointer;">
                                                                <i class="fas fa-check-circle me-1"></i>لا
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                                @error('is_perishable')
                                                    <div class="text-danger mt-2">
                                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="number" name="alert_quantity" class="form-control form-control-lg" id="alert_quantity" value="{{ old('alert_quantity', 10) }}" min="0" required style="border-radius: 12px; border: 2px solid #e9ecef;">
                                                    <label for="alert_quantity" class="fw-semibold">
                                                        <i class="fas fa-chart-line me-2 text-primary"></i>تنبيه الكمية
                                                    </label>
                                                </div>
                                                <div class="form-text mt-2">
                                                    <i class="fas fa-info-circle text-info me-1"></i>تنبيه قبل أن يصل المخزون إلى نقطة الخطر
                                                </div>
                                                @error('alert_quantity')
                                                    <div class="text-danger mt-2">
                                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Settings Section -->
                            <div class="col-12">
                                <div class="card border-0 bg-light" style="border-radius: 15px;">
                                    <div class="card-header bg-white border-0 py-4" style="border-radius: 15px 15px 0 0;">
                                        <h5 class="mb-0 fw-bold text-primary">
                                            <i class="fas fa-cogs me-2"></i>إعدادات إضافية
                                        </h5>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row g-4">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="number" name="reorder_level" class="form-control form-control-lg" id="reorder_level" value="{{ old('reorder_level', 0) }}" min="0" style="border-radius: 12px; border: 2px solid #e9ecef;">
                                                    <label for="reorder_level" class="fw-semibold">
                                                        <i class="fas fa-sync-alt me-2 text-primary"></i>حد إعادة الطلب
                                                    </label>
                                                </div>
                                                <div class="form-text mt-2">
                                                    <i class="fas fa-lightbulb text-warning me-1"></i>الكمية التي عندها يجب التفكير في إعادة الشراء
                                                </div>
                                                @error('reorder_level')
                                                    <div class="text-danger mt-2">
                                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" name="storage_conditions" class="form-control form-control-lg" id="storage_conditions" value="{{ old('storage_conditions') }}" placeholder="مثال: درجة حرارة الغرفة، بعيداً عن الرطوبة" style="border-radius: 12px; border: 2px solid #e9ecef;">
                                                    <label for="storage_conditions" class="fw-semibold">
                                                        <i class="fas fa-warehouse me-2 text-primary"></i>شروط التخزين
                                                    </label>
                                                </div>
                                                @error('storage_conditions')
                                                    <div class="text-danger mt-2">
                                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <textarea name="description" class="form-control" id="description" rows="4" placeholder="أي تفاصيل إضافية عن المادة" style="border-radius: 12px; border: 2px solid #e9ecef; min-height: 120px;">{{ old('description') }}</textarea>
                                                    <label for="description" class="fw-semibold">
                                                        <i class="fas fa-sticky-note me-2 text-primary"></i>ملاحظات إضافية
                                                    </label>
                                                </div>
                                                @error('description')
                                                    <div class="text-danger mt-2">
                                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row mt-5">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-lg px-5" style="border-radius: 25px;">
                                        <i class="fas fa-times me-2"></i>إلغاء
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow-lg" style="border-radius: 25px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                                        <i class="fas fa-save me-2"></i>حفظ المنتج
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

        let filledInputs = 0;
        inputs.forEach(input => {
            if (input.value.trim() !== '') {
                filledInputs++;
            }
        });

        const progress = (filledInputs / inputs.length) * 100;
        progressBar.style.width = progress + '%';

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
                });

                // Add active class to selected badge
                const label = this.closest('.form-check-inline').querySelector('.badge');
                label.classList.add('border', 'border-primary', 'shadow-sm');
            });
        });

        // Initialize radio button states
        radioButtons.forEach(radio => {
            if (radio.checked) {
                const label = radio.closest('.form-check-inline').querySelector('.badge');
                label.classList.add('border', 'border-primary', 'shadow-sm');
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