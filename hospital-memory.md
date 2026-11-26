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

- **المرحلة 301 (2026-11-26 — `main`) — إعدادات المنظومة المالية (`MedFinance IFRS`): إدارة العملة الأساسية وأسعار الصرف، تسوية فروق الصرف، وأرشفة التغييرات، مع تطوير المخطط الشجري للعرض العمودي (من الأعلى للأسفل) ودعم الآيباد والتاتش باد (Accounting Settings, Dynamic Currency & FX Revaluation IAS 21, Audit Archive & Top-to-Bottom iPad/Trackpad Flowchart)**:
  * **قاعدة البيانات والحسابات ([2026_11_26_100000_add_fx_difference_leaf_accounts.php](file:///C:/get/hospital-system/database/migrations/2026_11_26_100000_add_fx_difference_leaf_accounts.php), [AccChartOfAccountsSeeder.php](file:///C:/get/hospital-system/database/seeders/AccChartOfAccountsSeeder.php))**:
    - إضافة الحسابين التحليليين بمستوى 5: `33551` (**خسائر فروقات أسعار الصرف الأجنبي**) و`43551` (**أرباح فروقات أسعار الصرف الأجنبي**).
  * **المتحكم والمسارات ([AccountingSettingsController.php](file:///C:/get/hospital-system/app/Http/Controllers/Accounting/AccountingSettingsController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php), [ChartOfAccountsController.php](file:///C:/get/hospital-system/app/Http/Controllers/Accounting/ChartOfAccountsController.php))**:
    - إضافة مسارات `accounting.settings.index`, `accounting.settings.currencies.update`, `accounting.settings.currencies.store`, و`accounting.settings.currencies.revalue`.
    - تعديل أسعار الصرف والعملة الأساسية مع إلزامية كتابة سبب التغيير وأرشفته آلياً في `acc_audit_logs` باسم الموظف والتاريخ والسعر القديم والجديد والفارق وIP.
    - احتساب فروق أسعار الصرف غير المحققة للأرصدة النقدية والذمم بالعملات الأجنبية وترحيل قيد تسوية يومية متوازن (`33551` / `43551`).
  * **الواجهات ([layout.blade.php](file:///C:/get/hospital-system/resources/views/accounting/layout.blade.php), [settings/index.blade.php](file:///C:/get/hospital-system/resources/views/accounting/settings/index.blade.php), [chart/index.blade.php](file:///C:/get/hospital-system/resources/views/accounting/chart/index.blade.php), [vouchers/create.blade.php](file:///C:/get/hospital-system/resources/views/accounting/vouchers/create.blade.php))**:
    - إضافة تبويب **`الإعدادات`** داخل شريط `MedFinance IFRS`، وربط بطاقة **`العملة الأساسية والسعر`** ديناميكياً من `acc_currencies`، وإظهار مؤشر فارق الصرف بالسندات.
    - ترقية المخطط الشجري بالنافذة الكاملة إلى اتجاه **من الأعلى إلى الأسفل** ونقله إلى `@push('modals')` (`100dvh` + `safe-area-inset-bottom`) مع شريط تحكم عائم ثابت (أسهم تحريك يمين/يسار/أعلى/أسفل، تكبير، تصغير، توسيط، إغلاق)، ودعم **التمرير بإصبعين على التاتش باد** و**القرص والتحريك باللمس على الآيباد**.
  * **الاختبارات وضمان الجودة ([AccountingVoucherAndChartTest.php](file:///C:/get/hospital-system/tests/Feature/AccountingVoucherAndChartTest.php))**:
    - اجتياز `23 passed (136 assertions)`، واجتياز `Laravel Pint` (`15 files`) و`view:cache`.

- **المرحلة 300 (2026-11-26 — `hr`) — تطوير شجرة الحسابات التفاعلية بالتركيز التدريجي ووضع النافذة الكاملة التفاعلي بالأسهم وتوحيد مصطلحات الأصل والفرع (Drill-Down Chart of Accounts, Fullscreen Interactive Flowchart, Origin/Branch Terminology & Strict Leaf Posting Governance)**:
  * **النماذج والمتحكمات ([Account.php](file:///C:/get/hospital-system/app/Models/Accounting/Account.php), [ChartOfAccountsController.php](file:///C:/get/hospital-system/app/Http/Controllers/Accounting/ChartOfAccountsController.php), [VoucherController.php](file:///C:/get/hospital-system/app/Http/Controllers/Accounting/VoucherController.php))**:
    - تحديث `Account::scopePostable()` وأضاف `hasJournalLines()` و`canReceiveEntries()` لضمان عدم قبول القيود لأي حساب له فروع.
    - جلب كامل الدليل باستعلام واحد سريع في `ChartOfAccountsController::index()` بدون `N+1` وبناء JSON مباشر للواجهة.
    - منع إضافة فرع تحت أي حساب مسجلة عليه قيود محاسبية مسبقاً (`lines()->exists()`) في `store()` و`suggestCode()` و`getDetails()`.
    - إبقاء المستويات 1–4 تجميعية، وتحويل أي حساب قابل للقيد (مستوى 5+) إلى `is_postable = false` تلقائياً عند إضافة أول فرع تحته.
  * **الواجهة التفاعلية ([resources/views/accounting/chart/index.blade.php](file:///C:/get/hospital-system/resources/views/accounting/chart/index.blade.php))**:
    - إعادة بناء الشاشة بعرض كامل مع تنقل تدريجي (Drill-Down): يختفي المستوى الأعلى ويتحول لمسار Breadcrumb وتظهر الفروع المباشرة فقط، مع درج جانبي منزلق للتفاصيل والرصيد والقيود.
    - توحيد المسميات بالكامل إلى **«الأصل»** و**«فرع»** (`+ إضافة فرع`, `الحساب الأصل`) بدلاً من «الأب» و«ابن».
    - إضافة **وضع النافذة الكاملة (المخطط الشجري التفاعلي)** بمربعات مترابطة بأسهم SVG ومنطق RTL: يبدأ بأصل واحد فقط ولا يفتح الفروع إلا عند الضغط، مع نمط **«التركيز الأحادي»** افتراضياً وزر تبديل فوري لـ **«التوسيع الحر»**، وبحث للقفز السريع مع التكبير والتصغير والسحب.
  * **الاختبارات وضمان الجودة ([AccountingVoucherAndChartTest.php](file:///C:/get/hospital-system/tests/Feature/AccountingVoucherAndChartTest.php))**:
    - اجتياز `21 passed (106 assertions)` عبر `AccountingCoreTest`, `AccountingVoucherAndChartTest`, و`NicuAccountingTest`، مع اجتياز `Laravel Pint` و`view:cache`.

- **المرحلة 299 (2026-11-25 — `main`) — منظومة تعهد دخول الخدج الخطي لولي الأمر (A4) والطباعة المباشرة والأرشفة الاختيارية بالسكانر أو رفع الملفات (NICU Parental Admission Consent A4 Print, Post-Booking Prompt & Optional Scanner/File Archiving)**:
  * **قاعدة البيانات والنماذج ([2026_11_25_170000_add_guardian_name_and_document_type_for_nicu_consent.php](file:///C:/get/hospital-system/database/migrations/2026_11_25_170000_add_guardian_name_and_document_type_for_nicu_consent.php), [IncubatorReservation.php](file:///C:/get/hospital-system/app/Models/IncubatorReservation.php), [NicuAttachment.php](file:///C:/get/hospital-system/app/Models/NicuAttachment.php), [NicuSetting.php](file:///C:/get/hospital-system/app/Models/NicuSetting.php))**:
    - إضافة عمود `guardian_name` إلى `incubator_reservations` لتوثيق اسم ولي أمر المريض الموقّع، وعمود `document_type` إلى `nicu_attachments` (`admission_consent`, `birth_certificate`, `parent_id`, `referral_doc`, `general`).
    - توحيد آلية حفظ المرفقات والصور الممسوحة بالسكانر في دالة مشتركة `NicuAttachment::storeForReservation()`.
  * **المسارات والمتحكمات ([routes/web.php](file:///C:/get/hospital-system/routes/web.php), [IncubatorReservationController.php](file:///C:/get/hospital-system/app/Http/Controllers/IncubatorReservationController.php), [NicuController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuController.php))**:
    - إضافة مساري `nicu.print.admission-consent` و`nicu.admission-consent.upload`.
    - دعم خيار «حفظ وطباعة التعهد» أو «حفظ الحجز فقط» وإظهار نافذة ما بعد الحجز الفورية (`show_consent_prompt`)، مع بقاء رفع السكانر/الملف اختيارياً 100% دون أي تقييد لسير العمل.
    - تفعيل المسح بالسكانر أيضاً داخل نموذج الأرشيف العام (`storeAttachment`) بملف الطفل.
  * **الواجهات وقالب الطباعة A4 ([admission_consent.blade.php](file:///C:/get/hospital-system/resources/views/nicu/print/admission_consent.blade.php), [create.blade.php](file:///C:/get/hospital-system/resources/views/incubator_reservations/create.blade.php), [show.blade.php](file:///C:/get/hospital-system/resources/views/nicu/show.blade.php))**:
    - بناء استمارة التعهد الخطي A4 بشعار مستشفى الكفاءات الأهلي وتضمين اسم ولي الأمر، الطبيب المعالج، بيانات الطفل والحاضنة، النص القانوني الكامل، التواقيع والبصمة، وتذييل المستشفى.
    - إضافة حقل اسم ولي الأمر وبطاقة رابعة اختيارية في شاشة الحجز، وزر الطباعة وشارة الحالة ونافذة السكانر/الرفع وبطاقة الأرشيف في ملف الطفل.
  * **الاختبارات وضمان الجودة ([NicuAdmissionConsentTest.php](file:///C:/get/hospital-system/tests/Feature/NicuAdmissionConsentTest.php))**:
    - اجتياز `18 passed (162 assertions)` عبر `NicuAdmissionConsentTest` و`NicuEnhancementsTest` و`NicuTabAndStatePersistenceTest`، مع اجتياز `Pint` و`view:cache`.

- **المرحلة 298 (2026-11-25 — `main`) — الدخول المباشر الديناميكي لمخزن وعُهدة الخدج المرتبط بصلاحية وموقع المستخدم بدون ID ثابت، وبحث فوري لمدير القسم وتحويل ساعات العمل والحد الأقصى لخيارات اختيارية (Dynamic User-Linked NICU Store Direct Access, Live Department Manager Autocomplete & Optional Department Hours)**:
  * **مخزن وعُهدة الخدج الديناميكي بدون ID ثابت ([Location.php](file:///C:/get/hospital-system/app/Models/Location.php), [NicuStoreController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuStoreController.php), [NicuController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuController.php), [StockTransferController.php](file:///C:/get/hospital-system/app/Http/Controllers/StockTransferController.php), [resources/views/nicu-store/show.blade.php](file:///C:/get/hospital-system/resources/views/nicu-store/show.blade.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
    - إضافة عمود `code` الفريد لجدول `locations` بالميجريشن [2026_11_25_150000_add_code_to_locations_and_configure_nicu_store.php](file:///C:/get/hospital-system/database/migrations/2026_11_25_150000_add_code_to_locations_and_configure_nicu_store.php) وتفعيل دالتي `Location::resolveNicuStoreForUser()` و `Location::resolveNicuStore()` (أولوية لمخزن المستخدم `user->location_id` ثم إعداد `nicu_settings.nicu_store_location_id` ثم كود `nicu` ثم الاكتشاف أو الإنشاء التلقائي).
    - إلغاء شاشة القائمة الوسيطة والدخول مباشرةً على شاشة المخزن (`/nicu-store`) مع بحث وفلترة الأرصدة، صرف مستهلك عام للقسم، فلتر تاريخ، وزر تغيير الموقع المعتمد للإدارة.
    - حصر صلاحية الوصول والرابط الجانبي وطلبيات المخزن الرئيسي بصلاحية `manage nicu store` (والإدارة/`manage inventory`) فقط.
  * **بحث فوري لمدير القسم وتحويل ساعات العمل والحد الأقصى لاختيارية ([DepartmentController.php](file:///C:/get/hospital-system/app/Http/Controllers/DepartmentController.php), [manager-picker.blade.php](file:///C:/get/hospital-system/resources/views/departments/partials/manager-picker.blade.php), [edit.blade.php](file:///C:/get/hospital-system/resources/views/departments/edit.blade.php), [create.blade.php](file:///C:/get/hospital-system/resources/views/departments/create.blade.php))**:
    - إضافة نقطة البحث `GET /departments/managers/search` ومكوّن بحث حي بالاسم والإيميل والهاتف مع استبعاد المرضى بالكامل.
    - تشغيل الميجريشن [2026_11_25_160000_make_department_working_hours_and_max_patients_nullable.php](file:///C:/get/hospital-system/database/migrations/2026_11_25_160000_make_department_working_hours_and_max_patients_nullable.php) وتحويل `working_hours_start`, `working_hours_end`, `max_patients_per_day` إلى اختيارية (`nullable`) مع عرض آمن في [show.blade.php](file:///C:/get/hospital-system/resources/views/departments/show.blade.php) و [public_index.blade.php](file:///C:/get/hospital-system/resources/views/departments/public_index.blade.php).
  * **الاختبارات وضمان الجودة ([NicuEnhancementsTest.php](file:///C:/get/hospital-system/tests/Feature/NicuEnhancementsTest.php), [DepartmentManagerSearchTest.php](file:///C:/get/hospital-system/tests/Feature/DepartmentManagerSearchTest.php))**:
    - اجتياز `23 passed (211 assertions)` في اختبارات مخزن الخدج والتحويلات، و`4 passed (33 assertions)` في اختبارات الأقسام، مع اجتياز `Pint` و`view:cache`.

- **المرحلة 312 (2026-11-27 — `main`) — ربط أدوية الرقود والجراحة بنقطة بيع الصيدلية POS والكاشير المركزي والتسليم الآلي للردهة (Inpatient Medications to Pharmacy POS & Central Cashier End-to-End Cycle)**:
  * **قاعدة البيانات والنماذج ([2026_11_27_020000_add_pharmacy_status_to_surgery_treatments_table.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_11_27_020000_add_pharmacy_status_to_surgery_treatments_table.php), [SurgeryTreatment.php](file:///c:/wamp64/www/hospital-system/app/Models/SurgeryTreatment.php), [Surgery.php](file:///c:/wamp64/www/hospital-system/app/Models/Surgery.php), [Prescription.php](file:///c:/wamp64/www/hospital-system/app/Models/Prescription.php), [PharmacySale.php](file:///c:/wamp64/www/hospital-system/app/Models/PharmacySale.php))**:
    - إضافة عمودي `prescription_item_id` و `pharmacy_status` لجدول `surgery_treatments` وربطهما بالعلاقات في الموديلات.
    - دعم ربط `surgery_id` في جدول الوصفات `prescriptions` وجدول مبيعات الصيدلية `pharmacy_sales`.
  * **المتحكمات والمسارات ([ClinicalStationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/ClinicalStationController.php), [PharmacyPosController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/PharmacyPosController.php), [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
    - عند قيام الطبيب أو المقيم بوصف دواء أو تطبيق باقة أدوية جاهزة في شاشة الرقود (`storeTreatment` / `applyMedicinePackage`)، يتم توليد وصفة إلكترونية `Prescription` مرتبطة بالعملية وإرسالها آلياً لصيدلية المستشفى (`sent_to_pharmacy`).
    - في شاشة الصيدلية `pharmacy/pos`: تظهر طلبات الرقود في طابور الوصفات مع شارة مميزة `[🏨 رقود: غرفة X]`.
    - عند تجهيز الصيدلي للوصفة، تُنشأ فاتورة بيع من نوع `inpatient` بمحور سداد `central_cashier` وحالة `pending_cashier`.
    - فور سداد المرافق في الكاشير المركزي (`cashier.index`)، تُحدث الفاتورة إلى مدفوعة، وتتيح للصيدلي تأكيد التسليم للردهة (`pharmacy.pos.sales.handoff`).
    - عند تأكيد التسليم، تتحدث حالة العلاج في جدول إعطاء الدواء للممرض تلقائياً إلى `تم الصرف والتسليم من الصيدلية ✅`.
  * **الواجهات ([show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/clinical/show.blade.php), [pos/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/pos/index.blade.php), [cashier/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/index.blade.php), [pharmacy-payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/pharmacy-payment-form.blade.php))**:
    - إضافة شارات تتبع حالة العلاج بالصيدلية والكاشير والتسليم في كارت علاجات الرقود.
    - إضافة زر `[ 💊 إرسال للصيدلية (POS) ]` للإرسال اليدوي أو إعادة المزامنة في أي وقت.
    - تمييز فواتير الرقود بشارات ورقم الغرفة في الصيدلية والكاشير المركزي.
  * **الاختبارات وضمان الجودة ([InpatientPharmacyPosWorkflowTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/InpatientPharmacyPosWorkflowTest.php))**:
    - اجتياز اختبار تدفق الدورة بالكامل بنجاح تام (1 passed, 26 assertions).
    - اجتياز اختبارات التمريض والعمليات الجراحية (14 passed, 200 assertions)، وتنسيق `Pint` ونجاح كاش القوالب `view:cache`.

- **المرحلة 311 (2026-11-27 — `main`) — إلغاء زر تخريج الغرفة من الشاشة السريرية وحصره في محطة التمريض (Clinical Station: Room Discharge Button Removal & Strict Nursing Station Containment)**:
  * **إلغاء زر «تخريج من الغرفة» من الشاشة السريرية ([resources/views/surgery-stations/clinical/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - حذف زر التخريج الفندقي وإخلاء السرير نهائياً من رأس الملف السريري للمريض (`clinical/show`) لمنع أي تشويش أو ضغط غير مقصود من قبل الأطباء والمقيمين.
    - حصر عملية التخريج الفندقي وتحرير السرير بمكانها الطبيعي واللوجستي في **محطة التمريض** (`/surgery-stations/nursing`) فقط، حيث يتابع ممرض الطابق خروج المريض الفعلي وتسوية الحسابات بالكاشير.
  * **الاختبارات والتأكيد**:
    - اجتياز اختبارات تدفق التمريض [NursingRoomCheckinWorkflowTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NursingRoomCheckinWorkflowTest.php) بالكامل بنجاح (5 passed, 26 assertions).
    - نجاح كاش القوالب `view:cache`.

- **المرحلة 310 (2026-11-27 — `main`) — إلغاء زر تسجيل دخول الغرفة اليدوي في محطة التمريض واعتماد الدخول التلقائي بمجرد تخصيص الغرفة من الاستعلامات (Automatic Room Check-in on Assignment, Seamless Inpatient Admission & Nursing Station Simplification)**:
  * **تجريد وتبسيط شاشة التمريض (`resources/views/surgery-stations/nursing/index.blade.php`)**:
    - إلغاء زر «تسجيل دخول الغرفة» نهائياً من جدول محطة التمريض لرفع العبء التوثيقي عن الكادر التمريضي.
    - إتاحة زر «عرض وتعديل» وزر «تخريج» مباشرة للمرضى المخصص لهم غرف، والإبقاء على زر «تخصيص غرفة» فقط للمرضى في وضع الانتظار.
  * **التسجيل التلقائي للدخول الفندقي (`SurgeryController.php`, `ClinicalStationController.php`, `ResidentStationController.php`)**:
    - عند اختيار أو تعديل الغرفة من الاستعلامات أو حجز العملية يتم تسجيل `room_checkin_at = now()` و `room_checkin_by = Auth::id()` تلقائياً وقفل الغرفة.
    - إلغاء قفل فتح الملف السريري للمقيم (`resident-station.show`) والتعبئة التلقائية لتوقيت الدخول دون أي تحذيرات أو عوائق.
    - معالجة الحالات السابقة في قاعدة البيانات وتثبيت تواريخ الدخول.
  * **الاختبارات وضمان الجودة**:
    - اجتياز اختبارات `NursingRoomCheckinWorkflowTest` و `ResidentStationRoomCheckinGateTest` (7 passed, 37 assertions)، واختبارات `SurgeryTest` (9 passed, 174 assertions)، واجتياز `Pint` و`view:cache`.

- **المرحلة 309 (2026-11-26 — `main`) — حزمة تثبيت سكانر المستشفى التلقائي بنقرة واحدة وتوزيعها لجميع الأقسام وزر تحميل عام (Universal Hospital Scanner 1-Click Setup Package, Multi-Station Distribution, Windows Startup & Permanent Insecure Content Policy)**:
  * **تجهيز حزمة التثبيت بنقرة واحدة (`public/downloads/HospitalScanner_Setup.zip`)**:
    - حزمة مستقلة مجمعة تضم: `HospitalScanner.exe`، ملف التثبيت التلقائي `تثبيت_السكانر_InstallScanner.bat`، وملف التعليمات.
    - يقوم ملف الباتش بإنشاء مجلد محمي دائم `%LOCALAPPDATA%\HospitalScanner`، وربط البرنامج ببدء تشغيل الويندوز (`Startup`) ليعمل تلقائياً عند تشغيل الحاسبة يومياً.
    - ضبط صلاحيات وسياسات متصفحي كروم و Edge تلقائياً بالسجل (Registry Policy `InsecureContentAllowedForUrls`) لموقع هوستنجر لمنع حظر الاتصال بالمحتوى المحلي عبر HTTPS.
    - تشغيل البرنامج فوراً في الخلفية ليكون جاهزاً للمسح دون الحاجة لإعادة تشغيل الحاسبة.
  * **تبسيط وتجريد واجهات النظام وإلغاء روابط وأزرار التنزيل (Download Links Stripped)**:
    - بناءً على توجيه المستخدم، تم إلغاء كافة روابط وأزرار تنزيل البرنامج من القائمة الجانبية (Sidebar) ومن شاشتي حجز وتعديل العمليات الجراحية نهائياً.
    - الإبقاء حصراً على: زر كشف السكانر `[ 🔍 كشف السكانر ]`، زر المسح المباشر `[ 🖨️ مسح من السكانر ]`، زر رفع الملف، وشارة الحالة، مما يجعل الواجهة نظيفة ومباشرة.

- **المرحلة 308 (2026-11-26 — `main`) — زر الكشف التلقائي عن أجهزة السكانر المرتبطة بالحاسبة وتفعيل المسح الضوئي المباشر وتجريد الواجهة (Surgery Booking: USB Scanner Auto-Detection, Hardware Name Badge & Direct One-Click Scanning)**:
  * **زر الكشف التلقائي عن السكانر (`#detect_scanner_btn`) وتفعيل زر المسح الضوئي ([create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/create.blade.php), [edit.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/edit.blade.php))**:
    - إضافة زر `[ 🔍 كشف السكانر ]` في شاشتي حجز وتعديل العملية الجراحية.
    - استعلام لحظي لبرنامج وسيط السكانر المحلي على منافذ `127.0.0.1:5000` و `127.0.0.1:37426` لكشف اتصال الأجهزة.
    - عند كشف اتصال السكانر بنجاح، يتم قراءة اسم جهاز السكانر وعرضه في شارة الحالة الخضراء (`#scannerStatusIndicator`: "متصل: [اسم الجهاز]")، وتحويل زر «مسح من السكانر» إلى اللون الأخضر الزاهي وتفعيله فورياً.
  * **تجريد وتبسيط شريط المرفقات بناءً على رغبة المستخدم**:
    - إلغاء زر ومودال الكاميرا بالكامل لعدم الحاجة إليه.
    - إلغاء زر ومودال دليل السكانر وحصر التنبيهات في إشعارات Toast مباشرة وسريعة لتكون الواجهة نظيفة ومركزة على المسح بالسكانر ورفع الملفات.
  * **مودال تكبير ومعاينة الوثيقة الممسوحة (`#documentPreviewModal`)**:
    - إمكانية تكبير واستعراض الوثيقة الملتقطة أو الممسوحة ضوئياً بملء الشاشة للتأكد من وضوح كافة بيانات الإحالة والتوقيعات قبل حفظ العملية.
  * **إصلاح ملف الهجرة الناقص (`AddSurgeryIdToPrescriptionsAndPharmacySales`)**:
    - إكمال وتعريف كود الهجرة السليمة لإضافة `surgery_id` إلى جداول الوصفات والمبيعات الصيدلانية لضمان نجاح `migrate` و `composer autoload`.

- **المرحلة 307 (2026-11-26 — `main`) — حل بطء وتعليق الانتقال إلى شاشة حجز العمليات الجراحية (من 10 ثوانٍ إلى 0.1 ثانية) وإلغاء تحميل 10 آلاف مريض في الذاكرة مع تفعيل البحث اللحظي بالـ AJAX (Surgery Booking Transition Optimization: Eliminate 10K Patients In-Memory Saturation & Enable Instant AJAX Select2)**:
  * **تشخيص السبب الجذري للبطء الشديد (Root Cause Discovery)**:
    - كشفت القياسات الدقيقة أن دالة `SurgeryController::create()` كانت تنفذ استعلام `Patient::with('user')->get()->sortBy(...)` الذي يسحب **10,368 مريضاً** و 10,368 مستخدماً دفعة واحدة ويولد أكثر من 20,700 كائن Eloquent في الذاكرة.
    - قيام قالب Blade في `surgeries/create.blade.php` بالدوران 10,368 مرة وتوليد مسارات `route('inquiry.patients.history')` و `route('inquiry.patients.dossier.print')` وترميز JSON لكل مريض داخل السمة `data-patient` مما كان يُنتج صفحة HTML بحجم ضخم يتجاوز **4 ميغابايت** ويستغرق معالجتها في السيرفر وحده 5.8 ثوانٍ، تليها 3 إلى 5 ثوانٍ أخرى في المتصفح لبناء عناصر مكتبة Select2 على 10 آلاف خيار، مما سبب تعليق التحميل (اللودنك) لأكثر من 10 ثوانٍ.
  * **المعالجة الجذرية والتحسين الفوري ([SurgeryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgeryController.php), [BedReservationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/BedReservationController.php))**:
    - قصر قائمة المرضى في `SurgeryController::create()` على المريض المختار حصراً `$selectedPatient` عند التحويل من الاستعلامات أو تمرير `patient_id`، أو جلب أحدث 25 مريضاً فقط كعينة أولية سريعة.
    - تطبيق نفس التحسين على دالتي `SurgeryController::edit()` و `SurgeryController::show()` ودالة `BedReservationController::create()` لحجز الرقود والأسرة لمنع تكرار البطء في أي محطة.
  * **تفعيل البحث اللحظي بالـ AJAX في Select2 ([create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/create.blade.php), [edit.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/edit.blade.php))**:
    - ربط حقل اختيار المريض `#patient_id` بنقطة نهاية البحث اللحظي السريع `route('inquiry.search.patients')` مع تأخير ذكي (debounce 250ms) والبحث بالاسم أو الهاتف أو رقم الملف أو الهوية الوطنية.
    - ربط حدث `select2:select` لتحديث بيانات `data-patient` فورياً ومزامنة شريط هوية المريض العلوي (Executive Banner) واللوحة الجانبية الثابتة (Sticky Summary) ومسار التغطية فور اختيار أي مريض جديد دون أي بطء.
  * **النتيجة والقياسات والاختبارات ([SurgeryCreateModernWorkspaceTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryCreateModernWorkspaceTest.php), [SurgeryTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryTest.php))**:
    - انخفاض زمن فتح شاشة حجز العملية الجراحية من **10 ثوانٍ** إلى **0.2 ثانية فقط (206 ميلي ثانية)** أي تسريع بمقدار **50 ضعفاً**.
    - اجتياز اختبارات العمليات [SurgeryCreateModernWorkspaceTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryCreateModernWorkspaceTest.php) (1 passed, 20 assertions) واختبارات [SurgeryTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryTest.php) (9 passed, 174 assertions) واختبارات الاستعلامات (5 passed, 27 assertions) وكاش القوالب `view:cache` وتنسيق Pint.

- **المرحلة 306 (2026-11-26 — `main`) — اختصار دورة الاستعلامات بالكامل إلى شاشة واحدة مباشرة مع قفل الخدمات لحين تحديد المريض وبحث فوري في الأعلى وتوجيه موظف الاستعلامات إليها فور تسجيل الدخول (Instant Single-Page Inquiry Direct Booking, Strict Patient-First Services Lock, Delegated Selection & Direct Login Redirection)**:
  * **توجيه موظف الاستعلامات / الاستقبال مباشرة إلى شاشة الاستعلامات فور تسجيل الدخول ([LoginController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Auth/LoginController.php), [DashboardController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DashboardController.php), [User.php](file:///c:/wamp64/www/hospital-system/app/Models/User.php), [bootstrap/app.php](file:///c:/wamp64/www/hospital-system/bootstrap/app.php), [web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
    - إضافة دالة استعلام وتدقيق الدور `isInquiryStaff()` في موديل المستخدم `User` لتشمل: أدوار الاستعلامات والاستقبال (`inquiry_staff`, `receptionist`, `consultation_receptionist`, `استعلامات`, `استقبال`)، والأقسام المعنية، وصلاحيات إنشاء الاستعلامات لمن ليس لديه دور إداري أو طبي أعلى.
    - توجيه موظف الاستعلامات فور نجاح تسجيل الدخول في `LoginController::redirectTo()` مباشرة إلى مسار `route('inquiry.create')`.
    - توجيه موظف الاستعلامات في `DashboardController::index()` ومسار الجذر `/` ووسيط المصادقة `RedirectIfAuthenticated` في `bootstrap/app.php` مباشرة إلى `route('inquiry.create')` لتوفير الوقت وسرعة الاستجابة لطلبات المراجعين.
  * **إلغاء التنقل والقفزات بين الصفحات ([InquiryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/InquiryController.php), [create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/inquiry/create.blade.php), [app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php))**:
    - اختصار الدورة كاملة في رابط واحد مباشر «الاستعلامات (طلب جديد)» `inquiry.create`.
    - شريط البحث اللحظي الفوري في أعلى الشاشة وتحته مباشرة كافة الخدمات الطبية (كشف، تحاليل، أشعة، صيدلية، مصرف دم، تحويلات تخصصية).
  * **قفل وحظر اختيار أي خدمة أو تحويل تخصصي لحين تحديد المريض أولاً (Strict Patient-First Services Lock)**:
    - إبقاء كافة بطاقات الخدمات والتحويلات مقفلة وغير قابلة للاختيار برمجياً وبصرياً (`pointer-events: none`, `opacity: 0.45`, `grayscale`) مع تنبيه القفل `#servicesLockedNotice`.
    - حظر النقر في `toggleRequestType` وروابط الأقسام التخصصية لحين اختيار المريض، وفك القفل فورياً بمجرد اختيار المريض من نتائج البحث، وإعادة القفل عند «تغيير المريض».
  * **معالجة وحل مشكلة اختيار المريض من النتائج (Direct Selection Fix & JS Const Error Resolution)**:
    - حل الخطأ الجوهري `TypeError: Assignment to constant variable` الناجم عن تعريف `currentPatientId` كـ `const` وإعادة تعيينه عند النقر، وتحويله إلى `let` مع إضافة `currentPatientIsInsured` و `currentPatientIsInsuranceLocked`.
    - توفير دالة اختيار عامة `window.selectPatientById(pId)` مرتبطة بكل سطر وبزر «اختيار» مباشرة إلى جانب تفويض النقر `results.addEventListener('click')` لضمان استجابة النقر فورياً على أي جهاز ومتصفح.
    - تحديث ديناميكي فوري لقسم التغطية المالية للضمان والكاش (`#insuranceCoverageSection`) وتنبيه نواقص الضمان (`#patientInsuranceLockedAlert`) عند اختيار المريض دون الحاجة لإعادة تحميل الصفحة.
  * **الاختبارات وضمان الجودة ([InquiryStreamlinedDirectBookingTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/InquiryStreamlinedDirectBookingTest.php))**:
    - اجتياز اختبارات الميزة بنجاح تام (5 passed, 27 assertions) تشمل فحص تسجيل الدخول وتحويل لوحة التحكم والصفحة الرئيسية، واختبارات الضمان (5 passed, 26 assertions) وتوافق كاش القوالب `view:cache` وتنسيق Pint.

- **المرحلة 305 (2026-11-26 — `main`) — إعادة هيكلة جدول محطة الطبيب المقيم إلى 5 أعمدة واضحة وفصل اسم المريض في عمود مخصص مع إضافة عمود العملية والغرفة والطبيب الأخصائي (Resident Doctor Station: 5-Column Table Restructure — Isolated Patient Name, Surgery & Room, Specialist Doctor, Phase & Actions)**:
  * **فصل اسم المريض وإعادة هيكلة الجدول إلى 5 أعمدة واضحة ([resources/views/surgery-stations/resident/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/resident/index.blade.php))**:
    - تخصيص العمود الأول لـ «اسم المريض» فقط ورقم الملف الطبي دون أي تكديس للشارات، لتوفير رؤية بصرية مريحة وسريعة للمقيم.
    - إضافة عمود مخصص لـ «العملية والغرفة» يضم اسم الجراحة ورقم الغرفة والطابق وحالة استلام ممرض الطابق وتاريخ ووقت الموعد.
    - إضافة عمود مخصص لـ «الطبيب الأخصائي» يعرض اسم الجراح الأخصائي (`د. [اسم الطبيب]`) بأيقونة سريرية وتخصصه الطبي.
    - عمود «المرحلة» بشارات الأطوار السريرية الملونة (قبل الصالة، داخل الصالة، بعد الصالة).
    - عمود «الإجراءات» بزر «عرض وتعديل» وقفل الدخول حتى استلام الغرفة من التمريض.
    - ضبط توزيع العرض للأعمدة الخمسة بدقة: (اسم المريض 25%، العملية والغرفة 25%، الطبيب الأخصائي 20%، المرحلة 16%، الإجراءات 14%) مع ضبط `colspan="5"`.
    - إزالة شريط أزرار الفلترة العلوية «تصنيف الطور:» بناءً على رغبة المستخدم لتصبح الواجهة نظيفة ومباشرة.
  * **تكامل البحث اللحظي الفوري (Instant Real-time Filter)**:
    - إضافة اسم الطبيب الأخصائي إلى سمة البحث اللحظي `data-search` في أسطر الجدول لتمكين المقيم من البحث والفلترة المباشرة بمجرد كتابة اسم الطبيب الجراح دون الحاجة لإعادة تحميل الصفحة.
  * **تحديث تبويب الأرشيف والعمليات المكتملة**:
    - إضافة عمود «الطبيب الأخصائي» في جدول العمليات المكتملة لتوحيد الرؤية والتناسق عبر كامل التبويبات مع ضبط `colspan="7"`.
  * **الاختبارات وضمان الجودة ([tests/Feature/ResidentStationPostOpTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ResidentStationPostOpTest.php), [tests/Feature/ResidentStationRoomCheckinGateTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ResidentStationRoomCheckinGateTest.php))**:
    - اجتياز اختبارات محطة المقيم والأعمدة الخمسة وسير عمل تسكين الغرفة بنجاح تام (4 passed, 28 assertions).
    - اجتياز اختبارات باقات الأدوية المشتركة [ClinicalStationMedicinePackageTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ClinicalStationMedicinePackageTest.php) (4 passed, 20 assertions).
    - اجتياز فحص كاش القوالب `view:cache` وتنسيق `Laravel Pint`.

- **المرحلة 304 (2026-11-26 — `main`) — تثبيت وتأمين تأشير إعطاء الجرعات الدوائية للممرض بنقرة واحدة لا رجعة فيها وحظر التراجع + ربط ومزامنة توصيات وملاحظات ما بعد الجراحة للجراح بالمحطة السريرية (MAR One-Way Permanent Administration & Surgeon Post-Op Notes Two-Way Clinical Sync)**:
  * **تثبيت وحوكمة إعطاء الجرعات الدوائية للممرض (MAR One-Way Administration Lock)**:
    - منع إلغاء التأشير تماماً لكادر التمريض (`canUserUndoEntry = false` لغير الإدارة) مع توثيق لحظي لاسم الممرض وتاريخ ووقت الجرعة الفعلي وتثبيته في جدول `SurgeryTreatment`.
    - حظر أي محاولة إلغاء للتأشير برمجياً من السيرفر وإرجاع خطأ 422 للممرض، وقفل مربعات الاختيار تلقائياً في الواجهة (`@disabled` مع وسم `mar-dose-locked`) فور أول نقرة ناجحة مع حصر صلاحية التراجع للإدارة حصراً.
  * **ربط ومزامنة توصيات وملاحظات ما بعد الجراحة للجراح ([ClinicalStationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/ClinicalStationController.php), [SurgeryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgeryController.php), [show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - ربط حقل «توصيات ما بعد الجراحة (Post-Op)» في تبويب الخطة السريرية (`tab=plan`) ليقرأ ويعرض تلقائياً ملاحظات الجراح المدونة في تفاصيل العملية الجراحية (`surgeries.post_op_notes` في صفحة `surgeries/{id}`).
    - إبراز شارة مخصصة «من ملاحظات الجراح» بجانب الحقل وتضمين الملاحظات بوضوح في بطاقة خلاصة توصيات الجراح بأعلى تبويب الخطة.
    - تفعيل المزامنة التلقائية الثنائية (Two-Way Sync): عند تعديل الجراح لتفاصيل العملية (`surgeries.updateDetails`) تُحدّث توصيات محطة المقيم `resident_stations.post_op_notes` تلقائياً؛ وعند قيام المقيم أو الطبيب بتحديث تبويب الخطة تُزامن التوصيات فوراً إلى سجل العملية الجراحية `surgeries.post_op_notes`.
  * **الاختبارات وضمان الجودة ([tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php))**:
    - اجتياز كامل اختبارات المحطة السريرية والصلاحيات والمزامنة الثنائية بنجاح تام (24 passed, 314 assertions) واجتياز فحص `Laravel Pint` وتوليد قوالب بليد `view:cache`.

- **المرحلة 303 (2026-11-26 — `main`) — عزل تسوية الأجهزة الطبية للجراح بالحسابات + قفل صالة العمليات بعد الإكمال + بوابة التخريج المالية للمرضى الراقدين وإصلاح الأرشيف + وضع انتظار الغرفة (`Room Standby`) بصلاحية مخصصة (OR Completion Lock, Surgeon-Only Medical Devices Accounting, Strict Discharge Financial Clearance & Permission-Gated Room Standby)**:
  * **تسوية الأجهزة الطبية وفصل كاشير المريض ([2026_11_26_130000_add_devices_billing_status_to_surgeries.php](file:///C:/get/hospital-system/database/migrations/2026_11_26_130000_add_devices_billing_status_to_surgeries.php), [Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php), [AccountantController.php](file:///C:/get/hospital-system/app/Http/Controllers/AccountantController.php), [CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [resources/views/accountant/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/accountant/surgeries/index.blade.php), [resources/views/accountant/surgeries/review-form.blade.php](file:///C:/get/hospital-system/resources/views/accountant/surgeries/review-form.blade.php))**:
    - فصل محاسبة الأجهزة الطبية عن `billing_status` الخاص بفاتورة المريض ونقلها لحقل مستقل `devices_billing_status`، مع احتساب تكلفة الأجهزة (`getDevicesTotalFee()`) وتحصيلها من حساب الطبيب الجراح فقط في الحسابات وكشف حساب الجراح (`calculateSurgeonFinancials()`, `syncSurgeonDoctorDue()`).
    - استمرار ظهور العمليات التي تم تعديل نوعها في `/cashier/surgeries` مع شارة «بانتظار تسعير الحسابات»، واعتماد `calculatePendingItemsBreakdown()` و`syncPaymentStatus()` لمزامنة `payment_status` فور إضافة تحاليل/أشعة/استشارات بعد العملية مع إشعار تنبيهي في الكاشير العام، وضبط تحاليل ما بعد العملية لتكون `منفصلة` افتراضياً.
  * **إغلاق دورة صالة العمليات بعد الإكمال ([SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [OperationTheaterStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/OperationTheaterStationController.php), [resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
    - منع إعادة تشغيل العملية أو إرجاعها للانتظار أو إلغائها أو حذفها بعد إكمالها (`completed`)، وإتمام `pre_op` وإنشاء `post_op` تلقائياً عند الخروج من الصالة.
  * **سياسة المرور المباشر للطلبات السريرية وبوابة التخريج المالية وأرشيف المقيم ([StaffRequestController.php](file:///C:/get/hospital-system/app/Http/Controllers/StaffRequestController.php), [NursingStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/NursingStationController.php), [ResidentStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ResidentStationController.php), [ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php), [resources/views/staff/surgery-lab-tests/index.blade.php](file:///C:/get/hospital-system/resources/views/staff/surgery-lab-tests/index.blade.php), [resources/views/staff/surgery-radiology-tests/index.blade.php](file:///C:/get/hospital-system/resources/views/staff/surgery-radiology-tests/index.blade.php))**:
    - مرور كافة طلبات المقيم للراقدين (مختبر/أشعة/استشارة) مباشرة للمحطات مع شارة الحالة المالية بالكاشير، مقابل فرض براءة الذمة المالية الصارمة (`getFinancialClearanceSummary()`) قبل السماح بالتخريج في التمريض والاستقبال.
    - إصلاح استعلام أرشيف محطة المقيم وإظهار شارة التمييز بين الحالة الراقدة بعد العملية والحالة المكتملة والمخرّجة.
  * **وضع انتظار الغرفة وصلاحية التخصيص اللاحق ([2026_11_26_133000_add_assign_surgery_room_permission.php](file:///C:/get/hospital-system/database/migrations/2026_11_26_133000_add_assign_surgery_room_permission.php), [RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php), [RoleManagementController.php](file:///C:/get/hospital-system/app/Http/Controllers/RoleManagementController.php), [InquiryController.php](file:///C:/get/hospital-system/app/Http/Controllers/InquiryController.php), [resources/views/surgeries/create.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/create.blade.php), [resources/views/surgeries/edit.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/edit.blade.php), [resources/views/inquiry/occupancy.blade.php](file:///C:/get/hospital-system/resources/views/inquiry/occupancy.blade.php), [resources/views/surgery-stations/nursing/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/nursing/index.blade.php))**:
    - إتاحة حجز العملية بدون غرفة (`room_id = null`) عبر وضع الانتظار، وتخصيص الغرفة لاحقاً عبر مسار `POST /surgeries/{surgery}/assign-room` المحمي بصلاحية `assign surgery room` القابلة للنقل بين الاستعلامات والتمريض.
  * **الاختبارات وضمان الجودة ([tests/Feature/SurgeryCashierDevicesAndRoomWaitingWorkflowTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryCashierDevicesAndRoomWaitingWorkflowTest.php))**:
    - اجتياز `Laravel Pint` و`php artisan view:cache` وبناء اختبارات ميزة آلية تغطي كافة المسارات المالية، الحظر، براءة الذمة، وأذونات وضع الانتظار.

- **المرحلة 302 (2026-11-26 — `main`) — توحيد تبويبات محطة الطبيب المقيم في تبويب نشط واحد وإعادة هيكلة الجدول إلى 4 أعمدة رئيسية مع فلاتر الأطوار اللحظية (Resident Doctor Station Unified Active Tab, 4-Column Clinical Table & Instant Phase Filters)**:
  * **توحيد تبويب محطة المقيم ([ResidentStationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/ResidentStationController.php), [resident/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/resident/index.blade.php))**:
    - دمج حالتي التهيئة قبل الصالة والمتابعة بعد الصالة في تبويب موحد أسرع وأنظم تحت مسمى «مرضى الرقود والعمليات النشطة» (`activeSurgeries`).
    - إضافة شريط أزرار الفلترة اللحظية السريعة فورياً بدون إعادة تحميل الصفحة (كافة الحالات النشطة، قبل الصالة، داخل صالة العمليات، بعد الصالة / الرقود).
  * **إعادة هيكلة جدول الحالات النشطة إلى 4 أعمدة رئيسية ([resident/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/resident/index.blade.php))**:
    1. **اسم المريض**: يضم اسم المريض، رقم الملف، العملية الجراحية وتصنيفها، الغرفة والطابق، وتاريخ ووقت الموعد.
    2. **المرحلة**: شارات الأطوار السريرية الملونة (`قبل الصالة (تهيئة)`، `داخل صالة العمليات`، `بعد الصالة (عناية الرقود)`).
    3. **الحالة السريرية**: شارة الطبيب المقيم المكلف، وحالة المحطة السريرية (في الانتظار / معلقة / جارية / مكتملة)، وحالة استلام وتسكين الغرفة من ممرض الطابق.
    4. **الإجراءات**: قفل الزر تنبيهياً إذا لم تُستلم الغرفة بعد، وزر «عرض وتعديل» للدخول المباشر إلى المحطة السريرية للعملية.
  * **الاختبارات وضمان الجودة ([ResidentStationPostOpTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ResidentStationPostOpTest.php), [ResidentStationRoomCheckinGateTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ResidentStationRoomCheckinGateTest.php), [ResidentFollowUpClinicalSignsTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ResidentFollowUpClinicalSignsTest.php))**:
    - اجتياز اختبارات محطة المقيم كاملة بـ 9 اختبارات ناجحة (65 assertions).
    - اجتياز فحص `view:cache` وتنسيق `Pint`.

- **المرحلة 301 (2026-11-26 — `main`) — إعادة هيكلة جدول محطة التمريض إلى 4 أعمدة محددة وسلسة، وحل مشكلة استمرارية البحث المباشر وإعادة فتح قائمة المستلزمات الطبية بعد الإضافة للسلة (Nursing Station 4-Column Table Restructure & Persistent Supplies Dropdown Flow)**:
  * **إعادة هيكلة جدول محطة التمريض إلى 4 أعمدة رئيسية ([nursing/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/nursing/index.blade.php))**:
    - إعادة ترتيب وهيكلة أعمدة جدول مرضى الرقود والعمليات النشطة من 7 أعمدة إلى 4 أعمدة واضحة ومباشرة:
      1. **اسم المريض**: يضم اسم المريض، رقم الملف، العملية الجراحية وتصنيفها، الغرفة والطابق، وتاريخ ووقت الموعد.
      2. **المرحلة**: شارة الطور السريري الحالية (قبل الصالة / داخل صالة العمليات / بعد الصالة) مع اسم الصالة أو تفاصيل المرحلة.
      3. **الحالة السريرية**: حالة استلام وتسكين الغرفة (تم استلام الغرفة / بانتظار الاستلام)، وملاحظات الرعاية والتمريض، واسم الممرض المسؤول.
      4. **الإجراءات**: زر استلام الغرفة، زر عرض وتعديل الملف السريري، وزر التخريج.
    - تحديث خلايا `@empty` وحالة عدم وجود نتائج لتطابق عدد الأعمدة الجديد (`colspan="4"`).
  * **حل اختفاء قائمة البحث اللحظي للمستلزمات الطبية ([show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - معالجة مشكلة اختفاء القائمة المنسدلة عند الضغط على «إضافة للقائمة» ومحاولة إضافة مستلزم آخر:
      1. إضافة زر سهم منسدل (Chevron Toggle Button) مدمج بحقل البحث لفتح وإغلاق القائمة في أي وقت.
      2. ربط حدث `click` على حقل البحث نفسه مع `e.stopPropagation()` و `filterItems(this.value)` لفتح القائمة عند النقر حتى ولو كان الحقل في وضع التركيز (Focused).
      3. استثناء زر «إضافة للقائمة» من حدث النقر العام للوثيقة لمنع إغلاق القائمة عند النقر عليه.
      4. إعادة تفريغ حقل البحث وتعيين `filterItems('')` وإظهار القائمة بـ `display: block` فور إضافة المستلزم إلى السلة، مع تركيز المؤشر ليتمكن المستخدم من اختيار المستلزم التالي فوراً وبسلاسة تامة.
  * **الاختبارات وضمان الجودة**:
    - اجتياز اختبارات محطة التمريض `tests/Feature/NursingStationSurgeriesTest.php` كاملة (4 passed, 21 assertions).
    - اجتياز اختبارات سير عمل تسكين الغرف `tests/Feature/NursingRoomCheckinWorkflowTest.php` كاملة (5 passed, 26 assertions).
    - اجتياز اختبارات صرف عهدة الطابق والسلة الجماعية `tests/Feature/NursingFloorDispenseTest.php` كاملة (6 passed, 43 assertions).
    - اجتياز فحص القوالب `view:cache` وتنسيق `Pint`.

- **المرحلة 300 (2026-11-26 — `main`) — توحيد تبويبات محطة التمريض في تبويب تفاعلي واحد، وإبراز شارة الطور في رأس المحطة السريرية، وتفعيل البحث اللحظي وسلة الصرف الجماعي المتعدد للمستلزمات الطبية (Unified Nursing Station Active Tab, Clinical Phase Header Badge & Live Search Multi-Item Supplies Cart)**:
  * **توحيد تبويب محطة التمريض ([NursingStationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/NursingStationController.php), [nursing/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/nursing/index.blade.php))**:
    - دمج حالتي «قبل الصالة» و «بعد الصالة» في تبويب موحد وأسرع تحت مسمى «مرضى الرقود والعمليات النشطة» يمنح الممرضين رؤية شاملة للمرضى.
    - إضافة أزرار فلترة لحظية سريعة فورية بدون إعادة تحميل الصفحة (كافة الحالات النشطة، قبل الصالة، داخل صالة العمليات، بعد الصالة / الرقود).
    - تمييز كل مريض بشارة ملونة واضحة لطور حالته السريرية، مع أزرار تسكين الغرفة والمغادرة والدخول للتفاصيل المباشرة.
  * **شارة الطور السريري في رأس المحطة ([show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - إبراز وسم الطور الجراحي والسريري في رأس الصفحة بجانب اسم المريض والعملية (`قبل الصالة (تحضير وتهيئة)` / `داخل صالة العمليات` / `بعد الصالة (عناية الرقود)`).
  * **البحث اللحظي التفاعلي للمستلزمات الطبية ([show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - استبدال القائمة المنسدلة التقليدية بحقل بحث فوري لحظي أثناء الكتابة (As-you-type autocomplete) يفلتر المستلزمات الطبية لعهدة طابق المريض.
    - عرض اسم المستلزم، التصنيف، الرصيد المتاح، كود الوجبة/الباركود، وتاريخ الصلاحية مع تظليل النتائج وزر مسح سريع.
  * **سلة الصرف الجماعي المتعدد مع التعديل والحذف المباشر ([ClinicalStationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/ClinicalStationController.php), [show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - تمكين الكادر من إضافة عدة مستلزمات طبية دفعة واحدة إلى قائمة مؤقتة قيد الصرف.
    - إمكانية تعديل كمية أي مستلزم مباشرة من الجدول مع فحص الرصيد الأقصى ومنع التجاوز.
    - إمكانية تعديل سبب الاستخدام والملاحظات لكل مستلزم على حدة، وحذف أي بند من القائمة بضغطة زر، أو تفريغ القائمة بالكامل.
    - زر حفظ وصرف جماعي بنقرة واحدة ينفذ العملية ضمن معاملة مالية ومخزنية آمنة (`DB::transaction`) مع تسجيل حركات `StockMovement` وقيود `SurgeryTreatment` وخصم الأرصدة من وجبات عهدة الطابق بدقة.
  * **الاختبارات وضمان الجودة ([NursingFloorDispenseTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NursingFloorDispenseTest.php), [NursingStationSurgeriesTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NursingStationSurgeriesTest.php))**:
    - إضافة اختبارات ميزة آلية للصرف الجماعي المتعدد وللتبويب الموحد والفلاتر اللحظية.
    - اجتياز اختبارات صرف عهدة الطابق بنجاح (`6 passed, 43 assertions`) واختبارات محطة التمريض (`9 passed, 48 assertions`).
    - اجتياز فحص القوالب `view:cache` و `Laravel Pint` بالكامل.

- **المرحلة 300 (2026-11-26 — `main`) — فصل شاشة «معاينات الوليد الحية» بنظام البطاقات (`Cards`) وإضافتها كعنصر قياسي مستقل أسفل «رادار صالات العمليات والولادة» بالقائمة الجانبية وتنظيف شاشة الرادار (Dedicated NICU Live Newborn Observations Cards Screen, Standard Sidebar Item & Clean Delivery Radar)**:
  * **القائمة الجانبية والمسار ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
    - إضافة مسار `GET /nicu/observations` (`nicu.observations.index`) وإضافة رابط **«معاينات الوليد الحية»** كعنصر `<li class="nav-item">` قياسي مسطّح مطابق لباقي القوائم مباشرة أسفل **«رادار صالات العمليات والولادة»** مع بادج العدّاد الحي.
  * **المتحكمات والواجهات ([NicuDeliveryObservationController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuDeliveryObservationController.php), [NicuDeliveryRadarController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuDeliveryRadarController.php), [resources/views/nicu/observations/index.blade.php](file:///C:/get/hospital-system/resources/views/nicu/observations/index.blade.php), [resources/views/nicu/observations/show.blade.php](file:///C:/get/hospital-system/resources/views/nicu/observations/show.blade.php), [resources/views/nicu/delivery-radar.blade.php](file:///C:/get/hospital-system/resources/views/nicu/delivery-radar.blade.php))**:
    - بناء شاشة `nicu.observations.index` المستقلة بـ 4 كروت KPI + تبويبين (`المعاينات الحية الآن` بعرض البطاقات `Cards` ومؤقتات حية وبحث فوري وأزرار التحويل السريع للحاضنة والطباعة A4 + `أرشيف التسليم والتحويل` القابل للبحث + مودال إضافة ولادة طبيعية يدوياً).
    - تنظيف شاشة رادار الولادات لتبقى مخصصة لجدول عمليات اليوم مع عمود «معاينة الوليد (ساعتان)» وزر وصول سريع لشاشة المعاينات الحية.
  * **الاختبارات وضمان الجودة ([tests/Feature/NicuDeliveryObservationTest.php](file:///C:/get/hospital-system/tests/Feature/NicuDeliveryObservationTest.php))**:
    - اجتياز `22 passed (306 assertions)` عبر `NicuDeliveryObservationTest` و`NicuDeliveryRadarTest` و`RolePermissionsMatrixTest` مع توافق `Laravel Pint` و`view:cache`.

- **المرحلة 299 (2026-11-26 — `main`) — نقل وتخصيص منظومة الخدمات الإضافية والفندقية الذكية (نمط ICD-10) لقسم الخدج والحاضنات NICU مع شاشة إدارة وتسعير «الخدمات المتفرقة للمستشفى» في الإدارة والإعدادات (NICU Smart Extra & Hotel Services Engine + Hospital Miscellaneous Services Admin Management)**:
  * **قاعدة البيانات والنماذج ([2026_11_26_100000_create_nicu_extra_services_and_department_scope.php](file:///C:/get/hospital-system/database/migrations/2026_11_26_100000_create_nicu_extra_services_and_department_scope.php), [NicuExtraService.php](file:///C:/get/hospital-system/app/Models/NicuExtraService.php), [HospitalExtraService.php](file:///C:/get/hospital-system/app/Models/HospitalExtraService.php), [IncubatorReservation.php](file:///C:/get/hospital-system/app/Models/IncubatorReservation.php))**:
    - إضافة عمود `department` (`general`, `nicu`, `all`) لجدول `hospital_extra_services` وبذر 18 خدمة مخصصة لقسم الخدج موزعة على 5 تصنيفات (`clinical`, `care`, `administrative`, `accommodation`, `fine`).
    - إنشاء جدول `nicu_extra_services` وربطه بعلاقة `extraServices()` على `IncubatorReservation` مع عزل خدمات العمليات عن خدمات الخدج (`scopeForGeneral()`, `scopeForNicu()`).
  * **المحرك المالي والكاشير والمطبوعات ([NicuBillingService.php](file:///C:/get/hospital-system/app/Services/NicuBillingService.php), [CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [cashier/nicu/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu/payment-form.blade.php), [nicu-itemized-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-itemized-print.blade.php), [nicu/statement.blade.php](file:///C:/get/hospital-system/resources/views/nicu/statement.blade.php))**:
    - احتساب الخدمات الإضافية للخدج كاش 100% على المريض (خارج الضمان الصحي) وجمعها لحظياً ضمن `grand_total` و`patient_grand_total` و`net_balance`.
    - إضافة صندوق «إضافة خدمات إضافية وفندقية للخدج والحاضنة» بالبحث الذكي والتصنيف السريع في شاشة كاشير الخدج بترتيب مطابق لشاشة العمليات مع إمكانية إضافة خدمة جديدة وحفظها تلقائياً في دليل الخدج، وتعديل السعر والكمية، والحذف.
    - إظهار الخدمات الإضافية تلقائياً في الوصل الحراري التفصيلي وكشف الحساب الرسمي A4.
  * **إدارة «الخدمات المتفرقة للمستشفى» في الإدارة والإعدادات ([HospitalExtraServiceController.php](file:///C:/get/hospital-system/app/Http/Controllers/Admin/HospitalExtraServiceController.php), [admin/extra-services/index.blade.php](file:///C:/get/hospital-system/resources/views/admin/extra-services/index.blade.php), [layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php), [nicu/partials/settings-nav.blade.php](file:///C:/get/hospital-system/resources/views/nicu/partials/settings-nav.blade.php))**:
    - إضافة عنصر قائمة فرعي جديد باسم **«الخدمات المتفرقة للمستشفى»** تحت قسم **الإدارة والإعدادات** وتبويب وصول سريع في إعدادات الخدج.
    - شاشة تحكم متكاملة بإحصائيات وتبويبات للأقسام (العمليات، الخدج، مشترك)، إضافة وتعديل وحذف وتفعيل/تعطيل، مع تعديل وحفظ تلقائي فوري للسعر عبر AJAX.
  * **الاختبارات وضمان الجودة ([NicuExtraServicesTest.php](file:///C:/get/hospital-system/tests/Feature/NicuExtraServicesTest.php))**:
    - 5 اختبارات ميزة آلية شاملة بالإضافة لاختبارات `NicuEnhancementsTest` واجتياز `Laravel Pint` و`view:cache` بالكامل.

- **المرحلة 298 (2026-11-25 — `main`) — تفعيل مركزية الدفع لكافة فواتير ووصفات الصيدلية في الكاشير المركزي وحظر الدفع بالصيدلية مع صلاحية مستقلة ورادار التسليم التفاعلي (Central Cashier Pharmacy Routing, Permission-gated Tab & Real-time Dispensing Handoff)**:
  * **منع الدفع النقدي في الصيدلية وتوجيهه للكاشير المركزي ([PharmacyPosController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/PharmacyPosController.php), [pos/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/pos/index.blade.php))**:
    - حظر أي استلام نقدي أو دفع في نافذة الصيدلية، وتوجيه 100% من مبيعات الرف (OTC) والوصفات الطبية (`prescriptions`) إلى الكاشير المركزي (`payment_route = 'central_cashier'`, `payment_status = 'pending_cashier'`).
    - تحويل أزرار نقطة البيع في الصيدلية إلى `إرسال للكاشير المركزي للدفع (F9)`.
    - ربط محرك احتساب الضمان الصحي والتحمل المالي للمريض تلقائياً حسب نوع التأمين والتعاقد.
  * **صلاحيات مخصصة للتحكم بظهور تبويب الصيدلية في الكاشير المركزي ([RoleManagementController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RoleManagementController.php), [RolesAndPermissionsSeeder.php](file:///c:/wamp64/www/hospital-system/database/seeders/RolesAndPermissionsSeeder.php))**:
    - إنشاء صلاحيتين دقيقتين: `process pharmacy payments` (تسديد مدفوعات الصيدلية) و `view cashier pharmacy` (عرض تبويب الصيدلية في الكاشير).
    - تسجيل الصلاحيتين ضمن الفئة الثالثة (الصندوق والكاشير)، ومنحهما افتراضياً للأدوار الإدارية وأمناء الصندوق، مع إمكانية منحها وسحبها من أي دور بدقة.
  * **تبويب ونافذة تسديد الصيدلية في الكاشير المركزي ([CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php), [cashier/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/index.blade.php), [cashier/pharmacy-payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/pharmacy-payment-form.blade.php), [receipt-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt-print.blade.php))**:
    - إضافة تبويب مخصص «الصيدلية والعلاج» مع عداد لحظي وزر فلترة سريع في جدول الكاشير العام.
    - بناء واجهة تسديد فواتير الصيدلية المستقلة `cashier.pharmacy.payment.form` تدعم النقد، والبطاقة الإلكترونية، والدفع المختلط (Split Payment)، وحساب الخصم وتوثيق السندات المالية رسمياً وتوليد رقم الوصل.
    - دعم طباعة الوصل المالي A4/الحراري مع تفاصيل الأدوية والكميات والأسعار والحصص التأمينية.
  * **رادار الصيدلية اللحظي والتسليم بعد التحقق المالي (Post-Payment Handoff)**:
    - تحديث رادار طابور الصيدلية ليعرض وسام `🟢 مدفوعة بالكاشير - جاهز للتسليم` بمجرد السداد عند الكاشير المركزي، أو `🟡 بانتظار الكاشير`.
    - مسار مستقل لتسليم الدواء `POST /pharmacy/pos/sales/{sale}/handoff` يتحقق أمنياً من `payment_status === 'paid'` ويمنع صرف أي دواء غير مسدد مالياً برمز 422، مع توثيق `dispensed_by` و `dispensed_at` وتحديث حالة الوصفة إلى `dispensed`.
  * **الاختبارات وضمان الجودة ([PharmacyCentralCashierWorkflowTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacyCentralCashierWorkflowTest.php), [PharmacyPosTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacyPosTest.php))**:
    - 6 اختبارات ميزة آلية جديدة شاملة لدورة الصيدلية والكاشير المركزي (30 assertions) ونجاح اختبارات نقاط البيع (30 assertions).
    - اجتياز فحص `view:cache` و `Laravel Pint` بالكامل.

- **المرحلة 297 (2026-11-25 — `main`) — إصلاح خطأ TypeError: htmlspecialchars(): Argument #1 must be string, array given في شاشتي الأشعة والمختبر عند طلبات الخدج (Fix Array Result htmlspecialchars TypeError in Radiology & Lab Views)**:
  * **إصلاح الخطأ في الواجهات ([resources/views/radiology-staff/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/radiology-staff/index.blade.php), [resources/views/lab/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/index.blade.php))**:
    - المشكلة: كانت حلقة المودال السفلية (`#completeNicuRadModal` و `#completeNicuLabModal`) تستدعي موديلات `\App\Models\Request` والتي تحتوي على كاستنج لمصفوفة `'result' => 'array'`، مما تسبب في تمرير مصفوفة إلى دالة `htmlspecialchars()` داخل `{{ $nicuRad->result }}` وانهيار الصفحة برمز 500.
    - الحل الجذري: حصر المودال في موديلات `NicuMedicalOrder` واستبعاد `\App\Models\Request` باستخدام `@if($nicuRad instanceof \App\Models\Request) @continue @endif` (لأن طلبات `Request` تُفتح عبر شاشة التقرير المخصصة لها `radiology-staff.show`).
    - الأمان المزدوج: تحصين طباعة `result` و `technician_notes` بفحص `is_array()` لضمان عدم حدوث أي خطأ طباعة مستقبلاً.
  * **الاختبارات وضمان الجودة ([tests/Feature/RadiologyStaffCallNextTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/RadiologyStaffCallNextTest.php))**:
    - كتابة اختبار انحدار آلي لمحاكاة وجود طلب أشعة للخدج من نوع `MedicalRequest` يحمل نتيجة كمصفوفة `result => [...]` والتحقق من فتح شاشة الأشعة بنجاح بدون أخطاء (`5 passed, 13 assertions`).
    - اجتياز فحص `view:cache` و `Pint`.

- **المرحلة 296 (2026-11-25 — `main`) — توحيد وترقية واجهة إضافة مستخدم جديد وحصر دليل المستخدمين في كادر وموظفي المستشفى واستبعاد المرضى تماماً (Modern Create User Screen Unified with Edit, Live Preview & Complete Patient Exclusion from Staff Directory)**:
  * **استبعاد حسابات المرضى من دليل كادر المستشفى ([UserManagementController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/UserManagementController.php))**:
    - حصر استعلام شاشة المستخدمين وإحصائياتها وبطاقات الـ KPI في كادر وموظفي المستشفى فقط.
    - استبعاد أي مستخدم يحمل دور `patient`، أو مرتبط بسجل في جدول المرضى `patients`، أو يقتصر دوره على مريض بدون أدوار وظيفية.
    - استبعاد دور `patient` من قائمة الأدوار المتاحة للاختيار في شاشتي الإضافة والتعديل ومنع تعيينه لكادر المستشفى.
  * **الواجهة التفاعلية العصرية ([resources/views/users/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/users/create.blade.php))**:
    - إعادة بناء الشاشة كلياً لتتطابق مع شاشة التعديل بنسق العمودين (Two-column layout).
    - إضافة بطاقة جانبية تفاعلية للمعاينة الحية الفورية (Live Profile Preview Card) تتحدث لحظياً بمجرد كتابة الاسم والبريد وتحديد الموقع وعدد الصلاحيات.
    - تقسيم الصلاحيات والأدوار إلى فئات وتصنيفات وظيفية ملونة بأيقونات معبرة (الإدارة العليا، الأطباء، التمريض والطوارئ، الصيدلية، الكاشير والمحاسبة...).
    - دعم حقل البحث اللحظي السريع داخل أسماء الصلاحيات وأزرار «تحديد القسم / إلغاء».
    - حقول كلمة المرور والتأكيد مع أزرار إظهار وإخفاء كلمة المرور (Show/Hide Password).
  * **الاختبارات وضمان الجودة ([UserEditModernViewTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/UserEditModernViewTest.php), [UserManagementIndexTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/UserManagementIndexTest.php))**:
    - إضافة اختبارات ميزة آلية لعرض شاشة الإضافة العصرية بكافة أقسامها وتصنيفاتها وإنشاء مستخدم جديد بنجاح (`4 passed, 28 assertions`).
    - اختبار استبعاد حسابات المرضى بالكامل من الدليل وإحصائيات النظام واجتياز كافة الفلاتر والبحث اللحظي (`5 passed, 22 assertions`) واجتياز `Pint`.

- **المرحلة 295 (2026-11-25 — `main`) — تحديث وتطوير واجهة إدارة المستخدمين إلى تصميم عصري Enterprise مع البحث والفلترة الفورية اللحظية التلقائية (Modern Users Directory & Instant Real-time Debounced Filter)**:
  * **الكنترولر ([UserManagementController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/UserManagementController.php))**:
    - حساب وتمرير إحصائيات سريعة (`stats`): إجمالي الحسابات، الحسابات النشطة، الحسابات المعطلة، وعدد الأدوار الوظيفية.
    - دعم الفلترة المتزامنة بالبحث، الصلاحيات، مواقع التعيين/المخازن (`location_id`)، والحالة التشغيلية.
  * **الواجهة التفاعلية العصرية ([resources/views/users/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/users/index.blade.php))**:
    - ترقية التصميم إلى نمط Enterprise حديث بـ 4 بطاقات إحصائيات علوية (KPI Cards).
    - حقل بحث سريع ذكي بمؤقت Debounce لحظي (420ms) يفلتر البيانات تلقائياً بمجرد الكتابة وبدون الحاجة لزر بحث، مع مؤشر تحميل تفاعلي (Spinner) وزر مسح سريع `(✕)` وحفظ موضع المؤشر.
    - قوائم منسدلة تفاعلية فورية للصلاحيات، المواقع/الأقسام، والحالة تتحدث تلقائياً بـ `onchange`.
    - شارات الفلاتر النشطة (Filter Chips) مع إمكانية حذف أي فلتر بضغطة زر أو مسح كل الفلاتر.
    - جدول مستخدمين عصري بأفاتار ملون (User Initials)، شارات أدوار وأقسام دقيقة، ونقاط حالة نابضة (Active/Inactive Pulse).
    - أزرار إجراءات مستديرة ناعمة بتلميحات Tooltips وحماية حسابات الإدارة العليا.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/UserManagementIndexTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/UserManagementIndexTest.php))**:
    - بناء حزمة اختبارات شاملة للواجهة الجديدة والفلترة اللحظية بحسب الصلاحية والبحث والموقع والحالة (`4 passed, 18 assertions`) مع اجتياز كامل لـ `Pint`.

- **المرحلة 294 (2026-11-25 — `main`) — تطوير سند تحويل وصرف واستلام الشحنات المخزنية الرسمي A4 مع شعار مستشفى الكفاءات الأهلي المعتمد وتوقيعين رسميين وتوثيق خارطة طريق إدارة الهوية والشعار ديناميكياً (Stock Transfer Request Official A4 Voucher, Al-Kafa'at Hospital Emblem, Dual Sign-off & Hospital Branding Roadmap)**:
  * **المسار والكنترولر ([routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php), [StockTransferController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/StockTransferController.php))**:
    - إضافة مسار `GET /stock-transfers/requests/{stockTransferRequest}/print` باسم `stock-transfers.requests.print`.
    - إضافة دالة `printRequest()` مع فحص الصلاحيات الصارم للأدوار الإدارية ومسؤولي عهدة الأقسام المستلمة والمصدرة.
  * **الواجهات وقالب الطباعة الرسمي A4 ([resources/views/stock-transfers/requests/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/stock-transfers/requests/show.blade.php), [resources/views/stock-transfers/requests/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/stock-transfers/requests/print.blade.php))**:
    - إضافة أزرار الطباعة الرسمية في أعلى صفحة الشحنة وأعلى جدول المواد في `show.blade.php`.
    - بناء قالب الطباعة الرسمي A4 مع اعتماد اسم **«مستشفى الكفاءات الأهلي»** (Al-Kafa'at Private Hospital).
    - تضمين شعار المستشفى المعتمد بدقة عالية عبر Base64 Data URI من `images/1.jpg` لضمان عدم تأخر أو انكسار الشعار عند الطباعة.
    - جدول المواد الكامل مع تفاصيل الكميات المطلوبة والمصروفة والمستلمة وفروقات الصرف وملاحظات أمين المخزن.
    - اعتماد التواقيع الرسمية المطلوبة حصراً: **منظم الطلب (مسؤول عهدة القسم المستلم)** و**أمين المخزن المصرف (مسؤول التجهيز والصرف)**.
  * **خارطة طريق إدارة هوية وشعار المستشفى ديناميكياً ([docs/HOSPITAL_BRANDING_ROADMAP.md](file:///c:/wamp64/www/hospital-system/docs/HOSPITAL_BRANDING_ROADMAP.md)) وخارطة طريق النقحرة والترجمة التلقائية لأسماء المرضى ([docs/PATIENT_NAME_AUTO_TRANSLITERATION_ROADMAP.md](file:///c:/wamp64/www/hospital-system/docs/PATIENT_NAME_AUTO_TRANSLITERATION_ROADMAP.md))**:
    - حفظ وأرشفة المخطط الهندسي الكامل لإنشاء لوحة تحكم إدارية لرفع الشعار وتعديل اسم المستشفى والترويسة مع كاشينغ فائق السرعة ومعاينة حية A4 للمستقبل.
    - حفظ وتوثيق المخطط الكامل للنقحرة الصوتية الذكية لأسماء المرضى بالإنجليزية (100% Offline) لتوليدها تلقائياً فور كتابة الاسم بالعربي في الاستقبال والطوارئ والتقارير الطبية.
  * **الاختبارات وضمان الجودة ([tests/Feature/FloorCustodianStockTransferTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/FloorCustodianStockTransferTest.php))**:
    - إضافة اختبارات الطباعة للممرضين ومنع الوصول غير المصرح به للأقسام الأخرى والتحقق من التواقيع واسم مستشفى الكفاءات الأهلي (`15 passed, 107 assertions`).

- **المرحلة 293 (2026-11-25 — `main`) — بناء منظومة معاينة المواليد بعد الولادة (ساعتين) وربطها برادار العمليات مع استمارة `Obstetric Case Sheet` والطباعة A4 والأرشفة الموقعة والتحويل للحاضنة (Dynamic 2-Hour Newborn Observation, Delivery Radar Hub, Obstetric Case Sheet A4 Print, Signed Form Archive & NICU Incubator Handoff)**:
  * **قاعدة البيانات والنماذج ([2026_11_25_140000_create_nicu_delivery_observations_and_settings.php](file:///C:/get/hospital-system/database/migrations/2026_11_25_140000_create_nicu_delivery_observations_and_settings.php), [NicuSetting.php](file:///C:/get/hospital-system/app/Models/NicuSetting.php), [NicuDeliveryObservation.php](file:///C:/get/hospital-system/app/Models/NicuDeliveryObservation.php), [NicuDeliveryObservationBaby.php](file:///C:/get/hospital-system/app/Models/NicuDeliveryObservationBaby.php), [Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php))**:
    - إنشاء جداول `nicu_settings` و`nicu_delivery_observations` و`nicu_delivery_observation_babies` مع دعم التوائم واستقلال قرار كل مولود (`observing`, `handed_over`, `admitted_to_nicu`).
    - إعدادات ديناميكية افتراضية للمعاينة: المدة القياسية `120` دقيقة، بدون أجور ومشمولة مع الولادة افتراضياً (`observation_is_billable=0`, `observation_fee=0`) مع إمكانية تفعيل الأجور وتغيير المدة وترويسة الطباعة لأي مستشفى.
    - إنشاء صلاحية Spatie مستقلة `manage nicu delivery observations` ومنحها افتراضياً لأدوار الخدج والإدارة وتسجيلها في [RoleManagementController.php](file:///C:/get/hospital-system/app/Http/Controllers/RoleManagementController.php).
  * **المتحكمات والمسارات ([NicuDeliveryObservationController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuDeliveryObservationController.php), [NicuDeliveryRadarController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuDeliveryRadarController.php), [IncubatorReservationController.php](file:///C:/get/hospital-system/app/Http/Controllers/IncubatorReservationController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
    - بدء المعاينة بضغطة زر من عملية برادار الولادات مع تعبئة تلقائية لبيانات الأم والجراح والعملية (`startFromSurgery`)، بالإضافة لدعم فتح معاينة يدوية للولادات الطبيعية (`storeManual`).
    - تعديل الاستمارة وإدارة المواليد والعلاجات الوقائية الاختيارية (`update`)، توثيق التسليم القانوني للأهل (`handover`)، رفع ومعاينة الورقة الموقعة والمبصمة (`uploadSignedForm`, `downloadSignedForm`)، طباعة A4 (`print`)، وتحديث الإعدادات الديناميكية (`updateSettings`).
    - دعم التحويل المباشر لكل مولود إلى حاضنة NICU عبر `?observation_baby_id=` في `IncubatorReservationController` وتحديث حالة الملاحظة تلقائياً (`partial` أو `admitted_to_nicu`).
  * **الواجهات والاختبارات ([delivery-radar.blade.php](file:///C:/get/hospital-system/resources/views/nicu/delivery-radar.blade.php), [delivery-radar-settings.blade.php](file:///C:/get/hospital-system/resources/views/nicu/delivery-radar-settings.blade.php), [show.blade.php](file:///C:/get/hospital-system/resources/views/nicu/observations/show.blade.php), [print.blade.php](file:///C:/get/hospital-system/resources/views/nicu/observations/print.blade.php), [create.blade.php](file:///C:/get/hospital-system/resources/views/incubator_reservations/create.blade.php), [NicuDeliveryObservationTest.php](file:///C:/get/hospital-system/tests/Feature/NicuDeliveryObservationTest.php))**:
    - تطوير رادار الولادات إلى 3 تبويبات متكاملة (رادار عمليات اليوم + لوحة المعاينات الحية بمؤقت حي + أرشيف التسليم والتحويل القابل للبحث) مع إصلاح خطأ `@elseif(in_progress === false ...)` في القالب.
    - اجتياز `Laravel Pint` و`view:cache` وحزمة الاختبارات (`28 passed, 368 assertions`).

- **المرحلة 292 (2026-11-25 — `main`) — تطوير تبويب «باقة الخدمة والتبديل ومعلومات الدخول» في ملف الخديج مع صلاحية مستقلة لمعلومات الدخول والتاريخ المرضي للطفل والأم (NICU Admission Info, Reason, Infant & Maternal Medical History with Granular Permission)**:
  * **قاعدة البيانات والمودل ([2026_11_25_130000_add_admission_history_fields_to_incubator_reservations.php](file:///C:/get/hospital-system/database/migrations/2026_11_25_130000_add_admission_history_fields_to_incubator_reservations.php), [IncubatorReservation.php](file:///C:/get/hospital-system/app/Models/IncubatorReservation.php))**:
    - إضافة الأعمدة (`admission_reason`, `medical_history`, `maternal_history`, `admission_info_updated_by`, `admission_info_updated_at`) إلى جدول `incubator_reservations`.
    - إنشاء صلاحية Spatie مستقلة: `manage nicu admission history` ومُنحت تلقائياً لأدوار الأطباء والإدارة (`nicu_doctor`, `doctor`, `resident`, `admin`, `admin-hsop`, `hospital_admin`, `super_admin`) دون التمريض بحيث يمكن للإدارة منحها لأي ممرض أو مستخدم عبر شاشة الأدوار ([RoleManagementController.php](file:///C:/get/hospital-system/app/Http/Controllers/RoleManagementController.php)).
  * **الكنترولر والمسار ([NicuController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
    - إضافة دالة `updateAdmissionHistory()` ومسار `POST /nicu/reservations/{reservation}/admission-history` (`nicu.admission-history.update`) مع قفل التعديل التلقائي عند خروج الطفل (`discharged`).
  * **الواجهة ([resources/views/nicu/show.blade.php](file:///C:/get/hospital-system/resources/views/nicu/show.blade.php))**:
    - تعديل اسم التبويب الأول إلى: **`باقة الخدمة والتبديل ومعلومات الدخول`**.
    - إضافة بطاقة منظمة لـ **«معلومات الدخول والتاريخ المرضي»** تتضمن ملخص الدخول الأساسي و3 حقول كتابة صافية وموسّعة (**سبب الدخول للحاضنة**، **التاريخ المرضي للطفل**، **التاريخ المرضي للأم**) مع وضع القراءة فقط لمن لا يملك الصلاحية أو بعد الخروج.
  * **الاختبارات الآلية وضمان الجودة ([tests/Feature/NicuEnhancementsTest.php](file:///C:/get/hospital-system/tests/Feature/NicuEnhancementsTest.php))**:
    - اجتياز `23 passed (328 assertions)` عبر `NicuEnhancementsTest`, `NicuDischargedChartAndClinicalProfileTest`, و `RolePermissionsMatrixTest`، وتوافق تام مع `Laravel Pint` و `view:cache`.

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
... [truncated for diff preview]