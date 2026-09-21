@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-plus-circle me-2"></i>إضافة دواء / مستلزم طبي جديد
            </h2>
            <p class="text-muted small mb-0">تعريف صنف جديد بالدليل مع ضبط الوحدات والتسعير المرن والتغطية التأمينية</p>
        </div>
        <div>
            <a href="{{ route('pharmacy.medicines.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> العودة لدليل الأدوية
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

    <form action="{{ route('pharmacy.medicines.store') }}" method="POST" id="medicineForm">
        @csrf

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
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="مثال: Amoxicillin 500mg Cap" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">الرمز الوطني للضمان</label>
                                <input type="text" name="national_code" class="form-control @error('national_code') is-invalid @enderror font-monospace" value="{{ old('national_code') }}" placeholder="مثال: 01-C00-038">
                                <small class="text-muted" style="font-size: 0.75rem;">الرمز الرسمي بكتيب هيئة الضمان</small>
                                @error('national_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">الاسم العلمي والمادة الفعالة</label>
                                <input type="text" name="generic_name" class="form-control @error('generic_name') is-invalid @enderror" value="{{ old('generic_name') }}" placeholder="مثال: Amoxicillin Trihydrate">
                                @error('generic_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">الشكل الصيدلاني</label>
                                <select name="dosage_form" class="form-select @error('dosage_form') is-invalid @enderror">
                                    <option value="حبوب" {{ old('dosage_form') == 'حبوب' ? 'selected' : '' }}>حبوب (Tablets)</option>
                                    <option value="كبسول" {{ old('dosage_form') == 'كبسول' ? 'selected' : '' }}>كبسول (Capsules)</option>
                                    <option value="شراب" {{ old('dosage_form') == 'شراب' ? 'selected' : '' }}>شراب (Syrup)</option>
                                    <option value="أمبول" {{ old('dosage_form') == 'أمبول' ? 'selected' : '' }}>أمبول (Ampoule)</option>
                                    <option value="فيال" {{ old('dosage_form') == 'فيال' ? 'selected' : '' }}>فيال (Vial)</option>
                                    <option value="مرهم" {{ old('dosage_form') == 'مرهم' ? 'selected' : '' }}>مرهم / كريم (Ointment)</option>
                                    <option value="قطرات" {{ old('dosage_form') == 'قطرات' ? 'selected' : '' }}>قطرات (Drops)</option>
                                    <option value="رذاذ" {{ old('dosage_form') == 'رذاذ' ? 'selected' : '' }}>رذاذ / بخاخ (Spray)</option>
                                    <option value="مستلزم طبي" {{ old('dosage_form') == 'مستلزم طبي' ? 'selected' : '' }}>مستلزم طبي (Supply)</option>
                                </select>
                                @error('dosage_form')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">العيار / التركيز</label>
                                <input type="text" name="strength" class="form-control @error('strength') is-invalid @enderror" value="{{ old('strength') }}" placeholder="مثال: 500mg أو 10ml">
                                @error('strength')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">باركود العلبة الرئيسي</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-barcode"></i></span>
                                    <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror font-monospace" value="{{ old('barcode') }}" placeholder="امسح بالباركود...">
                                </div>
                                @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">باركود الشريط / الوحدة الصغرى</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-barcode"></i></span>
                                    <input type="text" name="sub_barcode" class="form-control @error('sub_barcode') is-invalid @enderror font-monospace" value="{{ old('sub_barcode') }}" placeholder="امسح باركود الشريط...">
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
                                <input type="text" name="main_unit" id="main_unit" class="form-control" value="{{ old('main_unit', 'علبة') }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">الوحدة الصغرى <span class="text-danger">*</span></label>
                                <input type="text" name="sub_unit" id="sub_unit" class="form-control" value="{{ old('sub_unit', 'شريط') }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">معامل التحويل (كم وحدة بالعلبة؟) <span class="text-danger">*</span></label>
                                <input type="number" name="sub_units_count" id="sub_units_count" class="form-control fw-bold text-center" value="{{ old('sub_units_count', 2) }}" min="1" required>
                                <small class="text-muted" style="font-size: 0.75rem;">العلبة تحتوي على كم شريط؟</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">سعر الشراء والتكلفة (د.ع) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="cost_price" class="form-control" value="{{ old('cost_price', 0) }}" min="0" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">سعر بيع العلبة نقداً (د.ع) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="sale_price" id="sale_price" class="form-control fw-bold text-primary" value="{{ old('sale_price', 0) }}" min="0" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">سعر بيع الشريط نقداً (د.ع)</label>
                                <input type="number" step="0.01" name="sub_unit_sale_price" id="sub_unit_sale_price" class="form-control text-primary" value="{{ old('sub_unit_sale_price', 0) }}" min="0">
                                <small class="text-muted" style="font-size: 0.75rem;">يُحسب تلقائياً ويمكن تعديله</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">سعر العلبة للضمان الصحي (د.ع)</label>
                                <input type="number" step="0.01" name="hi_price" class="form-control text-success" value="{{ old('hi_price') }}" placeholder="إن تُرك فارغاً يُعتمد سعر الكاش">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">سعر العلبة لوزارة الداخلية (د.ع)</label>
                                <input type="number" step="0.01" name="moi_price" class="form-control" value="{{ old('moi_price') }}" placeholder="إن تُرك فارغاً يُعتمد سعر الكاش">
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_insurance_covered" id="is_insurance_covered" value="1" {{ old('is_insurance_covered', '1') == '1' ? 'checked' : '' }}>
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
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="fw-bold text-warning text-dark mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>3. الاشتراطات والرقابة المخزنية
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">حد التنبيه لنفاد الرصيد (بالعلب) <span class="text-danger">*</span></label>
                                <input type="number" name="min_stock_alert" class="form-control" value="{{ old('min_stock_alert', 5) }}" min="0" required>
                                <small class="text-muted" style="font-size: 0.75rem;">تنبيه عندما يقل الرصيد عن هذا الحد</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">شروط الحفظ والتخزين</label>
                                <input type="text" name="storage_temperature" class="form-control" value="{{ old('storage_temperature') }}" placeholder="مثال: يحفظ في الثلاجة 2-8 مئوية">
                            </div>

                            <div class="col-md-6">
                                <div class="card p-3 border bg-light h-100">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="requires_prescription" id="requires_prescription" value="1" {{ old('requires_prescription') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold text-danger" for="requires_prescription">
                                            <i class="fas fa-file-prescription me-1"></i> اشتراط وصفة طبية إجبارية
                                        </label>
                                    </div>
                                    <small class="text-muted mt-1">يمنع صرفه بدون وصفة طبيب مسجلة بالنظام</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card p-3 border bg-light h-100">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_controlled" id="is_controlled" value="1" {{ old('is_controlled') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold text-dark" for="is_controlled">
                                            <i class="fas fa-lock me-1 text-warning"></i> أدوية رقابية ومؤثرات عقلية
                                        </label>
                                    </div>
                                    <small class="text-muted mt-1">يخضع لجرد وتدقيق رقابي خاص</small>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">ملاحظات إضافية</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="أي ملاحظات تخص الصنف أو الاستخدام...">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- البطاقة 4: رصيد الشحنة الافتتاحية (اختياري) -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-info mb-0">
                            <i class="fas fa-boxes me-2"></i>4. رصيد الشحنة الافتتاحية (اختياري)
                        </h6>
                        <span class="badge bg-light text-muted border">يمكن إضافته لاحقاً</span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">إذا كان لديك رصيد فعلي حالي في الصيدلية، يمكنك إدخال الوجبة الأولى مباشرة هنا لتسجيل تاريخ انتهائها وتفعيل قاعدة FEFO فوراً.</p>
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">رقم الوجبة (Lot / Batch)</label>
                                <input type="text" name="initial_batch_number" class="form-control font-monospace" value="{{ old('initial_batch_number') }}" placeholder="مثال: LOT-2026-A1">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">تاريخ انتهاء الصلاحية</label>
                                <input type="date" name="initial_expiry_date" class="form-control" value="{{ old('initial_expiry_date') }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">الكمية الافتتاحية (بالعلب)</label>
                                <input type="number" name="initial_quantity" class="form-control fw-bold text-center" value="{{ old('initial_quantity') }}" min="1" placeholder="مثال: 50">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- شريط الحفظ -->
            <div class="col-12 text-end mt-4">
                <a href="{{ route('pharmacy.medicines.index') }}" class="btn btn-light py-2 px-4 border me-2">إلغاء</a>
                <button type="submit" class="btn btn-primary py-2 px-5 shadow-sm">
                    <i class="fas fa-save me-1"></i> حفظ وإضافة الصنف للدليل
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const salePriceInput = document.getElementById('sale_price');
    const subUnitsCountInput = document.getElementById('sub_units_count');
    const subUnitSalePriceInput = document.getElementById('sub_unit_sale_price');

    function calculateSubUnitPrice() {
        const salePrice = parseFloat(salePriceInput.value) || 0;
        const subUnitsCount = parseInt(subUnitsCountInput.value) || 1;
        if (salePrice > 0 && subUnitsCount > 0) {
            // إذا لم يكن المستخدم قد أدخل سعراً يدوياً مسبقاً للشريط
            const calculated = (salePrice / subUnitsCount).toFixed(2);
            subUnitSalePriceInput.value = calculated;
        }
    }

    salePriceInput.addEventListener('input', calculateSubUnitPrice);
    subUnitsCountInput.addEventListener('input', calculateSubUnitPrice);
});
</script>
@endpush
@endsection
