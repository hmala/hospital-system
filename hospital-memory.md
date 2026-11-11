# Hospital System Memory & Architecture Guide (الذاكرة المركزية للنظام)

> **الغرض من هذا الملف:**  
> يعمل هذا الملف كـ **الذاكرة المستدامة والمهارة المرجعية (Persistent Memory Skill)** لنظام إدارة المستشفى. يُقرأ في بداية كل جلسة عمل أو عند الانتقال بين الأجهزة والحسابات المختلفة لضمان استمرارية التطوير، فهم البنية المعمارية، الالتزام بالمعايير البرمجية، ومعرفة أحدث حالة وصل إليها المشروع بدقة.

---

## 1. نظرة عامة على المشروع (Project Overview)
- **البيئة التقنية الأساسية:** PHP 8.2 / Laravel 12.
- **الواجهة الأمامية (Frontend):** Vite, Blade Templates, Tailwind CSS, Bootstrap 5, Chart.js v4, Laravel Echo & Pusher.
- **قاعدة البيانات:** MySQL / MariaDB (محلياً عبر WAMP: `hospital_pho`، وسحابياً عبر Hostinger: `u175868711_her2020`).
- **نظام الصلاحيات والأدوار:** `spatie/laravel-permission` مع مصفوفة صلاحيات تفصيلية ومحدثة دائماً بـ `RolesAndPermissionsSeeder`.
- **نظام التشغيل المحلي:** Windows (WampServer `c:\wamp64\www\hospital-system`).
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
  * **التحويل الداخلي (`stock_movements` / `StockTransfer`):** يوافق أمين المخزن الرئيسي على الطلب وتتحرك الوجبات من رصيد الموقع الرئيسي (Location #1) إلى رصيد مخزن الطابق التابع للممرض.
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

- **دمج تحديثات فرع `hr` في الفرع الرئيسي `main` (2026-11-11) — تكامل وحدة العناية المركزة والقلبية `ICU/CCU`، تسوية الخدج `NICU`، وصلاحيات وقسائم `HR` مع الحفاظ على مسار العمل المحلي `WAMP`:**
  * **الدمج التام مع استبعاد تعديل القواعد ومسار `C:\get\hospital-system`**: تم دمج كافة الأكواد والواجهات والمايجريشنات والبذور من `origin/hr` (`b2966c1`) إلى `main` مع الحفاظ الصارم على مسار العمل الرسمي `c:\wamp64\www\hospital-system` وقواعد الرفع على `main` فقط (`GEMINI.md`, `.agents/rules/hospital-core.md`).
  * **تحديث قاعدة البيانات المحلية (`hospital_pho`)**: تشغيل الـ 5 مايجريشنات الجديدة (`nicu_milk_types`, `icu_beds`, `icu_admissions`, `ccu_services`, `ccu_stay_brackets`, `ccu_settings`, إلخ) وبذور `IcuBedSeeder`, `CcuTariffSeeder`, `RolesAndPermissionsSeeder`، مع الحفاظ التام على تصميم الاستعلامات الثنائي وتكامل بطاقة **`حجز عناية مركزة (ICU)`** في [`resources/views/inquiry/create.blade.php`](file:///c:/wamp64/www/hospital-system/resources/views/inquiry/create.blade.php)، واجتياز 21 حزمة اختبارية (`168 passed, 1,186 assertions`) و `Pint` (`71 files`).

- **المرحلة 188 (2026-11-11 — `main`) — إضافة خيار خروج "خرج متوفي" (`deceased`) لمحطة الطوارئ مع التقارير الطبية وسجل الحالات والتحقق المالي (Emergency Deceased Discharge Option, Reports & Financial Clearance Parity)**:
  * **الكنترولر ومنطق الخروج ([app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php))**:
    - تفعيل القيمة `deceased` ضمن خيارات التخريج المعتمدة في `EmergencyController::discharge` بنفس شرط إبراء الذمة المالية (`getUnpaidAmount()`).
    - ضبط الوجهة الافتراضية `خرج متوفي` والتوصيات الافتراضية `توثيق حالة الوفاة واستكمال الإجراءات الرسمية` بدلاً من عبارة مراجعة العيادة الخارجية.
  * **شاشة الطوارئ وسجل الحالات والتقارير ([resources/views/emergency/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/show.blade.php), [resources/views/emergency/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/index.blade.php), [resources/views/emergency/medical-report.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/medical-report.blade.php), [resources/views/emergency/verify-report.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/verify-report.blade.php))**:
    - إضافة خيار `خرج متوفي` في قائمة تسجيل الخروج مع رسالة تأكيد وشارة داكنة مميزة بعد الاعتماد.
    - توثيق وعرض حالة `deceased` في سجل الطوارئ والتقرير الطبي وصفحة التحقق عبر رمز QR.
  * **الاختبارات الآلية**:
    - إضافة واجتياز اختبار `test_emergency_discharge_deceased_workflow_and_renders_across_views` مع فحص قفل الدفع المسبق في `EmergencyFinancialClearanceAndCancellationTest` بنجاح 100% (`25 passed, 139 assertions`).

- **المرحلة 189 (2026-11-11) — منظومة التسعير الديناميكي وشرائح الإقامة للضمان، شمول التحاليل والأشعة بالباقة بنقرة زر، الكاشير والحسابات والمطالبات، ونقل التشخيص الأولي و ICD-10 لداخل إضبارة الطبيب (CCU Dynamic Tariffs, Official MOH Brackets, One-Click Bundling, Cashier, Treasury & Claims)**:
  * **نقل التشخيص السريري الأولي و ICD-10 ومقياس GCS إلى إضبارة الطبيب**:
    - إزالة حقول `primary_diagnosis`، `icd10_code`، و `initial_gcs` من واجهة الاستعلامات [`resources/views/icu/admit.blade.php`](file:///c:/wamp64/www/hospital-system/resources/views/icu/admit.blade.php) وجعلها اختيارية في `IcuAdmissionController@store`، مع استبدالها ببطاقة تشخيص أولي للطبيب داخل [`resources/views/icu/show.blade.php`](file:///c:/wamp64/www/hospital-system/resources/views/icu/show.blade.php) تدعم البحث الحي الثنائي اللغة عبر `route('doctor.icd10.search')` وتحفظ عبر `CcuCareController@updateInitialDiagnosis`.
  * **قاعدة البيانات والنماذج والتحكم الديناميكي للتسعير (`database/migrations/2026_11_11_100000_create_ccu_billing_and_tariffs_tables.php`, [`CcuTariffSeeder.php`](file:///c:/wamp64/www/hospital-system/database/seeders/CcuTariffSeeder.php))**:
    - إنشاء جداول `ccu_services`, `ccu_stay_brackets`, `ccu_settings`, `ccu_service_logs`, `ccu_payments` وتطوير نماذج `CcuService`, `CcuStayBracket`, `CcuSetting`, `CcuServiceLog`, `CcuPayment`.
    - شاشة التحكم الديناميكي للباقات، الخدمات الإضافية، الشرائح السعرية، والإعدادات [`resources/views/ccu/services/index.blade.php`](file:///c:/wamp64/www/hospital-system/resources/views/ccu/services/index.blade.php) عبر [`CcuServiceController.php`](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CcuServiceController.php).
  * **محرك الاحتساب المالي وتدرج الضمان وتسعيرة الكاش المخصصة ([`app/Services/CcuBillingService.php`](file:///c:/wamp64/www/hospital-system/app/Services/CcuBillingService.php))**:
    - دعم باقات الضمان الرسمية (التنفس الصناعي 750,000 د.ع، الأكسجين 500,000 د.ع)، تدرج الشرائح السنوية للمستشفى الخاص وسقف 30 يوماً سنوياً عبر جميع رقودات المريض في السنة.
    - دعم الكاش بنمطين: الباقة اليومية الشاملة أو تسعيرة السرير المنفصلة (`cash_bed_only_separate`) مع خدمات إضافية مخصصة (`CcuService::TYPE_ADDON`).
    - إظهار التحاليل والأشعة والاستشارات المشمولة بالباقة بمبلغ `0` د.ع للمريض مع حفظ القيمة الأصلية (`original_price` / `bundled_reference_total`) والتبديل اللحظي بنقرة زر بين مشمول بالباقة أو إضافي عبر `toggleOrderBilling` و `toggleConsultationBilling`.
  * **الكاشير، المطالبات، التحليلات، والخزينة المركزية**:
    - تطوير [`app/Http/Controllers/CcuCashierController.php`](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CcuCashierController.php) وتكامله مع [`CashierController.php`](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php) (`#tab-ccu`), [`AccountantController.php`](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/AccountantController.php) (`ccuAnalytics`), [`HealthInsuranceClaimController.php`](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/HealthInsuranceClaimController.php) (تتبع القيمة الأصلية، حصة الضمان، المقبوض، والباقي بذمة الضمان `hia_remaining_balance`), و[`HospitalTreasuryController.php`](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/HospitalTreasuryController.php).
  * **حزمة الاختبارات الآلية الشاملة**:
    - إنشاء [`tests/Feature/CcuBillingAndCashierTest.php`](file:///c:/wamp64/www/hospital-system/tests/Feature/CcuBillingAndCashierTest.php) واجتياز كامل الاختبارات المشتركة (`31 passed, 213 assertions`) وتنسيق Pint بنجاح.

- **المرحلة 188 (2026-11-10) — بناء وتكامل منظومة التوجيهات السريرية المثبتة، جدول الأدوية والمضخات MAR، الاستشارات التخصصية، والمرفقات وتخطيط القلب (CCU Clinical Care Suite)**:
  * **التوجيهات السريرية المثبتة في رأس الإضبارة (Pinned Clinical Directives)**:
    - بناء صندوق التوجيهات السريرية المشددة في أعلى ملف المريض [`resources/views/icu/show.blade.php`](file:///c:/wamp64/www/hospital-system/resources/views/icu/show.blade.php) يبرز أوامر الاستشاري العاجلة مع اسم الطبيب والتوقيت وزر تثبيت/إلغاء التثبيت 📌 بنقرة واحدة عبر نموذج [`app/Models/CcuDoctorNote.php`](file:///c:/wamp64/www/hospital-system/app/Models/CcuDoctorNote.php).
  * **جدول إعطاء وجرعات الأدوية ومضخات التسريب الوريدي (MAR Flowsheet)**:
    - تبويب مخصص `#medicationTabPane` لجدولة الأدوية بالساعة (كل 4، 6، 8، 12، 24 ساعة) ودعم مضخات التسريب الوريدي المستمر (Syringe Pumps) مع معدل الضخ (Infusion Rate) وتحديد مسار الإعطاء (IV, Infusion, IM, NG Tube, Nebulizer, SC).
    - زر التمريض السريع "توثيق الإعطاء الآن" مع توثيق الممرض وتحديث الموعد القادم آلياً، وشارات التنبيه الذكية (🔴 متأخر Overdue، 🟡 مستحق الآن Due Now، 🟢 القادم Upcoming) عبر نموذج [`app/Models/CcuMedicationSchedule.php`](file:///c:/wamp64/www/hospital-system/app/Models/CcuMedicationSchedule.php).
  * **استشارات الأطباء التخصصية (CCU Consultations Suite)**:
    - تبويب مخصص `#consultationsTabPane` لطلب الاستشارات السريرية الداخلية (مع اختيار استشاري المستشفى من القائمة) أو الخارجية، وتوثيق الأجور، ورد وتوصيات الاستشاري المعتمدة بنقرة زر عبر نموذج [`app/Models/CcuConsultation.php`](file:///c:/wamp64/www/hospital-system/app/Models/CcuConsultation.php).
  * **أرشيف المرفقات الطبية وتخطيط القلب (ECG & Medical Attachments)**:
    - تبويب مخصص `#attachmentsTabPane` لرفع وتخزين مستندات وتخطيط القلب ECG والتقارير الطبية والمفراس الخارجي ومعاينتها فورياً عبر نموذج [`app/Models/CcuAttachment.php`](file:///c:/wamp64/www/hospital-system/app/Models/CcuAttachment.php).
  * **المتحكم والمسارات والتحقق الآلي**:
    - بناء متحكم [`app/Http/Controllers/CcuCareController.php`](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CcuCareController.php) ومجموعة مسارات `ccu.*` في [`routes/web.php`](file:///c:/wamp64/www/hospital-system/routes/web.php).
    - حزمة اختبارات آلية جديدة شاملة [`tests/Feature/CcuClinicalSuiteTest.php`](file:///c:/wamp64/www/hospital-system/tests/Feature/CcuClinicalSuiteTest.php) نجحت بالكامل بنسبة 100% (`6 passed, 47 assertions`) إلى جانب استقرار حزمة [`tests/Feature/IcuModuleTest.php`](file:///c:/wamp64/www/hospital-system/tests/Feature/IcuModuleTest.php) (`15 passed, 65 assertions`).

- **المرحلة 187 (2026-11-10) — تثبيت موضع الشاشة والتبويبات النشطة في إضبارة العناية المركزة، وبناء منظومة حجز تحاليل المختبر والأشعة والتصوير الطبي المتكاملة (ICU Dossier Tab & Scroll State Persistence, Fast Lab & Bedside Radiology Suite)**:
  * **تثبيت موضع التمرير واستمرارية التبويب النشط ومنع القفز لأعلى الصفحة ([resources/views/icu/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/icu/show.blade.php), [app/Http/Controllers/IcuController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/IcuController.php))**:
    - بناء محرك حفظ واستعادة موضع التمرير اللحظي في `sessionStorage` بمفتاح فريد لكل مريض `icu_scroll_{id}` وتوقيت مرحلي يمنع إعادة القفز لبداية الصفحة عند حفظ المؤشرات، ميزان السوائل، أوامر الأطباء، مهام الرعاية، أو إرسال التحاليل والأشعة.
    - دعم واستمرارية التبويب النشط عبر `_active_tab` ورابط الـ URL Hash وتخزينه في `sessionStorage`، مع إرساله تلقائياً عبر أي نموذج أو زر في الصفحة واستعادته عبر دالة `redirectBackWithActiveState($request, $tab, $fallbackUrl)`.
  * **منظومة حجز تحاليل المختبر السريعة والمتكاملة مع المختبر المركزي ([resources/views/icu/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/icu/show.blade.php), [app/Http/Controllers/IcuController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/IcuController.php), [app/Http/Controllers/LabStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabStaffController.php))**:
    - تبويب مخصص `#labTabPane` يشمل بحثاً ذكياً فوري Autocomplete من كتالوج المستشفى، أزرار إدراج سريعة بنقرة واحدة لتحاليل العناية الشائعة (ABG غازات دم شرياني، CBC، Electrolytes شوارد، RFT كلى، LFT كبد، PT/INR تخثر، Troponin، وزرع الدم Blood Culture)، صينية شرائح تفاعلية (Chips Tray)، وتحديد أولوية الفحص (Normal, Urgent, 🚨 STAT).
    - توليد الطلب المركزي في جدول `requests` بنوع `lab` وربطه بحقل `icu_admission_id`، وتوزيع الفحوصات في جدول `icu_medical_orders` مع مزامنة حالة الإنجاز والنتائج والمدى المرجعي آلياً عند إدخالها من قبل فني المختبر في `LabStaffController`.
  * **منظومة حجز وتصوير الأشعة والسونار المتنقل بجانب السرير ([resources/views/icu/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/icu/show.blade.php), [app/Http/Controllers/IcuController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/IcuController.php), [app/Http/Controllers/RadiologyStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RadiologyStaffController.php))**:
    - تبويب مخصص `#radiologyTabPane` لحجز الأشعة النقالة Portable CXR، سونار بجانب السرير FAST/Abdomen، إيكو القلب Bedside Echo، ومفراس الدماغ CT Brain مع حقل للملاحظات السريرية ودواعي الفحص.
    - توليد الطلب المركزي في `requests` بنوع `radiology` وتحديد التصنيف الفرعي تلقائياً، ومزامنة تقرير الطبيب الشعاعي آلياً مع إضبارة العناية عند اعتماده في `RadiologyStaffController`.
  * **المايجريشن والنماذج والعلاقات البرمجية (`database/migrations/2026_11_10_115143_add_icu_admission_id_to_requests_and_enrich_icu_orders.php`, [app/Models/Request.php](file:///c:/wamp64/www/hospital-system/app/Models/Request.php), [app/Models/IcuAdmission.php](file:///c:/wamp64/www/hospital-system/app/Models/IcuAdmission.php), [app/Models/IcuMedicalOrder.php](file:///c:/wamp64/www/hospital-system/app/Models/IcuMedicalOrder.php))**:
    - إضافة حقل `icu_admission_id` إلى جدول `requests`.
    - إثراء جدول `icu_medical_orders` بحقول الفحوصات والأسعار والمجموعات والنتائج والمرفقات ومسؤول الطلب.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/IcuModuleTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/IcuModuleTest.php))**:
    - توسيع الاختبارات إلى 15 اختباراً آلياً تشمل اختبارات رندرة تبويبات المختبر والأشعة، حجز التحاليل بأولوية STAT، حجز الأشعة والسونار، وثبات موضع التبويب النشط بنسبة نجاح 100% (`15 passed, 65 assertions`).

- **المرحلة 186 (2026-11-10) — ربط وتكامل حجز العناية المركزة في الاستعلامات وزيارات المرضى، وتخصيص استلام المريض الفوري (ICU Inquiry Admission Integration, Pre-selected Patient Handoff & Reception Booking Card Suite)**:
  * **تكامل شاشة الاستعلامات `inquiry.create` وزيارات المرضى `visits.edit`**:
    - إضافة بطاقة خدمة "حجز عناية مركزة (ICU)" في شاشة إنشاء طلبات الاستعلامات [resources/views/inquiry/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/inquiry/create.blade.php) وشاشة الزيارة [resources/views/visits/edit.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/visits/edit.blade.php) بنفس الهوية البصرية لبطاقات العمليات والطوارئ والخدج (`fa-heartbeat`, `text-danger`).
    - تمرير رقم المريض `?patient_id={id}` تلقائياً للرابط `icu.admissions.create`.
  * **تطوير واجهة الاستقبال وحجز سرير العناية ([resources/views/icu/admit.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/icu/admit.blade.php), [app/Http/Controllers/IcuAdmissionController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/IcuAdmissionController.php))**:
    - استلام فوري للمريض المحول من الاستعلامات مع تنزيل بطاقة بياناته آلياً وإخفاء حقل البحث الأولي مع إمكانية التغيير بنقرة زر.
    - قراءة حالة الضمان بدقة وإلغاء الافتراض الخاطئ لمسار الضمان لمرضى الكاش غير المشمولين، وإظهار تفاصيل الضمان فقط للمرضى المشمولين فعلياً.
    - حماية واستدعاء علاقة `healthInsuranceCategory` في `IcuAdmissionController@create`.
  * **مصفوفة الصلاحيات والحوكمة ([database/seeders/RolesAndPermissionsSeeder.php](file:///c:/wamp64/www/hospital-system/database/seeders/RolesAndPermissionsSeeder.php), [app/Http/Controllers/InquiryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/InquiryController.php))**:
    - إسناد صلاحيتي `view icu radar` و `admit icu patients` لدوري الاستعلامات `receptionist` و `inquiry_staff`.
    - شمول صلاحيات العناية في مصفوفة السماح لدخول الاستعلامات `InquiryController@create`، مع حماية استدعاءات أدوار السونار والإيكو المفقودة بـ `try/catch`.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/IcuModuleTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/IcuModuleTest.php))**:
    - توسيع الاختبارات إلى 11 اختباراً آلياً تشمل اختبار رندرة بطاقة العناية المركزة في الاستعلامات، واختبار تسكين المريض المحول بـ `patient_id` بنجاح 100% (`11 passed, 43 assertions`).

- **المرحلة 185 (2026-11-10) — بناء وتفعيل منظومة وحدة العناية المركزة والحرجة المتكاملة: رادار الأسرة الحي، شيت المؤشرات بالساعة، ميزان السوائل، وأوامر الطبيب ورعاية التمريض (ICU / CCU Comprehensive Critical Care Suite)**:
  * **قاعدة البيانات وهيكلية الجداول (`database/migrations/2026_11_10_095152_create_icu_beds_and_admissions_tables.php`)**:
    - جدول `icu_beds`: إدارة أسرة العناية (رقم السرير، نوعه: عام / عزل ضغط سالب / عناية قلبية CCU / عناية عصبية Neuro، الغرفة، الطابق، الحالة التشغيلية، السعر اليومي).
    - جدول `icu_admissions`: سجل دخول وتسكين المريض بالعناية، التشخيص السريري، ICD-10، مقياس الوعي الأولي GCS، جهاز التنفس، العزل، والتأمين.
    - جدول `icu_vital_signs`: شيت العلامات الحيوية بالساعة (GCS، النبض، الضغط، MAP المحسوب آلياً، SpO2، الحرارة، سكر الدم، إعدادات جهاز التنفس Mode، FiO2، PEEP).
    - جدول `icu_fluid_balances`: ميزان السوائل بالساعة (الداخل: محاليل، دم، تغذية، علاجات / الخارج: بول، مفارغ جراحية، رايل، مفرزات، واحتساب الصافي Net Balance تلقائياً).
    - جدول `icu_medical_orders`: أوامر الطبيب الاستشاري السريرية بأولويات (`routine`, `urgent`, `stat`) ومتابعة تنفيذها من الكادر.
    - جدول `icu_care_schedules`: جدول مهام الرعاية التمريضية بالساعة (تقليب، سحب مفرزات، تفريغ كيس البول).
  * **النماذج والعلاقات البرمجية ([app/Models/IcuBed.php](file:///c:/wamp64/www/hospital-system/app/Models/IcuBed.php), [app/Models/IcuAdmission.php](file:///c:/wamp64/www/hospital-system/app/Models/IcuAdmission.php), [app/Models/IcuVitalSign.php](file:///c:/wamp64/www/hospital-system/app/Models/IcuVitalSign.php), [app/Models/IcuFluidBalance.php](file:///c:/wamp64/www/hospital-system/app/Models/IcuFluidBalance.php), [app/Models/IcuMedicalOrder.php](file:///c:/wamp64/www/hospital-system/app/Models/IcuMedicalOrder.php), [app/Models/IcuCareSchedule.php](file:///c:/wamp64/www/hospital-system/app/Models/IcuCareSchedule.php))**:
    - الفرز الرقمي المتسلسل للأسرة `scopeOrderByNumber()`.
    - شارات وأنماط الأنواع والحالات (`type_badge_class`, `status_badge_class`, `gcs_badge_class`).
    - محرك الاحتساب التلقائي لصافي السوائل والضغط الشرياني الوسطي MAP.
    - إضافة تصنيف `'icu' => 'إيراد العناية المركزة (ICU)'` في [app/Models/FinancialTransaction.php](file:///c:/wamp64/www/hospital-system/app/Models/FinancialTransaction.php).
  * **المتحكمات والمسارات البرمجية ([app/Http/Controllers/IcuController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/IcuController.php), [app/Http/Controllers/IcuAdmissionController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/IcuAdmissionController.php), [app/Http/Controllers/IcuBedController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/IcuBedController.php))**:
    - رادار العناية الحية (`/icu`): إحصائيات KPI الحية للأسرة وأجهزة التنفس، شبكة الكروت التفاعلية مع تنبيهات مستوى الوعي GCS.
    - إضبارة مريض العناية (`/icu/admissions/{id}`): شيت الفلوشيت بالساعة، ميزان السوائل In/Out، أوامر الطبيب، وتخريج المريض وتحويل السرير للتعقيم.
    - تسجيل الدخول وحجز السرير (`/icu/admissions/create`): تسكين المريض، تحديد السرير والطبيب والتشخيص الأولي.
    - إدارة وتوزيع الأسرة (`/icu/beds`): متابعة التعقيم والصيانة وإضافة أسرّة جديدة.
  * **واجهات المستخدم والقائمة الجانبية ([resources/views/icu/radar.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/icu/radar.blade.php), [resources/views/icu/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/icu/show.blade.php), [resources/views/icu/admit.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/icu/admit.blade.php), [resources/views/icu/beds/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/icu/beds/index.blade.php), [resources/views/layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php))**:
    - تصميم عصري متناسق مع شاشات الخدج والعمليات، إبراز حالات الأسرة بالألوان، وإدراج قسم العناية المركزة في القائمة الجانبية بشارة عدد المرضى الراقدين.
  * **الصلاحيات وحزمة الاختبارات الآلية الشاملة ([database/seeders/RolesAndPermissionsSeeder.php](file:///c:/wamp64/www/hospital-system/database/seeders/RolesAndPermissionsSeeder.php), [tests/Feature/IcuModuleTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/IcuModuleTest.php))**:
    - 8 صلاحيات دقيقة وأدوار `icu_doctor` و `icu_nurse`.
    - 7 اختبارات ميزة آلية تغطي الدخول، الرادار، العلامات، السوائل، الأوامر، والتخريج بنسبة نجاح 100% (`7 passed, 30 assertions`).

- **المرحلة 184 (2026-11-10) — منظومة تسوية وتفكيك الضمان الصحي الشاملة للخدج: المختبر والأشعة والاستشارات والحاضنة بالتوازي وفق الفئة E (NICU Comprehensive Insurance Settlement & Co-payment Engine)**:
  * **تفكيك وحساب تسوية الضمان للخدج وفق الفئة E ([app/Services/NicuBillingService.php](file:///c:/wamp64/www/hospital-system/app/Services/NicuBillingService.php))**:
    - اعتماد الفئة E رسمياً لكافة الخدج المشمولين بالضمان (`is_insured`):
      * الفحوصات المخبرية (`lab`): مساهمة الضمان 75% وتحمل المريض 25% للفحوصات المشمولة بالضمان (`is_hi_active`).
      * الفحوصات الإشعاعية (`radiology`): مساهمة الضمان 75% وتحمل المريض 25% للفحوصات المشمولة.
      * استشارات الأطباء (`consultations`): الاستشارات الداخلية مشمولة بنسبة 90% ضمان و 10% مريض، والاستشارات الخارجية غير مشمولة (100% كاش على المريض).
      * الفحوصات والخدمات غير المشمولة: 100% كاش على المريض و 0% على الضمان مع بيان شارة صريحة.
      * أجور الحاضنة اليومية: احتسابها وفق الفئة E ومصفوفة عمر الحمل الرسمية للوزارة.
    - تجميع المجاميع الكبرى: `insurance_grand_total` (إجمالي مطالبة الضمان = حاضنة + مختبر + أشعة + استشارات داخلية)، و `patient_grand_total` (حصة المريض المستحقة)، وتحديث الحجز تلقائياً.
  * **تحديث كشف الحساب التراكمي للخدج ([resources/views/nicu/statement.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/nicu/statement.blade.php))**:
    - إضافة أعمدة التغطية المعتمدة ومساهمة الضمان وتحمل المريض كاش في جدول الاستشارات (Section 2) وجدول الفحوصات المخبرية والأشعة (Section 3).
    - إضافة سطور مجاميع مفككة للمختبر والأشعة والاستشارات.
    - تفكيك بطاقة الخلاصة المالية النهائية لتبين بدقة مساهمات الضمان وحصص المريض في الحاضنة والمختبر والأشعة والاستشارات، وإجمالي مطالبة الضمان، وحصة المريض، والمسدد، والفائض المستحق للإرجاع إن وجد.
  * **تحديث واجهة تسديد الكاشير الموحدة للخدج ([resources/views/cashier/nicu/payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/nicu/payment-form.blade.php))**:
    - قراءة وتحديث أرقام شريط الموقف المالي العلوي وصندوق التسديد الذكي لحساب المتبقي الحقيقي للدفع دون مطالبة ولي الأمر بأي مبالغ يغطيها الضمان.
    - إضافة أعمدة وشارات التغطية ومساهمات الضمان وتحمل المريض في جداول التحاليل والأشعة والاستشارات مع سطر المجاميع المفككة لكل قسم.
  * **تحديث شيت كشوفات ومطالبات الضمان للخدج ([resources/views/cashier/nicu-statements.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/nicu-statements.blade.php))**:
    - تحديث عمود تفصيل البنود لإبراز مساهمة الضمان بالحاضنة والمختبر والأشعة والاستشارات، ومطابقة مطالبة الضمان الشاملة.
  * **تحديث وصل التسوية والخدمات التفصيلي الحراري/A4 ([resources/views/cashier/nicu-itemized-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/nicu-itemized-print.blade.php))**:
    - إدراج تفكيك نسب ومبالغ الضمان والمريض تحت كل تحليل وفحص أشعة واستشارة، وتحديث صندوق الموقف المالي الشامل.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/NicuInsuranceSettlementAndClaimsTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NicuInsuranceSettlementAndClaimsTest.php))**:
    - 5 اختبارات ميزة آلية تغطي: الاحتساب التلقائي لأيام الرقود، مصفوفة عمر الحمل، انعكاس المطالبات في الخزينة المركزية، كشوفات الكاشير، وتفكيك مساهمة الضمان والمريض في التحاليل والاستشارات وشاشتي الكشف والكاشير بنجاح 100% (`5 passed, 35 assertions`).

- **المرحلة 183 (2026-11-09) — ضبط الفرز الرقمي المتسلسل للحاضنات (1 إلى 16)، والتكامل التام بين حاضنات العناية وكبسولات العلاج الضوئي (NICU Natural Numerical Sequence Sorting & Dynamic Phototherapy/Capsule Suite)**:
  * **الفرز الرقمي الصحيح للحاضنات ([app/Models/Incubator.php](file:///c:/wamp64/www/hospital-system/app/Models/Incubator.php))**:
    - معالجة مشكلة الفرز الأبجدي للنصوص (Lexicographical Sorting) الناتج عن تخزين `incubator_number` كـ `VARCHAR` والذي كان يفرز 10 و 11 مباشرة بعد 1.
    - إضافة الـ Scope المخصص `scopeOrderByNumber($query, $direction = 'asc')` الداعم لمحركي MySQL (`CAST(incubator_number AS UNSIGNED)`) و SQLite (`CAST(incubator_number AS INTEGER)`).
  * **تطبيق الفرز الرقمي عبر كافة شاشات ومسارات الحاضنات**:
    1. لوحة الخدج الرئيسية `/nicu` عبر [app/Http/Controllers/NicuController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/NicuController.php).
    2. واجهة حجز واستقبال الخديج `/incubator-reservations/create` عبر [app/Http/Controllers/IncubatorReservationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/IncubatorReservationController.php) وقوائم الاختيار التفاعلية.
    3. إدارة الحاضنات `/incubators` عبر [app/Http/Controllers/IncubatorController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/IncubatorController.php).
  * **الحوكمة والتكامل التام لنوعي الحاضنات (حاضنة اعتيادية مقابل كبسولة علاج ضوئي)**:
    - تحديث `getTypeNameAttribute`, `getTypeColorAttribute`, `getTypeIconAttribute`, و `scopeOfType` لدعم التوافق المزدوج مع نوعي `capsule` و `phototherapy`.
    - تحديث إحصائيات KPIs التراكمية في شاشة الخدج الرئيسية وشاشة إدارة الحاضنات لتظهر بدقة عدد الحاضنات وعدد كبسولات العلاج الضوئي بشكل مستقل.
    - تحديث تبويبات شاشة `/incubators` ومرشحات شاشة `/incubator-reservations/create` لفرز وفلترة النوعين بسلاسة مع الحفاظ التام على الأرقام القائمة للمستشفى.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/IncubatorSortingAndTypeTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/IncubatorSortingAndTypeTest.php))**:
    - 4 اختبارات ميزة آلية تغطي: الفرز الرقمي المتسلسل، محاكاة الـ 16 حاضنة في NICU، حجز واستقبال الخديج وفلترة العلاج الضوئي، وتبويبات إدارة الحاضنات بنسبة نجاح 100% (`4 passed, 13 assertions`).

- **المرحلة 182 (2026-11-08) — إصلاح حفظ تفكيك الرواتب، تفكيك مصفوفة صلاحيات HR، قالب وقسيمة الراتب الفردية وطباعة باجة الموظف الرسمية (HR Payroll Model Fix, Granular Permissions Matrix, Official Employee ID Badge & Individual Payslip Suite)**:
  * **إصلاح الخلل البرمجي في موديل الرواتب ([app/Models/HrPayroll.php](file:///c:/wamp64/www/hospital-system/app/Models/HrPayroll.php), [database/migrations/2026_10_06_054330_add_new_fields_to_hr_payrolls_table.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_06_054330_add_new_fields_to_hr_payrolls_table.php))**:
    - إضافة كافة حقول التفكيك المالي المفقودة إلى `$fillable` و `$casts` في `HrPayroll`:
      (`overtime_amount`, `absence_deduction`, `loan_deduction`, `social_security_amount`, `tax_amount`, `payment_method`, `is_paid`, `paid_at`).
    - تصحيح الميغريشن الداعم `2026_10_06_054330_add_new_fields_to_hr_payrolls_table.php` بفحص وإضافة الأعمدة في حال عدم وجودها في البيئات الجديدة والاختبارات الآلية لـ SQLite.
    - إضافة دوال المساعدة الحسابية `total_earnings` و `total_deductions` وعلاقة `payrollCycle()`.
  * **تفكيك مصفوفة صلاحيات الموارد البشرية (Granular HR Permissions Matrix & Roles Governance)**:
    - تفكيك الصلاحيات إلى 19 صلاحية دقيقة ومستقلة:
      (`view hr`, `view employees`, `create employees`, `edit employees`, `delete employees`, `print employee badges`, `manage hr documents`, `manage hr actions`, `view hr payroll`, `create hr payroll`, `approve hr payroll`, `delete hr payroll`, `print hr payslips`, `manage hr loans`, `manage hr absences`, `manage hr overtime`, `manage hr shifts`, `manage hr schedules`, `manage hr settings`).
    - تحديث [database/seeders/RolesAndPermissionsSeeder.php](file:///c:/wamp64/www/hospital-system/database/seeders/RolesAndPermissionsSeeder.php) وإسناد كافة الصلاحيات لدور مدير الموارد البشرية `hr_manager` والمدير `admin`.
    - تحديث [app/Http/Controllers/RoleManagementController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RoleManagementController.php):
      * إدراج قسم `hr` ضمن ترتيب موديولات المستشفى `getModuleOrder()`.
      * تصنيف الصلاحيات وعنونتها بشارات وأسماء عربية واضحة في `getPermissionDefinitions()`.
      * تصنيف دور `hr_manager` ضمن فئة الإدارة وشارة `مدير الموارد البشرية (HR)`.
      * عزل صلاحيات الـ HR ضمن مجموعة `permissionGroup('hr')`.
    - حماية كافة متحكمات الموارد البشرية عبر الـ Middlewares بالصلاحيات الدقيقة (`HrPayrollController`, `HrShiftController`, `HrScheduleController`, `HrLoanController`, `HrAbsenceController`, `HrOvertimeController`, `HrActionSettingController`, `HrSettingsController`, `HrAllowanceController`, `HrEmployeeController`).
    - تحديث روابط وأقسام الموارد البشرية في القائمة الجانبية [resources/views/layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php) لتتوافق مع الصلاحيات الدقيقة.
  * **قالب وقسيمة الراتب الفردية للموظف (Individual Employee Payslip Print)**:
    - بناء واجهة طباعة قسيمة الراتب الفاخرة [resources/views/hr/payrolls/payslip.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/hr/payrolls/payslip.blade.php) والمسار `Route::get('payrolls/slip/{slip}/print')` بالصلاحية `print hr payslips`.
    - جدول مالي مزدوج: استحقاقات ومكافآت (الأساسي، المخصصات، الإضافي، المكافآت) مقابل استقطاعات وخصومات (الغياب، العقوبات، قسط السلفة، الضمان الاجتماعي، الضريبة).
    - بطاقة صافي الراتب المستحق بالدينار العراقي وخانات التواقيع الثلاثية (مسؤول الرواتب، المحاسب المالي، ومدير الموارد البشرية).
    - إضافة زر `[ 🖨️ قسيمة الراتب ]` في جدول شيت الرواتب المركزي [resources/views/hr/payrolls/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/hr/payrolls/show.blade.php).
  * **طباعة باجة وهوية الموظف الرسمية (Official Staff ID Badge / CR80 PVC Standard)**:
    - بناء واجهة بطاقة الهوية الرسمية [resources/views/hr/employees/badge.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/hr/employees/badge.blade.php) والمسار `Route::get('employees/{employee}/badge')` بالصلاحية `print employee badges`.
    - تصميم قياسي مزدوج (وجه أمامي ووجه خلفي):
      * **الوجه الأمامي:** شريط ألوان الأقسام، الشعار، صورة الموظف، الاسم الكامل، المسمى، كود `EMP-xxxx`، وباركود رقمي visual barcode.
      * **الوجه الخلفي:** فصيلة الدم، هاتف الطوارئ، الرقم الوطني، رقم ترخيص المهنة للأطباء، تعليمات المستشفى، ومربع الختم الرسمي.
    - إضافة أزرار طباعة الباجة في سجل الموظفين [resources/views/hr/employees/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/hr/employees/index.blade.php) وإضبارة الموظف [resources/views/hr/employees/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/hr/employees/show.blade.php).
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/HREmployeeTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HREmployeeTest.php))**:
    - توسيع الاختبارات لتشمل: اختبار حفظ تفكيك الرواتب في الموديل، اختبار رندرة وطباعة باجة الموظف، واختبار قسيمة الراتب الفردية بنسبة نجاح 100% (`8 passed, 38 assertions`).

- **المرحلة 181 (2026-11-08) — نظام تسديد وتصفية رسوم الخدج والحاضنات الموحد وإلغاء الأمانات والسلف بسلاسة واجهة العمليات الجراحية (NICU Cashier Unified Folio & Payment Suite, Advance Deposit Deprecation & Inpatient Settlement Engine)**:
  * **إلغاء مصطلحي "الأمانات" و"السلفة" وتوحيد المفهوم المالي تحت "التسديد"**:
    - حذف مسميات الأمانات والسلف تماماً من الكاشير والواجهات والإيصالات.
    - توحيد المسميات في [app/Models/NicuPayment.php](file:///c:/wamp64/www/hospital-system/app/Models/NicuPayment.php) والواجهات إلى: (تسديد رسوم الحاضنة، دفعة تسديد، تسديد وتصفية نهائية، واسترجاع فائض الرسوم).
    - تحديث سندات القبض والإيصالات الحرارية في [resources/views/cashier/nicu-receipt.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/nicu-receipt.blade.php)، [resources/views/cashier/nicu-receipt-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/nicu-receipt-print.blade.php)، و [resources/views/cashier/nicu-itemized-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/nicu-itemized-print.blade.php).
  * **واجهة تسديد رسوم الخدج المستقلة الفاخرة (ستايل العمليات الجراحية الموحد) ([resources/views/cashier/nicu/payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/nicu/payment-form.blade.php))**:
    - صفحة تسديد وتصفية عريضة بديلة للمودال القديم.
    - بطاقتان علويتان: (بيانات الحاضنة، باقة الخدمة الحالية، الطبيب المشرف، تاريخ وساعة الدخول، وعدد الأيام الفندقية والساعات المحسوبة حياً) + (بيانات ولي الأمر، الهاتف، وشارة التغطية كاش أو ضمان وطني).
    - شريط الأرقام المالية العلوي: إجمالي التكلفة المعتمدة | المسدد سابقاً | المتبقي للتسديد أو فائض مستحق الإرجاع.
    - كشف البنود السريرية المفصلة: باقات الإقامة والتنفس، الفحوصات المخبرية، الأشعة والسونار، الاستشارات الطبية، الأدوية وتغذية الخدج، وسجل الدفعات المسددة سابقاً مع أزرار المعاينة والطباعة.
    - صندوق التسديد السفلي الذكي (Smart Payment Dock) بنفس ستايل العمليات: كبسولات `[ 🟢 تسديد كامل المبلغ المتبقي ]` مقابل `[ 🟡 تسديد جزئي (دفعة مقبوضة الآن) ]` مع شارة حية لاحتساب المتبقي واقتراحات سريعة للمبالغ وخيارات نقد/بطاقة.
    - قسم إرجاع الفائض المالي (Refund) بنقرة واحدة عند وجود مبالغ مسددة بالزيادة كخروج الطفل المبكر.
  * **تحديث شاشة الكاشير الرئيسية ([resources/views/cashier/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/index.blade.php))**:
    - بقاء جدول الخدج في مكانه الطبيعي داخل الكاشير.
    - تحويل زر "قبض دفعة" إلى زر: `[ 💳 تسديد الرسوم ]` ينقل لصفحة التسديد مباشرة، وإزالة المودالات المنبثقة القديمة.
  * **مسارات وتحكم السيرفر ([routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php), [app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php))**:
    - مسار `GET /cashier/nicu/{reservation}/payment` باسم `cashier.nicu.payment.form`.
    - تابع `showNicuPaymentForm()` و `processNicuPayment()` لدعم التسديد الكامل والجزئي واسترجاع الفائض وحفظ السندات والعمليات بنزاهة محاسبية كاملة.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/NicuCashierPaymentFormTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NicuCashierPaymentFormTest.php))**:
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
    - تحديث [database/seeders/RolesAndPermissionsSeeder.php](file:///c:/wamp64/www/hospital-system/database/seeders/RolesAndPermissionsSeeder.php) و [app/Http/Controllers/RoleManagementController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RoleManagementController.php) بإدراج وتصنيف هذه الصلاحيات بمسمياتها العربية وشاراتها الرسمية.
  * **حوكمة حجز الاستقبال وعزل الإجراءات السريرية (Reception Booking vs Clinical Segregation)**:
    - دور الاستعلامات (`receptionist`) محصور بإنشاء وإدارة حجز الحاضنة واستقبال الطفل (`create incubator reservations`, `view incubator reservations`, `manage incubator reservations`) وطباعة أسورة المعصم وكارت الحاضنة الشفاف (`print nicu cards`) وتوليد رابط الكاميرا (`manage nicu baby cam`).
    - الاستعلامات محظورة وممنوعة تماماً على صعيد السيرفر (403 Forbidden) عن أي إجراء سريري أو تمريضي (توثيق العلامات، طلب التحاليل، الأشعة، كتابة الوصفات، تعديل الملف السريري، أو إخراج الطفل).
    - توزيع الصلاحيات السريرية لطبيب الخدج (`nicu_doctor`) والتمريضية لممرضة الخدج (`nicu_nurse`) دون استحداث أدوار جديدة والتزاماً بالأدوار الأساسية للمستشفى.
  * **منظومة إدارة أنواع وتصنيفات حليب وتغذية الخدج (NICU Milk/Formula Dynamic Catalog)**:
    - جدول ومايجريشن جديد `nicu_milk_types` ([database/migrations/2026_11_08_102500_create_nicu_milk_types_table.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_11_08_102500_create_nicu_milk_types_table.php)) وموديل [app/Models/NicuMilkType.php](file:///c:/wamp64/www/hospital-system/app/Models/NicuMilkType.php) مع تصنيفات سريرية وترتيب عرض وملاحظات استخدام وحالة تفعيل.
    - متحكم [app/Http/Controllers/NicuMilkTypeController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/NicuMilkTypeController.php) يدعم عمليات CRUD وتبديل التفعيل اللحظي عبر AJAX (`/nicu/milk-types/{id}/toggle`) وجلب القائمة النشطة JSON.
    - واجهة إدارة موحدة بتبويبات في [resources/views/nicu/services/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/nicu/services/index.blade.php) تجمع بين `[ 🏷️ باقات وخدمات الحاضنات والتنفس ]` و `[ 🍼 قائمة وتصنيفات حليب وتغذية الخدج ]` مع بطاقات إحصائيات، جدول متجاوب، ومودالات الإضافة والتعديل.
    - ربط ديناميكي مباشر داخل إضبارة الخديج [resources/views/nicu/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/nicu/show.blade.php) في داتا ليست الرضاعة السريعة (`quickFeedingTypes`) وتوثيق العلامات الحيوية، مع رابط وصول سريع `[ ⚙️ إدارة أنواع الحليب ]` للمخولين.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/NicuGranularPermissionsAndMilkTypesTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NicuGranularPermissionsAndMilkTypesTest.php))**:
    - 5 اختبارات ميزة تغطي حظر الاستعلامات، تمكين أطباء وممرضات الخدج، عمليات أنواع الحليب والتبديل اللحظي، ورندرة الواجهات والتبويبات بنجاح 100% (`5 passed, 38 assertions`).

- **المرحلة 179 (2026-11-08) — قفل إضبارة الخديج عند الخروج للأرشفة والمطالعة فقط، أرشفة سجلات المرضى المخرجين، ونقل عمر الحمل ووزن الطفل لداخل الإضبارة NICU Discharged Baby Chart Lockdown, Medical Archive Segregation & Clinical Birth Profile Relocation**:
  * قفل سجل وإضبارة الطفل الخديج عند خروجه لوضع المطالعة والأرشفة فقط (`Read-Only Mode`) مع بنر تحذيري وحظر 15 تابعاً سريرياً في السيرفر.
  * عزل المرضى المخرجين في تبويب "السجلات وأرشفة المرضى" في القائمة وفي الشريط الجانبي.
  * نقل حقلي عمر الحمل ووزن الطفل من حجز الاستقبال لداخل الإضبارة ببطاقة مقفلة مع حوكمة تأكيد التعديل.
  * حزمة اختبارات شاملة في [tests/Feature/NicuDischargedChartAndClinicalProfileTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NicuDischargedChartAndClinicalProfileTest.php) بنجاح 100% (`5 passed, 37 assertions`).

- **المرحلة 174 (2026-11-07) — حظر فتح المحطة السريرية للمقيم قبل استلام المريض في الغرفة، وتطبيق نظام التنبيهات الطبي الشامل SweetAlert2 (Resident Clinical Station Gate & Global Hospital SweetAlert2 & Toastr Modern Styling System):**
  * **حوكمة محطة المقيم السريرية ([app/Http/Controllers/ResidentStationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/ResidentStationController.php), [resources/views/surgery-stations/resident/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/resident/index.blade.php))**:
    - قفل زر "عرض وتعديل" في محطة المقيم وتحويله إلى `[ 🔒 بانتظار استلام الغرفة ]` إذا كان المريض مخصصاً لغرفة ولم يسجل ممرض الطابق استلامه الفعلي بعد.
    - إضافة شارة صفراء أنيقة تحت اسم المريض: `[ ⏳ بانتظار استلام ممرض الطابق ]` تتحول تلقائياً إلى خضراء `[ ✔️ تم الاستلام (hh:mm A) ]`.
    - حظر الدخول المباشر بالرابط في `ResidentStationController@show` وإعادة التوجيه مع رسالة تحذيرية واضحة.
  * **محرك التنبيهات الطبي الشامل والتخلص من النوافذ السوداء ([resources/views/layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php))**:
    - دمج مكتبة SweetAlert2 الفاخرة مع تنسيق طبي مخصص: بطاقة بيضاء ناصعة، خط النظام الطبي، زوايا دائرية `rounded-4`، أيقونات طبية متحركة ونابضة، وأزرار كبسولية عصرية.
    - **Global Alert Interceptor**: اعتراض دالة `window.alert` في المتصفح تلقائياً وتحويل أي تنبيه في كامل شاشات المستشفى إلى SweetAlert2 ملون وأنيق بدلاً من نوافذ المتصفح السوداء الافتراضية.
    - تحديث تنسيقات Toastr لتتحول من الصناديق الداكنة القديمة إلى بطاقات زجاجية ملونة وناعمة بنمط الباستيل الطبي.
    - ربط رسائل الـ Session (النجاح والتحذيرات والخطأ) لتظهر تلقائياً كـ Toasts عائمة أو بطاقات تحذيرية فاخرة.
  * **حزم الاختبارات الآلية الشاملة ([tests/Feature/ResidentStationRoomCheckinGateTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ResidentStationRoomCheckinGateTest.php))**:
    - كتابة واجتياز اختبارات قفل المحطة للمقيم وحظر الرابط بنسبة 100% (2 passed, 11 assertions)، واستمرار اجتياز كافة اختبارات محطة التمريض والضمان (15 passed, 108 assertions).

- **المرحلة 173 (2026-11-07) — حوكمة دخول وتخريج المريض من غرف الطابق في محطة التمريض، ربط محرك الإقامة الفندقية بلحظة الاستلام الفعلي، وحجب ملف المريض قبل الاستلام (Floor Nursing Room Check-in / Checkout Governance, Bed Release, Stay Calculation Synchronization & Check-in Clinical Access Gate):**
  * **تصميم مسار حوكمة إشغال الغرف ومحطة التمريض ([app/Http/Controllers/NursingStationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/NursingStationController.php), [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
    - إضافة توابع `roomCheckin()` و `roomCheckout()` لتسجيل الدخول الفعلي للغرفة وتخريج المريض مع تدقيق أمني وحفظ هوية الممرض ووقت الإشغال وتحديث حالة الغرفة بين `occupied` و `available` وتسجيل الحركات في `UserActivityLogger`.
    - **بوابة حجب الوصول السريري (Check-in Gate)**: في `NursingStationController@show` و `ClinicalStationController@show`، منع الممرضين من فتح ملف المريض وتعديل بياناته السريرية إذا كانت الحالة مخصصة لغرفة ولم يتم تسجيل دخولها واستلامها بعد، مع توجيه تنبيهي فوري: "يرجى تسجيل دخول المريض للغرفة أولاً قبل الدخول إلى ملف المريض والتعديل السريري".
  * **تطوير واجهة محطة التمريض وتبديل الأزرار ([resources/views/surgery-stations/nursing/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/nursing/index.blade.php), [resources/views/surgery-stations/nursing/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/nursing/show.blade.php), [resources/views/surgery-stations/clinical/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
    - في تبويبي ما قبل وما بعد الصالة:
      - **قبل تسجيل الدخول**: إخفاء زر "عرض وتعديل" تماماً وعرض زر وحيد أخضر بارز `[ 🛏️ تسجيل دخول الغرفة ]` مع شارة `[ ⏳ بانتظار استلام الغرفة ]`.
      - **بعد تسجيل الدخول**: يختفي زر تسجيل الدخول ويتبدل مكانه إلى زر `[ 📋 عرض وتعديل ]` وبجانبه زر أحمر سريع `[ 🚪 تخريج ]` لإخلاء السرير مع شارة خضراء أنيقة `[ ✔️ تم الدخول (hh:mm A) ]`.
    - إضافة زر التخريج السريع ومعلومات الغرفة وتوقيت الدخول في الترويسة العلوية لملف التمريض والمحطة السريرية.
  * **قاعدة احتساب الإقامة الفندقية الصارمة وتصحيح الغرف العادية ([app/Models/Surgery.php](file:///c:/wamp64/www/hospital-system/app/Models/Surgery.php), [app/Models/HealthInsuranceStayTier.php](file:///c:/wamp64/www/hospital-system/app/Models/HealthInsuranceStayTier.php))**:
    - ربط وقت بدء احتساب الإقامة الفندقية في `calculateStayDetails()` بحقل `room_checkin_at` كأساس أولي بدلاً من تاريخ الجدولة.
    - تجميد الإقامة عند تاريخ التخريج `discharged_at` فور نقر زر التخريج.
    - قفل وتأكيد أن الغرفة العادية (`regular`) لا يترتب عليها أي فرق ترقية إطلاقاً (`upgrade_diff = 0`) حتى لو كان سعرها التجاري أعلى من سعر عقد الضمان.
    - قفل الليالي الإضافية للعمليات المجدولة التي لم تبدأ أو لم تسجل دخولها بعد لتكون 0 ليلة إضافية، مما يجعل حصة المريض للغرفة العادية في العملية #1 تساوي 0 د.ع تماماً ومطابقة للنظام.
  * **حزم الاختبارات الآلية الشاملة ([tests/Feature/NursingRoomCheckinWorkflowTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NursingRoomCheckinWorkflowTest.php))**:
    - كتابة واجتياز 5 اختبارات آلية بنسبة 100% (5 passed, 27 assertions) تغطي: زر تسجيل الدخول، الحجب قبل الدخول، التبديل إلى العرض والتخريج، التخريج الفعلي وإخلاء السرير، ومطابقة محرك احتساب الإقامة مع `room_checkin_at`.
    - اجتياز كافة اختبارات استقطاع الإقامة والخدمات الإضافية للعمليات بإجمالي 21 اختباراً ناجحاً بنسبة 100%.

- **المرحلة 172 (2026-11-07) — نظام استقطاع الإقامة الفندقية المتدرج للضمان الصحي، تسعير الغرف، وفرق الترقية الفعلي (VIP/VVIP) عن كل الليالي، وتفكيك الحسابات في الكاشير (Health Insurance Tiered Stay Deduction Engine, Dynamic Room Tariff Management, Ubiquitous Room Upgrade Surcharge & Cashier Live Nights Breakdown):**
  * **بنية قاعدة البيانات والشرائح الخمس المعتمدة ([database/migrations/2026_11_07_140000_create_health_insurance_stay_tiers_table.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_11_07_140000_create_health_insurance_stay_tiers_table.php), [app/Models/HealthInsuranceStayTier.php](file:///c:/wamp64/www/hospital-system/app/Models/HealthInsuranceStayTier.php))**:
    - إنشاء جدول وموديل `health_insurance_stay_tiers` مع الشرائح الخمس القياسية للضمان:
      1. الليلة الأولى (يوم 1): تحمل 0% (مشمولة بالعملية للغرفة العادية، 0 د.ع مريض و 0 د.ع ضمان).
      2. الليلة الثانية (يوم 2): تحمل 10% (20,000 د.ع مريض، و 180,000 د.ع ضمان).
      3. إقامة اعتيادية (أيام 3 إلى 8): تحمل 25% (50,000 د.ع مريض / يوم، و 150,000 د.ع ضمان / يوم).
      4. إقامة ممتدة (أيام 9 إلى 13 - 5 أيام): تحمل 50% (100,000 د.ع مريض / يوم، و 100,000 د.ع ضمان / يوم).
      5. إقامة طويلة (أيام 14 فأكثر): تحمل 75% (150,000 د.ع مريض / يوم، و 50,000 د.ع ضمان / يوم).
    - حفظ وإدارة السعر اليومي المعتمد للغرفة العادية لدى الضمان (`standard_room_daily_price = 200,000 د.ع`).
    - توفير توابع حسابية ذكية: `getStandardRoomPrice()`, `getTierForDay()`, `getCopayPercentageForDay()`, `calculateNightFigures()`.
  * **إدارة نسب الاستقطاع وأسعار الغرف بتبويب تفاعلي ([app/Http/Controllers/HealthInsuranceCategoryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/HealthInsuranceCategoryController.php), [resources/views/health-insurance-categories/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/health-insurance-categories/index.blade.php), [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
    - إضافة تبويب مستقل في واجهة نسب استقطاع الضمان الصحي (`/health-insurance-categories`):
      - تبويب 1: فئات العمليات والخدمات الطبية (أ - ط).
      - تبويب 2: شرائح استقطاع الإقامة والغرف للضمان، مع بطاقات مؤشرات أسعار الغرف الفعلية (عادية: 250k، VIP: 350k، VVIP: 450k) وفروقات الترقية اليومية.
    - إضافة نوافذ منبثقة (Modals) ومسارات لتعديل السعر المعتمد وتعديل نسب وأيام وملاحظات أي شريحة ديناميكياً.
  * **محرك حساب الليالي وفروقات الترقية الصارمة ([app/Models/Surgery.php](file:///c:/wamp64/www/hospital-system/app/Models/Surgery.php))**:
    - تطوير دالة `calculateStayDetails()` لتعتمد قاعدة 12:00 ظهراً ونظام الشرائح المتدرجة لمرضى الضمان:
    - **قاعدة فرق الترقية الفعلي عن كل الليالي**: الضمان يغطي حصراً تسعيرة الغرفة العادية (200,000 د.ع). أي ترقية لـ VIP (فرق 150,000 د.ع / يوم) أو VVIP (فرق 250,000 د.ع / يوم) يتحملها المريض بالكامل بنسبة 100% عن كل ليلة دون استثناء (بما فيها الليلة الأولى).
    - توليد مصفوفة تفصيلية `nights_breakdown` لكل ليلة مع تفكيك دقيق لحصة المريض ومطالبة الضمان وفرق الترقية.
  * **تطوير واجهة دفع العمليات ودعم تفكيك الليالي بالكاشير ([resources/views/cashier/surgeries/payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php), [app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php))**:
    - عرض شارة "استقطاع متدرج (ضمان)" في سطر الغرفة بجدول العمليات، وزر منسدل لعرض جدول تفكيك الليالي والاستقطاعات.
    - مزامنة حية وفورية في JavaScript لاحتساب حصة المريض ومطالبة الضمان للغرفة عند الدفع الكامل أو الجزئي.
    - معالجة وإصلاح حصر حالة الغرفة كمدفوعة في الكاشير `processSurgeryPayment` لتطابق حصة المريض الفعلية المطلوبة بدلاً من كامل سعر الغرفة التجاري.
  * **حزم الاختبارات الآلية الشاملة ([tests/Feature/HealthInsuranceStayTiersTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HealthInsuranceStayTiersTest.php))**:
    - كتابة واجتياز 8 اختبارات آلية بنسبة 100% (8 passed, 70 assertions) واختبارات الخدمات الإضافية السابقة (8 passed, 47 assertions) بإجمالي 16 اختباراً و117 توكيداً ناجحاً.

- **المرحلة 171 (2026-11-07) — توحيد جدول دفع العمليات الجراحية مطابقةً لواجهة الطلبات الطبية وكبسولات الشمول المنفصلة السداسية الأعمدة (Surgery Cashier Payment Table Modern Redesign & Segmented Action Pills Parity):**
  * **الإلحاق التلقائي مع حجز وتحديث العمليات ([app/Models/Surgery.php](file:///c:/wamp64/www/hospital-system/app/Models/Surgery.php), [app/Http/Controllers/SurgeryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgeryController.php))**:
    - إضافة دالة `attachDefaultExtraServices()` على موديل `Surgery` لربط 3 خدمات إضافية افتراضية تلقائياً لكل عملية جديدة (كاش أو ضمان):
      1. **تخطيط قلب**: 15,000 د.ع (تصنيف طبي `medical`).
      2. **اجور فتح طبلة**: 10,000 د.ع (تصنيف إداري `administrative`).
      3. **خدمات فندقية**: 30,000 د.ع (تصنيف إقامة `accommodation`).
    - منع تكرار الخدمات عند استدعاء الدالة مرات متكررة، وربطها فورياً بالعملية الحالية #4 (منير سمير يوسف).
  * **تعديل السعر المباشر للخدمات الإضافية ([app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php), [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
    - إضافة مسار وتابع `CashierController::updateSurgeryExtraService` عبر `PATCH /surgeries/{surgery}/extra-services/{extraService}` لتعديل سعر وكمية الخدمة بالدينار العراقي فورياً عبر AJAX.
    - دعم قراءة مصفوفة `extra_service_prices` في معالجة دفع العملية `processSurgeryPayment` لحفظ وتحديث السعر المخصص الذي يكتبه الكاشير في قاعدة البيانات مباشرة واحتساب الحصص والوصولات بدقة.
  * **تطوير واجهة دفع العمليات في الكاشير ([resources/views/cashier/surgeries/payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
    - **إلغاء زر الشمول الكلي ("الغاء زر شمول الكلي")**: إزالة كارت ومفتاح `#inclusive` العام لتمكين التحكم الفردي النقي والمستقل لكل خدمة دون أي تعارض.
    - **حقل السعر القابل للتعديل**: تزويد كل خدمة إضافية بحقل إدخال سعر مخصص `extra-service-price-input` بزيادات دينار ومحاذاة ممتازة، يعيد حساب حصة المريض ومطالبة الضمان والإجمالي لحظياً بمجرد الكتابة.
    - **زر الشمول الفردي لكل خدمة**: تفعيل مفتاح الشمول `package-inclusion-switch` المستقل لكل خدمة مع شارات واضحة `[ ✔️ مشمول بالعملية (0 د.ع) ]` أو `[ ✕ غير مشمول (منفصل) ]`.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/SurgeryExtraServicesTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryExtraServicesTest.php))**:
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
    - إنشاء ملف بيانات رسمي ثابت [database/seeders/approved_emergency_services_data.php](file:///c:/wamp64/www/hospital-system/database/seeders/approved_emergency_services_data.php) ومايجريشن آلي قابل للنشر على سيرفر هوستنجر بنقرة واحدة [database/migrations/2026_10_20_110000_sync_emergency_services_from_approved_excel.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_20_110000_sync_emergency_services_from_approved_excel.php).
  * **تصحيح الأسعار الصفرية وحالات الضمان والكاش**:
    - تصحيح الخدمات التي كانت مسجلة بـ 0 د.ع في الداتابيز (مثل: إدخال أنبوب صدر Chest tube بـ 500,000 كاش وضمان، أجور التجبيس للأطراف السفلى بـ 50,000 كاش و 40,000 ضمان، خدمات نقل الإسعاف الداخلي 25,000 والخارجي 50,000، وإعطاء الدم تحت إشراف طبيب مع المطابقة بـ 75,000).
    - تحديث أسعار الكاش وأسعار الضمان الصحي وحالات التغطية لـ 20 خدمة مختلفة.
  * **إدراج الخدمات المعتمدة الجديدة**:
    - إضافة خدمة `#114`: `أجور التجبير مع كافة المستلزمات بنوعيه (fiberglass or plaster of paris) نصفي او كامل - الاطراف العليا` (كاش: 50,000 د.ع، ضمان: 30,000 د.ع، مشمول بالضمان).
    - ضبط خدمة `#66`: `زرق إبرة خارجي` بسعر 25,000 د.ع.
  * **التعطيل الآمن للخدمات القديمة والمكررة (15 خدمة)**:
    - إيقاف تفعيل (`is_active = 0`, `is_hi_active = 0`) للخدمات الـ 15 غير الموجودة بملف الإكسل المعتمد (مثل تكرارات صوندة المعدة، والخدمات غير المعرفة، وتكرارات الغرز)، مما يمنع ظهورها نهائياً في شاشات الاستقبال ومحطة كشف الطوارئ مع صيانة السجلات والفواتير التاريخية المرتبطة دون كسر أي مفاتيح أجنبية.
  * **تحديث السيدر الرسمي والاختبارات الآلية الحصينة**:
    - ترقية [database/seeders/EmergencyServicesSeeder.php](file:///c:/wamp64/www/hospital-system/database/seeders/EmergencyServicesSeeder.php) ليعتمد على مصفوفة البيانات الرسمية.
    - بناء واجتياز اختبار الميزة [tests/Feature/EmergencyServicesApprovedSyncTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyServicesApprovedSyncTest.php) واجتياز كامل حزمة اختبارات الطوارئ: `33 passed (171 assertions)`.
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
    - بناء واجتياز اختبار الميزة [tests/Feature/DoctorLabQuickPackagesTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DoctorLabQuickPackagesTest.php) بنجاح كامل (`2 passed, 9 assertions`) وتأكيد استقرار حزمة تشخيصات وزيارات الأطباء (`7 passed, 28 assertions`).
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
  * **الاختبارات الآلية الحصينة ([tests/Feature/LabAndCashierDeduplicationAndActiveOnlyTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabAndCashierDeduplicationAndActiveOnlyTest.php))**:
    - إنشاء واجتياز حزمة اختبارات ميزة تغطي: ربط الفحص المفعل فقط عند طلب الطبيب، منع ظهور الفحص المعطل ودمج التكرار في شاشة المختبر، واستبعاد المعطل ومنع التكرار في الكاشير والوصل الحراري (`3 passed, 13 assertions`).
- **المرحلة 94 (2026-10-21):** تصحيح أسعار الخدمات الفردية في الوصل الحراري 80mm وإلغاء تكرار المجموع الكلي وعرض المبلغ المطلوب من المريض فقط (Cashier 80mm Thermal Receipt Line-Item Patient Fee Fix & Grand Total Duplication Removal):
  * **معالجة تكرار المجموع الإجمالي أمام كل خدمة (`resources/views/cashier/receipt-print.blade.php`)**:
    - إلغاء الشرط القديم `empty($payment->patient_share) ? $item['price'] : $payment->amount` الذي كان يطبع المجموع الكلي للوصل مكرراً في عمود المبلغ أمام كل خدمة عند وجود حصة مريض (الضمان الصحي).
    - طباعة السعر الفعلي لكل خدمة في عمود المبلغ `$item['price']` والمطابق لما يدفعه المريض لكل فحص في كاشير النظام.
  * **حساب حصة المريض الدقيقة لكافة الخدمات الفردية في الوصل**:
    - تحاليل المختبر وفحوصات الأشعة الإضافية والمنفردة: احتساب `calculateInsurancePricing()['patient_share']` بدلاً من السعر المعتمد الكامل.
    - باقات المختبر واستشارات الطوارئ وخدمات الطوارئ: تطبيق نسبة التحمل بدقة متناهية (`copay_percentage`).
    - النتيجة: مجموع أسطر الخدمات في جدول الوصل يطابق تماماً "المبلغ المدفوع" في قاع الوصل بدون أي زيادة أو نقص.
  * **الاختبارات الآلية الحصينة ([tests/Feature/CashierPrintReceiptItemPriceTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/CashierPrintReceiptItemPriceTest.php))**:
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
    - بناء واجتياز اختبار الميزة الجديد [tests/Feature/DoctorIcd10SearchTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DoctorIcd10SearchTest.php) بنجاح كامل (`6 passed, 24 assertions`).
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
  * **معالجة تضارب مفاتيح إدخال الباراميترات الفرعية ([resources/views/lab/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/show.blade.php) & [resources/views/staff/requests/show-lab.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/show-lab.blade.php))**:
    - تحويل مفاتيح الإدخال للباراميترات الفرعية من اسم الفحص المشترك إلى مفتاح فريد ومميز بمعرف الباراميتر: `test_results[sub_{{ $subTest->id }}][value]`.
    - حل مشكلة مسح الفحوصات التي تشترك في نفس أسماء المعايير الفرعية (مثل اشتراك FSH و LH في معيار `Men` و `Follicular Phase` الخ) نهائياً ومنع استبدال القيم أثناء الإرسال (`$_POST`).
    - ترقية استرجاع القيم المحفوظة في الشاشة عبر الفهرسة المتعددة (`$dbResultsBySubId` و `$dbResultsByParentAndName`) لضمان ظهور القيم المحفوظة لجميع التحاليل بدقة متناهية.
  * **إفراد فحص الإدرار العام (General Urine Examination - G.U.E) في صفحة طباعية مستقلة ([resources/views/staff/requests/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/print.blade.php))**:
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
  * **الاختبارات الآلية الحصينة ([tests/Feature/LabGeneralUrinePageBreakAndCollisionFixTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabGeneralUrinePageBreakAndCollisionFixTest.php))**:
    - بناء واجتياز اختبارات ميزة متكاملة تؤكد منع التضارب بين FSH و LH، وتؤكد عزل G.U.E بفاصل الصفحة وظهور تقرير CBC المرفق وكافة الفحوصات بنجاح (`2 passed, 17 assertions`)، مع اجتياز كامل حزمة فحوصات المختبر (`65 passed, 324 assertions`).

- **المرحلة 97:** مطابقة آلية طلبات طبيب الطوارئ مع الاستشارية: الدمج التلقائي، الحذف الفردي، شريط التقدم السريري، وأرشيف الطلبات الملغاة (Emergency Doctor Request Auto-Merge, Item-Level Removal, Clinical Stepper & Cancelled Archive):
  * **الدمج التلقائي لطلبات المختبر والأشعة للطوارئ ([app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php))**:
    - عند قيام طبيب الطوارئ بطلب تحاليل مختبرية جديدة لحالة طوارئ لديها طلب معلق غير مسدد (`payment_id === null` و `status === 'pending'`)، يتم دمج الفحوصات الجديدة تلقائياً في نفس الطلب عبر `syncWithoutDetaching` بدلاً من تشتيت الكاشير بطلبات متعددة.
    - نفس الدمج التلقائي الذكي يطبق على طلبات الأشعة والسونار غير المسددة.
    - تحديث وحساب المبالغ المستحقة للكاشير تلقائياً بعد كل دمج عبر `upsertUnifiedEmergencyPayment`.
  * **تمكين طبيب الطوارئ من حذف وتعديل الفحوصات الفردية ([app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php) & [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
    - إضافة مسار ودالة الحذف الفردي `DELETE /emergency/{emergency}/remove-request-item`.
    - قفل وحظر الحذف التام إذا كان الطلب مسدداً لدى الكاشير (`payment_id !== null` أو حالة الطلب مسدد) لحماية الصندوق والحسابات برمز `422 Unprocessable Entity`.
    - فك ارتباط الفحص المطلوب فقط من جدول الربط الوسيط (`detach`).
    - في حال حذف الفحص الأخير المتبقي في الطلب، يتم تحويل حالة الطلب تلقائياً إلى ملغي (`cancelled`) وتوثيق سبب الإلغاء، مع تحديث استحقاق الكاشير.
  * **ترقية شاشة الطوارئ بواجهة الاستشارية الحديثة ([resources/views/emergency/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/show.blade.php))**:
    - **شريط مسار ومراحل الفحص التفاعلي (Status Progress Stepper)**: شريط مصغر بأعلى كل بطاقة يوضح مراحل الإنجاز: `[ 1. طلب الطبيب ⬅️ 2. عند الكاشير / سُدّد ⬅️ 3. قيد الفحص بالمختبر/الأشعة ⬅️ 4. النتيجة جاهزة ]` مع شارات وألوان تفاعلية.
    - **الجدول السريري المصغر (Clinical Mini-Table)**: استبدال طريقة العرض القديمة بجدول سطر منظم وأنيق يعرض: رقم التسلسل، اسم الفحص، شارة الحالة الحية، وزر الحذف `[ × ]` التفاعلي للأطباء قبل السداد عبر AJAX سلس دون إعادة تحميل الصفحة.
    - **أرشيف الطلبات الملغاة**: عزل وإخفاء الطلبات الملغاة من القائمة النشطة ووضعها داخل أكورديون مطوي أنيق: `[ 📁 أرشيف طلبات المختبر/الأشعة الملغاة (مخفية) ]`.
    - تصفية اللوحة الجانبية لمريض الطوارئ (`side-cur-pane`) لإخفاء الطلبات الملغاة من قائمة الإجراءات النشطة.
  * **الاختبارات الآلية الحصينة ([tests/Feature/EmergencyRequestItemModificationAndAutoMergeTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyRequestItemModificationAndAutoMergeTest.php))**:
    - بناء واجتياز 7 اختبارات ميزة متكاملة تغطي الدمج التلقائي للمختبر والأشعة، الحذف الفردي، تحول الطلب لملغي عند إفراغه، حظر الحذف بعد السداد، وظهور الـ Stepper والجدول المصغر وأرشيف الملغاة (`7 passed, 38 assertions`)، مع اجتياز كامل حزمة الطوارئ بنجاح (`40 passed, 209 assertions`).

- **المرحلة 101:** نقل مسار تحميل مرفقات المختبر للمسارات العامة المحمية برمز التوكن لمنع مطالبة المريض بتسجيل الدخول (Lab Attachment Route Token-Protected Public Access Fix):
  * **نقل مسار تحميل المرفقات للمسارات العامة المحمية ([routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
    - نقل المسار `Route::get('/lab/requests/{request}/attachment')` خارج مجموعة ميدلوير تسجيل الدخول `Route::middleware(['auth'])` ووضعه مباشرة بجانب مسار التقرير العام `lab.public-report`.
    - حل مشكلة مطالبة المريض بتسجيل الدخول أو إعادة توجيهه لصفحة `/login` عند النقر على زر تحميل تقرير الجهاز (PDF).
    - حماية المسار بكنترولر المختبر [app/Http/Controllers/LabStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabStaffController.php) عبر الرمز المشفر (`?token=...`) بحيث يفتح للمريض بنقرة واحدة، ويُحظر أي وصول غير مصرح به بدون التوكن الصحيح برمز `403 Forbidden`.
  * **حزمة الاختبارات الآلية الحصينة ([tests/Feature/LabWhatsAppShareAndPublicReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabWhatsAppShareAndPublicReportTest.php))**:
    - بناء وتأكيد اختباري ميزة لتنزيل المريض للمرفق بدون تسجيل دخول وحظر الدخول بدون رمز، مع اجتياز كامل لجميع اختبارات المختبر وحزمة النظام (`68 passed, 329 assertions`).

- **المرحلة 109:** حماية كشفية نفس اليوم من التكرار والازدواجية، وأتمتة مراجعة النتائج المجانية، ودمج فحوصات اليوم في محطة الطبيب، وتنظيف قيود هوستنجر المكررة (Same-Day Duplicate Consultation Prevention, Automatic Free Results Recheck, Workstation Requests Aggregation & Hostinger Duplicate Cleanup):
  * **أتمتة مراجعة النتائج ومنع الحجز المكرر لنفس اليوم ([app/Http/Controllers/InquiryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/InquiryController.php) & [app/Http/Controllers/AppointmentController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/AppointmentController.php))**:
    - فحص تلقائي لحظي: إذا دخل مريض ولديه كشفية سابقة في نفس اليوم لدى نفس الطبيب، يتم تحويل الحجز تلقائياً إلى "مراجعة نتائج مجانية" برسم `consultation_fee = 0` و `is_free_recheck = true` وربطه بالزيارة الأصلية دون استيفاء أي مبالغ إضافية من المريض.
    - حماية الكاشير والصندوق من ازدواجية وصولات الكشفية وضمان تطابق أعداد المراجعين الفعليين.
  * **دمج فحوصات وزيارات نفس اليوم في محطة الطبيب ([app/Http/Controllers/DoctorVisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorVisitController.php))**:
    - تجميع ودمج كافة طلبات وفحوصات المريض الطبية لنفس تاريخ اليوم تلقائياً، بحيث تظهر كافة التحاليل والأشعة أمام الطبيب في لوحة العمل حتى لو كان هناك أكثر من زيارة.
    - رفع حد الذاكرة `ini_set('memory_limit', '512M')` لحل استنفاد الذاكرة وخطأ 500 نهائياً عند فتح الزيارات التي تحتوي على آلاف الأدوية ورموز ICD-10.
  * **تحديث واجهة الطبيب بزر تعليق الفحوصات وتنبيه الإنهاء المبكر ([resources/views/doctors/visits/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/show.blade.php))**:
    - إضافة زر صريح: `[ ⏳ تعليق بانتظار الفحوصات ]` يسمح للطبيب بالرجوع للعيادة واستدعاء المريض التالي مع إبقاء الزيارة مفتوحة ريثما تصدر النتائج.
    - تنبيه سريري ذكي عند النقر على "إنهاء الزيارة" بوجود فحوصات مخبرية أو شعاعية معلقة لتنبيه الطبيب بعدم إقفال الزيارة مبكراً.
  * **تنظيف السجلات المكررة على قاعدة بيانات هوستنجر والمحلي**:
    - معالجة الحالات الـ 4 المكررة ليوم 2026-10-22 (سلمى راضي، والمراجعين الثلاثة الآخرين) بتحويل الزيارات المفرغة إلى `cancelled` وتصفير الحجوزات المكررة كمراجعات مجانية ونقل الوصفات الطبية للزيارات الأصلية، ليصبح عدد المراجعين الفعليين مطابقاً للواقع المالي السليم (2,280,000 د.ع بدون مضاعفة).
  * **حزمة الاختبارات الآلية الحصينة ([tests/Feature/SameDayRecheckBookingTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SameDayRecheckBookingTest.php))**:
    - إضافة اختبارات متكاملة تؤكد تحويل حجز نفس اليوم لمراجعة مجانية برسم 0 د.ع، ودمج كافة فحوصات نفس اليوم داخل محطة الطبيب بنجاح كامل (`2 passed, 10 assertions`).

- **المرحلة 100:** توحيد إطار تقرير المختبر الكحلي الشامل وإلغاء الخطوط المطلقة ودمج أزرار تحميل مرفقات الأجهزة بالشريط العائم (Lab Report Unified Full Container Border & Toolbar Attachment Downloads):
  * **تصحيح هيدر تقرير المختبر وتوحيد الإطار الخارجي الكامل ([resources/views/staff/requests/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/print.blade.php))**:
    - إعادة تفعيل تنسيق `display: flex !important; direction: rtl !important; justify-content: space-between !important; align-items: center !important;` للهيدر الرئيسي `.report-header` وهيدر صفحة الإدرار `.page2-header` في وضع التقرير العام للمريض (`body.is-public-report`).
    - إلغاء الخطوط العائمة المشوهة القديمة (`.decorative-border` المطلقة التي كانت تقطع منتصف الهيدر في الـ PDF) واستبدالها بإطار رسمي موحد ومستمر يحيط بكامل الصفحة: `.report-page { border: 1.5px solid #005a9c; border-radius: 4px; padding: 6mm 10mm; }` للشاشة وللطباعة الورقية، مع إخفائه التلقائي عند الطباعة على ورق المستشفى المروس (`letterhead-mode`).
    - ضبط التباعد العلوي للتقرير العام `body.is-public-report { padding-top: 65px !important; }` مع تصفيره عند الطباعة الورقية (`padding: 0 !important;`).
  * **دمج أزرار تحميل مرفقات الأجهزة الطبية مباشرة في الشريط العائم**:
    - إضافة أزرار أنيقة ومميزة داخل شريط الإجراءات العلوي العائم (`.print-toolbar`) للمريض والكادر الطبي لتحميل تقارير الأجهزة الأصلية (مثل CBC و ABG بصيغة PDF) بنقرة زر واحدة.
    - تختفي هذه الأزرار تلقائياً عند حفظ الـ PDF أو الطباعة الورقية بفضل خاصية `@media print { .print-toolbar { display: none !important; } }`، فيبقى التقرير الطبي المطبوع رسمياً وفخماً دون أي تشويه.
  * **حزمة الاختبارات الآلية الحصينة ([tests/Feature/LabWhatsAppShareAndPublicReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabWhatsAppShareAndPublicReportTest.php))**:
    - إضافة وتأكيد اختبارات الميزة لظهور أزرار تحميل المرفقات في شريط الأدوات واختفاء عناصر الإطار المشوهة القديمة مع اجتياز كامل لجميع اختبارات المختبر وحزمة النظام (`66 passed, 327 assertions`).

- **المرحلة 99:** عزل صفحات تقرير المختبر وتثبيت الفوتر لكل صفحة، إلغاء السطر التقديري لتقارير الأجهزة وحفظ النتائج السابقة وهيدر الفحوصات (Lab Report Isolated Page Containers, Dedicated Footers, Removal of Placeholder Device Rows, and 100% Previous Results & Header Fidelity):
  * **عزل الصفحات الطباعية بحاويات مستقلة ([resources/views/staff/requests/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/print.blade.php))**:
    - استبدال جدول `report-print-container` بحاويات صفحات مستقلة كلياً `.report-page.page-1` و `.report-page.page-2.gue-page-break` تعمل بنظام CSS Flexbox مع تثبيت الفوتر أسفل كل صفحة على حدة عبر `.report-bottom-block`.
    - حل مشكلة الصفحة الأولى بدون فوتر نهائياً؛ تظهر بيانات الطباعة والتاريخ وعنوان المستشفى أسفل الصفحة 1 وأسفل الصفحة 2 بدقة متناهية.
    - ضبط ارتفاع حاوية الطباعة إلى `min-height: 270mm !important;` مع `page-break-after: always !important;` للصفحة الأولى و `page-break-after: auto !important;` لآخر صفحة لمنع توليد أي صفحة ثالثة بيضاء أو تجاوزات نهائياً.
  * **إلغاء السطر التقديري لمرفقات الأجهزة في جدول التحاليل**:
    - حذف إدراج أسطر تقديرية للفحوصات المرفقة كـ CBC داخل جدول الكيمياء الحيوية (إلغاء سطر `📎 تقرير الجهاز مرفق` و `Automated Hematology Analyzer / Clinical Device`) بناءً على توجيهات الطبيب، والاعتماد الكامل على بطاقة وأزرار تحميل تقارير الأجهزة الأصلية (PDF) في شريط المرفقات ورسائل الواتساب.
  * **ترويسة مدمجة وخفيفة لصفحة فحص الإدرار العام (G.U.E)**:
    - تزويد الصفحة الثانية بترويسة مستشفى مصغرة وشريط مختصر لمعلومات المريض (الاسم، العمر، الجنس، رقم العينة، والتاريخ) مع جدول النتائج الـ 19 المضغوط المكون من 4 أعمدة، بحيث تتسع كافة الفحوصات وتوقيع وخاتم المختبر والفوتر المعتمد بأريحية تامة دون أي تزاحم.
  * **الحفاظ بنسبة 100% على النتائج السابقة وهيدر كل تحليل**:
    - الحفاظ الكامل على دالة استرجاع السجلات التاريخية للمريض `$findPrevResults` وعرض مربع النتائج السابقة الرأسي والرقائق الملونة وتواريخ الفحوصات السابقة أسفل القيمة الحالية لكل تحليل.
    - الحفاظ على بانر العنوان المستقل لكل فحص (`category-header-row`) لمنع أي تداخل بين التحاليل.
  * **حزمة الاختبارات الآلية الحصينة**:
    - اجتياز كامل لجميع اختبارات المختبر وحزمة النظام (`65 passed, 324 assertions`).

- **المرحلة 98:** حل تضارب الباراميترات الفرعية للمختبر وعزل فحص الإدرار العام (G.U.E) في صفحة طباعية مستقلة ومطابقة كافة الفحوصات والمرفقات 100% (Lab Subtest Collision Fix, Dedicated GUE Page Break, Clinical Grouping & 100% Ordered Tests Coverage):
  * **الالتزام بأنماط إدخال النتائج المعتمدة في شاشة الإعدادات ([resources/views/lab/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/show.blade.php))**:
    - حذف كلمة `(اختياري)` من ترويسة الجدول لتصبح `تقرير الجهاز / المرفق`، وإلغاء كلمة `(اختياري)` من أزرار الرفع.
    - إلغاء ظهور زر `إرفاق تقرير` للفحوصات المحددة كـ `single_value` (قيمة مباشرة مفردة) أو `sub_tests` (قيم فرعية) وإظهار شرطة `—` بدلاً منها لمنع تشويه الجدول.
    - قصر إظهار زر رفع المرفق حصراً على الفحوصات المحددة في الإعدادات كـ `attachment_only` (مرفق تقرير جهاز) أو `flexible` (مرن) أو الفحوصات التي رُفع لها مرفق مسبقاً.
  * **تمكين المريض من استعراض وتحميل مرفقات الأجهزة عبر الواتساب والتقرير الطبي ([resources/views/staff/requests/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/print.blade.php) & [app/Http/Controllers/LabStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabStaffController.php))**:
    - إضافة بطاقة أنيقة في التقرير الطبي المعتمد تظهر فيها أزرار تحميل وعرض تقارير الأجهزة والملفات المرفقة (العامة والخاصة بكل فحص).
    - تمكين المريض من فتح وتحميل المرفقات بصورة آمنة ومباشرة عبر التوكن المشفر `?token=...` دون الحاجة لتسجيل دخول، وتخطي حظر 403.
    - تضمين تنبيه ذكي في نص رسالة الواتساب ينبه المريض بوجود تقارير أجهزة مرفقة يمكنه استعراضها وتحميلها بنقرة زر واحدة ([app/Models/Request.php](file:///c:/wamp64/www/hospital-system/app/Models/Request.php)).
  * **الاختبارات الآلية الحصينة**:
    - اجتياز كامل لجميع اختبارات حزمة المختبر والواتساب وأنماط الإدخال (`16 passed, 72 assertions`).

- **المرحلة 93:** فصل حفظ مسودة نتائج المختبر عن الاعتماد النهائي والإرسال للطبيب (Lab Draft Saving vs Final Doctor Transmission):
  * **فصل الحفظ كمسودة عن الاعتماد النهائي ([app/Http/Controllers/LabStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabStaffController.php))**:
    - عند اختيار `action === 'draft'` أو `status === 'in_progress'`: حفظ كافة النتائج الرقمية والمدخلات ومرفقات الأجهزة والملاحظات الطبية في قاعدة البيانات وجدول `lab_results`، مع تثبيت حالة الطلب على `in_progress` (مسودة قيد الفحص بالمختبر)، وعدم إرسال أي إشعار تيليجرام، وتوثيق النشاط كمسودة (`save_lab_draft`)، وإبقاء فني المختبر في نفس الصفحة لمتابعة العمل لاحقاً مع رسالة تأكيد خضراء أنيقة.
    - عند اختيار `action === 'complete'`: اعتماد النتائج نهائياً، تحويل حالة الطلب إلى `completed`، إرسال إشعار التيليجرام فورياً، وتوثيق النشاط (`save_lab_results`)، والتحويل إلى القائمة الرئيسية للمختبر.
  * **شريط الإجراءات المزدوج وشارات الحالة في واجهة المختبر ([resources/views/lab/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/show.blade.php))**:
    - توفير زرين بارزين في الشريط العلوي وكذلك في شريط الإجراءات السفلي أسفل جدول النتائج:
      1. `[ 💾 حفظ مؤقت (مسودة) ]`: بلون تحذيري دافئ ومميز لحفظ المدخلات دون إرسالها للطبيب.
      2. `[ 🚀 اعتماد نهائي وإرسال للطبيب ]`: بلون أخضر رسمي للاعتماد والإرسال المباشر.
    - إظهار شارة ذكية لحالة المسودة `[ ✏️ مسودة قيد الفحص ]` أعلى الصفحة، وبطاقة تنبيه سفلية توضح أن الطلب محفوظ كمسودة قيد الفحص بالمختبر ولم يُرسل للطبيب بعد.
  * **الحماية الكاملة لواجهة الطبيب والبيانات (Zero Doctor Disruption & Zero Data Loss)**:
    - عدم إجراء أي تغيير على شاشة كشف الطبيب؛ تظل تعرض مرحلة `[ 🔬 تنفيذ الفحص بالمختبر ]` طالما أن الطلب مسودة `in_progress`، ولا تنتقل إلى `[ ✅ النتيجة جاهزة ]` إلا بعد الاعتماد النهائي.
  * **الاختبارات الآلية الحصينة ([tests/Feature/LabDraftResultSaveTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabDraftResultSaveTest.php))**:
    - بناء واجتياز 3 اختبارات ميزة متكاملة تؤكد حفظ المسودة وبقاء الحالة `in_progress`، والاعتماد النهائي والتحويل لـ `completed`، وظهور الأزرار الثنائية في شاشة المختبر (`3 passed, 20 assertions`).

- **المرحلة 102 (2026-10-22) — التدقيق المالي الشامل لخزينة المستشفى، الشريط التنفيذي ذو الـ 6 كروت، مطالبات الضمان ومستحقات الأطباء المعلقة، وتفكيك وسائط الأشعة والتصوير**:
  * **شريط الكروت التنفيذي السداسي الشامل المتجاوب (6 Executive Cards Strip)**:
    - كشف أسباب اللبس الرقمي الذي لاحظه المستخدم: كارت الرصيد التراكمي (كل الوقت) مقابل كروت الفترة الزمنية المحددة، وعزل نقد الدرج عن مطالبات الضمان والمستحقات المعلقة.
    - توسيع الشريط العلوي من 5 إلى 6 كروت متوازنة ومتجاوبة بنظام 6 أعمدة متساوية على الشاشات الكبيرة (`col-xl-2`):
      1. `الرصيد الفعلي بالخزينة`: مع وسم بارز يوضح أنه تراكمي شامل لكل الفترات وتفصيل الوارد والصادر التراكمي.
      2. `إجمالي المقبوض نقداً (الفترة)`: نقد صندوق الخزينة والكاشير الفعلي المحصل.
      3. `مطالبات الضمان الصحي (الفترة)` (كارت تنفيذي جديد): يعرض قيمة الذمم المدينة المؤجلة قيد التحصيل من هيئات وشركات الضمان الصحي مع عدد المطالبات، مما أظهر أكثر من 14.6 مليون دينار كانت غائبة عن مؤشرات الخزينة.
      4. `المبالغ المسترجعة للمرضى`: تتبع دقيق لكافة حركات الاسترجاع الناتجة عن إلغاء كشفيات أو خدمات.
      5. `المصروفات المنفذة`: نفقات التشغيل والمشتريات المنفذة، مع سطر فرعي بارز يرصد التزامات مستحقات الأطباء المعلقة غير المنصرفة (`DoctorDue::where('status', 'pending')`).
      6. `صافي سيولة الفترة`: الفائض النقدي الفعلي لحركة الفترة (الوارد النقدي - المنصرف).
  * **تفكيك وسائط قسم الأشعة والتصوير إلى خدمات تخصصية مستقلة**:
    - تفكيك بند الأشعة الموحد القديم وإفراد كل وسيطة تشخيصية ببطاقة وإحصائية ولون بياني مخصص:
      * السونار (Ultrasound)
      * الأشعة السينية (X-Ray)
      * الرنين المغناطيسي (MRI)
      * المفراس الحلزوني (CT Scan)
      * إيكو القلب (Echo)
    - تنظيم قائمة تصفية البنود والتصنيفات في شريط الفلاتر إلى مجموعتين واضحتين: `[الأقسام والخدمات الطبية]` و `[المصروفات والمسترجعات]`.
  * **تطوير جدول إيرادات ومصروفات الأقسام (التبويب 2) وحل عجز الاستشارية الظاهري**:
    - إضافة أعمدة صريحة تفصل: `[المقبوض نقداً (Cash)]` و `[مطالبات الضمان (Insurance)]` و `[إجمالي الاستحقاق (Accrual Gross)]` بجانب `[حصص الأطباء / التكاليف]` و `[صافي المستشفى (Net)]`.
    - حل مشكلة العجز الظاهري لعيادات الاستشارية بإظهار المقبوض نقداً مع مطالبات الضمان لتغطية حصص الأطباء وعكس صافي أرباح المستشفى الحقيقية بدقة تامة.
  * **تحديث كروت ذكاء الأعمال للأطباء (التبويب 4 - Doctor BI)**:
    - تعديل الكارت الثاني ليعرض `إجمالي الدخل الاستحقاقي للأطباء` كرقم رئيسي، مع أسطر فرعية شفافة توضح صافي المستشفى وحصص الأطباء ونقد الدرج ومطالبات الضمان.
- **المرحلة 103 (2026-10-22) — تدشين واجهة وسجلات مطالبات الضمان الصحي الشاملة: فئات المرضى، تفاصيل الخدمات، أسعارها، حصص المرضى والضمان، اسم الطبيب، وتصدير Excel (Health Insurance Claims Interface, Patient Categories, Service Details, Pricing, Doctor Names & Excel Export)**:
  * **بناء واجهة كشوفات ومطالبات الضمان الصحي المخصصة ([resources/views/insurance/claims/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/insurance/claims/index.blade.php))**:
    - واجهة تنفيذية متكاملة وفخمة تتيح تتبع ومراجعة كافة الخدمات المقدمة للمرضى المشمولين بالضمان الصحي الوطني وقوى الأمن والتعاقدات.
    - عرض اسم المريض، فئة الضمان (`الفئة A`, `الفئة H`, `الفئة D`...) مع شارات ملونة لكل فئة، رقم باج/بطاقة الضمان، نوع الخدمة/القسم، اسم الطبيب المعالج/الاستشاري، تفاصيل الفحوصات الطبية الدقيقة، السعر الكلي، المبلغ المدفوع نقداً، ومطالبة الضمان، مع نسبة التغطية.
    - شريط كروت مؤشرات تنفيذية خماسي (إجمالي مطالبات الضمان، تحمل المرضى نقداً، إجمالي القيمة الاستحقاقية، عدد الخدمات المسددة، والمرضى المستفيدين).
    - شريط تصفية سريع حسب فئات الضمان يوضح إحصائيات كل فئة ومطالباتها مع الفلترة بنقرة زر واحدة.
    - فلاتر متقدمة تشمل: الفترات الزمنية السريعة (اليوم، أمس، هذا الأسبوع، هذا الشهر، الشهر السابق، هذا العام، كل الأوقات)، نطاق التاريخ المخصص، فئة الضمان، القسم، الطبيب، والبحث السريع.
  * **الكنترولر ومنطق احتساب الاستحقاقات ([app/Http/Controllers/HealthInsuranceClaimController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/HealthInsuranceClaimController.php))**:
    - استعلام دقيق يغطي كافة الدفعات المسددة التي تشمل حصة ضمان أو تصنيف تأميني مع معالجة حصرية لأسماء الجداول (`payments.`) لمنع أي تضارب بين الحقول.
    - احتساب المؤشرات اللحظية والإحصائيات التجميعية استحقاقياً وتوزيع الفئات، مع ترقيم الصفحات (`paginate(25)`).
  * **تصدير إكسيل رسمي ومطابق لمعايير هيئات الضمان ([app/Exports/HealthInsuranceClaimsExport.php](file:///c:/wamp64/www/hospital-system/app/Exports/HealthInsuranceClaimsExport.php))**:
    - توليد شيت إكسيل منسق ومبوب بـ 16 عموداً شاملاً كافة بيانات المطالبة، المريض، فئته، طبيبه، وتفاصيل الخدمة ومبالغها.
  * **توسيع نموذج المدفوعات والوصول الموحد ([app/Models/Payment.php](file:///c:/wamp64/www/hospital-system/app/Models/Payment.php))**:
    - إضافة دوال الوصول: `attending_doctor_name` (استخراج اسم الطبيب النظيف من الكشفية أو الزيارة أو الطوارئ أو الجراحة)، `service_details_text` (تجميع كافة الفحوصات والخدمات المقدمة في الإيصال)، و `insurance_type_label`.
  * **ربط المسارات والقائمة الجانبية الموحدة ([routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php) & [resources/views/layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php))**:
    - إضافة مسارات `Route::prefix('insurance-claims')` للواجهة والتصدير.
    - إضافة رابط `مطالبات وسجلات الضمان` ضمن قسم الحسابات العامة والمالية بالقائمة الجانبية.
  * **حزمة الاختبارات الآلية الحصينة ([tests/Feature/HealthInsuranceClaimsReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HealthInsuranceClaimsReportTest.php))**:
    - بناء 4 اختبارات ميزة متكاملة تؤكد الحماية، صحة عرض البيانات وأسماء الأطباء وفئات الضمان، فلترة الفئات، وتصدير ملف Excel مع اجتياز كامل (`4 passed, 15 assertions`).

- **المرحلة 104: تحويل كشف مطالبات الضمان الصحي إلى العرض التجميعي حسب المرضى تباعاً مع مجاميع الاستحقاق وتصحيح استعلام البحث (Health Insurance Claims Patient-Grouped Sequential View, Service Rows, Subtotals, View Mode Toggle & Search Fix)**:
  * **العرض التجميعي الذكي حسب المريض تباعاً ([resources/views/insurance/claims/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/insurance/claims/index.blade.php))**:
    - تحويل الواجهة الرئيسية لتظهر كل مريض على حدة في ترويسة رئيسية مميزة (الاسم، فئة الضمان بشارتها الملونة، رقم بطاقة الضمان، الهاتف، رقم الملف، وإجمالي عدد خدماته).
    - عرض كافة خدمات وفحوصات هذا المريض الطبية مسردة تباعاً بسطور تفصيلية (رقم الوصل، وقته، القسم، الطبيب المعالج، تفاصيل الفحص الطبي الدقيق، السعر الكلي، نقد المريض، ومطالبة الضمان ونسبة التغطية مع زر الطباعة).
    - سطر ملخص ومجاميع خاص بكل مريض (Subtotal) يحسب إجمالي القيمة الاستحقاقية لخدماته، إجمالي تحمله نقداً، وإجمالي مطالبة الضمان للمستشفى.
    - بعد انتهاء خدمات ومجاميع المريض، يبدأ المريض التالي مباشرة بنفس الترتيب السلس والواضح.
    - توفير مبدل عرض مرن (View Mode Switcher): `[ حسب المريض (مجمّع) ]` و `[ حسب الوصل (تفصيلي) ]` للتبديل الفوري بنقرة زر واحدة.
  * **تطوير الكنترولر والاستعلامات ([app/Http/Controllers/HealthInsuranceClaimController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/HealthInsuranceClaimController.php))**:
    - استعلام تجميعي فائق الكفاءة والسرعة يجمع مطالبات كل مريض مع ترقيم صفحات المرضى (`paginate(15)`) وتحميل العلاقات مسبقاً لمنع أي استعلامات متكررة (N+1).
    - حصر ومعالجة الدفعات التي قد لا ترتبط بملف مريض محدد في قسم مستقل لضمان عدم ضياع أي دينار.
    - تصحيح استعلام البحث النصي (`search`): البحث عن اسم المريض ورقم هاتفه عبر علاقة `user` الصحيحة في قاعدة البيانات (حيث لا توجد أعمدة name أو phone في جدول patients بل في جدول users) وحل استثناء `1054 Unknown column 'name'`.
  * **ترقية تصدير ملفات Excel ([app/Exports/HealthInsuranceClaimsExport.php](file:///c:/wamp64/www/hospital-system/app/Exports/HealthInsuranceClaimsExport.php))**:
    - دعم تصدير ملف الإكسيل بنمطين متوافقين مع العرض النشط: تصدير شيت ملخص المرضى واستحقاقاتهم التراكمية، أو تصدير كشف الوصولات الفردية بكامل أعمدته الـ 16.
  * **حزمة الاختبارات الآلية الحصينة ([tests/Feature/HealthInsuranceClaimsReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HealthInsuranceClaimsReportTest.php))**:
    - توسيع الاختبارات إلى 6 اختبارات آلية ميزة تؤكد ظهور المرضى وخدماتهم المجمعة ومجاميع الاستحقاق، وعمل البحث بالاسم العربي للمريض بنجاح مع اجتياز كامل (`6 passed, 24 assertions`).
- **(2026-10-22 — المرحلة 105: توحيد هيكلية محطة عمل الطوارئ مع محطة الاستشارية: تخصيص تبويب طلبات الفحوصات لإنشاء الطلبات الجديدة ونقل كافة بطاقات الطلبات ونتائج المختبر والأشعة إلى السجل الطبي الدائم والفحوصات Emergency Workstation Harmonization with Outpatient Clinic: Dedicated Ordering Forms Tab & Consolidation of Investigation Cards, Steppers & Results into Permanent Medical Record)**:
  * **تخصيص تبويب طلبات الفحوصات (Tab 3: `#tab-requests`) للطلبات الجديدة ([resources/views/emergency/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/show.blade.php))**:
    - إزالة بطاقات الطلبات والنتائج السابقة المكدسة التي كانت تملأ تبويب الطلبات وتخفي استمارات الفحوصات الطبية.
    - فتح وعرض نماذج طلب المختبر والأشعة مباشرة وبشكل دائم ومريح دون الحاجة للنقر على زر فتح منسدل (Collapse)، مع تفعيل الفلاتر السريعة (الكل، مشمول بالضمان، كاش فقط)، والبحث اللحظي التفاعلي، وعداد العناصر المحددة.
    - إضافة شريط توجيهي رسمي ينبه الطبيب إلى أن متابعة مسار الطلبات، مراحل سداد الكاشير، وصدور النتائج والتقارير الطبية الرسمية معروضة ومحدثة لحظياً في لوحة "السجل الطبي والفحوصات" على يسار الشاشة مع زر نقل سلس.
  * **تطوير وترقية لوحة السجل الطبي الدائم والفحوصات (`#side-cur-pane`)**:
    - نقل وإعادة هيكلة كافة بطاقات طلبات المختبر والأشعة لتظهر في مكانها المعتمد تحت تبويب "طلبات الحالة" بالسجل الطبي الأيسر (مطابق تماماً لمحطة الطبيب الاستشارية).
    - إضافة شريط تصفية تفاعلي سريع (Sub-Filter Pills: الكل، المختبر، الأشعة، العلاجات) لتسهيل تنقل الطبيب بين أنواع الفحوصات.
    - تزويد كل بطاقة فحص بشريط التقدم ومراحل المسار التفاعلي ذي الخطوات الأربع (Status Progress Stepper: طلب الطبيب ⬅️ الكاشير ⬅️ المختبر/قسم الأشعة ⬅️ النتيجة/التقرير).
    - إبراز حالة السداد (مسدد بشارة خضراء / عند الكاشير بشارة تنبيه صفراء)، وأولوية الفحص، وزر الطباعة الرسمية للنتائج المعتمدة.
    - جدول سريري مصغر لعناصر الفحص يوضح القيم والوحدات والمعدلات الطبيعية مع تمييز النتائج غير الطبيعية باللون الأحمر، مع إتاحة زر الحذف الفوري للعناصر غير المسددة للطبيب (`emergencyRemoveRequestItem`).
    - عرض تفاصيل تقارير الأشعة والمشاهدات والانطباع التشخيصي، مع أزرار معاينة وتحميل صور الأشعة المرفقة.
    - تضمين أرشيف الطلبات الملغاة داخل أكورديون منظم أسفل القائمة.
  * **التحقق والاختبارات الآلية الحصينة**:
- **(2026-10-22 — المرحلة 106: منظومة إلغاء طلبات فحوصات الطوارئ ودورة الاسترجاع المالي لدى الكاشير وإصدار السندات السالبة ومعالجة حالة المريضة نسيمه عطب سلمان Emergency Request Cancellation, Doctor Action, Cashier Refund Processing, Negative Voucher Invoicing & Patient #47 Refund Execution)**:
  * **معالجة حالة المريضة نسيمه عطب سلمان (طوارئ #47 - Patient ID 8600)**:
    - التحقق وتدقيق كافة الخدمات: كشفية استشارية باطنية، فحص سكر دم، كشفية طوارئ، فحص أشعة رنين كاحل، وأشعة سينية مقطعين.
    - إلغاء فحصي الأشعة غير المنجزين والمطلوبين بالخطأ:
      1. طلب رنين مغناطيسي لمفصل الكاحل (`Ankle - Joint MRI`) بقيمة 200,000 د.ع.
      2. طلب أشعة سينية عادية مقطعين (`Two-Section Standard X-Ray`) بقيمة 40,000 د.ع.
    - إصدار سندي استرجاع مالي رسمي لدى الكاشير بقيمة إجمالية **240,000 د.ع**:
      * وصل استرجاع **`REC-20261022-0020`** بمبلغ `-200,000 د.ع`.
      * وصل استرجاع **`REC-20261022-0021`** بمبلغ `-40,000 د.ع`.
    - تحديث حالة الطلبين إلى `status = 'cancelled'` و `payment_status = 'refunded'` وانتقالهما تلقائياً لأرشيف الطلبات الملغاة بسجل الطوارئ.
  * **توسيع محطة عمل الطوارئ بإمكانية إلغاء الفحوصات ([app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php) & [resources/views/emergency/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/show.blade.php))**:
    - تمكين الطبيب من إلغاء أي طلب أشعة أو مختبر غير مكتمل (معلق أو مسدد) بنقرة زر واحدة مع إدخال سبب الإلغاء.
    - معالجة ذكية فورية: إذا كان الطلب مسدداً، يتم تحديث حالته إلى `cancelled` وتوجيهه تلقائياً إلى تبويب "مستحقات الاسترجاع" لدى الكاشير لصرف المبلغ للمريض فوراً.
  * **تطوير واجهة ونظام الكاشير لدعم استرجاعات الطوارئ ([app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php) & [resources/views/cashier/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/index.blade.php))**:
    - إدراج طلبات الأشعة والمختبر الملغاة في طوارئ المستشفى تلقائياً ضمن قائمة مستحقات الاسترجاع مع عداد الإشعارات بالشريط العلوي للكاشير.
    - فتح نافذة صرف الاسترجاع المالي الفوري وتحديد وسيلة الإرجاع (نقد/شبكة) مع تدوين الملاحظات.
    - توليد سند استرجاع سالب رسمي (`Payment` بمبلغ سالب ورقم إيصال رسمي `REC-...`)، وتحديث حالة دفع الطلب إلى `refunded` ونقله لصفحة طباعة الإيصال الحرارية.
  * **إظهار فحوصات الطوارئ في صفحة طباعة الوصل ([resources/views/cashier/receipt.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt.blade.php))**:
    - دعم عرض بنود وتفاصيل تحاليل وأشعة الطوارئ في جدول تفاصيل الوصل المالي بعد أن كانت محصورة بكشفية الطوارئ فقط.
  * **تطوير الموديلات وقاعدة البيانات**:
    - دعم حالة الدفع `refunded` في عمود `payment_status` بجداولي `emergency_radiology_requests` و `emergency_lab_requests` مع مايجريشن مخصص يدعم MySQL و SQLite.
    - إضافة علاقة `payment()` وحقل `payment_status` إلى الموديلين.
  * **حزمة الاختبارات الآلية الحصينة ([tests/Feature/EmergencyRequestCancellationAndRefundTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyRequestCancellationAndRefundTest.php))**:
- **(2026-10-22 — المرحلة 107: تعريب وتوحيد مسميات الأدوار والصلاحيات بالكامل، وإعادة تصميم لوحة الأدوار التنفيذية، وإضافة صلاحية التحكم بمطالبات الضمان الصحي Complete Roles & Permissions Arabic Localization, Executive Roles Hub Redesign, Unified Role Labels Engine, Zero Untranslated Permissions & Insurance Claims Access Control)**:
  * **إدراج واجهة مطالبات وسجلات الضمان الصحي في نظام الأدوار والصلاحيات**:
    - استحداث صلاحية مستقلة ومخصصة باسم `view insurance claims` (عرض ومتابعة كشوفات وسجلات مطالبات الضمان وتصديرها).
    - إضافة مايجريشن مخصص `database/migrations/2026_10_22_150000_add_view_insurance_claims_permission.php` وتثبيتها في قاعدة البيانات ومنحها لأدوار الإدارة والمحاسب.
    - حماية مسارات وواجهة `HealthInsuranceClaimController` بميدلوير يمنع الدخول (403 Forbidden) إلا لمن يملك صلاحية `view insurance claims` أو `manage health insurance` أو إدارة المستشفى.
    - إتاحة التحكم بها وتفعيلها/تعطيلها بسهولة عبر بطاقة "الحسابات العامة والمالية" في شاشة إدارة وتعديل الأدوار `/roles/{role}/edit`.
  * **تعريب وتوحيد منظومة مسميات الأدوار بالكامل (Unified Role Labels Engine)**:
    - تطوير دالة مركزية موحدة `RoleManagementController::getRoleLabel($roleName)` تشكل المصدر المعتمد لترجمة وتخصيص أي دور (المسمى العربي، الوصف، الأيقونة، ولون الشارة).
    - تغطية كافة الأدوار الـ 26 المسجلة في النظام بما فيها الأدوار التي كانت تظهر باللغة الإنجليزية:
      * `accountant` ⬅️ **المحاسب المالي** (أيقونة الحاسبة واللون الأزرق).
      * `admin-hsop` / `hospital_admin` ⬅️ **مدير المستشفى**.
      * `thaman` ⬅️ **هيئة الضمان الصحي**.
      * `gh&est` ⬅️ **كادر الجراحة والاستشارية**.
      * `staff` ⬅️ **كادر إداري عام**.
    - تصنيف الأدوار إلى 6 قطاعات وظيفية ذكية عبر `getRoleCategory()`:
      * `الطبية والجراحية`، `المالية والكاشير`، `التشخيصية`، `الاستقبال والطوارئ`، `الصيدلية والمستودع`، `الإدارة والأنظمة`.
    - تعميم الدالة الموحدة على شاشات: قائمة الأدوار (`roles.index`)، شاشة تعديل الصلاحيات (`roles.edit`)، إضافة دور (`roles.create`)، وإدارة المستخدمين (`users.index`, `users.create`, `users.edit`).
  * **إعادة تصميم وهيكلة لوحة إدارة الأدوار (`resources/views/roles/index.blade.php`)**:
    - شريط كروت مؤشرات تنفيذية علوي (إجمالي الأدوار، الكادر المعين، إجمالي الصلاحيات، الأدوار الشاغرة).
    - شريط تبويبات وفلاتر سريعة للقطاعات الوظيفية (Category Filter Pills) لتصفية الأدوار بنقرة زر واحدة.
    - بحث لحظي ذكي (Live Search) بالاسم العربي أو الإنجليزي أو الوصف.
    - مبدل عرض مزدوج (Dual View Mode): عرض البطاقات الحديثة (Cards Grid) مع شريط نسبة تغطية الصلاحيات ووسوم الأقسام البارزة، وعرض الجدول المضغوط (Compact Table).
    - نافذة تفاعلية منبثقة سريعة (Role Users Modal) عبر AJAX لمعاينة جميع الموظفين المعينين لأي دور بضغطة زر دون مغادرة الصفحة.
  * **تعريب 100% من صلاحيات النظام (Zero Untranslated Permissions)**:
    - تدقيق كامل الـ 145 صلاحية في قاعدة البيانات وسد النواقص لضمان عدم ظهور أي كود برمجي إنجليزي في الواجهات (إضافة `inquiry.create.radiology` والصلاحيات التراثية المخفية).
    - تعريب الأقسام الرئيسية في شاشة الصلاحيات (`permissions.index`) وفق بطاقات النظام العشرة.
  * **حزمة الاختبارات الآلية الحصينة**:
    - اجتياز اختبارات صلاحيات الضمان الصحي في [HealthInsuranceClaimsReportTest](file:///c:/wamp64/www/hospital-system/tests/Feature/HealthInsuranceClaimsReportTest.php) بـ 8 اختبارات ناجحة (`8 passed, 26 assertions`).
  * **المرحلة 111 (2026-10-23) — إصلاح استعلام التحقق من الفحوصات المعلقة عند إنهاء الزيارة، وتحسين استجابة AJAX، وتفعيل الحظر الصارم للحجز المكرر في واجهة الاستعلامات (Doctor Visit Completion SQL Fix, Clean AJAX Error Handling & Inquiry Hard Block UI)**:
    - **إصلاح استعلام SQL لفحص الفحوصات المعلقة (`DoctorVisitController::update`)**: معالجة خطأ `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'patient_id' in 'where clause'` الذي كان يتسبب بـ 500 Internal Server Error عند رغبة الطبيب بإنهاء الزيارة المكتملة فحوصاتها، من خلال تصحيح الاستعلام بالاعتماد على معرّفات زيارات اليوم (`whereIn('visit_id', $sameDayVisitIds)`).
    - **تحسين إظهار رسائل AJAX عند إنهاء الزيارة (`show.blade.php`)**: قراءة وعرض رسالة الخطأ السريرية الصادرة من السيرفر (`data.message`) مباشرة بدلاً من الرسالة العامة الافتراضية "حدث خطأ أثناء إنهاء الزيارة".
    - **الحظر الصارم لتكرار الحجز في واجهة الاستعلامات (`inquiry/create.blade.php`)**: تحويل التنبيه البرتقالي إلى بانيل أحمر مانع (`⛔ غير مسموح بتكرار الحجز لنفس اليوم نهائياً`)، مع تعطيل زر الإرسال والحفظ تلقائياً (`Disabled`) ومنع الإرسال فورياً في حال كان المريض مسجلاً اليوم لدى الطبيب.
  * **المرحلة 112 (2026-10-24) — تفكيك وقرارات طلبات الفحوصات لدى الكاشير بتصميم الكبسولات العصرية (Segmented Pills) وانعكاسها السريري وتبويب الفحوصات المؤجلة بلوحة الكاشير (Cashier Item-Level Pay/Postpone/Cancel Controls, Modern iOS-Style Segmented Pills, Clinical Gate Release, Doctor Alerts & Dedicated Postponed Tab in Cashier Hub)**:
    - **تصميم الكبسولات العصرية السلسة (Modern Segmented Pills)**: تحويل خيارات الكاشير لكل فحص إلى شريط كبسولي ناعم ومضغوط بتدرجات لونية وتأثيرات ظلية حديثة (تسديد بالأخضر الزمردي ⬅️ تأجيل بالكهرماني ⬅️ إلغاء بالأحمر الوردي) مع قائمة أسباب مدمجة تظهر بسلاسة عند التأجيل أو الإلغاء.
    - **تفكيك الطلبات وحساب الصندوق المالي (`CashierController::processRequestPayment`)**: فصل الفحوصات المؤجلة في طلب مستقل بحالة `postponed`، والفحوصات الملغاة في طلب مستقل بحالة `cancelled` مع توثيق السبب السريري، وتحصيل المسدد فقط مع إمكانية إلغاء أو تأجيل كافة الفحوصات برسم 0.
    - **استحداث تبويب الفحوصات المؤجلة بلوحة الكاشير (`/cashier`)**:
      * شريط تبويبات الكاشير يضم الآن تبويباً مخصصاً: `[ ⏱ الفحوصات المؤجلة (N) ]` يعزل الطلبات المؤجلة عن قائمة العمل اليومية المعتادة.
      * جدول تفصيلي متكامل يعرض: رقم الطلب، القسم التشخيصي، المريض، الفحوصات المؤجلة مع شارة سبب التأجيل (قلة سيولة، ضيق وقت، رفض فحص)، الطبيب الطالب، المبلغ المتوقع، ووقت وتاريخ التأجيل.
      * زر إجراء مباشر: `[ 💳 تسديد الآن ]` يفتح صفحة التسديد فوراً بمجرد عودة المريض لاستئناف الفحص دون الحاجة لطلب طبي جديد.
      * بانيل تنبيه كهرماني علوي ذكي ينبه الكاشير لوجود طلبات مؤجلة مع زر سريع لفتح التبويب فورياً.
    - **تحرير القفل السريري بمحطة الطبيب (`DoctorVisitController` & `show.blade.php`)**: استبعاد الفحوصات الملغاة والمؤجلة من شرط الفحوصات المعلقة لتمكين الطبيب من إنهاء الزيارة فوراً دون احتجاز، مع إظهار تنبيهات سريرية بارزة أعلى تبويب الفحوصات وفلتر سريع للملغاة.
    - **عزل الأقسام التشخيصية وطابور العيادة (`RadiologyStaffController` & `DoctorQueueController`)**: استبعاد الفحوصات الملغاة والمؤجلة من شاشات الفحص الفعالة بالأشعة والمختبر وعدم إدراج المريض بقائمة انتظار الفحوصات.
    - **حزمة الاختبارات الآلية الحصينة (`CashierItemActionCancellationAndPostponeTest`)**: اجتياز اختبار تسديد الفحوصات المختارة وفصل الملغي والمؤجل بأسبابه، واختبار إلغاء كافة الفحوصات بالكامل، واختبار ظهور الفحوصات المؤجلة في التبويب المخصص بلوحة الكاشير بنجاح تام (`3 passed, 38 assertions`).
  * **المرحلة 113 (2026-10-25) — تحصين نسب التحمل المالية، إلغاء التعديل اليدوي للنسب والمبالغ، ومطابقة تسعير الوصل الإلكتروني والحراري للضمان الصحي (Strict Cashier Copay Lockdown, Removal of Manual Copay & Amount Overrides, 100% Web & Thermal Receipt Parity & Hostinger Data Fix)**:
    - **تشخيص وحل تباين الوصل الإلكتروني والحراري (`receipt.blade.php`)**:
      * معالجة مشكلة قراءة السعر الخاص بدلاً من سعر الضمان في جدول البنود للوصل الإلكتروني بمطابقة الدالة `calculateInsurancePricing` لكل من المختبر والأشعة.
      * تصحيح القيد المالي رقم #1062 للمريضة شكرية أحمد على قاعدة بيانات هوستنجر وقاعدة البيانات المحلية، بإعادة نسبة التحمل إلى 25% الرسمية المعتمدة للفئة H وتعديل المبلغ إلى 18,500 د.ع وتثبيت حصة الضمان 55,500 د.ع بدقة 100%.
    - **إلغاء التعديل اليدوي لنسبة التحمل نهائياً عبر كافة واجهات الكاشير (`payment-form.blade.php`, `request-payment-form.blade.php`, `surgeries/payment-form.blade.php`)**:
      * إزالة زر فتح وتعديل الضمان `[ 🔒 تثبيت آلي ] / [ 🔓 تعديل استثنائي مفتوح ]` ومجموعة حقول الإدخال اليدوية للنسبة (`#manual_copay_input_group`) وأزرار النسب الخماسية السريعة للعمليات.
      * قفل خانة النسبة لتكون حقل عرض محمي وشارة تأكيد رسمية فقط مع تمرير النسبة تلقائياً عبر حقل مخفي وفق معادلة فئة المريض الرسمية `getCopayPercentageFor()`.
    - **قفل خانة المبلغ المطلوب قبضه (`#amount_input`)**:
      * جعل حقل المبلغ `readonly` مع خلفية مضاءة ووسم حماية مشفر `[ 🔒 محسوب آلياً ومطابق للوصل ]` لمنع الكاشير من كتابة مبالغ يدوية مغايرة للحسبة الآلية الدقيقة.
    - **التحصين البرمجي الصارم في الكنترولر (`CashierController.php`)**:
      * إلزام دالة `processPayment` و `processRequestPayment` و `processSurgeryPayment` بفرض نسبة التحمل الرسمية لفئة المريض تلقائياً وتثبيت `$patientPaidAmount` مساوياً لحصة المريض المحسوبة نظامياً دون الاعتماد على مدخلات الفورم المعدلة يدوياً.
    - **حزمة الاختبارات الآلية**:
      * اجتياز كامل حزمة اختبارات الكاشير بـ 20 اختباراً ناجحاً (`20 passed, 154 assertions`).

  - **المرحلة 114 (2026-10-26): عداد تكرار الخدمات التمريضية والطبية بالطوارئ وتحديث الحسابات والوصولات آلياً (Emergency Nursing Services Dynamic Stepper Counter, Realtime Multiplied Pricing & Full Receipt Parity)**:
    - **قاعدة البيانات والمايجريشن**:
      * إنشاء مايجريشن `2026_10_26_090000_add_quantity_to_emergency_emergency_service_table.php` لإضافة حقل `quantity` الافتراضي 1 لجدول الوسيط `emergency_emergency_service`.
      * تطبيق الترحيل محلياً وسحابياً على قاعدة بيانات هوستنجر بدقة 100%.
    - **واجهة الطوارئ (`resources/views/emergency/show.blade.php`)**:
      * تحويل اختيار الخدمات التمريضية إلى صف تفاعلي متطور مزود بعداد أنيق `[-] [ 1 ] [+]` يظهر تلقائياً عند تحديد الخدمة.
      * ضبط العداد ليبدأ افتراضياً من 1 (بحد أدنى 1 وحد أقصى 99).
      * تحديث شارة الإجمالي الفرعي للخدمة فورياً (`سعر الخدمة × العدد`) مع استجابة لحظية لأزرار الزيادة والنقصان والإدخال الرقمي اليدوي.
    - **محرك الحسابات والكاشير والتقارير الطبية**:
      * تحديث دالة `updateMedical` في `EmergencyController` لقراءة وحفظ مصفوفة الكميات `service_quantities`.
      * تحديث دوال الاحتساب المالي `getUnpaidAmount` و `upsertUnifiedEmergencyPayment` و `CashierController` لضرب سعر الخدمة في كميتها لحساب حصة المريض وحصة الضمان والإجمالي العام.
      * إظهار رمز التكرار والكمية `(×N)` في مسميات بنود الوصل الإلكتروني والوصل الحراري والتقرير الطبي المعتمد لقسم الطوارئ.
    - **حزمة الاختبارات الآلية**:
      * إنشاء اختبار الميزة `tests/Feature/EmergencyServiceQuantityStepperTest.php` واجتيازه بنجاح (`2 passed, 13 assertions`) مع سلامة كامل حزمة اختبارات الكاشير الـ 20.

  - **المرحلة 115 (2026-10-26): مطابقة وتوحيد واجهة دفع الطوارئ 100% مع واجهة الكاشير الرئيسية، تحصين نسب فئات الضمان، وإرجاع وصولات المريضة فاطمة (Emergency Cashier Form 100% Parity with Request Payment, Strict Category Copay Lockdown & Unpaid Receipt Reversal)**:
    - **إرجاع وتصحيح وصولات المريضة فاطمة صباح (الفئة H)**:
      * إرجاع قيد ودفع خدمات الطوارئ رقم #1191 إلى "غير مسدد" ومحمي بنسبة 0% الرسمية للفئة H بمبلغ 0 د.ع للمريض وتغطية 100% للضمان (75,000 د.ع).
      * إرجاع وتصحيح وصل التحاليل رقم #1187 إلى "غير مسدد" مع إعادة احتسابه وفق نسبة الـ 25% الرسمية المعتمدة للفئة H (حصة المريض: 7,750 د.ع، وحصة الضمان: 23,250 د.ع بدلاً من حساب الـ 15% الخاطئ).
    - **إعادة تصميم وتوحيد واجهة دفع الطوارئ بالكامل (`resources/views/cashier/emergency-payment-form.blade.php`)**:
      * مطابقة الواجهة بنسبة 100% مع واجهة `request-payment/567` العصرية الفاخرة:
        - رأس أزرق ملكي مع بطاقتين علويتين لبيانات الحالة وبيانات المريض ورقم الهوية.
        - بطاقات الإحصاء المالي الثلاثية المباشرة (إجمالي السعر المعتمد، حصة هيئة الضمان، والصافي المطلوب نقداً).
        - شريط فئة الضمان الصحي المعتمد مع شارة `[ الفئة H ]` ونسبة الاستقطاع الرسمية للطوارئ.
        - جدول تفصيلي متكامل للبنود والخدمات يوضح السعر المعتمد وحصة المريض وحصة الضمان لكل بند.
        - قفل خانة المبلغ المحصل `readonly` مع وسام `[ 🔒 محسوب آلياً ومطابق للوصل ]` لمنع الإدخال اليدوي.
        - التوجيه التلقائي المباشر للوصل فور تأكيد الدفع لطباعة الإيصال الإلكتروني والحراري.
    - **التحصين البرمجي الصارم في الكنترولر (`CashierController.php` & `EmergencyController.php`)**:
      * إلزام دالة `processEmergencyPayment` بجلب نسبة فئة المريض تلقائياً عبر `getCopayPercentageFor('emergency')`.
      * تعديل دالة `upsertUnifiedEmergencyPayment` لإبقاء الفواتير المغطاة بالكامل 100% بالضمان في حالة انتظار لتأكيدها وإصدار وصل رسمي لها عبر الكاشير.
      * معالجة تحويل الدفعات المسددة مسبقاً تلقائياً إلى صفحة الوصل الرسمي بدلاً من رسائل التحويل التحذيرية.
    - **حزمة الاختبارات الآلية الحصينة**:
      * بناء اختبار ميزة متكامل `tests/Feature/EmergencyCashierCopayLockTest.php` واجتيازه بنجاح تام مع اجتياز كامل حزمة اختبارات الكاشير الـ 21 بنجاح (`21 passed, 165 assertions`).

### 🟢 Done (المراحل المنجزة والموثقة):
- **المرحلة 132 (2026-10-30): تمكين الكاشير الإداري casshieradmin والمخولين بالإبطال من استعراض كافة الوصولات وفلترة الكاشيرات (Cashier Admin & Void Authorized Users Full Receipts Access & Departmental Cross-Drawer Filter Parity)**:
  * إزالة قيد الفلترة الذاتية `where('cashier_id', $user->id)` عن الكاشير الإداري `casshieradmin` ومن يملك صلاحية `void payment receipt` في تقرير المقبوضات (`/cashier/reports`).
  * تمكينهم من رؤية والبحث في وصولات جميع موظفي الكاشير وصناديق المستشفى، واختيار كاشير محدد أو "جميع الكاشيرات" للتدقيق والإبطال.
  * اجتياز كامل حزمة اختبارات إبطال الوصولات بنجاح 100% (11 passed, 73 assertions).

- **المرحلة 131 (2026-10-29): دمج وتوحيد فحوصات CBC في فحص نشط وحيد رقم 43 ونقل باقات الأطباء وتعطيل المكرر كأرشيف (CBC Tests Consolidation into Single Active ID 43 & Legacy Archiving)**:
  * اعتماد السجل رقم **#43** كفحص نشط ورسمي وحيد باسم **`CBC (5-Part Diff)`** وبسعر 20,000 د.ع مع كامل البارامترات الـ 27.
  * تمييز وتعطيل السجل #44 كأرشيف مدمج والسجل #1440 كمكرر ملغي (`is_active = 0`).
  * ترحيل باقات الأطباء المفضلة (21 باقة) لترتبط بالفحص 43 مباشرة وتطبيق المايجريشن محلياً وسحابياً على هوستنجر.

- **المرحلة 130 (2026-10-29): ترقية وتوحيد طلب استشارة الطوارئ إلى اختيار استشاري فردي وربطها مالياً بخدمة استشارة اخصائي من العيادة الاستشارية بسعر 30,000 د.ع (Emergency Single Consultant Selection & Automated 30,000 IQD Emergency Service Billing)**:
  * استبدال صندوق الاختيار المتعدد القديم (`select multiple`) بقائمة منسدلة حديثة وسلسة (`Dropdown single select`) لاختيار الطبيب الاستشاري المطلوب، مع حصر الخيارات في أطباء الاستشارية (`consultant`) والسونار (`sonar`) المتاحين اليوم بالدوام فقط.
  * إلغاء الرقم المقطوع القديم (50,000 د.ع) وتصفير رسوم الموعد الإضافية (`consultation_fee = 0`) منعاً للازدواجية.
  * ربط وإلحاق خدمة الطوارئ الرسمية **"استشارة اخصائي من العيادة الاستشارية"** (رقم #115) بسعرها الرسمي المعتمد **30,000 د.ع** تلقائياً إلى خدمات الحالة في جدول `emergency_emergency_service`، ومضاعفة الكمية عند طلب استشارات إضافية.
  * إدراج الخدمة رسمياً في فاتورة الطوارئ الموحدة ووصل الكاشير الإلكتروني والحراري وحسابات الضمان الصحي ومستحقات الطبيب (24,000 د.ع للطبيب و 6,000 د.ع للمستشفى).
  * بناء واجتياز اختبار ميزة شامل بنجاح تام (`10 passed, 45 assertions`).

- **المرحلة 129 (2026-10-29): توحيد فحص عد الدم الشامل الآلي باسمه الطبي المعتمد CBC (5-Part Diff) وفصل فحص فيلم الدم المجهري Blood Film وتحديث المطبوعات والاختبارات (Automated CBC (5-Part Diff) Medical Standardization, Standalone Microscopic Blood Film & Complete Print Parity)**:
  * توحيد واعتماد المسمى الطبي الدقيق `CBC (5-Part Diff)` لفحص الجهاز الآلي (رقم 43 و 44) بسعر 20,000 د.ع مع كافة البارامترات الـ 27 الفرعية.
  * تعطيل السجل المكرر (رقم 1440) منعاً للازدواجية، وتثبيت فحص فيلم الدم المجهري اليدوي المستقل `Blood Film` (رقم 1243) بسعر 50,000 د.ع.
  * تحديث دالة التطبيع السريرية `$canonicalTestName` في طباعة الطوارئ والاستشارية ومحطة الإدخال ومرفقات الجهاز بنمط `[ 🔬 مرفق: CBC (5-Part Diff) (PDF) ]`.
  * تطبيق المايجريشن وتحديث السجلات محلياً وعلى قاعدة بيانات هوستنجر الحية واجتياز كافة الاختبارات الآلية (21 passed, 133 assertions).

- **المرحلة 128 (2026-10-29): فك ارتباط وإلغاء كافة الطلبات الطبية عند إبطال الوصل Rebill و Refund ومنع ظهورها في طابور المختبر والأشعة وإظهارها كملغاة عند الطبيب (Reopened & Refunded Payment Medical Requests Cascade Cancellation, Departmental Queue Purification & Clinical Doctor Alerts)**:
  * حصر وجمع كافة معرفات الطلبات الطبية المرتبطة بالوصل المالي المُراد إبطاله بأي شكل (`$payment->request_id`, `$payment->lab_request_ids`, `$payment->radiology_request_ids`).
  * فك ارتباط وتصفير كافة الطلبات المدمجة دفعة واحدة عند إعادة الفتح (`rebill`) وإلغاؤها عند الاسترداد (`refund`) مع توثيق السبب ليظهر كملغي لدى الطبيب.
  * تطهير طوابير العمل في المختبر والأشعة باستبعاد الطلبات الملغاة أو المرتبطة بوصولات مبطلة.
  * تصحيح السجلات العالقة (مثل الطلب رقم 859 العالق في الوصل 1677 على هوستنجر) واجتياز اختبارات الميزة الشاملة.

- **المرحلة 116: منظومة إبطال وإلغاء الدفع وإعادة فتح الوصولات للتصحيح والاسترداد مع واجهة تدقيق رقابية مستقلة وصلاحية مشددة (Receipt Void & Payment Reversal System, Dedicated Audit Page, Granular Permissions & Full Cascade Re-billing Workflow)**:
  * **بنية الصلاحيات والتحصين الأمني (Spatie Permissions & Gating)**:
    - إضافة صلاحية مستقلة ومحصورة في `RolesAndPermissionsSeeder`: `void payment receipt`.
    - ربط الصلاحية حصراً بالأدوار الرقابية والمسؤولة (`admin` و `accountant`) مع إمكانية منحها بالاسم للمشرف الإداري أو الاستعلامات، وحجبها عن الكاشير العادي لمنع تضارب المصالح.
    - حماية مسارات الإبطال وعرض الاستمارة برمجياً عبر `auth()->user()->can('void payment receipt')` في `PaymentVoidController` وميدلوير الصلاحيات.
  * **قاعدة البيانات وسجل التدقيق الرقابي (Database Schema & Audit Trail)**:
    - إضافة حقول الرقابة إلى جدول `payments`: `voided_at`, `voided_by`, `void_type` (`rebill`, `refund`), `void_reason`, `original_receipt_number`.
    - إنشاء جدول مخصص لتدقيق الإلغاءات `payment_void_logs` لتوثيق تاريخ وحركة كل وصل ملغى والمستخدم والمبالغ ونوع الإجراء وسبب الإبطال بالثانية.
    - تحديث موديل `Payment` بدوال `voidedBy()`, `voidLogs()`, و `isVoided()`.
  * **الكنترولر ومنطق الأعمال الصارم (Strict Controller & Form Request)**:

- **المرحلة 103 (2026-10-22) — منظومة توصيات وخطة رعاية الخدج التكرارية، تنبيهات الرضاعة والعلاج باللوحة الحية، بوابة الخروج المالي، وتصحيح مسمى الخدج بالمختبر والأشعة**:
  * **معالجة مسمى المريض والمصدر في لوحتي المختبر والأشعة عند "إظهار الكل"**:
    - تصحيح ظهور اسم المريض "غير محدد" ونوع الطلب "مختبر خارجي مباشر" وحالة الدفع "غير مسدد" عند عرض جميع الطلبات في لوحة المختبر ولوحة الأشعة.
    - فحص معرّف حجز الحاضنة وعرض اسم الطفل الخديج بوضوح، ورقم الحاضنة، ورقم الملف، والطبيب الأخصائي، مع وسم بارز: `[ 👶 قسم الخدج والحاضنات ]` وشارة دفع `[ حساب الحاضنة ]`.
  * **بوابة الخروج المالي الإلزامية للخدج وإبقاء الحسابات المعلقة بالكاشير**:
    - منع تخريج أي طفل خديج في حال وجود متبقٍ مالي بذمته (`$stayDetails['net_balance'] > 0`)، مع توجيه المستخدم برسالة خطأ عربية وإعادة التوجيه إلى الكاشير للتسديد.
    - في حال وجود حالات سابقة تم تخريجها مع وجود رصيد متبقٍ، تم تحديث كاشير الخدج (`/cashier`) للإبقاء على ظهور بطاقاتهم مع وسم تحذيري واضح: `[ ⚠️ خروج معلق مالياً ]`.
  * **منظومة خطة الرعاية والتوصيات السريرية التكرارية للخدج (`nicu_care_schedules`)**:
    - إضافة تبويب كامل في محطة الخدج السريرية: `[ 📋 التوصيات وخطة الرعاية ]` لإدارة ومتابعة توصيات الرضاعة والأدوية التكرارية.
    - الربط التفاعلي مع محطة التمريض (تحديث موعد الرضاعة تلقائياً عند إدخال الرضاعة بالعلامات الحيوية) ومع سجل إعطاء العلاج MAR (تنفيذ سريع وتحديث الموعد القادم بنقرة واحدة).
    - عزل سريري تام عن المخزن والصيدلية والكاشير الخارجي.
  * **شريط تنبيهات الحاضنات اللحظي على لوحة الحاضنات الرئيسية (`resources/views/nicu/index.blade.php`)**:
    - ظهور بطاقات تنبيهية بارزة ولحظية على كل حاضنة: `🍼 حان موعد الرضاعة` و `💊 حان موعد إعطاء دواء` مع إظهار تفاصيل الدواء أو الرضاعة ومقدار التأخير.
  * **الاختبارات الآلية الحصينة**:
    - اجتياز اختبارات دورة العمل الكاملة `NicuLabRadiologyCashierWorkflowTest` و `NicuWorkflowTest` بنجاح 100% (33 اختباراً، 296 تأكيداً).

- **المرحلة 104 (2026-10-22) — منظومة صرف واسترجاع فائض أمانات الخدج في الكاشير وإدراجها بتبويب مستحقات الاسترجاع وسند الاسترجاع والطباعة الحرارية**:
  * **رصد وصرف فائض الأمانات بالصندوق**:
    - رصد حالات الخدج التي دفعت أمانات تزيد عن إجمالي الحساب (`excess_deposit > 0`) وإظهار وسم بارز: `[ 💰 فائض: +X د.ع ]` مع زر مباشر: `[ ↩️ إرجاع الأمانات ]`.
    - مودال مخصص `#nicuRefundModal` معبأ تلقائياً بكامل الفائض المستحق، ومقيد بالحد الأقصى لمنع التجاوز.
    - معالجة الصرف والخصم التلقائي من صافي مدفوعات الحاضنة وإصدار سند استرجاع رسمي.
  * **الربط مع تبويب مستحقات الاسترجاع العام (`#tab-refunds`)**:
    - إدراج حالات الخدج ذات الفائض في جدول مستحقات الاسترجاع بجانب الفحوصات والطوارئ وتحديث عداد التنبيه.
    - استمرار ظهور الحالات التي خرجت طالما بقي في ذمة المستشفى أمانات مستحقة للإرجاع.
  * **سند استرجاع رسمي وطباعة حرارية 78mm**:
    - تحويل ترويسة ومحتوى الوصل الحراري إلى "سند صرف واسترجاع أمانات - قسم الخدج" مع إشارة سالبة وتوضيح الكاشير وتاريخ الصرف.
  * **الاختبارات الآلية الحصينة**:
    - إضافة اختبار `test_cashier_can_refund_excess_deposits_for_nicu_reservation` واجتياز كامل الحزمة (34 اختباراً، 314 تأكيداً).

- **المرحلة 105 (2026-10-26) — المخطط البياني لتتبع نمو ووزن الخديج ومطابقة محطة المختبر والأشعة مع الطبيب الاستشاري بالكامل**:
  * **المخطط البياني لتتبع وزن ونمو الخديج (Weight & Growth Curve)**:
    - إضافة تبويب مستقل في واجهة الخدج السريرية: `[ 📈 معدلات النمو والوزن ]` مع 5 بطاقات مؤشرات حيوية رئيسية (وزن الولادة/الدخول كخط أساس، الوزن الحالي، صافي التغير التراكمي مع النسبة %، معدل النمو اليومي جم/يوم، ومدة الحضانة بالأيام مع عدد القراءات).
    - رسم بياني تفاعلي متقدم عبر Chart.js (`#nicuWeightGrowthChart`) يقارن منحنى تدرج وزن الطفل مع خط الأساس لوزن الدخول (Baseline)، مع تهيئة ديناميكية عند فتح التبويب `shown.bs.tab`.
    - إرشاد سريري فسيولوجي لحديثي الولادة يوضح النزول الطبيعي حتى 10% في الأيام الأولى ثم بدء النمو المتسارع بمعدل 15-20 جم/كجم/يوم.
    - جدول سجل القياسات التفصيلي وتدرج الوزن (Weight Progression Log) يوثق التاريخ والوقت، الوزن، التغير عن القراءة السابقة، التغير التراكمي عن البداية، العلامات الحيوية المرافقة (حرارة/نبض/تشبع أكسجين)، التغذية، والممرض الموثق.
  * **مطابقة محطة طلبات المختبر والأشعة بقسم الخدج مع ترتيب وشاشات الطبيب الاستشاري بنسبة 100%**:
    - **محطة المختبر (`#labPane`)**: رأس المحطة مع زر إرسال علوي وسفلي تفاعلي مع عداد التحاليل المختارة، شريط باقات الخدج الشائعة والسريعة بنقرة واحدة، حقل البحث الذكي مع تصفية فورية وزر مسح سريع واقتراحات Autocomplete، صينية التحاليل المختارة (Chips Tray) مع إمكانية حذف أي تحليل أو إفراغ الكل بنقرة واحدة، حقل ملاحظات وتوجيهات الطبيب السريرية لفني التحاليل، اختيار الأولوية (عادي، عاجل، فوري طارئ STAT 🚨)، شريط تبويبات تصفية الأقسام الطبية والمختارة فقط، وشبكة بطاقات التحاليل الثنائية (Compact 2-Column Grid) مع شارة التأشير والتحديد اللوني والأسعار والأكواد.
    - **محطة الأشعة والسونار (`#radiologyPane`)**: شريط فحوصات التصوير الشائعة لحديثي الولادة (أشعة صدر نقالة بالحاضنة، سونار الدماغ واليافوخ، إيكو قلب وتفقد PDA، أشعة بطن نقالة NEC، وتصوير كامل Babygram)، البحث الذكي، صينية الفحوصات المختارة، ملاحظات الطبيب لقسم الأشعة، الأولوية، تبويبات التصنيفات، والشبكة الثنائية للبطاقات.
    - **تقسيم الشاشة المتوازن**: جنباً إلى جنب (`col-xl-6` لمحطة الطلب و `col-xl-6` لسجل ومتابعة الطلبات والنتائج المركزية والمرفقات ومسار المراحل).
  * **تكامل المرفقات الطبية الحرة وملاحظات الطبيب الاختصاص المثبتة وصلاحيات الأدوار**:
    - تبويب مستقل للمرفقات والمستندات الطبية مع حرية تسمية الملف وحفظه بسجل المريض وأرشفة ملفات PDF والصور.
    - بنر علوي بارز لملاحظات الطبيب الاستشاري وتثبيتها في أعلى محطة الخدج.
    - دعم عرض مرفقات أجهزة فحص المختبر المركزي (Sysmex CBC report / curves) داخل محطة الخدج.
    - هيكلة صلاحيات دقيقة (`nicu_doctor` للأوامر السريرية وملاحظات الاختصاص، `nicu_nurse` لمحطة التمريض وخطة الرعاية MAR).
  * **الاختبارات وضمان الجودة الشاملة**:
    - اجتياز كامل حزمة اختبارات الخدج `NicuWorkflowTest` بنجاح 100% (34 اختباراً، 280 تأكيداً).
    - اجتياز كامل اختبارات دورة العمل المركزية `NicuLabRadiologyCashierWorkflowTest` بنجاح 100% (4 اختبارات، 82 تأكيداً).
    - المجموع: **38 اختباراً ناجحاً بنسبة 100% (362 تأكيداً)**.
    - تحديث الشجرة المعرفية Graphify إلى 5,443 عقدة و 9,923 علاقة.



- **المرحلة 106 (2026-10-27) — رفع شرط العمر عن حجز الخدج بالاستعلامات وربط الحجز التلقائي بالأم أو الأب**:
  * إلغاء شرط فحص العمر (`age < 1`) في شاشة الاستقبال والاستعلامات (`resources/views/inquiry/create.blade.php`).
  * تفعيل بطاقة [ حجز حاضنة خدج ] لكافة المرضى البالغين (الأم أو الأب) مع عبارة إرشادية واضحة `حجز واستقبال مولود بالحاضنة (مرتبط بالأم أو المسؤول)`.
  * اجتياز كامل اختبارات الخدج بنسبة 100%.

- **المرحلة 107 (2026-10-27) — منظومة بيان الولادة، المرفقات بالسكانر، تمييز الطفل المستقل، وعزل رقم ضمان المولود**:
  * **بيان الولادة الرسمي (Birth Certificate)**:
    - إضافة حقول `birth_certificate_no` و `birth_certificate_date` في جدول `incubator_reservations` عبر مايجريشن `2026_10_27_120041_add_birth_certificate_fields_to_incubator_reservations_table.php` وتحديث الموديل.
    - الحقول اختيارية تماماً في شاشة الحجز (`incubator_reservations/create`) ومتاحة للتعبئة إن وجدت.
  * **المرفقات الرسمية بنمط العمليات الجراحية (سكانر + رفع ملف)**:
    - إضافة قسم خاص في شاشة الحجز: وثيقة بيان الولادة، هوية/موحدة الوالدين، وتقرير الإحالة الطبي.
    - دعم المسح الضوئي المباشر من أجهزة السكانر المحلية عبر خوادم السكانر (`http://127.0.0.1:5000/scan` و `37426/scan`) أو رفع ملفات PDF وصور.
    - حفظ المرفقات تلقائياً في جدول `nicu_attachments` لترتبط مباشرة بملف الطفل وإضبارته الطبية.
  * **عزل رقم ضمان الطفل عن رقم ضمان الأم/الأب**:
    - تخزين رقم بطاقة الضمان الصحي للطفل (`insurance_card_no`) في جدول الحجز `incubator_reservations` حصراً دون المساس أو التعديل على سجل الأم/الأب في جدول `patients`.
  * **تمييز الطفل المستقل (محول من الخارج أو مسجل بملف مستقل)**:
    - إضافة عمود `is_independent_patient` وخيار تفاعلي في نموذج الحجز لاختيار صفة المريض (الأم/الأب أو الطفل نفسه).
    - ضبط ذكي وتلقائي: إذا كان عمر المريض < 1 سنة يتم تحديده كطفل مستقل تلقائياً وملء اسمه مباشرة دون كتابة "طفل فلان".
    - إظهار أوسمة خضراء واضحة في لوحة الخدج الحية والمحطة السريرية والأرشيف: `[ 👶 ملف طفل مستقل ]` واسم الأم الحقيقي من حقل `patients.mother_name`.
  * **الاختبارات وضمان الجودة**:
    - إضافة اختبار متكامل `test_nicu_reservation_with_birth_certificate_independent_baby_and_attachments_workflow` بنجاح 100%.
    - اجتياز كامل حزمة الخدج: **37 اختباراً ناجحاً بنسبة 100% (316 تأكيداً)**.

- **المرحلة 120: توحيد ومطابقة محطة تحاليل الطوارئ 100% مع محطة المختبر السريرية (Emergency Lab Station 100% Parity with Standard Lab Request Station & Clinical Workflow)**:
  * **إعادة بناء وتوحيد واجهة محطة تحاليل الطوارئ ([resources/views/staff/requests/emergency-lab-show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/emergency-lab-show.blade.php))**:
    - مطابقة تامة 1:1 لجميع تفاصيل التصميم، البنية الهيكلية، وأدوات العمل الموجودة في `resources/views/lab/show.blade.php`.
    - شريط علوي موحد متكامل يحتوي على أزرار الإجراءات: `[ 🖨️ طباعة التقرير ]`، `[ 💬 إرسال النتيجة عبر واتساب ]`، `[ 💾 حفظ مؤقت (مسودة) ]`، `[ 🚀 اعتماد نهائي وإرسال للطبيب ]`، `[ ➕ إضافة تحاليل ]`، و `[ ⬅️ القائمة ]`.
    - بطاقة بيانات المريض والطلب السداسية بالأيقونات الدائرية الملونة: اسم المريض، رقم الهاتف مع زر واتساب، العمر/الجنس، فصيلة الدم، الطبيب وقسم الطوارئ، وحالة السداد المالي.
    - قسم إلحاق التحاليل الذكي القابل للطي `#appendTestsSection`: عارض باقات التحاليل (Packages)، مجموعات المفضلات، المفضلات الفردية، البحث السريع مع الفلترة العربية، وقائمة الفحوصات المقسمة حسب التصنيف الرئيسي.
    - جدول النتائج السريرية الموحد `#resultsTable` بكافة أعمدته: الرقم، اسم الفحص (مع دعم الفحوصات المركبة والشجرية `subTests`، وفحوصات مرفقات الأجهزة)، النتيجة، الوحدة، المدى المرجعي، مؤشر الحالة الحي (Flag: طبيعي، مرتفع، منخفض)، وحقل مرفق تقرير الجهاز بنمط الكبسولة الأنيقة `pill-upload-wrapper`.
    - شريط ملخص تقييم النتائج اللحظي: عداد النتائج الطبيعية، المرتفعة، المنخفضة، وغير المدخلة.
    - محرك JavaScript الموحد: تقييم النتائج الفوري بالألوان، معادلة فريدوالد لباقة الدهون (Friedewald: VLDL & LDL)، وتنبيه الدهون الثلاثية $\ge 400\text{ mg/dL}$.
  * **التحصين ومعالجة السيرفر ([app/Http/Controllers/StaffRequestController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/StaffRequestController.php))**:
    - تطوير دالة `completeEmergencyLab` لدعم حفظ نمط `test_results` الموحد ونمط `results` الكلاسيكي معاً.
    - معالجة مرفقات الفحوصات الفردية `test_attachments` وإزالتها وحفظ المرفق العام.
    - إضافة دالة `autoCalculateLipidOnTestResults` لحساب معادلات باقة الدهون آلياً على السيرفر.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/UnifiedEmergencyLabStationAndReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/UnifiedEmergencyLabStationAndReportTest.php))**:
    - 6 اختبارات ميزة تغطي أزرار الطوارئ في قائمة المختبر، تحميل الواجهة السريرية المطابقة بالكامل، حفظ واعتماد النتائج بالنمطين، طباعة التقرير الطبي، وإلحاق التحاليل بنجاح تام (`6 passed, 38 assertions`).

- **المرحلة 117: الحساب الآلي الذكي لمعادلات باقة الدهون (Lipid Profile - Friedewald Formula: LDL & VLDL) في واجهة المختبر والسيرفر مع التنبيه السريري والتحصين الشامل**:
  * **تجهيز باقة الدهون وفحص VLDL في قاعدة البيانات**:
    - إنشاء مايجريشن `2026_10_27_112000_seed_lipid_profile_composite_test_and_vldl.php` لتسجيل فحص `Very Low Density Lipoprotein` (VLDL) بمدى مرجعي طبيعي (5 - 30 mg/dL).
    - تسجيل فحص باقة الدهون الشاملة `Lipid Profile` كفحص مركب (`sub_tests`) يحتوي على الفحوصات الفرعية الخمسة الرسمية: Total Cholesterol (100 - 200), Triglyceride (60 - 160), HDL (> 35), LDL Calculated (< 130), VLDL Calculated (5 - 30).
  * **محرك الحساب الآلي اللحظي في واجهة المختبر ([resources/views/lab/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/show.blade.php))**:
    - بناء دالة مطابقة متقدمة مرنة تتعرف على أسماء الفحوصات بالإنجليزية والعربية والرموز.
    - حساب فوري لـ VLDL بمجرد إدخال الدهون الثلاثية ($VLDL = TG / 5$) مع وسم كبسولي `[ ⚡ محسوب: TG / 5 ]`.
    - حساب فوري لـ LDL بمجرد توفر الكوليسترول الكلي والـ HDL والـ TG ($LDL = TC - HDL - \frac{TG}{5}$).
    - إضافة تنبيه سريري تحذيري بارز أعلى الصف عند تجاوز الدهون الثلاثية 400 mg/dL ($TG \ge 400$) بأن معادلة فريدوالد غير دقيقة وينصح بالـ Direct LDL.
    - احترام الإدخال اليدوي إذا قام الفني بتعديل القيمة بنفسه مع إظهار شارة `[ ✍️ مدخل يدوياً ]`.
  * **التحصين ومعالجة السيرفر ([app/Http/Controllers/LabStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabStaffController.php))**:
    - دالة `autoCalculateLipidResults` لضمان حساب LDL و VLDL آلياً وتخزينها في قاعدة البيانات وجداول النتائج والـ JSON عند الحفظ.
  * **حزمة الاختبارات الآلية الحصينة ([tests/Feature/LipidProfileAutoCalculationTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LipidProfileAutoCalculationTest.php))**:
    - 4 اختبارات ميزة تغطي الحساب الدقيق للمعادلة، شرط الدهون الثلاثية 400، الحساب في الفحوصات المفردة والمركبة، وحفظ الإدخال اليدوي بنجاح تام (`4 passed, 25 assertions`).

- **المرحلة 133: دعم خصومات الكاشير الإدارية والخاصة بنسب خماسية (0% - 100%) وحصرها التام خارج الضمان (Cashier Multi-Departmental 5% Step Discounts System & Strict Non-Insurance Enforcement)**:
  * **قاعدة البيانات وموديل الدفعات**: إضافة حقول `discount_percentage`, `discount_amount`, `discount_reason`, و `discount_by` في جدول `payments`. وتطبيق المايجريشن محلياً وعلى هوستنجر (`remote_hostinger`).
  * **كنترولر الكاشير**: شمول أقسام الفحوصات الطبية (`processRequestPayment`)، الطوارئ (`processEmergencyPayment`)، والعمليات الجراحية (`processSurgeryPayment`)، مع عزل صارم وتصفير تام للخصم في حال كان المريض مشمولاً بالضمان الصحي أو قوى الأمن الداخلي.
  * **واجهات الكاشير والاحتساب المباشر**: إضافة صناديق تحكم تفاعلية بنسب خماسية من 0% حتى 100% مع أزرار سريعة وحقل سبب الخصم، وتحديث فوري لمبالغ القبض والإيصالات بجافاسكريبت.
  * **الإيصالات والطباعة الحرارية وPDF**: توثيق سطر تفصيلي يوضح نسبة وقيمة وسبب الخصم قبل خانة الصافي المدفوع.
  * **حزمة الاختبارات الآلية**: إنشاء `CashierDiscountTest.php` واجتياز 4 اختبارات ميزة بنجاح 100% (`4 passed, 37 assertions`).
- **المرحلة 141 (2026-11-01) — نظام نسب التحمل المفصلة لكل خدمة في فواتير الضمان الصحي الموحدة لدى الكاشير (Per-Service Item Copay Enforcement)**:
  * تطبيق نسب التحمل المعتمدة لكل خدمة على حدة في فواتير الضمان الصحي الموحدة (مختبر 25%، أشعة 25%، تخطيط قلب بإحالة 10%، وتخطيط مباشر 100% كاش)، مع تحديث واجهة الكاشير التفاعلية ومحرك الحسابات اللحظية وسندات القبض.
- **المرحلة 143 (2026-11-02) — حصر سجل وتفاصيل طلبات تعزيز المخزون على عهدة الطابق وحماية الصلاحيات (Scoped Floor Stock Requests & Approval Authorization Guard)**:
  * **عزل وحصر سجل الطلبات (`StockTransferController@indexRequests`)**: قفل الاستعلام تلقائياً للكادر التمريضي ومسؤولي العهدة ليعرض حصراً طلبات مخزن عهدتهم المعتمد (`to_location_id = user->location_id`) والطلبات التي رفعوها، مع حجب طلبات بقية الأقسام والمستشفيات، وإبقاء العرض الشامل للإدارة وأمناء المستودع المركزي فقط.
  * **حوكمة واجهة السجل (`stock-transfers/requests/index.blade.php`)**: تخصيص ترويسة الصفحة لتعكس اسم عهدة الطابق، وإضافة زر رجوع ذكي يعيد الممرض لمحطة التمريض والرقود، واستبدال النصوص الإنجليزية بشارات حالة معتمدة (قيد المراجعة / معتمد وتم الصرف / مرفوض).
  * **حماية تفاصيل واعتماد الطلبات (`showRequest`, `approveRequest`, `rejectRequest`)**: منع الممرض من استعراض طلبات أي طابق آخر (403 Forbidden)، وحظر أزرار وإجراءات الموافقة والرفض على الممرضين وقصرها برمجياً على أمين المخزن الرئيسي ومديري النظام.
  * **حزمة الاختبارات الآلية (`FloorCustodianStockTransferTest.php`)**: تغطية كاملة شملت قفل المخزن، رفض التلاعب، فحص قفل السجل على الطابق، ومنع الدخول والاعتماد غير المصرح (5 passed, 19 assertions).
- **المرحلة 144 (2026-11-02) — تمكين وتأمين تعديل وإلغاء طلبات تعزيز المخزون المعلقة لكادر التمريض والعهدة (Nursing Station Sub-Store Pending Request Edit & Cancel Workflow)**:
  * **مسارات التعديل والإلغاء (`routes/web.php`)**: إضافة مسارات التعديل والتحديث والحذف (`requests.edit`, `requests.update`, `requests.destroy`).
  * **التحكم والتحصين الأمني (`StockTransferController`)**:
    - بناء دوال `editRequest`, `updateRequest`, و `destroyRequest`.
    - قفل الإجراء برمجياً على الطلبات المعلقة (`status === 'pending'`) ومنع أي تعديل أو إلغاء بعد الموافقة والصرف أو الرفض.
    - حصر الصلاحية بمقدم الطلب أو مسؤول عهدة الطابق المعتمد أو مدير النظام (403 Unauthorized لأي طرف آخر).
    - التحقق الصارم من توفر الكميات المطلوبة في المخزن الرئيسي وإرجاع تنبيهات واضحة في حال وجود نقص.
  * **واجهة تعديل الطلبية (`resources/views/stock-transfers/requests/edit.blade.php`)**:
    - تصميم الواجهة بالكامل بنمط البحث الذكي المريح والمطابق لتشخيص الأمراض ICD-10 (قائمة عائمة، شارة الكود، شارة الوحدة، تنقل بالأسهم).
    - استرجاع وتعبئة المواد والكميات المسجلة مسبقاً تلقائياً في الجدول مع أزرار الزيادة والنقصان السريعة (+ / -) وزر مسح سريع.
    - إمكانية إضافة مواد جديدة وحذف أسطر مع الحفاظ على مادة واحدة على الأقل.
  * **حوكمة واجهتي السجل والتفاصيل (`requests/index.blade.php`, `requests/show.blade.php`)**:
    - إضافة أزرار الإجراءات السريعة `[ ✏️ تعديل ]` و `[ 🗑️ إلغاء ]` في جدول سجل الطلبات وفي رأس وبطاقة تنبيه تفاصيل الطلب للطلبات المعلقة فقط.
  * **حزمة الاختبارات الآلية الشاملة (`tests/Feature/FloorCustodianStockTransferTest.php`)**:
    - تغطية 100%: فحص فتح شاشة التعديل بالمواد السابقة، نجاح التحديث، حظر تعديل الطلبات المعتمدة، نجاح إلغاء وحذف الطلب، ومنع التعديل أو الحذف من طوابق أخرى (10 passed, 40 assertions).
- **المرحلة 149 (2026-11-03) — ترقية وتحديث تصميم تقرير طباعة تحاليل العمليات الجراحية لمطابقة تقرير المختبر السريري الرسمي (Surgery Lab Tests Official Clinical Print Report Modernization)**:
  * **إعادة بناء قالب الطباعة بالكامل لمطابقة المختبر العام (`resources/views/staff/surgery-lab-tests/print.blade.php`)**:
    - استبدال التصميم القديم (ذي الزخارف البنية وإطار 2025 والتنسيق البدائي) بالتصميم الطبي السريري المعتمد من المستشفى والمطابق لتقرير `staff/requests/print.blade.php`.
    - ترويسة رسمية ثنائية اللغة للمستشفى وشعار مائي خفيف بالخلفية (`images/1.jpg`) مع شريط عنوان التقرير المتدرج: `تقرير الفحوصات المخبرية للعمليات الجراحية (SURGERY CLINICAL LABORATORY REPORT)`.
    - بطاقة سريرية رمادية ثلاثية المناطق (3-Zone Clinical Card):
      1. المنطقة اليسرى: اسم المريض بالعربية والإنجليزية (عبر `transliterateArabicName`)، العمر والجنس، ورقم الهاتف.
      2. المنطقة الوسطى: اسم الجراح المعالج، نوع العملية الجراحية، الموقع السريري ورقم الغرفة والطابق `[ 🛏️ غرفة 302 (الطابق الثالث) ]`، وتاريخ ووقت الفحص.
      3. المنطقة اليمنى: باركود ورمز استجابة سريعة مباشر QR Code للتحقق اللحظي من الفحص.
    - جدول تحاليل موحد ومبوب بالأقسام الرئيسية (Categories)، يعرض: اسم الفحص والرمز، النتيجة، الوحدة، المدى المرجعي المحسوب آلياً حسب جنس وعمر المريض (`LabTestReference::forPatient`)، وتقييم الحالة السريرية التفاعلي بشارات ملونة (`Normal`, `High`, `Low`, `Abnormal`, `Pending`).
    - دعم الفحوصات الفرعية (Sub-tests) وقسم التوصيات والملاحظات السريرية (`Clinical Notes & Recommendations`).
    - كتلة التواقيع والاعتماد الرسمي المزدوج (أخصائي المختبر + الختم الدائري المعتمد لمستشفى الفرات الأهلي).
    - شريط أدوات طباعة عائم متكامل: زر طباعة فوري، زر إرسال التقرير عبر واتساب، مبدل وضعية الطباعة (ورق أبيض كامل Full مقابل ورق مروس رسمي Letterhead مع تحكم بالمسافة العلوية)، وزر رجوع.
  * **تطوير دالة الكنترولر (`app/Http/Controllers/StaffRequestController.php`)**:
    - ترقية دالة `printSurgeryLabTest` لتحميل كافة العلاقات السريرية (`surgery.patient.user`, `surgery.doctor.user`, `surgery.room`, `surgery.department`, `labTest.references`, `labTest.subTests`).
    - إزالة قيود الحظر المسبق وتمكين طباعة الفحوصات سواء كانت مكتملة أو جارية أو كأمر عمل وتكليف مخبري (Requisition / Work Order).
  * **حزمة الاختبارات الآلية الشاملة (`tests/Feature/SurgeryLabTestUnifiedFlowTest.php`)**:
    - إضافة اختبار `test_print_page_renders_modern_clinical_report_with_patient_surgery_and_test_details` للتحقق من سلامة مسار الطباعة، وعرض بيانات المريض والجراح والغرفة والنتائج والملاحظات ومطابقة الهوية البصرية (8 passed, 49 assertions).
- **المرحلة 150 (2026-11-03) — تحديث واجهة سداد العمليات الجراحية ودعم الدفع المركب (كاش + بطاقة) وشمول التحاليل الروتينية ضمن أتعاب العملية (Surgery Cashier Split Payment & Included Routine Pre-Op Tests Flow)**:
  * **شمول التحاليل الروتينية للعملية ضمن أتعاب الجراحة مجاناً (`0 د.ع`)**:
    - معاملة التحاليل المرافقة للعملية الجراحية كأول ليلة في الغرفة بحيث تكون مشمولة حكماً ضمن أتعاب الجراحة دون إضافة أي تكاليف جديدة على المريض (`0 د.ع`).
    - إظهار شارة خضراء مميزة في جدول الكاشير: `[ 🧪 مشمول ضمن باقة العملية (0 د.ع) ]`، مع تأشيرها تلقائياً لتسديدها وربطها برقم الإيصال (`payment_id`) لتتحول إلى `paid` مباشرة في المختبر وتأكيد الدفع دون زيادة في حصة المريض.
  * **دعم الدفع المركب / المختلط (Split Payment: كاش + بطاقة ائتمان / POS)**:
    - إضافة حقلي `cash_amount` و `card_amount` في جدول `payments` وتوسيع `payment_method` ليشمل خيار `split`.
    - ظهور صندوق تفاعلي ذكي فوري ومباشر بمجرد اختيار `🔄 دفع مركب / مختلط (مقسم كاش + بطاقة)` يتيح توزيع المبلغ بين النقد والبطاقة مع زر المناصفة (50/50) ومزامنة آلية وشارة تحقق وتطابق لحظية (`متطابق: X IQD`).
    - إبراز تفاصيل الدفع المركب في الإيصالات وسندات القبض الحرارية وشاشات العرض (`دفع مركب: نقداً X د.ع | بطاقة Y د.ع`).
  * **ترقية وتحديث واجهة السداد ومطابقتها لواجهة تسديد الطلبات (`resources/views/cashier/surgeries/payment-form.blade.php`)**:
    - ترويسة متدرجة حديثة وبطاقة سريرية لمعلومات المريض ونوع العملية ونسبة التحمل للضمان الصحي الوطني وقوى الأمن (HI / MOI).
    - دعم تسديد الرسوم جزئياً (دفعة مقدمة) أو كلياً مع الحساب اللحظي للمتبقي المؤجل.
  * **حزمة الاختبارات الآلية الشاملة (`tests/Feature/SurgeryCashierSplitAndInsurancePaymentTest.php`)**:
    - 5 اختبارات ناجحة بنسبة 100% (27 assertions): فحص الواجهة وظهور شارة شمول التحاليل، تسديد الضمان ووسم التحاليل كمدفوعة، تسديد الدفع المركب بنجاح وظهوره في الوصل، رفض الدفع المركب عند عدم تطابق المجموع، والتسديد الجزئي مع بقاء المتبقي معلقاً.

- **المرحلة 148 (2026-11-03) — إلغاء ازدواجية التبويبات وتوحيد شاشة تحاليل العمليات بجدول واحد ومطابقة واجهة إدخال النتائج للمختبر العام (Surgery Lab Tests Single Unified Table & General Lab Twin Results Entry Flow)**:
  * **توحيد شاشة تحاليل العمليات بجدول واحد موحد (`resources/views/staff/surgery-lab-tests/index.blade.php` & `StaffRequestController@surgeryLabTests`)**:
    - إلغاء التبويبين المنفصلين ودمج كافة مرضى العمليات الجراحية في جدول واحد موحد ومرن مطابق لشاشة المختبر العام `/lab`.
    - تزويد الواجهة بشريط فرز لحالة الفحص بنمط الـ Pills: `الكل`، `🟡 بانتظار تحديد الفحوصات`، `🟠 بانتظار الفحص / معلق`، و `🟢 المكتملة والمعتمدة` مع أزرار التاريخ (`اليوم` | `كافة التواريخ`) ومربع البحث السريع.
    - عرض الموقع السريري للمريض بوضوح `[ 🛏️ غرفة 302 (الطابق الثالث) ]` وحالة دفع الرسوم، مع زر ذكي تفاعلي: `[ 🧪 تحديد باقة التحاليل ]` للعمليات المحجوزة حديثاً، وزر `[ 👁️ إدخال النتائج / معاينة ]` مع زر الطباعة للعمليات المحددة تحاليلها مسبقاً.
  * **مطابقة واجهة إدخال النتائج لشاشة المختبر العام (`resources/views/staff/surgery-lab-tests/show.blade.php`)**:
    - شريط إجراءات علوي وسفلي متكامل: `[ 💾 حفظ مؤقت (مسودة) ]` (حفظ النتائج وإبقائها كمسودة قيد الفحص)، `[ 🚀 اعتماد نهائي وإرسال ]` (اعتماد رسمي وختم وقت الإكمال)، `[ ➕ إضافة تحاليل ]` (إلحاق فحوصات جديدة دون حذف النتائج السابقة عبر نافذة منبثقة Modal)، `[ 🖨️ طباعة التقرير ]`، `[ 📲 إرسال النتيجة عبر واتساب ]`، وزر العودة للقائمة.
    - بطاقة المريض والعملية والموقع السريري المدمجة والمطابقة تماماً لـ `/lab`.
    - جدول النتائج السريري مع استرجاع المرجع الطبيعي المعتمد لكل فحص آلياً استناداً لجنس وعمر المريض عبر `LabTestReference::forPatient()`، ووحدة القياس، والتقييم اللحظي التفاعلي بالألوان (طبيعي / مرتفع / منخفض / غير مكتمل)، وشريط إحصاءات النتائج المباشر، وتنبيهات معادلة فريدوالد لباقة الدهون.
  * **توسيع مايجريشن قاعدة البيانات (`database/migrations/2026_11_03_002201_add_notes_to_surgery_lab_tests_table.php`)**:
    - إضافة عمود الملاحظات السريرية `notes` في جدول `surgery_lab_tests` وتوسيع حقل الحالة `status` ليشمل `in_progress`.
  * **حزمة الاختبارات الآلية الشاملة (`tests/Feature/SurgeryLabTestUnifiedFlowTest.php`)**:
    - تغطية شاملة لتدفق العمل الموحد: التحقق من الجدول الموحد، بدء تحديد الباقة، تأكيد التحاليل والتحويل لصفحة النتائج، حفظ المسودة والاعتماد النهائي، إلحاق تحاليل جديدة، وظهور تنبيه المختبر العام (6 passed, 32 assertions).
- **المرحلة 147 (2026-11-02) — توحيد وربط منظومة تحاليل العمليات الجراحية بالمختبر العام وحل فراغ شاشة تحاليل العمليات (Surgery Lab Tests Unified Tabs & General Laboratory Direct Integration)**:
  * **توحيد شاشة تحاليل العمليات (`StaffRequestController@surgeryLabTests` & `resources/views/staff/surgery-lab-tests/index.blade.php`)**:
    - حل مشكلة ظهور شاشة `staff/surgery-lab-tests` فارغة بعد حجز العمليات من الاستعلامات؛ حيث كانت تشترط وجود تحاليل محددة مسبقاً (`whereHas('labTests')`).
    - بناء نظام تبويبين (Tabs) مدمجين ومتجاوبين:
      1. **تبويب 1: عمليات بانتظار تحديد التحاليل (`tab=pending_selection`)**: يعرض العمليات المحجوزة حديثاً مع اسم المريض ورقم الغرفة والطابق (`غرفة 302 - الطابق الثالث`) والجراح وموعد العملية وحالة العملية، مع زر بارز ومباشر `[ 🧪 تحديد باقة التحاليل ]` ينقل موظف المختبر لشاشة اختيار الفحوصات بضغطة زر واحدة.
      2. **تبويب 2: طلبات الفحوصات والنتائج (`tab=active_tests`)**: يعرض سجل التحاليل الجارية والمكتملة وإدخال النتائج والطباعة وإحصائيات الحالات.
    - التحويل الذكي التلقائي: تفتح الصفحة افتراضياً على العمليات بانتظار التحديد إذا لم تكن هناك طلبات جارية منعاً لظهور صفحة فارغة مطلقاً.
  * **الربط مع شاشة المختبر الرئيسية اليومية (`LabStaffController@index` & `resources/views/lab/index.blade.php`)**:
    - إضافة بطاقة تنبيه ذكية ملفتة في أعلى شاشة المختبر `/lab`: `[ تنبيه: يوجد X عمليات جراحية محجوزة بانتظار تحديد باقة التحاليل! ]` مع زر تحويل فوري للصفحة المخصصة.
    - إضافة زر تحويل سريع في ترويسة الصفحة بجانب دليل التحاليل بشارة عدد العمليات المعلقة.
  * **تحديث شارات القائمة الجانبية (`AppServiceProvider` & `layouts/app.blade.php`)**:
    - تحديث عداد شارة `تحاليل العمليات` في القائمة الجانبية ليشمل مجموع الفحوصات المعلقة والعمليات المحجوزة بانتظار التحديد لضمان عدم إغفال أي مريض.
  * **إصلاح مايجريشن حقول الدفع (`2026_01_13_063756_add_payment_fields_to_surgery_lab_tests_table.php`)**:
    - إضافة أعمدة `payment_status` و `payment_id` في مايجريشن جدول `surgery_lab_tests`.
  * **حزمة الاختبارات الآلية الشاملة (`tests/Feature/SurgeryLabTestUnifiedFlowTest.php`)**:
    - فحص ظهور المريض المحجوز حديثاً في تبويب تحديد التحاليل مع بيانات الغرفة والطابق.
    - فحص نجاح بدء اختيار التحاليل والتحويل لصفحة الفحوصات.
    - فحص ظهور بطاقة التنبيه والعداد في واجهة المختبر العامة (3 passed, 17 assertions).
- **المرحلة 146 (2026-11-02) — ربط دورة صالة العمليات وقائمة الانتظار بمحطة التمريض والرقود وإظهار غرف الطوابق تلقائياً (Nursing Station Surgery Lifecycle Sync & Floor Ward Auto-Inference)**:
  * **تحرير مسار الحالات في محطة التمريض (`NursingStationController`)**:
    - حل مشكلة اختفاء المرضى المحجوزين عند تسجيل الدخول `checkIn` (حالة `waiting`) أو بدء العملية `start` (حالة `in_progress`)، حيث تم توسيع استعلام ما قبل الصالة (`preOpQuery`) ليشمل الحالات في قائمة الانتظار وقيد التجهيز طالما لم تنتهِ الصالة بعد.
    - فك الارتباط الحصري بين متابعة ما بعد الصالة (`postOpQuery`) ومحطة التخدير فقط، ليشمل تلقائياً اكتمال محطة صالة العمليات (`operationTheaterStation->completed`) التي يتم التحكم بها مباشرة من قائمة انتظار العمليات (`surgeries/waiting`)، أو اكتمال التخدير، أو اكتمال العملية، لتمكين ممرض الطابق من تسجيل العلامات الحيوية وصرف علاجات ومستهلكات الرقود فور خروج المريض من الصالة.
    - الاستنتاج الذكي لطابق الممرض تلقائياً من مخزن عهدته المعتمد (`ردهة الطابق الثالث` ⬅️ `الطابق الثالث`) لفتح مرضى وغرف طابقه مباشرة دون الحاجة للبحث اليدوي.
  * **حوكمة واجهة محطة التمريض (`resources/views/surgery-stations/nursing/index.blade.php`)**:
    - إبراز رقم الغرفة والطابق بشكل لافت وواضح تحت اسم المريض في كافة الجداول (ما قبل الصالة، ما بعد الصالة، والأرشيف) بشارة: `[ 🛏️ غرفة 302 (الطابق الثالث) ]`.
    - إضافة شريط فرز سريع للطوابق (`[ كافة الطوابق | الطابق الثاني | الطابق الثالث (طابقك) ]`) مع شارة مميزة لطابق عهدة الممرض لسهولة التنقل السريع عند الحاجة.
    - استبدال النصوص والشارات المبهمة بشارات حالة دقيقة (`بانتظار الصالة (مسجل دخول)`، `قيد التجهيز / الصالة`، `خرج من صالة العمليات`، و `متابعة ورعاية جارية`).
  * **حزمة الاختبارات الآلية الشاملة (`tests/Feature/NursingStationSurgeriesTest.php`)**:
    - فحص ظهور المريض المحجوز في الغرفة 302 لممرضة الطابق الثالث مريم.
    - فحص بقاء العملية في مرحلة ما قبل الصالة عند تسجيل الدخول وتحولها إلى `waiting`.
    - فحص انتقال العملية تلقائياً إلى تبويب ما بعد الصالة (المتابعة) عند اكتمال صالة العمليات على `surgeries/waiting` (3 passed, 13 assertions).
- **المرحلة 166 (2026-11-05) — إعادة التصميم العصرية لشاشة قائمة انتظار وتدفق العمليات الجراحية كلوحة تحكم ذكية وبطاقات مؤشرات تفاعلية (Smart OR Flight Board Modern Redesign, Interactive KPI Filters & Clean Stage Progression)**:
  * **إعادة تصميم عصرية شاملة لشاشة قائمة انتظار العمليات ([resources/views/surgeries/waiting.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/waiting.blade.php))**:
    - **ترويسة تنفيذية فائقة الأناقة (Executive Header)**: أيقونة الصالات المتدرجة مع عنوان ناصع وشارة `OR Flight Board`، ومؤشر للتوقيت المباشر للمركز، وأزرار إجراءات تنفيذية أنيقة (حجز عملية جديدة بتدرج أزرق ملكي، والأرشيف العام، وتحديث فوري).
    - **شريط تقويم أسبوعي تفاعلي (Apple/Tailwind Segmented Strip)**: كبسولات أيام رشيقة بحدود ناعمة وتأثيرات رفع بصرية، تعرض اليوم والتاريخ وعدد العمليات، مع تمييز اليوم النشط بتدرج كحلي داكن فاخر، ونقطة ذهبية متوهجة لليوم الحالي.
    - **لوحة مؤشرات سريعة تفاعلية (Interactive KPI Stat Cards)**: 5 بطاقات إحصائية للمسار: (إجمالي عمليات اليوم، مجدولة، بالانتظار - تحضير، داخل الصالة - جارية، ومكتملة - خرجت من الصالة). النقر على أي بطاقة يفلتر جدول العمليات فوراً لإظهار الحالات المطابقة مع تمييز البطاقة النشطة وتحديث عداد العمليات الظاهرة دون إعادة تحميل الصفحة.
    - **اكتمال العملية وخروجها من الصالة**: فور ضغط "إنهاء"، يتم حفظ `status = 'completed'` و `end_time = now()` في قاعدة البيانات، وتتحول الشارة إلى `[ ✔️ مكتملة (خرجت من الصالة) ]` وشارة الإجراءات `[ ✔️ عملية مكتملة ]`، وينقص عداد الصالة ويزيد عداد المكتملة تلقائياً.
    - **شريط بحث فوري ذكي**: حقل بحث مدور مع أيقونة وعدّاد حي للحالات المعروضة نسبةً للإجمالي (`عرض X من أصل Y عملية`).
    - **جدول الصالات الذكي (Master Flight Board Table)**: بطاقة المريض مع الرمز والصورة ورقم الملف، تفاصيل العملية، الصالة المخصصة مع التحديد المباشر وإضافة صالة فورية، الوقت مع مؤقت عد تنازلي حي للعمليات الجارية، وحصر الإجراءات بأزرار تقدم المسار حصراً (`دخول`، `تحويل وبدء`، و `إنهاء` الذي يختفي ويتحول إلى `تمت الصالة` فور انتهاء الصالة).
  * **حوكمة صلاحية إعدادات تبويبات المحطة السريرية**:
    - تخصيص الصلاحية الرسمية `manage surgery tab permissions` لإدارة مصفوفة التبويبات السريرية.
    - إدراج الرابط في القائمة الجانبية فقط لمن يملك الصلاحية وإزالته تماماً من ترويسة قائمة الانتظار للمحافظة على نظافة لوحة العمليات.
  * **حزم الاختبارات الآلية الشاملة**:
    - اجتياز اختبارات شاشة قائمة الانتظار `tests/Feature/SurgeryWaitingListFlightBoardTest.php` بنسبة 100% (8 passed, 28 assertions).
    - اجتياز اختبارات المحطة السريرية ومصفوفة الصلاحيات `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` بنسبة 100% (11 passed, 44 assertions).

- **المرحلة 165 (2026-11-05) — مزامنة انتقال المريض لمحطة المقيم ما بعد الصالة وترشيق أزرار وشارات قائمة انتظار العمليات (Resident Post-Op Lifecycle Sync & Waiting List Complete Button Refinement)**:
  * مزامنة محطة المقيم مع التمريض فور اكتمال صالة العمليات.
  * إخفاء زر إنهاء فور اكتمال الصالة وتوقف العداد وتحول الشارة لـ `[ 🏁 خرج من الصالة (متابعة) ]`.

- **المرحلة 164 (2026-11-05) — صرف التمريض المباشر من عهدة مخزن الطابق والربط التلقائي بغرفة المريض (Nursing Floor Sub-Store Direct Patient Dispensing)**:
  * الاستنتاج التلقائي لمخزن عهدة الطابق من `room->floor` وربطه بالعملية والحركات المخزنية والعلاجات.
  * اجتياز `NursingFloorDispenseTest` (4 passed, 27 assertions).

- **المرحلة 157 (2026-11-04) — القضاء على التمرير العمودي وهيكلة المحطة السريرية واستبيان الرقود بنظام Fit-to-Screen والتمرير الداخلي المنضبط (Clinical Station Compact Fit-to-Screen Architecture & Zero Whole-Page Scroll)**:
  * **ضغط وتكثيف الترويسة والشريط القيادي السريري (Clinical Executive Strip)**:
    - تقليص ارتفاع شريط بيانات المريض والعملية بنسبة 60% ليصبح شريطاً تنفيذياً رشيقاً موحداً (ارتفاع ~45px فقط) يضم اسم المريض، رقم الملف، العمر والجنس، نوع العملية، الجراح، الصالة/الغرفة، التوقيت، وأزرار تبديل المرحلة (Pre-Op / Post-Op) وشارة الحالة على سطر واحد متناسق.
    - تقليص حجم الـ Avatar إلى 34px وهوامش الترويسة من `16px 22px` إلى `8px 16px`، مع تنحيف شريط المسار السريع (Breadcrumb).
  * **إعادة موازنة الأعمدة والتخطيط (Sidebar 25% : Workspace 75%)**:
    - تعديل التقسيم العرضي إلى `col-md-4 col-lg-3` للشريط الجانبي العمودي و `col-md-8 col-lg-9` لمساحة العمل النشطة، مما منح مساحة العمل 75% من عرض الشاشة لمنع التفاف الأسطر وتقليص الارتفاع الرأسي للفورمات والجداول بنسبة 40%.
    - تكثيف تبويبات القائمة الجانبية بحجم خط `0.81rem` وحشوة `6px 10px` مع تثبيت الشريط الجانبي في أعلى الشاشة (`sticky-top`).
  * **دمج أزرار إجراءات استبيان الرقود في رأس البطاقة وإلغاء التكرار**:
    - نقل أزرار استبيان الرقود الحيوية (`طباعة A4`، `نموذج فارغ`، `كرت QR حراري`، و `إعادة فتح الاستبيان` للأدمن) مباشرة إلى يمين رأس بطاقة التبويب (`pane-header`) بجوار شارة الحالة (`مكتمل 94%` أو `بانتظار التقييم`).
    - إزالة البطاقة الداكنة التكرارية التي كانت تستهلك 130px رأسياً دون داعٍ.
  * **ترشيق بطاقة الـ QR وجدول التقييم اليدوي وملخص النتائج**:
    - تصغير رمز الـ QR من 190px إلى 110px مع الحفاظ على وضوحه التام للمسح بالكاميرا، ودمج أزرار التكبير بملء الشاشة والفتح المباشر ورابط الاستبيان في بطاقة مدمجة بالعمود الأيمن (`col-lg-4`).
    - تكثيف صفوف جدول التقييم اليدوي الخماسي (`.table-survey`) بحشوة مدمجة `4px 6px`، وتقليص حقول الملاحظات إلى سطر واحد مع زر حفظ رشيق بالعمود الأيسر (`col-lg-8`).
    - عرض نتائج التقييم المكتملة في شبكة بطاقات وشارات رشيقة بحجم خط `0.78rem`.
  * **القضاء على سكرول المتصفح الكامل عبر حاوية التمرير الداخلي المنضبط (`.clin-workspace-scroll`)**:
    - حصر أي تمرير رأسي طويل داخل بطاقة مساحة العمل فقط عبر `max-height: calc(100vh - 180px); overflow-y: auto;` بشريط تمرير أنيق ونحيف (5px).
    - تثبيت شريط إنهاء المرحلة السريرية (`Pre-Op / Post-Op Completion`) بشكل دائم في أسفل البطاقة ليظل مرئياً ومتاحاً للنقر الفوري بدون أي حاجة للنزول بالسكرول.
  * **حزمة الاختبارات الآلية الشاملة**:
    - اجتياز اختبارات المحطة السريرية والصلاحيات بنسبة 100%: `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` (9 passed, 39 assertions).
    - اجتياز اختبارات استبيان الرقود والـ QR والطباعة بنسبة 100%: `tests/Feature/InpatientRoomSurveyTest.php` (8 passed, 48 assertions).

- **المرحلة 145 (2026-11-02) — حظر تعديل وحذف طلبات المخازن الفرعية على أمين المخزن الرئيسي وقصر دوره على الاعتماد أو الرفض الرقابي (Storekeeper Governance Guard & Strict Requester-Only Modification Lock)**:
  * **حوكمة الرقابة المخزنية وفصل الصلاحيات (`StockTransferController`)**:
    - حجب أزرار `[ ✏️ تعديل ]` و `[ 🗑️ إلغاء ]` عن أمين المخزن الرئيسي في سجل وتفاصيل طلبات المخازن الفرعية.
    - حظر مسارات وعمليات الـ `PUT` والـ `DELETE` برمجياً في `StockTransferController` على أمين المخزن وإرجاع `403 Forbidden`.
    - حصر التعديل والإلغاء حصراً على مقدم الطلب أو مسؤول عهدة الطابق المستلم، أو الإدارة العليا التقنية (`super_admin`).
    - الإبقاء على صلاحية أمين المخزن الرقابية فقط: `الموافقة وصرف المواد` أو `رفض الطلب` مع ذكر السبب لحفظ أثر التدقيق (Audit Trail) ومنع تضارب العهدة الجردية.
  * **حزمة الاختبارات الآلية الشاملة (`tests/Feature/FloorCustodianStockTransferTest.php`)**:
    - توسيع حزمة الاختبارات لتشمل `test_warehouse_manager_cannot_edit_or_delete_sub_store_requests` والتحقق من عدم ظهور الأزرار ورفض محاولات الـ PUT/DELETE بـ 403 (11 passed, 52 assertions).

- **المرحلة 169 (2026-11-05) — معالجة ظهور عمليات محطة الجراح، فك ازدواجية سجلات الأطباء، وتحديث واجهة المحطة السريرية (Surgeon Station Identity Multiplicity Resolution, Direct Action Link & Modern Executive Redesign)**:
  * **معالجة تصفية وصلاحيات محطة الجراح ([app/Http/Controllers/SurgeonStationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgeonStationController.php))**:
    - إضافة دالة `getSurgeonDoctorIds($user)` لجمع كافة معرّفات الطبيب المرتبطة بحساب المستخدم (بما فيها معرّف الطبيب الأساسي، معرّف الطبيب من `user_id`، ومعرّفات الأطباء المطابقة لنفس الاسم بعد تجريد البادئات `د.` و `دكتور`)؛ مما يعالج جذرياً مشكلة الازدواجية التاريخية للأطباء المستوردين.
    - تحديث استعلام العمليات في `index()` واستعلام التحقق في `authorizeSurgeon()` لاستخدام `whereIn('doctor_id', $surgeonDoctorIds)`.
  * **إدراج الأطباء الجراحين في نماذج العمليات ([app/Http/Controllers/SurgeryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgeryController.php))**:
    - توسيع استعلام الأطباء في `create()` و `edit()` ليشمل كل طبيب يحمل دور الجراح (`role = 'الجراح' / 'surgeon' / 'جراح'`) بالإضافة إلى النوع والتخصص.
  * **إعادة تصميم واجهة محطة الجراح وإضافة زر الدخول المباشر ([resources/views/surgery-stations/surgeon/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/surgeon/index.blade.php))**:
    - إضافة زر الإجراء السريري الأساسي `[ 🩺 فتح المحطة ]` (`route('surgeon-station.show', $surgery)`).
    - إضافة ترويسة تنفيذية فخمة، بطاقات مؤشرات KPI ذكية (إجمالي العمليات، بانتظار المعاينة، قيد المتابعة، ومكتملة ومُعتمدة)، وتنسيق جدول رشيق وحديث.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/SurgeonStationAccessAndWorkflowTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeonStationAccessAndWorkflowTest.php))**:
    - 3 اختبارات تغطي: ظهور العمليات بعد إتمام الصالة، معالجة ازدواجية حسابات الجراحين، وحماية الصلاحيات بنجاح 100% (`3 passed, 10 assertions`).

- **المرحلة 171 (2026-11-07) — توحيد جدول دفع العمليات الجراحية مطابقةً لواجهة الطلبات الطبية وكبسولات الشمول المنفصلة السداسية الأعمدة (Surgery Cashier Payment Table Modern Redesign & Segmented Action Pills Parity)**:
  * **إعادة تصميم جدول دفع العمليات الجراحية بستة أعمدة موحدة ([resources/views/cashier/surgeries/payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
    - مطابقة هيكلية التصميم وتجربة المستخدم لشاشة تسديد الطلبات الطبية (`request-payment-form.blade.php`) بستة أعمدة موحدة: (`# / التحديد`، `البند والخدمة`، `السعر المعتمد`، `تحمل المريض (نقداً)`، `حصة الضمان (ذمة)`، `الإجراء والشمول بالباقة`).
    - دمج كبسولات التحكم العصرية (iOS/Apple Segmented Control Pills): `[ دفع كامل ]` vs `[ دفع جزء ]` للعملية والغرفة، و `[ 📦 مشمول ]` (أخضر زمردي مع شارة 0 د.ع) vs `[ 💵 منفصل ]` (أزرق ملكي) للمختبر والأشعة والخدمات الإضافية.
    - المحافظة على التعديل المباشر للأسعار `extra-service-price-input`، مع ربط مستمعي الأحداث لإعادة احتساب وتحديث خانات تحمل المريض ومطالبة الضمان وتذييل الجدول `tfoot` لحظياً.
    - استدعاء `attachDefaultExtraServices()` تلقائياً في `showSurgeryPaymentForm` للعمليات النشطة لضمان توفر الخدمات الافتراضية للعمليات القديمة، وفك حظر شاشة الدفع إذا وُجدت أي بنود أو خدمات معلقة.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/SurgeryExtraServicesTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryExtraServicesTest.php))**:
    - اجتياز كامل اختبارات التحقق من بنية الجدول الموحد وكبسولات الشمول والإلحاق الرجعي بنسبة 100% (`8 passed, 47 assertions`)، واستمرار اجتياز كافة اختبارات دفع وتجزئة العمليات والضمان (`15 passed, 98 assertions`).

- **المرحلة 179 (2026-11-08) — قفل إضبارة الخديج عند الخروج للأرشفة والمطالعة فقط، أرشفة سجلات المرضى المخرجين، ونقل عمر الحمل ووزن الطفل لداخل الإضبارة (NICU Discharged Baby Chart Lockdown, Medical Archive Segregation & Clinical Birth Profile Relocation)**:
  * **قفل سجل وإضبارة الطفل الخديج عند خروجه (Zero-Mutation Discharged Chart Lockdown)**:
    - عندما تكون حالة الطفل مخرجاً (`status === 'discharged'`): تتحول محطة الرعاية السريرية الشاملة (`/nicu/reservations/{reservation}`) إلى وضع الأرشفة والمطالعة فقط (`Read-Only Mode`).
    - إبراز بنر تحذيري تنفيذي فاخر في أعلى الصفحة يوضح تاريخ وتوقيت الخروج، نوع الخروج، وقفل الإجراءات الطبية.
    - إخفاء نماذج التوثيق والطلب عبر كامل التبويبات السريرية (تسجيل العلامات الحيوية، سجل إعطاء الأدوية MAR، طلب الاستشارات السريرية، إرسال تحاليل المختبر، طلب فحوصات الأشعة، صرف وتعديل الوصفات الطبية، إضافة وتعديل جداول الرعاية والتغذية، ملاحظات الطبيب الاختصاص، رفع المرفقات، وتبديل الباقات).
    - توسيع جداول السجلات والنتائج المؤرشفة بعرض الصفحة الكامل (`col-12`) لتقديم واجهة أرشيف سريرية ناصعة ومريحة، مع الحفاظ على إمكانية استعراض التقارير التاريخية، تحميل المرفقات، والطباعة.
    - حماية السيرفر الصارمة: حظر كافة عمليات الإضافة أو التعديل في `NicuController` عبر 15 تابعاً سريرياً وإعادة التوجيه مع رسالة تنبيهية تمنع أي تلاعب.
  * **قائمة السجلات وأرشفة المرضى (Discharged Babies Archive View)**:
    - في صفحة سجل وإضبارات الخدج (`/nicu/reservations`): إضافة كبسولات تنقل علوية تفاعلية (`الأطفال بالحاضنات (الحالات النشطة)`، `السجلات وأرشفة المرضى`، `قيد الانتظار`، و`كافة الإضبارات`).
    - عزل الأطفال المخرجين تلقائياً ونقلهم إلى تبويب "السجلات وأرشفة المرضى" (`view=archived`) دون إشغال قائمة الحاضنات النشطة.
    - إضافة رابط وصول مباشر في القائمة الجانبية للنظام (`السجلات وأرشفة المرضى`) تحت قسم وحدة رعاية الأطفال والخدج (NICU).
    - تخصيص زر المعاينة للأطفال المخرجين ليظهر بأيقونة العين كـ "معاينة إضبارة الطفل المؤرشفة (للاطلاع والمطالعة فقط)".
  * **نقل عمر الحمل ووزن الطفل إلى داخل الإضبارة مع حوكمة القفل والتعديل المشروط**:
    - إزالة حقلي `عمر الحمل (gestational_age)` و`وزن الطفل (birth_weight)` من استمارة حجز الحاضنة وتسجيل الدخول الأولي (`/incubator-reservations/create`)، واستبدالهما بتنبيه سريري يوضح تسجيلهما لاحقاً بدقة داخل الإضبارة.
    - إضافة بطاقة سريرية بارزة "بيانات الولادة وعمر الحمل السريرية" داخل تبويب **"باقة الخدمة والتبديل (12:00 ظهراً)"** تعرض وزن الدخول وعمر الحمل ومستوى الخداجة بتصميم مقفل للحماية (`Locked Mode`).
    - توفير زر `[ ✏️ تعديل البيانات السريرية ]`؛ عند النقر يظهر تنبيه تأكيد SweetAlert2: *"هل أنت متأكد من تعديل هذه البيانات؟"*، وعند الموافقة يُفتح نموذج التعديل المباشر مع زر حفظ وزر إلغاء دون أي نوافذ منبثقة مشتتة.
    - عند خروج الطفل: تُقفل بطاقة البيانات السريرية نهائياً ويختفي زر التعديل.
    - مسار الحفظ (`POST /nicu/reservations/{reservation}/clinical-profile`) يقوم بتحديث البيانات، مزامنة الوزن الحالي إن لم يسجل، وإعادة احتساب تفاصيل البقاء وشرائح الضمان الصحي تلقائياً عبر `NicuBillingService`.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/NicuDischargedChartAndClinicalProfileTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NicuDischargedChartAndClinicalProfileTest.php))**:
    - 5 اختبارات ميزة مخصصة تغطي: عزل الحالات النشطة عن المؤرشفة في التبويبات، خلو شاشة التسجيل من الحقلين، التعديل والتثبيت داخل الإضبارة، قفل الواجهة السريرية للطفل المخرج، والحظر الصارم في السيرفر بنسبة نجاح 100% (`5 passed, 37 assertions`)، مع اجتياز كامل حزمة اختبارات الخدج العامة (`66 passed, 541 assertions`).

- **المرحلة 179 (2026-11-08) — تأمين واستقرار كشوفات وحسابات الطوارئ ضد الانهيار البرمجي ودعم مرضى الطوارئ المؤقتين (Emergency Cashier Statements & Movements Null-Safety Hardening & Emergency Patient Resolution)**:
  * **معالجة الانهيار البرمجي في كشوفات وحسابات الطوارئ**:
    - معالجة خطأ `Attempt to read property "user" on null` في شاشة كشوفات حسابات الطوارئ (`/cashier/emergency/statements`) الناتج عن استدعاء متداخل لدالة `optional()` عند وجود حالات طوارئ لمرضى مسجلين سريعاً بدون ملف دائم (`emergency_patient_id`).
    - استبدال دوال `optional()` المتداخلة في عروض Blade بمعامل الأمان المتسلسل الأصلي في PHP 8 (`?->`) ودعم استخراج الاسم بدقة متسلسلة: مريض الطوارئ الدائم ⬅️ مريض الطوارئ السريع ⬅️ مريض السند المباشر ⬅️ `غير معروف`.
    - شمول التحديث لشاشات: كشوفات حسابات الطوارئ (`emergency-statements.blade.php`)، الحركات المالية للطوارئ (`emergency-movements.blade.php`)، وتفاصيل حساب طبيب الطوارئ (`emergency-doctor-account.blade.php`).
  * **تطوير نموذج البيانات والتحسين الأدائي (Eager-Loading & Model Accessors)**:
    - إضافة Accessor موحد وآمن `$payment->patient_name` في موديل `Payment` يمنع أي انهيار مستقبلي في أي قسم مالي.
    - إضافة Accessor موحد `$emergency->patient_name` في موديل `Emergency`.
    - في كنترولر الكاشير (`CashierController.php`): تضمين `emergency.emergencyPatient` و `patient.user` في الـ Eager Loading لمنع استعلامات N+1 وتأمين استخراج أسماء المرضى في تقرير الإحالات التشخيصية والعمليات (`emergencyReferrals`).
    - حصر فلتر الكاشير في تقارير المدفوعات `/cashier/report` بالمستخدمين النشطين الذين يحملون دور كاشير رسمي (`cashier`, `casshieradmin`) وإزالة الأطباء وموظفي الاستعلامات من القائمة المنسدلة.
    - تصحيح حقل `cashier_id` في `Appointment::cancel()` لينسب سند الاسترجاع للكاشير الأصلي المستوفي للمبلغ بدلاً من حساب الطبيب الملغي للموعد.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/EmergencyCashierStatementsNullSafetyTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyCashierStatementsNullSafetyTest.php))**:
    - كتابة واجتياز 3 اختبارات آلية جديدة تغطي كشوفات الطوارئ، الحركات المالية، وحسابات أطباء الطوارئ لمرضى الطوارئ السريعين والنظاميين والمدفوعات اليتيمة بنسبة نجاح 100% (`3 passed, 8 assertions`)، مع استمرار اجتياز مصفوفة الصلاحيات بالكامل (`11 passed, 183 assertions`).

- **المرحلة 178 (2026-11-07) — تعديل العلامات الحيوية السريرية مباشرة في الجدول العلوي وإلغاء المودل المنبثق نهائياً، مع حوكمة الـ 30 دقيقة والتمرير السلس السريع (Inline Flowsheet Vitals Editing, Modal Removal, Direct Table Sync & 30-Minute Governance Enforcement)**:
  * **تعديل مباشر وسلس في الجدول العلوي دون فتح نوافذ منبثقة (Zero-Modal Direct Flowsheet Editing)**:
    - إلغاء المودل المنبثق نهائياً والتخلص من أي مشاكل تنسيق أو قطع في رأس الصفحة.
    - عند النقر على زر `[ ✏️ تعديل ]` في سجل القراءات التاريخية: تعبئة قيم القراءة فورياً في خانات الجدول العلوي (`grid_bp`, `grid_temp`, `grid_pr`, `grid_rr`, `grid_spo2`, `grid_rbs`, `grid_pain_score`, `grid_gcs`, `grid_crt`) وتفعيلها بتحديد أزرق أنيق.
    - تمرير الصفحة بسلاسة وتلقائية (`smooth scroll`) نحو جدول العلامات الحيوية والتركيز على خانة ضغط الدم.
    - إظهار شارة تنبيهية أعلى الجدول توضح توقيت القراءة واسم مسجلها مع زر سريع `[ ✕ إلغاء التعديل ]` لاستعادة القيم الأصلية بنقرة واحدة.
  * **معالجة الحفظ الموحد عبر زر "حفظ التحديثات السريرية" (`ClinicalStationController@update`)**:
    - دعم حقل `editing_reading_id` المخفي ضمن النموذج الرئيسي؛ عند الحفظ يتم التحقق الصارم من مهلة الـ 30 دقيقة أو صلاحية الأدمن وتحديث القراءة المحددة فوراً.
    - إذا كانت القراءة المعدلة هي الأحدث: مزامنة قيم المحطة السريرية العلوية ومحطة التمريض تلقائياً ولحظياً.
  * **حزم الاختبارات الآلية الشاملة ([tests/Feature/ClinicalVitalsGovernanceTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ClinicalVitalsGovernanceTest.php))**:
    - اجتياز كامل الاختبارات بنسبة 100% (`8 passed, 50 assertions`) واستمرار اجتياز كافة اختبارات المحطة وصلاحيات التبويبات (`11 passed, 44 assertions`).

- **المرحلة 177 (2026-11-07) — شارات استقطاع حصة الجراح والضمان وتلخيص الاستقطاعات التلقائي في نموذج دفع العمليات (Surgery Cashier Smart Package Inclusions Badges & Live Doctor Deductions Total Breakdown)**:
  * **شارات الباقة الذكية في جدول بنود تسديد العمليات**:
    - إضافة شارات `package-status-badge` التفاعلية لأسطر الغرفة، التحاليل، الأشعة، والخدمات الإضافية.
    - للمريض النقدي (كاش): شارة واضحة `[ ✂️ استقطاع من حصة الجراح: X د.ع ]` تظهر فور اختيار كبسولة `[ 📦 مشمول ]`.
    - لمرضى الضمان الصحي: شارة تعاقدية `[ 🛡️ مشمول بعقد الضمان ]`.
    - للبند المنفصل: شارة رمادية `[ 🪙 منفصل عن العملية ]`.
  * **سطر التلخيص الإجمالي للاستقطاعات بأسفل جدول الفاتورة (`tfoot`)**:
    - إضافة سطر ملخص استقطاعات الجراح لصالح المستشفى: `إجمالي استقطاعات الباقة المخصومة من حصة الجراح: [ X د.ع ]` مع شارة توضيحية لنظام المحاسبة.
  * **تحديث الجافاسكريبت اللحظي (`calculateSelectedAmount()`)**:
    - حساب مجموع استقطاعات الجراح لحظياً وتحديث نصوص وألوان الشارات وسطر الملخص فورياً عند التبديل بين الكبسولات.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/SurgeryExtraServicesTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryExtraServicesTest.php))**:
    - اجتياز كامل الاختبارات بنسبة 100% (`13 passed, 89 assertions`).

- **المرحلة 177 (2026-11-07) — حوكمة العلامات الحيوية بالمحطة السريرية: جدول إدخال أفقي بالأعمدة، قفل التعديل بمهلة 30 دقيقة لنفس الحساب، حصر الصلاحية للإدارة، وحجب المنحنيات البيانية عن التمريض (Unified Clinical Station: Horizontal Vitals Flowsheet Grid, 30-Minute Self-Edit Window, Admin Immutability Override & Role-Gated Trend Charts)**:
  * **تحويل العلامات الحيوية إلى جدول أفقي بالأعمدة (Horizontal Flowsheet Grid)**:
    - استبدال البطاقات المنفصلة بجدول سريري أفقي متكامل بـ 9 أعمدة: (ضغط الدم BP، الحرارة Temp، النبض PR، التنفس RR، الأكسجين SPo2، سكر الدم RBS، مقياس الألم Pain، مقياس الوعي GCS، امتلاء الشعيرات CRT).
    - إدخال فوري للقيم داخل خلايا الجدول بتصميم طبي ناعم وسلس، مع عرض الوحدات والمدى الطبيعي المرجعي في ترويسة الأعمدة.
  * **حوكمة التعديل وقفل السجلات الطبية (30-Minute Immutability Governance)**:
    - إضافة أعمدة `vitals_recorded_at` و `vitals_recorded_by` في جدول `resident_stations`، وعمود `user_id` في `resident_station_readings`.
    - قاعدة الـ 30 دقيقة الصارمة: يُسمح بالتعديل حصراً لنفس حساب الموظف الذي أدخل القراءة ولمدة 30 دقيقة فقط من توقيت التسجيل.
    - انقضاء المهلة أو الدخول من حساب مختلف: تُقفل القراءة تلقائياً (`readonly`) ولا يمكن تعديلها إلا من حساب مدير النظام (`Admin`) حصراً، مدعوماً بحماية صارمة في السيرفر لمنع أي تعديل غير مصرح.
    - دعم زر [ ➕ تسجيل قراءة دورية جديدة ] لتمكين الكوادر من توثيق قياسات دورية لاحقة دون المساس بالقراءات التاريخية السابقة وبدء عداد جديد لها.
  * **حجب المنحنيات البيانية عن التمريض**:
    - حصر ظهور قسم المنحنيات البيانية لتطور العلامات الحيوية (`Chart.js`) للجراح، المقيم، ومدير النظام حصراً، وحجبه كلياً عن طاقم التمريض (`nurse`).
  * **حزم الاختبارات الآلية الشاملة ([tests/Feature/ClinicalVitalsGovernanceTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ClinicalVitalsGovernanceTest.php))**:
    - كتابة واجتياز 5 اختبارات آلية جديدة تغطي كامل متطلبات الأعمدة، حجب المخططات، حوكمة الـ 30 دقيقة، قفل الحساب، وتجاوز الأدمن بنسبة 100% (`5 passed, 36 assertions`)، واستمرار اجتياز كافة اختبارات المحطة السريرية (`11 passed, 44 assertions`) بإجمالي 16 اختباراً ناجحاً.

- **المرحلة 176 (2026-11-07) — قسم حسابات وتصفية جراحي العمليات: استقطاعات الباقة لصالح المستشفى، كشوفات حسابات الجراحين، وصفحة تفاصيل وسندات صرف مستحقات العمليات (Surgery Doctor Accounts & Package Inclusions Settlement Engine: Granular Inclusions Deduction, Doctor Accounts Index, Itemized Surgery Breakdown & Treasury Payouts)**:
  * **بنية البيانات وحفظ استقطاعات الباقة المشمولة بالعملية**:
    - إضافة أعمدة `is_package_included`, `included_deductible_price`, `deduction_bearer` في جداول `surgery_lab_tests`, `surgery_radiology_tests`, `surgery_extra_services`.
    - إضافة `is_room_package_included`, `room_deduction_bearer` في جدول `surgeries`.
    - إضافة `doctor_deductions_amount`, `included_package_items` (JSON Snapshot) في جدول `payments`.
    - تحديث موديلات `SurgeryLabTest`, `SurgeryRadiologyTest`, `SurgeryExtraService`, `Surgery`, `Payment`, و `Doctor` (إضافة علاقة `surgeries()`).
  * **معالجة الدفع وحساب الاستقطاعات في الكاشير (`CashierController@processSurgeryPayment`)**:
    - للمرضى النقديين: احتساب قيمة الخدمات المشمولة بالباقة وتحميلها كاستقطاع على حصة الطبيب الجراح (`deduction_bearer = 'doctor'`) لصالح إيراد المستشفى.
    - لمرضى الضمان الصحي: إدراجها ضمن التغطية العقدية (`deduction_bearer = 'insurance'`).
    - تسجيل صافي الاستحقاق للجراح تلقائياً في `DoctorDue` بالمعادلة: صافي المستحق = أتعاب الجراحة - استقطاعات الباقة.
  * **القائمة الجانبية والشاشات الجديدة للحسابات المالية**:
    - إضافة قسم فرعي: `حسابات جراحي العمليات` ضمن قسم "الحسابات العامة والمالية" في `resources/views/layouts/app.blade.php`.
    - **شاشة ملخص حسابات الجراحين (`resources/views/cashier/surgeries/doctor-accounts.blade.php`)**: جدول بكافة الجراحين، عدد العمليات، الأتعاب الاسمية، استقطاعات الباقة، صافي المستحق، إجمالي المصروف، والرصيد المتبقي بذمة المستشفى، مع زر [ 👁️ كشف الحساب والتفاصيل ].
    - **شاشة كشف حساب الجراح وعملياته التفصيلية (`resources/views/cashier/surgeries/doctor-account-details.blade.php`)**: بطاقات مؤشرات علوية، نموذج صرف دفعة / تصفية رصيد للجراح، جدول مفصل بجميع عمليات الجراح مع تفكيك دقيق للخدمات المشمولة بالباقة، وزر صرف وتصفية العملية بشكل فوري.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/SurgeryExtraServicesTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryExtraServicesTest.php))**:
    - اجتياز كامل اختبارات التحقق من استقطاع الباقة من حصة الجراح نقداً، عرض حسابات الجراحين، كشف الحساب المفصل، والصرف الفعلي بنسبة 100% (`13 passed, 85 assertions`)، واستمرار اجتياز كافة اختبارات الضمان والتمريض (`13 passed, 97 assertions`).

- **المرحلة 175 (2026-11-07) — نظام الكاشير الموحد للعمليات الجراحية: الشمول التام للغرفة بالباقة، الحذف الفوري لتحاليل وأشعة العمليات، والدفع الجزئي العام الإجمالي للمبلغ الكلي (Surgery Cashier Unified Suite: Granular Lab/Radiology Deletion, Room Package Inclusion Toggle & Global Bottom Invoice Split/Partial Payment)**:
  * **الحذف الفوري للتحاليل والأشعة المعلقة بالعملية**: إضافة مسارات وتوابع `removeSurgeryLabTest()` و `removeSurgeryRadiologyTest()` في الكاشير وحمايتها وتطبيق أزرار الحذف الدائرية الحمراء `delete-surgery-item-btn` مع تأكيد SweetAlert2 فاخر وحذف لحظي من الجدول وإعادة احتساب فوري.
  * **توحيد كبسولات الشمول بالباقة للغرفة**: استبدال مفاتيح التجزئة المشتتة بكبسولات موحدة: `[ 📦 مشمول بالعملية ]` (حصة المريض 0 د.ع) مقابل `[ 💵 منفصل ]` (احتساب الاستقطاع الاعتيادي أو الضمان)، مع دعم كامل في السيرفر وتحديث المبالغ وإصدار الوصل بما يطابق الشمول.
  * **بطاقة الدفع الجزئي العام أسفل الفاتورة (Global Partial Payment)**: نقل خيار الدفع الكامل والجزئي إلى بطاقة تحكم موحدة أسفل الجدول: `[ 🟢 دفع كامل للمبلغ المعتمد ]` مقابل `[ 🟡 دفع جزئي (دفعة مقبوضة الآن) ]` مع حقل إدخال المبلغ المقبوض وشارة احتساب دين العملية المتبقي حياً ولحظياً، وتوثيقه في الوصل والعملية وسجل المدفوعات.
  * **فصل شاشة الانتظار وتقويم العمليات**: تحويل شاشة الانتظار `/surgeries/waiting` إلى شاشة عرض مخصصة لعمليات اليوم فقط مع تنقل يومي سلس `[ السابق / التالي ]`، ونقل وتخصيص التقويم الشهري المدمج إلى سجل العمليات العام `/surgeries`.
  * **حزمة الاختبارات الآلية الشاملة**: إضافة 3 اختبارات جديدة في `tests/Feature/SurgeryExtraServicesTest.php` واجتياز كافة الاختبارات بنسبة 100% (11 passed, 61 assertions) واستمرار اجتياز كافة اختبارات الضمان وشرائح الإقامة (19 passed, 131 assertions).

- **المرحلة 170 (2026-11-07) — الإلحاق التلقائي لخدمات العمليات (تخطيط قلب، فتح طبلة، خدمات فندقية)، تسعيرها المرن، والتحكم الفردي بالشمول (Auto Surgery Default Services, Inline Editable Pricing & Granular Inclusion)**:
  * الإلحاق التلقائي لـ 3 خدمات إضافية افتراضية مع كل حجز عملية: (تخطيط قلب 15,000 د.ع، اجور فتح طبلة 10,000 د.ع، خدمات فندقية 30,000 د.ع) عبر `attachDefaultExtraServices` على موديل `Surgery`.
  * دعم التعديل المباشر للسعر للخدمات الإضافية مع مسار PATCH المخصص في الكاشير.
  * إلغاء مفتاح الشمول الكلي العام وتمكين الشمول الفردي المستقل لكل خدمة بدون تعارض.

- **المرحلة 168 (2026-11-05) — توحيد طباعة طلبات تحاليل الطوارئ قبل إدخال النتائج وإظهار فحص CBC في أمر العمل مطابقةً لباقي الأقسام (Emergency Lab Request Print & CBC Work Order Requisition Parity)**:
  * **معالجة طباعة طلب الطوارئ كأمر عمل قبل إدخال النتائج ([resources/views/staff/requests/emergency-lab-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/emergency-lab-print.blade.php))**:
    - تعريف وضع أمر العمل `$hasWorkOrder` بدقة: عندما يكون الطلب غير مكتمل ولم تُدخل نتائجه بعد وله تحاليل مطلوبة (`!$isCompleted && !$hasActualResults && $requestedLabTestsList->count() > 0`).
    - تضمين `$hasWorkOrder` في شرط إظهار الصفحة الأولى `@if($hasStandardContent || $hasUrineContent || $hasWorkOrder)` لمنع ظهور الصفحة فارغة/بيضاء نهائياً.
    - إدراج قسم أمر العمل الموحد بالبنر الأزرق المتقطع وقائمة الفحوصات المطلوبة متبوعة بخط منقط لكتابة النتيجة يدوياً من قبل فني المختبر وشارة `Pending` لكل تحليل مطلوب.
    - ضمان ظهور فحص صورة الدم الكاملة (CBC) وكافة الفحوصات المعتمدة على المرفقات في أمر العمل دون استبعادها.
  * **توحيد أزرار إجراءات الطوارئ مع باقي الأقسام ([resources/views/lab/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/index.blade.php))**:
    - للطلبات المعلقة: زر `[ إدخال النتائج ]` باللون الأزرق وزر `[ 🖨️ طباعة الطلب ]` بـ `btn-outline-dark`.
    - للطلبات المكتملة: زر `[ معاينة ]` وزر `[ 🖨️ طباعة ]` بالأخضر وزر الواتساب.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/UnifiedEmergencyLabStationAndReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/UnifiedEmergencyLabStationAndReportTest.php))**:
    - إضافة واجتياز اختبار التحقق من ظهور CBC في أمر العمل بنجاح بنسبة 100% (`11 passed, 69 assertions`).

- **المرحلة 187 (2026-11-10) — إعادة هندسة ومطابقة واجهتي حجز وتعديل العمليات الجراحية بنظام بيئة العمل ثنائية الأعمدة وشريط هوية المريض التنفيذي (Surgery Create & Edit Unified Modern Split-Workspace, Patient Executive Banner & Action Bar Parity)**:
  * **إزالة لوحة الاعتماد والملخص الإضافية وإبقاء أزرار الحفظ المباشرة ([resources/views/surgeries/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/create.blade.php))**:
    - حذف كارت الملخص المالي الإضافي استجابة لتوجيه المستخدم.
    - وضع أزرار الاعتماد والحفظ المباشرة أسفل عمود المعلومات الأيمن بوضوح كامل وسلاسة.
  * **إعادة هندسة وتطوير واجهة تعديل العملية الجراحية بالكامل للمطابقة التامة مع شاشة الحجز ([resources/views/surgeries/edit.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/edit.blade.php))**:
    - **شريط هوية المريض التنفيذي العلوي (`#patientExecutiveBanner`)**: يعرض اسم المريض، رقم الملف، العمر، الجنس، الهاتف، بطاقة وشارة الضمان، وأزرار السجل الطبي الشامل وطباعة الإضبارة وشارة تعديل العملية #{{ $surgery->id }}.
    - **يمين الشاشة (`col-lg-5`)**: كارت موحد يضم مسار التغطية (ضمان أم كاش)، اختيار المريض، الطبيب الجراح، الطبيب المرسل، تاريخ العملية، ومسح/رفع ورقة التحويل بالسكانر، متبوعاً بأزرار الحفظ المباشرة `[ 💾 حفظ وتحديث حجز العملية الجراحية ]` و `[ ❌ إلغاء ]`.
    - **يسار الشاشة (`col-lg-7`)**: بالأعلى جدول العمليات الجراحية المطلوبة (`#collapseOperations`) مع دعم العمليات المتعددة والإضافية وزر `[ ➕ إضافة عملية أخرى ]`، وبالأسفل كارت اختيار الغرفة والإقامة السريرية (`#collapseRoomStay`) مع تبويبات الطوابق وأزرار الغرف الملونة.
  * **حزم الاختبارات الآلية الشاملة ([tests/Feature/SurgeryCreateModernWorkspaceTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryCreateModernWorkspaceTest.php))**:
    - إنشاء واجتياز اختبار التحقق من الرندرة لصفحتي الحجز والتعديل مع شريط الهوية التنفيذي وتوزيع الأعمدة بنجاح 100% (`1 passed, 15 assertions`).
    - فحص وتنسيق الكود بـ `vendor/bin/pint --dirty` بنجاح كامل.

- **المرحلة 186 (2026-11-10) — إعادة هندسة وتطوير واجهة إنشاء طلب الاستعلامات بنظام بيئة العمل ثنائية الأعمدة بدون تمرير (Inquiry Create Modern Split-Workspace Redesign, Sticky Action Panel & Quick Available Doctor Chips)**:
  * **تخطيط بيئة العمل ثنائي الأعمدة المتجاوب ([resources/views/inquiry/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/inquiry/create.blade.php))**:
    - **يمين الشاشة (`col-lg-7`)**: بطاقات الخدمات الطبية المباشرة بحجم عصري وشارات تفاعلية أنيقة، مع فصل تحويلات الوحدات الخارجية (طوارئ، عمليات، رقود، حاضنة خدج) في قسم مستقل بأيقونات انتقال واضحة لمنع التشتت والخلط مع الخدمات متعددة الاختيار.
    - **يسار الشاشة (`col-lg-5`)**: لوحة تفاصيل وحفظ ثابتة بمحاذاة النظر (`position: sticky`) تلغي التمرير العمودي المجهد (`Scrolling Fatigue`)، وتعرض حالة توجيهية فارغة (`Empty State`) قبل الاختيار، ثم تظهر الحقول وخيارات التغطية (ضمان / كاش) وزر الاعتماد في نفس مستوى النظر فوراً.
  * **شريط هوية المريض التنفيذي وحل تكرار تنبيه الضمان**:
    - دمج بيانات المريض في شريط أبيض منحني (`rounded-4`) يضم رقم الملف، العمر، الجنس، الهاتف، العنوان، وحالة الضمان الصحي.
    - إضافة أزرار وصول مباشر لـ: السجل الطبي الشامل (`inquiry.patients.history`)، تعديل الملف (`patients.edit`)، وطباعة الإضبارة (`inquiry.patients.dossier.print`).
    - القضاء على تكرار تنبيه قفل الضمان الصحي، وحصره في تنبيه موحد واضح ومباشر مع زر الإشعار الرقمي السريع لشعبة الضمان والتسجيل.
  * **تسريع حجز الاستشارية والتحقق الذكي**:
    - إضافة أزرار شرائح سريعة (`Quick Selection Chips`) للأطباء المتواجدين اليوم فعلياً أعلى حقل البحث، ليتم تعبئة الطبيب وتفعيل الفحص التلقائي بنقرة زر واحدة.
    - ترقية تنبيهات التحقق والنواقص لتعتمد على **SweetAlert2** بدل تنبيهات المتصفح التقليدية (`alert`).
  * **حزم الاختبارات الآلية الشاملة**:
    - اجتياز اختبارات قفل الضمان وحجز السونار بنجاح 100% (`InquiryInsuranceDataIncompleteTest`, `UltrasoundInquiryBookingTest`)، مع اجتياز اختبارات تدفق الحاضنة (`NicuWorkflowTest`).

- **المرحلة 185 (2026-11-08) — طباعة سندات صرف مستحقات الطبيب الاستشاري (A4 وحراري 78mm) ومحرك استرجاع وإلغاء الصرف الكلي وحالة الوصولات (مقبوضة/غير مقبوضة) (Consultant Doctor Payout Voucher Printing, Strict Full Reversal Engine & Receipts Paid/Pending Dynamic Status)**:
  * **صفحة وقالب طباعة سند الصرف الرسمي ([resources/views/consultant-availability/doctor-payout-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/consultant-availability/doctor-payout-print.blade.php))**:
    - تطبيق الهدر الرسمي الثلاثي لمستشفى الكفاءات الأهلي مع الشعار المعتمد بتقنية Base64.
    - تفقيط المبالغ المالية باللغة العربية وتحويل الأرقام إلى كلمات عبر الخدمة الجديدة `TafqeetService`.
    - شريط عنوان السند (`سند صرف نقدي - مستحقات طبيب استشاري` أو `سند استرجاع مالي`).
    - مساحات التواقيع الرسمية الثلاثية: توقيع الطبيب المستلم، منظم السند/المحاسب، واعتماد الإدارة المالية.
    - التبديل الفوري بنقرة زر بين وضع الطباعة الرسمية A4 ووضع الوصل الحراري الصغير (Thermal Receipt 78mm) المناسب لطابعات الكاشير.
  * **تطوير واجهة كشف الحساب والتبويبات ([resources/views/consultant-availability/doctor-account-details.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/consultant-availability/doctor-account-details.blade.php))**:
    - إظهار شريط إشعار علوي بارز عند اعتماد أي صرف يحتوي على زر فوري `[ 🖨️ طباعة سند الصرف الآن ]`.
    - ربط عمود حالة الوصولات بوضوح: `[ ⏳ غير مقبوضة ]` عند عدم الصرف للطبيب، `[ ✓ مقبوضة ]` بعد اعتماد الصرف، و `[ 🔄 مسترجعة ]` عند رد الوصل للمريض.
    - فلترة الكشفيات حسب حالة الصرف للطبيب (`جميع الحركات`، `غير مقبوضة (معلقة)`، `مقبوضة (مسددة للطبيب)`، `مسترجعة للمريض`).
    - إعادة احتساب المبلغ المقترح للصرف ديناميكياً ليقتصر على **الوصولات غير المقبوضة فقط** منعاً لتكرار صرف كشفية مسددة مسبقاً.
    - إعادة هيكلة تبويب "سجل سندات الصرف والتسويات" ليعرض جدول سندات الخزينة الرسمية الصادرة (`FinancialTransaction`) برقم السند، تاريخ الصرف، المبلغ، المحاسب، البيان، وزرين للإجراءات: `[ 🖨️ طباعة السند ]` و `[ 🔄 استرجاع / إلغاء ]`.
    - وضع تفاصيل الكشفيات المسددة فردياً (`DoctorDue`) داخل قسم قابل للطي (Accordion) لمنع ازدحام الواجهة.
  * **محرك استرجاع وإلغاء سندات الصرف الكلي الحصري ([app/Http/Controllers/ConsultantAvailabilityController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/ConsultantAvailabilityController.php))**:
    - تطبيق قاعدة الإلغاء والاسترجاع الكلي للسند حصراً (`full reversal only`) منعاً للغموض المحاسبي وضمان عودة كافة وصولات السند الملغى إلى حالة `غير مقبوضة` بدقة تامة.
    - نافذة استرجاع تفاعلية (Modal) تُلزم المحاسب بإدخال سبب الاسترجاع وتمنع الاسترجاع المكرر.
    - إعادة المبلغ بالكامل فوراً إلى رصيد الطبيب المتاح، وخصمه من إجمالي المسدد.
    - إعادة كافة مستحقات الكشفيات المرتبطة بالسند إلى حالة معلقة `pending` لتعود غير مقبوضة ومتاحة للصرف مجدداً.
    - توثيق القيد العكسي في الخزينة برقم سند استرجاع `REF-DOC-...` وتوثيق العملية في ملاحظات السند الأصلي.
  * **خدمة التفقيط المالي بالدينار العراقي ([app/Services/TafqeetService.php](file:///c:/wamp64/www/hospital-system/app/Services/TafqeetService.php))**:
    - خدمة احترافية لتحويل الأرقام والمبالغ بالدينار العراقي إلى نصوص عربية نحوية متقنة.
  * **حزم الاختبارات الآلية الشاملة ([tests/Feature/ConsultantDoctorPayoutVoucherAndReversalTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ConsultantDoctorPayoutVoucherAndReversalTest.php))**:
    - 4 اختبارات آلية جديدة تغطي توليد السندات، عرض وتفقيط الطباعة، الاسترجاع الكلي وإعادة الوصولات لحالة غير مقبوضة بنجاح 100% (`4 passed, 40 assertions`)، مع اجتياز كامل اختبارات فلاتر الحسابات (`6 passed, 49 assertions`).

- **المرحلة 184 (2026-11-08) — حل مشكلة التحقق من صحة مبلغ الصرف وإلغاء قيود الخطوة في كشف حساب الطبيب الاستشاري (Consultant Doctor Payout Input Validation Fix & Arbitrary Amount Step-Any Support)**:
  * **معالجة خطأ التحقق في المتصفح ([resources/views/consultant-availability/doctor-account-details.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/consultant-availability/doctor-account-details.blade.php))**:
    - حل مشكلة ظهور تنبيه المتصفح `Please select a valid value` عند النقر على "تسجيل واعتماد سند الصرف".
    - إزالة التعارض بين `min="1"` و `step="500"` الذي كان يفرض قيوداً رياضية على المبالغ `min + (k * step)` فتفشل كافة المبالغ المستديرة (مثل 25,000 أو 10,000).
    - تعيين `step="any"` و `min="0.01"` للسماح بأي مبلغ صحيح أو عشري ضمن حدود الرصيد المتاح للطبيب.
  * **حزم الاختبارات الآلية الشاملة ([tests/Feature/ConsultantDoctorAccountsFiltersTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ConsultantDoctorAccountsFiltersTest.php))**:
    - تحديث الاختبارات للتأكد من رندرة مدخلات الصرف بخصائص `step="any"` و `min="0.01"` واجتياز الاختبارات بنجاح 100% (`6 passed, 49 assertions`).

- **المرحلة 187 (2026-11-10) — الحفظ التلقائي للخدمات الإضافية الجديدة في دليل المستشفى وتقسيم واجهة حجز العمليات لبيئة عمل ثنائية (Auto-Persist Custom Extra Services to Hospital Catalog & Split Surgery Create Workspace)**:
  * **محرك الحفظ التلقائي للخدمات الإضافية المخصصة في الدليل المرجعي ([app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php))**:
    - عند قيام الكاشير بكتابة وإضافة أي خدمة إضافية جديدة لم تكن موجودة في الدليل (`hospital_extra_services`)، يتم تلقائياً حفظها في الدليل العام للمستشفى باسمها وسعرها وتصنيفها المحدد وحالتها كخدمة نشطة (`is_active = true`).
    - ربط المعرف الجديد `service_id` بسجل العملية الجراحية `surgery_extra_services` أو الطلب الطبي `visit_extra_services`.
    - ظهور الخدمة الجديدة تلقائياً وفورياً في القائمة المنسدلة الذكية (Autocomplete) والتصنيف الخاص بها لجميع العمليات والطلبات القادمة دون الحاجة لإعادة كتابتها يدوياً.
    - تحديث السعر الافتراضي للخدمة إذا كان مسجلاً بصفر وأدخل الكاشير سعراً معتمداً.
    - معالجة المتغيرات التحملية الخاصة بـ `$labCopay`, `$radCopay`, `$ecgCopay` في `processSurgeryPayment`.
  * **تطوير واجهة كاشير العمليات وشاشة الإضافة ([resources/views/cashier/surgeries/payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
    - إضافة حقل التصنيف الخفي `service_category` لربطه بالفئة النشطة.
    - تحديث خيار الإضافة المخصصة في القائمة المنسدلة ليوضح حفظ الخدمة بالدليل: `إضافة كخدمة جديدة: "..." (✨ سيتم حفظها في دليل الخدمات لتظهر تلقائياً بالمرات القادمة)`.
  * **إعادة هندسة واجهة حجز العملية الجراحية ثنائية الأعمدة ([resources/views/surgeries/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/create.blade.php), [app/Http/Controllers/SurgeryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgeryController.php))**:
    - تقسيم الشاشة إلى نصفي عمل متوازيين ومتكاملين:
      * **يمين الشاشة (`col-lg-7`)**: شريط هوية المريض التنفيذي، بيانات العملية، الجراح، الطبيب المرسل، تاريخ العملية، الماسح الضوئي لورقة الإحالة، وجدول العمليات الجراحية متعدد العمليات مع الملخص المالي.
      * **يسار الشاشة (`col-lg-5`)**: لوحة الإقامة والغرف الفندقية بأزرار الطوابق الميكرو، مع بطاقة الملخص المالي الثابتة (`Sticky Action Sidebar`) التي تعرض إجمالي العمليات، رسوم الغرفة، نوع التغطية (ضمان/كاش)، والإجمالي الكلي مع زر الاعتماد الفوري بمحاذاة النظر.
  * **حزم الاختبارات الآلية الشاملة**:
    - اجتياز اختبار `test_cashier_adding_new_custom_service_persists_to_hospital_extra_services_catalog` في `tests/Feature/SurgeryExtraServicesTest.php` بنجاح 100% (`15 passed, 108 assertions`).
    - اجتياز اختبار `SurgeryCreateModernWorkspaceTest.php` بنجاح 100% (`1 passed, 15 assertions`).
    - اجتياز اختبار `SurgeryCashierSplitAndInsurancePaymentTest.php` بنجاح 100% (`7 passed, 51 assertions`).

- **المرحلة 183 (2026-11-08) — ترقية طباعة كشف مقبوضات الكاشير، إضافة الهدر المعتمد، منع تكرار المجموع في كل صفحة، وإظهار عمود نوع التغطية (Cashier Report Print Standard Header, Single Table Total Row & Insurance/Cash Coverage Column)**:
  * **الهدر الموحد المعتمد ([resources/views/cashier/report-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/report-print.blade.php))**:
    - تطبيق الهدر الثلاثي الرسمي (مستشفى الكفاءات الأهلي، قسم الحسابات المالية والتدقيق، Al-Kafa'at Private Hospital).
    - تضمين شعار المستشفى المعتمد (`images/1.jpg`) مع تشفير Base64 لضمان سرعة التحميل ودقة الطباعة العالية.
    - إضافة شريط عنوان المستند الموحد وكروت الملخص المالي السريع.
    - إضافة العلامة المائية الرسمية للنظام في سنتر كل صفحة مطبوعة، وحجب رابط المتصفح التلقائي من هوامش الورقة عبر ضبط `@page { margin: 0 }`.
    - فصل الترويسة أعلى الجدول وتخصيص `thead` فقط لعناوين الأعمدة مع `table-layout: fixed; width: 100%` لضمان انتظام الجدول في الصفحات التالية من الأعلى دون أي تشوه.
  * **منع تكرار المجموع الكلي أسفل كل صفحة مطبوعة ([resources/views/cashier/report-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/report-print.blade.php))**:
    - تصحيح خاصية CSS لذيل الجدول من `display: table-footer-group` إلى `display: table-row-group`.
    - حصر ظهور سطر المجموع الكلي ليظهر **مرة واحدة فقط في نهاية الجدول والتقرير على الصفحة الأخيرة**.
  * **إضافة عمود "نوع التغطية" وتطوير الموديل ([resources/views/cashier/report-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/report-print.blade.php), [app/Models/Payment.php](file:///c:/wamp64/www/hospital-system/app/Models/Payment.php))**:
    - إضافة عمود صريح في الجدول يعرض نوع التغطية لكل وصل: `نقدي (كاش)` أو `ضمان (الضمان الصحي الوطني / قوى الأمن الداخلي)` مع رقم البطاقة وحصة الضمان بدقة.
    - تزويد موديل `Payment` بالـ Accessor التوافقي `insurance_type_name`.
  * **حزم الاختبارات الآلية الشاملة ([tests/Feature/CashierReportPrintTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/CashierReportPrintTest.php))**:
    - اجتياز اختبار رندرة الهدر المعتمد، منع تكرار المجموع، وظهور عمود نوع التغطية بنجاح 100% (`1 passed, 15 assertions`).

- **المرحلة 182 (2026-11-08) — ديناميكية مبلغ الصرف واعتماده على البحث والفترة المحددة وشريط الفلترة الموحد لكشف حساب الطبيب الاستشاري (Consultant Doctor Filtered Payout Amount Engine, Unified Top Filters & Period Dues Sync)**:
  * **محرك احتساب مستحقات الفترة والبيان التلقائي ([app/Http/Controllers/ConsultantAvailabilityController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/ConsultantAvailabilityController.php))**:
    - استخراج إحصائيات البحث والفترة المفلترة بدقة (`filteredDoctorShare`, `filteredHospitalShare`, `filteredTotalAmount`, `filteredCount`).
    - ربط وتعيين المبلغ المقترح للصرف ديناميكياً بحصة الطبيب للفترة المحددة `suggestedPayoutAmount = min($availableBalance, max(0, $filteredDoctorShare))` مع ضمان عدم تجاوز الرصيد المتبقي للطبيب.
    - توليد البيان والملاحظات الذكية المقترحة لسند الصرف آلياً بناءً على تاريخ الفترة المحددة أو مصطلح البحث.
  * **ترقية وتطوير واجهة كشف الحساب بالكامل ([resources/views/consultant-availability/doctor-account-details.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/consultant-availability/doctor-account-details.blade.php))**:
    - رفع وتوحيد شريط الفلترة والبحث ليصبح كرت عام في أعلى الصفحة أسفل مؤشرات الـ KPIs مباشرة، ليحكم كلاً من بطاقة الصرف وجدول الكشفيات معاً.
    - إضافة شريط معلوماتي أنيق يُبرز نتائج الفترة المحددة (عدد الكشفيات، إجمالي المبالغ، حصة الطبيب المستحقة للفترة، وحصة المستشفى).
    - ترقية بطاقة الصرف: تزويد حقل المبلغ بزرين سريعين ذكيين: `[ 📅 الفترة (X د.ع) ]` لتعبئة مستحقات البحث بنقرة واحدة، و `[ ⚡ كامل الرصيد (Y د.ع) ]` لتعبئة كامل الرصيد المتاح.
    - دعم تأكيد الصرف التفاعلي عبر **SweetAlert2** مع إظهار المبلغ والبيان المحسوب بدقة.
  * **حزم الاختبارات الآلية الشاملة ([tests/Feature/ConsultantDoctorAccountsFiltersTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ConsultantDoctorAccountsFiltersTest.php))**:
    - إضافة اختبار `test_consultant_doctor_account_filtered_payout_amount_calculation` والتحقق من حساب مستحقات الفترة وعرضها في النموذج بنجاح 100% (`6 passed, 47 assertions`).

- **المرحلة 144 (2026-11-03) — نظام ثبات واستمرارية التبويبات والتبويبات الفرعية وموضع التمرير في قسم الخدج (NICU Full Tab, Pill & Scroll Persistence Across All Operations)**:
  * **الثبات الكامل للتبويبات الرئيسية والفرعية ([resources/views/nicu/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/nicu/show.blade.php))**:
    - ربط كافة التبويبات السريرية الـ 9 (`servicePane`, `vitalsPane`, `growthPane`, `careSchedulesPane`, `consultationsPane`, `labPane`, `radiologyPane`, `prescriptionsPane`, `attachmentsPane`) والتبويبات الفرعية الـ 3 (`pill-feeding`, `pill-medication`, `pill-doctor-note`) ديناميكياً بمتغيرات الجلسة والطلب `$currentActiveTab` و `$currentActivePill`.
    - حقن تلقائي لحظي لمدخلات `_active_tab` و `_active_pill` داخل أي نموذج أو زر يتم النقر عليه أو إرساله في الصفحة.
  * **تثبيت موضع التمرير ومنع القفز لأعلى الصفحة (Scroll Position Restoration)**:
    - حفظ موضع التمرير اللحظي في `sessionStorage` عند إرسال أي فورم أو النقر على أي زر حفظ، واستعادته بدقة وتوقيت مرحلي عند اكتمال تحميل الصفحة ليبقى المستخدم في ذات الموضع بدقة تامة.
    - تعميم آلية حفظ التمرير أيضاً على لوحة إشغال الحاضنات (`nicu/index.blade.php`) وشاشة تسعير وباقات الخدمات (`nicu/services/index.blade.php`).
  * **كنترولر الخدج ([app/Http/Controllers/NicuController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/NicuController.php))**:
    - إضافة دالة المساعدة المركزية `redirectBackWithActiveState` وتعميمها عبر كافة دوال الخدج (توثيق العلامات الحيوية، جداول الرعاية، الملاحظات الطبية، الاستشارات، طلبات المختبر والأشعة، الوصفات، إدارة العلاج MAR، المرفقات، وتبديل الباقات)، لتمرير التبويب والتبويب الفرعي تلقائياً في الجلسة `session(['active_tab' => ..., 'active_pill' => ...])`.
  * **حزمة الاختبارات الآلية الشاملة ([tests/Feature/NicuTabAndStatePersistenceTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NicuTabAndStatePersistenceTest.php))**:
    - 5 اختبارات ميزة تغطي توثيق القراءات الحيوية، جداول الرعاية، ملاحظات الأطباء، وتفعيل التبويبات والتبويبات الفرعية على صعيد السيرفر بنجاح 100% (`5 passed, 20 assertions`).

### 🟡 In Progress (قيد المتابعة والتنفيذ):
1. **نشر التحديثات البرمجية والمايجريشنات على سيرفر هوستنجر السحابي (`/deploy`)**:
   - تشغيل المايجريشن الجديد `2026_10_27_081000_add_void_fields_and_create_payment_void_logs_table.php` على قاعدة بيانات هوستنجر ومزامنة الصلاحيات `RolesAndPermissionsSeeder`.
   - رفع ونشر التحديثات الحيوية المنجزة.
3. **ترقية وتطوير سجل العمليات الجراحية (`/surgeries`)**:
   - إضافة فلاتر متقدمة (حسب الجراح، نوع العملية، والقسم)، وزر تصدير كشف العمليات إلى Excel، وإحصائيات تفاعلية للعمليات المنجزة والمعلقة.
4. **تصدير سجل المرضى العام (`/patients`)**:
   - توفير زر لتصدير كشف سجل المرضى (الاسم، العمر، الجنس، الهاتف، العنوان، الرقم الوطني، وتاريخ التسجيل) إلى Excel للتقارير والإحصاء الطبي.
5. **تنظيف جذر المشروع**:
   - نقل السكربتات المؤقتة التي استخدمت في المطابقة الحسابية والفحص إلى مجلد `scratch/` للحفاظ على نظافة وهيكلية المشروع.

### 🔮 Next Up (الخطوات اللاحقة):
- **خارطة طريق منظومة خدمات ومراسلات الواتساب الموحدة (WhatsApp Services Roadmap & Granular Role Permissions)**:
  بناءً على التأسيس الهندسي المنجز في المرحلة 80 (إنشاء بطاقة وقسم `خدمات ومراسلات الواتساب (WhatsApp)` المستقل في شاشة إدارة الأدوار والصلاحيات `/roles/{role}/edit` ومفتاح التحكم الأول الخاص بالمختبر)، تتضمن الخطط المستقبلية تفعيل وإدارة مفاتيح التحكم السبعة المجمعة بنظام الصلاحيات وتضمين أزرار المشاركة في واجهات الأقسام المعنية:
  1. `[ إرسال نتائج التحاليل المخبرية عبر واتساب ]` (`send lab whatsapp`): ✅ **مكتمل ومفعل كلياً**، محمي بصلاحية مستقلة ومربوط بجدول وشاشة المختبر وتقرير الطباعة.
  2. `[ إرسال تقارير السونار عبر واتساب ]` (`send ultrasound whatsapp`): مخصص لدور فني السونار والموجات فوق الصوتية (`radiology_ultrasound`) لإرسال تقرير فحص السونار المعتمد للمريض برابط مشفر.
  3. `[ إرسال تقارير الرنين المغناطيسي عبر واتساب ]` (`send mri whatsapp`): مخصص لدور فني ومسؤول الرنين المغناطيسي (`radiology_mri`) لمشاركة تقرير الرنين مع المريض فور اعتماده.
  4. `[ إرسال تقارير المفراس الحلزوني عبر واتساب ]` (`send ct whatsapp`): مخصص لدور فني ومسؤول المفراس الحلزوني (`radiology_ct` / `radiology_staff`) لإرسال تقارير فحوصات CT Scan.
  5. `[ إرسال تقارير الإيكو عبر واتساب ]` (`send echo whatsapp`): مخصص لدور فني فحص وتخطيط صدى القلب (`radiology_echo`) لمشاركة نتائج وتقارير الإيكو.
  6. `[ إرسال تقارير الأشعة السينية عبر واتساب ]` (`send xray whatsapp`): مخصص لدور فني الأشعة العادية (`radiology_general`) لإرسال تقارير الأشعة الرقمية للمرضى.
  7. `[ إرسال الوصفات الطبية عبر واتساب ]` (`send prescription whatsapp`): مخصص لأطباء العيادات الاستشارية والطوارئ (`doctor`) لإرسال الروشتة/الوصفة الدوائية الإلكترونية المعتمدة للمريض عبر محادثة واتساب فورية.
- إضافة تقارير أرباح متقدمة وحصص العمليات الجراحية المعقدة في الخزينة (حصة الجراح، المساعد، التخدير، والمستشفى).
- ربط إشعارات WebSockets الفورية (Pusher/Echo) بين محطات الاستقبال والعيادات والمختبر.
- فحص دوري للأداء وتحسين سرعة استعلامات التقارير الكبيرة.

---

## 5. بروتوكول استئناف العمل عبر الجلسات والأجهزة (Continuity Protocol)
> تم حفظ وتجهيز كافة التوجيهات الجاهزة للنسخ في الملف المخصص: [SESSION_PROMPTS.md](SESSION_PROMPTS.md).

### 🚀 عند فتح محادثة جديدة أو العمل من جهاز آخر:
وجّه المساعد الذكي بالأمر التالي مباشرة أو أشر إلى الملف:
> **"راجع ملف SESSION_PROMPTS.md وابدأ العمل"**  
أو:  
> **"راجع قسم الحالة الحالية في `hospital-memory.md`، ولنبدأ العمل على [المهمة المطلوبة]."**

### 🏁 قبل إنهاء أي جلسة عمل:
اطلب من المساعد:
> **"حدّث قسم Current Project State في ملف hospital-memory.md وسجل العمليات في AGENTS.md بما تم إنجازه في هذه الجلسة."**
