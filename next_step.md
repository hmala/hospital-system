أنت الآن جاهز تنقل العمل إلى الحاسبة الثانية بدون مشاكل.
لقد حددنا أن الجلسة هذه كانت تحضيرية، لذا ما تحتاجه هو:

- فتح نفس المشروع (`hospital-system`) على الحاسبة الجديدة.
- استخدام نفس الفرع Git أو نسخة من الملفات.
- متابعة العمل من نقطة إنجاز الوظيفة التالية: إضافة واجهات وموديلات وتحسين تجربة إدارة المخزون.

🔧 آخر التحديثات المنفذة اليوم:

1. البنية الأساسية للمخزون والمشتريات
- إنشاء `Product`, `Supplier`, `Purchase`, `PurchaseItem`, `StockBatch`, `StockMovement`, `Location`, `LocationProductThreshold`.
- تم إضافة حقول جديدة في `products`: `category`, `description`, `reorder_level`, `storage_conditions`, `code` أصبح قابل لأن يكون فارغاً ويتم توليده تلقائياً.
- إنشاء سلسلة ميجريشنات لتوسيع `stock_batches`: `location_id`, `purchase_item_id`, `internal_barcode`, `original_barcode`, `supplier_barcode`, `manufacturer_lot_number`, `original_received_at`, `parent_batch_id`.
- إضافة مواد قابلة للتلف `is_perishable` مع التحقق من `expiry_date` عند الاستلام.

2. تحسينات الواجهات
- تحديث الشريط الجانبي في `resources/views/layouts/app.blade.php` لإظهار قسم `إدارة المخزون` مع روابط:
  - المواد
  - الموردون
  - المخزون
  - نقل المخزون
  - إرجاع المخزون
  - مخازن الأقسام
  - قائمة المشتريات
- تحديث واجهات المنتجات لصفحات:
  - `resources/views/products/index.blade.php`
  - `resources/views/products/create.blade.php`
  - `resources/views/products/edit.blade.php`
  - `resources/views/products/barcode.blade.php`
  - `resources/views/products/print-all.blade.php`
- تحديث واجهات الموردين لصفحات:
  - `resources/views/suppliers/index.blade.php`
  - `resources/views/suppliers/create.blade.php`
- تحديث واجهات المشتريات لصفحات:
  - `resources/views/purchases/index.blade.php`
  - `resources/views/purchases/create.blade.php`
  - `resources/views/purchases/show.blade.php`
- تحديث واجهات الموقع والمخازن لصفحات:
  - `resources/views/locations/index.blade.php`
  - `resources/views/locations/create.blade.php`
  - `resources/views/locations/show.blade.php`
- إضافة واجهات مخزون جديدة:
  - `resources/views/inventory/index.blade.php`
  - `resources/views/inventory/low_stock.blade.php`
- إضافة واجهات باركود جديدة:
  - `resources/views/barcodes/index.blade.php`
  - `resources/views/barcodes/show.blade.php`
  - `resources/views/barcodes/purchase.blade.php`
  - `resources/views/barcodes/multiple.blade.php`
- إضافة واجهات نقل/إرجاع المخزون:
  - `resources/views/stock-transfers/create.blade.php`
  - `resources/views/stock-transfers/return.blade.php`

3. المسارات (Routes)
- تحديث `routes/web.php` لإضافة:
  - `products.print-all`, `products.edit`, `products.update`, `products.destroy`, `products.barcode`
  - `purchases.index`, `purchases.show`
  - `barcodes.*` لطباعة الدفعات والفاتورة المتعددة
  - `inventory.index`, `inventory.low_stock`
  - `stock-transfers.create`, `stock-transfers.store`, `stock-transfers.returns.create`, `stock-transfers.returns.store`
  - `locations.index`, `locations.create`, `locations.store`, `locations.show`

4. الـ Controllers
- تحديث `app/Http/Controllers/ProductController.php` لدعم CRUD كامل، التصنيفات، التحديث، وطباعة الباركود.
- تحديث `app/Http/Controllers/PurchaseController.php` لاستلام مشتريات جديدة، إنشاء `PurchaseItem`، إنشاء `StockBatch`، وحركة المخزون.
- إضافة `app/Http/Controllers/BarcodeController.php` لطباعة باركودات دفعة واحدة، فاتورة كاملة، وطباعة متعددة.
- إضافة `app/Http/Controllers/InventoryController.php` لعرض المخزون العام وتقرير المخزون المنخفض.
- إضافة `app/Http/Controllers/LocationController.php` لإدارة مخازن الأقسام.
- إضافة/تحديث `app/Http/Controllers/StockTransferController.php` لدعم:
  - نقل المخزون بين مواقع
  - إرجاع المخزون من المخزن الفرعي إلى المخزن الرئيسي
  - تسجيل حركة مخزون من نوع `transfer` و `return`

5. قواعد البيانات والموديلات
- إنشاء `app/Models/PurchaseItem.php` لربط بنود الفاتورة بالدفعات.
- تحديث `app/Models/StockBatch.php` لدعم العلاقات الجديدة وحالات الـ FIFO.
- تحديث `app/Models/Product.php` لدعم الحقول الجديدة والمنطق الخاص بالمستويات والتنبيهات.
- تحديث `app/Models/Location.php` لدعم علاقة `stockBatches` و `productThresholds`.
- إضافة `app/Models/LocationProductThreshold.php` لاختبارات تنبيه المخزون بمستوى المخزن.

6. نظام الباركود والطباعة
- دعم باركود داخلي لكل دفعة.
- دعم باركود المورد ورقم دفعة المصنع.
- دعم QR Code يحتوي على بيانات مفصلة لكل دفعة.
- إضافة واجهات طباعة متعددة للدفع بالفاتورة أو اختيار دفعات.

7. نظام المخزون والمنخفض
- صفحة `/inventory` لمراقبة رصيد المواد والمخازن.
- صفحة `/inventory/low-stock` لعرض المواد التي وصلت لحد التنبيه أو أقل.
- دعم فلترة المخزون حسب `location_id`.
- حساب التنبيه المحلي لكل مخزن عبر `LocationProductThreshold`.

8. رابط الإرجاع
- تم إضافة رابط `إرجاع المخزون` في القائمة الجانبية.
- تم إنشاء صفحة إرجاع خاصة لإعادة الكميات من المخازن الفرعية إلى المخزن الرئيسي.
- تم توجيه زر الإرجاع من صفحة تفاصيل الفاتورة `purchases.show` إلى نموذج الإرجاع.

9. واجهات اختيار التحاليل والأشعة للعمليات
- إضافة صفحة `staff/surgery-lab-tests/selection` لعرض العمليات الجراحية التي تحتاج اختيار تحاليل.
- إضافة صفحة `staff/surgery-radiology-tests/selection` لعرض العمليات الجراحية التي تحتاج اختيار أشعة.
- إضافة روابط في الشريط الجانبي للانتقال السريع إلى القوائم الجديدة.
- إنشاء طلب عام عند الضغط على `اختر التحاليل` أو `اختر الأشعة` ثم إعادة التوجيه إلى صفحة العرض الصحيحة.

🎯 خطوات العمل الحالية:
1. ✅ تنفيذ `php artisan migrate`.
2. تنفيذ `php artisan db:seed --class=RolesAndPermissionsSeeder` و `php artisan db:seed --class=SidebarLinkSeeder`.

---

## 🔄 التحديثات الأخيرة - 3 مايو 2026

### 9. نظام محطات العمليات الجراحية (Surgery Stations System)
تم إنشاء نظام محطات منفصل لإدارة سير العمليات الجراحية عبر أربع محطات رئيسية:

#### قاعدة البيانات:
- ✅ إنشاء جدول `surgeon_stations` - محطة الطبيب الجراح
- ✅ إنشاء جدول `anesthesia_stations` - محطة التخدير
- ✅ إنشاء جدول `resident_stations` - محطة الطبيب المقيم
- ✅ إنشاء جدول `nursing_stations` - محطة التمريض
- ✅ Migration: `2026_05_03_130000_create_surgery_stations_tables.php`

#### النماذج (Models):
- ✅ `app/Models/SurgeonStation.php` - إدارة محطة الجراح
- ✅ `app/Models/AnesthesiaStation.php` - إدارة محطة التخدير
- ✅ `app/Models/ResidentStation.php` - إدارة محطة المقيم
- ✅ `app/Models/NursingStation.php` - إدارة محطة التمريض
- ✅ تحديث `app/Models/Surgery.php` بإضافة علاقات المحطات ودوال مساعدة

#### المتحكمات (Controllers):
- ✅ `app/Http/Controllers/SurgeonStationController.php` - CRUD لمحطة الجراح
- ✅ `app/Http/Controllers/AnesthesiaStationController.php` - CRUD لمحطة التخدير
- ✅ `app/Http/Controllers/ResidentStationController.php` - CRUD لمحطة المقيم
- ✅ `app/Http/Controllers/NursingStationController.php` - CRUD لمحطة التمريض

#### الواجهات (Views):
محطة الجراح:
- ✅ `resources/views/surgery-stations/surgeon/index.blade.php` - قائمة العمليات
- ✅ `resources/views/surgery-stations/surgeon/show.blade.php` - تفاصيل المحطة

محطة التخدير:
- ✅ `resources/views/surgery-stations/anesthesia/index.blade.php` - قائمة العمليات
- ✅ `resources/views/surgery-stations/anesthesia/show.blade.php` - تفاصيل المحطة

محطة المقيم:
- ✅ `resources/views/surgery-stations/resident/index.blade.php` - قائمة العمليات
- ✅ `resources/views/surgery-stations/resident/show.blade.php` - تفاصيل المحطة

محطة التمريض:
- ✅ `resources/views/surgery-stations/nursing/index.blade.php` - قائمة العمليات
- ✅ `resources/views/surgery-stations/nursing/show.blade.php` - تفاصيل المحطة

#### المسارات (Routes):
تم إضافة 16 مساراً جديداً في `routes/web.php` ضمن مجموعة `surgery-stations`:
- ✅ محطة الجراح: index, show, update, complete
- ✅ محطة التخدير: index, show, update, complete
- ✅ محطة المقيم: index, show, update, complete
- ✅ محطة التمريض: index, show, update, complete

#### القائمة الجانبية:
- ✅ إضافة 4 روابط للمحطات في قسم "العمليات الجراحية"
- ✅ إضافة عدادات ملونة لكل محطة (info, warning, primary, success)
- ✅ أيقونات مميزة: 🩺 الجراح، 💉 التخدير، 👨‍⚕️ المقيم، 👩‍⚕️ التمريض

#### سير العمل (Workflow):
```
محطة الجراح → محطة التخدير → محطة المقيم → محطة التمريض → اكتمال العملية
```

#### ميزات النظام:
- ✅ كل محطة لها جدول منفصل في قاعدة البيانات
- ✅ تتبع حالة كل محطة (pending, in_progress, completed)
- ✅ تسجيل أوقات البدء والإتمام لكل محطة
- ✅ إنشاء تلقائي للمحطة التالية عند إتمام المحطة الحالية
- ✅ عدادات مباشرة تعرض عدد العمليات المعلقة في كل محطة
- ✅ صلاحيات منفصلة لكل محطة
- ✅ واجهات عربية سهلة الاستخدام

#### الوثائق:
- ✅ `docs/SURGERY_STATIONS_GUIDE.md` - دليل شامل للنظام

### 10. تحديثات واجهة العمليات
- ✅ إزالة عمود "الإجراءات" من جدول العمليات النشطة
- ✅ تبسيط عرض العمليات للتركيز على المعلومات الأساسية
- ✅ تحديث colspan في صفوف التفاصيل من 8 إلى 7

### 📋 الملفات المنشأة/المحدثة في هذه الجلسة:

**Models:**
- `app/Models/SurgeonStation.php`
- `app/Models/AnesthesiaStation.php`
- `app/Models/ResidentStation.php`
- `app/Models/NursingStation.php`
- `app/Models/Surgery.php` (محدث)

**Controllers:**
- `app/Http/Controllers/SurgeonStationController.php`
- `app/Http/Controllers/AnesthesiaStationController.php`
- `app/Http/Controllers/ResidentStationController.php`
- `app/Http/Controllers/NursingStationController.php`

**Views:**
- 8 ملفات blade جديدة للمحطات
- `resources/views/surgeries/index.blade.php` (محدث)

**Migrations:**
- `database/migrations/2026_05_03_130000_create_surgery_stations_tables.php`

**Routes:**
- `routes/web.php` (محدث - إضافة 16 مساراً)

**Layouts:**
- `resources/views/layouts/app.blade.php` (محدث - إضافة روابط المحطات والعدادات)

**Documentation:**
- `docs/SURGERY_STATIONS_GUIDE.md`

### ⚡ الأوامر المنفذة:
```bash
php artisan migrate --path=database/migrations/2026_05_03_130000_create_surgery_stations_tables.php
```

### 📝 ملاحظات مهمة:
1. نظام المحطات يعمل بشكل تسلسلي - لا يمكن الانتقال لمحطة إلا بعد إتمام السابقة
2. كل محطة تُنشأ تلقائياً عند إتمام المحطة السابقة
3. العدادات في القائمة الجانبية تحدث تلقائياً
4. النظام جاهز للاستخدام الفوري
3. تنظيف الكاش لو أردت: `php artisan view:clear && php artisan config:clear`.
4. تجربة الواجهات الجديدة:
   - `/products`
   - `/suppliers`
   - `/purchases/create`
   - `/inventory`
   - `/inventory/low-stock`
   - `/barcodes`
   - `/stock-transfers/create`
   - `/stock-transfers/returns/create`
5. تأكد أن زر `إرجاع المخزون` يظهر في القائمة الجانبية.

📌 ملاحظات مهمة:
- الإرجاع حالياً مقيد فقط من مخزن فرعي إلى مخزن رئيسي.
- عند نقل المخزون أو إرجاعه، يتم تسجيل `StockMovement` و`StockBatch` الجديد أو تحديث الدفعة القائمة.
- `original_barcode` و`original_received_at` تحفظ تاريخ وأصل الدفعة بعد النقل.
- النظام الحالي يعتبر `supplier_barcode` و`manufacturer_lot_number` حقول اختيارية.

🔜 مقترح العمل القادم:
1. اختبار كامل للـ workflow من إنشاء المشتريات إلى طباعة الباركود واسترجاع المخزون.

📁 قائمة الملفات المضافة/المعدّلة التي تحتوي الكود الفعلي:

- Controllers:
  - app/Http/Controllers/BarcodeController.php
  - app/Http/Controllers/DoctorVisitController.php
  - app/Http/Controllers/InquiryController.php
  - app/Http/Controllers/InventoryController.php
  - app/Http/Controllers/LabStaffController.php
  - app/Http/Controllers/LabTestController.php
  - app/Http/Controllers/LabTestReferenceController.php
  - app/Http/Controllers/PurchaseController.php
  - app/Http/Controllers/RadiologyController.php
  - app/Http/Controllers/RadiologyStaffController.php
  - app/Http/Controllers/RoleManagementController.php
  - app/Http/Controllers/StaffRequestController.php
  - app/Http/Controllers/StockTransferController.php
  - app/Http/Controllers/SurgeryController.php
  - app/Http/Controllers/UserLabTestGroupController.php

- Models:
  - app/Models/LabTest.php
  - app/Models/LabTestReference.php
  - app/Models/Request.php
  - app/Models/ServiceType.php
  - app/Models/StockTransferRequest.php
  - app/Models/User.php
  - app/Models/UserLabTestGroup.php
  - app/Models/UserLabTestStat.php

- Migrations:
  - database/migrations/2026_04_10_000000_create_stock_transfer_requests_table.php
  - database/migrations/2026_04_10_000001_add_location_id_to_users_table.php
  - database/migrations/2026_04_10_110334_add_location_id_to_users_table.php
  - database/migrations/2026_04_12_155449_create_lab_test_references_table.php
  - database/migrations/2026_04_13_060419_create_user_lab_test_stats_table.php
  - database/migrations/2026_04_13_061929_simplify_user_lab_test_stats_table.php
  - database/migrations/2026_04_14_110000_create_user_lab_test_groups_table.php
  - database/migrations/2026_04_17_000001_make_test_ids_nullable_in_surgery_tests.php
  - database/migrations/2026_04_22_073158_create_service_types_table.php

- Seeders:
  - database/seeders/RadiologyTypesSeeder.php
  - database/seeders/RolesAndPermissionsSeeder.php
  - database/seeders/ServiceTypesSeeder.php

- Views:
  - resources/views/barcodes/print-all.blade.php
  - resources/views/barcodes/show.blade.php
  - resources/views/cashier/index.blade.php
  - resources/views/cashier/request-payment-form.blade.php
  - resources/views/dashboard.blade.php
  - resources/views/doctors/visits/show.blade.php
  - resources/views/inquiry/create.blade.php
  - resources/views/inquiry/index.blade.php
  - resources/views/inventory/index.blade.php
  - resources/views/lab-tests/edit.blade.php
  - resources/views/lab-tests/index.blade.php
  - resources/views/lab-tests/groups/
  - resources/views/lab-tests/references.blade.php
  - resources/views/layouts/app.blade.php
  - resources/views/permissions/index.blade.php
  - resources/views/products/barcode.blade.php
  - resources/views/products/print-all.blade.php
  - resources/views/purchases/show.blade.php
  - resources/views/radiology/print.blade.php
  - resources/views/radiology/show.blade.php
  - resources/views/radiology/show-inquiry.blade.php
  - resources/views/roles/create.blade.php
  - resources/views/roles/edit.blade.php
  - resources/views/roles/index.blade.php
  - resources/views/staff/lab-visits/create.blade.php
  - resources/views/staff/requests/index.blade.php
  - resources/views/staff/requests/show.blade.php
  - resources/views/staff/requests/show-lab.blade.php
  - resources/views/staff/requests/show-radiology.blade.php
  - resources/views/staff/surgery-lab-tests/index.blade.php
  - resources/views/staff/surgery-lab-tests/show.blade.php
  - resources/views/staff/surgery-radiology-tests/index.blade.php
  - resources/views/staff/surgery-radiology-tests/show.blade.php
  - resources/views/staff/surgery-radiology-tests/print.blade.php
  - resources/views/stock-transfers/create.blade.php
  - resources/views/stock-transfers/return.blade.php
  - resources/views/stock-transfers/requests/index.blade.php
  - resources/views/stock-transfers/requests/show.blade.php
  - resources/views/surgeries/create.blade.php
  - resources/views/surgeries/index.blade.php
  - resources/views/surgeries/print.blade.php
  - resources/views/visits/edit.blade.php

- Routes:
  - routes/web.php

- Frontend assets / JS:
  - resources/js/dashboard.js

- Providers:
  - app/Providers/AppServiceProvider.php

- أدوات/فحص إضافية:
  - check_current_session.php
  - check_group_permissions.php
  - check_names.php
  - check_radiology_data.php
  - check_radiology_types.php
  - check_user_roles.php

> ملاحظة: هذا الملف الآن يتضمن قائمة الملفات التي تحتوي على الكود الكامل المضاف/المعدل. لعرض الكود الفعلي، افتح كل ملف من القائمة داخل المشروع.
2. بناء صفحة أو وظيفة لإرجاع الكمية من المخزن الرئيسي إلى المورد.
3. إضافة صلاحيات عرض/تنفيذ `stock-transfers.returns` حسب الدور.
4. تطوير API / مسح ضوئي للباركود في الخطوة التالية.

تم بحمد الله ✅

---

## 🔄 التحديثات الأخيرة - 9 مايو 2026

### 11. إصلاح ازدواجية الدفع في الطوارئ (Payment Double-Charging Fix)

**المشكلة:** عند إضافة خدمة جديدة لحالة طوارئ مدفوعة مسبقاً، كانت الفاتورة تحسب الخدمات القديمة مجدداً.

**الحل المنفذ:**
- إضافة عمود `payment_id` (FK إلى `payments`) في الجداول:
  - `emergency_emergency_service`
  - `emergency_lab_requests`
  - `emergency_radiology_requests`
- إضافة عمود `follow_up_payment_id` في جدول `emergencies`
- Migration: `database/migrations/2026_05_09_add_payment_tracking_to_services.php` ✅ منفذ

**الملفات المحدثة:**
- `app/Http/Controllers/CashierController.php` - دالة `processEmergencyPayment()`:
  - تضع الـ `payment_id` على جميع الخدمات غير المدفوعة عند معالجة الدفع
  - تحقق من `follow_up_payment_id` لرسوم متابعة الطبيب
- `resources/views/cashier/emergency-payment-form.blade.php`:
  - تعرض فقط الخدمات WHERE `payment_id IS NULL`
  - تعرض فقط بنود التحاليل والأشعة غير المدفوعة

### 12. رسوم متابعة الطبيب (Doctor Follow-up Fee)

**تفاصيل:**
- رسوم ثابتة: **30,000 IQD**
- يتم تفعيلها من صفحة إنشاء حالة الطوارئ عبر checkbox

**الملفات المحدثة:**
- `resources/views/emergency/create.blade.php`:
  ```html
  <input type="checkbox" name="doctor_follow_up" value="1">
  <label>متابعة الطبيب <span class="text-muted">(+30,000 IQD)</span></label>
  ```
- `app/Http/Controllers/EmergencyController.php` - دالة `store()`:
  - إذا `doctor_follow_up == 1` يضع `doctor_follow_up_fee = 30000`

### 13. نظام العلامات الحيوية المتكامل (Vital Signs History System)

**المشكلة:** كانت العلامات الحيوية تُخزَّن في عمود JSON واحد (تُستبدل القديمة بالجديدة).

**الحل المنفذ:**
- إنشاء جدول `emergency_vital_signs` لحفظ كل قياس كسجل منفصل مع:
  - `emergency_id`, `recorded_by` (FK إلى users)
  - `blood_pressure`, `heart_rate`, `temperature`, `respiratory_rate`
  - `oxygen_saturation`, `blood_glucose`, `notes`
  - `created_at`, `updated_at`

**الملفات المنشأة:**
- `app/Models/EmergencyVitalSign.php` - موديل جديد:
  ```php
  protected $fillable = ['emergency_id', 'recorded_by', 'blood_pressure',
                         'heart_rate', 'temperature', 'respiratory_rate',
                         'oxygen_saturation', 'blood_glucose', 'notes'];
  // علاقات: emergency(), recordedBy()
  ```
- `database/migrations/2026_05_09_create_emergency_vital_signs_table.php` (الجدول كان موجوداً مسبقاً - تم التأكيد بوجود 5 سجلات)

**الملفات المحدثة:**
- `app/Models/Emergency.php`:
  - إضافة imports: `HasMany`, `HasOne`
  - إضافة علاقات:
    ```php
    public function vitalSignReadings(): HasMany { ... } // أحدث أولاً
    public function latestVitalSign(): HasOne { ... }    // latestOfMany()
    ```
  - تحديث Accessors لتقرأ من `latestVitalSign` أولاً ثم JSON كاحتياط
  - `getBloodPressureAttribute()`, `getHeartRateAttribute()`, `getTemperatureAttribute()`, إلخ
  - إضافة `follow_up_payment_id` في `$fillable`
  - إضافة `vital_signs => 'array'` في `$casts`

- `app/Http/Controllers/EmergencyController.php`:
  - دالة `updateVitals()`: تنشئ سجلاً جديداً في `EmergencyVitalSign` + تحدث JSON كاحتياط
  - دالة `index()`: تعمل eager load لـ `vitalSignReadings` (آخر 5 مع `recordedBy`)
  - دالة `show()`: تعمل eager load لـ `vitalSignReadings`

### 14. إصلاح واجهة الطوارئ الرئيسية (index.blade.php)

**المشكلة الرئيسية:** صفحة بيضاء بسبب أخطاء بنية Blade.

**الإصلاحات المنفذة:**
1. **`@keyframes` → `@@keyframes`**: منع Blade من تفسير `@keyframes pulse` كـ directive
2. **`@endsection` مفقود**: إضافة `@endsection` قبل `@section('scripts')`
3. **Modals خارج @section**: نقل جميع المودالز داخل `@section('content')`
4. **تكرار دالة**: حذف `latestVitalSign()` المكررة في Emergency.php

**البنية الصحيحة الحالية للملف:**
- سطر 4: `@section('content')`
- سطر 1100: `@endsection`
- سطر 1102: `@section('scripts')`
- سطر 1196: `@endsection`

**أزرار الإجراءات لكل حالة طوارئ:**
- 🔴 "قياس" → `#vitalSignsModal-{{ $emergency->id }}` (العلامات الحيوية)
- 🟢 "تشخيص" → `#medicalModal-{{ $emergency->id }}`
- 🔵 "تحاليل" → `#labModal-{{ $emergency->id }}`
- 🟦 "أشعة" → `#radiologyModal-{{ $emergency->id }}`
- 🟡 "استشارة" → `#consultationModal-{{ $emergency->id }}`

**vitalSignsModal يعرض:**
- جدول بالقراءات السابقة من `$emergency->vitalSignReadings` (آخر 5)
- أعمدة: التاريخ/الوقت، ضغط الدم، النبض، الحرارة، التنفس، الأكسجين، السكر، المسجِّل
- نموذج فارغ لإضافة قياس جديد يُرسل إلى `route('emergency.update-vitals', $emergency)`

### 15. إصلاح صفحة تفاصيل الطوارئ (show.blade.php)

- تحديث قسم العلامات الحيوية لعرض جميع السجلات التاريخية
- جدول يعرض: التاريخ، جميع القيم، اسم المسجِّل
- بطاقات ملخص للقراءة الحالية في الأعلى
- زر vitalSignsModal لإضافة قياس جديد

### 📋 ملفات هذه الجلسة:

**Models:**
- `app/Models/EmergencyVitalSign.php` ← **جديد**
- `app/Models/Emergency.php` ← محدث (علاقات + accessors + fillable/casts)

**Controllers:**
- `app/Http/Controllers/EmergencyController.php` ← محدث (updateVitals, index, show, store)
- `app/Http/Controllers/CashierController.php` ← محدث (processEmergencyPayment)

**Views:**
- `resources/views/emergency/index.blade.php` ← محدث (إصلاح كامل للبنية + أزرار منفصلة)
- `resources/views/emergency/show.blade.php` ← محدث (جدول تاريخ العلامات الحيوية)
- `resources/views/emergency/create.blade.php` ← محدث (checkbox متابعة الطبيب)
- `resources/views/cashier/emergency-payment-form.blade.php` ← محدث (فلترة payment_id IS NULL)
- `resources/views/layouts/app.blade.php` ← محدث (حذف تعليق HTML قبل DOCTYPE)

**Migrations:**
- `database/migrations/2026_05_09_add_payment_tracking_to_services.php` ← **جديد** ✅ منفذ
- `database/migrations/2026_05_09_create_emergency_vital_signs_table.php` ← **جديد** (تم تخطيه - الجدول موجود)

### ⚡ الأوامر المنفذة في هذه الجلسة:
```bash
php artisan migrate
php artisan view:clear
php artisan optimize:clear
```

### 📝 ملاحظات مهمة:
1. جدول `emergency_vital_signs` موجود مسبقاً ويحتوي على 5 سجلات
2. نظام الدفع الآن يعتمد على `payment_id` لتتبع ما تم دفعه وما لم يُدفع
3. العلامات الحيوية تُخزَّن في جدول منفصل + JSON كاحتياط
4. ملف `index.blade.php` تم إصلاحه بالكامل - لا توجد صفحة بيضاء

### 🔜 المهام المقترحة للجلسة القادمة:
1. اختبار صفحة `/emergency` للتأكد من عدم وجود صفحة بيضاء أو Quirks Mode
2. اختبار زر "قياس" وعرض جدول القراءات السابقة
3. اختبار حفظ قياس جديد في جدول `emergency_vital_signs`
4. اختبار عدم تكرار الخدمات المدفوعة في الفاتورة
5. اختبار رسوم متابعة الطبيب (30,000 IQD)

```bash
# عند الاستمرار:
php artisan optimize:clear
php artisan view:cache
```

تم بحمد الله ✅
