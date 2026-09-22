@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-plus-circle me-2"></i>توريد وجبة / شحنة جديدة للدواء
            </h2>
            <p class="text-muted small mb-0">إدخال كميات جديدة مع رقم التشغيلة وتاريخ انتهاء الصلاحية لتطبيق قاعدة FEFO</p>
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
                        <i class="fas fa-file-invoice me-2"></i>بيانات الشحنة والتوريد
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('pharmacy.batches.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">اختر الدواء / المستلزم <span class="text-danger">*</span></label>
                                <select name="medicine_id" class="form-select @error('medicine_id') is-invalid @enderror" required>
                                    <option value="">-- اختر من دليل الأدوية --</option>
                                    @foreach($medicines as $med)
                                        <option value="{{ $med->id }}" {{ old('medicine_id', $selectedMedicineId) == $med->id ? 'selected' : '' }}>
                                            {{ $med->name }} ({{ $med->dosage_form }} - {{ $med->strength }}) | الوحدة: {{ $med->main_unit }}
                                            @if($med->national_code) | [{{ $med->national_code }}] @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('medicine_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">رقم الوجبة / التشغيلة (Lot / Batch Number) <span class="text-danger">*</span></label>
                                <input type="text" name="batch_number" class="form-control font-monospace @error('batch_number') is-invalid @enderror" value="{{ old('batch_number') }}" placeholder="مثال: LOT-2026-09A" required>
                                @error('batch_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">تاريخ انتهاء الصلاحية <span class="text-danger">*</span></label>
                                <input type="date" name="expiry_date" class="form-control @error('expiry_date') is-invalid @enderror" value="{{ old('expiry_date') }}" required>
                                @error('expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">الكمية المستلمة (بالعلب / الوحدات الكبرى) <span class="text-danger">*</span></label>
                                <input type="number" name="initial_quantity" class="form-control fw-bold text-center @error('initial_quantity') is-invalid @enderror" value="{{ old('initial_quantity', 10) }}" min="1" required>
                                @error('initial_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">سعر شراء العلبة في هذه الشحنة (د.ع) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="purchase_price" class="form-control @error('purchase_price') is-invalid @enderror" value="{{ old('purchase_price', 0) }}" min="0" required>
                                @error('purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">اسم المورد أو شركة التوزيع</label>
                                <input type="text" name="supplier_name" class="form-control" value="{{ old('supplier_name') }}" placeholder="مثال: شركة الرافدين للمستلزمات الطبية">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">تاريخ الاستلام والتوريد</label>
                                <input type="date" name="received_at" class="form-control" value="{{ old('received_at', date('Y-m-d')) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">الحالة الأولية للشحنة <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>نشطة (جاهزة ومتاحة للصرف فوراً)</option>
                                    <option value="quarantined" {{ old('status') === 'quarantined' ? 'selected' : '' }}>محجورة (قيد الفحص المخبري / معلقة الصرف)</option>
                                </select>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <a href="{{ route('pharmacy.batches.index') }}" class="btn btn-light border px-4 me-2">إلغاء</a>
                            <button type="submit" class="btn btn-primary px-5 shadow-sm">
                                <i class="fas fa-save me-1"></i> حفظ وتوريد الوجبة
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
