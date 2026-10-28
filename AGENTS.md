# AI Agent Guidance for hospital-system
## Context & Memory
Always read `hospital-memory.md` before starting any task to understand architecture, coding standards, and project state.
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
- `hospital-memory.md` — Primary memory, architecture map, and current project status.
- `.agents/rules/hospital-core.md` — Core system rules and quick domain controller/model map.
- `docs/SESSION_ARCHIVE.md` — Complete historical session archive (Phases 1 to 30).
- `docs/المرجع_الشامل_للنظام.md` — Comprehensive Arabic system reference.
- `docs/legacy/` — Archived transition notes (`next_step.md`, `MODIFIED_FILES.md`, `RADIOLOGY_STAFF_ACCESS.md`, `SURGERY_TESTS_FIX.md`).
- `graphify-out/wiki/index.md` / `graphify-out/GRAPH_REPORT.md` — Code AST graph and dependencies.

## Agent behavior
- Prefer small, incremental changes.
- Preserve existing documentation and avoid duplicating long docs content; link to `docs/` files instead.
- Do not modify `vendor/`.
- Validate Laravel-specific changes with `php artisan test` or `composer test` when appropriate.
- When working on permissions or role-related logic, search for `spatie/laravel-permission`, `RolesAndPermissionsSeeder`, and `permission:cache-reset`.
## Session log (2026-10-28 — المرحلة 121: توحيد تقرير طباعة تحاليل الطوارئ 100% مع التقرير السريري الموحد، وعزل فحص الإدرار العام G.U.E في صفحة منفصلة بالكامل Emergency Lab Print Report 100% Unification & Dedicated G.U.E Page 2 Separation)

### Done
- **إعادة بناء وتوحيد واجهة طباعة تقرير تحاليل الطوارئ ([resources/views/staff/requests/emergency-lab-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/emergency-lab-print.blade.php))**:
  * مطابقة بصرية وهيكلية تامة 1:1 مع `resources/views/staff/requests/print.blade.php` بألوان المستشفى الرسمية (`#005a9c` أزرق داكن، `#0284c7` أزرق سماوي) بدلاً من النمط القديم.
  * شريط علوي موحد متكامل يحتوي على أزرار: `[ 🖨️ طباعة التقرير ]`، `[ 💬 إرسال واتساب للمريض ]`، روابط تقارير الأجهزة المرفقة، أزرار تبديل نمط الطباعة (ورق مروس Letterhead مقابل ورق أبيض كامل Full)، وضابط المسافة العلوية.
  * ترويسة رسمية ثنائية باللغتين العربية والإنجليزية تتوسطها صورة شعار المستشفى المضمنة بتقنية base64 للظهور الفوري دون أي تأخير طباعي.
  * بطاقة بيانات المريض وقسم الطوارئ السريرية ثلاثية المناطق (Patient Demographics, Order & STAT Coverage, Direct QR Code).
- **عزل فحص الإدرار العام (General Urine Examination - G.U.E) في صفحة مستقلة (Page 2 Dedicated Architecture)**:
  * فحص وتقسيم النتائج آلياً عبر دالة الفلترة الذكية `$isGeneralUrine`.
  * عند وجود فحوصات عامة مع فحص الإدرار:
    - **الصفحة الأولى (Page 1)**: تتضمن الفحوصات العامة مع الترويسة الكاملة وبطاقة المريض وجدول النتائج والتذييل والتوقيعات الرسمية.
    - **الصفحة الثانية (Page 2 - Dedicated G.U.E Page)**: فاصل صفحات طباعي صارم (`.gue-page-break`)، ترويسة مصغرة، شريط مريض مختصر، شريط عنوان فحص الإدرار، وجدول الـ 4 أعمدة المتخصص (`INVESTIGATION / TEST NAME`, `Result`, `Reference Range`, `Status`)، ومربع التوقيعات والختم المعتمد الخاص بالصفحة الثانية.
  * عند طلب فحص الإدرار كفحص وحيد مستقل: يطبع كتقرير رئيسي متكامل على الصفحة الأولى مع الجدول الرباعي دون إنشاء صفحة ثانية فارغة.
- **توسيع المعالجة بالكنترولر وتحديث العلاقات ([app/Http/Controllers/StaffRequestController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/StaffRequestController.php))**:
  * تحميل الفحوصات الفرعية للتحاليل المركبة (`labTests.subTests`, `requestTests.labTest.subTests`) لمنع استعلامات N+1.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/UnifiedEmergencyLabStationAndReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/UnifiedEmergencyLabStationAndReportTest.php))**:
  * إضافة اختبارات ميزة تغطي عزل فحص الإدرار في الصفحة الثانية عند دمجه مع فحوصات أخرى، وطباعته منفرداً على الصفحة الأولى عند عدم وجود فحوصات أخرى بنجاح تام (`8 passed, 52 assertions`).

## Session log (2026-10-28 — المرحلة 120: توحيد ومطابقة محطة تحاليل الطوارئ 100% مع محطة المختبر السريرية Emergency Lab Station 100% Parity with Standard Lab Station & Clinical Architecture)

### Done
- **إعادة بناء وتوحيد واجهة محطة تحاليل الطوارئ ([resources/views/staff/requests/emergency-lab-show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/emergency-lab-show.blade.php))**:
  * مطابقة تامة 1:1 لجميع تفاصيل التصميم، البنية الهيكلية، وأدوات العمل الموجودة في `resources/views/lab/show.blade.php`.
  * شريط علوي موحد متكامل يحتوي على أزرار الإجراءات: `[ 🖨️ طباعة التقرير ]`، `[ 💬 إرسال النتيجة عبر واتساب ]`، `[ 💾 حفظ مؤقت (مسودة) ]`، `[ 🚀 اعتماد نهائي وإرسال للطبيب ]`، `[ ➕ إضافة تحاليل ]`، و `[ ⬅️ القائمة ]`.
  * بطاقة بيانات المريض والطلب السداسية بالأيقونات الدائرية الملونة: اسم المريض، رقم الهاتف مع زر واتساب، العمر/الجنس، فصيلة الدم، الطبيب وقسم الطوارئ، وحالة السداد المالي.
  * قسم إلحاق التحاليل الذكي القابل للطي `#appendTestsSection`: عارض باقات التحاليل (Packages)، مجموعات المفضلات، المفضلات الفردية، البحث السريع مع الفلترة العربية، وقائمة الفحوصات المقسمة حسب التصنيف الرئيسي.
  * جدول النتائج السريرية الموحد `#resultsTable` بكافة أعمدته: الرقم، اسم الفحص (مع دعم الفحوصات المركبة والشجرية `subTests`، وفحوصات مرفقات الأجهزة)، النتيجة، الوحدة، المدى المرجعي، مؤشر الحالة الحي (Flag: طبيعي، مرتفع، منخفض)، وحقل مرفق تقرير الجهاز بنمط الكبسولة الأنيقة `pill-upload-wrapper`.
  * شريط ملخص تقييم النتائج اللحظي: عداد النتائج الطبيعية، المرتفعة، المنخفضة، وغير المدخلة.
  * محرك JavaScript الموحد: تقييم النتائج الفوري بالألوان، معادلة فريدوالد لباقة الدهون (Friedewald: VLDL & LDL)، وتنبيه الدهون الثلاثية $\ge 400\text{ mg/dL}$.
- **التحصين ومعالجة السيرفر ([app/Http/Controllers/StaffRequestController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/StaffRequestController.php))**:
  * تطوير دالة `completeEmergencyLab` لدعم حفظ نمط `test_results` الموحد ونمط `results` الكلاسيكي معاً.
  * معالجة مرفقات الفحوصات الفردية `test_attachments` وإزالتها وحفظ المرفق العام.
  * إضافة دالة `autoCalculateLipidOnTestResults` لحساب معادلات باقة الدهون آلياً على السيرفر.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/UnifiedEmergencyLabStationAndReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/UnifiedEmergencyLabStationAndReportTest.php))**:
  * 6 اختبارات ميزة تغطي أزرار الطوارئ في قائمة المختبر، تحميل الواجهة السريرية المطابقة بالكامل، حفظ واعتماد النتائج بالنمطين، طباعة التقرير الطبي، وإلحاق التحاليل بنجاح تام (`6 passed, 38 assertions`).

## Session log (2026-10-28 — المرحلة 119: توحيد المحطة السريرية الجراحية للمقيم والتمريض، ومصفوفة صلاحيات التبويبات بالكامل Unified Clinical Surgery Station & Dynamic Role-Based Tab Permissions Matrix)

### Done
- **قاعدة البيانات وموديل مصفوفة صلاحيات التبويبات السريرية ([database/migrations/2026_10_28_091831_create_surgery_tab_permissions_table.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_28_091831_create_surgery_tab_permissions_table.php))**:
  * إنشاء جدول مخصص `surgery_tab_permissions` (`role_name`, `tab_key`, `access_level: hidden, read, write`).
  * بناء الموديل [app/Models/SurgeryTabPermission.php](file:///c:/wamp64/www/hospital-system/app/Models/SurgeryTabPermission.php) متضمناً ثوابت التبويبات الـ 8، مصفوفة الضبط الطبي الافتراضي، ودوال احتساب الصلاحيات التراكمية للمستخدم (`getUserTabPermissions`).
- **لوحة تحكم الإدارة لمصفوفة الصلاحيات ([app/Http/Controllers/SurgeryTabPermissionController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgeryTabPermissionController.php) & [resources/views/surgeries/settings/tabs.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/settings/tabs.blade.php))**:
  * واجهة مصفوفية مركزية تعرض الأدوار (طبيب مقيم، كادر تمريض، طبيب جراح، منسق عمليات، كادر عمليات، مدير نظام) أمام التبويبات الثمانية مع قوائم منسدلة فورية ملونة (`🚫 مخفي`, `👁️ قراءة فقط`, `✍️ قراءة وإدخال`).
  * إمكانية حفظ التعديلات الجماعية، واستعادة الضبط الطبي الافتراضي بنقرة زر واحدة، وتوثيق النشاط الإداري في `UserActivityLogger`.
- **المحطة السريرية الجراحية الموحدة الذكية ([app/Http/Controllers/ClinicalStationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/ClinicalStationController.php) & [resources/views/surgery-stations/clinical/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * واجهة موحدة ونقية تستبدل الواجهات السابقة وتتشكل وتتكيف لحظياً حسب صلاحيات هوية المستخدم المسجل:
    1. **السيرة المرضية (History)**: الشكوى، التاريخ المرضي والجراحي والدوائي، والحساسية.
    2. **العلامات الحيوية والفحص (Vitals & Physical Exam)**: بطاقات الضغط والحرارة والنبض والتنفس والأكسجين والسكر والوعي (GCS/CRT)، مع منحنيات Chart.js البيانية وجدول السجل التاريخي.
    3. **الفحوصات والمختبر والأشعة (Lab & Radiology Tests)**: عرض نتائج التحاليل والتقارير الإشعاعية.
    4. **الخطة الطبية وتوصيات الجراحة (Medical Plan)**: خطة ما قبل الجراحة، توصيات ما بعد العملية، ومراجعة الأجهزة.
    5. **الأدوية وجدول الإعطاء (Treatments)**: وصف الأدوية، وتسجيل إعطاء الجرعات للممرض بنقرة واحدة، وسجل الإعطاء.
    6. **سجل المتابعة الدوري (Follow Up Sheet)**: جولات المرور الصباحية والمسائية وتدوين ملاحظات الأطباء والتمريض.
    7. **رصد السوائل (Intake & Output)**: ميزان السوائل الوريدية والفموية والدم في مقابل الإدرار والنزح، واحتساب الصافي آلياً.
    8. **الرعاية وملاحظات التمريض (Nursing Care & Discharge)**: ملاحظات التمريض، وتعيين الممرض، وتعليمات التخريج.
  * تحويل الروابط القديمة (`resident-station.show` و `nursing-station.show`) بسلاسة وشفافية 100% للمحطة الموحدة لضمان التوافق التام بدون كسر أي زر قديم.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php))**:
  * بناء 8 اختبارات ميزة تغطي عرض وتعديل المصفوفة، استعادة الافتراضي، تشكل التبويبات حسب الصلاحيات، تسجيل العلامات الحيوية، إضافة وإعطاء الأدوية، تسجيل المتابعة، والتحويل الشفاف للروابط القديمة بنجاح تام (`8 passed, 37 assertions`).

## Session log (2026-10-28 — المرحلة 118: إعادة هيكلة جدول وقائمة انتظار العمليات الجراحية، تبويبات أيام الأسبوع، وتخصيص الصالات والأوقات يدوياً مع إضافة صالات جديدة فورياً Operation Theaters & Realtime Manual Schedule Customization)

### Done
- **قاعدة البيانات ونموذج صالات العمليات الجراحية المخصص ([database/migrations/2026_10_28_083000_create_operation_theaters_table_and_add_to_surgeries.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_28_083000_create_operation_theaters_table_and_add_to_surgeries.php))**:
  * إنشاء جدول مخصص لصالات العمليات الجراحية `operation_theaters` (`id`, `name`, `code`, `is_active`, `timestamps`) لفصل صالات العمليات الجراحية عن غرف الرقود وأسرة الإقامة.
  * إضافة حقل `operation_theater_id` إلى جدول `surgeries` للربط الرسمي والمباشر.
  * إنشاء الموديل [app/Models/OperationTheater.php](file:///c:/wamp64/www/hospital-system/app/Models/OperationTheater.php) وتحديث [app/Models/Surgery.php](file:///c:/wamp64/www/hospital-system/app/Models/Surgery.php) بالعلاقة `operationTheater()`.
- **الكنترولر والمسارات البرمجية للتخصيص اللحظي ([app/Http/Controllers/SurgeryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgeryController.php))**:
  * إضافة مسار ودالة `quickStoreTheater` لإنشاء صالة عمليات جديدة فورياً عبر AJAX وإضافتها لقائمة الصالات المعتمدة.
  * إضافة مسار ودالة `quickUpdateSchedule` لتحديث صالة العملية ووقتها اليدوي المخصص فورياً لأي مريض عبر AJAX وبث التحديثات.
  * تطوير دالة `waiting()` لجلب عمليات اليوم المختار وتبويبات أيام الأسبوع وقائمة الصالات النشطة.
- **إعادة تصميم واجهة قائمة الانتظار والجدول اليومي ([resources/views/surgeries/waiting.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/waiting.blade.php))**:
  * تبويبات أيام الأسبوع العلوية (السبت إلى الجمعة) للتنقل السلس مع عداد العمليات وتمييز اليوم الحالي.
  * جدول سريري نقي وواضح لعمليات اليوم المختار بالأعمدة المتفق عليها:
    - **المريض**: الاسم، رقم الملف، والعمر/الجنس.
    - **العملية الجراحية**: نوع العملية وشارة العمليات الإضافية.
    - **الطبيب الجراح**: اسم الطبيب المسؤول.
    - **الصالة المخصصة (Customizable)**: عرض اسم الصالة الفعلي مع أيقونة تعديل سريعة بنقرة واحدة.
    - **الوقت (Customizable)**: عرض الوقت اليدوي المخصص (مع مؤقت حي بالثواني للعملية الجارية) وإمكانية تعديله فورياً.
    - **حالة المسار**: أوسمة واضحة (🔵 مجدولة، 🟡 بالانتظار، 🟢 داخل الصالة جارية، 🟣 منتهية/خرج).
    - **الإجراءات السريعة**: أزرار النقل المباشر (`[دخول]`، `[تحويل وبدء]`، `[إنهاء]`) وملف العملية.
  * نافذة مودال منبثقة تفاعلية لتعديل الصالة والوقت يدوياً مع إمكانية إضافة اسم صالة جديدة من نفس النافذة فورياً بدون مغادرة الصفحة.
- **حزمة الاختبارات الآلية الحصينة ([tests/Feature/SurgeryWaitingListFlightBoardTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryWaitingListFlightBoardTest.php))**:
  * بناء 8 اختبارات ميزة تغطي الصلاحيات، عرض القائمة وأيام الأسبوع، حالات المسار، الدخول والبدء والإنهاء، إضافة الصالات الجديدة عبر AJAX، وتحديث الموعد والصالة بنجاح تام (`8 passed, 28 assertions`).

## Session log (2026-10-27 — المرحلة 117: الحساب الآلي الذكي لمعادلات باقة الدهون Lipid Profile - Friedewald Formula: LDL & VLDL في واجهة المختبر والسيرفر مع التنبيه السريري والتحصين الشامل)

### Done
- **تجهيز باقة الدهون وفحص VLDL في قاعدة البيانات**:
  * إنشاء وترحيل `2026_10_27_112000_seed_lipid_profile_composite_test_and_vldl.php`.
  * إضافة فحص `Very Low Density Lipoprotein` (VLDL) بمدى مرجعي طبيعي (5 - 30 mg/dL).
  * إنشاء فحص باقة الدهون الشاملة `Lipid Profile` كفحص مركب (`sub_tests`) بالباراميترات الخمسة المعتمدة: Total Cholesterol, Triglyceride, High Density Lipoprotein, Low Density Lipoprotein (Calculated), Very Low Density Lipoprotein (Calculated).
- **محرك الحساب الآلي اللحظي في واجهة المختبر ([resources/views/lab/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/show.blade.php))**:
  * مطابقة مرنة للأسماء الطبية للدهون تدعم العربية والإنجليزية والرموز واختصارات TG, TC, HDL, LDL, VLDL.
  * حساب فوري لـ VLDL بمجرد إدخال قيمة الدهون الثلاثية ($VLDL = TG / 5$) وتحديث وسام `[ ⚡ محسوب: TG / 5 ]`.
  * حساب فوري لـ LDL بمجرد توفر الكوليسترول الكلي والـ HDL والدهون الثلاثية طبقاً لمعادلة فريدوالد ($LDL = TC - HDL - \frac{TG}{5}$).
  * إضافة بانيل تنبيه سريري باللون الكهرماني يظهر فورياً أعلى الصف عند تجاوز الدهون الثلاثية 400 mg/dL ($TG \ge 400$) بأن معادلة فريدوالد غير دقيقة علمياً ويُنصح بالقياس المخبري المباشر (Direct LDL).
  * دعم التعديل اليدوي للفني وإظهار شارة `[ ✍️ مدخل يدوياً ]` عند رغبته في إدخال قيمة يدوية مقاسة مباشرة دون إجبار.
- **التحصين ومعالجة السيرفر ([app/Http/Controllers/LabStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabStaffController.php))**:
  * إضافة دالة `autoCalculateLipidResults` لضمان احتساب وتخزين نتائج VLDL و LDL في قاعدة البيانات والـ JSON عند الحفظ حتى في حال تم إرسالها فارغة أو تم حفظ الطلب كمسودة.
- **حزمة الاختبارات الآلية الحصينة ([tests/Feature/LipidProfileAutoCalculationTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LipidProfileAutoCalculationTest.php))**:
  * بناء 4 اختبارات ميزة تغطي الحساب الآلي الصحيح لمعادلتي VLDL و LDL، عدم الحساب القسري وظهور التنبيه عند تجاوز الدهون الثلاثية 400، الحساب في الفحوصات الفردية والمركبة، واحترام الإدخال اليدوي بنجاح تام (`4 passed, 25 assertions`).

## Session log (2026-10-27 — المرحلة 116: منظومة إبطال وإلغاء الدفع وإعادة فتح الوصولات للتصحيح والاسترداد مع واجهة تدقيق رقابية مستقلة وصلاحية مشددة Receipt Void & Payment Reversal System, Dedicated Audit Page, Granular Permissions & Full Cascade Re-billing Workflow)

### Done
- **بنية الصلاحيات والتحصين الأمني (Spatie Permissions & Gating)**:
  * إضافة صلاحية مستقلة ومحصورة في `RolesAndPermissionsSeeder`: `void payment receipt`.
  * ربط الصلاحية حصراً بالأدوار الرقابية والمسؤولة (`admin` و `accountant`) مع إمكانية منحها بالاسم للمشرف الإداري أو الاستعلامات، وحجبها عن الكاشير العادي لمنع تضارب المصالح.
  * حماية مسارات الإبطال وعرض الاستمارة برمجياً عبر `auth()->user()->can('void payment receipt')` في `PaymentVoidController` وميدلوير الصلاحيات.
- **قاعدة البيانات وسجل التدقيق الرقابي (Database Schema & Audit Trail)**:
  * إضافة حقول الرقابة إلى جدول `payments`: `voided_at`, `voided_by`, `void_type` (`rebill`, `refund`), `void_reason`, `original_receipt_number`.
  * إنشاء جدول مخصص لتدقيق الإلغاءات `payment_void_logs` لتوثيق تاريخ وحركة كل وصل ملغى والمستخدم والمبالغ ونوع الإجراء وسبب الإبطال بالثانية.
  * تحديث موديل `Payment` بدوال `voidedBy()`, `voidLogs()`, و `isVoided()`.
- **الكنترولر ومنطق الأعمال الصارم (Strict Controller & Form Request)**:
  * إنشاء `app/Http/Requests/VoidPaymentRequest.php` للتحقق من الصلاحيات وسلامة البيانات والسبب الإلزامي (الحد الأدنى 5 أحرف).
  * إنشاء `app/Http/Controllers/PaymentVoidController.php` مع دالتين رئيسيتين:
    1. `showVoidForm(Payment $payment)`: عرض واجهة الإبطال المخصصة مع بطاقات ملخص المريض والوصل والمبالغ.
    2. `void(VoidPaymentRequest $request, Payment $payment)`: معالجة الإبطال داخل `DB::transaction` متكاملة، ودعم خياري:
       - **إعادة فتح القيد للتسديد والتصحيح (`rebill`)**: تصفير حالة الدفع وتفريغ رقم الوصل وتاريخ القبض، وإعادة القيد لطابور الكاشير فوراً، مع إعادة السجلات المرتبطة (طوارئ، مواعيد، طلبات مختبر وأشعة) إلى حالة الانتظار غير المسدد `pending`.
       - **إبطال نهائي واسترداد للمريض (`refund`)**: تثبيت حالة الوصل كمسترد، وإلغاء الطلب أو الموعد المرتبط نهائياً.
    3. تسجيل حركة النشاط الإداري والرقابي الحساس في `UserActivityLogger`.
- **واجهات المستخدم الفاخرة وتجربة الاستخدام (Dedicated UI & Visual Excellence)**:
  * معالجة مشكلة قص المودل وظهوره أسفل الشاشة، بالانتقال إلى صفحة مستقلة متكاملة وواسعة `resources/views/cashier/payments/void.blade.php`:
    - شريط علوي بلون الرقابة الفاخر (Crimson Gradient) يوضح أبعاد العملية.
    - بطاقات إحصائية ثلاثية علوية (بيانات الوصل الأصلي، بيانات المريض وتغطيته، والمبلغ المقبوض نقداً وحصة الضمان).
    - بطاقتان كبسوليتان تفاعليتان للاختيار بين "إعادة فتح القيد للتسديد والتصحيح" و "إبطال نهائي واسترداد نقدي للمريض".
    - أزرار أسباب سريعة مسبقة التجهيز (خطأ بفئة الضمان، تكرار الفحص، رغبة المريض، تصحيح إداري) لتعبئة حقل السبب بنقرة واحدة.
    - تنبيه أمني واضح للمستخدم بمسؤولية التدقيق والرقابة المالية.
  * في شاشة الوصل (`receipt.blade.php`): إظهار زر `[ ⚠️ إبطال الوصل / إعادة فتح للتصحيح ]` للمستخدم المخول فقط، وتلوين الوصل الملغى بشارة ملفتة `[ 🚫 وصل ملغى ومبطل ]`.
- **حزمة الاختبارات الآلية الشاملة (Automated Feature Tests)**:
  * إنشاء `tests/Feature/PaymentVoidAndReversalTest.php` بـ 5 اختبارات ميزة تغطي كافة السيناريوهات بنجاح تام (`5 passed, 29 assertions`).

## Session log (2026-10-26 — المرحلة 115: مطابقة وتوحيد واجهة دفع الطوارئ 100% مع واجهة الكاشير الرئيسية، تحصين نسب فئات الضمان، وإرجاع وصولات المريضة فاطمة Emergency Cashier Form 100% Parity with Request Payment, Strict Category Copay Lockdown & Unpaid Receipt Reversal)

### Done
- **إرجاع وتصحيح وصولات المريضة فاطمة صباح (الفئة H)**:
  * إرجاع قيد ودفع خدمات الطوارئ رقم #1191 إلى "غير مسدد" ومحمي بنسبة 0% الرسمية للفئة H بمبلغ 0 د.ع للمريض وتغطية 100% للضمان (75,000 د.ع).
  * إرجاع وتصحيح وصل التحاليل رقم #1187 إلى "غير مسدد" مع إعادة احتسابه وفق نسبة الـ 25% الرسمية المعتمدة للفئة H (حصة المريض: 7,750 د.ع، وحصة الضمان: 23,250 د.ع بدلاً من حساب الـ 15% الخاطئ).
- **إعادة تصميم وتوحيد واجهة دفع الطوارئ بالكامل ([resources/views/cashier/emergency-payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/emergency-payment-form.blade.php))**:
  * مطابقة الواجهة بنسبة 100% مع واجهة `request-payment/567` العصرية الفاخرة:
    - رأس أزرق ملكي مع بطاقتين علويتين لبيانات الحالة وبيانات المريض ورقم الهوية.
    - بطاقات الإحصاء المالي الثلاثية المباشرة (إجمالي السعر المعتمد، حصة هيئة الضمان، والصافي المطلوب نقداً).
    - شريط فئة الضمان الصحي المعتمد مع شارة `[ الفئة H ]` ونسبة الاستقطاع الرسمية للطوارئ (0%).
    - جدول تفصيلي متكامل للبنود والخدمات يوضح السعر المعتمد وحصة المريض وحصة الضمان لكل بند.
    - قفل خانة المبلغ المحصل `readonly` مع وسام `[ 🔒 محسوب آلياً ومطابق للوصل ]` لمنع الإدخال اليدوي.
    - التوجيه التلقائي المباشر للوصل فور تأكيد الدفع لطباعة الإيصال الإلكتروني والحراري.
- **التحصين البرمجي الصارم في الكنترولر ([app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php) & [app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php))**:
  * إلزام دالة `processEmergencyPayment` بجلب نسبة فئة المريض تلقائياً عبر `getCopayPercentageFor('emergency')`.
  * تعديل دالة `upsertUnifiedEmergencyPayment` لإبقاء الفواتير المغطاة بالكامل 100% بالضمان في حالة انتظار لتأكيدها وإصدار وصل رسمي لها عبر الكاشير.
  * معالجة تحويل الدفعات المسددة مسبقاً تلقائياً إلى صفحة الوصل الرسمي بدلاً من رسائل التحويل التحذيرية.
- **حزمة الاختبارات الآلية الحصينة ([tests/Feature/EmergencyCashierCopayLockTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyCashierCopayLockTest.php))**:
  * بناء اختبار ميزة متكامل واجتيازه بنجاح تام مع اجتياز كامل حزمة اختبارات الكاشير الـ 21 بنجاح (`21 passed, 165 assertions`).

## Session log (2026-10-26 — المرحلة 114: عداد تكرار الخدمات التمريضية والطبية بالطوارئ وتحديث الحسابات والوصولات آلياً Emergency Nursing Services Dynamic Stepper Counter, Realtime Multiplied Pricing & Full Receipt Parity)

### Done
- **قاعدة البيانات والترحيل السحابي والمحلي ([database/migrations/2026_10_26_090000_add_quantity_to_emergency_emergency_service_table.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_26_090000_add_quantity_to_emergency_emergency_service_table.php))**:
  * إضافة حقل `quantity` الافتراضي 1 بجدول الوسيط `emergency_emergency_service` لتسجيل تكرار أي خدمة تمريضية أو طبية لنفس المريض دون الإخلال بالقيد الفريد.
  * تطبيق الترحيل بنجاح على قاعدة البيانات المحلية وتطبيق التعديل الهيكلي DDL على قاعدة بيانات هوستنجر السحابية بدقة 100%.
- **تصميم العداد التفاعلي (Dynamic Stepper Counter) ([resources/views/emergency/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/show.blade.php))**:
  * تحويل كل خدمة تمريضية إلى صف تفاعلي أنيق يظهر فيه عداد كبسولي `[-] [ 1 ] [+]` فور تحديد الخدمة.
  * القيمة الافتراضية تبدأ من 1 (بحد أدنى 1 وحد أقصى 99).
  * تحديث فوري لشارة السعر الإجمالي للخدمة (`السعر × العدد د.ع`) عند النقر على `+` أو `-` أو الكتابة الرقمية المباشرة في الحقل.
- **تحديث محرك الحسابات المالية بالطوارئ والكاشير ([app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php), [app/Models/Emergency.php](file:///c:/wamp64/www/hospital-system/app/Models/Emergency.php) & [app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php))**:
  * قراءة وتخزين مصفوفة `service_quantities` في جدول الوسيط أثناء الحفظ والتحديث.
  * ضرب أسعار الخدمات في الكمية في دوال احتساب الرصيد غير المسدد `getUnpaidAmount` والحساب الموحد `upsertUnifiedEmergencyPayment` وحسابات الكاشير.
- **مطابقة الوصولات والتقارير الطبية الرسمية ([resources/views/cashier/receipt.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt.blade.php), [resources/views/cashier/receipt-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt-print.blade.php) & [resources/views/emergency/medical-report.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/medical-report.blade.php))**:
  * استرجاع حقل `quantity` وعرض مضاعف الخدمة `(×N)` في مسميات البنود في الوصل الإلكتروني والحراري والتقرير الطبي للطوارئ واحتساب الإجمالي بدقة.
- **حزمة الاختبارات الآلية الحصينة ([tests/Feature/EmergencyServiceQuantityStepperTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyServiceQuantityStepperTest.php))**:
  * بناء اختبارات ميزة تغطي إظهار العداد، حفظ الكميات، وحساب المضاعفات المالية، واجتياز كافة الاختبارات بنجاح (`2 passed, 13 assertions`).

## Session log (2026-10-25 — المرحلة 113: تحصين نسب التحمل المالية، إلغاء التعديل اليدوي للنسب والمبالغ، ومطابقة تسعير الوصل الإلكتروني والحراري للضمان الصحي Strict Cashier Copay Lockdown, Removal of Manual Copay & Amount Overrides, 100% Web & Thermal Receipt Parity & Hostinger Data Fix)

### Done
- **تشخيص وحل تباين الوصل الإلكتروني والحراري ([resources/views/cashier/receipt.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt.blade.php))**:
  * معالجة مشكلة قراءة السعر الخاص بدلاً من سعر الضمان في جدول البنود للوصل الإلكتروني بمطابقة الدالة `calculateInsurancePricing` لكل من المختبر والأشعة.
  * تصحيح القيد المالي رقم #1062 للمريضة شكرية أحمد على قاعدة بيانات هوستنجر وقاعدة البيانات المحلية، بإعادة نسبة التحمل إلى 25% الرسمية المعتمدة للفئة H وتعديل المبلغ إلى 18,500 د.ع وتثبيت حصة الضمان 55,500 د.ع بدقة 100%.
- **إلغاء التعديل اليدوي لنسبة التحمل نهائياً عبر كافة واجهات الكاشير ([resources/views/cashier/payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/payment-form.blade.php), [resources/views/cashier/request-payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/request-payment-form.blade.php), [resources/views/cashier/surgeries/payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
  * إزالة زر فتح وتعديل الضمان `[ 🔒 تثبيت آلي ] / [ 🔓 تعديل استثنائي مفتوح ]` ومجموعة حقول الإدخال اليدوية للنسبة (`#manual_copay_input_group`) وأزرار النسب الخماسية السريعة للعمليات.
  * قفل خانة النسبة لتكون حقل عرض محمي وشارة تأكيد رسمية فقط مع تمرير النسبة تلقائياً عبر حقل مخفي وفق معادلة فئة المريض الرسمية `getCopayPercentageFor()`.
- **قفل خانة المبلغ المطلوب قبضه (`#amount_input`)**:
  * جعل حقل المبلغ `readonly` مع خلفية مضاءة ووسم حماية مشفر `[ 🔒 محسوب آلياً ومطابق للوصل ]` لمنع الكاشير من كتابة مبالغ يدوية مغايرة للحسبة الآلية الدقيقة.
- **التحصين البرمجي الصارم في الكنترولر ([app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php))**:
  * إلزام دالة `processPayment` و `processRequestPayment` و `processSurgeryPayment` بفرض نسبة التحمل الرسمية لفئة المريض تلقائياً وتثبيت `$patientPaidAmount` مساوياً لحصة المريض المحسوبة نظامياً دون الاعتماد على مدخلات الفورم المعدلة يدوياً.
- **حزمة الاختبارات الآلية الحصينة ([tests/Feature/CashierPrintReceiptItemPriceTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/CashierPrintReceiptItemPriceTest.php))**:
  * إضافة اختبار ميزة يضمن دقة تسعير بنود الضمان في الوصل الإلكتروني والحراري، واجتياز كامل حزمة اختبارات الكاشير بـ 20 اختباراً ناجحاً (`20 passed, 154 assertions`).

## Session log (2026-10-24 — المرحلة 112: تفكيك وقرارات طلبات الفحوصات لدى الكاشير بتصميم الكبسولات العصرية Segmented Pills وانعكاسها السريري وتبويب الفحوصات المؤجلة بلوحة الكاشير Cashier Item-Level Pay/Postpone/Cancel Controls, Modern iOS-Style Segmented Pills, Clinical Gate Release, Doctor Alerts & Dedicated Postponed Tab in Cashier Hub)

### Done
- **تصميم الكبسولات العصرية السلسة (Modern Segmented Pills) ([resources/views/cashier/request-payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/request-payment-form.blade.php))**:
  * استبدال الأزرار الكلاسيكية المزدحمة بشريط كبسولي ناعم ومضغوط (iOS/Tailwind Segmented Control) لكل فحص بروابط بصرية فائقة الأناقة:
    - `[ ✓ تسديد ]` بتدرج أخضر زمردي وظل خفيف ناعم.
    - `[ ⏱ تأجيل ]` بتدرج كهرماني دافئ عند الرغبة في تأجيل الفحص.
    - `[ ✕ إلغاء ]` بتدرج وردي/أحمر لإلغاء الفحص بطلب المريض.
  * قائمة أسباب مدمجة (Pill Dropdown & Input) تظهر بسلاسة عند اختيار الإلغاء أو التأجيل لتوثيق السبب بدقة.
- **تفكيك طلبات الفحوصات وحساب الصندوق المالي ([app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php))**:
  * فصل الفحوصات المؤجلة في طلب طبي مستقل برقم جديد وحالة `status = 'postponed'`.
  * فصل الفحوصات الملغاة في طلب طبي مستقل برقم جديد وحالة `status = 'cancelled'` مع توثيق السبب المالي/السريري.
  * احتساب وتحصيل رسوم الفحوصات المسددة فقط ضمن الإيصال المالي الرسمي، أو استكمال العملية بمبلغ 0 في حال ألغى/أجل المريض كل الفحوصات.
- **استحداث تبويب الفحوصات المؤجلة بلوحة الكاشير ([app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php) & [resources/views/cashier/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/index.blade.php))**:
  * عزل الطلبات المؤجلة عن قائمة العمل اليومية المعتادة بتضمين استعلام مخصص في `CashierController@index`.
  * إضافة تبويب مستقل في شريط التبويبات الرئيسي: `[ ⏱ الفحوصات المؤجلة (N) ]` مع شارة عداد باللون الكهرماني.
  * جدول تفصيلي متكامل يعرض: رقم الطلب، القسم التشخيصي، المريض، الفحوصات المؤجلة، سبب التأجيل، الطبيب، المبلغ، وتاريخ التأجيل.
  * زر إجراء مباشر: `[ 💳 تسديد الآن ]` يفتح استمارة التسديد فوراً بمجرد عودة المريض لاستئناف الفحص دون الحاجة لطلب طبي جديد.
  * بانيل تنبيه كهرماني علوي بارز يظهر للكاشير بوجود طلبات مؤجلة مع زر انتقال فوري للتبويب.
- **تحرير القفل السريري بمحطة الطبيب وتنبيهات الكاشير ([app/Http/Controllers/DoctorVisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorVisitController.php) & [resources/views/doctors/visits/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/show.blade.php))**:
  * استبعاد الفحوصات المؤجلة والملغاة (`whereNotIn('status', ['completed', 'cancelled', 'postponed'])`) من شرط القفل السريري الإلزامي.
  * تمكين الطبيب من إنهاء الزيارة فوراً ودون احتجاز متى ما سدد المريض أو ألغى/أجل بقية الفحوصات.
  * إظهار تنبيهات سريرية بارزة أعلى تبويب الفحوصات توضح الفحوصات التي ألغاها أو أجلها المريض مع أسبابها، مع فلتر سريع `[ ⛔ ملغاة ]`.
- **عزل الأقسام التشخيصية وطابور العيادة ([app/Http/Controllers/RadiologyStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RadiologyStaffController.php) & [app/Http/Controllers/DoctorQueueController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorQueueController.php))**:
  * استبعاد الفحوصات الملغاة والمؤجلة تلقائياً من قوائم العمل النشطة لمختبر التحاليل وأقسام الأشعة.
  * منع إدراج المريض في قائمة "بانتظار الفحوصات" في شاشة استدعاء الطبيب إذا كانت الفحوصات ملغاة أو مؤجلة.
- **حزمة الاختبارات الآلية الحصينة ([tests/Feature/CashierItemActionCancellationAndPostponeTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/CashierItemActionCancellationAndPostponeTest.php))**:
  * بناء اختبارات متكاملة تغطي تسديد فحص وإلغاء وتأجيل آخر مع الأسباب وتفكيك السجلات وإنهاء الزيارة، واختبار إلغاء كافة الفحوصات، واختبار ظهور الفحوصات المؤجلة في التبويب المخصص بلوحة الكاشير بنجاح تام (`3 passed, 38 assertions`).

## Session log (2026-10-23 — المرحلة 111: إصلاح استعلام التحقق من الفحوصات المعلقة عند إنهاء الزيارة، وتحسين استجابة AJAX، وتفعيل الحظر الصارم للحجز المكرر في واجهة الاستعلامات Doctor Visit Completion SQL Fix, Clean AJAX Error Handling & Inquiry Hard Block UI)

### Done
- **إصلاح استعلام SQL لفحص الفحوصات المعلقة ([app/Http/Controllers/DoctorVisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorVisitController.php))**:
  * معالجة خطأ `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'patient_id' in 'where clause'` الذي تسبب بـ 500 Internal Server Error عند رغبة الطبيب بإنهاء الزيارة المكتملة فحوصاتها.
  * تصحيح الاستعلام بالاعتماد على معرّفات زيارات اليوم للمريض والطبيب (`whereIn('visit_id', $sameDayVisitIds)`).
- **تحسين إظهار رسائل AJAX عند إنهاء الزيارة ([resources/views/doctors/visits/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/show.blade.php))**:
  * قراءة وعرض رسالة الخطأ السريرية الدقيقة الصادرة من السيرفر (`data.message`) مباشرة بدلاً من الرسالة الافتراضية "حدث خطأ أثناء إنهاء الزيارة".
- **الحظر الصارم لتكرار الحجز في واجهة الاستعلامات ([resources/views/inquiry/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/inquiry/create.blade.php))**:
  * تحويل التنبيه البرتقالي إلى بانيل أحمر مانع (`⛔ غير مسموح بتكرار الحجز لنفس اليوم نهائياً`).
  * تعطيل زر الإرسال والحفظ تلقائياً (`Disabled`) بنص `[ ⛔ محظور: مسجل اليوم بالفعل لدى الطبيب ]` ومنع الإرسال فورياً في حال كان المريض مسجلاً اليوم لدى الطبيب.

## Session log (2026-10-23 — المرحلة 110: الحظر الصارم للحجز المكرر لنفس اليوم، والقفل السريري الإلزامي لإنهاء الزيارة بانتظار الفحوصات Strict Same-Day Duplicate Booking Prevention, Hard Validation Block & Mandatory Visit Completion Clinical Gate Pending Test Results)

### Done
- **الحظر الصارم لتكرار الحجز لنفس اليوم نهائياً ([app/Http/Controllers/AppointmentController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/AppointmentController.php) & [app/Http/Controllers/InquiryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/InquiryController.php))**:
  * منع تسجيل أو قطع أي حجز لنفس المريض عند نفس الطبيب في نفس اليوم نهائياً (حتى لو برسم 0 د.ع أو تأكيد الاستعلامات).
  * إرجاع رسالة خطأ وإرشاد واضحة: *"⚠️ غير مسموح بتكرار الحجز لنفس اليوم نهائياً! المريض مسجل اليوم بالفعل لدى الطبيب. لا داعي لقطع حجز جديد، حيث يدخل المريض للعيادة مباشرة لمتابعة الفحوصات أو المراجعة"*.
  * ضمان عدم توليد أي قيود أو وصولات إضافية بصندوق الكاشير أو سجلات الحركات المالية.
- **القفل السريري الإلزامي لإنهاء الزيارة في محطة الطبيب ([app/Http/Controllers/DoctorVisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorVisitController.php) & [resources/views/doctors/visits/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/show.blade.php))**:
  * **التحقق البرمجي الصارم (Backend Gate)**: في دالة `update`، يُمنع تحويل الزيارة إلى `completed` في حال وجود أي فحوصات مخبرية أو شعاعية معلقة لم تكتمل نتائجها (`whereNotIn('status', ['completed', 'cancelled'])`).
  * **التحكم بالواجهة (Frontend Lock)**: استبدال زر "إنهاء الزيارة" بزر مقفل ومعطل (`Disabled`) يحمل أيقونة القفل مع نافذة تنبيهية إرشادية توضح عدد الفحوصات المعلقة، وتوجه الطبيب لاستخدام زر `[ ⏳ تعليق بانتظار الفحوصات ]`.
  * **شريط تحذيري وإرشادي علوي**: عرض بانيل تنبيه كهرماني بارز أعلى صفحة الزيارة يوضح وجود فحوصات معلقة مع زر سريع `[ تعليق واستدعاء التالي ]` للرجوع لمحطة العيادة ومتابعة باقي المراجعين ريثما تصدر النتائج.
- **حزمة الاختبارات الآلية الحصينة ([tests/Feature/SameDayRecheckBookingTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SameDayRecheckBookingTest.php))**:
  * تحديث وتوسيع الاختبارات للتأكد من حظر حجز نفس اليوم بالكامل، ومنع إنهاء الزيارة عند وجود فحوصات معلقة، والسماح بإنهائها فقط عند اكتمال كافة الفحوصات بنجاح تام (`3 passed, 10 assertions`).

## Session log (2026-10-23 — المرحلة 109: حماية كشفية نفس اليوم من التكرار، وأتمتة مراجعة النتائج المجانية، ودمج فحوصات اليوم في محطة الطبيب، وتنظيف قيود هوستنجر المكررة Same-Day Duplicate Consultation Prevention, Automatic Free Results Recheck, Workstation Requests Aggregation & Hostinger Duplicate Cleanup)

### Done
- **أتمتة مراجعة النتائج ومنع الحجز المكرر لنفس اليوم ([app/Http/Controllers/InquiryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/InquiryController.php) & [app/Http/Controllers/AppointmentController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/AppointmentController.php))**:
  * فحص تلقائي لحظي: إذا دخل مريض ولديه كشفية سابقة في نفس اليوم لدى نفس الطبيب، يتم تحويل الحجز تلقائياً إلى "مراجعة نتائج مجانية" برسم `consultation_fee = 0` و `is_free_recheck = true` وربطه بالزيارة الأصلية دون استيفاء أي مبالغ إضافية من المريض.
  * منع ازدواجية قيود الإيراد والوصولات لدى الكاشير وحماية الصندوق.
- **دمج فحوصات وزيارات نفس اليوم في محطة الطبيب ([app/Http/Controllers/DoctorVisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorVisitController.php))**:
  * تجميع ودمج كافة طلبات وفحوصات المريض الطبية لنفس تاريخ اليوم تلقائياً، بحيث تظهر كافة التحاليل والأشعة أمام الطبيب في لوحة العمل حتى لو كان هناك أكثر من زيارة.
  * رفع حد الذاكرة `ini_set('memory_limit', '512M')` لحل استنفاد الذاكرة وخطأ 500 نهائياً عند فتح الزيارات التي تحتوي على آلاف الأدوية ورموز ICD-10.
- **تحديث واجهة الطبيب بزر تعليق الفحوصات وتنبيه الإنهاء المبكر ([resources/views/doctors/visits/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/show.blade.php))**:
  * إضافة زر صريح: `[ ⏳ تعليق بانتظار الفحوصات ]` يسمح للطبيب بالرجوع للعيادة واستدعاء المريض التالي مع إبقاء الزيارة مفتوحة ريثما تصدر النتائج.
  * تنبيه سريري ذكي عند النقر على "إنهاء الزيارة" بوجود فحوصات مخبرية أو شعاعية معلقة لتنبيه الطبيب بعدم إقفال الزيارة مبكراً.
- **تنظيف السجلات المكررة على قاعدة بيانات هوستنجر والمحلي**:
  * معالجة الحالات الـ 4 المكررة ليوم 2026-10-22 (سلمى راضي، والمراجعين الثلاثة الآخرين) بتحويل الزيارات المفرغة إلى `cancelled` وتصفير الحجوزات المكررة كمراجعات مجانية ونقل الوصفات الطبية للزيارات الأصلية، ليصبح عدد المراجعين الفعليين مطابقاً للواقع المالي السليم.
- **حزمة الاختبارات الآلية الحصينة ([tests/Feature/SameDayRecheckBookingTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SameDayRecheckBookingTest.php))**:
  * إضافة اختبارات متكاملة تؤكد تحويل حجز نفس اليوم لمراجعة مجانية برسم 0 د.ع، ودمج كافة فحوصات نفس اليوم داخل محطة الطبيب بنجاح كامل (`2 passed, 10 assertions`).

## Session log (2026-10-23 — المرحلة 108: توحيد وهيكلة محطة الأشعة والتصوير الطبي وفق نمط المختبر حسب الأقسام، وبناء شريط الفلترة الموحد للتاريخ، وتصحيح التبويب الافتراضي وتزامن الفلاتر Radiology Hub Department Architecture Harmonization with Lab, Unified Date Toolbar, Instant Consultation Results Sync & Zero Tab-Switching Glitch)

### Done
- **توحيد التبويب الافتراضي وحل مشكلة اختفاء نتائج الاستشارية عند اختيار التاريخ**:
  * تشخيص ومعالجة عدم تطابق المسمى البرمجي القديم (`waiting`) مع معرّف التبويب المعتمد (`station` / `station-pane`)، حيث كان يتسبب في عدم وجود كلاس التفعيل `show active` عند إعادة تحميل الصفحة بعد اختيار أي تاريخ، ما يؤدي لاختفاء جدول النتائج كلياً حتى يتنقل المستخدم بين التبويبات ويعود إليه.
  * توحيد التبويب الافتراضي في الكنترولر والواجهة إلى `$activeTab = 'station'` وضمان بقاء تبويب الاستشارية وبانيل العرض نشطاً بـ `show active` فورياً.
  * تحديث جدول الاستشارية ليعرض كافة طلبات العيادات المحولة (`$consultationRequests`) مرتبة بالأولوية السريرية (قيد النداء ⬅️ بالانتظار ⬅️ قيد الفحص ⬅️ المكتملة) بحيث تظهر الفحوصات في التواريخ السابقة مع أزرار الطباعة والمعاينة دون إخفائها خلف قائمة الانتظار فقط.
  * حصر المزامنة اللحظية لطابور الاستدعاء (`syncRadiologyQueue`) لتعمل حصراً عند اختيار تاريخ "اليوم"، ومنع مسح نتائج التواريخ السابقة من الشاشة.
- **تزامن حقول الفلترة التلقائي مع التبويبات والبطاقات العلوية**:
  * ربط مستمعات التبويبات في الجافاسكربت لتحديث حقلي النموذج المخفيين (`radiology-active-tab-input` و `radiology-source-input`) لحظياً عند التنقل، لضمان بقاء المستخدم في نفس التبويب عند اختيار أي تاريخ.
  * مزامنة تظليل وتمييز بطاقات الأقسام العلوية الأربعة (`#radiology-department-cards`) فورياً مع التبويب النشط.
- **إعادة هيكلة محطة الأشعة وفق نمط المختبر (Department Breakdown)**:
  * شريط بطاقات أقسام علوي رباعي: (طلبات العيادات الاستشارية، طلبات الأشعة المباشرة، أشعة وفحوصات قسم الطوارئ، كافة طلبات القسم).
  * شريط فلترة وبحث موحد وشامل للتاريخ (اليوم، أمس، الأسبوع، الشهر، كافة التواريخ، وتقويم مخصص) بدلاً من الفلاتر المتكررة داخل التبويبات.
  * تبويب مستقل لطلبات الأشعة المباشرة والاستقبال (`#direct-pane`) مع دعم زر الإلغاء الفوري.
- **حزمة الاختبارات الآلية الحصينة**:
  * إضافة اختبارات متقدمة تؤكد بقاء تبويب الاستشارية نشطاً بـ `show active` وظهور الفحوصات فورياً عند فلترة التاريخ بدون تنقل في [RadiologyPriorPendingRequestsTabTest](file:///c:/wamp64/www/hospital-system/tests/Feature/RadiologyPriorPendingRequestsTabTest.php) بـ 5 اختبارات ناجحة (`5 passed, 36 assertions`).
  * اجتياز كامل حزمة اختبارات قسم الأشعة والتصوير الطبي بـ 41 اختباراً ناجحاً (`41 passed, 217 assertions`).

## Session log (2026-10-22 — المرحلة 107: تعريب وتوحيد مسميات الأدوار والصلاحيات بالكامل، وإعادة تصميم لوحة الأدوار التنفيذية، وإضافة صلاحية التحكم بمطالبات الضمان الصحي Complete Roles & Permissions Arabic Localization, Executive Roles Hub Redesign, Unified Role Labels Engine, Zero Untranslated Permissions & Insurance Claims Access Control)

### Done
- **إدراج واجهة مطالبات وسجلات الضمان الصحي في نظام الأدوار والصلاحيات**:
  * استحداث صلاحية مستقلة ومخصصة باسم `view insurance claims` (عرض ومتابعة كشوفات وسجلات مطالبات الضمان وتصديرها).
  * إضافة مايجريشن مخصص `database/migrations/2026_10_22_150000_add_view_insurance_claims_permission.php` وتثبيتها في قاعدة البيانات ومنحها لأدوار الإدارة والمحاسب.
  * حماية مسارات وواجهة `HealthInsuranceClaimController` بميدلوير يمنع الدخول (403 Forbidden) إلا لمن يملك صلاحية `view insurance claims` أو `manage health insurance` أو إدارة المستشفى.
  * إتاحة التحكم بها وتفعيلها/تعطيلها بسهولة عبر بطاقة "الحسابات العامة والمالية" في شاشة إدارة وتعديل الأدوار `/roles/{role}/edit`.
- **تعريب وتوحيد منظومة مسميات الأدوار بالكامل (Unified Role Labels Engine)**:
  * تطوير دالة مركزية موحدة `RoleManagementController::getRoleLabel($roleName)` تشكل المصدر المعتمد لترجمة وتخصيص أي دور (المسمى العربي، الوصف، الأيقونة، ولون الشارة).
  * تغطية كافة الأدوار الـ 26 المسجلة في النظام بما فيها الأدوار التي كانت تظهر باللغة الإنجليزية:
    * `accountant` ⬅️ **المحاسب المالي** (أيقونة الحاسبة واللون الأزرق).
    * `admin-hsop` / `hospital_admin` ⬅️ **مدير المستشفى**.
    * `thaman` ⬅️ **هيئة الضمان الصحي**.
    * `gh&est` ⬅️ **كادر الجراحة والاستشارية**.
    * `staff` ⬅️ **كادر إداري عام**.
  * تصنيف الأدوار إلى 6 قطاعات وظيفية ذكية عبر `getRoleCategory()`:
    * `الطبية والجراحية`، `المالية والكاشير`، `التشخيصية`، `الاستقبال والطوارئ`، `الصيدلية والمستودع`، `الإدارة والأنظمة`.
  * تعميم الدالة الموحدة على شاشات: قائمة الأدوار (`roles.index`)، شاشة تعديل الصلاحيات (`roles.edit`)، إضافة دور (`roles.create`)، وإدارة المستخدمين (`users.index`, `users.create`, `users.edit`).
- **إعادة تصميم وهيكلة لوحة إدارة الأدوار (`resources/views/roles/index.blade.php`)**:
  * شريط كروت مؤشرات تنفيذية علوي (إجمالي الأدوار، الكادر المعين، إجمالي الصلاحيات، الأدوار الشاغرة).
  * شريط تبويبات وفلاتر سريعة للقطاعات الوظيفية (Category Filter Pills) لتصفية الأدوار بنقرة زر واحدة.
  * بحث لحظي ذكي (Live Search) بالاسم العربي أو الإنجليزي أو الوصف.
  * مبدل عرض مزدوج (Dual View Mode): عرض البطاقات الحديثة (Cards Grid) مع شريط نسبة تغطية الصلاحيات ووسوم الأقسام البارزة، وعرض الجدول المضغوط (Compact Table).
  * نافذة تفاعلية منبثقة سريعة (Role Users Modal) عبر AJAX لمعاينة جميع الموظفين المعينين لأي دور بضغطة زر دون مغادرة الصفحة.
- **تعريب 100% من صلاحيات النظام (Zero Untranslated Permissions)**:
  * تدقيق كامل الـ 145 صلاحية في قاعدة البيانات وسد النواقص لضمان عدم ظهور أي كود برمجي إنجليزي في الواجهات (إضافة `inquiry.create.radiology` والصلاحيات التراثية المخفية).
  * تعريب الأقسام الرئيسية في شاشة الصلاحيات (`permissions.index`) وفق بطاقات النظام العشرة.
- **حزمة الاختبارات الآلية الحصينة**:
  * اجتياز اختبارات صلاحيات الضمان الصحي في [HealthInsuranceClaimsReportTest](file:///c:/wamp64/www/hospital-system/tests/Feature/HealthInsuranceClaimsReportTest.php) بـ 8 اختبارات ناجحة (`8 passed, 26 assertions`).
  * اجتياز كامل اختبارات مصفوفة الأدوار والصلاحيات في [RolePermissionsMatrixTest](file:///c:/wamp64/www/hospital-system/tests/Feature/RolePermissionsMatrixTest.php) بـ 11 اختباراً ناجحاً (`11 passed, 182 assertions`).

## Session log (2026-10-22 — المرحلة 106: منظومة إلغاء طلبات فحوصات الطوارئ ودورة الاسترجاع المالي لدى الكاشير وإصدار السندات السالبة ومعالجة حالة المريضة نسيمه عطب سلمان Emergency Request Cancellation, Doctor Action, Cashier Refund Processing, Negative Voucher Invoicing & Patient #47 Refund Execution)

### Done
- **معالجة وتصحيح حالة المريضة نسيمه عطب سلمان (طوارئ #47 - Patient ID 8600)**:
  * تدقيق كافة الخدمات المسجلة للمريضة وتحديد الفحصين غير المنجزين المطلوبين بالخطأ.
  * إلغاء فحص رنين الكاحل (`Ankle - Joint MRI` بقيمة 200,000 د.ع) وفحص الأشعة السينية مقطعين (`Two-Section Standard X-Ray` بقيمة 40,000 د.ع).
  * إصدار سندي استرجاع مالي رسمي لدى الكاشير بمجموع **240,000 د.ع**:
    - وصل استرجاع **`REC-20261022-0020`** بمبلغ `-200,000 د.ع`.
    - وصل استرجاع **`REC-20261022-0021`** بمبلغ `-40,000 د.ع`.
  * تحويل حالة الطلبين إلى `status = 'cancelled'` و `payment_status = 'refunded'` وانتقالهما تلقائياً لأرشيف الطلبات الملغاة.
- **توسيع محطة عمل الطوارئ بإمكانية إلغاء الفحوصات ([app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php) & [resources/views/emergency/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/show.blade.php))**:
  * زر إلغاء فوري للطبيب على بطاقات الفحوصات غير المكتملة في لوحة السجل الطبي والفحوصات مع تسجيل سبب الإلغاء.
  * في حال كان الطلب مسدداً، يحول إلى `cancelled` ويوجّه فوراً إلى قائمة مستحقات الاسترجاع لدى الكاشير.
- **تطوير واجهة ونظام الكاشير لدعم استرجاعات الطوارئ ([app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php) & [resources/views/cashier/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/index.blade.php))**:
  * إدراج طلبات الأشعة والمختبر الملغاة في طوارئ المستشفى تلقائياً ضمن قائمة مستحقات الاسترجاع (Refunds) مع عداد إشعارات بارز.
  * نافذة تأكيد وصرف الاسترجاع المالي الفوري وتحديد وسيلة الإرجاع (نقد/شبكة).
  * إصدار سند استرجاع سالب رسمي (`Payment` بسالب ورقم إيصال رسمي `REC-...`)، وتحديث حالة دفع الطلب إلى `refunded`.
- **إظهار فحوصات الطوارئ في صفحة طباعة الوصل ([resources/views/cashier/receipt.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt.blade.php))**:
  * دعم عرض بنود وتفاصيل تحاليل وأشعة الطوارئ في جدول تفاصيل الوصل المالي بعد أن كانت محصورة بكشفية الطوارئ فقط.
- **تطوير الموديلات وقاعدة البيانات**:
  * إضافة مايجريشن مخصص `database/migrations/2026_10_22_140000_add_refunded_to_emergency_requests_payment_status.php` لدعم حالة الدفع `refunded` في جداول أشعة ومختبر الطوارئ متوافقاً مع كل من MySQL و SQLite.
  * إضافة علاقة `payment()` وحقل `payment_status` إلى `EmergencyRadiologyRequest` و `EmergencyLabRequest`.
- **حزمة الاختبارات الآلية الحصينة ([tests/Feature/EmergencyRequestCancellationAndRefundTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyRequestCancellationAndRefundTest.php))**:
  * اجتياز كامل لجميع اختبارات الميزة الـ 3 (`3 passed, 13 assertions`) مع اجتياز كامل لاختبارات الطوارئ السابقة (14 passed).

## Session log (2026-10-22 — المرحلة 105: توحيد هيكلية محطة عمل الطوارئ مع محطة الاستشارية: تخصيص تبويب طلبات الفحوصات لإنشاء الطلبات الجديدة ونقل كافة بطاقات الطلبات ونتائج المختبر والأشعة إلى السجل الطبي الدائم والفحوصات Emergency Workstation Harmonization with Outpatient Clinic: Dedicated Ordering Forms Tab & Consolidation of Investigation Cards, Steppers & Results into Permanent Medical Record)

### Done
- **تخصيص تبويب طلبات الفحوصات (Tab 3: `#tab-requests`) للطلبات الجديدة ([resources/views/emergency/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/show.blade.php))**:
  * إزالة بطاقات الطلبات والنتائج السابقة التي كانت تملأ تبويب الطلبات وتخفي نماذج الإدخال خلف كتل متراكمة من الوصولات القديمة.
  * فتح وعرض نماذج طلب المختبر والأشعة بشكل دائم ومباشر دون إخفائها خلف أزرار Collapse منسدلة، مع دعم الفلاتر السريعة (الكل، مشمول بالضمان، كاش فقط)، والبحث اللحظي التفاعلي، وعداد العناصر المحددة.
  * إضافة شريط إرشادي رسمي ينبه الطبيب إلى أن متابعة مسار الطلبات، مراحل سداد الكاشير، وصدور النتائج والتقارير الطبية الرسمية معروضة ومحدثة لحظياً في لوحة "السجل الطبي والفحوصات" على يسار الشاشة مع زر انتقال سريع.
- **تطوير وترقية لوحة السجل الطبي الدائم والفحوصات (`#side-cur-pane`)**:
  * نقل وإعادة هيكلة كافة بطاقات طلبات المختبر والأشعة لتظهر في مكانها المعتمد تحت تبويب "طلبات الحالة" بالسجل الطبي الأيسر (مطابق تماماً لمحطة الطبيب الاستشارية).
  * إضافة شريط تصفية تفاعلي سريع (Sub-Filter Pills: الكل، المختبر، الأشعة، العلاجات) عبر دالة `filterCurEmergencyRequests`.
  * تزويد كل بطاقة فحص بشريط التقدم ومراحل المسار التفاعلي ذي الخطوات الأربع (Status Progress Stepper: طلب الطبيب ⬅️ الكاشير ⬅️ المختبر/قسم الأشعة ⬅️ النتيجة/التقرير).
  * إبراز حالة السداد (مسدد بشارة خضراء / عند الكاشير بشارة تنبيه صفراء)، وأولوية الفحص، وزر الطباعة الرسمية للنتائج المعتمدة.
  * جدول سريري مصغر لعناصر الفحص يوضح القيم والوحدات والمعدلات الطبيعية مع تمييز النتائج غير الطبيعية باللون الأحمر، مع إتاحة زر الحذف الفوري للعناصر غير المسددة للطبيب (`emergencyRemoveRequestItem`).
  * عرض تفاصيل تقارير الأشعة والمشاهدات والانطباع التشخيصي، مع أزرار معاينة وتحميل صور الأشعة المرفقة.
  * تضمين أرشيف الطلبات الملغاة داخل أكورديون منظم أسفل القائمة.
- **التحقق والاختبارات الآلية الحصينة**:
  * اجتياز كامل حزمة اختبارات الطوارئ ومحطة العمل (`41 passed, 218 assertions`) واجتياز اختبارات تعديل وحذف الفحوصات وشريط المراحل (`7 passed, 38 assertions`) دون أي تعارض أو كسر في الوظائف.

## Session log (2026-10-22 — المرحلة 104: تحويل كشف مطالبات الضمان الصحي إلى العرض التجميعي حسب المرضى تباعاً مع مجاميع الاستحقاق وتصحيح استعلام البحث Health Insurance Claims Patient-Grouped Sequential View, Service Rows, Subtotals, View Mode Toggle & Search Fix)

### Done
- **العرض التجميعي الذكي حسب المريض تباعاً ([resources/views/insurance/claims/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/insurance/claims/index.blade.php))**:
  * تحويل الواجهة الرئيسية لتظهر كل مريض على حدة في ترويسة رئيسية مميزة (الاسم، فئة الضمان بشارتها الملونة، رقم بطاقة الضمان، الهاتف، رقم الملف، وإجمالي عدد خدماته).
  * عرض كافة خدمات وفحوصات هذا المريض الطبية مسردة تباعاً بسطور تفصيلية (رقم الوصل، وقته، القسم، الطبيب المعالج، تفاصيل الفحص الطبي الدقيق، السعر الكلي، نقد المريض، ومطالبة الضمان ونسبة التغطية مع زر الطباعة).
  * سطر ملخص ومجاميع خاص بكل مريض (Subtotal) يحسب إجمالي القيمة الاستحقاقية لخدماته، إجمالي تحمله نقداً، وإجمالي مطالبة الضمان للمستشفى.
  * بعد انتهاء خدمات ومجاميع المريض، يبدأ المريض التالي مباشرة بنفس الترتيب السلس والواضح.
  * توفير مبدل عرض مرن (View Mode Switcher): `[ حسب المريض (مجمّع) ]` و `[ حسب الوصل (تفصيلي) ]` للتبديل الفوري بنقرة زر واحدة.
- **تطوير الكنترولر والاستعلامات ([app/Http/Controllers/HealthInsuranceClaimController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/HealthInsuranceClaimController.php))**:
  * استعلام تجميعي فائق الكفاءة والسرعة يجمع مطالبات كل مريض مع ترقيم صفحات المرضى (`paginate(15)`) وتحميل العلاقات مسبقاً لمنع أي استعلامات متكررة (N+1).
  * حصر ومعالجة الدفعات التي قد لا ترتبط بملف مريض محدد في قسم مستقل لضمان عدم ضياع أي دينار.
  * تصحيح استعلام البحث النصي (`search`): البحث عن اسم المريض ورقم هاتفه عبر علاقة `user` الصحيحة في قاعدة البيانات وحل استثناء `1054 Unknown column 'name'`.
- **ترقية تصدير ملفات Excel ([app/Exports/HealthInsuranceClaimsExport.php](file:///c:/wamp64/www/hospital-system/app/Exports/HealthInsuranceClaimsExport.php))**:
  * دعم تصدير ملف الإكسيل بنمطين متوافقين مع العرض النشط: تصدير شيت ملخص المرضى واستحقاقاتهم التراكمية، أو تصدير كشف الوصولات الفردية بكامل أعمدته الـ 16.
- **حزمة الاختبارات الآلية الحصينة ([tests/Feature/HealthInsuranceClaimsReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HealthInsuranceClaimsReportTest.php))**:
  * توسيع الاختبارات إلى 6 اختبارات آلية ميزة تؤكد ظهور المرضى وخدماتهم المجمعة ومجاميع الاستحقاق، وعمل البحث بالاسم العربي للمريض بنجاح مع اجتياز كامل (`6 passed, 24 assertions`).

## Session log (2026-10-22 — المرحلة 103: تدشين واجهة وسجلات مطالبات الضمان الصحي الشاملة: فئات المرضى، تفاصيل الخدمات، أسعارها، حصص المرضى والضمان، اسم الطبيب، وتصدير Excel Health Insurance Claims Interface, Patient Categories, Service Details, Pricing, Doctor Names & Excel Export)

### Done
- **بناء واجهة كشوفات ومطالبات الضمان الصحي المخصصة ([resources/views/insurance/claims/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/insurance/claims/index.blade.php))**:
  * واجهة تنفيذية متكاملة تتيح تتبع ومراجعة كافة الخدمات المقدمة للمرضى المشمولين بالضمان الصحي الوطني وقوى الأمن والتعاقدات.
  * عرض اسم المريض، فئة الضمان (`الفئة A`, `الفئة H`, `الفئة D`...) مع شارات ملونة لكل فئة، رقم باج/بطاقة الضمان، نوع الخدمة/القسم، اسم الطبيب المعالج/الاستشاري، تفاصيل الفحوصات الطبية الدقيقة، السعر الكلي، المبلغ المدفوع نقداً، ومطالبة الضمان، مع نسبة التغطية.
  * شريط كروت مؤشرات تنفيذية خماسي (إجمالي مطالبات الضمان، تحمل المرضى نقداً، إجمالي القيمة الاستحقاقية، عدد الخدمات المسددة، والمرضى المستفيدين).
  * شريط تصفية سريع حسب فئات الضمان يوضح إحصائيات كل فئة ومطالباتها مع الفلترة بنقرة زر واحدة.
  * فلاتر متقدمة تشمل: الفترات الزمنية السريعة (اليوم، أمس، هذا الأسبوع، هذا الشهر، الشهر السابق، هذا العام، كل الأوقات)، نطاق التاريخ المخصص، فئة الضمان، القسم، الطبيب، والبحث السريع.
- **الكنترولر ومنطق احتساب الاستحقاقات ([app/Http/Controllers/HealthInsuranceClaimController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/HealthInsuranceClaimController.php))**:
  * استعلام دقيق يغطي كافة الدفعات المسددة التي تشمل حصة ضمان أو تصنيف تأميني مع معالجة حصرية لأسماء الجداول (`payments.`) لمنع أي تضارب بين الحقول.
  * احتساب المؤشرات اللحظية والإحصائيات التجميعية استحقاقياً وتوزيع الفئات، مع ترقيم الصفحات (`paginate(25)`).
- **تصدير إكسيل رسمي ومطابق لمعايير هيئات الضمان ([app/Exports/HealthInsuranceClaimsExport.php](file:///c:/wamp64/www/hospital-system/app/Exports/HealthInsuranceClaimsExport.php))**:
  * توليد شيت إكسيل منسق ومبوب بـ 16 عموداً شاملاً كافة بيانات المطالبة، المريض، فئته، طبيبه، وتفاصيل الخدمة ومبالغها.
- **توسيع نموذج المدفوعات والوصول الموحد ([app/Models/Payment.php](file:///c:/wamp64/www/hospital-system/app/Models/Payment.php))**:
  * إضافة دوال الوصول: `attending_doctor_name` (استخراج اسم الطبيب النظيف من الكشفية أو الزيارة أو الطوارئ أو الجراحة)، `service_details_text` (تجميع كافة الفحوصات والخدمات المقدمة في الإيصال)، و `insurance_type_label`.
- **ربط المسارات والقائمة الجانبية الموحدة ([routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php) & [resources/views/layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php))**:
  * إضافة مسارات `Route::prefix('insurance-claims')` للواجهة والتصدير.
  * إضافة رابط `مطالبات وسجلات الضمان` ضمن قسم الحسابات العامة والمالية بالقائمة الجانبية.
- **حزمة الاختبارات الآلية الحصينة ([tests/Feature/HealthInsuranceClaimsReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HealthInsuranceClaimsReportTest.php))**:
  * بناء 4 اختبارات ميزة متكاملة تؤكد الحماية، صحة عرض البيانات وأسماء الأطباء وفئات الضمان، فلترة الفئات، وتصدير ملف Excel مع اجتياز كامل (`4 passed, 15 assertions`).

## Session log (2026-10-22 — المرحلة 102: التدقيق المالي الشامل لخزينة المستشفى، الشريط التنفيذي ذو الـ 6 كروت، مطالبات الضمان ومستحقات الأطباء المعلقة، وتفكيك وسائط الأشعة والتصوير Hospital Treasury Financial Audit, 6 Executive Cards Strip, Insurance Claims Receivable, Doctor Liabilities & Radiology Modalities Decomposition)

### Done
- **شريط الكروت التنفيذي السداسي الشامل المتجاوب (6 Executive Cards Strip) ([resources/views/treasury/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/treasury/index.blade.php))**:
  * كشف أسباب اللبس الرقمي الذي لاحظه المستخدم: كارت الرصيد التراكمي (كل الوقت) مقابل كروت الفترة الزمنية المحددة، وعزل نقد الدرج عن مطالبات الضمان والمستحقات المعلقة.
  * توسيع الشريط العلوي من 5 إلى 6 كروت متوازنة ومتجاوبة بنظام 6 أعمدة متساوية على الشاشات الكبيرة (`col-xl-2`):
    1. `الرصيد الفعلي بالخزينة`: مع وسم بارز يوضح أنه تراكمي شامل لكل الفترات وتفصيل الوارد والصادر التراكمي.
    2. `إجمالي المقبوض نقداً (الفترة)`: نقد صندوق الخزينة والكاشير الفعلي المحصل.
    3. `مطالبات الضمان الصحي (الفترة)` (كارت تنفيذي جديد): يعرض قيمة الذمم المدينة المؤجلة قيد التحصيل من هيئات وشركات الضمان الصحي مع عدد المطالبات، مما أظهر أكثر من 14.6 مليون دينار كانت غائبة عن مؤشرات الخزينة.
    4. `المبالغ المسترجعة للمرضى`: تتبع دقيق لكافة حركات الاسترجاع الناتجة عن إلغاء كشفيات أو خدمات.
    5. `المصروفات المنفذة`: نفقات التشغيل والمشتريات المنفذة، مع سطر فرعي بارز يرصد التزامات مستحقات الأطباء المعلقة غير المنصرفة (`DoctorDue::where('status', 'pending')`).
    6. `صافي سيولة الفترة`: الفائض النقدي الفعلي لحركة الفترة (الوارد النقدي - المنصرف).
- **تفكيك وسائط قسم الأشعة والتصوير إلى خدمات تخصصية مستقلة ([app/Http/Controllers/HospitalTreasuryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/HospitalTreasuryController.php) & [resources/views/treasury/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/treasury/index.blade.php))**:
  * تفكيك بند الأشعة الموحد القديم وإفراد كل وسيطة تشخيصية ببطاقة وإحصائية ولون بياني مخصص:
    * السونار (Ultrasound)
    * الأشعة السينية (X-Ray)
    * الرنين المغناطيسي (MRI)
    * المفراس الحلزوني (CT Scan)
    * إيكو القلب (Echo)
  * تنظيم قائمة تصفية البنود والتصنيفات في شريط الفلاتر إلى مجموعتين واضحتين: `[الأقسام والخدمات الطبية]` و `[المصروفات والمسترجعات]`، مع دعم التصفية المباشرة بالسندات المرتبطة بنوع وسيطة الفحص.
- **تطوير جدول إيرادات ومصروفات الأقسام (التبويب 2) وحل عجز الاستشارية الظاهري**:
  * إضافة أعمدة صريحة تفصل: `[المقبوض نقداً (Cash)]` و `[مطالبات الضمان (Insurance)]` و `[إجمالي الاستحقاق (Accrual Gross)]` بجانب `[حصص الأطباء / التكاليف]` و `[صافي المستشفى (Net)]`.
  * حل مشكلة العجز الظاهري لعيادات الاستشارية (حيث كان يظهر المقبوض نقداً أقل من حصص الأطباء لأن المريض يدفع 2,500 د.ع كاش والباقي 22,500 د.ع مطالبة ضمان صحي)؛ ليصبح الدخل الاستحقاقي الإجمالي يغطي حصص الأطباء ويظهر صافي أرباح المستشفى الحقيقية بدقة تامة.
- **تحديث كروت ذكاء الأعمال للأطباء (التبويب 4 - Doctor BI)**:
  * تعديل الكارت الثاني ليعرض `إجمالي الدخل الاستحقاقي للأطباء` كرقم رئيسي، مع أسطر فرعية شفافة توضح صافي المستشفى وحصص الأطباء ونقد الدرج ومطالبات الضمان.
- **حزمة الاختبارات الآلية الحصينة ([tests/Feature/TreasuryExecutiveCardsAndRadiologyModalitiesTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/TreasuryExecutiveCardsAndRadiologyModalitiesTest.php))**:
  * بناء اختبارات ميزة تؤكد ظهور الكروت الـ 6 بمطالبات الضمان والمستحقات المعلقة، وظهور وسائط الأشعة المفككة، مع اجتياز كامل حزمة اختبارات الخزينة (`11 passed, 55 assertions`).

## Session log (2026-10-21 — المرحلة 101: نقل مسار تحميل مرفقات المختبر للمسارات العامة المحمية برمز التوكن لمنع مطالبة المريض بتسجيل الدخول Lab Attachment Route Token-Protected Public Access Fix)

### Done
- **نقل مسار تحميل المرفقات للمسارات العامة المحمية ([routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
  * نقل المسار `Route::get('/lab/requests/{request}/attachment')` خارج مجموعة ميدلوير تسجيل الدخول `Route::middleware(['auth'])` ووضعه مباشرة بجانب مسار التقرير العام `lab.public-report`.
  * حل مشكلة مطالبة المريض بتسجيل الدخول أو إعادة توجيهه لصفحة `/login` عند النقر على زر تحميل تقرير الجهاز (PDF).
  * حماية المسار بكنترولر المختبر [app/Http/Controllers/LabStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabStaffController.php) عبر الرمز المشفر (`?token=...`) بحيث يفتح للمريض بنقرة واحدة، ويُحظر أي وصول غير مصرح به بدون التوكن الصحيح برمز `403 Forbidden`.
- **حزمة الاختبارات الآلية الحصينة ([tests/Feature/LabWhatsAppShareAndPublicReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabWhatsAppShareAndPublicReportTest.php))**:
  * بناء وتأكيد اختباري ميزة لتنزيل المريض للمرفق بدون تسجيل دخول وحظر الدخول بدون رمز، مع اجتياز كامل لجميع اختبارات المختبر وحزمة النظام (`68 passed, 329 assertions`).

## Session log (2026-10-21 — المرحلة 100: توحيد إطار تقرير المختبر الكحلي الشامل وإلغاء الخطوط المطلقة ودمج أزرار تحميل مرفقات الأجهزة بالشريط العائم Lab Report Unified Full Container Border & Toolbar Attachment Downloads)

### Done
- **تصحيح هيدر تقرير المختبر وتوحيد الإطار الخارجي الكامل ([resources/views/staff/requests/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/print.blade.php))**:
  * إعادة تفعيل تنسيق `display: flex !important; direction: rtl !important; justify-content: space-between !important; align-items: center !important;` للهيدر الرئيسي `.report-header` وهيدر صفحة الإدرار `.page2-header` في وضع التقرير العام (`body.is-public-report`).
  * إلغاء الخطوط العائمة المشوهة القديمة (`.decorative-border` المطلقة التي كانت تقطع منتصف الهيدر في الـ PDF) واستبدالها بإطار رسمي موحد ومستمر يحيط بكامل الصفحة: `.report-page { border: 1.5px solid #005a9c; border-radius: 4px; padding: 6mm 10mm; }` للشاشة وللطباعة الورقية، مع إخفائه التلقائي عند الطباعة على ورق المستشفى المروس (`letterhead-mode`).
  * ضبط التباعد العلوي للتقرير العام `body.is-public-report { padding-top: 65px !important; }` مع تصفيره عند الطباعة الورقية (`padding: 0 !important;`).
- **دمج أزرار تحميل مرفقات الأجهزة الطبية مباشرة في الشريط العائم**:
  * إضافة أزرار أنيقة ومميزة داخل شريط الإجراءات العلوي العائم (`.print-toolbar`) للمريض والكادر الطبي لتحميل تقارير الأجهزة الأصلية (مثل CBC و ABG بصيغة PDF) بنقرة زر واحدة.
  * تختفي هذه الأزرار تلقائياً عند حفظ الـ PDF أو الطباعة الورقية بفضل خاصية `@media print { .print-toolbar { display: none !important; } }`، فيبقى التقرير الطبي المطبوع رسمياً وفخماً.
- **حزمة الاختبارات الآلية الحصينة ([tests/Feature/LabWhatsAppShareAndPublicReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabWhatsAppShareAndPublicReportTest.php))**:
  * إضافة وتأكيد اختبارات الميزة لظهور أزرار تحميل المرفقات في شريط الأدوات واختفاء عناصر الإطار المشوهة القديمة مع اجتياز كامل لجميع اختبارات المختبر وحزمة النظام (`66 passed, 327 assertions`).

## Session log (2026-10-21 — المرحلة 99: عزل صفحات تقرير المختبر وتثبيت الفوتر لكل صفحة، إلغاء السطر التقديري لتقارير الأجهزة وحفظ النتائج السابقة وهيدر الفحوصات Lab Report Isolated Page Containers, Dedicated Footers, Removal of Placeholder Device Rows, and 100% Previous Results & Header Fidelity)

### Done
- **عزل الصفحات الطباعية بحاويات مستقلة ([resources/views/staff/requests/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/print.blade.php))**:
  * استبدال جدول `report-print-container` بحاويات صفحات مستقلة كلياً `.report-page.page-1` و `.report-page.page-2.gue-page-break` بنظام CSS Flexbox مع تثبيت الفوتر أسفل كل صفحة على حدة عبر `.report-bottom-block`.
  * حل مشكلة الصفحة الأولى بدون فوتر نهائياً؛ تظهر بيانات الطباعة والتاريخ وعنوان المستشفى أسفل الصفحة 1 وأسفل الصفحة 2 بدقة متناهية.
  * ضبط ارتفاع حاوية الطباعة إلى `min-height: 270mm !important;` مع `page-break-after: always !important;` للصفحة الأولى و `page-break-after: auto !important;` لآخر صفحة لمنع توليد أي صفحة ثالثة بيضاء أو تجاوزات نهائياً.
- **إلغاء السطر التقديري لمرفقات الأجهزة في جدول التحاليل**:
  * حذف إدراج أسطر تقديرية للفحوصات المرفقة كـ CBC داخل جدول الكيمياء الحيوية (إلغاء سطر `📎 تقرير الجهاز مرفق` و `Automated Hematology Analyzer / Clinical Device`) بناءً على توجيهات الطبيب، والاعتماد الكامل على بطاقة وأزرار تحميل تقارير الأجهزة الأصلية (PDF) في شريط المرفقات ورسائل الواتساب.
- **ترويسة مدمجة وخفيفة لصفحة فحص الإدرار العام (G.U.E)**:
  * تزويد الصفحة الثانية بترويسة مستشفى مصغرة وشريط مختصر لمعلومات المريض (الاسم، العمر، الجنس، رقم العينة، والتاريخ) مع جدول النتائج الـ 19 المضغوط المكون من 4 أعمدة، بحيث تتسع كافة الفحوصات وتوقيع وخاتم المختبر والفوتر المعتمد بأريحية تامة دون أي تزاحم.
- **الحفاظ بنسبة 100% على النتائج السابقة وهيدر كل تحليل**:
  * الحفاظ الكامل على دالة استرجاع السجلات التاريخية للمريض `$findPrevResults` وعرض مربع النتائج السابقة الرأسي والرقائق الملونة وتواريخ الفحوصات السابقة أسفل القيمة الحالية لكل تحليل.
  * الحفاظ على بانر العنوان المستقل لكل فحص (`category-header-row`) لمنع أي تداخل بين التحاليل.
- **حزمة الاختبارات الآلية الحصينة ([tests/Feature/LabGeneralUrinePageBreakAndCollisionFixTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabGeneralUrinePageBreakAndCollisionFixTest.php))**:
  * اجتياز كامل لجميع اختبارات المختبر وحزمة النظام (`65 passed, 324 assertions`).

## Session log (2026-10-21 — المرحلة 98: حل تضارب الباراميترات الفرعية للمختبر وعزل فحص الإدرار العام (G.U.E) في صفحة طباعية مستقلة ومطابقة كافة الفحوصات والمرفقات 100% Lab Subtest Collision Fix, Dedicated GUE Page Break, Clinical Grouping & 100% Ordered Tests Coverage)

### Done
- **معالجة تضارب مفاتيح إدخال الباراميترات الفرعية ([resources/views/lab/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/show.blade.php) & [resources/views/staff/requests/show-lab.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/show-lab.blade.php))**:
  * تحويل مفاتيح الإدخال للباراميترات الفرعية من اسم الفحص المشترك إلى مفتاح فريد ومميز بمعرف الباراميتر: `test_results[sub_{{ $subTest->id }}][value]`.
  * حل مشكلة مسح الفحوصات التي تشترك في نفس أسماء المعايير الفرعية (مثل اشتراك FSH و LH في معيار `Men` و `Follicular Phase` الخ) نهائياً ومنع استبدال القيم أثناء الإرسال (`$_POST`).
  * ترقية استرجاع القيم المحفوظة في الشاشة عبر الفهرسة المتعددة (`$dbResultsBySubId` و `$dbResultsByParentAndName`) لضمان ظهور القيم المحفوظة لجميع التحاليل بدقة متناهية.
- **إفراد فحص الإدرار العام (General Urine Examination - G.U.E) في صفحة طباعية مستقلة ([resources/views/staff/requests/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/print.blade.php))**:
  * عزل فحص الإدرار العام (19 فحصاً فرعياً) عن الفحوصات العامة (الهرمونات، الدم، الكيمياء) وتطبيق فاصل صفحات طباعي تلقائي: `.gue-page-break` (`page-break-before: always; break-before: page;`).
  * إذا كان الطلب يحتوي على G.U.E فقط دون تحاليل أخرى، يُطبع في صفحة واحدة مباشرة دون إحداث صفحة بيضاء سابقة.
  * عرض نتائج فحص الإدرار الـ 19 مباشرة وبشكل سلس في جدول الصفحة المخصصة بدون أي تقسيمات فرعية متكلفة مع ترويسة أعمدة واضحة `[ Test Description | Result | Status ]`.
  * تزويد كل من صفحة الفحوصات العامة وصفحة فحص الإدرار المستقلة بختم وتوقيع معتمد ومستقل بأعلى معايير الجودة الطبية.
- **عزل وفصل فحوصات الهرمونات والتحاليل الفردية ومنع التصنيف العالق (Category Bleed Fix)**:
  * التحقق من الفئات المرجعية الديموغرافية (مثل Men, Follicular Phase) وعرض الاسم الطبي الحقيقي للفحص (`Follicular-stimulating hormone` و `Luteinizing hormone`) في عمود Test Description، وإلحاق اسم الفئة بالمعدل الطبيعي: `1.7 - 8.6 (Men)`.
  * تصفية وإلغاء التصنيف العالق فوراً (`$currentGroup = null`) بحيث تظهر التحاليل المستقلة التالية (`Prolactin`, `Thyroid stimulating hormone`, `25-OH Vitamin`) في سطور وبطاقات مستقلة تماماً ومفصولة بخطوط واضحة دون أي دمج خاطئ.
- **مطابقة الفحوصات المطلوبة بنسبة 100% وإظهار مرفقات الأجهزة (CBC) والفحوصات المعلقة**:
  * رصد ومطابقة كافة الفحوصات المطلوبة من قبل الطبيب في التقرير المطبوع.
  * الفحوصات المرفقة كتقارير أجهزة (مثل Complete blood count & Differential) يظهر لها سطر رسمي بارز ومميز في جدول النتائج المطبوعة: `Complete blood count & Differential` | `📎 تقرير الجهاز مرفق (Attached Device Report)` | شارة `Attached`.
  * الفحوصات التي لم تُدخل نتائجها بعد تظهر بشارة `Pending (قيد الإنجاز)` ولا تختفي أو تسقط من التقرير.
- **تحديث وإكمال نتائج الطلب رقم 285 للمريض جاسم حامد عباس**:
  * حفظ وتثبيت قيمة فحص FSH للرجال (`3.5 mIU/mL`) بجانب فحص LH (`2.72 mIU/mL`) بنجاح، وظهور كافة الفحوصات الـ 8 للمريض كاملة في شاشة المختبر والتقرير المطبوع.
- **الاختبارات الآلية الحصينة ([tests/Feature/LabGeneralUrinePageBreakAndCollisionFixTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabGeneralUrinePageBreakAndCollisionFixTest.php))**:
  * بناء واجتياز اختبارات ميزة متكاملة تؤكد منع التضارب بين FSH و LH، وتؤكد عزل G.U.E بفاصل الصفحة وظهور تقرير CBC المرفق وكافة الفحوصات بنجاح (`2 passed, 17 assertions`)، مع اجتياز كامل حزمة فحوصات المختبر (`65 passed, 324 assertions`).

## Session log (2026-10-21 — المرحلة 97: مطابقة آلية طلبات طبيب الطوارئ مع الاستشارية: الدمج التلقائي، الحذف الفردي، شريط التقدم السريري، وأرشيف الطلبات الملغاة Emergency Doctor Request Auto-Merge, Item-Level Removal, Clinical Stepper & Cancelled Archive)

### Done
- **الدمج التلقائي لطلبات المختبر والأشعة للطوارئ ([app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php))**:
  * عند قيام طبيب الطوارئ بطلب تحاليل مختبرية جديدة لحالة طوارئ لديها طلب معلق غير مسدد (`payment_id === null` و `status === 'pending'`)، يتم دمج الفحوصات الجديدة تلقائياً في نفس الطلب عبر `syncWithoutDetaching` بدلاً من تشتيت الكاشير بطلبات متعددة.
  * نفس الدمج التلقائي الذكي يطبق على طلبات الأشعة والسونار غير المسددة.
  * تحديث وحساب المبالغ المستحقة للكاشير تلقائياً بعد كل دمج عبر `upsertUnifiedEmergencyPayment`.
- **تمكين طبيب الطوارئ من حذف وتعديل الفحوصات الفردية ([app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php) & [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
  * إضافة مسار ودالة الحذف الفردي `DELETE /emergency/{emergency}/remove-request-item`.
  * قفل وحظر الحذف التام إذا كان الطلب مسدداً لدى الكاشير (`payment_id !== null` أو حالة الطلب مسدد) لحماية الصندوق والحسابات برمز `422 Unprocessable Entity`.
  * فك ارتباط الفحص المطلوب فقط من جدول الربط الوسيط (`detach`).
  * في حال حذف الفحص الأخير المتبقي في الطلب، يتم تحويل حالة الطلب تلقائياً إلى ملغي (`cancelled`) وتوثيق سبب الإلغاء، مع تحديث استحقاق الكاشير.
- **ترقية شاشة الطوارئ بواجهة الاستشارية الحديثة ([resources/views/emergency/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/show.blade.php))**:
  * **شريط مسار ومراحل الفحص التفاعلي (Status Progress Stepper)**: شريط مصغر بأعلى كل بطاقة يوضح مراحل الإنجاز: `[ 1. طلب الطبيب ⬅️ 2. عند الكاشير / سُدّد ⬅️ 3. قيد الفحص بالمختبر/الأشعة ⬅️ 4. النتيجة جاهزة ]` مع شارات وألوان تفاعلية.
  * **الجدول السريري المصغر (Clinical Mini-Table)**: استبدال طريقة العرض القديمة بجدول سطر منظم وأنيق يعرض: رقم التسلسل، اسم الفحص، شارة الحالة الحية، وزر الحذف `[ × ]` التفاعلي للأطباء قبل السداد عبر AJAX سلس دون إعادة تحميل الصفحة.
  * **أرشيف الطلبات الملغاة**: عزل وإخفاء الطلبات الملغاة من القائمة النشطة ووضعها داخل أكورديون مطوي أنيق: `[ 📁 أرشيف طلبات المختبر/الأشعة الملغاة (مخفية) ]`.
  * تصفية اللوحة الجانبية لمريض الطوارئ (`side-cur-pane`) لإخفاء الطلبات الملغاة من قائمة الإجراءات النشطة.
- **الاختبارات الآلية الحصينة ([tests/Feature/EmergencyRequestItemModificationAndAutoMergeTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyRequestItemModificationAndAutoMergeTest.php))**:
  * بناء واجتياز 7 اختبارات ميزة متكاملة تغطي الدمج التلقائي للمختبر والأشعة، الحذف الفردي، تحول الطلب لملغي عند إفراغه، حظر الحذف بعد السداد، وظهور الـ Stepper والجدول المصغر وأرشيف الملغاة (`7 passed, 38 assertions`)، مع اجتياز كامل حزمة الطوارئ بنجاح (`40 passed, 209 assertions`).

## Session log (2026-10-21 — المرحلة 96: ربط شاشة المختبر بإعدادات أنماط الإدخال وإلغاء أزرار المرفقات للفحوصات الرقمية المفردة وتمكين المريض من تحميل مرفقات الأجهزة عبر الواتساب Lab Result Entry Types Integration, Selective Attachment Buttons & Patient WhatsApp Attachment Downloads)

### Done
- **الالتزام بأنماط إدخال النتائج المعتمدة في شاشة الإعدادات ([resources/views/lab/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/show.blade.php))**:
  * حذف كلمة `(اختياري)` من ترويسة الجدول لتصبح `تقرير الجهاز / المرفق`، وإلغاء كلمة `(اختياري)` من أزرار الرفع.
  * إلغاء ظهور زر `إرفاق تقرير` للفحوصات المحددة كـ `single_value` (قيمة مباشرة مفردة) أو `sub_tests` (قيم فرعية) وإظهار شرطة `—` بدلاً منها لمنع تشويه الجدول.
  * قصر إظهار زر رفع المرفق حصراً على الفحوصات المحددة في الإعدادات كـ `attachment_only` (مرفق تقرير جهاز) أو `flexible` (مرن) أو الفحوصات التي رُفع لها مرفق مسبقاً.
- **تمكين المريض من استعراض وتحميل مرفقات الأجهزة عبر الواتساب والتقرير الطبي ([resources/views/staff/requests/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/print.blade.php) & [app/Http/Controllers/LabStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabStaffController.php))**:
  * إضافة بطاقة أنيقة في التقرير الطبي المعتمد تظهر فيها أزرار تحميل وعرض تقارير الأجهزة والملفات المرفقة (العامة والخاصة بكل فحص).
  * تمكين المريض من فتح وتحميل المرفقات بصورة آمنة ومباشرة عبر التوكن المشفر `?token=...` دون الحاجة لتسجيل دخول، وتخطي حظر 403.
  * تضمين تنبيه ذكي في نص رسالة الواتساب ينبه المريض بوجود تقارير أجهزة مرفقة يمكنه استعراضها وتحميلها بنقرة زر واحدة ([app/Models/Request.php](file:///c:/wamp64/www/hospital-system/app/Models/Request.php)).
- **الاختبارات الآلية الحصينة**:
  * اجتياز كامل لجميع اختبارات حزمة المختبر والواتساب وأنماط الإدخال (`16 passed, 72 assertions`).

## Session log (2026-10-21 — المرحلة 93: فصل حفظ مسودة نتائج المختبر عن الاعتماد النهائي والإرسال للطبيب Lab Draft Saving vs Final Doctor Transmission)

### Done
- **فصل الحفظ كمسودة عن الاعتماد النهائي ([app/Http/Controllers/LabStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabStaffController.php))**:
  * عند اختيار `action === 'draft'` أو `status === 'in_progress'`: حفظ النتائج الرقمية والمدخلات ومرفقات الأجهزة والملاحظات كمسودة في قاعدة البيانات وجدول `lab_results`.
  * تثبيت حالة الطلب على `in_progress` (قيد الفحص بالمختبر) لضمان عدم وصول إشعار للطبيب أو تقدم مسار الطلب لديه قبل اكتمال التحاليل.
  * توثيق النشاط المكتبي كمسودة `save_lab_draft` وإبقاء فني المختبر في نفس الشاشة مع رسالة نجاح خضراء لتسهيل إكمال باقي الفحوصات لاحقاً.
  * عند اختيار `action === 'complete'`: اعتماد النتائج نهائياً، تحويل حالة الطلب إلى `completed`، إرسال إشعار التيليجرام، وتوثيق النشاط `save_lab_results`، والتحويل إلى القائمة الرئيسية للمختبر.
- **شريط الإجراءات المزدوج وشارات الحالة في واجهة المختبر ([resources/views/lab/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/show.blade.php))**:
  * إضافة زرين مميزين في الترويسة العلوية وفي شريط أسفل النتائج:
    1. `[ 💾 حفظ مؤقت (مسودة) ]`: بلون تحذيري دافئ ومميز.
    2. `[ 🚀 اعتماد نهائي وإرسال للطبيب ]`: بلون أخضر رسمي.
  * شارة ذكية أعلى الصفحة `[ ✏️ مسودة قيد الفحص ]` توضح لفني المختبر حالة الطلب الحالية بوضوح، وبطاقة تنبيه سفلية تبين وضع الطلب.
  * ضبط دالة JavaScript `submitLabForm` لتحديد نوع الإجراء والحالة بدقة وسلاسة.
- **حماية واجهة الطبيب والبيانات (Zero Doctor Disruption & Zero Data Loss)**:
  * الإبقاء الكامل على واجهة كشف الطبيب دون أي تغيير؛ تظل تعرض مرحلة `[ 🔬 تنفيذ الفحص بالمختبر ]` طالما أن الطلب مسودة، ولا تنتقل إلى `[ ✅ النتيجة جاهزة ]` إلا بعد الاعتماد النهائي.
- **الاختبارات الآلية الحصينة ([tests/Feature/LabDraftResultSaveTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabDraftResultSaveTest.php))**:
  * اجتياز كامل لـ 3 اختبارات ميزة تغطي حفظ المسودة، والاعتماد النهائي، ووجود الأزرار الثنائية (`3 passed, 20 assertions`).

## Session log (2026-10-21 — المرحلة 95: حظر الفحوصات المعطلة ومنع تكرار التحاليل في واجهة المختبر والكاشير ومطابقة طلبات الطبيب 1-to-1 دون المساس بسجلات قاعدة البيانات Lab & Cashier Active-Only Filtering, Name Deduplication & 1-to-1 Doctor Order Fidelity)

### Done
- **مطابقة طلبات الطبيب 1-to-1 وحظر ربط الفحوصات المعطلة ([app/Http/Controllers/DoctorVisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorVisitController.php))**:
  * عرض التحاليل المفعلة فقط والفريدة بالاسم للطبيب في شاشة الكشف الطبي.
  * عند حفظ طلب الطبيب: الاستعلام حصراً عن الفحوصات المفعلة (`where('is_active', true)`) وأخذ أول معرف مفعل لكل اسم فحص، مما يمنع نهائياً سحب أو تخزين معرفات الفحوصات المعطلة أو المكررة في `details['lab_test_ids']`.
  * في دالة حذف الفحص الفردي `removeRequestItem`: مطابقة وحذف كافة المعرفات المرتبطة باسم الفحص لضمان خلو الطلب من المعرفات المعلقة.
- **تصفية واجهة محطة المختبر وإلغاء التكرار الصارم ([resources/views/lab/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/show.blade.php))**:
  * في دالة `buildSelectedLabTests`: استبعاد أي فحص معطل (`!$labTest->is_active`) من الظهور أمام فني المختبر حتى في الطلبات القديمة المسجلة مسبقاً.
  * إلغاء التكرار الصارم الموحد بالاسم الصافي مع توحيد الأحرف والمسافات (`Normalized Case-Insensitive Unique Name`).
  * ترتيب التحاليل تنازلياً حسب التفعيل (`orderByRaw('is_active DESC')`) لربط المراجع والقيم الفرعية بالفحص المفعل دائماً.
- **حماية حسابات الكاشير وطباعة الوصل الحراري 80mm ([app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php) & [resources/views/cashier/receipt-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt-print.blade.php))**:
  * استبعاد الفحوصات المعطلة من الاحتساب المالي ومنع تكرار نفس الفحص عبر مصفوفة التتبع `$seenLabTestNames`.
  * في طباعة الوصل الحراري 80mm: عدم طباعة أي فحص معطل، وتصفية الأسطر بالاسم الصافي لمنع تكرار الفحص في الوصل.
  * في نموذج الطلب [app/Models/Request.php](file:///c:/wamp64/www/hospital-system/app/Models/Request.php): جمع أسعار الفحوصات المفعلة واستبعاد التكرارات.
- **حماية البيانات التاريخية (Zero Data Deletion)**:
  * الإبقاء الكامل على جميع سجلات جدول `lab_tests` دون حذف أي سطر لحماية التاريخ الطبي والمحاسبي.
- **الاختبارات الآلية الحصينة ([tests/Feature/LabAndCashierDeduplicationAndActiveOnlyTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabAndCashierDeduplicationAndActiveOnlyTest.php))**:
  * اجتياز كامل لـ 3 اختبارات ميزة تغطي دورة الفحص كاملة (`3 passed, 13 assertions`).

## Session log (2026-10-21 — المرحلة 94: تصحيح أسعار الخدمات الفردية في الوصل الحراري 80mm وإلغاء تكرار المجموع الكلي وعرض المبلغ المطلوب من المريض فقط Cashier 80mm Thermal Receipt Line-Item Patient Fee Fix & Grand Total Duplication Removal)

### Done
- **معالجة تكرار المجموع الإجمالي أمام كل خدمة ([resources/views/cashier/receipt-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt-print.blade.php))**:
  * إلغاء الشرط القديم `empty($payment->patient_share) ? $item['price'] : $payment->amount` الذي كان يتسبب في تكرار المجموع الإجمالي في كل أسطر الجدول لمرضى الضمان الصحي.
  * ضبط عمود المبلغ ليعرض السعر الفعلي لكل خدمة المطلوب من المريض فقط `$item['price']`.
- **حساب حصة المريض الدقيقة لكافة الخدمات الفردية في الوصل**:
  * تحاليل المختبر وفحوصات الأشعة: استدعاء `calculateInsurancePricing` وسحب `patient_share` بدلاً من السعر المعتمد الكامل.
  * باقات المختبر واستشارات الطوارئ وخدمات الطوارئ: تطبيق نسبة التحمل بدقة متناهية (`copay_percentage`).
  * مطابقة رياضية بنسبة 100% بين مجموع أسطر الخدمات وسطر "المبلغ المدفوع" في قاع الوصل.
- **الاختبارات الآلية الحصينة ([tests/Feature/CashierPrintReceiptItemPriceTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/CashierPrintReceiptItemPriceTest.php))**:
  * إنشاء واجتياز اختبار ميزة متكامل يتحقق من ظهور الأجور الفردية المطلوبة من المريض حصراً أمام كل فحص وعدم تكرار المجموع الإجمالي في أسطر الجدول (`1 passed, 7 assertions`).

## Session log (2026-10-21 — المرحلة 92: تمكين الطبيب من حذف الفحوصات الفردية وإخفاء الطلبات الملغاة وإلغاء عرض الأسعار لمنع التشتيت Doctor Item-Level Request Modification, Clean Clean UI & Cancelled Requests Archive)

### Done
- **تمكين الطبيب من حذف وتعديل الفحوصات الفردية من الطلبات غير المسددة ([app/Http/Controllers/DoctorVisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorVisitController.php))**:
  * إضافة دالة `removeRequestItem` ومسار `DELETE /doctor/requests/{request}/remove-item`.
  * حظر وإقفال الحذف إذا كان الطلب مسدداً `payment_status === 'paid'` لحماية الحسابات.
  * دعم حذف تحاليل المختبر وفحوصات الأشعة والخدمات التمريضية كلاً على حدة مع تحديث `details` وإعادة احتساب المبالغ تلقائياً.
  * في حال حذف الفحص الأخير المتبقي في الطلب، يتم تحويل حالة الطلب تلقائياً إلى ملغي `cancelled` وتوثيق سبب الإلغاء.
- **إخفاء الطلبات الملغاة وترقية بطاقات طلبات الطبيب وإلغاء الأسعار ([resources/views/doctors/visits/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/show.blade.php))**:
  * عزل وتصفية الطلبات النشطة فقط في القائمة الأساسية لتجنب تشتيت الطبيب.
  * تجميع كافة الطلبات الملغاة في أسفل التبويب داخل قسم مطوي أنيق `[ 📁 أرشيف الطلبات الملغاة (مخفية) ]`.
  * **شريط مسار ومراحل الفحص التفاعلي (Status Progress Stepper)**: شريط مصغر بأعلى كل بطاقة يوضح مراحل الإنجاز الأربعة: `[ طلب الطبيب ⬅️ سداد الكاشير ⬅️ تنفيذ الفحص بالمختبر/الأشعة ⬅️ النتيجة جاهزة ]` مع ألوان وأيقونات حية.
  * **الجدول السريري المصغر (Clinical Mini-Table)**: استبدال الشارات بجدول سطر منظم يعرض: رقم التسلسل، اسم الفحص، العضو المستهدف (للأشعة)، شارة الحالة، وزر الحذف `[×]` التفاعلي مع حذف AJAX السلس.
  * تمييز لوني واضح لحالة الدفع: شارة صفراء وإطار أصفر للطلبات غير المسددة، شارة خضراء للمدفوعة.
  * إلغاء عرض الأسعار في بطاقات الطلبات داخل عيادة الطبيب لمنع التشتيت وحصر الحسابات والخصومات بنظام الكاشير.
- **الاختبارات الآلية الحصينة ([tests/Feature/DoctorRemoveRequestItemTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DoctorRemoveRequestItemTest.php))**:
  * اجتياز كامل لـ 5 اختبارات ميزة تغطي حذف تحليل، إلغاء الطلب عند خلوه، حذف فحص أشعة، حظر الحذف بعد السداد، وإخفاء الملغاة (`5 passed, 23 assertions`).

## Session log (2026-10-21 — المرحلة 91: توحيد سداد ووصول وتجميع طلبات الزيارة الطبية في الكاشير والدمج التلقائي وإلغاء التكرار Doctor & Cashier Unified Visit Checkout, Single Receipt & Consolidated Index Table)

### Done
- **الدمج التلقائي لطلبات الطبيب غير المسددة ([app/Http/Controllers/DoctorVisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorVisitController.php))**:
  * دمج تلقائي فور حفظ الطبيب لأي فحوصات مخبرية جديدة في نفس الطلب غير المسدد `payment_status === 'pending'`.
  * دمج تلقائي فور حفظ الطبيب لأي فحوصات شعاعية جديدة في نفس طلب الأشعة غير المسدد لمنع تكرار وتعدد الطلبات المعلقة في النظام.
- **تعديل كادر المختبر والأشعة للفحوصات قبل السداد وتثبيتها بعده ([app/Http/Controllers/StaffRequestController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/StaffRequestController.php))**:
  * إضافة إمكانية إضافة وحذف تحاليل للمختبر (`appendLabTests`, `removeLabTest`).
  * إضافة إمكانية إضافة وحذف فحوصات للأشعة (`appendRadiologyTypes`, `removeRadiologyType`).
  * حظر وإقفال التعديل فور السداد `payment_status === 'paid'` لحماية الحسابات.
- **سداد موحد شامل لكافة خدمات الزيارة في الكاشير ([app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php))**:
  * في نافذة سداد الطلبات (`showRequestPaymentForm`): سحب كافة طلبات الزيارة المعلقة (مختبر + أشعة) تلقائياً في فاتورة واحدة شاملة.
  * في معالجة الدفع (`processRequestPayment`): دفع جميع الطلبات في حركة مالية واحدة وسجل دفع واحد `Payment` وتحديث كافة الطلبات إلى `paid` مع ربطها بنفس `payment_id`.
- **وصل حراري 80mm واحد شامل للمريض بدون أي تكرار ([resources/views/cashier/receipt-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt-print.blade.php))**:
  * طباعة وصل حراري موحد يحمل رقم تسلسلي واحد `REC-...` ويشمل تحاليل المختبر وفحوصات الأشعة معاً.
  * تطبيق فلترة وإلغاء التكرار الصارم (`Deduplication`) لضمان عدم تكرار اسم أي فحص مهما كانت السجلات القديمة.
- **تجميع الطلبات المعلقة في جدول الكاشير ([resources/views/cashier/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/index.blade.php))**:
  * دمج أسطر الطلبات المعلقة لنفس الزيارة في سطر واحد موحد يحمل شارات الأقسام المشتركة `[🔬 تحاليل] [🩻 أشعة] [طلب موحد]`.
  * استبدال الأزرار المتعددة بزر واحد: `تسديد شامل` يمنع تكرار ظهور نفس المريض بمبالغ متطابقة.
- **الاختبارات الآلية الحصينة**:
  * `tests/Feature/DoctorLabRequestAutoMergeAndLabEditTest.php` (5 passed)
  * `tests/Feature/DoctorRadiologyRequestAutoMergeAndStaffEditTest.php` (5 passed)
  * `tests/Feature/UnifiedVisitCheckoutCashierTest.php` (2 passed)
  * اجتياز كامل للحزمة (`12 passed, 78 assertions`).

## Session log (2026-10-20 — المرحلة 89: حل مشكلة التحديث السريع لصفحة الكاشير وضبط التوقيت الافتراضي على 30 ثانية مع أداة تحكم مرنة وزر تحديث يدوي فوري Cashier Live Refresh Rate Optimization, Configurable Interval Selector & Manual Sync)

### Done
- **معالجة التحديث السريع لمحطة الكاشير ([resources/views/cashier/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/index.blade.php))**:
  * إلغاء التحديث التلقائي القديم المفرط الذي كان يعمل كل 5 ثوانٍ (`setInterval 5000`) ويتسبب في ارتباك الكاشير ومسح حقول الإدخال والبحث.
  * ضبط التوقيت الافتراضي الموصى به على **30 ثانية** مع جدولة ذكية عبر `setTimeout` لضمان عدم تداخل الطلبات المتتابعة.
- **إضافة أداة التحكم بوقت التحديث وزر التحديث اليدوي الفوري**:
  * إضافة قائمة منسدلة أنيقة في ترويسة الكاشير بجوار مؤشر المباشر تتيح للكاشير اختيار:
    - `15 ثانية`
    - `30 ثانية` (افتراضي)
    - `دقيقة واحدة` (60 ثانية)
    - `دقيقتين` (120 ثانية)
    - `إيقاف التلقائي` (تحديث يدوي فقط)
  * حفظ وتثبيت خيار المستخدم في المتصفح `localStorage.getItem('cashier_refresh_interval')` ليستمر عبر الجلسات والصفحات.
  * زر أيقوني دائري لتحديث البيانات فورياً بنقرة زر 🔄 مع تأثير دوران تفاعلي (`fa-spin`) أثناء جلب البيانات.
  * تحديث شارة "مباشر" تلقائياً لتعرض الوقت المختار (مثل: `مباشر (30 ث)`) أو التحول إلى شارة رمادية `متوقف (يدوي)` عند إيقاف التحديث التلقائي.
- **الحماية الذكية من مقاطعة الكاشير أثناء الإدخال (Smart Non-Intrusive Polling)**:
  * إيقاف وتأجيل التحديث التلقائي فوراً في الحالات التالية:
    1. عند وجود أي نافذة منبثقة مفتوحة (`.modal.show`).
    2. عند تركيز أو كتابة الكاشير في حقول البحث أو إدخال التاريخ أو النماذج (`input:focus, textarea:focus, select:focus`) لمنع ضياع ما يكتبه.
    3. عند تصغير أو إخفاء تبويب المتصفح (`document.hidden`) لتقليل الحمل على السيرفر، مع استئناف فوري وسلس بمجرد العودة للتبويب (`visibilitychange`).
- **الاختبارات الآلية الحصينة ([tests/Feature/CashierRefreshIntervalControlTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/CashierRefreshIntervalControlTest.php))**:
  * إنشاء واجتياز اختبار ميزة يتحقق من وجود أدوات التحكم بالتحديث وزر المزامنة ومطابقة الفترات الزمنية بنجاح كامل (`1 passed, 7 assertions`).

## Session log (2026-10-20 — المرحلة 88: اعتماد ومزامنة خدمات الطوارئ الرسمية 96 خدمة وتصحيح الأسعار كاش وضمان واستبعاد الخدمات المكررة والقديمة من واقع ملف الإكسل المعتمد Approved Emergency Services Excel Full Sync & Pricing Reconciliation)

### Done
- **المزامنة الشاملة لجدول خدمات الطوارئ (`emergency_services`)**:
  * قراءة واعتماد ملف الإكسل المعتمد `emergncy final.xlsx` الذي يحتوي على 96 خدمة طوارئ منقحة.
  * إنشاء ملف بيانات رسمي ثابت [database/seeders/approved_emergency_services_data.php](file:///c:/wamp64/www/hospital-system/database/seeders/approved_emergency_services_data.php).
  * إنشاء مايجريشن آلي قابل للنشر المباشر [database/migrations/2026_10_20_110000_sync_emergency_services_from_approved_excel.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_20_110000_sync_emergency_services_from_approved_excel.php) وتنفيذه بنجاح.
- **تصحيح الأسعار وحالات الضمان والكاش**:
  * تصحيح الخدمات التي كانت مسجلة بـ 0 د.ع في الداتابيز (مثل: إدخال أنبوب صدر Chest tube بـ 500,000 كاش وضمان، أجور التجبيس للأطراف السفلى بـ 50,000 كاش و 40,000 ضمان، خدمات نقل الإسعاف الداخلي 25,000 والخارجي 50,000، وإعطاء الدم تحت إشراف طبيب مع المطابقة بـ 75,000).
  * تحديث أسعار الكاش وأسعار الضمان الصحي وحالات التغطية لـ 20 خدمة مختلفة.
- **إدراج الخدمات المعتمدة الجديدة**:
  * إضافة خدمة `#114`: `أجور التجبير مع كافة المستلزمات بنوعيه (fiberglass or plaster of paris) نصفي او كامل - الاطراف العليا` (كاش: 50,000 د.ع، ضمان: 30,000 د.ع، مشمول بالضمان).
  * ضبط خدمة `#66`: `زرق إبرة خارجي` بسعر 25,000 د.ع.
- **التعطيل الآمن للخدمات القديمة والمكررة (15 خدمة)**:
  * إيقاف تفعيل (`is_active = 0`, `is_hi_active = 0`) للخدمات الـ 15 غير الموجودة بملف الإكسل المعتمد (مثل تكرارات صوندة المعدة، وتكرارات الغرز القديمة)، لمنع ظهورها في شاشات الاستقبال ومحطة كشف الطوارئ مع حماية السجلات التاريخية.
- **تحديث السيدر الرسمي والاختبارات الآلية الحصينة**:
  * تحديث [database/seeders/EmergencyServicesSeeder.php](file:///c:/wamp64/www/hospital-system/database/seeders/EmergencyServicesSeeder.php).
  * إنشاء واجتياز اختبار الميزة [tests/Feature/EmergencyServicesApprovedSyncTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyServicesApprovedSyncTest.php) واجتياز حزمة الطوارئ كاملة (`33 passed, 171 assertions`).

## Session log (2026-10-19 — المرحلة 87: إصلاح باقاتي السريعة للمختبر وتفعيل الفلترة اللحظية الذكية وإلغاء التحديد بالنقر المزدوج Doctor Consultation Quick Lab Packages Fix, Instant Filter & Toggle Deselection)

### Done
- **إصلاح أزرار الباقات السريعة وعداد الفحوصات ([resources/views/doctors/visits/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/show.blade.php) & [app/Models/UserLabTestGroup.php](file:///c:/wamp64/www/hospital-system/app/Models/UserLabTestGroup.php))**:
  * حل مشكلة عدم استجابة باقات المختبر السريعة للطبيب: استبدال استدعاء `$pkg->tests` الذي كان يرجع `null` بالخاصية الصحيحة `$pkg->labTests->pluck('id')` و `$pkg->labTests->pluck('name')`.
  * إضافة دالة الـ Accessor المتوافقة `getTestsAttribute()` في نموذج `UserLabTestGroup` لضمان عدم حدوث أي خطأ عند استدعاء `$group->tests`.
  * تصحيح شارة عداد الفحوصات في الباقة لتعرض العدد الفعلي لفحوصات الباقة `{{ $pkg->labTests->count() }}`.
- **الفلترة اللحظية الذكية وعرض فحوصات الباقة فقط**:
  * عند النقر على الباقة لأول مرة، يتم اختيار كافة فحوصاتها وتصفية شبكة التحاليل فورياً لعرض فحوصات الباقة فقط، لتمكين الطبيب من مراجعتها وتعديلها بسهولة دون التشتت في شبكة الـ 600+ فحص.
  * إظهار شريط إشعار تفاعلي أنيق أعلى الشبكة (`#docLabActiveFilterNotice`) يوضح اسم الباقة النشطة وعدد الفحوصات وزر `[ إلغاء الفلترة وعرض كافة الفحوصات ]`.
  * إضافة تبويب سريع في شريط الفئات: **[ 🎯 المختارة فقط ]** (`SELECTED_ONLY`) لعرض كافة الفحوصات المحددة حالياً في أي وقت.
- **إلغاء التحديد وإلغاء الفلترة عند النقر مجدداً على الباقة (Toggle Deselection & Reset)**:
  * عند النقر مرة ثانية على زر نفس الباقة المفعلة أو إذا كانت كافة فحوصاتها محددة بالفعل، يتم تلقائياً إلغاء تحديد كافة فحوصات هذه الباقة (`cb.checked = false`)، وإلغاء فلترة الباقة لتعود الشبكة لعرض كافة الفحوصات، وتحديث شريط الفحوصات المختارة فورياً.
- **الاختبارات الآلية الحصينة ([tests/Feature/DoctorLabQuickPackagesTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DoctorLabQuickPackagesTest.php))**:
  * إنشاء واجتياز اختبار الميزة بنجاح كامل (`2 passed, 9 assertions`).

## Session log (2026-10-19 — المرحلة 86: ترقية موضع التشخيص الطبي ICD-10 لكارت عريض بارز وفصل حقل التوصيات وإلغاء التعبئة التلقائية Doctor Consultation ICD-10 Full-Width Prominence & Clean Clinical Notes)

### Done
- **فصل التوصيات وإلغاء التعبئة التلقائية ([resources/views/doctors/visits/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/show.blade.php))**:
  * إلغاء نسخ اسم التشخيص تلقائياً إلى خانة تفاصيل وتوصيات التشخيص (`diagnosis[description]`) عند اختيار عنصر من القائمة، ليظل الحقل نظيفاً تماماً ومفرغاً لتعليمات وتوصيات الطبيب للمريض.
- **ترقية موضع ومظهر التشخيص الطبي ICD-10 المعتمد (Full-Width Hero Section)**:
  * نقل قسم التشخيص الطبي من العمود النصفي الضيق إلى كارت عريض بارز (`col-12`) أسفل العلامات الحيوية مباشرة.
  * حقل بحث واسع (`input-group-lg`) مع أيقونة بحث متفاعلة وزر مسح سريع ومؤشر تحميل.
  * ترقية القائمة المنسدلة المقترحة لتفتح بعرض كامل 100% مع طبقة عليا فائقة (`z-index: 99999 !important`) وإطار كحلي واضح (`2px solid #005a9c`) وظلال مرتفعة ومنع أي قص (`overflow: visible !important`).
  * بطاقة تأكيد التشخيص المختار النشطة في سطر عريض وواضح وزر سريع لتغيير التشخيص.
- **الصف الثنائي المتوازن للملاحظات السريرية**:
  * توزيع الفحص السريري (Physical Examination) في الجانب الأيمن، وتفاصيل وتوصيات التشخيص في الجانب الأيسر بارتفاع متناسق ومريح.
- **الاختبارات الآلية الحصينة ([tests/Feature/DoctorIcd10SearchTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DoctorIcd10SearchTest.php))**:
  * إضافة اختبار التحقق من إمكانية حفظ التشخيص بحقل توصيات فارغ (`doctor_can_save_diagnosis_with_empty_description`).
  * اجتياز كامل لكافة الاختبارات (`7 passed, 28 assertions`).

## Session log (2026-10-19 — المرحلة 85: محرك البحث الذكي لتشخيصات ICD-10 بالأعمدة الثلاثة وترقية تبويب الفحص والتشخيص المنظم في الاستشارية Doctor Outpatient ICD-10 Live 3-Column Search & Balanced Tab Layout)

### Done
- **محرك البحث اللحظي لتشخيصات ICD-10 بالأعمدة الثلاثة ([app/Http/Controllers/DoctorVisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorVisitController.php))**:
  * إضافة دالة `searchIcd10(HttpRequest $request)` تبحث في أعمدة `code` و `description_ar` و `description` مع ترتيب ذكي يبدأ بالمطابقة الدقيقة ثم بدايات النصوص، مع تحديد حد أقصى للنتائج لمنع أي بطء.
  * تسجيل المسار السريع `GET /doctor/icd10/search` في [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php).
  * لا يتم عرض أو إرجاع أي نتائج إذا كان حقل البحث فارغاً (تطبيقاً لشرط "لا يعرض إلا عند بدء الكتابة").
- **ترقية وتنظيم تبويب الفحص والتشخيص ([resources/views/doctors/visits/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/show.blade.php))**:
  * **شريط العلامات الحيوية الرقمية (Digital Vitals Strip)**:
    - بطاقات مصغرة متناسقة لكافة المؤشرات الحيوية (الضغط الانقباضي والانبساطي، النبض، الحرارة، التنفس، الأكسجين، الوزن، الطول).
    - **حاسبة مؤشر كتلة الجسم الحية (Live BMI Calculator)**: حساب آلي فوري لمؤشر كتلة الجسم فور إدخال الوزن والطول وإظهار شارة ملونة ذكية (طبيعي / نقص وزن / وزن زائد / سمنة).
  * **الصف الثنائي المتوازن (Two Balanced Columns Layout)**:
    - **الجانب الأيمن**: كارت الشكوى السريرية والفحص الطبي (Physical Examination) بمساحة كتابة مريحة وواضحة.
    - **الجانب الأيسر**: كارت التشخيص الطبي المعتمد (ICD-10):
      * حقل البحث الذكي مع شارات اللغات وزر مسح سريع ومؤشر تحميل.
      * قائمة منسدلة أنيقة (`icd10-autocomplete-dropdown`) تظهر شارة الرمز الطبي، الاسم بالعربي، الاسم بالإنكليزي، وتصنيف المرض.
      * **بطاقة تأكيد التشخيص المختار النشطة (`selected_diagnosis_card`)**: تظهر فور اختيار التشخيص متضمنة الرمز والاسم وزر إلغاء واختيار تشخيص آخر.
      * خيار التشخيص المخصص اليدوي (`other`).
      * مربع ملاحظات وتفاصيل التشخيص الإضافية.
  * **تسهيل التحقق والحفظ المرن**:
    - جعل `diagnosis.description` اختيارياً (`nullable`) مع ملئه تلقائياً باسم التشخيص عند عدم كتابة ملاحظات إضافية، لمنع فشل الـ Validation وتسهيل عمل الطبيب.
- **عرض التشخيص في استعراض الزيارة للقراءة فقط ([resources/views/doctors/visits/show-readonly.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/show-readonly.blade.php))**:
  * إظهار كود التشخيص بشارة ملونة واسم التشخيص العربي والإنجليزي والملاحظات بتنسيق سريري واضح.
- **الاختبارات الآلية الحصينة ([tests/Feature/DoctorIcd10SearchTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DoctorIcd10SearchTest.php))**:
  * اجتياز كامل لـ 6 اختبارات ميزة تغطي البحث بالرمز، العربي، الإنكليزي، الحقل الفارغ، حفظ التشخيص، ورسم المكونات الجديدة (`6 passed, 24 assertions`).

## Session log (2026-10-19 — المرحلة 84: السجل التراكمي لآخر 3 نتائج سابقة لكل فحص مخبري عمودياً أسفل النتيجة الحالية Lab Print Cumulative Previous Results & Delta Check History)

### Done
- **تطوير نموذج الطلبات الطبية ([app/Models/Request.php](file:///c:/wamp64/www/hospital-system/app/Models/Request.php))**:
  * إضافة علاقة `labResults()` تربط الطلب بنتائجه المخبرية في جدول `lab_results`.
  * إضافة دالة `getPreviousLabResultsMap()` لاستخراج آخر 3 نتائج معتمدة سابقة لنفس المريض (`patient_id`) لكل تحليل مخبري قبل الطلب الحالي، مرتبة تنازلياً حسب تاريخ وتوقيت الفحص، مع دعم الاستخراج المزدوج من جدول `lab_results` الفعلي وحقل `requests.result` القديم، ومنع التكرارات لنفس الطلب.
- **عرض النتائج السابقة عمودياً أسفل النتيجة الحالية في جدول الفحوصات ([resources/views/staff/requests/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/print.blade.php))**:
  * الإبقاء على الهيكلية الثلاثية الكلاسيكية المعتمدة للجدول (اسم الفحص | النتيجة ووحدة القياس Conventional Units | الحالة Status) المتطابقة مع تقارير المستشفيات المرجعية.
  * في خانة النتيجة (`test-value-cell`):
    - تظهر النتيجة الحالية في السطر الأول بخط عريض مع وحدة القياس.
    - أسفل النتيجة الحالية مباشرة تظهر النتائج السابقة **مرتبة عمودياً (`.prev-vertical-box`)** يفصلها خط منقط ناعم:
      * كل سطر سابق يحتوي على القيمة مع لون دال على الحالة (أحمر للمرتفع، أصفر للمنخفض، أزرق للطبيعي) متبوعة بتاريخ الفحص المختصر `(d/m/y)`.
    - في حال كان هذا أول فحص للمريض ولا توجد نتائج سابقة، لا يشغل أي مساحة رأسية إضافية ويبقى السطر على مقاسه القياسي.
  * محاذاة عناصر السطر إلى الأعلى (`align-items: flex-start`) لضمان محاذاة اسم التحليل والمدى الطبيعي وشارة الحالة بدقة تامة.
- **الاختبارات الآلية الحصينة**:
  * ترقية اختبار الميزة [tests/Feature/LabPrintGenderAndHeaderTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabPrintGenderAndHeaderTest.php):
    - فحص ظهور الصندوق العمودي للنتائج السابقة `prev-vertical-box` وقيم الفحوصات وتواريخها لمريضة لديها زيارات سابقة لنفس الفحص (`test_lab_print_displays_previous_results_for_same_patient`).
    - فحص عدم ظهور الصندوق العمودي نهائياً عند عدم وجود نتائج سابقة للمريض (`test_lab_print_does_not_show_vertical_box_when_no_previous_results`).
  * اجتياز كامل للاختبارات (`5 passed, 29 assertions`) واجتياز حزمة المختبر كاملة (`46 passed, 207 assertions`).

## Session log (2026-10-19 — المرحلة 83: تصحيح قراءة وتحديد جنس المريض وترقية ترويسة تقرير المختبر لتطابق الطوارئ والأشعة Lab Print Patient Gender Resolution Fix & Unified Emergency/Radiology Header Style)

### Done
- **تصحيح تحديد وقراءة جنس المريض في تقرير المختبر وكافة شاشات النظام**:
  * إضافة دالتي الوصول (`Accessor`) في نموذج المريض [app/Models/Patient.php](file:///c:/wamp64/www/hospital-system/app/Models/Patient.php):
    - `getGenderAttribute()`: قراءة الجنس تلقائياً من جدول المستخدمين المرتبط `user->gender` بدلاً من إرجاع `null` بسبب عدم وجود عمود `gender` في جدول `patients`.
    - `getGenderDisplayAttribute()`: إرجاع التسمية العربية المعربة الموحدة (`ذكر` / `أنثى` / `غير محدد`).
  * تصحيح دالة استخراج الجنس في قالب طباعة المختبر [resources/views/staff/requests/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/print.blade.php):
    - فحص الجنس من ملف المريض واليوزر مباشرة ودعم كافة صيغ الإناث (`female`, `أنثى`, `f`, `انثى`) لطباعة `Female`.
    - معالجة الحالة التي جعلت المريضة هيفاء مدلول لفته (طلب #191) تظهر كـ `Male` لعدم العثور على حقل الجنس في نموذج المريض والرجوع للوضع الافتراضي الخاطئ.
- **ترقية وتوحيد ترويسة تقرير المختبر المعتمد لتطابق تصميم الطوارئ والأشعة**:
  * إعادة بناء وترقية ترويسة تقرير المختبر [resources/views/staff/requests/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/print.blade.php) لتصبح ثلاثية الأعمدة متطابقة تماماً مع استمارة الطوارئ والأشعة:
    - الجانب الأيمن: ترويسة مستشفى الكفاءات الأهلي وقسم المختبر والتحاليل الطبية بالعربي.
    - المنتصف: شعار المستشفى الملكي الأصلي (`images/1.jpg`) مشفر ومضمن بتقنية Base64 لمنع أي مشاكل بالمسارات أو شهادات الأمان وسيرفرات الطباعة.
    - الجانب الأيسر: اسم المستشفى والقسم باللغة الإنكليزية (`Al-Kafa'at Private Hospital - Clinical Laboratory Dept.`).
    - شريط العنوان الكحلي المتدرج الملكي (Document Title Banner) متضمناً كود التقرير والطلب `LAB-#{id}`.
    - توحيد لون إطار الصفحة (Border) إلى الأزرق الملكي المتناسق `#005a9c` مع زوايا إشعاعية خفيفة.
- **الاختبارات الآلية**:
  * بناء وتشغيل اختبار الميزة [tests/Feature/LabPrintGenderAndHeaderTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabPrintGenderAndHeaderTest.php) واجتيازه بنجاح (`3 passed, 17 assertions`) واجتياز حزمة المختبر كاملة (`44 passed, 195 assertions`).

## Session log (2026-10-19 — المرحلة 82: إصلاح ظهور المشاهدات والنتائج وترقية تقرير الأشعة والسونار المعتمد لتصميم الطوارئ مع مربع توقيع فارغ للاختبار Radiology & Ultrasound Findings Display Fix & Emergency-Style Report Upgrade)

### Done
- **إصلاح مشكلة عدم ظهور المشاهدات والنتائج الإشعاعية في التقرير المطبوع**:
  * إضافة كاستينغ `'result' => 'array'` في نموذج الطلبات الطبية [app/Models/Request.php](file:///c:/wamp64/www/hospital-system/app/Models/Request.php) لمنع تحويل المصفوفات تلقائياً ومطابقة بيانات التقرير الطبي.
  * تعديل دالتي `show` و `print` في [app/Http/Controllers/RadiologyStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RadiologyStaffController.php) لتوحيد وقراءة حقول المشاهدات (`findings`)، الانطباع التشخيصي (`impression`)، التوصيات الطبية (`recommendations`)، والصور المرفقة من السجل الفعلي وحمايتها من التجاهل أو إرجاع قيم فارغة.
  * إتاحة زر "طباعة / معاينة التقرير" دائماً في شاشة محطة السونار والأشعة [resources/views/radiology-staff/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/radiology-staff/show.blade.php) لتمكين المعاينة الفورية قبل وبعد الاعتماد.
- **ترقية تصميم استمارة التقرير الطبي للأشعة والسونار المعتمد لتطابق تقرير الطوارئ**:
  * إعادة بناء وتصميم قالب التقرير الطبي [resources/views/radiology/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/radiology/print.blade.php) وفق مقاس A4 الدقيق مع خط Cairo العربي الطبي، الترويسة المزدوجة بشعار المستشفى، إطار البوردر الملكي الداخلي، وشريط التحكم العلوي المتجاوب (طباعة، حفظ PDF، رجوع).
  * بطاقة بيانات المريض والطلب الشاملة (الاسم، العمر، الجنس، الهاتف، رقم الإضبارة، شارة التغطية كاش/ضمان، الطبيب المحول، العيادة، التاريخ، ورقم الـ RAD).
  * بطاقة فحص السونار/الأشعة المطلوبة وصندوق المشاهدات والتقرير السريري المفصل.
  * مربعات التمييز التشخيصي للانطباع الطبي والتوصيات السريرية والصور المرفقة إن وجدت.
  * قسم الاعتماد والتوقيع الرسمي متضمناً:
    - **مربع توقيع فارغ (Empty Signature Line & Stamp Area)** مخصص للاختبار اليدوي والتدقيق كما طلب المستخدم.
    - خاتم مستشفى الكفاءات الأهلي وقسم الأشعة المعتمد.
    - الرمز الإلكتروني المعتمد (QR Code) الذكي المباشر للتحقق.
- **الاختبارات الآلية**:
  * إنشاء واجتياز اختبار الميزة [tests/Feature/RadiologyPrintReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/RadiologyPrintReportTest.php) بنجاح واجتياز حزمة الأشعة كاملة (`24 passed, 115 assertions`).

## Session log (2026-10-19 — المرحلة 81: منظومة التحكم اللحظي والذكي بتشغيل وإطفاء الفحوصات المختبرية ومطابقة الضمان الصحي Lab Tests Instant Activation & Smart Insurance Bulk Controls)

### Done
- **التحكم المفرد اللحظي (Single Quick Toggle AJAX)**:
  * مفتاحان تفاعليان بأسلوب iOS Switch لكل فحص مختبري في شاشة الكتالوج [resources/views/lab-tests/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab-tests/index.blade.php):
    1. **الحالة العامة (`is_active`)**: تفعيل/تعطيل فوري عبر AJAX. عند إطفاء الفحص يختفي فورياً وكلياً من عيادات الأطباء في الاستشارية، الاستعلامات، الطوارئ، العمليات، وقوائم البحث والباقات.
    2. **الضمان الصحي (`is_hi_active`)**: تفعيل/تعطيل الفحص ضمن مظلة الضمان الصحي فورياً عبر AJAX مع شارات تمييز الفحوصات المسعرة بالضمان عن غير المسعرة.
  * تحديث لحظي للشارات البصرية وحالة الصف بدون الحاجة لإعادة تحميل الصفحة نهائياً مع إشعار توست خفيف.
- **التحكم الكلي الذكي والجماعي (Smart Bulk Actions & Floating Bar)**:
  * زر ذكي بنقرة واحدة: `[ ⚡ إطفاء كافة الفحوصات خارج الضمان ]` يتيح إطفاء جميع الفحوصات غير المشمولة بتسعير الضمان (440 فحصاً مسعرة بصفر أو null) عاماً أو عن الضمان فقط عبر نافذة تأكيد منبثقة تفاعلية.
  * زر ذكي بنقرة واحدة: `[ 🛡️ تفعيل كافة المسعرة بالضمان ]` لإعادة تفعيل وتشغيل جميع الفحوصات المعتمدة والمسعرة بالضمان في المحطات دفعة واحدة.
  * مربعات اختيار جماعية (`Checkboxes`) مع شريط عائم سفلي ذكي يظهر عند تحديد فحص أو أكثر للتحكم الجماعي (تفعيل عام، تعطيل عام، تفعيل للضمان، إيقاف عن الضمان).
- **الواجهة والإحصائيات والفلاتر المتقدمة**:
  * 4 بطاقات إحصائيات سريعة أعلى الصفحة (إجمالي الفحوصات، الفحوصات النشطة عاماً، المعطلة، والمشمولة بالضمان).
  * فلترة جديدة دقيقة للفحوصات: (مشمولة بالضمان فقط / خارج الضمان فقط / الكل) مع تحديد عدد النتائج بالصفحة (25 إلى 500).
- **تطوير المتحكم والمسارات**:
  * دوال `quickToggle`, `bulkToggleStatus`, `bulkDisableNonInsurance` في [app/Http/Controllers/LabTestController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabTestController.php).
  * تسجيل المسارات في [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php) تحت بادئة `lab-tests`.
- **الاختبارات الآلية الحصينة**:
  * بناء وتشغيل [tests/Feature/LabTestActivationAndBulkControlTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabTestActivationAndBulkControlTest.php) بنجاح كامل (`5 passed, 20 assertions`) واجتياز حزمة المختبر كاملة (`41 passed, 179 assertions`).

## Session log (2026-10-18 — المرحلة 80: مشاركة نتائج التحاليل المخبرية عبر واتساب وبوابة التقرير الطبي المعتمد الآمنة Lab WhatsApp Result Sharing & Secure Public Report Portal)

### Done
- **تطوير نموذج الطلبات الطبية ([app/Models/Request.php](file:///c:/wamp64/www/hospital-system/app/Models/Request.php))**:
  * دالة `getPatientPhone()` لمعالجة وتوحيد أرقام الهواتف العراقية للنمط الدولي (`964...`) تلقائياً.
  * دالة `getPublicReportToken()` لتوليد رمز توقيع رقمي HMAC sha256 محكم ومشفر لحماية تقرير المريض من التخمين والتلاعب.
  * دالة `getPublicReportUrl()` لإنشاء رابط استعراض التقرير العام الآمن.
  * دالة `getWhatsAppShareUrl()` لتوليد رابط `https://wa.me/{phone}?text=...` محمل برسالة رسمية تحتوي على اسم المستشفى، نوع التحليل، ورابط مباشر لاستعراض التقرير المعتمد.
- **متحكم وبوابة التقرير الطبي العام الآمن ([app/Http/Controllers/LabPublicReportController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabPublicReportController.php))**:
  * مسار مستقل عام `/lab/results/view/{request}` يتيح للمريض فتح التقرير الطبي المعتمد فوراً من الهاتف عبر الرابط المشفر دون الحاجة لتسجيل دخول أو حساب بالمستشفى.
  * حماية صارمة لمنع الوصول غير المصرح به عند عدم تطابق رمز التحقق الرقمي HMAC.
- **ترقية صفحة التقرير الطبي ([resources/views/staff/requests/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/print.blade.php))**:
  * إضافة تنسيقات متجاوبة للهواتف الذكية (`@media screen and (max-width: 768px)`).
  * شريط علوي أنيق للمريض يحتوي على زر مباشر `[ 📥 تحميل / حفظ كـ PDF ]` يستدعي حفظ الـ PDF تلقائياً عند النقر.
  * زر في شريط الموظف `[ 💬 إرسال واتساب للمريض ]` لفتح محادثة واتساب بنقرة واحدة من صفحة الطباعة.
- **إضافة أزرار واتساب التفاعلية في شاشات المختبر**:
  * جدول طلبات المختبر ([resources/views/lab/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/index.blade.php)): إضافة زر واتساب أخضر أنيق في عمود "الإجراءات" للفحوصات المكتملة.
- **منظومة التحكم بالأدوار والصلاحيات (Role-Based WhatsApp Access Control)**:
  * إنشاء قسم وبطاقة تشغيلية مستقلة في شاشة الأدوار والصلاحيات: **`خدمات ومراسلات الواتساب (WhatsApp)`** بأيقونة الواتساب الخضراء وتصنيف خاص `whatsapp`.
  * إضافة صلاحية مخصصة `send lab whatsapp` (إرسال ومشاركة نتائج التحاليل للمرضى عبر واتساب) ضمن هذا القسم المنفصل في [app/Http/Controllers/RoleManagementController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RoleManagementController.php) و [database/seeders/RolesAndPermissionsSeeder.php](file:///c:/wamp64/www/hospital-system/database/seeders/RolesAndPermissionsSeeder.php).
  * إنشاء وتشغيل مايجريشن [database/migrations/2026_10_18_230000_add_send_lab_whatsapp_permission.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_18_230000_add_send_lab_whatsapp_permission.php) وتعيينها لأدوار `admin` و `lab_staff`.
  * حماية جميع أزرار وروابط الواتساب في الواجهات بـ `@can('send lab whatsapp')`: تظهر الصلاحية في شاشة تعديل الأدوار `/roles/{role}/edit` كخيار تفعيل/إطفاء ضمن بطاقة الواتساب المستقلة، وعند إلغاء تحديدها يختفي زر الواتساب فوراً من كافة شاشات المختبر لهذا الدور.
- **الاختبارات الآلية الحصينة ([tests/Feature/LabWhatsAppShareAndPublicReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabWhatsAppShareAndPublicReportTest.php))**:
  * اجتياز كامل لـ 7 اختبارات ميزة تغطي تهيئة الرقم الدولي، توليد رابط الواتساب، الدخول الآمن للتقرير بالرمز، منع الدخول بدون رمز، وظهور الأزرار عند منح الصلاحية واختفائها تماماً عند سحب الصلاحية (`7 passed, 25 assertions`) واجتياز حزمة المختبر الكاملة (`36 passed, 159 assertions`).
- **خطة التوسع المستقبلية لمنظومة الواتساب (WhatsApp Expansion Roadmap)**:
  * توثيق مفاتيح التحكم السبعة في الذاكرة المركزية `hospital-memory.md` لتفعيلها تباعاً حسب أقسام المستشفى: (المختبر ✅ منجز، السونار، الرنين، المفراس، الإيكو، الأشعة السينية، والوصفات الطبية) ضمن بطاقة "خدمات ومراسلات الواتساب" الموحدة في شاشة الصلاحيات.

## Session log (2026-10-18 — المرحلة 79: إكمال نواقص بيانات المرضى السريع وقفل حجز الضمان الصحي عند وجود نواقص بملف المريض وترقية واجهة الاختيار للبطاقات التفاعلية Patient Incomplete Profile Completion, Insurance Booking Lock & Interactive Option Cards)

### Done
- **إظهار رقم هاتف المريض في كارت شاشة المختبر ([resources/views/lab/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/show.blade.php))**:
  * إضافة رقم هاتف المريض إلى بطاقة "بيانات المريض والطلب" مع أيقونة اتصال ورابط مباشر `tel:`.
  * بناء اختبار ميزة: [tests/Feature/LabShowPatientPhoneTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabShowPatientPhoneTest.php) (`1 passed, 4 assertions`).
- **إضافة حقل تاريخ الميلاد وإظهار العمر التلقائي في صف الإكمال السريع ([resources/views/patients/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/patients/index.blade.php) و [app/Http/Controllers/PatientController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/PatientController.php))**:
  * إضافة حقل `date_of_birth` في دراور الإكمال السريع السلس (`btn-quick-complete`) مع 6 أعمدة متناسقة (`col-xl-2`).
  * استقبال تاريخ الميلاد في `quickUpdate` وتحديث `users.date_of_birth` واحتساب العمر التلقائي وإعادته في استجابة الـ AJAX.
  * تحديث شارة العمر في سطر جدول المريض لحظياً دون إعادة تحميل الصفحة، وحل مشكلة 7,919 مريض مسجلين بدون مواليد بسبب التسجيل السريع القديم.
  * اجتياز كامل للاختبارات: [tests/Feature/PatientCompletionAndQuickUpdateTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PatientCompletionAndQuickUpdateTest.php) (`4 passed, 18 assertions`).
- **منظومة قفل الحجز تحت مظلة الضمان الصحي عند وجود أي نقص سريري أو إداري**:
  * بناء دالتي `getMissingInsuranceFields()` و `isInsuranceDataComplete()` في نموذج [app/Models/Patient.php](file:///c:/wamp64/www/hospital-system/app/Models/Patient.php) للتحقق من الحقول الـ 5 الإلزامية: (رقم بطاقة/دفتر الضمان، البطاقة الموحدة/الرقم الوطني، رقم الهاتف، الجنس، تاريخ الميلاد).
  * حماية صارمة في الواجهة الخلفية [app/Http/Controllers/InquiryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/InquiryController.php) لمنع تمرير أي حجز ضمان مع نواقص وإرجاع تنبيه إداري يوضح الحقول المفقودة بدقة.
  * ترقية واجهة اختيار التغطية في [resources/views/inquiry/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/inquiry/create.blade.php) إلى **بطاقتين تفاعليتين لمسيتين (Selectable Visual Cards)** بدلاً من نقاط الراديو أو مربعات الاختيار التقليدية:
    - **بطاقة الضمان 🛡️**: تظهر بالأخضر عند التفعيل، وتقفل تلقائياً باللون الأحمر مع شارة `🔒 مقفل (نواقص بملف الضمان)` عند وجود نقص.
    - **بطاقة الحجز النقدي (كاش) 💵**: تفعل تلقائياً كخيار بديل مباشر للمتابعة الفورية للمريض.
    - إضافة دالة الجافاسكربت `selectInsuranceMode()` للتبديل اللحظي عند النقر على أي جزء من البطاقة.
  * تصميم صندوق تنبيه تحذيري بارز ومكتوب بعبارات واضحة جداً يوضح للموظف الحقول المفقودة كشارات حمراء وخطي العمل المتاحين أمامه فوراً.
  * منظومة التنبيه الرقمي العاجل لشعبة الضمان والتسجيل:
    - إضافة زر تفاعلي `[ ⚡ إرسال تنبيه عاجل لمكتب الضمان والتسجيل ]` داخل صندوق التحذير في [resources/views/inquiry/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/inquiry/create.blade.php).
    - مسار ومتحكم `InquiryController@notifyInsuranceIncomplete` ينشئ إشعاراً فورياً [app/Notifications/InsuranceIncompleteAlertNotification.php](file:///c:/wamp64/www/hospital-system/app/Notifications/InsuranceIncompleteAlertNotification.php) في قاعدة البيانات وبث Pusher/Echo لموظفي الضمان والتسجيل والمدراء (`thaman`, `receptionist`, `admin`).
    - الإشعار يحتوي على اسم المريض، النواقص الدقيقة، واسم موظف الاستعلامات المرسل، وبيانات المريض لفتح الدراور مباشرة.
    - بانر تنبيه تفاعلي فوري أعلى جدول المرضى (`#insuranceAlertsContainer`) يظهر لموظف الضمان فوراً بدون الحاجة لرفرش الشاشة نهائياً:
      * دعم مزدوج: بث لحظي عبر Pusher/Echo + استطلاع فوري خفيف عبر AJAX Polling كل 4 ثوانٍ (`patients.insurance-alerts-poll`).
      * نغمة صوتية تنبيهية فورية (Web Audio API) وحركة انسيابية عند وصول المريض.
      * زر `[ ⚡ إكمال النواقص فوراً ]` يفتح دراور إكمال المريض مباشرة (ويدرج المريض بأعلى الجدول حتى لو كان بصفحة أخرى أو مسجلاً حديثاً).
      * تعليم الإشعار كمقروء تلقائياً وإخفاء الكرت عند الحفظ.
  * توجيه يوزر الضمان (`thaman`) مباشرة إلى شاشة المرضى وإخفاء لوحة التحكم الرئيسية:
    - تعديل `LoginController@redirectTo`: توجيه مستخدمي دور `thaman` تلقائياً بعد تسجيل الدخول مباشرة إلى `/patients`.
    - تعديل `DashboardController@index`: إعادة توجيه أي محاولة دخول لـ `/dashboard` من قبل يوزر الضمان فوراً إلى `patients.index`.
    - تعديل المسار الجذري `/`: تحويل يوزر الضمان المسجل دخوله إلى `patients.index`.
    - تعديل الشريط الجانبي `layouts/app.blade.php`: إخفاء زر "الرئيسية" تماماً ليوزر الضمان واستبداله برابط "سجل وملفات المرضى" المباشر.
  * حماية JavaScript في حدث تقديم الاستمارة `submit` وعند تأكيد الحجز الإضافي لمنع أي محاولة تجاوز.
  * بناء وتشغيل الاختبارات الآلية: [tests/Feature/InquiryInsuranceDataIncompleteTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/InquiryInsuranceDataIncompleteTest.php) (`5 passed, 26 assertions`) واختبار توجيه يوزر الضمان والتنبيهات الفورية [tests/Feature/ThamanLoginRedirectTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ThamanLoginRedirectTest.php) (`7 passed, 19 assertions`).

## Session log (2026-10-18 — المرحلة 78: استيراد وتحديث كتالوج المختبر الشامل لـ 865 فحصاً وفك قيد تفرد الكود الطبي ومطابقة هوستنجر السحابية Lab Catalog Full 865 Import, Code Unique Constraint Removal & Hostinger Sync)

### Done
- **النسخ الاحتياطي الحصين**:
  * أخذ نسخة احتياطية كاملة لجدول `lab_tests` و `lab_test_sub_tests` للوكل في `storage/app/backups/lab_tests_backup_2026_10_18_190644.sql`.
  * أخذ نسخة احتياطية كاملة لهوستنجر السحابية في `storage/app/backups/hostinger_lab_tests_backup_2026_10_18_191208.sql`.
- **إلغاء قيد تفرد الكود الطبي واستيعاب الفحوصات المكررة سريرياً**:
  * بناء وتشغيل مايجريشن [database/migrations/2026_10_18_192000_drop_unique_code_from_lab_tests_table.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_18_192000_drop_unique_code_from_lab_tests_table.php) لإزالة `lab_tests_code_unique` وتحويله إلى `lab_tests_code_index`.
  * تطبيق نفس الفهرس على قاعدة بيانات هوستنجر بنجاح.
- **تطوير محرك الاستيراد والتزامن الشامل ([app/Imports/LabTestsImport.php](file:///c:/wamp64/www/hospital-system/app/Imports/LabTestsImport.php))**:
  * دعم إدراج الفحوصات الجديدة تلقائياً (`$createdCount`) عند عدم وجودها في الجدول.
  * دعم كشف وتعيين القسم الرئيسي `main_category` والتصنيف الفرعي `subcategory`.
  * تحصين السجلات والطلبات السابقة من الحذف.
- **مزامنة واستيراد الـ 865 فحصاً كاملاً**:
  * استيراد الـ 865 فحصاً وتحديث الأسعار ومسميات الفحوصات وإدراج الـ 234 فحصاً الجديد على قاعدة البيانات المحلية (`hospital_pho`).
  * استيراد وتحديث نفس الـ 865 فحصاً على قاعدة بيانات هوستنجر السحابية (`u175868711_her2020`).
  * تطابق تام لعدد الفحوصات: **865 فحصاً محلياً = 865 فحصاً سحابياً**.
- **الاختبارات الآلية**:
  * اجتياز كامل لحزمة اختبارات أسعار وتعديل التحاليل (`7 passed, 28 assertions`).

## Session log (2026-10-18 — المرحلة 77: تصحيح وتوحيد مسار زر العودة للكاشير في صفحة الوصل واستمارة دفع الطلبات الطبية Cashier Receipt & Request Payment Return Link Correction)

### Done
- **تصحيح مسار زر العودة للكاشير في صفحة إيصال القبض ([resources/views/cashier/receipt.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt.blade.php))**:
  * إزالة الشرط القديم المقيد لدور `consultation_receptionist` الذي كان يعيد الموظف إلى واجهة توفر الأطباء (`consultant-availability.index`).
  * ربط زر "العودة للكاشير" مباشرة بمسار الكاشير `route('cashier.index')` لضمان استمرارية عمل الموظف داخل واجهة الكاشير ومتابعة تسديد المرضى.
- **تحديث أزرار العودة في استمارة دفع الطلبات الطبية ([resources/views/cashier/request-payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/request-payment-form.blade.php))**:
  * توحيد روابط أزرار الرجوع العلوية والسفلية لتوجه إلى `route('cashier.index')` بدلاً من الخروج إلى شاشة توفر الأطباء.
- **الاختبارات الآلية الحصينة ([tests/Feature/CashierReceiptReturnLinkTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/CashierReceiptReturnLinkTest.php))**:
  * بناء اختبار ميزة للتأكد من ظهور رابط `cashier.index` لموظف استعلامات الاستشارية وعدم ظهور رابط `consultant-availability.index`: `1 passed (4 assertions)`.
  * نجاح كامل لحزمة اختبارات الصلاحيات والكاشير: `11 passed (179 assertions)`.

## Session log (2026-10-18 — المرحلة 76: إصلاح نمط إدخال نتائج التحاليل المخبرية، وحل مشكلة تحول الفحوصات المركبة إلى قيم مفردة، وتكامل واجهة تعديل التحليل مع نمط الإدخال Lab Subtests Result Entry Fix & Edit Form Integration)

### Done
- **حل مشكلة تحول الفحوصات المركبة (Sub-Tests) إلى قيم مفردة في المختبر ([resources/views/lab/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/show.blade.php) و [resources/views/staff/requests/show-lab.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/staff/requests/show-lab.blade.php))**:
  * ترقية استعلام وخريطة `$labTestMap` للبحث عن الفحوصات بالاسم الإنجليزي الكامل، وبالكود المختصر، وبالاختصارات الشائعة (مثل `CBC` ➔ `CBC_3DIFF`).
  * القضاء التام على مشكلة سقوط الفحص إلى خانة إدخال مفردة (`name="test_results[CODE][value]"`) إذا طُلب بالكود أو المختصر.
  * حماية دوال Blade المساعدة (`getTestIcon`, `getTestUnit`, `normalizeTestIds`, `normalizeTestsField`, `buildSelectedLabTests`) بـ `if (!function_exists(...))` لمنع أخطاء إعادة التصريح (Fatal Error: Cannot redeclare) عند استدعاء العرض أكثر من مرة.
- **إصلاح مسارات AJAX في شاشة الإعدادات العامة ([resources/views/lab-tests/entry_settings.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab-tests/entry_settings.blade.php))**:
  * تحويل مسارات التحديث الفردي والجماعي إلى روابط نسبية `route('lab-tests.entry-settings.update', [], false)` لتفادي تضارب المنفذ (Port 8000 vs 80) وسياسات CORS، وضمان حفظ التغيير لحظياً على أي بيئة عمل.
- **تكامل واجهة تعديل الفحص المختبري مع نمط الإدخال ([resources/views/lab-tests/edit.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab-tests/edit.blade.php) و [app/Http/Controllers/LabTestController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabTestController.php))**:
  * إضافة قسم تفاعلي كامل لاختيار نمط الإدخال (قيمة مباشرة، قيم فرعية، مرفق جهاز، كلاهما مرن) مباشرة من شاشة تعديل التحليل `/lab-tests/{id}/edit`.
  * إظهار عداد القيم الفرعية المسجلة حالياً مع زر وصول سريع لإدارة المعايير الفرعية `[ إدارة القيم الفرعية ]`.
  * تحديث `LabTestController@update` و `@store` لاستقبال وحفظ حقل `result_entry_type`.
- **مزامنة نمط التحاليل الـ 6 في قاعدتي البيانات**:
  * تحويل نمط التحاليل الـ 6 (ABG, CBC_3DIFF, CBC_5DIFF, GSE, GUE, SEMEN) في الداتابيز المحلية (`mysql`) وهوستنجر (`remote_hostinger`) إلى `sub_tests`.
- **الاختبارات الآلية**:
  * بناء وتشغيل [tests/Feature/LabSubtestsResolutionAndEditTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabSubtestsResolutionAndEditTest.php): `2 passed (6 assertions)`.
  * اجتياز كامل حزمة اختبارات المختبر: `16 passed (60 assertions)`.

## Session log (2026-10-18 — المرحلة 75: إعادة هندسة وتصميم استمارة تقرير الطوارئ الموحد ليكون في ورقة واحدة على وجهين مطابقة للتصميم القياسي Standard Duplex Two-Page Emergency Medical Report Form)

### Done
- **إعادة هندسة وتصميم التقرير الطبي للطوارئ ([resources/views/emergency/medical-report.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/medical-report.blade.php))**:
  * تحويل التقرير إلى **استمارة رسمية موحدة على ورقة واحدة بوجهين (Duplex Front & Back)** مقاس A4 مطابق تماماً لنموذج مستشفى الكفاءات الأهلي.
  * **الوجه الأول (Page 1 of 2)**:
    - ترويسة المستشفى ثنائية اللغة وشعار المستشفى وشريط العنوان الكحلي الداكن مع كبسولة رقم التقرير وكبسولة شارة النسخة.
    - جدول البيانات الشخصية والدخول (10 خانات موزعة على صفين: طريقة الوصول، رقم المراجعة، رقم ملف المريض، الجنس/العمر، التاريخ والوقت، الطبيب المناوب، الشكوى، التصنيف الفرزي Triage، الجهة المحولة، العنوان ورقم الهاتف).
    - شريط العلامات الحيوية عند الاستقبال بـ 6 خلايا: (مستوى الوعي GCS، تشبع الأكسجين، الحرارة، التنفس، النبض، ضغط الدم).
    - صندوق الشكوى الرئيسية، وصندوق تاريخ المرض الحالي (HPI).
    - صندوق الفحوصات المختبرية والشعاعية، وصندوق العلاج ومحاليل الطوارئ.
    - جدول التشخيص السريري والخدمات العلاجية وتوقيتها واسم الطبيب.
    - ترقيم وتذييل الصفحة: `Page 1 of 2`.
  * **الوجه الثاني (Page 2 of 2)**:
    - تكرار الترويسة وشريط العنوان للرسمية والتوثيق القانوني عند تصوير أو فصل الوجه الخلفي.
    - صندوق الملاحظات الطبية - العدلية (Medico-Legal Notes) ⚖️، وصندوق الملاحظات الإضافية 📄.
    - كتلة التوثيق الثلاثية الأفقية: (صندوق توقيع الطبيب المفرغ بخلفية شفافة 100% مع التاريخ والاسم + الختم الدائري الرسمي لقسم الطوارئ + الرمز الإلكتروني QR Code المربوط بصفحة التحقق الرقمي العام).
    - صندوق القرار الطبي النهائي عند الخروج وتوصيات المتابعة (التوصيات والمتابعة، الوجهة النهائية، الحالة عند الخروج ببادج كهرماني ملون).
    - ترقيم وتذييل الصفحة: `Page 2 of 2`.
  * ضبط خصائص الطباعة `@page { size: A4 portrait; margin: 0; }` ومعايرة ارتفاع الصفحة `296mm` مع `page-break-after: always` لسحب الطابعة للورقة تلقائياً على الوجهين بدون أي انزلاق لصفحة ثالثة.
- **الاختبارات الآلية الحصينة**:
  * [tests/Feature/EmergencyMedicalReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyMedicalReportTest.php): `3 passed (19 assertions)`.
  * اجتياز كامل حزمة اختبارات الطوارئ: `31 passed (160 assertions)`.

## Session log (2026-10-18 — المرحلة 74: منظومة استمارة تقرير الطوارئ الموحد - الخطوة الأولى: الحقول السريرية والعدلية وواجهات الإدخال Standard Emergency Medical Report Fields & Workstation Input Integration)

### Done
- **إضافة حقول استمارة تقرير الطوارئ الرسمي الموحد في قاعدة البيانات ([app/Models/Emergency.php](file:///c:/wamp64/www/hospital-system/app/Models/Emergency.php) و [database/migrations/2026_10_18_090000_add_standard_report_fields_to_emergencies_table.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_18_090000_add_standard_report_fields_to_emergencies_table.php))**:
  * إضافة أعمدة: `arrival_mode` (طريقة الوصول: إسعاف المستشفى، وصول شخصي، سيارة خاصة، إسعاف خارجي)، `referred_from` (الجهة المحولة)، `gcs` (مستوى الوعي Glasgow Coma Scale مع القيمة الافتراضية `'15/15'`)، `hpi` (تاريخ المرض الحالي History of Present Illness)، `medico_legal_notes` (الملاحظات الطبية - العدلية ⚖️)، `discharge_destination` (الوجهة النهائية عند الخروج)، و `discharge_recommendations` (توصيات وخطة المتابعة).
  * تحديث مصفوفة `$fillable` في نموذج `Emergency` لدعم الحقول الجديدة بالكامل.
- **تحديث المتحكم ودورة البيانات ([app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php))**:
  * في `store`: استقبال وتخزين طريقة الوصول `arrival_mode` والجهة المحولة `referred_from` ومستوى الوعي `gcs`.
  * في `updateVitals`: استقبال وحفظ مستوى الوعي `gcs` وتحديثه لحظياً مع القراءات الحيوية.
  * في `updateMedical`: استقبال وحفظ الشكوى الرئيسية `symptoms`، وتاريخ المرض الحالي `hpi`، والتشخيص الطبي `diagnosis`، والملاحظات الطبية العدلية `medico_legal_notes`، والملاحظات الإضافية `notes`.
  * في `discharge`: اعتماد وتخزين الوجهة النهائية وتوصيات الخروج تلقائياً وفق نوع الخروج أو من الإدخال المباشر.
- **تحديث واجهات الاستقبال ومحطة كشف وفحص الطوارئ**:
  * [resources/views/emergency/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/create.blade.php): إضافة قائمة منسدلة أنيقة لطريقة الوصول مع أيقونات معبرة، وحقل الجهة المحولة.
  * [resources/views/emergency/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/show.blade.php):
    - إضافة حقل مستوى الوعي `GCS` في تبويب العلامات الحيوية مع زر ضبط `طبيعي (15/15)`.
    - إضافة حقول الشكوى الرئيسية، تاريخ المرض الحالي (HPI)، التشخيص، الملاحظات الطبية العدلية ⚖️، والملاحظات الإضافية في تبويب التشخيص والخدمات.
- **الاختبارات الآلية المكتملة**:
  * بناء وتشغيل [tests/Feature/EmergencyStandardReportDataTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyStandardReportDataTest.php): `4 passed (15 assertions)`.
  * اجتياز كامل حزمة اختبارات الطوارئ: `31 passed (160 assertions)`.

## Session log (2026-10-17 — المرحلة 73: نظام إدارة نمط إدخال نتائج التحاليل المخبرية للمدير وحصر حالات الطوارئ المباشرة بالسعر النقدي 100% كاش Lab Test Result Entry Mode Management & Emergency Direct Cash Enforcement)

### Done
- **نظام إدارة نمط إدخال نتائج التحاليل المخبرية للمدير ([app/Http/Controllers/LabTestController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabTestController.php) و [app/Models/LabTest.php](file:///c:/wamp64/www/hospital-system/app/Models/LabTest.php) و [resources/views/lab-tests/entry_settings.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab-tests/entry_settings.blade.php))**:
  * إضافة عمود `result_entry_type` من نوع ENUM بقيم `['value', 'components', 'attachment', 'both']` في جدول `lab_tests` عبر مايجريشن `2026_10_17_174000_add_result_entry_type_to_lab_tests_table.php` مع القيمة الافتراضية `'value'`.
  * إضافة توابع وثوابت نموذج التحليل المخبري: `ENTRY_VALUE`, `ENTRY_COMPONENTS`, `ENTRY_ATTACHMENT`, `ENTRY_BOTH` ودوال الفحص `allowsValue()`, `allowsComponents()`, `allowsAttachment()`.
  * بناء شاشة إدارية حصرية للمدير `/lab-tests/entry-settings` بأربعة خيارات Checkbox تفاعلية لكل تحليل لحفظ التفضيلات لحظياً عبر AJAX بنقرة واحدة، ودعم التحديث الجماعي.
  * تكييف واجهة محطة فحص المختبر `resources/views/lab/show.blade.php` ديناميكياً لإظهار حقول القيمة أو القيم الفرعية أو رفع المرفق أو الاثنين معاً بناءً على إعدادات المدير.
  * بناء وتشغيل الاختبار الآلي: [tests/Feature/LabTestResultEntryTypeTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabTestResultEntryTypeTest.php): `6 passed (28 assertions)`.
- **حصر حالات وخدمات الطوارئ المباشرة بالسعر النقدي 100% وإلغاء توريث الضمان ([app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php) و [app/Models/Emergency.php](file:///c:/wamp64/www/hospital-system/app/Models/Emergency.php) و [app/Http/Controllers/InquiryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/InquiryController.php) و [resources/views/cashier/emergency-payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/emergency-payment-form.blade.php))**:
  * إلغاء التوريث التلقائي والبديل الاحتياطي للضمان من حساب المريض العام (`patient->insurance_type === 'hi'`) في دوال احتساب فواتير الطوارئ ومستحقاتها (`upsertUnifiedEmergencyPayment`, `Emergency::getUnpaidAmount`, و `emergency-payment-form.blade.php`).
  * حصر تغطية الضمان في الطوارئ فقط على الحالات المحجوزة صراحة عبر بوت الضمان أو المحالة بموافقة مسبقة.
  * جعل الخيار الافتراضي في استقبال الطوارئ والاستعلامات هو الحجز النقدي (`apply_insurance = 0` / كاش عادي) حتى لو كان للمريض بطاقة ضمان مسجلة.
  * تصحيح وتثبيت حالة الطوارئ #5 وسند الدفع #21 للمريض (منير سمير يوسف) لتصبح نقداً بنسبة 100% بمبلغ 115,000 د.ع بدون أي خصم للضمان (`insurance_share = 0`).
  * اجتياز كامل للاختبارات الآلية: [tests/Feature/EmergencyFinancialClearanceAndCancellationTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyFinancialClearanceAndCancellationTest.php): `6 passed (32 assertions)`.

## Session log (2026-10-17 — المرحلة 72: إبراء الذمة المالية وقفل إجراءات الطوارئ وإلغاء الحالات مع استرجاع الكاشير والخزينة Emergency Financial Clearance Locks, Cancellation & Cashier Refund Integration)

### Done
- **إبراء الذمة المالية وقفل الإجراءات والتقرير الطبي ([app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php) و [resources/views/emergency/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/show.blade.php) و [resources/views/emergency/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/index.blade.php))**:
  * ربط حالة إبراء الذمة المالية `$unpaidAmount` بحساب المتبقي غير المسدد من خدمات وفحوصات الطوارئ.
  * تعطيل زر التقرير الطبي الرسمي `[ 📄 التقرير الطبي للطوارئ ]` وإظهار أيقونة القفل مع تنبيه يوجه بضرورة تسديد الرسوم في الكاشير أولاً.
  * تعطيل قائمة الإجراءات والتحويل والتخريج `[ إجراءات وتحويل وتخريج الحالة 🔒 ]` بالكامل (تحويل للعمليات، تحويل للرقود، تحويل للاستشارية، خروج متعافي، خروج على مسؤوليته) حتى يتم السداد بالكامل.
  * وضع حماية برمجية Backend صارمة في دوال `medicalReport()`, `discharge()`, `transferToSurgery()`, `transferToAdmission()` تمنع أي تجاوز.
- **إلغاء حالات الطوارئ النشطة مع الحصانة التشغيلية والسريرية ([app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php))**:
  * بناء دالة `cancel(Request $request, Emergency $emergency)` ومسار `POST /emergency/{emergency}/cancel`.
  * الحصانة التشغيلية: منع إلغاء الحالات المخرجة مسبقاً (`status = 'discharged'`).
  * الحالات غير المسددة: تحويل الحالة إلى `cancelled` وحذف وصولات الدفع المعلقة غير المدفوعة وإلغاء مواعيد الاستشارات غير المكتملة.
  * الحالات المسددة مسبقاً: تحويلها إلى `cancelled` ووسمها للاسترجاع وتوجيه الموظف والمريض إلى الكاشير لاستلام المبلغ.
- **التكامل التلقائي مع الكاشير والخزينة المركزية ([app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php) و [resources/views/cashier/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/index.blade.php))**:
  * إضافة مسار `POST /cashier/emergency-refund/{emergency}` باسم `cashier.emergency.refund.process`.
  * إدراج حالات الطوارئ الملغاة في طابور `مستحقات الاسترجاع (Refund)` في الكاشير بشارة حمراء مميزة.
  * احتساب صافي المبلغ المسترد بدقة وإنشاء قيد دفعة سالبة في `payments` ينعكس تلقائياً في الخزينة المركزية كحركة منصرفات (`outflow / refund`).
  * حل مشكلة عدم عمل زر الصرف: التخلص من تضارب علامات التنصيص المزدوجة الناتجة عن `@json` داخل `onclick`، واستبدالها بنظام `data-*` attributes واستماع مفوض `$(document).on('click', '.btn-open-refund')` يعمل بحصانة تامة ضد التحديث الدوري.
  * إضافة ترحيل قاعدة بيانات `2026_10_17_155500_add_refunded_to_emergencies_payment_status_enum.php` لإضافة `'refunded'` لحقل `payment_status` في جدول `emergencies`.
  * طباعة سندات الاسترجاع الرسمية والحرارية.
- **عزل صلاحيات وسجلات الطوارئ وإخفاء الحالات الملغاة ([app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php) و [resources/views/emergency/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/index.blade.php))**:
  * قصر صلاحية جدول الطوارئ على المدير وأطباء الطوارئ فقط مع حظر أي أدوار غير مصرح لها (`abort(403)`).
  * حصر استعراض الحالات للأطباء على مرضاهم فقط (`doctor_id` أو `consultant_doctor_id`) مع احتساب الإحصائيات لمرضاهم ومنع الطبيب من فتح حالات الأطباء الآخرين.
  * إخفاء الحالات الملغاة تماماً من تبويب "حالات اليوم" ومن تبويب "النشطة حالياً" وحصرها في "السجل الكامل" فقط كحالة ملغاة ومغلقة وغير قابلة للفتح نهائياً.
- **الاختبارات الآلية (Automated Tests)**:
  * بناء وتشغيل [tests/Feature/EmergencyFinancialClearanceAndCancellationTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyFinancialClearanceAndCancellationTest.php): `6 passed (32 assertions)`.

## Session log (2026-10-17 — المرحلة 71: دورة استرجاع وصرف مبالغ الفحوصات الملغاة في الكاشير وإصدار السندات Cashier Refund Cycle for Cancelled Medical Requests & Treasury Outflows)

### Done
- **مسار واستعلامات استرجاع الفحوصات في الكاشير ([app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php) و [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
  * إضافة مسار `POST /cashier/request-refund/{request}` باسم `cashier.request.refund.process` محمي بصلاحية `process medical requests payments`.
  * إضافة استعلام `$pendingRefundRequests` في `CashierController::index()` لجلب كافة الطلبات الطبية الملغاة (`status = 'cancelled'`) التي لديها مدفوعات مسددة سابقة موجبة (`amount > 0`) ولم يتم استرجاعها بعد (`whereDoesntHave payments where amount < 0`).
  * بناء دالة `processRequestRefund(HttpRequest $httpRequest, MedicalRequest $request)`:
    - التحقق الصارم من حالة الطلب الملغي ووجود مدفوعات سابقة قابلة للاسترجاع.
    - احتساب صافي المبلغ المستحق بدقة (`$netRefundable`).
    - إنشاء سجل دفعة استرجاع سالبة (`Payment::create(['amount' => -$netRefundable, ...])`) برقم إيصال رسمي فريد (`REF-YYYYMMDD-XXXX`).
    - التكامل التلقائي مع الخزينة عبر `PaymentObserver`: قيد حركة منصرفات (`voucher_type = 'outflow'`, `category = 'refund'`) فورياً في الخزينة المركزية، وتسجيل حركة تدقيق رسمية في `UserActivityLogger`.
    - تحديث مصفوفة بيانات الطلب `$details` بحالة `refund_status => 'refunded'` وتسجيل المبلغ وتاريخ وموظف الصرف، ثم التوجيه لصفحة السند الرسمي مع زر الطباعة.
- **واجهة الكاشير وطابور الاسترجاع والمودال ([resources/views/cashier/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/index.blade.php))**:
  * إضافة شريط تنبيهي بارز في أعلى المعاملات عند وجود مبالغ مستحقة للاسترجاع (`$pendingRefundCount > 0`) ينبه موظف الكاشير بوجود مرضى ألغوا فحوصاتهم ويستحقون الصرف.
  * إضافة تبويب مخصص في شريط التبويبات باسم `مستحقات الاسترجاع (Refund)` مع شارة عداد حمراء حية.
  * عرض جدول تفصيلي للمسترجعات: رقم الطلب، القسم (مختبر / باقة / أشعة)، اسم المريض وهاتفه، رقم الوصل الأصلي المسدد، سبب وتاريخ الإلغاء واسم الموظف الملغي، وصافي المبلغ المستحق إرجاعه بالدينار العراقي.
  * بناء نافذة منبثقة تفاعلية (Modal `#medicalRefundModal`) لتأكيد الصرف، واختيار طريقة الإرجاع (نقداً / بطاقة)، وإدخال أي ملاحظات إدارية.
- **ترقية سندات القبض الحرارية والرسمية للاسترجاع ([resources/views/cashier/receipt.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt.blade.php) و [resources/views/cashier/receipt-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt-print.blade.php))**:
  * دعم السند الرسمي: التحول التلقائي عند كون الدفعة سالبة إلى عنوان `سند استرجاع مالي رسمي (Refund Voucher)`، وشارة حالة `تم الاسترجاع والصرف للمريض`، وتلوين الإجمالي بالأحمر مع بيان تفاصيل الفحص الملغي والوصل الأصلي.
  * دعم الوصل الحراري: إظهار شريط تحذيري بارز `⚠️ سند استرجاع مالي (REFUND)`، وتنسيق المبلغ المسترجع بدقة بدون إرباك الإشارات السالبة، مع توقيع المستلم وتوقيع أمين الصندوق.
- **الاختبارات الآلية الحصينة ([tests/Feature/ExternalRequestCancellationTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ExternalRequestCancellationTest.php))**:
  * إضافة اختبار `test_cashier_can_refund_cancelled_paid_medical_request` لاختبار دورة الاسترجاع المحاسبية بالكامل: `7 passed (44 assertions)`.

## Session log (2026-10-17 — المرحلة 70: نظام إلغاء الطلبات الخارجية المباشرة للمختبر والأشعة مع الحصانة السريرية والمالية Direct External Medical Requests Cancellation with Clinical & Financial Guards)

### Done
- **تطوير محرك التحقق والتمييز المرجعي ([app/Models/Request.php](file:///c:/wamp64/www/hospital-system/app/Models/Request.php))**:
  * إضافة دالة والـ Accessor `isDirectExternal()` بموديل `Request` لتمييز الطلبات الخارجية المباشرة المحجوزة من الاستعلامات أو الصندوق عن طلبات العيادات الاستشارية.
  * إضافة علاقة المدفوعات `payments()` للربط المباشر مع سجلات الكاشير.
- **الحصانة السريرية والمالية في المتحكمات ([app/Http/Controllers/LabStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabStaffController.php) و [app/Http/Controllers/RadiologyStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RadiologyStaffController.php))**:
  * إضافة دالة `cancel(HttpRequest $httpRequest, MedicalRequest $request)` في كلا المتحكمين.
  * **الحصانة السريرية**: حظر إلغاء أي طلب صادر عن طبيب استشاري داخل العيادات من قبل موظفي المختبر أو الأشعة (`!$request->isDirectExternal()`)، مع توجيه صريح بأن الإلغاء يتم حصراً من شاشة الطبيب المعالج.
  * **المرونة التشغيلية والتوافق المالي (Operational Refund Routing)**: تمكين موظفي المختبر والأشعة من إلغاء أي طلب خارجي مباشر تعذر إجراؤه حتى لو كان مسدداً مسبقاً في الكاشير (`paid`)، مع وسم الطلب بـ `cancelled_after_payment = true` و `refund_status = 'pending_refund'` وإشعار الموظف بتوجيه المريض إلى الكاشير لاسترداد المبلغ المسدد، وحفظ تفاصيل الإيصال والمبلغ في `$details`.
  * **الحصانة التشغيلية**: منع إلغاء الطلبات المكتملة والمعتمدة مسبقاً (`$request->status === 'completed'`).
  * **المعالجة الشاملة للإلغاء**: تحويل حالة الطلب إلى `cancelled`، توسيع `payment_status` ENUM ليشمل قيمة `cancelled`، تسجيل بيانات الإلغاء وسببه واسم الموظف والوقت في `$details`، وتحديث حالة زيارة الاستعلامات المباشرة إلى `cancelled` لإزالتها تلقائياً وفورياً من طابور الفحص والمناداة وقائمة الطلبات المعلقة في الكاشير، وتسجيل حركة تدقيق رسمية في `UserActivityLogger`.
- **المسارات وحماية الصلاحيات ([routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
  * تسجيل راوت `POST /lab/requests/{request}/cancel` باسم `lab.cancel` بميدلوير `can:view lab tests`.
  * تسجيل راوت `POST /radiology-staff/requests/{request}/cancel` باسم `radiology-staff.cancel` بميدلوير `can:view radiology`.
- **الواجهات والتفاعل الحي المودال**:
  * [resources/views/lab/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/index.blade.php): إظهار زر الإلغاء `[ 🚫 ]` ومودال تأكيد الإلغاء مع حقل سبب الإلغاء لكافة طلبات المختبر الخارجية المباشرة النشطة سواء كانت مدفوعة أو غير مدفوعة.
  * [resources/views/lab/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/show.blade.php): إضافة زر ومودال الإلغاء في شريط الإجراءات العلوي لشاشة الفحص.
  * [resources/views/radiology-staff/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/radiology-staff/index.blade.php): إضافة زر الإلغاء في طابور الانتظار الأولي (سواء تم النداء أم لا، وسواء سدد المريض أم لا) وفي تبويب الأرشيف العام مع مودال التأكيد الشامل لكافة فئات التصوير (أشعة، سونار، رنين، مفراس، إيكو).
  * [resources/views/radiology-staff/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/radiology-staff/show.blade.php): إضافة زر ومودال الإلغاء في شاشة كتابة التقرير وفحص الأشعة.
- **الاختبارات الآلية الحصينة ([tests/Feature/ExternalRequestCancellationTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ExternalRequestCancellationTest.php))**:
  * بناء 6 اختبارات شاملة تغطي: إلغاء طلبات المختبر والأشعة الخارجية غير المسددة، إلغاء الطلبات المسددة مع وسم استرجاع الكاشير، ومنع إلغاء طلبات الاستشارية: `6 passed (36 assertions)`.

## Session log (2026-10-17 — المرحلة 69: حصر طلبات الاستعلامات المباشرة بالسعر النقدي 100% واعتماد تسعير وعرض باقات المختبر كبند موحد في الكاشير والسندات مع تحسين محطات العمل Direct Inquiry Cash Enforcement, Laboratory Packages Unified Pricing in Cashier & Receipts, and Workstation Search Enhancements)

### Done
- **حصر طلبات الاستعلامات المباشرة بالسعر النقدي 100% (Direct Inquiry Cash Enforcement)**:
  * تطبيق السياسة الطبية والمالية: الطلبات الخارجية المباشرة من الاستعلامات (المختبر، الأشعة، السونار، الرنين، المفراس، الإيكو) غير مشمولة بالضمان الصحي وتحاسب نقداً بنسبة 100% كاش إذا لم تكن محالة ومقترنة بحجز طبيب استشاري داخل المستشفى.
  * تحديث [InquiryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/InquiryController.php): تثبيت `insurance_type = 'none'` لكافة الطلبات الطبية المباشرة الصادرة من الاستعلامات.
  * تحديث طابور الكاشير [resources/views/cashier/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/index.blade.php): تطبيق الفحص الحصين على تبويب "الكل" والتبويب المخصص لمنع حساب نسبة تحمل الضمان وإظهار السعر النقدي الكامل فوراً.
  * تحديث نموذج الدفع [resources/views/cashier/request-payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/request-payment-form.blade.php) و [app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php).
- **اعتماد تسعير وعرض باقات المختبر كبند موحد (Laboratory Packages Unified Pricing & Display)**:
  * حل مشكلة تجزئة الباقة ومضاعفة سعرها: عند اختيار باقة طبية (Package) في المختبر، تظهر الباقة في الكاشير وفي السندات كبند أساسي موحد `[ 📦 باقة: اسم الباقة (X تحاليل مشمولة) ]` بسعر الباقة المعتمد فقط.
  * دعم التحاليل الإضافية الذاتي: في حال اختيار تحاليل خارج الباقة، تُفصل التحاليل الإضافية فقط وتُحسب بسعرها المنفرد مضافة لسعر الباقة.
  * تحديث معالجة الدفع في `CashierController::processRequestPayment` وتحديث وصف الدفعة في سندات الخزينة.
  * تحديث واجهات السندات الحرارية والرسمية [resources/views/cashier/receipt.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt.blade.php) و [resources/views/cashier/receipt-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt-print.blade.php).
  * تحسين تفاعل الباقات في [resources/views/lab/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/show.blade.php): تصفير أي تحديدات سابقة تلقائياً عند النقر على أي باقة لضمان دقة التحاليل المختارة.
  * تصحيح حالة طلب المريض علي حسين الفت (طلب #6) بسعر باقة `celiac` المعتمد 50,000 د.ع، وطلب منير سمير يوسف (طلب #7) ليكون كاش 100%.
- **تطوير محطة عمل الأشعة والمختبر**:
  * بناء محرك البحث الحي الفوري (Autocomplete Dropdown) مع شارات الفحوصات المختارة وزر الإزالة وحساب الإجمالي المباشر.
  * تحويل باقات المختبر إلى شريط أفقي مدمج لتوفير المساحة الرأسية وتسهيل العمل المخبري.

## Session log (2026-10-16 — المرحلة 68: حل مشكلة عرض صورة التوقيع على هوستنجر بالتحويل التلقائي إلى Base64 Data URI وبث المسار المباشر وحصانة إنشاء الطبيب Doctor Signature Base64 Data URI, Stream Route & Safe Doctor Creation Guard)

### Done
- **تشخيص السبب الجذري على سيرفر هوستنجر**:
  * نظام المستشفى يعمل داخل مسار فرعي على هوستنجر (`https://hinpa.icu/hearmz/`).
  * عند استخدام دالة `asset($doctor->signature)` كان الرابط يطلب مسار الصورة الثابت من Apache، والذي يرجع خطأ `404 Not Found` نظراً لعدم توجيه طلبات الملفات الساكنة بالمسار الفرعي إلى مجلد `public/`، مما أدى لظهور أيقونة الصورة المكسورة مع النص البديل (`توقيع الطبيب`).
- **المعالجة الجذرية والذكية عبر Base64 Data URI وبث الراوت المباشر**:
  * **إضافة Accessor بموديل الطبيب ([app/Models/Doctor.php](file:///c:/wamp64/www/hospital-system/app/Models/Doctor.php))**:
    - `signature_data_uri`: قراءة ملف التوقيع مباشرة من القرص الصلب وتضمينه كـ `data:image/png;base64,...` مدمج ومباشر في كود HTML دون الحاجة لأي طلب شبكي خارجي (مثل شعار النظام المعتمد).
    - `signature_url`: يرجع Data URI تلقائياً مع خيار العودة لمسار الراوت الديناميكي.
  * **إضافة راوت وبث مباشر للتواقيع ([routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php) و [app/Http/Controllers/DoctorController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorController.php))**:
    - مسار `GET /doctors/{doctor}/signature` ودالة `signature()` لخدمة التوقيع عبر PHP مباشرة بحماية وتجاوز أي قيود في إعدادات السيرفر.
    - تحديث استجابة الـ AJAX في `uploadSignature()` لترجع الـ `signature_data_uri` فورياً للعرض اللحظي بعد الحفظ.
  * **تحديث واجهات العرض والتقارير الطبية**:
    - [resources/views/doctors/edit.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/edit.blade.php) و [resources/views/doctors/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/show.blade.php): اعتماد التوقيع المفرغ الفوري.
    - [resources/views/emergency/medical-report.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/medical-report.blade.php) و [resources/views/emergency/verify-report.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/verify-report.blade.php): تضمين التوقيع كـ Base64 مباشر للطباعة الرسمية الثابتة.
  * **تحصين إنشاء الطبيب وحماية الأعمدة**:
    - عزل الأعمدة الملغاة (`qualification`, `license_number`) في `DoctorController::store` و `storeReferringDoctor` بفحص `Schema::hasColumn`.
    - تطبيق `DB::transaction` لحماية إنشاء المستخدمين والأطباء من الحسابات اليتيمة.
- **الاختبارات الآلية (Automated Tests)**:
  * اجتياز كامل لـ [tests/Feature/DoctorSignatureTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DoctorSignatureTest.php): `4 passed (21 assertions)`.
  * اجتياز كامل لـ [tests/Feature/EmergencyMedicalReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyMedicalReportTest.php): `3 passed (19 assertions)`.

## Session log (2026-10-16 — المرحلة 67: حذف وتطهير طلبات وحالات المختبر والأشعة والطوارئ وتصفير مبالغها في الخزينة على سيرفر هوستنجر مع حماية الكتالوجات والمرضى Hostinger Lab, Radiology & Emergency Operational & Financial Purge with Catalogs & Patients Preserved)

### Done
- **أخذ نسخة احتياطية فورية وشاملة ([storage/app/backups/hostinger_lab_rad_emerg_before_purge_2026_10_16_202735.sql](file:///c:/wamp64/www/hospital-system/storage/app/backups/hostinger_lab_rad_emerg_before_purge_2026_10_16_202735.sql))**:
  * حفظ ملف SQL متكامل بحجم 87 كيلوبايت يحتوي على أوامر `INSERT` لكافة سجلات الطلبات والدفعات وقيود الخزينة وحالات الطوارئ قبل مسحها.
- **الحذف والتصفير المتزامن ضمن Transaction موحدة على هوستنجر**:
  * **المختبر (`lab`)**: حذف 14 طلباً مخبرياً (`requests`)، حذف 20 دفعة مسددة في الكاشير بقيمة 571,250 د.ع (`payments`)، وحذف 22 قيد خزينة بقيمة 583,750 د.ع (`financial_transactions`).
  * **الأشعة والسونار (`radiology`)**: حذف 31 طلب أشعة وسونار (`requests`)، حذف 19 سجلاً في جدول طابور الأشعة (`radiology_requests`)، وحذف نتيجتين مسجلتين (`radiology_results`).
  * **الطوارئ (`emergency`)**: حذف 8 حالات طوارئ ومرضى طوارئ (`emergencies` و `emergency_patients`)، وحذف الخدمات المسجلة والعلامات الحيوية وطلبات المختبر والأشعة التابعة للطوارئ، وحذف 4 دفعات كاشير بقيمة 230,000 د.ع، وحذف قيدي خزينة للطوارئ بقيمة 115,000 د.ع.
- **الحماية الكاملة للأصول والكتالوجات**:
  * كتالوج تحاليل المختبر (150 فحصاً + 127 فحصاً فرعياً + 218 قيمة مرجعية) سليم تماماً.
  * كتالوج الأشعة والسونار المعتمد الجديد (224 فحصاً باللغة الإنكليزية مع الأسعار) سليم تماماً.
  * كتالوج خدمات الطوارئ المسعرة (110 خدمات) سليم تماماً.
  * سجل المرضى العام (8,399 مريضاً) وحسابات الموظفين والأطباء سليمة ومحمية 100%.

## Session log (2026-10-16 — المرحلة 66: حذف وتطهير كافة السجلات التشغيلية والمالية للدكتورة رولا محمد اسماعيل على سيرفر هوستنجر مع الحفاظ التام على حسابها وملفها الطبي Dr. Rola Mohammed Ismail Hostinger Operational & Financial Purge with Profile Preserved)

### Done
- **أخذ نسخة احتياطية فورية وشاملة ([storage/app/backups/hostinger_dr_rola_backup_before_delete_2026_10_16_201919.sql](file:///c:/wamp64/www/hospital-system/storage/app/backups/hostinger_dr_rola_backup_before_delete_2026_10_16_201919.sql))**:
  * حفظ ملف SQL متكامل يحتوي على أوامر `INSERT` دقيقة لكل صف تم تحديده قبل الحذف لضمان أمان البيانات وإمكانية الاسترجاع عند الطلب.
- **الحذف التتابعي والآمن لكافة السجلات التشغيلية والمالية ضمن Transaction متماسكة**:
  * **المواعيد والحجوزات (`appointments`)**: حذف 12 موعداً مرتبطاً بالطبيبة.
  * **الزيارات الطبية (`visits`)**: حذف 7 زيارات طبية مسجلة للعيادة.
  * **طلبات الفحوصات والتحاليل (`requests`)**: حذف 18 طلباً محولاً للمختبر والأشعة.
  * **سجلات الفحوصات التابعة (`lab_results` و `radiology_requests`)**: حذف 10 سجلات تابعة لتلك الزيارات.
  * **الوصفات الصيدلانية (`prescriptions` و `prescribed_medications`)**: حذف 6 سجلات أدوية ووصفات.
  * **المستحقات والإيرادات المالية (`consultation_revenues` و `doctor_dues`)**: حذف 9 سجلات إيرادات كشفية و 6 سجلات مستحقات طبيب (`doctor_dues`).
  * **المدفوعات وقيود الخزينة (`payments` و `financial_transactions`)**: حذف 9 دفعات مسجلة بكاشير الاستشارية و 9 قيود مالية مقترنة بالوصولات في سجل الخزينة.
  * **تصفير رصيد الحساب المالي للطبيب (`doctor_financial_accounts`)**: تصفير الرصيد (`balance = 0.00`) وإجمالي الأرباح (`total_earned = 0.00`) مع إبقاء سجل الحساب لعدم كسر النظام.
- **الحفاظ التام على حساب وملف الطبيب**:
  * بقاء سجل الطبيب في جدول `doctors` (المعرّف: 242) وسجل المستخدم في جدول `users` (المعرّف: 510، الاسم: «رولا محمد اسماعيل»، البريد: `rola@hospital.com`) وإعدادات النسبة سليمة ومحمية 100%.

## Session log (2026-10-16 — المرحلة 65: استبدال وتطهير جدول الأشعة بالكامل بملف الإكسل وحذف الخدمات القديمة مع حماية السجلات السابقة Radiology Catalog Complete Wipe & Replace with Excel Import)

### Done
- **أخذ نسخة احتياطية فورية وتلقائية لقاعدة البيانات ([storage/app/backups/radiology_types_before_excel_replace_2026_10_16_194814.sql](file:///c:/wamp64/www/hospital-system/storage/app/backups/radiology_types_before_excel_replace_2026_10_16_194814.sql))**:
  * حفظ نسخة SQL كاملة وتفصيلية لجدول `radiology_types` قبل التعديل لضمان الأمان والرجوع.
- **تطوير محرك الاستيراد والتنظيف الذاتي ([app/Imports/RadiologyTypesImport.php](file:///c:/wamp64/www/hospital-system/app/Imports/RadiologyTypesImport.php))**:
  * دعم خيار `$replaceExisting`: يقوم تلقائياً بحذف أي خدمة غير موجودة في ملف الإكسل وغير مستخدمة في أي طلبات سابقة للمرضى (`deletedCount`).
  * تحصين السجلات الطبية السابقة: في حال كانت الخدمة مرتبطة بطلب فحص سابق، يتم تعطيلها (`is_active = false`) لحماية تكامل البيانات.
  * عزل وتفادي تعارض الأكواد الفريدة (Unique Code Swapping Guard): إضافة معالجة ذكية توافقية تمنع خطأ `1062 Duplicate entry` عند تبادل الأكواد بين الصفوف عبر بادئة مؤقتة تعمل بدقة على محركات `MySQL` و `SQLite`.
  * المعايرة الدقيقة للتصنيفات (`normalizeCategory`): ربط الأقسام بمسمياتها القياسية في النظام (`أشعة`، `سونار`، `مفراس`، `الرنين`، `إيكو`).
- **تحديث واجهة ونافذة استيراد الإكسل ومتحكم الأشعة ([resources/views/radiology/types/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/radiology/types/index.blade.php) و [app/Http/Controllers/RadiologyTypeController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RadiologyTypeController.php))**:
  * إضافة مفتاح تبديل بارز `[ ✓ ] استبدال محتويات الجدول بالكامل بمحتويات ملف الإكسل (حذف الخدمات غير الموجودة في الإكسل)` مفعل افتراضياً.
  * إظهار تفاصيل عدد الخدمات المحدثة والجديدة والمحذوفة في رسالة النجاح للمستخدم.
- **تنفيذ الاستبدال الفعلي لملف `الاخير والمعتمد اشعة.xlsx`**:
  * إزالة 80 خدمة قديمة بأسماء عربية كانت تزحم الصفحات الأولى.
  * تحديث وتثبيت 224 فحصاً باللغة الإنكليزية مطابقة تماماً للملف المعتمد بأسعار الكاش والضمان الصحي.
- **الاختبارات الآلية (Automated Tests)**:
  * تحديث [tests/Feature/LabAndRadiologyImportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabAndRadiologyImportTest.php) وإضافة اختبار `test_radiology_types_import_with_replace_all_deletes_stale_records`: اجتياز كامل لـ `6 passed (26 assertions)`.
  * اجتياز كامل اختبارات قسم الأشعة: `20 passed (89 assertions)`.

## Session log (2026-10-16 — المرحلة 64: إدراج حقول توجيهات وملاحظات الطبيب للمختبر والأشعة في محطة الطبيب وربطها الكامل بمحطات المختبر والأشعة Doctor Clinical Notes Input in Consultation Station & Full Lab/Radiology Workstation Integration)

### Done
- **إضافة حقول الملاحظات والتوجيهات السريرية في محطة عمل الطبيب ([resources/views/doctors/visits/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/show.blade.php))**:
  * **تبويب طلبات المختبر (`#labCollapse`)**: إضافة حقل نصي أنيق `[ 📝 ملاحظات وتوجيهات الطبيب للمختبر (اختياري) ]` يسمح للطبيب بكتابة شروط العينة (صيام 12 ساعة، فحص عاجل، متابعة بعد علاج، إلخ).
  * **تبويب طلبات الأشعة والتصوير (`#radiologyCollapse`)**: إضافة حقل نصي أنيق `[ 📝 ملاحظات وتوجيهات الطبيب لقسم الأشعة (اختياري) ]` يتيح كتابة الشك السريري (مثل: شك بكسر في الساق اليمنى، تركيز السونار على الكلية اليمنى، مريض لا يقوى على الوقوف).
- **استقبال وتخزين الملاحظات في تفاصيل الطلب ([app/Http/Controllers/DoctorVisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorVisitController.php))**:
  * إضافة التحقق من صحة الحقل `doctor_notes` (نص اختياري بحد أقصى 1000 حرف) في دالة `storeRequest`.
  * حفظ الملاحظات تلقائياً في مصفوفة تفاصيل الطلب `$details['doctor_notes']` لترتبط فورياً بالـ Accessor `doctor_notes` بموديل `Request`.
- **عرض التوجيهات السريرية لموظف وفني المختبر ([resources/views/lab/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/show.blade.php) و [resources/views/lab/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/index.blade.php))**:
  * إضافة بطاقة كهرمانية تنبيهية بارزة `[ 📝 ملاحظات وتوجيهات الطبيب المحول ]` أسفل بطاقة المريض في شاشة فحص وتحرير نتائج المختبر.
  * إظهار وسام تنبيهي أنيق `[ 📝 توجيهات الطبيب ]` في جدول طابور المختبر العام تحت اسم الطبيب المحول.
- **إظهار الوسام التنبيهي في جدول طابور الأشعة العام ([resources/views/radiology-staff/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/radiology-staff/index.blade.php))**:
  * إبراز وسام الملاحظات تحت اسم الطبيب المحول في جدول طابور الأشعة ليراه الفني فوراً قبل فتح الفحص.
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء واجتياز اختبارات المنظومة كاملة [tests/Feature/DoctorRequestClinicalNotesTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DoctorRequestClinicalNotesTest.php): `2 passed (18 assertions)`.

## Session log (2026-10-16 — المرحلة 63: عرض ملاحظات وتوجيهات الطبيب المحول لموظف الأشعة في شاشة الفحص وطابور العمل Doctor Clinical Notes Display in Radiology Staff Workstation & Queue)

### Done
- **إضافة معرّف ملاحظات الطبيب بموديل الطلبات الطبيـة ([app/Models/Request.php](file:///c:/wamp64/www/hospital-system/app/Models/Request.php))**:
  * إضافة Accessor رسمي `doctor_notes` بموديل `Request` لاستخراج وقراءة ملاحظات وتوجيهات الطبيب المحول تلقائياً من حقل `details['doctor_notes']` أو `details['notes']` أو `details['description']`.
- **ربط الملاحظات ببيانات الطابور لموظف الأشعة ([app/Http/Controllers/RadiologyStaffController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RadiologyStaffController.php))**:
  * تضمين `doctor_notes` في استجابة الـ JSON لطابور موظف الأشعة `queueStatus`.
- **عرض صندوق الملاحظات والتوجيهات في شاشة فحص الأشعة ([resources/views/radiology-staff/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/radiology-staff/show.blade.php))**:
  * إضافة بطاقة تنبيهية كهرمانية أنيقة `[ 📝 ملاحظات وتوجيهات الطبيب المحول ]` أعلى محرر الفحص والتقرير يبرز نص الملاحظات بدقة أمام موظف وفني الأشعة.

## Session log (2026-10-16 — المرحلة 62: منظومة رفع وتفريغ التواقيع اليدوية للأطباء تلقائياً والمعاينة الحية للشفافية وربط التواقيع بتقارير الطوارئ Doctor Signature Upload, Auto White Background Removal, Live Canvas Preview & Multi-Doctor Emergency Integration)

### Done
- **محرك معالجة وتفريغ التواقيع التلقائي ([app/Services/SignatureProcessingService.php](file:///c:/wamp64/www/hospital-system/app/Services/SignatureProcessingService.php))**:
  * **الخلفية والدافع**: توفير حل ذاتي وتلقائي بالكامل لربط تواقيع أطباء المستشفى (كطوارئ، استشاريين، جراحين) دون الحاجة لبرامج تصميم أو فوتوشوب أو طلب ملفات PNG جاهزة، وتمكين الإدارة من رفع صور ملتقطة بكاميرا الهاتف على ورقة بيضاء بأي صيغة (JPG / PNG / WEBP).
  * **خوارزمية عزل الخلفية والقص التلقائي**:
    - فحص البكسلات ومقارنة اللمعان (Luminance) عبر مكتبة PHP GD.
    - تحويل بكسلات الورقة البيضاء والظلال إلى شفافية ألفا كاملة (`100% Alpha Channel`).
    - تطبيق تنعيم تدريجي للحواف (Anti-Aliasing) لمنع تكسر وتسنن خطوط الحبر.
    - تعزيز خط الحبر (أزرق أو أسود) ليكون داكناً وحاداً في الطباعة والتقارير.
    - قص الهوامش الزائدة تلقائياً (Auto-Crop Bounding Box) لتركيز التوقيع بدون فراغات وحفظه بدقة في `public/images/signatures/doctor_{id}.png`.
- **تطوير واجهة الطبيب ([resources/views/doctors/edit.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/edit.blade.php))**:
  * إضافة بطاقة متقدمة **«التوقيع اليدوي الرسمي للطبيب»** مع علامة اعتماد التوقيع وإظهار التوقيع الحالي فوق خلفية شطرنج الشفافية (Checkerboard).
  * محرك تفريغ لحظي في المتصفح عبر JavaScript Canvas: بمجرد اختيار صورة من الجهاز أو الموبايل، يتم إظهار التوقيع مفرغاً وشفافاً فوراً في صندوق المعاينة.
  * شريط تحكم مرن في الحساسية (Sensitivity / Threshold Range Slider) لضبط عزل بياض وظلال الورقة في الصور المظلمة مع تحديث المعاينة الحية لحظياً.
  * زر حفظ فوري عبر AJAX `[ ⚡ حفظ التوقيع فوراً الآن ]` ومسار `POST /doctors/{doctor}/upload-signature` لتحديث التوقيع دون الحاجة لإعادة حفظ كامل بيانات الطبيب.
  * خيار إزالة التوقيع الحالي `[ ❌ حذف التوقيع الحالي ]`.
- **إبراز التوقيع في بطاقة تفاصيل الطبيب ([resources/views/doctors/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/show.blade.php))**:
  * إضافة بطاقة **«التوقيع اليدوي المعتمد»** في الشريط الجانبي تعرض التوقيع الشفاف مع وسام اعتماده وظهوره في التقارير الطبية.
- **تكامل تقرير الطوارئ مع أطباء متعددين وتواقيعهم المعتمدة ([resources/views/emergency/medical-report.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/medical-report.blade.php))**:
  * تحديث قالب التقرير ليرتبط باسم وتوقيع الطبيب المعالج للحالة ديناميكياً بدلاً من التسميات الثابتة.
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء واجتياز اختبارات المنظومة كاملة [tests/Feature/DoctorSignatureTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DoctorSignatureTest.php): `3 passed (13 assertions)`.
  * اجتياز كامل اختبارات تقرير الطوارئ [tests/Feature/EmergencyMedicalReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyMedicalReportTest.php): `3 passed (18 assertions)`.

## Session log (2026-10-16 — المرحلة 61: استدعاء المريض الاستثنائي للطبيب وثبات تسلسل الطابور ودعم طلبات الضمان المختلطة بالكاشير Out-of-Turn Calling, Queue Order Stability, Hostinger Old Catalogs & Mixed Insurance Requests)

### Done
- **استعادة القوائم القديمة للمختبر والأشعة بتاريخ 2026-10-11 على سيرفر هوستنجر (`remote_hostinger`)**:
  * أخذ نسخة احتياطية لسيرفر هوستنجر قبل التعديل: `storage/app/backups/hostinger_catalogs_backup_before_old_restore_2026_10_16_114446.sql` (415 KB).
  * استرجاع 150 فحص مختبر (`lab_tests`) و 305 خدمة أشعة وسونار (`radiology_types`) بدقة ومطابقتها.
  * الحفاظ الكامل على بيانات المرضى الـ 8,380 والمستخدمين والزيارات والفواتير دون أي مساس.
- **استدعاء المريض الاستثنائي/الحرج من جدول زيارات الطبيب (`/doctor/visits`) وثبات تسلسل الطابور**:
  * **الخلفية والدافع**: رغبة الطبيب في تقديم واستدعاء مريض معين من الطابور (مثل مريض تسلسله #5 أو #10 بحالة طارئة أو حرجة) دون أن يتغير أو يتلخبط ترتيب باقي المرضى المنتظرين في الطابور (مثل 1، 2، 3، 4 يبقون في طابورهم وبنفس أرقام دورهم).
  * **التعديل البرمجي في المتحكم والمراقب**:
    - إضافة دالة `callSpecific(Request $request, $appointmentId)` في [app/Http/Controllers/DoctorQueueController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorQueueController.php) ومسار `POST /queue/appointment/{appointment}/call-specific`.
    - تثبيت ترقيم الأدوار الثابت غير القابل للتلاشي أو اللخبطة: ربط توليد `queue_number` برمجياً في `AppointmentObserver::creating` و `InquiryController::storeRequest` بحساب أقصى رقم تسلسل لليوم الحالي `max(queue_number) + 1` فور الإنشاء.
    - تعديل استعلام طابور الانتظار لترتيب المرضى دائماً حسب `queue_number asc` ورفع الحد إلى 50 مريضاً.
  * **تطوير واجهة الطبيب [resources/views/doctors/visits/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/index.blade.php)**:
    - إظهار زر `[ 📢 استدعاء ]` للمريض الأول في الطابور، وزر `[ 📢 تقديم واستدعاء ]` متبوعاً بوسام `[ دور #X ]` لبقية المرضى المنتظرين في الجدول، مع ربطهما بوظيفة `doctorCallSpecific(appointmentId)` عبر الـ JavaScript والتحديث الفوري.
- **دعم تسديد الطلبات المختلطة (المشمولة وغير المشمولة بالضمان) في الكاشير دون حظر (`request-payment-form`)**:
  * **الخلفية والدافع**: عند طلب الطبيب فحوصات متعددة لمريض ضمان، ويكون بعضها غير مسعر للضمان أو غير مشمول (مثل أشعة الصدر `Chest Ap`)، كان النظام يمنع التسديد تماماً برسالة خطأ ويعطل زر الدفع.
  * **المرونة والاحتساب المالي التلقائي**:
    - تعديل منطق الحسابات في [app/Traits/HasInsurancePricing.php](file:///c:/wamp64/www/hospital-system/app/Traits/HasInsurancePricing.php) بحيث يعتبر الفحص مشمولاً فقط إذا كان الضمان مفعلاً وسعره أكبر من الصفر؛ وفي حال كان السعر غير مدخل أو غير مشمول، يتم اعتباره خدمة كاش عادية (100% على المريض و0 على الضمان) دون المساس بسعر الخدمة الأصلي.
    - تحديث واجهة كاشير الطلبات [resources/views/cashier/request-payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/request-payment-form.blade.php) لتمييز كل بند بوسام ملون: `[ مشمول بالضمان ]` بالأخضر أو `[ غير مشمول (كاش 100%) ]` بالكهرماني.
    - تفعيل زر التسديد دائماً وعرض تنبيه توضيحي للكاشير بحساب الفحوصات غير المشمولة كاش 100% وإشراك بقية الفحوصات في الضمان.
- **الاختبارات الآلية (Automated Tests)**:
  * اجتياز اختبارات استدعاء المريض الاستثنائي [tests/Feature/DoctorQueueCallSpecificTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DoctorQueueCallSpecificTest.php): `1 passed (16 assertions)`.
  * اجتياز اختبارات تسعير الضمان [tests/Unit/InsurancePricingTest.php](file:///c:/wamp64/www/hospital-system/tests/Unit/InsurancePricingTest.php): `4 passed (37 assertions)`.

## Session log (2026-10-16 — المرحلة 60: فصل المبالغ المسترجعة للمرضى في كارت خزينة مستقل ومطابقة السيرفر السحابي Treasury Refunds Dedicated KPI Card, Hostinger Sync & Cascaded Deletion Guard)

### Done
- **فصل المبالغ المسترجعة للمرضى عن المصروفات التشغيلية في الخزينة المركزية (`/treasury`)**:
  * **الخلفية والدافع**: عند استرجاع كشفية ملغاة لمريض (مثل وصل REC-0013 بقيمة 35,000 د.ع)، كانت تُدمج في كارت «إجمالي المنصرف» الذي يحمل عنواناً فرعياً «مستحقات أطباء ومشتريات ونثريات»، مما يسبب التباساً رقابياً لدى الإدارة.
  * **توسيع نموذج الحركات المالية ومراقب الدفع**:
    - إضافة تصنيف `refund` (استرجاع مالي للمرضى) في [app/Models/FinancialTransaction.php](file:///c:/wamp64/www/hospital-system/app/Models/FinancialTransaction.php).
    - تحديث [app/Observers/PaymentObserver.php](file:///c:/wamp64/www/hospital-system/app/Observers/PaymentObserver.php) ليتم تعيين تصنيف `category = 'refund'` تلقائياً لسندات الصرف الناتجة عن مبالغ الدفع السالبة.
    - إضافة معالج الحذف التتابعي `deleted(Payment $payment)` لحذف قيود الخزينة وأرباح الأطباء وإيراد الاستشارية فورياً عند حذف أي دفعة.
  * **المتحكم [app/Http/Controllers/HospitalTreasuryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/HospitalTreasuryController.php)**:
    - حساب `$periodRefunds` (مجموع المبالغ المسترجعة للمرضى) و `$periodRefundsCount` (عدد حركات الاسترجاع).
    - حساب `$periodExpenses` (المصروفات ومستحقات الأطباء الفعلية فقط بدون المسترجعات).
    - تمرير المتغيرات للواجهة واستخراج نسبة كل بند في شريط التوزيع.
  * **هيكلية الـ 5 كروت في الواجهة [resources/views/treasury/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/treasury/index.blade.php)**:
    1. **الرصيد الفعلي بالخزينة**: الرصيد اللحظي التراكمي في الصندوق.
    2. **إجمالي الوارد (الفترة)**: كافة مقبوضات الأقسام والكاشير.
    3. **المبالغ المسترجعة للمرضى (كارت جديد مخصص ومستقل)**: يوضح مسترجعات الكشفية والسونار باللون الكهرماني مع عداد حركات الاسترجاع.
    4. **المصروفات ومستحقات الأطباء**: يوضح الصرفيات التشغيلية والمشتريات فقط باللون الأحمر.
    5. **الصافي الفعلي للفترة**: الوارد مطروحاً منه (المسترجع + المصروف).
    - إضافة شريط استرجاعات مبالغ المرضى في بطاقة تفكيك وتوزيع المصروفات.
- **تحديث ومطابقة الخزينة السحابية على هوستنجر**:
  * تحديث تصنيف حركة الاسترجاع رقم 134 إلى `category = 'refund'` على قاعدة بيانات هوستنجر ومطابقة الرصيد الصافي ليوم 15-10 عند **552,500 د.ع**.
- **الاختبارات الآلية (Automated Tests)**:
  * اجتياز كامل اختبارات الخزينة: `9 passed (41 assertions)`.

## Session log (2026-10-16 — المرحلة 59: منظومة الحد من تكرار الحجز وتنبيهات الحجوزات النشطة للكشفية والسونار وتأكيد الموظف ومنع النقر المزدوج Duplicate Booking Prevention, Active Alerts, Staff Confirmation & Debounce)

### Done
- **التحقق اللحظي والفوري من الحجوزات النشطة غير المكتملة عبر AJAX (`/inquiry/check-active-booking`)**:
  * **الخلفية والدافع**: معالجة مشكلة تكرار الحجز للمريض في نفس اليوم بالخطأ لكشفيات الأطباء الاستشاريين أو السونار الخارجي المباشر، مع مراعاة حق المريض في حجز موعد ثانٍ مقصود لنفس اليوم بعد تنبيه الموظف وتأكيده الصريح.
  * **تعريف الحجز النشط**:
    - أي حجز أو موعد لم يكتمل بعد (`status not in ('completed', 'cancelled')`) لتاريخ اليوم، بصرف النظر عما إذا كان قد سدد في الكاشير أو ما زال بانتظار الدفع (فطالما لم ينهِ الطبيب أو أخصائي السونار الفحص، فهو حجز نشط في الطابور).
    - إذا كان الحجز السابق مكتملاً (`status = 'completed'`) أو ملغياً (`status = 'cancelled'`)، يُسمح بالحجز مباشرة دون أي اعتراض.
  * **تطوير واجهة الاستعلامات التفاعلية [resources/views/inquiry/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/inquiry/create.blade.php)**:
    - **صناديق تنبيه فورية (`duplicateDoctorWarningBox` و `duplicateUltrasoundWarningBox`)**: تظهر أسفل اختيار الطبيب أو نوع السونار فورياً مع تفاصيل رقم التسلسل وحالة الطابور والدفع.
    - **نافذة تأكيد مودال حديثة (`#confirmDuplicateModal`)**: تعترض الإرسال في حال وجود حجز نشط غير مؤكد، وتطلب من موظف الاستعلامات تأكيد رغبته في إصدار حجز إضافي مكرر.
    - **قيد التدقيق الرقابي**: إضافة وسام ونص صريح في الملاحظات `حجز إضافي مؤكد من الاستعلامات` عند التأكيد.
    - **حماية النقر المزدوج (Submit Debounce)**: تعطيل زر الحفظ فوراً وإظهار مؤشر التحميل الدوار لمنع الإرسال المكرر السريع.
  * **التحقق والتحصين في الخادم [app/Http/Controllers/InquiryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/InquiryController.php)**:
    - فحص وجود حجز نشط في دالة `storeRequest` للكشفية والسونار؛ فإذا وُجد ولم يُرسل حقل التأكيد `confirm_duplicate_booking = 1`، يتم الرد بتنبيه التكرار `duplicate_booking_warning` مع الاحتفاظ بكافة بيانات الإدخال.
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء واجتياز اختبارات [tests/Feature/InquiryDuplicateBookingTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/InquiryDuplicateBookingTest.php): `5 passed (14 assertions)`.
  * اجتياز كامل اختبارات الحركات المالية للاستشارية: `8 passed (42 assertions)`.

## Session log (2026-10-16 — المرحلة 58: حجب وصولات القبض للمواعيد الملغاة والمسترجعة والتقرير المالي الرسمي بفورمة المستشفى Hide Cancelled Receipts & Official Hospital Form Financial Report)

### Done
- **حجب وصولات القبض للمواعيد الملغاة والمسترجعة من فلتر وصولات القبض (`filter_type=payment`)**:
  * **الخلفية والدافع**: عند إلغاء حجز واسترجاع مبلغه (مثل حجز المريض فراس رزاق مع د. زياد طارق)، يبقى وصل القبض `REC-0012` ووصل الاسترجاع `REC-0013` محفوظين في قاعدة البيانات للتدقيق المالي وصندوق الكاشير، لكن وصل القبض كان يظهر عند تصفية «حركات قبض» كقيد قبض نشط اعتيادي.
  * **تعديل الاستعلامات البرمجية (`ConsultantAvailabilityController` و `ConsultantFinancialMovementsExport`)**:
    - تعديل استعلام فلتر «حركات قبض (النشطة فقط)» لاستثناء أي قيد مرتبط بموعد ملغى أو مسترجع (`payment_status in ('refunded', 'cancelled')` أو `status = 'cancelled'`).
    - تحسين فلتر «المواعيد المسترجعة والملغاة» لإبرازها بدقة.
  * **الواجهة وتصدير الإكسل**:
    - إضافة محدد `is_refunded_or_cancelled` في نموذج [app/Models/ConsultationRevenue.php](file:///c:/wamp64/www/hospital-system/app/Models/ConsultationRevenue.php).
    - في واجهة الحركات المالية [resources/views/consultant-availability/financial-movements.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/consultant-availability/financial-movements.blade.php):
      * إبراز القيود الملغاة والمسترجعة بوسام مميز رمادي `[ 🚫 قبض (ملغى ومسترجع) ]` مع شطب المبلغ وتوضيح `ملغى ومسترجع`.
      * تعديل تسمية الخيار في القائمة المنسدلة إلى «حركات قبض (النشطة فقط)».
    - تحديث تصدير الإكسل [app/Exports/ConsultantFinancialMovementsExport.php](file:///c:/wamp64/www/hospital-system/app/Exports/ConsultantFinancialMovementsExport.php) ليعكس الحالة بدقة `قبض (ملغى ومسترجع)`.
- **التقرير المالي الرسمي بفورمة المستشفى (Official Hospital Form Financial Movements Report)**:
  * بناء استمارة تقرير رسمي فاخرة [resources/views/consultant-availability/financial-movements-report.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/consultant-availability/financial-movements-report.blade.php) مطابقة لفورمة تقرير الطوارئ (A4 Portrait - عمودي):
    - إطار مزدوج ملكي أخضر زمردي (`#0f766e` و `#99f6e4`) وعلامة مائية رسمية لشعار المستشفى (`images/1.jpg`).
    - ترويسة رسمية ثنائية اللغة: مستشفى الكفاءات الأهلي (Al-Kafaat Private Hospital) - قسم الإدارة المالية والتدقيق الحسابي - العيادات الاستشارية والتخصصية وفحوصات السونار.
    - شريط عنوان أنيق مع رقم مرجعي مشفر `REF-FIN-{{ now() }}` وشارة نسخة الإدارة والتدقيق.
    - بطاقة محددات التقرير (الفترة الزمنية، فترة الدوام، نوع الخدمة، نوع الحركة، التغطية، والمحاسب المنظم).
    - بطاقات إحصائية مالية ملونة للمجاميع: المستلم نقداً (الدرج)، المسترجع نقداً للمرضى، الصافي النقدي الفعلي، وإجمالي القيود.
    - جدول الحركات المالية الرسمي بتنسيق عالي الدقة يوضح تفاصيل كل قيد، شطب وملاحظات القيود الملغاة، وحصص الأطباء والمستشفى للمخولين.
    - تذييل رسمي ثلاثي الأعمدة:
      * رمز استجابة سريعة QR Code مولد برمجياً عبر مكتبة `qrcode.min.js` المحلية للتحقق من بيانات التقرير ومطابقته.
      * الختم الدائري الرسمي لقسم التدقيق المالي بمستشفى الكفاءات الأهلي.
      * تواقيع المحاسب المنظم والمدير المالي / الإدارة للمصادقة والاعتماد.
  * تسجيل مسار التقرير `consultant-availability.financial-movements.print` في [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php) والدالة `printFinancialMovements` في [app/Http/Controllers/ConsultantAvailabilityController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/ConsultantAvailabilityController.php).
  * ربط زر **[ 🖨️ طباعة التقرير الرسمي ]** في واجهة الحركات المالية ليفتح التقرير في نافذة جديدة مع شريط علوي للطباعة الفورية.
- **الاختبارات الآلية (Automated Tests)**:
  * إضافة واجتياز اختبارات [tests/Feature/ConsultantFinancialMovementsTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ConsultantFinancialMovementsTest.php): `8 passed (42 assertions)`.

## Session log (2026-10-15 — المرحلة 57: شمول السونار الخارجي المباشر ضمن الحركات المالية للاستشارية وفلتر نوع الخدمة وحصص الأطباء Direct External Ultrasound Integration into Consultant Financial Movements & Service Category Filter)

### Done
- **شمول السونار الخارجي المباشر المحجوز من استعلامات الاستشارية في الحركات المالية (`/consultant-availability/financial-movements`)**:
  * **الخلفية والدافع**: حركات السونار الخارجي المباشر يتم حجزها واستقبالها مباشرة عبر استعلامات الاستشارية ويفحصها أطباء السونار الاستشاريون (د. رشا، د. إسراء، د. هبة)، ويتم تحصيلها في الكاشير، وكان من الضروري محاسبياً ومالياً أن تظهر في شاشة الحركات المالية للاستشارية.
  * **المايجريشن والترحيل التراجعي (Backfill)**:
    - إنشاء وتشغيل مايجريشن [database/migrations/2026_10_15_230000_backfill_direct_ultrasound_to_consultation_revenues.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_15_230000_backfill_direct_ultrasound_to_consultation_revenues.php).
    - ترحيل 16 دفعة سونار خارجي مباشر سابقة بقيمة 535,000 د.ع وتوليد قيودها في `consultation_revenues` بنوع حركة `payment_type = 'ultrasound'`.
  * **التسجيل اللحظي المباشر عند دفع الكاشير (Real-time Recording in Cashier)**:
    - تحديث [app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php) في دالة `processRequestPayment` لتسجيل/تحديث قيد في `ConsultationRevenue` تلقائياً بنوع `ultrasound` وبكافة تفاصيل الحصة وحسابات الطبيب عند تسديد أي طلب سونار مباشر مرتبط بالاستعلامات.
  * **الفلاتر والواجهة وتصدير Excel**:
    - إضافة فلتر **«نوع الخدمة»** (`service_category`):
      * `الكل (كشفيات عيادات + سونار)`
      * `🩺 كشفيات العيادات فقط`
      * `📡 السونار الخارجي المباشر فقط`
    - تمييز حركات السونار بوسام مميز في الواجهة `[ 📡 سونار خارجي ]` باللون السماوي مقابل `[ 🩺 كشفية عيادة ]` باللون الأزرق.
    - تحديث [app/Exports/ConsultantFinancialMovementsExport.php](file:///c:/wamp64/www/hospital-system/app/Exports/ConsultantFinancialMovementsExport.php) لتضمين فلتر وعمود «نوع الخدمة».
- **الاختبارات الآلية (Automated Tests)**:
  * تحديث واجتياز اختبارات [tests/Feature/ConsultantFinancialMovementsTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ConsultantFinancialMovementsTest.php): `5 passed (23 assertions)`.

## Session log (2026-10-15 — المرحلة 56: تصحيح الحركات المالية للاستشارية وفلتر الضمان والدوام الصباحي/المسائي وعرض النقد الفعلي وتصدير الإكسل Consultant Financial Movements, Actual Paid Copay, Insurance & Shift Filters)

### Done
- **تصحيح عمود المبلغ والبطاقات الإحصائية لواجهة الحركات المالية للاستشارية (`/consultant-availability/financial-movements`)**:
  * معالجة مشكلة عرض كشفية الطبيب الكاملة لمرضى الضمان: كان يظهر 25,000 د.ع بدلاً من ما دفعه المريض فعلياً نقداً بالدرج (مثلاً 2,500 د.ع نسبة التحمل).
  * إضافة دالة ومحددات `patient_paid_amount` و `is_insurance` و `insurance_share_amount` و `insurance_type_label` بنموذج [app/Models/ConsultationRevenue.php](file:///c:/wamp64/www/hospital-system/app/Models/ConsultationRevenue.php).
  * تحديث استعلامات البطاقات الإحصائية العليا (`المستلم نقداً بالصندوق (الدرج)`، `المسترجع نقداً`، `الصافي النقدي الفعلي`) لاحتساب النقد الفعلي المدفوع باستخدام `COALESCE(payments.amount, consultation_revenues.total_amount)`.
  * إبراز المبلغ النقدي الفعلي بخط كبير وواضح مع وسام تفصيلي شفاف تحته يوضح تفاصيل الكشفية وحصة الضمان لمرضى الضمان الصحي وقوى الأمن.
- **إضافة الفلاتر والبحث السريع وفترة الدوام (صباحي ومسائي) وتحديث تصدير Excel**:
  * إضافة فلتر فئة المريض (`الكل` / `نقداً فقط (كاش)` / `مشمول بالضمان`).
  * إضافة فلتر **فترة الدوام** (`shift`):
    - **☀️ الدوام الصباحي (8 ص - 3 م)**: الحركات المسجلة قبل 15:00:00.
    - **🌙 الدوام المسائي (3 م فما بعد)**: الحركات المسجلة من 15:00:00 فما بعد.
  * إضافة وسام ملون وأنيق (Pill Badge) في عمود التاريخ والتوقيت يوضح فترة كل حركة مباشرة.
  * إضافة حقل البحث السريع بالاسم، رقم الهاتف، أو رقم الوصل/الحركة.
  * تحديث [app/Exports/ConsultantFinancialMovementsExport.php](file:///c:/wamp64/www/hospital-system/app/Exports/ConsultantFinancialMovementsExport.php) لتضمين أعمدة (فترة الدوام، المدفوع نقداً، إجمالي الكشفية، تغطية الضمان، ونوع التأمين).
  * حل وتفادي خطأ تعارض أسماء الحقول في الـ SQL (`Column 'paid_at' in where clause is ambiguous`) عند الربط مع جدول `payments` عبر تحديد بادئة الجدول `consultation_revenues.` صراحة.
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء واجتياز اختبار الميزة [tests/Feature/ConsultantFinancialMovementsTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ConsultantFinancialMovementsTest.php): `4 passed (16 assertions)`.

## Session log (2026-10-15 — المرحلة 55: استيراد 7,930 مريض ضمان صحي، التصنيف الذكي للجنس، صف التحرير السريع المدمج وعمود حالة السجل والمزامنة السحابية Insurance Patients Import, Gender AI Classifier, Inline Drawer Quick Edit & Hostinger Sync)

### Done
- **استيراد مرضى الضمان الصحي ومعالجة فئاتهم (Insurance Patients Import)**:
  * تحليل ملف سجلات الخدمات `service_records.csv` (116,474 سجلاً و 8,108 مرضى فريدين).
  * مطابقة ومقارنة الأسماء مع قاعدة البيانات: تحديد 178 مريضاً مسجلاً مسبقاً وتحديث فئات 4 مرضى من الفئة H إلى الفئة A، واستيراد 7,930 مريضاً جديداً بأمان مع ربط حسابات المستخدمين التلقائية.
  * بناء أمر Artisan مخصص [app/Console/Commands/ImportInsurancePatientsCommand.php](file:///c:/wamp64/www/hospital-system/app/Console/Commands/ImportInsurancePatientsCommand.php). ارتفع إجمالي المرضى في المنظومة إلى 8,372 مريضاً.
- **التصنيف الذكي لجنس المرضى (Smart Gender Classifier)**:
  * تطوير أمر ذكاء سريري ولغوي [app/Console/Commands/DetectPatientsGenderCommand.php](file:///c:/wamp64/www/hospital-system/app/Console/Commands/DetectPatientsGenderCommand.php) يعتمد على:
    1. الإجراءات والخدمات السريرية النسائية (الولادات القيصرية والطبيعية، السونار النسائي والرحمي) والرجالية (البروستات ودوالي الخصية) لتصنيف قطعي مؤكد (915 أنثى، 341 ذكراً).
    2. البوادق واللواحق الصرفية العربية (`عبد`، `أم`، `أبو`، `ة`، `ات`، `اء`، `ون`).
    3. المعاجم الاسمية العربية، مع ترك الأسماء المشتركة محايدة لتأكيد موظفي الاستقبال.
  * تصنيف 6,626 مريضاً (بنسبة دقة ونجاح 83.5%).
- **مزامنة قاعدة البيانات السحابية الحصرية على Hostinger (Selective Patients Hostinger Sync)**:
  * تطوير أمر [app/Console/Commands/PushPatientsOnlyToHostingerCommand.php](file:///c:/wamp64/www/hospital-system/app/Console/Commands/PushPatientsOnlyToHostingerCommand.php) لمزامنة جدولي `patients` و `users` فقط دون المساس ببقية الـ 104 جداول على هوستنجر.
  * أخذ نسخة احتياطية سحابية كاملة قبل المزامنة، وتحديث المنظومة على هوستنجر إلى 8,372 مريضاً.
- **إعادة هيكلة واجهة المرضى وعمود حالة السجل وصف التحرير السريع المدمج (Inline Accordion Drawer)**:
  * إضافة عمود مخصص **«حالة السجل»** (العمود رقم 3) يوضح بنظرة واحدة:
    - `[ مكتمل 100% ✓ ]` للأضابير المكتملة.
    - `[ ⚠️ بحاجة إكمال ]` مع أوسمة تفصيلية للنواقص (`هاتف مفقود`، `رقم الضمان مفقود`، `البطاقة الوطنية`).
  * إلغاء النافذة المنبثقة (Modal) نهائياً واستبدالها بـ **«صف تحرير سريع مدمج» (Inline Drawer Row)** يفتح مباشرة أسفل صف المريض عند الضغط على **[ ✏️ إكمال ]**.
  * يتيح للموظف تعبئة الهاتف، الجنس، رقم الضمان، والبطاقة الموحدة، وحفظها مباشرة عبر AJAX (`PATCH /patients/{id}/quick-update`) دون أي تشويش أو تنقل أو إعادة تحميل للصفحة، مع وميض أخضر سلس وتحديث لحظي لبيانات الصف.
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء واجتياز [tests/Feature/PatientCompletionAndQuickUpdateTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PatientCompletionAndQuickUpdateTest.php): `3 passed (12 assertions)`.
  * اجتياز كامل اختبارات المرضى: `19 passed (81 assertions)`.

## Session log (2026-10-15 — المرحلة 54: التقرير الطبي لحالة الطوارئ وتوقيع الطبيب المعتمد والتحقق الرقمي عبر QR Code والطباعة بنسختين Emergency Medical Report, Dr. Mohanad Ismail Signature & Dual-Copy QR Verification)

### Done
- **نظام التقرير الطبي الرسمي لحالات الطوارئ (Emergency Medical Report)**:
  * **الأهداف والمتطلبات**: بناء نموذج تقرير طبي رسمي عالي الدقة (A4 Portrait) لكل حالة طوارئ يطبع مباشرة، متضمناً اسم وتوقيع طبيب الطوارئ المعتمد (د. مهند إسماعيل)، رمز استجابة سريعة (QR Code) للتحقق الإلكتروني، وخيار الطباعة المزدوجة (نسخة المريض + نسخة الإدارة والأرشيف).
  * **التطوير المعماري والبرمجي**:
    1. **التوقيع اليدوي الحقيقي لطبيب الطوارئ (د. مهند إسماعيل)**:
       - استخلاص ومعالجة الصورة المرفوعة لتوقيع د. مهند إسماعيل (`media_1792064825819.jpg`)، عزل الحبر الأزرق مع شفافية قناة ألفا (Alpha Channel) وتوليد الصورة النقية في [public/images/signatures/doctor_244.png](file:///c:/wamp64/www/hospital-system/public/images/signatures/doctor_244.png).
       - إضافة حقل `signature` إلى جدول `doctors` عبر مايجريشن [database/migrations/2026_10_15_160000_add_signature_to_doctors_table.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_15_160000_add_signature_to_doctors_table.php) وتحديث نموذج [app/Models/Doctor.php](file:///c:/wamp64/www/hospital-system/app/Models/Doctor.php).
       - ربط التوقيع بحساب د. مهند إسماعيل (`Doctor #244` / `User #627`).
    2. **قالب التقرير الطبي المتطور والطباعة المزدوجة**:
       - إنشاء استمارة [resources/views/emergency/medical-report.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/medical-report.blade.php):
         * مقاس A4 قياسي مع علامة مائية لشعار المستشفى وإطار مزدوج.
         * تفاصيل المريض (الاسم، العمر، الجنس، رقم الملف/الإضبارة، الهاتف، وقت الدخول والخروج، درجة الفرز الخطورة).
         * العلامات الحيوية عند الاستقبال (الضغط، النبض، الحرارة، التنفس، SpO2، السكر).
         * الشكوى الرئيسية والأعراض والتشخيص السريري المعتمد.
         * جدول التدخلات الطبية والعلاجات والمحاليل الوريدية والخدمات التمريضية.
         * جدول الفحوصات التشخيصية (مختبر الطوارئ والأشعة والتصوير).
         * قرار وتوصيات الخروج ومسار الحالة ومدة الإقامة.
         * خيارات الطباعة المزدوجة: شريط علوي للتبديل اللحظي بين (نسخة المريض فقط / نسخة الإدارة والأرشيف فقط / طباعة النسختين معاً بفاصل صفحات تلقائي).
         * تذييل رسمي: ختم قسم الطوارئ، وتوقيع د. مهند إسماعيل المعتمد، ورمز QR المشفر برابط التحقق المباشر.
    3. **بوابة التحقق الرقمي العام من التقرير (Public Verification Page)**:
       - إنشاء واجهة متوافقة مع الهواتف الذكية [resources/views/emergency/verify-report.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/verify-report.blade.php) تتيح لأي شخص أو جهة مسح رمز الـ QR والتحقق من صحة ومطابقة التقرير السريري مع سجلات المستشفى دون الحاجة لتسجيل الدخول.
       - تسجيل المسار العام `emergency.verify-report` في [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php) واستثناؤه من ميدلوير المصادقة في `EmergencyController::__construct()`.
    4. **أزرار سريعة للوصول للتقرير**:
       - إضافة زر **[ 📄 التقرير الطبي للطوارئ ]** في ترويسة محطة الفحص [resources/views/emergency/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/show.blade.php).
       - إضافة أيقونة طباعة سريعة للتقرير في جدول الطوارئ العام [resources/views/emergency/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/index.blade.php).
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء واجتياز اختبار الميزة [tests/Feature/EmergencyMedicalReportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyMedicalReportTest.php): `3 passed (18 assertions)`.
  * اجتياز كامل اختبارات قسم الطوارئ: `19 passed (102 assertions)`.

## Session log (2026-10-15 — المرحلة 53: تنظيم وتوحيد أزرار الطوارئ وإضافة مسار توثيق تحويل للاستشارية Emergency Actions Consolidation & Referral to Outpatient Discharge)

### Done
- **تنظيم وتوحيد أزرار إجراءات الطوارئ وإنهاء الازدحام والتكرار**:
  * **الأهداف والمتطلبات**: معالجة مشكلة تشتت وتكرار الأزرار في محطة الفحص وجدول الطوارئ، وإضافة خيار توثيقي مخصص لإحالة المريض إلى العيادات الاستشارية.
  * **التطوير البرمجي**:
    1. في ترويسة محطة الفحص [resources/views/emergency/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/show.blade.php):
       - دمج أزرار التحويل والتخريج المتناثرة في قائمة منسدلة موحدة أنيقة **«إجراءات وتحويل وتخريج الحالة ▾»** مقسمة لقسمين: (تحويل الحالة: للعمليات، رقود، تحويل للاستشارية) و (تسجيل الخروج: متعافي، على مسؤوليته).
       - إبقاء زر **[ طلب استشارة 📅 ]** وزر **[ 🖨️ طباعة ]** وزر **[ ↩️ الرجوع للقائمة ]** بارزة في الترويسة.
    2. إضافة نوع تخريج جديد للتوثيق الطبي والسريري **«تحويل للاستشارية»** (`transferred_to_consultation`):
       - إنشاء مايجريشن [database/migrations/2026_10_15_150000_update_discharge_type_in_emergencies_table.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_15_150000_update_discharge_type_in_emergencies_table.php) لتوسيع نوع حقل `discharge_type` إلى `varchar(50)`.
       - تحديث وحدة التحكم [app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php) لدعم `transferred_to_consultation` في التحقق والترجمة وتوثيق وقت الخروج وملاحظات الطبيب.
       - إضافة وسام مميز (Badge باللون الكحلي/الأزرق السماوي) في جدول الطوارئ [resources/views/emergency/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/index.blade.php) ومحطة الفحص.
    3. تبسيط جدول الطوارئ العام [resources/views/emergency/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/index.blade.php):
       - إزالة كافة القوائم المنسدلة والأزرار المتكررة نهائياً من الجدول العام.
       - حصر الجدول في 6 أعمدة أساسية مركزة (#، المريض، الحالة، الطبيب المسؤول، وقت الدخول، والإجراء) مع زر موحد مباشر وسريع **[ 🩺 فتح ملف الكشف ]**، وحصر كافة الإجراءات والتحويلات السريرية داخل المحطة الموثقة.
- **الاختبارات الآلية (Automated Tests)**:
  * تحديث [tests/Feature/EmergencyWorkflowTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyWorkflowTest.php) بإضافة اختبار `test_emergency_discharge_transferred_to_consultation_workflow` بنجاح: `9 passed (39 assertions)`.
  * اجتياز كامل اختبارات الطوارئ: `16 passed (84 assertions)`.

## Session log (2026-10-15 — المرحلة 52: تطوير وتكامل استشارات الطوارئ، السجل الطبي التراكمي، والحماية المالية للخدمات المسددة Emergency Consultation Financial Split, Medical History & Paid Services Protection)

### Done
- **تكامل استشارات الطوارئ المالية واحتساب حصص الطبيب والمستشفى (80% / 20%)**:
  * **الأهداف والمتطلبات**: معالجة وصولات كاشير الطوارئ عند طلب استشارة طبية (50,000 د.ع) بحيث يظهر اسم الطبيب الاستشاري المحول إليه في الوصل والتقرير المالي، مع توزيع الرسوم: 40,000 د.ع للطبيب و 10,000 د.ع للمستشفى.
  * **التطوير البرمجي**:
    1. تحديث [app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php):
       - تطوير دالة `processEmergencyPayment` لاحتساب وتوزيع أتعاب الاستشارة الطارئة تلقائياً: إنشاء سجل `ConsultationRevenue` بحصة 40,000 د.ع للطبيب و10,000 د.ع للمستشفى (80% / 20%)، إنشاء استحقاق `DoctorDue`، وتحديث الرصيد التراكمي في `DoctorFinancialAccount`.
       - إظهار اسم الطبيب الاستشاري واختصاصه وقسمه في نموذج دفع الطوارئ [emergency-payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/emergency-payment-form.blade.php).
    2. تحديث قوالب وصولات الكاشير:
       - [receipt-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt-print.blade.php): طباعة بند «استشارة طبية طوارئ: د. [اسم الاستشاري]» مع التاريخ والمبلغ، وتحديد اسم الطبيب في ترويسة الوصل.
       - [receipt.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt.blade.php): إظهار بنود الاستشارة مع اسم الطبيب في تفاصيل الفاتورة.
- **الحماية المالية للخدمات المسددة في الطوارئ (Paid Services Integrity & Safeguarding)**:
  * **الضوابط المحاسبية والرقابية**: منع حذف أي خدمة تم تسديدها في الكاشير وصدر بها وصل مالي رسمي (`payment_id IS NOT NULL`) مباشرة من واجهة التمريض/الطوارئ دون تسوية واسترجاع مالي في الكاشير.
  * **التطوير البرمجي**:
    1. في [app/Http/Controllers/EmergencyController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyController.php):
       - في `updateMedical`: فحص الخدمات المسددة مسبقاً في `emergency_emergency_service` والحفاظ عليها إجبارياً أثناء مزامنة `sync` حتى لو أرسل المتصفح قائمة بدونها.
       - في `show`: جلب خريطة الخدمات المسددة وأرقام وصولاتها لتمريرها للواجهة.
    2. في [resources/views/emergency/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency/show.blade.php):
       - قفل مربع الاختيار (Disabled) للخدمة المسددة مع وسم أخضر مميز ورقم الوصل `[مسددة - إيصال #...]`.
       - إتاحة إضافة أو إزالة الخدمات غير المسددة بحرية تامة وتحديث فاتورة الكاشير التلقائية.
       - إضافة تنبيه إرشادي بالضوابط المالية وإجراءات الاسترجاع المالي (Refund) عبر الكاشير.
- **محطة عمل الطوارئ المقسمة والسجل الطبي التراكمي (Emergency Split-Screen Workstation & Sticky Medical History)**:
  * **الهدف والتصميم المعماري**: توحيد وتطابق تجربة المستخدم لمحطة عمل الطوارئ مع محطة طبيب الاستشارية بتصميم الشاشة المزدوجة المتزامنة (Split-Screen 2-Column Layout):
    1. **العمود الأيمن (`col-12 col-xl-7 col-lg-7`)**: التبويبات الـ 4 الإجرائية المباشرة:
       - 1. العلامات الحيوية (`vitals-tab`)
       - 2. التشخيص والخدمات التمريضية والطبية (`diagnosis-tab`) مع حماية الخدمات المسددة.
       - 3. طلب المختبر والأشعة (`requests-tab`)
       - 4. علاج وأدوية الطوارئ والوصفة الإلكترونية (`treatment-tab`)
    2. **العمود الأيسر (`col-12 col-xl-5 col-lg-5`)**: اللوحة الدائمة الثابتة (`card sticky-top` على ارتفاع `top: 80px; z-index: 10`) لـ **«السجل الطبي ونتائج الفحوصات»** تظل ظاهرة أمام الطبيب والتمريض طوال وقت الفحص والعلاج، وتحتوي على:
       - زر وصول مباشر: **«السجل الزمني التفاعلي الكامل للمريض ↗»** (`route('doctor.patient.history', $emergency->patient)`).
       - 5 أشرطة تبويب سريعة ومتقدمة:
         1. **طلبات الحالة**: المتابعة اللحظية المباشرة لفحوصات المختبر الحالية (ونتائجها المعتمدة وطباعتها)، طلبات الأشعة الحالية (وتقاريرها)، والعلاجات والمحاليل المعطاة حالياً.
         2. **نتائج التحاليل السابقة**: نتائج المختبر السابقة مع القيم المرجعية ومؤشرات الطبيعي وغير الطبيعي وروابط تقارير أجهزة الفحص المرفقة.
         3. **تقارير الأشعة والتصوير**: المشاهدات (Findings)، الانطباع التشخيصي (Impression)، التوصيات، والصور ومرفقات الـ PDF.
         4. **الزيارات الاستشارية السابقة**: تواريخ الزيارات، الأطباء، الأقسام، الشكوى، التشخيصات، والوصفات الطبية.
         5. **عمليات/طوارئ**: سجل العمليات الجراحية السابقة وحالات الدخول السابقة للطوارئ.
- **الاختبارات الآلية (Automated Tests)**:
  * [tests/Feature/EmergencyConsultationPaymentTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyConsultationPaymentTest.php): `1 passed (19 assertions)`.
  * [tests/Feature/EmergencyMedicalHistoryAndPaidServicesTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyMedicalHistoryAndPaidServicesTest.php): `2 passed (13 assertions)`.
  * تشغيل كافة اختبارات الطوارئ: `15 passed (80 assertions)`.

## Session log (2026-10-14 — المرحلة 51: تطوير وترقية سجل زيارات العيادات والاستشارية وتصدير الإكسل والفلاتر المتقدمة Visits Registry Upgrade & Export)

### Done
- **تطوير وترقية شاملة لسجل زيارات العيادات والاستشارية (`/visits`)**:
  * **الأهداف والمتطلبات**: تلبية طلب الإدارة بتطوير سجل الزيارات العام وتزويده بالفلاتر المتقدمة، الإحصائيات الفورية، وتصدير الإكسل.
  * **التطوير البرمجي**:
    1. إنشاء صنف التصدير [app/Exports/VisitsExport.php](file:///c:/wamp64/www/hospital-system/app/Exports/VisitsExport.php):
       - تصدير كامل البيانات: رقم الزيارة، التاريخ والوقت، اسم المريض وهاتفه وإضبارته، الطبيب المعالج، العيادة/القسم، نوع الزيارة، حالة الزيارة، الشكوى والأعراض، التشخيص، الحاجة لعملية جراحية، وحالة العملية.
       - دعم كامل لاتجاه اليمين لليسار (RTL) وترويسة نيلي ملكي (Indigo Theme: `#1E40AF`).
    2. تسجيل المسار وتحديث وحدة التحكم [VisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/VisitController.php):
       - تسجيل مسار `visits.export`.
       - تطوير دالة `index` لتشمل: البحث السريع (اسم، هاتف، إضبارة)، فلترة الطبيب، فلترة القسم، فلترة حالة الزيارة، فلترة الحاجة لعملية، وفلترة التاريخ من/إلى، مع الحفاظ التام على معلمات الاستعلام في الترقيم (`withQueryString`).
       - احتساب 5 مؤشرات إحصائية KPI حية (إجمالي الزيارات، زيارات اليوم، المكتملة، قيد الفحص، وتحويلات العمليات المعلقة).
    3. إعادة بناء واجهة المستخدم بالكامل [resources/views/visits/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/visits/index.blade.php):
       - بطاقة ترويسة متدرجة أنيقة مع أزرار التصدير وتسجيل الزيارة.
       - 5 بطاقات إحصائية تفاعلية بمؤشرات ألوان ورسوم بيانية.
       - بطاقة فلاتر بحث متقدمة مع زر إعادة التعيين.
       - جدول بيانات حديث وشامل مع أوسمة الحالات، والوصول المباشر للطباعة والعرض والتعديل.
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء واجتياز اختبار الميزة [tests/Feature/VisitsExportAndFiltersTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/VisitsExportAndFiltersTest.php): `3 passed (10 assertions)`.
  * اجتياز كامل للاختبارات الحيوية: 17 اختباراً ناجحاً (63 تأكيداً).

## Session log (2026-10-14 — المرحلة 50: تصدير واستيراد وتحديث أسعار خدمات الطوارئ من الإكسل Bulk Excel Export & Import for Emergency Services)

### Done
- **إضافة نظام التصدير والاستيراد والتحديث الجماعي لخدمات الطوارئ**:
  * **الأهداف والمتطلبات**: تلبية طلب الإدارة بتوفير نفس ميزة التصدير والاستيراد لخدمات وأسعار قسم الطوارئ وضمان الصحة (`emergency-services`).
  * **التطوير البرمجي**:
    1. إنشاء صنف التصدير [app/Exports/EmergencyServicesExport.php](file:///c:/wamp64/www/hospital-system/app/Exports/EmergencyServicesExport.php):
       - تصدير كامل البيانات: المعرف (# ID)، اسم الخدمة، القسم/التصنيف، سعر الكاش (د.ع)، سعر الضمان الصحي (د.ع)، حالة الضمان، حالة التفعيل، وتاريخ التحديث.
       - دعم اتجاه اليمين لليسار (RTL) وترويسة حمراء مميزة للطوارئ (Crimson/Red theme: `#DC2626`).
    2. إنشاء صنف الاستيراد [app/Imports/EmergencyServicesImport.php](file:///c:/wamp64/www/hospital-system/app/Imports/EmergencyServicesImport.php):
       - كشف ديناميكي للأعمدة، مطابقة الخدمات بالمعرف أو الاسم.
       - تحديث سعر الكاش، سعر الضمان الصحي، شمول الضمان، التصنيف، وحالة التفعيل (نشط/معطل).
       - تصفير ضمان الداخلية تلقائياً (`moi_price = 0` و `is_moi_active = false`) وحفظ آمن داخل Transaction.
    3. تسجيل المسارات وتحديث وحدة التحكم [EmergencyServiceController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/EmergencyServiceController.php):
       - مسار `emergency-services.export`
       - مسار `emergency-services.import`
       - التحقق من نوع وحجم الملف (10MB) وتوثيق رسائل فلاش تفصيلية.
    4. تحديث واجهة المستخدم [resources/views/emergency-services/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/emergency-services/index.blade.php):
       - إضافة زر **"تصدير إكسل"** بالأخضر الفاتح مع الحفاظ على الفلاتر.
       - إضافة زر **"استيراد إكسل"** مع نافذة منبثقة (Modal) إرشادية أنيقة ومحمية بالصلاحيات.
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء واجتياز اختبار الميزة [tests/Feature/EmergencyServicesExportImportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/EmergencyServicesExportImportTest.php): `4 passed (13 assertions)`.
  * اجتياز كامل للاختبارات الحيوية: [LabAndRadiologyImportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabAndRadiologyImportTest.php): `5 passed (18 assertions)`.

## Session log (2026-10-14 — المرحلة 49: استيراد وتحديث أسعار تحاليل المختبر وخدمات الأشعة جماعياً من الإكسل Bulk Excel Import for Lab Tests & Radiology Services)

### Done
- **إضافة نظام الاستيراد والتحديث الجماعي الذكي من ملفات Excel و CSV**:
  * **الأهداف والمتطلبات**: تمكين الإدارة من تعديل الأسعار أو شمول الضمان أو حالة التفعيل في ملف الإكسل وإعادة رفعه بضغطة زر واحدة لتحديث قاعدة البيانات تلقائياً.
  * **التطوير البرمجي**:
    1. إنشاء صنف الاستيراد [app/Imports/LabTestsImport.php](file:///c:/wamp64/www/hospital-system/app/Imports/LabTestsImport.php):
       - كشف ديناميكي لعناوين الأعمدة (Dynamic Column Detection) يدعم العربية والإنجليزية أو الفهرس الافتراضي.
       - مطابقة التحاليل حسب المعرف (`ID`) أو الرمز الطبي (`code`) أو الاسم.
       - تنظيف ذكي للأرقام والعملات (إزالة الفواصل ورموز د.ع والمسافات).
       - تحديث سعر الكاش، سعر الضمان، تفعيل الضمان، التصنيف الفرعي، وحالة التفعيل (نشط/معطل).
       - حماية معايير المستشفى بتصفير ضمان الداخلية التام وحفظ البيانات داخل Transaction آمنة.
    2. إنشاء صنف الاستيراد [app/Imports/RadiologyTypesImport.php](file:///c:/wamp64/www/hospital-system/app/Imports/RadiologyTypesImport.php):
       - مطابقة خدمات الأشعة حسب المعرف أو الرمز أو الاسم.
       - تحديث سعر الكاش (`base_price`)، سعر الضمان (`hi_price`)، شمول الضمان، الحاجة لصبغة، وحالة التفعيل.
    3. تسجيل المسارات وتحديث وحدات التحكم:
       - مسار `lab-tests.import` في [LabTestController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabTestController.php)
       - مسار `radiology.types.import` في [RadiologyTypeController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RadiologyTypeController.php)
       - التحقق من نوع الملف وحجمه (أقصى حد 10MB) وتوثيق رسائل نجاح تفصيلية بعدد السجلات المحدثة والمتخطاة.
    4. تحديث واجهات المستخدم بنوافذ منبثقة (Modals) أنيقة ومحمية بالصلاحيات:
       - [resources/views/lab-tests/pricing_settings.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab-tests/pricing_settings.blade.php)
       - [resources/views/lab-tests/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab-tests/index.blade.php)
       - [resources/views/radiology/types/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/radiology/types/index.blade.php)
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء واجتياز اختبار الميزة [tests/Feature/LabAndRadiologyImportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabAndRadiologyImportTest.php): `5 passed (18 assertions)`.
  * اجتياز كامل للاختبارات الحيوية: [LabTestsExportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabTestsExportTest.php): `4 passed (8 assertions)`.

## Session log (2026-10-14 — المرحلة 48: تصدير جداول تحاليل المختبر وخدمات الأشعة إلى ملفات إكسل Excel Export for Lab Tests & Radiology Services)

### Done
- **إضافة تصدير إكسل لقائمة تحاليل المختبر (631 تحليلاً) وقائمة خدمات الأشعة (224 فحصاً)**:
  * **الأهداف والمتطلبات**: تلبية طلب الإدارة بتوفير زر لتصدير جدول الفحوصات المخبرية وجدول أنواع الأشعة إلى ملفات Excel منسقة باحترافية وتدعم الفلترة الحالية والبحث.
  * **التطوير البرمجي**:
    1. إنشاء صنف التصدير [app/Exports/LabTestsExport.php](file:///c:/wamp64/www/hospital-system/app/Exports/LabTestsExport.php):
       - تصدير كامل البيانات الطبية والمالية: المعرف، الاسم الإنجليزي/العربي، الرمز، القسم الرئيسي، التصنيف الفرعي، سعر الكاش، سعر الضمان الصحي، شمول الضمان، عدد الفحوصات الفرعية، وحالة التفعيل.
       - دعم اتجاه اليمين إلى اليسار (RTL)، ترويسة كحلية أنيقة، وحدود وتنسيق عملة عراقي دينار.
    2. إنشاء صنف التصدير [app/Exports/RadiologyTypesExport.php](file:///c:/wamp64/www/hospital-system/app/Exports/RadiologyTypesExport.php):
       - تصدير كامل البيانات: المعرف، الاسم، القسم/الجهاز (مفراس، رنين، سونار، إيكو، أشعة سينية، الخ)، الكود، سعر الكاش، سعر فئة الضمان ب، شمول الضمان، هل يتطلب مادة ملونة، وحالة التفعيل.
       - دعم اتجاه RTL وترويسة زمردية أنيقة (Emerald theme).
    3. تسجيل المسارات وتحديث وحدات التحكم:
       - مسار `lab-tests.export` في [LabTestController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/LabTestController.php)
       - مسار `radiology.types.export` في [RadiologyTypeController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RadiologyTypeController.php)
       - إتاحة الصلاحيات للأدوار المخولة (المسؤول، المحاسب، فني الأشعة، مسؤول المختبر).
    4. تحديث واجهات المستخدم بإضافة أزرار التصدير:
       - واجهة إدارة التحاليل: [resources/views/lab-tests/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab-tests/index.blade.php)
       - واجهة تسعيرة وإعدادات التحاليل: [resources/views/lab-tests/pricing_settings.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab-tests/pricing_settings.blade.php)
       - واجهة أنواع الأشعة: [resources/views/radiology/types/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/radiology/types/index.blade.php)
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء واجتياز اختبار الميزة [tests/Feature/LabTestsExportTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/LabTestsExportTest.php): `4 passed (8 assertions)`.
  * اجتياز كامل للاختبارات الحيوية: [HospitalTreasuryDashboardTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HospitalTreasuryDashboardTest.php): `4 passed (24 assertions)`.

## Session log (2026-10-14 — المرحلة 47: توحيد وتحديد التصنيفات الفرعية لكافة تحاليل المختبر الـ 631 Subcategories Assignment & Categorization Alignment)

### Done
- **تحديد وتوحيد التصنيف الفرعي (`subcategory`) لجميع تحاليل المختبر (631 تحليلاً)**:
  * **السبب والهدف**: كانت الفحوصات الـ 481 المضافة حديثاً تفتقر إلى حقل التصنيف الفرعي (`subcategory = NULL`) مما كان يؤدي إلى ظهورها دون تصنيف أو تحت "تحاليل أخرى" في واجهات فلترة المختبر وقوائم الأطباء السريعة.
  * **التعديل والمعالجة الدقيقة**:
    1. توحيد `main_category = 'المختبر'` لكافة السجلات الـ 631 في جدول `lab_tests`.
    2. تعيين وتصنيف الـ `subcategory` لكل فحص بدقة طبية تامة:
       - `Biochemistry`: 394 تحليلاً
       - `Serology`: 51 تحليلاً
       - `Hormone`: 41 تحليلاً
       - `Histopathology`: 39 تحليلاً
       - `Immunity`: 28 تحليلاً
       - `Microbiology`: 25 تحليلاً
       - `Hematology`: 21 تحليلاً
       - `Infectious disease`: 17 تحليلاً
       - `Virology`: 9 تحاليل
       - `PCR`: 6 تحاليل
       - إجمالي غير المصنف: `0` (صفر).
    3. تحديث مصفوفة الترجمة وعرض المجموعات في [UserLabTestGroupController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/UserLabTestGroupController.php) بإضافة `Histopathology` و `PCR` لعرضها بأسماء عربية أنيقة في محطات الأطباء.
  * **المايجريشن المعتمد**:
    - إنشاء وتشغيل [database/migrations/2026_10_14_220000_assign_subcategories_to_all_lab_tests.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_14_220000_assign_subcategories_to_all_lab_tests.php) بنجاح.
- **الاختبارات الآلية (Automated Tests)**:
  * اجتياز كامل للاختبارات الحيوية: [HospitalTreasuryDashboardTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HospitalTreasuryDashboardTest.php): `4 passed (24 assertions)`.

## Session log (2026-10-14 — المرحلة 46: إضافة وتصنيف الـ 481 تحليلاً مخبرياً الجديدة واعتماد كتالوج المختبر الشامل 631 فحصاً Seed 481 Additional Lab Tests & Full Catalog Unification)

### Done
- **إضافة 481 فحصاً مخبرياً إضافياً جديداً من كتاب الكفاءات ووثيقة الضمان الصحي**:
  * **استكمال قائمة المختبر الكاملة للمستشفى (631 تحليلاً إجمالياً)**:
    1. الحفاظ الصارم على التحاليل القديمة (150 تحليلاً) بأرقامها IDs وارتباطاتها (127 فحصاً فرعياً، 218 قاعدة مراجع طبيعية، 30 بكجاً، و127 قائمة أطباء).
    2. إضافة الفحوصات الإضافية الجديدة من ID رقم 153 فما فوق.
    3. استبعاد أي تكرار مع التحاليل السابقة (Drop Duplicates) عبر مطابقة الرموز والأكواد والأسماء اللاتينية.
  * **التصنيف الطبي الدقيق حسب الأقسام والتخصصات**:
    - كيمياء وفحوصات تخصصية: 358 تحليلاً
    - مناعة وسيرولوجي ودلالات أورام: 43 تحليلاً
    - علم الأمراض والنسيج المرضي والخزعات: 38 تحليلاً
    - فحص الزرع والأحياء المجهرية: 18 تحليلاً
    - أمراض الدم ومصرف الدم: 9 تحاليل
    - هرمونات وغدد صماء: 8 تحاليل
    - فحوصات جزيئية وفيروسات PCR: 6 تحاليل
    - وباقي التحاليل الروتينية الشاملة.
  * **التسعيرة المالية المعتمدة**:
    - أسعار الكاش مطابقة لكتاب الكفاءات الرسمي الصادر 10/10/2026.
    - أسعار الضمان الصحي مطابقة لوثيقة الضمان الصحي 2025.
    - الفحوصات غير المشمولة: كاش فقط 100% (`is_hi_active = 0` و `hi_price = 0`).
    - تصفير ضمان الداخلية التام (`is_moi_active = 0` و `moi_price = 0.00`).
  * **المايجريشن المعتمد**:
    - إنشاء وتشغيل [database/migrations/2026_10_14_210000_seed_additional_481_hospital_lab_tests.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_14_210000_seed_additional_481_hospital_lab_tests.php) بنجاح.
- **الاختبارات الآلية (Automated Tests)**:
  * اجتياز كامل للاختبارات الحيوية: [HospitalTreasuryDashboardTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HospitalTreasuryDashboardTest.php): `4 passed (24 assertions)`.

## Session log (2026-10-14 — المرحلة 45: توحيد وتحديث تسعيرة الـ 150 تحليلاً مخبرياً وتصفير ضمان الداخلية وحماية الفحوصات الفرعية والمعدلات الطبيعية Lab Tests Tariff Update & Association Protection)

### Done
- **تحديث تسعيرة تحاليل المختبر الـ 150 الحالية في قاعدة البيانات في أماكنها الحالية**:
  * **السبب والهدف**: الحفاظ التام والصارم على ارتباطات شبكة المختبر الحيوية دون أي خلل أو حذف لأي ID:
    1. حماية **127 فحصاً فرعياً** في `lab_test_sub_tests` (مثل تفاصيل غازات الدم ABG، صورة الدم CBC 3-Diff و CBC 5-Diff، فحص الإدرار العام GUE، فحص الغائط GSE، وفحص السائل المنوي Semen).
    2. حماية **218 قاعدة للمعدلات والمراجع الطبيعية** في `lab_test_references` (القيم الصغرى والعظمى والوحدات حسب الجنس والعمر لـ 133 تحليلاً).
    3. حماية **30 بكجاً مخبرياً** و **127 قائمة سريعة للأطباء** في محطات الكشف.
  * **التعديل والتحديث المالي الدقيق**:
    - مطابقة أسعار الكاش مع **كتاب الكفاءات الرسمي الصادر 10/10/2026** (صفحات 14 إلى 27).
    - مطابقة أسعار الضمان الصحي مع **وثيقة هيأة الضمان الصحي 2025** (27 صفحة) وتفعيل `is_hi_active = 1` لـ 143 تحليلاً مشمولاً، ووضع الفحوصات الـ 7 غير المشمولة **كاش فقط 100%**.
    - تصفير **ضمان الداخلية بالكامل** (`is_moi_active = 0` و `moi_price = 0.00`) لجميع تحاليل المختبر ولكافة خدمات الأشعة غير المشمولة بالضمان، لحل مشكلة ظهورها كضمان داخلية وجعلها كاش فقط.
  * **المايجريشن المعتمد**:
    - إنشاء وتشغيل [database/migrations/2026_10_14_200000_update_existing_150_lab_tests_tariffs.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_14_200000_update_existing_150_lab_tests_tariffs.php) بنجاح.
- **الاختبارات الآلية (Automated Tests)**:
  * اجتياز كامل للاختبارات الحيوية: [HospitalTreasuryDashboardTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HospitalTreasuryDashboardTest.php): `4 passed (24 assertions)`.

## Session log (2026-10-14 — المرحلة 44: استبعاد التحويلات والطلبات والزيارات الملغاة من مصفوفة إحالات أطباء الخزينة Exclude Cancelled Referrals in Treasury BI)

### Done
- **استبعاد التحويلات والزيارات والمواعيد الملغاة من مصفوفة ذكاء الأعمال وإحالات الأطباء (`HospitalTreasuryController.php`)**:
  * **السبب الجذري**: كان استعلام إحالات الفحوصات التشخيصية (`$referralReqQuery`) يستعلم عن كافة سجلات جدول `requests` المرتبطة بـ `visits` دون فحص حالة الطلب أو الزيارة أو الموعد. وعندما يقوم الطبيب بإلغاء موعد أو زيارة كان قد أصدر لها طلب فحص سابقاً (مثل موعد #80 لد. زياد طارق وطلب الأشعة #37)، كان النظام يستمر بعدّ هذا الطلب الملغى كإحالة ناجحة في تقرير الخزينة.
  * **الحل الجذري**:
    1. إضافة شروط استبعاد صريحة في استعلام تحويلات المختبر والأشعة:
       - `requests.status != 'cancelled'`
       - `visits.status != 'cancelled'`
       - `appointments.status != 'cancelled'`
    2. استبعاد العمليات الجراحية الملغاة من استعلام تحويلات العمليات:
       - `surgeries.status != 'cancelled'`
    3. النتيجة: اختفاء أي إحالات وهمية أو ملغاة، وظهور إحصاءات الإحالات الحقيقية فقط بدقة 100%.
- **الاختبارات الآلية (Automated Tests)**:
  * إضافة واجتياز اختبار آلي جديد `test_cancelled_requests_and_visits_are_excluded_from_doctor_referrals` في [HospitalTreasuryDashboardTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HospitalTreasuryDashboardTest.php): `4 passed (24 assertions)`.

## Session log (2026-10-14 — المرحلة 43: تشخيص وتصفية قيود د. زياد طارق في موعد الضمان الملغى Dr. Ziad Tariq Ibrahim Refund Sanitization)

### Done
- **تشخيص وتصفية أرقام د. زياد طارق ابراهيم اسماعيل (معرف: 193) في تقارير الخزينة ومصفوفة الأطباء**:
  * اكتشاف أن الأرقام المسجلة له في تقرير الخزينة ومصفوفة الأطباء (`إجمالي 22,500 د.ع | حصة الطبيب 12,500 د.ع | حصة المستشفى 10,000 د.ع | رصيد معلق 15,000 د.ع`) ناتجة عن الموعد رقم #80 الملغى بتاريخ `2026-10-13`.
  * الموعد كان كشفية ضمان صحي بقيمة `25,000 د.ع` (دفعة #82، إيراد #62)، وعند إلغائه من قبل الطبيب في ذلك اليوم بالوصل #86 نزل استرجاع جزئي `-2,500 د.ع` فقط (إيراد #66) ولم تُلغَ قيود الإيراد والمستحقات المعلقة نظراً لأن العملية تمت قبل تطبيق التحسينات الصارمة في `Appointment::cancel` و `PaymentObserver`.
  * **إنشاء وتشغيل مايجريشن آلي رسمي [2026_10_14_150000_sanitize_dr_ziad_cancelled_appointment_revenues.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_14_150000_sanitize_dr_ziad_cancelled_appointment_revenues.php)**:
    1. حذف قيود الإيرادات المعلقة (#62 و #66) من جدول `consultation_revenues`.
    2. حذف مستحقات الطبيب المعلقة (#58) من جدول `doctor_dues`.
    3. حذف حركات الصندوق للموعد الملغى (#88 و #92) من جدول `financial_transactions`.
    4. تصفير وإعادة مزامنة رصيد الحساب المالي لد. زياد في `doctor_financial_accounts` ليصبح `0.00 د.ع` تماماً.
  * تطبيق المايجريشن والتنظيف بنجاح 100% على السيرفرين (قاعدة البيانات المحلية `mysql` وقاعدة بيانات هوستنجر السحابية `remote_hostinger`).
- **الاختبارات الآلية (Automated Tests)**:
  * اجتياز كامل للاختبارات الحيوية: [DoctorVisitCancellationRefundTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DoctorVisitCancellationRefundTest.php) و [HospitalTreasuryDashboardTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HospitalTreasuryDashboardTest.php): `5 passed (26 assertions)`.

## Session log (2026-10-14 — المرحلة 42: القائمة الحلقية لسكربت النشر، مايجريشن تصحيح قيود المواعيد، وتصفية قيود د. قمر Saad Deploy Loop & Refund Sanitization)

### Done
- **تطوير سكربت النشر [deploy_hostinger.bat](file:///c:/wamp64/www/hospital-system/deploy_hostinger.bat) وأمر `/deploy`**:
  * تحويل السكربت إلى نمط القائمة الحلقية التفاعلية (Interactive Menu Loop): بعد انتهاء أي عملية نشر أو تنظيف، يعود السكربت تلقائياً للقائمة الرئيسية دون إغلاق النافذة.
  * إضافة أمر وسير عمل `/deploy` في [.agents/workflows/deploy.md](file:///c:/wamp64/www/hospital-system/.agents/workflows/deploy.md) وفي [SESSION_PROMPTS.md](file:///c:/wamp64/www/hospital-system/SESSION_PROMPTS.md).
- **تصحيح قيود استرجاع الكشفية الملغاة وحماية الفحوصات**:
  * في [Appointment.php](file:///c:/wamp64/www/hospital-system/app/Models/Appointment.php) (`cancel`): إذا لم تكن هناك دفعة كشفية مسددة فعلياً، تصبح قيمة الاسترجاع `0` فوراً لمنع خصم أي مبالغ وهمية من الطبيب.
  * إنشاء وتشغيل مايجريشن آلي [2026_10_14_140000_fix_hijacked_appointment_payments_and_refunds.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_10_14_140000_fix_hijacked_appointment_payments_and_refunds.php):
    1. تصحيح قيد الاسترجاع التاريخي #79 من `-6,250` إلى `-2,500 د.ع` لمطابقة كشفية الموعد الأصلية للمريض محمد سهم.
    2. فك ارتباط وصولات المختبر من حقل `payment_id` في 8 مواعيد قديمة وإعادة ربط كشفياتها الحقيقية.
- **تصفية وحذف سجلات د. قمر سعد المالية والتشغيلية (لوكل وأونلاين)**:
  * بناءً على طلب المستخدم، تم حذف الزيارة #27 والموعد #67، وطلبات الأشعة #23 و #24، والوصولات المالية المرتبطة (#67, #69, #70, #79).
  * تصفية وحذف قيود الخزينة (سند الصرف #86، قيدي إيرادات الاستشارية CR #52 و CR #60، ومستحقات الطبيب Due #50 و DFA #3) على كل من قاعدتي البيانات المحلية والسحابية (Hostinger)، فأصبح سجلها المالي والتشغيلي صفراً تماماً.
- **الاختبارات الآلية (Automated Tests)**:
  * اجتياز كامل للاختبارات الحيوية: [DoctorVisitCancellationRefundTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DoctorVisitCancellationRefundTest.php)، [HospitalTreasuryTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HospitalTreasuryTest.php)، و [HospitalTreasuryDashboardTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HospitalTreasuryDashboardTest.php): `10 passed (43 assertions)`.

## Session log (2026-10-14 — المرحلة 41: حظر وإخفاء ميزة سحب قاعدة البيانات على سيرفرات الإنتاج لمنع المسح العرضي وحماية البيانات الحية Block & Isolate Database Pull in Production)

### Done
- **حظر وإخفاء مركز سحب قاعدة البيانات على سيرفرات الإنتاج والسيرفر الحي (Hostinger)**:
  * **السبب الجذري**: على سيرفرات الإنتاج الحية، اتصال `mysql` في إعدادات لارافيل يشير إلى قاعدة بيانات السيرفر الحقيقية. عند تشغيل زر السحب، كانت دالة `pullFromRemote` تقوم بحذف الجداول محلياً (`DROP TABLE`) تمهيداً لنسخها، مما كان يتسبب في تفريغ قاعدة بيانات الإنتاج الحية أو تلفها إذا تم استدعاؤها على بيئة أونلاين.
  * **الحل الجذري**:
    1. في `routes/web.php`: حصر تسجيل مسارات `system/database-sync` بالبيئات المحلية والاختبارية فقط (`app()->environment(['local', 'testing'])`). في بيئة الإنتاج يرجع الطلب `404 Not Found`.
    2. في `resources/views/layouts/app.blade.php`: إخفاء زر "سحب الداتا بيس من هوستنجر" من القائمة الجانبية بالكامل خارج البيئة المحلية.
    3. في `app/Http/Controllers/DatabaseSyncController.php`: إضافة تحقق فوري `abort(404)` لأي محاولة وصول خارج `local/testing`.
    4. في `app/Services/DatabaseSyncService.php`: إضافة قفلين أمنيين قاطعين داخل `pullFromRemote`:
       - رمي استثناء حرج إذا تم استدعاء السحب على بيئة غير محلية.
       - رمي استثناء حرج إذا كان اسم قاعدة البيانات المحلية يطابق اسم قاعدة بيانات هوستنجر (`u175868711_her2020`).
    5. في `app/Console/Commands/PullHostingerDatabaseCommand.php`: قفل أمر التيرمينال `db:pull-hostinger` ومنعه من العمل على بيئة الإنتاج.
- **الاختبارات الآلية (Automated Tests)**:
  * تحديث واجتياز اختبار [DatabaseSyncTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DatabaseSyncTest.php): `5 passed (12 assertions)` يشمل اختبار الحظر التام على بيئة الإنتاج واختبار الحظر عند تطابق أسماء القواعد.

## Session log (2026-10-13 — المرحلة 40: منع استبدال دفعة كشفية الطبيب بدفعات الفحوصات وحل مشكلة الاسترجاع الخاطئ Fix Request Payment Hijacking and Appointment Refund)

### Done
- **تشخيص وحل مشكلة استرجاع مبلغ الإيكو/الأشعة وخصمه من حساب كشفية الطبيب عند إلغاء الحجز/الزيارة**:
  * **السبب الجذري**: في `CashierController::processRequestPayment`، عند تسديد المريض لرسوم فحص طبي (مثل الإيكو أو السونار أو التحاليل) المرتبط بزيارة عيادة استشارية، كان النظام يقوم بتحديث الموعد المرتبط بالزيارة (`Appointment::where('id', $request->visit->appointment_id)->update(['payment_id' => $payment->id])`)، مما يؤدي إلى استبدال `payment_id` الخاص بكشفية الطبيب بدفعة الفحص الطبي، وكذلك تغيير حالة الزيارة إلى `scheduled`. وعند قيام الطبيب بإلغاء الحجز أو الزيارة من محطة الطبيب، كانت دالة `Appointment::cancel()` تبحث عن `$this->payment` وتقوم باسترجاع مبلغ الفحص الطبي (6,250 د.ع) بدلاً من استرجاع كشفية الطبيب الفعلية (2,500 د.ع) وخصم كامل المبلغ من حصة الطبيب في الحركات المالية.
  * **الحل الجذري**:
    1. في `app/Http/Controllers/CashierController.php` (`processRequestPayment`): حظر استبدال `payment_id` الخاص بالموعد لأي كشفية طبيب، وحصر هذا التحديث فقط بحجوزات السونار المباشرة الخالية من كشفية طبيب مستقلة والتي لم يتم تسديدها بعد. ومنع تغيير حالة الزيارة إذا كانت قيد الكشف `in_progress`.
    2. في `app/Models/Appointment.php` (`cancel`): تخصيص استرجاع المبلغ ليقتصر حصراً على دفعة الكشفية الأصلية للموعد (`Payment::where('appointment_id', $this->id)->where('payment_type', 'appointment')->where('amount', '>', 0)`). ومنع إلغاء الطلبات الطبية المسددة لتبقى سارية أو تُسترجع رسمياً عبر الكاشير.
    3. في `app/Http/Controllers/DoctorVisitController.php` و `resources/views/doctors/visits/show.blade.php`: توضيح رسالة التأكيد للمستخدم/الطبيب بأن إلغاء الحجز يسترجع كشفية الطبيب فقط، وإشعار الطبيب في حال وجود فحوصات مسددة بأنها تبقى سارية ولا تُسترجع إلا عبر الكاشير.
    4. تصحيح القيدين التاريخيين المتأثرين بالخلل (المريض محمد سهم على د. قمر سعد، والمريض حسام على د. رولا) وإعادة ضبط مبالغ الاسترجاع إلى كشفية التحمل الصحيحة (2,500 د.ع) وموازنة حصص الأطباء بالكامل.
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء واجتياز اختبار شامل [DoctorVisitCancellationRefundTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DoctorVisitCancellationRefundTest.php): 2 passed (6 assertions).
  * اجتياز كامل للاختبارات الحيوية: 20 passed (216 assertions).

## Session log (2026-10-12 — المرحلة 39: حل مشكلة الخطأ 404 واستدعاء طابور طبيب السونار والأشعة Fix Ultrasound & Radiology Call Next Route 404)

### Done
- **تشخيص وحل مشكلة الخطأ `The route radiology-staff/call-next could not be found` عند استدعاء طبيب السونار**:
  * **السبب الجذري**: في واجهة محطة الأشعة والسونار (`resources/views/radiology-staff/index.blade.php`)، كانت دوال الـ JavaScript (`radiologyCallNext`, `radiologyRecall`, `syncRadiologyQueue`, `radiologySkip`, روابط المعاينة وكتابة التقرير) تستخدم مسارات نسبية مباشرة ذات شرطة مائلة في البداية (`/radiology-staff/...`). عند تشغيل النظام على استضافة في مسار فرعي مثل هوستنجر (`/hearmz`) أو سيرفرات التطوير المحلية (`/hospital-system`)، كان المتصفح يتجاهل المسار الأساسي ويطلب الرابط من جذر الدومين مما يتسبب في خطأ 404 Not Found وإظهار رسالة "The route radiology-staff/call-next could not be found".
  * **الحل الجذري**:
    1. تعريف `radiologyBaseUrl` ديناميكياً باستخدام `{{ url('/') }}` في الواجهة، وربط كافة اتصالات الـ AJAX وطلبات الـ fetch برابط التطبيق المعتمد.
    2. دعم كلا البروتوكولين `['GET', 'POST']` للمسارات الحيوية (`call-next`, `recall`, `skip`, `call`) في `routes/web.php` لمنع رفض الطلبات إذا تغيّر نوع الطلب أو حدثت إعادة توجيه.
    3. إضافة مسارات بديلة (Aliases) تحت بادئة `Route::prefix('radiology')` لضمان عمل الاستدعاء سواء تم الدخول من مسار `radiology` الموحد أو مسار `radiology-staff`.
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء واجتياز اختبار شامل [RadiologyStaffCallNextTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/RadiologyStaffCallNextTest.php): 4 passed (11 assertions) شمل اختبار استدعاء المريض الحقيقي في قسم السونار، اختبار استجابة المسار لـ GET و POST، واختبار المسار البديل.
  * اجتياز كامل لاختبارات الصلاحيات [RolePermissionsMatrixTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/RolePermissionsMatrixTest.php): 11 passed (179 assertions).

## Session log (2026-10-12 — المرحلة 38: نقل شريط التحكم بالمخططات ومؤقت التحديث لأعلى الصفحة وإلغاء أزرار السندات Move Charts & Refresh Toolbar to Header & Remove Voucher Buttons)

### Done
- **نقل شريط التحكم بالمخططات البيانية والتحديث التلقائي لأعلى ترويسة الصفحة (`resources/views/treasury/index.blade.php`)**:
  * نقل أزرار التحكم بالمخططات الأربعة (`#masterChartButtonGroup`: `📊 أعمدة`, `📶 أفقي`, `📈 منحنى`, `🍩 دائري`) إلى ترويسة الصفحة الرئيسية بجانب أزرار الطباعة والتصدير.
  * نقل أداة التحديث التلقائي والتوقيت الذكية (العد التنازلي المباشر، زر الإيقاف والتشغيل، قائمة الفترات الزمنية 15ث/30ث/60ث/120ث، وزر التحديث الفوري) لأعلى الصفحة.
  * حذف أزرار السندات (`+ سند صرف مصروفات` و `+ سند قبض إيراد`) من الترويسة العلوية تلبية لطلب المستخدم.
  * إزالة بطاقة التحكم المكررة من داخل التبويب الأول (`#analytics-tab-pane`)، مما جعل المخططات البيانية تبدأ فوراً في أعلى مساحة العرض دون حجز مساحات إضافية.
- **الاختبارات الآلية (Automated Tests)**:
  * اجتياز كامل لاختبارات لوحة الخزينة [HospitalTreasuryDashboardTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HospitalTreasuryDashboardTest.php): 3 passed (20 assertions).

## Session log (2026-10-12 — المرحلة 37: توحيد أزرار التحكم بالمخططات، التبديل الفوري لكامل الصفحة، ونظام التحديث التلقائي الذكي Unified Master Charts & Smart Auto-Refresh Engine)

### Done
- **توحيد أزرار التحكم بالمخططات البيانية وإزالة الأزرار المتكررة من كل بطاقة (`resources/views/treasury/index.blade.php`)**:
  * حذف مجموعات الأزرار المستقلة داخل ترويسة كل بطاقة (`#deptChartButtonGroup` و `#trendChartButtonGroup`) التي كانت تشتت المستخدم وتسبب تكراراً غير مبرر.
  * اعتماد **شريط تحكم موحد ورئيسي واحد** (`#masterChartButtonGroup`) أعلى تبويب التحليل المالي يتحكم في كافة رسوم الصفحة بشكل متزامن.
  * إضافة شارة أنيقة في ترويسة كل بطاقة (`deptChartModeBadge` و `trendChartModeBadge`) توضح النمط النشط حالياً بوضوح.
- **التبديل الفوري والمتزامن لكافة مخططات الصفحة (Instant Synchronous Multi-Chart Switcher)**:
  * حل مشكلة عدم تغير نوع المخطط في Chart.js v4 من خلال إعادة بناء وتدمير النسخ السابقة برمجياً (`chart.destroy()` ثم إعادة الإنشاء فورا)، مما يضمن استجابة لحظية 100% دون أي تأخير أو أخطاء.
  * دعم الأنماط الأربعة لكلا المخططين معاً:
    1. `📊 أعمدة رأسية`: أعمدة عمودية لكلا المخططين مع كتابة الأرقام أعلى كل عمود.
    2. `📶 أعمدة أفقية`: تحويل كلا المخططين لأعمدة أفقية مع كتابة المبالغ على يمين الأعمدة.
    3. `📈 منحنيات مساحية`: تحويل كلا المخططين إلى منحنيات خطية ومساحية انسيابية شفافة.
    4. `🍩 دائري ونسب`: تحويل كلا المخططين إلى مخططات دائرية (مخطط دائري لحصص الأقسام ومخطط دائري لمقارنة إجمالي الوارد والمنصرف مع الصافي في المركز).
- **نظام التحديث التلقائي الذكي للصفحة (Intelligent Auto-Refresh Engine)**:
  * إضافة أداة تحكم بالتحديث التلقائي في شريط التحكم الرئيسي وفي ترويسة الصفحة العامة:
    - عد تنازلي مباشر وثوانٍ حية (`30ث` افتراضياً).
    - نبض لوني أخضر للدلالة على فاعلية التحديث اللحظي.
    - زر إيقاف مؤقت واستئناف `[ ⏸️ نشط / ▶️ تشغيل ]`.
    - قائمة منسدلة لاختيار التردد الزمني: `[ ⚡ 15ث | ⏱️ 30ث (افتراضي) | 🕐 60ث (دقيقة) | ⏳ دقيقتين | 🛑 إيقاف ]`.
    - زر «تحديث الآن» لتنشيط البيانات يدوياً وفوراً مع أيقونة دوران.
  * حماية العمليات النشطة: التحديث التلقائي يتوقف ويؤجل نفسه تلقائياً إذا كانت هناك نافذة منبثقة مفتوحة (Modal) لعدم إضاعة إدخال البيانات على المستخدم.
  * الحفاظ على التبويب النشط: يتم حفظ الـ Hash (`#analytics`, `#departments`, `#ledger`, `#doctors`) قبل التحديث لضمان بقاء المستخدم في نفس الصفحة دون تغيير.
- **الاختبارات الآلية (Automated Tests)**:
  * اجتياز كامل لاختبارات لوحة الخزينة [HospitalTreasuryDashboardTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HospitalTreasuryDashboardTest.php): 3 passed (20 assertions).
  * اجتياز كامل لاختبارات الصلاحيات [RolePermissionsMatrixTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/RolePermissionsMatrixTest.php): 11 passed (179 assertions).

## Session log (2026-10-12 — المرحلة 36: إضافة شريط التحكم الشامل بأنماط المخططات البيانية Master Chart Controller & Dynamic Multi-Chart Engine)

### Done
- **إضافة شريط التحكم الشامل للمخططات البيانية (Master Chart Controller) في تبويب التحليل المالي (`resources/views/treasury/index.blade.php`)**:
  * **شريط تحكم رئيسي وموحد أعلى التبويب (`#masterChartButtonGroup`)**:
    - يوفر 4 أنماط بيانية متقدمة يمكن التبديل بينها بضغطة زر واحدة لتتحكم في كامل الصفحة بشكل متزامن:
      1. `📊 أعمدة رأسية (Vertical Bar / Clustered)`: أعمدة قائمة مع كتابة الأرقام والمبالغ فوق كل عمود مباشرة.
      2. `📶 أعمدة أفقية (Horizontal Bar)`: تحويل الأعمدة إلى مسارات أفقية مع كتابة المبالغ بجانب كل مسار (مناسب للمقارنة السريعة للأقسام).
      3. `📈 منحنيات مساحية (Area / Line Curve)`: تحويل المخططات إلى منحنيات انسيابية ملونة ذات شفافية مساحية لمراقبة الذروات والسيولة.
      4. `🍩 دائري وحصص (Donut / Pie)`: عرض التوزيع النسبي والدائري مع إجمالي الوارد في المركز ودليل الألوان.
  * **التحكم الفردي في كل بطاقة (Per-Card Independent Switchers)**:
    - بطاقة إيرادات الأقسام: أزرار تحكم مستقلة `[ 📊 أعمدة | 📶 أفقي | 📈 منحنى | 🍩 دائري ]`.
    - بطاقة حركة النقدية اليومية: أزرار تحكم مستقلة `[ 📊 أعمدة | 📶 أفقي | 📈 منحنى ]`.
  * **استجابة البيانات والأرقام والتسميات (Smart Data Labels)**:
    - تم تكييف بلجن الأرقام المباشرة (`barLabelsPlugin` و `deptBarDataLabels`) ليتعامل بذكاء مع الاتجاهين: يكتب الأرقام أعلى الأعمدة في الوضع الرأسي، ويكتبها على يمين الأعمدة في الوضع الأفقي.
  * **حفظ التفضيل التلقائي في المتصفح (Persistent Memory)**:
    - حفظ النمط المختار في `localStorage` (`treasury_chart_mode`) واستعادته تلقائياً عند إعادة تحميل الصفحة أو العودة إليها لاحقاً.
- **الاختبارات الآلية (Automated Tests)**:
  * اجتياز كامل لاختبارات لوحة الخزينة [HospitalTreasuryDashboardTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HospitalTreasuryDashboardTest.php): 3 passed (20 assertions).
  * اجتياز كامل لاختبارات الصلاحيات [RolePermissionsMatrixTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/RolePermissionsMatrixTest.php): 11 passed (179 assertions).

## Session log (2026-10-12 — المرحلة 35: إظهار الأرقام والمبالغ مباشرة على المخططات البيانية في الخزينة Display Direct Data Labels on Treasury Charts)

### Done
- **إظهار الأرقام والمبالغ بشكل مباشر ودائم في تبويب المخططات البيانية (`resources/views/treasury/index.blade.php`)**:
  * **مخطط الأعمدة البيانية التفصيلي للأقسام (Department Revenues Bar Chart - مطابق لطلب وصورة المستخدم)**:
    - تحويل عرض إيرادات الأقسام افتراضياً إلى مخطط أعمدة رأسية (Clustered/Vertical Bar Chart) مماثل لنموذج الصورة المرسلة.
    - إضافة بلجن (`deptBarDataLabels`) يرسم الأرقام والمبالغ الدقيقة بالدينار العراقي مباشرة وبخط عريض داكن فوق قمة كل عمود (مثال: `422,500 د.ع` للاستشارية و `185,000 د.ع` للمختبر).
    - كتابة اسم القسم بوضوح أسفل كل عمود على المحور الأفقي مع تلوين العمود باللون المخصص للقسم.
    - إضافة أزرار تبديل تفاعلية في ترويسة البطاقة `[ 📊 أعمدة | 🍩 دائري ]` تتيح للمستخدم التبديل الفوري بين مخطط الأعمدة والمخطط الدائري بسلاسة، مع تصحيح ربط الدالة `switchDeptChart` وإزالة التكرار في تعريف الثوابت واستدعاء `resize()` للمخطط عند التبديل.
  * **المخطط الدائري البديل (Donut Chart)**:
    - الحفاظ على المخطط الدائري كخيار بديل مع دليل الألوان الكامل (Legend) والأرقام في قلب الدائرة.
    - بطاقات تفصيلية للأقسام أسفل المخطط تعرض: لون القسم، الأيقونة، الاسم، عدد العمليات والوصولات، والمبلغ بالدينار العراقي، وشارة النسبة المئوية.
  * **مخطط حركة النقدية اليومية (Daily Cash Flow Bar Chart)**:
    - إضافة بلجن ملصقات البيانات المباشرة (`barLabelsPlugin`) عبر خطاف `afterDatasetsDraw` يرسم المبالغ الرقمية مباشرة فوق كل عمود (باللون الأخضر للوارد (+) وباللون الأحمر للمنصرف (-)) مع تنسيق ذكي ومختصر للمبالغ الكبيرة (K للألوف و M للملايين).
    - إضافة حاشية علوية للمخطط (`layout.padding.top: 24`) لمنع قص الأرقام عند قمة الرسم.
    - إظهار إجمالي الوارد والمنصرف مباشرة في شارة ترويسة بطاقة المنحنى.
  * **توضيح أسباب عدم ظهور الأرقام سابقاً**:
    1. السلوك الافتراضي لمكتبة Chart.js يقتصر على إظهار الأرقام عند تمرير الفأرة فقط (Tooltip on Hover) ما لم يتم تفعيل Data Labels مخصصة.
    2. عامل الفلتر الزمني: معظم الحركات الحالية في قاعدة البيانات مسجلة بتاريخ أمس (2026-10-11)، فإذا كان الفلتر محدداً على "اليوم" (2026-10-12) ولم تكن هناك عمليات مسجلة لليوم بعد، تظهر المخططات فارغة أو بأعمدة صفرية حتى يتم اختيار فترة أوسع كـ "أمس" أو "هذا الأسبوع" أو "كل الوقت".
- **الاختبارات الآلية (Automated Tests)**:
  * اجتياز كامل لاختبارات لوحة الخزينة [HospitalTreasuryDashboardTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HospitalTreasuryDashboardTest.php): 3 passed (20 assertions).
  * اجتياز كامل لاختبارات الصلاحيات [RolePermissionsMatrixTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/RolePermissionsMatrixTest.php): 11 passed (179 assertions).

## Session log (2026-10-12 — المرحلة 34: إضافة تبويب ذكاء الأعمال لأطباء الاستشارية ومصفوفة الإحالات والتحويلات Doctor BI & Referrals Matrix)

### Done
- **إضافة التبويب الرابع المالي والتحليلي في شاشة الخزينة المركزية (`resources/views/treasury/index.blade.php`, `app/Http/Controllers/HospitalTreasuryController.php`)**:
  * **التبويب 4 (تحليل أطباء الاستشارية والتحويلات - Doctor BI & Referrals Matrix)**:
    - بطاقات مؤشرات الأداء السريعة (KPI Cards):
      1. إجمالي الكشوفات المسجلة مفصلة حسب: **نقد / كاش** مقابل **ضمان صحي (HI/MOI)**.
      2. إجمالي الدخل المحصل، حصص الأطباء المصروفة أو المستحقة، وصافي عائد المستشفى بعد خصم الأجور.
      3. إجمالي الإحالات والخدمات الصادرة مع تفصيل فحوصات المختبر 🧪، فحوصات الأشعة والسونار والإيكو 🩻، والعمليات الجراحية 🏥.
    - جدول تحليلي ومصفوفة تفصيلية على مستوى كل طبيب:
      - اسم الطبيب والعيادة التخصصية.
      - عدد كشوفات الكاش 💵.
      - عدد كشوفات الضمان 💳.
      - إجمالي الكشوفات.
      - **مقبوض كاش 💵**: المبالغ المحصلة نقداً في الدرج من كشوفات الكاش الكاملة.
      - **تحمل المريض (المواطن) 💳**: المبالغ المحصلة نقداً في الدرج كنسبة تحمل (10%) من مرضى الضمان.
      - **نقد الدرج 💰**: مجموع النقد الفعلي المحصل في الصندوق (مقبوض كاش + تحمل المريض).
      - **مطالبة الضمان 📑**: المبالغ المستحقة كذمم مدينة ومطالبات على هيئة/شركة الضمان الصحي.
      - **إجمالي القيمة (Gross)**: مجموع النشاط والقيمة التعاقدية الكاملة للخدمات.
      - مستحقات وحصص الطبيب.
      - صافي عائد المستشفى (Net).
      - تحويلات المختبر ومصرف الدم الصادرة من زيارات الطبيب (`requests.type in ('lab', 'blood_bank')`).
      - تحويلات الأشعة والسونار والإيكو الصادرة من زيارات الطبيب (`requests.type = 'radiology'`).
      - إحالات العمليات الجراحية المجدولة للطبيب (`surgeries.doctor_id`).
      - المجموع الكلي للتحويلات الصادرة مع شارة إجمالية بارزة.
    - سطر المجموع العام النهائي لكافة أطباء المستشفى.
  * **الربط التفاعلي والتنقل المباشر من جدول الأقسام (Tab 2)**:
    - في سطر العيادات الاستشارية بجدول الأقسام: إضافة زر «🩺 تحليل الأطباء» ينقل المحاسب أو المدير فوراً إلى تبويب تفاصيل الأطباء والتحويلات بضغطة زر واحدة.
    - دعم كامل للذاكرة اللحظية للرابط (`#doctors` Hash) والتوافق التام مع الطباعة والفرز.
  * **مطابقة القيود المحاسبية بنسبة 100% وتفصيل النقد الفعلي (Financial Consistency)**:
    - توحيد مصدر احتساب كشوفات الكاش والضمان ليكون مباشرة من سجل الإيرادات المقبوضة `consultation_revenues` بدلاً من جدول المواعيد غير المسددة، مما يضمن تطابقاً تاماً مع جدول الحركات المالية ومبالغ المقبوض الفعلي واستبعاد الحجوزات المعلقة أو غير المدفوعة (`pending payment`).
    - التمييز الدقيق بين **نقد الدرج المحصل فعلياً من المواطنين** (كاش كامل + نسبة تحمل 10%) وبين **مطالبات وذمم هيئة الضمان الصحي** التي تُطلب لاحقاً.
- **الاختبارات الآلية (Automated Tests)**:
  * تحديث واجتياز اختبار الميزة [HospitalTreasuryDashboardTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HospitalTreasuryDashboardTest.php): 3 passed (19 assertions).
  * اجتياز كامل لاختبارات الصلاحيات [RolePermissionsMatrixTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/RolePermissionsMatrixTest.php): 11 passed (179 assertions).

## Session log (2026-10-12 — المرحلة 33: تطوير واجهة الخزينة العامة الموحدة بالتبويبات الثلاثة وإيرادات الأقسام والرسوم البيانية Modernize Hospital Treasury with 3 Tabs, Department Breakdown & Charts)

### Done
- **إعادة هيكلة وتطوير شاشة الخزينة المركزية بالكامل بنظام التبويبات الثلاثة (`resources/views/treasury/index.blade.php`, `app/Http/Controllers/HospitalTreasuryController.php`)**:
  * **التبويب 1 (الرسوم البيانية والتحليل المالي)**:
    - مخطط دائري تفاعلي (Donut Chart via Chart.js) يوضح نسبة مساهمة كل قسم في إجمالي دخل المستشفى مع أسطورة تفاعلية بالألوان.
    - مخطط بياني مساحي/أعمدة (Daily Cash Flow Bar Chart) يقارن حركة الوارد (+) مقابل المنصرف (-) يومياً لتحديد اتجاهات السيولة وأيام الذروة.
    - بطاقة توزيع طرق التحصيل المالي (النقد في الدرج Cash مقابل الدفع الإلكتروني والبطاقات Card/ZainCash).
    - بطاقة تحليل المصروفات (صرفيات الأطباء، فواتير المشتريات، والمصاريف التشغيلية).
  * **التبويب 2 (جدول إيرادات ومصروفات الأقسام التفصيلي)**:
    - جدول تحليلي ومصفوفة معيارية (`$departmentsConfig`) تغطي كافة الأقسام (الاستشارية، الأشعة والإيكو، المختبر، العمليات والرقود، الطوارئ، الصيدلية، الإيرادات العامة).
    - عرض عدد الحالات، إجمالي المقبوض (Gross)، التكاليف وحصص الأطباء، صافي عائد المستشفى (Net)، وشريط تقدم ملون (Progress Bar) يوضح النسبة المئوية من الدخل العام.
    - سطر المجموع العام النهائي للمستشفى.
    - أزرار تصفية فورية لنقل المستخدم مباشرة إلى قيود هذا القسم في تبويب الوصولات.
  * **التبويب 3 (سجل القيود والوصولات التفصيلية)**:
    - الجدول المحاسبي للقيود وسندات الصرف والقبض مع أرقام السندات والبيان وحالة القفل للإيصالات الرسمية، والترقيم والتصفح.
  * **شريط الفلاتر السريعة الموحد**:
    - أزرار فترات زمنية سريعة بضغطة زر: `[ اليوم | أمس | هذا الأسبوع | هذا الشهر | كل الوقت ]` مع حفظ التبويب النشط في الـ URL Hash.
  * **بنية معيارية قابلة للتوسع الفوري (Modular Extensible Architecture)**:
    - صُممت مصفوفة الأقسام بحيث يمكن إضافة أي قسم أو مركز ربحي جديد مستقبلاً في سطر برمجي واحد دون الحاجة لإعادة كتابة كود الواجهة.
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء واجتياز اختبار الميزة [HospitalTreasuryDashboardTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/HospitalTreasuryDashboardTest.php): 2 passed (9 assertions).
  * اجتياز كامل لاختبارات الصلاحيات [RolePermissionsMatrixTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/RolePermissionsMatrixTest.php): 11 passed (179 assertions).

## Session log (2026-10-12 — المرحلة 32: حل مشكلة الخطأ 403 عند فتح صور ومرفقات الأشعة والإيكو أونلاين Fix Storage File Access 403 Forbidden)

### Done
- **اكتشاف وتشخيص السبب الجذري للخطأ 403 أونلاين**:
  * في لارافيل 12، تم تفعيل خاصية `'serve' => true` افتراضياً للقرص الخاص `local` داخل `config/filesystems.php`.
  * بسبب غياب حقل `url` للقرص `local` ووجود النظام في مسار فرعي على هوستنجر (`/hearmz`)، قام لارافيل بحجز المسار العام `/storage/{path}` للقرص الخاص المشفر، مما جعل النظام يطلب توقيعاً رقمياً خاصاً (`hasValidSignature`) ويرفض فتح أي رابط عادي لصور ومرفقات الأشعة والمختبر برمز `403 Forbidden`.
  * غياب ارتباط Symlink أو قيود `FollowSymLinks` في استضافات أباتشي المشتركة زاد من تعذر القراءة المباشرة من نظام الملفات.
- **الحل الجذري والآمن**:
  * ضبط قرص التخزين الخاص في `config/filesystems.php` ليصبح `'serve' => false` لمنع احتكار مسار `/storage/`.
  * إضافة مسار برميجي مخصص وعالي الكفاءة في [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php): `Route::get('/storage/{path}')` يقوم بقراءة ملفات التخزين العام (`storage/app/public/`) وإعادتها كاستجابة ملف نظامية (`BinaryFileResponse`) مع نوع المحتوى المباشر وتأمين كامل ضد ثغرات التخطي (`Directory Traversal Guard: str_contains($path, '..')`).
- **الاختبارات الآلية (Automated Tests)**:
  * إنشاء واجتياز اختبار الميزة [StorageFileAccessTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/StorageFileAccessTest.php): 3 passed (5 assertions).
  * اجتياز كامل لكافة اختبارات الصلاحيات [RolePermissionsMatrixTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/RolePermissionsMatrixTest.php): 11 passed (179 assertions).

## Session log (2026-10-12 — المرحلة 31: استعادة ورفع أحدث قاعدة بيانات من eva.sql بالكامل إلى هوستنجر Restore Latest Eva Database to Hostinger)

### Done
- **استيراد ودمج أحدث نسخة احتياطية (`eva.sql`) محلياً**:
  * استيراد ملف `eva.sql` (بحجم 15.1 MB وتاريخ اليوم 9:27 ص) بنجاح بعد تجاوز قيود المفاتيح الأجنبية.
  * تضمين كافة السجلات الحديثة التي تم إدخالها اليوم صباحاً.
- **بناء محرك الرفع والاستعادة العكسي ودفع القاعدة بالكامل إلى هوستنجر (`db:push-hostinger`)**:
  * إضافة دالة `DatabaseSyncService::pushToRemote` وأمر Artisan `php artisan db:push-hostinger {--force}`.
  * رفع ودفع كافة الجداول (106 جدول) و **85,938 سجلاً** بنجاح إلى سيرفر هوستنجر الحي.
- **التحقق والتأكيد المباشر على سيرفر هوستنجر الحي (Verification)**:
  * مطابقة العدادات الحية على هوستنجر:
    - **المرضى**: **240 مريضاً** (تضم مرضى اليوم بالكامل).
    - **المستخدمين**: **417 مستخدماً**.
    - **المواعيد**: **47 موعداً**.
    - **الوصولات المالية**: **40 وصلاً**.
  * رفع التعديلات مباشرة على فرع `main`.


## Historical Session Logs Archive (أرشيف الجلسات السابقة)
تمت أرشفة سجلات الجلسات والمراحل من 1 إلى 30 في الملف المنفصل: [docs/SESSION_ARCHIVE.md](docs/SESSION_ARCHIVE.md) لتسريع معالجة الوكلاء والذاكرة.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.2.12
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- laravel-echo (ECHO) - v2
- tailwindcss (TAILWINDCSS) - v4

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.
<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |
</laravel-boost-guidelines>
