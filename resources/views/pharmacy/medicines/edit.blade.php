@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-edit me-2"></i>تعديل بيانات الدواء / المستلزم: {{ $medicine->name }}
            </h2>
            <p class="text-muted small mb-0">تحديث الأسعار، الوحدات، والاشتراطات الرقابية والتأمينية</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('pharmacy.medicines.show', $medicine->id) }}" class="btn btn-outline-info btn-sm">
                <i class="fas fa-eye me-1"></i> عرض الإضبارة
            </a>
            <a href="{{ route('pharmacy.medicines.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
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

    <form action="{{ route('pharmacy.medicines.update', $medicine->id) }}" method="POST" id="medicineForm">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- البطاقة 1: البيانات الأساسية للتعريف -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="fw-bold text-primary mb-0">
                            <i class="fas fa-info-circle me-2"></i>1. البيانات الأساسية للصنف
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">الاسم التجاري للدواء / المستلزم <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $medicine->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">الرمز الوطني للضمان</label>
                                <input type="text" name="national_code" class="form-control @error('national_code') is-invalid @enderror font-monospace" value="{{ old('national_code', $medicine->national_code) }}">
                                <small class="text-muted" style="font-size: 0.75rem;">الرمز الرسمي بكتيب الضمان</small>
                                @error('national_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">الاسم العلمي والمادة الفعالة</label>
                                <input type="text" name="generic_name" class="form-control @error('generic_name') is-invalid @enderror" value="{{ old('generic_name', $medicine->generic_name) }}">
                                @error('generic_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">الشكل الصيدلاني</label>
                                <select name="dosage_form" class="form-select @error('dosage_form') is-invalid @enderror">
                                    @php $currentForm = old('dosage_form', $medicine->dosage_form); @endphp
                                    <option value="حبوب" {{ $currentForm == 'حبوب' ? 'selected' : '' }}>حبوب (Tablets)</option>
                                    <option value="كبسول" {{ $currentForm == 'كبسول' ? 'selected' : '' }}>كبسول (Capsules)</option>
                                    <option value="شراب" {{ $currentForm == 'شراب' ? 'selected' : '' }}>شراب (Syrup)</option>
                                    <option value="أمبول" {{ $currentForm == 'أمبول' ? 'selected' : '' }}>أمبول (Ampoule)</option>
                                    <option value="فيال" {{ $currentForm == 'فيال' ? 'selected' : '' }}>فيال (Vial)</option>
                                    <option value="مرهم" {{ $currentForm == 'مرهم' ? 'selected' : '' }}>مرهم / كريم (Ointment)</option>
                                    <option value="قطرات" {{ $currentForm == 'قطرات' ? 'selected' : '' }}>قطرات (Drops)</option>
                                    <option value="رذاذ" {{ $currentForm == 'رذاذ' ? 'selected' : '' }}>رذاذ / بخاخ (Spray)</option>
                                    <option value="مستلزم طبي" {{ $currentForm == 'مستلزم طبي' ? 'selected' : '' }}>مستلزم طبي (Supply)</option>
                                </select>
                                @error('dosage_form')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">العيار / التركيز</label>
                                <input type="text" name="strength" class="form-control @error('strength') is-invalid @enderror" value="{{ old('strength', $medicine->strength) }}">
                                @error('strength')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">باركود العلبة الرئيسي</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-barcode"></i></span>
                                    <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror font-monospace" value="{{ old('barcode', $medicine->barcode) }}">
                                </div>
                                @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">باركود الشريط / الوحدة الصغرى</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-barcode"></i></span>
                                    <input type="text" name="sub_barcode" class="form-control @error('sub_barcode') is-invalid @enderror font-monospace" value="{{ old('sub_barcode', $medicine->sub_barcode) }}">
                                </div>
                                @error('sub_barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- البطاقة 2: الوحدات ومعامل التحويل والتسعير المرن -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="fw-bold text-success mb-0">
                            <i class="fas fa-coins me-2"></i>2. الوحدات والتسعير المرن
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">الوحدة الكبرى <span class="text-danger">*</span></label>
                                <input type="text" name="main_unit" id="main_unit" class="form-control" value="{{ old('main_unit', $medicine->main_unit) }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">الوحدة الصغرى <span class="text-danger">*</span></label>
                                <input type="text" name="sub_unit" id="sub_unit" class="form-control" value="{{ old('sub_unit', $medicine->sub_unit) }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">معامل التحويل (كم وحدة بالعلبة؟) <span class="text-danger">*</span></label>
                                <input type="number" name="sub_units_count" id="sub_units_count" class="form-control fw-bold text-center" value="{{ old('sub_units_count', $medicine->sub_units_count) }}" min="1" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">سعر الشراء والتكلفة (د.ع) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="cost_price" class="form-control" value="{{ old('cost_price', $medicine->cost_price) }}" min="0" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">سعر بيع العلبة نقداً (د.ع) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="sale_price" id="sale_price" class="form-control fw-bold text-primary" value="{{ old('sale_price', $medicine->sale_price) }}" min="0" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">سعر بيع الشريط نقداً (د.ع)</label>
                                <input type="number" step="0.01" name="sub_unit_sale_price" id="sub_unit_sale_price" class="form-control text-primary" value="{{ old('sub_unit_sale_price', $medicine->sub_unit_sale_price) }}" min="0">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">سعر العلبة للضمان الصحي (د.ع)</label>
                                <input type="number" step="0.01" name="hi_price" class="form-control text-success" value="{{ old('hi_price', $medicine->hi_price) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">سعر العلبة لوزارة الداخلية (د.ع)</label>
                                <input type="number" step="0.01" name="moi_price" class="form-control" value="{{ old('moi_price', $medicine->moi_price) }}">
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_insurance_covered" id="is_insurance_covered" value="1" {{ old('is_insurance_covered', $medicine->is_insurance_covered) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold text-dark" for="is_insurance_covered">
                                        مشمول بالتغطية التأمينية والضمان الصحي
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- البطاقة 3: الاشتراطات والرقابة المخزنية -->
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>3. الاشتراطات والرقابة المخزنية
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">حد التنبيه لنفاد الرصيد (بالعلب) <span class="text-danger">*</span></label>
                                <input type="number" name="min_stock_alert" class="form-control" value="{{ old('min_stock_alert', $medicine->min_stock_alert) }}" min="0" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">شروط الحفظ والتخزين</label>
                                <input type="text" name="storage_temperature" class="form-control" value="{{ old('storage_temperature', $medicine->storage_temperature) }}">
                            </div>

                            <div class="col-md-3">
                                <div class="card p-3 border bg-light h-100">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="requires_prescription" id="requires_prescription" value="1" {{ old('requires_prescription', $medicine->requires_prescription) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold text-danger" for="requires_prescription">
                                            <i class="fas fa-file-prescription me-1"></i> وصفة طبية إجبارية
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card p-3 border bg-light h-100">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_controlled" id="is_controlled" value="1" {{ old('is_controlled', $medicine->is_controlled) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold text-dark" for="is_controlled">
                                            <i class="fas fa-lock me-1 text-warning"></i> أدوية رقابية ومؤثرات
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">ملاحظات إضافية</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $medicine->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- شريط الحفظ -->
            <div class="col-12 text-end mt-4">
                <a href="{{ route('pharmacy.medicines.show', $medicine->id) }}" class="btn btn-light py-2 px-4 border me-2">إلغاء</a>
                <button type="submit" class="btn btn-primary py-2 px-5 shadow-sm">
                    <i class="fas fa-save me-1"></i> حفظ التعديلات
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
