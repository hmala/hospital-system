# إصلاح نظام التحاليل والأشعة للعمليات الجراحية

## المشكلة
عند عرض صفحة طلبات الأشعة للعمليات (`/staff/surgery-radiology-tests`)، كان يظهر خطأ:
```
Attempt to read property "user" on null
```

### السبب
1. عند حجز عملية جراحية، كان النظام ينشئ سجلات "عامة" بدون تحديد نوع التحليل/الأشعة (`null`)
2. بعض هذه السجلات كانت تشير إلى عمليات محذوفة أو مرضى محذوفين
3. عند محاولة عرض هذه السجلات، كان النظام يحاول الوصول إلى `$test->surgery->patient->user` وأحد هذه العلاقات كان `null`

## الحلول المطبقة

### 1. حماية ملفات العرض (Views)
تم إضافة فحوصات null-safe في الملفات التالية:

#### `resources/views/staff/surgery-radiology-tests/index.blade.php`
- إضافة `@if($test->surgery)` للتحقق من وجود العملية
- استخدام null-safe operator `?->` في: `$test->surgery?->patient?->user`
- عرض رسالة "غير معروف" عند عدم وجود البيانات

#### `resources/views/staff/surgery-radiology-tests/show.blade.php`
- التحقق من وجود العملية قبل عرض التفاصيل
- عرض رسالة تحذير عند عدم توفر معلومات العملية

#### `resources/views/staff/surgery-radiology-tests/print.blade.php`
- استخدام null-safe operators في جميع الأماكن
- التحقق من وجود البيانات قبل عرضها

### 2. تحسين الـ Controller
**الملف**: `app/Http/Controllers/StaffRequestController.php`

```php
$query = \App\Models\SurgeryRadiologyTest::with(['surgery.patient.user', 'surgery.doctor.user', 'radiologyType'])
    ->whereHas('surgery'); // التأكد من وجود عملية صالحة
```

هذا يمنع عرض السجلات التي تشير إلى عمليات محذوفة.

### 3. منع إنشاء السجلات العامة مستقبلاً
**الملف**: `app/Http/Controllers/SurgeryController.php`

تم تعديل وظائف `store()` و `update()` بحيث:
- ✅ يتم إنشاء سجلات التحاليل/الأشعة **فقط** عند تحديدها صراحة
- ❌ لا يتم إنشاء سجلات عامة (بدون `lab_test_id` أو `radiology_type_id`)
- 📝 تم إضافة تعليقات توضح أن موظفي المختبر/الأشعة سيضيفون الطلبات لاحقاً

### 4. سكريبت تنظيف البيانات القديمة
**الملف**: `cleanup_surgery_generic_tests.php`

يقوم السكريبت بـ:
- حذف جميع سجلات التحاليل العامة (`lab_test_id = null`)
- حذف جميع سجلات الأشعة العامة (`radiology_type_id = null`)
- حذف السجلات التي تشير إلى عمليات محذوفة (orphaned records)

## كيفية تشغيل السكريبت

```bash
php cleanup_surgery_generic_tests.php
```

## سير العمل الجديد

### عند حجز عملية جراحية:
1. يتم إنشاء العملية بدون تحاليل أو أشعة
2. يظهر في نظام الموظفين أن العملية بحاجة لتحديد التحاليل/الأشعة

### موظف المختبر/الأشعة:
1. يستطيع عرض العمليات الجديدة
2. يقوم بإضافة التحاليل/الأشعة المطلوبة يدوياً
3. يتم إنشاء سجلات محددة مرتبطة بـ `lab_test_id` أو `radiology_type_id`

## ملاحظات هامة

- ✅ تم حل مشكلة الخطأ "Attempt to read property on null"
- ✅ النظام الآن محمي ضد البيانات المفقودة
- ✅ لن يتم إنشاء سجلات عامة في المستقبل
- 📌 يجب تشغيل سكريبت التنظيف مرة واحدة لحذف السجلات القديمة
- 📌 يمكن إضافة واجهة لموظفي المختبر/الأشعة لإضافة الطلبات مباشرة من صفحة العملية

## الملفات المعدلة

### صفحات الأشعة (Radiology):
1. `app/Http/Controllers/StaffRequestController.php` - surgeryRadiologyTests()
2. `resources/views/staff/surgery-radiology-tests/index.blade.php`
3. `resources/views/staff/surgery-radiology-tests/show.blade.php`
4. `resources/views/staff/surgery-radiology-tests/print.blade.php`

### صفحات التحاليل (Lab Tests):
5. `app/Http/Controllers/StaffRequestController.php` - surgeryLabTests()
6. `resources/views/staff/surgery-lab-tests/index.blade.php`
7. `resources/views/staff/surgery-lab-tests/show.blade.php`
8. `resources/views/staff/surgery-lab-tests/print.blade.php`

### العمليات الجراحية:
9. `app/Http/Controllers/SurgeryController.php` - store() & update()

## الملفات الجديدة

1. `cleanup_surgery_generic_tests.php` - سكريبت تنظيف البيانات
2. `check_surgery_radiology_orphans.php` - سكريبت فحص السجلات اليتيمة

## نتائج التنظيف

عند تشغيل سكريبت التنظيف تم حذف:
- ✅ **9 سجلات تحاليل عامة** (بدون `lab_test_id`)
- ✅ **9 سجلات أشعة عامة** (بدون `radiology_type_id`)
- ✅ **0 سجلات يتيمة** (لا توجد سجلات تشير إلى عمليات محذوفة)
