@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-edit me-2"></i>تعديل بيانات الوجبة: {{ $batch->batch_number }}
            </h2>
            <p class="text-muted small mb-0">
                الدواء: <span class="fw-bold text-dark">{{ $batch->medicine->name ?? 'غير محدد' }}</span>
                | تاريخ الانتهاء: <span class="font-monospace text-danger">{{ $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : '-' }}</span>
            </p>
        </div>
        <div>
            <a href="{{ route('pharmacy.batches.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> العودة للوحة الوجبات
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            <h6 class="fw-bold mb-2"><i class="fas fa-exclamation-circle me-1"></i> يرجى تصحيح الأخطاء التالية:</h6>
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold text-primary mb-0">
                        <i class="fas fa-edit me-2"></i>تعديل بيانات الوجبة والرصيد
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('pharmacy.batches.update', $batch->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">رقم الوجبة (Lot) <span class="text-danger">*</span></label>
                                <input type="text" name="batch_number" class="form-control font-monospace @error('batch_number') is-invalid @enderror" value="{{ old('batch_number', $batch->batch_number) }}" required>
                                @error('batch_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">تاريخ انتهاء الصلاحية <span class="text-danger">*</span></label>
                                <input type="date" name="expiry_date" class="form-control @error('expiry_date') is-invalid @enderror" value="{{ old('expiry_date', $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : '') }}" required>
                                @error('expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">الرصيد الحالي بالعلب الكاملة <span class="text-danger">*</span></label>
                                <input type="number" name="current_quantity" class="form-control fw-bold text-center @error('current_quantity') is-invalid @enderror" value="{{ old('current_quantity', $batch->current_quantity) }}" min="0" required>
                                <small class="text-muted" style="font-size: 0.75rem;">العلب المغلقة المتوفرة</small>
                                @error('current_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">الأشرطة المتوفرة (من علب مفتوحة) <span class="text-danger">*</span></label>
                                <input type="number" name="current_sub_units" class="form-control fw-bold text-center @error('current_sub_units') is-invalid @enderror" value="{{ old('current_sub_units', $batch->current_sub_units) }}" min="0" required>
                                <small class="text-muted" style="font-size: 0.75rem;">الأشرطة المفردة المتوفرة</small>
                                @error('current_sub_units')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">سعر الشراء (د.ع) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="purchase_price" class="form-control @error('purchase_price') is-invalid @enderror" value="{{ old('purchase_price', $batch->purchase_price) }}" min="0" required>
                                @error('purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">اسم المورد</label>
                                <input type="text" name="supplier_name" class="form-control" value="{{ old('supplier_name', $batch->supplier_name) }}">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label small fw-bold">حالة الوجبة <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="active" {{ old('status', $batch->status) === 'active' ? 'selected' : '' }}>نشطة وسارية (متاحة للصرف)</option>
                                    <option value="quarantined" {{ old('status', $batch->status) === 'quarantined' ? 'selected' : '' }}>محجورة (معلقة الصرف)</option>
                                    <option value="expired" {{ old('status', $batch->status) === 'expired' ? 'selected' : '' }}>منتهية الصلاحية</option>
                                    <option value="depleted" {{ old('status', $batch->status) === 'depleted' ? 'selected' : '' }}>نافدة الكمية (رصيد صفري)</option>
                                </select>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <a href="{{ route('pharmacy.batches.index') }}" class="btn btn-light border px-4 me-2">إلغاء</a>
                            <button type="submit" class="btn btn-primary px-5 shadow-sm">
                                <i class="fas fa-save me-1"></i> حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
