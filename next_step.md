
أنت الآن جاهز تنقل العمل إلى الحاسبة الثانية بدون مشاكل.
لقد حددنا أن الجلسة هذه كانت تحضيرية، لذا ما تحتاجه هو:

فتح نفس المشروع (hospital-system) على الحاسبة الجديدة.
استخدم نفس الفرع Git (أو نسخة من ملفاتك).
تابع من النقطة التي توقفت عندها: إعداد وظيفة معينة (Controller/Model/Add feature).

🔧 آخر التحديثات المنفذة:
- إضافة بنية مخزن متكاملة للمشتريات: `products`, `suppliers`, `stock_batches`, `stock_movements`, `purchases`.
- تم إنشاء CRUD كامل لمنتجات (ProductController + views) مع توليد `code` تلقائي `PRD-0001`.
- تم إنشاء CRUD بسيط للموردين (SupplierController + views).
- تم إنشاء نموذج مشتريات (PurchaseController@store) مع توليد batches + حركة مخزون audit.
- تحسين واجهة الشريط الجانبي: قسم `إدارة المخزون` منفصل ويظهر الروابط: المواد، الموردون، مشتريات المخزن.
- إضافة دعم `is_perishable` في الشراء: إذا المادة قابلة للتلف => تتطلب `expiry_date`.
- إضافة migration جديدة لحقول أخرى في `products`: `category`, `description`, `reorder_level`, `cost_price`, `selling_price`, `safety_stock`, `storage_conditions`.

🎯 المطلوب عشان تلبسه workflow ثابت:
1. `php artisan migrate` (إذا ما نُفّذ). 
2. `php artisan db:seed --class=RolesAndPermissionsSeeder` + `SidebarLinkSeeder`.
3. (اختياري) `php artisan view:clear` + `php artisan config:clear`.
4. اختبار الواجهات: `/products`, `/suppliers`, `/purchases/create`.
5. نشر حالة stock low (alert_quantity) بفلتر/تقارير لاحقًا.

🔜 المقترح للفعل أولاً (بناءً على السياق)
إنشاء استخدام `stock_availability` أو `inventory_report`:
- يحتسب الكميات الفعلية من `stock_batches.current_qty`.
- ينبه أي مادة انخفضت تحت `alert_quantity`.
- يظهر في صفحة واحدة (Dashboard).

✍️ اكتب use case واضح:
"أريد تقرير المخزون المنخفض مع المواد، الكمية الحالية، مستوى التنبيه، والفرق".
أبدأ العمل في الملفات:
- app/Http/Controllers/InventoryController.php
- resources/views/inventory/low_stock.blade.php
- routes/web.php
- app/Models/StockBatch.php (scope واجراءات وثيقة).
