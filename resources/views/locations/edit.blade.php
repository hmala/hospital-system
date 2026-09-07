@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg text-white" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border-radius: 20px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row gap-3">
                        <div>
                            <h2 class="mb-1 fw-bold">
                                <i class="fas fa-edit me-3"></i>تعديل بيانات المخزن
                            </h2>
                            <p class="mb-0 opacity-75">تعديل اسم المخزن ونوعه (رئيسي / قسم فرعي).</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('locations.show', $location) }}" class="btn btn-light text-primary px-3 py-2 rounded-pill fw-bold">
                                <i class="fas fa-eye me-1"></i>عرض المخزن
                            </a>
                            <a href="{{ route('locations.index') }}" class="btn btn-outline-light px-3 py-2 rounded-pill fw-bold">
                                <i class="fas fa-arrow-left me-1"></i>رجوع للمخازن
                            </a>
                        </div>
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
                            <i class="fas fa-info-circle text-white"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">معلومات المخزن</h5>
                            <small class="text-muted">تحديث الاسم ونوع المخزن</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('locations.update', $location) }}" method="POST" id="locationForm">
                        @csrf
                        @method('PUT')

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="name" id="locationName" class="form-control" placeholder="اسم المخزن" value="{{ old('name', $location->name) }}" required>
                                    <label for="locationName">اسم المخزن <span class="text-danger">*</span></label>
                                    @error('name') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="type" id="locationType" class="form-select" required>
                                        <option value="">اختر نوع المخزن</option>
                                        <option value="main" {{ old('type', $location->type) == 'main' ? 'selected' : '' }}>رئيسي</option>
                                        <option value="sub" {{ old('type', $location->type) == 'sub' ? 'selected' : '' }}>قسم</option>
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
                                <i class="fas fa-save me-2"></i>حفظ التعديلات
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
