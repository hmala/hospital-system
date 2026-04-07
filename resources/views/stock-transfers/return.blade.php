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
                                <i class="fas fa-undo-alt me-3"></i>إرجاع مخزون إلى المخزن الرئيسي
                            </h2>
                            <p class="mb-0 opacity-75">ارجع الكميات المرسلة إلى المخزن الفرعي إلى المخزن الرئيسي ثم تابع خطوات الاسترجاع للمورد.</p>
                        </div>
                        <a href="{{ route('stock-transfers.create') }}" class="btn btn-light px-4 py-2 rounded-pill fw-bold">
                            <i class="fas fa-exchange-alt me-2"></i>نقل عادي
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notice -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info border-0 shadow-sm rounded-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-info text-white"></i>
                    </div>
                    <div>
                        <strong>ملاحظة:</strong> هذا النموذج مخصص لإرجاع الكميات من المخزن الفرعي إلى المخزن الرئيسي. بعد عودة البضاعة إلى الرئيسي يمكنك تنفيذ إرجاع المورد من خلال سجل المرتجعات لاحقًا.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Return Form -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                <div class="card-header bg-light border-0" style="border-radius: 15px 15px 0 0;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary rounded-circle p-2">
                            <i class="fas fa-warehouse text-white"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">تفاصيل الإرجاع</h5>
                            <small class="text-muted">اختر المخازن والمادة والكمية المطلوبة للإرجاع.</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success border-0 shadow-sm rounded-4">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger border-0 shadow-sm rounded-4">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('stock-transfers.returns.store') }}" id="returnForm">
                        @csrf

                        @if($purchase)
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card border-0 bg-white shadow-sm p-3 rounded-4">
                                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                            <div>
                                                <div class="small text-muted">فاتورة الاسترجاع</div>
                                                <div class="fw-semibold">{{ $purchase->invoice_number }} • {{ $purchase->supplier->name ?? '-' }}</div>
                                            </div>
                                            <div class="badge bg-secondary text-white rounded-pill">مرتبط بالفاتورة</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="from_location_id" class="form-select" required>
                                        <option value="">اختر المخزن الفرعي</option>
                                        @foreach($subLocations as $location)
                                            <option value="{{ $location->id }}" {{ old('from_location_id') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                    <label>من مخزن فرعي <span class="text-danger">*</span></label>
                                    @error('from_location_id') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="to_location_id" class="form-select" required>
                                        <option value="">اختر المخزن الرئيسي</option>
                                        @foreach($mainLocations as $location)
                                            <option value="{{ $location->id }}" {{ old('to_location_id') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                    <label>إلى المخزن الرئيسي <span class="text-danger">*</span></label>
                                    @error('to_location_id') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="product_id" class="form-select" required>
                                        <option value="">اختر المادة</option>
                                        @if($purchase)
                                            @foreach($purchase->items as $item)
                                                <option value="{{ $item->product->id }}" {{ old('product_id') == $item->product->id ? 'selected' : '' }}>{{ $item->product->name }}</option>
                                            @endforeach
                                        @else
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <label>المادة المراد إرجاعها <span class="text-danger">*</span></label>
                                    @error('product_id') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" name="qty" class="form-control" min="1" value="{{ old('qty', 1) }}" required>
                                    <label>الكمية <span class="text-danger">*</span></label>
                                    @error('qty') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between gap-3 flex-column flex-sm-row">
                            <a href="{{ $purchase ? route('purchases.show', $purchase) : route('stock-transfers.create') }}" class="btn btn-outline-secondary rounded-pill px-4 py-2">
                                <i class="fas fa-arrow-left me-2"></i>رجوع
                            </a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold">
                                <i class="fas fa-undo-alt me-2"></i>إرجاع إلى الرئيسي
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
        const form = document.getElementById('returnForm');
        form.addEventListener('submit', function (e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
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
    .alert {
        border-radius: 15px;
    }
</style>
@endsection