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
- When working on frontend or realtime behavior, inspect `vite.config.js`, `resources/`, and `package.json` scripts.
- **CRITICAL GIT RULE (قاعدة Git المعتمدة)**: الرفع المباشر (`git push`) يتم حصراً على الفرع الرئيسي `main` دون إنشاء فروع جانبية.


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
