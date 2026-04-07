@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px;">
                <div class="card-body p-4 text-white">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row gap-3">
                        <div>
                            <h2 class="mb-1 fw-bold">
                                <i class="fas fa-map-marker-alt me-3"></i>إضافة مخزن جديد
                            </h2>
                            <p class="mb-0 opacity-75">أضف مخزنًا جديدًا إلى النظام وحدد نوعه بوضوح.</p>
                        </div>
                        <a href="{{ route('locations.index') }}" class="btn btn-light px-4 py-2 rounded-pill fw-bold">
                            <i class="fas fa-arrow-left me-2"></i>رجوع للمخازن
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                <div class="card-header bg-light border-0" style="border-radius: 15px 15px 0 0;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary rounded-circle p-2">
                            <i class="fas fa-plus text-white"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">معلومات المخزن</h5>
                            <small class="text-muted">حدد اسم المخزن ونوعه</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('locations.store') }}" method="POST" id="locationForm">
                        @csrf
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="name" id="locationName" class="form-control" placeholder="اسم المخزن" value="{{ old('name') }}" required>
                                    <label for="locationName">اسم المخزن <span class="text-danger">*</span></label>
                                    @error('name') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="type" id="locationType" class="form-select" required>
                                        <option value="">اختر نوع المخزن</option>
                                        <option value="main" {{ old('type') == 'main' ? 'selected' : '' }}>رئيسي</option>
                                        <option value="sub" {{ old('type') == 'sub' ? 'selected' : '' }}>قسم</option>
                                    </select>
                                    <label for="locationType">نوع المخزن <span class="text-danger">*</span></label>
                                    @error('type') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex flex-column flex-sm-row gap-3 justify-content-between">
                            <a href="{{ route('locations.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                                <i class="fas fa-arrow-left me-2"></i>رجوع
                            </a>
                            <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill fw-bold">
                                <i class="fas fa-save me-2"></i>حفظ المخزن
                            </button>
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
        const form = document.getElementById('locationForm');
        const inputs = form.querySelectorAll('[required]');

        form.addEventListener('submit', function(e) {
            let isValid = true;
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('is-invalid');
                }
            });
            if (!isValid) {
                e.preventDefault();
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
    .form-control, .form-select {
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    .form-control:focus, .form-select:focus {
        transform: translateY(-2px);
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
</style>
@endsection
