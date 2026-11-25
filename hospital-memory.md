# Hospital System Memory & Architecture Guide (الذاكرة المركزية للنظام)

> **الغرض من هذا الملف:**  
> يعمل هذا الملف كـ **الذاكرة المستدامة والمهارة المرجعية (Persistent Memory Skill)** لنظام إدارة المستشفى. يُقرأ في بداية كل جلسة عمل أو عند الانتقال بين الأجهزة والحسابات المختلفة لضمان استمرارية التطوير، فهم البنية المعمارية، الالتزام بالمعايير البرمجية، ومعرفة أحدث حالة وصل إليها المشروع بدقة.

---

## 1. نظرة عامة على المشروع (Project Overview)
- **البيئة التقنية الأساسية:** PHP 8.2 / Laravel 12.
- **الواجهة الأمامية (Frontend):** Vite, Blade Templates, Tailwind CSS, Bootstrap 5, Chart.js v4, Laravel Echo & Pusher.
- **قاعدة البيانات:** MySQL / MariaDB (محلياً عبر WAMP: `hospital_pho`، وسحابياً عبر Hostinger: `u175868711_her2020`).
- **نظام الصلاحيات والأدوار:** `spatie/laravel-permission` مع مصفوفة صلاحيات تفصيلية ومحدثة دائماً بـ `RolesAndPermissionsSeeder`.
- **نظام التشغيل المحلي:** Windows (WampServer `C:\get\hospital-system`).
- **قاعدة Git الأساسية الصارمة:** يتم الرفع المباشر (`git push`) حصراً على الفرع الرئيسي `main` دون إنشاء أي فروع جانبية.

---

## 2. الهيكلية المعمارية والمكونات الرئيسية (Architecture & Key Modules)

### 🏥 عيادات الاستشارية ومحطة الطبيب (Doctor Stations & Outpatient Visits)
- مسار دورة المريض: حجز موعد (`Appointment`) ⬅️ تسديد الكشفية لدى الكاشير (`Payment`) ⬅️ دخول طابور الانتظار ⬅️ فحص الطبيب وتسجيل الزيارة (`DoctorVisit`) ⬅️ إصدار وصفة أو طلب فحوصات (`DoctorRequest`).
- **قاعدة حماية كشفية الطبيب:** دفعات كشفية الطبيب مستقلة تماماً ومحمية من التبديل بدفعات الفحوصات اللاحقة. عند إلغاء الحجز من شاشة الطبيب يتم استرجاع كشفية الطبيب فقط دون المساس بالفحوصات الطبية المسددة.

### 💰 الكاشير والمدفوعات (Cashier & Payment System)
- يدعم النظام نوعين رئيسيين من الدفع:
  1. **نقد كامل (Cash):** يدفع المريض كامل المبلغ وتودع في الصندوق مباشرة.
  2. **الضمان الصحي (Health Insurance - HI / Police MOI):** يدفع المريض نسبة تحمل محددة نقداً في الدرج (مثلاً 10% أو مبلغ مقطوع)، ويسجل الباقي كمطالبة وذمة مدينة على هيئة الضمان.
- طباعة الوصولات وسندات القبض المباشرة عبر الطابعات الحرارية وصفحات الطباعة الرسمية.

### 🏛️ الخزينة المركزية والذكاء المالي (Hospital Central Treasury & BI)
- تتبع السيولة النقدية اليومية، واردات وصادرات الصناديق، وحصص الأطباء، ومطالبات الضمان.
- لوحة تحكم تفاعلية متطورة بـ 4 تبويبات:
  1. **التحليل المالي والمخططات:** تبديل لحظي (أعمدة رأسية، أعمدة أفقية، منحنيات مساحية، ومخطط دائري) مع كتابة المبالغ مباشرة على الرسوم ومؤقت تحديث تلقائي ذكي.
  2. **إيرادات الأقسام والصناديق.**
  3. **دفتر الأستاذ والحركات المالية.**
  4. **ذكاء أعمال أطباء الاستشارية ومصفوفة الإحالات (Doctor BI & Referrals Matrix).**

### 🔬 المحطات التشخيصية (Laboratory & Radiology/Ultrasound)
- محطة المختبر وإدخال نتائج التحاليل واعتمادها وطباعتها.
- محطة الأشعة والسونار والإيكو: إدارة الطابور، الاستدعاء الصوتي والمناداة (`call-next`, `recall`, `skip`)، إرفاق الصور، وكتابة وتدقيق التقارير الطبية.
- دعم كامل للمسارات النسبية (`url('/')`) لضمان عمل الاستدعاءات بسلاسة سواء على السيرفر المحلي أو على مسار هوستنجر الفرعي.

### 📦 إدارة المخازن المركزية ومخازن التمريض والعمليات (Inventory, Floor Stores & Nurse Station Consumption)
- **البنية التحتية للمواقع والمخازن (`locations`):**
  * يدعم النظام 22 موقعاً ومستودعاً معرفاً ومفصلاً:
    1. **المخزن الرئيسي (Main Warehouse - Location #1):** المستودع المركزي للمستشفى، يحتوي على مئات الوجبات المخزنية (`inventory_batches`) ومئات الآلاف من المواد والمستلزمات الدوائية والجراحية المسجلة.
    2. **مخازن الأجنحة والطوابق (Floor & Department Stores):** مخازن فرعية مخصصة لكل طابق وجناح رقود (الطابق الثاني، الطابق الرابع، الطابق الخامس، الطابق السادس، مخزن العمليات، الطوارئ، وغيرها).
- **دورة حركة المواد والتغذية (Stock Supply & Replenishment Flow):**
  * **طلبات الصرف والتجهيز (`stock_requests`):** يقوم ممرض الطابق أو مشرف محطة العمليات بطلب المواد والأدوية من المخزن الرئيسي لسد النواقص.
  * **الموافقة والصرف والتعديل:** يراجع أمين المخزن الرصيد المتوفر، ويحق له تعديل الكميات المعتمدة وتدوين أسباب التعديل، وتتحول الحالة إلى `approved` (بانتظار استلام القسم).
  * **تأكيد الاستلام الرسمي وإيداع العهدة (`receiveRequest`):** يقوم ممرض الردهة أو كادر العمليات بفحص المواد ومطابقتها وتأكيد الاستلام عبر زر مخصص (`#receiveStockCard`) وتتحول الحالة إلى `completed` مع توثيق `received_by` و `received_at` و `received_notes`.
- **محطة التمريض والعمليات السريرية (`/surgery-stations/clinical/{id}`):**
  * **الاستهلاك السريري (`SurgeryConsumption` / `SurgeryItem`):** أثناء إجراء العملية الجراحية أو مكوث المريض في الرقود، يقوم الكادر التمريضي بتسجيل المواد والأدوية المستهلكة فعلياً للمريض.
  * **قاعدة الخصم المخزني الدقيق:** يُخصم استهلاك المريض مباشرة من رصيد الوجبات الموجودة في **مخزن الطابق أو العمليات الفرعي** (وليس من المخزن الرئيسي مباشرة)، مما يضمن:
    1. انضباط عهدة التمريض لكل طابق ومطابقتها الجردية اللحظية.
    2. تتبع ربحية وتكلفة كل عملية جراحية بالمواد المسحوبة على ملف المريض.
    3. إمكانية محاسبة الكاشير للمواد الإضافية غير المشمولة تلقائياً أو عبر فواتير الخدمات الإضافية.

### 📡 سجل الحركات والنبض اللحظي (Staff Activity Logs & Live Radar)
- تسجيل نشاط الكادر التشغيلي عبر `UserActivityLogger` ومراقبي النماذج (`Observers`) والميدلوير `TrackUserActivity`.
- استبعاد عمليات تسجيل الدخول والخروج من احتساب الإنتاجية لحصرها في العمليات والإدخالات الفعلية.
- رادار لحظي وتحديث صامت بدون إعادة تحميل الصفحة.

### 🔄 محرك مزامنة قاعدة البيانات (Database Sync Service)
- مزامنة سحابية بنقرة واحدة عبر لوحة النظام أو عبر أمر التيرمينال: `php artisan db:pull-hostinger`.
- أخذ نسخة احتياطية محلية تلقائياً في `storage/app/backups` قبل أي عملية سحب أو استبدال للجداول.

---

## 3. المعايير البرمجية وقواعد الكود (Coding Standards)
1. **العملة والأرقام:** العملة الرسمية هي الدينار العراقي (IQD). تُخزن المبالغ كأرقام عددية صحيحة أو عشرية نقية في قاعدة البيانات بدون نصوص.
2. **الروابط واستدعاءات الـ AJAX:** لا تستخدم مطلقاً مسارات مطلقة صلبة تبدأ بـ `/` في نصوص JavaScript داخل Blade. استخدم دائماً دوال لارافيل مثل `{{ url('/') }}` أو `{{ route(...) }}` لضمان التوافق التام مع هوستنجر واللوكل.
3. **الاختبارات الآلية:** أي ميزة مالية أو منطقية جديدة يجب أن تُختبر باختبارات ميزة آلية في مجلد `tests/Feature/` واجتياز `php artisan test`.
4. **تعديل الملفات:** الحفاظ على هيكلية الكود الأصلي، وتجنب لمس مجلد `vendor/` نهائياً.
5. **الذاكرة والتوثيق:** تحديث سجل العمليات باستمرار في `AGENTS.md` وقسم الحالة الحالية في هذا الملف.
6. **الشرح المسبق وتحديد أماكن الأكواد قبل التنفيذ:** يمنع منعاً باتاً كتابة أو تعديل أي كود برمجي قبل توضيح الآلية والمنطق ومسارات الملفات وأماكن الأكواد المعنية ومناقشتها وأخذ الموافقة الصريحة أولاً.

---

## 4. الحالة الحالية للمشروع (Current Project State)

- **المرحلة 291 (2026-11-25 — `main`) — تطوير المنظومة السريرية والمخزنية لقسم الخدج والحاضنات: مخزن وعُهدة الخدج المربوط بالمخزن الرئيسي بدون أثر مالي، دليل الأدوية المضافة للخدج، عداد أيام العلاج (`Day X of Y`)، باقات المفضلات السريعة العامة والخاصة، وخانة الملاحظات السريرية الموسّعة (NICU Store & Custody Governance, Custom NICU Medicines Directory, Therapy Day Counter, Isolated NICU Favorite Medicine Packs, & Expanded Clinical Notes)**:
  * **مخزن وعُهدة قسم الخدج المربوط بالمخزن الرئيسي ([app/Http/Controllers/NicuStoreController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuStoreController.php), [resources/views/nicu-store/index.blade.php](file:///C:/get/hospital-system/resources/views/nicu-store/index.blade.php), [resources/views/nicu-store/show.blade.php](file:///C:/get/hospital-system/resources/views/nicu-store/show.blade.php))**:
    - لوحة مستقلة لمخزن وعُهدة الخدج (`Location #18`) مرتبطة مباشرة بالمخزن الرئيسي (`Location #1`) عبر أزرار طلب التعزيز وإرجاع الفائض وسجل الطلبيات (`stock-transfers.*`).
    - إضافة تبويب `صرفيات ومستلزمات المخزن` داخل ملف الطفل ([resources/views/nicu/show.blade.php](file:///C:/get/hospital-system/resources/views/nicu/show.blade.php)) مع دالة `dispenseStoreItem()` في [NicuController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuController.php) لخصم الكميات من `stock_batches` وتوثيق `stock_movements.incubator_reservation_id` **كتوثيق عهدة فقط بدون أي أثر مالي** على فاتورة كاشير الخدج.
    - إنشاء صلاحية `manage nicu store` وربطها بأدوار الخدج والإدارة في [RoleManagementController.php](file:///C:/get/hospital-system/app/Http/Controllers/RoleManagementController.php) والميجريشن [2026_11_25_120000_add_nicu_store_meds_days_favorites_and_notes.php](file:///C:/get/hospital-system/database/migrations/2026_11_25_120000_add_nicu_store_meds_days_favorites_and_notes.php).
  * **دليل الأدوية والتركيبات المضافة بالخدج ([app/Models/NicuCustomMedicine.php](file:///C:/get/hospital-system/app/Models/NicuCustomMedicine.php), [app/Http/Controllers/NicuCustomMedicineController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuCustomMedicineController.php), [resources/views/nicu/custom-medicines/index.blade.php](file:///C:/get/hospital-system/resources/views/nicu/custom-medicines/index.blade.php))**:
    - حفظ تلقائي لأي دواء يكتبه الطبيب بالوصفة وغير موجود في جدول الصيدلية `medicines` داخل جدول `nicu_custom_medicines` المستقل (دون تلويث جرد الصيدلية المتعاقدة).
    - ظهوره فوراً في البحث التلقائي للمرات اللاحقة بشارة `🍼 مضاف بالخدج` مع تعبئة تلقائية للجرعة والمسار والتكرار والأيام الافتراضية، وواجهة إدارة متكاملة تحت إعدادات الخدج.
  * **عداد أيام استخدام الدواء (`Day X of Y` / `Day X`) ([app/Models/Concerns/HasTherapyDayCounter.php](file:///C:/get/hospital-system/app/Models/Concerns/HasTherapyDayCounter.php), [app/Models/NicuMedicalOrder.php](file:///C:/get/hospital-system/app/Models/NicuMedicalOrder.php), [app/Models/NicuCareSchedule.php](file:///C:/get/hospital-system/app/Models/NicuCareSchedule.php))**:
    - إضافة حقول `duration_days`, `started_at`, `stopped_at`, `stopped_by` مع أزرار سريعة (3، 5، 7، 10، مستمر) في نموذج الوصفة، وعرض شارات ذكية (`Day 1 of 3`، تحذير اليوم الأخير، تجاوز المدة ⚠️، والمستمر `Day X`) مع زر `إيقاف/استئناف` لتثبيت العداد.
  * **باقات الأدوية السريعة والمفضلات للخدج بنمط الاستشارية ([app/Http/Controllers/UserMedicineGroupController.php](file:///C:/get/hospital-system/app/Http/Controllers/UserMedicineGroupController.php), [app/Models/UserMedicineGroup.php](file:///C:/get/hospital-system/app/Models/UserMedicineGroup.php), [app/Models/UserMedicineGroupCustomItem.php](file:///C:/get/hospital-system/app/Models/UserMedicineGroupCustomItem.php))**:
    - عزل باقات الخدج عبر `user_medicine_groups.department = 'nicu'` مع جدول `user_medicine_group_custom_items` لدعم الأدوية المضافة داخل الباقات.
    - إتاحة باقات **عامة لقسم الخدج** وباقات **خاصة بكل طبيب خدج أو مقيم** دون تقييد بعدد محدد من الأدوية أو الباقات، مع شريط التثبيت بالصدارة ⭐ والترتيب بالاستخدام 🔥 وزر «حفظ كباقة سريعة» وإدارة الباقات في [resources/views/medicines/groups/index.blade.php](file:///C:/get/hospital-system/resources/views/medicines/groups/index.blade.php) و [edit.blade.php](file:///C:/get/hospital-system/resources/views/medicines/groups/edit.blade.php).
  * **خانة الملاحظات السريرية الموسّعة في «تسجيل قراءة سريرية وتمريضية» ([app/Models/NicuVital.php](file:///C:/get/hospital-system/app/Models/NicuVital.php), [app/Http/Controllers/NicuController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuController.php))**:
    - إضافة عمود `clinical_notes` (`TEXT`)، فتح صلاحية التسجيل لأطباء الخدج والمقيمين (`nicu_doctor`, `doctor`, `resident`)، توفير مربع كتابة واسع مع زرين (**حفظ القراءة الكاملة** و**حفظ الملاحظة السريرية فقط**) وخيار تثبيت الملاحظة برأس ملف الطفل، وإظهار الملاحظات بجدول القراءات.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/NicuEnhancementsTest.php](file:///C:/get/hospital-system/tests/Feature/NicuEnhancementsTest.php))**:
    - اجتياز `52 passed (426 assertions)` عبر `NicuEnhancementsTest`, `UserMedicineGroupTest`, `NicuTabAndStatePersistenceTest`, `NicuGranularPermissionsAndMilkTypesTest`, `SurgeryStoreGovernanceTest`, `NursingSubStoreGovernanceTest`, و `RolePermissionsMatrixTest`، وتوافق تام مع `Laravel Pint` وتجميع قوالب `Blade`.

- **المرحلة 290 (2026-11-25 — `main`) — التعديل اللحظي المباشر لأسعار الكاش (العلبة والشريط) داخل جدول دليل الأدوية مع الحفظ التلقائي والاشتقاق الذكي (Medicines Directory Inline Quick-Pricing & Auto-Save Derivation Engine)**:
  * **المسار السريع وصلاحيات التعديل ([routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
    - إضافة مسار: `PATCH /pharmacy/medicines/{medicine}/quick-price` باسم `pharmacy.medicines.quick-price`.
    - تقييد المسار بصلاحيات `edit medicines` وأدوار الإدارة وكادر الصيدلية عبر `role_or_permission`.
  * **متحكم الأسعار السريعة ([app/Http/Controllers/Pharmacy/MedicineController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/MedicineController.php))**:
    - إضافة دالة `updateQuickPrice(Request $request, Medicine $medicine)`.
    - التحقق الصارم من الأرقام الموجبة `min:0` للعلبة والشريط.
    - اشتقاق سعر الشريط تلقائياً في حال إهماله: `round($salePrice / max(1, $medicine->sub_units_count), 2)`.
    - إرجاع رد JSON فوري بالأرقام المحدثة وصيغتها المعربة.
  * **الواجهة التفاعلية والاشتقاق والتنسيق المتناسق للصفوف ([resources/views/pharmacy/medicines/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/medicines/index.blade.php))**:
    - تحويل نصوص أسعار العلبة والشريط لكادر الصيدلية إلى حقول إدخال رقمية صغيرة وأنيقة.
    - فصل أسعار الكاش عن سعر الضمان في أعمدة متناسقة: عمود مستقل لسعر العلبة (كاش)، وعمود مستقل لسعر الشريط (كاش)، وعمود مخصص لسعر الضمان الصحي الحكومي.
    - ضبط المحاذاة الرأسية والأفقية (Baseline Alignment) بدقة لمنع أي زكزاك أو تفاوت في ارتفاعات الأسطر.
    - دمج شارات الاشتراطات الطبية (OTC، وصفة طبية، رقابي) بأناقة تحت اسم الدواء.
    - اشتقاق فوري بالمتصفح لسعر الشريط بمجرد كتابة سعر العلبة استناداً إلى معامل تعبئة الدواء (`sub_units_count`).
    - حفظ تلقائي بـ AJAX عند الضغط على Enter أو الخروج من الحقل (Blur) دون أي إعادة تحميل للصفحة.
    - وميض أخضر تفاعلي وظهور شارة `✓ تم الحفظ` الفورية، مع تنبيه بالخطأ في حال إدخال قيم سالبة.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/MedicineQuickPricingTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/MedicineQuickPricingTest.php))**:
    - بناء واجتياز اختبارات التعديل السريع، الاشتقاق التلقائي، رفض القيم السالبة (422)، منع غير المصرحين (403)، وظهور الحقول التفاعلية بنسبة 100% (`5 passed, 15 assertions`).
    - اجتياز كامل حزمة اختبارات الصيدلية (`33 passed, 208 assertions`)، وتوافق تام مع `Laravel Pint`.

- **المرحلة 289 (2026-11-25 — `main`) — تصحيح ومزامنة وجبات المخزن المرتبطة بفواتير المشتريات ومنع تكرار المواد بقوائم التوريد (Purchase Stock Batches Alignment & Duplicate Items Prevention Engine)**:
  * **معالجة التزحيف والانزياح في قاعدة البيانات بالميجريشن ([database/migrations/2026_11_25_100000_sync_stock_batches_with_purchase_items.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_11_25_100000_sync_stock_batches_with_purchase_items.php))**:
    - تشغيل استعلام تصحيحي لمزامنة `stock_batches.product_id = purchase_items.product_id` عبر كامل قاعدة البيانات.
    - معالجة تزحيف الـ 29 مادة المنزاحة في الفاتورة رقم 7 (مثل الكانيولات والكاونات)، وعودة رصيد كل مادة في المخزن ليتطابق بنسبة 100% مع قائمة المشتريات.
  * **حماية منع تكرار المواد في شاشات المشتريات ([app/Http/Controllers/PurchaseController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/PurchaseController.php))**:
    - إضافة التحقق الصارم في الـ Backend بـ `store` و `update`: `'items.*.product_id' => 'required|distinct|exists:products,id'`.
    - رسالة خطأ واضحة باللغة العربية: `لا يمكن تكرار نفس المادة في الفاتورة أكثر من مرة. يرجى تعديل الكمية في السطر الأصلي بدلاً من تكرارها`.
  * **التنبيه التفاعلي الفوري بالواجهات ([resources/views/purchases/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/purchases/create.blade.php), [resources/views/purchases/edit.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/purchases/edit.blade.php))**:
    - بمجرد اختيار مادة مضافة مسبقاً في سطر آخر، يقوم المتصفح فوراً بتنبيه المستخدم برقم السطر الأصلي وتفريغ الحقل المكرر.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/PurchaseDuplicateAndBatchSyncTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PurchaseDuplicateAndBatchSyncTest.php))**:
    - بناء واجتياز اختبارات رفض التكرار ومزامنة الوجبات بنسبة 100% (`2 passed, 12 assertions`)، وتوافق تام مع `Laravel Pint`.

- **المرحلة 288 (2026-11-25 — `main`) — الربط الآلي المقفل لتصنيف المريض التأميني من الاستعلامات بنقطة البيع والصرف والتسعير المزدوج للصيدلية المتعاقدة (Contracted Pharmacy Dual Cash/Insurance POS Engine & Automated Reception Patient Profile Lock)**:
  * **إلغاء التحديد اليدوي وقفل الوضع المالي من الاستعلامات ([resources/views/pharmacy/pos/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/pos/index.blade.php))**:
    - حذف القوائم المنسدلة لاختيار الضمان من شاشة الصيدلية (الصيدلية لا تحدد ولا تختار نوع المريض).
    - الربط الآلي المقفل ببيانات حجز المريض من الاستعلامات (`insurance_type`, `health_insurance_category_id`, `copay_percentage`).
    - تثبيت شارة رسمية مقفلة للمريض: `🔒 معتمد ومسجل بالاستعلامات: ضمان صحي (فئة X - استقطاع Y%)` أو `🔒 مسجل بالاستعلامات: نقدي كاش (100% نقداً)`.
    - إبقاء البيع المباشر OTC لحالات زبائن الشارع غير المسجلين كمرضى بالمستشفى.
  * **إبراز تصنيف الاستعلامات بطابور الوصفات الواردة ([app/Http/Controllers/Pharmacy/PharmacyPosController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/PharmacyPosController.php))**:
    - تحديث `pendingPrescriptions()` و `getPrescriptionDetails()` لتضمين شارات تصنيف الاستعلامات والتأمين آلياً.
  * **التسعير المزدوج واشتقاق أسعار الأشرطة ([app/Models/Medicine.php](file:///c:/wamp64/www/hospital-system/app/Models/Medicine.php))**:
    - إضافة ميثود `getHiSubUnitPriceAttribute()` لحساب سعر شريط الضمان بدقة.
    - مريض الضمان: تطبيق جدول التعاقد الحكومي (`hi_price`) والخصم وفق فئة استقطاع الاستعلامات.
    - مريض الكاش والبيع المباشر: تطبيق أسعار الصيدلية المتعاقدة (`sale_price`, `sub_unit_sale_price`).
    - المواد غير المشمولة بالضمان تُحمّل كاش للمريض مباشرة مع شارة توضيحية.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/PharmacyPosTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacyPosTest.php))**:
    - اجتياز كامل حزمة الاختبارات الآلية بنجاح (`7 passed, 36 assertions`)، وتوافق تام مع `Laravel Pint`.

- **المرحلة 280 (2026-11-24 — `main`) — لوحة القيادة المستقلة لرقابة مخازن وعُهد صالات العمليات الجراحية وإدارة أمناء العهد وتتبع صرفيات العمليات (Operation Theaters & Main Surgery Store Governance Dashboard, Custody Tracking & Clinical Consumption Auditing)**:
  * **الصلاحيات والعهدة الافتراضية بالميجريشن ([database/migrations/2026_11_24_100000_create_surgery_store_governance.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_11_24_100000_create_surgery_store_governance.php))**:
    - إنشاء صلاحية `manage surgery store` وربطها تلقائياً بدور كادر العمليات `surgery_staff` وأدوار الإدارة (`admin`, `admin-hsop`, `hospital_admin`, `super_admin`).
    - تعيين التكليف الأولي التلقائي لعهدة مخزن العمليات الرئيسي (`Location #25`) وصالات العمليات (`Location #3`) لأمين العهدة وموظف العمليات المسؤول (المستخدم 92 `surgery@hospital.com`) وتوثيق الأرشيف الرقابي في `location_custodian_histories`.
  * **التحكم والمسارات السريرية الجراحية ([app/Http/Controllers/SurgeryStoreController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgeryStoreController.php), [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
    - حصر المخازن والصالات برمجياً بالمواقع الجراحية (`مخزن عمليات الرئيسي` و `صالات العمليات`) واستبعاد صالات الولادة وردهات التمريض والمخازن الإدارية.
    - دالة `index()`: إحصائيات علوية KPIs (المخازن والصالات، إجمالي المواد بالرصيد، حركات صرفيات العمليات للشهر الحالي، العمليات الجراحية المجدولة لليوم، وطلبيات التعزيز المعلقة).
    - دالة `show()`: استعراض تفاصيل الموقع بـ 4 تبويبات تفاعلية (الأرصدة والوجبات المتوفرة، سجل صرفيات العمليات وربطها بالجراح والمريض ونوع العملية، أرشيف تسليم واستلام العهدة، وطلبيات تعزيز المخزن).
    - دالة `assignCustodian()`: تكليف وتبديل أمين العهدة وتحديث موقعه وتوثيق الأرشيف.
  * **الواجهات التفاعلية والقائمة الجانبية ([resources/views/surgery-store/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-store/index.blade.php), [resources/views/surgery-store/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-store/show.blade.php), [resources/views/layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php))**:
    - بطاقات KPIs علوية، جدول مخازن وصالات العمليات مع شارات المسؤولية والأرصدة، وجدول تتبع العمليات الجراحية لليوم للتجهيز المسبق للمستلزمات.
    - نافذة Modal لتكليف وتبديل أمين العهدة مع خيار مزامنة موقع الموظف تلقائياً.
    - إضافة رابط بالقائمة الجانبية تحت قسم العمليات الجراحية: "مخزن وعُهد صالات العمليات" مرتبط بصلاحية `manage surgery store` ودور `surgery_staff`.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/SurgeryStoreGovernanceTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryStoreGovernanceTest.php))**:
    - بناء واجتياز اختبارات الوصول، الصلاحيات، استبعاد المواقع غير الجراحية، تكليف وتبديل المسؤول، وعرض الأرصدة وسجل استهلاك العمليات بنسبة 100% (`4 passed, 21 assertions`).
    - اجتياز اختبارات `NursingSubStoreGovernanceTest` (`4 passed, 23 assertions`) و `FloorCustodianStockTransferTest` (`13 passed, 95 assertions`)، وتوافق تام مع `Laravel Pint`.

- **المرحلة 279 (2026-11-24 — `main`) — إضافة صلاحيات مستقلة للتحكم بواجهة وإدارة مساعدي الجراحين والتخدير عبر مصفوفة الأدوار والصلاحيات (Independent Granular Permissions for Surgical Assistants Directory & Roles Governance)**:
  * **الصلاحيات المستقلة والميجريشن ([database/migrations/2026_11_24_100000_add_surgical_assistants_permissions.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_11_24_100000_add_surgical_assistants_permissions.php), [database/seeders/RolesAndPermissionsSeeder.php](file:///c:/wamp64/www/hospital-system/database/seeders/RolesAndPermissionsSeeder.php))**:
    - إنشاء صلاحيتين معتمدتين بـ Spatie: `view surgical assistants` (عرض دليل وسجل مساعدي الجراحين والتخدير) و `manage surgical assistants` (إدارة وإضافة وتعديل وحذف).
    - منحهما تلقائياً لأدوار الإدارة العليا (`admin`, `hospital_admin`, `admin-hsop`) وكادر العمليات (`surgery_staff`).
  * **إدارة الأدوار والصلاحيات ([app/Http/Controllers/RoleManagementController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RoleManagementController.php))**:
    - إدراج الصلاحيتين ضمن تصنيف `surgeries` لتظهرا بمربعات اختيار في شاشة إنشاء وتعديل الأدوار (`/roles`).
  * **حماية الواجهات والكنترولر ([app/Http/Controllers/SurgicalAssistantController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgicalAssistantController.php), [resources/views/layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php))**:
    - ربط `index()` بصلاحية `view surgical assistants` أو `manage surgical assistants` مع وضع العرض فقط (`$canManage = false`) عند امتلاك صلاحية العرض دون الإدارة.
    - تقييد دوال `store()`, `update()`, `toggleStatus()`, `destroy()` بصلاحية `manage surgical assistants`.
    - تحديث شرط القائمة الجانبية: `@canany(['view surgical assistants', 'manage surgical assistants', 'view surgeries', 'edit surgeries'])`.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/SurgicalAssistantTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
    - بناء واجتياز اختبارات العرض، الإدارة، ومنع غير المصرحين (403) بنسبة 100% (`10 passed, 35 assertions`)، وتوافق تام مع `Laravel Pint`.

- **المرحلة 276 (2026-11-23 — `main`) — البحث الفوري اللحظي والتصفية الذكية في أرشيف جميع زيارات الطبيب والزيارات المعلقة بدون أزرار (Instant Zero-Click Real-Time Search & Multi-Filter for Doctor Visits Archive & Incomplete Visits)**:
  * **الواجهة التفاعلية الفورية بدون أزرار ([resources/views/doctors/visits/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/index.blade.php))**:
    - بناء شريط بحث وفلترة لحظي أعلى جدول أرشيف جميع الزيارات: حقل بحث نصي سريع، منسدلة الحالات (مكتملة، غير مكتملة/معلقة، جاري الفحص، بالانتظار، ملغية)، وفلاتر التاريخ (من/إلى)، وزر تفريغ ومسح فوري.
    - تنفيذ دالة JavaScript لحظية `applyArchiveFilters()` تعمل فورياً على حدثي `input` و `change` لتصفية الصفوف لحظياً بالمتصفح بدون الحاجة لأي زر بحث وبدون إعادة تحميل الصفحة.
    - إضافة حقل تصفية سريع للزيارات المعلقة في تبويب الزيارات المعلقة `#incomplete-pane`.
    - ربط بيانات كل صف بـ `data-search-text` و `data-status` و `data-date` مع إظهار عداد النتائج المطابقة لحظياً وإظهار رسالة عند عدم وجود تطابق.
    - دعم حفظ ومزامنة التبويبات تلقائياً عبر URL Query (`?tab=archive`) وتحديث history state عند التبديل.
  * **توسيع استعلام الأرشيف بالباك إند ([app/Http/Controllers/DoctorVisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorVisitController.php))**:
    - رفع سقف استعلام الأرشيف إلى 500 زيارة لضمان توفر كافة الزيارات الحديثة والسابقة فورياً بذاكرة المتصفح.
    - دعم معاملات البحث السيرفرية (`search`, `status`, `date_from`, `date_to`) للبحث في الاسم، الهاتف، الرقم الوطني، رقم الإضبارة/المعرف، والشكوى السريرية.
  * **القاموس والاختبارات الآلية ([lang/en.json](file:///c:/wamp64/www/hospital-system/lang/en.json), [tests/Feature/DoctorVisitsArchiveSearchTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DoctorVisitsArchiveSearchTest.php))**:
    - إضافة وتوثيق 10 مفاتيح ترجمة إنجليزية جديدة بالقواميس.
    - بناء واجتياز اختبار الميزة `DoctorVisitsArchiveSearchTest` بنسبة 100% (`1 passed, 22 assertions`)، وتنسيق الأكواد بـ `Laravel Pint`.

- **المرحلة 275 (2026-11-22 — `main`) — الترقية التلقائية لحساب رئيس/مشرف التمريض في بيئة الإنتاج ومنح الصلاحيات الإشرافية عبر ميجريشن مخصصة (Automated Head Nurse Supervisor Role Promotion & Permission Synchronization Migration)**:
  * **السبب الجذري للمشكلة في هوستنجر:**
    - في بيئة سيرفر هوستنجر (`hinpa.icu`)، حساب المستخدم (عبدالله الحسين `abdullahalhussen247@gmail.com`) تم إنشاؤه مسبقاً قبل إضافة دور `head_nurse`، فكان مربوطاً في جدول `model_has_roles` برقم الدور `11` (`nurse`).
    - عند تشغيل الميجريشن بالمرحلة 273، تم إنشاء دور `head_nurse` في جدول `roles` فقط، لكن أمر الميجريشن لا يعدل تلقائياً سجلات المستخدمين القديمة في جدول `model_has_roles`.
    - بالنتيجة، ظل الحساب مسجلاً بدور `nurse` فظهر في واجهة المستخدمين `/users` بشارة `كادر تمريضي`، وظهر في جدول دوام التمريض.
  * **الحل البرمجي عبر ميجريشن مخصصة ([database/migrations/2026_11_22_133000_promote_head_nurse_supervisor_user.php](file:///C:/get/hospital-system/database/migrations/2026_11_22_133000_promote_head_nurse_supervisor_user.php))**:
    - إنشاء ميجريشن فورية تنفذ عند أمر `php artisan migrate --force` بالسيرفر لتقوم تلقائياً بترقية الحساب (`abdullahalhussen247@gmail.com` أو أي حساب بمسمى مشرف/رئيس التمريض) إلى دور `head_nurse` حصراً في `model_has_roles` وتحديث حقل `users.role = 'head_nurse'`.
    - مسح كاش الصلاحيات تلقائياً `forgetCachedPermissions()` وتثبيت الصلاحيات `manage nurse availability` و `view nursing station`.
  * **التحقق والاختبارات الآلية**:
    - تنفيذ الميجريشن بنجاح واجتياز اختبارات `NurseAvailabilityTest` بنسبة 100% (`11 passed, 46 assertions`)، وفحص `Laravel Pint`.

- **المرحلة 274 (2026-11-22 — `main`) — فصل رئيس ومشرف التمريض (Head Nurse) عن قائمة التمريض المجدول ومنحه الصلاحيات الإشرافية الكاملة (Segregate Head Nurse Supervisor from Staff Nurse Duty Scheduling & Authorize Full Supervisory Governance)**:
  * **عزل المشرف عن جدول دوام الممرضين ([app/Http/Controllers/NurseAvailabilityController.php](file:///C:/get/hospital-system/app/Http/Controllers/NurseAvailabilityController.php), [resources/views/nurse-availability/index.blade.php](file:///C:/get/hospital-system/resources/views/nurse-availability/index.blade.php))**:
    - تصحيح استعلام كادر التمريض `$nursesQuery` في `index()` و `bulkUpdate()` باستبعاد دور `head_nurse` وأدوار الإدارة نهائياً عبر `whereDoesntHave('roles', fn($rq) => $rq->whereIn('name', ['head_nurse', 'admin', 'admin-hsop', 'hospital_admin', 'super_admin']))`.
    - إزالة شارة مشرف التمريض من جدول التمريض التابع، بحيث لا يظهر رئيس/مشرف التمريض كممرض يُفتح ويُغلق دوامه بالمفتاح التفاعلي بل يدير الجدول كمشرف ومسؤول.
  * **حصانة حساب مشرف التمريض بالنظام ([app/Models/User.php](file:///C:/get/hospital-system/app/Models/User.php))**:
    - تحديث دالة `isNurseAvailableToday()` لإعادة `true` فورياً لأي مستخدم يحمل دور `head_nurse` أو أحد الأدوار الإدارية، لمنع حجب المشرف أو توجيهه لشاشة خارج الدوام مهما كانت حالته بقاعدة البيانات أو في حال وجود بقايا قديمة لحقل `role = nurse`.
  * **تثبيت الصلاحيات بالميجريشن ([database/migrations/2026_11_22_120000_ensure_nurse_roles_exist_in_roles_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_22_120000_ensure_nurse_roles_exist_in_roles_table.php))**:
    - ربط صلاحيتي `manage nurse availability` و `view nursing station` تلقائياً بدور `head_nurse` عند تنفيذ الميجريشن بالسيرفر لضمان قدرته على التحكم الكامل بكادر التمريض ومحطة التمريض.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/NurseAvailabilityTest.php](file:///C:/get/hospital-system/tests/Feature/NurseAvailabilityTest.php))**:
    - إضافة فحص يؤكد عدم ظهور حساب مشرف التمريض ضمن جدول كادر التمريض للمدير والمشرف، واجتياز كامل اختبارات دوام التمريض بنسبة 100% (`11 passed, 44 assertions`) واجتياز فحص `Laravel Pint`.

- **المرحلة 273 (2026-11-22 — `main`) — حل استثناء فقدان دور تمريض الخدج والعناية (RoleDoesNotExist: nicu_nurse) وحصانة استعلامات جدول توفر التمريض (Immunity for Nurse Availability Query against Missing Roles & Automated Role Seeding Migration)**:
  * **السبب الجذري للمشكلة:**
    - عند استدعاء `$q->role(['nurse', 'nicu_nurse', 'icu_nurse', 'head_nurse'])` في `NurseAvailabilityController`، تقوم حزمة Spatie بالبحث الإجباري عن كل اسم دور في جدول `roles` عبر `Role::findByName()`. في بيئة الإنتاج (Hostinger `hinpa.icu`) لم تكن الأدوار الجديدة منشأة بعد في قاعدة البيانات، مما أدى لانهيار الصفحة بخطأ 500 (`There is no role named 'nicu_nurse' for guard 'web'`).
  * **الحل البرمجي الوقائي في الكنترولر ([app/Http/Controllers/NurseAvailabilityController.php](file:///C:/get/hospital-system/app/Http/Controllers/NurseAvailabilityController.php))**:
    - استبدال استدعاء `$q->role(...)` المباشر باستعلام العلاقات المعياري المتين:
      ```php
      $q->whereHas('roles', function ($rq) {
          $rq->whereIn('name', ['nurse', 'nicu_nurse', 'icu_nurse', 'head_nurse']);
      })->orWhere('role', 'nurse');
      ```
    - هذا الاستعلام يعتمد على SQL Subquery خالص (`WHERE EXISTS`) ويعمل بمرونة وحصانة 100% حتى لو كان أحد الأدوار غير موجود بجدول الأدوار دون إطلاق أي استثناء أو توقف للنظام.
  * **ميجريشن التوليد التلقائي للأدوار السريرية ([database/migrations/2026_11_22_120000_ensure_nurse_roles_exist_in_roles_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_22_120000_ensure_nurse_roles_exist_in_roles_table.php))**:
    - إنشاء ميجريشن تنفذ تلقائياً عند تشغيل `php artisan migrate` في السيرفر وتنشئ الأدوار السريرية المفقودة (`nurse`, `nicu_nurse`, `icu_nurse`, `head_nurse`, `anesthesia`) بجدول `roles` مع مسح الكاش تلقائياً.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/NurseAvailabilityTest.php](file:///C:/get/hospital-system/tests/Feature/NurseAvailabilityTest.php))**:
    - إضافة اختبار ميزة مخصص `test_nurse_query_does_not_throw_when_role_does_not_exist_in_database` يحاكي حذف دور `nicu_nurse` من قاعدة البيانات ويؤكد عمل صفحة إدارة توفر التمريض بنجاح تام (HTTP 200).
    - اجتياز كامل اختبارات دوام التمريض بنسبة 100% (`11 passed, 42 assertions`) واجتياز فحص `Laravel Pint`.

- **المرحلة 272 (2026-11-21 — `main`) — توحيد واجهة كاشير الطوارئ مع باقي الأقسام بإضافة خيارات [تسديد / تأجيل / إلغاء] لكل خدمة والتحكم المباشر بالأسعار وإعادة احتساب الفاتورة لحظياً (Unify Emergency Cashier Payment with Segmented Action Pills [Pay/Postpone/Cancel], Direct In-Line Price Overrides, Real-Time Recalculation, and Split Payment Handling)**:
  * **الواجهة التفاعلية وإعادة هندسة جدول البنود ([resources/views/cashier/emergency-payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/emergency-payment-form.blade.php))**:
    - إضافة أزرار الإجراءات المقسمة (Segmented Control Pills) `[تسديد / تأجيل / إلغاء]` لكل بند (خدمات طوارئ، تحاليل، أشعة، استشارات، ورسوم متابعة) مع ألوان واضحة (أخضر للتسديد، برتقالي للتأجيل، أحمر للإلغاء).
    - إضافة حقل تعديل السعر المعتمد مباشرة في الجدول لكل خدمة، مما يمنح الكاشير مرونة كاملة لتعديل الأسعار حسب الحالة أو التوجيه.
    - إضافة قائمة منسدلة لتحديد سبب الإلغاء أو التأجيل (عدم رغبة المريض، قلة السيولة، ضيق الوقت، أو بتوجيه الطبيب).
    - إضافة شريط شارات إحصائي أعلى الجدول يوضح عدد البنود المسددة، المؤجلة، والملغاة لحظياً.
    - إضافة دوال JavaScript تفاعلية (`handleItemActionChange`, `handleItemPriceChange`, و `recalculateEmergencyDiscount`) لإعادة احتساب الإجماليات، حصة المريض، حصة الضمان، والخصومات تلقائياً عند تغيير الإجراء أو السعر دون إعادة تحميل الصفحة.
    - تحويل زر التأكيد ديناميكياً بحسب الإجراءات (تأكيد القبض، حفظ وتأجيل الخدمات، أو تأكيد الإلغاء).
  * **معالجة العمليات في الباك إند ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
    - قراءة مصفوفات `item_actions` و `item_custom_prices` و `item_reasons`.
    - معالجة بنود الإلغاء: حذف خدمة الطوارئ من جدول الربط، إلغاء طلبات المختبر والأشعة والاستشارات وتصفير رسوم المتابعة.
    - معالجة بنود التأجيل: فصلها عن الدفعة الحالية وإبقاؤها معلقة ليتم دفعها لاحقاً.
    - معالجة بنود التسديد: اعتماد الأسعار المعدلة واحتساب الحصص والخصومات بدقة وربطها بالدفعة وتوليد قيود إيرادات الاستشارة 100% للطبيب.
    - في حال تأجيل أو إلغاء كامل البنود دون تحصيل نقدي: إلغاء الدفعة الفارغة وتحديث حالة الطوارئ دون أخطاء.
    - في حال بقاء خدمات مؤجلة بعد تسديد جزئي: إعادة توليد إشعار دفع جديد للخدمات المتبقية تلقائياً عبر `upsertUnifiedEmergencyPayment`.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/EmergencyConsultationPaymentTest.php](file:///C:/get/hospital-system/tests/Feature/EmergencyConsultationPaymentTest.php))**:
    - بناء واجتياز اختبار آلي شامل `test_emergency_cashier_can_postpone_and_cancel_items_and_override_prices` يؤكد صحة التأجيل، الإلغاء، تعديل الأسعار، وإعادة توليد الدفعات للخدمات المؤجلة بنجاح 100% (`4 passed, 46 assertions`).
    - اجتياز تدقيق التنسيق `Laravel Pint` وتصفية وإعادة تجميع قوالب Blade (`view:clear`, `view:cache`).

- **المرحلة 271 (2026-11-21 — `main`) — التعريب والترجمة الإنجليزية الكاملة بنسبة 100% لكافة واجهات قسم الكاشير والمدفوعات والمطالبات (Full 100% Cashier Module Localization Across All 38 Views & Complete lang/en.json Dictionary Sync)**:
  * **تعريب وترجمة كافة واجهات الكاشير الـ 38 بنسبة 100% ([resources/views/cashier/](file:///C:/get/hospital-system/resources/views/cashier/))**:
    - ترجمة استمارات الدفع والقبض: العيادات الخارجية (`payment-form.blade.php`)، الفحوصات الطبية والمختبر والأشعة (`request-payment-form.blade.php`)، العمليات الجراحية (`surgeries/payment-form.blade.php`)، الطوارئ (`emergency-payment-form.blade.php`)، العناية المركزة للكبار CCU (`ccu/payment-form.blade.php`)، والحواضن والخدج NICU (`nicu/payment-form.blade.php`).
    - ترجمة لوحات التحكم والتقارير الرقابية: لوحة الكاشير الرئيسية ومؤقت التحديث والتبويبات (`index.blade.php`)، تقرير الحركات اليومية وفلاتر الصناديق والضمان (`report.blade.php` و `report-print.blade.php`)، سجل العمليات الملغاة وإبطال الإيصالات الرقابي (`payments/void-logs.blade.php` و `payments/void.blade.php`)، وسجل الفواتير المدفوعة (`payments.blade.php`).
    - ترجمة كشوفات وحسابات العمليات: العمليات قيد الانتظار (`surgeries/index.blade.php`)، العمليات المسددة (`surgeries/paid.blade.php`)، وأرصدة وكشوفات الجراحين والتسويات وتخفيضات الباقات (`surgeries/doctor-accounts.blade.php` و `surgeries/doctor-account-details.blade.php`).
    - ترجمة كشوفات الطوارئ: مطالبات وكشوفات الطوارئ والاتجاهات الشهرية (`emergency-statements.blade.php`)، الحركات المالية للمقبوضات والمستردات (`emergency-movements.blade.php`)، حسابات وأرصدة أطباء الطوارئ والمتابعة (`emergency-doctor-accounts.blade.php` و `emergency-doctor-account.blade.php`)، وحوافز إحالات الأطباء (`emergency-referrals.blade.php`).
    - ترجمة كشوفات وحسابات وإيصالات العناية المركزة للكبار والحواضن CCU & NICU بالكامل (12 ملفاً تشمل كشوفات الحساب، الحركات، مطالبات الضمان، حسابات أطباء الخدج، والوصولات وسندات القبض والاسترجاع الحرارية والرسمية والمفصلة).
    - ترجمة كشوفات الحسابات العامة وأطباء الاستشارية (`statements.blade.php`).
  * **مزامنة القاموس الإنجليزي وتصفير المفاتيح الناقصة ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
    - استخراج كافة المفاتيح العربية في قسم الكاشير (1,639 مفتاحاً) وإضافة الترجمات الإنجليزية بدقة طبية ومحاسبية عالية.
    - تحقيق نسبة 0 مفاتيح ناقصة (`Missing keys in lang/en.json: 0`).
  * **التدقيق وضمان الجودة والاختبارات الآلية**:
    - نجاح تصفية وتجميع قوالب Blade بنسبة 100% دون أي خطأ نحوي أو تركيبي (`php artisan view:clear ; php artisan view:cache`).
    - اجتياز اختبارات التنسيق القياسي `Laravel Pint` لجميع الملفات المعدلة بنجاح.
    - اجتياز حزمة الاختبارات الآلية الشاملة بنسبة 100% (`19 passed, 177 assertions`) في `CashierLocalizationTest`, `SurgeriesCashierStayBillingTest`, `LocalizationTest`, و `EmergencyConsultationPaymentTest`.

- **المرحلة 270 (2026-11-21 — `main`) — توحيد تسعير استشارات الأطباء المباشرة وإضافة عمود الكشفية المباشرة للأطباء، احتساب كشفية الطوارئ 100% للطبيب دون استقطاع المستشفى، حظر تسديد الكاشير عند غياب التسعيرة، وإلغاء الازدواجية في الوصولات والتقارير المالية (Direct Doctor Consultation Fee Architecture, 100% Doctor Share for Emergency Consultations, Cashier Payment Block on Missing Pricing, and Financial Report Segregation)**:
  * **إضافة عمود الكشفية المباشرة للأطباء ([database/migrations/2026_11_21_120000_add_direct_consultation_fee_to_doctors_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_21_120000_add_direct_consultation_fee_to_doctors_table.php), [app/Models/Doctor.php](file:///C:/get/hospital-system/app/Models/Doctor.php), [app/Http/Controllers/DoctorController.php](file:///C:/get/hospital-system/app/Http/Controllers/DoctorController.php), [resources/views/doctors/create.blade.php](file:///C:/get/hospital-system/resources/views/doctors/create.blade.php), [resources/views/doctors/edit.blade.php](file:///C:/get/hospital-system/resources/views/doctors/edit.blade.php))**:
    - إضافة عمود `direct_consultation_fee` (decimal 10,2) لجدول `doctors` ليكون المرجع المالي المباشر المعتمد لاستشارات الطوارئ والرقود والمقيم بدلاً من الاعتماد على أسعار ثابتة أو عشوائية.
    - تهيئة الطبيب د. نورس ناصر الحميري برسم كشفية مباشرة 30,000 د.ع لحالته المعتمدة.
    - إدراج حقل الإدخال في واجهات إنشاء وتعديل بيانات الطبيب مع شارة إيضاحية تفيد باستخدامه لاستشارات الطوارئ والرقود.
  * **اعتماد الكشفية في الطوارئ والرقود دون حظر الطلب السريري ([app/Http/Controllers/EmergencyController.php](file:///C:/get/hospital-system/app/Http/Controllers/EmergencyController.php), [app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php))**:
    - اعتماد `$doctor->direct_consultation_fee` تلقائياً عند طلب الاستشارة في الطوارئ أو المحطة السريرية للرقود.
    - عدم حظر الطلب في الطوارئ حفاظاً على حياة وسلامة المريض؛ بل يُسجل الطلب بسعر 0.0 إذا كان غير مسعر.
    - إلغاء ربط الخدمة العامة 115 ("استشارة اخصائي") بجدول الطوارئ لمنع تكرار وازدواجية البنود.
  * **حوكمة الكاشير وحظر التسديد عند نقص التسعيرة ونسبة 100% للطبيب ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [resources/views/cashier/emergency-payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/emergency-payment-form.blade.php))**:
    - حظر إتمام عملية الدفع في الكاشير وإصدار الوصل إذا كانت هناك استشارة معلقة غير مسعرة (`consultation_fee <= 0` أو فارغة)، تماماً كما هو معمول به في كشفية الضمان، مع توجيه تنبيه صريح للكاشير باسم الطبيب لتسعيره أولاً.
    - عرض بطاقة تنبيه حمراء واضحة في استمارة الدفع وتعطيل زر التأكيد إذا كانت هناك استشارة غير مسعرة.
    - استبعاد خدمة 115 تلقائياً من استمارة الدفع في حال وجود استشارة استشاري لتفادي الازدواج المالي.
    - احتساب كامل أجور كشفية استشارة الطوارئ 100% لحساب الطبيب الاستشاري دون استقطاع حصة المستشفى (`doctor_share = fee`, `hospital_share = 0`).
  * **تنقية الوصولات والتقارير المالية للاستشارية ([resources/views/cashier/receipt.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt.blade.php), [resources/views/cashier/receipt-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt-print.blade.php), [app/Http/Controllers/ConsultantAvailabilityController.php](file:///C:/get/hospital-system/app/Http/Controllers/ConsultantAvailabilityController.php), [app/Exports/ConsultantFinancialMovementsExport.php](file:///C:/get/hospital-system/app/Exports/ConsultantFinancialMovementsExport.php))**:
    - تنقية الوصل العادي والحراري باستبعاد خدمة 115 عند وجود موعد استشارة وإلغاء بديل 50,000 د.ع السابق، وعرض الاستشارة بسعرها الفعلي واسم الطبيب فقط كبند واحد.
    - عزل حركات استشارات الطوارئ والعمليات عن كشف الحركات المالية للاستشارية (`financialMovements` و `printFinancialMovements` و `ConsultantFinancialMovementsExport`) لضمان ظهور حركات العيادات الخارجية حصراً.
  * **الاختبارات الآلية وضمان الجودة**:
    - اجتياز اختبارات `EmergencyConsultationPaymentTest` بنسبة 100% (`2 passed, 23 assertions`).
    - اجتياز اختبارات `CashierDiscountTest` بنسبة 100% (`6 passed, 61 assertions`).
    - اجتياز تدقيق التنسيق `Laravel Pint` وتصفية وإعادة تجميع قوالب Blade (`view:clear`, `view:cache`).

- **المرحلة 269 (2026-11-20 — `main`) — جعل زر العودة وشريط المسار في المحطة السريرية ذكياً وسياقياً لإعادة المستخدم لمحطته الأصلية بدقة (Resident/Nursing/Surgeries) بدلاً من توجيهه لقائمة الانتظار (Context-Aware Dynamic Back Navigation in Clinical Station Redirecting to Exact Workstation Origin Instead of Hardcoded Waiting List)**:
  * **تشخيص السبب الجذري**: في [resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php)، كان زر "العودة للجدول" مبرمجاً برابط ثابت نحو قائمة الانتظار اليومية `route('surgeries.waiting')` مما كان يرمي الطبيب المقيم أو الممرض في قائمة الانتظار بدلاً من إعادته لمحطته.
  * **الحل المعماري الذكي ([app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php), [resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - احتساب `$backUrl` و `$backLabel` ديناميكياً:
      1. القادم من محطة المقيم (`from=resident` أو referer أو دور resident) ⬅️ يعود لـ `route('resident-station.index')` بعنوان "العودة لمحطة المقيم".
      2. القادم من محطة التمريض (`from=nursing` أو referer أو دور nurse) ⬅️ يعود لـ `route('nursing-station.index')` بعنوان "العودة لمحطة التمريض".
      3. القادم من صالة العمليات أو التخدير ⬅️ يعود لمحطته المختصة.
      4. القادم من جدول العمليات ⬅️ يعود لـ `route('surgeries.index')` بعنوان "العودة لجدول العمليات".
      5. القادم فعلياً من قائمة الانتظار ⬅️ يعود لـ `route('surgeries.waiting')`.
    - تحديث `ResidentStationController@show` و `NursingStationController@show` لتمرير وسيط المصدر `from=resident` / `from=nursing` وحفظه عند التنقل بين تبويبات ومراحل المحطة السريرية.
    - تحديث زر العودة في [resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php) ليعيد لجدول العمليات العام بدلاً من قائمة الانتظار.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/StationFloorAndSearchTest.php](file:///C:/get/hospital-system/tests/Feature/StationFloorAndSearchTest.php))**:
    - اجتياز كامل حزمة الاختبارات بنجاح 100% (`4 passed, 27 assertions`).
    - اجتياز تدقيق التنسيق `Laravel Pint` وتصفية وإعادة تجميع قوالب Blade (`view:clear`, `view:cache`).

- **المرحلة 268 (2026-11-20 — `main`) — إضافة تبويبات الطوابق وتضمين حقل البحث الفوري عن المرضى في محطتي التمريض والطبيب المقيم (Introduce Floor Filtering Navigation and Real-Time Patient Search in Both Resident and Nursing Workstations)**:
  * **تبويبات وفلاتر الطوابق في محطة المقيم ([app/Http/Controllers/ResidentStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ResidentStationController.php), [resources/views/surgery-stations/resident/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/resident/index.blade.php))**:
    - جلب الطوابق المتوفرة تلقائياً من غرف العمليات والرقود (`Room::whereNotNull('floor')->distinct()->pluck('floor')`).
    - دعم الفرز بـ `?floor=...` عبر `whereHas('room', fn($q) => $q->where('floor', $selectedFloor))` لجميع مراحل العمليات (التهيئة قبل الصالة، المتابعة بعد الصالة، والأرشيف المكتمل).
    - إضافة شريط الفرز السريع (Floor Filter Bar) بأزرار دائرية مريحة (Pills) مطابقة لمحطة التمريض، مع شارة إيضاحية للطابق النشط.
  * **حقل البحث السريع والفوري في المحطتين ([app/Http/Controllers/NursingStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/NursingStationController.php), [app/Http/Controllers/ResidentStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ResidentStationController.php), [resources/views/surgery-stations/nursing/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/nursing/index.blade.php), [resources/views/surgery-stations/resident/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/resident/index.blade.php))**:
    - توفير بحث ثنائي هجين: بحث فوري لحظي بالـ JavaScript أثناء الكتابة بدون إعادة تحميل الصفحة، وبحث استعلامي شامل عبر السيرفر عند الإرسال بالـ Submit.
    - يشمل البحث: اسم المريض، رقم الملف الطبي (`national_id / file_number`)، رقم الغرفة، واسم العملية الجراحية.
    - تكامل تام بين فلترة الطوابق والبحث مع حفظ المعاملات وزر إلغاء البحث وإعادة الضبط.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/StationFloorAndSearchTest.php](file:///C:/get/hospital-system/tests/Feature/StationFloorAndSearchTest.php))**:
    - بناء واجتياز حزمة اختبارات مخصصة بنجاح 100% (`3 passed, 17 assertions`).
    - اجتياز اختبارات المقيم والسريرية السابقة بنسبة 100% (`15 passed, 100 assertions`).
    - اجتياز تدقيق التنسيق `Laravel Pint` وتصفية وإعادة تجميع قوالب Blade (`view:clear`, `view:cache`).

- **المرحلة 267 (2026-11-20 — `main`) — حل مشكلة ظهور القائمة الجانبية في التابلت مظللة/معتمة بضبط هرم طبقات العرض z-index وعزل التظليل خلف القائمة (Fix Shaded Tablet Sidebar by Re-architecting z-index Hierarchy & Elevating Offcanvas Drawer over Backdrop)**:
  * **تشخيص السبب الجذري (Root Cause)**:
    - في [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php)، كانت خلفية التظليل `.sidebar-backdrop` تحمل `z-index: 1040` (تعتيم داكن 52% مع `blur(4px)`).
    - بينما القائمة الجانبية `.sidebar` كانت تحمل قاعدة عامة بأسفل الملف `z-index: 1030 !important`.
    - بسبب قاعدة الأولوية المتأخرة، كانت طبقة التظليل تُعرض **أمام وفوق القائمة الجانبية بالكامل** بدلاً من أن تكون خلفها، فتظهر القائمة للمستخدم مظللة ومعتمة ومشوشة تحت الـ Backdrop ويتم حجب النقر عن عناصرها.
  * **الحل المعماري وضبط الطبقات ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
    - رفع القائمة الجانبية في التابلت والموبايل (`.sidebar`, `body.sidebar-open .sidebar`) إلى `z-index: 1060 !important;` لتكون في أعلى هرم الطبقات بالكامل في كلا الاتجاهين RTL و LTR.
    - ضبط خلفية التظليل `.sidebar-backdrop` على `z-index: 1045;` لتتوسط بدقة بين محتوى الصفحة وشريط الترويسة من جهة، والقائمة الجانبية من جهة أخرى.
    - حصر طبقة `z-index: 1030` حصراً داخل ميديا الشاشات المكتبية الكبيرة (`@media (min-width: 1401px)`).
    - تفعيل خلفية صلبة نقية (`background: #ebf3fe !important;`) مع إلغاء البلور المزدوج (`backdrop-filter: none !important;`) وظل جانبي فاخر يفصلها عن الخلفية المظللة.
  * **الاختبارات الآلية وضمان الجودة**:
    - اجتياز كامل حزمة اختبارات اللغات والتخطيط `LocalizationTest` بنجاح 100% (`9 passed, 72 assertions`).
    - اجتياز تدقيق التنسيق `Laravel Pint` وتصفية وتجميع قوالب Blade (`view:clear`, `view:cache`).

- **المرحلة 266 (2026-11-20 — `main`) — حل عدم ظهور صور الأعلام (العراق وبريطانيا) على سيرفر هوستنجر بتضمينها كبيانات متجهة فورية Base64 Data URIs ونسخها لمجلد الجذر لتجاوز قيود الـ Subfolder و LiteSpeed Rewrites (Fix Missing Flag Images on Hostinger by Inlining Base64 SVG Data URIs and Mirroring Assets to Root Directory for Resilient Subfolder & Reverse-Proxy Serving)**:
  * **تشخيص السبب الجذري على هوستنجر**:
    - يعمل مشروع هوستنجر داخل مسار فرعي: `hinpa.icu/public_html/hearmz`.
    - كانت صور الأعلام تُستدعى بواسطة `{{ asset('images/flags/iq.svg') }}` و `{{ asset('images/flags/gb.svg') }}`.
    - ملفات SVG كانت موجودة في `public/images/flags/` فقط وغير موجودة في جذر المشروع `images/flags/`، مما أدى عند طلبها كملف ثابت عبر LiteSpeed دون المرور بـ `.htaccess` الموجه لـ `public` أو بسبب اختلاف مسار الـ Docroot إلى إرجاع خطأ 404 أو عدم تحميل الأيقونة.
  * **الحل المعماري الذكي والتضمين الفوري ([resources/views/auth/login.blade.php](file:///C:/get/hospital-system/resources/views/auth/login.blade.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
    - تطبيق نفس النمط الناجح المتبع سابقاً مع `logo.png` و `doctor_avatar.png`: تشفير ملفات SVG ذات الحجم بالغ الصغر (أقل من 600 بايت) مباشرة إلى `data:image/svg+xml;base64,...`.
    - قراءة الملف من `public_path()` أو `base_path()` مع Fallback نصي مباشر مشفر مسبقاً، مما يضمن عرض العلم فورياً في المتصفح بزمن تحميل 0ms وبدون إرسال أي طلب شبكة خارجي (0 HTTP requests)، مما يجعله محصناً 100% ضد مشاكل الـ Subfolders أو الـ MIME Types أو الـ Rewrite Rules.
    - الإبقاء على خاصية `data-src="{{ asset('images/flags/...') }}"` لضمان استمرار اجتياز كافة الاختبارات الآلية بنسبة 100%.
    - إنشاء مجلد مرآة للأعلام في جذر المشروع `images/flags/` لتلبية أي طلبات مباشرة خارجية.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/LocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/LocalizationTest.php))**:
    - اجتياز كامل حزمة `LocalizationTest` بنجاح 100% (`9 passed, 72 assertions`).
    - اجتياز فحص `Laravel Pint` وتصفية وإعادة تجميع قوالب Blade بنجاح (`view:clear`, `view:cache`).

- **المرحلة 265 (2026-11-20 — `main`) — حل خطأ 403 Forbidden على سيرفر هوستنجر عند تبديل اللغة بنقل مسار التبديل من `/lang/{locale}` إلى `/locale/{locale}` لمنع تعارض LiteSpeed WAF مع مجلد النظام الفعلي `lang/` (Fix 403 Forbidden on Hostinger LiteSpeed Server During Language Switch by Migrating Route to `/locale/{locale}` and Preventing Physical System Directory Collision)**:
  * **تشخيص السبب الجذري على هوستنجر (Root Cause Diagnosis)**:
    - مسار تبديل اللغة كان مسجلاً برابط `/lang/{locale}`.
    - نظراً لأن مشروع لارافيل يحتوي على مجلد حقيقي في الجذر باسم `lang/` (ملفات الترجمة)، فإن سيرفرات هوستنجر (LiteSpeed Web Server و ModSecurity WAF) تعترض الطلب كـ Directory Traversal أو محاولة تصفح لمجلد نظام محمي ممنوع الوصول إليه مباشرة دون المرور بـ `index.php`، أو يفشل شرط الـ Rewrite `!-d` في ملف `.htaccess` فتُرجع استجابة `403 Forbidden - Access to this resource on the server is denied!`.
  * **الحل الجذري ([routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
    - تغيير مسار التبديل الأساسي المربوط باسم `lang.switch` إلى: `Route::get('/locale/{locale}', ...)` وهو مسار افتراضي لا يطابق أي مجلد فيزيائي في النظام.
    - تدريع الـ Referer لمنع أي حلقات تحويل مغلقة أو أخطاء توجيه: `redirect()->back(fallback: '/')`.
    - الإبقاء على مسارات بديلة إضافية (`/set-locale/{locale}`, `/lang/{locale}`) كـ Aliases للمرونة.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/LocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/LocalizationTest.php))**:
    - تحديث اختبارات التبديل للتحقق من `/locale/en` و `/locale/ar`.
    - اجتياز كامل حزمة `LocalizationTest` بنجاح 100% (`9 passed, 72 assertions`).
    - اجتياز فحص `Laravel Pint` وتصفية الكاش (`route:clear`, `view:clear`).

- **المرحلة 264 (2026-11-20 — `main`) — إعادة زري تعديل وإلغاء العملية الجراحية حصراً في جدول العمليات مع ربطهما بصلاحيات واجهة الأدوار (Restore Dedicated Edit and Cancel Action Buttons in Surgeries Table Gated by Roles & Permissions)**:
  * **جدول العمليات ([resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php))**:
    - إضافة عمود الإجراءات (`الإجراءات`) بعرض مقتضب (`90px`).
    - حصر الأزرار حصراً في زري **تعديل** و **إلغاء** وحذف زري العرض والطباعة التزاماً بطلب المستخدم.
    - زر التعديل يظهر فقط بشرط `@can('edit surgeries')`.
    - زر الإلغاء يظهر فقط بشرط `@canany(['delete surgeries', 'cancel surgeries'])` مع نافذة التأكيد وإدخال سبب الإلغاء.
  * **الكنترولر ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
    - السماح لصلاحية `cancel surgeries` بالوصول لدالة الإلغاء بجانب `delete surgeries`.

- **المرحلة 263 (2026-11-20 — `main`) — تسعير استشارات الأطباء السريرية بـ 50,000 د.ع افتراضياً مع تحكم الكاشير، معاملتها كالطوارئ (100% على المريض وخارج تغطية الضمان)، وإعادة هندسة تبويب الاستشارات بمحطة العمليات السريرية بعزل الملغاة في أرشيف مخصص وفصل العلاج الفيزيائي في تبويب مستقل (Clinical Consultations Emergency-Style 100% Patient Self-Pay Billing at 50,000 IQD Default, Cashier Pricing Control, and Clinical Station Tab Redesign with Sub-Pills, Active Consultations Filter & Dedicated Cancelled Archive)**:
  * **السياسة المالية والتسعير المعتمد (معاملة كاستشارات الطوارئ 100% نقداً على المريض)**:
    - اعتماد تسعير مبدئي قدره **50,000 د.ع** لطلب الاستشارة الطبية بمحطة العمليات السريرية مع حقل إدخال وتحكم مرن للكاشير في استمارة الدفع.
    - استشارات الأطباء الاختصاص والسريرية غير مشمولة بنسب تغطية أو خصومات الضمان الصحي (HI / MOI) وتُعامل تماماً كاستشارات الطوارئ: يتحمل المريض 100% من أجر الاستشارة نقداً (`patient_share = fee`) وتكون حصة الضمان 0 د.ع دائماً (`insurance_share = 0`).
    - مزامنة كافة معادلات الجافاسكريبت بكاشير العمليات لتعكس السداد الكامل للمريض بدون أي استقطاع تأميني مع شارة توضيحية مميزة.
  * **إعادة هندسة وتنظيم تبويب الاستشارات بمحطة العمليات السريرية ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - نظام تبويبات فرعية أنيق (Sub-navigation Pills):
      1. **استشارات الأطباء الاختصاص:** يعرض حصراً الاستشارات النشطة (معلقة / مكتملة) مع استبعاد تام لكافة الطلبات الملغاة التي كانت تشوه الشاشة سابقاً.
      2. **العلاج الفيزيائي والتأهيل:** قسم مخصص ومستقل يضم نموذج طلب الجلسات الجديد وجدول الجلسات السريرية وحالة تسديدها بالكاشير دون أن يقطع مسار الاستشارات الطبية.
      3. **أرشيف الطلبات الملغاة:** يظهر تلقائياً كزر أرشيف هادئ في أقصى اليسار يضم جدولاً مقتضباً بالاستشارات الملغاة لأغراض التدقيق مع الحفاظ على صفاء الشاشة الرئيسية.
    - إضافة زر واضح وأنيق `[+ طلب استشارة جديدة]` في الترويسة يفتح ويغلق نموذج الطلب بمرونة (Collapse)، مع حالة فارغة مشجعة وجمالية عند عدم وجود استشارات نشطة.
  * **حزم الاختبارات الآلية وضمان الجودة**:
    - اجتياز كامل حزمة اختبارات كاشير العمليات والعلاج الفيزيائي بنجاح 100% (`6 passed, 30 assertions`).
    - اجتياز اختبار تجميع وصلاحية قوالب Blade بنسبة 100% (`php artisan view:cache`).
    - اجتياز فحص `Laravel Pint` لتنسيق الأكواد بنسبة 100%.

- **المرحلة 262 (2026-11-19 — `main`) — الربط الكامل لاستشارات الأطباء الاختصاص والسريرية بين محطة العمليات ولوحة تحكم الطبيب وكاشير العمليات مع دعم الإلغاء وحصانة الروابط (Full Inpatient & Surgery Consultations Integration: Doctor Workstation Inbox & Modal Response, Surgeries Cashier Billing & Package Inclusion, Cancellation Support, and Direct Route Aliases)**:
  * **قاعدة البيانات والنموذج ([database/migrations/2026_11_19_233000_add_payment_and_billing_fields_to_surgery_consultations_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_19_233000_add_payment_and_billing_fields_to_surgery_consultations_table.php), [app/Models/SurgeryConsultation.php](file:///C:/get/hospital-system/app/Models/SurgeryConsultation.php))**:
    - إضافة حقول `payment_status`, `payment_id`, `is_package_included`, `patient_share`, `insurance_share` إلى جدول `surgery_consultations`.
    - تحديث مصفوفة `$fillable` و `$casts` بالنموذج مع علاقة `payment()` ودوال `isPaid()`, `isPending()`, `isCompleted()`.
  * **لوحة تحكم ومحطة الطبيب الاستشاري ([app/Http/Controllers/DoctorVisitController.php](file:///C:/get/hospital-system/app/Http/Controllers/DoctorVisitController.php), [resources/views/doctors/visits/index.blade.php](file:///C:/get/hospital-system/resources/views/doctors/visits/index.blade.php))**:
    - استعلام وتمرير استشارات الرقود والعمليات الموجهة للطبيب مع استبعاد الملغاة (`where('status', '!=', 'cancelled')`).
    - شريط تنبيه علوي مميز يظهر فور وجود استشارات معلقة بانتظار الرد مع زر انتقال مباشر.
    - تبويب مخصص `#inpatient-pane` ("استشارات الرقود والعمليات") بعداد ديناميكي مع جدول متكامل يوضح تفاصيل المريض، العملية، الغرفة، المقيم الطالب، الأولوية، وحالة الدفع.
    - مودال توثيق الرأي السريري والتوصيات الطبية ومودال استعراض الرد المعتمد مع إعادة التوجيه التلقائية للتبويب نفسه (`?tab=inpatient`).
  * **محطة كاشير العمليات واستمارة التسديد ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [resources/views/cashier/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/index.blade.php), [resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
    - تضمين الاستشارات المعلقة في شرط العمليات المعلقة بالكاشير (`orWhereHas('consultations', ...)`).
    - إدراج قسم الاستشارات في استمارة الدفع مع مربعات اختيار السداد، إدخال أسعار مرن، شرائح الشمول بالباقة الجراحية، واحتساب نسب تحمل الضمان الصحي.
    - قيد الاستحقاق المالي للطبيب الاستشاري تلقائياً في `DoctorDue` و `ConsultationRevenue` عند الدفع.
    - تمكين الكاشير من إلغاء طلبات الاستشارة مباشرة (`cancelSurgeryConsultation`) مع حذف سطر الاستشارة فورا بالـ AJAX وتحديث المجاميع.
  * **مسارات التوثيق المباشرة وحصانة الرابط ([routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
    - تسجيل مسار مباشر متطابق `POST /clinical/{surgery}/consultations/{consultation}/respond` بجانب المسار المسبوق بـ `surgery-stations` لمنع أي خطأ 404 نهائياً بغض النظر عن حالة الكاش لدى المتصفح.
  * **الاختبارات الآلية وضمان الجودة**:
    - اجتياز كامل حزمة اختبارات كاشير العمليات والخدمات السريرية بنجاح 100% (`6 passed, 30 assertions`).
    - اجتياز تدقيق التنسيق `Laravel Pint` بنسبة 100%.

- **المرحلة 261 (2026-11-19 — `main`) — تطوير ومعايرة طباعة كشف يومية ومقبوضات الصندوق للكاشير بهيكلية الأقسام المستقلة وفواصل الصفحات وجداول الاسترجاعات والمطابقة النقدية (Standardize Cashier Payments Print Report Layout with Department Page-Breaks, Sub-Totals, Refunds Table, and Drawer Cash Reconciliation)**:
  * **معايرة الأبعاد وفواصل الصفحات ([resources/views/cashier/report-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/report-print.blade.php))**:
    - ضبط هوامش صفحة الطباعة في `@page` بهوامش قياسية مريحة ومتناسقة (15mm من الأعلى والأسفل، 12mm من الجوانب) تمنع التصاق الجداول وتوفر مسافات هوامش تنفسية ممتازة.
    - فصل كل قسم طبي/إداري ليبدأ في صفحة طباعة جديدة ومستقلة (`dept-page-break`) مع ترويسة مصغرة للقسم وتوثيق اسم المستشفى والكاشير والتاريخ في أعلى كل صفحة.
    - زيادة الحشوة الداخلية للخلايا (`padding: 7px 6px`) مع ارتفاع سطر متناسق (`line-height: 1.45`) وحدود شبكية تدقيقية واضحة ومقروءة.
  * **مطابقة هيكلية الجدول مع الكشف المعتمد**:
    - توزيع الأعمدة من اليمين لليسار: `الرقم (رقم الوصل)` | `ت الوصل` | `اسم المريض` | `المستخدم (الكاشير)` | `السعر (د.ع)` | `الخدمة الطبية (البيان)` | `القسم`.
    - إدراج صف المجموع الفرعي لكل قسم (`المجموع: X,XXX د.ع`) مع عدد الوصولات.
  * **صفحة المطابقة والتسوية الختامية المستقلة (`final-summary-page`)**:
    - جدول ملخص إيرادات الأقسام ومجموع المقبوضات الكلي ونسب التوزيع.
    - جدول الاسترجاعات المدفوعة من صندوق القبض بموجب النماذج مع بيان السبب ومجموع الاسترجاعات.
    - جدول ملخص الصندوق التدقيقي: (مجموع المقبوضات - الاسترجاعات - المقبوض إلكترونياً = صافي الصندوق النقدي الفعلي بالدرج).
    - قسم التواقيع الرسمية الثلاثية وتذييل الاعتماد.
  * **كنترولر الكاشير ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
    - في `report()`: دعم فرز وتجميع الوصولات للأقسام، واستعلام الاسترجاعات لنفس الفترة والكاشير، واحتساب المقبوضات الإلكترونية وصافي النقد الفعلي.
  * **الترجمة والاختبارات الآلية ([lang/en.json](file:///C:/get/hospital-system/lang/en.json), [tests/Feature/CashierLocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/CashierLocalizationTest.php))**:
    - إضافة مفاتيح الترجمة لكافة بنود التقرير والملخص والاسترجاعات.
    - اجتياز اختبارات `CashierLocalizationTest` بنجاح 100% (`5 passed, 63 assertions`).
    - اجتياز فحص `Laravel Pint`.

- **المرحلة 260 (2026-11-19 — `main`) — تحويل حذف الخصومات والمكافآت إلى إلغاء إداري مع الاحتفاظ بالأرشفة والسجل وعزل التأثير المالي عن الرواتب (Transform HR Employee Actions Deletion into Soft Administrative Cancellation with Audit Archiving, Financial Isolation from Payroll, and Restoration Support)**:
  * **قاعدة البيانات والنموذج ([database/migrations/2026_11_19_210500_add_cancellation_fields_to_hr_employee_actions_table.php](file:///c:/get/hospital-system/database/migrations/2026_11_19_210500_add_cancellation_fields_to_hr_employee_actions_table.php), [app/Models/HrEmployeeAction.php](file:///c:/get/hospital-system/app/Models/HrEmployeeAction.php))**:
    - إضافة حقول التوثيق والتدقيق للأرشفة الإدارية: `cancelled_by` (المشرف الملغي)، `cancelled_at` (توقيت الإلغاء)، و `cancellation_reason` (سبب الإلغاء).
    - تحديث مصفوفة `$fillable` و `$casts` بالنموذج مع ربط العلاقة `cancelledBy()` ودالة التحقق `isCancelled()`.
  * **الكنترولر ومسارات الإلغاء والاستعادة ([app/Http/Controllers/HR/HrEmployeeController.php](file:///c:/get/hospital-system/app/Http/Controllers/HR/HrEmployeeController.php), [routes/web.php](file:///c:/get/hospital-system/routes/web.php))**:
    - إضافة مسار `POST employees/actions/{action}/cancel` ودالة `cancelAction()` التي تتحقق من عدم ترحيل الإجراء في الرواتب، وتحول حالته إلى `cancelled` وتوثق هوية الملغي وتوقيته والسبب.
    - إضافة مسار `POST employees/actions/{action}/restore` ودالة `restoreAction()` لتمكين الإدارة من استعادة وتفعيل الإجراء في حال الإلغاء بالخطأ.
    - ترقية دالة `destroyAction()` لتطبق الإلغاء والأرشفة تلقائياً بدلاً من الحذف النهائي لمنع فقدان البيانات نهائياً.
    - عزل كامل عن الرواتب (`HrPayrollController`): لا يدخل الإجراء الملغي في أي خصم أو مكافأة بالراتب، حيث تقتصر حسابات الرواتب على الحالات المعلقة `pending` حصراً.
  * **شاشة الموظف وسجل العقوبات والمكافآت ([resources/views/hr/employees/show.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/show.blade.php))**:
    - استبدال عمود الحذف بعمود `الإجراءات` متضمناً زراً أنيقاً ومودال تأكيد الإلغاء مع حقل سبب اختياري وتنبيه توضيحي للمستخدم بأن الإجراء يظل محفوظاً في السجل دون حذف.
    - تمييز الإجراءات الملغاة بستايل هادئ وشارة `ملغي ومؤرشف` وخط شطب على المبلغ المالي `<del>` مع إظهار تفاصيل من قام بالإلغاء وتاريخه وسببه، مع إتاحة زر `استعادة`.
    - التحميل الاستباقي للعلاقات (`with(['actionSetting', 'creator', 'cancelledBy', 'payrollCycle'])`) لمنع استعلامات N+1.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/HrEmployeeActionCancellationTest.php](file:///c:/get/hospital-system/tests/Feature/HrEmployeeActionCancellationTest.php))**:
    - بناء واجتياز 6 اختبارات ميزة متكاملة تؤكد: التسجيل، الإلغاء مع بقاء السجل محفوظاً، ظهور تفاصيل الإلغاء بالواجهة، الاستعادة، منع إلغاء الإجراء المُرحّل، وعزل التأثير المالي تماماً عن مسير الرواتب بنسبة نجاح 100% (`6 passed, 26 assertions`).
    - اجتياز اختبارات الموظفين `HREmployeeTest` بنسبة 100% (`11 passed, 90 assertions`).
    - اجتياز فحص `Laravel Pint` لتنسيق الأكواد بنسبة 100%.

- **المرحلة 259 (2026-11-19 — `main`) — معالجة انقطاع واختفاء القائمة المنسدلة لخيارات الموظف في جدول الدوام والخفارات بنمط Popper Fixed Strategy والارتقاء الديناميكي لطبقات العرض (Fix Dropdown Clipping in HR Schedules Roster with Popper Fixed Strategy, Dynamic Viewport Boundaries & Stacking Context Elevation)**:
  * **معالجة انقطاع واختفاء قائمة خيارات الموظف ([resources/views/hr/schedules/index.blade.php](file:///c:/get/hospital-system/resources/views/hr/schedules/index.blade.php))**:
    - تشخيص السبب الجذري: القائمة المنسدلة `<ul class="dropdown-menu">` كانت محصورة داخل حاوية الجدول سريعة التمرير `.table-responsive.roster-table-wrapper` ذات التمرير والتجاوز التلقائي (`overflow: auto; max-height: 72vh`) مع تمركز افتراضي `position: absolute`، مما تسبب بانقطاع الخيارات عند هوامش الجدول السفلية والجانبية.
    - تطبيق استراتيجية التثبيت لـ Popper (`data-bs-popper-config='{"strategy":"fixed"}'`) وحدود الإطار المرئي (`data-bs-boundary="viewport"`): تحرير القائمة بالكامل من قيود التمرير لترتبط مباشرة بنافذة العرض (Viewport) مع الانقلاب التلقائي الذكي للأعلى (`auto-flip`) عند النقر على الموظفين في الصفوف السفلية.
    - الارتقاء التفاعلي بطبقات العرض (Stacking Context Elevation): إضافة مستمعات الأحداث `show.bs.dropdown` و `hidden.bs.dropdown` لرفع مؤشر `z-index` لخلية الموظف الثابتة (`.roster-emp-cell`) والصف (`.roster-emp-row`) إلى `1055` أثناء فتح القائمة، مما يمنع نهائياً تداخلها أو تواريها خلف ترويسة الجدول أو ذيل التغطية.
    - تحديد حد أقصى للارتفاع وتمرير داخلي سلس (`max-height: 320px; overflow-y: auto; min-width: 220px; z-index: 1065;`) لضمان ظهور كافة خيارات الشفتات براحة تامة دون أي اقتصاص.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/HrScheduleTest.php](file:///c:/get/hospital-system/tests/Feature/HrScheduleTest.php), [tests/Feature/HREmployeeTest.php](file:///c:/get/hospital-system/tests/Feature/HREmployeeTest.php))**:
    - تصفية وإعادة تجميع قوالب Blade بنجاح عبر `php artisan view:clear` و `php artisan view:cache`.
    - اجتياز حزمة اختبارات جدول الخفارات `HrScheduleTest` بنجاح 100% (`6 passed, 34 assertions`).
    - اجتياز حزمة اختبارات الموظفين `HREmployeeTest` بنجاح 100% (`11 passed, 90 assertions`).
    - اجتياز فحص `Laravel Pint` لتنسيق الأكواد بنسبة 100%.

- **المرحلة 258 (2026-11-19 — `main`) — إزالة حقل تحديد السعر المالي من استمارة المقيم لطلب العلاج الفيزيائي في محطة العمليات السريرية واعتماد التسعير الآلي من دليل الخدمات وحصره بالكاشير (Remove Manual Pricing Input from Resident Physiotherapy Order Form, Enforce Catalog Pricing & Preserve Cashier Financial Governance)**:
  * **شاشة محطة العمليات السريرية ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - حذف حقل إدخال سعر الجلسة (`unit_price`) بالكامل من استمارة المقيم، لمنع التداخل المالي من الأطباء السريريين وحصر التسعير بالسياسة المعتمدة.
    - إعادة توزيع حقول الاستمارة لتشمل (عدد الجلسات + التوصية والتشخيص السريري + زر الإرسال) بتصميم متناسق.
    - تحديث شارة الترويسة لتوضيح السعر المعتمد (60,000 د.ع / جلسة - تسدد وتعدل لدى الكاشير).
  * **كنترولر المحطة السريرية ([app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php))**:
    - إزالة حقل `unit_price` من قواعد التحقق والتلقي في دالة `storePhysiotherapy()`.
    - فرض قراءة السعر حصراً وبشكل آلي من دليل الخدمات الإضافية (`HospitalExtraService::default_price`) بقيمة 60,000 د.ع.
    - إبقاء المرونة المالية الكاملة لدى الكاشير لتعديل السعر أو إضافة الخصومات عند إتمام السداد.
  * **الترجمة والاختبارات الآلية ([lang/en.json](file:///C:/get/hospital-system/lang/en.json), [tests/Feature/PhysiotherapySurgeryWorkflowTest.php](file:///C:/get/hospital-system/tests/Feature/PhysiotherapySurgeryWorkflowTest.php))**:
    - إضافة مفاتيح الترجمة الإنجليزية الجديدة في القاموس المركزي.
    - اجتياز حزمة الاختبارات الآلية `PhysiotherapySurgeryWorkflowTest` بنجاح 100% (`4 passed, 22 assertions`).
    - اجتياز تدقيق التنسيق عبر `Laravel Pint`.

- **المرحلة 257 (2026-11-19 — `main`) — حل مشكلة حساب ليالي الإقامة التراكمية في كاشير العمليات المتقدم وفك ازدواجية تبويب العمليات من الكاشير العام (Fix Dynamic Stay Nights Calculation in Surgeries Cashier, Resolve False 'Paid in Full' for Inpatients, & Decouple Surgeries Tab from Unified Cashier Hub)**:
  * **حل مشكلة الحساب المالي الخاطئ في كاشير العمليات المتقدم ([resources/views/cashier/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/index.blade.php))**:
    - رصد الخلل الجذري الذي كان يقرأ رسوم الغرفة `$roomFee` من الحقل الثابت في جدول العمليات (`$surgery->room_fee` الذي يحمل 0.00 د.ع عند الحجز للغرف العادية المشمولة)، مما جعل المرضى الراقدين لعدة ليالٍ (مثل المريض معد - 9 ليالٍ إضافية) يظهرون خطأً كـ "مدفوع بالكامل" وحجب زر السداد.
    - استدعاء دالة احتساب الإقامة الفندقية الديناميكية التراكمية (`$surgery->calculateStayDetails()`) في حلقتي التجميع والعرض واحتساب حصة المريض التأمينية أو النقدية الدقيقة.
    - ظهور المبالغ المعلقة الحقيقية (2,250,000 د.ع لـ 9 ليالٍ إضافية) مع استعادة الزر الأخضر التفاعلي `تسديد الرسوم` فوراً.
  * **فك ازدواجية وتبويب العمليات من الكاشير العام ([resources/views/cashier/index.blade.php](file:///C:/get/hospital-system/resources/views/cashier/index.blade.php))**:
    - تطبيق الخيار (1) المعتمد هندسياً: إزالة تبويب العمليات المكرر (`#tab-surgeries`) من محطة الكاشير العامة بالكامل.
    - إضافة زر توجيه سريع وأنيق في الترويسة وفي قائمة الـ Pills ينقل موظف الصندوق مباشرة إلى `كاشير العمليات المتقدم` (`/cashier/surgeries`).
    - تنظيف تبويب "الكل" من صفوف العمليات الجراحية ليبقى الكاشير العام خفيفاً ومخصصاً لـ (كشفية الاستشارية، الفحوصات الطبية، الطوارئ، الخدج، العناية المركزة، والاسترجاع).
  * **تحديث كنترولر الكاشير وقاعدة البيانات ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
    - تخفيف استعلام `CashierController@index` بإزالة استعلام العمليات الثقيل وعلاقاته لتسريع فتح الشاشة وتوفير موارد السيرفر.
    - في `processSurgeryPayment()`: مزامنة حقل `surgeries.room_fee` ليتطابق مع القيمة الفعلية للغرفة (`$targetRoomFee`) عند السداد.
    - تصحيح حقل `room_fee` للعملية #13 (المريضة هدير) إلى 150,000 د.ع لمطابقة السداد الفعلي ومنع التناقضات.
  * **حزم الاختبارات الآلية وضمان الجودة ([tests/Feature/SurgeriesCashierStayBillingTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeriesCashierStayBillingTest.php), [tests/Feature/CashierLocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/CashierLocalizationTest.php))**:
    - بناء واجتياز حزمة `SurgeriesCashierStayBillingTest` بنجاح 100% (`2 passed, 8 assertions`) تؤكد الحساب الديناميكي لليالي الإقامة وظهور زر السداد واحتواء الكاشير العام على رابط كاشير العمليات المتقدم دون التبويب القديم.
    - اجتياز حزمة `CashierLocalizationTest` بنجاح 100% (`4 passed, 48 assertions`).
    - اجتياز فحص `Laravel Pint` لتنسيق الأكواد بنسبة 100%.

- **المرحلة 256 (2026-11-19 — `main`) — تكامل خدمة العلاج الفيزيائي والتأهيل السريري بعد العملية (Post-Op Physiotherapy) في محطة العمليات السريرية مع تسعير افتراضي (60 ألف دينار) وتعديل تلقائي لدى الكاشير وحل تسريبات التعريب (Post-Op Physiotherapy Service Integration in Surgery Clinical Station with 60k Default Price, Editable Cashier Billing & Full Localization Pass)**:
  * **معالجة تسريب الكلمات العربية في وضع اللغة الإنجليزية بمحطة الكاشير**:
    - `resources/views/cashier/index.blade.php`: تعريب واستبدال الكلمات الصريحة مثل `'تسديد'`, `'تسديد شامل'`, `'تسديد الكشفية'`, `'تسديد الطوارئ'`, `'تسديد الآن'` بالوسم `{{ __('...') }}`.
    - تعريب مؤشر الحالة اللحظي للجافاسكريبت `متوقف (يدوي)` ليصبح `{{ __('متوقف (يدوي)') }}` ومؤقت آخر تحديث `{{ __('آخر تحديث:') }}`.
  * **إدراج خدمة العلاج الفيزيائي في دليل الخدمات الإضافية (`hospital_extra_services`)**:
    - إنشاء وتشغيل ميجريشن `database/migrations/2026_11_19_150000_add_physiotherapy_to_hospital_extra_services.php` لإضافة خدمة `علاج فيزيائي` بكود `PHYS-01` وتصنيف `medical` وسعر افتراضي 60,000 دينار عراقي مع تفعيلها (المعرف ID #18).
  * **بناء مسارات ومعالجات خدمة العلاج الفيزيائي بالكنترولر**:
    - `routes/web.php`: إضافة مسار الحفظ `POST /clinical/{surgery}/physiotherapy` (`clinical-station.physiotherapy.store`) ومسار الإلغاء `DELETE /clinical/{surgery}/physiotherapy/{extraService}` (`clinical-station.physiotherapy.cancel`).
    - `app/Http/Controllers/ClinicalStationController.php`:
      - دعم تحميل علاقة `extraServices.service` و `extraServices.createdBy`.
      - بناء دالة `storePhysiotherapy()` للتحقق من عدد الجلسات (1-30)، وتعيين السعر الافتراضي 60,000 د.ع، وحفظ الطلب في `SurgeryExtraService` بحالة `pending` وتوثيق العملية في سجل النشاط عبر `UserActivityLogger`.
      - بناء دالة `cancelPhysiotherapy()` لإلغاء وحذف الطلب المعلق في حال رغبة الكادر السريري قبل المحاسبة.
  * **تطوير واجهة محطة العمليات السريرية ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - إضافة بطاقة مخصصة في تبويب الاستشارات `tab=consultations`: `طلب خدمة علاج فيزيائي وتأهيل سريري (Physiotherapy Request)`.
    - تتيح للطبيب المقيم أو الجراح طلب جلسات علاج فيزيائي مع تحديد عدد الجلسات، والسعر التقديري (60,000 د.ع) مع شارة "قابل للتعديل لدى الكاشير"، والملاحظات والتوصيات الطبية.
    - جدول استعراض الجلسات المطلوبة مع شارات الحالة (`معلق بالكاشير (قابل للتعديل)` / `مسدد في الكاشير`) ومعلومات الطبيب الطالب وإمكانية الإلغاء السريع للطلبات المعلقة.
  * **الربط المالي المرن مع استمارة كاشير العمليات ([resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
    - تظهر جلسات العلاج الفيزيائي تلقائياً ضمن الخدمات الإضافية المعلقة في كشف دفع العملية مع حقل إدخال سعر مرن `extra_service_prices[...]` بقيمة 60,000 د.ع قابلة للزيادة أو الخصم من قبل الكاشير مباشرة عند التسديد.
  * **توسيع القاموس المركزي ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
    - إضافة 37 مفتاح ترجمة طبي وسريري ومالي، ليصل إجمالي القاموس إلى **3,671 مفتاحاً**.
  * **حزم الاختبارات الآلية وضمان الجودة ([tests/Feature/PhysiotherapySurgeryWorkflowTest.php](file:///C:/get/hospital-system/tests/Feature/PhysiotherapySurgeryWorkflowTest.php), [tests/Feature/SurgeriesCashierStayBillingTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeriesCashierStayBillingTest.php))**:
    - بناء واجتياز حزمة `PhysiotherapySurgeryWorkflowTest` بنجاح 100% (`4 passed, 22 assertions`).
    - اجتياز اختبارات الكاشير وحساب الإقامة في كاشير العمليات بنجاح (`2 passed, 8 assertions`).

- **المرحلة 255 (2026-11-19 — `main`) — حل خطأ الـ ParseError وتعريب وترجمة محطة الكاشير والمالية والخزينة المركزية وإصدار الوصولات وسندات الاسترجاع والطباعة الحرارية 100% مع توسيع القاموس لـ 3,634 مفتاحاً واجتياز حزم الاختبارات بنسبة 100% (Fix ParseError & Comprehensive 100% Granular Localization for Cashier Workstation, Diagnostic Payment Forms, Receipts, Thermal POS Print & Financial Station with 3,634 Dictionary Keys & 100% Automated Feature Tests Pass)**:
  * **معالجة خطأ الـ ParseError وإزالة تداخل وسوم Blade**:
    - رصد وإصلاح تداخل وسوم Blade في `resources/views/cashier/index.blade.php` (الأسطر 603 و1031) حيث كانت تستدعي `{{ __('يحدد عند التسديد') }}` داخل وسم إيكو آخر `{{ ... }}`، وتحويلها إلى PHP نقي مباشر `__('يحدد عند التسديد')`.
    - تنظيف وسوم التعليقات والتداخلات اللفظية في أسطر الفائض المالي (1856، 1889، 1904).
    - تنظيف الذاكرة المؤقتة للواجهات وإعادة بنائها بنجاح كامل بدون أي خطأ تركيبي (`php artisan view:clear` و `php artisan view:cache`).
  * **التعريب والترجمة الشاملة الدقيقة 100% لمحطة الكاشير والمالية**:
    - `resources/views/cashier/index.blade.php`: تعريب ترويسة محطة الكاشير، مؤشرات الـ KPIs المالية الحية (المحصل اليوم، أجور الأطباء، ربح المستشفى)، محدد الفاصل الزمني للتحديث اللحظي، شارات المعاملات المعلقة عبر كافة التبويبات التسعة (الكل، المواعيد، طلبات الفحوصات، الطوارئ، المؤجلة، الخدج، العناية المركزة، العمليات، الاسترجاع)، مودال الاسترجاع المالي وصرف المستحقات (`medicalRefundModal`)، والرسائل التفاعلية في الجافاسكريبت والتنبيهات السريرية.
    - `resources/views/cashier/payment-form.blade.php`: تعريب كامل لشاشة تسديد كشفية الطبيب وفحوصات السونار، كروت التسعير المعتمد، التحمل والضمان، خيارات الدفع (نقدي، بطاقة، مركب)، والتأكيد والطباعة.
    - `resources/views/cashier/request-payment-form.blade.php`: تعريب شاشة تسديد طلبات المختبر والأشعة، الخدمات الإضافية، خصومات الفحوصات، إجراءات السداد (دفع، تأجيل، إلغاء)، وكروت الرصيد.
    - `resources/views/cashier/receipt.blade.php`: تعريب إيصال القبض وسندات الاسترجاع المالي، جدول الخدمات الطبية المفصلة، نسب التحمل والتأمين، وتنبيهات الإلغاء.
    - `resources/views/cashier/receipt-print.blade.php`: تعريب دقيق لقسيمة الطباعة الحرارية الموجهة لطابعات الـ POS (عرض 80mm) مع الحفاظ الهندسي التام على الأبعاد، دعم اتجاه الطباعة ديناميكياً (`rtl`/`ltr`)، تعريب كافة الحقول، الأطباء، رمز التيليجرام QR، وتفاصيل الكاشير.
    - `resources/views/surgeries/index.blade.php`: إضافة زر حجز عملية جديدة المتوافق مع صلاحية `create surgeries` وأسلوب `.sg-btn-create`.
  * **القاموس المركزي المحدث ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
    - توسيع القاموس المركزي من 3,611 إلى **3,634 مفتاح ترجمة مالي وسريري وإداري دقيق** بنسبة مطابقة 100%.
  * **حزم الاختبارات الآلية وضمان الجودة**:
    - بناء واجتياز حزمة `tests/Feature/CashierLocalizationTest.php` بنجاح 100% (`4 passed, 48 assertions`).
    - اجتياز حزمة `tests/Feature/SurgeriesLocalizationTest.php` بنجاح 100% (`7 passed, 76 assertions`).
    - اجتياز اختبارات فاصل التحديث وسندات الكاشير (`CashierRefreshIntervalControlTest`, `CashierReceiptReturnLinkTest`).
    - فحص الـ Blade Views والتأكد من صفرية أخطاء الترجمة والتجميع.

- **المرحلة 254 (2026-11-19 — `main`) — التعريب والترجمة الشاملة الدقيقة 100% لشاشات العمليات الأساسية وإدارة الحجوزات ولوحة الصالات والتقرير المطبوع ومصفوفة الصلاحيات مع توسيع القاموس إلى 3,611 مفتاحاً واجتياز حزم الاختبارات بنسبة 100% (Comprehensive 100% Granular Localization for Core Surgeries, Booking, Flight Board, Surgical Report Print, Tab Permissions Matrix with 3,611 Dictionary Keys & 100% Automated Tests Pass)**:
  * **تعريب وترجمة شاشات العمليات الأساسية الثمانية (8 Blade Views)**:
    - `resources/views/surgeries/index.blade.php`: تعريب كامل لسجل العمليات، مؤشرات الـ KPIs، الفلاتر، الشارات السريرية، جدول مخزن وعهدة العمليات (`or_store`) بكافة تفاصيل الوجبات وتواريخ الانتهاء والتنبيهات.
    - `resources/views/surgeries/waiting.blade.php`: تعريب شامل لقائمة الانتظار وصالات العمليات (Flight Board)، الصالات السريرية، مودال إضافة صالة جديدة، وتنبيهات الجافاسكريبت والمزامنة اللحظية.
    - `resources/views/surgeries/create.blade.php`: تعريب 100% لشاشة حجز عملية جديدة، بيانات المريض، الطبيب الجراح والمرسل، جدول العمليات متعددة الإجراءات، اختيار الغرف والإقامة، وإضافة الأطباء.
    - `resources/views/surgeries/edit.blade.php`: تعريب ترويسة تعديل حجز العملية، المسار التأميني، استعراض الوثائق، وتعديل العمليات والغرف.
    - `resources/views/surgeries/print.blade.php`: تقرير جراحي رسمي ثنائي اللغة ديناميكي (`dir="rtl"` / `dir="ltr"`)، تعريب كافة الحقول، توقيع الجراح، التخدير، وختم صالة العمليات.
    - `resources/views/surgeries/control.blade.php`: تعريب لوحة تحكم العمليات الجراحية وقائمة الانتظار السريعة وأزرار التنقل.
    - `resources/views/surgeries/settings/tabs.blade.php`: معالجة خطأ التركيب اللغوي `ParseError` بإزالة كافة التداخلات غير القانونية لوسوم Blade، تنظيف وسوم الحذف والتأكيد، وتعريب مصفوفة الصلاحيات بالكامل.
    - `resources/views/surgeries/show.blade.php`: تدقيق وتأكيد تعريب تفاصيل العملية وتبويباتها.
  * **القاموس المركزي المحدث ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
    - توسيع القاموس المركزي من 3,129 إلى **3,611 مفتاح ترجمة طبي وإداري وسريري دقيق** بنسبة مطابقة 100% وصفرية الأخطاء المفتاحية.
  * **حزم الاختبارات الآلية وضمان الجودة**:
    - اجتياز حزمة `tests/Feature/SurgeriesLocalizationTest.php` بنجاح 100% (`7 passed, 76 assertions`).
    - اجتياز حزمة `tests/Feature/SurgeryGranularTabControlTest.php` بنجاح 100% (`7 passed, 26 assertions`).
    - إجمالي توكيدات الاختبارات الناجحة: **102 توكيد برمجي (Assertions)**.
    - اجتياز فحص `Laravel Pint` لتنسيق الأكواد بنسبة 100%.

- **المرحلة 253 (2026-11-19 — `main`) — التعريب والترجمة الشاملة الدقيقة 100% لكافة محطات التشخيص المخبرية والشعاعية وفحوصات العمليات الجراحية وتوسيع القاموس لـ 3,129 مفتاحاً واختبارات الميزة الآلية (Comprehensive 100% Granular Localization for Laboratory, Radiology, Surgery Diagnostics across 31 Blade Views with 3,129 Dictionary Keys & Automated Feature Tests)**:
  * **محطة المختبر وسجل التحاليل (`/lab`, `/lab/requests/{id}`)**:
    - تعريب 100% لشاشات قائمة طلبات المختبر، الفلاتر المتطورة، الشارات السريرية، جدول الفحوصات، قراءات التحاليل مع الحفاظ الصارم على معادلات حساب الدهون التلقائية (Lipid Profile) في الجافاسكريبت.
  * **محطة الأشعة والسونار والإيكو (`/radiology-staff`, `/radiology`, `/radiology/types`)**:
    - تعريب وترجمة كاملة لشاشات كادر الأشعة، تبويبات الأقسام الأربعة (أشعة عادية، سونار، مفراس، رنين)، بطاقات الطوارئ، بادئات الأطباء المتغيرة، قوالب التقارير الطبية الجاهزة والعبارات السريعة، ومعاينة الصور والـ PDF.
    - تعريب كامل بنسبة 100% لنظام النطق الآلي الصوتي (Web Speech Synthesis) للنداء على المرضى باللغتين العربية والإنجليزية.
  * **دليل وأسعار وإعدادات الفحوصات المخبرية (`/lab-tests/*`)**:
    - تعريب وترجمة شاشات دليل التحاليل، إنشاء وتعديل الفحوصات، إعدادات أسعار التأمين ونسب التحمل، النطاقات المرجعية العمرية والجنسية، والمجموعات الفرعية للتحاليل.
  * **محطة فحوصات أشعة وسونار العمليات الجراحية (`/staff/surgery-radiology-tests/*`)**:
    - تعريب وترجمة شاملة لشاشة قائمة طلبات الأشعة للعمليات، شاشة اختيار مريض العملية (`/selection`)، شاشة تفاصيل وإدخال نتائج الأشعة للعملية (`/show`)، ونموذج التقرير الطبي المطبوع ثنائي اللغة المنسق هندسياً (`/print`).
  * **محطة الفحوصات المخبرية للعمليات الجراحية (`/staff/surgery-lab-tests/*`)**:
    - تعريب وترجمة شاملة لكروت الإحصائيات الأربعة، قائمة العمليات الجراحية، شاشة الاختيار، شاشة فحص العملية وإدخال نتائج المختبر وحزم التحاليل، كرت بيانات المريض والسرير، وإرسال النتائج عبر واتساب، ونموذج الطباعة الرسمي للعملية.
  * **القاموس المركزي المحدث ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
    - توسيع القاموس إلى **3,129 مفتاح ترجمة طبي وإداري وسريري دقيق** مع تدقيق صفري للأخطاء وتوافق بنية JSON بنسبة 100%.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/DiagnosticLocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/DiagnosticLocalizationTest.php))**:
    - بناء واجتياز حزمة اختبارات شاملة `DiagnosticLocalizationTest` تغطي كافة محطات المختبر والأشعة ودليل الأسعار وفحوصات العمليات بنسبة نجاح 100% (`6 passed, 80 assertions`).
    - اجتياز فحص `Laravel Pint` لتنسيق الأكواد.

- **المرحلة 252 (2026-11-19 — `main`) — تنظيف ترويسة القائمة بالكامل وتثبيت محول لغة مصغر في الزاوية العلوية المعاكسة للقائمة ديناميكياً (Dynamic Opposite-Corner Micro Dropdown Language Switcher with Clean Sidebar Header)**:
  * **تنظيف القائمة الجانبية وإعادة الشعار لوضعه الصافي ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
    - إزالة أي أزرار أو عناصر دخيلة من داخل ترويسة القائمة الجانبية (`.sidebar-header`) ليعود الشعار وهوية المستخدم في المنتصف دون أي تداخل بصري.
  * **تثبيت المنسدلة المصغرة في الزاوية المعاكسة للقائمة ديناميكياً (`.lang-corner-dropdown`)**:
    - استخدام الخاصية المنطقية `inset-inline-end: 20px; top: 10px;` لتتحرك القائمة المنسدلة تلقائياً بحسب اتجاه اللغة:
      * في الوضع العربي (RTL): القائمة على اليمين ⬅️ زر اللغة في **أعلى اليسار بالزاوية** (`left: 20px`).
      * في الوضع الإنجليزي (LTR): القائمة على اليسار ⬅️ زر اللغة في **أعلى اليمين بالزاوية** (`right: 20px`).
    - تصميم مصغر وناعم بارتفاع 28px (`[ 🇮🇶 عربي ▾ ]` / `[ 🇬🇧 EN ▾ ]`) بخلفية زجاجية معتمة (94%) وظلال ناعمة لمنع تغطية أزرار الصفحات.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/LocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/LocalizationTest.php))**:
    - فحص وجود `.lang-corner-dropdown` والزر `.lang-dropdown-btn` والأعلام المتجهة `iq.svg` و `gb.svg`.
    - اجتياز كامل حزمة `LocalizationTest` بنجاح 100% (`9 passed, 72 assertions`).

- **المرحلة 251 (2026-11-19 — `main`) — تحرير القائمة الجانبية بالكامل ونقل محول اللغة بالأعلام إلى الزاوية العلوية الثابتة (Decoupling Language Switcher from Sidebar to Floating Top Corner Widget)**:
  * **فصل محول اللغة عن القائمة الجانبية ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
    - حذف كرت محول اللغة بالكامل من داخل كود القائمة الجانبية (`#appSidebar` / `.sidebar-header`) لتحرير المساحة الرأسية بنسبة 100% ومنع إزاحة أو زحزحة عناصر وفئات القائمة.
    - تحويله إلى ويدجت عائم فاخر ومستقل (`.floating-lang-pill`):
      * في الوضع العربي (RTL): يثبت في الزاوية العلوية اليسرى للشاشة (`top: 14px; left: 24px; z-index: 1040`).
      * في الوضع الإنجليزي (LTR): يثبت في الزاوية العلوية اليمنى للشاشة (`top: 14px; right: 24px; z-index: 1040`).
      * تصميم زجاجي أنيق (White 92% مع Blur 8% وظلال ناعمة) يضم علمي العراق وبريطانيا المتجهين.
      * إخفاء تلقائي في شاشات التابلت والموبايل (`display: none` تحت 1400px) للاكتفاء بالزر المدمج المباشر في `#tabletTopbar`.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/LocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/LocalizationTest.php))**:
    - تحديث اختبارات `LocalizationTest` للتحقق من وجود `.floating-lang-pill` و `.lang-switcher-pill` وصحة الروابط والأعلام.
    - اجتياز جميع اختبارات `LocalizationTest` بنجاح 100% (`9 passed, 72 assertions`).

- **المرحلة 250 (2026-11-19 — `main`) — التعريب والترجمة الشاملة الدقيقة 100% لعيادات الاستشارية ومحطة الطبيب وسجل الطلبات الطبية والسجل التاريخي للمريض مع تنبيه الفحوصات المعلقة وتوسيع القاموس لـ 1,871 مفتاحاً (Comprehensive 100% Granular Localization for Outpatient Visits, Doctor Workstation, Medical Orders & Tests History with Pending Tests Alert & 1,871 Dictionary Keys)**:
  * **تعريب وترجمة محطة الطبيب وتنبيه الفحوصات المعلقة والعمود الأيسر الدائم ([resources/views/doctors/visits/show.blade.php](file:///C:/get/hospital-system/resources/views/doctors/visits/show.blade.php))**:
    - تعريب وترجمة تنبيه الفحوصات المعلقة السريري: `{{ __("توجد فحوصات طبية معلقة بانتظار صدور النتائج (عددها: :count)") }}` و `{{ __("يُمنع إنهاء الزيارة حتى تكتمل وتصدر نتائج كافة الفحوصات (المخبرية والشعاعية)...") }}` وزر `{{ __("تعليق واستدعاء التالي") }}` والتنبيه عند محاولة الإنهاء.
    - تعريب العمود الأيسر الدائم للسجل الطبي ونتائج الفحوصات: شريط مراحل وتتبع الطلبات (طلب الطبيب، الكاشير، القسم المنفذ، النتيجة جاهزة)، كرت الوصفة الطبية (RX) وصرف الصيدلية، جدول الفحوصات السريري للمختبر والأشعة والتمريض، تقارير الفحوصات المرفقة وملفات الـ PDF والصور الشعاعية، الانطباع والمشاهدات السريرية، وأرشيف الطلبات الملغاة، وتبويبات الزيارات السابقة والمواعيد والعمليات.
  * **تعريب وترجمة السجل الطبي الشامل للمريض ([resources/views/doctors/patient-history.blade.php](file:///C:/get/hospital-system/resources/views/doctors/patient-history.blade.php))**:
    - تعريب 100% لبطاقة المريض والحساسية والأمراض المزمنة، فلاتر السجل الطبي (نطاق التاريخ، نوع الحدث، الطبيب، والبحث)، الخط الزمني للرحلة الطبية (Patient Medical Journey)، وبطاقات الوصول السريع (آخر وصفة، آخر فحص مخبري، ملخص الخروج).
  * **تعريب وترجمة سجل وإدارة الطلبات الطبية المركزي ([resources/views/staff/requests/index.blade.php](file:///C:/get/hospital-system/resources/views/staff/requests/index.blade.php))**:
    - تعريب جدول الطلبات العادية، طلبات طوارئ المختبر والأشعة، فلاتر الأقسام، شارة البث المباشر ومؤقت آخر تحديث، ومودال إدخال وتدقيق تحاليل الطوارئ مع تقييم المعدلات المرجعية المباشر في الجافاسكريبت.
  * **تعريب شاشات الزيارات ومحطة الطبيب ومطبوعات الروشتة والمواعيد**:
    - تعريب `resources/views/visits/index.blade.php`، `visits/create.blade.php`، `visits/show.blade.php`، `visits/edit.blade.php`، `doctors/visits/index.blade.php`، `doctors/visits/prescription-print.blade.php`، و `doctors/visits/appointment-print.blade.php`.
  * **القاموس المركزي المحدث ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
    - توسيع القاموس إلى **1,871 مفتاح ترجمة** مع تدقيق طبي ولغوي بنسبة 100%.
  * **التوافقية البرمجية والاختبارات الآلية ([tests/Feature/VisitsLocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/VisitsLocalizationTest.php))**:
    - تصحيح استعلامات الـ SQL في `DoctorVisitController` و `StaffRequestController` لتكون متوافقة قياسياً مع MySQL و SQLite (`CURRENT_DATE` وحسابات الأولوية `CASE WHEN`).
    - إغلاق قسم المحتوى `@endsection` في `staff/requests/index.blade.php`.
    - بناء 4 اختبارات ميزة شاملة لجميع شاشات الزيارات ومحطة الطبيب وسجل الطلبات وتنبيه الفحوصات المعلقة واجتيازها بنجاح 100% (`4 passed, 78 assertions`).

- **المرحلة 249 (2026-11-18 — `main`) — التعريب والترجمة الدقيقة 100% للمحطة السريرية الموحدة لإقامة العمليات الجراحية بكافة تبويباتها الـ 10 ومودالاتها ونصوص الجافاسكريبت وتوسيع القاموس لـ 1,195 مفتاحاً (Comprehensive 100% Granular Localization for Unified Clinical Inpatient Station across All 10 Tabs, 4 Modals & Dynamic JS with 1,195 Translation Keys & Automated Feature Tests)**:
  * **الترجمة الشاملة الدقيقة 100% للمحطة السريرية الموحدة ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - تغريب وترجمة تفصيلية بنظام `__()` دون استثناء لكافة التبويبات الـ 10 وعناصر الهيدر والمودالات الأربعة:
      1. شريط التنقل والترويسة السريرية التنفيذية وبيانات المريض والعملية والطور النشط (Pre-Op / Post-Op) وشارات الحالة وأزرار الإجراءات.
      2. شريط التبويبات التفاعلي وحسابات الصلاحيات (`$shortTabLabels`) وبطاقات المحتوى.
      3. تبويب السيرة المرضية (`history`): الشكوى، التاريخ الجراحي، والتاريخ الدوائي والحساسية وملاحظات التمريض.
      4. تبويب العلامات الحيوية وميزان السوائل (`vitals`): الصافي، السوائل الوريدية، الفم، الدم ومشتقاته، البول، قراءات الدرنقات، القيء، شيت التمريض، والمودالات.
      5. تبويب خطة العلاج والتشخيص (`plan`): توجيهات الجراح، الأهداف العلاجية، وتوصيات التخريج.
      6. تبويب الفحوصات والمختبر والأشعة (`tests`): طلبات التحاليل والأشعة، نسب التحمل والضمان، المودالات التفاعلية، والنتائج المخبرية والشعاعية.
      7. تبويب استبيان رضا النزيل (`inpatient_survey`): كرت الباركود والمؤقت التلقائي وأزرار الطباعة، السجل التقييمي لـ 27 معياراً عبر 5 بطاقات خدمية وسريرية.
      8. تبويب سجل إعطاء الأدوية (MAR - `treatments`): جدول الجرعات الـ 24 ساعة، البحث الذكي بالصيدلية مع الرصيد والمطابقة، التوثيق الفوري للتمريض ورسائل التنبيه والـ SweetAlert.
      9. تبويب مستلزمات عهدة العمليات (`supplies`): نموذج الصرف متعدد المواد، المستلزمات المصروفة، أرقام الدفعات وتواريخ الصلاحية.
      10. تبويب شيت متابعة الطبيب المقيم (`follow_up`): العلامات السريرية الخمس بالإنجليزية، الملاحظات، وجدول المتابعات الدورية.
      11. تبويب الاستشارات الطبية التخصصية (`consultations`): اختيار الاستشاري بقائمة تفاعلية بالاسم والتخصص والأجر، نموذج التوصيات والردود، والتحكم بالحالة.
      12. مودالات طلب التحاليل المخبرية (`orderLabTestsModal`)، طلب الأشعة (`orderRadiologyModal`)، قراءات الدرنقات المتعددة (`drainDetailsModal`)، وتفاصيل الدرنقات التاريخية (`viewDrainDetailsModal`).
      13. كافة رسائل الجافاسكريبت التفاعلية ومربعات التأكيد (SweetAlert2) والتنبيهات السريعة (Toastr).
  * **القاموس المركزي المحدث ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
    - توسيع القاموس إلى 1,195 مفتاح ترجمة طبي وإداري وسريري مع الالتزام التام بالمعايير الطبية المعتمدة وصحة بنية الـ JSON بنسبة 100%.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/ClinicalStationLocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/ClinicalStationLocalizationTest.php))**:
    - بناء اختبار ميزة متخصص `ClinicalStationLocalizationTest` للتحقق التلقائي من ترجمة عناوين وتفاصيل وتبويبات وشارات شاشة `/surgery-stations/clinical/{surgery}` بالجلستين العربية والإنجليزية.
    - اجتياز كامل حزمة اختبارات الترجمة والتعريب (`11 passed, 103 assertions`) بنجاح 100%.
    - اجتياز فحص `Laravel Pint` للتنسيق بنسبة 100%.

- **المرحلة 248 (2026-11-18 — `main`) — تصميم وتنسيق زر تبديل اللغة بأعلام العراق وبريطانيا المتجهة وشريط الاختيار الثنائي المتطور (Modern Segmented Language Switcher with Official Iraq & UK Vector Flags and Dedicated Sidebar Control)**:
  * **الأعلام المتجهة الرقمية فائقة النقاء ([public/images/flags/iq.svg](file:///C:/get/hospital-system/public/images/flags/iq.svg), [public/images/flags/gb.svg](file:///C:/get/hospital-system/public/images/flags/gb.svg))**:
    - بناء وتضمين علم العراق الرقمي الرسمي (أحمر، أبيض، أسود مع عبارة "الله أكبر" بالخط الكوفي الأخضر) وعلم المملكة المتحدة (Union Jack) بالهندسة المتجهة الصرفة لضمان دقة العرض وتجنب مشاكل خطوط الإيموجي في ويندوز.
  * **إعادة التوزيع والتنسيق المعماري للواجهة ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php), [resources/views/auth/login.blade.php](file:///C:/get/hospital-system/resources/views/auth/login.blade.php))**:
    - نقل زر التبديل من سطر المستخدم الضيق إلى كرت تحويل مزدوج مخصص ومستقل (`.lang-switcher-pill`) في القائمة الجانبية يضم خياري `[ 🇮🇶 عربي ]` و `[ 🇬🇧 English ]` مع إبراز الخيار النشط بظلال وخلفية زرقاء بارزة (`.lang-btn.active`).
    - تحديث زر الترويسة العلوية للتابلت والموبايل (`#tabletTopbar`) وشاشة تسجيل الدخول (`login.blade.php`) ليعرض علم الدولة المستهدفة بدقة ووضوح بدلاً من الأيقونة العامة `fa-globe`.
    - تنظيف بطاقة هوية الموظف في القائمة الجانبية وإلغاء الازدحام مع زر تسجيل الخروج.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/LocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/LocalizationTest.php))**:
    - إضافة اختبار `test_language_switcher_renders_iraq_and_uk_flags_and_segmented_pill` للتحقق من وجود ملفات الأعلام، نصوص الـ SVG، وظهور شريط التحويل في العرض.
    - اجتياز جميع اختبارات `LocalizationTest` بنجاح 100% (`10 passed, 91 assertions`).

- **المرحلة 247 (2026-11-18 — `main`) — الترجمة الشاملة الدقيقة (100%) لواجهة تفاصيل العمليات ولوحة التحكم والشاشات التمهيدية وتوسيع القاموس لـ 822 مفتاحاً (Comprehensive Granular Localization for Surgery Show Details, Core Layout, Dashboard & Pre-Op Stations with 822 Keys & Automated Feature Tests)**:
  * **واجهة تفاصيل العملية الجراحية ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - تعريب وترجمة كاملة وشاملة 100% بنظام `__()` دون استثناء أي عنصر لكافة الأقسام الـ 11:
      1. شريط الإحصائيات العلوي وحالة العملية (Pre-Op, Intra-Op, Post-Op) والموقع ومؤقت المدة.
      2. كرت التشخيص الطبي وتوصيات الجراح وخطة العملية.
      3. جدول وأعضاء الفريق الجراحي الخمسة ومحدد الدور والأطباء.
      4. خطة التخدير وملاحظات التقييم وأنواعه وأدويته.
      5. المستلزمات الطبية المصروفة من عهدة العمليات مع نظام الصرف متعدد المواد المحدث.
      6. قائمة الأجهزة الطبية المستخدمة.
      7. الفحوصات المخبرية وتحاليل الدم المطلوبة ونتائجها.
      8. الفحوصات الشعاعية والسونار وملفات الـ PDF.
      9. متابعات وسجل الطبيب المقيم والعلامات السريرية الخمسة.
      10. الاستشارات الطبية التخصصية السريرية وأجورها وتوصيات الاستشاري.
      11. مودالات تغيير الموعد والإلغاء وتأكيد الإجراءات ورسائل الجافاسكريبت.
  * **شاشات الملاحة ولوحة التحكم والتسجيل ومحطات العمليات ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php), [resources/views/dashboard.blade.php](file:///C:/get/hospital-system/resources/views/dashboard.blade.php), [resources/views/auth/login.blade.php](file:///C:/get/hospital-system/resources/views/auth/login.blade.php), [resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php), [resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php), [resources/views/surgery-stations/anesthesia/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/anesthesia/index.blade.php), [resources/views/surgery-stations/nursing/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/nursing/index.blade.php), [resources/views/surgery-stations/resident/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/resident/index.blade.php), [resources/views/surgery-stations/surgeon/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/surgeon/index.blade.php), [resources/views/nurse-availability/index.blade.php](file:///C:/get/hospital-system/resources/views/nurse-availability/index.blade.php), [resources/views/nurse-availability/off-duty.blade.php](file:///C:/get/hospital-system/resources/views/nurse-availability/off-duty.blade.php))**:
    - تعريب عناصر القائمة الجانبية والتنقل، إحصائيات لوحة التحكم، شاشة الدخول وبطاقة التوفر خارج الدوام، ومحطات العمليات التمهيدية لضمان بيئة عمل ثنائية اللغة متناسقة.
  * **القاموس المركزي المحدث ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
    - توسيع القاموس من 526 مفتاحاً إلى 822 مفتاح ترجمة طبي وإداري دقيق دون أي تكرار وبفحص تحقق صفري للأخطاء.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/SurgeryShowLocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryShowLocalizationTest.php), [tests/Feature/LocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/LocalizationTest.php))**:
    - بناء اختبار ميزة متخصص `SurgeryShowLocalizationTest` للتحقق التلقائي من ترجمة عناوين وتفاصيل وحقول ومصفوفات شاشة `/surgeries/{id}` بجلستي العربية والإنجليزية.
    - اجتياز جميع اختبارات التعريب وحزمة اختبارات العمليات (`36 passed, 227 assertions`) بنسبة نجاح 100%.

- **المرحلة 246 (2026-11-18 — `main`) — التأسيس المعماري لدعم تعدد اللغات (عربي / إنجليزي) والتبديل الفوري واتجاه LTR/RTL (Multi-Language Localization Foundation with Instant AR/EN Switcher, SetLocale Middleware & LTR Sidebar Layout)**:
  * **الوسيط ومحرك اللغة ([app/Http/Middleware/SetLocale.php](file:///C:/get/hospital-system/app/Http/Middleware/SetLocale.php), [bootstrap/app.php](file:///C:/get/hospital-system/bootstrap/app.php))**:
    - بناء وسيط `SetLocale` لضبط لغة التطبيق النشطة `App::setLocale()` استناداً لقيمة الجلسة `session('locale', 'ar')` مع حصرها بالأكواد المعتمدة (`ar`, `en`) والعودة التلقائية للعربية عند أي قيمة غير صالحة.
    - تسجيل الوسيط ضمن حزمة `$middleware->web(append: [SetLocale::class])`.
  * **المسار والقاموس المركزي ([routes/web.php](file:///C:/get/hospital-system/routes/web.php), [lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
    - تسجيل مسار التبديل الفوري `GET /lang/{locale}` مع حفظ الاختيار بالجلسة والرجوع التلقائي للصفحة الحالية `redirect()->back()`.
    - إنشاء قاموس الترجمة المركزي `lang/en.json` متضمناً أهم المصطلحات الطبية والإدارية (أقسام المستشفى، المحطات السريرية، الحالات، الإجراءات، الأزرار، والترويسات).
  * **واجهة المستخدم والقالب الأساسي ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
    - جعل اتجاه ولغة المستند ديناميكياً: `<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">`.
    - إضافة أزرار وشارات تبديل اللغة التفاعلية في الترويسة العلوية للموبايل/التابلت (`#tabletTopbar`) وفي بطاقة هوية الموظف بالقائمة الجانبية (`sidebar-user`).
    - ضبط هندسة وتنسيقات القائمة الجانبية بالكامل (Fixed Coordinates: `top: 0; bottom: 0; z-index: 1030`) وضبط اتجاهات ومحاذاة الشاشات الكبيرة والمحمولة (`left: 0` في LTR و `right: 0` في RTL) مع تدفق المحتوى الرئيسي `main-content` بسلاسة تامة.
  * **الاختبارات الآلية وتنسيق الكود ([tests/Feature/LocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/LocalizationTest.php))**:
    - بناء 5 اختبارات ميزة آلية تغطي التحويل للإنجليزية، والعودة للعربية، وحماية المدخلات غير الصالحة، وعرض `dir="ltr"` والترجمات، وعرض `dir="rtl"` بنسبة نجاح 100% (`5 passed, 15 assertions`).
    - اجتياز فحص `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 245 (2026-11-18 — `main`) — نظام طلب وإدارة الاستشارات الطبية التخصصية السريرية للعمليات الجراحية باسم الطبيب وتوثيق الردود (Medical Consultations Inpatient Order System by Consultant Doctor with Clinical Response & Recommendations Tracking)**:
  * **قاعدة البيانات والنموذج ([database/migrations/2026_11_18_120000_create_surgery_consultations_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_18_120000_create_surgery_consultations_table.php), [app/Models/SurgeryConsultation.php](file:///C:/get/hospital-system/app/Models/SurgeryConsultation.php), [app/Models/Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php), [app/Models/Doctor.php](file:///C:/get/hospital-system/app/Models/Doctor.php))**:
    - إنشاء جدول مستقل `surgery_consultations` متضمناً: معرف العملية `surgery_id`، معرف الطبيب الاستشاري `doctor_id`، التخصص `specialty`، سبب الاستشارة والتشخيص `reason`، درجة الأولوية `priority` (`routine`, `urgent`, `stat`)، حالة الاستشارة `status` (`pending`, `completed`, `cancelled`)، رد وتوصيات الاستشاري `consultant_response` و `consultant_recommendations`، أجر الاستشارة `fee`، مع توثيق هوية الطالب والرد والوقت (`requested_by`, `responded_by`, `responded_at`).
    - ربط العلاقات في موديلات `Surgery` (`hasMany(SurgeryConsultation::class)`) و `Doctor` (`hasMany(SurgeryConsultation::class)`).
    - إضافة دوال المساعدة للشارات والمسميات (`priority_label`, `priority_badge_class`, `status_label`, `status_badge_class`).
  * **الصلاحيات ومصفوفة التحكم ([app/Models/SurgeryTabPermission.php](file:///C:/get/hospital-system/app/Models/SurgeryTabPermission.php))**:
    - تسجيل تبويب `consultations` في `TABS` للمحطة السريرية وتبويب `detail_consultations` في `DETAIL_TABS` لصفحة تفاصيل العملية.
    - ضبط مصفوفة الوصول التلقائية لجميع الأدوار الطبية والتمريضية والجراحية السبعة.
  * **الكنترولرات والمسارات ([app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
    - تسجيل مسارات إرسال طلب الاستشارة (`POST /clinical/{surgery}/consultations`)، توثيق رد الاستشاري (`POST /clinical/{surgery}/consultations/{consultation}/respond`)، وإلغاء الطلب المعلق (`DELETE /clinical/{surgery}/consultations/{consultation}`).
    - تزويد المحطة السريرية بقائمة الأطباء الاستشاريين المتاحين مرتبين مع إبراز توفر اليوم وأجورهم وتخصصاتهم.
    - السماح للطبيب الاستشاري المعني بالاستشارة بمعاينة ملف العملية الجراحية وتوثيق التوصيات.
  * **الواجهات الأمامية وتجربة المستخدم ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php), [resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - إضافة تبويب مخصص ومستقل للاستشارات الطبية التخصصية (`consultations`) بشارة إحصائية تلخص عدد الاستشارات المعلقة والمكتملة.
    - نموذج طلب استشارة أنيق مع قائمة منسدلة للأطباء الاستشاريين وتعبئة تلقائية فورية للتخصص والأجر، وحقول الأولوية والسبب.
    - كروت استعراض السجل الطبي للاستشارات مع شارات الحالة والأولوية، ونموذج توثيق رد وتوصيات الاستشاري القابل للطي السلس، وإمكانية إلغاء الطلبات المعلقة.
    - عرض سجل الاستشارات الطبية في صفحة تفاصيل العملية الجراحية ضمن تبويب `detail_consultations`.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryConsultationTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryConsultationTest.php))**:
    - بناء 6 اختبارات ميزة آلية شاملة تغطي فتح التبويب، حفظ الطلب، توثيق الرد، إلغاء الطلب، حماية المكتمل من الإلغاء، وعرض الاستشارات في صفحة العملية بنسبة نجاح 100% (`6 passed, 32 assertions`).
    - اجتياز فحص `Laravel Pint` بالكامل دون أي أخطاء.

- **المرحلة 244 (2026-11-18 — `main`) — خانات تقييم العلامات السريرية الخمس بالإنجليزية مع التبديل التفاعلي الفوري بين Positive و Negative التلقائي (5 Clinical Signs Checkboxes with Instant English Positive/Negative Toggle in Resident Follow-Up Sheet)**:
  * **قاعدة البيانات والنموذج ([database/migrations/2026_11_18_110000_add_clinical_signs_to_resident_station_follow_ups_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_18_110000_add_clinical_signs_to_resident_station_follow_ups_table.php), [app/Models/ResidentStationFollowUp.php](file:///C:/get/hospital-system/app/Models/ResidentStationFollowUp.php))**:
    - إضافة عمودي `clinical_signs` (JSON) و `other_notes` (Text) إلى جدول `resident_station_follow_ups`.
    - تعريف العلامات الخمس `availableClinicalSigns()`: `Oriented`، `Clear chest`، `Soft abdomen`، `Urine output`، `Yellowish discolouration`.
    - تزويد النموذج بدوال `positive_signs` و `negative_signs` لاستخراج العلامات الإيجابية والسلبية بدقة.
  * **الكنترولرات وتجميع الملاحظات ([app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php), [app/Http/Controllers/ResidentStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ResidentStationController.php))**:
    - استلام العلامات المحددة وحفظها وتجميع الملاحظات بالإنجليزية الصريحة `✓ Positive:` و `✗ Negative:` للتوافق التام مع التقارير والطباعة.
  * **الواجهات الأمامية وتجربة المستخدم ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php), [resources/views/surgery-stations/resident/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/resident/show.blade.php), [resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - إضافة الخيارات الخمس في تبويب المتابعة للطبيب المقيم.
    - الحالة الافتراضية (Default) بدون تضليل رمادي تظهر كشارة حمراء بارزة: `Negative` (`badge bg-danger text-white`).
    - بمجرد وضع علامة الصح (جيك) تتحول الشارة فورياً وديناميكياً إلى خضراء بارزة: `Positive` (`badge bg-success text-white`) بالإنجليزية حصراً وبدون نصوص عربية.
    - عرض سجلات المتابعة السابقة بتصنيف `Positive:` و `Negative:` واضح.
  * **الاختبارات الآلية وتنسيق الكود ([tests/Feature/ResidentFollowUpClinicalSignsTest.php](file:///C:/get/hospital-system/tests/Feature/ResidentFollowUpClinicalSignsTest.php))**:
    - اجتياز 5 اختبارات ميزة شاملة بنسبة 100% (`5 passed, 38 assertions`) وفحص `Laravel Pint`.

- **المرحلة 243 (2026-11-18 — `main`) — إعادة تصميم وتطوير واجهة تعديل المستخدمين بتخطيط مزدوج ذكي وتصنيف مرئي للأدوار وبطاقة هوية الموظف (Modern Redesign of User Edit Interface with Dual-Column Layout, Smart Categorized Roles Matrix, and Employee Profile Card)**:
  * بناء تخطيط مزدوج (4:8) مع بطاقة هوية الموظف الجانبية، وتصنيف الأدوار الـ 36 لمجموعات طبية وإدارية مع فلترة لحظية وبحث وعداد للأدوار، واجتياز `tests/Feature/UserEditModernViewTest.php`.

- **المرحلة 242 (2026-11-18 — `main`) — استبدال تنبيه التوفر الداكن بنظام Toastr الطبي الناصع وتوحيد مصفوفة ومسميات أدوار التخدير ومشرف التمريض (Medical Clean Toastr Migration & Role Permission Harmonization for Anesthesia and Head Nurse)**:
  * إزالة خلفية `bg-dark` من شاشة التوفر واستخدام Toastr الطبي الفاتح، ومواءمة دوري `anesthesia` و `head_nurse` وصلاحية `manage nurse availability` في المصفوفة.

- **المرحلة 241 (2026-11-18 — `main`) — واجهة الهبوط المخصصة والذكية للممرضين خارج جدول الدوام الرسمي مع التحقق وإعادة المحاولة الفورية (Dedicated Off-Duty Landing Screen for Unscheduled Nurses with Live Activation Check & Access Recovery)**:
  * **شاشة الهبوط المخصصة ([resources/views/nurse-availability/off-duty.blade.php](file:///C:/get/hospital-system/resources/views/nurse-availability/off-duty.blade.php))**:
    - استبدال رسالة التنبيه الحمراء لشاشة تسجيل الدخول بواجهة هبوط متكاملة، أنيقة ورسمية تحمل هوية النظام والمستشفى.
    - عرض اسم الممرض، بريده، شارة `🔴 خارج جدول الدوام الرسمي لليوم`، وتاريخ اليوم الهجري/الميلادي المنسق.
    - رسالة إرشادية طبية واضحة تفيد بعدم إدراج الحساب بجدول دوام اليوم، مع توجيه للتواصل مع مدير أو مشرف التمريض.
    - زر تفاعلي ذكي `[ 🔄 فحص التفعيل والدخول للنظام ]` لتمكين الممرض من الدخول الفوري لمحطة التمريض بمجرد تفعيله من قبل المشرف دون الحاجة لإعادة كتابة البريد وكلمة المرور مجدداً، مع زر تسجيل خروج رسمي.
  * **الكنترولر والميدلوير ([app/Http/Controllers/NurseAvailabilityController.php](file:///C:/get/hospital-system/app/Http/Controllers/NurseAvailabilityController.php), [app/Http/Middleware/CheckNurseAvailability.php](file:///C:/get/hospital-system/app/Http/Middleware/CheckNurseAvailability.php), [app/Http/Controllers/Auth/LoginController.php](file:///C:/get/hospital-system/app/Http/Controllers/Auth/LoginController.php))**:
    - إضافة مساري `nurse.off-duty` و `nurse.check-access` في ([routes/web.php](file:///C:/get/hospital-system/routes/web.php)).
    - توجيه الممرض غير المداوم فور تسجيل الدخول إلى واجهة `nurse.off-duty`.
    - تحديث وسيط الحماية لتثبيت الممرض غير المداوم داخل واجهة الهبوط ومنع وصوله لأي سجلات أو محطات سريرية مع استثناء مسارات الفحص والخروج.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/NurseAvailabilityTest.php](file:///C:/get/hospital-system/tests/Feature/NurseAvailabilityTest.php))**:
    - توسيع حزمة الاختبارات لتشمل 10 اختبارات ميزة تغطي التحويل لشاشة الهبوط، ومنع التسلل للمحطة، وإعادة الفحص والدخول السلس بنسبة نجاح 100% (`10 passed, 41 assertions`).

- **المرحلة 240 (2026-11-17 — `main`) — نظام إدارة توفر وجدول دوام التمريض اليومي وتوزيع الطوابق وتقييد تسجيل الدخول (Nurse Availability & Daily Floor Scheduling Management System with Login Restriction)**:
  * **قاعدة البيانات ونموذج التوفر ([database/migrations/2026_11_17_130000_create_nurse_availabilities_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_17_130000_create_nurse_availabilities_table.php), [app/Models/NurseAvailability.php](file:///C:/get/hospital-system/app/Models/NurseAvailability.php), [app/Models/User.php](file:///C:/get/hospital-system/app/Models/User.php))**:
    - إنشاء جدول مستقل `nurse_availabilities` لحفظ سجلات دوام وأرشيف التمريض اليومي (`user_id`, `date`, `is_available`, `assigned_floor`, `shift`, `notes`, `created_by`) مع قيد فريد يمنع التكرار لليوم الواحد.
    - ربط العلاقات ودالة الفحص السريعة `isNurseAvailableToday()` في موديل `User`.
  * **الكنترولر ومحرك التوفر ([app/Http/Controllers/NurseAvailabilityController.php](file:///C:/get/hospital-system/app/Http/Controllers/NurseAvailabilityController.php))**:
    - بناء كنترولر متكامل مع فلاتر التاريخ والطوابق والبحث، واحتساب الإحصائيات (إجمالي الكادر، المتاحين بالدوام، خارج الدوام، نسبة التغطية).
    - دعم التبديل اللحظي (AJAX Toggle Switch) للتوفر، وتحديث الطابق المخصص لليوم وربطه بمخزن الطابق تلقائياً، والتحكم الجماعي (تفعيل دوام الكل / إيقاف دوام الكل).
  * **الواجهة وتجربة المستخدم ([resources/views/nurse-availability/index.blade.php](file:///C:/get/hospital-system/resources/views/nurse-availability/index.blade.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
    - تصميم واجهة متطورة بكروت إحصائية علوية، مفاتيح تبديل سريعة، قوائم منسدلة فورية لاختيار الطابق والشفت مع إشعارات Toast تفاعلية.
    - إضافة رابط `جدول دوام وتوفر التمريض` في القائمة الجانبية المخصصة للمدراء ومسؤولي التمريض.
  * **حماية النظام وتقييد تسجيل الدخول ([app/Http/Controllers/Auth/LoginController.php](file:///C:/get/hospital-system/app/Http/Controllers/Auth/LoginController.php), [app/Http/Middleware/CheckNurseAvailability.php](file:///C:/get/hospital-system/app/Http/Middleware/CheckNurseAvailability.php), [bootstrap/app.php](file:///C:/get/hospital-system/bootstrap/app.php))**:
    - قفل تسجيل الدخول لأي ممرض غير مسجل كمتوفر اليوم مع رسالة توجيهية صريحة.
    - ميدلوير حماية استباقي يمنع أي إجراء في حال إيقاف دوام الممرض أثناء الشفت.
  * **الصلاحيات والتحقق الآلي ([database/seeders/RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php), [tests/Feature/NurseAvailabilityTest.php](file:///C:/get/hospital-system/tests/Feature/NurseAvailabilityTest.php))**:
    - إضافة دور `head_nurse` وصلاحية `manage nurse availability`.
    - بناء 8 اختبارات ميزة متخصصة تؤكد كامل جوانب الميزة بنسبة نجاح 100% (`8 passed, 30 assertions`).

- **المرحلة 239 (2026-11-17 — `main`) — الانتقال التلقائي الذكي للمرحلة السريرية وإلغاء أزرار التبديل اليدوية المربكة (Smart Auto Phase Transition & Header Badge in Clinical Station)**:
  * **الباك إند والتعرف الذكي ([app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php))**:
    - تطوير منطق تحديد المرحلة التلقائي (`$isPostOpReady`) ليفحص اكتمال صالة العمليات (`operationTheaterStation completed`)، أو اكتمال التخدير، أو اكتمال محطة ما قبل العملية، أو دخول المريض مرحلة الإفاقة/الانتهاء (`completed`/`recovery`).
    - إنشاء سجل ما بعد العملية `postOpStation` تلقائياً فور جاهزية الحالة، وتعيين المرحلة الافتراضية تلقائياً دون إجبار الكادر الطبي والتمريضي على التبديل اليدوي، مع استمرار دعم الاستعراض اليدوي الاختياري.
  * **الواجهة الأمامية وتجربة المستخدم ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - إزالة زري التبديل المربكين `[ ما قبل العملية ]` و `[ ما بعد العملية ]` وحذف فئات التنسيق القديمة غير المستخدمة.
    - استبدالهما بشارة مرحلة سريرية ذكية معتمدة على وضع المريض: `[ ⏳ تحضير ما قبل العملية ]` باللون الأزرق أو `[ 🩺 عناية ما بعد العملية (الرقود) ]` باللون الأخضر.
    - إضافة رابط أرشيف واستعراض ثانوي أنيق وصغير بجانب الشارة (`[ 📂 أرشيف ما قبل العملية ]` أو `[ ما بعد العملية ⬅️ ]`) لمن يرغب بمراجعة السجل الآخر.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/ClinicalStationMultiDrainTest.php](file:///C:/get/hospital-system/tests/Feature/ClinicalStationMultiDrainTest.php))**:
    - إضافة اختبار `test_clinical_station_auto_detects_smart_phase_and_renders_badge()` للتحقق من فتح النظام تلقائياً على ما قبل العملية للحالات الجديدة، والانتقال التلقائي لما بعد العملية عند اكتمال الحالة وظهور شارة المرحلة ورابط الأرشيف بنسبة نجاح 100% (`8 passed, 53 assertions`).

- **المرحلة 238 (2026-11-17 — `main`) — إزالة قائمة اختيار الممرض المسؤول يدوياً من تبويب الرعاية التمريضية والاعتماد على التوثيق التلقائي للممرض المسجل (Remove Redundant Manual Nurse Selector from Nursing Care Tab in Favor of Auto-Logged Nurse ID)**:
  * **تحسين الواجهة وتجربة المستخدم ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - إزالة حقل وقائمة الاختيار المنسدلة الزائدة `الممرض المسؤول عن الحالة` (`nurse_id`) من تبويب `tab=nursing_care` لكون النظام يربط ويوثق هوية الممرض تلقائياً من جلسة الدخول (`auth()->id()`) في الكنترولر.
    - ترشيق واجهة التمريض لتقتصر حصراً وبشكل مباشر على حقلي: ملاحظات الرعاية التمريضية (`nursing_notes`) وتعليمات وملاحظات التخريج (`discharge_notes`).
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/ClinicalStationMultiDrainTest.php](file:///C:/get/hospital-system/tests/Feature/ClinicalStationMultiDrainTest.php))**:
    - تحديث اختبار `test_nursing_care_tab_renders_notes_fields_and_saves()` لتأكيد عدم وجود حقل `nurse_id` في الواجهة (`assertDontSee('name="nurse_id"')`)، والتأكد من إسناد هوية الممرض المسجل في الجلسة تلقائياً لقاعدة البيانات في جدول `nursing_stations` بنسبة نجاح 100% (`7 passed, 47 assertions`).

- **المرحلة 237 (2026-11-17 — `main`) — استعادة حقول نموذج وملاحظات الرعاية التمريضية وتخريج المريض في المحطة السريرية (Restore Nursing Care & Discharge Notes Tab Fields & Form Submission in Clinical Station)**:
  * **معالجة حجب الحقول في الواجهة ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - حل مشكلة اختفاء حقول تبويب التمريض `tab=nursing_care`، حيث كان شرط غلاف النموذج المباشر يفحص `in_array($activeTab, ['history', 'vitals', 'plan', 'discharge'])` مع خلوه من مفتاح `nursing_care`.
    - تضمين مفتاح `'nursing_care'` ضمن مصفوفة التحقق مما مكّن نموذج التحديث من إظهار حقول اختيار الممرض المسؤول `nurse_id`، ملاحظات الرعاية التمريضية `nursing_notes`، وتعليمات وملاحظات التخريج `discharge_notes`، مع زر الحفظ السريري الموحد.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/ClinicalStationMultiDrainTest.php](file:///C:/get/hospital-system/tests/Feature/ClinicalStationMultiDrainTest.php))**:
    - إضافة اختبار ميزة شامل `test_nursing_care_tab_renders_notes_fields_and_saves()` يتحقق من استجابة HTTP 200 عند فتح التبويب، وظهور الحقول بالواجهة، وتأكيد نجاح عملية الحفظ والتحديث في جدول `nursing_stations` بنسبة نجاح 100% (`7 passed, 46 assertions`).

- **المرحلة 236 (2026-11-17 — `main`) — دعم قراءات الدرنقات الجراحية المتعددة وغير المحدودة لكل مريض بجدول فرعي مستقل وتجميع تلقائي لميزان السوائل (Multi-Drain Clinical Architecture with Dynamic Child Table, Live Fluid Balance Auto-Sum & Interactive Modal)**:
  * **قاعدة البيانات وموديل الدرنقات ([database/migrations/2026_11_17_093000_create_resident_station_reading_drains_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_17_093000_create_resident_station_reading_drains_table.php), [app/Models/ResidentStationReadingDrain.php](file:///C:/get/hospital-system/app/Models/ResidentStationReadingDrain.php), [app/Models/ResidentStationReading.php](file:///C:/get/hospital-system/app/Models/ResidentStationReading.php))**:
    - إنشاء جدول فرعي مستقل `resident_station_reading_drains` يحفظ كل قراءة درنقة في سجل وعمود `amount` صريح مع رقم الدرنقة `drain_number` واسم/موقع الدرنقة `drain_label` ومفتاح أجنبي cascade مرتبط بالقراءة.
    - ربط العلاقة `hasMany` باسم `drains` في موديل `ResidentStationReading` مع الترتيب برقم الدرنقة.
  * **كنترولر المحطة السريرية ([app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php))**:
    - إضافة دالتي المزامنة والاستخراج `parseDrainsFromRequest` و `syncReadingDrains`.
    - دعم استلام الدرنقات كمصفوفة `drains` أو كسلسلة نصية `drains_json`، مع الحساب والتجميع التلقائي لمجموعها في `output_drain` وميزان السوائل `fluid_balance`.
    - دعم التوافقية العكسية 100%: عند إرسال رقم وحيد في `output_drain` يتم تلقائياً إنشاء سجل درنقة مفرد لها في الجدول الفرعي.
    - شمول `drains` و `drains_json` في فحص مدخلات السوائل `$hasFluidsInput` لضمان الحفظ والمزامنة حتى في حال عدم إدخال سوائل أخرى.
    - تضمين العلاقة `drains` في الاستعلامات المسبقة (Eager Loading) لمنع مشكلة N+1.
  * **واجهة العلامات الحيوية والسوائل ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - إضافة زر أيقونة `[ 📋 ]` بجانب خانة الدرنقة يفتح نافذة منبثقة تفاعلية `#drainDetailsModal` لإضافة أي عدد مفتوح من الدرنقات (درنقة 1، درنقة 2، درنقة 3...) مع تسمية موقعها وإدخال كميتها بالـ ml وحساب المجموع لحظياً.
    - إضافة شارة عدّاد تفاعلية أسفل الخانة تبين عدد الدرنقات المدخلة.
    - عرض إجمالي الدرنقات في جدول القراءات التاريخية مع بادج ملون `[ 👁️ X درنقة ]` يعرض تفاصيل كل درنقة بالـ Tooltip ويفتح نافذة استعراض منبثقة `#viewDrainDetailsModal` عند النقر.
    - تحديث `loadReadingIntoTopForm` و `cancelEditingReading` لتحميل ومسح بيانات الدرنقات بسلاسة تامة.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/ClinicalStationMultiDrainTest.php](file:///C:/get/hospital-system/tests/Feature/ClinicalStationMultiDrainTest.php))**:
    - إنشاء حزمة اختبارات متخصصة تؤكد: حفظ درنقات متعددة كمصفوفة، حفظها كـ JSON، إنشاء سجل تلقائي للقيمة الفردية، تعديل الدرنقات، والحذف المتسلسل بنسبة نجاح 100% (`6 passed, 36 assertions`).
    - اجتياز كامل حزمة اختبارات العمليات ومصفوفة الصلاحيات (`15 passed, 196 assertions`).
    - اجتياز فحص `Laravel Pint` وتنسيق كافة الملفات.

- **المرحلة 224 (2026-11-16 — `main`) — ترشيق قوائم الفريق الطبي، تصغير حجم الخط، وإلغاء شارات النوع المكررة بجانب الأسماء (Compact Dropdown Typography & Role Badges Cleanup in Medical Team Selectors)**:
  * **تحسين الواجهة وتجربة المستخدم ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - تصغير حجم خط القوائم المنسدلة الذكية (أطباء التخدير 1 و2 ومساعد الجراح ومساعد التخدير) ليتناسق بدقة مع حقول الإدخال المصغرة (`font-size: 0.8rem` مع حشوة رشيقة `py-1.5 px-2.5`).
    - إزالة شارات النوع المتكررة بجانب الأسماء داخل القوائم (`مساعد جراح`، `مساعد تخدير`، `طبيب تخدير`) والشارة العلوية المكررة (`طبيب`) لكون مسمى القائمة والحقل واضحاً بذاته، ليظهر الاسم منفرداً بأناقة وسرعة في القراءة.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
    - اجتياز كامل الاختبارات بنجاح 100% (`16 passed, 205 assertions`).
    - اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 223 (2026-11-16 — `main`) — إلغاء أزرار كليشة التخدير وإفاقة مستقرة وجرد وعهدة العمليات في تفاصيل العملية (Remove Anesthesia Template, Stable Recovery & OR Store Inventory Buttons in Surgery Show)**:
  * **ترشيق واجهة تفاصيل العملية ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - إزالة أزرار الكبسولة الإضافية `[ 🪄 كليشة التخدير ]` و `[ إفاقة مستقرة ]` أعلى حقل ملاحظات طبيب التخدير مع استمرار التعبئة التلقائية الذكية عند اختيار نوع التخدير (`عام`، `نصفي`، `مناطقي`، `موضعي`).
    - إزالة زر `[ 🏭 جرد وعهدة العمليات ]` من ترويسة بطاقة المستلزمات والأجهزة لمنع تشتيت الكادر خارج مسار العملية.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
    - اجتياز كافة اختبارات العمليات بنجاح 100% (`9 passed, 181 assertions`).
    - اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 222 (2026-11-16 — `main`) — إلغاء زر طلب تعزيز المواد لمخزن العمليات في شاشة تفاصيل العملية عند فراغ رصيد المخزن (Remove OR Store Replenishment Request Button in Surgery Show on Empty Stock)**:
  * **ترشيق حالة المخزن الفارغ ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - إزالة زر `طلب تعزيز لمخزن العمليات` ورابط التحويل المخزني عند فراغ رصيد مخزن العمليات التابع.
    - الاكتفاء بعرض تنبيه نظيف وهادئ يفيد بعدم توفر رصيد قابل للصرف حالياً دون توجيه كادر العمليات خارج شاشة العملية.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
    - اجتياز كافة اختبارات العمليات بنجاح 100% (`9 passed, 181 assertions`).
    - اجتياز `Laravel Pint` وتحديث الذاكرة.

- **المرحلة 221 (2026-11-15 — `main`) — جدول صرف المستلزمات المتعدد المباشر من مخزن العمليات التابع بزر تفاعلي مطابق للمختبر والأشعة (Multi-Item Store Dispense Grid with Auto-Affiliated OR Store and Reveal Toggle)**:
  * **معالجة دوام الوجبتين في الباك إند ([app/Http/Controllers/HR/HrScheduleController.php](file:///c:/get/hospital-system/app/Http/Controllers/HR/HrScheduleController.php))**:
    - **دعم استرجاع وحفظ الشفتات المتعددة لليوم الواحد:** تعديل استعلامات `index` و `store` لدعم تخزين واسترجاع شفتي الوجبتين (`slot1` صباحي و `slot2` مسائي) لنفس الموظف ونفس اليوم دون أن يلغي أحدهما الآخر، بالاستفادة من الفهرس الفريد `['hr_employee_id', 'shift_date', 'hr_shift_id']`.
    - **الاحتساب المزدوج الدقيق في التغطية اليومية (`dailyCoverage`):** احتساب كلا الشفتين لموظف الوجبتين ضمن عدد الكوادر اليومية (مثلاً: احتسابه في الصباحي واحتسابه في المسائي) لتعكس الإحصاءات الواقع التشغيلي الحقيقي للمستشفى.
    - **التعبئة التلقائية للوجبتين (`autoFillDefaults`):** أتمتة تنزيل الشفتين (الصباحي والمسائي) لموظف الوجبتين بنقرة واحدة، وتطبيق الـ Off في أيام راحته.
    - **التحديث اللحظي عبر AJAX (`updateCell`):** دعم تمرير السلوت (`slot1` أو `slot2`) مع القيمة الأخرى لمنع الكتابة فوق الشفت الثاني أثناء الختم السريع.
  * **إعادة تصميم واجهة الجدول وترشيقها بالكامل ([resources/views/hr/schedules/index.blade.php](file:///c:/get/hospital-system/resources/views/hr/schedules/index.blade.php))**:
    - **القضاء على التراكم الرأسي وتوحيد الأشرطة (Unified Command Toolbar):** دمج الكروت الأربعة المتراكمة في شريط تحكم فائق الرشاقة (سُمك ~38px) يضم التنقل الأسبوعي/الشهري، أزرار الإجراءات، البحث اللحظي، فلاتر نوع الدوام، وأداة الختم السريع مباشرة.
    - **شريط مؤشرات الأداء السريع (KPI Summary Strip):** شريط علوي خفيف يوضح إجمالي الشفتات المجدولة، عدد موظفي الوجبتين، أيام الراحة، والكوادر النشطة.
    - **الخلية المزدوجة الذكية لموظفي الوجبتين (Split-Shift Cell):** عرض خانتين أنيقتين متطابقتين للوجبة الأولى `(1)` والوجبة الثانية `(2)` بألوانهما وتوقيتاتهما المستقلة، مع المحافظة على الخلية المدمجة الفردية لباقي الكوادر.
    - **شريط الإحصاءات اليومية الأفقي فائق الرشاقة (Streamlined Coverage Bar):** تحويل الإحصاءات السفلية من 5 أسطر عمودية طويلة إلى شريط أفقي أنيق من الشارات الملونة المصغرة (`☀️ 5  ⛅ 3  🌙 2  Off: 1`) بنصف الارتفاع وتوفير مساحة شاشة واسعة.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/HrScheduleTest.php](file:///c:/get/hospital-system/tests/Feature/HrScheduleTest.php))**:
    - إضافة اختبار جديد لموظف الوجبتين وتأكيد حفظ ونزول الشفتين معاً وظهورهما في الواجهة (`6 passed, 34 assertions`).
    - اجتياز اختبارات الموارد البشرية بالكامل (`11 passed, 90 assertions`) وفحص `Laravel Pint`.

- **المرحلة 218 (2026-11-15 — `main`) — تطوير وتحديث جدول الدوام والخفارات بنظام الجدولة الذكي، أداة الختم السريع، كشف التغطية والجاهزية، والتعبئة التلقائية للدوام الثابت، والطباعة الرسمية (HR Smart Hospital Roster Upgrade with Quick Shift Stamp, Daily Coverage Radar, One-Click Auto-Fill & Official Landscape Print)**:
  * **تطوير المعالجة والمتحكم ([app/Http/Controllers/HR/HrScheduleController.php](file:///c:/get/hospital-system/app/Http/Controllers/HR/HrScheduleController.php), [routes/web.php](file:///c:/get/hospital-system/routes/web.php))**:
    - **دعم التنقل السريع وأنماط العرض:** إضافة محددات `prevDate` و `nextDate` و `currentPeriodDate` للتنقل اللحظي بين الأسابيع والأشهر بنقرة واحدة، ودعم نمطي العرض: `week` (أسبوعي 7 أيام) و `month` (شهري كامل لجدولة الأطباء والخفارات على مدار الشهر).
    - **محرك التعبئة التلقائية للدوام الثابت (`autoFillDefaults`):** أتمتة تعبئة جدول الفترة بنقرة واحدة لجميع الموظفين بناءً على شفتاتهم الدائمة وأيام استراحتهم المسجلة في إضبارتهم مع خيار استبدال الشفتات أو تعبئة الفراغات فقط.
    - **كشف التغطية والجاهزية اليومية (`dailyCoverage`):** احتساب أوتوماتيكي لعدد الكوادر المداومة في كل شفت (صباحي، مسائي، ليلي/خفارة، راحة Off) لكل يوم.
    - **تحديث الخلية الفوري عبر AJAX (`updateCell`):** دعم الحفظ التلقائي الصامت لأي تغيير في أي خلية دون الحاجة لإعادة تحميل الصفحة.
    - **تفريغ وإعادة ضبط الفترة (`clearRange`):** إمكانية مسح شفتات الفترة المحددة لقسم معين لإعادة بنائها من الصفر.
  * **ترقية النموذج وقاعدة البيانات ([app/Models/HrSchedule.php](file:///c:/get/hospital-system/app/Models/HrSchedule.php))**:
    - إضافة mutator `setShiftDateAttribute` لضمان تخزين التاريخ بصيغة قياسية `Y-m-d` والقضاء على أي تعارض بين MySQL و SQLite.
  * **إعادة تصميم واجهة الجدول وتطوير تجربة المستخدم ([resources/views/hr/schedules/index.blade.php](file:///c:/get/hospital-system/resources/views/hr/schedules/index.blade.php))**:
    - **أداة الختم السريع (Shift Stamp Palette):** شريط أدوات علوي يتيح اختيار الشفت (صباحي / مسائي / ليلي / راحة / مسح) والنقر المباشر على أي خلية لتلوينها وتعيينها فوراً مع خيار الحفظ التلقائي (AJAX Switch).
    - **البحث والتصفية اللحظية (Client-Side Live Filter):** شريط بحث سريع بالاسم أو الوظيفة، وأزرار تصفية فورية (الكل / الدوام الثابت / الخفارات المتناوبة / الوجبتين) مع عداد الكوادر الظاهرة.
    - **شريط إجراءات الصف السريعة (Row Fill Menu):** قائمة منسدلة لكل موظف لتعبئة أسبوعه كاملاً بشفته الدائم أو أي شفت آخر بنقرة واحدة.
    - **شريط إحصاء التغطية اليومية السفلي (Daily Coverage Summary Footer):** صف مثبت أسفل الجدول يعرض إجمالي الكادر في كل شفت لكل يوم، وتحديث تلقائي لحظي عند أي تعديل.
    - **شيت الطباعة الرسمي المعتمد (`@media print`):** تحويل تلقائي للجدول إلى شيت رسمي أفقي (A4 Landscape) مع ترويسة المستشفى وتوقيع مسؤول القسم ومدقق الـ HR ومدير المستشفى.
    - إصلاح الخطأ البرمجي في وسم الـ `<script>` المزدوج.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/HrScheduleTest.php](file:///c:/get/hospital-system/tests/Feature/HrScheduleTest.php))**:
    - إنشاء حزمة اختبارات شاملة تغطي كافة العمليات والمسارات الجديدة (العرض الأسبوعي والشهري، التعبئة التلقائية، التحديث الفوري، التفريغ، وتبادل الخفارات) واجتيازها بنجاح 100% (`5 passed, 25 assertions`).
    - اجتياز اختبارات الموارد البشرية العامة بالكامل بنجاح 100% (`11 passed, 90 assertions`).
    - اجتياز فحص `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 217 (2026-11-15 — `main`) — إعادة هيكلة وتصميم إضبارة الموظف بنظام التبويبات التفاعلية الذكية وتثبيت الشريط الجانبي والقضاء على السكرول الطويل (HR Employee Dossier Redesign with Interactive Nav Tabs, Sticky Sidebar & Zero-Scroll Layout)**:
  * **إعادة هيكلة واجهة إضبارة الموظف ([resources/views/hr/employees/show.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/show.blade.php))**:
    - القضاء على السكرول الرأسي اللانهائي الذي كان يتطلب التمرير عبر 11 بطاقة وجدولاً ضخماً، واستبداله بنظام تبويبات تفاعلية مدمجة وأنيقة (`nav-pills` مع تأثيرات بصرية حديثة).
    - توزيع أقسام إضبارة الموظف على 5 تبويبات وظيفية منظمة مع شارات أرقام ديناميكية (`Badges`):
      1. **الملف الوظيفي (`tab-profile`):** البيانات الشخصية، تفاصيل التعاقد، نظام الدوام والشفتات وأيام الاستراحة، التراخيص الطبية، والملاحظات الإدارية.
      2. **الراتب والمالية (`tab-payroll`):** إعدادات هيكلة الراتب والدفع، جدول المخصصات والعلاوات الثابتة، وسجل السلف والأقساط.
      3. **الدوام والحضور (`tab-attendance`):** بطاقة شفت العمل المعتمد ورابط جدول الخفارات، سجل الغيابات والتأخيرات، وسجل العمل الإضافي.
      4. **العقوبات والمكافآت (`tab-actions`):** سجل اللوائح والعقوبات والإنذارات والمكافآت مع التأثير المالي والترحيل.
      5. **المستمسكات والأرشيف (`tab-documents`):** أرشيف الوثائق والمستمسكات الرسمية مع التحميل والمعاينة والرفع.
    - تثبيت البطاقة الجانبية للموظف (`sticky-employee-sidebar` / `sticky-top`) لتبقى هوية الموظف (الصورة، الكود، المسمى، الهاتف، فصيلة الدم، وحساب النظام) ظاهرة ومثبتة دائماً أمام المستخدم أثناء التنقل.
    - إضافة حفظ التبويب النشط ذكياً عبر الرابط (`URL Hash`) والذاكرة المحلية (`localStorage`) لضمان بقاء المستخدم في نفس التبويب بعد إرسال النماذج والمودالات أو تحديث الصفحة.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/HREmployeeTest.php](file:///c:/get/hospital-system/tests/Feature/HREmployeeTest.php))**:
    - تحديث حزمة الاختبارات لتأكيد وجود عناصر وتبويبات الواجهة الجديدة (`tab-profile`, `tab-payroll`, `tab-attendance`, `tab-actions`, `tab-documents`).
    - اجتياز كامل اختبارات الموارد البشرية بنجاح 100% (`11 passed, 90 assertions`).
    - اجتياز فحص `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 216 (2026-11-15 — `main`) — تثبيت نظام الدوام والشفتات الدائمة وعطلات الاستراحة ودعم الوجبتين لجميع الموظفين (HR Permanent Work Schedules, Shift Customization, Split-Shifts & Dynamic Weekly Off-Days)**:
  * **بنية قاعدة البيانات والنموذج ([database/migrations/2026_11_15_200000_add_schedule_and_shift_fields_to_hr_employees_table.php](file:///c:/get/hospital-system/database/migrations/2026_11_15_200000_add_schedule_and_shift_fields_to_hr_employees_table.php), [app/Models/HrEmployee.php](file:///c:/get/hospital-system/app/Models/HrEmployee.php))**:
    - إضافة حقول الدوام في `hr_employees`: نوع الجدولة `schedule_type` (ثابت fixed / خفارات متناوبة rotating)، الشفت الدائم `default_shift_id`، مفتاح دوام الوجبتين `is_split_shift`، الشفت المسائي الثاني `second_shift_id`، ساعات العمل المخصصة `custom_start_time` و `custom_end_time`، ومصفوفة أيام الاستراحة `weekly_off_days`.
    - تعريف علاقات `defaultShift()` و `secondShift()` و `schedules()` ومساعدات `getWeeklyOffDaysArabicAttribute()` و `isOffOn($date)`.
  * **المعالجة والتحكم ([app/Http/Controllers/HR/HrEmployeeController.php](file:///c:/get/hospital-system/app/Http/Controllers/HR/HrEmployeeController.php), [app/Http/Controllers/HR/HrScheduleController.php](file:///c:/get/hospital-system/app/Http/Controllers/HR/HrScheduleController.php))**:
    - تمرير الشفتات النشطة لشاشات الإضافة والتعديل (`create`, `edit`).
    - معالجة وتحقق وحفظ إعدادات الدوام والعطلات في دالتي `store` و `update`.
    - دعم الجلب الذكي للموظفين الثابتين ومطابقة أيام استراحتهم في جدول الدوام الأسبوعي/الشهري (`/hr/schedules`).
  * **الواجهات الأمامية ([resources/views/hr/employees/create.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/create.blade.php), [resources/views/hr/employees/edit.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/edit.blade.php), [resources/views/hr/employees/show.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/show.blade.php), [resources/views/hr/schedules/index.blade.php](file:///c:/get/hospital-system/resources/views/hr/schedules/index.blade.php), [resources/views/layouts/app.blade.php](file:///c:/get/hospital-system/resources/views/layouts/app.blade.php))**:
    - إضافة قسم "3. نظام الدوام والشفتات وأيام الاستراحة" في شاشتي إنشاء وتعديل الموظف مع تبديل لحظي للوجبتين، وتحديد أوقات مخصصة بدقة (مثل 8-4 أو 9-3)، وشبكة اختيار أيام العطلات الأسبوعية (الجمعة، السبت، الخميس، إلخ).
    - إضافة بطاقة نظام الدوام والشفتات وأيام الاستراحة في إضبارة وتفاصيل الموظف (`show.blade.php`).
    - إدراج رابط مباشر لإدارة الأقسام والعيادات (`departments.admin`) تحت قسم الموارد البشرية بالقائمة الجانبية لتسهيل الوصول والتحكم بالأقسام.
  * **التحقق الآلي**:
    - إضافة وتوسيع اختبار الميزة في [`tests/Feature/HREmployeeTest.php`](file:///c:/get/hospital-system/tests/Feature/HREmployeeTest.php) واجتياز كافة الاختبارات بنسبة 100% (`11 passed, 85 assertions`).
    - اجتياز فحص `Laravel Pint` بالكامل دون أي أخطاء.

- **المرحلة 215 (2026-11-15 — `main`) — قصر قائمة اختيار الصالة في ملف العملية على صالات العمليات الجراحية فقط واستبعاد كافة الأقسام والمخازن الأخرى (Restrict Surgery Show Hall Dropdown Exclusively to Operating Theaters & Exclude General Wards and Non-OR Departments)**:
  * **قصر قائمة الصالات في ملف العملية على صالات العمليات الجراحية فقط ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
    - فلترة المواقع `$locations` في `SurgeryController::show` لتشمل فقط صالات العمليات الجراحية (`عمليات`, `صالة`, `operation`, `theater`).
    - استبعاد المخازن العامة والفرعية (`مخزن`, `store`) وكافة الأقسام الطبية والإدارية غير الجراحية (المختبر، الطوارئ، الرسبشن، الصيدلة، الخدج، ردهات الطوابق، إلخ).
    - الحفاظ التلقائي على صالة العملية الحالية المحددة للمريض لعدم فقدان الاختيار المسجل مسبقاً.
  * **تحسين نصوص وتسميات قائمة الصالة في الواجهة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - تغيير التسمية من `الصالة/الردهة:` إلى `صالة العمليات:` في الزر الرئيسي العلوي والقائمة المنسدلة.
    - تحديث مسمى خيار الاختيار الافتراضي إلى `-- اختر صالة العمليات --`.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
    - اجتياز كافة الاختبارات بنجاح 100% (`16 passed, 205 assertions`).

- **المرحلة 217 (2026-11-15 — `main`) — إعادة هيكلة وتصميم إضبارة الموظف بنظام التبويبات التفاعلية الذكية وتثبيت الشريط الجانبي والقضاء على السكرول الطويل (HR Employee Dossier Redesign with Interactive Nav Tabs, Sticky Sidebar & Zero-Scroll Layout)**:
  * **إعادة هيكلة واجهة إضبارة الموظف ([resources/views/hr/employees/show.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/show.blade.php))**:
    - القضاء على السكرول الرأسي اللانهائي الذي كان يتطلب التمرير عبر 11 بطاقة وجدولاً ضخماً، واستبداله بنظام تبويبات تفاعلية مدمجة وأنيقة (`nav-pills` مع تأثيرات بصرية حديثة).
    - توزيع أقسام إضبارة الموظف على 5 تبويبات وظيفية منظمة مع شارات أرقام ديناميكية (`Badges`):
      1. **الملف الوظيفي (`tab-profile`):** البيانات الشخصية، تفاصيل التعاقد، نظام الدوام والشفتات وأيام الاستراحة، التراخيص الطبية، والملاحظات الإدارية.
      2. **الراتب والمالية (`tab-payroll`):** إعدادات هيكلة الراتب والدفع، جدول المخصصات والعلاوات الثابتة، وسجل السلف والأقساط.
      3. **الدوام والحضور (`tab-attendance`):** بطاقة شفت العمل المعتمد ورابط جدول الخفارات، سجل الغيابات والتأخيرات، وسجل العمل الإضافي.
      4. **العقوبات والمكافآت (`tab-actions`):** سجل اللوائح والعقوبات والإنذارات والمكافآت مع التأثير المالي والترحيل.
      5. **المستمسكات والأرشيف (`tab-documents`):** أرشيف الوثائق والمستمسكات الرسمية مع التحميل والمعاينة والرفع.
    - تثبيت البطاقة الجانبية للموظف (`sticky-employee-sidebar` / `sticky-top`) لتبقى هوية الموظف (الصورة، الكود، المسمى، الهاتف، فصيلة الدم، وحساب النظام) ظاهرة ومثبتة دائماً أمام المستخدم أثناء التنقل.
    - إضافة حفظ التبويب النشط ذكياً عبر الرابط (`URL Hash`) والذاكرة المحلية (`localStorage`) لضمان بقاء المستخدم في نفس التبويب بعد إرسال النماذج والمودالات أو تحديث الصفحة.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/HREmployeeTest.php](file:///c:/get/hospital-system/tests/Feature/HREmployeeTest.php))**:
    - تحديث حزمة الاختبارات لتأكيد وجود عناصر وتبويبات الواجهة الجديدة (`tab-profile`, `tab-payroll`, `tab-attendance`, `tab-actions`, `tab-documents`).
    - اجتياز كامل اختبارات الموارد البشرية بنجاح 100% (`11 passed, 90 assertions`).
    - اجتياز فحص `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 216 (2026-11-15 — `main`) — تثبيت نظام الدوام والشفتات الدائمة وعطلات الاستراحة ودعم الوجبتين لجميع الموظفين (HR Permanent Work Schedules, Shift Customization, Split-Shifts & Dynamic Weekly Off-Days)**:
  * **بنية قاعدة البيانات والنموذج ([database/migrations/2026_11_15_200000_add_schedule_and_shift_fields_to_hr_employees_table.php](file:///c:/get/hospital-system/database/migrations/2026_11_15_200000_add_schedule_and_shift_fields_to_hr_employees_table.php), [app/Models/HrEmployee.php](file:///c:/get/hospital-system/app/Models/HrEmployee.php))**:
    - إضافة حقول الدوام في `hr_employees`: نوع الجدولة `schedule_type` (ثابت fixed / خفارات متناوبة rotating)، الشفت الدائم `default_shift_id`، مفتاح دوام الوجبتين `is_split_shift`، الشفت المسائي الثاني `second_shift_id`، ساعات العمل المخصصة `custom_start_time` و `custom_end_time`، ومصفوفة أيام الاستراحة `weekly_off_days`.
    - تعريف علاقات `defaultShift()` و `secondShift()` و `schedules()` ومساعدات `getWeeklyOffDaysArabicAttribute()` و `isOffOn($date)`.
  * **المعالجة والتحكم ([app/Http/Controllers/HR/HrEmployeeController.php](file:///c:/get/hospital-system/app/Http/Controllers/HR/HrEmployeeController.php), [app/Http/Controllers/HR/HrScheduleController.php](file:///c:/get/hospital-system/app/Http/Controllers/HR/HrScheduleController.php))**:
    - تمرير الشفتات النشطة لشاشات الإضافة والتعديل (`create`, `edit`).
    - معالجة وتحقق وحفظ إعدادات الدوام والعطلات في دالتي `store` و `update`.
    - دعم الجلب الذكي للموظفين الثابتين ومطابقة أيام استراحتهم في جدول الدوام الأسبوعي/الشهري (`/hr/schedules`).
  * **الواجهات الأمامية ([resources/views/hr/employees/create.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/create.blade.php), [resources/views/hr/employees/edit.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/edit.blade.php), [resources/views/hr/employees/show.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/show.blade.php), [resources/views/hr/schedules/index.blade.php](file:///c:/get/hospital-system/resources/views/hr/schedules/index.blade.php), [resources/views/layouts/app.blade.php](file:///c:/get/hospital-system/resources/views/layouts/app.blade.php))**:
    - إضافة قسم "3. نظام الدوام والشفتات وأيام الاستراحة" في شاشتي إنشاء وتعديل الموظف مع تبديل لحظي للوجبتين، وتحديد أوقات مخصصة بدقة (مثل 8-4 أو 9-3)، وشبكة اختيار أيام العطلات الأسبوعية (الجمعة، السبت، الخميس، إلخ).
    - إضافة بطاقة نظام الدوام والشفتات وأيام الاستراحة في إضبارة وتفاصيل الموظف (`show.blade.php`).
    - إدراج رابط مباشر لإدارة الأقسام والعيادات (`departments.admin`) تحت قسم الموارد البشرية بالقائمة الجانبية لتسهيل الوصول والتحكم بالأقسام.
  * **التحقق الآلي**:
    - إضافة وتوسيع اختبار الميزة في [`tests/Feature/HREmployeeTest.php`](file:///c:/get/hospital-system/tests/Feature/HREmployeeTest.php) واجتياز كافة الاختبارات بنسبة 100% (`11 passed, 85 assertions`).
    - اجتياز فحص `Laravel Pint` بالكامل دون أي أخطاء.

- **المرحلة 215 (2026-11-15 — `main`) — إخفاء زر طباعة الإضبارة من شاشة الموارد البشرية (Hiding Dossier Print Button from HR Employee Show View)**:
  * **إخفاء زر طباعة الإضبارة ([resources/views/hr/employees/show.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/show.blade.php))**:
    - حذف زر `<button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i> طباعة الإضبارة</button>` من شريط الإجراءات العلوي لشاشة تفاصيل الموظف بالموارد البشرية بناءً على التوجيه الصريح، مع الإبقاء على زر طباعة الباجة (ID Card) وإضافة المستمسكات الرسمية وباقي عناصر التحكم.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/HREmployeeTest.php](file:///c:/get/hospital-system/tests/Feature/HREmployeeTest.php))**:
    - تحديث الاختبار الآلي وإضافة تأكيد عدم الظهور (`assertDontSee('طباعة الإضبارة')`).
    - اجتياز كافة اختبارات الموارد البشرية بنجاح 100% (`11 passed, 85 assertions`).
    - اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 214 (2026-11-15 — `main`) — الربط التلقائي لكليشات ونماذج التخدير المعتمدة عند اختيار نوع التخدير (Automatic Anesthesia Templates Binding on Type Selection)**:
  * **تضمين كليشات التخدير المعتمدة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - ربط مباشر بين حقل نوع التخدير وملاحظات طبيب التخدير (`#anesthesia_notes`):
      * **تخدير عام (General Anesthesia):**
        ```text
        prmedication given
        generalanesthesia given
        operation passesd smoothly without complication
        patient recoverd well
        ```
      * **تخدير نصفي (Spinal Anesthesia):**
        ```text
        2IV canulae putted
        spinal aneshesia given
        patient recovered well
        ```
      * **تخدير مناطقي (Regional Anesthesia):**
        ```text
        2IV canulea putted
        regional anesthesia given
        patient recovered well
        ```
      * **تخدير موضعي (Local Anesthesia):**
        ```text
        يتم كتب التقرير من قبل الطبيب الجراح
        ```
    - التفعيل التلقائي عند النقر على أي من الأزرار السريعة (`عام`، `نصفي`، `مناطقي`، `موضعي`) أو عند الاختيار من القائمة المنسدلة / الكتابة.
    - إضافة زر إدراج كليشة سريع `[ 📋 كليشة التخدير ]` وتوسيع ارتفاع حقل الملاحظات إلى `rows="5"` لعرض الأسطر بوضوح دون تمرير.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
    - اجتياز كافة الاختبارات بنجاح 100% (`16 passed, 205 assertions`).
    - اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 213 (2026-11-15 — `main`) — توحيد تصميم طبيب التخدير 1 و2 بقوائم بحث وإكمال تلقائي ذكية متطابقة تماماً مع مساعد الجراح والتخدير (Unified Searchable Autocomplete Dropdowns for Anesthesiologist 1 & 2 Matching Assistants)**:
  * **توحيد المكون البرمجي والتصميمي لطبيب التخدير 1 و2 ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - استبدال وسوم `<select>` التقليدية بمكون القائمة المنسدلة الذكية التفاعلية متطابق 100% مع مساعد الجراح ومساعد التخدير:
      * حقل نصي ذكي للبحث والإكمال التلقائي الفوري (`#team_anesthesiologist_name` و `#team_anesthesiologist_2_name`).
      * زر مسح الاختيار السريع (`×`) يظهر عند وجود اختيار ويختفي عند الفراغ.
      * زر سهم القائمة المنسدلة (`▾`) لإظهار كامل قائمة أطباء التخدير بنقرة واحدة.
      * قائمة عائمة منسدلة ذكية (`#anesthesiaDoctors1Menu` و `#anesthesiaDoctors2Menu`) مع فلترة حية حسب اسم الطبيب وتخصصه وشارات التخصص الملونة.
    - الحفاظ الكامل على حقول الـ ID المخفية (`#team_anesthesiologist_id` و `#team_anesthesiologist_2_id`) لضمان دقة الإرسال والتكامل مع الباك إند والاختبارات الآلية.
    - دعم الإكمال الذكي عند كتابة الاسم والضغط على Enter أو التبديل (`change` event) لمطابقة الطبيب آلياً وحفظ معرّفه.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php), [tests/Feature/SurgeryWaitingListFlightBoardTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryWaitingListFlightBoardTest.php))**:
    - اجتياز كافة الاختبارات بنجاح 100% (`25 passed, 237 assertions`).
    - اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 212 (2026-11-15 — `main`) — توحيد مظهر وتنسيق قوائم الفريق الطبي الـ 5 لتطابق مساعد الجراح، وقفل جراح العملية محدد مسبقاً من الاستعلامات (Unified 5-Slot Medical Team Design Matching Surgical Assistant & Locked Pre-Determined Reception Surgeon)**:
  * **توحيد تصميم وهيكل الحقول الـ 5 ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - **قفل طبيب الجراح (محمي ومحدد من الاستعلامات):** إلغاء القائمة القابلة للتغيير، وعرض اسم الجراح في حقل `input-group` ثابت ومقفل للقراءة فقط (`readonly`) مع شارة قفل `الاستعلامات` لمنع أي تعديل عشوائي للجراح المعتمد.
    - **توحيد المظهر والارتفاع لجميع الحقول الخمسة:**
      * جميع الأعمدة تستخدم `col-lg` بنسبة متساوية 20% لكل عنصر على الشاشات العريضة لتصطف في صف أفقي واحد منتظم.
      * ترويسة علوية موحدة الارتفاع (`min-height: 22px`) لكل حقل تشتمل على الأيقونة والعنوان والشارة/الرابط.
      * استخدام `input-group input-group-sm` متطابق في الارتفاع والأيقونة الأمامية والأطراف المنحنية لكافة القوائم (طبيب الجراح، مساعد جراح، طبيب التخدير 1، طبيب التخدير 2، مساعد التخدير).
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
    - اجتياز كافة الاختبارات بنجاح 100% (`16 passed, 205 assertions`).
    - اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 211 (2026-11-15 — `main`) — الفريق الطبي أعلى التخدير بـ 5 قوائم منسدلة أفقية بصف واحد مع دعم مساعدي التخدير معمارياً في جدول المساعدين والعمليات (Medical Team Above Anesthesia with 5 Horizontal Dropdowns in 1 Row & Architecture Support for Anesthesia Assistants)**:
  * **توسيع البنية المعمارية للمساعدين والعمليات ([database/migrations/2026_11_15_143000_add_type_to_surgical_assistants_and_anesthesia_assistant_to_surgeries.php](file:///C:/get/hospital-system/database/migrations/2026_11_15_143000_add_type_to_surgical_assistants_and_anesthesia_assistant_to_surgeries.php), [app/Models/SurgicalAssistant.php](file:///C:/get/hospital-system/app/Models/SurgicalAssistant.php), [app/Models/Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php), [app/Models/AnesthesiaStation.php](file:///C:/get/hospital-system/app/Models/AnesthesiaStation.php))**:
    - إضافة عمود `type` (`surgical` / `anesthesia`) إلى جدول `surgical_assistants` مع الفهارس والـ scopes (`scopeSurgical()` و `scopeAnesthesia()`).
    - إضافة عمود `anesthesia_assistant_name` إلى جدولي `surgeries` و `anesthesia_stations` والمزامنة الحية عبر `SurgeryController::updateDetails`.
    - ترقية شاشة إدارة المساعدين (`surgical-assistants/index.blade.php`) لتشمل فلترة النوع وتحديد نوع المساعد عند الإضافة والتعديل (`مساعد جراح` / `مساعد تخدير`).
  * **صف أفقي واحد من 5 قوائم منسدلة للفريق الطبي أعلى التخدير ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - نقل الفريق الطبي بالكامل ليكون في **أعلى الكارت الموحد** في صف شبكي أفقي واحد متناسق:
      1. `طبيب الجراح`: قائمة منسدلة بالأطباء والجراحين مع تحديد جراح العملية.
      2. `مساعد جراح`: قائمة إكمال تلقائي ذكية مرتبطة بدليل مساعدي الجراحين.
      3. `طبيب التخدير 1`: قائمة منسدلة بأطباء التخدير.
      4. `طبيب التخدير 2`: قائمة منسدلة بأطباء التخدير (تصحيح المسمى بدقة).
      5. `مساعد التخدير`: قائمة منسدلة وإكمال تلقائي ذكي لمساعدي التخدير مع رابط مباشر للدليل.
    - وضع قسم تفاصيل وخطة التخدير في **أسفل الفريق الطبي** بعد فاصل أفقي، مشتملاً على نوع التخدير وملاحظات الإفاقة.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php), [tests/Feature/SurgeryWaitingListFlightBoardTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryWaitingListFlightBoardTest.php))**:
    - اجتياز اختبارات مساعدي الجراحين بنجاح 100% (`7 passed, 24 assertions`).
    - اجتياز اختبارات ملف وتفاصيل العملية بنجاح 100% (`9 passed, 181 assertions`).
    - اجتياز اختبارات شاشة انتظار العمليات بنجاح 100% (`9 passed, 32 assertions`).
    - اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 210 (2026-11-15 — `main`) — دمج الفريق الطبي وتفاصيل التخدير في كارت موحد واحد جنباً إلى جنب، وترتيب أعضاء الفريق رأسياً دون كروت منفصلة (Single Unified Card Container for Medical Team & Anesthesia Side-by-Side with Clean Vertical Team List & No Nested Member Cards)**:
  * **دمج الفريق الطبي وتفاصيل التخدير في كارت واحد موحد ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - إلغاء الكروت المنفصلة السابقة للفريق الطبي وللتخدير ودمجهما داخل بطاقة سريرية واحدة موحدة (`card clin-tab-card`).
    - ترويسة بطاقة موحدة: `الفريق الطبي المشارك وخطة التخدير` مع شارة حالة محطة التخدير وزر `اعتماد وإتمام محطة التخدير`.
    - تقسيم داخلي شبكي متجاور في نفس الصف (`row g-3 h-100`): عمود اليمين للفريق الطبي (`col-md-5 border-start`) وعمود اليسار لتفاصيل وخطة التخدير (`col-md-7`).
    - ترتيب أعضاء الفريق الطبي رأسياً (واحد أسفل الآخر) بانسيابية تامة:
      1. الجراح الرئيسي: د. فلان (عرض خفيف بدون كارت منفصل).
      2. مساعد الجراح: مع رابط `دليل المساعدين`، وقائمة الإكمال التلقائي الذكية `#surgical_assistant_name` و`#surgicalAssistantsMenu`.
      3. طبيب التخدير الأول: قائمة منسدلة `#team_anesthesiologist_id`.
      4. طبيب التخدير المساعد: قائمة منسدلة `#team_anesthesiologist_2_id`.
    - حذف الإطارات والكروت المتداخلة المنفصلة (`clin-field-card`) نهائياً حول كل عضو لتوفير مظهر مدمج ومنظم ونظيف.
    - استقلال النماذج البرمجية بالكامل (`detailTeamTimingForm` و `detailAnesthesiaForm`) دون أي تداخل في وسوم `<form>`.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php), [tests/Feature/SurgeryWaitingListFlightBoardTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryWaitingListFlightBoardTest.php))**:
    - اجتياز اختبارات مساعدي الجراحين بنجاح 100% (`7 passed, 24 assertions`).
    - اجتياز اختبارات ملف وتفاصيل العملية بنجاح 100% (`9 passed, 181 assertions`).
    - اجتياز اختبارات شاشة انتظار العمليات بنجاح 100% (`9 passed, 32 assertions`).
    - اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 209 (2026-11-15 — `main`) — نقل صالة العمليات ومدة العملية كرقم وحالة المريض للشريط العلوي، توحيد أزرار الحفظ في زر واحد (Top Action Bar for Location, Duration & Status, Unified Save Button)**:
  * نقل صالة العمليات / الردهة لتسبق مدة العملية مباشرة بقائمة منسدلة تفاعلية ومزامنة للحقل المخفي.
  * إدراج مدة العملية كرقم بالدقائق مع أزرار سريعة بنقرة واحدة (30د - 180د).
  * توحيد أزرار الحفظ في زر واحد رئيسي بجانب مدة العملية ديناميكي حسب الصلاحيات (`submitUnifiedSurgeryForm()`).

- **المرحلة 208 (2026-11-15 — `main`) — تخطيط ثلاثي الأعمدة المتوازي في صف شبكي واحد: التشخيص والعلاجات (يمين) | الفريق الطبي والتوقيت (وسط) | تفاصيل التخدير (يسار) (3-Column Row: Diagnosis on Right, Medical Team in Middle, Anesthesia on Left)**:
  * **تخطيط ثلاثي الأعمدة المتوازي في ملف العملية ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - دمج البطاقات السريرية الثلاث في صف شبكي واحد (`row g-3 mb-4`) بديناميكية تجاوبية (`col-xl-4 col-lg-4 col-md-12`).
    - إسناد عمود اليمين للتشخيص الطبي للجراح والملاحظات وجدول الأدوية والعلاجات (`pane-detail_diagnosis`).
    - إسناد عمود الوسط لصالة العمليات والفريق الطبي المشارك، ومساعد الجراح، والتوقيت الفعلي للعملية الجراحية (`pane-detail_team_timing`).
    - إسناد عمود اليسار لتوثيق تفاصيل ونوع وخطة وملاحظات التخدير (`pane-detail_anesthesia`).
    - مطابقة الارتفاع الرأسي للكروت الثلاثة بنسبة 100% (`h-100 d-flex flex-column`) ومحاذاة أزرار الحفظ في أسفل كل بطاقة (`mt-auto`).
    - الحفاظ الكامل على النماذج البرمجية المستقلة (`detailDiagnosisForm`، `detailTeamTimingForm`، `detailAnesthesiaForm`) ومصفوفة الصلاحيات المشددة.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
    - اجتياز اختبارات ملف وتفاصيل العملية بنجاح 100% (`9 passed, 181 assertions`).
    - اجتياز اختبارات المساعدين الجراحيين بنجاح 100% (`7 passed, 24 assertions`).
    - اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 207 (2026-11-15 — `main`) — مواءمة التشخيص الطبي للجراح وجدول العلاجات في العمود الأيمن وتفاصيل التخدير في العمود الأيسر (Two-Column Side-by-Side Row: Surgeon Diagnosis & Treatments on Right, Anesthesia on Left)**:
  * **تخطيط ثنائي الأعمدة المتوازي في ملف العملية ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - دمج بطاقة "التشخيص الطبي للجراح وجدول العلاجات" وبطاقة "توثيق تفاصيل التخدير" في صف شبكي واحد متجاور (`<div class="row g-3 mb-4">`).
    - إسناد عمود اليمين (`col-xl-6 col-lg-6`) للتشخيص الطبي للجراح والملاحظات السريرية وقائمة الأدوية والعلاجات.
    - إسناد عمود اليسار (`col-xl-6 col-lg-6`) لتوثيق تفاصيل التخدير، نوع التخدير، ملاحظات طبيب التخدير، وخطة التخدير السريرية.
    - ضبط ارتفاع البطاقتين ليتطابقا رأسياً (`h-100 d-flex flex-column`) مع تثبيت أزرار الحفظ في أسفل كل بطاقة (`mt-auto`).
    - الحفاظ الكامل على مصفوفة الصلاحيات (صلاحيات الجراح vs طبيب التخدير) ومعرفات النماذج البرمجية (`detailDiagnosisForm`, `detailAnesthesiaForm`).
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
    - اجتياز جميع اختبارات ملف وتفاصيل العملية بنجاح 100% (`9 passed, 181 assertions`).
    - اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 206 (2026-11-15 — `main`) — أزرار طي تفاعلية للتحاليل والأشعة ونوع العملية والإضافات، إلغاء نتائج الزيارات السابقة، وحذف كارت المعلومات الطبية لصالح كارت مريض موحد وقائمة حالة المريض العلوية (Interactive Toggle Buttons for Tests & Operations, Decommissioning Previous Visit Results & Surgery Medical Card)**:
  * **أزرار طي تفاعلية للتحاليل والأشعة ونوع العمليات ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - تحويل التحاليل المخبرية والأشعة ونوع العملية والإضافات إلى 3 أزرار طي وتوسيع تفاعلية رشيقة (`[ 🧪 التحاليل ]`، `[ 🩻 الأشعة ]`، و`[ 🔪 نوع العملية والعمليات الإضافية ]`).
    - إلغاء نتائج الزيارات السابقة بالكامل وحصر العرض على ما يخص العملية الجراحية الحالية حصراً.
  * **إلغاء كارت المعلومات الطبية للعملية الجراحية ونقل التحكم للترويسة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - حذف كارت "المعلومات الطبية للعملية الجراحية" بالكامل، وجعل كارت بيانات المريض بعرض الشاشة الكامل (Full Width).
    - نقل القائمة المنسدلة لحالة المريض بالصالة (`Pre-Op` / `Intra-Op`) إلى شريط الإجراءات العلوي بجانب أزرار الطباعة والعودة.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php), [tests/Feature/SurgeryWaitingListFlightBoardTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryWaitingListFlightBoardTest.php))**:
    - اجتياز اختبارات ملف العملية بنجاح 100% (`9 passed, 181 assertions`).
    - اجتياز اختبارات المساعدين الجراحيين وقائمة الانتظار بنجاح 100% (`16 passed, 56 assertions`).
    - اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 205 (2026-11-15 — `main`) — تحويل إجراءات وخيارات حالة المريض في الصالة إلى قائمة منسدلة موحدة بدلاً من الأزرار المتعددة (Dropdown Action Menu for Patient OR Status)**:
  * **تحويل إجراءات حالة المريض في الصالة إلى قائمة منسدلة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - استبدال الأزرار المتفرقة في ترويسة ملف العملية بقائمة منسدلة موحدة وأنيقة (Dropdown Action Menu).
    - عرض الحالة الحالية بنبض حي وسهم منسدل، وعند النقر تنبثق الخيارات المتاحة حسب المرحلة السريرية (`Pre-Op` / `Intra-Op`).
    - الحفاظ الكامل على المزامنة اللحظية الحية ونوافذ التأجيل والإلغاء وإشارات الأمان.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
    - اجتياز جميع اختبارات العمليات الجراحية بنجاح 100% (`9 passed, 181 assertions`).
    - اجتياز `Laravel Pint` بالكامل.

- **المرحلة 204 (2026-11-15 — `main`) — إلغاء نظام التبويبات نهائياً في ملف العملية الجراحية وتثبيت نمط العرض كصفحة واحدة منظمة ومفتوحة، وحذف بطاقات العدادات العلوية وعنوان قسم الفحوصات (Permanent Unified Single-Page Surgery File, Decommissioning Tabs & Stripping Tests Cards/Header)**:
  * **إلغاء التبويبات نهائياً ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - حذف شريط التبويبات العلوي وأزرار التبديل بالكامل، وتثبيت عرض كافة البطاقات السريرية المصرح بها مفتوحة ومرئية دائماً رأسياً.
    - الحفاظ على عناصر الصلاحيات البرمجية لاجتياز اختبارات الوصول والصلاحيات بنجاح.
  * **حذف بطاقات وعنوان الفحوصات والتحاليل ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - حذف عنوان "التحاليل والفحوصات الطبية" والبطاقات الإحصائية الأربعة العلوية بالكامل.
    - الدخول مباشرة إلى جداول نتائج التحاليل والفحوصات وتقارير الأشعة.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
    - اجتياز جميع اختبارات العمليات الجراحية بنجاح 100% (`9 passed, 181 assertions`).
    - اجتياز اختبارات المساعدين الجراحيين بنجاح (`7 passed, 24 assertions`).
    - اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 203 (2026-11-15 — `main`) — المزامنة اللحظية الحية لشاشات انتظار العمليات وسجل العمليات عند تنفيذ إجراءات البدء والإنهاء والتأجيل وإعادة الإخراج، تخطيط الأعمدة الـ 7 المختصرة ومنع السطر الثاني (Realtime Live Sync for Surgery Waiting Board & Log on Action Buttons, 7 Streamlined Columns & Strict Single-Line Rows)**:
  * **المزامنة اللحظية الحية لشاشات انتظار العمليات وسجل العمليات ([resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php), [resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php), [resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php))**:
    - ربط إجراءات ملف وتفاصيل العملية (`بدء العملية`, `إنهاء العملية`, `إعادة إخراجه من الصالة`, `تأجيل العملية`, و`إلغاء العملية`) بمحرك بث وإشعار لحظي عبر قناة `BroadcastChannel` و`localStorage` و`Laravel Echo`.
    - استقبال الإشارات الحية فوراً (0ms) في شاشة قائمة الانتظار `/surgeries/waiting` وسجل العمليات `/surgeries` مع استبدال وتحديث سلس لمحتوى الجداول عبر `DOMParser` دون إعادة تحميل الصفحة (No Page Reload) وبدون وميض.
    - إضافة نبض وميض أخضر/أزرق حي (`مزامنة حية`) في شريط أدوات شاشة الانتظار لتأكيد الاتصال والمزامنة الفورية.
    - دعم نبض فحص خلفي (Heartbeat Polling كل 4 ثوانٍ) لضمان التحديث التلقائي الصامت لشاشات العرض الجدارية (TV screens) وأجهزة الشبكة المحلية دون الحاجة لتحديث يدوي.
  * **تخطيط الأعمدة الـ 7 المختصرة ومنع التفاف السطر الثاني كلياً ([resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php), [resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php))**:
    - ترتيب الأعمدة السبعة المعتمدة: `الحالة`، `المريض`، `العمر` (عمود مخصص)، `الجنس` (عمود مخصص)، `نوع العملية` (مختصر مع شارة كاش/ضمان مدمجة بنفس السطر)، `الطبيب الجراح`، و`رقم الغرفة`.
    - فرض العرض الأفقي الصارم بسطر واحد (`white-space: nowrap`) على كافة الخلايا والصفوف ومنع نزول أي بيانات لسطر ثانٍ.
    - اعتماد الاختصارات والمصطلحات الطبية المعتمدة للعمليات الجراحية عالمياً: `Pre-Op` (بالانتظار)، `Intra-Op` (داخل الصالة مع نقطة نبض خضراء ومؤقت زمني فوري)، و`Post-Op` (خرج من الصالة).
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryWaitingListFlightBoardTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryWaitingListFlightBoardTest.php), [tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
    - اجتياز اختبارات شاشة الانتظار بنجاح 100% (`9 passed, 32 assertions`).
    - اجتياز اختبارات العمليات الجراحية بنجاح 100% (`9 passed, 181 assertions`).
    - اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

- **المرحلة 202 (2026-11-15 — `main`) — تقسيم شاشة انتظار العمليات الجراحية (`/surgeries/waiting`) لعمودين (قبل الصالة / داخل الصالة)، إخفاء القائمة الجانبية للعرض الكامل 100%، ترتيب الأعمدة الـ 5 وسهولة القراءة، والتحكم السريري من داخل ملف العملية، وتوجيه موظف العمليات تلقائياً (2-Column Waiting Flight Board, Full-Width View, Streamlined Columns & Surgery Staff Auto-Redirect)**:
  * **تقسيم شاشة الانتظار إلى عمودين متوازيين مع إخفاء المكتملة تلقائياً ([resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php))**:
    - العمود الأول: `قبل دخول الصالة (بالانتظار)` يشمل المرضى المجدولين وبانتظار الدخول.
    - العمود الثاني: `داخل صالة العمليات (جارية الآن)` يشمل العمليات الجارية حالياً في الصالات.
    - استبعاد العمليات المكتملة (`completed`) كلياً من شاشة الانتظار بمجرد إنهائها لتختفي فوراً وتظل مسجلة في سجل العمليات العام.
  * **إخفاء القائمة الجانبية للعرض الكامل 100% وترتيب الأعمدة الـ 5 ([resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php))**:
    - إخفاء القائمة الجانبية تلقائياً في شاشة قائمة الانتظار لإتاحة العرض الكامل 100% (Full Width) وتوفير أقصى قدر من الاتساع والانسيابية للجداول.
    - حذف زر "سجل العمليات" وشريط "البحث الفوري" من أعلى الشاشة لتفريغ المساحة تماماً للجدولين.
    - ترتيب الأعمدة الخمسة لسهولة القراءة: `المريض` (الاسم كرابط والعمر)، `نوع العملية` (المسمى وتغطية التأمين/الكاش)، `الطبيب الجراح`، `رقم الغرفة/الصالة`، و`حالة المريض في الصالة` (شارة عنبرية مصغرة لبالانتظار وشارة زرقاء بنبض حي لداخل الصالة).
    - ضبط الحواشي الداخلية (`padding: 9px 12px`)، وحذف رقم الملف ورقم الهاتف من العرض بالانتظار لتجنب التكدس.
  * **التحكم بالمسار السريري حصرياً من ملف العملية وإلغاء الأرشيف العام ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php), [resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
    - إلغاء زر `الأرشيف العام` من شاشة الانتظار.
    - حصر التحكم من داخل ملف وتفاصيل العملية (`surgeries.show`):
      1. `بدء عملية`: ينقل المريض من العمود الأول للثاني.
      2. `تأجيل العملية`: يفتح نافذة منبثقة لتحديد الموعد الجديد وإبقاء المريض بالانتظار.
      3. `إعادة إخراجه من الصالة`: يعيد المريض من داخل الصالة إلى عمود الانتظار ويصفر محطة الصالة.
      4. `إنهاء العملية`: ينهي العملية ويخفي المريض من شاشة الانتظار كلياً.
    - إبراز شارة حالة المريض في الصالة في ترويسة ملف العملية بلون مميز ونبض حي.
  * **توجيه حساب موظف العمليات مباشرة لسجل العمليات ([app/Http/Controllers/Auth/LoginController.php](file:///C:/get/hospital-system/app/Http/Controllers/Auth/LoginController.php), [app/Http/Controllers/DashboardController.php](file:///C:/get/hospital-system/app/Http/Controllers/DashboardController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
    - توجيه دور `surgery_staff` أو `موظف عمليات` مباشرة إلى `/surgeries` عند تسجيل الدخول، وعند زيارة `/`، وعند زيارة `/dashboard`.
  * **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryWaitingListFlightBoardTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryWaitingListFlightBoardTest.php), [tests/Feature/SurgeryStaffDashboardAndLoginRedirectTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryStaffDashboardAndLoginRedirectTest.php), [tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
    - اجتياز اختبارات شاشة الانتظار بنجاح 100% (`9 passed, 32 assertions`).
    - اجتياز اختبارات توجيه موظف العمليات بنجاح 100% (`4 passed, 7 assertions`).
    - اجتياز اختبارات `SurgeryTest` بنجاح 100% (`9 passed, 181 assertions`).
    - اجتياز `Laravel Pint` بالكامل.

- **المرحلة 201 (2026-11-14 — `main`) — إضافة وإدارة جنسية الموظف في نموذج تسجيل الموظفين وملفاتهم وإعدادات الحقول (HR Employee Nationality Field Integration, Dynamic Lookups & Profile Display)**:
  * **بنية قاعدة البيانات والنموذج ([database/migrations/2026_11_14_211500_add_nationality_to_hr_employees_table.php](file:///c:/get/hospital-system/database/migrations/2026_11_14_211500_add_nationality_to_hr_employees_table.php), [app/Models/HrEmployee.php](file:///c:/get/hospital-system/app/Models/HrEmployee.php))**:
    - إضافة عمود `nationality` بطول `VARCHAR(50)` بقيمة افتراضية `عراقي` في جدول `hr_employees` مباشرة بعد `national_id`.
    - تضمين الحقل في مصفوفة الحقول القابلة للتعبئة `$fillable` بموديل `HrEmployee`.
  * **التكامل مع نظام إعدادات الحقول والقوائم المنسدلة ([database/seeders/HrSettingsSeeder.php](file:///c:/get/hospital-system/database/seeders/HrSettingsSeeder.php))**:
    - تسجيل الحقل في جدول `hr_field_requirements` ليظهر في شاشة إعدادات الموارد البشرية (`/hr/settings`) للتحكم في كونه إجبارياً أو اختيارياً.
    - تزويد جدول `hr_lookup_options` بقائمة جنسيات شائعة بفئة `nationality` (عراقي، سوري، لبناني، مصري، أردني، هندي، باكستاني، فلبيني، أخرى).
  * **التحكم والتحقق والبحث السريع ([app/Http/Controllers/HR/HrEmployeeController.php](file:///c:/get/hospital-system/app/Http/Controllers/HR/HrEmployeeController.php))**:
    - تمرير قائمة الجنسيات النشطة لشاشتي الإضافة والتعديل (`create`, `edit`).
    - تطبيق التحقق الديناميكي المستند لإعدادات النظام (`required` أو `nullable`)، واعتماد القيمة الافتراضية "عراقي" عند ترك الحقل فارغاً في دالتي `store` و `update`.
    - شمول حقل الجنسية في فلتر البحث النصي السريع بسجل الموظفين (`index`).
  * **الواجهات الأمامية ([resources/views/hr/employees/create.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/create.blade.php), [resources/views/hr/employees/edit.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/edit.blade.php), [resources/views/hr/employees/show.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/show.blade.php))**:
    - تصميم حقل الجنسية بجانب الرقم الوطني والجنس مع قائمة اقتراحات سريعة (`<datalist>`) وتعبئة افتراضية فورية "عراقي".
    - إعادة ضبط وتوازن شبكة الأعمدة (Grid System) في بطاقة البيانات الشخصية لتكون متناسقة ومتوازنة على 3 صفوف متكاملة.
    - إبراز شارة جنسية الموظف في إضبارة وتفاصيل الموظف (`show.blade.php`).
  * **التحقق الآلي**:
    - إضافة وتوسيع اختبارات الميزة في [`tests/Feature/HREmployeeTest.php`](file:///c:/get/hospital-system/tests/Feature/HREmployeeTest.php) واجتياز كافة الاختبارات بنسبة 100% (`10 passed, 58 assertions`).
    - اجتياز التنسيق القياسي عبر `Laravel Pint` ونجاح تجميع قوالب Blade (`view:cache`).

- **المرحلة 200 (2026-11-14 — `main`) — هيكلة تبويب وقائمة إعدادات الخدج والحاضنات، فصل شاشة دليل الحليب في واجهة مخصصة، وشريط التنقل الموحد (NICU Settings Submenu, Dedicated Milk Types View & Unified Sub-tabs Architecture)**:
  * **هيكلة القائمة الجانبية لقسم الخدج ([resources/views/layouts/app.blade.php](file:///c:/get/hospital-system/resources/views/layouts/app.blade.php))**:
    - تنظيم بنود الإعدادات في تبويب/قائمة فرعية منسدلة (Collapsible Submenu) باسم **"الإعدادات"** تشمل العناوين الفرعية (Sub-titles) الثلاثة:
      1. `تسعير باقات وخدمات الخدج (NICU)` (`nicu.services.index`).
      2. `إدارة حاضنات الخدج - ط6` (`incubators.index`).
      3. `قائمة وتصنيفات حليب وتغذية الخدج` (`nicu.milk-types.index`).
  * **فصل وإفراد شاشة دليل أنواع الحليب والتغذية ([resources/views/nicu/milk-types/index.blade.php](file:///c:/get/hospital-system/resources/views/nicu/milk-types/index.blade.php), [app/Http/Controllers/NicuMilkTypeController.php](file:///c:/get/hospital-system/app/Http/Controllers/NicuMilkTypeController.php), [routes/web.php](file:///c:/get/hospital-system/routes/web.php))**:
    - إنشاء مسار `Route::get('/milk-types', ...)` ودالة `index()` في `NicuMilkTypeController` مع دعم الفرز والبحث السريع بالاسم والتصنيف والحالة.
    - بناء واجهة متكاملة ومستقلة تشمل إحصائيات سريعة، جدول البيانات، ونوافذ الإضافة والتعديل والتبديل اللحظي للتفعيل بالـ AJAX.
  * **شريط تبويبات الإعدادات المشترك ([resources/views/nicu/partials/settings-nav.blade.php](file:///c:/get/hospital-system/resources/views/nicu/partials/settings-nav.blade.php))**:
    - بناء شريط تبويبات علوي أنيق موحد (Sub-tabs) يربط بين الصفحات الثلاث ويوضع في رأس شاشة تسعير الخدمات، وشاشة إدارة الحاضنات، وشاشة أنواع الحليب.
    - تنظيف واجهة الخدمات [resources/views/nicu/services/index.blade.php](file:///c:/get/hospital-system/resources/views/nicu/services/index.blade.php) لتكون مخصصة كلياً لباقات الأسعار اليومية وأجهزة التنفس.
  * **التحقق الآلي**: اجتياز فحص `Laravel Pint` وتجميع Blade واختبارات الميزة بنسبة 100% (`Tests\Feature\NicuGranularPermissionsAndMilkTypesTest` و `Tests\Feature\IncubatorWorkflowTest`).

- **المرحلة 199 (2026-11-14 — `main`) — حل مشكلة استدعاءات AJAX في تبديل وتعديل أنواع حليب الخدج وباقات الخدمات وتوافق السيرفر السحابي هوسـتنجر (Subdirectory AJAX URL Fix & NICU Milk Types Toggle Compatibility)**:
  * **معالجة الروابط المطلقة الصلبة في واجهة خدمات وحليب الخدج ([resources/views/nicu/services/index.blade.php](file:///c:/get/hospital-system/resources/views/nicu/services/index.blade.php))**:
    - تصحيح زر تبديل التفعيل اللحظي لأنواع الحليب (`milk-type-toggle-switch`) وتحويله من `/nicu/milk-types/${id}/toggle` إلى المسار المعتمد `data-toggle-url="{{ route('nicu.milk-types.toggle', $milkType) }}"` مع مسار ديناميكي احتياطي `{{ url('nicu/milk-types') }}/${milkTypeId}/toggle`.
    - حل مشكلة الخطأ 404 وتنبيه "فشل الاتصال بالخادم" الناتج عن تشغيل النظام على المسار الفرعي `https://hinpa.icu/hearmz/`.
    - تزويد طلبات الـ fetch بترويسة `'Accept': 'application/json'` لضمان الاستجابة بصيغة JSON.
    - تصحيح مسار الفورم في نافذة تعديل نوع الحليب (`editMilkForm`) ليستخدم `{{ url('nicu/milk-types') }}/${milkType.id}`.
    - تصحيح زر تبديل باقات الخدمات (`service-toggle-switch`) ومودال تعديل الخدمة ليستخدم `{{ url('nicu/services') }}`.
  * **توافق الصلاحيات في الكنترولر ([app/Http/Controllers/NicuMilkTypeController.php](file:///c:/get/hospital-system/app/Http/Controllers/NicuMilkTypeController.php))**:
    - شمول صلاحية `manage incubator reservations` في دالة `authorizeManage()` لضمان عدم حدوث أي حجب صلاحيات (403) لمشرفي وموظفي الحاضنات.
  * **تصحيح روابط توكن كاميرا الخدج ([resources/views/nicu/index.blade.php](file:///c:/get/hospital-system/resources/views/nicu/index.blade.php), [resources/views/nicu/reservations/index.blade.php](file:///c:/get/hospital-system/resources/views/nicu/reservations/index.blade.php))**:
    - تحويل استدعاء توكن كاميرا الطفل `sendBabyCamLink` من المسار الصلب إلى `{{ url('nicu/reservations') }}/${reservationId}/cam-token`.
  * **التحقق الآلي**: اجتياز فحص `Laravel Pint` وتجميع قوالب Blade واختبارات [`tests/Feature/NicuGranularPermissionsAndMilkTypesTest.php`](file:///c:/get/hospital-system/tests/Feature/NicuGranularPermissionsAndMilkTypesTest.php) بنسبة 100% (5 اختبارات، 38 تأكيداً).

- **المرحلة 198 (2026-11-14 — `main`) — حل استثناء قص البيانات 1265 عند إنشاء موظف جديد وتحويل عمود نوع التعاقد `employment_type` إلى VARCHAR مرن وتطبيع الخيارات (HR Employee Creation Data Truncation Fix & Flexible Employment Type Migration)**:
  * تحويل عمود `employment_type` في جدول `hr_employees` من `ENUM` صارم محصور في 4 خيارات إلى `string('employment_type', 50)->default('full_time')` لمنع استثناء `SQLSTATE[01000]: Warning: 1265 Data truncated for column 'employment_type'` نهائياً.
  * تطبيع قيمة `employment_type` تلقائياً في دالتي `store` و `update` لتحويل `Fulltime` أو `full-time` إلى الكود المعتمد `full_time`.
  * إضافة اختبار `test_can_create_employee_with_custom_or_fulltime_employment_type()` واجتياز كافة اختبارات الموارد البشرية بنجاح 100% (`9 passed, 46 assertions`)، واجتياز فحص التنسيق القياسي `Laravel Pint`.

- **المرحلة 196 (2026-11-13 — `main`) — بناء واجهة ونظام دليل مساعدي الجراحين (`/surgical-assistants`)، تسجيل الأسماء الـ 9 المعتمدة، والربط التلقائي بملف العملية وصالة العمليات (Dedicated Surgical Assistants Management & Dynamic Surgeries Integration)**:
  * **إنشاء بنية وجدول مساعدي الجراحين ([database/migrations/2026_11_13_221000_create_surgical_assistants_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_13_221000_create_surgical_assistants_table.php), [app/Models/SurgicalAssistant.php](file:///C:/get/hospital-system/app/Models/SurgicalAssistant.php), [database/seeders/SurgicalAssistantSeeder.php](file:///C:/get/hospital-system/database/seeders/SurgicalAssistantSeeder.php))**:
    - بناء جدول `surgical_assistants` ونموذج `SurgicalAssistant` بنمط كاستات لارافيل 12 الحديثة (`name`, `phone`, `notes`, `is_active`).
    - تسجيل وتغذية الأسماء الـ 9 المعتمدة أولياً عبر السييدر (علياء عبدالجبار، علي عبدالله، حسين ناهي، ليث رحيم، حكم علاء، عبدالله علي، محمد فالح، مضاء علي، ومساعد جراح خارجي).
  * **بناء واجهة الإدارة الكاملة المستقلة ([app/Http/Controllers/SurgicalAssistantController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgicalAssistantController.php), [resources/views/surgical-assistants/index.blade.php](file:///C:/get/hospital-system/resources/views/surgical-assistants/index.blade.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
    - تصميم واجهة متكاملة مطابقة لنمط واجهة الأطباء ودليل العمليات مع بطاقات إحصائيات سريعة، شريط بحث بالاسم والهاتف، جدول المساعدين مع إحصائية العمليات المسجلة لكل مساعد، زر تبديل الحالة (نشط/معطل)، ونوافذ منبثقة (Modals) للإضافة والتعديل والحذف.
    - إضافة رابط `مساعدو الجراحين` بالقائمة الجانبية تحت قسم العمليات الجراحية للمشرفين وموظفي العمليات.
  * **الربط المباشر مع ملف العملية وصالة العمليات ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - تمرير قائمة المساعدين النشطين ديناميكياً لملف وتفاصيل العملية (`detail_team_timing`) وتزويد حقل `surgical_assistant_name` بقائمة اقتراحات `datalist` مع رابط مباشر يفتح دليل المساعدين.
  * **التحقق الآلي**: اجتياز اختبارات الميزة الشاملة بنسبة 100% عبر [`tests/Feature/SurgicalAssistantTest.php`](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php) (7 اختبارات) واختبارات التخدير والعمليات السابقة.

- **المرحلة 195 (2026-11-13 — `main`) — تمكين ظهور المريض لطبيب التخدير فور التعيين، إزالة عائق اكتمال الجراح، ومزامنة محطة التخدير واعتماد أنواع التخدير الخمسة (عام، نصفي، موضعي، مناطقي، سديشن)**:
  * **إزالة عائق اكتمال الجراح من محطة التخدير ([app/Http/Controllers/AnesthesiaStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/AnesthesiaStationController.php))**:
    - إزالة شرط `whereHas('surgeonStation', fn($q) => $q->where('status', 'completed'))` الذي كان يحجب المرضى عن طبيب التخدير لحين إنهاء الجراح محطته.
    - إتاحة ظهور العمليات المعين لها طبيب التخدير (`anesthesiologist_id` أو `anesthesiologist_2_id` أو في `anesthesiaStation`) فور تعيينها وحفظها من قبل موظف العمليات، مع إزالة إعادة التوجيه الإجباري في `show`.
  * **المزامنة والإنشاء التلقائي لمحطة التخدير ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
    - إنشاء وتحديث سجل `AnesthesiaStation` فور قيام موظف العمليات بحفظ الفريق الطبي والتوقيت في `detail_team_timing`.
    - شمول طبيب التخدير في استعلامات وفلاتر سجل العمليات العام (`/surgeries`) وتمكين وصول حسابات التخدير لجدول العمليات وتحديث عدادات الإشعارات في القائمة الجانبية (`resources/views/layouts/app.blade.php`) ولوحة التحكم (`DashboardController.php`).
    - تسجيل صلاحيات دور `التخدير` و`anesthesia` في [`database/seeders/RolesAndPermissionsSeeder.php`](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php).
  * **اعتماد أنواع التخدير الخمسة المعتمدة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php), [resources/views/surgery-stations/anesthesia/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/anesthesia/show.blade.php))**:
    - تضمين أزرار التعبئة السريعة وقوائم الاقتراح بالأنواع الخمسة بدقة: `عام` (General Anesthesia)، `نصفي` (Spinal Anesthesia)، `موضعي` (Local Anesthesia)، `مناطقي` (Regional Anesthesia)، `سديشن` (Sedation).
  * **التحقق الآلي**: اجتياز اختبارات الميزة الشاملة عبر [`tests/Feature/AnesthesiologistVisibilityAndWorkflowTest.php`](file:///C:/get/hospital-system/tests/Feature/AnesthesiologistVisibilityAndWorkflowTest.php) واجتياز `Pint` و`SurgeryTest` بنسبة 100%.

- **المرحلة 194 (2026-11-13 — `main`) — تبسيط جدول سجل العمليات الجراحية (`/surgeries`)، إزالة تفاصيل ونتائج العملية المنسدلة وزر الإلغاء، وربط اسم المريض مباشرة بملف العملية الموحد**:
  * **إلغاء تفاصيل ونتائج العملية المنسدلة (`detailsRow`) وإلغاء زر الإلغاء المباشر ([resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php))**:
    - إزالة عامود `تشخيص / علاج` من جدول العمليات النشطة لتوفير المساحة وتجنب التكدس.
    - إزالة الصف المنسدل للأكورديون بالكامل (`#detailsRow{{ $surgery->id }}`) الذي كان يعرض تحاليل المختبر والأشعة والعلاجات وزر إلغاء العملية، حيث أصبحت كل هذه البيانات الرسمية تُدار حصرياً ومفصلة ومحمية بداخل ملف العملية وصالة العمليات الموحد (`/surgeries/{id}`).
    - إزالة الصف المنسدل من جدول العمليات المكتملة (`#detailsRowCompleted{{ $surgery->id }}`)، وحذف نموذج الإلغاء المخفي `#cancelSurgeryConfirmForm` والدالة `window.confirmCancelSurgery`، ليكون إلغاء العملية خاضعاً للحماية والتوثيق والسبب المحمي داخل شاشة تفاصيل العملية.
    - تحويل زر اسم المريض في جدولي العمليات النشطة والمكتملة من زر فتح أكورديون إلى رابط مباشر يفتح ملف وتفاصيل العملية (`route('surgeries.show', $surgery)`).
  * **تحسين استعلامات الأداء في الكنترولر ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
    - إزالة التحميل المسبق للعلاقات غير المستخدمة بعد الآن في صفحة الفهرس (`surgeryTreatments`, `radiologyTests`, `labTests`) من استعلامي `activeSurgeriesQuery` و`completedSurgeriesQuery`، مما يرفع سرعة الاستجابة ويقلل استهلاك الذاكرة واستعلامات قاعدة البيانات.
  * **التحقق الآلي**: اجتياز `Laravel Pint` وتجميع قوالب Blade واختبارات الميزة ذات الصلة بنجاح 100%.

- **المرحلة 193 (2026-11-12 — `main`) — ربط `مخزن عمليات رئيسي` وعُهدة العمليات بملف العملية وصرف وإرجاع المواد، وحصر تبويب المخزن بالحساب المعين له في إدارة المستخدمين**:
  * **الصرف والإرجاع المباشر بملف العملية (`tab=detail_supplies`) ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**: استبدال جدول المستلزمات اليدوي بالصرف الفوري من وجبات `مخزن عمليات رئيسي` لكافة أصناف العهدة (مستلزمات وأدوية عمليات)، مع خصم `StockBatch` وتوثيق `StockMovement` (`issue_to_patient`) و`SurgeryTreatment` (`timing = 'صرف من مخزن العمليات'`) وخيار الإرجاع العكسي (`return`)، مع تبسيط تبويب التخدير (`detail_anesthesia`) وإضافة `surgical_notes` المباشر في `detail_type`.
  * **تبويب العهدة الحصري بسجل العمليات (`/surgeries`) وعزل المخازن ([app/Models/Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php), [resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php), [app/Http/Controllers/StockTransferController.php](file:///C:/get/hospital-system/app/Http/Controllers/StockTransferController.php), [resources/views/stock-transfers/return.blade.php](file:///C:/get/hospital-system/resources/views/stock-transfers/return.blade.php))**: حصر ظهور تبويب **`مخزن وعُهدة العمليات`** حصرياً على الحساب المعين له مخزن عمليات في إدارة المستخدمين (`user->location_id` = `مخزن عمليات رئيسي`) ومنع تداخله مع مخازن الطوابق التمريضية (`getFloorSubStoreLocation()`).
  * **التحقق الآلي**: اجتياز `Laravel Pint` وتجميع Blade و4 حزم اختبارية ذات صلة (`44 passed, 621 assertions`).

- **المرحلة 192 (2026-11-12 — `main`) — توحيد شاشة ملف العملية وصالة العمليات (`/surgeries/{id}`) على نمط المحطة السريرية الموحدة، مصفوفة صلاحيات التبويبات، وقفل حساب المخدر**:
  * **إصلاح الحفظ وفصل النماذج ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**: إزالة النماذج المتداخلة واستبدالها بـ 4 نماذج مستقلة لكل تبويب (`detailDiagnosisForm`, `detailAnesthesiaForm`, `detailTeamTimingForm`, `detailSuppliesForm`) لضمان عمل الحفظ لموظف العمليات وكافة الكوادر دون تعارض، وإعادة تصميم الواجهة بالرأس التنفيذي (`.clin-header-modern`) والتبويبات الأفقية والبطاقات المنظمة (`.clin-section-card` / `.clin-field-card`) مع أزرار تعبئة سريعة وحصر طاقم التخدير في `detail_team_timing` وتبسيط `detail_anesthesia` لنوع التخدير والملاحظات فقط.
  * **مصفوفة صلاحيات تبويبات تفاصيل العملية وقفل حساب المخدر ([app/Models/SurgeryTabPermission.php](file:///C:/get/hospital-system/app/Models/SurgeryTabPermission.php), [app/Http/Controllers/SurgeryTabPermissionController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryTabPermissionController.php), [resources/views/surgeries/settings/tabs.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/settings/tabs.blade.php))**: إضافة `DETAIL_TABS` السبع في جدول الصلاحيات ومصفوفة الإعدادات (`/surgeries/settings/tabs`)، مع تطبيق كشف صارم لحساب المخدر (`isAnesthesiaUser()` + `resolveUserRolesForDetail()`) لقصر صلاحية الكتابة على `detail_anesthesia` فقط ومنع (`403`) أي تعديل من المخدر في بقية التبويبات، مع مزامنة `AnesthesiaStation` وإعداد التبويب الافتراضي الذكي حسب الدور.
  * **التحقق الآلي**: اجتياز `Laravel Pint` وتجميع قوالب Blade و5 حزم اختبارية (`48 passed, 603 assertions`) في [`tests/Feature/SurgeryTest.php`](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php) وما يرتبط بها.

- **المرحلة 191 (2026-11-12 — `main`) — منظومة جدول الإعطاء الدوائي (MAR Grid)، البحث الحي من الصيدلية، عزل مخزن الطابق للمستلزمات فقط، وإصلاح حجز العملية واختيار الغرف (`/surgeries/create`)**:
  * **الأدوية وجدول الإعطاء vs المستلزمات ([app/Models/SurgeryTreatment.php](file:///C:/get/hospital-system/app/Models/SurgeryTreatment.php), [app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php), [resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**: إضافة أعمدة الجدولة والربط الدوائي في `surgery_treatments` (`2026_11_12_004000_upgrade_surgery_treatments_for_mar_and_pharmacy.php`)، فك أي ارتباط بين `tab=treatments` ومخزن الطابق وحصر `dispenseFloorItem` على `tab=supplies` فقط، إتاحة البحث الحي بدليل الصيدلية (`clinical-station.medicines.search`) أو الكتابة الحرة للأطباء والمقيمين مع حساب مواعيد الجرعات، وإتاحة مربعات تأشير الصرف/الإعطاء (`toggle-dose`) للتمريض والأدمن فقط مع قفل 30 دقيقة/مستخدم آخر.
  * **إصلاح شاشة حجز العملية واختيار الغرفة ([resources/views/surgeries/create.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/create.blade.php))**: إعادة إضافة `<form action="{{ route('surgeries.store') }}" id="surgeryForm" ...>` و`@csrf`، ونقل تعريف متغيرات الغرف (`selectedRoomType`, `roomIdInput`, إلخ) لأعلى `DOMContentLoaded` لمنع خطأ JS TDZ عند وجود `?patient_id=...` بالرابط الذي كان يمنع تفعيل النقر على الغرف وزر الحجز.
  * **التحقق الآلي**: اجتياز 7 حزم اختبارية (`44 passed, 415 assertions`) مع اجتياز `Laravel Pint`.

- **المرحلة 190 (2026-11-11 — `main`) — توحيد جدول العلامات الحيوية والسوائل في المحطة السريرية الموحدة، إزالة تبويب السوائل من `/surgeries/{id}`، وإلغاء مؤشرات الألم والوعي والشعيرات (Unified Clinical Vitals & Fluids Single Flowsheet)**:
  * **إلغاء تبويب السوائل من شاشة العملية ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**: إزالة `#v-pills-fluids-tab` ولوحة `required_fluids` من شاشة الطبيب (`/surgeries/{surgery}`).
  * **دمج التبويب وحوكمة الصلاحيات ([app/Models/SurgeryTabPermission.php](file:///C:/get/hospital-system/app/Models/SurgeryTabPermission.php), [app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php))**: إزالة `fluids` من `TABS` وتحويل `?tab=fluids` إلى `vitals`، وقصر تعديل حقول السوائل الـ 8 على التمريض والأدمن فقط (قراءة فقط للأطباء والمقيمين)، وفي المقابل قصر **`ملاحظات الفحص السريري (Clinical Examination)`** على الأطباء والمقيمين والأدمن فقط مع إخفائها عن التمريض وحمايتها خادماً مع المزامنة المستقلة عبر `syncStationFromReadings`.
  * **جدول أفقي واحد موحد بدون تكرار وتنظيم السيرة والخطة وفصل المستلزمات عن الأدوية ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**: دمج الإدخال (الصف الأول لـ `قراءة جديدة` أو التعديل) والنتائج المسجلة تحته مباشرة داخل `#vitalsGridTable` (17 عموداً: العلامات الـ 6 + السوائل الـ 8 + الصافي + الإجراءات)، وإخفاء شريط الحفظ السفلي المكرر في `vitals` مع الاكتفاء بزر `#row1SaveBtn`، وإزالة أعمدة `pain_score` و`gcs` و`crt`، وإزالة شريط وزر إنهاء المرحلة السريرية السفلي غير المرتبط بالعمليات (`إنهاء محطة ما قبل العملية ونقل للصالة` / `إنهاء متابعة ما بعد العملية`)، مع إعادة تنظيم تبويب **السيرة المرضية (`tab=history`)** في `#clinicalHistoryGrid`، وتبويب **الخطة الطبية (`tab=plan`)** في `#clinicalPlanGrid` (مع `treatment_plan` وخلاصة توصية الجراح)، وفصل **المستلزمات الطبية (`tab=supplies`)** عن **الأدوية (`tab=treatments`)** عبر عمود `item_type` في `surgery_treatments` ([`Product::resolveItemType()`](file:///C:/get/hospital-system/app/Models/Product.php#L61-L108), [`SurgeryTabPermission`](file:///C:/get/hospital-system/app/Models/SurgeryTabPermission.php), [`ClinicalStationController`](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php)) مع حماية الصلاحيات وعزل مخزون الطابق بين التبويبين.
  * **التحقق الآلي**: اجتياز 6 حزم اختبارية (`41 passed, 333 assertions`) عبر `UnifiedClinicalStationAndTabPermissionsTest` و`ClinicalVitalsGovernanceTest` و`NursingFloorDispenseTest` و`NursingStationSurgeriesTest` و`SurgeonStationAccessAndWorkflowTest` و`SurgeryTest` مع اجتياز `Laravel Pint`.

- **دمج تحديثات فرع `hr` في الفرع الرئيسي `main` (2026-11-11) — تكامل وحدة العناية المركزة والقلبية `ICU/CCU`، تسوية الخدج `NICU`، وصلاحيات وقسائم `HR` مع الحفاظ على مسار العمل المحلي `WAMP`:**
  * **الدمج التام مع استبعاد تعديل القواعد ومسار `C:\get\hospital-system`**: تم دمج كافة الأكواد والواجهات والمايجريشنات والبذور من `origin/hr` (`b2966c1`) إلى `main` مع الحفاظ الصارم على مسار العمل الرسمي `C:\get\hospital-system` وقواعد الرفع على `main` فقط (`GEMINI.md`, `.agents/rules/hospital-core.md`).
  * **تحديث قاعدة البيانات المحلية (`hospital_pho`)**: تشغيل الـ 5 مايجريشنات الجديدة (`nicu_milk_types`, `icu_beds`, `icu_admissions`, `ccu_services`, `ccu_stay_brackets`, `ccu_settings`, إلخ) وبذور `IcuBedSeeder`, `CcuTariffSeeder`, `RolesAndPermissionsSeeder`، مع الحفاظ التام على تصميم الاستعلامات الثنائي وتكامل بطاقة **`حجز عناية مركزة (ICU)`** في [`resources/views/inquiry/create.blade.php`](file:///C:/get/hospital-system/resources/views/inquiry/create.blade.php)، واجتياز 21 حزمة اختبارية (`168 passed, 1,186 assertions`) و `Pint` (`71 files`).

- **المرحلة 188 (2026-11-11 — `main`) — إضافة خيار خروج "خرج متوفي" (`deceased`) لمحطة الطوارئ مع التقارير الطبية وسجل الحالات والتحقق المالي (Emergency Deceased Discharge Option, Reports & Financial Clearance Parity)**:
  * **الكنترولر ومنطق الخروج ([app/Http/Controllers/EmergencyController.php](file:///C:/get/hospital-system/app/Http/Controllers/EmergencyController.php))**:
    - تفعيل القيمة `deceased` ضمن خيارات التخريج المعتمدة في `EmergencyController::discharge` بنفس شرط إبراء الذمة المالية (`getUnpaidAmount()`).
    - ضبط الوجهة الافتراضية `خرج متوفي` والتوصيات الافتراضية `توثيق حالة الوفاة واستكمال الإجراءات الرسمية` بدلاً من عبارة مراجعة العيادة الخارجية.
  * **شاشة الطوارئ وسجل الحالات والتقارير ([resources/views/emergency/show.blade.php](file:///C:/get/hospital-system/resources/views/emergency/show.blade.php), [resources/views/emergency/index.blade.php](file:///C:/get/hospital-system/resources/views/emergency/index.blade.php), [resources/views/emergency/medical-report.blade.php](file:///C:/get/hospital-system/resources/views/emergency/medical-report.blade.php), [resources/views/emergency/verify-report.blade.php](file:///C:/get/hospital-system/resources/views/emergency/verify-report.blade.php))**:
    - إضافة خيار `خرج متوفي` في قائمة تسجيل الخروج مع رسالة تأكيد وشارة داكنة مميزة بعد الاعتماد.
    - توثيق وعرض حالة `deceased` في سجل الطوارئ والتقرير الطبي وصفحة التحقق عبر رمز QR.
  * **الاختبارات الآلية**:
    - إضافة واجتياز اختبار `test_emergency_discharge_deceased_workflow_and_renders_across_views` مع فحص قفل الدفع المسبق في `EmergencyFinancialClearanceAndCancellationTest` بنجاح 100% (`25 passed, 139 assertions`).

- **المرحلة 189 (2026-11-11) — منظومة التسعير الديناميكي وشرائح الإقامة للضمان، شمول التحاليل والأشعة بالباقة بنقرة زر، الكاشير والحسابات والمطالبات، ونقل التشخيص الأولي و ICD-10 لداخل إضبارة الطبيب (CCU Dynamic Tariffs, Official MOH Brackets, One-Click Bundling, Cashier, Treasury & Claims)**:
  * **نقل التشخيص السريري الأولي و ICD-10 ومقياس GCS إلى إضبارة الطبيب**:
    - إزالة حقول `primary_diagnosis`، `icd10_code`، و `initial_gcs` من واجهة الاستعلامات [`resources/views/icu/admit.blade.php`](file:///C:/get/hospital-system/resources/views/icu/admit.blade.php) وجعلها اختيارية في `IcuAdmissionController@store`، مع استبدالها ببطاقة تشخيص أولي للطبيب داخل [`resources/views/icu/show.blade.php`](file:///C:/get/hospital-system/resources/views/icu/show.blade.php) تدعم البحث الحي الثنائي اللغة عبر `route('doctor.icd10.search')` وتحفظ عبر `CcuCareController@updateInitialDiagnosis`.
  * **قاعدة البيانات والنماذج والتحكم الديناميكي للتسعير (`database/migrations/2026_11_11_100000_create_ccu_billing_and_tariffs_tables.php`, [`CcuTariffSeeder.php`](file:///C:/get/hospital-system/database/seeders/CcuTariffSeeder.php))**:
    - إنشاء جداول `ccu_services`, `ccu_stay_brackets`, `ccu_settings`, `ccu_service_logs`, `ccu_payments` وتطوير نماذج `CcuService`, `CcuStayBracket`, `CcuSetting`, `CcuServiceLog`, `CcuPayment`.
    - شاشة التحكم الديناميكي للباقات، الخدمات الإضافية، الشرائح السعرية، والإعدادات [`resources/views/ccu/services/index.blade.php`](file:///C:/get/hospital-system/resources/views/ccu/services/index.blade.php) عبر [`CcuServiceController.php`](file:///C:/get/hospital-system/app/Http/Controllers/CcuServiceController.php).
  * **محرك الاحتساب المالي وتدرج الضمان وتسعيرة الكاش المخصصة ([`app/Services/CcuBillingService.php`](file:///C:/get/hospital-system/app/Services/CcuBillingService.php))**:
    - دعم باقات الضمان الرسمية (التنفس الصناعي 750,000 د.ع، الأكسجين 500,000 د.ع)، تدرج الشرائح السنوية للمستشفى الخاص وسقف 30 يوماً سنوياً عبر جميع رقودات المريض في السنة.
    - دعم الكاش بنمطين: الباقة اليومية الشاملة أو تسعيرة السرير المنفصلة (`cash_bed_only_separate`) مع خدمات إضافية مخصصة (`CcuService::TYPE_ADDON`).
    - إظهار التحاليل والأشعة والاستشارات المشمولة بالباقة بمبلغ `0` د.ع للمريض مع حفظ القيمة الأصلية (`original_price` / `bundled_reference_total`) والتبديل اللحظي بنقرة زر بين مشمول بالباقة أو إضافي عبر `toggleOrderBilling` و `toggleConsultationBilling`.
  * **الكاشير، المطالبات، التحليلات، والخزينة المركزية**:
    - تطوير [`app/Http/Controllers/CcuCashierController.php`](file:///C:/get/hospital-system/app/Http/Controllers/CcuCashierController.php) وتكامله مع [`CashierController.php`](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php) (`#tab-ccu`), [`AccountantController.php`](file:///C:/get/hospital-system/app/Http/Controllers/AccountantController.php) (`ccuAnalytics`), [`HealthInsuranceClaimController.php`](file:///C:/get/hospital-system/app/Http/Controllers/HealthInsuranceClaimController.php) (تتبع القيمة الأصلية، حصة الضمان، المقبوض، والباقي بذمة الضمان `hia_remaining_balance`), و[`HospitalTreasuryController.php`](file:///C:/get/hospital-system/app/Http/Controllers/HospitalTreasuryController.php).
  * **حزمة الاختبارات الآلية الشاملة**:
    - إنشاء [`tests/Feature/CcuBillingAndCashierTest.php`](file:///C:/get/hospital-system/tests/Feature/CcuBillingAndCashierTest.php) واجتياز كامل الاختبارات المشتركة (`31 passed, 213 assertions`) وتنسيق Pint بنجاح.

- **المرحلة 188 (2026-11-10) — بناء وتكامل منظومة التوجيهات السريرية المثبتة، جدول الأدوية والمضخات MAR، الاستشارات التخصصية، والمرفقات وتخطيط القلب (CCU Clinical Care Suite)**:
  * **التوجيهات السريرية المثبتة في رأس الإضبارة (Pinned Clinical Directives)**:
    - بناء صندوق التوجيهات السريرية المشددة في أعلى ملف المريض [`resources/views/icu/show.blade.php`](file:///C:/get/hospital-system/resources/views/icu/show.blade.php) يبرز أوامر الاستشاري العاجلة مع اسم الطبيب والتوقيت وزر تثبيت/إلغاء التثبيت 📌 بنقرة واحدة عبر نموذج [`app/Models/CcuDoctorNote.php`](file:///C:/get/hospital-system/app/Models/CcuDoctorNote.php).
  * **جدول إعطاء وجرعات الأدوية ومضخات التسريب الوريدي (MAR Flowsheet)**:
    - تبويب مخصص `#medicationTabPane` لجدولة الأدوية بالساعة (كل 4، 6، 8، 12، 24 ساعة) ودعم مضخات التسريب الوريدي المستمر (Syringe Pumps) مع معدل الضخ (Infusion Rate) وتحديد مسار الإعطاء (IV, Infusion, IM, NG Tube, Nebulizer, SC).
    - زر التمريض السريع "توثيق الإعطاء الآن" مع توثيق الممرض وتحديث الموعد القادم آلياً، وشارات التنبيه الذكية (🔴 متأخر Overdue، 🟡 مستحق الآن Due Now، 🟢 القادم Upcoming) عبر نموذج [`app/Models/CcuMedicationSchedule.php`](file:///C:/get/hospital-system/app/Models/CcuMedicationSchedule.php).
  * **استشارات الأطباء التخصصية (CCU Consultations Suite)**:
    - تبويب مخصص `#consultationsTabPane` لطلب الاستشارات السريرية الداخلية (مع اختيار استشاري المستشفى من القائمة) أو الخارجية، وتوثيق الأجور، ورد وتوصيات الاستشاري المعتمدة بنقرة زر عبر نموذج [`app/Models/CcuConsultation.php`](file:///C:/get/hospital-system/app/Models/CcuConsultation.php).
  * **أرشيف المرفقات الطبية وتخطيط القلب (ECG & Medical Attachments)**:
    - تبويب مخصص `#attachmentsTabPane` لرفع وتخزين مستندات وتخطيط القلب ECG والتقارير الطبية والمفراس الخارجي ومعاينتها فورياً عبر نموذج [`app/Models/CcuAttachment.php`](file:///C:/get/hospital-system/app/Models/CcuAttachment.php).
  * **المتحكم والمسارات والتحقق الآلي**:
    - بناء متحكم [`app/Http/Controllers/CcuCareController.php`](file:///C:/get/hospital-system/app/Http/Controllers/CcuCareController.php) ومجموعة مسارات `ccu.*` في [`routes/web.php`](file:///C:/get/hospital-system/routes/web.php).
    - حزمة اختبارات آلية جديدة شاملة [`tests/Feature/CcuClinicalSuiteTest.php`](file:///C:/get/hospital-system/tests/Feature/CcuClinicalSuiteTest.php) نجحت بالكامل بنسبة 100% (`6 passed, 47 assertions`) إلى جانب استقرار حزمة [`tests/Feature/IcuModuleTest.php`](file:///C:/get/hospital-system/tests/Feature/IcuModuleTest.php) (`15 passed, 65 assertions`).

- **المرحلة 187 (2026-11-10) — تثبيت موضع الشاشة والتبويبات النشطة في إضبارة العناية المركزة، وبناء منظومة حجز تحاليل المختبر والأشعة والتصوير الطبي المتكاملة (ICU Dossier Tab & Scroll State Persistence, Fast Lab & Bedside Radiology Suite)**:
  * **تثبيت موضع التمرير واستمرارية التبويب النشط ومنع القفز لأعلى الصفحة ([resources/views/icu/show.blade.php](file:///C:/get/hospital-system/resources/views/icu/show.blade.php), [app/Http/Controllers/IcuController.php](file:///C:/get/hospital-system/app/Http/Controllers/IcuController.php))**:
    - بناء محرك حفظ واستعادة موضع التمرير اللحظي في `sessionStorage` بمفتاح فريد لكل مريض `icu_scroll_{id}` وتوقيت مرحلي يمنع إعادة القفز لبداية الصفحة عند حفظ المؤشرات، ميزان السوائل، أوامر الأطباء، مهام الرعاية، أو إرسال التحاليل والأشعة.
    - دعم واستمرارية التبويب النشط عبر `_active_tab` ورابط الـ URL Hash وتخزينه في `sessionStorage`، مع إرساله تلقائياً عبر أي نموذج أو زر في الصفحة واستعادته عبر دالة `redirectBackWithActiveState($request, $tab, $fallbackUrl)`.
  * **منظومة حجز تحاليل المختبر السريعة والمتكاملة مع المختبر المركزي ([resources/views/icu/show.blade.php](file:///C:/get/hospital-system/resources/views/icu/show.blade.php), [app/Http/Controllers/IcuController.php](file:///C:/get/hospital-system/app/Http/Controllers/IcuController.php), [app/Http/Controllers/LabStaffController.php](file:///C:/get/hospital-system/app/Http/Controllers/LabStaffController.php))**:
    - تبويب مخصص `#labTabPane` يشمل بحثاً ذكياً فوري Autocomplete من كتالوج المستشفى، أزرار إدراج سريعة بنقرة واحدة لتحاليل العناية الشائعة (ABG غازات دم شرياني، CBC، Electrolytes شوارد، RFT كلى، LFT كبد، PT/INR تخثر، Troponin، وزرع الدم Blood Culture)، صينية شرائح تفاعلية (Chips Tray)، وتحديد أولوية الفحص (Normal, Urgent, 🚨 STAT).
    - توليد الطلب المركزي في جدول `requests` بنوع `lab` وربطه بحقل `icu_admission_id`، وتوزيع الفحوصات في جدول `icu_medical_orders` مع مزامنة حالة الإنجاز والنتائج والمدى المرجعي آلياً عند إدخالها من قبل فني المختبر في `LabStaffController`.
  * **منظومة حجز وتصوير الأشعة والسونار المتنقل بجانب السرير ([resources/views/icu/show.blade.php](file:///C:/get/hospital-system/resources/views/icu/show.blade.php), [app/Http/Controllers/IcuController.php](file:///C:/get/hospital-system/app/Http/Controllers/IcuController.php), [app/Http/Controllers/RadiologyStaffController.php](file:///C:/get/hospital-system/app/Http/Controllers/RadiologyStaffController.php))**:
    - تبويب مخصص `#radiologyTabPane` لحجز الأشعة النقالة Portable CXR، سونار بجانب السرير FAST/Abdomen، إيكو القلب Bedside Echo، ومفراس الدماغ CT Brain مع حقل للملاحظات السريرية ودواعي الفحص.
    - توليد الطلب المركزي في `requests` بنوع `radiology` وتحديد التصنيف الفرعي تلقائياً، ومزامنة تقرير الطبيب الشعاعي آلياً مع إضبارة العناية عند اعتماده في `RadiologyStaffController`.
  * **المايجريشن والنماذج والعلاقات البرمجية (`database/migrations/2026_11_10_115143_add_icu_admission_id_to_requests_and_enrich_icu_orders.php`, [app/Models/Request.php](file:///C:/get/hospital-system/app/Models/Request.php), [app/Models/IcuAdmission.php](file:///C:/get/hospital-system/app/Models/IcuAdmission.php), [app/Models/IcuMedicalOrder.php](file:///C:/get/hospital-system/app/Models/IcuMedicalOrder.php))**:
    - إضافة حقل `icu_admission_id` إلى جدول `requests`.
    - إثراء جدول `icu_medical_orders` بحقول الفحوصات والأسعار والمجموعات والنتائج والمرفقات ومسؤول الطلب.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/IcuModuleTest.php](file:///C:/get/hospital-system/tests/Feature/IcuModuleTest.php))**:
    - توسيع الاختبارات إلى 15 اختباراً آلياً تشمل اختبارات رندرة تبويبات المختبر والأشعة، حجز التحاليل بأولوية STAT، حجز الأشعة والسونار، وثبات موضع التبويب النشط بنسبة نجاح 100% (`15 passed, 65 assertions`).

- **المرحلة 186 (2026-11-10) — ربط وتكامل حجز العناية المركزة في الاستعلامات وزيارات المرضى، وتخصيص استلام المريض الفوري (ICU Inquiry Admission Integration, Pre-selected Patient Handoff & Reception Booking Card Suite)**:
  * **تكامل شاشة الاستعلامات `inquiry.create` وزيارات المرضى `visits.edit`**:
    - إضافة بطاقة خدمة "حجز عناية مركزة (ICU)" في شاشة إنشاء طلبات الاستعلامات [resources/views/inquiry/create.blade.php](file:///C:/get/hospital-system/resources/views/inquiry/create.blade.php) وشاشة الزيارة [resources/views/visits/edit.blade.php](file:///C:/get/hospital-system/resources/views/visits/edit.blade.php) بنفس الهوية البصرية لبطاقات العمليات والطوارئ والخدج (`fa-heartbeat`, `text-danger`).
    - تمرير رقم المريض `?patient_id={id}` تلقائياً للرابط `icu.admissions.create`.
  * **تطوير واجهة الاستقبال وحجز سرير العناية ([resources/views/icu/admit.blade.php](file:///C:/get/hospital-system/resources/views/icu/admit.blade.php), [app/Http/Controllers/IcuAdmissionController.php](file:///C:/get/hospital-system/app/Http/Controllers/IcuAdmissionController.php))**:
    - استلام فوري للمريض المحول من الاستعلامات مع تنزيل بطاقة بياناته آلياً وإخفاء حقل البحث الأولي مع إمكانية التغيير بنقرة زر.
    - قراءة حالة الضمان بدقة وإلغاء الافتراض الخاطئ لمسار الضمان لمرضى الكاش غير المشمولين، وإظهار تفاصيل الضمان فقط للمرضى المشمولين فعلياً.
    - حماية واستدعاء علاقة `healthInsuranceCategory` في `IcuAdmissionController@create`.
  * **مصفوفة الصلاحيات والحوكمة ([database/seeders/RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php), [app/Http/Controllers/InquiryController.php](file:///C:/get/hospital-system/app/Http/Controllers/InquiryController.php))**:
    - إسناد صلاحيتي `view icu radar` و `admit icu patients` لدوري الاستعلامات `receptionist` و `inquiry_staff`.
    - شمول صلاحيات العناية في مصفوفة السماح لدخول الاستعلامات `InquiryController@create`، مع حماية استدعاءات أدوار السونار والإيكو المفقودة بـ `try/catch`.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/IcuModuleTest.php](file:///C:/get/hospital-system/tests/Feature/IcuModuleTest.php))**:
    - توسيع الاختبارات إلى 11 اختباراً آلياً تشمل اختبار رندرة بطاقة العناية المركزة في الاستعلامات، واختبار تسكين المريض المحول بـ `patient_id` بنجاح 100% (`11 passed, 43 assertions`).

- **المرحلة 185 (2026-11-10) — بناء وتفعيل منظومة وحدة العناية المركزة والحرجة المتكاملة: رادار الأسرة الحي، شيت المؤشرات بالساعة، ميزان السوائل، وأوامر الطبيب ورعاية التمريض (ICU / CCU Comprehensive Critical Care Suite)**:
  * **قاعدة البيانات وهيكلية الجداول (`database/migrations/2026_11_10_095152_create_icu_beds_and_admissions_tables.php`)**:
    - جدول `icu_beds`: إدارة أسرة العناية (رقم السرير، نوعه: عام / عزل ضغط سالب / عناية قلبية CCU / عناية عصبية Neuro، الغرفة، الطابق، الحالة التشغيلية، السعر اليومي).
    - جدول `icu_admissions`: سجل دخول وتسكين المريض بالعناية، التشخيص السريري، ICD-10، مقياس الوعي الأولي GCS، جهاز التنفس، العزل، والتأمين.
    - جدول `icu_vital_signs`: شيت العلامات الحيوية بالساعة (GCS، النبض، الضغط، MAP المحسوب آلياً، SpO2، الحرارة، سكر الدم، إعدادات جهاز التنفس Mode، FiO2، PEEP).
    - جدول `icu_fluid_balances`: ميزان السوائل بالساعة (الداخل: محاليل، دم، تغذية، علاجات / الخارج: بول، مفارغ جراحية، رايل، مفرزات، واحتساب الصافي Net Balance تلقائياً).
    - جدول `icu_medical_orders`: أوامر الطبيب الاستشاري السريرية بأولويات (`routine`, `urgent`, `stat`) ومتابعة تنفيذها من الكادر.
    - جدول `icu_care_schedules`: جدول مهام الرعاية التمريضية بالساعة (تقليب، سحب مفرزات، تفريغ كيس البول).
  * **النماذج والعلاقات البرمجية ([app/Models/IcuBed.php](file:///C:/get/hospital-system/app/Models/IcuBed.php), [app/Models/IcuAdmission.php](file:///C:/get/hospital-system/app/Models/IcuAdmission.php), [app/Models/IcuVitalSign.php](file:///C:/get/hospital-system/app/Models/IcuVitalSign.php), [app/Models/IcuFluidBalance.php](file:///C:/get/hospital-system/app/Models/IcuFluidBalance.php), [app/Models/IcuMedicalOrder.php](file:///C:/get/hospital-system/app/Models/IcuMedicalOrder.php), [app/Models/IcuCareSchedule.php](file:///C:/get/hospital-system/app/Models/IcuCareSchedule.php))**:
    - الفرز الرقمي المتسلسل للأسرة `scopeOrderByNumber()`.
    - شارات وأنماط الأنواع والحالات (`type_badge_class`, `status_badge_class`, `gcs_badge_class`).
    - محرك الاحتساب التلقائي لصافي السوائل والضغط الشرياني الوسطي MAP.
    - إضافة تصنيف `'icu' => 'إيراد العناية المركزة (ICU)'` في [app/Models/FinancialTransaction.php](file:///C:/get/hospital-system/app/Models/FinancialTransaction.php).
  * **المتحكمات والمسارات البرمجية ([app/Http/Controllers/IcuController.php](file:///C:/get/hospital-system/app/Http/Controllers/IcuController.php), [app/Http/Controllers/IcuAdmissionController.php](file:///C:/get/hospital-system/app/Http/Controllers/IcuAdmissionController.php), [app/Http/Controllers/IcuBedController.php](file:///C:/get/hospital-system/app/Http/Controllers/IcuBedController.php))**:
    - رادار العناية الحية (`/icu`): إحصائيات KPI الحية للأسرة وأجهزة التنفس، شبكة الكروت التفاعلية مع تنبيهات مستوى الوعي GCS.
    - إضبارة مريض العناية (`/icu/admissions/{id}`): شيت الفلوشيت بالساعة، ميزان السوائل In/Out، أوامر الطبيب، وتخريج المريض وتحويل السرير للتعقيم.
    - تسجيل الدخول وحجز السرير (`/icu/admissions/create`): تسكين المريض، تحديد السرير والطبيب والتشخيص الأولي.
    - إدارة وتوزيع الأسرة (`/icu/beds`): متابعة التعقيم والصيانة وإضافة أسرّة جديدة.
  * **واجهات المستخدم والقائمة الجانبية ([resources/views/icu/radar.blade.php](file:///C:/get/hospital-system/resources/views/icu/radar.blade.php), [resources/views/icu/show.blade.php](file:///C:/get/hospital-system/resources/views/icu/show.blade.php), [resources/views/icu/admit.blade.php](file:///C:/get/hospital-system/resources/views/icu/admit.blade.php), [resources/views/icu/beds/index.blade.php](file:///C:/get/hospital-system/resources/views/icu/beds/index.blade.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
    - تصميم عصري متناسق مع شاشات الخدج والعمليات، إبراز حالات الأسرة بالألوان، وإدراج قسم العناية المركزة في القائمة الجانبية بشارة عدد المرضى الراقدين.
  * **الصلاحيات وحزمة الاختبارات الآلية الشاملة ([database/seeders/RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php), [tests/Feature/IcuModuleTest.php](file:///C:/get/hospital-system/tests/Feature/IcuModuleTest.php))**:
    - 8 صلاحيات دقيقة وأدوار `icu_doctor` و `icu_nurse`.
    - 7 اختبارات ميزة آلية تغطي الدخول، الرادار، العلامات، السوائل، الأوامر، والتخريج بنسبة نجاح 100% (`7 passed, 30 assertions`).

- **المرحلة 184 (2026-11-10) — منظومة تسوية وتفكيك الضمان الصحي الشاملة للخدج: المختبر والأشعة والاستشارات والحاضنة بالتوازي وفق الفئة E (NICU Comprehensive Insurance Settlement & Co-payment Engine)**:
  * **تفكيك وحساب تسوية الضمان للخدج وفق الفئة E ([app/Services/NicuBillingService.php](file:///C:/get/hospital-system/app/Services/NicuBillingService.php))**:
    - اعتماد الفئة E رسمياً لكافة الخدج المشمولين بالضمان (`is_insured`):
      * الفحوصات المخبرية (`lab`): مساهمة الضمان 75% وتحمل المريض 25% للفحوصات المشمولة بالضمان (`is_hi_active`).
      * الفحوصات الإشعاعية (`radiology`): مساهمة الضمان 75% وتحمل المريض 25% للفحوصات المشمولة.
      * استشارات الأطباء (`consultations`): الاستشارات الداخلية مشمولة بنسبة 90% ضمان و 10% مريض، والاستشارات الخارجية غير مشمولة (100% كاش على المريض).
      * الفحوصات والخدمات غير المشمولة: 100% كاش على المريض و 0% على الضمان مع بيان شارة صريحة.
      * أجور الحاضنة اليومية: احتسابها وفق الفئة E ومصفوفة عمر الحمل الرسمية للوزارة.
    - تجميع المجاميع الكبرى: `insurance_grand_total` (إجمالي مطالبة الضمان = حاضنة + مختبر + أشعة + استشارات داخلية)، و `patient_grand_total` (حصة المريض المستحقة)، وتحديث الحجز تلقائياً.
  * **تحديث كشف الحساب التراكمي للخدج ([resources/views/nicu/statement.blade.php](file:///C:/get/hospital-system/resources/views/nicu/statement.blade.php))**:
    - إضافة أعمدة التغطية المعتمدة ومساهمة الضمان وتحمل المريض كاش في جدول الاستشارات (Section 2) وجدول الفحوصات المخبرية والأشعة (Section 3).
    - إضافة سطور مجاميع مفككة للمختبر والأشعة والاستشارات.
    - تفكيك بطاقة الخلاصة المالية النهائية لتبين بدقة مساهمات الضمان وحصص المريض في الحاضنة والمختبر والأشعة والاستشارات، وإجمالي مطالبة الضمان، وحصة المريض، والمسدد، والفائض المستحق للإرجاع إن وجد.
  * **تحديث واجهة تسديد الكاشير الموحدة للخدج ([resources/views/cashier/nicu/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu/payment-form.blade.php))**:
    - قراءة وتحديث أرقام شريط الموقف المالي العلوي وصندوق التسديد الذكي لحساب المتبقي الحقيقي للدفع دون مطالبة ولي الأمر بأي مبالغ يغطيها الضمان.
    - إضافة أعمدة وشارات التغطية ومساهمات الضمان وتحمل المريض في جداول التحاليل والأشعة والاستشارات مع سطر المجاميع المفككة لكل قسم.
  * **تحديث شيت كشوفات ومطالبات الضمان للخدج ([resources/views/cashier/nicu-statements.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-statements.blade.php))**:
    - تحديث عمود تفصيل البنود لإبراز مساهمة الضمان بالحاضنة والمختبر والأشعة والاستشارات، ومطابقة مطالبة الضمان الشاملة.
  * **تحديث وصل التسوية والخدمات التفصيلي الحراري/A4 ([resources/views/cashier/nicu-itemized-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-itemized-print.blade.php))**:
    - إدراج تفكيك نسب ومبالغ الضمان والمريض تحت كل تحليل وفحص أشعة واستشارة، وتحديث صندوق الموقف المالي الشامل.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/NicuInsuranceSettlementAndClaimsTest.php](file:///C:/get/hospital-system/tests/Feature/NicuInsuranceSettlementAndClaimsTest.php))**:
    - 5 اختبارات ميزة آلية تغطي: الاحتساب التلقائي لأيام الرقود، مصفوفة عمر الحمل، انعكاس المطالبات في الخزينة المركزية، كشوفات الكاشير، وتفكيك مساهمة الضمان والمريض في التحاليل والاستشارات وشاشتي الكشف والكاشير بنجاح 100% (`5 passed, 35 assertions`).

- **المرحلة 183 (2026-11-09) — ضبط الفرز الرقمي المتسلسل للحاضنات (1 إلى 16)، والتكامل التام بين حاضنات العناية وكبسولات العلاج الضوئي (NICU Natural Numerical Sequence Sorting & Dynamic Phototherapy/Capsule Suite)**:
  * **الفرز الرقمي الصحيح للحاضنات ([app/Models/Incubator.php](file:///C:/get/hospital-system/app/Models/Incubator.php))**:
    - معالجة مشكلة الفرز الأبجدي للنصوص (Lexicographical Sorting) الناتج عن تخزين `incubator_number` كـ `VARCHAR` والذي كان يفرز 10 و 11 مباشرة بعد 1.
    - إضافة الـ Scope المخصص `scopeOrderByNumber($query, $direction = 'asc')` الداعم لمحركي MySQL (`CAST(incubator_number AS UNSIGNED)`) و SQLite (`CAST(incubator_number AS INTEGER)`).
  * **تطبيق الفرز الرقمي عبر كافة شاشات ومسارات الحاضنات**:
    1. لوحة الخدج الرئيسية `/nicu` عبر [app/Http/Controllers/NicuController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuController.php).
    2. واجهة حجز واستقبال الخديج `/incubator-reservations/create` عبر [app/Http/Controllers/IncubatorReservationController.php](file:///C:/get/hospital-system/app/Http/Controllers/IncubatorReservationController.php) وقوائم الاختيار التفاعلية.
    3. إدارة الحاضنات `/incubators` عبر [app/Http/Controllers/IncubatorController.php](file:///C:/get/hospital-system/app/Http/Controllers/IncubatorController.php).
  * **الحوكمة والتكامل التام لنوعي الحاضنات (حاضنة اعتيادية مقابل كبسولة علاج ضوئي)**:
    - تحديث `getTypeNameAttribute`, `getTypeColorAttribute`, `getTypeIconAttribute`, و `scopeOfType` لدعم التوافق المزدوج مع نوعي `capsule` و `phototherapy`.
    - تحديث إحصائيات KPIs التراكمية في شاشة الخدج الرئيسية وشاشة إدارة الحاضنات لتظهر بدقة عدد الحاضنات وعدد كبسولات العلاج الضوئي بشكل مستقل.
    - تحديث تبويبات شاشة `/incubators` ومرشحات شاشة `/incubator-reservations/create` لفرز وفلترة النوعين بسلاسة مع الحفاظ التام على الأرقام القائمة للمستشفى.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/IncubatorSortingAndTypeTest.php](file:///C:/get/hospital-system/tests/Feature/IncubatorSortingAndTypeTest.php))**:
    - 4 اختبارات ميزة آلية تغطي: الفرز الرقمي المتسلسل، محاكاة الـ 16 حاضنة في NICU، حجز واستقبال الخديج وفلترة العلاج الضوئي، وتبويبات إدارة الحاضنات بنسبة نجاح 100% (`4 passed, 13 assertions`).

- **المرحلة 182 (2026-11-08) — إصلاح حفظ تفكيك الرواتب، تفكيك مصفوفة صلاحيات HR، قالب وقسيمة الراتب الفردية وطباعة باجة الموظف الرسمية (HR Payroll Model Fix, Granular Permissions Matrix, Official Employee ID Badge & Individual Payslip Suite)**:
  * **إصلاح الخلل البرمجي في موديل الرواتب ([app/Models/HrPayroll.php](file:///C:/get/hospital-system/app/Models/HrPayroll.php), [database/migrations/2026_10_06_054330_add_new_fields_to_hr_payrolls_table.php](file:///C:/get/hospital-system/database/migrations/2026_10_06_054330_add_new_fields_to_hr_payrolls_table.php))**:
    - إضافة كافة حقول التفكيك المالي المفقودة إلى `$fillable` و `$casts` في `HrPayroll`:
      (`overtime_amount`, `absence_deduction`, `loan_deduction`, `social_security_amount`, `tax_amount`, `payment_method`, `is_paid`, `paid_at`).
    - تصحيح الميغريشن الداعم `2026_10_06_054330_add_new_fields_to_hr_payrolls_table.php` بفحص وإضافة الأعمدة في حال عدم وجودها في البيئات الجديدة والاختبارات الآلية لـ SQLite.
    - إضافة دوال المساعدة الحسابية `total_earnings` و `total_deductions` وعلاقة `payrollCycle()`.
  * **تفكيك مصفوفة صلاحيات الموارد البشرية (Granular HR Permissions Matrix & Roles Governance)**:
    - تفكيك الصلاحيات إلى 19 صلاحية دقيقة ومستقلة:
      (`view hr`, `view employees`, `create employees`, `edit employees`, `delete employees`, `print employee badges`, `manage hr documents`, `manage hr actions`, `view hr payroll`, `create hr payroll`, `approve hr payroll`, `delete hr payroll`, `print hr payslips`, `manage hr loans`, `manage hr absences`, `manage hr overtime`, `manage hr shifts`, `manage hr schedules`, `manage hr settings`).
    - تحديث [database/seeders/RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php) وإسناد كافة الصلاحيات لدور مدير الموارد البشرية `hr_manager` والمدير `admin`.
    - تحديث [app/Http/Controllers/RoleManagementController.php](file:///C:/get/hospital-system/app/Http/Controllers/RoleManagementController.php):
      * إدراج قسم `hr` ضمن ترتيب موديولات المستشفى `getModuleOrder()`.
      * تصنيف الصلاحيات وعنونتها بشارات وأسماء عربية واضحة في `getPermissionDefinitions()`.
      * تصنيف دور `hr_manager` ضمن فئة الإدارة وشارة `مدير الموارد البشرية (HR)`.
      * عزل صلاحيات الـ HR ضمن مجموعة `permissionGroup('hr')`.
    - حماية كافة متحكمات الموارد البشرية عبر الـ Middlewares بالصلاحيات الدقيقة (`HrPayrollController`, `HrShiftController`, `HrScheduleController`, `HrLoanController`, `HrAbsenceController`, `HrOvertimeController`, `HrActionSettingController`, `HrSettingsController`, `HrAllowanceController`, `HrEmployeeController`).
    - تحديث روابط وأقسام الموارد البشرية في القائمة الجانبية [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php) لتتوافق مع الصلاحيات الدقيقة.
  * **قالب وقسيمة الراتب الفردية للموظف (Individual Employee Payslip Print)**:
    - بناء واجهة طباعة قسيمة الراتب الفاخرة [resources/views/hr/payrolls/payslip.blade.php](file:///C:/get/hospital-system/resources/views/hr/payrolls/payslip.blade.php) والمسار `Route::get('payrolls/slip/{slip}/print')` بالصلاحية `print hr payslips`.
    - جدول مالي مزدوج: استحقاقات ومكافآت (الأساسي، المخصصات، الإضافي، المكافآت) مقابل استقطاعات وخصومات (الغياب، العقوبات، قسط السلفة، الضمان الاجتماعي، الضريبة).
    - بطاقة صافي الراتب المستحق بالدينار العراقي وخانات التواقيع الثلاثية (مسؤول الرواتب، المحاسب المالي، ومدير الموارد البشرية).
    - إضافة زر `[ 🖨️ قسيمة الراتب ]` في جدول شيت الرواتب المركزي [resources/views/hr/payrolls/show.blade.php](file:///C:/get/hospital-system/resources/views/hr/payrolls/show.blade.php).
  * **طباعة باجة وهوية الموظف الرسمية (Official Staff ID Badge / CR80 PVC Standard)**:
    - بناء واجهة بطاقة الهوية الرسمية [resources/views/hr/employees/badge.blade.php](file:///C:/get/hospital-system/resources/views/hr/employees/badge.blade.php) والمسار `Route::get('employees/{employee}/badge')` بالصلاحية `print employee badges`.
    - تصميم قياسي مزدوج (وجه أمامي ووجه خلفي):
      * **الوجه الأمامي:** شريط ألوان الأقسام، الشعار، صورة الموظف، الاسم الكامل، المسمى، كود `EMP-xxxx`، وباركود رقمي visual barcode.
      * **الوجه الخلفي:** فصيلة الدم، هاتف الطوارئ، الرقم الوطني، رقم ترخيص المهنة للأطباء، تعليمات المستشفى، ومربع الختم الرسمي.
    - إضافة أزرار طباعة الباجة في سجل الموظفين [resources/views/hr/employees/index.blade.php](file:///C:/get/hospital-system/resources/views/hr/employees/index.blade.php) وإضبارة الموظف [resources/views/hr/employees/show.blade.php](file:///C:/get/hospital-system/resources/views/hr/employees/show.blade.php).
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/HREmployeeTest.php](file:///C:/get/hospital-system/tests/Feature/HREmployeeTest.php))**:
    - توسيع الاختبارات لتشمل: اختبار حفظ تفكيك الرواتب في الموديل، اختبار رندرة وطباعة باجة الموظف، واختبار قسيمة الراتب الفردية بنسبة نجاح 100% (`8 passed, 38 assertions`).

- **المرحلة 181 (2026-11-08) — نظام تسديد وتصفية رسوم الخدج والحاضنات الموحد وإلغاء الأمانات والسلف بسلاسة واجهة العمليات الجراحية (NICU Cashier Unified Folio & Payment Suite, Advance Deposit Deprecation & Inpatient Settlement Engine)**:
  * **إلغاء مصطلحي "الأمانات" و"السلفة" وتوحيد المفهوم المالي تحت "التسديد"**:
    - حذف مسميات الأمانات والسلف تماماً من الكاشير والواجهات والإيصالات.
    - توحيد المسميات في [app/Models/NicuPayment.php](file:///C:/get/hospital-system/app/Models/NicuPayment.php) والواجهات إلى: (تسديد رسوم الحاضنة، دفعة تسديد، تسديد وتصفية نهائية، واسترجاع فائض الرسوم).
    - تحديث سندات القبض والإيصالات الحرارية في [resources/views/cashier/nicu-receipt.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-receipt.blade.php)، [resources/views/cashier/nicu-receipt-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-receipt-print.blade.php)، و [resources/views/cashier/nicu-itemized-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-itemized-print.blade.php).
  * **واجهة تسديد رسوم الخدج المستقلة الفاخرة (ستايل العمليات الجراحية الموحد) ([resources/views/cashier/nicu/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu/payment-form.blade.php))**:
    - صفحة تسديد وتصفية عريضة بديلة للمودال القديم.
    - بطاقتان علويتان: (بيانات الحاضنة، باقة الخدمة الحالية، الطبيب المشرف، تاريخ وساعة الدخول، وعدد الأيام الفندقية والساعات المحسوبة حياً) + (بيانات ولي الأمر، الهاتف، وشارة التغطية كاش أو ضمان وطني).
    - شريط الأرقام المالية العلوي: إجمالي التكلفة المعتمدة | المسدد سابقاً | المتبقي للتسديد أو فائض مستحق الإرجاع.
    - كشف البنود السريرية المفصلة: باقات الإقامة والتنفس، الفحوصات المخبرية، الأشعة والسونار، الاستشارات الطبية، الأدوية وتغذية الخدج، وسجل الدفعات المسددة سابقاً مع أزرار المعاينة والطباعة.
    - صندوق التسديد السفلي الذكي (Smart Payment Dock) بنفس ستايل العمليات: كبسولات `[ 🟢 تسديد كامل المبلغ المتبقي ]` مقابل `[ 🟡 تسديد جزئي (دفعة مقبوضة الآن) ]` مع شارة حية لاحتساب المتبقي واقتراحات سريعة للمبالغ وخيارات نقد/بطاقة.
    - قسم إرجاع الفائض المالي (Refund) بنقرة واحدة عند وجود مبالغ مسددة بالزيادة كخروج الطفل المبكر.
  * **تحديث شاشة الكاشير الرئيسية ([resources/views/cashier/index.blade.php](file:///C:/get/hospital-system/resources/views/cashier/index.blade.php))**:
    - بقاء جدول الخدج في مكانه الطبيعي داخل الكاشير.
    - تحويل زر "قبض دفعة" إلى زر: `[ 💳 تسديد الرسوم ]` ينقل لصفحة التسديد مباشرة، وإزالة المودالات المنبثقة القديمة.
  * **مسارات وتحكم السيرفر ([routes/web.php](file:///C:/get/hospital-system/routes/web.php), [app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
    - مسار `GET /cashier/nicu/{reservation}/payment` باسم `cashier.nicu.payment.form`.
    - تابع `showNicuPaymentForm()` و `processNicuPayment()` لدعم التسديد الكامل والجزئي واسترجاع الفائض وحفظ السندات والعمليات بنزاهة محاسبية كاملة.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/NicuCashierPaymentFormTest.php](file:///C:/get/hospital-system/tests/Feature/NicuCashierPaymentFormTest.php))**:
    - 6 اختبارات ميزة مخصصة تغطي كافة مسارات الواجهة والتسديد والإرجاع والصلاحيات بنجاح 100% (`6 passed, 21 assertions`).

- **المرحلة 180 (2026-11-08) — تفكيك وتفصيل صلاحيات الخدج، تخصيص حجز الاستعلامات، ومنظومة إدارة أنواع وتصنيفات حليب وتغذية الخدج (Granular NICU Permissions Matrix, Reception Baby Admission Segregation & Dynamic NICU Milk/Formula Catalog Suite)**:
  * **تفكيك وتفصيل صلاحيات قسم الخدج والحاضنات (Granular NICU Permissions Matrix)**:
    - تفكيك الصلاحيات العريضة والمدمجة إلى 14 صلاحية دقيقة ومستقلة:
      1. `manage nicu services`: إدارة وتسعير باقات وخدمات وحليب الخدج.
      2. `record nicu vitals`: تسجيل وتوثيق العلامات الحيوية والوزن والرضاعة في الحاضنة.
      3. `order nicu lab tests`: إصدار طلبات الفحوصات المخبرية وتحاليل الخدج.
      4. `order nicu radiology`: إصدار طلبات الفحوصات الشعاعية والسونار للخدج.
      5. `manage nicu prescriptions`: كتابة واعتماد الوصفات والعلاجات الدوائية للطفل.
      6. `administer nicu medications`: توثيق إعطاء وتطبيق العلاجات في محطة التمريض (سجل MAR).
      7. `manage nicu consultations`: طلب واعتماد تقارير الاستشارات الطبية للخدج.
      8. `switch nicu services`: تبديل باقات الرعاية والخدمات اليومية للأطفال.
      9. `edit nicu clinical profile`: تعديل وتثبيت بيانات الولادة السريرية وعمر الحمل داخل الإضبارة.
      10. `manage nicu doctor notes`: تدوين وتثبيت ملاحظات وتوجيهات الطبيب الاختصاص.
      11. `manage nicu care plans`: اعتماد وجدولة خطط الرعاية والتغذية ومواعيد الرضاعة.
      12. `discharge incubator reservations`: إصدار أمر الخروج الطبي وتصفية إقامة الطفل.
      13. `view nicu archive`: تصفح أرشيف وسجلات إضبارات الأطفال المخرجين.
      14. `manage nicu baby cam`: توليد وإدارة روابط بث كاميرا الحاضنة للأهل.
    - تحديث [database/seeders/RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php) و [app/Http/Controllers/RoleManagementController.php](file:///C:/get/hospital-system/app/Http/Controllers/RoleManagementController.php) بإدراج وتصنيف هذه الصلاحيات بمسمياتها العربية وشاراتها الرسمية.
  * **حوكمة حجز الاستقبال وعزل الإجراءات السريرية (Reception Booking vs Clinical Segregation)**:
    - دور الاستعلامات (`receptionist`) محصور بإنشاء وإدارة حجز الحاضنة واستقبال الطفل (`create incubator reservations`, `view incubator reservations`, `manage incubator reservations`) وطباعة أسورة المعصم وكارت الحاضنة الشفاف (`print nicu cards`) وتوليد رابط الكاميرا (`manage nicu baby cam`).
    - الاستعلامات محظورة وممنوعة تماماً على صعيد السيرفر (403 Forbidden) عن أي إجراء سريري أو تمريضي (توثيق العلامات، طلب التحاليل، الأشعة، كتابة الوصفات، تعديل الملف السريري، أو إخراج الطفل).
    - توزيع الصلاحيات السريرية لطبيب الخدج (`nicu_doctor`) والتمريضية لممرضة الخدج (`nicu_nurse`) دون استحداث أدوار جديدة والتزاماً بالأدوار الأساسية للمستشفى.
  * **منظومة إدارة أنواع وتصنيفات حليب وتغذية الخدج (NICU Milk/Formula Dynamic Catalog)**:
    - جدول ومايجريشن جديد `nicu_milk_types` ([database/migrations/2026_11_08_102500_create_nicu_milk_types_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_08_102500_create_nicu_milk_types_table.php)) وموديل [app/Models/NicuMilkType.php](file:///C:/get/hospital-system/app/Models/NicuMilkType.php) مع تصنيفات سريرية وترتيب عرض وملاحظات استخدام وحالة تفعيل.
    - متحكم [app/Http/Controllers/NicuMilkTypeController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuMilkTypeController.php) يدعم عمليات CRUD وتبديل التفعيل اللحظي عبر AJAX (`/nicu/milk-types/{id}/toggle`) وجلب القائمة النشطة JSON.
    - واجهة إدارة موحدة بتبويبات في [resources/views/nicu/services/index.blade.php](file:///C:/get/hospital-system/resources/views/nicu/services/index.blade.php) تجمع بين `[ 🏷️ باقات وخدمات الحاضنات والتنفس ]` و `[ 🍼 قائمة وتصنيفات حليب وتغذية الخدج ]` مع بطاقات إحصائيات، جدول متجاوب، ومودالات الإضافة والتعديل.
    - ربط ديناميكي مباشر داخل إضبارة الخديج [resources/views/nicu/show.blade.php](file:///C:/get/hospital-system/resources/views/nicu/show.blade.php) في داتا ليست الرضاعة السريعة (`quickFeedingTypes`) وتوثيق العلامات الحيوية، مع رابط وصول سريع `[ ⚙️ إدارة أنواع الحليب ]` للمخولين.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/NicuGranularPermissionsAndMilkTypesTest.php](file:///C:/get/hospital-system/tests/Feature/NicuGranularPermissionsAndMilkTypesTest.php))**:
    - 5 اختبارات ميزة تغطي حظر الاستعلامات، تمكين أطباء وممرضات الخدج، عمليات أنواع الحليب والتبديل اللحظي، ورندرة الواجهات والتبويبات بنجاح 100% (`5 passed, 38 assertions`).

- **المرحلة 179 (2026-11-08) — قفل إضبارة الخديج عند الخروج للأرشفة والمطالعة فقط، أرشفة سجلات المرضى المخرجين، ونقل عمر الحمل ووزن الطفل لداخل الإضبارة NICU Discharged Baby Chart Lockdown, Medical Archive Segregation & Clinical Birth Profile Relocation**:
  * قفل سجل وإضبارة الطفل الخديج عند خروجه لوضع المطالعة والأرشفة فقط (`Read-Only Mode`) مع بنر تحذيري وحظر 15 تابعاً سريرياً في السيرفر.
  * عزل المرضى المخرجين في تبويب "السجلات وأرشفة المرضى" في القائمة وفي الشريط الجانبي.
  * نقل حقلي عمر الحمل ووزن الطفل من حجز الاستقبال لداخل الإضبارة ببطاقة مقفلة مع حوكمة تأكيد التعديل.
  * حزمة اختبارات شاملة في [tests/Feature/NicuDischargedChartAndClinicalProfileTest.php](file:///C:/get/hospital-system/tests/Feature/NicuDischargedChartAndClinicalProfileTest.php) بنجاح 100% (`5 passed, 37 assertions`).

- **المرحلة 174 (2026-11-07) — حظر فتح المحطة السريرية للمقيم قبل استلام المريض في الغرفة، وتطبيق نظام التنبيهات الطبي الشامل SweetAlert2 (Resident Clinical Station Gate & Global Hospital SweetAlert2 & Toastr Modern Styling System):**
  * **حوكمة محطة المقيم السريرية ([app/Http/Controllers/ResidentStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ResidentStationController.php), [resources/views/surgery-stations/resident/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/resident/index.blade.php))**:
    - قفل زر "عرض وتعديل" في محطة المقيم وتحويله إلى `[ 🔒 بانتظار استلام الغرفة ]` إذا كان المريض مخصصاً لغرفة ولم يسجل ممرض الطابق استلامه الفعلي بعد.
    - إضافة شارة صفراء أنيقة تحت اسم المريض: `[ ⏳ بانتظار استلام ممرض الطابق ]` تتحول تلقائياً إلى خضراء `[ ✔️ تم الاستلام (hh:mm A) ]`.
    - حظر الدخول المباشر بالرابط في `ResidentStationController@show` وإعادة التوجيه مع رسالة تحذيرية واضحة.
  * **محرك التنبيهات الطبي الشامل والتخلص من النوافذ السوداء ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
    - دمج مكتبة SweetAlert2 الفاخرة مع تنسيق طبي مخصص: بطاقة بيضاء ناصعة، خط النظام الطبي، زوايا دائرية `rounded-4`، أيقونات طبية متحركة ونابضة، وأزرار كبسولية عصرية.
    - **Global Alert Interceptor**: اعتراض دالة `window.alert` في المتصفح تلقائياً وتحويل أي تنبيه في كامل شاشات المستشفى إلى SweetAlert2 ملون وأنيق بدلاً من نوافذ المتصفح السوداء الافتراضية.
    - تحديث تنسيقات Toastr لتتحول من الصناديق الداكنة القديمة إلى بطاقات زجاجية ملونة وناعمة بنمط الباستيل الطبي.
    - ربط رسائل الـ Session (النجاح والتحذيرات والخطأ) لتظهر تلقائياً كـ Toasts عائمة أو بطاقات تحذيرية فاخرة.
  * **حزم الاختبارات الآلية الشاملة ([tests/Feature/ResidentStationRoomCheckinGateTest.php](file:///C:/get/hospital-system/tests/Feature/ResidentStationRoomCheckinGateTest.php))**:
    - كتابة واجتياز اختبارات قفل المحطة للمقيم وحظر الرابط بنسبة 100% (2 passed, 11 assertions)، واستمرار اجتياز كافة اختبارات محطة التمريض والضمان (15 passed, 108 assertions).

- **المرحلة 173 (2026-11-07) — حوكمة دخول وتخريج المريض من غرف الطابق في محطة التمريض، ربط محرك الإقامة الفندقية بلحظة الاستلام الفعلي، وحجب ملف المريض قبل الاستلام (Floor Nursing Room Check-in / Checkout Governance, Bed Release, Stay Calculation Synchronization & Check-in Clinical Access Gate):**
  * **تصميم مسار حوكمة إشغال الغرف ومحطة التمريض ([app/Http/Controllers/NursingStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/NursingStationController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
    - إضافة توابع `roomCheckin()` و `roomCheckout()` لتسجيل الدخول الفعلي للغرفة وتخريج المريض مع تدقيق أمني وحفظ هوية الممرض ووقت الإشغال وتحديث حالة الغرفة بين `occupied` و `available` وتسجيل الحركات في `UserActivityLogger`.
    - **بوابة حجب الوصول السريري (Check-in Gate)**: في `NursingStationController@show` و `ClinicalStationController@show`، منع الممرضين من فتح ملف المريض وتعديل بياناته السريرية إذا كانت الحالة مخصصة لغرفة ولم يتم تسجيل دخولها واستلامها بعد، مع توجيه تنبيهي فوري: "يرجى تسجيل دخول المريض للغرفة أولاً قبل الدخول إلى ملف المريض والتعديل السريري".
  * **تطوير واجهة محطة التمريض وتبديل الأزرار ([resources/views/surgery-stations/nursing/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/nursing/index.blade.php), [resources/views/surgery-stations/nursing/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/nursing/show.blade.php), [resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - في تبويبي ما قبل وما بعد الصالة:
      - **قبل تسجيل الدخول**: إخفاء زر "عرض وتعديل" تماماً وعرض زر وحيد أخضر بارز `[ 🛏️ تسجيل دخول الغرفة ]` مع شارة `[ ⏳ بانتظار استلام الغرفة ]`.
      - **بعد تسجيل الدخول**: يختفي زر تسجيل الدخول ويتبدل مكانه إلى زر `[ 📋 عرض وتعديل ]` وبجانبه زر أحمر سريع `[ 🚪 تخريج ]` لإخلاء السرير مع شارة خضراء أنيقة `[ ✔️ تم الدخول (hh:mm A) ]`.
    - إضافة زر التخريج السريع ومعلومات الغرفة وتوقيت الدخول في الترويسة العلوية لملف التمريض والمحطة السريرية.
  * **قاعدة احتساب الإقامة الفندقية الصارمة وتصحيح الغرف العادية ([app/Models/Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php), [app/Models/HealthInsuranceStayTier.php](file:///C:/get/hospital-system/app/Models/HealthInsuranceStayTier.php))**:
    - ربط وقت بدء احتساب الإقامة الفندقية في `calculateStayDetails()` بحقل `room_checkin_at` كأساس أولي بدلاً من تاريخ الجدولة.
    - تجميد الإقامة عند تاريخ التخريج `discharged_at` فور نقر زر التخريج.
    - قفل وتأكيد أن الغرفة العادية (`regular`) لا يترتب عليها أي فرق ترقية إطلاقاً (`upgrade_diff = 0`) حتى لو كان سعرها التجاري أعلى من سعر عقد الضمان.
    - قفل الليالي الإضافية للعمليات المجدولة التي لم تبدأ أو لم تسجل دخولها بعد لتكون 0 ليلة إضافية، مما يجعل حصة المريض للغرفة العادية في العملية #1 تساوي 0 د.ع تماماً ومطابقة للنظام.
  * **حزم الاختبارات الآلية الشاملة ([tests/Feature/NursingRoomCheckinWorkflowTest.php](file:///C:/get/hospital-system/tests/Feature/NursingRoomCheckinWorkflowTest.php))**:
    - كتابة واجتياز 5 اختبارات آلية بنسبة 100% (5 passed, 27 assertions) تغطي: زر تسجيل الدخول، الحجب قبل الدخول، التبديل إلى العرض والتخريج، التخريج الفعلي وإخلاء السرير، ومطابقة محرك احتساب الإقامة مع `room_checkin_at`.
    - اجتياز كافة اختبارات استقطاع الإقامة والخدمات الإضافية للعمليات بإجمالي 21 اختباراً ناجحاً بنسبة 100%.

- **المرحلة 172 (2026-11-07) — نظام استقطاع الإقامة الفندقية المتدرج للضمان الصحي، تسعير الغرف، وفرق الترقية الفعلي (VIP/VVIP) عن كل الليالي، وتفكيك الحسابات في الكاشير (Health Insurance Tiered Stay Deduction Engine, Dynamic Room Tariff Management, Ubiquitous Room Upgrade Surcharge & Cashier Live Nights Breakdown):**
  * **بنية قاعدة البيانات والشرائح الخمس المعتمدة ([database/migrations/2026_11_07_140000_create_health_insurance_stay_tiers_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_07_140000_create_health_insurance_stay_tiers_table.php), [app/Models/HealthInsuranceStayTier.php](file:///C:/get/hospital-system/app/Models/HealthInsuranceStayTier.php))**:
    - إنشاء جدول وموديل `health_insurance_stay_tiers` مع الشرائح الخمس القياسية للضمان:
      1. الليلة الأولى (يوم 1): تحمل 0% (مشمولة بالعملية للغرفة العادية، 0 د.ع مريض و 0 د.ع ضمان).
      2. الليلة الثانية (يوم 2): تحمل 10% (20,000 د.ع مريض، و 180,000 د.ع ضمان).
      3. إقامة اعتيادية (أيام 3 إلى 8): تحمل 25% (50,000 د.ع مريض / يوم، و 150,000 د.ع ضمان / يوم).
      4. إقامة ممتدة (أيام 9 إلى 13 - 5 أيام): تحمل 50% (100,000 د.ع مريض / يوم، و 100,000 د.ع ضمان / يوم).
      5. إقامة طويلة (أيام 14 فأكثر): تحمل 75% (150,000 د.ع مريض / يوم، و 50,000 د.ع ضمان / يوم).
    - حفظ وإدارة السعر اليومي المعتمد للغرفة العادية لدى الضمان (`standard_room_daily_price = 200,000 د.ع`).
    - توفير توابع حسابية ذكية: `getStandardRoomPrice()`, `getTierForDay()`, `getCopayPercentageForDay()`, `calculateNightFigures()`.
  * **إدارة نسب الاستقطاع وأسعار الغرف بتبويب تفاعلي ([app/Http/Controllers/HealthInsuranceCategoryController.php](file:///C:/get/hospital-system/app/Http/Controllers/HealthInsuranceCategoryController.php), [resources/views/health-insurance-categories/index.blade.php](file:///C:/get/hospital-system/resources/views/health-insurance-categories/index.blade.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
    - إضافة تبويب مستقل في واجهة نسب استقطاع الضمان الصحي (`/health-insurance-categories`):
      - تبويب 1: فئات العمليات والخدمات الطبية (أ - ط).
      - تبويب 2: شرائح استقطاع الإقامة والغرف للضمان، مع بطاقات مؤشرات أسعار الغرف الفعلية (عادية: 250k، VIP: 350k، VVIP: 450k) وفروقات الترقية اليومية.
    - إضافة نوافذ منبثقة (Modals) ومسارات لتعديل السعر المعتمد وتعديل نسب وأيام وملاحظات أي شريحة ديناميكياً.
  * **محرك حساب الليالي وفروقات الترقية الصارمة ([app/Models/Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php))**:
    - تطوير دالة `calculateStayDetails()` لتعتمد قاعدة 12:00 ظهراً ونظام الشرائح المتدرجة لمرضى الضمان:
    - **قاعدة فرق الترقية الفعلي عن كل الليالي**: الضمان يغطي حصراً تسعيرة الغرفة العادية (200,000 د.ع). أي ترقية لـ VIP (فرق 150,000 د.ع / يوم) أو VVIP (فرق 250,000 د.ع / يوم) يتحملها المريض بالكامل بنسبة 100% عن كل ليلة دون استثناء (بما فيها الليلة الأولى).
    - توليد مصفوفة تفصيلية `nights_breakdown` لكل ليلة مع تفكيك دقيق لحصة المريض ومطالبة الضمان وفرق الترقية.
  * **تطوير واجهة دفع العمليات ودعم تفكيك الليالي بالكاشير ([resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php), [app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
    - عرض شارة "استقطاع متدرج (ضمان)" في سطر الغرفة بجدول العمليات، وزر منسدل لعرض جدول تفكيك الليالي والاستقطاعات.
    - مزامنة حية وفورية في JavaScript لاحتساب حصة المريض ومطالبة الضمان للغرفة عند الدفع الكامل أو الجزئي.
    - معالجة وإصلاح حصر حالة الغرفة كمدفوعة في الكاشير `processSurgeryPayment` لتطابق حصة المريض الفعلية المطلوبة بدلاً من كامل سعر الغرفة التجاري.
  * **حزم الاختبارات الآلية الشاملة ([tests/Feature/HealthInsuranceStayTiersTest.php](file:///C:/get/hospital-system/tests/Feature/HealthInsuranceStayTiersTest.php))**:
    - كتابة واجتياز 8 اختبارات آلية بنسبة 100% (8 passed, 70 assertions) واختبارات الخدمات الإضافية السابقة (8 passed, 47 assertions) بإجمالي 16 اختباراً و117 توكيداً ناجحاً.

- **المرحلة 171 (2026-11-07) — توحيد جدول دفع العمليات الجراحية مطابقةً لواجهة الطلبات الطبية وكبسولات الشمول المنفصلة السداسية الأعمدة (Surgery Cashier Payment Table Modern Redesign & Segmented Action Pills Parity):**
  * **الإلحاق التلقائي مع حجز وتحديث العمليات ([app/Models/Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
    - إضافة دالة `attachDefaultExtraServices()` على موديل `Surgery` لربط 3 خدمات إضافية افتراضية تلقائياً لكل عملية جديدة (كاش أو ضمان):
      1. **تخطيط قلب**: 15,000 د.ع (تصنيف طبي `medical`).
      2. **اجور فتح طبلة**: 10,000 د.ع (تصنيف إداري `administrative`).
      3. **خدمات فندقية**: 30,000 د.ع (تصنيف إقامة `accommodation`).
    - منع تكرار الخدمات عند استدعاء الدالة مرات متكررة، وربطها فورياً بالعملية الحالية #4 (منير سمير يوسف).
  * **تعديل السعر المباشر للخدمات الإضافية ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
    - إضافة مسار وتابع `CashierController::updateSurgeryExtraService` عبر `PATCH /surgeries/{surgery}/extra-services/{extraService}` لتعديل سعر وكمية الخدمة بالدينار العراقي فورياً عبر AJAX.
    - دعم قراءة مصفوفة `extra_service_prices` في معالجة دفع العملية `processSurgeryPayment` لحفظ وتحديث السعر المخصص الذي يكتبه الكاشير في قاعدة البيانات مباشرة واحتساب الحصص والوصولات بدقة.
  * **تطوير واجهة دفع العمليات في الكاشير ([resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
    - **إلغاء زر الشمول الكلي ("الغاء زر شمول الكلي")**: إزالة كارت ومفتاح `#inclusive` العام لتمكين التحكم الفردي النقي والمستقل لكل خدمة دون أي تعارض.
    - **حقل السعر القابل للتعديل**: تزويد كل خدمة إضافية بحقل إدخال سعر مخصص `extra-service-price-input` بزيادات دينار ومحاذاة ممتازة، يعيد حساب حصة المريض ومطالبة الضمان والإجمالي لحظياً بمجرد الكتابة.
    - **زر الشمول الفردي لكل خدمة**: تفعيل مفتاح الشمول `package-inclusion-switch` المستقل لكل خدمة مع شارات واضحة `[ ✔️ مشمول بالعملية (0 د.ع) ]` أو `[ ✕ غير مشمول (منفصل) ]`.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/SurgeryExtraServicesTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryExtraServicesTest.php))**:
    - اجتياز كامل اختبارات الإلحاق التلقائي وتعديل السعر والدفع بالأسعار المخصصة بنسبة 100% (6 passed, 27 assertions)، واجتياز اختبارات دفع وتجزئة العمليات ولوحة الصالات (21 passed, 107 assertions).

- **المرحلة 167 (2026-11-05) — حوكمة صلاحيات الأجهزة الطبية وإصلاح انتقال عمليات الصالة المكتملة للأرشيف وإعادة تصميم جدول العمليات بدقة الأدوار (Surgery Master Registry Roles Governance, OT Exit Status Auto-Sync & Medical Devices Route Protection):**
  * **إصلاح انتقال العمليات المكتملة للأرشيف (`SurgeryController@index`, `OperationTheaterStationController@complete`)**:
    - تحديث `OperationTheaterStationController::complete()` ليقوم بتحديث حالة العملية إلى `status = 'completed'` وتعيين `end_time = now()` فور انتهاء الصالة وخروج المريض.
    - تحديث استعلام العمليات النشطة في `SurgeryController@index` لاستبعاد أي حالة اكتملت صالتها تلقائياً.
    - تحديث استعلام ومؤشرات العمليات المكتملة لتضم كل عملية أتمت الصالة أو اكتملت.
    - مزامنة السجلات السابقة في قاعدة البيانات لتتحول الحالات التي خرجت من الصالة إلى `completed` وتنتقل فوراً لتبويب المكتملة.
  * **حوكمة دقيقة للإجراءات والأعمدة بالأدوار (`surgeries/index.blade.php`)**:
    - إخفاء عمود وفحوصات التشخيص والعلاج تماماً عن موظفي الاستقبال والاستعلامات (`@if(!auth()->user()->hasRole(['receptionist', 'inquiry_staff']))`).
    - حصر أزرار الإجراءات في الجدول بالأدوار: زر التعديل بـ `@can('edit surgeries')`، زر الحذف بـ `@can('delete surgeries')`، والطباعة بـ `surgeries.print`.
    - حصر زر إلغاء العملية داخل صف التفاصيل بكادر العمليات والإدارة وطبيب الحالة (`$canManageSurgery`) وفقط للعمليات النشطة الجارية.
    - المحافظة على صف تفاصيل الفحوصات المنسدل (`detailsRow`) للأشعة والمختبر والعلاجات بتصميم كروت وألوان متناسقة وعصرية.
    - تصميم عصري فخم بالكامل (ترويسة تنفيذية، بطاقات مؤشرات KPI دقيقة، وشريط بحث وتصفية ذكي).
  * **حزم الاختبارات الآلية الشاملة**:
    - اجتياز اختبارات مصفوفة التبويبات والمحطة السريرية `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` بنسبة 100% (11 passed, 44 assertions).
    - اجتياز اختبارات لوحة الصالات وقائمة الانتظار `tests/Feature/SurgeryWaitingListFlightBoardTest.php` بنسبة 100% (8 passed, 28 assertions).

- **المرحلة 166 (2026-11-05) — إعادة التصميم العصرية لشاشة قائمة انتظار وتدفق العمليات الجراحية كلوحة تحكم ذكية وبطاقات مؤشرات تفاعلية (Smart OR Flight Board Modern Redesign, Interactive KPI Filters & Clean Stage Progression):**
  * **مزامنة محطة المقيم ما بعد العملية (`ResidentStationController@index`)**:
    - تصحيح استعلام مرحلة ما بعد الصالة للمقيم (`postOpSurgeries`) ليتطابق مع التمريض، بحيث ينتقل المريض للمقيم فور اكتمال صالة العمليات (`operationTheaterStation->status === 'completed'`) أو اكتمال التخدير دون اشتراط التخدير حصراً.
    - استبعاد الحالات التي أتمت الصالة من جدول ما قبل الصالة لدى المقيم لضمان نظافة الطابور.
  * **ترشيق شاشة قائمة الانتظار (`surgeries/waiting.blade.php`)**:
    - **اختفاء زر "إنهاء" فور اكتمال الصالة**: فحص `$isOtCompleted` لإخفاء زر إنهاء الصالة الأحمر نهائياً ومنع التكرار اللانهائي، وإظهار شارة خضراء مميزة `[ ✔️ تمت الصالة ]`.
    - **تحديث شارة الحالة**: تحول الشارة فوراً من `داخل الصالة (جارية)` إلى `[ 🏁 خرج من الصالة (متابعة) ]` وإيقاف عداد وقت الصالة.
    - **تنظيف عمود الإجراءات**: حذف الأزرار والأيقونات الزائدة وغير الضرورية، والاكتفاء بالزر السريري المباشر `[ 🩺 المحطة السريرية ]` وأزرار المسار الفعلي.
  * **حزم الاختبارات الآلية الشاملة**:
    - إنشاء واجتياز اختبار الميزة `tests/Feature/ResidentStationPostOpTest.php` بنجاح 100% (1 passed, 3 assertions).
    - اجتياز اختبارات شاشة قائمة الانتظار `SurgeryWaitingListFlightBoardTest` بنسبة 100% (8 passed, 28 assertions).

- **المرحلة 164 (2026-11-05) — منظومة صرف التمريض المباشر من عهدة مخزن الطابق والربط التلقائي بغرفة المريض وخصم الرصيد اللحظي (Nursing Floor Sub-Store Direct Patient Dispensing, Auto Floor-Store Inference & Instant Stock Deduction):**
  * **الاستنتاج التلقائي لمخزن عهدة الطابق (`Surgery::getFloorSubStoreLocation`)**:
    - قراءة طابق غرفة المريض تلقائياً وربطه بالمخزن الفرعي المطابق له (مثلاً: غرفة 305 ⬅️ الطابق الثالث ⬅️ ردهة الطابق الثالث #24) دون الحاجة لتدخل يدوي من التمريض مما يمنع نهائياً أخطاء اختيار المخازن.
  * **مايجريشن وتوسيع قاعدة البيانات (`2026_11_05_000001_add_floor_dispense_fields_to_tables.php`)**:
    - ربط حركات المخزون `stock_movements` بحقل `surgery_id` وحقل `notes`.
    - ربط علاجات المريض السريرية `surgery_treatments` بحقول الوجبة `batch_id`، وحركة المخزون `stock_movement_id`، والكمية `quantity`.
  * **محرك الصرف والخصم في الكنترولر (`ClinicalStationController@dispenseFloorItem`)**:
    - قفل الوجبة مخزنياً `lockForUpdate` والتحقق من تبعيتها لعهدة طابق المريض ورصيدها الكافي.
    - الخصم الفعلي للرصيد فوراً `StockBatch->decrement('current_qty', $qty)`.
    - توثيق حركة `StockMovement` بنوع `issue_to_patient` باسم الممرض المباشر.
    - إضافة العلاج كمنفذ `administered` في طبلة المريض السريرية بتفاصيل الوجبة والكمية والوقت.
  * **الواجهة التفاعلية لطبلة المريض (`clinical/show.blade.php`)**:
    - شارة بارزة لعهدة الطابق المعتمدة لغرفة المريض في ترويسة تبويب الأدوية.
    - زر ونافذة منبثقة (Modal) لصرف الأدوية والمستلزمات مع عرض الرصيد المتاح وتاريخ الصلاحية وتحديث الحد الأقصى للكمية لحظياً.
    - شارة توثيق العهدة والوجبة بجانب كل مادة مصروفة في جدول العلاجات.
  * **حزمة الاختبارات الآلية الشاملة (`tests/Feature/NursingFloorDispenseTest.php`)**:
    - 4 اختبارات ميزة تغطي كافة حالات الصرف والاستنتاج ورفض تجاوز الرصيد أو السحب من طابق خاطئ بنجاح 100% (27 assertions).
    - استمرار اجتياز كافة اختبارات المحطة السريرية `UnifiedClinicalStationAndTabPermissionsTest` (9 passed, 39 assertions).

- **المرحلة 163 (2026-11-05) — دمج وتفعيل إضافة Understand Anything وتوليد شبكة المعرفة والعلاقات التفاعلية للمشروع (Understand Anything Knowledge Graph & Interactive Dashboard Integration):**
  * **تجهيز وبناء حزمة الإضافة (`Understand Anything`)**:
    - تثبيت بيئة العمل ومكتبات التحليل الـ AST اللغوية (`tree-sitter-php`, `tree-sitter-javascript`, `graphology`, `elkjs`).
    - بناء الحزمة الأساسية `@understand-anything/core` بنجاح وتجهيز بيئة الـ Dashboard المبنية على Vite 6 و React 19 و @xyflow/react.
  * **توليد شبكة المعرفة والعلاقات التفاعلية (`.ua/knowledge-graph.json`)**:
    - إنشاء محرك التوليد الآلي المستدام `scripts/build_ua_graph.mjs` لفحص الـ Models, Controllers, Services, والـ Routes.
    - استخراج **221 مكوناً وعقدة (Nodes)** و **983 علاقة وارتباط حقيقي (Edges)** تشمل استدعاءات الـ Controllers، علاقات الـ Eloquent (`hasMany`, `belongsTo`, `belongsToMany`)، والمسارات الحية.
    - تقسيم النظام إلى **7 طبقات معمارية (Layers)** واضحة باللغة العربية (الاستشارية، الكاشير والخزينة، المحطات التشخيصية، الرقود والعمليات، الحاضنات والخدج، المخازن والصيدلية، والبنية التحتية).
    - إضافة **جولة تعليمية إرشادية بـ 5 مسارات سريرية ومالية (Guided Tour)** تشرح مسارات المريض والتحصيل والعمليات خطوة بخطوة بالرسم التفاعلي.
  * **تشغيل الـ Dashboard التفاعلي المحلي**:
    - تشغيل خادم الـ Dashboard المحلي عبر Vite والتوثيق الآمن بـ Access Token وربطه مع المتصفح.

- **المرحلة 163 (2026-11-04) — استبيان الرقود: الورقة الثانية لخدمات التمريض والمختبر والصيدلية، إفراغ الإيميل في الطباعة، واللوحة المدمجة للنتائج بـ 27 معياراً (Inpatient Survey Page 2 Clinical Services, Blank Print Email & Compact 2-Column Results Dashboard):**
  * **الورقة الثانية للاستبيان (16 معياراً سريرياً جديداً)**:
    - مايجريشن قاعدة البيانات `2026_11_04_000002_add_page2_services_to_inpatient_room_surveys_table.php` بإضافة معايير طاقم التمريض (6)، قسم المختبر والتحاليل (5)، قسم الصيدلية (5)، وملاحظات كل قسم، وبيانات المريض والمرافق (الاسم، التوقيع، الهاتف، البريد).
    - نموذج `InpatientRoomSurvey` والكنترولر: احتساب تلقائي للمعدلات الفرعية لكل قسم (`room_score`, `nursing_score`, `lab_score`, `pharmacy_score`) والتقييم الإجمالي (`overall_score`).
  * **إفراغ الإيميل في الطباعة والاستبيان**:
    - تفريغ حقل الإيميل بالكامل في نموذج الطباعة A4 المعتمد `print-a4.blade.php` ليكون سطراً منقطاً فارغاً ومطابقاً للنموذج الورقي الرسمي لمستشفى الكفاءات.
    - عدم استيراد إيميل المستخدم من جدول `users` وتفريغ القيمة الافتراضية في واجهة الموبايل العامة.
  * **اللوحة المدمجة للنتائج (Compact 2-Column Dashboard)**:
    - إعادة تصميم واجهة النتائج المكتملة في المحطة السريرية `clinical/show.blade.php` إلى عمودين متوازنين أنيقين يختصران المساحة الرأسية بنسبة 70% وتلغي السكرول المفرط.
    - عرض كافة المعايير الـ 27 بأيقونات ملونة، نجوم مصغرة، وشارات تقييم ناصعة وموجزة.
  * **حزمة الاختبارات الآلية والرفع المباشر**:
    - اجتياز اختبارات استبيان الرقود بنسبة 100%: `tests/Feature/InpatientRoomSurveyTest.php` (10 passed, 74 assertions).
    - اجتياز اختبارات المحطة السريرية بنسبة 100%: `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` (9 passed, 39 assertions).
    - رفع التحديثات مباشرة إلى الفرع الرئيسي `main` عبر `git push origin main`.

- **المرحلة 162 (2026-11-04) — الربط التلقائي لتصفيات الضمان الصحي للخدج واحتساب فرق مطالبة المستشفى ومصفوفة عمر الحمل بالخزينة والحسابات (NICU Insurance Automatic Settlement, Hospital Claim Margin & Gestational Matrix Treasury Integration):**
  * **محرك الفوترة الحية والاحتساب التلقائي (`NicuBillingService`)**:
    - ترقية عداد الأيام التراكمية في السجل المفتوح (`activeOpenDays`) ليتم احتسابه حياً وتلقائياً بحسب تاريخ الدخول والتوقيت الفندقي وتحديث قاعدة البيانات دون الحاجة لتدخل يدوي.
    - احتساب مصفوفة عمر الحمل الوزارية بدقة ($\ge 34$, 32-34, 28-32, $<28$ أسبوعاً) مع تحديد حصة المريض وحصة الضمان لكل يوم إقامة.
    - تحديث وحفظ `patient_share_amount` و `insurance_share_amount` تلقائياً على نموذج `IncubatorReservation` ليمثل الفرق الصافي الذي تطلبه المستشفى من هيئة الضمان.
  * **خزينة المستشفى المركزية (`HospitalTreasuryController`)**:
    - إدراج وتوحيد مطالبات وتصفيات الخدج والحاضنات (`insurance_share_amount`) مباشرة داخل كارت "مطالبات الضمان الصحي للفترة" التراكمي في واجهة الخزينة كذمم مدينة للمستشفى.
  * **كشوفات حسابات الخدج لدى الكاشير (`cashier/nicu-statements.blade.php` و `CashierController`)**:
    - إضافة فلتر نوع التغطية المالية (الكل / ضمان فقط / نقدي فقط).
    - إضافة كارت إحصائي بارز: `[ 🛡️ مطالبات الضمان للخدج (تطلبها المستشفى) ]`.
    - ترقية جدول الكشوفات لإبراز: عمر الحمل بالأسابيع، إجمالي الفاتورة الكاملة، مطالبة الضمان، حصة المريض، المسدد نقداً، وموقف المريض المالي.
  * **لوحة تحليلات محاسب الخدج (`AccountantController` و `accountant/nicu/analytics.blade.php`)**:
    - إضافة كارت إحصائي لمطالبات الضمان للخدج خلال الفترة المحددة.
    - إضافة جدول تحليلي وتفصيلي لمطالبات الضمان مصنفة وفق مصفوفة عمر الحمل الوزارية.
  * **طبلة الخديج السريرية (`nicu/show.blade.php`)**:
    - إبراز مربع مالي علوي لمطالبة الضمان في ترويسة الطفل بجانب عداد الساعات والأيام وحصة المريض.
  * **حزمة الاختبارات الآلية الشاملة**:
    - إنشاء واجتياز اختبار الميزة `tests/Feature/NicuInsuranceSettlementAndClaimsTest.php` بنسبة 100% (4 passed, 18 assertions).
    - استمرار اجتياز كافة اختبارات حسابات وتدفقات الخدج السابقة: `tests/Feature/NicuAccountingTest.php` (4 passed, 21 assertions)، `tests/Feature/NicuLabRadiologyCashierWorkflowTest.php` (4 passed, 82 assertions)، و `tests/Feature/NicuWorkflowTest.php` (39 passed, 341 assertions).

- **المرحلة 161 (2026-11-04) — حل ظهور شعار المستشفى محلياً وعلى هوستنجر بتضمين Base64 الآمن لصفحات الاستبيان والطباعة (Resilient Base64 Hospital Logo Embedding for Surveys & Print):**
  * **تشخيص المشكلة**: دالة `asset('images/1.jpg')` تولد رابطاً مطلقاً `http://localhost/images/1.jpg` بينما السيرفر المحلي WAMP يخدم المشروع من مسار فرعي `http://localhost/hospital-system/public/` مما يسبب خطأ 404 واختفاء الصورة محلياً، في حين أنها كانت تعمل على هوستنجر لأن الدومين موجه مباشرة لمجلد `public`.
  * **الحل الجذري والآمن**:
    - قراءة ملف الشعار مباشرة من مسار النظام الفيزيائي `public_path('images/1.jpg')` وتشفيره بنظام Base64 Data URI (`data:image/jpeg;base64,...`) مع دعم التبديل التلقائي إلى `logo.png`.
    - تطبيق التضمين الفوري في:
      1. تقرير استبيان الرقود A4 (`print-a4.blade.php`) في الشعار الرئيسي والعلامة المائية الخلفية.
      2. كرت استبيان الرقود الحراري 80mm (`print-qr.blade.php`) بترويسة المستشفى.
      3. واجهة استبيان الموبايل العامة للمريض (`inpatient-public.blade.php`) في الترويسة الرئيسية.
  * **النتيجة**: الشعار يظهر الآن بشكل دائم وفوري على كلا البيئتين (السيرفر المحلي WAMP وسيرفر هوستنجر السحابي) وفي الطباعة وحفظ الـ PDF بدون اعتماد على مسار الدومين أو اتصال الإنترنت.
  * **حزم الاختبارات الآلية**: اجتياز اختبارات استبيان الرقود والمحطة السريرية بنسبة 100%.

- **المرحلة 160 (2026-11-04) — نمط العرض الثنائي لاستبيان الرقود: باركود عملاق في حالة الانتظار ولوحة نتائج شاملة لجميع المفردات عند الاكتمال مع الإخفاء التام للباركود (Inpatient Survey 2-State Layout: Grand Centered QR on Pending & Full-Width 11-Item Scorecard on Completion with Total QR Elimination):**
  * **إلغاء نموذج الإدخال اليدوي نهائياً من المحطة السريرية**:
    - حذف جدول الإدخال اليدوي للممرض بالكامل لتبسيط الشاشة والاعتماد 100% على تقييم المريض ومرافقه الذاتي عبر الموبايل بخصوصية وأمان تامين.
  * **الحالة الأولى (بانتظار التقييم - Pending)**:
    - عرض باركود عملاق عالي الدقة (340x340) في منتصف الشاشة بعرض بطاقة مخصصة فخمة مع رقم الغرفة واسم المريض.
    - شريط علوي أنيق يحتوي على أزرار الإجراءات: طباعة كرت حراري للمريض (80mm)، طباعة تقرير A4، استبيان فارغ A4، تكبير بملء الشاشة، وزر تحديث فوري.
    - زر مباشر لفتح رابط التقييم وزر نسخ الرابط مع تأكيد النسخ الفوري.
  * **الحالة الثانية (عند اكتمال التقييم - Completed)**:
    - **إخفاء الباركود ونافذة تكبيره كلياً من الصفحة**، واستبدالها بلوحة نتائج تفصيلية شاملة بكامل عرض الشاشة.
    - **شريط تنفيذي علوي ناصع**: نسبة التقييم الإجمالية بلون ذكي ودائرة تقييم بارزة، وسيلة التقديم، مؤشر توصية المرضى (NPS / 10)، توقيت الإرسال، وزر إعادة فتح وتصفير الاستبيان للمدير (Admin Reset).
    - **شبكة النتائج الشاملة للمفردات الـ 11**: عرض النظافة العامة، نظافة الحمام، التكييف، الإضاءة، الهدوء، راحة السرير، لوحة التحكم، مساحات التخزين، جودة الأثاث، الترفيه، وتواصل الطبيب المعالج مع النجوم وشارات التقييم وأشرطة التقدم الملونة.
    - **بطاقات أولويات التحسين وملاحظات المريض النصية**: عرض أهم 3 أولويات تحسين حددها المريض ومقترحاته وملاحظاته على الغرفة والطبيب.
  * **حزم الاختبارات الآلية الشاملة**:
    - اجتياز اختبارات استبيان الرقود بنسبة 100%: `tests/Feature/InpatientRoomSurveyTest.php` (10 passed, 65 assertions).
    - اجتياز اختبارات المحطة السريرية الموحدة بنسبة 100%: `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` (9 passed, 39 assertions).

- **المرحلة 159 (2026-11-04) — ترويسة إكلينيكية تنفيذية فائقة العصرية بنظام الأعمدة والتبديل الزجاجي (Ultra-Modern Hospital EHR Executive Header with Data Pillars & Glass Segmented Control):**
  * **ترقية معمارية الترويسة إلى مستوى المستشفيات العالمية (Hospital EHR Standard)**:
    - بطاقة بيضاء ناصعة وفخمة مع شريط إكلينيكي علوي ناعم (3px Gradient) وظل محيطي خفيف (`0 4px 20px -4px rgba(15, 23, 42, 0.05)`).
    - أفاتار طبي متدرج (`.avatar-modern-box`) بأبعاد 48x48px وظل أزرق ملكي خفيف.
    - اسم المريض كحلي داكن ناصع (`#0f172a` 1.22rem) مع شارة كود الملف الطبية (`#P-ID`).
  * **التقسيم المعماري بالأعمدة الذكية (Data Pillars)**:
    - **عمود 1 (هوية المريض):** الأفاتار، الاسم، رقم الملف، العمر بشارة ناعمة، والجنس.
    - **فاصل رأسي ناعم (Divider):** يفصل هوية المريض عن بيانات الإجراء.
    - **عمود 2 (العملية والجراح):** مسمى الإجراء الجراحي بخط واضح، واسم الجراح برتبة طبية خضراء (`badge-doctor-chip`).
    - **فاصل رأسي ناعم (Divider):** يفصل الإجراء عن الموقع والجدولة.
    - **عمود 3 (الموقع والجدولة):** رقم الغرفة/الصالة بشارة ذهبية (`badge-room-chip`) وساعة الموعد بشارة رمادية (`badge-time-chip`).
  * **أزرار المرحلة بنظام Segmented Toggle (Apple iPadOS Style)**:
    - شريط كبسولي عائم ناعم (`.clin-segmented-control`) للتبديل الفوري بين (ما قبل العملية / ما بعد العملية) بزر أبيض عائم ذي ظل ناعم للمرحلة النشطة.
    - شارة حالة المرحلة مع نقطة نبض ملونة (`.status-pulse-dot`) تنبض بلون الحالة (مكتملة / قيد المتابعة / بالانتظار).
  * **حزم الاختبارات الآلية الشاملة**:
    - اجتياز اختبارات المحطة السريرية بنسبة 100%: `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` (9 passed, 39 assertions).
    - اجتياز اختبارات استبيان الرقود بنسبة 100%: `tests/Feature/InpatientRoomSurveyTest.php` (8 passed, 48 assertions).

- **المرحلة 158 (2026-11-04) — ترويسة سريرية بيضاء ناصعة وتبويبات تابلت رشيقة وتحصين زر إعادة فتح الاستبيان للأدمن على هوستنجر (Clean White Header, Tablet Auto-Fit Tabs & Resilient Hostinger Admin Survey Reset):**
  * **ترويسة طبية بيضاء عالية التباين (Clean Medical White Header)**:
    - إلغاء الترويسة السوداء/الكحلية بالكامل واستبدالها ببطاقة بيضاء ناصعة (`#ffffff`) مريحة للعين مع خط كحلي غامق واضح جداً لاسم المريض (`#0f172a`).
    - رقمنة بيانات العملية والجراح والصالة والتوقيت في رقائق معلومات (`.clin-info-chip`) واضحة بنسبة 100% بأيقونات ملونة.
    - تحويل أزرار تحديد المرحلة (Pre-Op / Post-Op) وشارات الحالة إلى وضعية طبية فاتحة وناعمة.
  * **تبويبات تابلت أفقية رشيقة بدون سكرول (Responsive Tablet Grid Tabs)**:
    - اختصار مسميات التبويبات الـ 9 إلى كلمات سريرية ذكية (السيرة، العلامات، الخطة، المتابعة، الأدوية، السوائل، التمريض، الاستبيان ⭐، الفحوصات) مع توفير تلميح كامل (Tooltip) عند التمرير.
    - توزيع مرن ومتناسق (`flex: 1 1 0px`) يتسع بالكامل على شاشات التابلت واللابتوب بعرض 100% دون أن يختفي أي تبويب ودون الحاجة لأي سكرول أفقي.
  * **تحصين زر إعادة فتح الاستبيان للمدير على هوستنجر**:
    - توسيع فحص صلاحية الأدمن الشامل `$canResetSurvey` في العرض والكنترولر ليدعم جميع أدوار Spatie (`admin`, `super_admin`, `super-admin`, `hospital_admin`, `مدير النظام`)، وحقل جدول المستخدمين المباشر `role === 'admin'`، وصلاحيات الإدارة (`manage settings`, `manage surgery waiting list`).
    - إبراز الزر في مكانين: في شريط الاستبيان العلوي بجوار أزرار الطباعة، وداخل بطاقة نتائج التقييم المكتملة.
  * **حزم الاختبارات الآلية الشاملة**:
    - اجتياز اختبارات المحطة السريرية بنسبة 100%: `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` (9 passed, 39 assertions).
    - اجتياز اختبارات استبيان الرقود بنسبة 100%: `tests/Feature/InpatientRoomSurveyTest.php` (8 passed, 48 assertions).

- **المرحلة 157 (2026-11-04) — استعادة التصميم الأصلي للمحطة السريرية بالتبويبات الأفقية العلوية الكاملة (Clinical Station Original Horizontal Tabs Full Restoration):**
  * **استعادة كاملة لواجهة المحطة السريرية الأصلية**:
    - استعادة التبويبات الأفقية العلوية (`.clin-tabs-wrapper`) بكامل عرض الشاشة (`100%`) مباشرة تحت ترويسة المريض.
    - إلغاء التخطيط المقسم رأسياً واستعادة مساحة العمل الكاملة لجميع أقسام طبلة المريض (السيرة المرضية، العلامات الحيوية، الفحوصات، الأدوية، السوائل، والمتابعة).
    - الحفاظ الكامل على ميزات استبيان الرقود (`inpatient_survey`) والـ QR والطباعة، ومسارات وإمكانيات الكادر.
  * **حزم الاختبارات الآلية الشاملة**:
    - اجتياز اختبارات المحطة السريرية والصلاحيات بنسبة 100%: `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` (9 passed, 39 assertions).
    - اجتياز اختبارات استبيان الرقود بنسبة 100%: `tests/Feature/InpatientRoomSurveyTest.php` (8 passed, 48 assertions).

- **المرحلة 156 (2026-11-04) — منظومة استبيان وتقييم غرف الرقود والأطباء وتكامل طبلة المريض ورمز الـ QR الحراري (Inpatient Room & Doctor Evaluation Survey with Patient QR & Unified Clinical Chart Integration):**
  * **تطبيق مواصفات Spec-Kit وترسانة ECC**:
    - صياغة وثيقة المواصفات الكاملة `specs/002-inpatient-room-survey/spec.md`.
    - صياغة الخطة المعمارية والتقنية `specs/002-inpatient-room-survey/plan.md`.
    - صياغة وتتبع قائمة المهام الذرية `specs/002-inpatient-room-survey/tasks.md`.
  * **قاعدة البيانات ونموذج الاستبيان (`InpatientRoomSurvey` & Migration)**:
    - إنشاء جدول `inpatient_room_surveys` برمز فريد `uuid`، وعلاقات مع العملية والمريض والغرفة والطبيب والمستخدم المدخل.
    - تقييم 10 معايير فندقية وخدمية خماسية (النظافة العامة، نظافة الحمام، التكييف/التدفئة، الإضاءة، الهدوء والخصوصية، راحة السرير، لوحة التحكم، مساحة التخزين، سلامة الأثاث، والترفيه والإنترنت).
    - تقييم زيارة وتواصل الطبيب المعالج خماسياً، والأسئلة المفتوحة (أهم 3 أولويات تحسين، مقترحات إضافية)، ومقياس التوصية بالمستشفى (NPS 1-10).
    - احتساب النسبة المئوية العامة تلقائياً `calculateOverallScore()` مع قفل الاستبيان بعد الإرسال لمنع التكرار.
  * **المسارات والكنترولر المتكامل (`InpatientSurveyController` & `routes/web.php`)**:
    - مسارات عامة للمريض/المرافق عبر الموبايل بدون تسجيل دخول: `/rate/{uuid}`، `/rate/{uuid}` (POST)، و `/rate/{uuid}/thank-you`.
    - مسارات خاصة بالكادر السريري ومحطة التمريض: `/clinical/{surgery}/inpatient-survey` (إدخال يدوي مباشر) و `/clinical/{surgery}/inpatient-survey/print-qr` (طباعة إيصال حراري 80mm).
  * **الواجهات التفاعلية وتكامل طبلة المريض ومحطة التمريض**:
    - واجهة موبايل عربية خفيفة وأنيقة لمسح الـ QR والتعبئة بلمسة واحدة (`resources/views/surveys/inpatient-public.blade.php`).
    - شاشة الشكر والامتنان التفاعلية (`resources/views/surveys/thank-you.blade.php`).
    - قالب طباعة إيصال الـ QR الحراري 80mm مع ترويسة المستشفى وباركود الاستبيان السريع (`resources/views/surveys/print-qr.blade.php`).
    - دمج تبويب `[ ⭐ استبيان تقييم الرقود ]` في طبلة المريض السريرية ومحطة التمريض عبر `SurgeryTabPermission` مع بطاقة رمز الـ QR، وزر التكبير بملء الشاشة للمسح من بعد، ونموذج الإدخال اليدوي، وعرض النتائج المعتمدة.
  * **حزمة الاختبارات الآلية الشاملة (`tests/Feature/InpatientRoomSurveyTest.php`)**:
    - 5 اختبارات ميزة تغطي دورة الاستبيان كاملة بنجاح 100% (26 assertions): فتح الرابط بالـ UUID، إرسال تقييم الموبايل واحتساب النتيجة، قفل الاستبيان المكتمل وعرض شاشة الشكر، الإدخال اليدوي للتمريض، وطباعة كرت الـ QR الحراري.

- **المرحلة 155 (2026-11-03) — رادار وبحث عهدة الطابق ودورة إرجاع المواد الفائضة للمخزن الرئيسي وفق معمارية Spec-Kit وترسانة ECC (Floor Sub-Store Stock Radar & Excess Item Return Workflow):**
  * **تطبيق منهجية Spec-Driven Development (SDD)**:
    - صياغة وثيقة المواصفات الرسمية `specs/001-floor-store-and-returns/spec.md`.
    - صياغة الخطة التقنية والمعمارية `specs/001-floor-store-and-returns/plan.md`.
    - صياغة قائمة المهام الذرية وتتبع إنجازها `specs/001-floor-store-and-returns/tasks.md`.
  * **رادار عهدة الطابق اللحظي التفاعلي بمحطة التمريض (`resources/views/surgery-stations/nursing/index.blade.php`)**:
    - بناء شريط بحث فوري فائق السرعة بدون ريلود بالاسم والباركود والوجبة داخل تبويب عهدة الطابق (`#floor-store`).
    - توفير شارات تصفية سريعة (`Pill Filters`): `[الكل]`، `[💊 أدوية]`، `[🩹 مستلزمات]`، `[⚠️ نفاد]`، `[⏳ قريبة الانتهاء]`.
    - إضافة زر إجرائي مباشر أمام كل وجبة: `[ ↩️ إرجاع ]` لتعبئة نموذج الإرجاع تلقائياً بالوجبة والمخزن.
    - إضافة زر رئيسي في ترويسة التبويب لطلب إرجاع عهدة مواد للمخزن الرئيسي.
  * **منظومة إرجاع المخزون الذكية والمحصنة (`resources/views/stock-transfers/return.blade.php`, `app/Http/Controllers/StockTransferController.php`)**:
    - قفل المخزن المصدر على عهدة طابق الممرض تلقائياً ومنع التلاعب به.
    - حصر قائمة المواد المنسدلة على وجبات ورصيد المخزن الفرعي المتوفرة فعلياً (`current_qty > 0`).
    - دعم إضافة أسطر مواد متعددة ديناميكياً وتوثيق الأسباب المعتمدة (`فائض`, `إلغاء عملية`, `قرب انتهاء`, `تالف`).
    - حماية الكميات والتحقق الصارم في المتصفح والخادم لمنع طلب كميات تتجاوز رصيد الطابق.
    - تصحيح وتأكيد توجيهات العودة إلى `nursing-station.index`.
  * **دورة الاعتماد بالمخزن الرئيسي**:
    - إبراز شارة `[ ↩️ إرجاع عهدة ]` في سجل الطلبات وسجل المعاينة مع توثيق الأسباب والملاحظات.
    - خصم الكميات من الطابق بدقة وإضافتها للمستودع المركزي وتوثيق حركة `StockMovement` بنوع `transfer`.
  * **حزم الاختبارات الآلية الشاملة**:
    - إنشاء واجتياز اختبار الميزة المتكامل `tests/Feature/FloorStockReturnWorkflowTest.php` بنسبة 100% (5 passed, 29 assertions).
    - استمرار اجتياز كافة اختبارات عهدة التمريض السابقة `tests/Feature/FloorCustodianStockTransferTest.php` (11 passed, 52 assertions).

- **المرحلة 154 (2026-11-03) — تصحيح توافقية مسار صيانة الحاضنات ودعم PATCH و POST وتحصين قسم الخدج (Incubator Maintenance Route Method Alignment & NICU Shielding):**
  * **معالجة خطأ 405 MethodNotAllowedHttpException في صيانة الحاضنات**:
    - ترقية مسار `incubators/{incubator}/toggle-maintenance` في `routes/web.php` ليدعم كلتا الطريقتين `POST` و `PATCH` عبر `Route::match(['post', 'patch'], ...)` لتتوافق مباشرة مع نموذج الصيانة المرسل بـ `@method('PATCH')` في `resources/views/incubators/show.blade.php`.
  * **تحصين كنترولر الخدج (`app/Http/Controllers/NicuController.php`)**:
    - استيراد واجهة `Illuminate\Support\Facades\DB` المفقودة لضمان عمل استعلامات مطابقة أدوية التمريض (MAR) بدون أي أخطاء.
  * **حزم الاختبارات الآلية**:
    - إنشاء واجتياز اختبار الميزة `tests/Feature/IncubatorMaintenanceTest.php` بنجاح 100% (3/3 passed, 9 assertions).
    - اجتياز اختبار إعطاء أدوية التمريض بالخدج بنجاح 100% في `tests/Feature/NicuWorkflowTest.php`.

- **المرحلة 153 (2026-11-03) — دمج منظومات الموارد البشرية والرواتب (HR) وقسم الخدج والحاضنات (NICU) ومركز وجراحة العيون (Eye Center) مع فرع main مع تحصين المحطات الأساسية (Complete Secure Integration of HR, NICU & Eye Center into Main with Core Stations Shielding):**
  * **الدمج الآمن لمنظومات فرع `hr` مع الحفاظ على الفرع الأصلي كاملاً**:
    - دمج كامل ميزات الموارد البشرية والرواتب (HR & Payroll: عقود، سلف، إجازات، غيابات، إضافي، وجدولة الشفتات).
    - دمج قسم الخدج والحاضنات بالكامل (NICU & Incubators: الرادار السريري للولادات، مخططات النمو، الأوامر الطبية، وجداول الرعاية والرضاعة، وحسابات وإيرادات الخدج المستقلة).
    - دمج مركز وجراحة العيون بالكامل (Eye Center: استقبال العيون، الفحص السريري OD/OS، كشفية النظارات، العمليات الجراحية، كاشير العيون ومخزن العيون).
    - الحفاظ التام على فرع `hr` محلياً وعلى GitHub (`origin/hr`) دون حذفه ليكون مرجعاً دائماً.
  * **تحصين وتصحيح المحطات الأساسية**:
    - فحص محطة كاشير الإيصالات العامة وإزالة استدعاءات الخدج غير المعرفة لمنع أخطاء 500 في إيصالات العمليات والفحوصات.
    - تصحيح قيود الخزينة المركزية للخدج إلى `hospital_revenue` لحماية التناسق المالي.
    - تصحيح استعلام مطابقة جرعات التمريض (MAR) ليكون متوافقاً ديناميكياً مع كافة محركات قواعد البيانات.
  * **حزم الاختبارات الآلية الشاملة**:
    - اجتياز أكثر من 700 فحص وتحقق آلي بنجاح 100% لكافة محطات النظام: العمليات (4/4)، كاشير العمليات والدفع المركب (7/7)، عهدة التمريض ومخازن الطوابق (11/11)، تحاليل العمليات والمختبر (8/8)، الموارد البشرية (5/5)، حسابات الخدج (4/4)، سير عمل العيون (15/15)، وسير عمل الخدج (39/39).

- **المرحلة 152 (تحديد وتعديل نوع حجز العملية ضمان أم كاش 100% في الحجز والتعديل وإبراز شارات التغطية وربطها التلقائي بالكاشير Surgery Reservation Insurance vs Cash Coverage Mode & Direct Cashier Alignment Flow):**
  * **تحديد وتعديل مسار حجز العملية (ضمان أم نقدي خاص كاش 100%) (`resources/views/surgeries/create.blade.php`, `resources/views/surgeries/edit.blade.php`, `app/Http/Controllers/SurgeryController.php`)**:
    - بناء بطاقة تفاعلية بارزة وموحدة عند حجز أو تعديل أي مريض مؤمن:
      1. `[ 🔘 🛡️ حجز تحت مظلة الضمان الصحي ]`: تعامل العملية كعملية ضمان وتطبق عليها شروط الاستقطاع ونسب الفئة التأمينية ومطالبة الضمان.
      2. `[ 🔘 💵 حجز نقدي خاص (كاش كامل خارج الضمان) ]`: تعامل العملية كعملية نقدية 100% دون استهلاك رصيد المريض التأميني وتفتح للكاشير وضعية الكاش والخصم المالي.
    - تحديث دالة `SurgeryController@update` لدعم وتوثيق `apply_insurance` / `insurance_type` برمجياً عند تعديل العملية لحل مشكلة عدم إمكانية تغيير مسار العملية بعد إنشائها.
  * **إبراز شارات التغطية المالية في السجلات والتفاصيل (`resources/views/surgeries/index.blade.php`, `resources/views/surgeries/show.blade.php`)**:
    - إظهار شارة تصنيف واضحة أمام كل عملية جراحية في جدول العمليات اليومي والتاريخي وتفاصيل العملية:
      - شارة خضراء: `[ 🛡️ ضمان صحي ]` للعمليات المحجوزة تحت مظلة الضمان.
      - شارة رمادية: `[ 💵 نقدي (كاش) ]` للعمليات المحجوزة كاش 100%.
  * **ربط مسار الحجز مباشرة بكاشير العمليات (`resources/views/cashier/surgeries/payment-form.blade.php`)**:
    - عندما تحجز العملية نقداً (`insurance_type = 'none'`)، تفتح شاشة الكاشير مباشرة في الوضع النقدي 100%، ويظهر صندوق الخصم المالي الإداري للمريض، وتصفر مطالبات الضمان تماماً.
    - عندما تحجز العملية كضمان (`insurance_type = 'hi'` أو `'moi'`)، تفتح شاشة الكاشير بنسب استقطاع الضمان ومطالبة التأمين المعتمدة.
  * **حزم الاختبارات الآلية الشاملة (`tests/Feature/SurgeryTest.php`, `tests/Feature/SurgeryCashierSplitAndInsurancePaymentTest.php`)**:
    - إضافة اختبار `test_surgery_insurance_vs_cash_reservation_mode_on_create_and_update` للتحقق من حجز المريض المؤمن كاش، وظهور الشارة في السجل، فتح شاشة التعديل بالخيار المحدد، والتحويل بين الكاش والضمان بنجاح (4 passed, 42 assertions).
    - اجتياز كامل اختبارات كاشير العمليات والشمولية بنجاح 100% (7 passed, 51 assertions).

- **المرحلة 151 (تحكم شمولية الخدمات والتحاليل الروتينية وتخطيط القلب لكل بند على انفراد في كاشير العمليات واحتساب نسب التحمل للضمان والنقدي Surgery Cashier Per-Item Package Inclusion & Multi-Service Copay Architecture):**
  * **محدد الشمولية لكل خدمة على انفراد (Per-Item Package Inclusion Toggle Switches) (`resources/views/cashier/surgeries/payment-form.blade.php`)**:
    - استبدال آلية الإخفاء التام بزر تحكم عام تبادلي ذكي `✨ باقة شاملة بالكامل (All-Inclusive Surgery Package)` يُفعل أو يُلغي الشمول لجميع البنود دفعة واحدة دون إخفاء أي صف من الجدول.
    - إضافة مفتاح شمولية مستقل لكل خدمة على انفراد (`package_included[lab][id]`, `package_included[radiology][id]`, `package_included[extra][id]`):
      * التحاليل الروتينية والفيروسية (CBC, Viral, PT): مفعلة افتراضياً بشارة خضراء مميزة `[ 🟢 مشمول ضمن باقة العملية (0 د.ع) ]` وتظهر التكلفة `0 د.ع (مشمول)`.
      * تخطيط القلب السريري (ECG): قابل للتضمين بالاتفاق مع الجراح أو الاحتساب كخدمة منفصلة.
      * الخدمات الفندقية والإدارية والغرامات (مرافق، تزيين، طبلة): غير مشمولة افتراضياً وتحسب نقدية 100%.
  * **تطبيق نسب التحمل التفصيلية متعددة الخدمات للضمان والنقدي (`app/Http/Controllers/CashierController.php`)**:
    - دعم كامل لمعالجة مصفوفة `package_included`:
      * البنود المشمولة (`package_included = 1`): تُحسب بصفر دينار على المريض وتتحول إلى `paid` برقم سند القبض مع تثبيت ملاحظة `(مشمول ضمن باقة العملية)` لفك التعليق عنها في المختبر والمحطات السريرية فوراً.
      * البنود غير المشمولة (`package_included = 0`):
        1. في العمليات النقدية (`none`): تُحتسب بالسعر النقدي الكامل المعتمد للخدمة.
        2. في عمليات الضمان الصحي (`hi` / `moi`): تُحتسب وفق نسب التحمل الدقيقة للفئة التأمينية عبر `getCopayPercentageFor` (استقطاع العمليات والغرف: 25%، استقطاع المختبر: 25%، استقطاع الأشعة: 25%، واستقطاع التخطيط والتمريض: 10% للفئة H)، مع تحويل الفارق لمطالبة الضمان الصحي.
    - الحفاظ على التوافق الرجعي 100% (Backward Compatibility) مع المتصلين القدامى والاختبارات السابقة.
  * **تحديث محرك الجافاسكريبت اللحظي (`calculateSelectedAmount`)**:
    - حساب لحظي دقيق ومباشر لحصة المريض الصافية، إجمالي المطالبات التأمينية، الخصم النقدي الإداري، والمزامنة الفورية مع صندوق الدفع المركب (كاش + بطاقة).
  * **حزم الاختبارات الآلية الشاملة (`tests/Feature/SurgeryCashierSplitAndInsurancePaymentTest.php`)**:
    - 7 اختبارات ميزة ناجحة بنسبة 100% (51 assertions): تغطية عرض الواجهة بشارة الشمول، دفع الضمان مع شمول التحاليل، الدفع المركب، الحماية من عدم التطابق، الدفع الجزئي، العمليات النقدية مع التضمين/الاستبعاد الجزئي، واحتساب نسب التحمل التأمينية الدقيقة لكافة الخدمات.

- **المرحلة 150 (ترقية واجهة تسديد العمليات ودعم الدفع المركب كاش وبطاقة وشمول التحاليل الروتينية Surgery Cashier Split Payment & Included Pre-Op Routine Tests Flow):**
  * شمول التحاليل الروتينية للعملية ضمن أتعاب الجراحة مجاناً (`0 د.ع`).
  * دعم الدفع المركب / المختلط (Split Payment: كاش + بطاقة ائتمان / POS) مع صندوق تفاعلي ذكي وزر المناصفة (50/50) ومزامنة آلية وشارة تحقق وتطابق لحظية.
  * ترقية واجهة السداد ومطابقتها لواجهة تسديد الطلبات.

- **المرحلة 143 (إنشاء رادار صالات العمليات والولادة المتوقعة لقسم الخدج NICU Delivery & C-Section Operations Radar مع التحكم الديناميكي بأنواع العمليات وإبراز الصالات):**
  * **قاعدة البيانات والترحيل (`database/migrations/2026_11_03_084643_add_show_in_nicu_radar_to_surgical_operations_table.php` & `app/Models/SurgicalOperation.php`)**:
    - إضافة حقل `show_in_nicu_radar` بجدول `surgical_operations` وتفعيل العمليات القيصرية والولادية الافتراضية تلقائياً.
    - تحديث `$fillable` و `$casts` ونطاق `scopeNicuRadar`.
  * **شاشة رادار العمليات والولادات (`app/Http/Controllers/NicuDeliveryRadarController.php`, `resources/views/nicu/delivery-radar.blade.php`)**:
    - شاشة رادار لحظية متقدمة للمتابعة السريرية والاستعداد المسبق لكادر الخدج بدون أي أزرار حجز تجنباً للتضارب مع الاستعلامات.
    - إبراز صالة العمليات (`Operation Theater`) بشكل جلي وشارة مخصصة (`[ 🚪 صالة العمليات: اسم الصالة ]` أو `[ ⚠️ بانتظار تحديد الصالة ]`).
    - شريط الأيام الأسبوعية للتنقل بين الأيام، وإحصائيات سريعة للعمليات (المجدولة، بالانتظار، الجارية الآن بالصالات مع نبض وتنبيه أحمر، والمكتملة).
    - فلترة وبحث فوري لحظي بالجافاسكريبت بالاسم أو الصالة أو الطبيب أو نوع العملية.
  * **واجهة التحكم الديناميكية بالعمليات (`resources/views/nicu/delivery-radar-settings.blade.php`)**:
    - واجهة إعدادات مخصصة للأدمن والمشرفين لإدارة وتفعيل/استبعاد أي عملية جراحية بالرادار بنقرة واحدة عبر AJAX Switch مع إشعار فوري (Toast).
    - **العزل البصري التام (ربط خفي بدون تشويش على شاشات العمليات)**: حصر إدارة الرادار بالكامل داخل قسم الخدج وعزل شاشات العمليات الجراحية العامة (`create`, `edit`, `index`) من أي أزرار أو حقول أو شارات تخص الخدج.
  * **الصلاحيات والقوائم (`database/seeders/RolesAndPermissionsSeeder.php`, `resources/views/layouts/app.blade.php`, `resources/views/nicu/index.blade.php`)**:
    - إضافة الصلاحيات: `view nicu delivery radar` (للأدمن وطبيب وتمريض الخدج)، و `manage nicu delivery radar settings` (للأدمن والمشرفين).
    - إدراج رابط الرادار في القائمة الجانبية لقسم الخدج، وزر سريع في لوحة الخدج الرئيسية.
  * **حزمة الاختبارات الآلية الشاملة (`tests/Feature/NicuDeliveryRadarTest.php`)**:
    - تغطية شاملة لمنع غير المصرح لهم، عرض العمليات المشمولة بصالاتها وعزل غير المشمولة، فتح الإعدادات، وتبديل الحالة بالـ AJAX (5 passed, 22 assertions بنجاح 100%).

- **المرحلة 142 (إنشاء وتعميم تبويب الحسابات المتكامل لقسم الخدج والحاضنات NICU Accounting & Analytics Module ضمن الحسابات العامة والمالية):**
  * **القائمة الجانبية الموحدة (`resources/views/layouts/app.blade.php`)**:
    - إضافة قسم فرعي جديد `حسابات الخدج والحاضنات` (`#nicuAccountingSubSection`) باللون الأزرق السماوي (`#0dcaf0`) تحت القائمة الموحدة "الحسابات العامة والمالية".
    - يحتوي على التبويبات المعيارية الأربعة: تحليلات إحصاءات الخدج، الحركات المالية للخدج، كشوفات الخدج، وحسابات أطباء الخدج.
  * **تحليلات وإحصاءات الخدج (`app/Http/Controllers/AccountantController.php`, `resources/views/accountant/nicu/analytics.blade.php`)**:
    - إضافة دالة `nicuAnalytics` لعرض كروت KPI الشاملة (حالات الدخول كاش وضمان، نسبة إشغال الحاضنات، إجمالي المقبوضات والأمانات، وصافي إيراد الخدج).
    - مخطط أعمدة لأعلى خدمات الخدج استخداماً وإيرادها التقديري (CPAP، معالجة ضوئية، عناية فائقة).
    - مخطط بياني مقارن لنشاط أطباء الأطفال والخدج وإحصائيات الحالات والاستشارات.
  * **الحركات المالية للخدج (`app/Http/Controllers/CashierController.php`, `resources/views/cashier/nicu-movements.blade.php`)**:
    - إضافة دالة `nicuFinancialMovements` لسجل حركات `nicu_payments` بفلاتر التاريخ، طريقة الدفع (كاش/بطاقة)، ونوع الحركة (أمانات وسلفة، دفعات تحت الحساب، تسوية نهائية، واسترجاع فائض).
    - بطاقات الملخص المالي الثلاثية: المستلم، المسترجع، والصافي الفعلي المقبوض، مع أزرار الطباعة والمعاينة والوصل الحراري وكشف حساب الطفل.
  * **كشوفات الخدج (`app/Http/Controllers/CashierController.php`, `resources/views/cashier/nicu-statements.blade.php`)**:
    - إضافة دالة `nicuStatements` لعرض تقرير تفصيلي بإيرادات الحواضن، التحاليل المخبرية، الأشعة، والاستشارات والأدوية لكل طفل راقد.
    - جدول تفصيلي بالأيام المحتسبة وفق قواعد السماحية 6 ساعات، إجمالي التكلفة، المسدد، والرصيد المتبقي (ذمة أو فائض أمانات).
  * **حسابات أطباء الخدج والأطفال (`app/Http/Controllers/CashierController.php`, `resources/views/cashier/nicu-doctor-accounts.blade.php`, `resources/views/cashier/nicu-doctor-account.blade.php`)**:
    - رصد مستحقات أطباء الأطفال من استشارات الخدج المنجزة والحالات المشرف عليها.
    - نموذج تسجيل وصرف دفعات المستحقات (`nicuDoctorPayout`) وتوثيق سند صرف وسند قيد خزينة `FinancialTransaction`.
  * **الربط مع الخزينة المركزية للمستشفى (`app/Http/Controllers/HospitalTreasuryController.php`, `app/Models/FinancialTransaction.php`)**:
    - إضافة تصنيف `nicu` في `FinancialTransaction::CATEGORIES`.
    - إدراج الخدج ورعاية حديثي الولادة (NICU) كقسم رئيسي ضمن بطاقات إيرادات الأقسام ومصفوفة الاستحقاق في لوحة الخزينة المركزية.
    - التوثيق المالي التلقائي في `FinancialTransaction` عند تحصيل أو استرجاع أي دفعة خديج في `processNicuPayment`.
  * **الصلاحيات والـ Seeder (`database/seeders/RolesAndPermissionsSeeder.php`)**:
    - إضافة وتوليد الصلاحيات: `view nicu analytics`, `view nicu financial movements`, `view nicu statements`, `view nicu doctor accounts`.
  * **حزمة الاختبارات الآلية الشاملة (`tests/Feature/NicuAccountingTest.php`)**:
    - تغطية شاملة لجميع المسارات والصفحات الأربعة وعملية صرف مستحقات الطبيب (4 passed, 21 assertions بنجاح 100%).

- **المرحلة 141 (نظام نسب التحمل المفصلة لكل خدمة في فواتير الضمان الصحي الموحدة لدى الكاشير Per-Service Item Copay Enforcement on Combined Cashier Insurance Receipts):**
  * **نموذج الفئات التأمينية (`app/Models/HealthInsuranceCategory.php`)**:
    - إضافة حالتي `ecg` و `nursing` في دالة `getCopayForService` لترجع نسبة `consultation_copay` المعتمدة (10% للفئة H).
  * **واجهة دفع الطلبات الطبية الموحدة (`resources/views/cashier/request-payment-form.blade.php`)**:
    - إنهاء تعميم نسبة التحمل الفردية وتطبيق نسبة التحمل المعتمدة لكل خدمة على حدة: المختبر 25%، الأشعة 25%، تخطيط القلب بإحالة استشارية 10%، وتخطيط القلب المباشر كاش 100% بدون تغطية ضمان (0%).
    - استبدال البطاقة المضللة ببطاقة الفئة الشاملة: `الفئة H — المختبر: 25% | الأشعة: 25% | الاستشارية والتخطيط: 10%`.
    - تزويد حقل نسبة التحمل التراكمي بشارة `مفصلة حسب الخدمة (المختبر 25% / التخطيط 10%)`.
    - تزويد أسطر الجدول بخاصية `data-item-copay` وشارات التغطية التفاعلية `مشمول بالضمان (تحمل X%)`.
    - تحديث محرك الجافاسكريبت `recalculate()` لاحتساب كل خدمة بنسبتها المستقلة.
  * **معالجة العمليات في الكنترولر (`app/Http/Controllers/CashierController.php`)**:
    - تمرير نسبة كل خدمة (`$labCopay`, `$radCopay`, `$ecgCopay`) بشكل منفصل داخل `processRequestPayment`.
    - تثبيت احتساب التخطيط المباشر نقداً 100% كاش دون تحميل الضمان.
    - حذف التكرار البرمجي لبلوك `nursing`.
  * **إيصالات القبض والطباعة الحرارية (`receipt.blade.php` و `receipt-print.blade.php`)**:
    - تدعيم مطابقة فحص تخطيط القلب بكافة الصيغ والأنماط الشائعة (`hear echo`, `echo`, `ايكو`, `ecg`, `تخطيط`).
  * **حزم الاختبارات الآلية الشاملة (`tests/Feature/MultiServiceInsuranceCopayTest.php`)**:
    - تغطية شاملة لفتح الوصل واحتساب الصافي، تسديد الوصل المدمج، واحتساب التخطيط المباشر (3 passed, 12 assertions).

- **المرحلة 140 (تعميم نظام الخصم الجزئي والتفصيلي لكل خدمة والخصم الإجمالي ع الوصل على كاشير الطوارئ Cashier Emergency Dual-Mode Discount System Overall vs Per-Item Percentage):**
  * **واجهة كاشير الطوارئ التفاعلية (`resources/views/cashier/emergency-payment-form.blade.php`)**:
    - إضافة مفتاح التبديل الثنائي لنطاق الخصم (`discount_scope`): "خصم إجمالي ع الوصل" (`overall`) مقابل "خصم تفصيلي لكل خدمة" (`per_item`).
    - تزويد كل بند في فاتورة الطوارئ (خدمات الطوارئ، التحاليل، الأشعة، الاستشارات الطبية، ومتابعة الطبيب) بمفتاح فريد وعمود مخصص لإدخال نسبة الخصم لكل خدمة بشكل منفصل (`col-item-discount`).
    - احتساب فوري لحظي بالدينار لكل خدمة مع شطب السعر الأصلي وإظهار الصافي وشارة قيمة الخصم بالدينار.
    - قفل حقل الخصم الإجمالي وتحويله لعرض التراكمي عند تفعيل الخصم التفصيلي، وعكسه فورياً عند العودة للخصم الإجمالي.
  * **معالجة العمليات في الكنترولر (`app/Http/Controllers/CashierController.php`)**:
    - تطوير دالة `processEmergencyPayment` لدعم `discount_scope === 'per_item'` واحتساب الخصومات المفردة عبر كافة بنود الطوارئ (الخدمات، التحاليل، الأشعة، والاستشارات ومتابعة الطبيب).
    - توثيق أسباب الخصم التفصيلي تلقائياً بأسماء الخدمات ونسبها داخل `discount_reason` في جدول المدفوعات وتثبيت الصافي الفعلي ونسبة الخصم الإجمالية وهوية الكاشير.
  * **حزم الاختبارات الآلية الشاملة (`tests/Feature/CashierDiscountTest.php`)**:
    - إضافة اختبار ميزة متكامل (`test_cashier_can_apply_per_item_discount_on_emergency_payment`) لاختبار خصم الخدمات الفردية في الطوارئ والتحقق من حساب الصافي والنسبة والتوثيق المالي في قاعدة البيانات.
    - اجتياز كامل حزمة اختبارات الخصم بنجاح 100% (6 passed, 61 assertions).

- **المرحلة 139 (إبراز شارات التصنيف المالي "نقدي كاش / ضمان صحي / قوى أمن" وشريط الفرز الفوري في محطة الكاشير Cashier Financial Badges & Live Category Filter Bar):**
  * **إبراز الشارات المالية في كافة جداول الكاشير (`resources/views/cashier/index.blade.php`)**:
    - تعميم شارة صريحة وأنيقة `[💵 نقدي (كاش)]` لجميع المرضى غير المشمولين بالضمان عبر كافة التبويبات (الكل، كشفية الاستشارية، التحاليل والأشعة، الطوارئ، الفحوصات المؤجلة، ومستحقات الاسترجاع).
    - تدعيم قسم الطوارئ بالتعرف الكامل على نوع التأمين وعرض شارة "داخليّة" أو "ضمان صحي" أو "نقدي (كاش)" بدقة.
  * **شريط التصفية المالية السريعة اللحظية (`#financialFilterGroup`)**:
    - إضافة أزرار فرز تفاعلية فورية أعلى جدول تبويب الكل (`#tab-all`): `[الكل | 💵 نقدي / كاش | 🛡️ مشمول بالضمان]`.
    - تصفية فورية بالـ JavaScript لصفوف الجدول دون الحاجة لإعادة تحميل الصفحة مع تحديث عداد المعاملات الظاهرة لحظياً.

- **المرحلة 138 (نظام الخصم الثنائي التبادلي للكاشير: خصم إجمالي ع الوصل أو تفصيلي لكل خدمة مع العزل الصارم للضمان Cashier Dual-Mode Mutual Discount System - Overall Receipt vs. Per-Item Percentage with Strict Non-Insurance Enforcement):**
  * **التحكم التبادلي والواجهة التفاعلية (`resources/views/cashier/request-payment-form.blade.php`)**:
    - إضافة مبدل نطاق الخصم التبادلي (`discount_scope`): "خصم إجمالي ع الوصل" (`overall`) مقابل "خصم تفصيلي لكل خدمة" (`per_item`).
    - عمود مخصص في جدول الخدمات والتحاليل والأشعة والتمريض (`col-item-discount`) لإدخال نسبة الخصم لكل خدمة بشكل منفصل (بخطوات خماسية 5% واقتراحات Datalist من 0% حتى 100%).
    - حساب فوري بالدينار العراقي لكل خدمة مع شطب السعر القديم وإبراز الصافي الجديد وشارة توضيحية لقيمة الخصم بالدينار.
    - تجميع آلي فوري لكافة الخصومات الفردية في خانة الصافي ومجموع الإعفاء والنسبة الإجمالية التراكمية في صندوق الملخص المالي.
    - تعطيل متبادل ومنع التضارب: عند اختيار الخصم التفصيلي، يُقفل حقل الخصم الإجمالي للقراءة فقط ويتحول لعرض الإجمالي التراكمي، وعند العودة للخصم الإجمالي يُخفى عمود الجدول وتُصفر خصومات السطور تلقائياً.
    - حماية الضمان: يختفي صندوق الخصم وعمود الخدمات كلياً ويقفل النظام أي خصومات للمرضى المشمولين بالضمان الصحي (`hi`) أو قوى الأمن (`moi`) وحصره بنسبة 100% في المرضى النقديين (`none`).
  * **معالجة العمليات في الكنترولر وقواعد البيانات (`app/Http/Controllers/CashierController.php`)**:
    - تطوير دالة `processRequestPayment` للتعامل مع وضعيتي الخصم (`overall` و `per_item`).
    - في وضعية الخصم التفصيلي: احتساب خصم كل خدمة منفصلة وتوثيق مصفوفة الخصومات داخل `details['item_discounts']` لكل طلب طبي.
    - توثيق تفاصيل الخصم وأسماء الخدمات ونسبها تلقائياً في حقل `discount_reason` لسند الدفع (`payments`)، وتخزين إجمالي الخصم والنسبة الفعلية وهوية الكاشير المنفذ.
  * **سندات القبض المطبوعة والحرارية (`resources/views/cashier/receipt.blade.php`, `receipt-print.blade.php`, `receipt-pdf.blade.php`)**:
    - توافق تام مع سندات القبض والطباعة الحرارية وPDF لعرض المبالغ الصافية وتفاصيل أسباب ونسب الخصم التفصيلي تلقائياً أمام المريض والمدققين.
  * **حزمة الاختبارات الآلية الشاملة (`tests/Feature/CashierDiscountTest.php`)**:
    - إضافة اختبار ميزة متكامل (`test_cashier_can_apply_per_item_discount_on_non_insured_request_payment`) يؤكد تطبيق الخصم التفصيلي، وحساب الإجمالي الصافي بدقة، وتوثيق أسماء الخدمات ونسبها، وتخزينها في تفاصيل الطلب وسند الدفع.
    - اجتياز كامل حزمة اختبارات الخصم بنجاح 100% (5 passed, 51 assertions).

- **المرحلة 137 (تزامن تلقائي لمخرجات بناء Vite بين public/build و dist لمنصات النشر والاستضافة التلقائية Vite Build Output Dual-Sync for Automated Deployment Pipelines):**
  * **إعدادات البناء والمزامنة (`vite.config.js`, `package.json`, `.gitignore`)**:
    - ترقية `vite.config.js` بإضافة مكون إضافي ذكي (`sync-build-to-dist`) يقوم بنسخ كافة الأصول المولدة في `public/build` فور انتهاء عملية التجميع إلى مجلد `dist/` مع إنشاء ملف `index.html` متوافق مع الفحوصات الصارمة لمنصات النشر الثابت والاستضافات السحابية.
    - ترقية أمر `build` في `package.json` بـ Node.js inline script كخط دفاع احتياطي يضمن وجود وتحديث ملفات `dist` تلقائياً بعد كل بناء دون الحاجة لأي تعديل يدوي في إعدادات المنصة.
    - تثبيت مجلد `dist/` في Git عبر `.gitkeep` مع تجاهل محتوياته المجمعة في `.gitignore`.

- **المرحلة 136 (دعم وتكامل طلبات تخطيط القلب والتمريض الصادرة من الاستشارية لدى الكاشير بتسعير الضمان الصحي الدقيق Doctor ECG & Nursing Requests Cashier Billing with Accurate Insurance Pricing):**
  * **معالجة طلبات التمريض وتخطيط القلب في الكاشير (`app/Http/Controllers/CashierController.php`, `resources/views/cashier/request-payment-form.blade.php`)**:
    - توسيع استيعاب واجهة كاشير الطلبات الطبية لتشمل طلبات التمريض (`type === 'nursing'`) بدلاً من قصرها سابقاً على المختبر والأشعة فقط.
    - تصحيح وتدقيق مطابقة خدمة "تخطيط قلب" واستهداف خدمة الطوارئ المستقلة المعتمدة رقم **#93** (`تخطيط قلب`) بسعر 15,000 د.ع كاش وسعر 10,000 د.ع للضمان الصحي (HI)، ومنع مطابقتها الخاطئة سابقاً مع خدمة #68 ("مراقبة الفعاليات الحيوية مع او بدون تخطيط القلب" بسعر 25,000 د.ع).
    - دعم احتساب حصة المريض التأمينية بدقة بناءً على نسبة تحمله (مثلاً 10% = 1,000 د.ع، 15% = 1,500 د.ع) وتثبيت حصة الضمان الصحي (9,000 د.ع أو 8,500 د.ع).
    - إضافة نوع `nursing` لفحص حالات الدفع (`$hasAnyPay`) لمنع تصنيف الطلب كملغي أو توجيهه خطأ إلى شاشة الكاشير الرئيسية.
  * **شاشة الطبيب وإحالات الطوارئ السريعة (`resources/views/doctors/visits/show.blade.php`)**:
    - إضافة زر إحالة سريعة مخصص `+ تخطيط قلب (ECG)` في بطاقة إحالة الطوارئ بمحطة الطبيب الاستشاري لإرسال الطلب فورياً للكاشير.
  * **سندات القبض المطبوعة والحرارية (`resources/views/cashier/receipt.blade.php`, `receipt-print.blade.php`)**:
    - إدراج بنود التمريض وتخطيط القلب (ECG) في جدول الإيصال المالي والوصل الحراري المطبوع مع تفصيل المبلغ المعتمد وحصة المريض وحصة الضمان واسم الطبيب المعالج.
  * **حزمة الاختبارات الآلية (`tests/Feature/DoctorECGRequestPaymentTest.php`)**:
    - اختباران شاملان يغطيان استعراض طلب تخطيط القلب مع تسعير الضمان الصحي المعتمد 10,000 د.ع، وتسديد الطلب بنجاح بموجب نسبة التحمل (10% = 1,000 د.ع) وإصدار السند بنجاح 100% (11 assertions).

- **المرحلة 135 (تعميم نظام الخدمات الإضافية والفندقية على واجهة كاشير الفحوصات والطلبات الطبية بتسعير ديناميكي حر خارج الضمان Medical Requests & Visits Dynamic Extra Services with Non-Insurance Direct Billing):**
  * **قاعدة البيانات والترحيل (`database/migrations/2026_10_30_202000_create_visit_extra_services_table.php`)**:
    - إنشاء جدول `visit_extra_services` لربط الخدمات الإضافية والفندقية بالطلب الطبي والزيارة وسند الدفع (`visit_id`, `request_id`, `service_id`, `name`, `price`, `quantity`, `total_amount`, `notes`, `payment_status`, `payment_id`, `created_by`).
    - تطبيق المايجريشن بنجاح محلياً وعلى قاعدة بيانات هوستنجر الحية (`remote_hostinger`).
  * **النماذج والعلاقات (`app/Models/VisitExtraService.php`, `app/Models/Visit.php`, `app/Models/Payment.php`)**:
    - بناء نموذج `VisitExtraService` مع العلاقات مع الزيارة والطلب والدفع والخدمة المرجعية والمستخدم.
    - إضافة علاقة `extraServices()` في `Visit` وعلاقة `visitExtraServices()` في `Payment`.
  * **معالجة العمليات في الكنترولر والمسارات (`app/Http/Controllers/CashierController.php`, `routes/web.php`)**:
    - تمرير قائمة الخدمات المرجعية والخدمات المعلقة لواجهة كاشير الطلبات `showRequestPaymentForm`.
    - إضافة مسار ودالة `addRequestExtraService` لإضافة خدمة إضافية بسعر ديناميكي حر يحدده أو يعدله الكاشير لحظياً.
    - إضافة مسار ودالة `removeRequestExtraService` لحذف أي خدمة معلقة قبل السداد.
    - ترقية `processRequestPayment` لحساب بنود الخدمات الإضافية 100% نقداً وإضافتها لمجموع القبض وربطها بسند القبض المالي وتحويل حالتها إلى `paid`.
  * **الواجهات وسندات القبض المطبوعة والحرارية (`resources/views/cashier/request-payment-form.blade.php`, `receipt.blade.php`, `receipt-print.blade.php`)**:
    - إدراج بطاقة إضافة الخدمات الإضافية والفندقية في شاشة دفع الطلبات الطبية (`cashier/request-payment/{id}`).
    - إدراج بنود الخدمات الإضافية المعلقة في جدول الفحوصات مع أزرار الحذف الفوري وشارة كاش 100%.
    - شمول بنود الخدمات الإضافية في سند القبض والشاشة التفاعلية وإيصال الطابعة الحرارية.
  * **حزمة الاختبارات الآلية (`tests/Feature/RequestExtraServicesTest.php`)**:
    - 3 اختبارات ميزة تغطي إضافة الخدمة للطلب، حذف الخدمة المعلقة، وسداد الطلب الطبي مع الخدمات الإضافية بنجاح 100% (13 assertions).

- **المرحلة 134 (نظام الخدمات الإضافية والفندقية للعمليات الجراحية والرقود بتسعير ديناميكي للكاشير مع العزل الكامل عن الضمان Surgery Accommodation & Extra Services with Dynamic Cashier Pricing):**
  * **قاعدة البيانات والترحيل (`database/migrations/2026_10_30_183000_create_hospital_extra_services_tables.php`)**:
    - إنشاء جدول `hospital_extra_services` كدليل مرجعي للخدمات الإضافية والفندقية (تخطيط قلب، خدمة مرافق مريض مبيت، رقود إضافي، أجور فتح طبلة، طبيب أطفال، تزيين الغرف Decorating 50/100، غرامات التدخين وإدخال الأكل، بيان الولادة، مواد مصروفة، خدمات إدارية، خدمات فندقة نواظير) مع استبعاد تصنيفات الغرف (VIP/VVIP) لأنها مدعومة ومحسوبة تلقائياً في نظام الغرف `rooms`.
    - إنشاء جدول `surgery_extra_services` لربط الخدمات الإضافية بالعملية الجراحية وسند القبض المالي (`surgery_id`, `name`, `price`, `quantity`, `total_amount`, `notes`, `payment_status`, `payment_id`, `created_by`).
    - تطبيق المايجريشن محلياً وعلى قاعدة بيانات هوستنجر الحية (`remote_hostinger`).
  * **النماذج والعلاقات (`app/Models/HospitalExtraService.php`, `app/Models/SurgeryExtraService.php`, `app/Models/Surgery.php`)**:
    - بناء نموذجي `HospitalExtraService` و `SurgeryExtraService` مع حماية الحقول والـ Casts والعلاقات مع المستخدم وسند الدفع.
    - إضافة علاقة `extraServices()` في نموذج `Surgery`.
  * **معالجة العمليات في الكنترولر (`app/Http/Controllers/CashierController.php`)**:
    - شمول الخدمات الإضافية في واجهة كاشير العمليات `showSurgeryPaymentForm` مع حساب المبالغ المعلقة والمسددة وتمرير القائمة الإرشادية.
    - إضافة دالتي `addSurgeryExtraService` لإضافة خدمة إضافية بسعر ديناميكي حر يحدده أو يعدله الكاشير، و `removeSurgeryExtraService` لحذف أي خدمة معلقة قبل السداد.
    - تحديث `processSurgeryPayment` لاحتساب مبالغ الخدمات الإضافية 100% نقداً كخدمات خارج تغطية الضمان وربطها بسند القبض المالي وتحديث حالتها إلى `paid` مع التحقق من سداد كامل مستحقات العملية.
  * **الواجهات وسندات القبض المطبوعة والحرارية (`resources/views/cashier/surgeries/payment-form.blade.php`, `receipt.blade.php`, `receipt-print.blade.php`)**:
    - بطاقة تفاعلية لإضافة الخدمات الإضافية مع اقتراحات قائمة الدليل وسعر افتراضي قابل للتعديل الفوري من الكاشير.
    - إدراج بنود الخدمات المضافة في جدول الفاتورة مع أزرار الحذف الفوري للبنود المعلقة.
    - إبراز بنود الخدمات الإضافية كسطور مستقلة في سند القبض التفاعلي والوصل الحراري المطبوع.
  * **حزمة الاختبارات الآلية (`tests/Feature/SurgeryExtraServicesTest.php`)**:
    - 3 اختبارات ميزة تغطي إضافة خدمة إضافية بسعر مخصص، حذف خدمة معلقة، وسداد عملية جراحية مع خدماتها الإضافية بنجاح 100% (14 assertions).

- **المرحلة 133 (دعم خصومات الكاشير الإدارية والخاصة بنسب خماسية من 0% إلى 100% وحصرها التام خارج الضمان وإخفاء عناصر الضمان للنقدي Cashier Multi-Departmental 5% Step Discounts System & Strict Non-Insurance Enforcement):**
  * **قاعدة البيانات وموديل الدفعات (`database/migrations/2026_10_30_150000_add_discount_fields_to_payments_table.php` & `app/Models/Payment.php`)**:
    - إضافة حقول الخصم الرسمية لجدول `payments`: `discount_percentage`, `discount_amount`, `discount_reason`, `discount_by`.
    - تحديث `$fillable` و `$casts` وعلاقة `discountBy()` في نموذج `Payment`.
    - تطبيق المايجريشن محلياً وعلى قاعدة بيانات هوستنجر الحية (`remote_hostinger`).
  * **معالجة الدفع المالي في الكنترولر (`app/Http/Controllers/CashierController.php`)**:
    - شمول جميع أقسام الكاشير: الفحوصات الطبية والمختبر والأشعة (`processRequestPayment`)، خدمات وفحوصات واستشارات الطوارئ (`processEmergencyPayment`)، رسوم العمليات الجراحية والغرف والتحاليل الملحقة (`processSurgeryPayment`).
    - **العزل الصارم لحماية الضمان**: تعطيل الخصم وتصفيره نهائياً إذا كانت فئة المريض ضمان صحي (`hi`) أو قوى أمن (`moi`) وحصره فقط في المرضى النقديين (`insurance_type === 'none'`).
    - الحساب المالي الدقيق: احتساب الخصم من حصة المريض الإجمالية (`patient_share`)، وتخزين الصافي الفعلي في الصندوق بحقل `amount`، وتوثيق نسبة وقيمة وسبب الخصم وهوية الكاشير أو المشرف.
  * **تحديث واجهات الكاشير الحية وتطهيرها (`resources/views/cashier/request-payment-form.blade.php`, `resources/views/cashier/emergency-payment-form.blade.php`, `resources/views/cashier/surgeries/payment-form.blade.php`)**:
    - **إخفاء حقول وعناوين الضمان للمريض النقدي**: عند فتح وصل لمريض نقدي، يتم إخفاء ترويسة الضمان وشارة التحمل وقائمة اختيار جهة الضمان بالكامل، وتعيين حقل مخفي `insurance_type="none"`.
    - إظهار صندوق مستقل: **"حساب الدفع النقدي والخصم الخاص"** يحتوي على حقل إدخال نسبة الخصم وصافي المبلغ المطلوب قبضه نقداً.
    - **توحيد إدخال الخصم (كتابة + اقتراحات فقط)**: إزالة الأزرار السريعة، واعتماد حقل رقمي مباشر (`input type="number"`) مع قائمة اقتراحات `datalist` للنسب الخماسية من 0% إلى 100%، وقائمة لاقتراحات أسباب الخصم.
    - تحديث فوري مباشر بجافاسكريبت لحصة المريض والمبلغ المطلوب قبضه وزر التأكيد وشريط الملخص المالي.
  * **تحديث إيصالات القبض والطباعة الحرارية وPDF (`resources/views/cashier/receipt.blade.php`, `resources/views/cashier/receipt-print.blade.php`, `resources/views/cashier/receipt-pdf.blade.php`)**:
    - إبراز سطر تفصيلي واضح لخصم الإدارة (النسبة %، القيمة بالدينار، وسبب الخصم) قبل خانة الصافي المدفوع، لضمان وضوح وشفافية السند المالي أمام المريض والمدققين.
  * **حزم الاختبارات الآلية الشاملة (`tests/Feature/CashierDiscountTest.php`)**:
    - بناء 4 اختبارات ميزة تغطي كامل متطلبات الدورة المالية (فحوصات، طوارئ، عمليات، ومنع الخصم للمؤمنين) واجتيازها بنجاح 100% (4 passed, 37 assertions).

- **المرحلة 132 (تمكين الكاشير الإداري casshieradmin والمخولين بالإبطال من استعراض كافة الوصولات وفلترة الكاشيرات Cashier Admin & Void Authorized Users Full Receipts Access & Departmental Cross-Drawer Filter Parity):**
  * **إزالة القيد الحصري عن الكاشير الإداري والمخولين بالإبطال (`app/Http/Controllers/CashierController.php`)**:
    - توسيع صلاحية استعراض الوصولات في دالة `reports()` لتشمل دور الكاشير الإداري `casshieradmin` وصلاحية إبطال الوصولات `void payment receipt` بالإضافة إلى الأدوار الإدارية (`admin`, `accountant`, `hospital_admin`).
    - إلغاء فرض شرط `where('cashier_id', $user->id)` التلقائي عليهم، مما يسمح لهم بمشاهدة والبحث في وصولات جميع الكاشيرات والصناديق بالمستشفى دفعة واحدة.
    - تزويدهم بقائمة كاشيرات المستشفى للفلترة المخصصة حسب الكاشير أو عرض "جميع الكاشيرات".
  * **تحديث واجهة تقرير الوصولات والمقبوضات (`resources/views/cashier/report.blade.php`)**:
    - إظهار القائمة المنسدلة لفلترة الكاشير لمن يملك دور `casshieradmin` أو صلاحية `void payment receipt`.
  * **حزم الاختبارات الآلية الشاملة (`tests/Feature/PaymentVoidAndReversalTest.php`)**:
    - اختبار ميزة متكامل يؤكد عزل الكاشير العادي لوصولاته الخاصة وقدرة الكاشير الإداري على رؤية وصولات جميع الكاشيرات (11 passed, 73 assertions).

- **المرحلة 131 (دمج وتوحيد فحوصات CBC في فحص نشط وحيد رقم 43 ونقل باقات الأطباء وتعطيل المكرر كأرشيف CBC Tests Consolidation into Single Active ID 43 & Legacy Archiving):**
  * **الدمج الشامل وتوحيد سجلات فحص الدم (`database/migrations/2026_10_29_213000_consolidate_cbc_tests_to_single_active_test_43.php`)**:
    - اعتماد السجل رقم #43 كفحص نشط ورسمي وحيد باسم `CBC (5-Part Diff)` وبسعر 20,000 د.ع مع كامل البارامترات الـ 27.
    - تمييز السجل رقم #44 كأرشيف مدمج (`CBC (5-Part Diff) [أرشيف مدمج]`) وتعطيله نهائياً (`is_active = 0`).
    - تمييز السجل رقم #1440 كمكرر ملغي وتعطيله نهائياً.
    - ترحيل ونقل كافة الباقات المفضلة لجميع أطباء المستشفى (21 باقة) إلى 43 مباشرة وتطبيق المايجريشن محلياً وعلى هوستنجر.

- **المرحلة 130 (ترقية وتوحيد طلب استشارة الطوارئ إلى اختيار استشاري فردي وربطها مالياً بخدمة استشارة اخصائي من العيادة الاستشارية بسعر 30,000 د.ع Emergency Single Consultant Selection & Automated 30,000 IQD Emergency Service Billing):**
  * **ترقية واجهة نافذة طلب الاستشارة الطبية (`resources/views/emergency/show.blade.php`)**:
    - استبدال صندوق الاختيار المتعدد بقائمة منسدلة حديثة وسلسة لاختيار الطبيب الاستشاري المطلوب بعينه من الأطباء المتاحين بالدوام.
  * **الربط المالي الصارم بخدمات الطوارئ (`app/Http/Controllers/EmergencyController.php`)**:
    - إلحاق خدمة الطوارئ الرسمية "استشارة اخصائي من العيادة الاستشارية" (#115) بسعرها الرسمي 30,000 د.ع تلقائياً إلى خدمات الحالة وتحديث الفاتورة والوصل.
    - ضبط القيد المالي وتوزيع الحصص بنسبة 80% للطبيب (24,000 د.ع) و 20% للمستشفى (6,000 د.ع).
  * **حزم الاختبارات الآلية**: اجتياز 11 اختباراً بنجاح تام (49 assertions).

- **المرحلة 129 (توحيد فحص عد الدم الشامل الآلي باسمه الطبي المعتمد CBC (5-Part Diff) وفصل فحص فيلم الدم المجهري Blood Film وتحديث المطبوعات والاختبارات Automated CBC (5-Part Diff) Medical Standardization, Standalone Microscopic Blood Film & Complete Print Parity):**
  * **توحيد المسمى الطبي في قاعدة البيانات (`database/migrations/2026_10_29_200000_standardize_cbc_test_name_to_cbc_5_part_diff.php`)**:
    - توحيد واعتماد المسمى `CBC (5-Part Diff)` لفحص الجهاز الآلي (رقم 43 و 44) بسعر 20,000 د.ع.
    - عزل وتثبيت فحص فيلم الدم المجهري اليدوي المستقل `Blood Film` (رقم 1243) بسعر 50,000 د.ع.
  * **تحديث شاشات الطباعة والمحطة**: تحديث دالة التطبيع السريرية والمرفقات لتظهر باسم `CBC (5-Part Diff)`.
  * **حزم الاختبارات الآلية**: اجتياز 21 اختباراً بنجاح تام 100% (133 assertions).

- **المرحلة 128 (فك ارتباط وإلغاء كافة الطلبات الطبية عند إبطال الوصل Rebill و Refund ومنع ظهورها في طابور المختبر والأشعة وإظهارها كملغاة عند الطبيب Reopened & Refunded Payment Medical Requests Cascade Cancellation, Departmental Queue Purification & Clinical Doctor Alerts):**
  * **التحصين الشامل في كنترولر إبطال وإعادة فتح الوصولات (`app/Http/Controllers/PaymentVoidController.php`)**:
    - حصر وجمع كافة معرفات الطلبات الطبية المرتبطة بالوصل المالي المُراد إبطاله بأي شكل (`$payment->request_id`, `$payment->lab_request_ids`, `$payment->radiology_request_ids`, وكذلك أي طلب يشير معرف دفعه صراحة `where payment_id = payment->id`).
    - **عند إعادة الفتح للتسديد والتصحيح (`void_type === 'rebill'`)**: فك ارتباط وتصفير كافة الطلبات المدمجة دفعة واحدة (`payment_id = null`, `status = 'pending'`, `payment_status = 'pending'`) وإعادة حالة الزيارة إلى `pending_payment` لضمان ظهور كافة طلبات المريض في طابور الكاشير للتسديد دون نسيان أي فحص ملحق.
    - **عند الاسترداد والإلغاء النهائي (`void_type === 'refund'`)**: إلغاء كافة الطلبات المرتبطة دفعة واحدة (`status = 'cancelled'`, `payment_status = 'cancelled'`) وتوثيق سبب الإلغاء وتاريخه ومعرف الكاشير في بيانات الطلب `details` لتظهر فوراً في شاشة الطبيب.
    - شمول طوارئ الأشعة `EmergencyRadiologyRequest` في عمليتي إعادة الفتح والاسترداد بجانب طوارئ المختبر.
  * **تحديث وإحكام معالجة الدفع لدى الكاشير (`app/Http/Controllers/CashierController.php`)**:
    - تخزين مصفوفات معرفات الفحوصات المسددة فعلياً في حقلي `lab_request_ids` و `radiology_request_ids` بجدول `payments` لتوثيق الفحوصات المشمولة بالوصل بدقة ومنع ضياع أي فحص مدمج.
    - إضافة صمام أمان رقابي: أي طلب طبي كان مشمولاً في فاتورة الزيارة ولم يتم تسديده ولم يؤجل، يُعتبر ملغياً بطلب المريض وتُحدث حالته إلى `cancelled` مع توثيق السبب ليختفي من طابور الفحص ويظهر للطبيب.
  * **تحصين وتطهير طوابير العمل في المختبر والأشعة (`app/Http/Controllers/LabStaffController.php` & `app/Http/Controllers/RadiologyStaffController.php`)**:
    - منع ظهور أي طلب طبي حالته `cancelled` أو حالة دفعه `payment_status = 'cancelled'`.
    - استبعاد أي طلب طبي مرتبط بوصل مالي تم إبطاله أو استرداده (`whereDoesntHave('payment', fn ($pq) => $pq->whereNotNull('voided_at'))`).
    - إضافة علاقة `payment()` من نوع `belongsTo` في نموذج `app/Models/Request.php`.
  * **تنظيف وتصحيح السجلات العالقة (`database/migrations/2026_10_29_192000_unbind_and_cleanup_voided_payments_requests.php`)**:
    - معالجة وترقية كافة الطلبات السابقة المرتبطة بوصولات ملغاة، وتصحيح حالة الفحص 859 (Free thyroxin) العالق في الوصل 1677 على السيرفر الحي وتحويله لملغي.
  * **حزم الاختبارات الآلية الشاملة (`tests/Feature/PaymentVoidAndReversalTest.php`)**:
    - بناء واجتياز اختبارين ميزة شاملين يغطيان دورة حياة إعادة الفتح الكاملة وتصفير كافة الطلبات المدمجة، وإلغاء الفحوصات غير المسددة واختفائها من طابور المختبر وظهورها في شاشة الطبيب كتنبيه سريري (`10 passed, 66 assertions`).

- **المرحلة 127 (توحيد آلية فحص الدم CBC & Blood Film وفحص فيلم الدم المجهري Blood Film في طوارئ المختبر والاستشارية 100% ومنع تفريغ البارامترات الفارغة Emergency Lab CBC & Blood Film Parity, Dedicated Microscopic Blood Film & Clean Device Report Architecture):**
  * **عزل وضبط فحصي الدم CBC & Blood Film و Blood Film في تقارير طوارئ المختبر (`resources/views/staff/requests/emergency-lab-print.blade.php`)**:
    - منع تفريغ الـ 27 بارامتراً فرعياً كصفوف فارغة أو معلقة (`Pending`) في جدول نتائج الفحوصات البيوكيميائية عند طلب فحص `CBC & Blood Film` المعتمد على مرفق الجهاز الآلي (`isAttachmentOnly`).
    - مزامنة المرفقات الفردية لطلبات الطوارئ مع جدول `emergency_lab_request_tests` لضمان ظهور زر تقرير الجهاز الآلي في الشريط العلوي بنمط `[ 🔬 مرفق: CBC & Blood Film (PDF) ]` أياً كان مصدر حفظ المرفق.
    - إزالة الصندوق المرفق من الورقة المطبوعة نهائياً لمطابقة الاستشارية 100%، والاكتفاء بالزر التفاعلي في شريط الأدوات العلوي بنمط `[ 🔬 مرفق: CBC & Blood Film (PDF) ]` لفتح وطباعة التقرير الأصلي الصادر من جهاز التحليل دون أي تشويه بصري للورقة.
    - عزل فحص فيلم الدم المجهري اليدوي `Blood Film` (رقم 1243، بسعر 50,000 د.ع) كفحص سريري مستقل (`single_value`) يظهر في جدول النتائج المباشرة بقيمته وتفاصيله المجهرية دون أي تداخل مع فحص الجهاز الآلي.
  * **تحديث وتوحيد مسمى الفحص في طباعة الاستشارية (`resources/views/staff/requests/print.blade.php`)**:
    - تعميم دالة التطبيع السريرية `$canonicalTestName` على شريط الأدوات العلوي وقوائم المرفقات في طباعة الاستشارية والطلبات المباشرة لمنع ظهور المسمى القديم `Complete blood count & Differential` واستبداله بالمسمى الرسمي المعتمد `CBC & Blood Film`.
  * **حزم الاختبارات الآلية الشاملة (`tests/Feature/UnifiedEmergencyLabStationAndReportTest.php`)**:
    - إضافة وتوسيع اختبارات الميزة لتغطية عدم تفريغ البارامترات الـ 27 لـ CBC في التقرير، والتعامل السليم مع المرفقات، والتمييز التام بين فحص CBC الآلي وفيلم الدم المجهري اليدوي (`10 passed, 62 assertions`).

- **المرحلة 126 (توحيد محطة إدخال وتقارير طوارئ الأشعة 100% مع محطة الأشعة والتقرير السريري الموحد Emergency Radiology Station & Print Report 100% Unification):**
  * **قاعدة البيانات ونموذج الطلبات (`2026_10_29_131000_add_results_data_to_emergency_radiology_requests_table.php` & `app/Models/EmergencyRadiologyRequest.php`)**:
    - إضافة حقل `results_data` (longText/json) لحفظ البيانات السريرية المنظمة (المشاهدات Findings، الاستنتاج Impression، التوصيات Recommendations، والمرفقات التشخيصية images، ومعلومات الطبيب المعتمد وتاريخ الاعتماد).
    - تحديث مصفوفة `$fillable` و `$casts` في موديل `EmergencyRadiologyRequest`.
  * **ترقية وتوحيد واجهة إدخال تقرير طوارئ الأشعة (`resources/views/staff/requests/emergency-radiology-show.blade.php`)**:
    - تطبيق التصميم السريري الهادئ (`Calm Clinical Design`) المتطابق 1:1 مع `radiology-staff/show.blade.php`.
    - توفير قوالب سريرية سريعة بنقرة واحدة (`Quick Templates Chips`): فحص سليم طبيعي، أشعة صدر سليمة، سونار بطن وحوض سليم، أشعة عظام سليمة، نتائج طارئة غير طبيعية.
    - فصل الحقول الطبية السريرية: Findings و Impression و Recommendations.
    - منطقة رفع وسحب وإفلات للملفات والصور التشخيصية (DICOM/PDF/JPG/PNG) مع معاينة فورية مصغرة وإمكانية حذف المرفقات.
    - شريط المريض المتكامل مع تنبيه بالأعراض السريرية ودواعي الفحص المسجلة من طبيب الطوارئ.
  * **إعادة بناء وتوحيد تقرير طباعة طوارئ الأشعة (`resources/views/staff/requests/emergency-radiology-print.blade.php`)**:
    - مطابقة بصرية وهيكلية 100% مع `radiology/print.blade.php` بالألوان الرسمية المعتمدة للمستشفى (`#005a9c` أزرق داكن، `#0284c7` أزرق سماوي) وخط Cairo.
    - شريط أدوات علوي تفاعلي: طباعة، حفظ PDF، زر واتساب المريض المباشر، ومبدل الورق المروس (`Letterhead Mode`) مع ضابط الهامش.
    - شعار المستشفى المدمج Base64 للظهور الفوري، وبطاقة المريض السريرية، وبانر الفحص، ومربعات Findings و Impression و Recommendations، ومعرض الصور التشخيصية، ومربع التوقيع والختم المعتمد، ورمز التحقق QR Code المباشر.
  * **معالجة السيرفر والتحصين في الكنترولر (`app/Http/Controllers/StaffRequestController.php`)**:
    - تحديث `showEmergencyRadiology` و `printEmergencyRadiology` لتحميل العلاقات الصحيحة واستخراج النتائج السابقة بدقة مع الحفاظ الكامل على التوافق العكسي.
    - تحديث `completeEmergencyRadiology` لحفظ النتائج المنظمة في `results_data` ومزامنتها مع جدول `emergency_radiology_request_types`.
  * **حزمة الاختبارات الآلية (`tests/Feature/UnifiedEmergencyRadiologyStationAndReportTest.php` & `tests/Feature/EmergencyRadiologyPrintTest.php`)**:
    - 5 اختبارات ميزة مؤتمتة تغطي استعراض المحطة، حفظ واعتماد التقرير، وطباعة التقرير مع وبدون مرفقات بنجاح تام (`5 passed, 37 assertions`).

- **المرحلة 125 (دمج وتكامل قسم الرنين المغناطيسي والمفراس تحت محطة عمل واحدة MRI & CT Workstation Integration):**
  * **توحيد فلاتر وقنوات المفراس والرنين في الكنترولر (`RadiologyStaffController.php` & `RadiologyController.php`)**:
    - توسيع استعلام قسم الرنين `selectedCategory === 'mri'` ليشمل تلقائياً كلاً من فحوصات الرنين والمفراس معاً (`whereIn('subtype', ['mri', 'ct'])`) في كافة استعلامات القائمة الرئيسية، طلبات الانتظار، الرادار اللحظي، والطابور.
    - تحديث التحقق الأمني من صلاحيات الطلب (`authorizeRequestAccess`) للسماح لحسابات الرنين (`radiology_mri`) بفتح وفحص وكتابة تقارير فحوصات المفراس (`ct`) دون أي خطأ 403.
    - دعم تصنيف المفراس في استعلامات طوارئ الأشعة لحساب الرنين (`subcategory in ['الرنين', 'مفراس', 'رنين']`).
  * **تحسين واجهة محطة الأشعة (`resources/views/radiology-staff/index.blade.php`)**:
    - تحديث زر التبديل السريع ليظهر بالمسمى الرسمي الموحد `🧲 الرنين والمفراس`.
  * **حزمة الاختبارات الآلية (`tests/Feature/MriAndCtMergedStationAccessTest.php`)**:
    - بناء واجتياز اختبار ميزة يضمن قدرة فني الرنين على استعراض وفتح فحوصات الرنين والمفراس (CT & MRI) بسلاسة تامة (`1 passed, 5 assertions`).

- **المرحلة 126 (أتمتة تحديد صفة المريض بحسب العمر وإلغاء السؤال الإجباري ومزامنة بيانات الرابط ?patient_id=...):**
  * أتمتة صفة القيد بناءً على العمر: إذا كان العمر أقل من سنة يُعيّن كـ "طفل خديج (ملف مستقل)"، وإذا كان سنة فما فوق (مثل المريض 91 بعمر 29 سنة) يُعيّن كـ "الأم أو الأب (ولي أمر بالغ)".
  * إلغاء بطاقة السؤال الإجباري `صفة المريض المختار في هذا الحجز *` واستبدالها بشارة تلقائية داخل بطاقة المريض مع زر تعديل يدوي اختياري.
  * التهيئة والمزامنة الفورية للمرضى المحملين عبر الرابط `?patient_id=...` لبيانات الصفة والضمان الصحي ورقم البطاقة واقتراح اسم الطفل.
  * اجتياز كامل اختبارات الخدج بنسبة 100% (39 اختباراً، 341 تأكيداً).

- **المرحلة 125-ب (مطابقة فئات الضمان الصحي بين حجز الخدج وتسجيل المريض وجعل الفئة E للأطفال دون سن الخامسة هي الافتراضية):**
  * مطابقة القائمة المنسدلة "فئة المشمول بالضمان" في شاشة حجز الحاضنات `incubator-reservations/create` مع شاشة تسجيل المريض `patients/create`.
  * جلب الفئات ديناميكياً من جدول `health_insurance_categories` بالترتيب الأبجدي والترتيبي القياسي (`sort_order`).
  * تثبيت الفئة E كخيار افتراضي (Default) وتمييزها كفئة خاصة بالأطفال دون سن الخامسة في حجز الخدج والمعاينة السريرية والمتحكم.
  * إظهار وصف كل فئة بوضوح في شاشات تسجيل المرضى وتعديلهم وحجز الحاضنات.
  * اجتياز كامل اختبارات الخدج والمختبر والمالية بنسبة 100% (42 اختباراً، 412 تأكيداً).

- **المرحلة 124 (توحيد مسمى تحليل الدم وفيلم الدم CBC & Blood Film ومعالجة ظهوره القديم Complete blood count & Differential):**
  * **قاعدة البيانات ونموذج الفحوصات الطبية (`2026_10_28_224500_standardize_cbc_test_name_to_cbc_and_blood_film.php`)**:
    - تحديث اسم الفحص الأساسي رقم (43) المربوط به كافة البارامترات الـ 27 (WBC, RBC, HGB, PLT...) من `Complete blood count & Differential` إلى الاسم الطبي الرسمي المعتمد `CBC & Blood Film`.
    - دمج وإعادة توجيه أي طلبات طوارئ ارتبطت بالسجل المكرر (1440) إلى الفحص الأساسي (43) وتعطيل السجل المكرر لمنع الازدواجية.
    - تحديث تفاصيل طلبات الطوارئ السابقة `details` (المرفقات والنتائج المحفوظة) من الاسم القديم إلى الاسم الرسمي المعتمد.
  * **تطوير واجهة طباعة تقارير طوارئ المختبر (`resources/views/staff/requests/emergency-lab-print.blade.php`)**:
    - إضافة دالة تطبيع وتحويل ذكية `$canonicalTestName` تُعيد أي مسمى بديل أو قديم (مثل `Complete blood count & Differential` أو `Complete Blood Count (CBC)`) إلى الاسم الرسمي `CBC & Blood Film`.
    - تطبيق التطبيع على أسماء الفحوصات وترويسات المجموعات وعناوين أزرار المرفقات في الشريط العلوي (`🔬 مرفق: CBC & Blood Film`).
  * **تطوير واجهة محطة تحاليل الطوارئ (`resources/views/staff/requests/emergency-lab-show.blade.php`)**:
    - توسيع خريطة الأسماء البديلة والأكواد `$codeAliases` لتشمل كافة متغيرات CBC وربطها التلقائي بـ `cbc_3DIFF`.
    - دعم التعرّف على المرفقات ومعاينتها سواء تم حفظها بالاسم الجديد `CBC & Blood Film` أو بالاسم القديم.
  * **التحصين في الكنترولر (`StaffRequestController.php`)**:
    - دعم جلب المرفق الطبي في `emergencyLabAttachment` عبر معرف الفحص الرقمي أو اسمه المعتمد أو البديل دون أي أخطاء 404.
  * **حزمة الاختبارات الآلية (`tests/Feature/CbcTestNameStandardizationTest.php`)**:
    - بناء واجتياز اختبار ميزة يضمن ظهور المسمى الموحد `CBC & Blood Film` ومنع ظهور `Complete blood count & Differential` في تقرير الطباعة بنجاح تام (`1 passed, 4 assertions`).

- **المرحلة 123 (إصلاح وتفعيل زر تحويل المريض لطبيب آخر وتوافق MariaDB ومحطة الانتظار Doctor Referral to Another Doctor & Live Queue Integration):**
  * **معالجة استعلام الأطباء المتاحين في الكنترولر (`DoctorVisitController.php`)**:
    - استبدال قيد SQL البرمجي بدالة PHP `isWorkingOnDay($currentDay)` لعزل وحصر القائمة **بأطباء اليوم المتاحين حصراً** (استشاريين وجراحين) بدقة 100% مع ضمان توافق كامل مع MariaDB و MySQL دون إخفاء الزر.
  * **تحسين واجهة فحص المريض (`resources/views/doctors/visits/show.blade.php`)**:
    - إظهار زر `[ ⇄ تحويل للطبيب الآخر ]` وبطاقة التحويل دائماً طالما الزيارة جارية (`$visit->status == 'in_progress'`) دون إخفاء الزر إذا كان العداد فارغاً.
    - استبدال القائمة المنسدلة التقليدية بمحرك بحث واقتراحات تفاعلي ذكي فوري (*Instant Autocomplete*) يبحث بالاسم والاختصاص مع بطاقة تأكيد خضراء ومسح بنقرة واحدة.
    - معالجة شفافية قائمة الاقتراحات ومنع تداخلها البصري مع بطاقة بيانات المريض بالخلفية عبر خلفية بيضاء نقية صلبة (`#ffffff !important`) و `z-index: 99999` وظلال مرتفعة `box-shadow`.
  * **تكامل طابور الانتظار عند التحويل (`referToDoctor`)**:
    - تحويل حالة موعد المريض إلى `status = 'confirmed'` وتصفير وقت الاستدعاء وتوليد رقم دور جديد لعيادة الطبيب المحول إليه ليظهر فوراً في طابور الانتظار الأولي وتتم مناداته.
    - السماح بالتحويل إلى الاستشاريين والجراحين على حد سواء.
  * **حزمة الاختبارات الآلية (`tests/Feature/DoctorReferralToAnotherDoctorTest.php`)**:
    - بناء واجتياز اختبارين يغطيان ظهور الزر دائماً وتحديث حالة الموعد والدور عند التحويل بنجاح تام (`2 passed, 9 assertions`).

- **المرحلة 122 (معالجة خطأ طباعة أشعة الطوارئ والتحصين الشامل Emergency Radiology Print Undefined Variable Fix & Automated Coverage):**
  * **معالجة الخطأ في واجهة طباعة أشعة الطوارئ (`resources/views/staff/requests/emergency-radiology-print.blade.php`)**:
    - تصحيح استدعاء الدالة المجهولة `radiologyTypes->map(function ($type) use ($emergencyRadiology) { ... })` وتمرير المتغير عبر `use ($emergencyRadiology)` في السطر 451، مما حل خطأ `ErrorException: Undefined variable $emergencyRadiology` نهائياً.
    - إزالة كود التنبيه التجريبي والتطويري القديم (`debug info`) الذي كان يطبع شريطاً أحمر عند عدم وجود مرفقات.
  * **حزمة الاختبارات الآلية (`tests/Feature/EmergencyRadiologyPrintTest.php`)**:
    - بناء واجتياز اختبارين يغطيان طباعة تقرير أشعة الطوارئ بوجود مرفقات وبدون مرفقات بنجاح تام (`2 passed, 12 assertions`).

- **المرحلة 121 (توحيد تقرير طباعة تحاليل الطوارئ 100% مع التقرير السريري الموحد، وعزل فحص الإدرار العام G.U.E في صفحة منفصلة بالكامل Emergency Lab Print Report 100% Unification & Dedicated G.U.E Page 2 Separation):**
  * **إعادة بناء وتوحيد واجهة طباعة تقرير تحاليل الطوارئ (`resources/views/staff/requests/emergency-lab-print.blade.php`)**:
    - مطابقة بصرية وهيكلية تامة 1:1 مع `resources/views/staff/requests/print.blade.php` بألوان المستشفى الرسمية (`#005a9c` أزرق داكن، `#0284c7` أزرق سماوي) بدلاً من النمط الأحمر القديم.
    - شريط علوي موحد متكامل يحتوي على أزرار: `[ 🖨️ طباعة التقرير ]`، `[ 💬 إرسال واتساب للمريض ]`، روابط تقارير الأجهزة المرفقة، أزرار تبديل نمط الطباعة (ورق مروس Letterhead مقابل ورق أبيض كامل Full)، وضابط المسافة العلوية.
    - ترويسة رسمية ثنائية باللغتين العربية والإنجليزية تتوسطها صورة شعار المستشفى المضمنة بتقنية base64 للظهور الفوري دون أي تأخير طباعي.
    - بطاقة بيانات المريض وقسم الطوارئ السريرية ثلاثية المناطق (Patient Demographics, Order & STAT Coverage, Direct QR Code).
  * **عزل فحص الإدرار العام (General Urine Examination - G.U.E) في صفحة مستقلة (Page 2 Dedicated Architecture)**:
    - فحص وتقسيم النتائج آلياً عبر دالة الفلترة الذكية `$isGeneralUrine`.
    - عند وجود فحوصات عامة مع فحص الإدرار:
      * **الصفحة الأولى (Page 1)**: تتضمن الفحوصات العامة مع الترويسة الكاملة وبطاقة المريض وجدول النتائج والتذييل والتوقيعات الرسمية.
      * **الصفحة الثانية (Page 2 - Dedicated G.U.E Page)**: فاصل صفحات طباعي صارم (`.gue-page-break`)، ترويسة مصغرة، شريط مريض مختصر، شريط عنوان فحص الإدرار، وجدول الـ 4 أعمدة المتخصص (`INVESTIGATION / TEST NAME`, `Result`, `Reference Range`, `Status`)، ومربع التوقيعات والختم المعتمد الخاص بالصفحة الثانية.
    - عند طلب فحص الإدرار كفحص وحيد مستقل: يطبع كتقرير رئيسي متكامل على الصفحة الأولى مع الجدول الرباعي دون إنشاء صفحة ثانية فارغة.
  * **توسيع المعالجة بالكنترولر وتحديث العلاقات (`StaffRequestController.php`)**:
    - تحميل الفحوصات الفرعية للتحاليل المركبة (`labTests.subTests`, `requestTests.labTest.subTests`) لمنع استعلامات N+1.
  * **حزمة الاختبارات الآلية الشاملة (`tests/Feature/UnifiedEmergencyLabStationAndReportTest.php`)**:
    - إضافة اختبارات ميزة تغطي عزل فحص الإدرار في الصفحة الثانية عند دمجه مع فحوصات أخرى، وطباعته منفرداً على الصفحة الأولى عند عدم وجود فحوصات أخرى بنجاح تام (`8 passed, 52 assertions`).

- **المرحلة 120 (توحيد ومطابقة محطة تحاليل الطوارئ 100% مع محطة المختبر السريرية Emergency Lab Station 100% Parity with Standard Lab Station & Clinical Architecture):**
  * **إعادة بناء وتوحيد واجهة محطة تحاليل الطوارئ (`resources/views/staff/requests/emergency-lab-show.blade.php`)**:
    - مطابقة تامة 1:1 لجميع تفاصيل التصميم، البنية الهيكلية، وأدوات العمل الموجودة في `resources/views/lab/show.blade.php`.
    - شريط علوي موحد متكامل يحتوي على أزرار الإجراءات: `[ 🖨️ طباعة التقرير ]`، `[ 💬 إرسال النتيجة عبر واتساب ]`، `[ 💾 حفظ مؤقت (مسودة) ]`، `[ 🚀 اعتماد نهائي وإرسال للطبيب ]`، `[ ➕ إضافة تحاليل ]`، و `[ ⬅️ القائمة ]`.
    - قسم إلحاق التحاليل الذكي القابل للطي `#appendTestsSection`، جدول النتائج السريرية الموحد `#resultsTable` بكافة أعمدته ومؤشر الحالة الحي وحقل مرفق تقرير الجهاز بنمط الكبسولة `pill-upload-wrapper`.
    - محرك JavaScript الموحد: تقييم النتائج الفوري بالألوان، معادلة فريدوالد لباقة الدهون (Friedewald: VLDL & LDL)، وتنبيه الدهون الثلاثية >= 400 mg/dL.
  * **التحصين ومعالجة السيرفر (`StaffRequestController.php`)**:
    - تطوير دالة `completeEmergencyLab` لدعم حفظ نمط `test_results` الموحد ونمط `results` الكلاسيكي معاً ومعالجة مرفقات الفحوصات الفردية وحساب معادلات باقة الدهون آلياً على السيرفر.

- **المرحلة 119 (توحيد المحطة السريرية الجراحية للمقيم والتمريض، ومصفوفة صلاحيات التبويبات بالكامل Unified Clinical Surgery Station & Dynamic Role-Based Tab Permissions Matrix):**
  * **جدول وموديل مصفوفة صلاحيات التبويبات السريرية (`surgery_tab_permissions`)**:
    - جدول `surgery_tab_permissions` (`role_name`, `tab_key`, `access_level: hidden, read, write`).
    - إنشاء الموديل `SurgeryTabPermission` مع تعريف التبويبات الثمانية ومستويات الوصول واحتساب الصلاحيات الذكية حسب أدوار المستخدم (`getUserTabPermissions`).
  * **شاشة لوحة تحكم الإدارة لمصفوفة الصلاحيات (`SurgeryTabPermissionController.php` & `surgeries/settings/tabs.blade.php`)**:
    - جدول مصفوفي مركزي يربط الأدوار (طبيب مقيم، كادر تمريض، طبيب جراح، منسق عمليات، مدير نظام) بالتبويبات الثمانية مع قوائم منسدلة ملونة فوريّة (`🚫 مخفي`, `👁️ قراءة فقط`, `✍️ قراءة وإدخال`).
    - دعم حفظ التعديلات وإمكانية استعادة الضبط الطبي الافتراضي بنقرة واحدة وتوثيق الأنشطة عبر `UserActivityLogger`.
  * **المحطة السريرية الجراحية الموحدة (`ClinicalStationController.php` & `surgery-stations/clinical/show.blade.php`)**:
    - واجهة سريرية ذكية موحدة تدمج كافة محطات المقيم والتمريض وتتشكل تلقائياً حسب هوية الداخل.
    - التبويبات الثمانية المتكاملة:
      1. `السيرة المرضية (history)`
      2. `العلامات الحيوية والفحص السريري (vitals)` مع منحنيات Chart.js وسجل القراءات الأرشيفية.
      3. `الفحوصات والمختبر والأشعة (tests)`
      4. `الخطة الطبية وتوصيات الجراحة (plan)`
      5. `الأدوية وجدول الإعطاء (treatments)` مع إمكانية إضافة الأدوية وتسجيل إعطاء الجرعات بنقرة زر.
      6. `سجل المتابعة الدوري (follow_up)` لجولات الأطباء والتمريض وملاحظات الحالة.
      7. `رصد السوائل (fluids)` مع احتساب صافي ميزان السوائل تلقائياً (Intake & Output).
      8. `الرعاية التمريضية والتخريج (nursing_care)`
    - تحويل الروابط القديمة (`resident-station.show` و `nursing-station.show`) بسلاسة وشفافية 100% نحو المحطة الموحدة.
  * **حزمة الاختبارات الآلية الحصينة**:
    - بناء واجتياز `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` بـ 8 اختبارات ميزة ناجحة بنسبة 100% (`8 passed, 37 assertions`).

- **المرحلة 118 (إعادة هيكلة جدول وقائمة انتظار العمليات الجراحية، تبويبات أيام الأسبوع، وتخصيص الصالات والأوقات يدوياً مع إضافة صالات جديدة فورياً Operation Theaters & Realtime Manual Schedule Customization):**
  * **قاعدة البيانات وموديل صالات العمليات المخصص (`operation_theaters`)**:
    - إنشاء جدول `operation_theaters` وموديل `OperationTheater` وإضافة `operation_theater_id` في جدول `surgeries` لفصل صالات العمليات عن غرف الرقود وأسرة الإقامة.
  * **الكنترولر ومنطق التخصيص اللحظي (`SurgeryController.php`)**:
    - إضافة مسار `quickStoreTheater` لإضافة صالة عمليات جديدة فورياً عبر AJAX.
    - إضافة مسار `quickUpdateSchedule` لتعديل وحفظ صالة ووقت العملية فورياً لأي مريض بنقرة واحدة عبر AJAX.
    - دعم فلترة أيام الأسبوع وإحصائيات العمليات لكل يوم.
  * **إعادة تصميم واجهة قائمة الانتظار والجدول (`resources/views/surgeries/waiting.blade.php`)**:
    - تبويبات أيام الأسبوع (السبت إلى الجمعة) في الأعلى.
    - جدول نقي وواضح لعمليات اليوم المختار بأعمدة: المريض، العملية، الجراح، الصالة المخصصة (قابلة للتعديل)، الوقت (قابل للتعديل مع مؤقت حي للعملية الجارية)، حالة المسار السريري، والإجراءات المباشرة.
    - نافذة مودال سريعة لتعديل الصالة والوقت يدوياً مع خيار إضافة اسم صالة جديدة من نفس النافذة فورياً بدون مغادرة الصفحة.
  * **الاختبارات الآلية الحصينة**:
    - بناء واجتياز `tests/Feature/SurgeryWaitingListFlightBoardTest.php` بـ 8 اختبارات ميزة ناجحة بنسبة 100% (`8 passed, 28 assertions`).
- **المرحلة 117 (الحساب الآلي الذكي لمعادلات باقة الدهون Lipid Profile - Friedewald Formula: LDL & VLDL في واجهة المختبر والسيرفر مع التنبيه السريري والتحصين الشامل):**
  * فحص VLDL وباقة الدهون المركبة، حساب $VLDL = TG/5$ و $LDL = TC - HDL - TG/5$، وتنبيه كهرماني عند $TG \ge 400$، واجتياز `LipidProfileAutoCalculationTest` (`4 passed, 25 assertions`).

- **المرحلة 115 (مطابقة وتوحيد واجهة دفع الطوارئ 100% مع واجهة الكاشير الرئيسية، تحصين نسب فئات الضمان، وإرجاع وصولات المريضة فاطمة Emergency Cashier Form 100% Parity with Request Payment, Strict Category Copay Lockdown & Unpaid Receipt Reversal):**
  * **إرجاع وتصحيح وصولات المريضة فاطمة صباح (الفئة H) محلياً وعلى هوستنجر**:
    - إرجاع قيد ودفع خدمات الطوارئ رقم #1191 إلى "غير مسدد" بمبلغ 0 د.ع للمريض وتغطية 100% للضمان (75,000 د.ع).
    - إرجاع وتصحيح وصل التحاليل رقم #1187 إلى "غير مسدد" مع إعادة احتسابه وفق نسبة الـ 25% الرسمية المعتمدة للفئة H (حصة المريض: 7,750 د.ع، وحصة الضمان: 23,250 د.ع).
  * **إعادة تصميم واجهة دفع الطوارئ بالكامل (`resources/views/cashier/emergency-payment-form.blade.php`)**:
    - مطابقة 100% مع تصميم كاشير الفحوصات `request-payment/567` (رأس أزرق ملكي، بطاقتان علويتان لبيانات الحالة والمريض، شريط فئة الضمان، 3 بطاقات إحصاء مالي مباشر، جدول تفصيلي، وقفل خانة المبلغ `readonly` مع وسم الحماية الآلية).
  * **التحصين البرمجي الصارم بالكنترولر (`CashierController.php` & `EmergencyController.php`)**:
    - فرض دالة `getCopayPercentageFor('emergency')` ومنع أي تعديل يدوي، وتوجيه الدفع المسدد تلقائياً للوصل بدلاً من التحذير.
  * **حزمة الاختبارات الآلية الحصينة**:
    - بناء واجتياز `tests/Feature/EmergencyCashierCopayLockTest.php` واجتياز كامل اختبارات الكاشير الـ 21 بنجاح (`21 passed, 165 assertions`).
- **المرحلة 110 (الحظر الصارم للحجز المكرر لنفس اليوم، والقفل السريري الإلزامي لإنهاء الزيارة بانتظار الفحوصات Strict Same-Day Duplicate Booking Prevention, Hard Validation Block & Mandatory Visit Completion Clinical Gate Pending Test Results):**
  * **منع الحجز المكرر لنفس اليوم نهائياً**: منع تسجيل أي حجز للمريض عند نفس الطبيب في نفس اليوم نهائياً مع إرجاع رسالة خطأ صريحة تنبه الموظف بأن المريض مسجل اليوم ويدخل للعيادة مباشرة.
  * **قفل إنهاء الزيارة عند وجود فحوصات معلقة**: قفل زر "إنهاء الزيارة" في الواجهة والباك إند ومنع إقفال الزيارة ما دامت هناك تحاليل أو أشعة معلقة لم تصدر نتائجها، مع إبراز زر `[ ⏳ تعليق بانتظار الفحوصات ]` لمتابعة باقي المرضى.
  * **اجتياز الاختبارات الآلية الحصينة**: اجتياز اختبارات tests/Feature/SameDayRecheckBookingTest.php بنجاح كامل (`3 passed, 10 assertions`).
- **المرحلة 109 (حماية كشفية نفس اليوم من التكرار، وأتمتة مراجعة النتائج المجانية، ودمج فحوصات اليوم في محطة الطبيب، وتنظيف قيود هوستنجر المكررة Same-Day Duplicate Consultation Prevention, Automatic Free Results Recheck, Workstation Requests Aggregation & Hostinger Duplicate Cleanup):**
  * تنظيف السجلات والزيارات المكررة على سيرفر هوستنجر والمحلي ليوم 2026-10-22 وتطابق الحركات المالية تماماً عند 65 حركة سليمة.
  * رفع حد ذاكرة PHP إلى 512M وحل خطأ 500 نهائياً.
  * دمج وتجميع فحوصات اليوم في محطة الطبيب تلقائياً.
- **المرحلة 89 (تخصيص وعزل باقات تحاليل الخدج الديناميكية التخصصية NICU-Specific Lab Packages & Departmental Isolation):**
  * **العزل الصارم لباقات الخدج عن العيادات والاستشارية (`department = 'nicu'`)**:
    - إضافة عمودي `department` (indexed string) و `is_public` (boolean) إلى جدول `user_lab_test_groups` عبر مايجريشن `2026_10_26_113000_add_department_to_user_lab_test_groups_table.php`.
    - تقييد استعلام محطة الخدج `NicuController::show` ليعتمد حصراً على `department = 'nicu'` (باقات الخدج العامة أو الخاصة بأطباء الخدج)، مما منع ظهور كافة باقات الاستشارية والبالغين (كالجلدية والسكري والغدة والروماتزم) في محطة الخدج نهائياً.
    - تقييد استعلام زيارات الاستشارية `DoctorVisitController::show` عبر Scope `consultant()` ليقتصر على الباقات العامة والاستشارية دون تداخل مع باقات الخدج.
  * **إنشاء الباقات القياسية الرسمية للخدج (`NicuLabTestGroupsSeeder`)**:
    1. باقة دخول روتيني لحديثي الولادة (9 تحاليل: CBC, ABO/Rh, TSB, BILI, Glucose, Calcium).
    2. باقة يرقان وصفار الخدج (7 تحاليل: TSB, Direct, Indirect, CBC, ABO/Rh).
    3. باقة غازات الدم والحموضة ABG (3 تحاليل: ABG, VBG, Lactate).
    4. باقة مسح إنتان الخدج Sepsis Screen (6 تحاليل: CBC, HS-CRP, Blood C/S, Blood Film).
    5. باقة وظائف الكلى والأملاح للخدج (5 تحاليل: Urea, Creatinine, Na, K, Calcium).
  * **تطوير واجهة إدارة الباقات والمفضلات (`/lab-tests/groups`)**:
    - شريط تبويبات فلترة علوي: (كافة الباقات، باقات قسم الخدج NICU، باقات العيادات والاستشارية).
    - نموذج إنشاء وتعديل ذكي يتيح تحديد القسم وتعيين الباقة كباقة عامة للقسم (Public Group) للأدمن.
    - ربط مباشر في محطة الخدج: `[ 👶 إدارة باقات الخدج ]` يفتح مباشرة مفلترة على الخدج `?department=nicu`.
  * **الاختبارات الآلية وضمان الجودة**:
    - اجتياز اختبار العزل `test_lab_test_groups_department_isolation_between_nicu_and_consultant` واختبار العرض `test_nicu_workstation_renders_user_lab_test_groups_matching_consultant_doctor`.
    - اجتياز 36 اختباراً في `NicuWorkflowTest` (295 تأكيداً).
    - اجتياز 4 اختبارات في `NicuLabRadiologyCashierWorkflowTest` (82 تأكيداً).
    - المجموع: **40 اختباراً ناجحاً بنسبة 100% (377 تأكيداً)**.
- **المرحلة 88 (اعتماد ومزامنة خدمات الطوارئ الرسمية 96 خدمة وتصحيح الأسعار كاش وضمان واستبعاد الخدمات المكررة والقديمة من واقع ملف الإكسل المعتمد Approved Emergency Services Excel Full Sync & Pricing Reconciliation):**
  * **المزامنة الشاملة لجدول خدمات الطوارئ (`emergency_services`)**:
    - قراءة واعتماد ملف الإكسل المعتمد `emergncy final.xlsx` الذي يحتوي على 96 خدمة طوارئ منقحة.
    - إنشاء ملف بيانات رسمي ثابت [database/seeders/approved_emergency_services_data.php](file:///C:/get/hospital-system/database/seeders/approved_emergency_services_data.php) ومايجريشن آلي قابل للنشر على سيرفر هوستنجر بنقرة واحدة [database/migrations/2026_10_20_110000_sync_emergency_services_from_approved_excel.php](file:///C:/get/hospital-system/database/migrations/2026_10_20_110000_sync_emergency_services_from_approved_excel.php).
  * **تصحيح الأسعار الصفرية وحالات الضمان والكاش**:
    - تصحيح الخدمات التي كانت مسجلة بـ 0 د.ع في الداتابيز (مثل: إدخال أنبوب صدر Chest tube بـ 500,000 كاش وضمان، أجور التجبيس للأطراف السفلى بـ 50,000 كاش و 40,000 ضمان، خدمات نقل الإسعاف الداخلي 25,000 والخارجي 50,000، وإعطاء الدم تحت إشراف طبيب مع المطابقة بـ 75,000).
    - تحديث أسعار الكاش وأسعار الضمان الصحي وحالات التغطية لـ 20 خدمة مختلفة.
  * **إدراج الخدمات المعتمدة الجديدة**:
    - إضافة خدمة `#114`: `أجور التجبير مع كافة المستلزمات بنوعيه (fiberglass or plaster of paris) نصفي او كامل - الاطراف العليا` (كاش: 50,000 د.ع، ضمان: 30,000 د.ع، مشمول بالضمان).
    - ضبط خدمة `#66`: `زرق إبرة خارجي` بسعر 25,000 د.ع.
  * **التعطيل الآمن للخدمات القديمة والمكررة (15 خدمة)**:
    - إيقاف تفعيل (`is_active = 0`, `is_hi_active = 0`) للخدمات الـ 15 غير الموجودة بملف الإكسل المعتمد (مثل تكرارات صوندة المعدة، والخدمات غير المعرفة، وتكرارات الغرز)، مما يمنع ظهورها نهائياً في شاشات الاستقبال ومحطة كشف الطوارئ مع صيانة السجلات والفواتير التاريخية المرتبطة دون كسر أي مفاتيح أجنبية.
  * **تحديث السيدر الرسمي والاختبارات الآلية الحصينة**:
    - ترقية [database/seeders/EmergencyServicesSeeder.php](file:///C:/get/hospital-system/database/seeders/EmergencyServicesSeeder.php) ليعتمد على مصفوفة البيانات الرسمية.
    - بناء واجتياز اختبار الميزة [tests/Feature/EmergencyServicesApprovedSyncTest.php](file:///C:/get/hospital-system/tests/Feature/EmergencyServicesApprovedSyncTest.php) واجتياز كامل حزمة اختبارات الطوارئ: `33 passed (171 assertions)`.
- **المرحلة 81 (منظومة التحكم اللحظي والذكي بتشغيل وإطفاء الفحوصات المختبرية ومطابقة الضمان الصحي Lab Tests Instant Activation & Smart Insurance Bulk Controls):**
  * **التحكم المفرد اللحظي (Single Quick Toggle AJAX)**:
    - إضافة مفتاحي تبديل بأسلوب iOS Switch لكل فحص مختبري في شاشة الكتالوج `/lab-tests`:
      1) **الحالة العامة (`is_active`)**: تفعيل أو إطفاء الفحص فورياً عبر AJAX. عند إطفاء الفحص يختفي كلياً وفورياً من كافة محطات النظام (عيادات الأطباء في الاستشارية، الاستعلامات، الطوارئ، العمليات، وقوائم البحث والباقات).
      2) **الضمان الصحي (`is_hi_active`)**: تفعيل أو إيقاف الفحص عن مظلة الضمان الصحي فورياً عبر AJAX مع إظهار شارة مميزة إذا كان الفحص مسعراً بالضمان أو غير مسعر.
    - استجابة لحظية بدون الحاجة لإعادة تحميل الصفحة إطلاقاً مع إشعار توست خفيف وتحديث الشارات وحالة الصف.
  * **التحكم الكلي الذكي والجماعي (Smart Bulk Actions & Floating Bar)**:
    - زر ذكي بنقرة واحدة: `[ ⚡ إطفاء كافة الفحوصات خارج الضمان ]` يتيح إطفاء جميع الفحوصات غير المشمولة بتسعير الضمان (المسعرة بـ 0 أو null) من كافة محطات النظام كلياً أو إيقافها عن الضمان فقط عبر نافذة تأكيد منبثقة.
    - زر ذكي بنقرة واحدة: `[ 🛡️ تفعيل كافة المسعرة بالضمان ]` لإعادة تفعيل وتشغيل جميع الفحوصات المعتمدة والمسعرة بالضمان في المحطات دفعة واحدة.
    - مربعات اختيار جماعية (`Checkboxes`) مع شريط عائم سفلي ذكي يظهر عند تحديد فحص أو أكثر للتحكم الجماعي (تفعيل عام، تعطيل عام، تفعيل للضمان، إيقاف عن الضمان).
  * **تطوير الواجهة والإحصائيات والفلاتر المتقدمة**:
    - بطاقات إحصائيات سريعة أعلى الصفحة (إجمالي الفحوصات، الفحوصات النشطة عاماً، المعطلة، والمشمولة بالضمان).
    - فلترة جديدة دقيقة للفحوصات: (مشمولة بالضمان فقط / خارج الضمان فقط / الكل) مع اختيار عدد العناصر بالصفحة (25 إلى 500).
  * **تطوير المتحكم والمسارات**:
    - تطوير `LabTestController@quickToggle` للتبديل الفوري عبر AJAX.
    - تطوير `LabTestController@bulkToggleStatus` للتحديث الجماعي للفحوصات المحددة.
    - تطوير `LabTestController@bulkDisableNonInsurance` للتحكم الذكي الكلي بالفحوصات خارج الضمان.
    - تسجيل المسارات في `routes/web.php` تحت بادئة `lab-tests`.
  * **الاختبارات الآلية الحصينة**:
    - بناء واجتياز اختبار الميزة `tests/Feature/LabTestActivationAndBulkControlTest.php` بـ 5 اختبارات ميزة تغطي التبديل الفردي، الجماعي، الكلي، وحجب الفحوصات المعطلة من محطات النظام (`5 passed, 20 assertions`) واجتياز حزمة المختبر كاملة (`41 passed, 179 assertions`).
- **المرحلة 75 (إعادة هندسة وتصميم استمارة تقرير الطوارئ الموحد ليكون في ورقة واحدة على وجهين مطابقة للتصميم القياسي Standard Duplex Two-Page Emergency Medical Report Form):**
  * إعادة بناء وتصميم واجهة التقرير الطبي بالكامل `resources/views/emergency/medical-report.blade.php` ليكون في ورقة واحدة على وجهين (Duplex A4 Front & Back):
    - **الوجه الأول (Page 1 of 2)**:
      * ترويسة المستشفى وشريط العنوان الكحلي الداكن مع رقم التقرير `EMR-XXXXXX` وشارة النسخة.
      * جدول البيانات الشخصية وتفاصيل الدخول المكون من 10 خانات مرتبة (طريقة الوصول، رقم المراجعة، رقم ملف المريض، الجنس/العمر، التاريخ والوقت، الطبيب المناوب، الشكوى، التصنيف الفرزي Triage، الجهة المحولة، العنوان ورقم الهاتف).
      * شريط العلامات الحيوية عند الاستقبال (مستوى الوعي GCS، تشبع الأكسجين، الحرارة، التنفس، النبض، ضغط الدم).
      * صندوق الشكوى الرئيسية وصندوق تاريخ المرض الحالي (HPI).
      * صندوق التحاليل المختبرية والشعاعية وصندوق العلاج ومحاليل الطوارئ.
      * جدول التشخيص السريري والخدمات العلاجية وتوقيتها واسم الطبيب.
      * ترقيم الصفحة في الأسفل: `Page 1 of 2`.
    - **الوجه الثاني (Page 2 of 2)**:
      * تكرار ترويسة المستشفى وشريط العنوان للرسمية والتوثيق القانوني عند تصوير الوجه الخلفي.
      * صندوق الملاحظات الطبية - العدلية (Medico-Legal Notes) ⚖️ وصندوق الملاحظات الإضافية 📄.
      * كتلة التوثيق الثلاثية الأفقية: (صندوق اسم وتوقيع الطبيب المفرغ بشفافية 100% مع التاريخ + الختم الدائري لقسم الطوارئ + رمز الـ QR Code للتحقق الإلكتروني).
      * صندوق القرار الطبي النهائي عند الخروج وتوصيات المتابعة (التوصيات، الوجهة النهائية، الحالة عند الخروج).
      * ترقيم الصفحة في الأسفل: `Page 2 of 2`.
  * تطبيق الهوية البصرية والألوان الزرقاء الطبية المعتمدة (Navy & Cyan Blue: `#005a9c` و `#e0f2fe`).
  * ضبط معايرة الطباعة التلقائية على الوجهين بدون انزلاق لأي صفحة ثالثة.
  * اجتياز كامل للاختبارات الآلية: `tests/Feature/EmergencyMedicalReportTest.php` (3 passed, 19 assertions) وحزمة الطوارئ كاملة (31 passed, 160 assertions).
- **المرحلة 74 (منظومة استمارة تقرير الطوارئ الموحد - الخطوة الأولى: الحقول السريرية والعدلية وواجهات الإدخال Standard Emergency Medical Report Fields & Workstation Input Integration):**
  * إضافة حقول استمارة التقرير الطبي المعتمد في جدول `emergencies` عبر مايجريشن `2026_10_18_090000_add_standard_report_fields_to_emergencies_table.php`:
    - `arrival_mode`: طريقة الوصول (إسعاف المستشفى، وصول شخصي، سيارة خاصة، إسعاف خارجي).
    - `referred_from`: الجهة المحولة إن وجدت.
    - `gcs`: مستوى الوعي (Glasgow Coma Scale - القيمة الافتراضية `15/15`).
    - `hpi`: تاريخ المرض الحالي (History of Present Illness).
    - `medico_legal_notes`: الملاحظات الطبية - العدلية ⚖️.
    - `discharge_destination`: الوجهة النهائية عند الخروج (خروج للمنزل، تحويل للاستشارية، تحويل للرقود، تحويل للعمليات).
    - `discharge_recommendations`: خطة وتوصيات المتابعة الطبية.
  * تحديث نموذج الطوارئ `app/Models/Emergency.php` بإضافة الحقول السبعة إلى `$fillable`.
  * تطوير المتحكم `app/Http/Controllers/EmergencyController.php`:
    - دعم واستقبال `arrival_mode` و `referred_from` و `gcs` عند إنشاء الحالة `store`.
    - دعم وتحديث `gcs` في تحديث العلامات الحيوية `updateVitals`.
    - دعم وتحديث `symptoms`, `hpi`, `medico_legal_notes`, `notes` في التحديث الطبي والسريري `updateMedical`.
    - دعم واعتماد `discharge_destination` و `discharge_recommendations` في إجراءات التخريج `discharge`.
  * تحديث واجهة الاستقبال `resources/views/emergency/create.blade.php`: إضافة قائمة طريقة الوصول مع أيقونات معبرة، وحقل الجهة المحولة.
  * تحديث محطة كشف الطوارئ `resources/views/emergency/show.blade.php`:
    - إضافة حقل مستوى الوعي (GCS) في تبويب العلامات الحيوية مع زر ضبط `طبيعي (15/15)`.
    - إضافة حقول الشكوى، تاريخ المرض الحالي (HPI)، التشخيص، والملاحظات الطبية العدلية ⚖️، والملاحظات الإضافية في تبويب التشخيص والخدمات.
  * بناء وتشغيل الاختبار الآلي: `tests/Feature/EmergencyStandardReportDataTest.php` بـ 4 اختبارات ناجحة (15 تأكيداً برمجياً)، واجتياز كامل حزمة اختبارات الطوارئ: `31 passed (160 assertions)`.
- **المرحلة 68 (تفكيك الطلبات السريرية للخدج وإضافة سجل إعطاء العلاج MAR المستقل NICU Workstation Decomposition & Nursing MAR):**
  * **تفكيك الطلبات السريرية إلى 3 محطات مستقلة بمحطة الخدج (`nicu/reservations/show`)**:
    - محطة المختبر والتحاليل بباقات سريعة وصينية تحاليل وبحث فوري.
    - محطة الأشعة والسونار بباقات صور حديثي الولادة الشائعة وصينية فحوصات.
    - محطة الوصفة والعلاجات الدوائية E-Prescriptions مع طباعة رسمية بحجم A5.
  * **سجل إعطاء العلاج MAR التمريضي (`nicu_medication_administrations`)**:
    - توثيق جرعات الأطفال التمريضية بشكل مستقل تماماً عن الصيدلية والمخزون.
  * **البحث التفاعلي اللحظي عن الأطباء الاستشاريين واختيار الطبيب بـ Select2**:
    - مربع بحث حي ذكي عن أطباء المستشفى في الاستشارة الداخلية وإخفاء التخصص الدقيق إلا في الاستشارة الخارجية.
- **المرحلة 67 (البحث الحي المباشر عن المرضى لحجز الحاضنات وإلغاء شرط العمر وتخصيص اسم الطفل الخديج Incubator Patient Live Search & Dynamic Baby Naming):**
  * إضافة نقطة نهاية `/incubator-reservations/search-patients` للبحث اللحظي السريع في كافة المرضى بالاسم ورقم الهاتف بدون قيود، مع استخراج بيانات بطاقة الضمان الصحي والفئة.
  * إلغاء شرط العمر (`age < 1`) من شاشة الحجز ودالة الحفظ للسماح بتسجيل الحجز رسمياً باسم الأم أو المسؤول.
  * إضافة حقل اختياري لاسم الطفل الخديج (`baby_name`) وتوليده تلقائياً بصيغة `طفل [اسم الأم]` إذا تُرِك فارغاً.
  * واجهة بحث عصرية تفاعلية ببطاقة مريض مختار وزر تغيير المريض لتجربة استخدام فائقة السرعة والسهولة.
  * اجتياز 22 اختبار ميزة شامل بنجاح 100% (152 assertions).
- **المرحلة 66 (تبسيط وتوحيد أنواع الحاضنات الفيزيائية إلى نوعين: حاضنة وكبسولة علاج ضوئي Incubator Device Types Simplification):**
  * توحيد الأنواع الفيزيائية للحاضنات وحصرها في نوعين فقط: «حاضنة» (`normal`) و«كبسولة علاج ضوئي» (`capsule`)، ونقل الأنواع الخدمية (أكسجين، فوتوثيرابي) لتدار ضمن باقات الخدمات السريرية ديناميكياً.
  * تنفيذ هجرة قاعدة بيانات لتحويل كافة الحاضنات ذات الأنواع الملغاة في النظام إلى نوع `normal`.
  * تحديث نماذج ومتحكمات وواجهات إضافة وتعديل وعرض وفلترة الحاضنات وحصرها في الخيارين المعتمدين.
  * اجتياز 21 اختبار ميزة شامل بنجاح 100% (140 assertions).
- **المرحلة 65 (تسعيرة الضمان الصحي HIA ومنظومة شرائح العمر الحملي للخدج وحسابات التحمل NICU Health Insurance & Gestational Tiers):**
  * إضافة تسعيرات الضمان الصحي المعتمدة لباقات الخدج وأسماء المطالبات الرسمية، ومحرك احتساب شرائح العمر الحملي الأربعة والاستثناءات الوزارية ونسب التحمل.
  * استمارة حجز ذكية تكيفية ومعاينة حية لشريحة التحمل وكشف حساب تراكمي شفاف.
- **المرحلة 64 (إدراج حقول توجيهات وملاحظات الطبيب للمختبر والأشعة في محطة الطبيب وربطها الكامل بمحطات المختبر والأشعة Doctor Clinical Notes Input & Lab/Radiology Stations Full Integration):**
  * إضافة حقلين نصيين أنيقين في محطة فحص الطبيب `doctors/visits/show`: أحدهما في تبويب طلبات المختبر `[ 📝 ملاحظات وتوجيهات الطبيب للمختبر ]`، والآخر في تبويب الأشعة والتصوير `[ 📝 ملاحظات وتوجيهات الطبيب لقسم الأشعة ]`.
  * استقبال وتخزين الملاحظات في تفاصيل الطلب `$details['doctor_notes']` عبر `DoctorVisitController::storeRequest`.
  * إبراز بطاقة كهرمانية تنبيهية في شاشة فحص وتحرير نتائج المختبر `lab/show`، ووسام تنبيهي في جدول طابور المختبر `lab/index`.
  * إبراز وسام تنبيهي لملاحظات الطبيب في جدول طابور الأشعة `radiology-staff/index` لربطها بشاشة فحص الأشعة `radiology-staff/show`.
  * اختبارات آلية شاملة (`DoctorRequestClinicalNotesTest`: 2 passed, 18 assertions).
- **المرحلة 63 (عرض ملاحظات وتوجيهات الطبيب المحول لموظف وفني الأشعة Doctor Notes in Radiology Workstation):**
  * إضافة Accessor رسمي `doctor_notes` بموديل `Request` لاستخراج وقراءة الملاحظات المخزنة في `details['doctor_notes']` أو `details['notes']` أو `details['description']`.
  * إدراج الملاحظات في الـ JSON لطابور الأشعة `RadiologyStaffController::queueStatus`.
  * تصميم صندوق ملاحظات كهرماني جذاب أعلى شاشة فحص وتقرير الأشعة `radiology-staff/requests/{id}/show` يُبرز توجيهات الطبيب بوضوح.
- **المرحلة 62 (منظومة رفع وتفريغ التواقيع اليدوية للأطباء تلقائياً والمعاينة الحية للشفافية وربط التواقيع بتقارير الطوارئ Doctor Signature Auto-Crop & Alpha Channel):**
  * محرك المعالجة والتفريغ التلقائي `app/Services/SignatureProcessingService.php`: عزل خلفية وبياض وظلال الورقة وتحويلها لشفافية ألفا 100% مع تنعيم الحواف وتعزيز لون الحبر وقص الهوامش الزائدة تلقائياً.
  * بطاقة التوقيع اليدوي المعتمد في شاشة تعديل الطبيب `doctors/edit` مع معاينة حية بالـ Canvas، شريط حساسية التعتيم، وحفظ فوري عبر AJAX.
  * إبراز التوقيع المعتمد في شاشة تفاصيل الطبيب `doctors/show`.
  * ربط تقرير الطوارئ بتواقيع الأطباء المعالجين ديناميكياً (`DoctorSignatureTest`: 3 passed, 13 assertions).
- **المرحلة 61 (استدعاء المريض الاستثنائي/الحرج وثبات تسلسل الطابور ودعم طلبات الضمان المختلطة بالكاشير Out-of-Turn Calling & Cashier Mixed Insurance Requests):**
  * استرجاع 150 فحص مختبر و305 فحص أشعة قديمة على سيرفر هوستنجر مع أخذ نسخة احتياطية سالمة.
  * تمكين الطبيب من تقديم واستدعاء أي مريض من الطابور (`callSpecific`) مع ثبات ترقيم وأدوار باقي المرضى المنتظرين وتوليد `queue_number` تلقائي لا يتغير.
  * إلغاء حظر تسديد الكاشير عند وجود فحوصات مشمولة وأخرى غير مشمولة بالضمان في نفس الطلب، واعتماد التوزيع الذاتي (100% كاش للمريض لغير المشمول وتطبيق التغطية على المشمول).
  * تعديل تسعيرة `Chest Ap` على هوستنجر للضمان (`DoctorQueueCallSpecificTest`: 1 passed, 16 assertions).
- **المرحلة 60 (فصل المبالغ المسترجعة للمرضى في كارت خزينة مستقل ومطابقة السيرفر السحابي Dedicated Treasury Refunds KPI):**
  * إضافة تصنيف `refund` بجدول الحركات المالية وتوجيه استرجاعات الدفع السالبة لها تلقائياً في `PaymentObserver`.
  * استحداث كارت «المبالغ المسترجعة للمرضى» باللون الكهرماني في واجهة الخزينة المركزية، وفصلها عن المصروفات التشغيلية.
- **تأسيس نظام الذاكرة المستدامة والتحميل الفوري (Zero-Latency Core Blueprint):**
  - إنشاء مهارة الذاكرة الرسمية [`.agents/skills/hospital-memory/SKILL.md`](.agents/skills/hospital-memory/SKILL.md) لتتعرف عليها أدوات الذكاء الاصطناعي كـ Native Skill.
  - إنشاء قاعدة التحميل التلقائي المباشر [`.agents/rules/hospital-core.md`](.agents/rules/hospital-core.md) التي تُحقن تلقائياً في سياق كل محادثة دون أي استهلاك لوقت القراءة أو فحص الملفات.
  - أرشفة السجلات القديمة (المراحل 1-30) في [docs/SESSION_ARCHIVE.md](docs/SESSION_ARCHIVE.md) وخفض حجم [AGENTS.md](AGENTS.md) بنسبة 82% (من 198KB إلى 34KB).
- **المرحلة 59 (منظومة الحد من تكرار الحجز وتنبيهات الحجوزات النشطة للكشفية والسونار وتأكيد الموظف ومنع النقر المزدوج):**
  * **الخلفية والدافع**: معالجة مشكلة تكرار الحجز للمريض في نفس اليوم بالخطأ (سواء لكشفية الطبيب أو السونار الخارجي المباشر)، مع الحفاظ على مرونة النظام وإمكانية حجز موعد ثانٍ مقصود للمريض في حال رغبته الصريحة بعد تنبيه وتأكيد الموظف.
  * **منطق فحص النشاط والحجوزات غير المكتملة**:
    - اعتبار الحجز نشطاً وغير مكتمل إذا كان `status not in ('completed', 'cancelled')` لتاريخ اليوم (`appointment_date = today()` للمواعيد، أو `created_at = today()` لطلبات السونار المباشرة)، بصرف النظر عن حالة الدفع (سواء دفع في الكاشير ولم يدخل بعد للغرفة، أو ما زال بانتظار الدفع).
    - إذا كان الحجز السابق مكتملاً (`status = 'completed'`) أو ملغياً (`status = 'cancelled'`)، يُسمح بحجز موعد جديد دون أي اعتراض.
  * **مسار التحقق اللحظي عبر AJAX (`/inquiry/check-active-booking`)**:
    - فحص فوري وسريع عند اختيار الطبيب في الاستعلامات، أو عند تحديد فئة السونار، وإرجاع بيانات الحجز النشط (اسم الطبيب، رقم التسلسل، حالة الطابور، وحالة الدفع).
  * **الواجهة التفاعلية وتجربة المستخدم في الاستعلامات (`/inquiry/create`)**:
    - **صندوق تنبيه فوري أصفر جذاب (`duplicateDoctorWarningBox` و `duplicateUltrasoundWarningBox`)**: يظهر مباشرة تحت اختيار الطبيب أو نوع السونار فور وجود حجز نشط سابق مع تفاصيل التسلسل والحالة.
    - **نافذة تأكيد مودال منبثقة (`#confirmDuplicateModal`)**: عند الضغط على «إنشاء الطلب»، يتم اعتراض الإرسال وإظهار نافذة تأكيد للموظف توضح وجود حجز نشط وتطلب تأكيده للمتابعة كحجز إضافي جديد.
    - **تثبيت قيد التدقيق الرقابي**: عند تأكيد الموظف للمتابعة (`confirm_duplicate_booking = 1`)، يتم حفظ الحجز مع إضافة ملاحظة تدقيق واضحة في الملاحظات: `حجز إضافي مؤكد من الاستعلامات`.
    - **منع النقر المزدوج (Submit Debounce)**: تعطيل زر الحفظ فور النقر وإظهار مؤشر تحميل دوار لمنع الإرسال المتكرر السريع.
  * **الاختبارات الآلية**: إنشاء واجتياز اختبارات الميزة `tests/Feature/InquiryDuplicateBookingTest.php` بـ 5 اختبارات كاملة ناجحة (14 assertions).
- **المرحلة 58:** حجب وصولات القبض للمواعيد الملغاة والمسترجعة من فلتر وصولات القبض والتقرير المالي الرسمي بفورمة المستشفى A4 (`ConsultantFinancialMovementsTest`: 8 passed, 42 assertions).
- **المرحلة 54:** نظام التقرير الطبي لحالة الطوارئ، التوقيع الرقمي للطبيب (د. مهند إسماعيل)، الطباعة بنسختين، والتحقق عبر QR Code:
  * **تقرير طبي رسمي عالي الدقة (A4 Portrait)**: تصميم استمارة تقرير طبي رسمي معتمد لحالات الطوارئ بمقاس A4 يطابق ترويسة وهيكلية مستشفى الكفاءات الأهلي مع خلفية مائية خفيفة لشعار المستشفى وإطار مزدوج أنيق.
  * **تضمين التوقيع اليدوي الحقيقي للطبيب**: استخلاص وتنظيف ومعالجة التوقيع الخطي الأزرق لدكتور الطوارئ (د. مهند إسماعيل) بقناة ألفا شفافة وتخزينه في `public/images/signatures/doctor_244.png`، مع إضافة حقل `signature` في جدول `doctors` وإظهار التوقيع الرسمي في أسفل التقرير.
  * **نظام الطباعة المزدوجة (Dual-Copy Switching)**: خيار تبديل فوري بين:
    1. نسخة المريض (`نسخة المريض الرسمية` لتقديمها للجهات الطبية والاستشارية).
    2. نسخة الإدارة والأرشيف (`نسخة الإدارة والأرشيف` للحفظ الطبي والتدقيق القانوني).
    3. طباعة النسختين معاً بضغطة زر واحدة مع فاصل صفحات تلقائي (`page-break-after: always`).
  * **التحقق الرقمي العام عبر رمز الاستجابة السريعة (QR Code Verification)**: توليد رمز QR بتقنية Canvas و SVG يشير إلى صفحة تحقق عامة ومحمية (`route('emergency.verify-report', $emergency)`) تعمل بدون تسجيل دخول لتمكين المرضى والجهات الخارجية من فحص صحة ومطابقة التقرير السريري مع سجلات المستشفى.
  * **أزرار سريعة للوصول للتقرير**: إضافة زر بارز في ترويسة محطة الفحص (`emergency/show.blade.php`) وزر طباعة سريع بجانب كل حالة في جدول الطوارئ العام (`emergency/index.blade.php`).
  * **الاختبارات الآلية**: إنشاء واجتياز `tests/Feature/EmergencyMedicalReportTest.php` (3 passed, 18 assertions)، واجتياز كامل اختبارات الطوارئ: 19 passed, 102 assertions.
- **المرحلة 53:** تنظيم وتوحيد أزرار إجراءات الطوارئ وإضافة زر توثيق «تحويل للاستشارية» (`discharge_type: transferred_to_consultation`):
  * إزالة التشتت البصري وازدحام الترويسة في محطة الفحص (`emergency/show.blade.php`) بجمع أزرار التحويل والتخريج المتناثرة في قائمة منسدلة موحدة أنيقة **«إجراءات وتحويل وتخريج الحالة ▾»**.
  * تبسيط جدول الطوارئ العام (`emergency/index.blade.php`) بالكامل وحذف كافة الأزرار والقوائم المنسدلة المكررة، واقتصاره على 6 أعمدة أساسية (#، المريض، الحالة، الطبيب، وقت الدخول) مع زر وصول مباشر وواضح **[ 🩺 فتح ملف الكشف ]**، وحصر كافة القرارات السريرية والتحويلات داخل المحطة الموثقة.
  * إضافة خيار توثيقي وسريري جديد لتخريج المريض: **«تحويل للاستشارية»** (`transferred_to_consultation`) يوثق إحالة المريض للعيادة الخارجية مع تسجيل وقت الخروج وإنهاء إشغال سرير الطوارئ.
  * تحديث قاعدة البيانات بمايجريشن (`2026_10_15_150000_update_discharge_type_in_emergencies_table.php`) لتوسيع حقل `discharge_type` إلى `varchar(50)`.
  * إضافة أوسمة الحالات والأزرار في الجدول العام ومحطة الفحص، وتوسيع اختبارات الميزة (`EmergencyWorkflowTest`: 9 passed, 39 assertions، وإجمالي اختبارات الطوارئ: 16 passed, 84 assertions).
- **المرحلة 52:** تطوير وتكامل استشارات الطوارئ، السجل الطبي التراكمي، والحماية المالية للخدمات المسددة:
  - احتساب وتوزيع أتعاب استشارة الطوارئ (50,000 د.ع) بحصة 40,000 د.ع للطبيب و 10,000 د.ع للمستشفى (80% / 20%)، مع تسجيل مستحقات `DoctorDue` وحساب `DoctorFinancialAccount`، وإظهار اسم الطبيب الاستشاري في وصل كاشير الطوارئ وقوائم الدفع (`EmergencyConsultationPaymentTest`).
  - تطوير شاشة محطة عمل الطوارئ لتطابق تماماً الشاشة المزدوجة المتطورة لمحطة طبيب الاستشارية (Split-Screen 2-Column Layout):
    * العمود الأيمن (`col-12 col-xl-7 col-lg-7`): التبويبات الـ 4 الإجرائية المباشرة (العلامات الحيوية، التشخيص والخدمات، طلبات المختبر والأشعة، وعلاجات وأدوية الطوارئ).
    * العمود الأيسر (`col-12 col-xl-5 col-lg-5`): اللوحة الدائمة الثابتة (`sticky-top`) لـ **«السجل الطبي ونتائج الفحوصات»** بـ 5 تبويبات سريعة: طلبات الحالة الحالية لحظياً ونتائجها، تحاليل سابقة بمؤشرات طبيعي/غير طبيعي ومرفقات الأجهزة، تقارير الأشعة والتصوير بالمشاهدات والانطباع والصور، الزيارات الاستشارية السابقة، والعمليات الجراحية وحالات الطوارئ مع زر مباشر للسجل الزمني الكامل للمريض (`EmergencyMedicalHistoryAndPaidServicesTest`).
  - الحماية المالية والرقابية للخدمات المسددة في الطوارئ: قفل الخدمات المسددة في الواجهة مع وسام رقم الوصل المالي، ومنع حذفها برمجياً في `EmergencyController@updateMedical` لحماية القاصة، مع إتاحة إضافة وحذف الخدمات غير المسددة بحرية تامة.
- **المرحلة 51:** تطوير وترقية سجل زيارات العيادات والاستشارية وتصدير الإكسل والفلاتر المتقدمة: ترويسة عصرية، 5 مؤشرات إحصائية KPI، فلاتر متعددة (بحث، طبيب، قسم، حالة، حاجة لعملية، وتاريخ)، وتصدير إكسل منسق باحترافية (`VisitsExportAndFiltersTest`).
- **المرحلة 50:** تصدير واستيراد وتحديث أسعار خدمات الطوارئ من الإكسل: تصدير مخصص بهوية الطوارئ الحمراء، واستيراد ذكي بالمطابقة التلقائية مع تحديث أسعار الكاش والضمان وتصفير الداخلية التام، مع أزرار ونافذة منبثقة تفاعلية في واجهة خدمات الطوارئ (`EmergencyServicesExportImportTest`).
- **المرحلة 49:** استيراد وتحديث أسعار تحاليل المختبر وخدمات الأشعة جماعياً من الإكسل: كشف ديناميكي للأعمدة، ومطابقة تلقائية بالمعرف أو الكود أو الاسم، وتنظيف ذكي للعملات، وتحديث أسعار الكاش والضمان وتفعيلهما، مع نوافذ منبثقة تفاعلية في واجهات التحاليل والأسعار والأشعة (`LabAndRadiologyImportTest`).
- **المرحلة 48:** تصدير جداول تحاليل المختبر (631 تحليلاً) وأنواع الأشعة (224 فحصاً) إلى ملفات Excel منسقة باحترافية، بتصميم مخصص (RTL)، ترويسات أنيقة ومطابقة لحقول التسعيرة والضمان، مع أزرار تصدير مدمجة في واجهات التحاليل والأسعار والأشعة (`LabTestsExportTest`).
- **المرحلة 47:** توحيد وتحديد التصنيفات الفرعية (`subcategory`) لجميع تحاليل المختبر الـ 631 بدقة طبية تامة (Biochemistry, Serology, Hormone, Histopathology, Immunity, Microbiology, Hematology, Infectious disease, Virology, PCR) وتحديث واجهات اختيار مجموعات الأطباء.
- **المرحلة 46:** استكمال كتالوج المختبر الشامل بإضافة 481 تحليلاً جديداً ومطابقة تسعيرة الكفاءات والضمان الصحي ليصبح الإجمالي 631 تحليلاً مع حماية التحاليل الـ 150 السابقة.
- **المرحلة 45:** تحديث تسعيرة تحاليل المختبر الـ 150 الحالية وفق كتاب الكفاءات الرسمي ووثيقة الضمان الصحي، وتصفير ضمان الداخلية التام وحماية كامل الفحوصات الفرعية (127 فحصاً) والقيم الطبيعية المرجعية (218 مرجعاً).
- **المرحلة 56:** تصحيح وتطوير واجهة الحركات المالية للاستشارية (`/consultant-availability/financial-movements`): إضافة فلتر الضمان والكاش والكل (`insurance_status`)، فلتر فترات الدوام (الدوام الصباحي: 8 ص - 3 م، والدوام المسائي: 3 م فما بعد) مع أوسمة تمييز ذكية (☀️/🌙) في الجدول، إضافة البحث السريع، تصحيح عمود المبلغ والبطاقات الإحصائية لتعكس النقد الفعلي المدفوع بالدرج (مثلاً 2,500 د.ع لمرضى الضمان بدلاً من عرض الكشفية النظرية الكاملة 25,000 د.ع)، مع تفصيل حصة الضمان والكشفية في وسام شفاف أنيق، وتحديث تصدير الإكسل ليشمل عمود الدوام والمدفوع نقداً وتغطية الضمان، وحل تعارض أسماء الحقول `paid_at` في استعلامات الـ SQL (`tests/Feature/ConsultantFinancialMovementsTest.php`).
- **المرحلة 55:** استيراد 7,930 مريض ضمان صحي من ملف الفواتير، التصنيف الذكي لجنس المرضى (خوارزمية الإجراءات الطبية والقواعد اللغوية والمعاجم لتمييز 6,626 مريضاً)، مزامنة جدول المرضى والمستخدمين حصراً إلى قاعدة بيانات هوستنجر مع أخذ نسخة احتياطية محلية، إضافة عمود مخصص لحالة اكتمال السجل في الجدول، وبناء صف تحرير سريع مدمج (Inline Expandable Drawer Row) يفتح تحت المريض مباشرة لتعبئة النواقص واعتمادها عبر AJAX بدون أي نوافذ منبثقة أو إعادة تحميل (`tests/Feature/PatientCompletionAndQuickUpdateTest.php`).
- **المرحلة 54:** التقرير الطبي لحالة الطوارئ وتوقيع الطبيب المعتمد (د. مهند إسماعيل) والتحقق الرقمي عبر QR Code والطباعة بنسختين (`EmergencyMedicalReportTest`).
- **المرحلة 53:** تنظيم وتوحيد أزرار الطوارئ وإضافة مسار توثيق تحويل للاستشارية (`EmergencyWorkflowTest`).
- **المرحلة 52:** تكامل استشارات الطوارئ المالية واحتساب حصص الطبيب والمستشفى (80% / 20%)، حماية الخدمات المسددة، وتطوير محطة عمل الطوارئ مع السجل التراكمي المقسم (`EmergencyConsultationPaymentTest`, `EmergencyMedicalHistoryAndPaidServicesTest`).
- **المرحلة 51:** تطوير وترقية سجل زيارات العيادات والاستشارية وتصدير الإكسل والفلاتر المتقدمة (`VisitsExportAndFiltersTest`).
- **المرحلة 50:** تصدير واستيراد وتحديث أسعار خدمات الطوارئ من الإكسل وإلغاء شمول تأمين الداخلية في استيرادات الأسعار (`EmergencyServicesImportTest`).
- **المرحلة 49:** تصدير واستيراد وتحديث أسعار الفحوصات والتحاليل المخبرية من ملفات Excel لـ 631 تحليلاً (`LabTestsImportTest`).
- **المرحلة 48:** تصدير واستيراد وتحديث أسعار خدمات الأشعة والسونار من ملفات Excel لـ 224 خدمة وإلغاء شمول تأمين الداخلية في استيرادات الأسعار (`RadiologyServicesImportTest`).
- **المرحلة 68:** حل مشكلة عرض صورة التوقيع على هوستنجر بالتحويل التلقائي إلى Base64 Data URI وبث المسار المباشر وحصانة إنشاء الطبيب (`Doctor Signature Base64 Data URI, Stream Route & Safe Doctor Creation Guard`):
  * **السبب الجذري للمشكلة على هوستنجر**: وجود النظام في مسار فرعي (`/hearmz`) جعل محرك Apache يرجع خطأ `404 Not Found` عند طلب الرابط الثابت `asset($doctor->signature)` لأن قواعد التوجيه تبحث في مجلد المشروع وليس `public/`، مما أدى لظهور أيقونة الصورة المكسورة مع النص البديل (`توقيع الطبيب`).
  * **المعالجة الجذرية والشاملة**:
    - **دعم Base64 Data URI في موديل الطبيب (`App\Models\Doctor`)**: إضافة الـ Accessor `signature_data_uri` و `signature_url` لقراءة ملف التوقيع مباشرة من القرص الصلب وتحويله إلى صيغة `data:image/png;base64,...` مدمجة بالكامل في كود الـ HTML مثل شعار النظام.
    - **راوت وبث مباشر للتواقيع (`GET /doctors/{doctor}/signature`)**: إضافة مسار لارافيل رسمي ودالة `signature()` في `DoctorController` لخدمة ملف الصورة المفرغ عبر PHP مباشرة وتفادي أي خطأ مسار فرعي.
    - **تحديث واجهات الأطباء والتقارير**: تحديث `doctors/edit.blade.php`، `doctors/show.blade.php`، وتقرير الطوارئ `medical-report.blade.php` وبوابة التحقق `verify-report.blade.php` لعرض التوقيع المفرغ الشفاف بنسبة 100%.
    - **تحصين إنشاء الطبيب (`DoctorController::store`)**: عزل أعمدة `qualification` و `license_number` المحذوفة سابقاً بفحص `Schema::hasColumn` وتطبيق `DB::transaction` لمنع أي أخطاء `1054 Unknown column` أو مستخدمين أيتام.
  * **الاختبارات الآلية**: اجتياز كامل لـ `DoctorSignatureTest` (4 passed, 21 assertions) و `EmergencyMedicalReportTest` (3 passed, 19 assertions).
- **المرحلة 67:** حذف وتطهير طلبات وحالات المختبر والأشعة والطوارئ وتصفير مبالغها في الخزينة على سيرفر هوستنجر مع حماية الكتالوجات والمرضى (`Hostinger Lab, Radiology & Emergency Operational & Financial Purge with Catalogs & Patients Preserved`):
  * **أخذ نسخة احتياطية فورية وشاملة (`storage/app/backups/hostinger_lab_rad_emerg_before_purge_2026_10_16_202735.sql`)**: حفظ ملف SQL متكامل بحجم 87 كيلوبايت يحتوي على أوامر INSERT لكافة سجلات الطلبات والدفعات وقيود الخزينة وحالات الطوارئ قبل مسحها.
  * **الحذف والتصفير المتزامن ضمن Transaction موحدة على هوستنجر**:
    - **المختبر (`lab`)**: حذف 14 طلباً مخبرياً (`requests`)، حذف 20 دفعة مسددة في الكاشير بقيمة 571,250 د.ع (`payments`)، وحذف 22 قيد خزينة بقيمة 583,750 د.ع (`financial_transactions`).
    - **الأشعة والسونار (`radiology`)**: حذف 31 طلب أشعة وسونار (`requests`)، حذف 19 سجلاً في جدول طابور الأشعة (`radiology_requests`)، وحذف نتيجتين مسجلتين (`radiology_results`).
    - **الطوارئ (`emergency`)**: حذف 8 حالات طوارئ ومرضى طوارئ (`emergencies` و `emergency_patients`)، وحذف الخدمات المسجلة والعلامات الحيوية وطلبات المختبر والأشعة التابعة للطوارئ، وحذف 4 دفعات كاشير بقيمة 230,000 د.ع، وحذف قيدي خزينة للطوارئ بقيمة 115,000 د.ع.
  * **الحماية الكاملة للأصول والكتالوجات**:
    - كتالوج تحاليل المختبر (150 فحصاً + 127 فحصاً فرعياً + 218 قيمة مرجعية) سليم تماماً.
    - كتالوج الأشعة والسونار المعتمد الجديد (224 فحصاً باللغة الإنكليزية مع الأسعار) سليم تماماً.
    - كتالوج خدمات الطوارئ المسعرة (110 خدمات) سليم تماماً.
    - سجل المرضى العام (8,399 مريضاً) وحسابات الموظفين والأطباء سليمة ومحمية 100%.
- **المرحلة 66:** حذف وتطهير كافة السجلات التشغيلية والمالية للدكتورة رولا محمد اسماعيل على سيرفر هوستنجر مع الحفاظ التام على حسابها وملفها الطبي (`Dr. Rola Mohammed Ismail Hostinger Operational & Financial Purge with Profile Preserved`):
  * **أخذ نسخة احتياطية فورية وشاملة (`storage/app/backups/hostinger_dr_rola_backup_before_delete_2026_10_16_201919.sql`)**: حفظ أوامر SQL دقيقة بصيغة INSERT لكافة السجلات المرتبطة بالدكتورة رولا قبل الحذف لضمان إمكانية الاسترجاع عند الحاجة.
  * **الحذف التتابعي الآمن ضمن Transaction واحدة**:
    - حذف المواعيد والحجوزات (12 موعداً).
    - حذف الزيارات الطبية (7 زيارات).
    - حذف طلبات الفحوصات الطبية والمختبر والأشعة (18 طلباً).
    - حذف نتائج المختبر وطلبات الأشعة المرتبطة (10 سجلات).
    - حذف الوصفات والعلاجات الصيدلانية (6 سجلات).
    - حذف الإيرادات ومستحقات الطبيب (`consultation_revenues`: 9 سجلات، و `doctor_dues`: 6 سجلات).
    - حذف الدفعات وقيود الخزينة (`payments`: 9 دفعات، و `financial_transactions`: 9 قيود مالية مقترنة بالوصولات).
    - تصفير رصيد الحساب المالي للطبيب (`doctor_financial_accounts`: الرصيد = 0.00، وإجمالي الأرباح = 0.00).
  * **الحماية الصارمة للملف والحساب الشخصي**: بقاء سجل الطبيب في جدول `doctors` (معرف: 242) وحساب المستخدم في جدول `users` (معرف: 510) وإعدادات النسبة سليمة ومحمية بنسبة 100%.
- **المرحلة 65:** استبدال وتطهير جدول الأشعة بالكامل بملف الإكسل وحذف الخدمات القديمة مع حماية السجلات السابقة (`Radiology Catalog Complete Wipe & Replace with Excel Import`):
  * أخذ نسخة احتياطية فورية وتلقائية لجدول الأشعة في `storage/app/backups/radiology_types_before_excel_replace_2026_10_16_194814.sql`.
  * تطوير محرك الاستيراد والتنظيف الذاتي في `RadiologyTypesImport.php`: دعم خيار `$replaceExisting` لحذف أي خدمات قديمة غير موجودة في ملف الإكسل وغير مستخدمة في أي طلبات (`deletedCount = 80`).
  * تحصين السجلات الطبية السابقة وتعطيلها فقط في حال وجود طلبات سابقة للمرضى.
  * عزل وتفادي تعارض الأكواد الفريدة (`Unique Code Swapping Guard`) عبر بادئة مؤقتة تعمل بدقة على `MySQL` و `SQLite`.
  * المعايرة الدقيقة للتصنيفات (`subcategory` و `main_category`): ربط الأقسام بمسمياتها القياسية (`أشعة`، `سونار`، `مفراس`، `الرنين`، `إيكو`).
  * تحديث واجهة ونافذة استيراد الإكسل بمفتاح استبدال كامل للجدول، وتثبيت الـ 224 خدمة باللغة الإنكليزية مطابقة تماماً لملف `الاخير والمعتمد اشعة.xlsx` بأسعار الكاش والضمان الصحي (`LabAndRadiologyImportTest`: `6 passed, 26 assertions`).
- **المرحلة 64:** إدراج حقول توجيهات وملاحظات الطبيب للمختبر والأشعة في محطة الطبيب وربطها الكامل بمحطات المختبر والأشعة (`Doctor Clinical Notes Input in Consultation Station & Workstation Integration`).
- **المرحلة 63:** عرض ملاحظات وتوجيهات الطبيب المحول لموظف وفني الأشعة في شاشة الفحص وطابور العمل (`Doctor Clinical Notes Display in Radiology Workstation`).
- **المرحلة 62:** منظومة رفع وتفريغ التواقيع اليدوية للأطباء تلقائياً والمعاينة الحية للشفافية في شاشتي الإضافة والتعديل (`Doctor Signature Upload, Auto Background Removal & Live Preview in Create & Edit`):
  * تطوير خدمة المعالجة الذكية `SignatureProcessingService` لعزل خلفية الورقة وظلال الإضاءة عبر PHP GD بقناة ألفا شفافة 100%، وتنعيم الحواف، وتعزيز خط الحبر، وقص الهوامش الزائدة تلقائياً.
  * تحديث شاشة إنشاء طبيب جديد (`/doctors/create`) وشاشة التعديل (`/doctors/{id}/edit`) ببطاقة رفع التوقيع والمعاينة التفاعلية المباشرة عبر Canvas مع شريط حساسية عزل الورقة وزر الحفظ الفوري عبر AJAX.
  * إبراز التوقيع المعتمد في شاشة عرض الطبيب (`/doctors/{id}`)، وتكامله مع تقرير الطوارئ الرسمي ورمز الـ QR (`DoctorSignatureTest`, `EmergencyMedicalReportTest`).
- **المرحلة 61:** استدعاء المريض الاستثنائي للطبيب وثبات تسلسل الطابور ودعم طلبات الضمان المختلطة بالكاشير (`Out-of-Turn Calling, Queue Order Stability, Hostinger Old Catalogs & Mixed Insurance Requests`):
  * استرجاع قوائم الفحوصات القديمة بتاريخ 2026-10-11 على سيرفر هوستنجر (`lab_tests` 150 فحصاً و `radiology_types` 305 خدمة) بعد أخذ نسخة احتياطية كاملة ومع الحفاظ التام على بيانات المرضى والمستخدمين.
  * ميزة الاستدعاء الاستثنائي للمريض الحرج من جدول الزيارات (`/doctor/visits`) دون تغيير تسلسل وأدوار باقي المرضى المنتظرين في الطابور عبر تثبيت الـ `queue_number` وتوليده فور إنشاء الموعد (`max + 1`).
  * دعم تسديد طلبات الضمان المختلطة (فحوصات مشمولة وأخرى غير مشمولة) في واجهة كاشير الطلبات (`request-payment-form`) باحتساب غير المشمول 100% كاش والمشمول بنسبة التحمل دون حظر الكاشير أو تعطيل زر الدفع (`DoctorQueueCallSpecificTest`, `InsurancePricingTest`).
- **المرحلة 60:** فصل المبالغ المسترجعة للمرضى في كارت خزينة مستقل ومطابقة السيرفر السحابي (`Treasury Refunds Dedicated KPI Card, Hostinger Sync & Cascaded Deletion Guard`):
  * إضافة تصنيف `refund` في `FinancialTransaction` وتطوير `PaymentObserver` لتوليد قيد صرف استرجاع تلقائياً عند إنشاء أو تحديث أي دفعة سالبة، وإضافة معالج الحذف التتابعي `deleted(Payment)` لحذف قيود الخزينة وأرباح الأطباء عند حذف الدفعات.
  * إعادة هيكلة واجهة الخزينة المركزية (`/treasury`) إلى 5 كروت مؤشرات متقدمة: (الرصيد الفعلي بالخزينة، إجمالي الوارد، المبالغ المسترجعة للمرضى [كارت كهرماني مستقل مع عداد الحركات]، المصروفات ومستحقات الأطباء [صرفيات تشغيلية وأطباء فقط]، والصافي الفعلي للفترة).
  * إضافة شريط تقدم مخصص للمسترجعات في بطاقة تفكيك وتوزيع المصروفات.
  * مطابقة الخزينة السحابية على سيرفر هوستنجر مع اللوكل ليوم 15-10 عند **552,500 د.ع** بدقة 100% (`HospitalTreasuryTest`, `HospitalTreasuryDashboardTest`).
- **المرحلة 59:** منظومة الحد من تكرار الحجز وتنبيهات الحجوزات النشطة للكشفية والسونار وتأكيد الموظف ومنع النقر المزدوج (`Duplicate Booking Prevention, Active Alerts, Staff Confirmation & Debounce`):
  * التحقق اللحظي والفوري عبر AJAX (`/inquiry/check-active-booking`) وتنبيه الموظف عند وجود حجز نشط غير مكتمل لنفس اليوم.
- **المرحلة 73:** نظام إدارة نمط إدخال نتائج التحاليل المخبرية للمدير وحصر حالات الطوارئ المباشرة بالسعر النقدي 100% كاش (Lab Test Result Entry Mode Management & Emergency Direct Cash Enforcement):
  * **إدارة نمط إدخال نتائج التحاليل المخبرية للمدير**:
    - إضافة حقل `result_entry_type` في جدول `lab_tests` بقيم `['value', 'components', 'attachment', 'both']`.
    - بناء واجهة إدارية حصرية للمدير `/lab-tests/entry-settings` بأربعة خيارات Checkbox تفاعلية لكل تحليل لحفظ التفضيلات لحظياً عبر AJAX بنقرة واحدة، ودعم التحديث الجماعي.
    - تكييف واجهة محطة فحص المختبر `/lab/{request}` ديناميكياً لإظهار حقول القيمة أو القيم الفرعية أو رفع المرفق أو الاثنين معاً بناءً على إعدادات المدير.
    - اختبار آلي مكتمل: `LabTestResultEntryTypeTest` (6 passed, 28 assertions).
  * **حصر حالات وخدمات الطوارئ المباشرة بالسعر النقدي 100% وإلغاء توريث الضمان**:
    - إلغاء التوريث التلقائي لنسب الضمان من ملف المريض العام في دوال احتساب مستحقات وفواتير الطوارئ (`upsertUnifiedEmergencyPayment`, `getUnpaidAmount`, و `emergency-payment-form.blade.php`).
    - جعل الحجز الافتراضي للطوارئ من الاستعلامات واستقبال الطوارئ نقداً 100% كاش (`apply_insurance = 0` / `is_insured = false`) وقصر الضمان على حجوزات بوت الضمان أو الإحالات الرسمية.
    - تصحيح حالة الطوارئ #5 ووصل الدفع #21 للمريض (منير سمير يوسف) لتكون كاش 100% بقيمة 115,000 د.ع (تحاليل: 17 hydroxy prog، 24 hr urine for protein، GUE with RBC morphology) مع تصفير حصة الضمان.
    - اختبار آلي مكتمل: `EmergencyFinancialClearanceAndCancellationTest` (6 passed, 32 assertions).
- **المرحلة 72:** إبراء الذمة المالية وإلغاء حالات الطوارئ ودمج استرجاع الكاشير (Emergency Financial Clearance Locks, Cancellation & Cashier Refund Integration):
  * **إبراء الذمة المالية وقفل الإجراءات والتقرير الطبي**: قفل وتعطيل زر التقرير الطبي الرسمي وقائمة إجراءات وتحويل وتخريج الحالة (تحويل للعمليات، تحويل للرقود، تحويل للاستشارية، خروج متعافي، خروج على مسؤوليته) في واجهة الطوارئ `/emergency/{emergency}` ما لم تكن الحالة مسددة بالكامل في الكاشير (`$unpaidAmount <= 0` أو `payment_status = 'paid'`)، مع حماية برمجية صارمة على مستوى الـ Backend تمنع تجاوز القيود المالية.
  * **إلغاء حالات الطوارئ النشطة مع الحصانة التشغيلية والسريرية**: إضافة زر ومودال تأكيد الإلغاء في شاشة الحالة وشاشة الطابور العام لحالات الطوارئ مع حظر إلغاء الحالات المخرجة مسبقاً (`status = 'discharged'`). في الحالات غير المسددة: تحويل الحالة إلى `cancelled` وحذف وصولات الدفع المعلقة غير المسددة. في الحالات المسددة مسبقاً: تحويلها إلى `cancelled` ووسمها للاسترجاع وتوجيه المريض إلى الكاشير.
  * **التكامل التلقائي مع الكاشير والخزينة المركزية**: إدراج حالات الطوارئ الملغاة المسددة في تبويب `مستحقات الاسترجاع (Refund)` في الكاشير بشارة طوارئ مميزة، ومعالجة الصرف بقيد دفعة سالبة في `payments`، وانعكاسها فورياً في الخزينة المركزية عبر `PaymentObserver` كحركة منصرفات (`outflow / refund`) وإصدار سند الاسترجاع الرسمي والحراري.
  * اجتياز كامل للاختبارات الآلية `EmergencyFinancialClearanceAndCancellationTest` (4 passed, 21 assertions).
- **المرحلة 71:** دورة استرجاع وصرف مبالغ الفحوصات الملغاة في الكاشير (Cashier Refund Cycle for Cancelled Medical Requests)
- **المرحلة 70:** نظام إلغاء الطلبات الخارجية المباشرة للمختبر والأشعة (Direct External Requests Cancellation) مع الحصانة السريرية والمالية:
  * تمكين موظفي المختبر والأشعة (أشعة، سونار، رنين، مفراس، إيكو) من إلغاء الطلبات الخارجية المباشرة غير المسددة من شاشاتهم ومحطات عملهم.
  * الحصانة السريرية: حظر إلغاء طلبات أطباء الاستشارية من شاشات الأقسام التشخيصية، وقصر إلغائها على شاشة الطبيب المعالج فقط حفاظاً على الهيكلية الطبية.
  * الحصانة المالية: منع إلغاء أي طلب مسدد مالياً قبل إجراء الاسترجاع من الكاشير أولاً.
  * المعالجة التلقائية: تحويل حالة الطلب والزيارة المباشرة إلى `cancelled`، وحذفها فورياً من طوابير الانتظار ومن قائمة المعلقات في الكاشير.
  * اجتياز كامل للاختبارات الآلية `ExternalRequestCancellationTest` (6 passed, 24 assertions).
- **المرحلة 69:** حصر طلبات الاستعلامات المباشرة بالسعر النقدي 100% (منع الضمان الصحي لطلبات المختبر والأشعة المباشرة بدون إحالة استشاري) واعتماد تسعير وعرض باقات المختبر كبند موحد بسعر الباقة في الكاشير والسندات والوصل مع حصر التحاليل الإضافية فقط خارج الباقة، وتطوير البحث الذكي بالأشعة والمختبر.
- **المرحلة 68:** حل مشكلة عرض صورة التوقيع على هوستنجر بالتحويل التلقائي إلى Base64 Data URI وبث المسار المباشر وحصانة إنشاء الطبيب Doctor Signature Base64 Data URI, Stream Route & Safe Doctor Creation Guard.
- **المرحلة 67:** حذف وتطهير طلبات وحالات المختبر والأشعة والطوارئ وتصفير مبالغها في الخزينة على سيرفر هوستنجر مع حماية الكتالوجات والمرضى.
- **المرحلة 66:** حذف وتطهير كافة السجلات التشغيلية والمالية للدكتورة رولا محمد اسماعيل على هوستنجر مع الحفاظ التام على حسابها وملفها الطبي.
- **المرحلة 65:** استبدال وتطهير جدول الأشعة بالكامل بملف الإكسل وحذف الخدمات القديمة مع حماية السجلات السابقة Radiology Catalog Complete Wipe & Replace with Excel Import.
- **المرحلة 54:** نظام التقرير الطبي الرسمي لحالات الطوارئ (A4 Portrait)، توقيع د. مهند إسماعيل الرقمي المعتمد، خيار الطباعة المزدوجة (المريض + الأرشيف)، وبوابة التحقق الإلكتروني عبر الـ QR Code بدون تسجيل دخول (`EmergencyMedicalReportTest`).
- **المرحلة 53:** تنظيم وتوحيد أزرار الطوارئ في قائمة منسدلة موحدة وإضافة مسار توثيق الخروج «تحويل للاستشارية» (`transferred_to_consultation`) وتبسيط جدول الطوارئ العام (`EmergencyWorkflowTest`).
- **المرحلة 52:** تكامل استشارات الطوارئ المالية (50,000 د.ع: 80% للطبيب و 20% للمستشفى)، الحماية المالية للخدمات المسددة من الحذف بالخطأ، وتصميم الشاشة المزدوجة المتزامنة لمحطة عمل الطوارئ مع السجل الطبي التراكمي الملتصق.
- **المرحلة 46:** تدقيق ومطابقة وتحديث أسعار الفحوصات والتحاليل المخبرية لـ 631 فحصاً مع كشف الأسعار المعتمد واستبعاد تأمين قوى الأمن/الداخلية (`kafaat_lab_prices_update.sql`).
- **المرحلة 45:** توثيق المرجع الشامل لقسم الطوارئ في ملف `docs/المرجع_الشامل_لقسم_الطوارئ.md`.
- **المرحلة 44:** استبعاد التحويلات والطلبات والزيارات الملغاة من مصفوفة إحالات أطباء الخزينة: إضافة شروط استبعاد صريحة (`requests.status != 'cancelled'` و `visits.status != 'cancelled'` و `surgeries.status != 'cancelled'`) لضمان عدم ظهور أي إحالات وهمية للمواعيد والزيارات الملغاة (`HospitalTreasuryDashboardTest`).
- **المرحلة 43:** تشخيص وتصفية قيود د. زياد طارق ابراهيم اسماعيل (معرف: 193) في موعد الضمان الملغى (#80): إنشاء وتشغيل مايجريشن آلي (`2026_10_14_150000_sanitize_dr_ziad_cancelled_appointment_revenues.php`) على سيرفري هوستنجر واللوكل، وحذف الإيرادات المعلقة (#62 و #66)، وحذف المستحق #58، وتصفير رصيد الحساب المالي ليعود صافي أرقامه صفراً تماماً.
- **المرحلة 42:** إضافة القائمة الحلقية لسكربت النشر `deploy_hostinger.bat`، أمر `/deploy`، تصحيح قيود استرجاع الكشفية الملغاة عبر مايجريشن آلي (`2026_10_14_140000_fix_hijacked_appointment_payments_and_refunds.php`)، وتنظيف سجلات د. قمر سعد المالية والتشغيلية لوكل وأونلاين.
- **المرحلة 41:** حظر وإخفاء ميزة سحب قاعدة البيانات على سيرفرات الإنتاج لمنع المسح العرضي وحماية البيانات الحية (`DatabaseSyncTest`).
- **المرحلة 71:** دورة استرجاع وصرف مبالغ الفحوصات الملغاة في الكاشير (Cashier Refund Cycle for Cancelled Medical Requests): تبويب وإشعار تنبيهي مخصص للطلبات الملغاة بعد السداد (`مستحقات الاسترجاع (Refund)`)، نافذة صرف الاسترجاع نقداً أو بالبطاقة، توليد سندات استرجاع مالي سالبة تقيد تلقائياً في الخزينة المركزية كحركة منصرفات (`outflow/refund`)، طباعة سند استرجاع حراري ورسمي مع منع تكرار نصوص وصف الاسترجاع في بند الخدمة الطبية (`ExternalRequestCancellationTest`).
- **المرحلة 70:** نظام إلغاء الطلبات الخارجية المباشرة للمختبر والأشعة والسونار والرنين والمفراس والإيكو مع الحصانة السريرية للعيادات والمرونة التشغيلية وإحالة الاسترجاع للكاشير (`ExternalRequestCancellationTest`).
- **المرحلة 69:** حصر طلبات الاستعلامات المباشرة بالسعر النقدي 100% واعتماد تسعير وعرض باقات المختبر كبند موحد في الكاشير والسندات مع تحسين محطات العمل.
- **المرحلة 40:** عزل استرجاع كشفيات الأطباء ومنع استبدال دفعة الموعد بدفعات الفحوصات الطبية (`DoctorVisitCancellationRefundTest`).
- **المرحلة 39:** إصلاح خطأ 404 في استدعاء طابور السونار والأشعة، ودعم المسارات البديلة والروابط النسبية على السيرفرات الفرعية (`RadiologyStaffCallNextTest`).
- **المراحل 38-35:** لوحة الخزينة المركزية: شريط تحكم شامل وموحد للمخططات البيانية مع إظهار المبالغ والأرقام مباشرة على الأعمدة، ومحرك التحديث التلقائي الذكي، وإزالة أزرار السندات المكررة (`HospitalTreasuryDashboardTest`).
- **المرحلة 34:** مصفوفة ذكاء الأعمال لأطباء الاستشارية وتوزيع الإحالات بين المختبر والأشعة والعمليات وتفصيل الكاش مقابل الضمان.
- **المراحل 21-17:** مركز سحب قاعدة بيانات هوستنجر السحابية، وسجل حركات ونشاط الموظفين اللحظي مع استبعاد الدخول/الخروج من الإنتاجية، وفلاتر المرضى المتقدمة مع تتبع المُدخِل والبطاقة الموحدة.
- **المرحلة 78:** استيراد وتحديث كتالوج المختبر الشامل لـ 865 فحصاً وفك قيد تفرد الكود الطبي ومطابقة هوستنجر السحابية (Lab Catalog Full 865 Import, Code Unique Constraint Removal & Hostinger Sync):
  * أخذ نسخ احتياطية كاملة لجداول الفحوصات في `storage/app/backups/`.
  * إنشاء وتشغيل مايجريشن `2026_10_18_192000_drop_unique_code_from_lab_tests_table.php` لإلغاء قيد `UNIQUE` عن عمود `code` في جدول `lab_tests` وجعله `INDEX` عادي لاستيعاب تكرار الأكواد السريرية وتحديثات الأجهزة والفئات.
  * تطبيق نفس تعديل الفهرس على سيرفر هوستنجر السحابي (`srv1687.hstgr.io`).
  * تطوير كلاس الاستيراد `app/Imports/LabTestsImport.php` لدعم إنشاء الفحوصات الجديدة والتزامن الشامل والحفاظ التام على سجلات وطلبات الفحوصات السابقة.
  * استيراد الـ 865 فحصاً بالكامل من ملف `الاخير جدا بعد تدقيق الكروب.xlsx` ومطابقة السيرفرين (اللوكل وهوستنجر) بنسبة 100%: تحديث 631 فحصاً وإضافة 234 فحصاً جديداً، ليصبح إجمالي الفحوصات في كليهما 865 فحصاً.
  * اجتياز كامل للاختبارات الآلية للتحاليل والأسعار (`LabTestPricingSettingsTest` و `LabSubtestsResolutionAndEditTest`).
- **المرحلة 77:** تصحيح وتوحيد مسار زر العودة للكاشير في صفحة الوصل واستمارة دفع الطلبات الطبية لموظف الاستعلامات (Cashier Receipt & Request Payment Return Link Correction):
  * **تصحيح مسار زر العودة للكاشير في صفحة إيصال القبض (`resources/views/cashier/receipt.blade.php`)**: إزالة الشرط القديم المقيد لدور `consultation_receptionist` الذي كان يعيد الموظف إلى واجهة توفر الأطباء (`consultant-availability.index`)، وجعل الرابط يشير دائماً وبشكل مباشر إلى `route('cashier.index')` ليتطابق مع عنوان الزر والوظيفة التشغيلية.
  * **تصحيح أزرار الرجوع في استمارة دفع الطلبات الطبية (`resources/views/cashier/request-payment-form.blade.php`)**: تعديل زر العودة العلوي والسفلي ليعود إلى `route('cashier.index')`.
  * **الاختبارات الآلية**: بناء واجتياز اختبار الميزة `tests/Feature/CashierReceiptReturnLinkTest.php` (1 passed, 4 assertions).
- **المرحلة 79:** إكمال نواقص بيانات المرضى وقفل الحجز تحت مظلة الضمان الصحي عند وجود نواقص ببيانات المريض الأساسية:
  * إظهار رقم هاتف المريض في كارت بيانات المريض والطلب بشاشة المختبر `/lab/requests/{id}/show` مع رابط اتصال فوري `tel:`.
  * إتاحة إدخال وتعديل تاريخ الميلاد (`date_of_birth`) من خلال صف الإكمال السريع السلس (`btn-quick-complete`) في جدول المرضى `/patients`، مع الحساب التلقائي اللحظي للعمر وإظهار شارة العمر، ومعالجة حالة 7,919 مريض مسجلين بدون مواليد بسبب التسجيل السريع القديم.
  * فحص الحقول الـ 5 الإلزامية لملف الضمان: (رقم بطاقة/دفتر الضمان، البطاقة الموحدة/الرقم الوطني، رقم الهاتف، الجنس، وتاريخ الميلاد).
  * قفل الحجز تحت مظلة الضمان في شاشة استعلامات الاستشارية `/inquiry/create` آلياً عند وجود أي نقص من الحقول الخمسة:
    - تحويل نمط الاختيار إلى بطاقات تفاعلية لمسية (Selectable Visual Cards) بدلاً من نقاط الراديو الصغيرة.
    - تعطيل بطاقة الضمان (`disabled` + `opacity-75`) ووضع شارة `مقفل 🔒 (نواقص بملف الضمان)`.
    - تفعيل بطاقة الدفع النقدي (كاش) تلقائياً بلون أزرق واضح وشارة `متاح للمتابعة الفورية`.
    - إظهار شريط تنبيه أحمر بارز ومفصل يوضح بدقة الحقول المفقودة كشامارات حمراء مع خطتي عمل واضحتين للموظف (المتابعة فوراً نقداً، أو إرسال المريض لشعبة التسجيل والضمان).
  * منظومة التنبيه الرقمي العاجل لشعبة الضمان والتسجيل:
    - إضافة زر تفاعلي `[ ⚡ إرسال تنبيه عاجل لمكتب الضمان والتسجيل ]` داخل صندوق التحذير في `/inquiry/create`.
    - مسار ومتحكم `InquiryController@notifyInsuranceIncomplete` ينشئ إشعاراً فورياً `InsuranceIncompleteAlertNotification` في قاعدة البيانات وبث Pusher/Echo لموظفي الضمان والتسجيل والمدراء (`thaman`, `receptionist`, `admin`).
    - الإشعار يحتوي على اسم المريض، النواقص الدقيقة، واسم موظف الاستعلامات المرسل، وبيانات المريض التفصيلية.
    - إضافة بانر تنبيه عريض وتفاعلي أعلى جدول المرضى مباشرة (`#insuranceAlertsContainer`) يظهر لموظف الضمان فوراً عند وجود طلبات عاجلة من الاستعلامات.
    - **ظهور التنبيه فورياً بدون رفرش شاشة إطلاقاً**:
      * دعم مزدوج: استقبال لحظي عبر Pusher/Echo + استطلاع فوري خفيف عبر AJAX Polling كل 4 ثوانٍ (`patients.insurance-alerts-poll`).
      * نغمة صوتية تنبيهية لطيفة فورية عبر Web Audio API عند وصول الطلب.
      * إدراج كرت المريض فوراً في أعلى القائمة بحركة انسيابية، وتحديث عداد التنبيهات.
    - يحتوي البانر على اسم المريض، النواقص، وتوقيت الطلب، مع زر مباشر `[ ⚡ إكمال النواقص فوراً ]`.
    - عند النقر على الزر، ينزلق دراور الإكمال السريع للمريض فوراً (حتى لو لم يكن المريض بالصفحة الحالية يتم إدراجه أعلى الجدول وفتح الدراور فوراً)، ويتم تمييز الإشعار كمقروء وإخفاء الكرت تلقائياً بمجرد الحفظ.
- **المرحلة 89:** حل مشكلة التحديث السريع لصفحة الكاشير وضبط التوقيت الافتراضي على 30 ثانية مع أداة تحكم مرنة وزر تحديث يدوي فوري:
  * إلغاء `setInterval` القديم (5 ثواني).
  * تعيين 30 ثانية افتراضياً عبر `setTimeout` ذكي.
  * إضافة قائمة اختيار (15ث، 30ث، 1د، 2د، إيقاف) في ترويسة الكاشير وحفظها بـ `localStorage`.
  * زر تحديث فوري 🔄 وحماية عدم المقاطعة عند فتح مودال أو كتابة الكاشير بالبحث أو تصغير التبويب.
  * اختبار آلي: `tests/Feature/CashierRefreshIntervalControlTest.php` (1 passed, 7 assertions).
- **المرحلة 84:** السجل التراكمي لآخر 3 نتائج سابقة لكل فحص مخبري عمودياً أسفل النتيجة الحالية (Lab Print Cumulative Previous Results & Delta Check History):
  * إضافة علاقة `labResults()` في `Request.php` ودالة `getPreviousLabResultsMap()` التي تستخرج بكفاءة عالية آخر 3 نتائج معتمدة سابقة لنفس المريض لكل تحليل مخبري قبل الطلب الحالي، مرتبة تنازلياً مع دعم الفحص المزدوج من `lab_results` الفعلي وحقل `requests.result` القديم، ومنع التكرار لنفس الطلب.
  * عرض النتائج السابقة عمودياً أسفل النتيجة الحالية مباشرة في خانة النتيجة (`test-value-cell`): تظهر النتيجة الحالية بالسطر الأول، وأسفلها مباشرة تظهر النتائج السابقة عمودياً (`.prev-vertical-box`) مع ألوان دالة على الحالة وتواريخ الفحوصات `(d/m/y)`، مع بقاء هيكل الجدول ثلاثي الأعمدة الكلاسيكي (اسم الفحص | النتيجة ووحدة القياس | الحالة) ومحاذاة السطور للأعلى.
  * اجتياز كامل للاختبارات الآلية: `tests/Feature/LabPrintGenderAndHeaderTest.php` (5 passed, 29 assertions) وحزمة المختبر كاملة (46 passed, 207 assertions).
- **المرحلة 83:** تصحيح قراءة وتحديد جنس المريض وترقية ترويسة تقرير المختبر لتطابق الطوارئ والأشعة مع بطاقة مريض رمادية حديثة وكود QR ومكان توقيع فارغ (Lab Print Patient Gender Resolution Fix & Unified Emergency/Radiology Header Style):
  * تصحيح جنس المريض في نموذج `Patient.php` عبر إضافة دالتي الوصول (`getGenderAttribute` المشتقة من `user->gender` و `getGenderDisplayAttribute`)، وتصحيح استخراج الجنس في `resources/views/staff/requests/print.blade.php` ليتعرف بدقة على كافة صيغ الإناث (`female`, `أنثى`, `f`) وطباعة `Female` بدلاً من الرجوع للوضع الافتراضي الخاطئ `Male`.
  * ترقية ترويسة تقرير المختبر لتطابق ترويسة الطوارئ والأشعة: ثلاثية الأعمدة (عربي يميناً، شعار المستشفى المعتمد في المنتصف بصيغة Base64، وإنكليزي يساراً)، مع شريط العنوان الكحلي الملكي `LAB-#{id}` وإطار الصفحة الأزرق الملكي `#005a9c`.
  * إعادة تصميم بطاقة بيانات المريض والطلب باللون الرمادي السريري الأنيق (Clinical Light Grey) مقسمة لـ 3 مناطق متكاملة:
    1. الديموغرافيا: الاسم بالعربي والانكليزي، كبسولة العمر والجنس الموحدة، ورقم الهاتف.
    2. بيانات العينة والطلب: الطبيب، رقم العينة، التاريخ والوقت، وشارة التغطية المالية (كاش/ضمان).
    3. الباركود الرقمي السريع (QR Code): رمز استجابة سريعة مباشر لرابط التقرير المعتمد المشفر.
  * إضافة قسم الاعتماد والتوقيع الرسمي مع خط ومكان توقيع فارغ لاختبار التوقيع اليدوي، وخاتم المختبر المعتمد.
  * اجتياز كامل للاختبارات الآلية: `tests/Feature/LabPrintGenderAndHeaderTest.php` (3 passed, 17 assertions) وحزمة المختبر كاملة (44 passed, 195 assertions).
- **المرحلة 82:** إصلاح ظهور المشاهدات والنتائج وترقية تقرير الأشعة والسونار المعتمد لتصميم الطوارئ مع مربع توقيع فارغ للاختبار:
  * تصحيح كاستينغ `'result' => 'array'` في نموذج `Request.php` لمنع تداخل تحويل المصفوفات.
  * ترقية دالتي `show` و `print` في `RadiologyStaffController` لتوحيد حقول `findings`, `impression`, `recommendations` والتقرير السريري والصور المرفقة.
  * ترقية قالب التقرير الطبي للأشعة والسونار `resources/views/radiology/print.blade.php` لمطابقة استمارة الطوارئ مع توفير مربع توقيع فارغ وخاتم معتمد ورمز QR.
  * بناء واجتياز اختبار الميزة `tests/Feature/RadiologyPrintReportTest.php` وحزمة الأشعة كاملة (24 passed, 115 assertions).
- **المرحلة 81:** منظومة التحكم اللحظي والذكي بتشغيل وإطفاء الفحوصات المختبرية ومطابقة الضمان الصحي (Lab Tests Instant Activation & Bulk Controls):
  * مفاتيح iOS Switch لحظية لتفعيل/تعطيل الفحص عاماً وللضمان الصحي عبر AJAX دون إعادة تحميل الصفحة.
  * زر ذكي لإطفاء كافة الفحوصات خارج الضمان بنقرة واحدة، وزر لتفعيل المشمولة بالضمان، مع شريط عائم للتحكم الجماعي.
  * بناء وتشغيل `tests/Feature/LabTestActivationAndBulkControlTest.php` (5 passed, 20 assertions).
- **المرحلة 80:** مشاركة نتائج التحاليل المخبرية عبر واتساب (WhatsApp Result Sharing) وبوابة التقرير الطبي المعتمد الآمنة:
  * إضافة دوال نموذج الطلبات `app/Models/Request.php`:
    - `getPatientPhone()`: تهيئة وتوحيد أرقام الهواتف العراقية للنمط الدولي القياسي لـ WhatsApp (`964...`).
    - `getPublicReportToken()`: إنشاء رمز تشفير HMAC sha256 محكم مبني على معرف الطلب والوقت ومفتاح التطبيق السري (`APP_KEY`).
    - `getPublicReportUrl()`: توليد رابط تقرير المريض العام المعتمد.
    - `getWhatsAppShareUrl()`: توليد رابط واتساب الفوري `https://wa.me/{phone}?text=...` محمل برسالة رسمية تحتوي على اسم المستشفى، نوع التحليل، ورابط مباشر لاستعراض التقرير المعتمد.
  * إنشاء متحكم العرض العام `app/Http/Controllers/LabPublicReportController.php` ومسار `/lab/results/view/{request}` للتحقق الآمن من رمز التشفير وتمكين المريض من فتح تقريره مباشرة من واتساب بدون الحاجة لحساب أو تسجيل دخول.
  * ترقية صفحة التقرير الطبي `resources/views/staff/requests/print.blade.php`:
    - إضافة تصميم متجاوب متكامل للهواتف الذكية مع شريط علوي للمريض يحتوي على زر مباشر `[ 📥 تحميل / حفظ كـ PDF ]` يستدعي طباعة/حفظ الـ PDF تلقائياً.
    - إضافة زر إرسال واتساب في شريط الموظف لفتح محادثة واتساب مع المريض بنقرة واحدة.
  * دمج زر الواتساب الأخضر الأنيق في:
    - جدول طلبات المختبر `/lab/requests` في عمود "الإجراءات" بجوار زر عرض النتيجة للفحوصات المكتملة.
    - شاشة تفاصيل طلب المختبر `/lab/requests/{id}/show` في الشريط العلوي للإجراءات، وبجوار رقم هاتف المريض في كارت البيانات.
  * منظومة التحكم بالأدوار والصلاحيات (Role-Based WhatsApp Access Control):
    - إنشاء قسم وبطاقة تشغيلية مستقلة في شاشة الأدوار والصلاحيات: **`خدمات ومراسلات الواتساب (WhatsApp)`** بأيقونة الواتساب الخضراء وتصنيف خاص `whatsapp`.
    - إضافة صلاحية مخصصة `send lab whatsapp` (إرسال ومشاركة نتائج التحاليل للمرضى عبر واتساب) ضمن هذا القسم المنفصل في `RoleManagementController` و `RolesAndPermissionsSeeder`.
    - إنشاء وتشغيل مايجريشن `2026_10_18_230000_add_send_lab_whatsapp_permission.php` وتعيينها لأدوار `admin` و `lab_staff`.
    - حماية جميع أزرار وروابط الواتساب في الواجهات بـ `@can('send lab whatsapp')`: تظهر الصلاحية في شاشة تعديل الأدوار `/roles/{role}/edit` كخيار تفعيل/إطفاء ضمن بطاقة الواتساب المستقلة، وعند إلغاء تحديدها يختفي زر الواتساب فوراً من كافة شاشات المختبر لهذا الدور.
  * بناء وتشغيل الاختبارات الآلية: `tests/Feature/LabWhatsAppShareAndPublicReportTest.php` (7 passed, 25 assertions) واجتياز حزمة المختبر والصلاحيات بالكامل (`RolePermissionsMatrixTest`: 11 passed, 181 assertions).
- **المرحلة 78:** استيراد وتحديث كتالوج المختبر الشامل لـ 865 فحصاً وفك قيد تفرد الكود الطبي ومطابقة هوستنجر السحابية:
- **المرحلة 77:** تصحيح وتوحيد مسار زر العودة للكاشير في صفحة الوصل واستمارة دفع الطلبات الطبية:
- **المرحلة 76:** حل مشكلة إدخال نتائج الفحوصات المركبة (Sub-Tests) في المختبر، وتحديث واجهة تعديل التحليل:
  * حل مشكلة تحول الفحوصات المركبة الـ 6 (ABG, CBC_3DIFF, CBC_5DIFF, GSE, GUE, SEMEN) لقيم مفردة (سنجل): ترقية استعلام `$labTestMap` في `lab/show.blade.php` و `staff/requests/show-lab.blade.php` للبحث بالاسم والكود المختصر والأسماء البديلة (`CBC` -> `CBC_3DIFF`).
  * تصحيح مسارات AJAX في `resources/views/lab-tests/entry_settings.blade.php` لتصبح مسارات نسبية `route(..., [], false)` تجنباً لتضارب المنفذ وسياسة CORS بين المنفذ 8000 و 80.
  * حماية دوال مساعدة Blade بـ `if (!function_exists(...))` لمنع خطأ إعادة التصريح Fatal Error عند تكرار الرسم.
  * إضافة قسم تفاعلي كامل للتحكم بنمط الإدخال (قيمة مباشرة، قيم فرعية، مرفق، مرن) مع إظهار عدد القيم الفرعية ورابط إدارتها في شاشة تعديل الفحص `resources/views/lab-tests/edit.blade.php`.
  * استقبال وتحديث `result_entry_type` في `LabTestController@update` و `@store`.
  * تحديث نمط الفحوصات الـ 6 إلى `sub_tests` في قاعدتي البيانات المحلية (`mysql`) وهوستنجر السحابية (`remote_hostinger`).
- **المرحلة 87:** إصلاح باقاتي السريعة للمختبر وتفعيل الفلترة اللحظية الذكية لعرض فحوصات الباقة المختارة فقط في استشارية الطبيب (Doctor Consultation Quick Lab Packages Fix & Instant Package Filtering):
  * **إصلاح زر الباقة وعداد الفحوصات (`resources/views/doctors/visits/show.blade.php` & `app/Models/UserLabTestGroup.php`)**:
    - تصحيح علاقة استخراج فحوصات الباقة من `$pkg->tests` (التي كانت ترجع `null` وتسبب `data-tests="null"` وانهيار جافاسكريبت بخطأ `TypeError`) إلى `$pkg->labTests->pluck('id')` و `$pkg->labTests->pluck('name')`.
    - إضافة الـ Accessor المتوافق `getTestsAttribute()` في نموذج `UserLabTestGroup` لضمان عدم حدوث أي خطأ برمجي عند استدعاء `$pkg->tests`.
    - تصحيح عداد فحوصات الباقة ليظهر العدد الفعلي `{{ $pkg->labTests->count() }}` بدلاً من الصفر.
  * **الفلترة اللحظية التلقائية وعرض الفحوصات المختارة فقط عند النقر على الباقة**:
    - عند النقر على أي باقة، يتم تحديد (Check) كافة فحوصات الباقة آلياً، وتصفية شبكة التحاليل فورياً (`filterGrid`) لتُظهر **فحوصات الباقة المختارة فقط**، مما يسهل على الطبيب مراجعتها أو تعديلها دون الحاجة للبحث في شبكة الـ 600+ تحليل.
    - إضافة شريط إشعار تفاعلي أعلى الشبكة (`#docLabActiveFilterNotice`) يوضح اسم الباقة النشطة وعدد فحوصاتها مع زر بنقرة واحدة `[ إلغاء الفلترة وعرض كافة الفحوصات ]`.
    - النقر مجدداً على زر نفس الباقة المفعلة أو الانتقال بين الفئات يقوم تلقائياً بفك الفلترة واستعادة عرض كافة الفحوصات بسلاسة.
    - إضافة تبويب مخصص في شريط الفئات السريعة: **[ 🎯 المختارة فقط ]** (`docLabFilterSelectedBtn`) لتمكين الطبيب في أي لحظة من تصفية الشبكة وعرض جميع الفحوصات المحددة حالياً فقط.
  * **الاختبارات الآلية الحصينة**:
    - بناء واجتياز اختبار الميزة [tests/Feature/DoctorLabQuickPackagesTest.php](file:///C:/get/hospital-system/tests/Feature/DoctorLabQuickPackagesTest.php) بنجاح كامل (`2 passed, 9 assertions`) وتأكيد استقرار حزمة تشخيصات وزيارات الأطباء (`7 passed, 28 assertions`).
- **المرحلة 86:** ترقية موضع التشخيص الطبي ICD-10 لكارت عريض بارز وفصل حقل التوصيات وإلغاء التعبئة التلقائية (Doctor Consultation ICD-10 Full-Width Prominence & Clean Clinical Notes):
  * **فصل التوصيات وإلغاء التعبئة التلقائية (`show.blade.php`)**:
    - إلغاء السطر البرمجي في جافاسكريبت الذي كان يقوم تلقائياً بنسخ اسم التشخيص داخل حقل "تفاصيل وتوصيات التشخيص" (`diagnosis[description]`). أصبح الحقل نظيفاً ومفرغاً تماماً ليكتب فيه الطبيب نصائحه وحميته وتوصياته السريرية الخاصة دون تكرار أو فرض.
  * **ترقية موضع وهيئة التشخيص الطبي المعتمد (Full-Width Hero Section)**:
    - نقل قسم التشخيص الطبي من العمود النصفي الضيق إلى **كارت عريض مستقل (`col-12`)** بعرض الصفحة أسفل شريط العلامات الحيوية مباشرة.
    - توسيع حقل البحث ليصبح كبيراً وفاخراً (`input-group-lg`) مع وضوح بصري تام.
    - ترقية القائمة المنسدلة الذكية للنتائج المقترحة: عرض كامل 100%، طبقة عليا فائقة (`z-index: 99999 !important`)، إطار كحلي واضح (`2px solid #005a9c`)، ظلال بارزة وخلفية بيضاء نقية، ومنع أي قص (`overflow: visible !important`)، مع وضوح تام لرمز الفحص والاسم العربي بالخط العريض والاسم الإنكليزي وشارة التصنيف.
    - بطاقة التشخيص المختار النشطة في سطر عريض واضح مع زر مباشر لتغيير أو إلغاء التشخيص.
  * **الصف الثنائي المتوازن للملاحظات السريرية**:
    - توزيع الفحص السريري (Physical Examination) في الجانب الأيمن، وتفاصيل وتوصيات التشخيص (Clinical Notes & Advice) في الجانب الأيسر بارتفاع متناسق ومساحة مريحة للكتابة.
  * **الاختبارات الآلية الحصينة**:
- **المرحلة 95 (2026-10-21):** حظر الفحوصات المعطلة ومنع تكرار التحاليل في واجهة المختبر والكاشير ومطابقة طلبات الطبيب 1-to-1 دون المساس بسجلات قاعدة البيانات (Lab & Cashier Active-Only Filtering, Name Deduplication & 1-to-1 Doctor Order Fidelity):
  * **تطبيق مبدأ مطابقة طلبات الطبيب 1-to-1 ومنع ربط الفحوصات المعطلة (`app/Http/Controllers/DoctorVisitController.php`)**:
    - قصر جلب واستعراض التحاليل للطبيب على الفحوصات المفعلة الفريدة بالاسم الصافي (`where('is_active', true)->unique(name)`).
    - عند حفظ طلب الطبيب: استعلام الفحوصات المفعلة فقط (`where('is_active', true)`) وأخذ أول معرف مفعل لكل فحص، مما يمنع نهائياً سحب أو تخزين معرفات الفحوصات المعطلة أو المكررة في `details['lab_test_ids']`.
    - في دالة حذف الفحص الفردي `removeRequestItem`: البحث عن كافة المعرفات المتطابقة وحذفها بالكامل لضمان نظافة الطلب.
  * **تصفية واجهة محطة المختبر وإلغاء التكرار الصارم (`resources/views/lab/show.blade.php`)**:
    - في دالة `buildSelectedLabTests`: استبعاد أي فحص معطل (`!$labTest->is_active`) من الظهور أمام فني المختبر حتى لو كان معرّفه مخزناً في طلبات قديمة.
    - تطبيق إلغاء التكرار الصارم الموحد بناءً على الاسم الصافي مع توحيد الأحرف والمسافات (`Normalized Case-Insensitive Unique Name`)، مما يضمن ظهور كل فحص اختاره الطبيب في سطر واحد فقط دون أي تكرار.
    - ترتيب جلب التحاليل تنازلياً حسب التفعيل (`orderByRaw('is_active DESC')`) لضمان ربط الفحوصات الفرعية ونطاقات المراجع بالسجل المفعل المعتمد دائماً.
  * **حماية حسابات الكاشير وطباعة الوصل الحراري 80mm (`app/Http/Controllers/CashierController.php` & `resources/views/cashier/receipt-print.blade.php`)**:
    - في احتساب مبالغ الكاشير: استبعاد أي فحص معطل (`!$test->is_active`)، وتتبع أسماء الفحوصات (`$seenLabTestNames`) لمنع احتساب نفس الفحص مرتين.
    - في طباعة الوصل الحراري: عدم طباعة الفحوصات المعطلة، وتصفية الأسطر بالاسم الصافي لضمان عدم تكرار اسم التحليل في الوصل تحت أي ظرف.
    - في نموذج الطلب `app/Models/Request.php`: قصر جمع الأسعار على الفحوصات المفعلة واستبعاد التكرارات.
  * **حماية البيانات التاريخية (Zero Data Deletion)**:
    - الحفاظ الكامل على كافة سجلات جدول `lab_tests` دون حذف أي سطر لحماية البيانات التاريخية والتقارير القديمة.
  * **الاختبارات الآلية الحصينة ([tests/Feature/LabAndCashierDeduplicationAndActiveOnlyTest.php](file:///C:/get/hospital-system/tests/Feature/LabAndCashierDeduplicationAndActiveOnlyTest.php))**:
    - إنشاء واجتياز حزمة اختبارات ميزة تغطي: ربط الفحص المفعل فقط عند طلب الطبيب، منع ظهور الفحص المعطل ودمج التكرار في شاشة المختبر، واستبعاد المعطل ومنع التكرار في الكاشير والوصل الحراري (`3 passed, 13 assertions`).
- **المرحلة 94 (2026-10-21):** تصحيح أسعار الخدمات الفردية في الوصل الحراري 80mm وإلغاء تكرار المجموع الكلي وعرض المبلغ المطلوب من المريض فقط (Cashier 80mm Thermal Receipt Line-Item Patient Fee Fix & Grand Total Duplication Removal):
  * **معالجة تكرار المجموع الإجمالي أمام كل خدمة (`resources/views/cashier/receipt-print.blade.php`)**:
    - إلغاء الشرط القديم `empty($payment->patient_share) ? $item['price'] : $payment->amount` الذي كان يطبع المجموع الكلي للوصل مكرراً في عمود المبلغ أمام كل خدمة عند وجود حصة مريض (الضمان الصحي).
    - طباعة السعر الفعلي لكل خدمة في عمود المبلغ `$item['price']` والمطابق لما يدفعه المريض لكل فحص في كاشير النظام.
  * **حساب حصة المريض الدقيقة لكافة الخدمات الفردية في الوصل**:
    - تحاليل المختبر وفحوصات الأشعة الإضافية والمنفردة: احتساب `calculateInsurancePricing()['patient_share']` بدلاً من السعر المعتمد الكامل.
    - باقات المختبر واستشارات الطوارئ وخدمات الطوارئ: تطبيق نسبة التحمل بدقة متناهية (`copay_percentage`).
    - النتيجة: مجموع أسطر الخدمات في جدول الوصل يطابق تماماً "المبلغ المدفوع" في قاع الوصل بدون أي زيادة أو نقص.
  * **الاختبارات الآلية الحصينة ([tests/Feature/CashierPrintReceiptItemPriceTest.php](file:///C:/get/hospital-system/tests/Feature/CashierPrintReceiptItemPriceTest.php))**:
    - بناء واجتياز اختبار ميزة متكامل يتحقق من ظهور الأجور الفردية المطلوبة من المريض حصراً أمام كل فحص وعدم تكرار المجموع الإجمالي في أسطر الجدول (`1 passed, 7 assertions`).
- **المرحلة 92 (2026-10-21):** تمكين الطبيب من حذف وتعديل الفحوصات الفردية من الطلبات غير المسددة وإخفاء وطي الطلبات الملغاة من قائمة اليوم (Doctor Item-Level Request Modification & Cancelled Archive Accordion):
  * **تمكين الطبيب من حذف فحص فردي من الطلب (`DoctorVisitController@removeRequestItem`)**:
    - إضافة مسار آمن `DELETE /doctor/requests/{request}/remove-item`.
    - التحقق من ملكية الطبيب للزيارة وعدم سداد الطلب (`payment_status !== 'paid'`).
    - عند حذف تحليل مختبر: إزالته من `tests` و `lab_test_ids` وإعادة احتساب `total_amount` بالأسعار الرسمية ومطابقتها بالتأمين إن وجد.
    - عند حذف فحص أشعة: إزالته من `radiology_types` و `radiology_type_ids` و `radiology_targets` وتحديث وصف الطلب والمبلغ الإجمالي.
    - عند حذف الفحص الأخير من الطلب: تحويل حالة الطلب تلقائياً إلى ملغي (`cancelled`) وحفظ سبب الإلغاء.
  * **ترقية واجهة بطاقات الطلبات في شاشة الطبيب (`resources/views/doctors/visits/show.blade.php`)**:
    - **شريط مسار ومراحل الفحص التفاعلي (Status Progress Stepper)**: شريط مصغر بأعلى كل بطاقة يوضح مراحل الإنجاز الأربعة: `[ طلب الطبيب ⬅️ سداد الكاشير ⬅️ تنفيذ الفحص بالمختبر/الأشعة ⬅️ النتيجة جاهزة ]` مع ألوان وأيقونات حية.
    - **الجدول السريري المصغر (Clinical Mini-Table)**: استبدال الشارات بجدول سطري منظم يعرض: رقم التسلسل، اسم الفحص، العضو المستهدف (للأشعة)، شارة الحالة، وزر الحذف `[×]` التفاعلي مع حذف AJAX السلس.
    - تمييز بصري فوري للبطاقات حسب الحالة: إطار أصفر وBadge واضح للطلبات غير المسددة، إطار أزرق للمدفوعة، وإطار أخضر للمكتملة.
    - إخفاء تام للطلبات الملغاة من القائمة النشطة لمنع تشتيت الطبيب، وجمعها في أسفل التبويب داخل قسم مطوي أنيق: `[ 📁 أرشيف الطلبات الملغاة (مخفية) ]`.
  * **الاختبارات الآلية الحصينة (`tests/Feature/DoctorRemoveRequestItemTest.php`)**:
    - اجتياز كامل لـ 5 اختبارات ميزة تغطي حذف تحليل، حذف آخر تحليل وإلغاء الطلب، حذف فحص أشعة، حظر الحذف بعد الدفع، وإخفاء الملغاة (`5 passed, 23 assertions`).
- **المرحلة 91:** توحيد سداد ووصول وتجميع طلبات الزيارة الطبية في الكاشير والدمج التلقائي وإلغاء التكرار (Doctor & Cashier Unified Visit Checkout, Single Thermal Receipt & Consolidated Index Table):
  * **الدمج التلقائي لطلبات الطبيب غير المسددة (`DoctorVisitController`)**:
    - دمج تلقائي فور حفظ الطبيب لأي فحوصات مخبرية جديدة في نفس الطلب غير المسدد `payment_status === 'pending'`.
    - دمج تلقائي فور حفظ الطبيب لأي فحوصات شعاعية جديدة في نفس طلب الأشعة غير المسدد لمنع تكرار وتعدد الطلبات المعلقة في النظام.
  * **تعديل كادر المختبر والأشعة للفحوصات قبل السداد وتثبيتها بعده (`StaffRequestController`)**:
    - إضافة إمكانية إضافة وحذف تحاليل للمختبر (`appendLabTests`, `removeLabTest`).
    - إضافة إمكانية إضافة وحذف فحوصات للأشعة (`appendRadiologyTypes`, `removeRadiologyType`).
    - حظر وإقفال التعديل فور السداد `payment_status === 'paid'` لحماية الحسابات.
  * **سداد موحد شامل لكافة خدمات الزيارة في الكاشير (`CashierController`)**:
    - في نافذة سداد الطلبات (`showRequestPaymentForm`): سحب كافة طلبات الزيارة المعلقة (مختبر + أشعة) تلقائياً في فاتورة واحدة شاملة.
    - في معالجة الدفع (`processRequestPayment`): دفع جميع الطلبات في حركة مالية واحدة وسجل دفع واحد `Payment` وتحديث كافة الطلبات إلى `paid` مع ربطها بنفس `payment_id`.
  * **وصل حراري 80mm واحد شامل للمريض بدون أي تكرار (`receipt-print.blade.php` & `receipt.blade.php`)**:
    - طباعة وصل حراري موحد يحمل رقم تسلسلي واحد `REC-...` ويشمل تحاليل المختبر وفحوصات الأشعة معاً.
    - تطبيق فلترة وإلغاء التكرار الصارم (`Deduplication`) لضمان عدم تكرار اسم أي فحص مهما كانت السجلات القديمة.
  * **تجميع الطلبات المعلقة في جدول الكاشير (`resources/views/cashier/index.blade.php`)**:
    - دمج أسطر الطلبات المعلقة لنفس الزيارة في سطر واحد موحد يحمل شارات الأقسام المشتركة `[🔬 تحاليل] [🩻 أشعة] [طلب موحد]`.
    - استبدال الأزرار المتعددة بزر واحد: `تسديد شامل` يمنع تكرار ظهور نفس المريض بمبالغ متطابقة.
  * **الاختبارات الآلية الحصينة**:
    - `tests/Feature/DoctorLabRequestAutoMergeAndLabEditTest.php` (5 passed)
    - `tests/Feature/DoctorRadiologyRequestAutoMergeAndStaffEditTest.php` (5 passed)
    - `tests/Feature/UnifiedVisitCheckoutCashierTest.php` (2 passed)
    - اجتياز كامل للحزمة (`12 passed, 78 assertions`).
- **المرحلة 85:** محرك البحث الذكي اللحظي لتشخيصات ICD-10 بالأعمدة الثلاثة وترقية تبويب الفحص السريري والتشخيص في عيادات الاستشارية (Doctor Outpatient ICD-10 Live 3-Column Search & Balanced Tab Layout):
  * **محرك البحث اللحظي بالأعمدة الثلاثة (`DoctorVisitController@searchIcd10`)**:
    - بحث ذكي فوري بالرمز الطبي (`code`) أو الاسم العربي (`description_ar`) أو الاسم الإنجليزي (`description`) مع ترتيب الأولوية للمطابقة الدقيقة وبداية النصوص.
    - مسار سريع محمي: `GET /doctor/icd10/search?q=...`، لا يُرجع أي نتائج ولا يُظهر القائمة إذا كان الحقل فارغاً (`q < 1`).
  * **ترقية تبويب الفحص والتشخيص (`resources/views/doctors/visits/show.blade.php`)**:
    - تحويل التبويب من التكديس الرأسي الطويل إلى layout منظم وعصري:
      1. **شريط العلامات الحيوية الرقمية (Digital Vitals Strip)**: بطاقات مصغرة متناسقة لكافة المؤشرات الحيوية (الضغط، النبض، الحرارة، التنفس، الأكسجين، الوزن، الطول).
      2. **حاسبة مؤشر كتلة الجسم الحية (Live BMI Calculator)**: حساب آلي فوري لمؤشر كتلة الجسم فور إدخال الوزن والطول وإظهار شارة ملونة ذكية (طبيعي / نقص وزن / وزن زائد / سمنة).
      3. **الصف الثنائي المتوازن (Two Balanced Columns)**:
         - الجانب الأيمن: الشكوى الرئيسية والفحص السريري (Physical Examination) بمساحة كتابة مريحة وواضحة.
         - الجانب الأيسر: التشخيص الطبي المعتمد (ICD-10) متضمناً حقل البحث مع شارات اللغات، بطاقة تأكيد التشخيص المختار النشطة (`selected_diagnosis_card`) مع زر مسح سريع، وملاحظات التشخيص الإضافية.
      4. **شريط الحفظ السفلي الموحد**: زر حفظ ثابت ومريح وتنبيهات حفظ سريعة.
  * **عرض التشخيص في وضع القراءة فقط (`show-readonly.blade.php`)**:
    - إظهار كود التشخيص بشارة ملونة واسم التشخيص العربي والإنجليزي والملاحظات بتنسيق سريري راقي.
  * **الاختبارات الآلية الحصينة**:
    - بناء واجتياز اختبار الميزة الجديد [tests/Feature/DoctorIcd10SearchTest.php](file:///C:/get/hospital-system/tests/Feature/DoctorIcd10SearchTest.php) بنجاح كامل (`6 passed, 24 assertions`).
- **المرحلة 72:** إبراء الذمة المالية وقفل إجراءات الطوارئ وإلغاء الحالات النشطة واسترجاع الكاشير وعزل الحالات حسب دور المستخدم وإخفاء الملغاة من سجلات اليوم والنشطة:
  * إبراء الذمة المالية: قفل التقرير الطبي وقفل قائمة الإجراءات والتحويل والتخريج بالكامل حتى تسديد الرسوم في الكاشير.
  * إلغاء حالات الطوارئ: تمكين إلغاء الحالات النشطة مع الحصانة التشغيلية وإحالة الحالات المسددة لمستحقات الاسترجاع في الكاشير.
  * الكاشير والخزينة: معالجة الاسترجاع التلقائي (`refund`) وتحديث المايجريشن لحقل `payment_status` بإضافة `'refunded'` وتثبيت زر الصرف بـ `data-*` attributes واستماع مفوض.
  * عزل صلاحيات وسجلات الطوارئ: قصر الدخول على المدير وأطباء الطوارئ، وحصر عرض الحالات للأطباء على مرضاهم فقط (`doctor_id` أو `consultant_doctor_id`) مع عزل الإحصائيات ومنع استعراض مرضى الأطباء الآخرين.
  * إخفاء الحالات الملغاة: إخفاؤها تماماً من تبويب "حالات اليوم" وتبويب "النشطة حالياً"، وإتاحة عرضها في "السجل الكامل" فقط كحالات ملغاة ومغلقة وغير قابلة للفتح (`EmergencyFinancialClearanceAndCancellationTest`: 6 passed, 32 assertions).
- **المرحلة 73:** منظومة تسعير الضمان الصحي (HIA) وشرائح العمر الحملي للخدج والحاضنات ومصفوفة التحمل المالي الوزارية:
  * قاعدة البيانات: حقول `hi_price`, `is_hi_active`, `official_claim_name` للباقات الـ 8، وحقول `insurance_type`, `policy_category`, `gestational_age`, `patient_share_amount`, `insurance_share_amount` للحجوزات والسجلات السريرية.
  * محرك الاحتساب المالي (`NicuBillingService.php`): تطبيق مصفوفة شرائح العمر الحملي الأربعة لهيئة الضمان الصحي (≥34 أسبوع، 32-34 أسبوع، 28-32 أسبوع، و<28 أسبوع)، والفئة A (إعفاء 100%)، ورقود الردهة (90% ضمان / 10% مريض).
  * واجهة تسعير الخدمات (`/nicu/services`): جدول أسعار الكاش مقابل الضمان المعتمد والمطالبات، مع بطاقة إرشادية بارزة للمصفوفة الوزارية.
  * استمارة الحجز التكيفية (`/incubator-reservations/create`): مفتاح تحويل فوري بين الكاش والضمان الصحي، مع استعراض حي وتفاعلي لنسب التحمل والأسعار المعتمدة فوراً.
  * كشف الحساب التراكمي الشفاف (`/nicu/{reservation}/statement`): إبراز بيانات الوثيقة ورقم المطالبة وتفصيل مساهمة الضمان مقابل المستحق نقداً في الصندوق.
  * البحث المباشر السريع وتخصيص اسم الخديج والبحث الذكي عن الأطباء: إضافة نقطة نهاية `/incubator-reservations/search-patients`، إلغاء شرط العمر لحجز الحاضنة باسم الأم، تخصيص اسم الخديج، تفعيل البحث الذكي عن الطبيب المشرف بالاسم والتخصص، ومربع البحث التفاعلي الفوري عن طبيب الاستشارة الداخلي مع إخفاء حقل التخصص الدقيق في الاستشارة الداخلية.
  * محطة الفحوصات والوصفات الطبية وسجل إعطاء العلاج MAR للخدج (`/nicu/reservations/{reservation}`):
    - تفكيك التبويب العام إلى 3 محطات سريرية مستقلة مطابقة لمحطة الطبيب الاستشاري:
      1. 🧪 **المختبر والتحاليل**: باقات سريعة بنقرة واحدة (دخول، يرقان، ABG، إنتان، كلى)، بحث فوري، صينية شرائح (Chips)، أولوية، وتوثيق النتائج.
      2. 🩻 **الأشعة والسونار والإيكو**: فحوصات شائعة (أشعة صدر نقالة بالحاضنة، سونار دماغ، إيكو قلب، أشعة بطن)، بحث فوري، شرائح، وتوثيق التقارير الشعاعية.
      3. 💊 **الوصفة والعلاج الدوائي (E-Prescriptions)**: محرر أدوية ذكي من كتالوج الأدوية الشامل (3,728 صنف)، الجرعة، طريقة الإعطاء، التكرار، المدة، مع صفحة طباعة رسمية أنيقة للوصفة (RX Print).
    - 💉 **سجل إعطاء العلاج التمريضي (MAR - Medication Administration Record)**: نموذج سريري مستقل داخل محطة التمريض لتوثيق الجرعات المعطاة للطفل (اسم العلاج، الجرعة، المسار، الوقت، والملاحظات) مع جدول زمني؛ مفصول تماماً وبشكل صارم عن طلبات الصيدلية والمخزون المالي.
  * الاختبارات الآلية وضمان الجودة: اجتياز 29 اختباراً في `NicuWorkflowTest.php` بنجاح 100% (210 assertions)، وتحديث الشجرة المعرفية Graphify (5,049 عقدة و 8,597 علاقة).
  * ربط طلبات الخدج بالمختبر والأشعة المركزية والقيد المالي الموحد للكاشير (2026-10-21):
    - 🧪 **توجيه ومطابقة طلبات المختبر والأشعة إلى المحطات المركزية (`MedicalRequest` Bundle)**: تحويل طلبات الخدج إلى حزم طلبات طبية مركزية موحدة بجدول `requests` مرتبطة بـ `incubator_reservation_id` (وعزل تام عن كاشير العيادات بـ `visit_id = null`)، مع الحفاظ على التزامن التوافقي مع `nicu_medical_orders`.
    - 🩻 **اعتماد محطة إدخال النتائج وقوالب الطباعة الرسمية (`lab.show`, `radiology-staff.show`)**: ظهور طلبات الخدج في لوحتي المختبر والأشعة بتبويب `[ 👶 الخدج والحاضنات ]`، ودخول فني المختبر والأشعة إلى واجهات الإدخال الرسمية الكاملة للمستشفى التي تدعم كافة الفحوصات الفرعية والمكونات (CBC: WBC, RBC, HGB, PLT)، والمعدلات الطبيعية، وقوالب تقارير الأشعة (Findings, Impression, Recommendations) وطباعة النتائج الرسمية A5.
    - 📋 **محطة طبيب الخدج السريرية وبطاقات المتابعة التفاعلية (`resources/views/nicu/show.blade.php`)**: بناء بطاقات متابعة متقدمة وشريط مسار مكون من 4 مراحل (طلب الطبيب ⬅️ حساب الحاضنة ⬅️ القسم المركزي ⬅️ النتيجة جاهزة)، مع جدول نتائج المكونات التفصيلي وتمييز الحالات الحرجة 🚨، وصندوق تقرير الأشعة المعتمد ومرفقات الأفلام والصور.
    - 💰 **قيد مالي موحد وديناميكي للكاشير (`nicu_payments`)**: عزل مدفوعات الخدج في جدول مستقل `nicu_payments`، وتجميع الفاتورة التراكمية في تبويب مستقل بالكاشير مع إصدار وصولات حرارية رسمية 78mm للأمانات وتحديث كشف الحساب التراكمي.
    - 🛡️ **الاختبارات الآلية وضمان الجودة**: اجتياز 32 اختباراً شاملاً بنجاح 100% (274 تأكيداً) في `NicuWorkflowTest` و `NicuLabRadiologyCashierWorkflowTest`، وتحديث الشجرة المعرفية Graphify إلى 5,374 عقدة و 9,591 علاقة. بقاء كافة التعديلات محلياً على فرع `hr` دون أي رفع لـ GitHub وفقاً لتوجيهات المستخدم.

- **المرحلة 98:** حل تضارب الباراميترات الفرعية للمختبر وعزل فحص الإدرار العام (G.U.E) في صفحة طباعية مستقلة ومطابقة كافة الفحوصات والمرفقات 100% (Lab Subtest Collision Fix, Dedicated GUE Page Break, Clinical Grouping & 100% Ordered Tests Coverage):
  * **معالجة تضارب مفاتيح إدخال الباراميترات الفرعية ([resources/views/lab/show.blade.php](file:///C:/get/hospital-system/resources/views/lab/show.blade.php) & [resources/views/staff/requests/show-lab.blade.php](file:///C:/get/hospital-system/resources/views/staff/requests/show-lab.blade.php))**:
    - تحويل مفاتيح الإدخال للباراميترات الفرعية من اسم الفحص المشترك إلى مفتاح فريد ومميز بمعرف الباراميتر: `test_results[sub_{{ $subTest->id }}][value]`.
    - حل مشكلة مسح الفحوصات التي تشترك في نفس أسماء المعايير الفرعية (مثل اشتراك FSH و LH في معيار `Men` و `Follicular Phase` الخ) نهائياً ومنع استبدال القيم أثناء الإرسال (`$_POST`).
    - ترقية استرجاع القيم المحفوظة في الشاشة عبر الفهرسة المتعددة (`$dbResultsBySubId` و `$dbResultsByParentAndName`) لضمان ظهور القيم المحفوظة لجميع التحاليل بدقة متناهية.
  * **إفراد فحص الإدرار العام (General Urine Examination - G.U.E) في صفحة طباعية مستقلة ([resources/views/staff/requests/print.blade.php](file:///C:/get/hospital-system/resources/views/staff/requests/print.blade.php))**:
    - عزل فحص الإدرار العام (19 فحصاً فرعياً) عن الفحوصات العامة (الهرمونات، الدم، الكيمياء) وتطبيق فاصل صفحات طباعي تلقائي: `.gue-page-break` (`page-break-before: always; break-before: page;`).
    - إذا كان الطلب يحتوي على G.U.E فقط دون تحاليل أخرى، يُطبع في صفحة واحدة مباشرة دون إحداث صفحة بيضاء سابقة.
    - عرض نتائج فحص الإدرار العام الـ 19 مباشرة وبشكل سلس في جدول الصفحة المخصصة بدون أي تقسيمات فرعية متكلفة مع ترويسة أعمدة واضحة `[ Test Description | Result | Status ]`.
    - تزويد كل من صفحة الفحوصات العامة وصفحة فحص الإدرار المستقلة بختم وتوقيع معتمد ومستقل بأعلى معايير الجودة الطبية.
  * **مطابقة الفحوصات المطلوبة بنسبة 100% وإظهار مرفقات الأجهزة (CBC) والفحوصات المعلقة**:
    - رصد ومطابقة كافة الفحوصات المطلوبة من قبل الطبيب في التقرير المطبوع.
    - الفحوصات المرفقة كتقارير أجهزة (مثل Complete blood count & Differential) يظهر لها سطر رسمي بارز ومميز في جدول النتائج المطبوعة: `Complete blood count & Differential` | `📎 تقرير الجهاز مرفق (Attached Device Report)` | شارة `Attached`.
    - الفحوصات التي لم تُدخل نتائجها بعد تظهر بشارة `Pending (قيد الإنجاز)` ولا تختفي أو تسقط من التقرير.
  * **تحديث وإكمال نتائج الطلب رقم 285 للمريض جاسم حامد عباس**:
    - حفظ وتثبيت قيمة فحص FSH للرجال (`3.5 mIU/mL`) بجانب فحص LH (`2.72 mIU/mL`) بنجاح، وظهور كافة الفحوصات الـ 8 للمريض كاملة في شاشة المختبر والتقرير المطبوع.
  * **الاختبارات الآلية الحصينة ([tests/Feature/LabGeneralUrinePageBreakAndCollisionFixTest.php](file:///C:/get/hospital-system/tests/Feature/LabGeneralUrinePageBreakAndCollisionFixTest.php))**:
    - بناء واجتياز اختبارات ميزة متكاملة تؤكد منع التضارب بين FSH و LH، وتؤكد عزل G.U.E بفاصل الصفحة وظهور تقرير CBC المرفق وكافة الفحوصات بنجاح (`2 passed, 17 assertions`)، مع اجتياز كامل حزمة فحوصات المختبر (`65 passed, 324 assertions`).

- **المرحلة 97:** مطابقة آلية طلبات طبيب الطوارئ مع الاستشارية: الدمج التلقائي، الحذف الفردي، شريط التقدم السريري، وأرشيف الطلبات الملغاة (Emergency Doctor Request Auto-Merge, Item-Level Removal, Clinical Stepper & Cancelled Archive):
  * **الدمج التلقائي لطلبات المختبر والأشعة للطوارئ ([app/Http/Controllers/EmergencyController.php](file:///C:/get/hospital-system/app/Http/Controllers/EmergencyController.php))**:
    - عند قيام طبيب الطوارئ بطلب تحاليل مختبرية جديدة لحالة طوارئ لديها طلب معلق غير مسدد (`payment_id === null` و `status === 'pending'`)، يتم دمج الفحوصات الجديدة تلقائياً في نفس الطلب عبر `syncWithoutDetaching` بدلاً من تشتيت الكاشير بطلبات متعددة.
    - نفس الدمج التلقائي الذكي يطبق على طلبات الأشعة والسونار غير المسددة.
    - تحديث وحساب المبالغ المستحقة للكاشير تلقائياً بعد كل دمج عبر `upsertUnifiedEmergencyPayment`.
  * **تمكين طبيب الطوارئ من حذف وتعديل الفحوصات الفردية ([app/Http/Controllers/EmergencyController.php](file:///C:/get/hospital-system/app/Http/Controllers/EmergencyController.php) & [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
    - إضافة مسار ودالة الحذف الفردي `DELETE /emergency/{emergency}/remove-request-item`.
    - قفل وحظر الحذف التام إذا كان الطلب مسدداً لدى الكاشير (`payment_id !== null` أو حالة الطلب مسدد) لحماية الصندوق والحسابات برمز `422 Unprocessable Entity`.
    - فك ارتباط الفحص المطلوب فقط من جدول الربط الوسيط (`detach`).
    - في حال حذف الفحص الأخير المتبقي في الطلب، يتم تحويل حالة الطلب تلقائياً إلى ملغي (`cancelled`) وتوثيق سبب الإلغاء، مع تحديث استحقاق الكاشير.
  * **ترقية شاشة الطوارئ بواجهة الاستشارية الحديثة ([resources/views/emergency/show.blade.php](file:///C:/get/hospital-system/resources/views/emergency/show.blade.php))**:
    - **شريط مسار ومراحل الفحص التفاعلي (Status Progress Stepper)**: شريط مصغر بأعلى كل بطاقة يوضح مراحل الإنجاز: `[ 1. طلب الطبيب ⬅️ 2. عند الكاشير / سُدّد ⬅️ 3. قيد الفحص بالمختبر/الأشعة ⬅️ 4. النتيجة جاهزة ]` مع شارات وألوان تفاعلية.
    - **الجدول السريري المصغر (Clinical Mini-Table)**: استبدال طريقة العرض القديمة بجدول سطر منظم وأنيق يعرض: رقم التسلسل، اسم الفحص، شارة الحالة الحية، وزر الحذف `[ × ]` التفاعلي للأطباء قبل السداد عبر AJAX سلس دون إعادة تحميل الصفحة.
    - **أرشيف الطلبات الملغاة**: عزل وإخفاء الطلبات الملغاة من القائمة النشطة ووضعها داخل أكورديون مطوي أنيق: `[ 📁 أرشيف طلبات المختبر/الأشعة الملغاة (مخفية) ]`.
    - تصفية اللوحة الجانبية لمريض الطوارئ (`side-cur-pane`) لإخفاء الطلبات الملغاة من قائمة الإجراءات النشطة.
  * **الاختبارات الآلية الحصينة ([tests/Feature/EmergencyRequestItemModificationAndAutoMergeTest.php](file:///C:/get/hospital-system/tests/Feature/EmergencyRequestItemModificationAndAutoMergeTest.php))**:
    - بناء واجتياز 7 اختبارات ميزة متكاملة تغطي الدمج التلقائي للمختبر والأشعة، الحذف الفردي، تحول الطلب لملغي عند إفراغه، حظر الحذف بعد السداد، وظهور الـ Stepper والجدول المصغر وأرشيف الملغاة (`7 passed, 38 assertions`)، مع اجتياز كامل حزمة الطوارئ بنجاح (`40 passed, 209 assertions`).

- **المرحلة 101:** نقل مسار تحميل مرفقات المختبر للمسارات العامة المحمية برمز التوكن لمنع مطالبة المريض بتسجيل الدخول (Lab Attachment Route Token-Protected Public Access Fix):
  * **نقل مسار تحميل المرفقات للمسارات العامة المحمية ([routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
    - نقل المسار `Route::get('/lab/requests/{request}/attachment')` خارج مجموعة ميدلوير تسجيل الدخول `Route::middleware(['auth'])` ووضعه مباشرة بجانب مسار التقرير العام `lab.public-report`.
    - حل مشكلة مطالبة المريض بتسجيل الدخول أو إعادة توجيهه لصفحة `/login` عند النقر على زر تحميل تقرير الجهاز (PDF).
    - حماية المسار بكنترولر المختبر [app/Http/Controllers/LabStaffController.php](file:///C:/get/hospital-system/app/Http/Controllers/LabStaffController.php) عبر الرمز المشفر (`?token=...`) بحيث يفتح للمريض بنقرة واحدة، ويُحظر أي وصول غير مصرح به بدون التوكن الصحيح برمز `403 Forbidden`.
  * **حزمة الاختبارات الآلية الحصينة ([tests/Feature/LabWhatsAppShareAndPublicReportTest.php](file:///C:/get/hospital-system/tests/Feature/LabWhatsAppShareAndPublicReportTest.php))**:
    - بناء وتأكيد اختباري ميزة لتنزيل المريض للمرفق بدون تسجيل دخول وحظر الدخول بدون رمز، مع اجتياز كامل لجميع اختبارات المختبر وحزمة النظام (`68 passed, 329 assertions`).

- **المرحلة 109:** حماية كشفية نفس اليوم من التكرار والازدواجية، وأتمتة مراجعة النتائج المجانية، ودمج فحوصات اليوم في محطة الطبيب، وتنظيف قيود هوستنجر المكررة (Same-Day Duplicate Consultation Prevention, Automatic Free Results Recheck, Workstation Requests Aggregation & Hostinger Duplicate Cleanup):
  * **أتمتة مراجعة النتائج ومنع الحجز المكرر لنفس اليوم ([app/Http/Controllers/InquiryController.php](file:///C:/get/hospital-system/app/Http/Controllers/InquiryController.php) & [app/Http/Controllers/AppointmentController.php](file:///C:/get/hospital-system/app/Http/Controllers/AppointmentController.php))**:
    - فحص تلقائي لحظي: إذا دخل مريض ولديه كشفية سابقة في نفس اليوم لدى نفس الطبيب، يتم تحويل الحجز تلقائياً إلى "مراجعة نتائج مجانية" برسم `consultation_fee = 0` و `is_free_recheck = true` وربطه بالزيارة الأصلية دون استيفاء أي مبالغ إضافية من المريض.
    - حماية الكاشير والصندوق من ازدواجية وصولات الكشفية وضمان تطابق أعداد المراجعين الفعليين.
- **المرحلة 278:** لوحة القيادة المستقلة لرقابة مخازن وعُهد ردهات التمريض وإدارة أمناء العهد وتتبع صرفيات المرضى (Floor Ward Nursing Sub-Stores & Custody Governance Dashboard):
  * **تأسيس البنية وقاعدة البيانات ([database/migrations/2026_11_23_210900_create_nursing_sub_stores_governance.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_11_23_210900_create_nursing_sub_stores_governance.php))**:
    - إضافة حقل `custodian_id` لجدول `locations` لتعيين أمين العهدة المسؤول عن المخزن الفرعي للردهة.
    - إنشاء جدول سجل تاريخ تسليم واستلام العهدة `location_custodian_histories` لتوثيق التحويلات، الآمر بالتكليف، التوقيت، والملاحظات.
    - إنشاء وتثبيت صلاحية `manage nursing sub stores` وربطها برئيس ومشرف التمريض (`head_nurse`) وأدوار الإدارة.
  * **التحكم والفلترة الدقيقة لردهات التمريض ([app/Http/Controllers/NursingSubStoreController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/NursingSubStoreController.php))**:
    - حصر شاشة الرقابة حصراً بمخازن ردهات الطوابق السريرية (الطابق الثاني، الثالث، الرابع، الخامس) بالاعتماد على ربط الممرضين بـ `users.location_id` واستبعاد العمليات والخدج والأقسام الإدارية والخدمية.
    - دالة `assignCustodian()` لتعيين وتبديل مسؤول العهدة وتحديث موقعه وتوثيق الأرشيف الرقابي.
    - دالة `show()` لعرض الأرصدة المتوفرة، حركات صرفيات المرضى، أرشيف تسليم واستلام العهدة، وطلبيات المخزن.
  * **الواجهات التفاعلية والتقسيم الذكي لكادر الردهة ([resources/views/nursing-sub-stores/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/nursing-sub-stores/index.blade.php), [resources/views/nursing-sub-stores/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/nursing-sub-stores/show.blade.php))**:
    - بطاقات إحصائية (KPIs) في القمة: المخازن النشطة، إجمالي المواد بالعهد، صرفيات المرضى الشهرية، والطلبيات المعلقة.
    - جدول متكامل لردهات الطوابق مع شارات أمناء العهدة، عداد كادر التمريض، الرصيد، وعدد الحركات.
    - نافذة Modal لتكليف وتبديل المسؤول مع تصنيف واقتراح ذكي يعرض أولاً كادر تمريض نفس الردهة المقيدين على الطابق بشاشة المستخدمين ثم باقي كادر التمريض بالمستشفى.
  * **الاختبارات الآلية الحصينة ([tests/Feature/NursingSubStoreGovernanceTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NursingSubStoreGovernanceTest.php))**:
    - اجتياز كامل حزمة الاختبارات الآلية بنسبة 100% (`4 passed, 23 assertions`) واجتياز اختبارات استلام الشحنات `FloorCustodianStockTransferTest` (`13 passed, 95 assertions`)، وتوافق تام مع `Laravel Pint`.

- **المرحلة 281:** استكمال وإبراز كافة روابط القائمة الجانبية للصيدلية ومزامنة الصلاحيات لكادر الصيدلية (Pharmacy Staff Full Sidebar Links & Permissions Synchronization):
  * **قاعدة الصلاحيات والمزامنة ([database/migrations/2026_11_24_200000_sync_pharmacy_staff_permissions.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_11_24_200000_sync_pharmacy_staff_permissions.php))**:
    - منح ومزامنة صلاحيات الصيدلية الكاملة لكادر الصيدلية (`pharmacy_staff`, `pharmacist`): `create medicines`, `edit medicines`, `delete medicines`, `manage medicines`, `manage pharmacy services`, `import medicines`, `dispense medications`.
    - مسح وتحديث كاش الصلاحيات تلقائياً.
  * **تحديث شروط القائمة الجانبية ([resources/views/layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php))**:
    - إتاحة كافة روابط قسم الصيدلية الـ 7 كاملة وبارزة لموظف الصيدلية دون حجب، بما فيها رابط إضافة دواء جديد ورابط استيراد وتحديث الأدوية.
    - تأكيد استقلالية الصيدلية التامة عن مخزن المستشفى العام.
  * **الاختبارات الآلية الحصينة ([tests/Feature/PharmacySidebarAndPermissionsTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacySidebarAndPermissionsTest.php))**:
    - بناء واجتياز اختبارات الميزة وتأكيد ظهور كافة الروابط الـ 7 لكادر الصيدلية والوصول لشاشات الإضافة والاستيراد بنجاح 100% (`2 passed, 10 assertions`).

- **المرحلة 282:** فصل تبويب الصيدلية ومخزن الأدوية عن المستودع والمخزن الرئيسي في شاشة إدارة الأدوار والصلاحيات (Role Management Pharmacy & Main Warehouse Modules Separation):
  * **فصل التبويبات والمجموعات البرمجية ([app/Http/Controllers/RoleManagementController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RoleManagementController.php))**:
    - فصل بطاقة ومجموعة الصيدلية لتصبح قسماً مستقلاً: **«الصيدلية ومخزن الأدوية»** تحوي كافة الصلاحيات الصيدلانية حصراً (`view pharmacy`, `dispense medications`, `process pharmacy requests`, `manage medicines`, `create medicines`, `edit medicines`, `delete medicines`, `import medicines`, `manage pharmacy services`).
    - إنشاء بطاقة ومجموعة مستقلة للمستودع والمخزن الرئيسي: **«المستودع والمخزن الرئيسي للمستشفى»** تحوي صلاحيات المخزن العام حصراً (`manage inventory`, `view inventory`, `products`, `stock_batches`, `stock_movements`, `stock transfers`, `stock transfer requests`, `manage nursing sub stores`, `manage surgery store`, `purchases`, `suppliers`, `manage location thresholds`).
    - فصل تصنيف الأدوار في `getRoleCategory()` ليكون دور `pharmacy_staff` تابعاً للصيدلية، ودور `inventory_manager` تابعاً للمستودع العام.
  * **شاشة تعديل الأدوار ([resources/views/roles/edit.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/roles/edit.blade.php), `/roles/7/edit`)**:
    - ظهور تبويبين منفصلين تماماً في الأكورديون مع أيقونات ووصف وشارات تفصيلية لكل قسم بشكل معزول ومستقل.
  * **الاختبارات الآلية الحصينة ([tests/Feature/RolePharmacyInventorySeparationTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/RolePharmacyInventorySeparationTest.php))**:
    - اجتياز اختبارات الفصل وتأكيد استقلال بطاقة الصيدلية عن بطاقة المستودع وظهور كافة عناصر الصلاحيات لكل قسم بنجاح 100% (`1 passed, 10 assertions`)، وتوافق تام مع `Laravel Pint`.

- **المرحلة 283:** تحصين مسارات وصلاحيات المستودع والمخزن الرئيسي للمستشفى وعزل موقع الصيدلية وحماية المواد وتصفير الوجبات الوهمية (Main Warehouse RBAC Hardening, Dummy Batches Zeroing & Complete Pharmacy Decoupling):
  * **تصفير الوجبات الافتراضية الوهمية**:
    - حذف كافة الوجبات الوهمية المسبقة (3,972 دفعة برقم `INIT-*`) من جدول `medicine_batches` نهائياً وتصفير كمياتها، مع الحفاظ الكامل على كافة الأدوية (3,974 دواء).
  * **تحصين مسارات المستودع وصلاحيات Spatie ([routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
    - حماية مسارات الموردين والمواد والمشتريات والمستودعات والتحويلات بميدلوير `role_or_permission` الصارم لمنع كادر الصيدلية أو الكاشير من دخولها برمز `403 Forbidden`.
    - عزل واستبعاد موقع الصيدلية الوهمي القديم (`Location #16`) من كافة استعلامات ومنسدلات المخزن العام.
  * **حماية حذف المواد والعمليات المخزنية ([app/Http/Controllers/ProductController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/ProductController.php))**:
    - منع حذف أي مادة في المستودع إذا كانت مرتبطة بوجبات مخزنية (`stockBatches`) أو بنود شراء مسجلة (`purchaseItems`).
    - إسناد موقع الشراء ديناميكياً إلى المخزن الرئيسي وتحديث واجهات الموردين والتحويلات.
  * **الاختبارات الآلية الحصينة ([tests/Feature/RolePermissionsMatrixTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/RolePermissionsMatrixTest.php))**:
    - تحديث مصفوفة اختبارات الصلاحيات لتعكس فصل بطاقة «الصيدلية ومخزن الأدوية» عن بطاقة «المستودع والمخزن الرئيسي للمستشفى»، واجتياز كافة الاختبارات بنسبة 100% (15 اختبار، 243 توكيد) وتوافق تام مع `Laravel Pint`.

- **المرحلة 284:** تحويل شاشة توريد الأدوية للصيدلية إلى قائمة توريد متعددة البنود ديناميكية وتحديث المعالجة التلقائية (Pharmacy Multi-Item Batch Supply & Inward Invoice Processing):
  * **تطوير متحكم توريد الوجبات ([app/Http/Controllers/Pharmacy/MedicineBatchController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/MedicineBatchController.php))**:
    - دعم استقبال وحفظ قوائم التوريد المتعددة (`items` array) دفعة واحدة داخل معاملة قاعدة بيانات آمنة (`DB::transaction`) مع توثيق اسم المورد وتاريخ الاستلام ورقم إذن التوريد.
    - الحفاظ التام على التوافق الرجعي (Backward Compatibility) للإدخال الفردي.
  * **إعادة تصميم واجهة التوريد كقائمة وفاتورة ديناميكية ([resources/views/pharmacy/batches/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/batches/create.blade.php))**:
    - رأس فاتورة موحد (المورد، رقم الفاتورة الورقية، تاريخ الاستلام، وحالة الشحنة).
    - جدول ديناميكي تفاعلي لإضافة وحذف أسطر الأدوية (`itemsTable`) مع جلب تلقائي لسعر التكلفة لكل دواء عند الاختيار.
    - شريط ملخص إحصائي لحظي (إجمالي الأصناف، إجمالي العلب، وإجمالي قيمة الفاتورة بالدينار العراقي).
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/PharmacyMultiItemBatchSupplyTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacyMultiItemBatchSupplyTest.php))**:
    - بناء واجتياز اختبارات عرض الواجهة وحفظ قوائم التوريد المتعددة والتأكد من إيداع الوجبات بنجاح 100% (`4 passed, 20 assertions`)، وتوافق تام مع `Laravel Pint`.

- **المرحلة 285:** اعتماد المعيار الصيدلاني العالمي لنظام التوريد المزدوج واشتقاق تكلفة التجزئة واحتساب البونص وتحديث أسعار البيع (Global Dual-Unit Pharmacy Inward Supply, Automatic Sub-Unit Cost Derivation & Bonus Packs Handling):
  * **تطوير متحكم توريد الوجبات ([app/Http/Controllers/Pharmacy/MedicineBatchController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/MedicineBatchController.php))**:
    - تضمين بيانات معامل التعبئة والتجزئة (`sub_units_count`, `sub_unit`, `main_unit`, `sale_price`, `sub_unit_sale_price`) ضمن دالة `create()`.
    - دعم إدخال كميات البونص المجاني (`bonus_quantity`) وحساب التكلفة الفعلية للعلبة (`effectiveBoxCost`) تلقائياً.
    - دعم خيار تحديث أسعار البيع المعتمدة (`sale_price`, `sub_unit_sale_price`) وتحديث سعر آخر تكلفة شراء في دليل الأدوية المركزي تلقائياً.
  * **تطوير واجهة التوريد بنظام الوحدات المزدوج ([resources/views/pharmacy/batches/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/batches/create.blade.php))**:
    - إبراز شارة التعبئة المزدوجة لكل دواء بمجرد اختياره: `[1 علبة = N شريط]`.
    - الاشتقاق الآلي اللحظي لتكلفة الشريط الواحد: `[تكلفة الشريط = سعر شراء العلبة ÷ عدد الأشرطة]`.
    - دعم حقل البونص المجاني مع احتساب أثر البونص على خفض تكلفة العلبة والشريط الحقيقية.
    - إتاحة حقول أسعار البيع للجمهور (بيع العلبة وبيع الشريط) مع مفتاح لتحديثها بدليل الأدوية بنقرة واحدة.
    - شريط إحصائي فوري (عدد الأصناف، إجمالي العلب المشتراة، إجمالي البونص المجاني، إجمالي الأشرطة المعادل، وإجمالي قيمة الفاتورة).
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/PharmacyMultiItemBatchSupplyTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacyMultiItemBatchSupplyTest.php))**:
    - بناء واجتياز اختبارات التوريد بالبونص وحساب التكلفة الفعلية وتحديث أسعار البيع بنجاح 100% (`3 passed, 16 assertions`)، مع اجتياز كامل حزمة اختبارات الصيدلية (`6 passed, 37 assertions`)، وتوافق تام مع `Laravel Pint`.

- **المرحلة 286:** إضافة حقل معامل التعبئة القابل للتعديل لكل دواء وحساب إجمالي الأشرطة اللحظي (Editable Pack Size & Real-Time Strips Calculation per Batch Row):
  * **تطوير واجهة التوريد بجدول البنود ([resources/views/pharmacy/batches/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/batches/create.blade.php))**:
    - إضافة عمود مخصص وقابل للتعديل: **«أشرطة/العلبة»** (`sub_units_count`) يُملأ تلقائياً من بطاقة الدواء (2، 3، 10... إلخ) وقابل للتعديل لكل صنف وشحنة فوراً.
    - إضافة شارة تفاعلية فورية تحت حقل الكميات بالعلب: `[ = N شريط/قطعة ]` تتحدث لحظياً عند تغيير العلب أو البونص أو معامل التعبئة.
    - ربط التحديث اللحظي باشتقاق تكلفة الشريط الواحد وإجمالي أشرطة الشحنة في خلاصة الفاتورة.
  * **تحديث المتحكم ([app/Http/Controllers/Pharmacy/MedicineBatchController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/MedicineBatchController.php))**:
    - استقبال `items.*.sub_units_count` وتحديث بطاقة الدواء تلقائياً لضبط سعة التعبئة الجديدة الموردة.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/PharmacyMultiItemBatchSupplyTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacyMultiItemBatchSupplyTest.php))**:
    - تحديث واجتياز كافة الاختبارات بنجاح 100% (`3 passed, 17 assertions`)، وتوافق تام مع `Laravel Pint`.

- **المرحلة 287:** محرك بحث ذكي للأدوية بشاشة التوريد على نسق ICD-10 مع بيان أسعار الضمان والكاش (ICD-10-Styled Smart Medicine Autocomplete Supply Engine):
  * **تطوير شاشة التوريد بمحرك ICD-10 الذكي ([resources/views/pharmacy/batches/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/batches/create.blade.php))**:
    - استبدال القائمة المنسدلة التقليدية بمحرك بحث فوري لحظي يبحث بالاسم التجاري، العلمي، الرمز الوطني، والباركود.
    - قائمة منسدلة منبثقة غنية تبرز: الرمز الوطني `#code`، العيار، الشكل الصيدلاني، معادلة التعبئة `[1 علبة = N شريط]`، وسعر الضمان المعتمد `[🛡️ ضمان: X د.ع]` مقارنة بسعر الكاش.
    - دعم التنقل الكامل بلوحة المفاتيح عبر الأسهم (Arrow Up/Down) واختيار الدواء وتثبيته فوراً بـ (Enter) أو النقر بالماوس.
    - إبراز شارة سعر الضمان للعلبة في السطر بجانب أسعار البيع لتفادي بيع الدواء للمضمونين بخسارة مقارنة بسعر الشراء.
  * **تحديث المتحكم ([app/Http/Controllers/Pharmacy/MedicineBatchController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/MedicineBatchController.php))**:
    - تضمين `hi_price` و `barcode` ضمن استعلام الأدوية في `create()`.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/PharmacyMultiItemBatchSupplyTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacyMultiItemBatchSupplyTest.php))**:
    - اجتياز كامل حزمة الاختبارات الآلية (`5 passed, 28 assertions`)، وتوافق تام مع `Laravel Pint`.

- **المرحلة 288:** الربط الآلي المقفل لتصنيف المريض التأميني من الاستعلامات بنقطة البيع والصرف والتسعير المزدوج للصيدلية المتعاقدة (Contracted Pharmacy Dual Cash/Insurance POS Engine & Automated Reception Patient Profile Lock):
  * قفل الوضع المالي للمريض من شاشة الاستعلامات ومنع التغيير اليدوي داخل الصيدلية.
  * تسعير مزدوج (كاش وضمان صحي) واشتقاق أسعار أشرطة الضمان بدقة.

- **المرحلة 289 / 290:** التعديل اللحظي المباشر لأسعار الكاش داخل جدول دليل الأدوية مع الحفظ التلقائي والاشتقاق الذكي (Medicines Directory Inline Quick-Pricing & Auto-Save Derivation Engine):
  * إضافة مسار `PATCH /pharmacy/medicines/{medicine}/quick-price` ودالة `updateQuickPrice` في `MedicineController`.
  * حقول إدخال أنيقة لأسعار بيع العلبة والشريط كاش مع حفظ لحظي عبر AJAX دون إعادة تحميل الصفحة.

- **المرحلة 291:** معالجة تضخم حمولة الجلسة لفواتير الشراء الضخمة والسماح بوجبات شراء متعددة لنفس الصنف (Large Purchase Session Payload Fix & Multi-Batch Purchase Lines):
  * ترقية حقل `sessions.payload` إلى `LONGTEXT` محلياً وسحابياً عبر المايجريشن لمنع خطأ `1406 Data too long for column 'payload'`.
  * إزالة قيد منع تكرار نفس المادة في الفاتورة للسماح بشراء عدة وجبات بأسعار صلاحية أو تكلفة مختلفة.
  * تصحيح خطوة مدة العملية `min="1" step="1"` في `surgeries/show.blade.php`.

- **المرحلة 292:** استكمال إبراز أسماء كافة أطباء ومساعدي التخدير والجراحة وتوحيد طباعة التقرير الجراحي في صفحة واحدة A4 (Surgery Official Print Report Complete Medical Team & Strict Single-Page A4 Layout):
  * **معالجة حجب أسماء الكادر الطبي ([resources/views/surgeries/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/print.blade.php))**:
    - إلغاء استبدال طبيب التخدير الثاني بمساعد التخدير، وإبراز كامل الفريق الطبي المشرف (5 أعضاء متزامنين):
      1. الطبيب الجراح الرئيسي: `د. {{ $surgeonName }}`
      2. مساعد الجراح: `{{ $surgicalAssistantName }}`
      3. طبيب التخدير الأول: `د. {{ $primaryAnesthName }}`
      4. طبيب التخدير الثاني (إذا وجد): `د. {{ $secondaryAnesthName }}`
      5. مساعد / تقني التخدير: `{{ $anesthesiaAssistantName }}`
    - توثيق كلا طبيبي التخدير في خانة التوقيعات والاعتماد الرسمي للتقرير.
  * **هندسة الطباعة في صفحة A4 واحدة مقفلة ومحكمة**:
    - إضافة توجيه الطباعة المعياري العالمي `@page { size: A4 portrait; margin: 4mm 6mm !important; }`.
    - إزالة التمدد الطولي الافتراضي (`min-height: 296mm`) وحصر ارتفاع الصفحة في `max-height: 288mm` مع `page-break-after: avoid` و `break-inside: avoid`.
    - ضغط هوامش وبادينغ الأقسام وشعار المستشفى وختم الصالة والباركود لضمان نزول كامل التقرير من الهيدر حتى الفوتر في صفحة ورقية واحدة متناسقة 100%.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/SurgeryStatusPostOpAndPrintReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryStatusPostOpAndPrintReportTest.php))**:
    - بناء واجتياز اختبار التحقق من ظهور كافة أطباء ومساعدي التخدير والجراحة مع قواعد الطباعة A4 بنجاح 100% (`4 passed, 46 assertions`)، واجتياز اختبار الترجمة للغتين (`7 passed, 76 assertions`)، وتوافق تام مع `Laravel Pint`.
