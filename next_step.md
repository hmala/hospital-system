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

🎯 خطوات العمل الحالية:
1. ✅ تنفيذ `php artisan migrate`.
2. تنفيذ `php artisan db:seed --class=RolesAndPermissionsSeeder` و `php artisan db:seed --class=SidebarLinkSeeder`.
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
2. بناء صفحة أو وظيفة لإرجاع الكمية من المخزن الرئيسي إلى المورد.
3. إضافة صلاحيات عرض/تنفيذ `stock-transfers.returns` حسب الدور.
4. تطوير API / مسح ضوئي للباركود في الخطوة التالية.

تم بحمد الله ✅
