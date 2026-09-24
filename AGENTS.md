# AI Agent Guidance for hospital-system

## Purpose
This file tells AI code agents how to understand and work with this Laravel-based hospital management application.

## Project overview
- Laravel PHP project using PHP 8.2 and Laravel 12.
- Frontend uses Vite, Tailwind, Bootstrap, Laravel Echo, and Pusher.
- Uses `spatie/laravel-permission` for role and permission management.
- Contains many domain-specific docs under `docs/`, especially for radiology, cashier, payment, scanner, and surgery workflows.

## Key commands
Use these commands in the repository root.

- `composer install`
- `npm install`
- `npm run dev`
- `npm run build`
- `composer test` or `php artisan test`
- `php artisan serve`
- `php artisan migrate --force`
- `php artisan config:clear`
- `php artisan permission:cache-reset`

The `composer.json` scripts also define:
- `composer run-script setup` for initial install and build
- `npm run dev` for frontend development

## Important files and directories
- `app/` — main application code: controllers, models, providers, observers, notifications, imports, exports.
- `bootstrap/` — Laravel bootstrap files.
- `config/` — application configuration.
- `database/` — migrations, seeders and factories.
- `resources/` — frontend templates, assets, views.
- `routes/` — route definitions.
- `tests/` — automated tests.
- `docs/` — feature-specific guides and workflow documentation.
- `next_step.md` — likely project-specific next actions.

## Documentation references
Consult these files before making changes or proposing fixes:
- `docs/INSTALLATION_GUIDE.md`
- `docs/SUMMARY_RADIOLOGY_PERMISSIONS.md`
- `docs/RADIOLOGY_PERMISSIONS_QUICK_GUIDE.md`
- `docs/RADIOLOGY_INQUIRY_PERMISSIONS.md`
- `docs/SCANNER_SYSTEM_README.md`
- `docs/SURGERY_STATIONS_GUIDE.md`
- `docs/PAYMENT_SYSTEM_COMPLETE.md`
- `docs/CASHIER_PAYMENT_SYSTEM.md`
- `RADIOLOGY_STAFF_ACCESS.md`
- `next_step.md`

## Agent behavior
- Prefer small, incremental changes.
- Preserve existing documentation and avoid duplicating long docs content; link to `docs/` files instead.
- Do not modify `vendor/`.
- Validate Laravel-specific changes with `php artisan test` or `composer test` when appropriate.
- When working on permissions or role-related logic, search for `spatie/laravel-permission`, `RolesAndPermissionsSeeder`, and `permission:cache-reset`.
- When working on frontend or realtime behavior, inspect `vite.config.js`, `resources/`, and `package.json` scripts.
- **CRITICAL GIT RULE (قاعدة Git المعتمدة)**: الرفع المباشر (`git push`) يتم حصراً على الفرع الرئيسي `main` دون إنشاء فروع جانبية.

## Session log (2026-09-24 — منظومة الوصفات الطبية الإلكترونية المدمجة E-Prescription وصرف الصيدلية الفوري)

### Done
- **قاعدة البيانات ونماذج الوصفات الإلكترونية (E-Prescriptions Schema & Models)**:
  * إنشاء ميغريشن `2026_09_24_100000_create_e_prescriptions_tables.php` لجدولي `prescriptions` و `prescription_items` مع توليد رقم وصفي موحد (`RX-YYYYMMDD-XXXX`) وتتبع حالات الصرف (`pending`, `partially_dispensed`, `dispensed`, `cancelled`).
  * إنشاء نماذج Eloquent: [Prescription.php](file:///c:/wamp64/www/hospital-system/app/Models/Prescription.php) و [PrescriptionItem.php](file:///c:/wamp64/www/hospital-system/app/Models/PrescriptionItem.php) وربط العلاقات مع [Doctor.php](file:///c:/wamp64/www/hospital-system/app/Models/Doctor.php)، [Patient.php](file:///c:/wamp64/www/hospital-system/app/Models/Patient.php)، و [Visit.php](file:///c:/wamp64/www/hospital-system/app/Models/Visit.php).
  * تزويد النظام بدليل الأدوية الرسمي المعتمد للتأمين الصحي العراقي (~220 دواء) مع ربط البدائل والوجبات الافتتاحية عبر الباذر [OfficialMedicinesSeeder.php](file:///c:/wamp64/www/hospital-system/database/seeders/OfficialMedicinesSeeder.php).
- **واجهة الطبيب والوصفة الطبية الرسمية (Doctor Consultation & Printable Prescription)**:
  * تحديث [DoctorVisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorVisitController.php) وواجهة الكشف [show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/show.blade.php) لربط قائمة الأدوية بدليل الأصناف مع الإكمال التلقائي واختيار الشكل الدوائي وحفظ الوصفة إلكترونياً تلقائياً.
  * تصميم قالب طباعة الوصفة الطبية [prescription-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/prescription-print.blade.php) بتصميم طبي احترافي يشمل بيانات الطبيب والمريض، العلامة المائية للراشيتة RX، الباركود، والتعليمات الصيدلانية.
- **منظومة اقتراح البدائل الدوائية وموافقة الطبيب اللحظية (Doctor-Pharmacy Alternative Approval System)**:
  * إنشاء ميغريشن `2026_09_24_120000_add_substitution_fields_to_prescription_items_table.php` لإضافة حقول طلب البديل وحالة الموافقة (`suggested_medicine_id`, `substitution_status`, `substitution_reason`, `substitution_response_notes`, `substitution_responded_at`).
  * تحديث [PrescriptionItem.php](file:///c:/wamp64/www/hospital-system/app/Models/PrescriptionItem.php) و [PharmacyPosController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/PharmacyPosController.php) بإضافة مسار `pos.items.suggest-alternative` لإرسال مقترح البديل من الصيدلية إلى الطبيب.
  * تحديث [DoctorVisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorVisitController.php) بإضافة مساري `visits.substitution-requests` و `prescriptions.items.respond-substitution` للتعامل مع موافقة ورفض الطبيب للبدائل وتحديث الخطة العلاجية تلقائياً.
  * تصميم لوحة التنبيهات اللحظية التفاعلية في شاشة كشف الطبيب [show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/show.blade.php) مع فحص تلقائي حي وموافقة/رفض بنقرة واحدة.
  * ربط التنبيهات في واجهة الصيدلية [resources/views/pharmacy/pos/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/pos/index.blade.php) لعرض حالة طلب البديل (🟡 بانتظار موافقة الطبيب / 🟢 وافق الطبيب معتمد / 🔴 رفض الطبيب).
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء وتحديث [PrescriptionWorkflowTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PrescriptionWorkflowTest.php) واجتياز كافة الاختبارات بنجاح 100%: **11 passed (51 assertions)** واجتياز حزمة اختبارات الصيدلية بالكامل: **24 passed (101 assertions)**.

## Session log (2026-09-23 — إصلاح وتفعيل أزرار حفظ إعدادات أسعار وتصنيفات المختبر ودعم stack scripts)

### Done
- **إصلاح أزرار حفظ أسعار التحاليل (الكلي والفردي)**:
  * إضافة `@stack('scripts')` و `@stack('styles')` في القالب العام [resources/views/layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php) لتمكين تشغيل كافة السكربتات المحقونة في جميع الواجهات.
  * ربط زر الحفظ الكلي العلوي والسفلي بـ `form="labPricingForm"` وإتاحة زر حفظ إضافي بأسفل الجدول لتسهيل الحفظ السريع.
  * تحديث [LabTestPricingController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabTestPricingController.php) لضبط `is_hi_active` و `is_moi_active` تلقائياً بحسب وجود أسعار الضمان والداخلية.
  * اجتياز كامل اختبارات `LabTestPricingSettingsTest` بنجاح (4 passed, 18 assertions).

## Session log (2026-09-23 — مطابقة وتحديث أسعار وتصنيفات تحاليل المختبر وفقاً لجدول الضمان الصحي 2025)

### Done
- **مطابقة تحاليل المختبر مع جدول الضمان الصحي الرسمي (PDF)**:
  * مطابقة 150 تحليلاً في قاعدة البيانات مع قائمة الـ PDF (381 تحليلاً).
  * تحديث وتصحيح تصنيفات 91 تحليلاً لاعتماد تصنيف الـ PDF (`Group`): نقل الهرمونات إلى `Hormone`، مؤشرات الأورام إلى `Immunity`، الفيروسات إلى `Infectious disease`، فحص السائل المنوي إلى `Microbiology`، وفصائل الدم إلى `Hematology`.
  * إدخال وتفعيل أسعار الضمان الصحي (`hi_price` و `is_hi_active`) لـ 134 تحليلاً مطابقاً.
  * إنشاء ميغريشن رسمي `2026_09_23_180000_update_lab_tests_pricing_and_categories_from_pdf.php` لضمان سريان التحديثات تلقائياً عند النشر على خادم هوستنجر (Hostinger).


## Session log (2026-09-20 — منظومة إدارة الصيدلية ونقطة البيع POS وتتبع الصلاحيات FEFO: الخطوات 1 إلى 4)

### Done
- **الخطوة 1: قاعدة البيانات والموديلات (Database Schema & Eloquent Models)**:
  * إنشاء ميغريشن `2026_09_20_230000_create_pharmacy_system_tables.php` المتضمن 6 جداول:
    - `medicines`: دليل الأصناف مع دعم الرمز الوطني الرسمي (`national_code` e.g. `01-C00-038`)، الباركود المزدوج للعلبة والشريط، الوحدات المتعددة (`main_unit` و `sub_unit`) مع معامل التحويل الديناميكي (`sub_units_count`)، التسعير المرن الثلاثي (نقدي، ضمان صحي، وزارة الداخلية)، والاشتراطات الرقابية (وصفة إجبارية، أدوية رقابية).
    - `medicine_alternatives`: جدول التكافؤ الحيوي وربط البدائل الدوائية العلمية والتجارية.
    - `pharmacy_services`: جدول الخدمات الصيدلانية غير المخزنية (حقن، قياس ضغط، سكر، غيار).
    - `medicine_batches`: جدول تتبع الشحنات والتشغيلات بنظام FEFO (الأقرب انتهاءً أولاً).
    - `pharmacy_sales`: فواتير المبيعات مع فصل حصة المريض عن التأمين وتعليق الفواتير (Hold).
    - `pharmacy_sale_items`: بنود الفاتورة وتفاصيل الوحدات والجرعات.
  * إنشاء موديلات Eloquent: [Medicine.php](file:///e:/hospital-system/app/Models/Medicine.php)، [MedicineAlternative.php](file:///e:/hospital-system/app/Models/MedicineAlternative.php)، [MedicineBatch.php](file:///e:/hospital-system/app/Models/MedicineBatch.php)، [PharmacyService.php](file:///e:/hospital-system/app/Models/PharmacyService.php)، [PharmacySale.php](file:///e:/hospital-system/app/Models/PharmacySale.php)، [PharmacySaleItem.php](file:///e:/hospital-system/app/Models/PharmacySaleItem.php).
  * خوارزمية الخصم الذكي `MedicineBatch::deductStock` مع فتح العلب المغلقة تلقائياً عند بيع الأشرطة المفردة.
- **الخطوة 2: لوحة التحكم ودليل الأدوية والخدمات والاستيراد (Catalog & Services & Excel Import)**:
  * الصلاحيات: تحديث `RolesAndPermissionsSeeder.php` بإضافة صلاحيات الصيدلية (`view pharmacy`, `manage medicines`, `create medicines`, `edit medicines`, `delete medicines`, `manage pharmacy services`, `import medicines`, `dispense medications`, `process pharmacy requests`) ومنحها لـ `pharmacy_staff` و `admin`.
  * المتحكمات: [MedicineController.php](file:///e:/hospital-system/app/Http/Controllers/Pharmacy/MedicineController.php) و [PharmacyServiceController.php](file:///e:/hospital-system/app/Http/Controllers/Pharmacy/PharmacyServiceController.php).
  * الواجهات: [index.blade.php](file:///e:/hospital-system/resources/views/pharmacy/medicines/index.blade.php)، [create.blade.php](file:///e:/hospital-system/resources/views/pharmacy/medicines/create.blade.php)، [edit.blade.php](file:///e:/hospital-system/resources/views/pharmacy/medicines/edit.blade.php)، [show.blade.php](file:///e:/hospital-system/resources/views/pharmacy/medicines/show.blade.php)، [import.blade.php](file:///e:/hospital-system/resources/views/pharmacy/medicines/import.blade.php)، و [services/index.blade.php](file:///e:/hospital-system/resources/views/pharmacy/services/index.blade.php).
- **الخطوة 3: إدارة الشحنات وتنبيهات الصلاحية (FEFO Dashboard)**:
  * المتحكم: [MedicineBatchController.php](file:///e:/hospital-system/app/Http/Controllers/Pharmacy/MedicineBatchController.php) لتتبع الوجبات، احتساب قيمة المخزون المعرض للتلف (< 90 يوم)، توريد شحنات جديدة، تعديل الأرصدة، والحجر الاحترازي `quarantined`.
  * الواجهات: [batches/index.blade.php](file:///e:/hospital-system/resources/views/pharmacy/batches/index.blade.php)، [batches/create.blade.php](file:///e:/hospital-system/resources/views/pharmacy/batches/create.blade.php)، [batches/edit.blade.php](file:///e:/hospital-system/resources/views/pharmacy/batches/edit.blade.php).
- **الخطوة 4: واجهة نقطة البيع السريعة والصرف (Dynamic POS)**:
  * المتحكم: [PharmacyPosController.php](file:///e:/hospital-system/app/Http/Controllers/Pharmacy/PharmacyPosController.php) مع بحث فوري بالباركود والاسم، التبديل بين بيع العلبة والشريط، اقتراح البدائل بضغطة زر، احتساب استقطاع الضمان (الفئات A إلى I)، تعليق واستئناف الفواتير (Hold & Resume)، ودعم مساري الدفع (كاشير صيدلية أو كاشير مركزي).
  * الواجهات: [pos/index.blade.php](file:///e:/hospital-system/resources/views/pharmacy/pos/index.blade.php)، [pos/receipt.blade.php](file:///e:/hospital-system/resources/views/pharmacy/pos/receipt.blade.php) (وصل حراري 80mm)، [pos/history.blade.php](file:///e:/hospital-system/resources/views/pharmacy/pos/history.blade.php)، [pos/show.blade.php](file:///e:/hospital-system/resources/views/pharmacy/pos/show.blade.php).
- **تحسينات الواجهة العامة (UI Fixes)**:
  * تصحيح انطواء القائمة الجانبية بإغلاق وسم `</div>` الخاص بكتلة الموارد البشرية في [resources/views/layouts/app.blade.php](file:///e:/hospital-system/resources/views/layouts/app.blade.php).
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء [PharmacyCoreTest.php](file:///e:/hospital-system/tests/Unit/PharmacyCoreTest.php)، [PharmacyCatalogTest.php](file:///e:/hospital-system/tests/Feature/PharmacyCatalogTest.php)، [PharmacyBatchTest.php](file:///e:/hospital-system/tests/Feature/PharmacyBatchTest.php)، و [PharmacyPosTest.php](file:///e:/hospital-system/tests/Feature/PharmacyPosTest.php) واجتياز جميع اختبارات النظام: **32 passed (144 assertions)** بنجاح 100%.

### Next Step
- **الخطوة 5**: الربط مع عيادات الاستشارية (استيراد وصفات المرضى مباشرة من شاشات الأطباء إلى نقطة البيع) والربط مع الكاشير المركزي لتحصيل المبالغ المحولة.

## Session log (2026-09-16 — نظام إدارة الموارد البشرية HR: سجل وإضبارة الموظفين والكوادر الطبية)

### Done
- **قاعدة البيانات وهيكل الموظفين**:
  * إنشاء جدول `employees` متكامل لدعم جميع تصنيفات الكوادر (طبي، تمريضي، فني، إداري، خدمات) مع الحقول الخاصة بتراخيص مزاولة المهنة وتواريخ الانتهاء ورقم هوية النقابة والمؤهل العلمي، ونوع التعاقد، والراتب الأساسي، والصورة الشخصية، والربط الاختياري مع حسابات `users`.
  * إنشاء موديل [Employee.php](file:///d:/ali%20altimimi/hospital-system/app/Models/Employee.php) مع دوال فحص الكادر الطبي، فحص انتهاء الترخيص، والـ Accessors للتسميات العربية.
  * ربط العلاقات مع `User` و `Department`.
- **الصلاحيات والأدوار (Spatie)**:
  * إضافة صلاحيات الموارد البشرية: `view hr`, `view employees`, `create employees`, `edit employees`, `delete employees`.
  * إنشاء دور `hr_manager` وتعيين الصلاحيات له في `RolesAndPermissionsSeeder.php`.
- **الواجهات والتحكم**:
  * إنشاء [EmployeeController.php](file:///d:/ali%20altimimi/hospital-system/app/Http/Controllers/HR/EmployeeController.php) مع البحث المتقدم، الفلاتر (نوع الكادر، القسم، الحالة، وتراخيص الممارسة)، وبطاقات الإحصائيات الفورية.
  * واجهة القائمة [index.blade.php](file:///d:/ali%20altimimi/hospital-system/resources/views/hr/employees/index.blade.php).
  * واجهة التسجيل الذكية [create.blade.php](file:///d:/ali%20altimimi/hospital-system/resources/views/hr/employees/create.blade.php) المزودة بـ Dynamic UI لإظهار قسم التراخيص الطبية تلقائياً عند اختيار الكادر الطبي/التمريضي/الفني وإخفائه للإداريين والخدمات.
  * واجهة التعديل [edit.blade.php](file:///d:/ali%20altimimi/hospital-system/resources/views/hr/employees/edit.blade.php).
  * واجهة الإضبارة الشاملة [show.blade.php](file:///d:/ali%20altimimi/hospital-system/resources/views/hr/employees/show.blade.php) مع تنبيهات صلاحية التراخيص ودعم الطباعة المباشرة.
  * إضافة قسم «الموارد البشرية» في القائمة الجانبية بـ [resources/views/layouts/app.blade.php](file:///d:/ali%20altimimi/hospital-system/resources/views/layouts/app.blade.php).
- **نظام المستمسكات والوثائق الرسمية المورثة للرمز الوظيفي**:
  * إنشاء جدول `employee_documents` وموديل [EmployeeDocument.php](file:///d:/ali%20altimimi/hospital-system/app/Models/EmployeeDocument.php) لتخزين كافة أنواع المستمسكات (بطاقة موحدة، بطاقة سكن، عقد عمل، وثيقة تخرج، ترخيص مهنة، وغيرها).
  * خوارزمية تسمية وتخزين منظمة تتبع الرمز الوظيفي ونوع المستمسك: `{$employee_code}_{$document_type}_{$timestamp}.{$ext}` في مجلدات مفهرسة بالرمز الوظيفي لكل موظف.
  * واجهة ديناميكية تفاعلية في شاشتي التسجيل والتعديل تتيح رفع عدة مستمسكات معاً مع فتح سطر جديد تلقائياً عند اختيار أي ملف أو بالضغط على إضافة مستمسك.
  * إضافة جدول المستمسكات المؤرشفة في الإضبارة [show.blade.php](file:///d:/ali%20altimimi/hospital-system/resources/views/hr/employees/show.blade.php) مع نافذة Modal للرفع السريع في أي وقت لاحق من قبل مسؤول الـ HR حصراً، ودعم التحميل والمعاينة والحذف الآمن.
- **شاشة إعدادات الموارد البشرية والتحكم بالحقول الإجبارية والقوائم**:
  * إنشاء جداول وموديلات [HrFieldRequirement.php](file:///d:/ali%20altimimi/hospital-system/app/Models/HrFieldRequirement.php) و [HrLookupOption.php](file:///d:/ali%20altimimi/hospital-system/app/Models/HrLookupOption.php) مع الباذر [HrSettingsSeeder.php](file:///d:/ali%20altimimi/hospital-system/database/seeders/HrSettingsSeeder.php).
  * لوحة تحكم متكاملة [resources/views/hr/settings/index.blade.php](file:///d:/ali%20altimimi/hospital-system/resources/views/hr/settings/index.blade.php) تتيح لمسؤول الموارد البشرية:
    1. تحديد أي حقل ليكون (إجبارياً مطلوباً * أو اختيارياً) ديناميكياً مع انعكاس ذلك فورياً على شاشات الإدخال وقواعد التحقق (Validation).
    2. إدارة أنواع التعيين والتعاقد (إضافة، تعديل، تفعيل، وتعطيل).
    3. إدارة أنواع المستمسكات الرسمية (إضافة، تفعيل، وتعطيل).
  * ربط شاشة الإعدادات بالقائمة الجانبية بالصلاحية `@can('view hr')`.
- **الاختبارات الآلية**:
  * تحديث وتوسيع [HREmployeeTest.php](file:///d:/ali%20altimimi/hospital-system/tests/Feature/HREmployeeTest.php) للتحقق من رفع المستمسكات، وراثة التسمية، التحقق الديناميكي للحقول الإجبارية، وإدارة القوائم بنجاح تام (12 passed, 62 assertions).

## Session log (2026-09-23 — توحيد جدول العمليات الجراحية الموحدة والمتعددة في جلسة واحدة)

### Done
- **جدول العمليات الجراحية الموحد (Unified Surgical Operations Table)**:
  * إعادة تصميم قسم العمليات في [surgeries/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/create.blade.php) ليكون جدولاً موحداً ومتناسقاً بالكامل:
    - يبدأ بالسطر الأول: **العملية (1)** الأساسية متضمنة: `[صنف العملية]` ⬅ `[نوع العملية]` ⬅ `[السعر د.ع]` ⬅ `[ملاحظات / توصيف]` ⬅ `[🔒 أساسية]`.
    - يقع أسفلها مباشرة زر: `[ ➕ إضافة عملية أخرى ]`.
    - عند الضغط على الزر، ينزل سطر جديد بتنسيق مطابق: **العملية (2)** ⬅ ثم **العملية (3)** وهكذا، مع زر حذف لكل عملية إضافية `[ 🗑️ حذف ]`.
    - ترقيم ديناميكي متسلسل يُحدّث تلقائياً عند الإضافة أو الحذف (`renumberAllOpRows()`).
    - شريط مالي فوري لحساب إجمالي كلفة كافة العمليات المحددة وعرض عدد العمليات المضافة لحظياً.
    - فلترة تلقائية متكاملة بين صنف العملية ونوعها مع استدعاء السعر الافتراضي لكل عملية تلقائياً.
    - تحويل الحاوية إلى جدول HTML فعلي (`#surgicalOperationsTable` مع `thead` و `tbody` و `table-layout: fixed`) لضمان محاذاة كافة الخلايا والمدخلات أفقياً ورأسياً على نفس الخط (`vertical-align: middle`).
    - توحيد ارتفاع حقول الإدخال والـ Select2 (ارتفاع 31px) وإزالة العناصر المسببة لعدم التناسق الرأسي.
  * إزالة التكرار في حقول ورقة التحويل الطبي / السكانر.
  * تصحيح تكرار تعريف متغير الجافاسكريبت `addOpIndex` الذي كان يعيق حدث الضغط.
  * **تطبيق الهيكل الموحد على واجهة التعديل [surgeries/edit.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/edit.blade.php)**:
    - تطبيق الجدول الموحد للعمليات الجراحية (`#surgicalOperationsTable`) بنفس التنسيق والمحاذاة التامة.
    - استرجاع العمليات الإضافية السابقة تلقائياً من قاعدة البيانات `$surgery->additionalOperations` وتعبئتها في الجدول مع إمكانية تعديل الأسعار، الملاحظات، الحذف، وإضافة عمليات جديدة.
    - ربط الحساب المالي الحي والتحقق عند الإرسال والتعديل وتحديث العمليات المتعددة في `SurgeryController::update`.
  * تحديث وتمرير كافة الاختبارات الآلية بنجاح 100%: **33 passed (166 assertions)**.

## Session log (2026-09-22 — معالجة اختفاء أزرار كشف الطبيب الاستشاري عند عودة نتائج الفحوصات وتوحيد واجهة توفر الأطباء)

### Done
- **معالجة اختفاء أزرار كشف الطبيب (Doctor Consultation Buttons & Auto-Complete Fix)**:
  * منع التحديث التلقائي لحالة زيارات الاستشارية إلى `completed` في `app/Models/Request.php` و `LabStaffController.php` و `RadiologyStaffController.php` و `StaffRequestController.php` و `RadiologyController.php` عند اكتمال نتائج المختبر أو الأشعة؛ حيث تم قصر الإكمال التلقائي حصراً على الزيارات المباشرة للمختبر/الأشعة التي ليس لها طبيب استشاري، وترك إغلاق الزيارة بيد الطبيب حصراً.
  * تحديث واجهة كشف الطبيب [show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/show.blade.php) بإضافة زر `[ 🔄 إعادة فتح الزيارة للمتابعة ]` وشارة `الزيارة مكتملة`، وإتاحة زر `[ تحويل لحجز عملية ]` أثناء الكشف (`in_progress`) وبعد الإكمال دون قيود.
  * تحديث `DoctorVisitController::showSurgeryForm` و `markNeedsSurgery` لقبول التحويل للعمليات أثناء الكشف وإكمال الزيارة والموعد تلقائياً عند التحويل.
  * تصحيح حالة الزيارة الحالية 161 وإعادتها فورياً إلى `in_progress`.
  * استبعاد الزيارات المكتملة (`completed`) والملغاة تلقائياً من جدول «مراجعو الفحوصات الطبية (الأشعة والمختبر)» في [DoctorQueueController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorQueueController.php) فور قيام الطبيب بإنهاء الزيارة.
- **واجهة طلبات المختبر**:
  * إزالة زر «زيارة مختبرية مباشرة» من شاشة طلبات المختبر [lab/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/index.blade.php).
  * إضافة ميزة البحث المباشر باسم المريض، رقم الهاتف، الطبيب، أو رقم الطلب في [LabStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabStaffController.php) و [lab/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/index.blade.php).
- **ربط تحويلات العمليات من العيادات الاستشارية بالاستعلامات (Inquiry Surgery Referrals)**:
  * تحديث [InquiryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/InquiryController.php) و [inquiry/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/inquiry/index.blade.php) لعرض جدول خاص بـ «مرضى العيادات الاستشارية المحولين للعمليات الجراحية» ببيانات المريض والطبيب وملاحظات العملية وزر مباشر لحجز العملية.
- **تبسيط واجهة حجز العمليات الجراحية (Surgery Booking Simplification)**:
  * إزالة حقول أطباء التخدير (المخدر الأول والثاني) من واجهة حجز العملية [surgeries/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/create.blade.php)؛ حيث أن تفاصيل وأطباء التخدير يتم تحديدها وتعبئتها حصراً داخل صالة العمليات ومحطة التخدير.
- **توحيد وتبسيط واجهة توفر الأطباء الاستشاريين**:
  * توحيد جداول الأطباء في جدول مركزي أنيق وموحد، وحذف عمود أيام العمل وتفعيل البحث والفرز في [consultant-availability/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/consultant-availability/index.blade.php).
  * تصحيح مسارات الاستدعاء المباشر على خادم هوستنجر الأونلاين.

- **منظومة العمليات المتعددة في جلسة واحدة (Multiple Operations per Surgery Session)**:
  * دعم إضافة أكثر من نوع عملية للمريض في نفس جلسة صالة العمليات كعمليات مرافقة/تكميلية (`additional_operations`).
  * تحديث واجهة الحجز [surgeries/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/create.blade.php) بإضافة بطاقة تفاعلية ديناميكية تسمح باختيار أي عدد من العمليات الثانوية مع أسعارها وملاحظاتها، مع شريط حساب مالي حي يجمع كلفة العملية الرئيسية + العمليات المرافقة = الإجمالي.
  * تحديث [SurgeryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgeryController.php) لحفظ وتخزين العمليات الإضافية تلقائياً في جدول `surgery_additional_operations` واستبعادها من الحقول المباشرة لجدول `surgeries`.
  * إضافة `fee` إلى `$fillable` في موديل [SurgeryAdditionalOperation.php](file:///c:/wamp64/www/hospital-system/app/Models/SurgeryAdditionalOperation.php).
  * تحديث واجهة تفاصيل العملية [surgeries/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/show.blade.php) لعرض سعر كل عملية إضافية بدقة في جدول العمليات الإضافية.
  * إضافة اختبار `test_can_book_surgery_with_multiple_operations_and_verify_fees` في [SurgeryTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryTest.php) واجتياز جميع الاختبارات الآلية (33 passed, 154 assertions) بنجاح 100%.
- **دمج منظومة الموارد البشرية والصيدلية مع الفرع الرئيسي ومزامنة GitHub**:
  * حفظ التعديلات المحلية ودمج فرع `origin/hr` مع `main` بسلام وبدون أي تعارضات (58 ملفاً جديداً).
  * تشغيل ميغريشن وباذر الموارد البشرية والصيدلية.
  * رفع أحدث كود مدمج إلى GitHub على فرع `main` (`77a6e16`).

### Next Steps
1. مراجعة كشف الزيارة 161 من قبل الطبيب والتأكد من إمكانية إنهاء الزيارة أو التحويل للعملية.

## Session log (2026-09-21 — منظومة الفحوصات الفرعية Sub-Tests وإصلاح طلبات الاستعلامات ومزامنة هوستنجر)

### Done
- **إصلاح حقل الضمان في طلبات الاستعلامات (Insurance Type in Requests)**:
  * إنشاء ميغريشن `2026_09_21_210000_add_insurance_type_to_requests_table.php` لإضافة `insurance_type` لجدول `requests`.
- **معمارية الفحوصات الفرعية التلقائية (Multi-Parameter / Sub-Tests Architecture)**:
  * إنشاء موديل [LabTestSubTest.php](file:///c:/wamp64/www/hospital-system/app/Models/LabTestSubTest.php) ومتحكم [LabTestSubTestController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabTestSubTestController.php).
  * توحيد واجهة [sub-tests/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab-tests/sub-tests/index.blade.php) لتطابق 100% تصميم واجهة القيم المرجعية [references.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab-tests/references.blade.php) بنظام التقسيم المزدوج (Two-Column Layout) ونموذج التعديل الجانبي الفوري بدون نوافذ منبثقة (Modals).
  * إنشاء ميغريشن `2026_09_21_211500_enhance_lab_results_for_sub_tests.php` لإضافة `sub_test_id` و `parent_test_name` وتعديل نوع `value` إلى نص.
  * تحديث [LabStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabStaffController.php) و [StaffRequestController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/StaffRequestController.php) لحفظ وتخزين المعايير الفرعية مع المدى المرجعي والوحدة في جدول `lab_results` وربطها بالزيارة والمريض مباشرة.
  * تحديث واجهات إدخال نتائج المختبر [lab/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/show.blade.php) و [staff/requests/show-lab.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/show-lab.blade.php) لتلوين كامل خلايا الصف فورياً (`table-success`, `table-danger`, `table-warning`) مع إطارات الحقول وعداد النتائج الحي.
  * تحديث شاشة الطباعة [staff/requests/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/print.blade.php) لتجميع المعايير الفرعية بتنسيق مخبري احترافي تحت الفحص الأب.
  * اجتياز جميع الاختبارات الآلية `php artisan test` (7 passed / 40 assertions) بنجاح.
- **المزامنة مع GitHub وسيرفر هوستنجر (Hostinger SSH Deployment)**:
  * رفع التحديثات إلى المستودع الرسمي `https://github.com/hmala/hospital-system.git` على الفرع `main`.
  * مزامنة وتحديث سيرفر هوستنجر بنجاح إلى أحدث Commit (`6e171f5`) عبر `git reset --hard origin/main`.
  * تحديث شجرة المعرفة البرمجية Graphify (2,841 عقدة و 4,118 علاقة).

### Next Steps (لحاسبة العمل غداً)
1. تشغيل `git pull origin main` على حاسبة العمل.
2. تشغيل `php artisan migrate --force` و `php artisan db:seed --class=HealthInsuranceCategorySeeder --force` على سيرفر الأونلاين (Hostinger) لتفعيل الجداول وفئات الضمان.

## Session log (2026-09-18 — منظومة شاشات الطابور والاستدعاء الذكي للعيادات الاستشارية)

### Done
- **قاعدة البيانات وهيكل الطابور (Queue Architecture & Database Migration)**:
  * إنشاء ميغريشن `2026_09_18_143000_add_queue_fields_to_appointments_table.php` لإضافة `queue_number` متسلسل لكل طبيب يومياً، و `called_at` لمتابعة توقيت الاستدعاء، ودعم حالات `calling` و `in_consultation`.
  * تحديث [Appointment.php](file:///c:/wamp64/www/hospital-system/app/Models/Appointment.php) بإضافة الحقول للدوال المساعدة `$fillable` و `$casts` وتسميات الحالات وألوانها.
- **متحكم شاشات الطابور والـ API اللحظي**:
  * إنشاء [DoctorQueueController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorQueueController.php) بدوال: `display` (شاشة العيادة)، `allClinicsDisplay` (شاشة الصالة المركزية)، `queueData` (بيانات JSON اللحظية)، `allClinicsData`، ودوال التحكم المشترك (`callNext`, `recall`, `startConsultation`, `skip`).
  * تسجيل المسارات العامة والمحمية في [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php) تحت البادئة `/queue`.
- **شاشات العرض التلفزيونية (TV Displays & Audio Chime)**:
  * إنشاء واجهة شاشة عيادة الطبيب [doctor-display.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/queue/doctor-display.blade.php) المزودة بتصميم داكن فاخر عالي التباين، وساعة رقمية، ونغمة جرس نقية (Web Audio API Chime)، ونداء صوتي عربي ذكي (Text-to-Speech)، مع وميض الاستدعاء وتحديث تلقائي كل 3 ثوانٍ.
  * إنشاء واجهة شاشة الصالة العامة [all-clinics-display.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/queue/all-clinics-display.blade.php) لشبكة عيادات المستشفى.
- **التحكم المزدوج (Dual Control) للاستقبال والطبيب**:
  * تحديث واجهة الاستشارية [consultant-availability/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/consultant-availability/index.blade.php) بإضافة عمود `# الدور` وزر `[ 📢 استدعاء ]` وزر `[ 🖥️ شاشة العيادة ]` ورابط شاشة الصالة العامة.
  * تحديث واجهة الطبيب [doctors/visits/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/index.blade.php) بمحطة استدعاء علوية متزامنة لحظياً للتحكم بالطابور والمناداة وبدء الكشف وفتح شاشة التلفاز.
  * إضافة رابط «شاشة طابور الانتظار» في القائمة الجانبية [layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php).
- **فصل الطابور الأولي ولوحة متابعة الفحوصات الذكية (Queue Separation & Results Workflow)**:
  * استبعاد المرضى الذين تم تحويلهم لزيارة فعلية من قائمة الانتظار الأولية (`whereDoesntHave('visit')`) لمنع عودة المريض للطابور الأولي عند طلب أشعة أو تحاليل.
  * إضافة لوحة مخصصة في محطة الطبيب `مراجعو الفحوصات الطبية (الأشعة والمختبر)` مع شارات الجاهزية (`🟢 النتائج جاهزة للمراجعة` مقابل `🟡 قيد الفحص`).
  * قفل ذكي (Smart Lock) لزر استدعاء نتائج الفحص (`[ ⏳ بانتظار صدور النتائج ]` باللون الرمادي غير القابل للضغط) حتى يكتمل الفحص من فني المختبر/الأشعة، ثم يتحول تلقائياً للأخضر المفعّل `[ 🔬 استدعاء لمتابعة النتائج ]`.
  * حماية برمجية في `DoctorQueueController::callForResults` تمنع استدعاء المريض للفحوصات غير المكتملة.
  * تحويل واجهة الطبيب [doctors/visits/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/index.blade.php) إلى نظام 4 تبويبات ذكية (محطة العيادة والمناداة، زيارات اليوم، زيارات معلقة، أرشيف الزيارات).
  * تجميع قوالب Blade وتأكيد اجتياز جميع الاختبارات الآلية `php artisan test` (7 passed / 40 assertions) بنجاح.

## Session log (2026-09-17 — توحيد معمارية الصلاحيات والأدوار وإصلاح 403 وأزرار الواجهات للـ Roles & Permissions)

### Done
- **توحيد معمارية الصلاحيات والأدوار (Roles & Permissions Architecture Standardization)**:
  * قصر التخطي الشامل في `Gate::before` بـ [AppServiceProvider.php](file:///c:/wamp64/www/hospital-system/app/Providers/AppServiceProvider.php) على مدير النظام الرئيسي المطلق `admin` فقط، وإلزام كافة الأدوار الأخرى (بما فيها `admin-hsop`) بالصلاحيات الدقيقة المحددة لها عبر مصفوفة الصلاحيات.
  * تحديث وتوحيد فحوصات الصلاحيات `$user->can(...)` في كافة المتحكمات الرئيسية:
    - المختبر والأشعة: [LabTestController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabTestController.php), [LabTestReferenceController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabTestReferenceController.php), [RadiologyTypeController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RadiologyTypeController.php), [StaffRequestController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/StaffRequestController.php).
    - المرضى والمواعيد والزيارات: [PatientController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/PatientController.php), [AppointmentController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/AppointmentController.php), [VisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/VisitController.php).
    - العمليات الجراحية والطوارئ: [SurgeryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgeryController.php), [EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php).
    - الكاشير والمدفوعات: [CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php).
  * تحديث واجهات Blade لربط أزرار الإضافة والتعديل والحذف والتفعيل بالصلاحيات `@can(...)` بدلاً من التحقق الجامد من الدور فقط.
  * تحديث [RolesAndPermissionsSeeder.php](file:///c:/wamp64/www/hospital-system/database/seeders/RolesAndPermissionsSeeder.php) وربط كافة الصلاحيات المستحدثة والتحقق من اجتياز كامل الاختبارات الآلية (7/7 tests passing).

## Session log (2026-09-16 — نظام إلغاء العمليات الجراحية والاسترجاع المالي للكاشير للضمان والنقدي)

### Done
- **نظام إلغاء العمليات والاسترجاع المالي (Surgery Cancellation & Refund Workflow)**:
  * تحديث `SurgeryController::destroy` و `SurgeryController::cancel` لتحرير الغرفة وإيقاف الفحوصات وتوجيه المبالغ المسددة إلى الكاشير كـ (مستحق استرجاع Refund) مع الحفاظ على سجل المدفوعات.
  * إنشاء ميغريشن `2026_09_16_090500_update_payment_status_in_surgeries_table.php` لتوسيع `payment_status` لدعم `refunded` و `partial` وتجنب خطأ MySQL 1265.
  * تحديث استعلام وقائمة الكاشير `CashierController::surgeriesIndex` و [index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/surgeries/index.blade.php) لإظهار العمليات الملغاة بشارة حمراء مميزة وزر استرجاع مباشر.
  * تحديث `CashierController::processSurgeryRefund` لاحتساب الاسترجاع النقدي الفعلي الدقيق لحصة المريض (Co-payment) وإلغاء مطالبات التأمين للضمان تلقائياً، وإنشاء سندات استرجاع سالبة، وتصفير المبالغ بعد الاسترجاع.
  * تنظيف واجهة الدفع [payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php) وإخفاء نموذج الدفع المعلق كلياً للعمليات الملغاة، وتخصيص بطاقة استرجاع مالي أنيقة، وعرض تفاصيل التحاليل والأشعة المدفوعة.
  * تحديث [MODIFIED_FILES.md](file:///c:/wamp64/www/hospital-system/MODIFIED_FILES.md) وقاعدة المعرفة [graphify](file:///c:/wamp64/www/hospital-system/graphify-out).

## Session log (2026-09-15 — تصحيح دفع العمليات الجراحية نقداً وتوحيد وصل الطباعة الحراري 80mm)

### Done
- **تصحيح الدفع النقدي للعمليات الجراحية في الكاشير**:
  * معالجة مشكلة عدم إمكانية سداد العملية نقداً للمرضى الذين لديهم ملف ضمان (`insurance_type = 'none'`) بسبب إرسال `null` لأعمدة `copay_percentage` و `claim_status`، وتعيين القيم الافتراضية `0.00` و `'none'` في [CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php).
  * تعديل هجرة جدول `payments` لجعل أعمدة الحصة والنسبة تقبل `nullable()->default(0.00)`.
  * إضافة عرض رسائل التنبيه والخطأ في واجهة دفع العمليات [payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php) ورفع تعريفات عناصر الجافاسكريبت للأعلى.
- **توحيد إيصالات الطباعة بقياس 80mm حراري**:
  * قفل أبعاد الطباعة في [receipt-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt-print.blade.php) بعرض 78mm ممركز على ورق A4 والورق العادي.

## Session log (2026-09-14 — جدول فئات ونسب استقطاع هيئة الضمان الصحي الوطني للقطاع الأهلي)

### Done
- **جدول فئات الضمان الصحي وقاعدة البيانات**:
  * إنشاء جدول `health_insurance_categories` لتخزين الفئات التسع (A إلى I) بحسب تعليمات وزارة الصحة / هيئة الضمان الصحي الوطني مع نسب الاستقطاع لكل نوع خدمة (استشارية، عمليات جراحية، مختبر، أشعة وسونار، خدمات ساندة، أدوية، طوارئ 0%، أسنان) مع دعم شرط الختم الحراري وتفعيل/تعطيل الفئات.
  * إضافة عمود `health_insurance_category_id` لجدول المرضى `patients`.
  * إضافة عمود `insurance_type` لجداول `appointments` و `medical_requests` و `surgeries` لتمكين حجز المريض نقدياً أو على الضمان مع إمكانية التحديد عند كل حجز.
  * إنشاء الباذر [HealthInsuranceCategorySeeder.php](file:///c:/wamp64/www/hospital-system/database/seeders/HealthInsuranceCategorySeeder.php) لملء الفئات التسع ونسبها الرسمية للقطاع الأهلي.
- **الموديلات ومنطق التحمل**:
  * إنشاء [HealthInsuranceCategory.php](file:///c:/wamp64/www/hospital-system/app/Models/HealthInsuranceCategory.php) مع دالة `getCopayForService($serviceType)`.
  * ربط العلاقة في [Patient.php](file:///c:/wamp64/www/hospital-system/app/Models/Patient.php) ودالة `getCopayPercentageFor($serviceType)` لحساب نسبة الاستقطاع تلقائياً بناءً على فئة المريض.
- **لوحة إدارة وتعديل الفئات**:
  * إنشاء [HealthInsuranceCategoryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/HealthInsuranceCategoryController.php) وواجهة [resources/views/health-insurance-categories/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/health-insurance-categories/index.blade.php) لتعديل نسب الاستقطاع ديناميكياً في أي وقت وتفعيل/تعطيل الفئات.
  * إضافة رابط «فئات ونسب الضمان الصحي» في القائمة الجانبية تحت قسم «الإعدادات».
- **واجهات المرضى والاستعلامات والكاشير**:
  * تحديث نماذج المرضى (إضافة وتعديل) لاختيار فئة الضمان الصحي (A - I) تلقائياً عند اختيار هيئة الضمان الصحي الوطني.
  * تحديث شاشات دفع الكاشير (المواعيد، الطلبات الطبية، العمليات الجراحية) لاعتماد النسبة المحددة لفئة المريض تلقائياً مع إظهار شارة الفئة وتنبيه الختم الحراري، مع الحفاظ على صلاحية الكاشير لتغيير النسبة بالأزرار الخماسية.
  * اجتياز جميع الاختبارات الآلية `php artisan test` بنجاح.

## Session log (2026-09-13 — طبقة تسعير الضمان الصحي وضمان وزارة الداخلية بنظام نسبة التحمل Co-payment)

### Done
- **معمارية وهيكل قاعدة البيانات**:
  * إضافة أعمدة التسعير للخدمات الطبية (`moi_price`, `is_moi_active`, `hi_price`, `is_hi_active`) لجداول (`lab_tests`, `radiology_types`, `emergency_services`, `doctors`, `departments`).
  * إضافة أعمدة الضمان للمرضى (`insurance_type`, `insurance_card_no`, `copay_percentage`) في جدول `patients`.
  * إضافة أعمدة اللقطة المالية وتجميد الأرقام الثلاثة (`total_amount`, `patient_share`, `insurance_share`, `insurance_type`, `insurance_card_no`, `copay_percentage`, `claim_status`) في جدول `payments`.
- **منطق التسعير والـ Trait**:
  * إنشاء [HasInsurancePricing.php](file:///c:/wamp64/www/hospital-system/app/Traits/HasInsurancePricing.php) بدوال الحساب المحاسبية الدقيقة `calculateInsurancePricing($insuranceType, $copayPercent)`.
  * ربطه مع موديلات [LabTest.php](file:///c:/wamp64/www/hospital-system/app/Models/LabTest.php), [RadiologyType.php](file:///c:/wamp64/www/hospital-system/app/Models/RadiologyType.php), [EmergencyService.php](file:///c:/wamp64/www/hospital-system/app/Models/EmergencyService.php), [Doctor.php](file:///c:/wamp64/www/hospital-system/app/Models/Doctor.php), [Department.php](file:///c:/wamp64/www/hospital-system/app/Models/Department.php).
- **واجهات الإدارة والتحكم بالتسعير**:
  * تحديث نماذج الإضافة والتعديل والمتحكمات لكل من التحاليل، الأشعة، خدمات الطوارئ، الأطباء، الأقسام، والمرضى.
- **محطة الكاشير والوصولات والتقارير**:
  * تحديث [CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php) لدعم استلام نسبة التحمل الخماسية والضمان مباشرة من الكاشير وتجميد الأرقام الثلاثة تلقائياً في سداد الاستشاريات، طلبات المختبر والأشعة، والطوارئ.
  * تحديث واجهات الكاشير [payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/payment-form.blade.php) و [request-payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/request-payment-form.blade.php) بأزرار خماسية تفاعلية سريعة (0%, 5%, 10%, 15%, 20%, 25%, 30%, 50%, 100%) مع إعادة حساب فورية للجدول وحصة المريض والصندوق.
  * تحديث إيصالات الدفع [receipt.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt.blade.php) وطباعة الفواتير [receipt-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt-print.blade.php) لعرض تفاصيل الضمان وحصة المريض والوزارة بدقة.
  * إضافة فلاتر الضمان وإحصائيات مطالبات التأمين في تقرير الكاشير [report.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/report.blade.php).
- **الاختبارات الآلية**:
  * إنشاء [InsurancePricingTest.php](file:///c:/wamp64/www/hospital-system/tests/Unit/InsurancePricingTest.php) واجتياز جميع اختبارات التحمل والتسعير بنجاح (All 6 tests passing).

## Session log (2026-09-07 — سجل وأرشيف المريض الشامل للاستعلامات والماسح الضوئي)

### Done
- **تنفيذ واجهة سجل وأرشيف المريض الشامل للاستعلامات**:
  * إنشاء موديل [PatientDocument.php](file:///c:/wamp64/www/hospital-system/app/Models/PatientDocument.php) وهجرة جدول `patient_documents` لدعم أرشفة وثائق المرضى (هويات، تقارير، موافقات، نتائج، سكنر).
  * ربط علاقات المريض الشاملة في [Patient.php](file:///c:/wamp64/www/hospital-system/app/Models/Patient.php) مع (`documents`, `emergencies`, `surgeries`, `radiologyRequests`, `requests`).
  * برمجة دوال العرض والرفع والحذف والطباعة في [InquiryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/InquiryController.php).
  * تسجيل المسارات في [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php).
  * إنشاء واجهة السجل والأرشيف التفاعلية [patient_history.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/inquiry/patient_history.blade.php) المزودة بربط مباشر مع الماسح الضوئي (Scanner Bridge) على `localhost:5000`.
  * إنشاء نموذج طباعة إضبارة المريض الشاملة [patient_dossier_print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/inquiry/patient_dossier_print.blade.php).
  * إضافة رابط «سجل وأرشيف المرضى» في القائمة الجانبية بـ [resources/views/layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php) تحت قسم «إدارة المرضى» بالصلاحية `@can('view inquiries')`.
  * مسح وتحديث كاش الـ Routes والـ Views والإعدادات.

### Next Steps / Pending
- تجربة سحب المستندات عبر السكنر المباشر والتحقق من سير العمل مع موظفي الاستعلامات.

### Issues known
- لا يوجد حالياً.

## Domain guidance
- This repository contains many Arabic-language docs and comments; be careful not to lose or mistranslate them.
- Key domain areas include radiology, cashier/payment workflows, scanner integration, and surgery stations.
- When making changes that affect UI or workflow state, verify related docs under `docs/` and root markdown files.
