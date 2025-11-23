# إضافة إمكانية طلب الأشعة في نظام الطلبات الطبية

## التغييرات المنفذة:

### 1. تعديلات على Controller (DoctorVisitController.php)

#### في دالة show():
- إضافة جلب أنواع الأشعة النشطة من قاعدة البيانات:
```php
$radiologyTypes = \App\Models\RadiologyType::where('is_active', true)->orderBy('name')->get();
```
- إضافة `radiologyTypes` إلى المتغيرات المرسلة للعرض

#### في دالة storeRequest():
- إضافة validation لأنواع الأشعة:
```php
'radiology_types' => 'nullable|array',
'radiology_types.*' => 'exists:radiology_types,id'
```
- إضافة منطق لحفظ أنواع الأشعة المختارة في details:
```php
if ($request->type === 'radiology' && $request->radiology_types) {
    $details['radiology_types'] = $request->radiology_types;
    $types = \App\Models\RadiologyType::whereIn('id', $request->radiology_types)->pluck('name')->toArray();
    $details['radiology_type_names'] = $types;
}
```

### 2. تعديلات على العرض (show.blade.php)

#### في Modal إضافة الطلب:
- إضافة أزرار اختيار نوع الطلب (تحاليل أو أشعة):
```html
<div class="btn-group w-100" role="group">
    <input type="radio" class="btn-check" name="request_type_selector" id="lab_type" value="lab" checked>
    <label class="btn btn-outline-primary" for="lab_type">
        <i class="fas fa-flask me-2"></i>
        تحاليل مخبرية
    </label>

    <input type="radio" class="btn-check" name="request_type_selector" id="radiology_type" value="radiology">
    <label class="btn btn-outline-info" for="radiology_type">
        <i class="fas fa-x-ray me-2"></i>
        أشعة
    </label>
</div>
```

- إضافة قسم جديد لعرض أنواع الأشعة:
```html
<div class="card border-0 shadow-sm request-section" id="radiologyTests" style="display: none;">
    <!-- عرض جميع أنواع الأشعة كـ checkboxes -->
</div>
```

#### في JavaScript:
- إضافة منطق للتبديل بين التحاليل والأشعة:
```javascript
requestTypeRadios.forEach(radio => {
    radio.addEventListener('change', function() {
        const selectedType = this.value;
        requestTypeInput.value = selectedType;

        if (selectedType === 'lab') {
            labTestsSection.style.display = 'block';
            radiologyTestsSection.style.display = 'none';
            // إلغاء تحديد الأشعة
        } else if (selectedType === 'radiology') {
            labTestsSection.style.display = 'none';
            radiologyTestsSection.style.display = 'block';
            // إلغاء تحديد التحاليل
        }
    });
});
```

#### في عرض الطلبات:
- تحسين عرض التفاصيل لإظهار أنواع الأشعة المطلوبة:
```blade
@if($request->type == 'radiology' && isset($request->details['radiology_type_names']))
    <strong>الأشعة:</strong> {{ implode(', ', array_slice($request->details['radiology_type_names'], 0, 3)) }}
@endif
```

### 3. CSS الإضافي:
```css
.radiology-item {
    transition: all 0.3s ease;
    cursor: pointer;
}

.radiology-item:hover {
    background-color: #f8f9fa;
    border-color: #17a2b8 !important;
}
```

## طريقة الاستخدام:

1. في صفحة فحص المريض، انقر على "إضافة طلب طبي"
2. اختر نوع الطلب:
   - **تحاليل مخبرية**: لإضافة تحاليل طبية
   - **أشعة**: لإضافة طلبات أشعة
3. حدد الفحوصات أو الأشعة المطلوبة
4. انقر "إضافة الطلب"

## الميزات:

✅ واجهة موحدة للتحاليل والأشعة
✅ عرض معلومات إضافية (هل يتطلب صبغة، هل يتطلب تحضير)
✅ إلغاء التحديد التلقائي عند التبديل بين الأنواع
✅ عرض محسّن للطلبات في الجدول
✅ دعم كامل لأنواع الأشعة الثمانية الموجودة في النظام

## أنواع الأشعة المتوفرة:

1. أشعة عادية (X-ray)
2. أشعة مقطعية (CT Scan)
3. الرنين المغناطيسي (MRI)
4. الموجات فوق الصوتية (Ultrasound)
5. تصوير الثدي (Mammography)
6. أشعة الأسنان (Dental X-ray)
7. أشعة العظام (Bone Scan)
8. تصوير الأوعية الدموية (Angiography)
