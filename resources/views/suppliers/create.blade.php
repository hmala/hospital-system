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
                                <i class="fas fa-user-plus me-3"></i>إضافة مورد جديد
                            </h2>
                            <p class="mb-0 opacity-75">أدخل بيانات المورد الجديد في النظام</p>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('suppliers.index') }}" class="btn btn-light px-4 py-2 rounded-pill">
                                <i class="fas fa-arrow-left me-2"></i>رجوع للقائمة
                            </a>
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
            <form method="POST" action="{{ route('suppliers.store') }}" id="supplierForm">
                @csrf

                <!-- Basic Information Card -->
                <div class="card border-0 shadow-lg mb-4" style="border-radius: 15px;">
                    <div class="card-header bg-light border-0" style="border-radius: 15px 15px 0 0;">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary rounded-circle p-2 me-3">
                                <i class="fas fa-info-circle text-white"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">المعلومات الأساسية</h5>
                                <small class="text-muted">بيانات المورد الرئيسية</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="name" class="form-control" id="nameInput" placeholder="اسم المورد" value="{{ old('name') }}" required>
                                    <label for="nameInput">اسم المورد <span class="text-danger">*</span></label>
                                    @error('name') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="contact_person" class="form-control" id="contactPersonInput" placeholder="المسؤول" value="{{ old('contact_person') }}">
                                    <label for="contactPersonInput">المسؤول</label>
                                    @error('contact_person') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="tel" name="phone" class="form-control" id="phoneInput" placeholder="رقم الهاتف" value="{{ old('phone') }}">
                                    <label for="phoneInput">رقم الهاتف</label>
                                    <div class="form-text">مثال: +966501234567</div>
                                    @error('phone') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" name="email" class="form-control" id="emailInput" placeholder="البريد الإلكتروني" value="{{ old('email') }}">
                                    <label for="emailInput">البريد الإلكتروني</label>
                                    <div class="form-text">مثال: supplier@company.com</div>
                                    @error('email') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="address" class="form-control" id="addressTextarea" rows="4" placeholder="العنوان">{{ old('address') }}</textarea>
                                    <label for="addressTextarea">العنوان</label>
                                    @error('address') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                                <i class="fas fa-arrow-left me-2"></i>رجوع
                            </a>
                            <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill fw-bold">
                                <i class="fas fa-save me-2"></i>حفظ المورد
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@section('scripts')
<script>
    function updateProgress() {
        const form = document.getElementById('supplierForm');
        const inputs = form.querySelectorAll('input[required], textarea[required]');
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
        // Form progress tracking
        const form = document.getElementById('supplierForm');
        const inputs = form.querySelectorAll('input, textarea');

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
        const formControls = document.querySelectorAll('.form-control');
        formControls.forEach(control => {
            control.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
                this.style.transition = 'all 0.2s ease';
            });

            control.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Phone number formatting
        const phoneInput = document.getElementById('phoneInput');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.startsWith('966')) {
                    value = '+' + value;
                } else if (value.startsWith('05')) {
                    value = '+966' + value.substring(1);
                }
                e.target.value = value;
            });
        }

        // Email validation feedback
        const emailInput = document.getElementById('emailInput');
        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (this.value && !emailRegex.test(this.value)) {
                    this.style.borderColor = '#dc3545';
                    this.style.boxShadow = '0 0 0 0.2rem rgba(220, 53, 69, 0.25)';
                } else if (this.value) {
                    this.style.borderColor = '#28a745';
                    this.style.boxShadow = '0 0 0 0.2rem rgba(40, 167, 69, 0.25)';
                }
            });
        }
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
    .form-floating > textarea {
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .form-floating > .form-control:focus,
    .form-floating > textarea:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        transform: translateY(-2px);
    }

    .form-floating > label {
        color: #6c757d;
        transition: all 0.2s ease;
    }

    .form-floating > .form-control:focus ~ label,
    .form-floating > textarea:focus ~ label {
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
