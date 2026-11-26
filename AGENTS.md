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
- `docs/HOSPITAL_BRANDING_ROADMAP.md` — Comprehensive blueprint for dynamic hospital identity, logo upload, and official print header.
- `docs/PATIENT_NAME_AUTO_TRANSLITERATION_ROADMAP.md` — Comprehensive blueprint for automated patient name transliteration (Arabic to English) offline.
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

## Session log (2026-11-26 — المرحلة 307: حل بطء وتعليق الانتقال إلى شاشة حجز العمليات الجراحية من 10 ثوانٍ إلى 0.1 ثانية وإلغاء تحميل 10 آلاف مريض في الذاكرة مع تفعيل البحث اللحظي بالـ AJAX Surgery Booking Transition Lag Fix: Eliminate 10K Patients In-Memory Saturation & Enable Instant AJAX Select2)

### Done
- **تشخيص السبب الجذري للبطء الشديد (Root Cause Discovery)**:
  * كشفت القياسات الدقيقة أن دالة `SurgeryController::create()` كانت تنفذ استعلام `Patient::with('user')->get()->sortBy(...)` الذي يسحب **10,368 مريضاً** و 10,368 مستخدماً دفعة واحدة ويولد أكثر من 20,700 كائن Eloquent في الذاكرة.
  * قيام قالب Blade في `surgeries/create.blade.php` بالدوران 10,368 مرة وتوليد مسارات `route('inquiry.patients.history')` و `route('inquiry.patients.dossier.print')` وترميز JSON لكل مريض داخل السمة `data-patient` مما كان يُنتج صفحة HTML بحجم ضخم يتجاوز **4 ميغابايت** ويستغرق معالجتها في السيرفر وحده 5.8 ثوانٍ، تليها 3 إلى 5 ثوانٍ أخرى في المتصفح لبناء عناصر مكتبة Select2 على 10 آلاف خيار، مما سبب تعليق التحميل (اللودنك) لأكثر من 10 ثوانٍ.
- **المعالجة الجذرية والتحسين الفوري ([app/Http/Controllers/SurgeryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgeryController.php), [app/Http/Controllers/BedReservationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/BedReservationController.php))**:
  * قصر قائمة المرضى في `SurgeryController::create()` على المريض المختار حصراً `$selectedPatient` عند التحويل من الاستعلامات أو تمرير `patient_id`، أو جلب أحدث 25 مريضاً فقط كعينة أولية سريعة بدلاً من 10 آلاف مريض.
  * تطبيق نفس التحسين على دالتي `SurgeryController::edit()` و `SurgeryController::show()` ودالة `BedReservationController::create()` لحجز الرقود والأسرة لمنع تكرار البطء في أي محطة.
- **تفعيل البحث اللحظي بالـ AJAX في Select2 ([resources/views/surgeries/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/create.blade.php), [resources/views/surgeries/edit.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/edit.blade.php))**:
  * ربط حقل اختيار المريض `#patient_id` بنقطة نهاية البحث اللحظي السريع `route('inquiry.search.patients')` مع تأخير ذكي (debounce 250ms) والبحث بالاسم أو الهاتف أو رقم الملف أو الهوية الوطنية.
  * ربط حدث `select2:select` لتحديث بيانات `data-patient` فورياً ومزامنة شريط هوية المريض العلوي (Executive Banner) واللوحة الجانبية الثابتة (Sticky Summary) ومسار التغطية فور اختيار أي مريض جديد دون أي بطء.
- **النتيجة والقياسات والاختبارات ([tests/Feature/SurgeryCreateModernWorkspaceTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryCreateModernWorkspaceTest.php), [tests/Feature/SurgeryTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryTest.php))**:
  * انخفاض زمن فتح شاشة حجز العملية الجراحية من **10 ثوانٍ** إلى **0.2 ثانية فقط (206 ميلي ثانية)** أي تسريع بمقدار **50 ضعفاً**.
  * اجتياز اختبارات العمليات [SurgeryCreateModernWorkspaceTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryCreateModernWorkspaceTest.php) (1 passed, 20 assertions) واختبارات [SurgeryTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryTest.php) (9 passed, 174 assertions) واختبارات الاستعلامات (5 passed, 27 assertions) وكاش القوالب `view:cache` وتنسيق Pint.

## Session log (2026-11-26 — المرحلة 306: اختصار دورة الاستعلامات بالكامل إلى شاشة واحدة مباشرة مع قفل الخدمات لحين تحديد المريض وبحث فوري في الأعلى وتوجيه الموظف إليها فور تسجيل الدخول Instant Single-Page Inquiry Direct Booking, Strict Patient-First Services Lock & Direct Login Redirection)

### Done
- **توجيه موظف الاستعلامات والاستقبال مباشرة إلى شاشة الاستعلامات فور تسجيل الدخول ([app/Http/Controllers/Auth/LoginController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Auth/LoginController.php), [app/Http/Controllers/DashboardController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DashboardController.php), [app/Models/User.php](file:///c:/wamp64/www/hospital-system/app/Models/User.php), [bootstrap/app.php](file:///c:/wamp64/www/hospital-system/bootstrap/app.php), [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
  * إضافة دالة `isInquiryStaff()` في موديل `User` لاكتشاف وتدقيق موظفي الاستعلامات والاستقبال بالدور (`inquiry_staff`, `receptionist`, `consultation_receptionist`، وأسماء الأدوار العربية) أو القسم أو الصلاحيات.
  * توجيه موظف الاستعلامات في `LoginController::redirectTo()` فور تسجيل الدخول مباشرة إلى شاشة الاستعلامات `route('inquiry.create')`.
  * حماية المسارات بتحويل موظف الاستعلامات عند زيارة لوحة التحكم `/dashboard` أو الصفحة الرئيسية `/` أو عند اعتراض وسيط `RedirectIfAuthenticated` في `bootstrap/app.php` مباشرة إلى `route('inquiry.create')`.
- **إلغاء التنقل والقفزات بين الصفحات واختصار دورة الاستعلامات بالكامل ([app/Http/Controllers/InquiryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/InquiryController.php), [resources/views/inquiry/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/inquiry/create.blade.php), [resources/views/layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php))**:
  * إلغاء السلسلة الطويلة السابقة (قائمة جانبية ⬅️ جدول الإحالات ⬅️ النقر على طلب جديد ⬅️ صفحة بحث منفصلة ⬅️ اختيار المريض ⬅️ صفحة الحجز).
  * توجيه رابط «الاستعلامات (طلب جديد)» في القائمة الجانبية مباشرة إلى نموذج الحجز الموحد `route('inquiry.create')` مع تخصيص رابط «لوحة التحويلات والإحالات» `route('inquiry.index')` لمن يحتاج متابعة تحويلات الطوارئ والرقود.
  * وضع شريط البحث اللحظي الفوري في أعلى الصفحة دائماً بشكل بارز ومركزي مع زر تسجيل مريض جديد.
  * وضع كامل الخدمات والعيادات الطبية المباشرة (كشف طبي، تحاليل، أشعة، صيدلية، مصرف دم، تحويلات الأقسام التخصصية) مباشرة تحت شريط البحث في نفس الشاشة الواحدة.
- **قفل وحظر اختيار أي خدمة أو تحويل نهائياً لحين تحديد المريض أولاً (Strict Patient-First Services Lock)**:
  * جعل كافة بطاقات الخدمات والتحويلات غير قابلة للاختيار تماماً (`pointer-events: none`, `opacity: 0.45`, `grayscale`) مع ظهور تنبيه قفل بارز `#servicesLockedNotice` وشريط إرشادي بانتظار تحديد المريض.
  * حظر دالة `toggleRequestType` ودالة الروابط التخصصية برمجياً مع إرجاع تنبيه `⛔ الخدمات مقفلة لحين تحديد المريض` ونقل التركيز فورياً لشريط البحث في حال محاولة النقر قبل اختيار المريض.
  * بمجرد اختيار المريض من نتائج البحث يتم فك القفل فورياً وإعادة تفعيل الألوان والخيارات بالكامل، وعند النقر على «تغيير المريض» يعاد قفل الخدمات وتصفير التحديدات تلقائياً.
- **حل مشكلة تعذر الاختيار وتفويض النقر المباشر اللحظي لاختيار المريض (Direct Selection Fix & JS Const Error Resolution)**:
  * حل الخطأ الجوهري `TypeError: Assignment to constant variable` الناجم عن تعريف `currentPatientId` كـ `const` وإعادة تعيينه عند النقر، وتحويله إلى `let` مع إضافة `currentPatientIsInsured` و `currentPatientIsInsuranceLocked`.
  * توفير دالة اختيار عامة `window.selectPatientById(pId)` مرتبطة بكل سطر وبزر «اختيار» مباشرة إلى جانب تفويض النقر `results.addEventListener('click')` بدلالة `data-patient-id` وتخزين الكاش `window.patientSearchResultsCache`.
  * تثبيت المريض المختار في حقل `patient_id` المخفي وتحديث بطاقة هويته وشارات التأمين الصحي فورياً في الواجهة دون مغادرة الصفحة أو إعادة تحميلها.
  * تحديث ديناميكي فوري لقسم التغطية المالية للضمان والكاش (`#insuranceCoverageSection`) وتنبيه نواقص الضمان (`#patientInsuranceLockedAlert`) عند اختيار المريض دون الحاجة لإعادة تحميل الصفحة.
  * تحديث روابط التحويلات التخصصية (طوارئ، عمليات، رقود، حاضنة، عناية) برقم المريض الجديد فور اختياره، مع زر سريع لـ «تغيير المريض» في أي وقت.
  * التحقق البرمجي قبل الإرسال لمنع الحفظ بدون تحديد المريض مع التركيز التلقائي على شريط البحث.
- **توسيع محرك البحث اللحظي بالـ AJAX ([app/Http/Controllers/InquiryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/InquiryController.php))**:
  * دعم البحث برقم الملف / الإضبارة (`patient.id`)، الرقم الوطني (`national_id`)، رقم بطاقة أو دفتر الضمان (`insurance_card_no`)، واسم المريض ورقم هاتفه.
  * إرجاع كائن بيانات متكامل للمريض يضم الاسم، الهاتف، العمر، الجنس، العنوان، حالة ونوع الضمان، وعما إذا كان الملف مقفلاً لنواقص الضمان.
- **الاختبارات وضمان الجودة ([tests/Feature/InquiryStreamlinedDirectBookingTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/InquiryStreamlinedDirectBookingTest.php))**:
  * إنشاء واجتياز 5 اختبارات ميزة بنجاح تام (5 passed, 27 assertions) تغطي: فتح شاشة الاستعلامات مباشرة مع البحث والخدمات في نفس الصفحة، إعادة توجيه مسار البحث القديم للحجز المباشر، دقة نتائج الـ AJAX بالاسم ورقم الهاتف ورقم الملف، تثبيت بيانات المريض المختار، وفحص إعادة التوجيه المباشر لموظف الاستعلامات عند تسجيل الدخول وزيارة لوحة التحكم والصفحة الرئيسية.
  * اجتياز فحص كاش القوالب `view:cache` وتنسيق `Laravel Pint`.

## Session log (2026-11-26 — المرحلة 305: إعادة هيكلة جدول محطة الطبيب المقيم إلى 5 أعمدة واضحة وفصل اسم المريض في عمود مخصص مع إضافة عمود العملية والغرفة والطبيب الأخصائي Resident Doctor Station: 5-Column Table Restructure — Isolated Patient Name, Surgery & Room, Specialist Doctor, Phase & Actions)

### Done
- **فصل اسم المريض وإعادة هيكلة الجدول إلى 5 أعمدة واضحة ([resources/views/surgery-stations/resident/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/resident/index.blade.php))**:
  * تخصيص العمود الأول لـ «اسم المريض» فقط ورقم الملف الطبي دون أي تكديس للشارات، لتوفير رؤية بصرية مريحة وسريعة للمقيم.
  * إضافة عمود مخصص لـ «العملية والغرفة» يضم اسم الجراحة ورقم الغرفة والطابق وحالة استلام ممرض الطابق وتاريخ ووقت الموعد.
  * إضافة عمود مخصص لـ «الطبيب الأخصائي» يعرض اسم الجراح الأخصائي (`د. [اسم الطبيب]`) بأيقونة سريرية وتخصصه الطبي.
  * عمود «المرحلة» بشارات الأطوار السريرية الملونة (قبل الصالة، داخل الصالة، بعد الصالة).
  * عمود «الإجراءات» بزر «عرض وتعديل» وقفل الدخول حتى استلام الغرفة من التمريض.
  * ضبط توزيع العرض للأعمدة الخمسة بدقة: (اسم المريض 25%، العملية والغرفة 25%، الطبيب الأخصائي 20%، المرحلة 16%، الإجراءات 14%) مع ضبط `colspan="5"`.
  * إزالة شريط أزرار الفلترة العلوية «تصنيف الطور:» بناءً على رغبة المستخدم لتصبح الواجهة نظيفة ومباشرة.
- **تكامل البحث اللحظي الفوري (Instant Real-time Filter)**:
  * إضافة اسم الطبيب الأخصائي إلى سمة البحث اللحظي `data-search` في أسطر الجدول لتمكين المقيم من البحث والفلترة المباشرة بمجرد كتابة اسم الطبيب الجراح دون الحاجة لإعادة تحميل الصفحة.
- **تحديث تبويب الأرشيف والعمليات المكتملة**:
  * إضافة عمود «الطبيب الأخصائي» في جدول العمليات المكتملة لتوحيد الرؤية والتناسق عبر كامل التبويبات مع ضبط `colspan="7"`.
- **الاختبارات وضمان الجودة ([tests/Feature/ResidentStationPostOpTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ResidentStationPostOpTest.php), [tests/Feature/ResidentStationRoomCheckinGateTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ResidentStationRoomCheckinGateTest.php))**:
  * اجتياز اختبارات محطة المقيم والأعمدة الخمسة وسير عمل تسكين الغرفة بنجاح تام (4 passed, 28 assertions).
  * اجتياز اختبارات باقات الأدوية المشتركة [ClinicalStationMedicinePackageTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ClinicalStationMedicinePackageTest.php) (4 passed, 20 assertions).
  * اجتياز فحص كاش القوالب `view:cache` وتنسيق `Laravel Pint`.

## Session log (2026-11-26 — المرحلة 304: تثبيت وتأمين تأشير إعطاء الجرعات الدوائية للممرض بنقرة واحدة وحظر التراجع + ربط ومزامنة توصيات ما بعد الجراحة للجراح بالمحطة السريرية MAR One-Way Administration & Surgeon Post-Op Notes Sync)

### Done
- **حوكمة تأشير الجرعة في جدول الإعطاء MAR ([app/Models/SurgeryTreatment.php](file:///c:/wamp64/www/hospital-system/app/Models/SurgeryTreatment.php), [app/Http/Controllers/ClinicalStationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/ClinicalStationController.php))**:
  * منع إلغاء التأشير تماماً لكادر التمريض (`canUserUndoEntry = false` لغير الإدارة).
  * بمجرد نقر الممرض على خانة الجرعة، يتم توثيق الصرف باسمه وتاريخ ووقت الصرف الفعلي اللحظي وتثبيته.
  * حظر أي محاولة إلغاء للتأشير (`checked: 0`) برمجياً على مستوى السيرفر مع إرجاع كود 422 ورسالة: `لا يمكن للممرض التراجع عن تأشير الجرعة بعد توثيق إعطائها للمريض؛ إلغاء الجرعة محصور بالإدارة الطبية فقط لسلامة المريض.`
  * الإدارة فقط (`admin`) تحتفظ بصلاحية فك أو تصحيح الجرعة عند الأخطاء الإدارية القصوى.
- **الواجهة التفاعلية والـ JavaScript اللحظي ([resources/views/surgery-stations/clinical/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * قفل خانات الجرعات المنفذة تلقائياً (`@disabled`) لكادر التمريض مع وسام الإقفال الأمني `mar-dose-locked`.
  * تحديث دوال الـ JavaScript (`handleMarCheckboxToggle` و `addPrnDoseConfirmation`) لتقفل الخانة فوراً بعد أول نقرة ناجحة دون إمكانية النقر العكسي.
- **ربط ومزامنة توصيات وملاحظات ما بعد الجراحة للجراح ([app/Http/Controllers/ClinicalStationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/ClinicalStationController.php), [app/Http/Controllers/SurgeryController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgeryController.php), [resources/views/surgery-stations/clinical/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * ربط حقل «توصيات ما بعد الجراحة (Post-Op)» في تبويب الخطة الطبية (`tab=plan`) ليعرض تلقائياً ملاحظات الجراح المدونة في تفاصيل العملية الجراحية (`surgeries.post_op_notes` / `surgeries/{id}`).
  * إبراز وسم «من ملاحظات الجراح» بجانب الحقل وبطاقة خلاصة توصيات الجراح في رأس تبويب الخطة.
  * تفعيل المزامنة التلقائية الثنائية (Two-Way Sync): عند تعديل الجراح لتفاصيل العملية (`surgeries.updateDetails`) تُحدّث توصيات محطة المقيم `resident_stations.post_op_notes` تلقائياً؛ وعند قيام الطبيب بتحديث تبويب الخطة تُزامن التوصيات فوراً إلى سجل العملية الجراحية `surgeries.post_op_notes`.
- **الاختبارات وضمان الجودة ([tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php))**:
  * اجتياز كامل اختبارات المحطة السريرية والأدوية MAR والصلاحيات والمزامنة بنجاح تام (24 passed, 314 assertions).
  * اجتياز فحص `Laravel Pint` و `view:cache`.
  * اجتياز كامل اختبارات المحطة السريرية والأدوية MAR والصلاحيات بنجاح تام (23 passed, 305 assertions).
  * اجتياز فحص `Laravel Pint` و `view:cache`.

## Session log (2026-11-26 — المرحلة 303: قفل صالة العمليات بعد الإكمال + فصل محاسبة الأجهزة الطبية للجراح + بوابة التخريج المالية وإصلاح الأرشيف + وضع انتظار الغرفة بصلاحية مخصصة OR Lock, Surgeon-Only Devices Billing, Discharge Gate & Permission-Gated Room Standby)

### Done
- **الأجهزة الطبية ومزامنة الكاشير ([2026_11_26_130000_add_devices_billing_status_to_surgeries.php](file:///C:/get/hospital-system/database/migrations/2026_11_26_130000_add_devices_billing_status_to_surgeries.php), [Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php), [AccountantController.php](file:///C:/get/hospital-system/app/Http/Controllers/AccountantController.php), [CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * فصل `devices_billing_status` عن `billing_status` بحيث لا تتعطل فاتورة المريض في الكاشير بسبب إضافة جهاز طبي داخل الصالة، وتُسوى الأجهزة على حساب الطبيب الجراح فقط (`calculateSurgeonFinancials()` / `syncSurgeonDoctorDue()`).
  * إضافة `calculatePendingItemsBreakdown()`, `getFinancialClearanceSummary()`, `syncPaymentStatus()` لمزامنة `payment_status` فور إضافة تحاليل/أشعة/استشارات ما بعد العملية أو تعديل الغرف، وضبط التحاليل بعد العملية لتكون منفصلة افتراضياً مع إشعار تنبيهي بالكاشير العام.
- **منع إعادة العملية وبوابة التخريج وأرشيف المقيم ([SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [OperationTheaterStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/OperationTheaterStationController.php), [NursingStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/NursingStationController.php), [ResidentStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ResidentStationController.php), [StaffRequestController.php](file:///C:/get/hospital-system/app/Http/Controllers/StaffRequestController.php), [ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php))**:
  * حظر إعادة بدء أو إلغاء أو إرجاع العملية المكتملة مع إتمام `pre_op` وضمان وجود `post_op` عند إكمال الصالة.
  * إتاحة مرور طلبات المختبر والأشعة للراقدين مباشرة مع بيان حالتها المالية، وتطبيق فحص براءة الذمة الصارم في `roomCheckout()` و`discharge()`، وإصلاح استعلام الأرشيف في `ResidentStationController`.
- **وضع انتظار الغرفة بصلاحية مخصصة ([2026_11_26_133000_add_assign_surgery_room_permission.php](file:///C:/get/hospital-system/database/migrations/2026_11_26_133000_add_assign_surgery_room_permission.php), [RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php), [RoleManagementController.php](file:///C:/get/hospital-system/app/Http/Controllers/RoleManagementController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php), [InquiryController.php](file:///C:/get/hospital-system/app/Http/Controllers/InquiryController.php), [resources/views/surgeries/create.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/create.blade.php), [resources/views/surgeries/edit.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/edit.blade.php), [resources/views/inquiry/occupancy.blade.php](file:///C:/get/hospital-system/resources/views/inquiry/occupancy.blade.php), [resources/views/surgery-stations/nursing/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/nursing/index.blade.php))**:
  * دعم الحجز وتعديل الحجز بـ `room_id = null`، وإضافة مسار `surgeries.assign-room` بصلاحية `assign surgery room` مع واجهات التخصيص في الإشغال ومحطة التمريض وإصلاح استدعاء `Room::getInitialFeeForType()` داخل `assignRoom()`.
- **الاختبارات وضمان الجودة ([tests/Feature/SurgeryCashierDevicesAndRoomWaitingWorkflowTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryCashierDevicesAndRoomWaitingWorkflowTest.php))**:
  * اجتياز `Laravel Pint` (`20 files`) و`php artisan view:cache` واختبارات الميزة الجديدة (`SurgeryCashierDevicesAndRoomWaitingWorkflowTest`) واختبارات العمليات (`SurgeryStatusPostOpAndPrintReportTest`, `SurgeryWaitingListFlightBoardTest`, `SurgeriesCashierStayBillingTest`).

## Session log (2026-11-26 — المرحلة 302: توحيد تبويبات محطة الطبيب المقيم في تبويب نشط واحد وإعادة هيكلة الجدول إلى 4 أعمدة رئيسية مع فلاتر الأطوار اللحظية Resident Doctor Station Unified Active Tab, 4-Column Clinical Table & Instant Phase Filters)

### Done
- **توحيد تبويب محطة المقيم ([app/Http/Controllers/ResidentStationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/ResidentStationController.php), [resources/views/surgery-stations/resident/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/resident/index.blade.php))**:
  * دمج حالتي التهيئة قبل الصالة والمتابعة بعد الصالة في تبويب موحد أسرع وأنظم تحت مسمى «مرضى الرقود والعمليات النشطة» (`activeSurgeries`).
  * إضافة شريط أزرار الفلترة اللحظية السريعة فورياً بدون إعادة تحميل الصفحة (كافة الحالات النشطة، قبل الصالة، داخل صالة العمليات، بعد الصالة / الرقود).
- **إعادة هيكلة جدول الحالات النشطة إلى 4 أعمدة رئيسية ([resources/views/surgery-stations/resident/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/resident/index.blade.php))**:
  * 1. **اسم المريض**: يضم اسم المريض، رقم الملف، العملية الجراحية وتصنيفها، الغرفة والطابق، وتاريخ ووقت الموعد.
  * 2. **المرحلة**: شارات الأطوار السريرية الملونة (`قبل الصالة (تهيئة)`، `داخل صالة العمليات`، `بعد الصالة (عناية الرقود)`).
  * 3. **الحالة السريرية**: شارة الطبيب المقيم المكلف، وحالة المحطة السريرية (في الانتظار / معلقة / جارية / مكتملة)، وحالة استلام وتسكين الغرفة من ممرض الطابق.
  * 4. **الإجراءات**: قفل الزر تنبيهياً إذا لم تُستلم الغرفة بعد، وزر «عرض وتعديل» للدخول المباشر إلى المحطة السريرية للعملية.
- **الاختبارات وضمان الجودة ([tests/Feature/ResidentStationPostOpTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ResidentStationPostOpTest.php), [tests/Feature/ResidentStationRoomCheckinGateTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ResidentStationRoomCheckinGateTest.php), [tests/Feature/ResidentFollowUpClinicalSignsTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/ResidentFollowUpClinicalSignsTest.php))**:
  * اجتياز اختبارات محطة المقيم كاملة بـ 9 اختبارات ناجحة (65 assertions).
  * اجتياز فحص `view:cache` وتنسيق `Pint`.

## Session log (2026-11-26 — المرحلة 301: إعادة هيكلة جدول محطة التمريض إلى 4 أعمدة محددة وسلسة، وحل مشكلة استمرارية البحث المباشر وإعادة فتح قائمة المستلزمات الطبية بعد الإضافة للسلة Nursing Station 4-Column Table Restructure & Persistent Supplies Dropdown Flow)

### Done
- **إعادة هيكلة جدول محطة التمريض إلى 4 أعمدة رئيسية ([resources/views/surgery-stations/nursing/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/nursing/index.blade.php))**:
  * إعادة ترتيب وهيكلة أعمدة جدول مرضى الرقود والعمليات النشطة من 7 أعمدة إلى 4 أعمدة واضحة ومباشرة (اسم المريض، المرحلة، الحالة السريرية، الإجراءات).
  * تحديث خلايا `@empty` وحالة عدم وجود نتائج لتطابق عدد الأعمدة الجديد (`colspan="4"`).
- **حل اختفاء قائمة البحث اللحظي للمستلزمات الطبية ([resources/views/surgery-stations/clinical/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * معالجة مشكلة اختفاء القائمة المنسدلة عند الضغط على «إضافة للقائمة» ومحاولة إضافة مستلزم آخر عبر إضافة زر سهم منسدل (Chevron Toggle Button) مدمج، وربط حدث `click` على حقل البحث لفتح القائمة عند النقر حتى أثناء التركيز، واستثناء زر الإضافة من إغلاق القائمة، وإعادة فتح القائمة تلقائياً بعد الإضافة.
- **الاختبارات وضمان الجودة ([tests/Feature/NursingStationSurgeriesTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NursingStationSurgeriesTest.php), [tests/Feature/NursingRoomCheckinWorkflowTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NursingRoomCheckinWorkflowTest.php), [tests/Feature/NursingFloorDispenseTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NursingFloorDispenseTest.php))**:
  * اجتياز كافة اختبارات محطة التمريض وسير عمل تسكين الغرف وصرف عهدة الطابق والسلة الجماعية بنجاح (15 passed, 90 assertions).
  * اجتياز فحص القوالب `view:cache` وتنسيق `Pint`.

## Session log (2026-11-26 — المرحلة 300: توحيد تبويبات محطة التمريض في تبويب تفاعلي واحد، وإبراز شارة الطور في رأس المحطة السريرية، وتفعيل البحث اللحظي وسلة الصرف الجماعي المتعدد للمستلزمات الطبية Unified Nursing Station Active Tab, Clinical Phase Header Badge & Live Search Multi-Item Supplies Cart)

### Done
- **توحيد تبويب محطة التمريض ([app/Http/Controllers/NursingStationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/NursingStationController.php), [resources/views/surgery-stations/nursing/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/nursing/index.blade.php))**:
  * دمج حالتي «قبل الصالة» و «بعد الصالة» في تبويب موحد أسرع وأنظم تحت مسمى «مرضى الرقود والعمليات النشطة».
  * إضافة أزرار فلترة لحظية سريعة فورية بدون إعادة تحميل الصفحة (كافة الحالات النشطة، قبل الصالة، داخل صالة العمليات، بعد الصالة / الرقود).
  * تمييز كل مريض بشارة ملونة واضحة لطور حالته السريرية، مع أزرار تسكين الغرفة والمغادرة وعرض التفاصيل المباشرة.
- **شارة الطور السريري في رأس المحطة ([resources/views/surgery-stations/clinical/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * إبراز وسم الطور الجراحي والسريري في رأس الصفحة بجانب اسم المريض والعملية (`قبل الصالة (تحضير وتهيئة)` / `داخل صالة العمليات` / `بعد الصالة (عناية الرقود)`).
- **البحث اللحظي التفاعلي للمستلزمات الطبية ([resources/views/surgery-stations/clinical/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * استبدال القائمة المنسدلة التقليدية بحقل بحث فوري لحظي أثناء الكتابة (As-you-type autocomplete) يفلتر المستلزمات الطبية لعهدة طابق المريض.
  * عرض اسم المستلزم، التصنيف، الرصيد المتاح، كود الوجبة/الباركود، وتاريخ الصلاحية مع تظليل النتائج وزر مسح وتفريغ سريع.
- **سلة الصرف الجماعي المتعدد مع التعديل والحذف المباشر ([app/Http/Controllers/ClinicalStationController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/ClinicalStationController.php), [resources/views/surgery-stations/clinical/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * تمكين الكادر من إضافة عدة مستلزمات طبية دفعة واحدة إلى قائمة مؤقتة قيد الصرف.
  * إمكانية تعديل كمية أي مستلزم مباشرة من الجدول مع فحص الرصيد الأقصى المتوفر ومنع التجاوز.
  * إمكانية تعديل سبب الاستخدام والملاحظات لكل مستلزم على حدة، وحذف أي بند من القائمة بضغطة زر، أو تفريغ القائمة بالكامل.
  * زر حفظ وصرف جماعي بنقرة واحدة ينفذ العملية ضمن معاملة مالية ومخزنية آمنة (`DB::transaction`) مع تسجيل حركات `StockMovement` وقيود `SurgeryTreatment` وخصم الأرصدة من وجبات عهدة الطابق بدقة.
- **الاختبارات وضمان الجودة ([tests/Feature/NursingFloorDispenseTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NursingFloorDispenseTest.php), [tests/Feature/NursingStationSurgeriesTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NursingStationSurgeriesTest.php))**:
  * إضافة اختبارات ميزة آلية للصرف الجماعي المتعدد وللتبويب الموحد والفلاتر اللحظية.
  * اجتياز اختبارات صرف عهدة الطابق بنجاح (`6 passed, 43 assertions`) واختبارات محطة التمريض (`9 passed, 48 assertions`).
  * اجتياز فحص القوالب `view:cache` و `Laravel Pint` بالكامل.

## Session log (2026-11-26 — المرحلة 300: فصل شاشة «معاينات الوليد الحية» بعرض البطاقات وإضافتها كعنصر قياسي مباشر تحت رادار العمليات بالقائمة الجانبية وتنظيف الرادار Dedicated NICU Live Observations Cards Screen & Standard Sidebar Item)

### Done
- **المسار والقائمة الجانبية ([routes/web.php](file:///C:/get/hospital-system/routes/web.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * إضافة مسار `GET /nicu/observations` (`nicu.observations.index`) ورابط قياسي مسطّح «معاينات الوليد الحية» بنفس تنسيق باقي القوائم مباشرة أسفل «رادار صالات العمليات والولادة» مع بادج العدّاد الحي.
- **المتحكمات والواجهات ([NicuDeliveryObservationController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuDeliveryObservationController.php), [NicuDeliveryRadarController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuDeliveryRadarController.php), [resources/views/nicu/observations/index.blade.php](file:///C:/get/hospital-system/resources/views/nicu/observations/index.blade.php), [resources/views/nicu/observations/show.blade.php](file:///C:/get/hospital-system/resources/views/nicu/observations/show.blade.php), [resources/views/nicu/delivery-radar.blade.php](file:///C:/get/hospital-system/resources/views/nicu/delivery-radar.blade.php))**:
  * إنشاء شاشة `nicu.observations.index` بعرض البطاقات (Cards) ومؤقتات حية، 4 بطاقات إحصائيات، تبويب أرشيف التسليم والتحويل، ومودال بدء معاينة ولادة طبيعية.
  * تنظيف شاشة رادار العمليات وإزالة التبويبات الثقيلة منها مع إبقاء عمود بدء/فتح معاينة الساعتين وزر الانتقال السريع لشاشة المعاينات الحية.
- **الاختبارات وضمان الجودة ([tests/Feature/NicuDeliveryObservationTest.php](file:///C:/get/hospital-system/tests/Feature/NicuDeliveryObservationTest.php))**:
  * اجتياز `22 passed (306 assertions)` وفحص `Laravel Pint` و`view:cache`.

## Session log (2026-11-26 — المرحلة 299: تفعيل منظومة الخدمات الإضافية والفندقية الذكية (نمط ICD-10) لقسم الخدج والحاضنات NICU مع شاشة إدارة وتسعير «الخدمات المتفرقة للمستشفى» في الإدارة والإعدادات NICU Smart Extra Services Engine & Hospital Miscellaneous Services Settings)

### Done
- **قاعدة البيانات والنماذج ([2026_11_26_100000_create_nicu_extra_services_and_department_scope.php](file:///C:/get/hospital-system/database/migrations/2026_11_26_100000_create_nicu_extra_services_and_department_scope.php), [NicuExtraService.php](file:///C:/get/hospital-system/app/Models/NicuExtraService.php), [HospitalExtraService.php](file:///C:/get/hospital-system/app/Models/HospitalExtraService.php), [IncubatorReservation.php](file:///C:/get/hospital-system/app/Models/IncubatorReservation.php))**:
  * إضافة عمود `department` (`general`, `nicu`, `all`) لجدول `hospital_extra_services` وبذر 18 خدمة مخصصة للخدج ضمن 5 فئات (`clinical`, `care`, `administrative`, `accommodation`, `fine`).
  * إنشاء جدول `nicu_extra_services` وربطه بعلاقة `extraServices()` في `IncubatorReservation` مع عزل قوائم العمليات عن الخدج (`scopeForGeneral()`, `scopeForNicu()`).
- **المحرك المالي والكاشير والمطبوعات ([NicuBillingService.php](file:///C:/get/hospital-system/app/Services/NicuBillingService.php), [CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [resources/views/cashier/nicu/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu/payment-form.blade.php), [resources/views/cashier/nicu-itemized-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-itemized-print.blade.php), [resources/views/nicu/statement.blade.php](file:///C:/get/hospital-system/resources/views/nicu/statement.blade.php))**:
  * احتساب الخدمات الإضافية للخدج كاش 100% على المريض (خارج الضمان) ضمن `grand_total` و`patient_grand_total` و`net_balance`.
  * بناء صندوق «إضافة خدمات إضافية وفندقية للخدج والحاضنة» الذكي بترتيب مطابق لشاشة العمليات مع بحث فوري وتصنيف سريع وحفظ تلقائي للخدمات الجديدة، بالإضافة لقسم البنود المضافة مع التعديل والحذف وإظهارها في الوصل الحراري وكشف الحساب A4.
- **إدارة «الخدمات المتفرقة للمستشفى» في الإدارة والإعدادات ([HospitalExtraServiceController.php](file:///C:/get/hospital-system/app/Http/Controllers/Admin/HospitalExtraServiceController.php), [resources/views/admin/extra-services/index.blade.php](file:///C:/get/hospital-system/resources/views/admin/extra-services/index.blade.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php), [resources/views/nicu/partials/settings-nav.blade.php](file:///C:/get/hospital-system/resources/views/nicu/partials/settings-nav.blade.php))**:
  * إضافة عنصر فرعي **«الخدمات المتفرقة للمستشفى»** تحت قائمة **الإدارة والإعدادات** وتبويب وصول سريع في إعدادات الخدج، مع تعديل أسعار فوري بـ AJAX وإدارة كاملة لكافة الأقسام.
- **الاختبارات وضمان الجودة ([tests/Feature/NicuExtraServicesTest.php](file:///C:/get/hospital-system/tests/Feature/NicuExtraServicesTest.php))**:
  * اجتياز كافة اختبارات `NicuExtraServicesTest` و`NicuEnhancementsTest` وفحص `Laravel Pint` و`view:cache`.

## Session log (2026-11-25 — المرحلة 298: تفعيل مركزية الدفع لكافة فواتير ووصفات الصيدلية في الكاشير المركزي وحظر الدفع بالصيدلية مع صلاحية مستقلة ورادار التسليم التفاعلي Central Cashier Pharmacy Routing, Permission-gated Tab & Real-time Dispensing Handoff)

### Done
- **منع الدفع النقدي في الصيدلية وتوجيهه للكاشير المركزي ([app/Http/Controllers/Pharmacy/PharmacyPosController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/PharmacyPosController.php), [resources/views/pharmacy/pos/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/pos/index.blade.php))**:
  * حظر أي استلام نقدي أو دفع في نافذة الصيدلية، وتوجيه 100% من مبيعات الرف (OTC) والوصفات الطبية (`prescriptions`) إلى الكاشير المركزي (`payment_route = 'central_cashier'`, `payment_status = 'pending_cashier'`).
  * تحويل أزرار نقطة البيع في الصيدلية إلى `إرسال للكاشير المركزي للدفع (F9)`.
  * ربط محرك احتساب الضمان الصحي والتحمل المالي للمريض تلقائياً حسب نوع التأمين والتعاقد.
- **صلاحيات مخصصة للتحكم بظهور تبويب الصيدلية في الكاشير المركزي ([app/Http/Controllers/RoleManagementController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RoleManagementController.php), [database/seeders/RolesAndPermissionsSeeder.php](file:///c:/wamp64/www/hospital-system/database/seeders/RolesAndPermissionsSeeder.php))**:
  * إنشاء صلاحيتين دقيقتين: `process pharmacy payments` (تسديد مدفوعات الصيدلية) و `view cashier pharmacy` (عرض تبويب الصيدلية في الكاشير).
  * تسجيل الصلاحيتين ضمن الفئة الثالثة (الصندوق والكاشير)، ومنحهما افتراضياً للأدوار الإدارية وأمناء الصندوق، مع إمكانية منحها وسحبها من أي دور بدقة عبر إدارة الأدوار.
- **تبويب ونافذة تسديد الصيدلية في الكاشير المركزي ([app/Http/Controllers/CashierController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/CashierController.php), [resources/views/cashier/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/index.blade.php), [resources/views/cashier/pharmacy-payment-form.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/pharmacy-payment-form.blade.php), [resources/views/cashier/receipt-print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/cashier/receipt-print.blade.php))**:
  * إضافة تبويب مخصص «الصيدلية والعلاج» مع عداد لحظي وزر فلترة سريع في جدول الكاشير العام.
  * بناء واجهة تسديد فواتير الصيدلية المستقلة `cashier.pharmacy.payment.form` تدعم النقد، والبطاقة الإلكترونية، والدفع المختلط (Split Payment)، وحساب الخصم وتوثيق السندات المالية رسمياً وتوليد رقم الوصل.
  * دعم طباعة الوصل المالي A4/الحراري مع تفاصيل الأدوية والكميات والأسعار والحصص التأمينية.
- **رادار الصيدلية اللحظي والتسليم بعد التحقق المالي (Post-Payment Handoff)**:
  * تحديث رادار طابور الصيدلية ليعرض وسام `🟢 مدفوعة بالكاشير - جاهز للتسليم` بمجرد السداد عند الكاشير المركزي، أو `🟡 بانتظار الكاشير`.
  * مسار مستقل لتسليم الدواء `POST /pharmacy/pos/sales/{sale}/handoff` يتحقق أمنياً من `payment_status === 'paid'` ويمنع صرف أي دواء غير مسدد مالياً برمز 422، مع توثيق `dispensed_by` و `dispensed_at` وتحديث حالة الوصفة إلى `dispensed`.
- **الاختبارات وضمان الجودة ([tests/Feature/PharmacyCentralCashierWorkflowTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacyCentralCashierWorkflowTest.php), [tests/Feature/PharmacyPosTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacyPosTest.php))**:
  * 6 اختبارات ميزة آلية جديدة شاملة لدورة الصيدلية والكاشير المركزي (30 assertions) ونجاح اختبارات نقاط البيع (30 assertions).
  * اجتياز فحص `view:cache` و `Laravel Pint` بالكامل.

## Session log (2026-11-25 — المرحلة 297: إصلاح خطأ TypeError في شاشتي الأشعة والمختبر عند التعامل مع نتائج طلبات الخدج Fix htmlspecialchars Array Argument Error in Radiology & Lab Views)

### Done
- **إصلاح الخطأ البرمجي في واجهتي الأشعة والمختبر ([resources/views/radiology-staff/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/radiology-staff/index.blade.php), [resources/views/lab/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/lab/index.blade.php))**:
  * سبب الخطأ: كانت حلقة المودال السفلية (`#completeNicuRadModal` و `#completeNicuLabModal`) تستدعي موديلات `\App\Models\Request` والتي تحتوي على كاستنج لمصفوفة `'result' => 'array'`، مما تسبب في تمرير مصفوفة إلى دالة `htmlspecialchars()` داخل `{{ $nicuRad->result }}` وتوقف الصفحة بـ 500.
  * المعالجة: حصر المودال في موديلات أوامر الخدج `NicuMedicalOrder` واستثناء `\App\Models\Request` باستخدام `@if($nicuRad instanceof \App\Models\Request) @continue @endif` (لأن طلبات `Request` تُفتح عبر شاشة التقرير المخصصة لها `radiology-staff.show`).
  * الأمان المزدوج: تحصين طباعة `result` و `technician_notes` بفحص `is_array()` لضمان عدم حدوث أي خطأ طباعة مستقبلاً.
- **الاختبارات وضمان الجودة ([tests/Feature/RadiologyStaffCallNextTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/RadiologyStaffCallNextTest.php))**:
  * كتابة اختبار انحدار آلي لمحاكاة وجود طلب أشعة للخدج من نوع `MedicalRequest` يحمل نتيجة كمصفوفة `result => [...]` والتحقق من فتح شاشة الأشعة بنجاح بدون أخطاء (`5 passed, 13 assertions`).
  * اجتياز فحص `view:cache` و `Pint`.

## Session log (2026-11-25 — المرحلة 296: توحيد وترقية واجهة إضافة مستخدم جديد وحصر دليل المستخدمين في كادر وموظفي المستشفى واستبعاد المرضى تماماً Modern Create User Screen Unified with Edit, Live Preview & Complete Patient Exclusion from Staff Directory)

### Done
- **استبعاد حسابات المرضى من دليل كادر المستشفى ([app/Http/Controllers/UserManagementController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/UserManagementController.php))**:
  * حصر استعلام شاشة المستخدمين وإحصائياتها وبطاقات الـ KPI في كادر وموظفي المستشفى فقط.
  * استبعاد أي مستخدم يحمل دور `patient`، أو مرتبط بسجل في جدول المرضى `patients`، أو يقتصر دوره على مريض بدون أدوار وظيفية.
  * استبعاد دور `patient` من قائمة الأدوار المتاحة للاختيار في شاشتي الإضافة والتعديل ومنع تعيينه لكادر المستشفى.
- **الواجهة التفاعلية العصرية ([resources/views/users/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/users/create.blade.php))**:
  * إعادة بناء شاشة الإضافة بالكامل لتتطابق مع شاشة التعديل وتعتمد تصميم العمودين (Two-column layout).
  * بطاقة جانبية للمعاينة الحية الفورية (Live Profile Preview Card) تعرض الأفاتار، الاسم، البريد، المخزن/الردهة، وعدد الأدوار المحددة لحظياً أثناء الكتابة.
  * تقسيم الأدوار والصلاحيات الوظيفية إلى فئات وأقسام ملونة بأيقونات معبرة مع حقل بحث سريع داخل الأدوار وزري «تحديد القسم / إلغاء».
  * حقول كلمات المرور مع أزرار إظهار وإخفاء كلمة المرور (Show/Hide Password).
- **الاختبارات وضمان الجودة ([tests/Feature/UserEditModernViewTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/UserEditModernViewTest.php), [tests/Feature/UserManagementIndexTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/UserManagementIndexTest.php))**:
  * بناء اختبارات ميزة آلية لعرض الشاشة العصرية الجديدة بكافة أقسامها وتصنيفاتها وإنشاء مستخدم جديد بنجاح (`4 passed, 28 assertions`).
  * اختبار استبعاد حسابات المرضى بالكامل من الدليل وإحصائيات النظام واجتياز كافة الفلاتر والبحث اللحظي (`5 passed, 22 assertions`) واجتياز `Pint`.

## Session log (2026-11-25 — المرحلة 295: تحديث وتطوير واجهة إدارة المستخدمين لتصميم عصري Enterprise مع البحث والفلترة الفورية اللحظية Modern Users Directory & Instant Real-time Debounced Filter)

### Done
- **المتحكم والبيانات ([app/Http/Controllers/UserManagementController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/UserManagementController.php))**:
  * حساب وتمرير مؤشرات وإحصائيات سريعة (`stats`): إجمالي الحسابات، الحسابات النشطة، الحسابات المعطلة، وعدد الأدوار الوظيفية.
  * دعم وتأكيد الفلترة متعددة المعايير بالبحث، الأدوار، مواقع التعيين والمخازن (`location_id`)، والحالة التشغيلية.
- **الواجهة التفاعلية العصرية ([resources/views/users/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/users/index.blade.php))**:
  * إضافة 4 بطاقات إحصائيات علوية (KPI Cards) متناسقة مع هوية النظام.
  * حقل بحث سريع ذكي بمؤقت Debounce لحظي (420ms) يفلتر البيانات تلقائياً بمجرد الكتابة وبدون الحاجة لأي زر بحث، مع مؤشر تحميل تفاعلي (Spinner) وزر مسح سريع `(✕)` مع الحفاظ على التركيز وموضع المؤشر.
  * قوائم منسدلة تفاعلية فورية للصلاحيات، المواقع/الأقسام، والحالة تتحدث تلقائياً بـ `onchange`.
  * شارات الفلاتر النشطة (Filter Chips) مع إمكانية حذف أي فلتر بضغطة زر أو مسح كل الفلاتر.
  * جدول مستخدمين عصري بأفاتار ملون (User Initials)، شارات أدوار وأقسام دقيقة، ونقاط حالة نابضة (Active/Inactive Pulse).
  * أزرار إجراءات مستديرة ناعمة بتلميحات Tooltips وحماية حسابات الإدارة العليا.
- **الاختبارات وضمان الجودة ([tests/Feature/UserManagementIndexTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/UserManagementIndexTest.php))**:
  * بناء اختبارات ميزة آلية جديدة لاختبار عرض الشاشة، الفلترة اللحظية بالدور والبحث والموقع والحالة (`4 passed, 18 assertions`) واجتياز `Pint`.

## Session log (2026-11-25 — المرحلة 294: سند تحويل وصرف الشحنات المخزنية الرسمي A4 بشعار مستشفى الكفاءات الأهلي المعتمد وتوقيعين وخارطة طريق إدارة الهوية ديناميكياً Stock Transfer Voucher A4, Al-Kafa'at Hospital Emblem, Dual Sign-off & Branding Roadmap)

### Done
- **المسار والكنترولر ([routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php), [app/Http/Controllers/StockTransferController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/StockTransferController.php))**:
  * إضافة مسار `GET /stock-transfers/requests/{stockTransferRequest}/print` باسم `stock-transfers.requests.print`.
  * إضافة دالة `printRequest()` مع فحص الصلاحيات للأدوار الإدارية ومسؤولي عهدة الأقسام المستلمة والمصدرة.
- **واجهة وقالب الطباعة الرسمي A4 ([resources/views/stock-transfers/requests/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/stock-transfers/requests/show.blade.php), [resources/views/stock-transfers/requests/print.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/stock-transfers/requests/print.blade.php))**:
  * إضافة أزرار الطباعة في رأس صفحة الشحنة وأعلى جدول المواد في `show.blade.php`.
  * بناء قالب الطباعة A4 المعتمد باسم **«مستشفى الكفاءات الأهلي»** (Al-Kafa'at Private Hospital).
  * تضمين شعار المستشفى المعتمد بدقة عالية عبر Base64 Data URI من `images/1.jpg` لضمان عدم تأخر أو انكسار الشعار عند الطباعة.
  * جدول المواد الكامل مع تفاصيل الكميات المطلوبة والمصروفة والمستلمة وملاحظات وفروقات الصرف.
  * حصر التواقيع الرسمية باثنين فقط كما طُلب: **منظم الطلب (مسؤول عهدة القسم المستلم)** و**أمين المخزن المصرف (مسؤول التجهيز والصرف)**.
- **حفظ وأرشفة خارطة طريق إدارة الهوية والشعار ديناميكياً ([docs/HOSPITAL_BRANDING_ROADMAP.md](file:///c:/wamp64/www/hospital-system/docs/HOSPITAL_BRANDING_ROADMAP.md))**:
  * توثيق المخطط الهندسي الشامل لإنشاء لوحة تحكم إدارية لرفع الشعار وتعديل اسم المستشفى والترويسة مع كاشينغ فائق السرعة ومعاينة حية A4 للعودة له وتنفيذه مستقبلاً.
- **الاختبارات وضمان الجودة ([tests/Feature/FloorCustodianStockTransferTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/FloorCustodianStockTransferTest.php))**:
  * إضافة اختبارات آلية للطباعة والتحقق من التوقيعين الحصريين وظهور اسم مستشفى الكفاءات الأهلي (`15 passed, 107 assertions`).

## Session log (2026-11-25 — المرحلة 293: بناء منظومة معاينة المواليد بعد الولادة (ساعتين) وربطها برادار العمليات مع استمارة Obstetric Case Sheet والطباعة والأرشفة والتحويل للحاضنة Dynamic 2-Hour Newborn Observation, Delivery Radar Hub, Obstetric Case Sheet A4 Print, Signed Archive & NICU Handoff)

### Done
- **قاعدة البيانات والنماذج ([2026_11_25_140000_create_nicu_delivery_observations_and_settings.php](file:///C:/get/hospital-system/database/migrations/2026_11_25_140000_create_nicu_delivery_observations_and_settings.php), [NicuSetting.php](file:///C:/get/hospital-system/app/Models/NicuSetting.php), [NicuDeliveryObservation.php](file:///C:/get/hospital-system/app/Models/NicuDeliveryObservation.php), [NicuDeliveryObservationBaby.php](file:///C:/get/hospital-system/app/Models/NicuDeliveryObservationBaby.php), [Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php))**:
  * إنشاء جداول `nicu_settings` و`nicu_delivery_observations` و`nicu_delivery_observation_babies` مع دعم التوائم واستقلال قرار كل مولود (تسليم للأهل أو تحويل لحاضنة).
  * تهيئة الإعدادات الديناميكية الافتراضية للمعاينة (المدة 120 دقيقة، مجانية/بدون أجور افتراضياً `observation_is_billable=0`، وقابلة للتفعيل والتسعير أو تعديل الترويسة لأي مستشفى).
  * إنشاء صلاحية `manage nicu delivery observations` ومنحها افتراضياً لكادر الخدج والإدارة وتسجيلها في [RoleManagementController.php](file:///C:/get/hospital-system/app/Http/Controllers/RoleManagementController.php).
- **المتحكمات والمسارات ([NicuDeliveryObservationController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuDeliveryObservationController.php), [NicuDeliveryRadarController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuDeliveryRadarController.php), [IncubatorReservationController.php](file:///C:/get/hospital-system/app/Http/Controllers/IncubatorReservationController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * دعم بدء المعاينة مباشرة من عملية برادار الولادات (`startFromSurgery`) أو الإدخال اليدوي للولادات الطبيعية (`storeManual`).
  * دعم استمارة المعاينة، توثيق التسليم القانوني للأهل، رفع ومعاينة الورقة الموقعة والمبصمة، وطباعة A4 الرسمية.
  * ربط زر `تحويل لحاضنة NICU` لكل مولود بتعبئة شاشة دخول الحاضنة تلقائياً وربط `incubator_reservation_id` مع تحديث حالة الملاحظة تلقائياً.
- **الواجهات والاختبارات ([delivery-radar.blade.php](file:///C:/get/hospital-system/resources/views/nicu/delivery-radar.blade.php), [delivery-radar-settings.blade.php](file:///C:/get/hospital-system/resources/views/nicu/delivery-radar-settings.blade.php), [show.blade.php](file:///C:/get/hospital-system/resources/views/nicu/observations/show.blade.php), [print.blade.php](file:///C:/get/hospital-system/resources/views/nicu/observations/print.blade.php), [create.blade.php](file:///C:/get/hospital-system/resources/views/incubator_reservations/create.blade.php), [NicuDeliveryObservationTest.php](file:///C:/get/hospital-system/tests/Feature/NicuDeliveryObservationTest.php))**:
  * تحويل رادار الولادات إلى مركز متكامل من 3 تبويبات: (رادار العمليات + لوحة المعاينات الحية بمؤقت حي + أرشيف التسليم والتحويل القابل للبحث)، وإصلاح خطأ الصياغة `in_progress` في القالب.
  * اجتياز `Laravel Pint` و`view:cache` وحزمة الاختبارات (`28 passed, 368 assertions`).

## Session log (2026-11-25 — المرحلة 292: تطوير تبويب «باقة الخدمة والتبديل ومعلومات الدخول» في ملف الخديج مع صلاحية مستقلة لمعلومات الدخول والتاريخ المرضي للطفل والأم NICU Admission Info & Medical History with Granular Permission)

### Done
- **تعديل التبويب الأول وإضافة بطاقة معلومات الدخول والتاريخ المرضي ([resources/views/nicu/show.blade.php](file:///C:/get/hospital-system/resources/views/nicu/show.blade.php))**:
  * تغيير مسمى التبويب الأول إلى `باقة الخدمة والتبديل ومعلومات الدخول`.
  * إضافة بطاقة `معلومات الدخول والتاريخ المرضي` تعرض ملخص الدخول و3 مربعات كتابة صافية ومرتبة: `سبب الدخول للحاضنة` (`admission_reason`)، `التاريخ المرضي للطفل` (`medical_history`)، و`التاريخ المرضي للأم` (`maternal_history`) مع عرض اسم وتاريخ آخر محدث.
- **قاعدة البيانات والمودل والصلاحية المستقلة ([2026_11_25_130000_add_admission_history_fields_to_incubator_reservations.php](file:///C:/get/hospital-system/database/migrations/2026_11_25_130000_add_admission_history_fields_to_incubator_reservations.php), [app/Models/IncubatorReservation.php](file:///C:/get/hospital-system/app/Models/IncubatorReservation.php), [app/Http/Controllers/RoleManagementController.php](file:///C:/get/hospital-system/app/Http/Controllers/RoleManagementController.php))**:
  * إنشاء صلاحية `manage nicu admission history` ومُنحت تلقائياً لأدوار الأطباء والإدارة فقط مع إتاحتها في شاشة الأدوار لمنحها للتمريض أو أي مستخدم حسب الحاجة.
- **الكنترولر والمسار والاختبارات ([app/Http/Controllers/NicuController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php), [tests/Feature/NicuEnhancementsTest.php](file:///C:/get/hospital-system/tests/Feature/NicuEnhancementsTest.php))**:
  * إضافة `updateAdmissionHistory()` ومسار `nicu.admission-history.update`، واجتياز `23 passed (328 assertions)` وتوافق `Laravel Pint` و`view:cache`.

## Session log (2026-11-25 — المرحلة 291: تطوير المنظومة السريرية والمخزنية لقسم الخدج والحاضنات NICU Store Governance, Custom Medicines Directory, Therapy Day Counter, Favorite Packs & Large Clinical Notes)

### Done
- **مخزن وعُهدة قسم الخدج المربوط بالمخزن الرئيسي ([app/Http/Controllers/NicuStoreController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuStoreController.php), [resources/views/nicu-store/](file:///C:/get/hospital-system/resources/views/nicu-store/index.blade.php))**:
  * إنشاء لوحة إدارة ومراقبة مخزن وعُهدة الخدج (`Location #18`) المربوطة مباشرة بالمخزن الرئيسي (`Location #1`) لطلبيات التعزيز وإرجاع الفائض.
  * إضافة تبويب `صرفيات ومستلزمات المخزن` داخل ملف الطفل في [resources/views/nicu/show.blade.php](file:///C:/get/hospital-system/resources/views/nicu/show.blade.php) مع دالة `dispenseStoreItem()` في [app/Http/Controllers/NicuController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuController.php) لتوثيق السحب وخصم العهدة بدون أي أثر مالي على فاتورة كاشير الخدج.
- **دليل الأدوية المضافة بالخدج ([app/Models/NicuCustomMedicine.php](file:///C:/get/hospital-system/app/Models/NicuCustomMedicine.php), [app/Http/Controllers/NicuCustomMedicineController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuCustomMedicineController.php), [resources/views/nicu/custom-medicines/index.blade.php](file:///C:/get/hospital-system/resources/views/nicu/custom-medicines/index.blade.php))**:
  * حفظ تلقائي لأي دواء يكتبه طبيب الخدج وغير موجود بجدول الصيدلية `medicines` داخل `nicu_custom_medicines`، وظهوره بالبحث الفوري لاحقاً مع واجهة إدارة تحت إعدادات الخدج.
- **عداد أيام استخدام الدواء ([app/Models/Concerns/HasTherapyDayCounter.php](file:///C:/get/hospital-system/app/Models/Concerns/HasTherapyDayCounter.php))**:
  * إضافة حقول `duration_days`, `started_at`, `stopped_at`, `stopped_by` في `nicu_medical_orders` و`nicu_care_schedules` مع شارات `Day X of Y` / `Day X` وتنبيهات تجاوز المدة وزر `إيقاف/استئناف`.
- **باقات الأدوية المفضلة السريعة للخدج ([app/Http/Controllers/UserMedicineGroupController.php](file:///C:/get/hospital-system/app/Http/Controllers/UserMedicineGroupController.php), [app/Models/UserMedicineGroupCustomItem.php](file:///C:/get/hospital-system/app/Models/UserMedicineGroupCustomItem.php))**:
  * عزل باقات الخدج عبر `department = 'nicu'` مع دعم الباقات العامة لقسم الخدج والخاصة بكل طبيب ومقيم وبأي عدد من الأدوية والباقات، متضمنة النجمة ⭐ والترتيب حسب الاستخدام 🔥.
- **خانة الملاحظات السريرية الكبيرة في تسجيل القراءة السريرية والتمريضية**:
  * إضافة `clinical_notes` إلى `nicu_vitals`، فتح صلاحية التسجيل لأطباء الخدج والمقيمين، وتوفير زرين (**حفظ القراءة الكاملة** و**حفظ الملاحظة فقط**) مع خيار التثبيت برأس الملف وعرض الملاحظات بالجدول.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/NicuEnhancementsTest.php](file:///C:/get/hospital-system/tests/Feature/NicuEnhancementsTest.php))**:
  * اجتياز `52 passed (426 assertions)` عبر `NicuEnhancementsTest`, `UserMedicineGroupTest`, `NicuTabAndStatePersistenceTest`, `NicuGranularPermissionsAndMilkTypesTest`, `SurgeryStoreGovernanceTest`, `NursingSubStoreGovernanceTest`, و `RolePermissionsMatrixTest`، وتوافق تام مع `Laravel Pint` و `view:cache`.

## Session log (2026-11-25 — المرحلة 290: التعديل اللحظي المباشر لأسعار الكاش داخل جدول دليل الأدوية مع الحفظ التلقائي والاشتقاق الذكي Medicines Directory Inline Quick-Pricing & Auto-Save Derivation Engine)

### Done
- **المسار وصلاحيات التعديل السريع ([routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
  * إضافة مسار `PATCH /pharmacy/medicines/{medicine}/quick-price` باسم `pharmacy.medicines.quick-price`.
  * حمايته بصلاحيات `edit medicines` ودور `pharmacy_staff` وأدوار الإدارة.
- **متحكم الأسعار السريعة ([app/Http/Controllers/Pharmacy/MedicineController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/MedicineController.php))**:
  * إضافة دالة `updateQuickPrice` للتحقق وتحديث `sale_price` و `sub_unit_sale_price` واشتقاق سعر الشريط تلقائياً ورد JSON الفوري.
- **واجهة جدول دليل الأدوية التفاعلية ([resources/views/pharmacy/medicines/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/medicines/index.blade.php))**:
  * تحويل نصوص أسعار العلبة والشريط لكادر الصيدلية إلى حقول إدخال رقمية صغيرة وأنيقة.
  * اشتقاق فوري بالمتصفح لسعر الشريط بمجرد كتابة سعر العلبة استناداً لسعة العلبة (`sub_units_count`).
  * حفظ تلقائي بـ AJAX عند الضغط على Enter أو الخروج من الحقل (Blur) دون إعادة تحميل الصفحة.
  * وميض أخضر تفاعلي وشارة `✓ تم الحفظ` الفورية، وتنبيه بالخطأ في حال إدخال قيم سالبة.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/MedicineQuickPricingTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/MedicineQuickPricingTest.php))**:
  * اجتياز كامل حزمة الاختبارات الآلية (`5 passed, 15 assertions`)، واجتياز حزمة الصيدلية الشاملة (`33 passed, 208 assertions`)، وتوافق تام مع `Laravel Pint`.

## Session log (2026-11-25 — المرحلة 288: الربط الآلي المقفل لتصنيف المريض التأميني من الاستعلامات بنقطة البيع والصرف والتسعير المزدوج للصيدلية المتعاقدة Contracted Pharmacy Dual Cash/Insurance POS Engine & Automated Reception Patient Profile Lock)

### Done
- **إلغاء التحديد اليدوي وقفل الوضع المالي من الاستعلامات ([resources/views/pharmacy/pos/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/pos/index.blade.php))**:
  * حذف القوائم المنسدلة لاختيار الضمان من شاشة الصيدلية (الصيدلية لا تحدد ولا تختار نوع المريض).
  * الربط الآلي المقفل ببيانات حجز المريض من الاستعلامات (`insurance_type`, `health_insurance_category_id`, `copay_percentage`).
  * تثبيت شارة رسمية مقفلة للمريض: `🔒 معتمد ومسجل بالاستعلامات: ضمان صحي (فئة X - استقطاع Y%)` أو `🔒 مسجل بالاستعلامات: نقدي كاش (100% نقداً)`.
  * إبقاء البيع المباشر OTC لحالات زبائن الشارع غير المسجلين كمرضى بالمستشفى.
- **إبراز تصنيف الاستعلامات بطابور الوصفات الواردة ([app/Http/Controllers/Pharmacy/PharmacyPosController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/PharmacyPosController.php))**:
  * تحديث `pendingPrescriptions()` و `getPrescriptionDetails()` لتضمين شارات تصنيف الاستعلامات والتأمين آلياً تحت اسم كل مريض.
- **التسعير المزدوج واشتقاق أسعار الأشرطة ([app/Models/Medicine.php](file:///c:/wamp64/www/hospital-system/app/Models/Medicine.php))**:
  * إضافة ميثود `getHiSubUnitPriceAttribute()` لحساب سعر شريط الضمان بدقة.
  * مريض الضمان: تطبيق جدول التعاقد الحكومي (`hi_price`) والخصم وفق فئة استقطاع الاستعلامات.
  * مريض الكاش والبيع المباشر: تطبيق أسعار الصيدلية المتعاقدة (`sale_price`, `sub_unit_sale_price`).
  * المواد غير المشمولة بالضمان تُحمّل كاش للمريض مباشرة مع شارة توضيحية.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/PharmacyPosTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacyPosTest.php))**:
  * اجتياز كامل حزمة الاختبارات الآلية بنجاح (`7 passed, 36 assertions`)، وتوافق تام مع `Laravel Pint`.

## Session log (2026-11-24 — المرحلة 287: محرك بحث ذكي للأدوية بشاشة التوريد على نسق ICD-10 مع بيان أسعار الضمان والكاش ICD-10-Styled Smart Medicine Autocomplete Supply Engine)

### Done
- **تطوير شاشة التوريد بمحرك ICD-10 الذكي ([resources/views/pharmacy/batches/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/batches/create.blade.php))**:
  * استبدال القائمة المنسدلة التقليدية بمحرك بحث فوري لحظي يبحث بالاسم التجاري، العلمي، الرمز الوطني، والباركود.
  * قائمة منسدلة منبثقة غنية تبرز: الرمز الوطني `#code`، العيار، الشكل الصيدلاني، معادلة التعبئة `[1 علبة = N شريط]`، وسعر الضمان المعتمد `[🛡️ ضمان: X د.ع]` مقارنة بسعر الكاش.
  * دعم التنقل الكامل بلوحة المفاتيح عبر الأسهم (Arrow Up/Down) واختيار الدواء وتثبيته فوراً بـ (Enter) أو النقر بالماوس.
  * إبراز شارة سعر الضمان للعلبة في السطر بجانب أسعار البيع لتفادي بيع الدواء للمضمونين بخسارة مقارنة بسعر الشراء.
- **تحديث المتحكم ([app/Http/Controllers/Pharmacy/MedicineBatchController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/MedicineBatchController.php))**:
  * تضمين `hi_price` و `barcode` ضمن استعلام الأدوية في `create()`.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/PharmacyMultiItemBatchSupplyTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacyMultiItemBatchSupplyTest.php))**:
  * اجتياز كامل حزمة الاختبارات الآلية (`5 passed, 28 assertions`)، وتوافق تام مع `Laravel Pint`.

## Session log (2026-11-24 — المرحلة 286: إضافة حقل معامل التعبئة القابل للتعديل لكل دواء وحساب إجمالي الأشرطة اللحظي Editable Pack Size & Real-Time Strips Calculation per Batch Row)

### Done
- **تطوير واجهة التوريد بجدول البنود ([resources/views/pharmacy/batches/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/batches/create.blade.php))**:
  * إضافة عمود مخصص وقابل للتعديل: **«أشرطة/العلبة»** (`sub_units_count`) يُملأ تلقائياً من بطاقة الدواء (2، 3، 10... إلخ) وقابل للتعديل لكل صنف وشحنة فوراً.
  * إضافة شارة تفاعلية فورية تحت حقل الكميات بالعلب: `[ = N شريط/قطعة ]` تتحدث لحظياً عند تغيير العلب أو البونص أو معامل التعبئة.
  * ربط التحديث اللحظي باشتقاق تكلفة الشريط الواحد وإجمالي أشرطة الشحنة في خلاصة الفاتورة.
- **تحديث المتحكم ([app/Http/Controllers/Pharmacy/MedicineBatchController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/MedicineBatchController.php))**:
  * استقبال `items.*.sub_units_count` وتحديث بطاقة الدواء تلقائياً لضبط سعة التعبئة الجديدة الموردة.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/PharmacyMultiItemBatchSupplyTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacyMultiItemBatchSupplyTest.php))**:
  * تحديث واجتياز كافة الاختبارات بنجاح 100% (`3 passed, 17 assertions`)، وتوافق تام مع `Laravel Pint`.

## Session log (2026-11-24 — المرحلة 285: اعتماد المعيار الصيدلاني العالمي لنظام التوريد المزدوج واشتقاق تكلفة التجزئة واحتساب البونص وتحديث أسعار البيع Global Dual-Unit Pharmacy Inward Supply, Automatic Sub-Unit Cost Derivation & Bonus Packs Handling)

### Done
- **تطوير متحكم توريد الوجبات ([app/Http/Controllers/Pharmacy/MedicineBatchController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/MedicineBatchController.php))**:
  * تضمين بيانات معامل التعبئة والتجزئة (`sub_units_count`, `sub_unit`, `main_unit`, `sale_price`, `sub_unit_sale_price`) ضمن دالة `create()`.
  * دعم إدخال كميات البونص المجاني (`bonus_quantity`) وحساب التكلفة الفعلية للعلبة (`effectiveBoxCost`) تلقائياً.
  * دعم خيار تحديث أسعار البيع المعتمدة (`sale_price`, `sub_unit_sale_price`) وتحديث سعر آخر تكلفة شراء في دليل الأدوية المركزي تلقائياً.
- **تطوير واجهة التوريد بنظام الوحدات المزدوج ([resources/views/pharmacy/batches/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/batches/create.blade.php))**:
  * إبراز شارة التعبئة المزدوجة لكل دواء بمجرد اختياره: `[1 علبة = N شريط]`.
  * الاشتقاق الآلي اللحظي لتكلفة الشريط الواحد: `[تكلفة الشريط = سعر شراء العلبة ÷ عدد الأشرطة]`.
  * دعم حقل البونص المجاني مع احتساب أثر البونص على خفض تكلفة العلبة والشريط الحقيقية.
  * إتاحة حقول أسعار البيع للجمهور (بيع العلبة وبيع الشريط) مع مفتاح لتحديثها بدليل الأدوية بنقرة واحدة.
  * شريط إحصائي فوري (عدد الأصناف، إجمالي العلب المشتراة، إجمالي البونص المجاني، إجمالي الأشرطة المعادل، وإجمالي قيمة الفاتورة).
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/PharmacyMultiItemBatchSupplyTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacyMultiItemBatchSupplyTest.php))**:
  * بناء واجتياز اختبارات التوريد بالبونص وحساب التكلفة الفعلية وتحديث أسعار البيع بنجاح 100% (`3 passed, 16 assertions`)، مع اجتياز كامل حزمة اختبارات الصيدلية (`6 passed, 37 assertions`)، وتوافق تام مع `Laravel Pint`.

## Session log (2026-11-24 — المرحلة 284: تحويل شاشة توريد الأدوية للصيدلية إلى قائمة توريد متعددة البنود ديناميكية Pharmacy Multi-Item Batch Supply & Inward Invoice Processing)

### Done
- **تطوير متحكم توريد الوجبات ([app/Http/Controllers/Pharmacy/MedicineBatchController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/Pharmacy/MedicineBatchController.php))**:
  * دعم استقبال وحفظ قوائم التوريد المتعددة (`items` array) دفعة واحدة داخل معاملة قاعدة بيانات آمنة (`DB::transaction`) مع توثيق اسم المورد وتاريخ الاستلام ورقم إذن التوريد.
  * الحفاظ التام على التوافق الرجعي (Backward Compatibility) للإدخال الفردي.
- **إعادة تصميم واجهة التوريد كقائمة وفاتورة ديناميكية ([resources/views/pharmacy/batches/create.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/pharmacy/batches/create.blade.php))**:
  * رأس فاتورة موحد (المورد، رقم الفاتورة الورقية، تاريخ الاستلام، وحالة الشحنة).
  * جدول ديناميكي تفاعلي لإضافة وحذف أسطر الأدوية (`itemsTable`) مع جلب تلقائي لسعر التكلفة لكل دواء عند الاختيار.
  * شريط ملخص إحصائي لحظي (إجمالي الأصناف، إجمالي العلب، وإجمالي قيمة الفاتورة بالدينار العراقي).
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/PharmacyMultiItemBatchSupplyTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacyMultiItemBatchSupplyTest.php))**:
  * بناء واجتياز اختبارات عرض الواجهة وحفظ قوائم التوريد المتعددة والتأكد من إيداع الوجبات بنجاح 100% (`4 passed, 20 assertions`)، وتوافق تام مع `Laravel Pint`.

## Session log (2026-11-24 — المرحلة 283: تحصين مسارات وصلاحيات المستودع والمخزن الرئيسي للمستشفى وعزل موقع الصيدلية وحماية المواد وتصفير الوجبات الوهمية Main Warehouse RBAC Hardening, Dummy Batches Zeroing & Complete Pharmacy Decoupling)

### Done
- **تصفير الوجبات الافتراضية الوهمية**:
  * حذف وتصفير جميع الوجبات الوهمية القديمة (3,972 دفعة برقم `INIT-*`) من جدول `medicine_batches` نهائياً وتصفير كمياتها، مع الحفاظ الكامل على كافة الأدوية (3,974 دواء).
- **تحصين مسارات المستودع وصلاحيات Spatie ([routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
  * حماية مسارات الموردين والمواد والمشتريات والمستودعات والتحويلات بميدلوير `role_or_permission` الصارم لمنع كادر الصيدلية أو الكاشير من دخولها برمز `403 Forbidden`.
  * عزل واستبعاد موقع الصيدلية الوهمي القديم (`Location #16`) من كافة استعلامات ومنسدلات المخزن العام في `LocationController`, `InventoryController`, `StockTransferController`.
- **حماية حذف المواد والعمليات المخزنية ([app/Http/Controllers/ProductController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/ProductController.php))**:
  * منع حذف أي مادة في المستودع إذا كانت مرتبطة بوجبات مخزنية (`stockBatches`) أو بنود شراء مسجلة (`purchaseItems`).
  * إسناد موقع الشراء ديناميكياً إلى المخزن الرئيسي وتحديث واجهات الموردين والتحويلات.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/RolePermissionsMatrixTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/RolePermissionsMatrixTest.php))**:
  * تحديث مصفوفة اختبارات الصلاحيات لتعكس فصل بطاقة «الصيدلية ومخزن الأدوية» عن بطاقة «المستودع والمخزن الرئيسي للمستشفى»، واجتياز كافة الاختبارات بنسبة 100% (15 اختبار، 243 توكيد) وتوافق تام مع `Laravel Pint`.

## Session log (2026-11-24 — المرحلة 282: فصل تبويب الصيدلية ومخزن الأدوية عن المستودع والمخزن الرئيسي في شاشة إدارة الأدوار والصلاحيات Role Management Pharmacy & Main Warehouse Modules Separation)

### Done
- **فصل التبويبات والمجموعات البرمجية ([app/Http/Controllers/RoleManagementController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RoleManagementController.php))**:
  * فصل بطاقة ومجموعة الصيدلية لتصبح قسماً مستقلاً: **«الصيدلية ومخزن الأدوية»** (`pharmacy`) تحوي كافة الصلاحيات الصيدلانية حصراً (`view pharmacy`, `dispense medications`, `process pharmacy requests`, `manage medicines`, `create medicines`, `edit medicines`, `delete medicines`, `import medicines`, `manage pharmacy services`).
  * إنشاء بطاقة ومجموعة مستقلة للمستودع العام: **«المستودع والمخزن الرئيسي للمستشفى»** (`inventory`) تحوي صلاحيات المستودع العام حصراً (`manage inventory`, `view inventory`, `products`, `stock_batches`, `stock_movements`, `stock transfers`, `stock transfer requests`, `manage nursing sub stores`, `manage surgery store`, `purchases`, `suppliers`, `manage location thresholds`).
  * فصل تصنيف الأدوار في `getRoleCategory()` ليكون دور `pharmacy_staff` تابعاً لقسم الصيدلية، ودور `inventory_manager` تابعاً لقسم المستودع العام.
- **شاشة تعديل الأدوار ([resources/views/roles/edit.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/roles/edit.blade.php), `/roles/7/edit`)**:
  * ظهور قسمين منفصلين تماماً في الأكورديون مع أيقونات ووصف وشارات تفصيلية لكل قسم بشكل معزول ومستقل بدلاً من الدمج السابق.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/RolePharmacyInventorySeparationTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/RolePharmacyInventorySeparationTest.php))**:
  * اجتياز اختبارات الفصل وتأكيد استقلال بطاقة الصيدلية عن بطاقة المستودع وظهور كافة عناصر الصلاحيات لكل قسم بنجاح 100% (`1 passed, 10 assertions`)، وتوافق تام مع `Laravel Pint`.

## Session log (2026-11-24 — المرحلة 281: إبراز كافة روابط القائمة الجانبية للصيدلية ومزامنة الصلاحيات لكادر الصيدلية Pharmacy Staff Complete Sidebar Links & Permissions Synchronization)

### Done
- **قاعدة البيانات والصلاحيات ([database/migrations/2026_11_24_200000_sync_pharmacy_staff_permissions.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_11_24_200000_sync_pharmacy_staff_permissions.php))**:
  * منح ومزامنة صلاحيات الصيدلية الكاملة لكادر الصيدلية (`pharmacy_staff` و `pharmacist`): `create medicines`, `edit medicines`, `delete medicines`, `manage medicines`, `manage pharmacy services`, `import medicines`, `dispense medications`.
  * تصفير وتحديث كاش الصلاحيات بنجاح (`permission:cache-reset`).
- **واجهة القائمة الجانبية ([resources/views/layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php))**:
  * إبراز كافة الروابط الـ 7 لقسم الصيدلية كاملة ودون حجب لموظف الصيدلية (`pharmacy_staff`, `pharmacist`):
    1. نقطة البيع والصرف (POS)
    2. سجل مبيعات الصيدلية
    3. تتبع الشحنات والصلاحية (FEFO)
    4. دليل الأدوية والبدائل
    5. إضافة دواء / مستلزم جديد
    6. الخدمات الصيدلانية
    7. استيراد وتحديث الأدوية (إكسل)
  * التأكيد القاطع على استقلالية الصيدلية التامة عن مخزن المستشفى العام.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/PharmacySidebarAndPermissionsTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/PharmacySidebarAndPermissionsTest.php))**:
  * بناء واجتياز اختبارات التحقق من ظهور الروابط الـ 7 لكادر الصيدلية والوصول لشاشات الإضافة والاستيراد بنسبة 100% (`2 passed, 10 assertions`)، وتوافق تام مع `Laravel Pint`.

## Session log (2026-11-24 — المرحلة 280: لوحة القيادة المستقلة لرقابة مخازن وعُهد صالات العمليات الجراحية وإدارة أمناء العهد وتتبع صرفيات العمليات Operation Theaters & Main Surgery Store Governance Dashboard, Custody Tracking & Clinical Consumption Auditing)

### Done
- **قاعدة البيانات والصلاحيات والعهدة الافتراضية ([database/migrations/2026_11_24_100000_create_surgery_store_governance.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_11_24_100000_create_surgery_store_governance.php))**:
  * إنشاء صلاحية `manage surgery store` وربطها تلقائياً بكادر العمليات `surgery_staff` وأدوار الإدارة (`admin`, `admin-hsop`, `hospital_admin`, `super_admin`).
  * تعيين التكليف الأولي التلقائي لعهدة مخزن العمليات الرئيسي (`Location #25`) وصالات العمليات (`Location #3`) لأمين العهدة وموظف العمليات المسؤول (المستخدم 92 `surgery@hospital.com`) وتوثيق الأرشيف الرقابي في `location_custodian_histories`.
- **التحكم والمسارات السريرية الجراحية ([app/Http/Controllers/SurgeryStoreController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgeryStoreController.php), [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
  * حصر المخازن والصالات برمجياً بالمواقع الجراحية (`مخزن عمليات الرئيسي` و `صالات العمليات`) واستبعاد صالات الولادة وردهات التمريض والمخازن الإدارية.
  * دالة `index()`: إحصائيات علوية KPIs (المخازن والصالات، إجمالي المواد بالرصيد، حركات صرفيات العمليات للشهر الحالي، العمليات الجراحية المجدولة لليوم، وطلبيات التعزيز المعلقة).
  * دالة `show()`: استعراض تفاصيل الموقع بـ 4 تبويبات تفاعلية (الأرصدة والوجبات المتوفرة، سجل صرفيات العمليات وربطها بالجراح والمريض ونوع العملية، أرشيف تسليم واستلام العهدة، وطلبيات تعزيز المخزن).
  * دالة `assignCustodian()`: تكليف وتبديل أمين العهدة وتحديث موقعه وتوثيق الأرشيف.
- **الواجهات التفاعلية والقائمة الجانبية ([resources/views/surgery-store/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-store/index.blade.php), [resources/views/surgery-store/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgery-store/show.blade.php), [resources/views/layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php))**:
  * بطاقات KPIs علوية، جدول مخازن وصالات العمليات مع شارات المسؤولية والأرصدة، وجدول تتبع العمليات الجراحية لليوم للتجهيز المسبق للمستلزمات.
  * نافذة Modal لتكليف وتبديل أمين العهدة مع خيار مزامنة موقع الموظف تلقائياً.
  * إضافة رابط بالقائمة الجانبية تحت قسم العمليات الجراحية: "مخزن وعُهد صالات العمليات" مرتبط بصلاحية `manage surgery store` ودور `surgery_staff`.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/SurgeryStoreGovernanceTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgeryStoreGovernanceTest.php))**:
  * بناء واجتياز اختبارات الوصول، الصلاحيات، استبعاد المواقع غير الجراحية، تكليف وتبديل المسؤول، وعرض الأرصدة وسجل استهلاك العمليات بنسبة 100% (`4 passed, 21 assertions`).
  * اجتياز اختبارات `NursingSubStoreGovernanceTest` (`4 passed, 23 assertions`) و `FloorCustodianStockTransferTest` (`13 passed, 95 assertions`)، وتوافق تام مع `Laravel Pint`.

## Session log (2026-11-24 — المرحلة 279: إضافة صلاحيات مستقلة للتحكم بواجهة وإدارة مساعدي الجراحين والتخدير عبر مصفوفة الأدوار والصلاحيات Independent Granular Permissions for Surgical Assistants Directory & Roles Governance)

### Done
- **قاعدة البيانات والصلاحيات ([database/migrations/2026_11_24_100000_add_surgical_assistants_permissions.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_11_24_100000_add_surgical_assistants_permissions.php), [database/seeders/RolesAndPermissionsSeeder.php](file:///c:/wamp64/www/hospital-system/database/seeders/RolesAndPermissionsSeeder.php))**:
  * إنشاء صلاحيتين مستقلتين: `view surgical assistants` (عرض الدليل والسجل) و `manage surgical assistants` (إدارة وإضافة وتعديل وحذف).
  * منحهما تلقائياً لأدوار الإدارة (`admin`, `hospital_admin`, `admin-hsop`) وكادر العمليات (`surgery_staff`).
- **إدارة الأدوار والصلاحيات ([app/Http/Controllers/RoleManagementController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/RoleManagementController.php))**:
  * إدراج الصلاحيتين تحت تصنيف `surgeries` لكي تظهرا في شاشة إنشاء وتعديل الأدوار (`/roles`).
- **التحكم والحماية بالواجهات ([app/Http/Controllers/SurgicalAssistantController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/SurgicalAssistantController.php), [resources/views/layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php), [resources/views/surgeries/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/surgeries/show.blade.php))**:
  * قصر الوصول بدقة وصرامة على الصلاحيات المستقلة `view surgical assistants` و `manage surgical assistants` وإلغاء الاستثناءات العامة السابقة (`view surgeries`, `edit surgeries`, `surgery_staff`) لضمان انضباط التفعيل والتعطيل من شاشة الأدوار بنسبة 100%.
  * ربط `index()` بصلاحية `view surgical assistants` مع وضع القراءة فقط إذا لم تتوفر صلاحية الإدارة.
  * ربط `store()`, `update()`, `toggleStatus()`, `destroy()` بصلاحية `manage surgical assistants`.
  * تحديث القائمة الجانبية وشاشة تفاصيل العملية لتشمل الصلاحيات الجديدة حصراً.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/SurgicalAssistantTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
  * إضافة اختبارات التحقق من الصلاحيات والمنع (403) والسماح واجتياز كافة الاختبارات بنسبة 100% (`10 passed, 35 assertions`)، وتنسيق الأكواد بـ `Laravel Pint`.

## Session log (2026-11-23 — المرحلة 278: لوحة القيادة المستقلة لرقابة مخازن وعُهد ردهات التمريض وإدارة أمناء العهد وتتبع صرفيات المرضى Floor Ward Nursing Sub-Stores & Custody Governance Dashboard)

### Done
- **قاعدة البيانات ونموذج العهدة ([database/migrations/2026_11_23_210900_create_nursing_sub_stores_governance.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_11_23_210900_create_nursing_sub_stores_governance.php), [app/Models/Location.php](file:///c:/wamp64/www/hospital-system/app/Models/Location.php), [app/Models/LocationCustodianHistory.php](file:///c:/wamp64/www/hospital-system/app/Models/LocationCustodianHistory.php))**:
  * إضافة حقل `custodian_id` لجدول `locations` لتعيين أمين العهدة المسؤول وربط علاقة `custodian()` و `custodianHistories()`.
  * إنشاء جدول الأرشيف الرقابي `location_custodian_histories` لتوثيق التسليم والاستلام والآمر بالصرف والملاحظات.
  * إنشاء صلاحية `manage nursing sub stores` ومنحها تلقائياً لدور `head_nurse` وأدوار الإدارة.
- **التحكم والفلترة الدقيقة لردهات التمريض ([app/Http/Controllers/NursingSubStoreController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/NursingSubStoreController.php), [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
  * قصر المخازن الفرعية برمجياً على ردهات وطوابق التمريض السريرية فقط (الطابق الثاني، الثالث، الرابع، الخامس) المرتبطة بكادر التمريض عبر `users.location_id` واستبعاد صالات العمليات والخدج والمخازن الإدارية.
  * دالة `assignCustodian()` لتكليف وتبديل أمين العهدة وتحديث موقعه، ودالة `show()` لعرض الأرصدة وصرفيات المرضى.
- **الواجهات التفاعلية والتقسيم الذكي ([resources/views/nursing-sub-stores/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/nursing-sub-stores/index.blade.php), [resources/views/nursing-sub-stores/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/nursing-sub-stores/show.blade.php), [resources/views/layouts/app.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/layouts/app.blade.php))**:
  * إضافة بطاقات KPIs في القمة، وجدول ردهات الطوابق مع شارات المسؤولية.
  * في نافذة تكليف المسؤول: تقسيم الممرضين بمجموعتين (`optgroup`): كادر تمريض نفس الردهة أولاً، ثم باقي كادر التمريض بالمستشفى.
  * صفحة تفاصيل الردهة بـ 4 تبويبات: الأرصدة والوجبات المتوفرة، سجل صرفيات المرضى، أرشيف تسليم واستلام العهدة، وطلبيات المخزن.
  * إضافة رابط مباشر بالقائمة الجانبية: "رقابة مخازن وعُهد التمريض".
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/NursingSubStoreGovernanceTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/NursingSubStoreGovernanceTest.php))**:
  * بناء واجتياز اختبارات الوصول، الصلاحيات، استبعاد المخازن غير التمريضية، تكليف وتبديل المسؤول، واستعراض الصرفيات بنسبة 100% (`4 passed, 23 assertions`).
  * اجتياز كامل اختبارات `FloorCustodianStockTransferTest` بنسبة 100% (`13 passed, 95 assertions`)، وتوافق تام مع `Laravel Pint`.

## Session log (2026-11-23 — المرحلة 277: دورة تأكيد استلام طلبيات المخزن لكادر تمريض الردهات وصالات العمليات وتوثيق الاستلام الرسمي للعهدة Floor & Operation Theater Stock Transfer Receipt Confirmation Workflow & Custody Auditing)

### Done
- **قاعدة البيانات ونموذج الطلب ([database/migrations/2026_11_23_195000_add_receipt_fields_to_stock_transfer_requests_table.php](file:///c:/wamp64/www/hospital-system/database/migrations/2026_11_23_195000_add_receipt_fields_to_stock_transfer_requests_table.php), [app/Models/StockTransferRequest.php](file:///c:/wamp64/www/hospital-system/app/Models/StockTransferRequest.php))**:
  * إضافة حقول `received_by` (ربط مع المستخدمين)، `received_at` (توقيت الاستلام)، و `received_notes` (ملاحظات الاستلام).
  * ربط علاقة Eloquent المعيارية `receivedBy()` وإضافة الحقول لـ `$fillable` و `$casts`.
- **التحكم والمسارات وصلاحيات كادر الردهات والعمليات ([app/Http/Controllers/StockTransferController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/StockTransferController.php), [routes/web.php](file:///c:/wamp64/www/hospital-system/routes/web.php))**:
  * مسار `POST stock-transfers/requests/{stockTransferRequest}/receive`.
  * إضافة دالة `receiveRequest()`: التحقق من الصلاحيات (حصر التأكيد بكادر تمريض الردهات، كادر صالات العمليات `surgery_staff`، مقدم الطلب، أو رئيس التمريض والإدارة).
  * تحديث الحالة إلى `completed` (مستلم ومودع بالعهدة) وتوثيق اسم المستلم وتاريخ الاستلام والملاحظات.
- **الواجهات التفاعلية ([resources/views/stock-transfers/requests/show.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/stock-transfers/requests/show.blade.php), [resources/views/stock-transfers/requests/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/stock-transfers/requests/index.blade.php))**:
  * بناء بطاقة تفاعلية واضحة `#receiveStockCard` لكادر الردهة والعمليات لتأكيد استلام الشحنة وإيداعها، مع حقل لملاحظات الاستلام وزر تأكيد واضح.
  * عرض شريط حالة أخضر رسمي عند اكتمال الاستلام مع إظهار اسم المستلم وتوقيت الاستلام وملاحظاته.
  * إبراز الفروقات والكميات المعدلة من المخزن الرئيسي مع الشارات في جدول تفاصيل الشحنة وسجل الطلبات العام.
  * إضافة زر استلام سريع `استلام` في جدول الطلبات لكادر التمريض والعمليات.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/FloorCustodianStockTransferTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/FloorCustodianStockTransferTest.php))**:
  * بناء واجتياز اختبارات تأكيد الاستلام للردهات وصالات العمليات، ومنع الكوادر غير المخولة، بنسبة 100% (`13 passed, 95 assertions`)، واجتياز `Laravel Pint`.

## Session log (2026-11-23 — المرحلة 276: البحث الفوري اللحظي والتصفية الذكية في أرشيف جميع زيارات الطبيب والزيارات المعلقة بدون أزرار Instant Zero-Click Real-Time Search & Multi-Filter for Doctor Visits Archive & Incomplete Visits)

### Done
- **الواجهة التفاعلية الفورية بدون أزرار ([resources/views/doctors/visits/index.blade.php](file:///c:/wamp64/www/hospital-system/resources/views/doctors/visits/index.blade.php))**:
  * بناء شريط بحث وفلترة لحظي أعلى جدول أرشيف جميع الزيارات: حقل بحث نصي سريع، منسدلة الحالات (مكتملة، غير مكتملة/معلقة، جاري الفحص، بالانتظار، ملغية)، وفلاتر التاريخ (من/إلى)، وزر تفريغ ومسح فوري.
  * تنفيذ دالة JavaScript لحظية `applyArchiveFilters()` تعمل فورياً على حدثي `input` و `change` لتصفية الصفوف لحظياً بالمتصفح بدون الحاجة لأي زر بحث وبدون إعادة تحميل الصفحة.
  * إضافة حقل تصفية سريع للزيارات المعلقة في تبويب الزيارات المعلقة `#incomplete-pane`.
  * ربط بيانات كل صف بـ `data-search-text` و `data-status` و `data-date` مع إظهار عداد النتائج المطابقة لحظياً وإظهار رسالة عند عدم وجود تطابق.
  * دعم حفظ ومزامنة التبويبات تلقائياً عبر URL Query (`?tab=archive`) وتحديث history state عند التبديل.
- **توسيع استعلام الأرشيف بالباك إند ([app/Http/Controllers/DoctorVisitController.php](file:///c:/wamp64/www/hospital-system/app/Http/Controllers/DoctorVisitController.php))**:
  * رفع سقف استعلام الأرشيف إلى 500 زيارة لضمان توفر كافة الزيارات الحديثة والسابقة فورياً بذاكرة المتصفح.
  * دعم معاملات البحث السيرفرية (`search`, `status`, `date_from`, `date_to`) للبحث في الاسم، الهاتف، الرقم الوطني، رقم الإضبارة/المعرف، والشكوى السريرية.
- **القاموس والاختبارات الآلية ([lang/en.json](file:///c:/wamp64/www/hospital-system/lang/en.json), [tests/Feature/DoctorVisitsArchiveSearchTest.php](file:///c:/wamp64/www/hospital-system/tests/Feature/DoctorVisitsArchiveSearchTest.php))**:
  * إضافة وتوثيق 10 مفاتيح ترجمة إنجليزية جديدة بالقواميس.
  * بناء واجتياز اختبار الميزة `DoctorVisitsArchiveSearchTest` بنسبة 100% (`1 passed, 22 assertions`)، وتنسيق الأكواد بـ `Laravel Pint`.

## Session log (2026-11-23 — المرحلة 191: واجهات شجرة الحسابات التفاعلية Split-View، شجرة مراكز التكلفة، محرك التفقيط، ونظام السندات والطباعة A4 Phase 2: Interactive Chart & Cost Centers Tree UI, Tafqeet Engine, Vouchers & Official A4 Printing)

### Done
- **شجرة الحسابات التفاعلية بنمط Split-View ([resources/views/accounting/chart/](file:///C:/get/hospital-system/resources/views/accounting/chart/))**:
  * شجرة تفاعلية هرمية متدرجة مع أيقونات تميز الحسابات التجميعية (1-4 📁) عن التحليلية (5-6 🟢).
  * لوحة تفاصيل الحساب اللحظية مع الرصيد وسجل آخر 10 حركات ونافذة منبثقة لإضافة حساب ابن مع اقتراح الكود تلقائياً.
- **شجرة مراكز التكلفة الميدانية ([resources/views/accounting/cost-centers/](file:///C:/get/hospital-system/resources/views/accounting/cost-centers/))**:
  * استعراض العناقيد الـ 5 لـ 32 مركز تكلفة يطابق صالات العمليات، الردهات، الطوارئ، الخدج، العيادات، المختبر، الأشعة، والمخازن.
- **محرك التفقيط بالدينار العراقي ([app/Services/Accounting/TafqeetService.php](file:///C:/get/hospital-system/app/Services/Accounting/TafqeetService.php))**:
  * تحويل المبالغ المالية إلى نص عربي معتمد بالدينار والفلس حتى خانة المليارات مع نقطة نهاية API تفاعلية لحظية.
- **نظام السندات المعتمدة والطباعة الرسمية A4 ([app/Http/Controllers/Accounting/VoucherController.php](file:///C:/get/hospital-system/app/Http/Controllers/Accounting/VoucherController.php), [resources/views/accounting/vouchers/](file:///C:/get/hospital-system/resources/views/accounting/vouchers/))**:
  * سند قبض (`RV`)، سند صرف (`PV`)، وقيد تسوية عام (`JV`) مع التحقق اللحظي من توازن القيد والتفقيط المباشر.
  * دعم الأرشفة الضوئية الرقمية بإرفاق صور الفواتير والوصولات الورقية.
  * نموذج طباعة رسمي A4 مع الترويسة، التفقيط التلقائي، والمربعات الثلاثية للتوقيعات الرسمية (المحاسب، التدقيق، والآمر بالصرف/المستلم).
  * محرك عكس القيد الرقابي مع توثيق سبب العكس في سجل التدقيق المالي.
- **التحقق الآلي**:
  * اجتياز كامل حزمة الاختبارات الآلية [`tests/Feature/AccountingVoucherAndChartTest.php`](file:///C:/get/hospital-system/tests/Feature/AccountingVoucherAndChartTest.php) بنجاح 100% (`11 passed, 36 assertions`)، بالإضافة لاختبارات البنية التحتية [`tests/Feature/AccountingCoreTest.php`](file:///C:/get/hospital-system/tests/Feature/AccountingCoreTest.php) (`4 passed, 29 assertions`).

## Session log (2026-11-23 — المرحلة 190: تأسيس البنية التحتية للنظام المحاسبي الموحد العراقي المحدث IFRS، شجرة الحسابات، شجرة مراكز التكلفة، محرك العملات، والقيود المحاسبية المعزولة Phase 1: Iraqi Unified Accounting System Infrastructure, Modular Schema, 1160 Accounts Tree, Cost Centers & Test Suite)

### Done
- **معمارية الجداول المعزولة ببادئة `acc_` ([database/migrations/2026_11_23_100000_create_accounting_core_tables.php](file:///C:/get/hospital-system/database/migrations/2026_11_23_100000_create_accounting_core_tables.php))**:
  * بناء 8 جداول مستقلة تماماً وقابلة للانسلاخ والبيع كـ SaaS أو On-Premise: `acc_currencies`, `acc_cost_centers`, `acc_accounts`, `acc_fiscal_years`, `acc_fiscal_periods`, `acc_journal_entries`, `acc_journal_entry_lines`, `acc_audit_logs`.
  * دعم حقل تعدد الشركات (`company_id`) في جميع الجداول لعزل المستأجرين مستقبلاً عبر `Global Scope`.
- **النماذج البرمجية المستقلة ([app/Models/Accounting/](file:///C:/get/hospital-system/app/Models/Accounting/))**:
  * بناء 8 نماذج Eloquent وفق معايير Laravel 12: [`Currency.php`](file:///C:/get/hospital-system/app/Models/Accounting/Currency.php), [`CostCenter.php`](file:///C:/get/hospital-system/app/Models/Accounting/CostCenter.php), [`Account.php`](file:///C:/get/hospital-system/app/Models/Accounting/Account.php), [`FiscalYear.php`](file:///C:/get/hospital-system/app/Models/Accounting/FiscalYear.php), [`FiscalPeriod.php`](file:///C:/get/hospital-system/app/Models/Accounting/FiscalPeriod.php), [`JournalEntry.php`](file:///C:/get/hospital-system/app/Models/Accounting/JournalEntry.php), [`JournalEntryLine.php`](file:///C:/get/hospital-system/app/Models/Accounting/JournalEntryLine.php), [`AuditLog.php`](file:///C:/get/hospital-system/app/Models/Accounting/AuditLog.php).
  * دعم التحقق الآلي من توازن القيد (`isBalanced`), إنشاء أرقام القيود التسلسلية (`generateEntryNumber`), وعكس القيود الرقابي مع سجل التدقيق (`reverse`).
- **مزودات البيانات وشجرة الحسابات العراقية ومراكز التكلفة ([database/seeders/](file:///C:/get/hospital-system/database/seeders/))**:
  * تغذية شجرة الدليل المحاسبي الموحد العراقي بالكامل (1,160 حساباً هرمياً) عبر [`AccChartOfAccountsSeeder.php`](file:///C:/get/hospital-system/database/seeders/AccChartOfAccountsSeeder.php) مع فرض قاعدة التقييد الصارمة: المستويات 1 إلى 4 حسابات تجميعية غير قابلة للتقييد (638 حساباً)، والمستويان 5 و 6 فقط يقبلان القيود (522 حساباً تحليلياً).
  * تغذية العملات الرسمية عبر [`AccCurrencySeeder.php`](file:///C:/get/hospital-system/database/seeders/AccCurrencySeeder.php): الدينار العراقي كعملة افتراضية أساسية (1.0000)، والدولار الأمريكي بالسعر الرسمي القابل للتعديل (1320.0000).
  * تغذية شجرة مراكز التكلفة (32 مركزاً عبر 5 عناقيد رئيسية تطابق صالات العمليات، الرقود، الطوارئ، الخدج، العيادات، المختبر، الأشعة، والمخازن) عبر [`AccCostCenterSeeder.php`](file:///C:/get/hospital-system/database/seeders/AccCostCenterSeeder.php).
  * تغذية السنة المالية 2026 بـ 12 فترة شهرية لإقفال الفترات عبر [`AccFiscalYearSeeder.php`](file:///C:/get/hospital-system/database/seeders/AccFiscalYearSeeder.php).
- **التحقق الآلي والاختبارات الشاملة**:
  * بناء واجتياز حزمة الاختبارات الآلية [`tests/Feature/AccountingCoreTest.php`](file:///C:/get/hospital-system/tests/Feature/AccountingCoreTest.php) بنجاح 100% (`4 passed, 29 assertions`).
  * توافق تام مع تنسيق `Laravel Pint` وتوثيق الخطة المعمارية الشاملة في [`docs/النظام_المحاسبي_الموحد_وثيقة_المعمارية_الشاملة.md`](file:///C:/get/hospital-system/docs/النظام_المحاسبي_الموحد_وثيقة_المعمارية_الشاملة.md).



## Session log (2026-11-22 — المرحلة 275: الترقية التلقائية لحساب رئيس ومشرف التمريض في بيئة الإنتاج وتثبيت الصلاحيات الإشرافية بالميجريشن Automated Head Nurse Supervisor Role Promotion & Permission Synchronization Migration)

### Done
- **تشخيص سبب عدم تغير الحالة على هوستنجر**:
  * حساب المستخدم (عبدالله الحسين `abdullahalhussen247@gmail.com`) كان مربوطاً بجدول `model_has_roles` برقم الدور 11 (`nurse`).
  * الميجريشن السابقة أنشأت الدور `head_nurse` بجدول `roles` فقط ولم تعدل سجل المستخدم بجدول ربط الأدوار.
- **ميجريشن الترقية التلقائية ([database/migrations/2026_11_22_133000_promote_head_nurse_supervisor_user.php](file:///C:/get/hospital-system/database/migrations/2026_11_22_133000_promote_head_nurse_supervisor_user.php))**:
  * البحث التلقائي عن حساب عبدالله الحسين أو أي حساب مسمى مشرف/رئيس التمريض وترقيته تلقائياً عبر `$user->syncRoles(['head_nurse'])` وتحديث `role = 'head_nurse'`.
  * منح دور `head_nurse` صلاحيات `manage nurse availability` و `view nursing station` ومسح كاش الصلاحيات.
- **التحقق والاختبارات الآلية**:
  * تنفيذ الميجريشن محلياً بنجاح، واجتياز اختبارات `NurseAvailabilityTest` بنسبة 100% (`11 passed, 46 assertions`)، وتنسيق الأكواد بـ `Laravel Pint`.

## Session log (2026-11-22 — المرحلة 274: فصل رئيس ومشرف التمريض عن قائمة كادر التمريض المجدول وتمكين الصلاحيات الإشرافية Segregate Head Nurse Supervisor from Staff Nurse Duty Scheduling & Authorize Full Supervisory Governance)

### Done
- **عزل المشرف عن جدول دوام الممرضين ([app/Http/Controllers/NurseAvailabilityController.php](file:///C:/get/hospital-system/app/Http/Controllers/NurseAvailabilityController.php), [resources/views/nurse-availability/index.blade.php](file:///C:/get/hospital-system/resources/views/nurse-availability/index.blade.php))**:
  * تصحيح استعلام كادر التمريض `$nursesQuery` في `index()` و `bulkUpdate()` باستبعاد دور `head_nurse` وأدوار الإدارة نهائياً عبر `whereDoesntHave('roles', fn($rq) => $rq->whereIn('name', ['head_nurse', 'admin', 'admin-hsop', 'hospital_admin', 'super_admin']))`.
  * إزالة شارة مشرف التمريض من جدول التمريض التابع، بحيث لا يظهر رئيس/مشرف التمريض كممرض يُفتح ويُغلق دوامه بالمفتاح التفاعلي بل يدير الجدول كمشرف ومسؤول.
- **حصانة حساب مشرف التمريض بالنظام ([app/Models/User.php](file:///C:/get/hospital-system/app/Models/User.php))**:
  * تحديث دالة `isNurseAvailableToday()` لإعادة `true` فورياً لأي مستخدم يحمل دور `head_nurse` أو أحد الأدوار الإدارية، لمنع حجب المشرف أو توجيهه لشاشة خارج الدوام مهما كانت حالته بقاعدة البيانات أو في حال وجود بقايا قديمة لحقل `role = nurse`.
- **تثبيت الصلاحيات بالميجريشن ([database/migrations/2026_11_22_120000_ensure_nurse_roles_exist_in_roles_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_22_120000_ensure_nurse_roles_exist_in_roles_table.php))**:
  * ربط صلاحيتي `manage nurse availability` و `view nursing station` تلقائياً بدور `head_nurse` عند تنفيذ الميجريشن بالسيرفر لضمان قدرته على التحكم الكامل بكادر التمريض ومحطة التمريض.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/NurseAvailabilityTest.php](file:///C:/get/hospital-system/tests/Feature/NurseAvailabilityTest.php))**:
  * إضافة فحص يؤكد عدم ظهور حساب مشرف التمريض ضمن جدول كادر التمريض للمدير والمشرف، واجتياز كامل اختبارات دوام التمريض بنسبة 100% (`11 passed, 44 assertions`) واجتياز فحص `Laravel Pint`.

## Session log (2026-11-22 — المرحلة 273: حل استثناء فقدان دور تمريض الخدج والعناية وحصانة استعلامات جدول توفر التمريض Immunity for Nurse Availability Query against Missing Roles & Automated Role Seeding Migration)

### Done
- **الحل البرمجي الوقائي في الكنترولر ([app/Http/Controllers/NurseAvailabilityController.php](file:///C:/get/hospital-system/app/Http/Controllers/NurseAvailabilityController.php))**:
  * استبدال `$q->role(...)` المباشر باستعلام العلاقات المعياري المتين `whereHas('roles', fn($rq) => $rq->whereIn('name', ['nurse', 'nicu_nurse', 'icu_nurse', 'head_nurse']))->orWhere('role', 'nurse')`.
  * حماية النظام ومنع انهيار الصفحة بخطأ 500 (`RoleDoesNotExist: There is no role named 'nicu_nurse' for guard 'web'`) في بيئات العمل أو الاستضافة غير المحدثة الأدوار.
- **ميجريشن التوليد التلقائي للأدوار السريرية ([database/migrations/2026_11_22_120000_ensure_nurse_roles_exist_in_roles_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_22_120000_ensure_nurse_roles_exist_in_roles_table.php))**:
  * إنشاء ميجريشن تنفذ تلقائياً عند تشغيل `php artisan migrate` في السيرفر وتنشئ الأدوار السريرية المفقودة (`nurse`, `nicu_nurse`, `icu_nurse`, `head_nurse`, `anesthesia`) بجدول `roles` مع مسح الكاش تلقائياً.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/NurseAvailabilityTest.php](file:///C:/get/hospital-system/tests/Feature/NurseAvailabilityTest.php))**:
  * إضافة اختبار ميزة مخصص `test_nurse_query_does_not_throw_when_role_does_not_exist_in_database` يؤكد فتح صفحة إدارة توفر التمريض بنجاح (HTTP 200) حتى لو حذف الدور من قاعدة البيانات.
  * اجتياز كامل اختبارات دوام التمريض بنسبة 100% (`11 passed, 42 assertions`) واجتياز فحص `Laravel Pint`.

## Session log (2026-11-21 — المرحلة 272: توحيد واجهة كاشير الطوارئ مع باقي الأقسام بإضافة خيارات [تسديد / تأجيل / إلغاء] لكل خدمة والتحكم المباشر بالأسعار وإعادة احتساب الفاتورة لحظياً Unify Emergency Cashier Payment with Segmented Action Pills [Pay/Postpone/Cancel], Direct In-Line Price Overrides, Real-Time Recalculation, and Split Payment Handling)

### Done
- **الواجهة التفاعلية وإعادة هندسة جدول البنود ([resources/views/cashier/emergency-payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/emergency-payment-form.blade.php))**:
  * إضافة أزرار الإجراءات المقسمة (Segmented Control Pills) `[تسديد / تأجيل / إلغاء]` لكل بند (خدمات طوارئ، تحاليل، أشعة، استشارات، ورسوم متابعة) مع ألوان واضحة (أخضر للتسديد، برتقالي للتأجيل، أحمر للإلغاء).
  * إضافة حقل تعديل السعر المعتمد مباشرة في الجدول لكل خدمة، مما يمنح الكاشير مرونة كاملة لتعديل الأسعار حسب الحالة أو التوجيه.
  * إضافة قائمة منسدلة لتحديد سبب الإلغاء أو التأجيل (عدم رغبة المريض، قلة السيولة، ضيق الوقت، أو بتوجيه الطبيب).
  * إضافة شريط شارات إحصائي أعلى الجدول يوضح عدد البنود المسددة، المؤجلة، والملغاة لحظياً.
  * إضافة دوال JavaScript تفاعلية (`handleItemActionChange`, `handleItemPriceChange`, و `recalculateEmergencyDiscount`) لإعادة احتساب الإجماليات، حصة المريض، حصة الضمان، والخصومات تلقائياً عند تغيير الإجراء أو السعر دون إعادة تحميل الصفحة.
  * تحويل زر التأكيد ديناميكياً بحسب الإجراءات (تأكيد القبض، حفظ وتأجيل الخدمات، أو تأكيد الإلغاء).
- **معالجة العمليات في الباك إند ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * قراءة مصفوفات `item_actions` و `item_custom_prices` و `item_reasons`.
  * معالجة بنود الإلغاء: حذف خدمة الطوارئ من جدول الربط، إلغاء طلبات المختبر والأشعة والاستشارات وتصفير رسوم المتابعة.
  * معالجة بنود التأجيل: فصلها عن الدفعة الحالية وإبقاؤها معلقة ليتم دفعها لاحقاً.
  * معالجة بنود التسديد: اعتماد الأسعار المعدلة واحتساب الحصص والخصومات بدقة وربطها بالدفعة وتوليد قيود إيرادات الاستشارة 100% للطبيب.
  * في حال تأجيل أو إلغاء كامل البنود دون تحصيل نقدي: إلغاء الدفعة الفارغة وتحديث حالة الطوارئ دون أخطاء.
  * في حال بقاء خدمات مؤجلة بعد تسديد جزئي: إعادة توليد إشعار دفع جديد للخدمات المتبقية تلقائياً عبر `upsertUnifiedEmergencyPayment`.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/EmergencyConsultationPaymentTest.php](file:///C:/get/hospital-system/tests/Feature/EmergencyConsultationPaymentTest.php))**:
  * بناء واجتياز اختبار آلي شامل `test_emergency_cashier_can_postpone_and_cancel_items_and_override_prices` يؤكد صحة التأجيل، الإلغاء، تعديل الأسعار، وإعادة توليد الدفعات للخدمات المؤجلة بنجاح 100% (`4 passed, 46 assertions`).
  * اجتياز تدقيق التنسيق `Laravel Pint` وتصفية وإعادة تجميع قوالب Blade (`view:clear`, `view:cache`).

## Session log (2026-11-21 — المرحلة 271: التعريب والترجمة الإنجليزية الكاملة بنسبة 100% لكافة واجهات قسم الكاشير والمدفوعات والمطالبات وتصفير المفاتيح الناقصة في القاموس Full 100% Cashier Module Localization Across All 38 Views & Complete lang/en.json Dictionary Sync)

### Done
- **تعريب وترجمة كافة واجهات قسم الكاشير والمدفوعات الـ 38 بنسبة 100% ([resources/views/cashier/](file:///C:/get/hospital-system/resources/views/cashier/))**:
  * **استمارات الدفع والقبض**: استمارة العيادات الخارجية والسونار (`payment-form.blade.php`)، الفحوصات الطبية والمختبر والأشعة (`request-payment-form.blade.php`)، كاشير العمليات الجراحية المتقدم (`surgeries/payment-form.blade.php`)، كاشير الطوارئ ومطالبات الاستشارات (`emergency-payment-form.blade.php`)، كاشير العناية المركزة للكبار CCU (`ccu/payment-form.blade.php`)، وكاشير قسم الخدج والحواضن NICU (`nicu/payment-form.blade.php`).
  * **لوحات التحكم والتقارير الرقابية**: لوحة الكاشير الرئيسية والعداد اللحظي والتبويبات (`index.blade.php`)، تقرير الحركات اليومية وفلاتر الصناديق والضمان (`report.blade.php` و `report-print.blade.php`)، سجل العمليات الملغاة وإبطال الإيصالات الرقابي (`payments/void-logs.blade.php` و `payments/void.blade.php`)، وسجل الفواتير المدفوعة (`payments.blade.php`).
  * **كشوفات وحسابات العمليات الجراحية**: جدول العمليات قيد الانتظار (`surgeries/index.blade.php`)، العمليات المسددة وأرشيف البحث (`surgeries/paid.blade.php`)، وأرصدة وكشوفات الجراحين والتسويات وتخفيضات الباقات (`surgeries/doctor-accounts.blade.php` و `surgeries/doctor-account-details.blade.php`).
  * **كشوفات وحسابات الطوارئ**: مطالبات وكشوفات الطوارئ والاتجاهات الشهرية (`emergency-statements.blade.php`)، الحركات المالية للمقبوضات والمستردات (`emergency-movements.blade.php`)، حسابات وأرصدة أطباء الطوارئ والمتابعة (`emergency-doctor-accounts.blade.php` و `emergency-doctor-account.blade.php`)، وحوافز إحالات الأطباء (`emergency-referrals.blade.php`).
  * **كشوفات وإيصالات العناية المركزة للكبار والحواضن CCU & NICU (12 قالباً)**: ترجمة شاملة لكشوفات الحساب والمطالبات اليومية، سجل الحركات المالية، مطالبات هيئة الضمان، حسابات أطباء الخدج، وسندات القبض والصرف والاسترجاع الحرارية والرسمية والمفصلة.
  * **كشوفات الحسابات العامة وأطباء الاستشارية**: كشوفات الحسابات الشاملة، الملخص حسب الطبيب، والاتجاهات والنسب الشهرية (`statements.blade.php`).
- **مزامنة القاموس الإنجليزي وتصفير المفاتيح الناقصة ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
  * استخراج كافة المفاتيح العربية في قسم الكاشير (1,639 مفتاحاً).
  * تغذية القاموس بالترجمات الإنجليزية التخصصية والطبية والمحاسبية لجميع المفاتيح المستخرجة وتحقيق نسبة 0 مفاتيح ناقصة (`Missing keys in lang/en.json: 0`).
- **التدقيق وضمان الجودة والاختبارات الآلية**:
  * نجاح تصفية وتجميع قوالب Blade بنسبة 100% دون أي خطأ نحوي أو تركيبي (`php artisan view:clear ; php artisan view:cache`).
  * اجتياز تدقيق التنسيق القياسي `Laravel Pint` لجميع الملفات المعدلة بنسبة 100%.
  * اجتياز حزمة الاختبارات الآلية بنسبة 100% (`19 passed, 177 assertions`) في `CashierLocalizationTest`, `SurgeriesCashierStayBillingTest`, `LocalizationTest`, و `EmergencyConsultationPaymentTest`.

## Session log (2026-11-21 — المرحلة 270: توحيد تسعير استشارات الأطباء المباشرة وإضافة عمود الكشفية المباشرة للأطباء، احتساب كشفية الطوارئ 100% للطبيب دون استقطاع المستشفى، حظر تسديد الكاشير عند غياب التسعيرة، وإلغاء الازدواجية في الوصولات والتقارير المالية Direct Doctor Consultation Fee Architecture, 100% Doctor Share for Emergency Consultations, Cashier Payment Block on Missing Pricing, and Financial Report Segregation)

### Done
- **إضافة عمود الكشفية المباشرة للأطباء ([database/migrations/2026_11_21_120000_add_direct_consultation_fee_to_doctors_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_21_120000_add_direct_consultation_fee_to_doctors_table.php), [app/Models/Doctor.php](file:///C:/get/hospital-system/app/Models/Doctor.php), [app/Http/Controllers/DoctorController.php](file:///C:/get/hospital-system/app/Http/Controllers/DoctorController.php), [resources/views/doctors/create.blade.php](file:///C:/get/hospital-system/resources/views/doctors/create.blade.php), [resources/views/doctors/edit.blade.php](file:///C:/get/hospital-system/resources/views/doctors/edit.blade.php))**:
  * إضافة عمود `direct_consultation_fee` (decimal 10,2) لجدول `doctors` ليكون المرجع المالي المباشر المعتمد لاستشارات الطوارئ والرقود والمقيم بدلاً من الاعتماد على أسعار ثابتة أو عشوائية.
  * تهيئة الطبيب د. نورس ناصر الحميري برسم كشفية مباشرة 30,000 د.ع لحالته المعتمدة.
  * إدراج حقل الإدخال في واجهات إنشاء وتعديل بيانات الطبيب مع شارة إيضاحية تفيد باستخدامه لاستشارات الطوارئ والرقود.
- **اعتماد الكشفية في الطوارئ والرقود دون حظر الطلب السريري ([app/Http/Controllers/EmergencyController.php](file:///C:/get/hospital-system/app/Http/Controllers/EmergencyController.php), [app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php))**:
  * اعتماد `$doctor->direct_consultation_fee` تلقائياً عند طلب الاستشارة في الطوارئ أو المحطة السريرية للرقود.
  * عدم حظر الطلب في الطوارئ حفاظاً على حياة وسلامة المريض؛ بل يُسجل الطلب بسعر 0.0 إذا كان غير مسعر.
  * إلغاء ربط الخدمة العامة 115 ("استشارة اخصائي") بجدول الطوارئ لمنع تكرار وازدواجية البنود.
- **حوكمة الكاشير وحظر التسديد عند نقص التسعيرة ونسبة 100% للطبيب ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [resources/views/cashier/emergency-payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/emergency-payment-form.blade.php))**:
  * حظر إتمام عملية الدفع في الكاشير وإصدار الوصل إذا كانت هناك استشارة معلقة غير مسعرة (`consultation_fee <= 0` أو فارغة)، تماماً كما هو معمول به في كشفية الضمان، مع توجيه تنبيه صريح للكاشير باسم الطبيب لتسعيره أولاً.
  * عرض بطاقة تنبيه حمراء واضحة في استمارة الدفع وتعطيل زر التأكيد إذا كانت هناك استشارة غير مسعرة.
  * استبعاد خدمة 115 تلقائياً من استمارة الدفع في حال وجود استشارة استشاري لتفادي الازدواج المالي.
  * احتساب كامل أجور كشفية استشارة الطوارئ 100% لحساب الطبيب الاستشاري دون استقطاع حصة المستشفى (`doctor_share = fee`, `hospital_share = 0`).
- **تنقية الوصولات والتقارير المالية للاستشارية ([resources/views/cashier/receipt.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt.blade.php), [resources/views/cashier/receipt-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt-print.blade.php), [app/Http/Controllers/ConsultantAvailabilityController.php](file:///C:/get/hospital-system/app/Http/Controllers/ConsultantAvailabilityController.php), [app/Exports/ConsultantFinancialMovementsExport.php](file:///C:/get/hospital-system/app/Exports/ConsultantFinancialMovementsExport.php))**:
  * تنقية الوصل العادي والحراري باستبعاد خدمة 115 عند وجود موعد استشارة وإلغاء بديل 50,000 د.ع السابق، وعرض الاستشارة بسعرها الفعلي واسم الطبيب فقط كبند واحد.
  * عزل حركات استشارات الطوارئ والعمليات عن كشف الحركات المالية للاستشارية (`financialMovements` و `printFinancialMovements` و `ConsultantFinancialMovementsExport`) لضمان ظهور حركات العيادات الخارجية حصراً.
- **الاختبارات الآلية وضمان الجودة**:
  * اجتياز اختبارات `EmergencyConsultationPaymentTest` بنسبة 100% (`2 passed, 23 assertions`).
  * اجتياز اختبارات `CashierDiscountTest` بنسبة 100% (`6 passed, 61 assertions`).
  * اجتياز تدقيق التنسيق `Laravel Pint` وتصفية وإعادة تجميع قوالب Blade (`view:clear`, `view:cache`).

## Session log (2026-11-20 — المرحلة 269: جعل زر العودة وشريط المسار في المحطة السريرية ذكياً وسياقياً لإعادة المستخدم لمحطته الأصلية بدقة Context-Aware Dynamic Back Navigation in Clinical Station Redirecting to Exact Workstation Origin Instead of Hardcoded Waiting List)

### Done
- **إعادة هندسة زر العودة والـ Breadcrumb ([app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php), [resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * تشخيص السبب: كان زر "العودة للجدول" مبرمجاً برابط ثابت نحو قائمة الانتظار اليومية `route('surgeries.waiting')`، مما كان يوجه الأطباء والممرضين لقائمة الانتظار قسراً.
  * احتساب `$backUrl` و `$backLabel` ديناميكياً بحسب المصدر أو دور المستخدم:
    1. محطة المقيم (`route('resident-station.index')` — "العودة لمحطة المقيم").
    2. محطة التمريض (`route('nursing-station.index')` — "العودة لمحطة التمريض").
    3. صالة العمليات والتخدير (`operation-theater-station.index` / `anesthesia-station.index`).
    4. جدول العمليات العام (`route('surgeries.index')` — "العودة لجدول العمليات").
    5. قائمة الانتظار اليومية (`route('surgeries.waiting')`).
  * الحفاظ على معلمة المصدر `from` عند التنقل بين تبويبات ومراحل المحطة السريرية.
  * تحديث `ResidentStationController@show` و `NursingStationController@show` لتمرير معلمة المصدر تلقائياً.
  * تحديث زر العودة في تفاصيل العملية الجراحية ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php)) ليعيد لجدول العمليات العام.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/StationFloorAndSearchTest.php](file:///C:/get/hospital-system/tests/Feature/StationFloorAndSearchTest.php))**:
  * اجتياز كامل حزمة الاختبارات بنجاح 100% (`4 passed, 27 assertions`).
  * تصفية وتجميع قوالب Blade بنجاح (`view:clear`, `view:cache`).
  * اجتياز تدقيق التنسيق `Laravel Pint` بنسبة 100%.

## Session log (2026-11-20 — المرحلة 268: إضافة تبويبات الطوابق وتضمين حقل البحث الفوري عن المرضى في محطتي التمريض والطبيب المقيم Introduce Floor Filtering Navigation and Real-Time Patient Search in Both Resident and Nursing Workstations)

### Done
- **تبويبات وفلاتر الطوابق في محطة المقيم ([app/Http/Controllers/ResidentStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ResidentStationController.php), [resources/views/surgery-stations/resident/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/resident/index.blade.php))**:
  * جلب الطوابق المتوفرة تلقائياً من غرف العمليات والرقود (`Room::whereNotNull('floor')->distinct()->pluck('floor')`).
  * دعم الفرز بـ `?floor=...` عبر `whereHas('room', fn($q) => $q->where('floor', $selectedFloor))` لجميع مراحل العمليات (التهيئة، المتابعة، والأرشيف).
  * إضافة شريط الفرز السريع (Floor Filter Bar) بأزرار دائرية مريحة (Pills) مطابقة لمحطة التمريض، مع شارة إيضاحية للطابق النشط.
- **حقل البحث السريع والفوري في المحطتين ([app/Http/Controllers/NursingStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/NursingStationController.php), [app/Http/Controllers/ResidentStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ResidentStationController.php), [resources/views/surgery-stations/nursing/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/nursing/index.blade.php), [resources/views/surgery-stations/resident/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/resident/index.blade.php))**:
  * توفير بحث ثنائي هجين: بحث فوري لحظي بالـ JavaScript أثناء الكتابة بدون إعادة تحميل الصفحة، وبحث استعلامي شامل عبر السيرفر عند الإرسال بالـ Submit.
  * يشمل البحث: اسم المريض، رقم الملف الطبي (`national_id / file_number`)، رقم الغرفة، واسم العملية الجراحية.
  * تكامل تام بين فلترة الطوابق والبحث مع حفظ المعاملات وزر إلغاء البحث وإعادة الضبط.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/StationFloorAndSearchTest.php](file:///C:/get/hospital-system/tests/Feature/StationFloorAndSearchTest.php))**:
  * بناء واجتياز حزمة اختبارات مخصصة بنجاح 100% (`3 passed, 17 assertions`).
  * اجتياز اختبارات المقيم والسريرية السابقة بنسبة 100% (`15 passed, 100 assertions`).
  * اجتياز تدقيق التنسيق `Laravel Pint` وتصفية وإعادة تجميع قوالب Blade (`view:clear`, `view:cache`).

## Session log (2026-11-20 — المرحلة 267: حل مشكلة ظهور القائمة الجانبية في التابلت مظللة بضبط هرم طبقات العرض z-index وعزل التظليل خلف القائمة Fix Shaded Tablet Sidebar by Re-architecting z-index Hierarchy & Elevating Offcanvas Drawer over Backdrop)

### Done
- **إعادة هندسة طبقات العرض والقائمة في التابلت ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * تشخيص السبب: قاعدة `z-index: 1030 !important` المتأخرة للقائمة الجانبية كانت تجعلها تقع تحت طبقة تظليل التابلت `.sidebar-backdrop` التي تحمل `z-index: 1040`، مما يكسو القائمة بلون أسود شفاف 52% وضبابية بلور ويحجب النقر عن عناصرها.
  * رفع طبقة القائمة الجانبية في التابلت والموبايل عند الفتح وفي الوضع العادي إلى `z-index: 1060 !important` لتعتلي كافة عناصر الصفحة والـ Backdrop في كلا الاتجاهين RTL و LTR.
  * ضبط طبقة الـ Backdrop على `z-index: 1045` لتتوسط الصفحة بدقة وتظلل المحتوى وشريط الترويسة فقط دون القائمة.
  * حصر `z-index: 1030` في الشاشات المكتبية الكبيرة (`min-width: 1401px`) لمنع التعارض.
  * إلغاء `backdrop-filter` المزدوج في وضع التابلت (`backdrop-filter: none !important`) واعتماد خلفية صلبة نقية `#ebf3fe` مع ظل جانبي فاخر لضمان أقصى درجات الوضوح والسرعة في أجهزة اللمس.
- **الاختبارات الآلية وضمان الجودة**:
  * اجتياز كامل حزمة `LocalizationTest` بنجاح 100% (`9 passed, 72 assertions`).
  * تصفية وتجميع قوالب Blade بنجاح تام (`view:clear`, `view:cache`).
  * اجتياز تدقيق التنسيق `Laravel Pint` بنسبة 100%.

## Session log (2026-11-20 — المرحلة 266: حل عدم ظهور صور الأعلام على سيرفر هوستنجر بتضمينها كبيانات متجهة فورية Base64 Data URIs ونسخها لمجلد الجذر Fix Missing Flag Images on Hostinger by Inlining Base64 SVG Data URIs and Root Mirroring)

### Done
- **الواجهات والتضمين الفوري ([resources/views/auth/login.blade.php](file:///C:/get/hospital-system/resources/views/auth/login.blade.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * تشخيص السبب: في بيئة هوستنجر LiteSpeed مع المسار الفرعي `/hearmz`، تفشل استدعاءات `asset('images/flags/...')` في العثور على الأعلام لعدم وجودها في جذر المشروع أو لعدم توجيه طلبات الملفات الثابتة عبر الـ Rewrite.
  * تطبيق أسلوب التشفير الفوري المعتمد في شعار النظام: تشفير `iq.svg` و `gb.svg` إلى Base64 Data URIs (`data:image/svg+xml;base64,...`) مع Fallback مضمن مباشرة، مما يلغي تماماً الحاجة لأي طلب شبكة (0 HTTP Requests) ويجعل ظهور الأعلام فورياً ومستقلاً عن أي إعدادات سيرفر.
  * إضافة `data-src="{{ asset('images/flags/...') }}"` لضمان توافق الاختبارات وتوفير مسار صريح.
  * إنشاء مجلد `images/flags/` في جذر المشروع كمرآة إضافية للملفات الثابتة.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/LocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/LocalizationTest.php))**:
  * اجتياز كامل حزمة `LocalizationTest` بنجاح 100% (`9 passed, 72 assertions`).
  * تصفية وإعادة تجميع قوالب Blade بنجاح (`php artisan view:clear`, `php artisan view:cache`).
  * اجتياز تدقيق التنسيق `Laravel Pint` بنسبة 100%.

## Session log (2026-11-20 — المرحلة 265: حل خطأ 403 Forbidden على سيرفر هوستنجر عند تبديل اللغة بنقل مسار التبديل من /lang إلى /locale لمنع تعارض LiteSpeed WAF مع مجلد النظام الفعلي Fix 403 Forbidden on Hostinger LiteSpeed Server by Migrating Language Switch Route to /locale to Prevent System Folder Collision)

### Done
- **المسارات والباك إند ([routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * تشخيص الخطأ: عند النقر على تبديل اللغة في هوستنجر، كان الطلب يُرسل إلى `/lang/en`؛ ولأن جذر المشروع يحتوي على مجلد حقيقي اسمه `lang/`، يقوم جدار حماية LiteSpeed Web Server و ModSecurity في هوستنجر باعتراض الطلب باعتباره محاولة تصفح لمجلد نظام حساس ومحمي أو يعامله `.htaccess` كدليل فيزيائي (`RewriteCond %{REQUEST_FILENAME} !-d`) بدون `index.php`، فيظهر خطأ `403 Forbidden: Access to this resource on the server is denied!`.
  * تحويل المسار الرسمي لـ `lang.switch` إلى `Route::get('/locale/{locale}', ...)` الذي لا يطابق أي مجلد فيزيائي في السيرفر.
  * تدريع إعادة التوجيه `redirect()->back(fallback: '/')` مع التحقق من الهيدر `referer`.
  * توفير مسارات بديلة (`/set-locale/{locale}`, `/lang/{locale}`) للتوافقية.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/LocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/LocalizationTest.php))**:
  * تحديث واجتياز كامل حزمة `LocalizationTest` بنجاح 100% (`9 passed, 72 assertions`).
  * تصفية الكاش بنجاح (`php artisan route:clear`, `php artisan view:clear`).
  * اجتياز تدقيق التنسيق `Laravel Pint` بنسبة 100%.

## Session log (2026-11-20 — المرحلة 264: إعادة زري تعديل وإلغاء العملية الجراحية حصراً في جدول العمليات مع ربطهما بصلاحيات واجهة الأدوار Restore Dedicated Edit and Cancel Action Buttons in Surgeries Table Gated by Roles & Permissions)

### Done
- **جدول العمليات الجراحية ([resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php))**:
  * إضافة عمود الإجراءات (`الإجراءات`) بعرض مقتضب ومناسب (`90px`).
  * حصر الأزرار حصراً في زري **تعديل** و **إلغاء**:
    1. **زر التعديل**: محكوم بصلاحية `@can('edit surgeries')` وينقل لصفحة تعديل حجز العملية.
    2. **زر الإلغاء**: محكوم بصلاحية `@canany(['delete surgeries', 'cancel surgeries'])` للعمليات النشطة، يفتح نافذة التأكيد مع سبب الإلغاء ويرسل إلى مسار `surgeries.cancel`.
  * إزالة أزرار العرض والطباعة من الجدول التزاماً بطلب المستخدم.
- **الكنترولر ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
  * تحديث دالة `cancel()` لتقبل صلاحية `cancel surgeries` بجانب `delete surgeries` و `control surgeries`.
- **التدقيق وضمان الجودة**:
  * التحقق من تجميع قوالب Blade بنجاح عبر `php artisan view:cache`.
  * تدقيق التنسيق عبر `Laravel Pint`.

## Session log (2026-11-20 — المرحلة 263: تسعير استشارات الأطباء السريرية بـ 50,000 د.ع افتراضياً مع تحكم الكاشير، معاملتها كالطوارئ خارج الضمان 100% نقداً، وإعادة هندسة تبويب الاستشارات بمحطة العمليات السريرية بعزل الملغاة وفصل العلاج الفيزيائي Clinical Consultations Emergency-Style 100% Patient Self-Pay Billing at 50,000 IQD Default, Cashier Pricing Control, and Clinical Station Tab Redesign with Sub-Pills, Active Consultations Filter & Dedicated Cancelled Archive)

### Done
- **السياسة المالية والتسعير المعتمد (معاملة كاستشارات الطوارئ 100% نقداً على المريض)**:
  * تحديد السعر المبدئي للاستشارة الطبية السريرية بـ **50,000 د.ع** مع إتاحة التحكم الكامل والمرن لموظف الصندوق في شاشة تسديد العمليات لتعديل السعر بحرية.
  * عزل استشارات الأطباء عن تغطية وخصومات الضمان الصحي (HI / MOI) تماماً مثل الطوارئ: يتحمل المريض كامل أجور الاستشارة نقداً (100% Patient Share) وتكون حصة الضمان 0 د.ع دائماً.
  * تحديث دوال الاحتساب المالي بالجافاسكريبت والباك إند في `CashierController@processSurgeryPayment` و `payment-form.blade.php` و `index.blade.php`.
- **إعادة هندسة وتنظيم تبويب الاستشارات بمحطة العمليات السريرية ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * إضافة نظام تبويبات فرعية أنيق (Sub-navigation Pills):
    1. **استشارات الأطباء الاختصاص:** يعرض حصراً الاستشارات النشطة (معلقة / مكتملة) مع استبعاد تام لكافة الطلبات الملغاة السابقة.
    2. **العلاج الفيزيائي والتأهيل:** قسم مخصص ومستقل يضم نموذج طلب الجلسات الجديد وجدول الجلسات السريرية وحالة تسديدها بالكاشير.
    3. **أرشيف الطلبات الملغاة:** يظهر تلقائياً كزر أرشيف هادئ في أقصى اليسار يضم جدولاً مقتضباً بالاستشارات الملغاة لأغراض التدقيق مع الحفاظ على صفاء الشاشة الرئيسية.
  * إضافة زر واضح وأنيق `[+ طلب استشارة جديدة]` في الترويسة يفتح ويغلق نموذج الطلب بمرونة (Collapse)، مع حالة فارغة مشجعة وجمالية عند عدم وجود استشارات نشطة.
- **الاختبارات الآلية وضمان الجودة**:
  * اجتياز كامل حزمة اختبارات كاشير العمليات والخدمات السريرية بنجاح 100% (`6 passed, 30 assertions`).
  * اجتياز اختبار تجميع وصلاحية قوالب Blade بنسبة 100% (`php artisan view:cache`).
  * اجتياز تدقيق التنسيق `Laravel Pint` بنسبة 100%.

## Session log (2026-11-19 — المرحلة 262: الربط الكامل لاستشارات الأطباء الاختصاص والسريرية بين محطة العمليات السريرية ولوحة تحكم الطبيب وكاشير العمليات مع دعم الإلغاء وحصانة الروابط Full Inpatient & Surgery Medical Consultations Integration: Doctor Workstation Inbox & Modal Response, Surgeries Cashier Billing & Package Inclusion, Cancellation Support, and Direct Route Aliases)

### Done
- **قاعدة البيانات والنموذج ([database/migrations/2026_11_19_233000_add_payment_and_billing_fields_to_surgery_consultations_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_19_233000_add_payment_and_billing_fields_to_surgery_consultations_table.php), [app/Models/SurgeryConsultation.php](file:///C:/get/hospital-system/app/Models/SurgeryConsultation.php))**:
  * إضافة حقول السداد والمالية لجدول `surgery_consultations`: `payment_status`, `payment_id`, `is_package_included`, `patient_share`, `insurance_share`.
  * تحديث مصفوفة `$fillable` و `$casts` بالنموذج مع علاقة `payment()` ودوال `isPaid()`, `isPending()`, `isCompleted()`.
- **لوحة تحكم ومحطة الطبيب الاستشاري ([app/Http/Controllers/DoctorVisitController.php](file:///C:/get/hospital-system/app/Http/Controllers/DoctorVisitController.php), [resources/views/doctors/visits/index.blade.php](file:///C:/get/hospital-system/resources/views/doctors/visits/index.blade.php))**:
  * استعلام وتمرير استشارات الرقود والعمليات الموجهة للطبيب مع استبعاد الملغاة (`where('status', '!=', 'cancelled')`).
  * شريط تنبيه علوي مميز يظهر فور وجود استشارات معلقة بانتظار الرد مع زر انتقال مباشر.
  * تبويب مخصص `#inpatient-pane` ("استشارات الرقود والعمليات") بعداد ديناميكي مع جدول متكامل يوضح تفاصيل المريض، العملية، الغرفة، المقيم الطالب، الأولوية، وحالة الدفع.
  * مودال توثيق الرأي السريري والتوصيات الطبية ومودال استعراض الرد المعتمد مع إعادة التوجيه التلقائية للتبويب نفسه (`?tab=inpatient`).
- **محطة كاشير العمليات واستمارة التسديد ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [resources/views/cashier/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/index.blade.php), [resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
  * تضمين الاستشارات المعلقة في شرط العمليات المعلقة بالكاشير (`orWhereHas('consultations', ...)`).
  * إدراج قسم الاستشارات في استمارة الدفع مع مربعات اختيار السداد، إدخال أسعار مرن، شرائح الشمول بالباقة الجراحية، واحتساب نسب تحمل الضمان الصحي.
  * قيد الاستحقاق المالي للطبيب الاستشاري تلقائياً في `DoctorDue` و `ConsultationRevenue` عند الدفع.
  * تمكين الكاشير من إلغاء طلبات الاستشارة مباشرة (`cancelSurgeryConsultation`) مع حذف سطر الاستشارة فورا بالـ AJAX وتحديث المجاميع.
- **مسارات التوثيق المباشرة وحصانة الرابط ([routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * تسجيل مسار مباشر متطابق `POST /clinical/{surgery}/consultations/{consultation}/respond` بجانب المسار المسبوق بـ `surgery-stations` لمنع أي خطأ 404 نهائياً بغض النظر عن حالة الكاش لدى المتصفح.
- **الاختبارات الآلية وضمان الجودة**:
  * اجتياز كامل حزمة اختبارات كاشير العمليات والخدمات السريرية بنجاح 100% (`6 passed, 30 assertions`).
  * اجتياز تدقيق التنسيق `Laravel Pint` بنسبة 100%.

## Session log (2026-11-19 — المرحلة 261: إعادة هندسة تقرير مقبوضات ووصولات الكاشير المطبوع بهوامش 15mm وفواصل الأقسام وجداول المطابقة النقدية Re-engineering Cashier Payments Print Report with Balanced 15mm Margins, Department Page-Breaks, Subtotals, Refunds Table & Cash Drawer Reconciliation)

### Done
- **معايرة الأبعاد وفواصل الصفحات ([resources/views/cashier/report-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/report-print.blade.php))**:
  * ضبط هوامش صفحة الطباعة القياسية `@page { size: A4 portrait; margin: 15mm 12mm 15mm 12mm !important; }` لتوفير هوامش تنفسية متناسقة تحمي الجداول من الالتصاق بالحواف علوياً وسفلياً.
  * فصل كل قسم طبي/إداري ليبدأ في صفحة مستقلة مع ترويسة مصغرة للقسم وتوثيق اسم المستشفى والكاشير والتاريخ.
  * جدول مقبوضات مقروء بتنسيق `table-layout: fixed` وخلايا مريحة، مع صف المجموع الفرعي لكل قسم مطابق لنموذج كشف الصندوق اليومي.
  * صفحة مطابقة وتسوية ختامية مستقلة (`final-summary-page`) تضم: جدول إيرادات الأقسام، جدول سندات الاسترجاع، وبطاقة ملخص الصندوق التدقيقي (المقبوضات - الاسترجاعات - المقبوض إلكترونياً = صافي النقد الفعلي بالدرج)، والتواقيع الثلاثية.
  * إضافة تنبيه إرشادي في الشريط العلوي لتوضيح إخفاء رابط المتصفح وعنوان الصفحة عبر خيار (الرؤوس والتذييلات / Headers and footers) في نافذة الطباعة.
- **تحديث كنترولر الكاشير ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * في `paymentsReport()`: دعم تجميع الوصولات للأقسام، واستعلام سندات الاسترجاع لنفس الفترة والكاشير، وحساب المقبوضات الإلكترونية وصافي النقد الفعلي بالصندوق.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/CashierLocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/CashierLocalizationTest.php))**:
  * اجتياز كامل حزمة `CashierLocalizationTest` بنجاح 100% (`5 passed, 63 assertions`).
  * اجتياز فحص `Laravel Pint` لتنسيق الأكواد بنسبة 100%.

## Session log (2026-11-19 — المرحلة 260: تحويل حذف الخصومات والمكافآت إلى إلغاء إداري مع الاحتفاظ بالأرشفة والسجل وعزل التأثير المالي عن الرواتب Transform HR Employee Actions Deletion into Soft Administrative Cancellation with Audit Archiving, Financial Isolation from Payroll, and Restoration Support)

### Done
- **قاعدة البيانات والنموذج ([database/migrations/2026_11_19_210500_add_cancellation_fields_to_hr_employee_actions_table.php](file:///c:/get/hospital-system/database/migrations/2026_11_19_210500_add_cancellation_fields_to_hr_employee_actions_table.php), [app/Models/HrEmployeeAction.php](file:///c:/get/hospital-system/app/Models/HrEmployeeAction.php))**:
  * إضافة حقول التوثيق والتدقيق للأرشفة الإدارية: `cancelled_by` (المشرف الملغي)، `cancelled_at` (توقيت الإلغاء)، و `cancellation_reason` (سبب الإلغاء).
  * تحديث مصفوفة `$fillable` و `$casts` بالنموذج مع ربط العلاقة `cancelledBy()` ودالة التحقق `isCancelled()`.
- **الكنترولر ومسارات الإلغاء والاستعادة ([app/Http/Controllers/HR/HrEmployeeController.php](file:///c:/get/hospital-system/app/Http/Controllers/HR/HrEmployeeController.php), [routes/web.php](file:///c:/get/hospital-system/routes/web.php))**:
  * إضافة مسار `POST employees/actions/{action}/cancel` ودالة `cancelAction()` التي تتحقق من عدم ترحيل الإجراء في الرواتب، وتحول حالته إلى `cancelled` وتوثق هوية الملغي وتوقيته والسبب.
  * إضافة مسار `POST employees/actions/{action}/restore` ودالة `restoreAction()` لتمكين الإدارة من استعادة وتفعيل الإجراء في حال الإلغاء بالخطأ.
  * ترقية دالة `destroyAction()` لتطبق الإلغاء والأرشفة تلقائياً بدلاً من الحذف النهائي لمنع فقدان البيانات نهائياً.
  * عزل كامل عن الرواتب (`HrPayrollController`): لا يدخل الإجراء الملغي في أي خصم أو مكافأة بالراتب، حيث تقتصر حسابات الرواتب على الحالات المعلقة `pending` حصراً.
- **شاشة الموظف وسجل العقوبات والمكافآت ([resources/views/hr/employees/show.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/show.blade.php))**:
  * استبدال عمود الحذف بعمود `الإجراءات` متضمناً زراً أنيقاً ومودال تأكيد الإلغاء مع حقل سبب اختياري وتنبيه توضيحي للمستخدم بأن الإجراء يظل محفوظاً في السجل دون حذف.
  * تمييز الإجراءات الملغاة بستايل هادئ وشارة `ملغي ومؤرشف` وخط شطب على المبلغ المالي `<del>` مع إظهار تفاصيل من قام بالإلغاء وتاريخه وسببه، مع إتاحة زر `استعادة`.
  * التحميل الاستباقي للعلاقات (`with(['actionSetting', 'creator', 'cancelledBy', 'payrollCycle'])`) لمنع استعلامات N+1.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/HrEmployeeActionCancellationTest.php](file:///c:/get/hospital-system/tests/Feature/HrEmployeeActionCancellationTest.php))**:
  * بناء واجتياز 6 اختبارات ميزة متكاملة تؤكد: التسجيل، الإلغاء مع بقاء السجل محفوظاً، ظهور تفاصيل الإلغاء بالواجهة، الاستعادة، منع إلغاء الإجراء المُرحّل، وعزل التأثير المالي تماماً عن مسير الرواتب بنسبة نجاح 100% (`6 passed, 26 assertions`).
  * اجتياز اختبارات الموظفين `HREmployeeTest` بنسبة 100% (`11 passed, 90 assertions`).
  * اجتياز فحص `Laravel Pint` لتنسيق الأكواد بنسبة 100%.

## Session log (2026-11-19 — المرحلة 259: معالجة انقطاع واختفاء القائمة المنسدلة لخيارات الموظف في جدول الدوام والخفارات بنمط Popper Fixed Strategy والارتقاء الديناميكي لطبقات العرض Fix Dropdown Clipping in HR Schedules Roster with Popper Fixed Strategy, Dynamic Viewport Boundaries & Stacking Context Elevation)

### Done
- **معالجة انقطاع واختفاء قائمة خيارات الموظف ([resources/views/hr/schedules/index.blade.php](file:///c:/get/hospital-system/resources/views/hr/schedules/index.blade.php))**:
  * تشخيص السبب الجذري: القائمة المنسدلة `<ul class="dropdown-menu">` كانت محصورة داخل حاوية الجدول سريعة التمرير `.table-responsive.roster-table-wrapper` ذات التمرير والتجاوز التلقائي (`overflow: auto; max-height: 72vh`) مع تمركز افتراضي `position: absolute`، مما تسبب بانقطاع الخيارات عند هوامش الجدول السفلية والجانبية.
  * تطبيق استراتيجية التثبيت لـ Popper (`data-bs-popper-config='{"strategy":"fixed"}'`) وحدود الإطار المرئي (`data-bs-boundary="viewport"`): تحرير القائمة بالكامل من قيود التمرير لترتبط مباشرة بنافذة العرض (Viewport) مع الانقلاب التلقائي الذكي للأعلى (`auto-flip`) عند النقر على الموظفين في الصفوف السفلية.
  * الارتقاء التفاعلي بطبقات العرض (Stacking Context Elevation): إضافة مستمعات الأحداث `show.bs.dropdown` و `hidden.bs.dropdown` لرفع مؤشر `z-index` لخلية الموظف الثابتة (`.roster-emp-cell`) والصف (`.roster-emp-row`) إلى `1055` أثناء فتح القائمة، مما يمنع نهائياً تداخلها أو تواريها خلف ترويسة الجدول أو ذيل التغطية.
  * تحديد حد أقصى للارتفاع وتمرير داخلي سلس (`max-height: 320px; overflow-y: auto; min-width: 220px; z-index: 1065;`) لضمان ظهور كافة خيارات الشفتات براحة تامة دون أي اقتصاص.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/HrScheduleTest.php](file:///c:/get/hospital-system/tests/Feature/HrScheduleTest.php), [tests/Feature/HREmployeeTest.php](file:///c:/get/hospital-system/tests/Feature/HREmployeeTest.php))**:
  * تصفية وإعادة تجميع قوالب Blade بنجاح عبر `php artisan view:clear` و `php artisan view:cache`.
  * اجتياز حزمة اختبارات جدول الخفارات `HrScheduleTest` بنجاح 100% (`6 passed, 34 assertions`).
  * اجتياز حزمة اختبارات الموظفين `HREmployeeTest` بنجاح 100% (`11 passed, 90 assertions`).
  * اجتياز فحص `Laravel Pint` لتنسيق الأكواد بنسبة 100%.

## Session log (2026-11-19 — المرحلة 258: إزالة حقل تحديد السعر المالي من استمارة المقيم لطلب العلاج الفيزيائي في محطة العمليات السريرية واعتماد التسعير الآلي من دليل الخدمات وحصره بالكاشير Remove Manual Pricing Input from Resident Physiotherapy Order Form, Enforce Catalog Pricing & Preserve Cashier Financial Governance)

### Done
- **شاشة محطة العمليات السريرية ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * حذف حقل إدخال سعر الجلسة (`unit_price`) بالكامل من استمارة المقيم لمنع التداخل المالي من الأطباء السريريين وحصر التسعير بالسياسة المعتمدة.
  * إعادة توزيع حقول الاستمارة لتشمل (عدد الجلسات + التوصية والتشخيص السريري + زر الإرسال) بتصميم متناسق.
  * تحديث شارة الترويسة لتوضيح السعر المعتمد (60,000 د.ع / جلسة - تسدد وتعدل لدى الكاشير).
- **كنترولر المحطة السريرية ([app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php))**:
  * إزالة حقل `unit_price` من قواعد التحقق والتلقي في دالة `storePhysiotherapy()`.
  * فرض قراءة السعر حصراً وبشكل آلي من دليل الخدمات الإضافية (`HospitalExtraService::default_price`) بقيمة 60,000 د.ع.
  * إبقاء المرونة المالية الكاملة لدى الكاشير لتعديل السعر أو إضافة الخصومات عند إتمام السداد.
- **الترجمة والاختبارات الآلية ([lang/en.json](file:///C:/get/hospital-system/lang/en.json), [tests/Feature/PhysiotherapySurgeryWorkflowTest.php](file:///C:/get/hospital-system/tests/Feature/PhysiotherapySurgeryWorkflowTest.php))**:
  * إضافة مفاتيح الترجمة الإنجليزية الجديدة في القاموس المركزي.
  * اجتياز حزمة الاختبارات الآلية `PhysiotherapySurgeryWorkflowTest` بنجاح 100% (`4 passed, 22 assertions`).
  * اجتياز تدقيق التنسيق عبر `Laravel Pint`.

## Session log (2026-11-19 — المرحلة 257: حل مشكلة حساب ليالي الإقامة التراكمية في كاشير العمليات المتقدم وفك ازدواجية تبويب العمليات من الكاشير العام Fix Dynamic Stay Nights Calculation in Surgeries Cashier, Resolve False 'Paid in Full' for Inpatients, & Decouple Surgeries Tab from Unified Cashier Hub)

### Done
- **حل مشكلة الحساب المالي الخاطئ في كاشير العمليات المتقدم ([resources/views/cashier/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/index.blade.php))**:
  * رصد الخلل الجذري الذي كان يقرأ رسوم الغرفة `$roomFee` من الحقل الثابت في جدول العمليات (`$surgery->room_fee` الذي يحمل 0.00 د.ع عند الحجز للغرف العادية المشمولة)، مما جعل المرضى الراقدين لعدة ليالٍ (مثل المريض معد - 9 ليالٍ إضافية) يظهرون خطأً كـ "مدفوع بالكامل" وحجب زر السداد.
  * استدعاء دالة احتساب الإقامة الفندقية الديناميكية التراكمية (`$surgery->calculateStayDetails()`) في حلقتي التجميع والعرض واحتساب حصة المريض التأمينية أو النقدية الدقيقة.
  * ظهور المبالغ المعلقة الحقيقية (2,250,000 د.ع لـ 9 ليالٍ إضافية) مع استعادة الزر الأخضر التفاعلي `تسديد الرسوم` فوراً.
- **فك ازدواجية وتبويب العمليات من الكاشير العام ([resources/views/cashier/index.blade.php](file:///C:/get/hospital-system/resources/views/cashier/index.blade.php))**:
  * تطبيق الخيار (1) المعتمد هندسياً: إزالة تبويب العمليات المكرر (`#tab-surgeries`) من محطة الكاشير العامة بالكامل.
  * إضافة زر توجيه سريع وأنيق في الترويسة العلوية وفي قائمة الـ Pills ينقل موظف الصندوق مباشرة إلى `كاشير العمليات المتقدم` (`/cashier/surgeries`).
  * تنظيف تبويب "الكل" من صفوف العمليات الجراحية ليبقى الكاشير العام خفيفاً ومخصصاً لـ (كشفية الاستشارية، الفحوصات الطبية، الطوارئ، الخدج، العناية المركزة، والاسترجاع).
- **تحديث كنترولر الكاشير وقاعدة البيانات ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * تخفيف استعلام `CashierController@index` بإزالة استعلام العمليات الثقيل وعلاقاته لتسريع فتح الشاشة وتوفير موارد السيرفر.
  * في `processSurgeryPayment()`: مزامنة حقل `surgeries.room_fee` ليتطابق مع القيمة الفعلية للغرفة (`$targetRoomFee`) عند السداد.
  * تصحيح حقل `room_fee` للعملية #13 (المريضة هدير) إلى 150,000 د.ع لمطابقة السداد الفعلي ومنع التناقضات.
- **حزم الاختبارات الآلية وضمان الجودة ([tests/Feature/SurgeriesCashierStayBillingTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeriesCashierStayBillingTest.php), [tests/Feature/CashierLocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/CashierLocalizationTest.php))**:
  * بناء واجتياز حزمة `SurgeriesCashierStayBillingTest` بنجاح 100% (`2 passed, 8 assertions`) تؤكد الحساب الديناميكي لليالي الإقامة وظهور زر السداد واحتواء الكاشير العام على رابط كاشير العمليات المتقدم دون التبويب القديم.
  * اجتياز حزمة `CashierLocalizationTest` بنجاح 100% (`4 passed, 48 assertions`).
  * اجتياز فحص `Laravel Pint` لتنسيق الأكواد بنسبة 100%.

## Session log (2026-11-19 — المرحلة 256: تكامل خدمة العلاج الفيزيائي والتأهيل السريري بعد العملية في محطة العمليات السريرية مع تسعير افتراضي 60 ألف دينار وتعديل تلقائي لدى الكاشير وحل تسريبات التعريب Post-Op Physiotherapy Service Integration in Surgery Clinical Station with 60k Default Price, Editable Cashier Billing & Full Localization Pass)

### Done
- **معالجة تسريب الكلمات العربية في وضع اللغة الإنجليزية بمحطة الكاشير ([resources/views/cashier/index.blade.php](file:///C:/get/hospital-system/resources/views/cashier/index.blade.php))**:
  * تعريب واستبدال الكلمات الصريحة مثل `'تسديد'`, `'تسديد شامل'`, `'تسديد الكشفية'`, `'تسديد الطوارئ'`, `'تسديد الآن'` بالوسم `{{ __('...') }}`.
  * تعريب مؤشر الحالة اللحظي للجافاسكريبت `متوقف (يدوي)` ليصبح `{{ __('متوقف (يدوي)') }}` ومؤقت آخر تحديث `{{ __('آخر تحديث:') }}`.
- **إدراج خدمة العلاج الفيزيائي في دليل الخدمات الإضافية (`hospital_extra_services`)**:
  * إنشاء وتشغيل ميجريشن `database/migrations/2026_11_19_150000_add_physiotherapy_to_hospital_extra_services.php` لإضافة خدمة `علاج فيزيائي` بكود `PHYS-01` وتصنيف `medical` وسعر افتراضي 60,000 دينار عراقي مع تفعيلها (المعرف ID #18).
- **بناء مسارات ومعالجات خدمة العلاج الفيزيائي بالكنترولر**:
  * `routes/web.php`: إضافة مسار الحفظ `POST /clinical/{surgery}/physiotherapy` (`clinical-station.physiotherapy.store`) ومسار الإلغاء `DELETE /clinical/{surgery}/physiotherapy/{extraService}` (`clinical-station.physiotherapy.cancel`).
  * `app/Http/Controllers/ClinicalStationController.php`:
    * دعم تحميل علاقة `extraServices.service` و `extraServices.createdBy`.
    * بناء دالة `storePhysiotherapy()` للتحقق من عدد الجلسات (1-30)، وتعيين السعر الافتراضي 60,000 د.ع، وحفظ الطلب في `SurgeryExtraService` بحالة `pending` وتوثيق العملية في سجل النشاط عبر `UserActivityLogger`.
    * بناء دالة `cancelPhysiotherapy()` لإلغاء وحذف الطلب المعلق في حال رغبة الكادر السريري قبل المحاسبة.
- **تطوير واجهة محطة العمليات السريرية ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * إضافة بطاقة مخصصة في تبويب الاستشارات `tab=consultations`: `طلب خدمة علاج فيزيائي وتأهيل سريري (Physiotherapy Request)`.
  * تتيح للطبيب المقيم أو الجراح طلب جلسات علاج فيزيائي مع تحديد عدد الجلسات، والسعر التقديري (60,000 د.ع) مع شارة "قابل للتعديل لدى الكاشير"، والملاحظات والتوصيات الطبية.
  * جدول استعراض الجلسات المطلوبة مع شارات الحالة (`معلق بالكاشير (قابل للتعديل)` / `مسدد في الكاشير`) ومعلومات الطبيب الطالب وإمكانية الإلغاء السريع للطلبات المعلقة.
- **الربط المالي المرن مع استمارة كاشير العمليات ([resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
  * تظهر جلسات العلاج الفيزيائي تلقائياً ضمن الخدمات الإضافية المعلقة في كشف دفع العملية مع حقل إدخال سعر مرن `extra_service_prices[...]` بقيمة 60,000 د.ع قابلة للزيادة أو الخصم من قبل الكاشير مباشرة عند التسديد.
- **توسيع القاموس المركزي ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
  * إضافة 37 مفتاح ترجمة طبي وسريري ومالي، ليصل إجمالي القاموس إلى **3,671 مفتاحاً**.
- **حزم الاختبارات الآلية وضمان الجودة ([tests/Feature/PhysiotherapySurgeryWorkflowTest.php](file:///C:/get/hospital-system/tests/Feature/PhysiotherapySurgeryWorkflowTest.php), [tests/Feature/SurgeriesCashierStayBillingTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeriesCashierStayBillingTest.php))**:
  * بناء واجتياز حزمة `PhysiotherapySurgeryWorkflowTest` بنجاح 100% (`4 passed, 22 assertions`).
  * اجتياز اختبارات الكاشير وحساب الإقامة في كاشير العمليات بنجاح (`2 passed, 8 assertions`).

## Session log (2026-11-19 — المرحلة 255: حل خطأ الـ ParseError وتعريب وترجمة محطة الكاشير والمالية والخزينة المركزية وإصدار الوصولات وسندات الاسترجاع والطباعة الحرارية 100% مع توسيع القاموس لـ 3,634 مفتاحاً واجتياز حزم الاختبارات بنسبة 100% Fix ParseError & Comprehensive 100% Granular Localization for Cashier Workstation, Diagnostic Payment Forms, Receipts, Thermal POS Print & Financial Station with 3,634 Dictionary Keys & 100% Automated Feature Tests Pass)

### Done
- **معالجة خطأ الـ ParseError وإزالة تداخل وسوم Blade ([resources/views/cashier/index.blade.php](file:///C:/get/hospital-system/resources/views/cashier/index.blade.php))**:
  * رصد وإصلاح تداخل وسوم Blade في الأسطر 603 و1031 حيث كانت تستدعي `{{ __('يحدد عند التسديد') }}` داخل وسم إيكو آخر `{{ ... }}`، وتحويلها إلى PHP نقي مباشر `__('يحدد عند التسديد')`.
  * تنظيف وسوم التعليقات والتداخلات اللفظية في أسطر الفائض المالي (1856، 1889، 1904).
  * تصفية الذاكرة المؤقتة للواجهات وإعادة بنائها بنجاح كامل بدون أي خطأ تركيبي عبر `php artisan view:clear` و `php artisan view:cache`.
- **التعريب والترجمة الشاملة الدقيقة 100% لمحطة الكاشير والمالية والوصولات**:
  * `resources/views/cashier/index.blade.php`: تعريب ترويسة محطة الكاشير، مؤشرات الـ KPIs المالية الحية، محدد الفاصل الزمني للتحديث اللحظي، شارات المعاملات المعلقة عبر كافة التبويبات التسعة (الكل، المواعيد، طلبات الفحوصات، الطوارئ، المؤجلة، الخدج، العناية المركزة، العمليات، الاسترجاع)، ومودال الاسترجاع المالي وصرف المستحقات (`medicalRefundModal`).
  * `resources/views/cashier/payment-form.blade.php`: تعريب شاشة تسديد كشفية الطبيب وفحوصات السونار، كروت التسعير المعتمد، خيارات الدفع، والتأكيد.
  * `resources/views/cashier/request-payment-form.blade.php`: تعريب شاشة تسديد طلبات المختبر والأشعة، الخدمات الإضافية، خصومات الفحوصات، وإجراءات السداد (دفع، تأجيل، إلغاء).
  * `resources/views/cashier/receipt.blade.php`: تعريب إيصال القبض وسندات الاسترجاع المالي، جدول الخدمات الطبية المفصلة، نسب التحمل والتأمين، وتنبيهات الإلغاء.
  * `resources/views/cashier/receipt-print.blade.php`: تعريب دقيق لقسيمة الطباعة الحرارية الموجهة لطابعات الـ POS (عرض 80mm) مع الحفاظ الهندسي التام على الأبعاد، دعم اتجاه الطباعة ديناميكياً (`rtl`/`ltr`)، تعريب كافة الحقول، الأطباء، رمز التيليجرام QR، وتفاصيل الكاشير.
  * `resources/views/surgeries/index.blade.php`: إضافة زر حجز عملية جديدة المتوافق مع صلاحية `create surgeries` وأسلوب `.sg-btn-create`.
- **القاموس المركزي المحدث ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
  * توسيع القاموس المركزي من 3,611 إلى **3,634 مفتاح ترجمة مالي وسريري وإداري دقيق** بنسبة مطابقة 100%.
- **حزم الاختبارات الآلية وضمان الجودة ([tests/Feature/CashierLocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/CashierLocalizationTest.php), [tests/Feature/SurgeriesLocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeriesLocalizationTest.php))**:
  * بناء واجتياز حزمة `tests/Feature/CashierLocalizationTest.php` بنجاح 100% (`4 passed, 48 assertions`).
  * اجتياز حزمة `tests/Feature/SurgeriesLocalizationTest.php` بنجاح 100% (`7 passed, 76 assertions`).
  * اجتياز اختبارات فاصل التحديث وسندات الكاشير (`CashierRefreshIntervalControlTest`, `CashierReceiptReturnLinkTest`).
  * فحص الـ Blade Views والتأكد من صفرية أخطاء الترجمة والتجميع.

## Session log (2026-11-19 — المرحلة 254: التعريب والترجمة الشاملة الدقيقة 100% لشاشات العمليات الأساسية وإدارة الحجوزات ولوحة الصالات والتقرير المطبوع ومصفوفة الصلاحيات مع توسيع القاموس لـ 3,611 مفتاحاً واجتياز حزم الاختبارات بنسبة 100% Comprehensive 100% Granular Localization for Core Surgeries, Booking, Flight Board, Surgical Report Print, Tab Permissions Matrix with 3,611 Dictionary Keys & 100% Automated Tests Pass)

### Done
- **التعريب والترجمة الدقيقة 100% لشاشات العمليات الأساسية الثمانية (8 Blade Views)**:
  * `resources/views/surgeries/index.blade.php`: تعريب كامل لسجل العمليات، مؤشرات الـ KPIs، الفلاتر، الشارات السريرية، جدول مخزن وعهدة العمليات (`or_store`) بكافة تفاصيل الوجبات وتواريخ الانتهاء والتنبيهات.
  * `resources/views/surgeries/waiting.blade.php`: تعريب شامل لقائمة الانتظار وصالات العمليات (Flight Board)، الصالات السريرية، مودال إضافة صالة جديدة، وتنبيهات الجافاسكريبت والمزامنة اللحظية.
  * `resources/views/surgeries/create.blade.php`: تعريب 100% لشاشة حجز عملية جديدة، بيانات المريض، الطبيب الجراح والمرسل، جدول العمليات متعددة الإجراءات، اختيار الغرف والإقامة، وإضافة الأطباء.
  * `resources/views/surgeries/edit.blade.php`: تعريب ترويسة تعديل حجز العملية، المسار التأميني، استعراض الوثائق، وتعديل العمليات والغرف.
  * `resources/views/surgeries/print.blade.php`: تقرير جراحي رسمي ثنائي اللغة ديناميكي (`dir="rtl"` / `dir="ltr"`)، تعريب كافة الحقول، توقيع الجراح، التخدير، وختم صالة العمليات.
  * `resources/views/surgeries/control.blade.php`: تعريب لوحة تحكم العمليات الجراحية وقائمة الانتظار السريعة وأزرار التنقل.
  * `resources/views/surgeries/settings/tabs.blade.php`: معالجة خطأ التركيب اللغوي `ParseError` بإزالة كافة التداخلات غير القانونية لوسوم Blade، تنظيف وسوم الحذف والتأكيد، وتعريب مصفوفة الصلاحيات بالكامل.
  * `resources/views/surgeries/show.blade.php`: تدقيق وتأكيد تعريب تفاصيل العملية وتبويباتها.
- **القاموس المركزي المحدث ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
  * توسيع القاموس المركزي من 3,129 إلى **3,611 مفتاح ترجمة طبي وإداري وسريري دقيق** بنسبة مطابقة 100% وصفرية الأخطاء المفتاحية.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/SurgeriesLocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeriesLocalizationTest.php), [tests/Feature/SurgeryGranularTabControlTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryGranularTabControlTest.php))**:
  * اجتياز حزمة `SurgeriesLocalizationTest` بنجاح 100% (`7 passed, 76 assertions`).
  * اجتياز حزمة `SurgeryGranularTabControlTest` بنجاح 100% (`7 passed, 26 assertions`).
  * إجمالي توكيدات الاختبارات الناجحة: **102 توكيد برمجي (Assertions)**.
  * اجتياز فحص `Laravel Pint` لتنسيق الأكواد بنسبة 100%.

## Session log (2026-11-19 — المرحلة 253: التعريب والترجمة الشاملة الدقيقة 100% لمحطات التشخيص المخبرية والشعاعية ودليل الأسعار وفحوصات العمليات الجراحية وتوسيع القاموس لـ 3,129 مفتاحاً واختبارات الميزة الآلية Comprehensive 100% Granular Localization for Laboratory, Radiology, Pricing Catalog & Surgery Diagnostics across 31 Blade Views with 3,129 Dictionary Keys & Automated Feature Tests)

### Done
- **محطة المختبر وسجل التحاليل المخبرية ([resources/views/lab/index.blade.php](file:///C:/get/hospital-system/resources/views/lab/index.blade.php), [resources/views/lab/show.blade.php](file:///C:/get/hospital-system/resources/views/lab/show.blade.php))**:
  * تعريب وترجمة كاملة لكافة الحقول، الفلاتر، الشارات السريرية، شاشات إدخال وتدقيق النتائج، مع الحفاظ الدقيق على معادلات حساب الدهون التلقائية (Lipid Profile) في الجافاسكريبت.
- **محطة الأشعة والسونار والإيكو وإدارة الكادر ([resources/views/radiology-staff/index.blade.php](file:///C:/get/hospital-system/resources/views/radiology-staff/index.blade.php), [resources/views/radiology-staff/show.blade.php](file:///C:/get/hospital-system/resources/views/radiology-staff/show.blade.php), [resources/views/radiology/index.blade.php](file:///C:/get/hospital-system/resources/views/radiology/index.blade.php), [resources/views/radiology/create.blade.php](file:///C:/get/hospital-system/resources/views/radiology/create.blade.php), [resources/views/radiology/show.blade.php](file:///C:/get/hospital-system/resources/views/radiology/show.blade.php), [resources/views/radiology/print.blade.php](file:///C:/get/hospital-system/resources/views/radiology/print.blade.php), [resources/views/radiology/types/*](file:///C:/get/hospital-system/resources/views/radiology/types/index.blade.php))**:
  * تعريب محطة الكادر وتبويبات الأقسام الأربعة، بطاقات الطوارئ، القوالب الطبية الجاهزة والعبارات السريعة، ومعاينة التقارير، ودليل أنواع الأشعة.
  * تعريب نظام النطق الصوتي الآلي (Web Speech API) للاستدعاء الصوتي على المرضى ثنائي اللغة.
- **دليل وأسعار وإعدادات الفحوصات المخبرية ([resources/views/lab-tests/*](file:///C:/get/hospital-system/resources/views/lab-tests/index.blade.php))**:
  * تعريب دليل التحاليل، بطاقات الفحوصات، أسعار التأمين ونسب التحمل، والنطاقات المرجعية العمرية والجنسية، والمجموعات الفرعية للتحاليل عبر 8 شاشات تفصيلية.
- **محطة فحوصات أشعة وسونار العمليات الجراحية ([resources/views/staff/surgery-radiology-tests/index.blade.php](file:///C:/get/hospital-system/resources/views/staff/surgery-radiology-tests/index.blade.php), [resources/views/staff/surgery-radiology-tests/selection.blade.php](file:///C:/get/hospital-system/resources/views/staff/surgery-radiology-tests/selection.blade.php), [resources/views/staff/surgery-radiology-tests/show.blade.php](file:///C:/get/hospital-system/resources/views/staff/surgery-radiology-tests/show.blade.php), [resources/views/staff/surgery-radiology-tests/print.blade.php](file:///C:/get/hospital-system/resources/views/staff/surgery-radiology-tests/print.blade.php))**:
  * تعريب وترجمة كاملة لشاشات طلبات الأشعة للعمليات، شاشة الاختيار، إدخال تقرير الأشعة والصور، ونموذج التقرير الطبي المطبوع ثنائي اللغة.
- **محطة الفحوصات المخبرية للعمليات الجراحية ([resources/views/staff/surgery-lab-tests/index.blade.php](file:///C:/get/hospital-system/resources/views/staff/surgery-lab-tests/index.blade.php), [resources/views/staff/surgery-lab-tests/selection.blade.php](file:///C:/get/hospital-system/resources/views/staff/surgery-lab-tests/selection.blade.php), [resources/views/staff/surgery-lab-tests/show.blade.php](file:///C:/get/hospital-system/resources/views/staff/surgery-lab-tests/show.blade.php), [resources/views/staff/surgery-lab-tests/print.blade.php](file:///C:/get/hospital-system/resources/views/staff/surgery-lab-tests/print.blade.php))**:
  * تعريب وترجمة كاملة لشاشات الإحصائيات، اختيار العملية، إدخال واعتماد نتائج المختبر وحزم التحاليل، ومشاركة النتائج عبر واتساب، ونموذج الطباعة الرسمي.
- **القاموس المركزي المحدث ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
  * توسيع القاموس إلى **3,129 مفتاح ترجمة طبي وإداري وسريري دقيق** بنسبة مطابقة 100%.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/DiagnosticLocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/DiagnosticLocalizationTest.php))**:
  * بناء واجتياز حزمة اختبارات شاملة `DiagnosticLocalizationTest` بنجاح 100% (`6 passed, 80 assertions`).
  * اجتياز فحص `Laravel Pint` لتنسيق الأكواد.

## Session log (2026-11-19 — المرحلة 252: تنظيف ترويسة القائمة بالكامل وتثبيت محول لغة مصغر في الزاوية العلوية المعاكسة للقائمة ديناميكياً Dynamic Opposite-Corner Micro Dropdown Language Switcher with Clean Sidebar Header)

### Done
- **تنظيف القائمة الجانبية وإعادة الشعار لوضعه الصافي ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * إزالة أي عناصر أو أزرار دخيلة من ترويسة القائمة (`.sidebar-header`) ليبقى الشعار وهوية المستخدم في المنتصف بنظافة كاملة.
- **تثبيت المنسدلة المصغرة في الزاوية المعاكسة للقائمة ديناميكياً (`.lang-corner-dropdown`)**:
  * استخدام الخاصية المنطقية `inset-inline-end: 20px; top: 10px;` ليتحرك الزر المنسدل تلقائياً عكس جهة القائمة:
    * في العربية (القائمة على اليمين) ⬅️ زر اللغة في **أعلى اليسار بالزاوية** (`left: 20px`).
    * في الإنجليزية (القائمة على اليسار) ⬅️ زر اللغة في **أعلى اليمين بالزاوية** (`right: 20px`).
  * زر مصغر بارتفاع 28px فقط (`[ 🇮🇶 عربي ▾ ]` / `[ 🇬🇧 EN ▾ ]`) بخلفية زجاجية معتمة وظل ناعم.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/LocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/LocalizationTest.php))**:
  * اجتياز كامل حزمة `LocalizationTest` بنسبة 100% (`9 passed, 72 assertions`).

## Session log (2026-11-19 — المرحلة 251: تحرير القائمة الجانبية بالكامل ونقل محول اللغة بالأعلام إلى الزاوية العلوية الثابتة Decoupling Language Switcher from Sidebar to Floating Top Corner Widget)

### Done
- **فصل محول اللغة عن القائمة الجانبية ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * حذف محول اللغة من القائمة الجانبية نهائياً لتحرير كامل المساحة والارتفاع لروابط الأقسام بدون أي تأثير بصري أو تزاحم.
  * تحويله إلى ويدجت عائم ثابت أعلى الشاشة (`.floating-lang-pill`): في الزاوية العلوية اليسرى في RTL (`left: 24px`)، والزاوية العلوية اليمنى في LTR (`right: 24px`) مع خلفية زجاجية أنيقة وظل ناعم وعلمي العراق وبريطانيا.
  * إخفاؤه تلقائياً في شاشات التابلت/الموبايل للاكتفاء بالشريط المدمج `#tabletTopbar`.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/LocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/LocalizationTest.php))**:
  * فحص وتأكيد ظهور `.floating-lang-pill` والأعلام المتجهة `iq.svg` و `gb.svg`.
  * اجتياز كامل حزمة `LocalizationTest` بنسبة 100% (`9 passed, 72 assertions`).

## Session log (2026-11-19 — المرحلة 250: التعريب والترجمة الشاملة الدقيقة 100% لعيادات الاستشارية ومحطة الطبيب وسجل الطلبات الطبية والسجل التاريخي للمريض مع تنبيه الفحوصات المعلقة وتوسيع القاموس لـ 1,871 مفتاحاً واختبارات الميزة الآلية Comprehensive 100% Granular Localization for Outpatient Visits, Doctor Workstation, Medical Orders & Tests History with Pending Tests Alert & 1,871 Dictionary Keys)

### Done
- **تعريب وترجمة محطة الطبيب وتنبيه الفحوصات المعلقة والعمود الأيسر الدائم ([resources/views/doctors/visits/show.blade.php](file:///C:/get/hospital-system/resources/views/doctors/visits/show.blade.php))**:
  * تعريب وترجمة تنبيه الفحوصات المعلقة السريري: `{{ __("توجد فحوصات طبية معلقة بانتظار صدور النتائج (عددها: :count)") }}` و `{{ __("يُمنع إنهاء الزيارة حتى تكتمل وتصدر نتائج كافة الفحوصات (المخبرية والشعاعية)...") }}` وزر `{{ __("تعليق واستدعاء التالي") }}` والتنبيه السريري عند محاولة إنهاء الزيارة قبل صدور النتائج.
  * تعريب العمود الأيسر الدائم للسجل الطبي ونتائج الفحوصات: شريط مراحل وتتبع الطلبات (طلب الطبيب، الكاشير، القسم المنفذ، النتيجة جاهزة)، كرت الوصفة الطبية (RX) وصرف الصيدلية، جدول الفحوصات السريري للمختبر والأشعة والتمريض، تقارير الفحوصات المرفقة وملفات الـ PDF والصور الشعاعية، الانطباع والمشاهدات السريرية، وأرشيف الطلبات الملغاة، وتبويبات الزيارات السابقة والمواعيد والعمليات.
- **تعريب وترجمة السجل الطبي الشامل للمريض ([resources/views/doctors/patient-history.blade.php](file:///C:/get/hospital-system/resources/views/doctors/patient-history.blade.php))**:
  * تعريب 100% لبطاقة المريض والحساسية والأمراض المزمنة، فلاتر السجل الطبي (نطاق التاريخ، نوع الحدث، الطبيب، والبحث)، الخط الزمني للرحلة الطبية (Patient Medical Journey)، وبطاقات الوصول السريع (آخر وصفة، آخر فحص مخبري، ملخص الخروج).
- **تعريب وترجمة سجل وإدارة الطلبات الطبية المركزي ([resources/views/staff/requests/index.blade.php](file:///C:/get/hospital-system/resources/views/staff/requests/index.blade.php))**:
  * تعريب جدول الطلبات العادية، طلبات طوارئ المختبر والأشعة، فلاتر الأقسام، شارة البث المباشر ومؤقت آخر تحديث، ومودال إدخال وتدقيق تحاليل الطوارئ مع تقييم المعدلات المرجعية المباشر في الجافاسكريبت.
- **تعريب شاشات الزيارات ومحطة الطبيب ومطبوعات الروشتة والمواعيد**:
  * تعريب `resources/views/visits/index.blade.php`، `visits/create.blade.php`، `visits/show.blade.php`، `visits/edit.blade.php`، `doctors/visits/index.blade.php`، `doctors/visits/prescription-print.blade.php`، و `doctors/visits/appointment-print.blade.php`.
- **القاموس المركزي المحدث ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
  * توسيع القاموس إلى **1,871 مفتاح ترجمة** مع تدقيق طبي ولغوي بنسبة 100%.
- **التوافقية البرمجية والاختبارات الآلية ([tests/Feature/VisitsLocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/VisitsLocalizationTest.php))**:
  * تصحيح استعلامات الـ SQL في `DoctorVisitController` و `StaffRequestController` لتكون متوافقة قياسياً مع MySQL و SQLite (`CURRENT_DATE` وحسابات الأولوية `CASE WHEN`).
  * إغلاق قسم المحتوى `@endsection` في `staff/requests/index.blade.php`.
  * بناء 4 اختبارات ميزة شاملة لجميع شاشات الزيارات ومحطة الطبيب وسجل الطلبات وتنبيه الفحوصات المعلقة واجتيازها بنجاح 100% (`4 passed, 78 assertions`).

## Session log (2026-11-18 — المرحلة 249: التعريب والترجمة الدقيقة 100% للمحطة السريرية الموحدة لإقامة العمليات الجراحية بكافة تبويباتها الـ 10 ومودالاتها ونصوص الجافاسكريبت التفاعلية وتوسيع القاموس لـ 1,195 مفتاحاً واختبارات الميزة الآلية Comprehensive 100% Granular Localization for Unified Clinical Inpatient & Surgery Station across All 10 Tabs, 4 Modals & Dynamic JS with 1,195 Translation Keys & Automated Feature Tests)

### Done
- **الترجمة الشاملة الدقيقة 100% للمحطة السريرية الموحدة ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * تغريب وترجمة تفصيلية بنظام `__()` دون استثناء لكافة التبويبات الـ 10 وعناصر الهيدر والمودالات الأربعة:
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
- **القاموس المركزي المحدث ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
  * توسيع القاموس إلى 1,195 مفتاح ترجمة طبي وإداري وسريري مع الالتزام التام بالمعايير الطبية المعتمدة وصحة بنية الـ JSON بنسبة 100%.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/ClinicalStationLocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/ClinicalStationLocalizationTest.php))**:
  * بناء اختبار ميزة متخصص `ClinicalStationLocalizationTest` للتحقق التلقائي من ترجمة عناوين وتفاصيل وتبويبات وشارات شاشة `/surgery-stations/clinical/{surgery}` بالجلستين العربية والإنجليزية.
  * اجتياز كامل حزمة اختبارات الترجمة والتعريب (`11 passed, 103 assertions`) بنجاح 100%.
  * اجتياز فحص `Laravel Pint` للتنسيق بنسبة 100%.

## Session log (2026-11-18 — المرحلة 248: تصميم وتنسيق زر تبديل اللغة بأعلام العراق وبريطانيا المتجهة وشريط الاختيار الثنائي المتطور Modern Segmented Language Switcher with Official Iraq & UK Vector Flags and Dedicated Sidebar Control)

### Done
- **الأعلام المتجهة الرقمية فائقة النقاء ([public/images/flags/iq.svg](file:///C:/get/hospital-system/public/images/flags/iq.svg), [public/images/flags/gb.svg](file:///C:/get/hospital-system/public/images/flags/gb.svg))**:
  * بناء وتضمين علم العراق الرقمي الرسمي (أحمر، أبيض، أسود مع عبارة "الله أكبر" بالخط الكوفي الأخضر) وعلم المملكة المتحدة (Union Jack) بالهندسة المتجهة الصرفة لتفادي تشوهات خطوط الإيموجي في ويندوز.
- **إعادة التوزيع والتنسيق المعماري للواجهة ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php), [resources/views/auth/login.blade.php](file:///C:/get/hospital-system/resources/views/auth/login.blade.php))**:
  * نقل زر التبديل من سطر المستخدم الضيق إلى كرت تحويل مزدوج مخصص ومستقل (`.lang-switcher-pill`) في القائمة الجانبية يضم خياري `[ 🇮🇶 عربي ]` و `[ 🇬🇧 English ]` مع إبراز الخيار النشط بظلال وخلفية زرقاء بارزة (`.lang-btn.active`).
  * تحديث زر الترويسة العلوية للتابلت والموبايل (`#tabletTopbar`) وشاشة تسجيل الدخول (`login.blade.php`) ليعرض علم الدولة المستهدفة بدقة ووضوح بدلاً من الأيقونة العامة `fa-globe`.
  * تنظيف بطاقة هوية الموظف في القائمة الجانبية وفصل رابط الخروج بانتظام.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/LocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/LocalizationTest.php))**:
  * إضافة اختبار `test_language_switcher_renders_iraq_and_uk_flags_and_segmented_pill` للتحقق من وجود ملفات الأعلام، نصوص الـ SVG، وظهور شريط التحويل في العرض.
  * اجتياز جميع اختبارات `LocalizationTest` بنجاح 100% (`10 passed, 91 assertions`).

## Session log (2026-11-18 — المرحلة 247: الترجمة الشاملة الدقيقة 100% لواجهة تفاصيل العمليات ولوحة التحكم والشاشات التمهيدية وتوسيع القاموس لـ 822 مفتاحاً Comprehensive Granular Localization for Surgery Show Details, Core Layout, Dashboard & Pre-Op Stations with 822 Keys & Automated Feature Tests)

### Done
- **واجهة تفاصيل العملية الجراحية ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * تعريب وترجمة كاملة وشاملة 100% بنظام `__()` دون استثناء أي عنصر لكافة الأقسام الـ 11:
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
- **شاشات الملاحة ولوحة التحكم والتسجيل ومحطات العمليات ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php), [resources/views/dashboard.blade.php](file:///C:/get/hospital-system/resources/views/dashboard.blade.php), [resources/views/auth/login.blade.php](file:///C:/get/hospital-system/resources/views/auth/login.blade.php), [resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php), [resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php), [resources/views/surgery-stations/anesthesia/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/anesthesia/index.blade.php), [resources/views/surgery-stations/nursing/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/nursing/index.blade.php), [resources/views/surgery-stations/resident/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/resident/index.blade.php), [resources/views/surgery-stations/surgeon/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/surgeon/index.blade.php), [resources/views/nurse-availability/index.blade.php](file:///C:/get/hospital-system/resources/views/nurse-availability/index.blade.php), [resources/views/nurse-availability/off-duty.blade.php](file:///C:/get/hospital-system/resources/views/nurse-availability/off-duty.blade.php))**:
  * تعريب عناصر القائمة الجانبية والتنقل، إحصائيات لوحة التحكم، شاشة الدخول وبطاقة التوفر خارج الدوام، ومحطات العمليات التمهيدية لضمان بيئة عمل ثنائية اللغة متناسقة.
- **القاموس المركزي المحدث ([lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
  * توسيع القاموس من 526 مفتاحاً إلى 822 مفتاح ترجمة طبي وإداري دقيق دون أي تكرار وبفحص تحقق صفري للأخطاء.
- **الاختبارات الآلية وضمان الجودة ([tests/Feature/SurgeryShowLocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryShowLocalizationTest.php), [tests/Feature/LocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/LocalizationTest.php))**:
  * بناء اختبار ميزة متخصص `SurgeryShowLocalizationTest` للتحقق التلقائي من ترجمة عناوين وتفاصيل وحقول ومصفوفات شاشة `/surgeries/{id}` بجلستي العربية والإنجليزية.
  * اجتياز جميع اختبارات التعريب وحزمة اختبارات العمليات (`36 passed, 227 assertions`) بنسبة نجاح 100%.

## Session log (2026-11-18 — المرحلة 246: التأسيس المعماري لدعم تعدد اللغات عربي وإنجليزي وتبديل الجلسة وتوافق القائمة الجانبية Multi-Language Localization Foundation with Instant AR/EN Switcher, SetLocale Middleware & LTR Sidebar Layout)

### Done
- **الوسيط ومحرك اللغة ([app/Http/Middleware/SetLocale.php](file:///C:/get/hospital-system/app/Http/Middleware/SetLocale.php), [bootstrap/app.php](file:///C:/get/hospital-system/bootstrap/app.php))**:
  * بناء وسيط `SetLocale` لضبط لغة التطبيق النشطة `App::setLocale()` استناداً لقيمة الجلسة `session('locale', 'ar')` مع حصرها بالأكواد المعتمدة (`ar`, `en`) والعودة التلقائية للعربية عند أي قيمة غير صالحة.
  * تسجيل الوسيط ضمن حزمة `$middleware->web(append: [SetLocale::class])`.
- **المسار والقاموس المركزي ([routes/web.php](file:///C:/get/hospital-system/routes/web.php), [lang/en.json](file:///C:/get/hospital-system/lang/en.json))**:
  * تسجيل مسار التبديل الفوري `GET /lang/{locale}` مع حفظ الاختيار بالجلسة والرجوع التلقائي للصفحة الحالية `redirect()->back()`.
  * إنشاء قاموس الترجمة المركزي `lang/en.json` متضمناً أهم المصطلحات الطبية والإدارية (أقسام المستشفى، المحطات السريرية، الحالات، الإجراءات، الأزرار، والترويسات).
- **واجهة المستخدم والقالب الأساسي ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * جعل اتجاه ولغة المستند ديناميكياً: `<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">`.
  * إضافة أزرار وشارات تبديل اللغة التفاعلية في الترويسة العلوية للموبايل/التابلت (`#tabletTopbar`) وفي بطاقة هوية الموظف بالقائمة الجانبية (`sidebar-user`).
  * ضبط هندسة وتنسيقات القائمة الجانبية بالكامل (Fixed Coordinates: `top: 0; bottom: 0; z-index: 1030`) وضبط اتجاهات ومحاذاة الشاشات الكبيرة والمحمولة (`left: 0` في LTR و `right: 0` في RTL) مع تدفق المحتوى الرئيسي `main-content` وتصحيح زاوية دوران سهم التوسع (`rotate(90deg)` في LTR).
- **الاختبارات الآلية وتنسيق الكود ([tests/Feature/LocalizationTest.php](file:///C:/get/hospital-system/tests/Feature/LocalizationTest.php))**:
  * بناء 5 اختبارات ميزة آلية تغطي التحويل للإنجليزية، والعودة للعربية، وحماية المدخلات غير الصالحة، وعرض `dir="ltr"` والترجمات، وعرض `dir="rtl"` بنسبة نجاح 100% (`5 passed, 15 assertions`).
  * اجتياز فحص `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-18 — المرحلة 245: نظام طلب وإدارة الاستشارات الطبية التخصصية السريرية للعمليات الجراحية باسم الطبيب وتوثيق الردود Medical Consultations Inpatient Order System by Consultant Doctor with Clinical Response & Recommendations Tracking)

### Done
- **قاعدة البيانات والنموذج ([database/migrations/2026_11_18_120000_create_surgery_consultations_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_18_120000_create_surgery_consultations_table.php), [app/Models/SurgeryConsultation.php](file:///C:/get/hospital-system/app/Models/SurgeryConsultation.php), [app/Models/Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php), [app/Models/Doctor.php](file:///C:/get/hospital-system/app/Models/Doctor.php))**:
  * إنشاء جدول مستقل `surgery_consultations` متضمناً: معرف العملية `surgery_id`، معرف الطبيب الاستشاري `doctor_id`، التخصص `specialty`، سبب الاستشارة والتشخيص `reason`، درجة الأولوية `priority` (`routine`, `urgent`, `stat`)، حالة الاستشارة `status` (`pending`, `completed`, `cancelled`)، رد وتوصيات الاستشاري `consultant_response` و `consultant_recommendations`، أجر الاستشارة `fee`، مع توثيق هوية الطالب والرد والوقت (`requested_by`, `responded_by`, `responded_at`).
  * ربط العلاقات في موديلات `Surgery` (`hasMany(SurgeryConsultation::class)`) و `Doctor` (`hasMany(SurgeryConsultation::class)`).
  * إضافة دوال المساعدة للشارات والمسميات (`priority_label`, `priority_badge_class`, `status_label`, `status_badge_class`).
- **الصلاحيات ومصفوفة التحكم ([app/Models/SurgeryTabPermission.php](file:///C:/get/hospital-system/app/Models/SurgeryTabPermission.php))**:
  * تسجيل تبويب `consultations` في `TABS` للمحطة السريرية وتبويب `detail_consultations` في `DETAIL_TABS` لصفحة تفاصيل العملية.
  * ضبط مصفوفة الوصول التلقائية لجميع الأدوار الطبية والتمريضية والجراحية السبعة.
- **الكنترولرات والمسارات ([app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * تسجيل مسارات إرسال طلب الاستشارة (`POST /clinical/{surgery}/consultations`)، توثيق رد الاستشاري (`POST /clinical/{surgery}/consultations/{consultation}/respond`)، وإلغاء الطلب المعلق (`DELETE /clinical/{surgery}/consultations/{consultation}`).
  * تزويد المحطة السريرية بقائمة الأطباء الاستشاريين المتاحين مرتبين مع إبراز توفر اليوم وأجورهم وتخصصاتهم.
  * السماح للطبيب الاستشاري المعني بالاستشارة بمعاينة ملف العملية الجراحية وتوثيق التوصيات.
- **الواجهات الأمامية وتجربة المستخدم ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php), [resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * إضافة تبويب مخصص ومستقل للاستشارات الطبية التخصصية (`consultations`) بشارة إحصائية تلخص عدد الاستشارات المعلقة والمكتملة.
  * نموذج طلب استشارة أنيق مع قائمة منسدلة للأطباء الاستشاريين وتعبئة تلقائية فورية للتخصص والأجر، وحقول الأولوية والسبب.
  * كروت استعراض السجل الطبي للاستشارات مع شارات الحالة والأولوية، ونموذج توثيق رد وتوصيات الاستشاري القابل للطي السلس، وإمكانية إلغاء الطلبات المعلقة.
  * عرض سجل الاستشارات الطبية في صفحة تفاصيل العملية الجراحية ضمن تبويب `detail_consultations`.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryConsultationTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryConsultationTest.php))**:
  * بناء 6 اختبارات ميزة آلية شاملة تغطي فتح التبويب، حفظ الطلب، توثيق الرد، إلغاء الطلب، حماية المكتمل من الإلغاء، وعرض الاستشارات في صفحة العملية بنسبة نجاح 100% (`6 passed, 32 assertions`).
  * اجتياز فحص `Laravel Pint` بالكامل دون أي أخطاء.

## Session log (2026-11-18 — المرحلة 244: إضافة خانات تقييم العلامات السريرية الخمس بالإنجليزية مع التبديل التفاعلي الفوري بين Positive و Negative التلقائي 5 Clinical Signs Checkboxes with Instant English Positive/Negative Toggle in Resident Follow-Up Sheet)

### Done
- **قاعدة البيانات والنموذج ([database/migrations/2026_11_18_110000_add_clinical_signs_to_resident_station_follow_ups_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_18_110000_add_clinical_signs_to_resident_station_follow_ups_table.php), [app/Models/ResidentStationFollowUp.php](file:///C:/get/hospital-system/app/Models/ResidentStationFollowUp.php))**:
  * إضافة عمودي `clinical_signs` (JSON) و `other_notes` (Text) إلى جدول `resident_station_follow_ups`.
  * إضافة دالة تعريف العلامات السريرية الخمس `availableClinicalSigns()`: `Oriented`، `Clear chest`، `Soft abdomen`، `Urine output`، `Yellowish discolouration`.
  * إضافة Accessors `positive_signs` و `negative_signs` لاستخراج العلامات الإيجابية والسلبية بدقة وتوافق تام.
- **الكنترولرات وتجميع الملاحظات ([app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php), [app/Http/Controllers/ResidentStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ResidentStationController.php))**:
  * استلام وحفظ مصفوفة العلامات المحددة كـ `Positive` وحفظ الملاحظات الإضافية `other_notes`.
  * تجميع نصوص المتابعة باللغة الإنجليزية الصريحة `✓ Positive:` و `✗ Negative:` لضمان التوافقية العكسية 100% مع كافة الشاشات والتقارير والطباعة.
- **الواجهات الأمامية وتجربة المستخدم ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php), [resources/views/surgery-stations/resident/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/resident/show.blade.php), [resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * إضافة خيارات التقييم السريري الخمس في تبويب المتابعة للطبيب المقيم.
  * الحالة الافتراضية (Default) قبل الاختيار تظهر بوضوح وبدون تضليل رمادي كشارة حمراء بارزة: `Negative` (`badge bg-danger text-white`).
  * بمجرد وضع علامة الصح (وضع جيك) تتحول الشارة فورياً وديناميكياً إلى خضراء بارزة: `Positive` (`badge bg-success text-white`) بالإنجليزية حصراً وبدون نصوص عربية.
  * عرض سجلات المتابعة المحفوظة في الخط الزمني والجداول بتصنيف `Positive:` و `Negative:` واضح ودقيق لكل علامة.
- **الاختبارات الآلية وتنسيق الكود ([tests/Feature/ResidentFollowUpClinicalSignsTest.php](file:///C:/get/hospital-system/tests/Feature/ResidentFollowUpClinicalSignsTest.php))**:
  * بناء 5 اختبارات ميزة شاملة تغطي ظهور شارات `Negative` الافتراضية، والحفظ بالكنترولرين، والتحقق، وعرض النتائج بنجاح 100% (`5 passed, 38 assertions`).
  * اجتياز فحص `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-18 — المرحلة 243: إعادة تصميم وتطوير واجهة تعديل المستخدمين بتخطيط مزدوج ذكي وتصنيف مرئي للأدوار وبطاقة هوية الموظف Modern Redesign of User Edit Interface with Dual-Column Layout, Smart Categorized Roles Matrix, and Employee Profile Card)

### Done
- **إعادة بناء واجهة تعديل المستخدمين بالكامل ([resources/views/users/edit.blade.php](file:///C:/get/hospital-system/resources/views/users/edit.blade.php))**:
  * استبدال الترويسة الصفراء القديمة `bg-warning` بهوية طبية زرقاء فاخرة ناصعة وتخطيط ثنائي الأبعاد (4:8).
  * إضافة بطاقة هوية الموظف الجانبية (Profile Sidebar Card) متضمنة صورة رمزية ملونة (Avatar)، شارة الحالة التشغيلية الفورية (نشط/معطل)، رقم المعرف `#ID`، ملخص الأدوار الحالية، وتواريخ الحساب.
  * تصنيف مصفوفة الأدوار الـ 36 إلى مجموعات طبية وإدارية منظمة (الكوادر الطبية والجراحية، الاستقبال والتمريض والطوارئ، المحطات التشخيصية، المالية والكاشير، الصيدلية والمستودع، الإدارة والنظام) مع أزرار تحديد/إلغاء لكل قسم.
  * إضافة محرك بحث لحظي سريع في الأدوار (Live Search) وفلترة البطاقات بنقرة زر مع عدّاد تفاعلي مباشر لعدد الأدوار المحددة.
  * تصميم كروت أمان مستقلة لكلمة المرور مع زر إظهار/إخفاء كلمة المرور وتلميحات إرشادية.
  * ربط أنيق لاختيار المخزن أو عهدة الطابق مع أيقونات إدخال عصرية (`input-group`).
- **التحقق الآلي وتنسيق الكود ([tests/Feature/UserEditModernViewTest.php](file:///C:/get/hospital-system/tests/Feature/UserEditModernViewTest.php))**:
  * بناء اختبارات ميزة متخصصة تؤكد فتح الشاشة بتصميمها الجديد، وتصنيف الأدوار، ونجاح التحديث بنسبة 100% (`2 passed, 16 assertions`).
  * اجتياز فحص `Laravel Pint` وتنسيق كافة الملفات.

## Session log (2026-11-18 — المرحلة 242: استبدال تنبيه التوفر الداكن بنظام Toastr الطبي الناصع وتوحيد مصفوفة ومسميات أدوار التخدير ومشرف التمريض Medical Clean Toastr Migration & Role Permission Harmonization for Anesthesia and Head Nurse)

### Done
- **استبدال التنبيه الداكن بنظام Toastr الطبي ([resources/views/nurse-availability/index.blade.php](file:///C:/get/hospital-system/resources/views/nurse-availability/index.blade.php))**:
  * إزالة خلفية `bg-dark text-white` السوداء تماماً من شاشة إدارة توفر التمريض.
  * ربط الاستجابة الفورية عند التبديل بنظام المستشفى المعتمد `toastr.success` و `toastr.error` بألوان طبية فاتحة ومريحة للعين في أعلى الشاشة.
  * تحديث كرت الـ Toast الاحتياطي بتصميم أبيض ناصع `bg-white text-dark border-success-subtle` وأيقونة نجاح خضراء.
- **توحيد ومواءمة مصفوفة الصلاحيات والأدوار ([app/Http/Controllers/RoleManagementController.php](file:///C:/get/hospital-system/app/Http/Controllers/RoleManagementController.php), [database/seeders/RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php))**:
  * إضافة الدورين `anesthesia` و `head_nurse` وأدوار التمريض والأطباء المتخصصين (`icu_nurse`, `nicu_nurse`, `icu_doctor`, `nicu_doctor`) إلى `getRoleLabel()` بأسماء عربية صريحة وأيقونات طبية.
  * إدراج `anesthesia` ضمن فئة "الكوادر الطبية والجراحية" وإدراج `head_nurse` ضمن "الاستقبال والتمريض والطوارئ" في `getRoleCategory()`.
  * إدراج صلاحية `manage nurse availability` في مصفوفة الصلاحيات لشاشات التعديل (`roles.edit`) مع تسمية عربية واضحة وشارة إدارة.
  * مزامنة قاعدة البيانات لتمتلك أدوارهما كافة الصلاحيات المعتمدة واكتمال التحقق في `RolePermissionsMatrixTest` و `NurseAvailabilityTest`.

## Session log (2026-11-18 — المرحلة 241: واجهة الهبوط المخصصة والذكية للممرضين خارج جدول الدوام الرسمي مع التحقق وإعادة المحاولة الفورية Dedicated Off-Duty Landing Screen for Unscheduled Nurses with Live Activation Check & Access Recovery)

### Done
- **شاشة الهبوط المخصصة ([resources/views/nurse-availability/off-duty.blade.php](file:///C:/get/hospital-system/resources/views/nurse-availability/off-duty.blade.php))**:
  * استبدال رسالة التنبيه الحمراء لشاشة تسجيل الدخول بواجهة هبوط متكاملة، أنيقة ورسمية تحمل هوية النظام والمستشفى.
  * عرض اسم الممرض، بريده، شارة `🔴 خارج جدول الدوام الرسمي لليوم`، وتاريخ اليوم المنسق.
  * رسالة إرشادية طبية واضحة تفيد بعدم إدراج الحساب بجدول دوام اليوم، مع توجيه للتواصل مع مدير أو مشرف التمريض.
  * زر تفاعلي ذكي `[ 🔄 فحص التفعيل والدخول للنظام ]` لتمكين الممرض من الدخول الفوري لمحطة التمريض بمجرد تفعيله من قبل المشرف دون الحاجة لإعادة كتابة البريد وكلمة المرور مجدداً، مع زر تسجيل خروج رسمي.
- **الكنترولر والميدلوير ([app/Http/Controllers/NurseAvailabilityController.php](file:///C:/get/hospital-system/app/Http/Controllers/NurseAvailabilityController.php), [app/Http/Middleware/CheckNurseAvailability.php](file:///C:/get/hospital-system/app/Http/Middleware/CheckNurseAvailability.php), [app/Http/Controllers/Auth/LoginController.php](file:///C:/get/hospital-system/app/Http/Controllers/Auth/LoginController.php))**:
  * إضافة مساري `nurse.off-duty` و `nurse.check-access` في ([routes/web.php](file:///C:/get/hospital-system/routes/web.php)).
  * توجيه الممرض غير المداوم فور تسجيل الدخول إلى واجهة `nurse.off-duty`.
  * تحديث وسيط الحماية لتثبيت الممرض غير المداوم داخل واجهة الهبوط ومنع وصوله لأي سجلات أو محطات سريرية مع استثناء مسارات الفحص والخروج.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/NurseAvailabilityTest.php](file:///C:/get/hospital-system/tests/Feature/NurseAvailabilityTest.php))**:
  * توسيع حزمة الاختبارات لتشمل 10 اختبارات ميزة تغطي التحويل لشاشة الهبوط، ومنع التسلل للمحطة، وإعادة الفحص والدخول السلس بنسبة نجاح 100% (`10 passed, 41 assertions`).

## Session log (2026-11-17 — المرحلة 240: نظام إدارة توفر وجدول دوام التمريض اليومي وتوزيع الطوابق وتقييد تسجيل الدخول Nurse Availability & Daily Floor Scheduling Management System with Login Restriction)

### Done
- **قاعدة البيانات ونموذج التوفر ([database/migrations/2026_11_17_130000_create_nurse_availabilities_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_17_130000_create_nurse_availabilities_table.php), [app/Models/NurseAvailability.php](file:///C:/get/hospital-system/app/Models/NurseAvailability.php), [app/Models/User.php](file:///C:/get/hospital-system/app/Models/User.php))**:
  * إنشاء جدول مستقل `nurse_availabilities` لحفظ سجلات دوام وأرشيف التمريض اليومي (`user_id`, `date`, `is_available`, `assigned_floor`, `shift`, `notes`, `created_by`) مع قيد فريد يمنع التكرار لليوم الواحد.
  * ربط العلاقات ودالة الفحص السريعة `isNurseAvailableToday()` في موديل `User`.
- **الكنترولر ومحرك التوفر ([app/Http/Controllers/NurseAvailabilityController.php](file:///C:/get/hospital-system/app/Http/Controllers/NurseAvailabilityController.php))**:
  * بناء كنترولر متكامل مع فلاتر التاريخ والطوابق والبحث، واحتساب الإحصائيات (إجمالي الكادر، المتاحين بالدوام، خارج الدوام، نسبة التغطية).
  * دعم التبديل اللحظي (AJAX Toggle Switch) للتوفر، وتحديث الطابق المخصص لليوم وربطه بمخزن الطابق تلقائياً، والتحكم الجماعي (تفعيل دوام الكل / إيقاف دوام الكل).
- **الواجهة وتجربة المستخدم ([resources/views/nurse-availability/index.blade.php](file:///C:/get/hospital-system/resources/views/nurse-availability/index.blade.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * تصميم واجهة متطورة بكروت إحصائية علوية، مفاتيح تبديل سريعة، قوائم منسدلة فورية لاختيار الطابق والشفت مع إشعارات Toast تفاعلية.
  * إضافة رابط `جدول دوام وتوفر التمريض` في القائمة الجانبية المخصصة للمدراء ومسؤولي التمريض.
- **حماية النظام وتقييد تسجيل الدخول ([app/Http/Controllers/Auth/LoginController.php](file:///C:/get/hospital-system/app/Http/Controllers/Auth/LoginController.php), [app/Http/Middleware/CheckNurseAvailability.php](file:///C:/get/hospital-system/app/Http/Middleware/CheckNurseAvailability.php), [bootstrap/app.php](file:///C:/get/hospital-system/bootstrap/app.php))**:
  * قفل تسجيل الدخول لأي ممرض غير مسجل كمتوفر اليوم مع رسالة توجيهية صريحة.
  * ميدلوير حماية استباقي يمنع أي إجراء في حال إيقاف دوام الممرض أثناء الشفت.
- **الصلاحيات والتحقق الآلي ([database/seeders/RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php), [tests/Feature/NurseAvailabilityTest.php](file:///C:/get/hospital-system/tests/Feature/NurseAvailabilityTest.php))**:
  * إضافة دور `head_nurse` وصلاحية `manage nurse availability`.
  * بناء 8 اختبارات ميزة متخصصة تؤكد كامل جوانب الميزة بنسبة نجاح 100% (`8 passed, 30 assertions`).

## Session log (2026-11-17 — المرحلة 239: الانتقال التلقائي الذكي للمرحلة السريرية وإلغاء أزرار التبديل اليدوية المربكة Smart Auto Phase Transition & Header Badge in Clinical Station)

### Done
- **الباك إند والتعرف الذكي ([app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php))**:
  * تطوير منطق تحديد المرحلة التلقائي (`$isPostOpReady`) ليفحص اكتمال صالة العمليات (`operationTheaterStation completed`)، أو اكتمال التخدير، أو اكتمال محطة ما قبل العملية، أو دخول المريض مرحلة الإفاقة/الانتهاء (`completed`/`recovery`).
  * إنشاء سجل ما بعد العملية `postOpStation` تلقائياً فور جاهزية الحالة، وتعيين المرحلة الافتراضية تلقائياً دون إجبار الكادر الطبي والتمريضي على التبديل اليدوي، مع استمرار دعم الاستعراض اليدوي الاختياري.
- **الواجهة الأمامية وتجربة المستخدم ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * إزالة زري التبديل المربكين `[ ما قبل العملية ]` و `[ ما بعد العملية ]` وحذف فئات التنسيق القديمة غير المستخدمة.
  * استبدالهما بشارة مرحلة سريرية ذكية معتمدة على وضع المريض: `[ ⏳ تحضير ما قبل العملية ]` باللون الأزرق أو `[ 🩺 عناية ما بعد العملية (الرقود) ]` باللون الأخضر.
  * إضافة رابط أرشيف واستعراض ثانوي أنيق وصغير بجانب الشارة (`[ 📂 أرشيف ما قبل العملية ]` أو `[ ما بعد العملية ⬅️ ]`) لمن يرغب بمراجعة السجل الآخر.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/ClinicalStationMultiDrainTest.php](file:///C:/get/hospital-system/tests/Feature/ClinicalStationMultiDrainTest.php))**:
  * إضافة اختبار `test_clinical_station_auto_detects_smart_phase_and_renders_badge()` للتحقق من فتح النظام تلقائياً على ما قبل العملية للحالات الجديدة، والانتقال التلقائي لما بعد العملية عند اكتمال الحالة وظهور شارة المرحلة ورابط الأرشيف بنسبة نجاح 100% (`8 passed, 53 assertions`).

## Session log (2026-11-17 — المرحلة 238: إزالة قائمة اختيار الممرض المسؤول يدوياً من تبويب الرعاية التمريضية والاعتماد على التوثيق التلقائي للممرض المسجل Remove Redundant Manual Nurse Selector from Nursing Care Tab in Favor of Auto-Logged Nurse ID)

### Done
- **تحسين الواجهة وتجربة المستخدم ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * إزالة حقل وقائمة الاختيار المنسدلة الزائدة `الممرض المسؤول عن الحالة` (`nurse_id`) من تبويب `tab=nursing_care` لكون النظام يربط ويوثق هوية الممرض تلقائياً من جلسة الدخول (`auth()->id()`) في الكنترولر.
  * ترشيق واجهة التمريض لتقتصر حصراً وبشكل مباشر على حقلي: ملاحظات الرعاية التمريضية (`nursing_notes`) وتعليمات وملاحظات التخريج (`discharge_notes`).
- **التحقق الآلي وتنسيق الكود ([tests/Feature/ClinicalStationMultiDrainTest.php](file:///C:/get/hospital-system/tests/Feature/ClinicalStationMultiDrainTest.php))**:
  * تحديث اختبار `test_nursing_care_tab_renders_notes_fields_and_saves()` لتأكيد عدم وجود حقل `nurse_id` في الواجهة (`assertDontSee('name="nurse_id"')`)، والتأكد من إسناد هوية الممرض المسجل في الجلسة تلقائياً لقاعدة البيانات في جدول `nursing_stations` بنسبة نجاح 100% (`7 passed, 47 assertions`).

## Session log (2026-11-17 — المرحلة 237: استعادة حقول نموذج وملاحظات الرعاية التمريضية وتخريج المريض في المحطة السريرية Restore Nursing Care & Discharge Notes Tab Fields & Form Submission in Clinical Station)

### Done
- **معالجة حجب الحقول في الواجهة ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * حل مشكلة اختفاء حقول تبويب التمريض `tab=nursing_care`، حيث كان شرط غلاف النموذج المباشر يفحص `in_array($activeTab, ['history', 'vitals', 'plan', 'discharge'])` دون مفتاح `nursing_care`.
  * إضافة مفتاح `'nursing_care'` إلى مصفوفة التحقق، مما مكّن نموذج التحديث من إظهار حقول اختيار الممرض المسؤول `nurse_id`، ملاحظات الرعاية التمريضية `nursing_notes`، وتعليمات وملاحظات التخريج `discharge_notes`، وزر الحفظ الموحد `[ 💾 حفظ التحديثات السريرية ]`.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/ClinicalStationMultiDrainTest.php](file:///C:/get/hospital-system/tests/Feature/ClinicalStationMultiDrainTest.php))**:
  * إضافة اختبار ميزة شامل `test_nursing_care_tab_renders_notes_fields_and_saves()` يتحقق من استجابة HTTP 200 عند فتح التبويب، وظهور الحقول بالواجهة، وتأكيد نجاح عملية الحفظ والتحديث في جدول `nursing_stations` بنسبة نجاح 100% (`7 passed, 46 assertions`).

## Session log (2026-11-17 — المرحلة 236: دعم قراءات الدرنقات الجراحية المتعددة وغير المحدودة لكل مريض بجدول فرعي مستقل وتجميع تلقائي لميزان السوائل Multi-Drain Clinical Architecture with Dynamic Child Table, Live Fluid Balance Auto-Sum & Interactive Modal)

### Done
- **قاعدة البيانات وموديل الدرنقات ([database/migrations/2026_11_17_093000_create_resident_station_reading_drains_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_17_093000_create_resident_station_reading_drains_table.php), [app/Models/ResidentStationReadingDrain.php](file:///C:/get/hospital-system/app/Models/ResidentStationReadingDrain.php), [app/Models/ResidentStationReading.php](file:///C:/get/hospital-system/app/Models/ResidentStationReading.php))**:
  * إنشاء جدول فرعي مستقل `resident_station_reading_drains` يحفظ كل قراءة درنقة في سجل وعمود `amount` صريح مع رقم الدرنقة `drain_number` واسم/موقع الدرنقة `drain_label` ومفتاح أجنبي cascade مرتبط بالقراءة.
  * ربط العلاقة `hasMany` باسم `drains` في موديل `ResidentStationReading` مع الترتيب برقم الدرنقة.
- **كنترولر المحطة السريرية ([app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php))**:
  * إضافة دالتي المزامنة والاستخراج `parseDrainsFromRequest` و `syncReadingDrains`.
  * دعم استلام الدرنقات كمصفوفة `drains` أو كسلسلة نصية `drains_json`، مع الحساب والتجميع التلقائي لمجموعها في `output_drain` وميزان السوائل `fluid_balance`.
  * دعم التوافقية العكسية 100%: عند إرسال رقم وحيد في `output_drain` يتم تلقائياً إنشاء سجل درنقة مفرد لها في الجدول الفرعي.
  * شمول `drains` و `drains_json` في فحص مدخلات السوائل `$hasFluidsInput` لضمان الحفظ والمزامنة حتى في حال عدم إدخال سوائل أخرى.
  * تضمين العلاقة `drains` في الاستعلامات المسبقة (Eager Loading) لمنع مشكلة N+1.
- **واجهة العلامات الحيوية والسوائل ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * إضافة زر أيقونة `[ 📋 ]` بجانب خانة الدرنقة يفتح نافذة منبثقة تفاعلية `#drainDetailsModal` لإضافة أي عدد مفتوح من الدرنقات (درنقة 1، درنقة 2، درنقة 3...) مع تسمية موقعها وإدخال كميتها بالـ ml وحساب المجموع لحظياً.
  * إضافة شارة عدّاد تفاعلية أسفل الخانة تبين عدد الدرنقات المدخلة.
  * عرض إجمالي الدرنقات في جدول القراءات التاريخية مع بادج ملون `[ 👁️ X درنقة ]` يعرض تفاصيل كل درنقة بالـ Tooltip ويفتح نافذة استعراض منبثقة `#viewDrainDetailsModal` عند النقر.
  * تحديث `loadReadingIntoTopForm` و `cancelEditingReading` لتحميل ومسح بيانات الدرنقات بسلاسة تامة.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/ClinicalStationMultiDrainTest.php](file:///C:/get/hospital-system/tests/Feature/ClinicalStationMultiDrainTest.php))**:
  * إنشاء حزمة اختبارات متخصصة تؤكد: حفظ درنقات متعددة كمصفوفة، حفظها كـ JSON، إنشاء سجل تلقائي للقيمة الفردية، تعديل الدرنقات، والحذف المتسلسل بنسبة نجاح 100% (`6 passed, 36 assertions`).
  * اجتياز كامل حزمة اختبارات العمليات ومصفوفة الصلاحيات (`15 passed, 196 assertions`).
  * اجتياز فحص `Laravel Pint` وتنسيق كافة الملفات.

## Session log (2026-11-16 — المرحلة 235: تمكين حساب الجراحين العام من استعراض كافة العمليات بالصالة، فك تقييد إتمام صالة العمليات، والربط السلس بملف وسجل العملية Surgeon Station: Display All Active Operating Room Surgeries for Shared Surgeon Account, Remove Operation Theater Completed Constraint, and Streamline Direct Click-Through Navigation)

### Done
- **معالجة حجب العمليات عن حساب الجراحين العام ([app/Http/Controllers/SurgeonStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeonStationController.php))**:
  * **توسيع التعرف على الحساب الموحد للجراحين (`$isSharedAccount`)**: شمول الاسم المعتمد في قاعدة البيانات `حساب الجراحين العام` بالإضافة إلى `حساب الجراحين`، البريد `gh@hospital.com`، دور `الجراح` بدون بروفايل طبيب، وصلاحية `view surgeon station`.
  * **إزالة تقييد حصر العمليات بـ `operationTheaterStation completed`**: إلغاء شرط `whereHas('operationTheaterStation', fn($q) => $q->where('status', 'completed'))` الذي كان يخفي 9 من أصل 14 عملية نشطة ومجدولة وقيد الانتظار، واستبداله بعرض كافة العمليات غير الملغاة `where('status', '!=', 'cancelled')` بمطابقة كاملة مع محطة التخدير.
  * **فك حظر فتح محطة الجراح في `show`**: إزالة شرط إعادة التوجيه لخطأ `يجب إتمام مرحلة صالة العمليات أولاً`، وتمكين الجراح من فتح وتوثيق الخطة والتوجيهات السريرية في أي مرحلة.
  * **تحديث الصلاحيات `authorizeSurgeon`**: منح الحساب الموحد للجراحين صلاحية كاملة على كل العمليات بالصالة دون أي حجب 403.
  * **دعم فلترة الحالات غير المسندة**: تمكين فلتر `doctor_id === 'unassigned'` لجلب العمليات التي بانتظار تعيين طبيب جراح.
- **تطوير واجهة محطة الجراح ([resources/views/surgery-stations/surgeon/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/surgeon/index.blade.php))**:
  * إضافة خيار `⚠️ بانتظار تعيين طبيب جراح` وخيار `👨‍⚕️ كل العمليات بالصالة` بالقائمة المنسدلة.
  * جعل النقر على اسم المريض، صورته الرمزية، واسم العملية روابط مباشرة وسلسة تفتح تفاصيل وسجل العملية (`surgeries.show`) مع تأثير hover مميز.
  * تمييز العمليات التي لم يُعين لها جراح ببادج تفاعلي `[ ➕ بانتظار التعيين ]` يفتح العملية للتعيين الفوري.
- **توجيه تسجيل الدخول والصفحة الرئيسية ([app/Http/Controllers/Auth/LoginController.php](file:///C:/get/hospital-system/app/Http/Controllers/Auth/LoginController.php), [app/Http/Controllers/DashboardController.php](file:///C:/get/hospital-system/app/Http/Controllers/DashboardController.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * تحديث منطق التوجيه لشمل `حساب الجراحين العام` و `gh@hospital.com` ليكون الهبوط الرئيسي دائماً في محطة الجراح.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeonGeneralAccountAllSurgeriesTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeonGeneralAccountAllSurgeriesTest.php), [tests/Feature/SurgeonStationAccessAndWorkflowTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeonStationAccessAndWorkflowTest.php))**:
  * بناء 4 اختبارات متخصصة تؤكد: استعراض حساب الجراحين العام لكافة العمليات بالصالة بنجاح، عمل فلتر الحالات غير المسندة، إمكانية فتح المحطة دون انتظار إتمام الصالة، وتوجيه تسجيل الدخول بنسبة نجاح 100% (`4 passed, 15 assertions`).
  * اجتياز اختبارات تدفق الجراح الأساسية بنسبة 100% (`5 passed, 22 assertions`).
  * اجتياز كامل حزمة العمليات الأساسية `SurgeryTest` بنسبة 100% (`9 passed, 176 assertions`).
  * اجتياز فحص `Laravel Pint` وتنسيق كافة الملفات بنجاح.

## Session log (2026-11-16 — المرحلة 234: فك الارتباط التام لجدول العلاج عن التشخيص وتمكين التحكم المستقل به لجميع الأدوار بما فيها المدير من مصفوفة الصلاحيات Full Decoupling of Medications/Treatments Dropdown from Diagnosis & Granular Control via Tab Permissions Matrix)

### Done
- **فك الارتباط عن تشخيص الجراح ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [app/Models/SurgeryTabPermission.php](file:///C:/get/hospital-system/app/Models/SurgeryTabPermission.php))**:
  * تصحيح شرط ظهور زر جدول العلاج في الترويسة العلوية لكارت العملية من `@if(isset($visibleTabs['detail_medications']) || isset($visibleTabs['detail_diagnosis']))` إلى `@if(isset($visibleTabs['detail_medications']))` حصراً، حيث كان الارتباط بـ `detail_diagnosis` يجبر جدول العلاج على الظهور دوماً حتى عند تعيينه كمخفي `hidden`.
  * تصحيح شرط صلاحية تعديل وإضافة العلاجات `$canWriteMedications` ليعتمد حصراً على `$visibleTabs['detail_medications']['can_write']` دون وراثة صلاحية الكتابة من التشخيص.
  * إزالة التراجع الاحتياطي (Fallback) من `SurgeryTabPermission` و`SurgeryController::updateDetails` ليكون `detail_medications` مستقلاً استقلالاً تاماً عن أي قسم آخر.
- **تمكين التحكم بالصلاحيات لدور مدير النظام (Admin Role Control in Matrix)**:
  * في صفحة مصفوفة الصلاحيات ([resources/views/surgeries/settings/tabs.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/settings/tabs.blade.php)): استبدال شارة `إدخال كامل (ثابت)` القديمة بقائمة منسدلة تفاعلية `<select>` لدور `admin` تمكّن المدير من تخصيص وتجربة الصلاحيات (`مخفي`, `قراءة فقط`, `إدخال كامل`) لحسابه أيضاً.
  * في `SurgeryTabPermission::getDetailTabPermissions`: تمكين حسابات المدراء من تطبيق التخصيصات المحفوظة في جدول الصلاحيات في حال تخصيصها، مع البقاء على `ACCESS_WRITE` كوضع افتراضي عند عدم وجود تخصيص يدوي.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryGranularTabControlTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryGranularTabControlTest.php))**:
  * تحديث اختبار `test_hiding_medications_removes_treatments_dropdown` ليثبت اختفاء جدول العلاج عند إخفاء `detail_medications` منفرداً مع بقاء `detail_diagnosis` ظاهراً ومتاحاً للكتابة.
  * إضافة اختبار جديد `test_admin_can_hide_treatments_dropdown_via_custom_permission` يؤكد إمكانية تحكم حساب المدير بجدول العلاجات إخفاءً وظهوراً عبر مصفوفة الصلاحيات بنجاح.
  * اجتياز كامل اختبارات التحكم الحبيبي بنسبة 100% (`6 passed, 20 assertions`).
  * اجتياز حزمة اختبارات العمليات الأساسية `SurgeryTest` بنسبة 100% (`9 passed, 176 assertions`).
  * اجتياز اختبارات محطة الجراح المعدلة بنسبة 100% (`9 passed, 37 assertions`).
  * اجتياز فحص `Laravel Pint` وتنسيق كافة الملفات بنجاح.

## Session log (2026-11-16 — المرحلة 233: معالجة خطأ المتغير غير المعرف $combinedReadings في صفحة متابعات المقيم بمحطة الجراح وتأمين العرض المستقل Fix Undefined Variable $combinedReadings & Decouple Resident Follow-ups in Surgeon Station)

### Done
- **معالجة خطأ `$combinedReadings` وتأمينه في الكنترولر والواجهة ([app/Http/Controllers/SurgeonStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeonStationController.php), [resources/views/surgery-stations/surgeon/follow-ups.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/surgeon/follow-ups.blade.php))**:
  * تجميع قراءات العلامات الحيوية قبل وبعد العملية في `SurgeonStationController::residentFollowUps` وتمرير المتغير `$combinedReadings` مع العرض دائماً.
  * تأمين واجهة العرض بتهيئة احتياطية `$combinedReadings = $combinedReadings ?? collect();` في أعلى ملف Blade لمنع حدوث أي خطأ `Undefined variable` مستقبلاً.
  * تحديث شرط السكربت إلى `@if(!empty($combinedReadings) && $combinedReadings->count() > 0)`.
  * فك تشابك متابعات الطبيب المقيم وجعلها قسماً مستقلاً بذاته يعرض الجدول عند توفر المتابعات حتى إن لم تكن هناك محطات ما قبل/ما بعد العملية مسجلة بعد.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeonStationAccessAndWorkflowTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeonStationAccessAndWorkflowTest.php))**:
  * إضافة اختبار `test_surgeon_can_view_resident_follow_ups_even_without_resident_stations` للتحقق من فتح صفحة المتابعات بنجاح (HTTP 200) دون أخطاء حتى في غياب قراءات العلامات الحيوية.
  * اجتياز كامل حزمة اختبارات محطة الجراح بنسبة 100% (`5 passed, 22 assertions`).
  * اجتياز فحص `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-16 — المرحلة 232: تفكيك الواجهة والتحكم الذري المستقل بكل معلومة وعنصر في شاشة العمليات عبر مصفوفة الصلاحيات Granular Modular UI Controls for Surgery File via Tab Permissions Matrix)

### Done
- **تفكيك التداخلات الموروثة وإتاحة التحكم الفردي المستقل لكل معلومة ([app/Models/SurgeryTabPermission.php](file:///C:/get/hospital-system/app/Models/SurgeryTabPermission.php), [resources/views/surgeries/settings/tabs.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/settings/tabs.blade.php))**:
  * تفكيك التبويبات المركبة القديمة (`detail_team_timing`, `detail_supplies`) إلى 11 عنصراً ومفتاحاً معيارياً مستقلاً في `SurgeryTabPermission::DETAIL_TABS`:
    1. `detail_tests`: الفحوصات والتحاليل (مخبرية وأشعة).
    2. `detail_type`: نوع العملية والعمليات الإضافية.
    3. `detail_diagnosis`: تشخيص الجراح وتوصياته والخطة السريرية.
    4. `detail_medications`: الأدوية والعلاجات الموصوفة وجدول العلاج السريع.
    5. `detail_anesthesia`: تفاصيل وخطة التخدير السريرية.
    6. `detail_team`: الفريق الطبي المساعد (مساعد جراح، أطباء تخدير 1 و2، مساعد تخدير).
    7. `detail_timing_location`: صالة العمليات ومدة وتوقيت العملية.
    8. `detail_supplies`: صرف المستلزمات الطبية من مخزن العمليات التابع.
    9. `detail_devices`: الأجهزة الطبية المستخدمة في العملية.
    10. `detail_followups`: المتابعات السريرية للأطباء المقيمين.
    11. `detail_badges`: شارات حالة التشخيص والتخدير الملونة بالترويسة العلوية لكارت المريض.
  * ظهور كل عنصر كعمود مستقل بصلاحياته الثلاث (`🚫 مخفي`, `👁️ قراءة فقط`, `✍️ قراءة وإدخال`) لكل دور وظيفي في صفحة إعدادات التبويبات (`/surgery-stations/settings/tabs`).
  * الحفاظ الكامل على التوافقية العكسية (Backward Compatibility) مع التعيينات القديمة وقيم مصفوفة الصلاحيات الافتراضية عبر `resolveEffectiveTabAccess` و`getDetailTabPermissions`.
- **ربط عناصر واجهة العملية بالمفاتيح المستقلة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * **شارات الترويسة**: إحاطتها بشرط `detail_badges` مع ربط شارة التشخيص بـ `detail_diagnosis` وشارة التخدير بـ `detail_anesthesia`. تختفي فوراً وبشكل منفصل عند تحديدها كمخفية.
  * **أزرار الشريط العلوي**: إحاطة زري صالة العمليات ومدة وتوقيت العملية بشرط `detail_timing_location`. إحاطة زر وجدول العلاجات السريعة بشرط `detail_medications`.
  * **الأقسام الداخلية والأعمدة**: ربط قسم الفريق الطبي بـ `detail_team`، وربط قسم الأجهزة الطبية بـ `detail_devices`، مع جعل حقل ملاحظات التخدير يتمدد بالعرض الكامل (`col-12`) تلقائياً عند إخفاء قسم الأجهزة لتحقيق مظهر بصري متناسق.
- **تحديث منطق الحفظ ومعالجة الطلبات ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
  * تحديث `updateDetails` لدعم التبويبات الجديدة (`detail_medications`, `detail_team`, `detail_timing_location`, `detail_devices`) وفحص صلاحيات الكتابة الخاصة بكل حقل بدقة متناهية، مع دعم الطلبات المجمعة والمفردة دون أي تعارض.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryGranularTabControlTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryGranularTabControlTest.php))**:
  * بناء 5 اختبارات ميزة متخصصة تؤكد: ظهور العناصر بالوضع الافتراضي، اختفاء شارات الترويسة عند إخفاء `detail_badges`، اختفاء زري الصالة والمدة عند إخفاء `detail_timing_location`، اختفاء جدول العلاجات عند إخفاء `detail_medications`، واختفاء كارت الأجهزة الطبية عند إخفاء `detail_devices` بنسبة نجاح 100% (`5 passed, 15 assertions`).
  * اجتياز كامل حزمة اختبارات العمليات الأساسية `SurgeryTest` بنسبة 100% (`9 passed, 176 assertions`).
  * اجتياز اختبارات الألوان `SurgeryDiagnosisAndAnesthesiaStatusColorTest` وتغيير الحالة والطباعة `SurgeryStatusPostOpAndPrintReportTest` بنسبة 100%.
  * اجتياز فحص `Laravel Pint` وتنسيق كافة الملفات بنجاح.

## Session log (2026-11-16 — المرحلة 231: تمكين تغيير حالة المريض بعد الخروج من الصالة، نقل زر الحفظ الموحد بجانب طباعة التقرير، إزالة زر حفظ الفريق المشتت، وتطوير استمارة طباعة التقرير الجراحي بشعار وهوية المستشفى الرسمية Post-Op Status Transitions, Unified Top Save Button, Removal of Redundant Team Save Button, and Official Surgery Print Report)

### Done
- **تمكين تغيير حالة المريض بعد "خرج من الصالة" ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
  * إزالة التقييد السابق الذي كان يحول زر الحالة إلى شارة جامدة عند اكتمال العملية (`status === 'completed'`).
  * إبقاء القائمة المنسدلة نشطة للمستخدمين المخولين مع خيارات مرنة تتيح: (1) استئناف العملية وإعادة إدخال المريض لصالة العمليات (`surgeries.start` / Intra-Op) مع تصفير `end_time = null`، (2) إعادة المريض لقائمة الانتظار (`surgeries.return-to-waiting` / Pre-Op) مع تصفير `end_time = null`، (3) خيار إلغاء العملية عند الحاجة.
- **زر الحفظ الموحد وإزالة زر "حفظ الفريق والتوقيت" المكرر**:
  * نقل زر الحفظ الرئيسي إلى الترويسة العلوية السريعة بجانب زر "طباعة التقرير" بمظهر أخضر مميز `[ 💾 حفظ بيانات العملية ]` (`#btnUnifiedSaveSurgeryTop`).
  * حذف الزر السفلي المكرر القديم الذي كان يظهر في المنتصف بعبارة مشتتة `حفظ الفريق الطبي والتوقيت`، حيث لم يعد له داعٍ وأصبح الحفظ يتم مركزياً لكافة بيانات العملية (التشخيص، التخدير، الفريق الطبي، الموقع، والتوقيت).
- **إعادة تصميم استمارة طباعة التقرير الجراحي المعتمد ([resources/views/surgeries/print.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/print.blade.php))**:
  * إعادة بناء صفحة الطباعة بمطابقة تامة مع المعايير المعمارية الرسمية لتقارير أقسام المستشفى (الأشعة والمختبر).
  * إضافة الترويسة الرسمية ثلاثية الأقسام: اسم المستشفى بالعربية وقسم الجراحة، شعار المستشفى المعتمد Base64 والعلامة المائية المركزية، واسم المستشفى والقسم بالإنجليزية.
  * بانر وثيقة التقرير الجراحي المعتمد بكود العملية `OR-#id` وبادج الحالة الملون، شبكة بيانات المريض الرباعية، بطاقة أعضاء الفريق الجراحي والتخديري، جدول العمليات الأساسية والإضافية، صناديق التشخيص الطبي وتوصيات الجراح ووصف التقنية الجراحية، توثيق وخطة التخدير والأجهزة، جدول الأدوية المصروفة، ومربع الاعتمادات الرسمية (توقيع الجراح، توقيع طبيب التخدير، ختم صالة العمليات، وكود التحقق الإلكتروني).
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryStatusPostOpAndPrintReportTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryStatusPostOpAndPrintReportTest.php))**:
  * اجتياز كافة سيناريوهات الاختبارات الآلية بنسبة 100% (`3 passed, 34 assertions`).
  * اجتياز اختبارات الألوان `SurgeryDiagnosisAndAnesthesiaStatusColorTest` بنسبة 100%.
  * اجتياز فحص `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-16 — المرحلة 230: تمييز لوني ديناميكي لحالة تشخيص الجراح وتوثيق التخدير في واجهة تفاصيل العملية لكادر العمليات Dynamic Status Colors for Surgeon Diagnosis and Anesthesia in Surgery Show)

### Done
- **التمييز اللوني لحالة تشخيص الجراح ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * **إذا لم يُشخص بعد (`!$isDiagnosed`)**: إبراز الحالة باللون الأحمر الصريح (`bg-danger`, `border-danger`, `text-danger`) بشارة واضحة `[ ❌ التشخيص: لم يُشخص بعد ]` في الترويسة العلوية لكارت المريض، مع تنبيه أحمر عاجل `لم يتم إدخال التشخيص الطبي للعملية بعد (بانتظار توثيق الطبيب الجراح)`، وتمييز إطار حقل التشخيص بحدود حمراء واضحة وبادج `[ غير مُشخّص ]`.
  * **إذا تم التشخيص (`$isDiagnosed`)**: إبراز الحالة باللون الأخضر الصريح (`bg-success`, `border-success`, `text-success`) بشارة `[ ✔️ التشخيص: تم التشخيص ]`، وتنبيه أخضر معتمد `تم توثيق التشخيص الطبي للعملية بنجاح من قِبل الطبيب الجراح`، وحدود خضراء ناعمة.
- **التمييز اللوني المتباين لحالة التخدير بألوان مختلفة تماماً**:
  * **إذا لم يُدخل التخدير بعد (`!$isAnesthesiaDocumented`)**: إبراز الحالة باللون البرتقالي الكهرماني التحذيري (`#ea580c` / `#ffedd5`) بشارة `[ ⏳ التخدير: لم يُدخل بعد ]` في الترويسة العلوية، وتنبيه كهرماني بارز داخل قسم وخطة التخدير `لم يتم إدخال نوع وخطة التخدير بعد (بانتظار طبيب التخدير)` مع تمييز حدود حقل نوع التخدير باللون البرتقالي.
  * **إذا تم توثيق التخدير (`$isAnesthesiaDocumented`)**: إبراز الحالة باللون الأزرق النيلي / السماوي الداكن (`#0284c7` / `#e0f2fe`) بشارة `[ 💉 التخدير: تم التوثيق (نوع التخدير) ]`، وتنبيه أزرق رسمي يوضح تفاصيل التخدير المسجلة وطبيب التخدير المشرف.
- **الروابط السريعة المباشرة**: ربط الشارات العلوية بروابط تنقل سلسة تنقل المستخدم بنقرة واحدة إلى قسم التشخيص `#pane-detail_diagnosis` أو قسم التخدير `#pane-detail_anesthesia`.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryDiagnosisAndAnesthesiaStatusColorTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryDiagnosisAndAnesthesiaStatusColorTest.php))**:
  * إنشاء حزمة اختبارات متخصصة تؤكد ظهور المؤشرات والألوان الصحيحة لحالتي عدم التشخيص/التشخيص وعدم إدخال التخدير/توثيقه بنسبة 100% (`2 passed, 18 assertions`).
  * اجتياز كامل حزمة العمليات `SurgeryTest` بنسبة 100% (`9 passed, 181 assertions`).
  * اجتياز فحص `Laravel Pint` وتحديث كاش العرض بنجاح.

## Session log (2026-11-16 — المرحلة 229: ترشيق جدول محطة التخدير بحذف أعمدة الإجراءات وحالة المحطة ونوع التخدير، وجعل النقر على اسم المريض يفتح تفاصيل العملية مباشرة Streamlining Anesthesia Table to 4 Core Columns with Direct Click-Through on Patient and Surgery Name to Surgery Details)

### Done
- **ترشيق جدول محطة التخدير إلى 4 أعمدة رئيسية ([resources/views/surgery-stations/anesthesia/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/anesthesia/index.blade.php))**:
  * حذف عمود `الإجراءات` وعمود `حالة المحطة` وعمود `نوع التخدير` بالكامل لتوفير مساحة أفقية واسعة ومنع الازدحام البصري.
  * الاقتصار على الأعمدة الـ 4 الأساسية: (1) `المريض`، (2) `العملية الجراحية`، (3) `موعد العملية`، (4) `طبيب التخدير`.
  * جعل النقر على اسم المريض وصورته واسم العملية رابطاً مباشراً وسلساً يفتح تفاصيل وسجل العملية (`surgeries.show`) لتعديل كافة التفاصيل والتخدير بيسر وسرعة.
  * ترشيق شريط الفلاتر بإزالة فلتر الحالة غير المطلوب والتركيز على البحث اللحظي وفلتر الطبيب وفلتر التاريخ.
- **التحقق وتنسيق الكود**: اجتياز التحقق الآلي وتحديث الذاكرة المركزية والرفع على الفرع الرئيسي.

## Session log (2026-11-16 — المرحلة 228: تمكين حسابات التخدير من استعراض كافة العمليات بالصالة لتعيين الأطباء وإدخال التفاصيل، مع خيار فلترة الحالات غير المسندة Anesthesia Station: Display All Operating Room Surgeries for Doctor Assignment and Clinical Entry with Unassigned Status Filter)

### Done
- **عرض كافة العمليات الجراحية في محطة التخدير ([AnesthesiaStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/AnesthesiaStationController.php))**:
  * إزالة تقييد حصر العمليات بالمسندة مسبقاً، وإتاحة ظهور جميع العمليات النشطة بصالة العمليات (المسندة وغير المسندة) لتمكين كادر التخدير من تعيين الأطباء وتوثيق البيانات والتفاصيل السريرية.
  * دعم فلترة سريعة للحالات غير المسندة (`⚠️ بانتظار تعيين طبيب تخدير`) في القائمة المنسدلة للوصول الفوري إليها.
  * مزامنة فورية في `update()` لبيانات طبيب التخدير الأول والمساعد ونوع التخدير واسم مساعد التخدير مع جدول العمليات الرئيسي `surgeries` وإعادة التوجيه لمحطة التخدير.
- **تطوير واجهة محطة التخدير ([resources/views/surgery-stations/anesthesia/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/anesthesia/index.blade.php))**:
  * تمييز العمليات التي لم يُعين لها طبيب تخدير ببادج بارز `[ ⚠️ بانتظار التعيين ]` وزر مباشر باللون البرتقالي `[ ➕ تعيين طبيب وتفاصيل ]`.
  * إتاحة خيار استعراض كل العمليات أو العمليات الخاصة أو غير المعينة لكافة المستخدمين المخولين.
- **التحقق الآلي وتنسيق الكود**:
  * اجتياز اختبارات استعراض وتعيين العمليات وتحديث الموديل بنسبة 100%، واجتياز فحص `Laravel Pint`.

## Session log (2026-11-16 — المرحلة 227: تطوير محطة طبيب التخدير: شريط فلاتر متقدم، ضبط نوعي الحسابات الموحد والخاص، وتعيين المحطة كصفحة رئيسية للتخدير Anesthesia Station Complete Redesign: Real-time Filters, Dual Account Architecture for Shared OR vs Personal Anesthesiologist, and Default Home Landing Page)

### Done
- **إعادة تصميم واجهة محطة التخدير بالكامل ([resources/views/surgery-stations/anesthesia/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/anesthesia/index.blade.php))**:
  * بناء ترويسة تنفيذية داكنة حديثة مع تمييز تلقائي للحساب الموحد (`صالة العمليات - الحساب الموحد لكافة أطباء التخدير`) أو الحساب الخاص (`د. [الاسم] (حساب خاص)`).
  * شريط فلاتر ذكي: بحث لحظي سريع في المتصفح (`client-side`) مع دعم الخادم (`server-side`)، فلتر أطباء التخدير المنسدل للحساب الموحد، فلتر حالة المحطة (بانتظار المعاينة / قيد المتابعة / مكتملة ومُعتمدة)، وفلتر التاريخ مع أزرار سريعة (`اليوم` / `الكل`).
  * جدول عمليات رشيق ومفصل يوضح المريض، العملية، الموعد، طبيب التخدير، نوع التخدير ببادج ملون، حالة المحطة، وأزرار الإجراءات السريعة (فتح المحطة وتفاصيل العملية).
- **ضبط منظومة نوعي الحسابات في الكنترولر ([app/Http/Controllers/AnesthesiaStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/AnesthesiaStationController.php))**:
  * **الحساب الخاص بطبيب التخدير (Personal Doctor)**: يعرض حصراً العمليات المسندة إليه (رئيسي أو مساعد 2)، ومحمي في `authorizeAnesthesiologist` من فتح عمليات باقي الأطباء بحظر 403.
  * **حساب صالة العمليات الموحد (`حساب أطباء التخدير` / `anth@hospital.com`)**: يعرض كافة العمليات في صالة العمليات مع إمكانية الفلترة حسب أي طبيب تخدير، وصلاحية كاملة لفتح وتعديل المحطة دون أي حجب.
- **التعيين كواجهة هبوط رئيسية وشريط التنقل ([routes/web.php](file:///C:/get/hospital-system/routes/web.php), [LoginController.php](file:///C:/get/hospital-system/app/Http/Controllers/Auth/LoginController.php), [DashboardController.php](file:///C:/get/hospital-system/app/Http/Controllers/DashboardController.php), [app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * توجيه الحساب الموحد وأطباء التخدير فوراً إلى `anesthesia-station.index` عند تسجيل الدخول أو زيارة `/` أو `/dashboard`، وتخصيص رابط الرئيسية في السايدبار ليكون `محطة التخدير (الرئيسية)`.
- **التحقق الآلي وتنسيق الكود**:
  * اجتياز حزمة سيناريوهات التخدير 100%، واجتياز فحص `Laravel Pint`.

## Session log (2026-11-16 — المرحلة 226: إلغاء الكروت الإحصائية، إضافة فلاتر البحث والتواريخ وحالة المحطة، وضبط ازدواجية الحساب الموحد لصالة العمليات مقابل الحساب الخاص للجراح Surgeon Station Streamlining: Remove KPI Cards, Advanced Real-Time Filters, and Dual Account Architecture for Shared OR vs Personal Surgeon)

### Done
- **إلغاء الكروت الإحصائية الـ 4 ([resources/views/surgery-stations/surgeon/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/surgeon/index.blade.php))**:
  * حذف الكروت الإحصائية الأربعة العلوية لتخفيف التراكم الرأسي وإبراز قائمة العمليات وأدوات البحث والتصفية مباشرة.
- **شريط الفلاتر والبحث اللحظي المتقدم**:
  * إضافة حقل بحث لحظي سريع بالاسم ورقم الملف والعملية مع فلترة فورية بالمتصفح (`client-side`) وتوافق تام مع الفلترة بالخادم (`server-side`).
  * إضافة فلتر منسدل للأطباء الجراحين للحساب الموحد (`isSharedAccount`) وبادج خاص للأطباء ذوي الحسابات الفردية.
  * فلتر حالة المحطة (الكل / بانتظار المعاينة / قيد المتابعة / مكتملة) وفلتر التاريخ مع أزرار سريعة (`اليوم` / `الكل`).
- **ضبط منظومة نوعي الحسابات ([app/Http/Controllers/SurgeonStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeonStationController.php))**:
  * **الحساب الخاص بالجراح (Personal Surgeon)**: يعرض عملياته فقط ومحمي في `authorizeSurgeon` من فتح عمليات باقي الأطباء.
  * **حساب صالة العمليات الموحد (`حساب الجراحين`)**: يعرض كافة العمليات لجميع الجراحين مع إمكانية الفلترة بصلاحية كاملة لفتح وتعديل المحطة دون أي حجب.
- **التحقق الآلي وتنسيق الكود**:
  * اختبار سيناريوهات الحسابين وتأكيد الحماية وصلاحيات العرض بنسبة 100%.
  * اجتياز فحص `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-16 — المرحلة 225: جعل محطة الجراح الصفحة الرئيسية لحساب الجراحين وكافة أطباء الجراحة Surgeon Station as Default Landing and Home Page for Surgeons & Shared Account)

### Done
- **تعيين محطة الجراح كواجهة هبوط رئيسية ([routes/web.php](file:///C:/get/hospital-system/routes/web.php), [app/Http/Controllers/Auth/LoginController.php](file:///C:/get/hospital-system/app/Http/Controllers/Auth/LoginController.php), [app/Http/Controllers/DashboardController.php](file:///C:/get/hospital-system/app/Http/Controllers/DashboardController.php))**:
  * توجيه المستخدمين الذين يحملون دور `الجراح` أو `surgeon` أو الأطباء ذوي النوع `type === 'surgeon'` أو حساب `حساب الجراحين` مباشرة إلى محطة الجراح (`surgeon-station.index` على الرابط `http://127.0.0.1:8000/surgery-stations/surgeon`) فور تسجيل الدخول وعند التوجه للمسار الجذر `/` وعند طلب لوحة التحكم `/dashboard`.
- **تحديث شريط التنقل الجانبي ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * تخصيص رابط الرئيسية العلوي في السايدبار للجراحين وحساب الجراحين ليوجه مباشرة إلى `محطة الجراح (الرئيسية)` مع إبقاء المسارات السابقة للأطباء الاستشاريين وموظفي الضمان دون أي تعارض.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeonStationAccessAndWorkflowTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeonStationAccessAndWorkflowTest.php))**:
  * إضافة اختبارات متخصصة تؤكد التوجيه المباشر لمحطة الجراح للجراحين ولحساب الجراحين واختبار حالات الاستشارية العادية.
  * اجتياز فحص `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-16 — المرحلة 224: ترشيق قوائم الفريق الطبي، تصغير حجم الخط، وإلغاء شارات النوع المكررة بجانب الأسماء Compact Dropdown Typography & Role Badges Cleanup in Medical Team Selectors)

### Done
- **ترشيق القوائم المنسدلة للفريق الطبي ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * تصغير حجم الخط لجميع القوائم المنسدلة الذكية (أطباء التخدير ومساعد الجراح ومساعد التخدير) ليصبح بنمط مدمج ومتناسق (`font-size: 0.8rem` وحشوة أسطر رشيقة `py-1.5 px-2.5`).
  * حذف شارات النوع المكررة التي كانت تظهر بجانب كل اسم داخل القوائم (`مساعد جراح`، `مساعد تخدير`، `طبيب تخدير`) وإزالة شارة `طبيب` من ترويسة الحقل لكون اسم القائمة كافياً وواضحاً تماماً.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
  * اجتياز كامل الاختبارات بنجاح 100% (`16 passed, 205 assertions`).
  * اجتياز فحص `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-16 — المرحلة 223: إلغاء أزرار كليشة التخدير وإفاقة مستقرة وجرد وعهدة العمليات في تفاصيل العملية Remove Anesthesia Template, Stable Recovery, and OR Store Inventory Buttons in Surgery Show)

### Done
- **ترشيق واجهة تفاصيل العملية ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * حذف أزرار الكبسولة `[ 🪄 كليشة التخدير ]` و `[ إفاقة مستقرة ]` من أعلى حقل ملاحظات طبيب التخدير مع بقاء آلية التعبئة التلقائية عند اختيار نوع التخدير (`عام`، `نصفي`، `مناطقي`، `موضعي`) سارية بالكامل.
  * حذف زر `[ 🏭 جرد وعهدة العمليات ]` من شريط أدوات ترويسة بطاقة المستلزمات والأجهزة.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
  * اجتياز كافة اختبارات العمليات بنجاح 100% (`9 passed, 181 assertions`).
  * اجتياز فحص `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-16 — المرحلة 222: إلغاء زر طلب تعزيز المواد لمخزن العمليات في شاشة تفاصيل العملية عند فراغ رصيد المخزن Remove OR Store Replenishment Request Button in Surgery Show on Empty Stock)

### Done
- **ترشيق حالة المخزن الفارغ ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * إزالة زر `طلب تعزيز لمخزن العمليات` ورابط التحويل المخزني `stock-transfers.create` عند فراغ رصيد مخزن العمليات التابع.
  * الاكتفاء بعرض تنبيه نظيف وموجز يفيد بعدم توفر رصيد قابل للصرف حالياً دون نقل مستخدم شاشة العمليات إلى أقسام أخرى.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
  * اجتياز كافة اختبارات العمليات بنجاح 100% (`9 passed, 181 assertions`).
  * اجتياز فحص `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 221: جدول صرف المستلزمات المتعدد المباشر من مخزن العمليات التابع بزر تفاعلي مطابق للمختبر والأشعة Multi-Item Store Dispense Grid with Auto-Affiliated OR Store and Reveal Toggle)

### Done
- **جدول الصرف المتعدد للمستلزمات دفعة واحدة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
  * تحويل صرف المواد والمستلزمات من اختيار فردي إلى جدول تفاعلي شامل (`#detailSuppliesForm`) يتيح تحديد مربعات الاختيار (`checkbox`) لعدة مواد مع تحديد كمياتها وملاحظاتها وصرفها دفعة واحدة للمريض بنقرة زر واحدة في حركة مخزنية وسجل سريري موحد (`DB::transaction`).
  * دعم البحث اللحظي وفلاتر التصنيف (الكل، مستلزمات، أدوية ومحاليل، المحدد فقط) وتحديد الكل وتحديث عداد المواد النشط لحظياً.
- **سحب تلقائي من مخزن العمليات التابع وإلغاء القوائم المنسدلة للمخازن**:
  * ضبط السحب التلقائي والمباشر من المخزن التابع للعملية (`مخزن عمليات رئيسي` أو مخزن العمليات الفرعي) تلقائياً دون إظهار أي قائمة منسدلة لاختيار المخازن، مع إبراز اسم المخزن التابع كبادج رسمي واضح.
- **زر إظهار وإخفاء تفاعلي مطابق للمختبر والأشعة**:
  * إدراج زر كبسولي أنيق في ترويسة بطاقة المستلزمات (`[ 🛒 صرف مستلزمات من المخزن: X مادة ▾ ]`) يفتح ويغلق جدول الصرف عبر `collapse` لترتيب الصفحة وتجنب السكرول الطويل، مع إبقاء سجل المواد المصروفة سابقاً واضحاً ومستقلاً دائماً.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
  * اجتياز كامل الاختبارات بنجاح 100% (`16 passed, 205 assertions`).
  * اجتياز فحص `Laravel Pint` وتحديث `view:cache` بدون أي أخطاء.

## Session log (2026-11-15 — المرحلة 220: إعادة هيكلة واجهة جدول الدوام بالكامل، دعم دوام الوجبتين بخلية مزدوجة، وترشيق شريط الإحصاءات اليومية وشريط الأدوات الموحد HR Roster Complete Redesign with Split-Shift Dual Slots, Streamlined Daily Coverage Badges & Unified Command Bar)

### Done
- **معالجة دوام الوجبتين في الباك إند ([app/Http/Controllers/HR/HrScheduleController.php](file:///c:/get/hospital-system/app/Http/Controllers/HR/HrScheduleController.php))**:
  * **دعم استرجاع وحفظ الشفتات المتعددة لليوم الواحد:** تعديل استعلامات `index` و `store` لدعم تخزين واسترجاع شفتي الوجبتين (`slot1` صباحي و `slot2` مسائي) لنفس الموظف ونفس اليوم دون أن يلغي أحدهما الآخر، بالاستفادة من الفهرس الفريد `['hr_employee_id', 'shift_date', 'hr_shift_id']`.
  * **الاحتساب المزدوج الدقيق في التغطية اليومية (`dailyCoverage`):** احتساب كلا الشفتين لموظف الوجبتين ضمن عدد الكوادر اليومية (مثلاً: احتسابه في الصباحي واحتسابه في المسائي) لتعكس الإحصاءات الواقع التشغيلي الحقيقي للمستشفى.
  * **التعبئة التلقائية للوجبتين (`autoFillDefaults`):** أتمتة تنزيل الشفتين (الصباحي والمسائي) لموظف الوجبتين بنقرة واحدة، وتطبيق الـ Off في أيام راحته.
  * **التحديث اللحظي عبر AJAX (`updateCell`):** دعم تمرير السلوت (`slot1` أو `slot2`) مع القيمة الأخرى لمنع الكتابة فوق الشفت الثاني أثناء الختم السريع.
- **إعادة تصميم واجهة الجدول وترشيقها بالكامل ([resources/views/hr/schedules/index.blade.php](file:///c:/get/hospital-system/resources/views/hr/schedules/index.blade.php))**:
  * **القضاء على التراكم الرأسي وتوحيد الأشرطة (Unified Command Toolbar):** دمج الكروت الأربعة المتراكمة في شريط تحكم فائق الرشاقة (سُمك ~38px) يضم التنقل الأسبوعي/الشهري، أزرار الإجراءات، البحث اللحظي، فلاتر نوع الدوام، وأداة الختم السريع مباشرة.
  * **شريط مؤشرات الأداء السريع (KPI Summary Strip):** شريط علوي خفيف يوضح إجمالي الشفتات المجدولة، عدد موظفي الوجبتين، أيام الراحة، والكوادر النشطة.
  * **الخلية المزدوجة الذكية لموظفي الوجبتين (Split-Shift Cell):** عرض خانتين أنيقتين متطابقتين للوجبة الأولى `(1)` والوجبة الثانية `(2)` بألوانهما وتوقيتاتهما المستقلة، مع المحافظة على الخلية المدمجة الفردية لباقي الكوادر.
  * **شريط الإحصاءات اليومية الأفقي فائق الرشاقة (Streamlined Coverage Bar):** تحويل الإحصاءات السفلية من 5 أسطر عمودية طويلة إلى شريط أفقي أنيق من الشارات الملونة المصغرة (`☀️ 5  ⛅ 3  🌙 2  Off: 1`) بنصف الارتفاع وتوفير مساحة شاشة واسعة.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/HrScheduleTest.php](file:///c:/get/hospital-system/tests/Feature/HrScheduleTest.php))**:
  * إضافة اختبار جديد لموظف الوجبتين وتأكيد حفظ ونزول الشفتين معاً وظهورهما في الواجهة بنجاح (`6 passed, 34 assertions`).
  * اجتياز اختبارات الموارد البشرية بالكامل (`11 passed, 90 assertions`) وفحص `Laravel Pint`.

## Session log (2026-11-15 — المرحلة 219: نقل جدول العلاج للمنسدلة العلوية، إسناد الأجهزة الطبية لكارت التخدير، ووضع ملاحظات التخدير أسفل نوع التخدير Treatments in Top Dropdown, Medical Devices Next to Anesthesia, and Anesthesia Notes Below Type)

### Done
- **نقل جدول العلاجات لقائمة منسدلة علوية وإلغاء التكرار في كارت التشخيص ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * إضافة زر منسدل تفاعلي لجدول العلاج في شريط الإجراءات العلوي بجانب صالة ومدة العملية: `[ 💊 جدول العلاج: X علاج ▾ ]`.
  * إتاحة إدارة الأدوية وإضافة وحذف العلاجات وكليشة البروتوكول الجراحي الشائع داخل المنسدلة العلوية مع زر حفظ مستقل ومزامنة تلقائية مع زر الحفظ الموحد.
  * حذف جدول وبنر العلاج المكرر من كارت التشخيص ليصبح مخصصاً حصراً للتشخيص والخطة السريرية وتوصيات وملاحظات الجراح دون أي تكرار.
- **إعادة هيكلة كارت التخدير: الملاحظات أسفل نوع التخدير ومكانها الأجهزة الطبية المستخدمة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
  * وضع حقل وملاحظات وتفاصيل طبيب التخدير والكليشات المعتمدة أسفل حقل نوع التخدير رأسياً في العمود الأيمن.
  * تخصيص العمود الأيسر لكارت الأجهزة الطبية المستخدمة في العملية (عرض الأجهزة المرتبطة، زر إضافة جهاز طبي، بحث وتأكيد فوري، وزر الحذف السريع).
  * توسيع صلاحيات إضافة وإزالة الأجهزة في `SurgeryController::addDevice` و `removeDevice` لتشمل التخدير والعمليات مع الحفاظ التلقائي على التبويب الحالي عبر `redirect()->back()`.
  * توسيع جدول صرف مخزن العمليات في تبويب المستلزمات ليكون بعرض الشاشة الكامل (`col-xl-12`).
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
  * اجتياز كافة الاختبارات بنجاح 100% (`16 passed, 205 assertions`).
  * اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 218: تطوير وتحديث جدول الدوام والخفارات بنظام الجدولة الذكي، أداة الختم السريع، كشف التغطية والجاهزية، والتعبئة التلقائية للدوام الثابت، والطباعة الرسمية HR Smart Hospital Roster Upgrade with Quick Shift Stamp, Daily Coverage Radar, One-Click Auto-Fill & Official Landscape Print)

### Done
- **تطوير المعالجة والمتحكم ([app/Http/Controllers/HR/HrScheduleController.php](file:///c:/get/hospital-system/app/Http/Controllers/HR/HrScheduleController.php), [routes/web.php](file:///c:/get/hospital-system/routes/web.php))**:
  * **دعم التنقل السريع وأنماط العرض:** إضافة محددات `prevDate` و `nextDate` و `currentPeriodDate` للتنقل اللحظي بين الأسابيع والأشهر بنقرة واحدة، ودعم نمطي العرض: `week` (أسبوعي 7 أيام) و `month` (شهري كامل لجدولة الأطباء والخفارات على مدار الشهر).
  * **محرك التعبئة التلقائية للدوام الثابت (`autoFillDefaults`):** أتمتة تعبئة جدول الفترة بنقرة واحدة لجميع الموظفين بناءً على شفتاتهم الدائمة وأيام استراحتهم المسجلة في إضبارتهم مع خيار استبدال الشفتات أو تعبئة الفراغات فقط.
  * **كشف التغطية والجاهزية اليومية (`dailyCoverage`):** احتساب أوتوماتيكي لعدد الكوادر المداومة في كل شفت (صباحي، مسائي، ليلي/خفارة، راحة Off) لكل يوم.
  * **تحديث الخلية الفوري عبر AJAX (`updateCell`):** دعم الحفظ التلقائي الصامت لأي تغيير في أي خلية دون الحاجة لإعادة تحميل الصفحة.
  * **تفريغ وإعادة ضبط الفترة (`clearRange`):** إمكانية مسح شفتات الفترة المحددة لقسم معين لإعادة بنائها من الصفر.
- **ترقية النموذج وقاعدة البيانات ([app/Models/HrSchedule.php](file:///c:/get/hospital-system/app/Models/HrSchedule.php))**:
  * إضافة mutator `setShiftDateAttribute` لضمان تخزين التاريخ بصيغة قياسية `Y-m-d` والقضاء على أي تعارض بين MySQL و SQLite.
- **إعادة تصميم واجهة الجدول وتطوير تجربة المستخدم ([resources/views/hr/schedules/index.blade.php](file:///c:/get/hospital-system/resources/views/hr/schedules/index.blade.php))**:
  * **أداة الختم السريع (Shift Stamp Palette):** شريط أدوات علوي يتيح اختيار الشفت (صباحي / مسائي / ليلي / راحة / مسح) والنقر المباشر على أي خلية لتلوينها وتعيينها فوراً مع خيار الحفظ التلقائي (AJAX Switch).
  * **البحث والتصفية اللحظية (Client-Side Live Filter):** شريط بحث سريع بالاسم أو الوظيفة، وأزرار تصفية فورية (الكل / الدوام الثابت / الخفارات المتناوبة / الوجبتين) مع عداد الكوادر الظاهرة.
  * **شريط إجراءات الصف السريعة (Row Fill Menu):** قائمة منسدلة لكل موظف لتعبئة أسبوعه كاملاً بشفته الدائم أو أي شفت آخر بنقرة واحدة.
  * **شريط إحصاء التغطية اليومية السفلي (Daily Coverage Summary Footer):** صف مثبت أسفل الجدول يعرض إجمالي الكادر في كل شفت لكل يوم، وتحديث تلقائي لحظي عند أي تعديل.
  * **شيت الطباعة الرسمي المعتمد (`@media print`):** تحويل تلقائي للجدول إلى شيت رسمي أفقي (A4 Landscape) مع ترويسة المستشفى وتوقيع مسؤول القسم ومدقق الـ HR ومدير المستشفى.
  * إصلاح الخطأ البرمجي في وسم الـ `<script>` المزدوج.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/HrScheduleTest.php](file:///c:/get/hospital-system/tests/Feature/HrScheduleTest.php))**:
  * إنشاء حزمة اختبارات شاملة تغطي كافة العمليات والمسارات الجديدة (العرض الأسبوعي والشهري، التعبئة التلقائية، التحديث الفوري، التفريغ، وتبادل الخفارات) واجتيازها بنجاح 100% (`5 passed, 25 assertions`).
  * اجتياز اختبارات الموارد البشرية العامة بالكامل بنجاح 100% (`11 passed, 90 assertions`).
  * اجتياز فحص `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 217: إعادة هيكلة وتصميم إضبارة الموظف بنظام التبويبات التفاعلية الذكية وتثبيت الشريط الجانبي والقضاء على السكرول الطويل HR Employee Dossier Redesign with Interactive Nav Tabs, Sticky Sidebar & Zero-Scroll Layout)

### Done
- **إعادة هيكلة واجهة إضبارة الموظف ([resources/views/hr/employees/show.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/show.blade.php))**:
  * القضاء على السكرول الرأسي اللانهائي واستبدال التراكم العمودي بـ 5 تبويبات تفاعلية مدمجة وأنيقة (`nav-pills`) تدعم شارات الأرقام الحية (`Badges`):
    1. `الملف الوظيفي`: البيانات الشخصية، تفاصيل التعاقد، نظام الدوام والشفتات وأيام الاستراحة، التراخيص الطبية، والملاحظات الإدارية.
    2. `الراتب والمالية`: هيكلة الراتب وطريقة الدفع، المخصصات الثابتة، والسلف والأقساط.
    3. `الدوام والحضور`: بطاقة شفت العمل المعتمد ورابط جدول الخفارات، سجل الغيابات والتأخيرات، وسجل العمل الإضافي.
    4. `العقوبات والمكافآت`: سجل العقوبات والإنذارات والمكافآت وتأثيرها المالي والترحيل.
    5. `المستمسكات والأرشيف`: جدول الوثائق والمستمسكات الرسمية وإجراءات التحميل والمعاينة والحذف والرفع.
  * تثبيت البطاقة الجانبية للموظف (`sticky-employee-sidebar`) لتبقى هوية الموظف وحساب النظام ظاهرة دائماً أثناء التمرير في أي تبويب.
  * دعم الحفظ الذكي للتبويب النشط عبر الـ URL Hash والـ localStorage لضمان عدم ضياع التبويب بعد إرسال النماذج والمودالات أو إعادة تحميل الصفحة.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/HREmployeeTest.php](file:///c:/get/hospital-system/tests/Feature/HREmployeeTest.php))**:
  * تحديث حزمة الاختبارات وتأكيد وجود عناصر وتبويبات الواجهة الجديدة (`tab-profile`, `tab-payroll`, `tab-attendance`, `tab-actions`, `tab-documents`).
  * اجتياز كامل اختبارات الموارد البشرية بنجاح 100% (`11 passed, 90 assertions`).
  * اجتياز فحص `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 216: تثبيت نظام الدوام والشفتات الدائمة وعطلات الاستراحة الأسبوعية ودعم الوجبتين لجميع الموظفين HR Permanent Work Schedules, Shift Customization, Split-Shifts & Dynamic Weekly Off-Days)

### Done
- **بنية قاعدة البيانات والنموذج ([database/migrations/2026_11_15_200000_add_schedule_and_shift_fields_to_hr_employees_table.php](file:///c:/get/hospital-system/database/migrations/2026_11_15_200000_add_schedule_and_shift_fields_to_hr_employees_table.php), [app/Models/HrEmployee.php](file:///c:/get/hospital-system/app/Models/HrEmployee.php))**:
  * إضافة حقول الدوام في `hr_employees`: نوع الجدولة `schedule_type` (ثابت fixed / خفارات متناوبة rotating)، الشفت الدائم `default_shift_id`، مفتاح دوام الوجبتين `is_split_shift`، الشفت المسائي الثاني `second_shift_id`، ساعات العمل المخصصة `custom_start_time` و `custom_end_time`، ومصفوفة أيام الاستراحة `weekly_off_days`.
  * تعريف علاقات `defaultShift()` و `secondShift()` و `schedules()` ومساعدات `getWeeklyOffDaysArabicAttribute()` و `isOffOn($date)`.
- **المعالجة والتحكم ([app/Http/Controllers/HR/HrEmployeeController.php](file:///c:/get/hospital-system/app/Http/Controllers/HR/HrEmployeeController.php), [app/Http/Controllers/HR/HrScheduleController.php](file:///c:/get/hospital-system/app/Http/Controllers/HR/HrScheduleController.php))**:
  * تمرير الشفتات النشطة لشاشات الإضافة والتعديل (`create`, `edit`).
  * معالجة وتحقق وحفظ إعدادات الدوام والعطلات في دالتي `store` و `update`.
  * دعم الجلب الذكي للموظفين الثابتين ومطابقة أيام استراحتهم في جدول الدوام الأسبوعي/الشهري (`/hr/schedules`).
- **الواجهات الأمامية ([resources/views/hr/employees/create.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/create.blade.php), [resources/views/hr/employees/edit.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/edit.blade.php), [resources/views/hr/employees/show.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/show.blade.php), [resources/views/hr/schedules/index.blade.php](file:///c:/get/hospital-system/resources/views/hr/schedules/index.blade.php), [resources/views/layouts/app.blade.php](file:///c:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * إضافة قسم "3. نظام الدوام والشفتات وأيام الاستراحة" في شاشتي إنشاء وتعديل الموظف مع تبديل لحظي للوجبتين، وتحديد أوقات مخصصة بدقة (مثل 8-4 أو 9-3)، وشبكة اختيار أيام العطلات الأسبوعية (الجمعة، السبت، الخميس، إلخ).
  * إضافة بطاقة نظام الدوام والشفتات وأيام الاستراحة في إضبارة وتفاصيل الموظف (`show.blade.php`).
  * إدراج رابط مباشر لإدارة الأقسام والعيادات (`departments.admin`) تحت قسم الموارد البشرية بالقائمة الجانبية لتسهيل الوصول والتحكم بالأقسام.
- **التحقق الآلي**:
  * إضافة وتوسيع اختبار الميزة في [`tests/Feature/HREmployeeTest.php`](file:///c:/get/hospital-system/tests/Feature/HREmployeeTest.php) واجتياز كافة الاختبارات بنسبة 100% (`11 passed, 85 assertions`).
  * اجتياز فحص `Laravel Pint` بالكامل دون أي أخطاء.


## Session log (2026-11-15 — المرحلة 215: قصر قائمة اختيار الصالة في ملف العملية على صالات العمليات الجراحية فقط واستبعاد كافة الأقسام والمخازن الأخرى Restrict Surgery Show Hall Dropdown Exclusively to Operating Theaters & Exclude General Wards and Non-OR Departments)

### Done
- **قصر قائمة الصالات في ملف العملية على صالات العمليات الجراحية فقط ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
  * تحديث استعلام جلب الصالات `$locations` في `SurgeryController::show`:
    * فلترة المواقع لتشمل فقط صالات العمليات الجراحية (`عمليات`, `صالة`, `operation`, `theater`).
    * استبعاد المخازن العامة والفرعية غير المخصصة كصالات جراحية (`مخزن`, `store`).
    * استبعاد كافة الأقسام العامة (الاستشارية، المختبر، الطوارئ، الرسبشن، الحسابات، الإدارة، الفندقة، الصيدلة، الخدج، ردهات الطوابق، وغيرها من الأقسام غير الجراحية).
    * الحفاظ التلقائي على صالة العملية الحالية المحددة للمريض عبر `when($surgery->location_id, fn ($q) => $q->orWhere('id', $surgery->location_id))`.
- **تحسين نصوص وتسميات قائمة الصالة في الواجهة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * تغيير النص من `الصالة/الردهة:` إلى `صالة العمليات:` في الزر الرئيسي العلوي.
  * تحديث عنوان القائمة المنسدلة إلى `تحديد صالة العمليات`.
  * تحديث مسمى الحقل وخيار القائمة الافتراضي إلى `-- اختر صالة العمليات --`.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
  * اجتياز كافة اختبارات العمليات الجراحية ومساعدي الجراحين بنجاح 100% (`16 passed, 205 assertions`).

## Session log (2026-11-15 — المرحلة 215: إخفاء زر طباعة الإضبارة من شاشة الموارد البشرية Hiding Dossier Print Button from HR Employee Show View)

### Done
- **إخفاء زر طباعة الإضبارة ([resources/views/hr/employees/show.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/show.blade.php))**:
  * حذف زر `<button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i> طباعة الإضبارة</button>` من شريط التحكم العلوي لإضبارة الموظف في الموارد البشرية.
  * الحفاظ على باقي الأزرار التشغيلية (طباعة الباجة ID Card، إضافة مستمسك رسمي، تعديل البيانات، وجدول الخفارات).
- **التحقق الآلي وتنسيق الكود ([tests/Feature/HREmployeeTest.php](file:///c:/get/hospital-system/tests/Feature/HREmployeeTest.php))**:
  * تحديث الاختبارات الآلية وإضافة فحص تأكيد عدم ظهور الزر في شاشة تفاصيل الموظف (`assertDontSee('طباعة الإضبارة')`).
  * اجتياز كامل حزمة اختبارات الموارد البشرية بنجاح 100% (`11 passed, 85 assertions`).
  * اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.
  * اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 214: الربط التلقائي لكليشات ونماذج التخدير المعتمدة عند اختيار نوع التخدير Automatic Anesthesia Templates Binding on Type Selection)

### Done
- **تضمين كليشات التخدير المعتمدة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * ربط مباشر بين حقل نوع التخدير وملاحظات طبيب التخدير (`#anesthesia_notes`):
    * **تخدير عام:** إدراج الكليشة المعتمدة تلقائياً:
      `prmedication given`
      `generalanesthesia given`
      `operation passesd smoothly without complication`
      `patient recoverd well`
    * **تخدير نصفي:** إدراج الكليشة المعتمدة تلقائياً:
      `2IV canulae putted`
      `spinal aneshesia given`
      `patient recovered well`
    * **تخدير مناطقي:** إدراج الكليشة المعتمدة تلقائياً:
      `2IV canulea putted`
      `regional anesthesia given`
      `patient recovered well`
    * **تخدير موضعي:** إدراج الكليشة المعتمدة تلقائياً:
      `يتم كتب التقرير من قبل الطبيب الجراح`
  * التفعيل التلقائي عند النقر على أي من الأزرار السريعة (`عام`، `نصفي`، `مناطقي`، `موضعي`) أو عند الاختيار من القائمة المنسدلة / الكتابة.
  * إضافة زر إدراج كليشة سريع `[ 📋 كليشة التخدير ]` وتوسيع ارتفاع حقل الملاحظات إلى `rows="5"` لعرض الأسطر بوضوح دون تمرير.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
  * اجتياز كافة الاختبارات بنجاح 100% (`16 passed, 205 assertions`).
  * اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 213: توحيد تصميم طبيب التخدير 1 و2 بقوائم بحث وإكمال تلقائي ذكية متطابقة مع مساعد الجراح والتخدير Unified Searchable Autocomplete Dropdowns for Anesthesiologist 1 & 2 Matching Assistants)

### Done
- **توحيد تصميم وهيكل طبيب التخدير 1 و2 ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * استبدال وسوم `<select>` التقليدية بمكون القائمة المنسدلة الذكية التفاعلية متطابق 100% مع مساعد الجراح ومساعد التخدير:
    * حقل نصي ذكي للبحث والإكمال التلقائي الفوري (`#team_anesthesiologist_name` و `#team_anesthesiologist_2_name`).
    * زر مسح الاختيار السريع (`×`) يظهر عند وجود اختيار ويختفي عند الفراغ.
    * زر سهم القائمة المنسدلة (`▾`) لإظهار كامل قائمة أطباء التخدير بنقرة واحدة.
    * قائمة عائمة منسدلة ذكية (`#anesthesiaDoctors1Menu` و `#anesthesiaDoctors2Menu`) مع فلترة حية حسب اسم الطبيب وتخصصه وشارات التخصص الملونة.
  * الحفاظ الكامل على حقول الـ ID المخفية (`#team_anesthesiologist_id` و `#team_anesthesiologist_2_id`) لضمان صحة الإرسال والتكامل مع الباك إند والاختبارات الآلية.
  * دعم الإكمال الذكي عند كتابة الاسم والضغط على Enter أو التبديل (`change` event) لمطابقة الطبيب آلياً وحفظ معرّفه.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php), [tests/Feature/SurgeryWaitingListFlightBoardTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryWaitingListFlightBoardTest.php))**:
  * اجتياز كافة الاختبارات بنجاح 100% (`25 passed, 237 assertions`).
  * اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 212: توحيد مظهر وتنسيق قوائم الفريق الطبي الـ 5 لتطابق مساعد الجراح، وقفل جراح العملية محدد مسبقاً من الاستعلامات Unified 5-Slot Medical Team Design Matching Surgical Assistant & Locked Pre-Determined Reception Surgeon)

### Done
- **توحيد تصميم وهيكل الحقول الـ 5 للفريق الطبي ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * **قفل طبيب الجراح (محمي ومحدد من الاستعلامات):** إلغاء القائمة القابلة للتغيير، وتثبيت اسم الجراح في حقل `input-group` أنيق ومقفل للقراءة فقط (`readonly`) مع شارة قفل `الاستعلامات` منعاً للتعديل غير المصرح به على جراح العملية المعتمد.
  * **توحيد المظهر والارتفاع لجميع الحقول الخمسة:**
    * جميع الأعمدة الخمسة تستخدم كلاس `col-lg` بنسبة متساوية 20% لكل عنصر على الشاشات العريضة لتصطف في صف أفقي واحد منتظم.
    * ترويسة علوية موحدة الارتفاع (`min-height: 22px`) لكل حقل تشتمل على الأيقونة والعنوان والشارة/الرابط.
    * استخدام `input-group input-group-sm` متطابق في الارتفاع والأيقونة الأمامية والأطراف المنحنية لكافة القوائم (طبيب الجراح، مساعد جراح، طبيب التخدير 1، طبيب التخدير 2، مساعد التخدير).
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
  * اجتياز كافة الاختبارات بنجاح 100% (`16 passed, 205 assertions`).
  * اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 211: الفريق الطبي أعلى التخدير بـ 5 قوائم منسدلة أفقية بصف واحد مع دعم مساعدي التخدير معمارياً في جدول المساعدين والعمليات Medical Team Above Anesthesia with 5 Horizontal Dropdowns in 1 Row & Architecture Support for Anesthesia Assistants)

### Done
- **توسيع البنية المعمارية للمساعدين والعمليات ([database/migrations/2026_11_15_143000_add_type_to_surgical_assistants_and_anesthesia_assistant_to_surgeries.php](file:///C:/get/hospital-system/database/migrations/2026_11_15_143000_add_type_to_surgical_assistants_and_anesthesia_assistant_to_surgeries.php), [app/Models/SurgicalAssistant.php](file:///C:/get/hospital-system/app/Models/SurgicalAssistant.php), [app/Models/Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php), [app/Models/AnesthesiaStation.php](file:///C:/get/hospital-system/app/Models/AnesthesiaStation.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [app/Http/Controllers/SurgicalAssistantController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgicalAssistantController.php))**:
  * إضافة عمود `type` (`surgical` / `anesthesia`) إلى جدول `surgical_assistants` مع الفهارس والـ scopes (`scopeSurgical()` و `scopeAnesthesia()`).
  * إضافة عمود `anesthesia_assistant_name` إلى جدولي `surgeries` و `anesthesia_stations` ودعمه في مصفوفة الصلاحيات والتحديث اللحظي الموحد عبر `SurgeryController::updateDetails`.
  * ترقية شاشة إدارة المساعدين (`surgical-assistants/index.blade.php`) لتشمل فلترة النوع وتحديد نوع المساعد عند الإضافة والتعديل (`مساعد جراح` / `مساعد تخدير`).
- **صف أفقي واحد من 5 قوائم منسدلة للفريق الطبي أعلى التخدير ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * نقل الفريق الطبي بالكامل ليكون في **أعلى الكارت الموحد** في صف شبكي أفقي واحد متناسق:
    1. `طبيب الجراح`: قائمة منسدلة بالأطباء والجراحين مع تحديد جراح العملية.
    2. `مساعد جراح`: قائمة إكمال تلقائي ذكية مرتبطة بدليل مساعدي الجراحين.
    3. `طبيب التخدير 1`: قائمة منسدلة بأطباء التخدير.
    4. `طبيب التخدير 2`: قائمة منسدلة بأطباء التخدير (تصحيح المسمى بدقة).
    5. `مساعد التخدير`: قائمة منسدلة وإكمال تلقائي ذكي لمساعدي التخدير مع رابط مباشر للدليل.
  * وضع قسم تفاصيل وخطة التخدير في **أسفل الفريق الطبي** بعد فاصل أفقي، مشتملاً على نوع التخدير وملاحظات الإفاقة.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php), [tests/Feature/SurgeryWaitingListFlightBoardTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryWaitingListFlightBoardTest.php))**:
  * اجتياز اختبارات مساعدي الجراحين بنجاح 100% (`7 passed, 24 assertions`).
  * اجتياز اختبارات ملف وتفاصيل العملية بنجاح 100% (`9 passed, 181 assertions`).
  * اجتياز اختبارات شاشة انتظار العمليات بنجاح 100% (`9 passed, 32 assertions`).
  * اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 210: دمج الفريق الطبي وتفاصيل التخدير في كارت موحد واحد جنباً إلى جنب وترتيب أعضاء الفريق رأسياً دون كروت منفصلة Single Unified Card Container for Medical Team & Anesthesia Side-by-Side with Clean Vertical Team List & No Nested Member Cards)

### Done
- **دمج الفريق الطبي وتفاصيل التخدير في كارت واحد موحد ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * إلغاء الكروت المنفصلة للفريق الطبي وللتخدير ودمجهما داخل بطاقة سريرية واحدة موحدة (`card clin-tab-card`).
  * ترويسة بطاقة موحدة: `الفريق الطبي المشارك وخطة التخدير` مع شارة حالة محطة التخدير وزر `اعتماد وإتمام محطة التخدير`.
  * تقسيم داخلي شبكي متجاور في نفس الصف (`row g-3 h-100`): عمود اليمين للفريق الطبي (`col-md-5 border-start`) وعمود اليسار لتفاصيل وخطة التخدير (`col-md-7`).
  * ترتيب أعضاء الفريق الطبي رأسياً (واحد أسفل الآخر) بانسيابية تامة:
    1. الجراح الرئيسي: د. فلان (عرض خفيف بدون كارت منفصل).
    2. مساعد الجراح: مع رابط `دليل المساعدين`، وقائمة الإكمال التلقائي الذكية `#surgical_assistant_name` و`#surgicalAssistantsMenu`.
    3. طبيب التخدير الأول: قائمة منسدلة `#team_anesthesiologist_id`.
    4. طبيب التخدير المساعد: قائمة منسدلة `#team_anesthesiologist_2_id`.
  * حذف الإطارات والكروت المتداخلة المنفصلة (`clin-field-card`) نهائياً حول كل عضو لتوفير مظهر مدمج ومنظم ونظيف.
  * استقلال النماذج البرمجية بالكامل (`detailTeamTimingForm` و `detailAnesthesiaForm`) دون أي تداخل في وسوم `<form>`.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php), [tests/Feature/SurgeryWaitingListFlightBoardTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryWaitingListFlightBoardTest.php))**:
  * اجتياز اختبارات مساعدي الجراحين بنجاح 100% (`7 passed, 24 assertions`).
  * اجتياز اختبارات ملف وتفاصيل العملية بنجاح 100% (`9 passed, 181 assertions`).
  * اجتياز اختبارات شاشة انتظار العمليات بنجاح 100% (`9 passed, 32 assertions`).
  * اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 209: نقل صالة العمليات ومدة العملية كرقم وحالة المريض للشريط العلوي، توحيد أزرار الحفظ في زر واحد، ودمج الفريق الطبي والتخدير في العمود الأيسر Top Action Bar for Location, Duration & Status, Unified Save Button & Merged Team & Anesthesia Left Column)

### Done
- **نقل صالة العمليات ومدة العملية (رقم بالدقائق) وحالة المريض للشريط العلوي ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * ترتيب أزرار الإجراءات السريعة العلوية:
    1. `[ 🧪 التحاليل المخبرية ]`
    2. `[ 🩻 الأشعة والتصوير الطبي ]`
    3. `[ 🔪 نوع العملية والعمليات الإضافية ]`
    4. `[ ⏱️ حالة المريض بالصالة (Pre-Op / Intra-Op) ▾ ]`
    5. `[ 📍 صالة العمليات / الردهة: (اسم الصالة) ▾ ]` (منقولة قبل مدة العملية مباشرة بقائمة منسدلة تفاعلية)
    6. `[ ⏱️ مدة العملية: X دقيقة ▾ ]` (رقم بالدقائق، أزرار سريعة بنقرة واحدة `30د-180د`، ومساعد تحويل الساعات)
    7. `[ 💾 حفظ بيانات العملية ]` (زر الحفظ الموحد الرئيسي بجانب مدة العملية)
- **توحيد زر الحفظ وإلغاء التكرار في ذيل الكروت ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * حذف أزرار الحفظ المتفرقة في أسفل كروت التشخيص، الفريق الطبي، التخدير، ومدة العملية.
  * توحيد الحفظ في زر علوي بارز بجانب مدة العملية ديناميكي حسب الصلاحية: `حفظ بيانات العملية` للمسؤول/متعددي الصلاحيات، و`حفظ التشخيص والعلاجات` للجراح، و`حفظ تفاصيل التخدير` لطبيب التخدير، و`حفظ الفريق الطبي والتوقيت` لموظف العمليات.
  * دعم الإرسال الموحد `submitUnifiedSurgeryForm()` ومزامنة الصالة اللحظية `syncLocationSelection()`.
- **دمج الفريق الطبي مع التخدير في العمود الأيسر ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * إسناد العمود الأيمن (`col-xl-6`) للتشخيص الطبي للجراح وجدول العلاجات.
  * إسناد العمود الأيسر (`col-xl-6`) لكارتين متتاليين: كارت الفريق الطبي المشارك (أعلى) وكارت توثيق وخطة التخدير (أسفل).
  * نقل زر `اعتماد وإتمام محطة التخدير` إلى ترويسة كارت التخدير بأناقة.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php), [tests/Feature/SurgeryWaitingListFlightBoardTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryWaitingListFlightBoardTest.php))**:
  * اجتياز كافة اختبارات العمليات الجراحية وقائمة الانتظار والمساعدين بنجاح 100% (`25 passed, 237 assertions`).
  * اجتياز `Laravel Pint` بالكامل.

## Session log (2026-11-15 — المرحلة 208: تخطيط ثلاثي الأعمدة المتوازي في صف شبكي واحد: التشخيص والعلاجات في اليمين، الفريق الطبي والتوقيت في الوسط، وتفاصيل التخدير في اليسار 3-Column Parallel Row: Diagnosis on Right, Medical Team in Middle, Anesthesia on Left)

### Done
- **تخطيط ثلاثي الأعمدة المتوازي في ملف العملية ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * دمج البطاقات السريرية الثلاث في صف شبكي واحد (`<div class="row g-3 mb-4">`) بتنسيق تجاوبي ديناميكي (`col-xl-4 col-lg-4 col-md-12`).
  * إسناد عمود اليمين للتشخيص الطبي للجراح والملاحظات السريرية وجدول الأدوية والعلاجات (`pane-detail_diagnosis`).
  * إسناد عمود الوسط (قبل التخدير) لصالة العمليات والفريق الطبي المشارك، ومساعد الجراح، والتوقيت الفعلي للعملية (`pane-detail_team_timing`).
  * إسناد عمود اليسار لتوثيق تفاصيل التخدير، نوع التخدير، ملاحظات طبيب التخدير، وخطة التخدير السريرية (`pane-detail_anesthesia`).
  * ضبط ومطابقة الارتفاع الرأسي للكروت الثلاثة بنسبة 100% (`h-100 d-flex flex-column`) مع محاذاة وتثبيت أزرار الحفظ في أسفل كل بطاقة (`mt-auto`).
  * الحفاظ الكامل على النماذج البرمجية المستقلة (`detailDiagnosisForm`، `detailTeamTimingForm`، `detailAnesthesiaForm`) ومصفوفة الصلاحيات المشددة.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
  * اجتياز جميع اختبارات ملف وتفاصيل العملية بنجاح 100% (`9 passed, 181 assertions`).
  * اجتياز اختبارات المساعدين الجراحيين بنجاح 100% (`7 passed, 24 assertions`).
  * اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 207: مواءمة التشخيص الطبي للجراح وجدول العلاجات في العمود الأيمن وتفاصيل التخدير في العمود الأيسر Two-Column Side-by-Side Row: Surgeon Diagnosis & Treatments on Right, Anesthesia on Left)

### Done
- **تخطيط ثنائي الأعمدة المتوازي في ملف العملية ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * دمج بطاقة "التشخيص الطبي للجراح وجدول العلاجات" وبطاقة "توثيق تفاصيل التخدير" في صف شبكي واحد متجاور (`<div class="row g-3 mb-4">`).
  * إسناد عمود اليمين (`col-xl-6 col-lg-6`) للتشخيص الطبي للجراح والملاحظات السريرية وقائمة الأدوية والعلاجات.
  * إسناد عمود اليسار (`col-xl-6 col-lg-6`) لتوثيق تفاصيل التخدير، نوع التخدير، ملاحظات طبيب التخدير، وخطة التخدير السريرية.
  * ضبط ارتفاع البطاقتين ليتطابقا رأسياً (`h-100 d-flex flex-column`) مع تثبيت أزرار الحفظ في أسفل كل بطاقة (`mt-auto`).
  * الحفاظ الكامل على مصفوفة الصلاحيات (صلاحيات الجراح vs طبيب التخدير) ومعرفات النماذج البرمجية (`detailDiagnosisForm`, `detailAnesthesiaForm`).
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
  * اجتياز جميع اختبارات ملف وتفاصيل العملية بنجاح 100% (`9 passed, 181 assertions`).
  * اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 206: أزرار طي تفاعلية للتحاليل والأشعة ونوع العملية والإضافات، إلغاء نتائج الزيارات السابقة، وحذف كارت المعلومات الطبية لصالح كارت مريض موحد وقائمة حالة المريض العلوية Interactive Toggle Buttons for Tests & Operations, Decommissioning Previous Visit Results & Surgery Medical Card)

### Done
- **أزرار طي تفاعلية للتحاليل والأشعة ونوع العمليات ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * تحويل التحاليل المخبرية والأشعة والتصوير ونوع العملية والعمليات الإضافية إلى 3 أزرار طي وتوسيع تفاعلية رشيقة (`[ 🧪 التحاليل ]`، `[ 🩻 الأشعة ]`، و`[ 🔪 نوع العملية والعمليات الإضافية ]`).
  * عند النقر على أي زر، ينبثق الجدول المخصص أو لوحة التحكم بنوع العملية والإضافات والملاحظات السريرية فورا.
  * إلغاء جداول ونتائج الزيارات السابقة بالكامل وحصر العرض على ما يخص العملية الجراحية الحالية حصراً.
- **إلغاء كارت المعلومات الطبية للعملية الجراحية ونقل التحكم للترويسة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * حذف كارت "المعلومات الطبية للعملية الجراحية" بالكامل لمنع التكرار وازدحام الصفحة.
  * جعل كارت بيانات ومعلومات المريض بعرض الشاشة الكامل (Full Width) ومنسقا أفقيا بأناقة.
  * نقل القائمة المنسدلة لحالة المريض بالصالة (`Pre-Op` / `Intra-Op` / الإجراءات الحية) إلى شريط الإجراءات العلوي بجانب أزرار الطباعة والعودة.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php), [tests/Feature/SurgeryWaitingListFlightBoardTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryWaitingListFlightBoardTest.php))**:
  * اجتياز اختبارات ملف العملية بنجاح 100% (`9 passed, 181 assertions`).
  * اجتياز اختبارات المساعدين الجراحيين وقائمة الانتظار بنجاح 100% (`16 passed, 56 assertions`).
  * اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 205: تحويل إجراءات وخيارات حالة المريض في الصالة إلى قائمة منسدلة موحدة بدلاً من الأزرار المتعددة Dropdown Action Menu for Patient OR Status)

### Done
- **تحويل إجراءات حالة المريض في الصالة إلى قائمة منسدلة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * إلغاء الأزرار المتعددة والمتفرقة في ترويسة ملف العملية (`بدء العملية`, `تأجيل العملية`, `إنهاء العملية`, `إعادة إخراجه من الصالة`, `إلغاء العملية`).
  * بناء قائمة منسدلة موحدة ورشيقة (Bootstrap Dropdown Action Menu) تظهر الحالة الحالية بنبض ملون وبجانبها سهم `▾`.
  * عند النقر، تنبثق القائمة الرأسية لتعرض الإجراءات المتاحة بدقة حسب المرحلة الحقيقية للمريض (`Pre-Op` / `Intra-Op`) مع تمييز لوني وأيقونات واضحة.
  * الحفاظ على المزامنة اللحظية الفورية (`broadcastSurgeryLiveUpdate()`) وتأكيدات الأمان (Confirm Dialogs) والنوافذ المنبثقة للتأجيل والإلغاء.
  * عرض شارة القراءة فقط المقفلة عند اكتمال العملية (`Post-Op`) أو إلغائها أو لغير المصرح لهم بالتحكم.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
  * اجتياز اختبارات ملف وتفاصيل العملية بنجاح 100% (`9 passed, 181 assertions`).
  * اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 204: إلغاء نظام التبويبات نهائياً في ملف وتفاصيل العملية وتثبيت نمط الصفحة الواحدة المنظمة المفتوحة بالكامل Permanent Unified Continuous Single-Page Surgery File & Decommissioning Tabs System)

### Done
- **إلغاء نظام التبويبات وتثبيت الصفحة الواحدة المنظمة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * حذف أزرار التبديل `surgeryViewModeGroup` (`[ 📄 صفحة واحدة ]` و `[ 📑 تبويبات ]`) بالكامل من ترويسة الصفحة.
  * جعل كافة البطاقات والأقسام السريرية المصرح بها مفتوحة ومرئية دائماً رأسياً (`display: block !important`) بتسلسل انسيابي موحد.
  * تحويل شريط العناوين العلوي إلى شريط وصول وقفز سلس (Sticky Quick-Jump Links) للتنقل السريع بين الأقسام بضغطة زر دون طي أو إخفاء أي قسم.
  * دعم التمييز التلقائي للقسم النشط عبر التمرير (Scrollspy) وزر عائم للعودة للأعلى (`Back to Top`).
  * الحفاظ التام على مصفوفة الصلاحيات (حجب غير المصرح، قفل القراءة فقط، وإتاحة حفظ الأقسام القابلة للتعديل).
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
  * اجتياز اختبارات ملف العملية بنجاح 100% (`9 passed, 181 assertions`).
  * اجتياز اختبارات المساعدين الجراحيين بنجاح 100% (`7 passed, 24 assertions`).
  * اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 203: المزامنة اللحظية الحية لشاشات انتظار العمليات وسجل العمليات عند تنفيذ إجراءات البدء والإنهاء والتأجيل وإعادة الإخراج، تخطيط الأعمدة الـ 7 المختصرة ومنع السطر الثاني Realtime Live Sync for Surgery Waiting Board & Log on Action Buttons, 7 Streamlined Columns & Strict Single-Line Rows)

### Done
- **المزامنة اللحظية الحية لشاشات انتظار العمليات وسجل العمليات ([resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php), [resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php), [resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php))**:
  * ربط إجراءات ملف وتفاصيل العملية (`بدء العملية`, `إنهاء العملية`, `إعادة إخراجه من الصالة`, `تأجيل العملية`, و`إلغاء العملية`) بمحرك بث وإشعار لحظي عبر قناة `BroadcastChannel` و`localStorage` و`Laravel Echo`.
  * استقبال الإشارات الحية فوراً (0ms) في شاشة قائمة الانتظار `/surgeries/waiting` وسجل العمليات `/surgeries` مع استبدال وتحديث سلس لمحتوى الجداول عبر `DOMParser` دون إعادة تحميل الصفحة (No Page Reload) وبدون وميض.
  * إضافة نبض وميض أخضر/أزرق حي (`مزامنة حية`) في شريط أدوات شاشة الانتظار لتأكيد الاتصال والمزامنة الفورية.
  * دعم نبض فحص خلفي (Heartbeat Polling كل 4 ثوانٍ) لضمان التحديث التلقائي الصامت لشاشات العرض الجدارية (TV screens) وأجهزة الشبكة المحلية دون الحاجة لتحديث يدوي.
- **تخطيط الأعمدة الـ 7 المختصرة ومنع التفاف السطر الثاني كلياً ([resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php), [resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php))**:
  * ترتيب الأعمدة السبعة المعتمدة: `الحالة`، `المريض`، `العمر` (عمود مخصص)، `الجنس` (عمود مخصص)، `نوع العملية` (مختصر مع شارة كاش/ضمان مدمجة بنفس السطر)، `الطبيب الجراح`، و`رقم الغرفة`.
  * فرض العرض الأفقي الصارم بسطر واحد (`white-space: nowrap`) على كافة الخلايا والصفوف ومنع نزول أي بيانات لسطر ثانٍ.
  * اعتماد الاختصارات والمصطلحات الطبية المعتمدة للعمليات الجراحية عالمياً: `Pre-Op` (بالانتظار)، `Intra-Op` (داخل الصالة مع نقطة نبض خضراء ومؤقت زمني فوري)، و`Post-Op` (خرج من الصالة).
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryWaitingListFlightBoardTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryWaitingListFlightBoardTest.php), [tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
  * اجتياز اختبارات شاشة الانتظار بنجاح 100% (`9 passed, 32 assertions`).
  * اجتياز اختبارات العمليات الجراحية بنجاح 100% (`9 passed, 181 assertions`).
  * اجتياز `Laravel Pint` بالكامل دون أي ملاحظات.

## Session log (2026-11-15 — المرحلة 202: تقسيم شاشة الانتظار لعمودين (قبل الصالة / داخل الصالة)، إخفاء القائمة الجانبية للعرض الكامل 100%، ترتيب الأعمدة الـ 5 وسهولة القراءة، والتحكم السريري من داخل ملف العملية، وتوجيه موظف العمليات لسجل العمليات 2-Column Waiting Flight Board, Full-Width View, Streamlined Columns & Surgery Staff Auto-Redirect)

### Done
- **تقسيم شاشة الانتظار إلى عمودين متوازيين مع إخفاء المكتملة تلقائياً ([resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php))**:
  * العمود الأول: `قبل دخول الصالة (بالانتظار)` يشمل المرضى المجدولين وبانتظار الدخول.
  * العمود الثاني: `داخل صالة العمليات (جارية الآن)` يشمل العمليات الجارية حالياً في الصالات.
  * استبعاد العمليات المكتملة (`completed`) كلياً من شاشة الانتظار بمجرد إنهائها لتختفي فوراً وتظل مسجلة في سجل العمليات العام.
- **إخفاء القائمة الجانبية للعرض الكامل 100% وترتيب الأعمدة الـ 5 ([resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php))**:
  * إخفاء القائمة الجانبية تلقائياً في شاشة قائمة الانتظار لإتاحة العرض الكامل 100% (Full Width) وتوفير أقصى قدر من الاتساع والانسيابية للجداول.
  * حذف زر "سجل العمليات" وشريط "البحث الفوري" من أعلى الشاشة لتفريغ المساحة تماماً للجدولين.
  * ترتيب الأعمدة الخمسة لسهولة القراءة: `المريض` (الاسم كرابط والعمر)، `نوع العملية` (المسمى وتغطية التأمين/الكاش)، `الطبيب الجراح`، `رقم الغرفة/الصالة`، و`حالة المريض في الصالة` (شارة عنبرية مصغرة لبالانتظار وشارة زرقاء بنبض حي لداخل الصالة).
  * ضبط الحواشي الداخلية (`padding: 9px 12px`)، وحذف رقم الملف ورقم الهاتف من العرض بالانتظار لتجنب التكدس.
- **التحكم بالمسار السريري حصرياً من ملف العملية وإلغاء الأرشيف العام ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php), [resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
  * إلغاء زر `الأرشيف العام` من شاشة الانتظار.
  * حصر التحكم من داخل ملف وتفاصيل العملية (`surgeries.show`):
    1. `بدء عملية`: ينقل المريض من العمود الأول للثاني.
    2. `تأجيل العملية`: يفتح نافذة منبثقة لتحديد الموعد الجديد وإبقاء المريض بالانتظار.
    3. `إعادة إخراجه من الصالة`: يعيد المريض من داخل الصالة إلى عمود الانتظار ويصفر محطة الصالة.
    4. `إنهاء العملية`: ينهي العملية ويخفي المريض من شاشة الانتظار كلياً.
  * إبراز شارة حالة المريض في الصالة في ترويسة ملف العملية بلون مميز ونبض حي.
- **توجيه حساب موظف العمليات مباشرة لسجل العمليات ([app/Http/Controllers/Auth/LoginController.php](file:///C:/get/hospital-system/app/Http/Controllers/Auth/LoginController.php), [app/Http/Controllers/DashboardController.php](file:///C:/get/hospital-system/app/Http/Controllers/DashboardController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * توجيه دور `surgery_staff` أو `موظف عمليات` مباشرة إلى `/surgeries` عند تسجيل الدخول، وعند زيارة `/`، وعند زيارة `/dashboard`.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryWaitingListFlightBoardTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryWaitingListFlightBoardTest.php), [tests/Feature/SurgeryStaffDashboardAndLoginRedirectTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryStaffDashboardAndLoginRedirectTest.php), [tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
  * اجتياز اختبارات شاشة الانتظار بنجاح 100% (`9 passed, 32 assertions`).
  * اجتياز اختبارات توجيه موظف العمليات بنجاح 100% (`4 passed, 7 assertions`).
  * اجتياز اختبارات `SurgeryTest` بنجاح 100% (`9 passed, 181 assertions`).
  * اجتياز `Laravel Pint` بالكامل.

## Session log (2026-11-14 — المرحلة 201: تبسيط شاشة انتظار العمليات الجراحية `/surgeries/waiting` كجدول حصري بـ 5 أعمدة، إزالة التقويم والمؤشرات، تلوين المرضى حسب الحالة السريرية، وإضافة خيار إعادة الإخراج من الصالة Streamlined Waiting Table, Tri-Color Patient Stage Coding & Return to Waiting from OR)

### Done
- **تبسيط شاشة الانتظار وإزالة التقويم والمؤشرات ([resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php))**:
  * حذف شبكة التقويم الشهري المدمجة وحذف بطاقات المؤشرات (KPIs) وأزرار الطي العلوية لتصبح الشاشة تركز بنسبة 100% على جدول العمليات الفوري.
  * حصر أعمدة الجدول في 5 أعمدة معتمدة مطابقة للسجل العام: `حالة المريض في الصالة`، `المريض`، `نوع العملية`، `الطبيب الجراح`، و`رقم الغرفة`.
  * حذف عمود الإجراءات السريعة بالكامل من جدول الانتظار، ليكون التحكم السريري بمسار المريض مداراً ومحمياً بالكامل من داخل ملف العملية.
- **الترميز اللوني الثلاثي لصفوف وبيانات المرضى ([resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php), [resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php))**:
  * تمييز كل مريض بلون واضح ومحدد بالعين المجردة حسب حالته الحقيقية:
    1. `دخل الصالة (جارية)`: تظليل أزرق خفيف مع شريط جانبي أزرق عريض، شارة دخل الصالة بنبض أخضر، واسم وأيقونة المريض باللون الأزرق.
    2. `بالانتظار (لم يدخل بعد)`: تظليل أصفر عنبري دافئ مع شريط جانبي أصفر وشارة انتظار دافئة.
    3. `انتهى (خرج من الصالة)`: تظليل أخضر هادئ مع شريط جانبي أخضر، شارة انتهى (خرج من الصالة)، واسم وأيقونة المريض باللون الأخضر.
    4. `ملغاة`: تظليل وشريط أحمر هادئ.
  * تعميم هذا التمييز اللوني البصري الفوري على جدول العمليات العام `/surgeries`.
- **إضافة خيار "إعادة إخراجه من الصالة" وتصفير محطة الصالة ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * إضافة زر تحكم صريح ومميز في شاشة ملف وتفاصيل العملية: `[ 🚪 إعادة إخراجه من الصالة ]`.
  * تحديث دالة `returnToWaiting` في الكنترولر لتقوم بإلغاء حالة محطة صالة العمليات `operationTheaterStation` وإعادتها إلى `pending` وتصفير توقيت البدء، لمنع بقاء المريض معلقاً كـ `in_progress` بالخطأ.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryWaitingListFlightBoardTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryWaitingListFlightBoardTest.php), [tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
  * اجتياز اختبارات شاشة الانتظار وإعادة الإخراج من الصالة بنجاح 100% (`9 passed, 32 assertions`).
  * اجتياز اختبارات `SurgeryTest` بنجاح 100% (`9 passed, 181 assertions`).
  * اجتياز `Laravel Pint` بالكامل دون أي أخطاء.

## Session log (2026-11-14 — المرحلة 200: بناء نظام العرض الموحد كصفحة واحدة لملف العملية الجراحية `/surgeries/{id}` مع التبديل اللحظي والتنقل العائم Unified Continuous Single-Page Surgery File & Instant View Mode Switcher)

### Done
- **بناء نمط الصفحة الواحدة الموحدة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * إضافة أزرار تبديل نمط العرض في أعلى شريط الإجراءات: `[ 📄 صفحة واحدة ]` و `[ 📑 تبويبات ]` مع حفظ التفضيل لحظياً في `localStorage`.
  * تحويل عرض ملف العملية إلى صفحة واحدة متصلة تعرض كافة البطاقات المتاحة للمستخدم رأسياً بانسيابية تامة.
  * تحويل شريط التبويبات إلى شريط تنقل سريع ثابت في الأعلى (Sticky Quick-Nav) يقفز للقسم المطلوب بضغطة زر مع تمييز القسم النشط تلقائياً بالتمرير (Scrollspy).
  * إضافة زر عائم سلس أسفل الشاشة للعودة السريعة للأعلى (`Back to Top`).
  * الحفاظ الكامل على مصفوفة الصلاحيات (حجب الأقسام غير المخولة، قفل أقسام القراءة فقط، وإتاحة الحفظ لأقسام الكتابة).
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
  * اجتياز اختبارات ملف العملية بنجاح 100% (`16 passed, 205 assertions`).
  * اجتياز `Laravel Pint` بالكامل.

## Session log (2026-11-14 — المرحلة 199: توجيه حساب الممرض تلقائياً لمحطة التمريض `/surgery-stations/nursing` كصفحة رئيسية وإصلاح استعلام إلغاء كشفية الطبيب Nurse Landing & Auto-Redirection to Nursing Station, Doctor Visit Cancel Query Fix)

### Done
- **توجيه حساب الممرض مباشرة لمحطة التمريض ([app/Http/Controllers/Auth/LoginController.php](file:///C:/get/hospital-system/app/Http/Controllers/Auth/LoginController.php), [app/Http/Controllers/DashboardController.php](file:///C:/get/hospital-system/app/Http/Controllers/DashboardController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * ربط دور الممرض (`nurse` أو `تمريض` أو `user_type === 'nurse'`) بالتحويل التلقائي المباشر إلى محطة التمريض (`route('nursing-station.index')` / `/surgery-stations/nursing`).
  * تفعيل التوجيه فور تسجيل الدخول، وعند زيارة المسار الجذر `/`، وعند الدخول إلى لوحة التحكم `/dashboard`.
- **إصلاح استعلام إلغاء كشفية الطبيب ([app/Http/Controllers/DoctorVisitController.php](file:///C:/get/hospital-system/app/Http/Controllers/DoctorVisitController.php))**:
  * تصحيح خطأ الاستعلام `Column not found: 1054 Unknown column 'payment_status' in 'WHERE'` في جدول `radiology_requests` وتحويله إلى التحقق الصحيح عبر `exists()`.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/NurseDashboardAndLoginRedirectTest.php](file:///C:/get/hospital-system/tests/Feature/NurseDashboardAndLoginRedirectTest.php), [tests/Feature/DoctorVisitCancellationRefundTest.php](file:///C:/get/hospital-system/tests/Feature/DoctorVisitCancellationRefundTest.php))**:
  * كتابة حزمة اختبارات تحقق التوجيه واجتيازها بنجاح 100% (`3 passed, 5 assertions`).
  * اجتياز اختبار إلغاء كشفية الطبيب بنجاح (`2 passed, 6 assertions`).
  * اجتياز `Laravel Pint` بالكامل.

## Session log (2026-11-14 — المرحلة 198: حصر وتخصيص أعمدة سجل العمليات الجراحية `/surgeries` بالأعمدة الـ 5 المعتمدة فقط Streamlining Surgeries Table to 5 Core Columns)

### Done
- **حصر أعمدة جدول العمليات النشطة والمكتملة ([resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php))**:
  * اقتصار الجدول حصراً على 5 أعمدة:
    1. `حالة المريض في الصالة`: تمييز دقيق لحالة تواجد المريض (`دخل الصالة (جارية)` عند بدء العملية، `خرج من الصالة` عند الاكتمال، `لم يدخل بعد` في الانتظار أو الجدولة، و`ملغاة`).
    2. `المريض`: اسم المريض كرابط مباشر لفتح وتعديل ملف العملية (`route('surgeries.show', $surgery)`) مع رقم الهاتف.
    3. `نوع العملية`: مسمى العملية ونوع التغطية (كاش أو ضمان).
    4. `الطبيب الجراح`: اسم الجراح (أو خارجي).
    5. `رقم الغرفة`: رقم الغرفة بتصنيفها ونوعها.
  * إزالة الأعمدة الزائدة كلياً: إزالة عمود المعرف `#`، وعمود `تاريخ وموعد العملية`، وعمود `تاريخ الخروج`، وعمود `الإجراءات` لتوفير أقصى قدر من الوضوح والسرعة للمستخدم.
- **التحقق وتأكيد صحة القوالب**:
  * تجميع قوالب Blade بنجاح (`php artisan view:cache`).
  * اجتياز `Laravel Pint` واجتياز حزمة `tests/Feature/SurgeryTest.php` بنجاح 100% (`9 passed, 181 assertions`).

## Session log (2026-11-15 — المرحلة 202: اعتماد وتوحيد اسم "مستشفى الكفاءات الأهلي" في قاعدة البيانات وجميع وصولات وقوالب ووثائق النظام Unification of Hospital Name Across Database, Badge, Receipts & Views)

### Done
- **ترحيل وتحديث قاعدة البيانات ([database/migrations/2026_11_15_193000_update_hospital_name_to_alkafaat.php](file:///c:/get/hospital-system/database/migrations/2026_11_15_193000_update_hospital_name_to_alkafaat.php), [database/seeders/HospitalSeeder.php](file:///c:/get/hospital-system/database/seeders/HospitalSeeder.php))**:
  * إنشاء مايجريشن لتحديث سجل المستشفى الأساسي `id = 1` في جدول `hospitals` ليصبح اسمه `مستشفى الكفاءات الأهلي` بدلاً من الاسم القديم (`مستشفى الأمل الأهلي`) وتحديث البريد إلى `info@alkafaat-hospital.com`.
  * تحديث ملف البذار `HospitalSeeder.php` لضبط الهمزة وتطابق الاسم الرسمي.
- **ضبط الإعدادات العامة للمشروع ([config/app.php](file:///c:/get/hospital-system/config/app.php), [.env](file:///c:/get/hospital-system/.env), [resources/views/layouts/app.blade.php](file:///c:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * ضبط `APP_NAME="مستشفى الكفاءات الأهلي"` في ملف التكوين والبيئة، وتحديث عنوان النظام الرئيسي في `layouts/app.blade.php`.
- **توحيد القوالب والوصولات والباجات البرمجية**:
  * تحديث القيمة الاحتياطية (fallback) في باجة وهوية الموظف [hr/employees/badge.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/badge.blade.php) وقسيمة الراتب [hr/payrolls/payslip.blade.php](file:///c:/get/hospital-system/resources/views/hr/payrolls/payslip.blade.php).
  * توحيد بطاقة حجز الموعد [appointment-print.blade.php](file:///c:/get/hospital-system/resources/views/doctors/visits/appointment-print.blade.php) وطباعة الوصفة [prescription-print.blade.php](file:///c:/get/hospital-system/resources/views/doctors/visits/prescription-print.blade.php).
  * توحيد وصولات الصندوق وسندات الاسترجاع الحرارية [receipt-print.blade.php](file:///c:/get/hospital-system/resources/views/cashier/receipt-print.blade.php) ووصولات الخدج [nicu-receipt-print.blade.php](file:///c:/get/hospital-system/resources/views/cashier/nicu-receipt-print.blade.php) وكشف حساب الخدج [nicu-itemized-print.blade.php](file:///c:/get/hospital-system/resources/views/cashier/nicu-itemized-print.blade.php).
  * توحيد وصولات صيدلية الـ POS [pharmacy/pos/receipt.blade.php](file:///c:/get/hospital-system/resources/views/pharmacy/pos/receipt.blade.php)، كاشير العيون [eye_cashier_receipt_print.blade.php](file:///c:/get/hospital-system/resources/views/eye/cashier/eye_cashier_receipt_print.blade.php)، كشف النظارات [eye_examination_glasses_print.blade.php](file:///c:/get/hospital-system/resources/views/eye/examinations/eye_examination_glasses_print.blade.php)، وتذكرة حجز العيون [eye_reception_ticket_print.blade.php](file:///c:/get/hospital-system/resources/views/eye/reception/eye_reception_ticket_print.blade.php).
  * توحيد تقارير تحاليل وأشعة العمليات [staff/surgery-lab-tests/print.blade.php](file:///c:/get/hospital-system/resources/views/staff/surgery-lab-tests/print.blade.php) و [staff/surgery-radiology-tests/print.blade.php](file:///c:/get/hospital-system/resources/views/staff/surgery-radiology-tests/print.blade.php) وكارت سرير الخدج وسوار المعصم [nicu/print/crib_card.blade.php](file:///c:/get/hospital-system/resources/views/nicu/print/crib_card.blade.php) و [nicu/print/wristband.blade.php](file:///c:/get/hospital-system/resources/views/nicu/print/wristband.blade.php).
- **التحقق الآلي**:
  * تنسيق الكود عبر `vendor/bin/pint --dirty`، وتجميع Blade بنجاح (`view:cache`)، واجتياز اختبارات ميزة الموارد البشرية والباجات بنسبة 100% (`10 passed, 58 assertions`).

## Session log (2026-11-14 — المرحلة 201: إضافة وإدارة جنسية الموظف في نموذج تسجيل الموظفين وملفاتهم وإعدادات الحقول HR Employee Nationality Field Integration, Dynamic Lookups & Profile Display)

### Done
- **بنية قاعدة البيانات والنموذج ([database/migrations/2026_11_14_211500_add_nationality_to_hr_employees_table.php](file:///c:/get/hospital-system/database/migrations/2026_11_14_211500_add_nationality_to_hr_employees_table.php), [app/Models/HrEmployee.php](file:///c:/get/hospital-system/app/Models/HrEmployee.php))**:
  * إضافة عمود `nationality` بطول `VARCHAR(50)` مع قيمة افتراضية `عراقي` مباشرة بعد عمود `national_id`.
  * إضافة `'nationality'` ضمن مصفوفة `$fillable` بموديل `HrEmployee`.
- **التكامل مع نظام إعدادات الحقول والقوائم المنسدلة ([database/seeders/HrSettingsSeeder.php](file:///c:/get/hospital-system/database/seeders/HrSettingsSeeder.php))**:
  * تسجيل الحقل في جدول `hr_field_requirements` ليظهر في شاشة إعدادات الموارد البشرية (`/hr/settings`) للتحكم في كونه إجبارياً أو اختيارياً.
  * تزويد جدول `hr_lookup_options` بقائمة جنسيات شائعة بفئة `nationality` (عراقي، سوري، لبناني، مصري، أردني، هندي، باكستاني، فلبيني، أخرى).
- **التحكم والتحقق والبحث السريع ([app/Http/Controllers/HR/HrEmployeeController.php](file:///c:/get/hospital-system/app/Http/Controllers/HR/HrEmployeeController.php))**:
  * تمرير قائمة الجنسيات النشطة لشاشتي الإضافة والتعديل (`create`, `edit`).
  * تطبيق قواعد التحقق الديناميكية المعتمدة على إعدادات الموارد البشرية مع اعتماد "عراقي" كافتراضي تلقائي في دالتي `store` و `update`.
  * شمول حقل الجنسية في فلتر البحث العام بسجل الموظفين (`index`).
- **الواجهات الأمامية ([resources/views/hr/employees/create.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/create.blade.php), [resources/views/hr/employees/edit.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/edit.blade.php), [resources/views/hr/employees/show.blade.php](file:///c:/get/hospital-system/resources/views/hr/employees/show.blade.php))**:
  * إضافة حقل الجنسية في نموذج إنشاء الموظف ونموذج التعديل مع قائمة اقتراحات سريعة (`<datalist>`) وتعبئة افتراضية فورية "عراقي".
  * إعادة موازنة شبكة الأعمدة (Grid System) لبطاقة البيانات الشخصية لتكون متناسقة ومتوازنة على 3 صفوف متكاملة.
  * إبراز جنسية الموظف في بطاقة البيانات الشخصية بملف وإضبارة الموظف (`show.blade.php`).
- **التحقق الآلي**:
  * إضافة وتوسيع اختبارات الميزة في [`tests/Feature/HREmployeeTest.php`](file:///c:/get/hospital-system/tests/Feature/HREmployeeTest.php) واجتياز كافة الاختبارات بنسبة 100% (`10 passed, 58 assertions`).
  * اجتياز التنسيق القياسي عبر `Laravel Pint` ونجاح تجميع قوالب Blade (`view:cache`).

## Session log (2026-11-14 — المرحلة 200: هيكلة تبويب وقائمة إعدادات الخدج والحاضنات، فصل شاشة دليل الحليب في واجهة مخصصة، وشريط التنقل الموحد NICU Settings Submenu, Dedicated Milk Types View & Unified Sub-tabs Architecture)

### Done
- **هيكلة القائمة الجانبية وقسم الخدج ([resources/views/layouts/app.blade.php](file:///c:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * تجميع بنود الإعدادات تحت قائمة فرعية منسدلة (Collapsible Submenu) باسم **"الإعدادات"** (`#nicuSettingsSubSection`) تشمل العناوين الفرعية (Sub-titles) الثلاثة:
    1. `تسعير باقات وخدمات الخدج (NICU)` (`nicu.services.index`).
    2. `إدارة حاضنات الخدج - ط6` (`incubators.index`).
    3. `قائمة وتصنيفات حليب وتغذية الخدج` (`nicu.milk-types.index`).
- **بناء واجهة مخصصة مستقلة لدليل أنواع وتغذية الخدج ([resources/views/nicu/milk-types/index.blade.php](file:///c:/get/hospital-system/resources/views/nicu/milk-types/index.blade.php), [app/Http/Controllers/NicuMilkTypeController.php](file:///c:/get/hospital-system/app/Http/Controllers/NicuMilkTypeController.php), [routes/web.php](file:///c:/get/hospital-system/routes/web.php))**:
  * إضافة مسار `Route::get('/milk-types', [NicuMilkTypeController::class, 'index'])->name('milk-types.index')` ودالة `index()` في الكنترولر مع دعم البحث بالاسم والتصنيف والفلترة بالحالة (نشط/معطل).
  * تصميم واجهة مخصصة ومستقلة تشمل بطاقات إحصائيات سريعة، جدول الحليب، نوافذ منبثقة للإضافة والتعديل، ومفاتيح التفعيل اللحظية المعتمدة على AJAX.
  * تحديث رابط إدارة الحليب في إضبارة الخديج السريرية ([resources/views/nicu/show.blade.php](file:///c:/get/hospital-system/resources/views/nicu/show.blade.php)) ليوجه مباشرة للواجهة المستقلة.
- **شريط تبويبات الإعدادات المشترك ([resources/views/nicu/partials/settings-nav.blade.php](file:///c:/get/hospital-system/resources/views/nicu/partials/settings-nav.blade.php), [resources/views/nicu/services/index.blade.php](file:///c:/get/hospital-system/resources/views/nicu/services/index.blade.php), [resources/views/incubators/index.blade.php](file:///c:/get/hospital-system/resources/views/incubators/index.blade.php))**:
  * إنشاء شريط تبويبات علوي أنيق موحد (Sub-tabs) يربط بين الصفحات الثلاث ويوضع في رأس كل شاشة لتسهيل التنقل اللحظي بين باقات الخدمات والحاضنات وأنواع الحليب.
  * تنظيف شاشة الخدمات [resources/views/nicu/services/index.blade.php](file:///c:/get/hospital-system/resources/views/nicu/services/index.blade.php) وكنترولر الخدمات من كود الحليب وجعلها مخصصة كلياً لباقات التسعير والضمان والتنفس الاصطناعي.
- **التحقق الآلي وتأكيد الاختبارات ([tests/Feature/NicuGranularPermissionsAndMilkTypesTest.php](file:///c:/get/hospital-system/tests/Feature/NicuGranularPermissionsAndMilkTypesTest.php), [tests/Feature/IncubatorWorkflowTest.php](file:///c:/get/hospital-system/tests/Feature/IncubatorWorkflowTest.php))**:
  * اجتياز فحص التنسيق القياسي `Laravel Pint`، ونجاح التجميع المؤقت لقوالب Blade (`view:cache`)، واجتياز اختبارات ميزة الخدج والحاضنات بنجاح 100% (`5 passed, 41 assertions` و `12 passed, 75 assertions`).

## Session log (2026-11-14 — المرحلة 199: حل مشكلة استدعاءات AJAX في تبديل وتعديل أنواع حليب الخدج والخدمات وتوافق السيرفر السحابي Subdirectory AJAX URL Fix & NICU Milk Types Toggle Compatibility)

### Done
- **تصحيح مسارات استدعاءات الـ AJAX والنماذج في خدمات وحليب الخدج ([resources/views/nicu/services/index.blade.php](file:///c:/get/hospital-system/resources/views/nicu/services/index.blade.php))**:
  * معالجة المسار المطلق الصلب في زر تبديل حالة تفعيل نوع الحليب (`milk-type-toggle-switch`) وتحويله من `/nicu/milk-types/${id}/toggle` إلى `data-toggle-url="{{ route('nicu.milk-types.toggle', $milkType) }}"` مع fallback ديناميكي `{{ url('nicu/milk-types') }}/${milkTypeId}/toggle`، مما يحل خطأ 404 وانقطاع الاتصال بالخادم على السيرفر السحابي هوسـتنجر المرفوع على مسار فرعي (`/hearmz/`).
  * تزويد طلبات الـ fetch بترويسة `'Accept': 'application/json'` لضمان التعامل السلس مع استجابات JSON.
  * تصحيح مسار الفورم في مودال تعديل نوع الحليب ليكون `{{ url('nicu/milk-types') }}/${milkType.id}` بدلاً من المسار الصلب.
  * تصحيح مسار تبديل تفعيل باقات خدمات الخدج (`service-toggle-switch`) ومودال تعديل الخدمة لاستخدام `data-toggle-url` ومساعد `{{ url('nicu/services') }}`.
- **توافق الصلاحيات وسلاسة التجربة ([app/Http/Controllers/NicuMilkTypeController.php](file:///c:/get/hospital-system/app/Http/Controllers/NicuMilkTypeController.php))**:
  * توسيع فحص الصلاحية في `authorizeManage()` ليشمل صلاحية `manage incubator reservations` بجانب `manage nicu services` و `manage nicu care plans`، لمطابقة كنترولر الخدمات ومنع أي اعتراض صلاحيات 403 غير مقصود للمشرفين ومسؤولي الحاضنات.
- **تصحيح استدعاءات كاميرا الخدج عبر AJAX ([resources/views/nicu/index.blade.php](file:///c:/get/hospital-system/resources/views/nicu/index.blade.php), [resources/views/nicu/reservations/index.blade.php](file:///c:/get/hospital-system/resources/views/nicu/reservations/index.blade.php))**:
  * تحويل استدعاء توكن كاميرا الطفل `sendBabyCamLink` من `/nicu/reservations/...` إلى `{{ url('nicu/reservations') }}/${reservationId}/cam-token` للعمل بتوافق كامل مع النطاق المحلي والسحابي.
- **التحقق الآلي وتأكيد الاختبارات ([tests/Feature/NicuGranularPermissionsAndMilkTypesTest.php](file:///c:/get/hospital-system/tests/Feature/NicuGranularPermissionsAndMilkTypesTest.php))**:
  * اجتياز فحص التنسيق القياسي `Laravel Pint`، ونجاح التجميع المؤقت لقوالب Blade (`view:cache`)، واجتياز اختبارات ميزة أنواع الحليب والصلاحيات بنجاح 100% (`5 passed, 38 assertions`).

## Session log (2026-11-14 — المرحلة 198: حل استثناء قص البيانات 1265 عند إنشاء موظف جديد وتحويل عمود نوع التعاقد `employment_type` إلى VARCHAR مرن وتطبيع الخيارات HR Employee Creation Data Truncation Fix & Flexible Employment Type Migration)

### Done
- **ترحيل وتوسيع عمود نوع التعاقد ([database/migrations/2026_11_14_200500_alter_employment_type_in_hr_employees_table.php](file:///c:/get/hospital-system/database/migrations/2026_11_14_200500_alter_employment_type_in_hr_employees_table.php))**:
  * تحويل عمود `employment_type` في جدول `hr_employees` من `ENUM` صارم محصور في 4 خيارات إلى `string('employment_type', 50)->default('full_time')` لمنع استثناء `SQLSTATE[01000]: Warning: 1265 Data truncated for column 'employment_type'` نهائياً.
  * إتاحة التوافق التام مع خيارات القوائم المنسدلة الديناميكية (`hr_lookup_options`) وخيارات العقود الحكومية والوزارية (`gov_contract`) وأي خيارات يضيفها مسؤول الموارد البشرية عبر شاشة الإعدادات `/hr/settings`.
- **تطبيع الكود والمعالجة الوقائية ([app/Http/Controllers/HR/HrEmployeeController.php](file:///c:/get/hospital-system/app/Http/Controllers/HR/HrEmployeeController.php), [app/Models/HrEmployee.php](file:///c:/get/hospital-system/app/Models/HrEmployee.php))**:
  * تطبيع قيمة `employment_type` تلقائياً في دالتي `store` و `update` لتحويل `Fulltime` أو `full-time` إلى الكود المعتمد `full_time`.
  * تحديث دالة التسمية `getEmploymentTypeNameAttribute()` في الموديل للتعرف على العقود الحكومية وتوفير fallback ديناميكي للاستعلام التلقائي من جدول `HrLookupOption`.
  * تصحيح السجل رقم 42 في `hr_lookup_options` لتوحيد الكود مع `full_time`.
- **التحقق الآلي وتأكيد الاختبارات ([tests/Feature/HREmployeeTest.php](file:///c:/get/hospital-system/tests/Feature/HREmployeeTest.php))**:
  * إضافة اختبار `test_can_create_employee_with_custom_or_fulltime_employment_type()` واجتياز كافة اختبارات الموارد البشرية بنجاح 100% (`9 passed, 46 assertions`)، واجتياز فحص التنسيق القياسي `Laravel Pint`.

## Session log (2026-11-13 — المرحلة 197: تصحيح احتساب وعرض العناصر المدفوعة سابقاً وبنود باقات العمليات في كاشير العمليات، استبعاد التكرار، وتمييز حصة المريض ومطالبة الضمان Surgery Cashier Previously Paid Items & Package Reconciliation)

### Done
- **تصحيح احتساب المبالغ المدفوعة سابقاً واستبعاد بنود الباقة ([resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php), [resources/views/cashier/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/index.blade.php))**:
  * استبعاد بنود باقة العملية (`is_package_included`) من التكرار والجمع المزدوج فوق رسم العملية في حساب `$paidLabFee` و`$paidRadiologyFee` و`$paidExtraFee`.
  * تمييز بنود باقة العملية في جدول العناصر المدفوعة سابقاً ببادج أنيق: `مشمول بالباقة (0 د.ع)` بدلاً من عرض أسعار الكاش الافتراضية.
  * إدراج صفوف الخدمات الإضافية المدفوعة فعلياً (`paidExtraServices`) داخل جدول العناصر المدفوعة سابقاً ليتطابق مجموع الصفوف مع الإجمالي في ذيل الجدول.
- **إبراز المسدد نقداً من المريض وتغطية الضمان الصحي ([resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
  * إضافة سطر تفصيلي واضح في بطاقة الملخص وفي ذيل جدول المدفوعات يوضح: `المسدد نقداً من المريض` (من واقع وصولات القبض المسجلة في الصندوق) مقابل `مطالبة وتغطية الضمان` لمريض التأمين.
  * ضبط توازن الحسابات والمعلقات بحيث يطابق الإجمالي المعتمد بدقة: `الإجمالي = المدفوع سابقاً + المعلق المطلوب دفعه`.
- **التحقق الآلي وتأكيد صحة الاختبارات ([tests/Feature/SurgeryCashierSplitAndInsurancePaymentTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryCashierSplitAndInsurancePaymentTest.php))**:
  * اجتياز جميع اختبارات ميزة كاشير العمليات والضمان الصحي بنجاح 100% (`8 passed, 55 assertions`).

## Session log (2026-11-13 — المرحلة 196: بناء واجهة ونظام دليل مساعدي الجراحين `/surgical-assistants`، تسجيل الأسماء الـ 9 المعتمدة، والربط التلقائي بملف العملية وصالة العمليات Dedicated Surgical Assistants Management & Dynamic Surgeries Integration)

### Done
- **إنشاء بنية وجدول مساعدي الجراحين ([database/migrations/2026_11_13_221000_create_surgical_assistants_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_13_221000_create_surgical_assistants_table.php), [app/Models/SurgicalAssistant.php](file:///C:/get/hospital-system/app/Models/SurgicalAssistant.php), [database/seeders/SurgicalAssistantSeeder.php](file:///C:/get/hospital-system/database/seeders/SurgicalAssistantSeeder.php))**:
  * بناء جدول `surgical_assistants` ونموذج `SurgicalAssistant` بنمط كاستات لارافيل 12 الحديثة (`name`, `phone`, `notes`, `is_active`).
  * تسجيل وتغذية الأسماء الـ 9 المعتمدة أولياً عبر السييدر (علياء عبدالجبار، علي عبدالله، حسين ناهي، ليث رحيم، حكم علاء، عبدالله علي، محمد فالح، مضاء علي، ومساعد جراح خارجي).
- **بناء واجهة الإدارة الكاملة المستقلة ([app/Http/Controllers/SurgicalAssistantController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgicalAssistantController.php), [resources/views/surgical-assistants/index.blade.php](file:///C:/get/hospital-system/resources/views/surgical-assistants/index.blade.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * تصميم واجهة متكاملة مطابقة لنمط واجهة الأطباء ودليل العمليات مع بطاقات إحصائيات سريعة، شريط بحث بالاسم والهاتف، جدول المساعدين مع إحصائية العمليات المسجلة لكل مساعد، زر تبديل الحالة (نشط/معطل)، ونوافذ منبثقة (Modals) للإضافة والتعديل والحذف.
  * إضافة رابط `مساعدو الجراحين` بالقائمة الجانبية تحت قسم العمليات الجراحية للمشرفين وموظفي العمليات.
- **الربط المباشر مع ملف العملية وصالة العمليات ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * تمرير قائمة المساعدين النشطين ديناميكياً لملف وتفاصيل العملية (`detail_team_timing`) وتزويد حقل `surgical_assistant_name` بقائمة اقتراحات `datalist` مع رابط مباشر يفتح دليل المساعدين.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgicalAssistantTest.php](file:///C:/get/hospital-system/tests/Feature/SurgicalAssistantTest.php))**:
  * اجتياز `Laravel Pint` وتجميع Blade واجتياز اختبارات الميزة بنجاح 100% (7 اختبارات).

## Session log (2026-11-13 — المرحلة 195: تمكين ظهور المريض لطبيب التخدير فور التعيين، إزالة عائق اكتمال الجراح، ومزامنة محطة التخدير واعتماد أنواع التخدير الخمسة Anesthesiologist Immediate Visibility, Auto Station Sync & 5 Anesthesia Types)

### Done
- **إزالة عائق اكتمال الجراح وإتاحة الوصول الفوري لمريض التخدير ([app/Http/Controllers/AnesthesiaStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/AnesthesiaStationController.php))**:
  * إزالة شرط حجب العمليات القائم على اكتمال محطة الجراح (`whereHas('surgeonStation', fn($q) => $q->where('status', 'completed'))`) في محطة التخدير (`/anesthesia`)، مما يتيح ظهور العمليات المعينة فوراً للمخدر للبدء بالتوثيق والتجهيز السريري.
  * إزالة التحويل الإجباري والمنع عند فتح شاشة التخدير (`show`).
- **المزامنة والإنشاء التلقائي لمحطة التخدير وسجل العمليات ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [app/Http/Controllers/DashboardController.php](file:///C:/get/hospital-system/app/Http/Controllers/DashboardController.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php), [database/seeders/RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php))**:
  * إنشاء وتحديث سجل `AnesthesiaStation` فور قيام موظف العمليات بحفظ الفريق الطبي والتوقيت في `detail_team_timing`.
  * شمول طبيب التخدير في استعلامات وفلاتر سجل العمليات العام (`/surgeries`) ليتمكن من رؤية عملياته كمخدر أو جراح، وتحديث عدادات القائمة الجانبية ولوحة التحكم.
  * تثبيت صلاحيات دور `التخدير` و`anesthesia` في قاعدة البيانات وبذور الصلاحيات.
- **اعتماد أنواع التخدير الخمسة المعتمدة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php), [resources/views/surgery-stations/anesthesia/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/anesthesia/show.blade.php))**:
  * توفير أزرار التعبئة السريعة وقوائم الاقتراح بالأنواع الخمسة: `عام` (General Anesthesia)، `نصفي` (Spinal Anesthesia)، `موضعي` (Local Anesthesia)، `مناطقي` (Regional Anesthesia)، `سديشن` (Sedation).
- **التحقق الآلي وتنسيق الكود ([tests/Feature/AnesthesiologistVisibilityAndWorkflowTest.php](file:///C:/get/hospital-system/tests/Feature/AnesthesiologistVisibilityAndWorkflowTest.php))**:
  * اجتياز `Laravel Pint` وتجميع قوالب Blade واجتياز اختبارات الميزة بنجاح 100%.

## Session log (2026-11-13 — المرحلة 194: تبسيط جدول سجل العمليات الجراحية `/surgeries`، إزالة تفاصيل ونتائج العملية المنسدلة وزر الإلغاء، وربط اسم المريض مباشرة بملف العملية الموحد Surgeries Index Streamlining & Redundant Accordion Removal)

### Done
- **إلغاء تفاصيل ونتائج العملية المنسدلة (`detailsRow`) وإلغاء زر الإلغاء المباشر ([resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php))**:
  * إزالة عامود `تشخيص / علاج` من جدول العمليات النشطة لتوفير المساحة وتجنب التكدس.
  * إزالة الصف المنسدل للأكورديون بالكامل (`#detailsRow{{ $surgery->id }}`) الذي كان يعرض تحاليل المختبر والأشعة والعلاجات وزر إلغاء العملية، حيث أصبحت كل هذه البيانات الرسمية تُدار حصرياً ومفصلة ومحمية بداخل ملف العملية وصالة العمليات الموحد (`/surgeries/{id}`).
  * إزالة الصف المنسدل من جدول العمليات المكتملة (`#detailsRowCompleted{{ $surgery->id }}`)، وحذف نموذج الإلغاء المخفي `#cancelSurgeryConfirmForm` والدالة `window.confirmCancelSurgery`، ليكون إلغاء العملية خاضعاً للحماية والتوثيق والسبب المحمي داخل شاشة تفاصيل العملية.
  * تحويل زر اسم المريض في جدولي العمليات النشطة والمكتملة من زر فتح أكورديون إلى رابط مباشر يفتح ملف وتفاصيل العملية (`route('surgeries.show', $surgery)`).
- **تحسين استعلامات الأداء في الكنترولر ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
  * إزالة التحميل المسبق للعلاقات غير المستخدمة بعد الآن في صفحة الفهرس (`surgeryTreatments`, `radiologyTests`, `labTests`) من استعلامي `activeSurgeriesQuery` و`completedSurgeriesQuery`، مما يرفع سرعة الاستجابة ويقلل استهلاك الذاكرة واستعلامات قاعدة البيانات.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php](file:///C:/get/hospital-system/tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php))**:
  * اجتياز `Laravel Pint` وتجميع Blade (`view:cache`)، واجتياز حزم الاختبارات الآلية بنجاح 100%.

## Session log (2026-11-12 — المرحلة 193: ربط مخزن وعُهدة العمليات (`مخزن عمليات رئيسي`) بملف العملية وصرف وإرجاع المواد، وتبويب العهدة الحصري للحساب المعين له المخزن في `/surgeries`, بالإضافة لملاحظات نوع العملية وإزالة الحقول المكررة من التخدير Operations Sub-Store Custody & Dispensing Integration)

### Done
- **تبسيط تبويب التخدير وإضافة ملاحظات نوع العملية في `/surgeries/{id}` ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
  * إزالة حقول تعيين أطباء التخدير المكررة وتوقيت التخدير من تبويب `detail_anesthesia` والإبقاء على نوع التخدير والوضعية وتصنيف ASA والملاحظات فقط.
  * إضافة حقل `surgical_notes` مباشر مع زر حفظ في تبويب نوع العملية (`detail_type` عبر `#detailTypeNotesForm`).
- **عزل مخزن العمليات وحل الأولوية لصالح `مخزن عمليات رئيسي` ([app/Models/Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php))**:
  * إضافة دوال `isOperationLocationName()`, `resolveOperationSubStores()`, و`getOperationSubStoreLocation()` بأولوية عهدة المستخدم (`user->location_id`) ثم **`مخزن عمليات رئيسي`** ثم صالات العمليات، مع منع تداخل `location_id` لصالات/مخازن العمليات مع استنتاج مخزن الطابق للتمريض (`getFloorSubStoreLocation()`).
- **الصرف المباشر والإرجاع من مخزن العمليات في `/surgeries/{id}` (`tab=detail_supplies`) ([routes/web.php](file:///C:/get/hospital-system/routes/web.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * استبدال جدول المستلزمات اليدوي السابق (`JSON`) بنظام صرف فعلي من عهدة مخزن العمليات (لكافة أصناف المخزن من مستلزمات وأدوية تخدير وغيرها) عبر `surgeries.supplies.dispense` مع خصم رصيد `StockBatch` وتسجيل `StockMovement` (`issue_to_patient`) وتوثيق الصرف في `SurgeryTreatment`، مع زر إرجاع وإلغاء صرف (`surgeries.supplies.reverse`) يعيد الرصيد ويسجل حركة `return` وتقييد الصلاحية بـ `detail_supplies = write`.
- **تبويب `مخزن وعُهدة العمليات` الحصري في `/surgeries` ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php), [app/Http/Controllers/StockTransferController.php](file:///C:/get/hospital-system/app/Http/Controllers/StockTransferController.php), [resources/views/stock-transfers/return.blade.php](file:///C:/get/hospital-system/resources/views/stock-transfers/return.blade.php))**:
  * قصر ظهور التبويب واللوحة (`#btnTabOrStore`, `#paneOrStore`) **حصراً على الحسابات المعين لها مخزن عمليات في إدارة المستخدمين (`user->location_id`)** (مثل حساب `موظف العمليات` المربوط بـ `مخزن عمليات رئيسي`) مع حجبه التام عن بقية الحسابات حتى عبر تمرير `?tab=or_store`.
  * توفير جرد الوجبات المتوفرة، بحث فوري، فلاتر التصنيف والنفاد والانتهاء، طلب تعزيز مواد، إرجاع عهدة للمخزن الرئيسي (`from_surgery=1`)، وسجل الحركات المباشر.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
  * اجتياز `Laravel Pint` (7 ملفات)، تجميع Blade (`view:cache`)، واجتياز 4 حزم اختبارية (`SurgeryTest`, `NursingFloorDispenseTest`, `UnifiedClinicalStationAndTabPermissionsTest`, `RolePermissionsMatrixTest`) بإجمالي `44 passed, 621 assertions`.

## Session log (2026-11-12 — المرحلة 192: توحيد ملف العملية وصالة العمليات `/surgeries/{id}` على نمط المحطة السريرية الموحدة، مصفوفة صلاحيات التبويبات المستقلة، وقفل حساب التخدير وترتيب المحتوى Unified Surgery Detail Workspace & Anesthesiologist Role Lock)

### Done
- **إصلاح حفظ موظف العمليات وإلغاء النماذج المتداخلة ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * إزالة جميع وسوم `<form>` المتداخلة وفصل كل تبويب بنموذج مستقل (`detailDiagnosisForm`, `detailAnesthesiaForm`, `detailTeamTimingForm`, `detailSuppliesForm`) لضمان عمل الحفظ لموظف العمليات والجراح والمخدر دون تعارض HTML.
- **مصفوفة صلاحيات ملف العملية وصالة العمليات وقفل حساب المخدر ([app/Models/SurgeryTabPermission.php](file:///C:/get/hospital-system/app/Models/SurgeryTabPermission.php), [app/Http/Controllers/SurgeryTabPermissionController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryTabPermissionController.php), [resources/views/surgeries/settings/tabs.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/settings/tabs.blade.php))**:
  * إضافة `DETAIL_TABS` (`detail_tests`, `detail_type`, `detail_diagnosis`, `detail_anesthesia`, `detail_team_timing`, `detail_supplies`, `detail_followups`) ومصفوفة الصلاحيات الافتراضية `detailDefaultMatrix()` للأدوار (`anesthesia`, `doctor`, `surgery_staff`, `resident`, `nurse`, `surgery_coordinator`, `receptionist`, `admin`) وعرضها في بطاقة مستقلة في إعدادات التبويبات.
  * تطبيق `isAnesthesiaUser()` و`resolveUserRolesForDetail()` للكشف الصارم عن حساب المخدر (دور `التخدير`/`anesthesia` أو `Doctor` بنوع/اختصاص تخدير) وحصر الكتابة فيه على تبويب `detail_anesthesia` فقط، ومنح الخادم `403` عند محاولة تعديل أي تبويب آخر.
- **إعادة بناء وتنظيم `/surgeries/{id}` ومحتوياته ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * تطبيق الرأس التنفيذي الموحد (`.clin-header-modern`) والتبويبات الأفقية المتجاوبة، والتوجيه الذكي لأول تبويب نشط حسب الدور (`detail_anesthesia` للمخدر، `detail_team_timing` لموظف العمليات، `detail_diagnosis` للجراح).
  * إعادة ترتيب محتويات التبويبات داخل بطاقات `.clin-section-card` و`.clin-field-card` متراصة متعددة الأعمدة مع أزرار تعبئة سريعة (`حالة مستقرة`، `عام`/`نصفي`/`موضعي`، `إفاقة مستقرة`)، وحصر تعيين طاقم التخدير وتوقيت العملية في `detail_team_timing` واقتصار `detail_anesthesia` على نوع التخدير والملاحظات، مع مزامنة `AnesthesiaStation` وإضافات التوقيت.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php))**:
  * اجتياز `Laravel Pint` (4 ملفات معدلة)، وترجمة Blade بنجاح (`php artisan view:cache`)، واجتياز 5 حزم اختبارية ذات صلة (`48 passed, 603 assertions`).

## Session log (2026-11-12 — المرحلة 191: بناء شبكة الأدوية MAR وفصل مخزن الطابق للمستلزمات فقط، وإصلاح نموذج حجز العملية `/surgeries/create` Medications MAR Grid, Floor Supplies Isolation & Surgery Create Form/TDZ Fix)

### Done
- **إصلاح عطل حجز العمليات واختيار الغرف في `/surgeries/create` ([resources/views/surgeries/create.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/create.blade.php))**:
  * استعادة وسم البداية المفقود للنموذج `<form action="{{ route('surgeries.store') }}" method="POST" id="surgeryForm" enctype="multipart/form-data">` مع `@csrf` وحقل `visit_id` المخفي قبل `#patientExecutiveBanner`.
  * تقديم تعريف متغيرات الغرف (`roomIdInput`, `selectedRoomType`, `selectedRoomFee`, `selectedRoomName`, `selectedRoomNumber`, `selectedRoomStayHours`) لأول `DOMContentLoaded` قبل استدعاء `$('#patient_id').trigger('change')` لتجنب خطأ JS TDZ (`Cannot access 'selectedRoomType' before initialization`) عند تمرير `?patient_id=`.
  * تأمين خصائص المريض بـ null-safe (`$selectedPatient?->user?->name` إلخ) وحفظ `old('surgical_operation_id')` و`old('room_id')` عند رجوع التحقق.
- **منظومة شبكة إعطاء الأدوية (MAR Grid) وفصل مخزن الطابق للمستلزمات فقط ([database/migrations/2026_11_12_004000_upgrade_surgery_treatments_for_mar_and_pharmacy.php](file:///C:/get/hospital-system/database/migrations/2026_11_12_004000_upgrade_surgery_treatments_for_mar_and_pharmacy.php), [app/Models/SurgeryTreatment.php](file:///C:/get/hospital-system/app/Models/SurgeryTreatment.php), [app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php), [resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php), [resources/views/surgery-stations/nursing/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/nursing/index.blade.php))**:
  * إضافة أعمدة MAR (`medicine_id`, `prescribed_by`, `route`, `frequency_mode`, `interval_hours`, `start_at`, `total_doses`) ودوال `FREQUENCY_PRESETS`, `calculateTotalDoses()`, `getDoseSlots()`, `toggleDoseSlot()`.
  * فك ارتباط تبويب الأدوية (`tab=treatments`) بمخزن الطابق نهائياً وحصر صرف مخزن الطابق بالمستلزمات الطبية فقط (`tab=supplies` و`dispenseFloorItem` عبر `Product::isSupply()`).
  * بناء مسار بحث أدوية الصيدلية (`clinical-station.medicines.search`) مع الإدخال الحر للأطباء والمقيمين، وبناء جدول MAR التفاعلي للتمريض بتأشير الجرعات بالوقت واليوم (`clinical-station.treatments.toggle-dose`) أو عند اللزوم (PRN) مع حماية القفل (30 دقيقة أو نفس الممرض) وصلاحية الإلغاء للأدمن.
- **التحقق الآلي وتنسيق الكود ([tests/Feature/SurgeryCreateModernWorkspaceTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryCreateModernWorkspaceTest.php), [tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php](file:///C:/get/hospital-system/tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php), [tests/Feature/NursingFloorDispenseTest.php](file:///C:/get/hospital-system/tests/Feature/NursingFloorDispenseTest.php))**:
  * مطابقة `Laravel Pint` (13 ملفاً) واجتياز 7 حزم اختبارية ذات صلة (`44 passed, 415 assertions`).

## Session log (2026-11-11 — توحيد جدول العلامات الحيوية وميزان السوائل في المحطة السريرية الموحدة، إزالة السوائل من `/surgeries/{id}`، وإلغاء مؤشرات الألم والوعي والشعيرات Unified Clinical Vitals & Fluids Single Flowsheet)

### Done
- **إزالة تبويب ونموذج السوائل من تفاصيل العملية ([resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * إزالة زر `#v-pills-fluids-tab` ولوحة اختيار `required_fluids` (`#v-pills-fluids`) من شاشة الطبيب/العملية مع الحفاظ على التوافق التام في قاعدة البيانات.
- **توحيد تبويب السوائل داخل تبويب العلامات الحيوية والفحص السريري ([app/Models/SurgeryTabPermission.php](file:///C:/get/hospital-system/app/Models/SurgeryTabPermission.php), [app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php))**:
  * إزالة `fluids` من `SurgeryTabPermission::TABS` و`defaultMatrix()` وتحديث تسمية `vitals` إلى `العلامات الحيوية والفحص والسوائل`، مع توجيه الروابط القديمة `?tab=fluids` تلقائياً إلى `vitals` وحذف السجلات القديمة في `surgery_tab_permissions`.
  * تقييد إدخال وتعديل حقول السوائل الـ 8 (`intake_iv_fluids`, `intake_oral`, `intake_blood`, `output_urine`, `output_drain`, `output_gtube_ng`, `output_vomiting`, `output_stool`) بكادر التمريض والأدمن فقط واجهةً وخادماً (`$canEditFluids`)، وجعلها للقراءة فقط للأطباء والمقيمين دون حجبهم بقفل الـ 30 دقيقة للعلامات الحيوية ودون مسح القيم المتبادلة عند الحفظ الجزئي أو الحذف عبر `syncStationFromReadings()`.
  * قصر عرض وتعديل **`ملاحظات الفحص السريري (Clinical Examination)`** على الأطباء والمقيمين والجراحين والأدمن فقط (`$canManageClinicalExam`) وإخفاؤها وحمايتها من التعديل أو المسح من شاشة كادر التمريض.
- **إعادة بناء الواجهة كجدول واحد موحد بدون تكرار ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * دمج نموذج الإدخال العلوي، حقول السوائل الـ 8، وسجل القراءات التاريخية داخل جدول أفقي واحد `#vitalsGridTable` (17 عموداً): الصف الأول للإدخال المباشر لـ `قراءة جديدة` (`is_new_reading=1`) أو تعديل قراءة سابقة (`[تعديل]`)، والصفوف التالية تعرض النتائج المحفوظة مباشرة أسفله من الأحدث للأقدم.
  * إخفاء شريط الحفظ السفلي المكرر (`حفظ التحديثات السريرية`) في تبويب `vitals` فقط والاكتفاء بزر الحفظ الموحد `#row1SaveBtn` في الصف الأول للجدول، مع إزالة أعمدة وحقول `مقياس الألم (pain_score)`، `مقياس الوعي (gcs)`، و`امتلاء الشعيرات (crt)` وحساب `صافي السوائل` لحظياً.
  * إزالة شريط وزر إنهاء المرحلة السريرية السفلي المنفصل عن العمليات (`/surgeries/waiting`) (`إنهاء محطة ما قبل العملية ونقل للصالة` / `إنهاء متابعة ما بعد العملية`) والاكتفاء بإدارة الدخول والصالة من قائمة انتظار العمليات وتخريج الغرفة من الرأس.
  * إعادة تنظيم وتصغير تبويب **السيرة المرضية (`tab=history`)** ضمن حاوية `#clinicalHistoryGrid` بمجموعتين مدمجتين (القصة والشكوى الحالية في صف ثنائي + السوابق الطبية والجراحية والدوائية والتحسس في صف رباعي على الشاشات الكبيرة مع أزرار تعبئة سريعة `لا يوجد` / `NKDA` عند وجود صلاحية الكتابة).
- **تنظيم تبويبي الخطة الطبية والمستلزمات مقابل الأدوية ([app/Models/Product.php](file:///C:/get/hospital-system/app/Models/Product.php), [app/Models/SurgeryTreatment.php](file:///C:/get/hospital-system/app/Models/SurgeryTreatment.php), [app/Models/SurgeryTabPermission.php](file:///C:/get/hospital-system/app/Models/SurgeryTabPermission.php), [app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php), [resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * إعادة تنظيم تبويب **الخطة الطبية (`tab=plan`)** داخل شبكة `#clinicalPlanGrid` (شبكة ثنائية للخطط والملاحظات + حقل `treatment_plan` + خلاصة توصية الجراح وإبراز المرحلة النشطة وزر تعبئة سريع `سليمة NAD`).
  * فصل **المستلزمات الطبية (`tab=supplies`)** عن **الأدوية وجدول الإعطاء (`tab=treatments`)** عبر إضافة عمود `item_type` في جدول `surgery_treatments` (`database/migrations/2026_11_11_235000_add_item_type_to_surgery_treatments_table.php`)، ودوال التصنيف [`Product::resolveItemType()`](file:///C:/get/hospital-system/app/Models/Product.php#L61-L108) (لتمييز `أدوية` / `مغذيات` عن `مستلزمات طبية` / `مواد طبية` / `تيوبات` / `خيوط` / إلخ) في المحطة السريرية ومخزن التمريض ([resources/views/surgery-stations/nursing/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/nursing/index.blade.php)).
  * تسجيل تبويب `supplies` في مصفوفة الصلاحيات (`admin`/`nurse` للكتابة، `doctor`/`resident`/`surgery_coordinator` للقراءة افتراضياً)، وإضافة مسار `clinical-station.supplies.dispense`، ومنع اختلاط الأصناف بين التبويبين في الخادم والواجهة، وحماية المستلزمات المصروفة من الحذف عند تعديل وصفات الجراح في [`SurgeonStationController`](file:///C:/get/hospital-system/app/Http/Controllers/SurgeonStationController.php) و[`SurgeryController`](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php).
- **التحقق الآلي وتنسيق الكود ([tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php](file:///C:/get/hospital-system/tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php), [tests/Feature/ClinicalVitalsGovernanceTest.php](file:///C:/get/hospital-system/tests/Feature/ClinicalVitalsGovernanceTest.php), [tests/Feature/NursingFloorDispenseTest.php](file:///C:/get/hospital-system/tests/Feature/NursingFloorDispenseTest.php))**:
  * مطابقة `Laravel Pint` لجميع الملفات المعدلة (`10 files`)، واجتياز 6 حزم اختبارية (`41 passed, 333 assertions`) بنسبة نجاح 100%.

## Session log (2026-11-11 — دمج تحديثات فرع `hr` (وحدة العناية المركزة والقلبية ICU/CCU، تسوية الخدج NICU، وصلاحيات وقسائم HR) في الفرع الرئيسي `main` مع الإبقاء على مسار العمل المحلي WAMP HR-to-Main Full Feature Merge Preserving Local Rules)

### Done
- **الدمج الآمن لتحديثات `origin/hr` على `main` (`c:\wamp64\www\hospital-system`)**:
  * دمج كافة التحديثات والميزات من فرع `hr` (حتى الكوميت `b2966c1`) مع استبعاد تعديلات مسار القواعد (`C:\get\hospital-system`) وقاعدة الرفع إلى `hr` فقط، للإبقاء التام على بيئة العمل المحلية في `c:\wamp64\www\hospital-system` وقواعد الرفع المباشر إلى `main`.
  * الحفاظ الكامل على خيار خروج **`خرج متوفي` (`deceased`)** في الطوارئ (`bf16ef7`) وإعادة تصميم شاشة الاستعلامات ثنائية الأعمدة متكاملةً مع بطاقة **`حجز عناية مركزة (ICU)`** في [resources/views/inquiry/create.blade.php](file:///C:/get/hospital-system/resources/views/inquiry/create.blade.php).
  * تشغيل المايجريشنات الـ 5 الجديدة (`nicu_milk_types`, `icu_beds`, `icu_admissions`, `ccu_services`, `ccu_stay_brackets`, `ccu_settings`, إلخ) وبذور `IcuBedSeeder`, `CcuTariffSeeder`, `RolesAndPermissionsSeeder` وتحديث كاش الصلاحيات بنجاح على قاعدة البيانات المحلية `hospital_pho`.
  * التحقق الآلي الشامل: اجتياز 21 حزمة اختبارية بإجمالي `168 passed, 1,186 assertions` عبر محاور `ICU/CCU`, `NICU`, `HR`, `Emergency`, `Inquiry`, `Surgery`, `Cashier`, `Consultants` ومطابقة `Laravel Pint` لجميع الملفات المتأثرة (`71 files`).

## Session log (2026-11-11 — المرحلة 188: إضافة خيار خروج "خرج متوفي" (`deceased`) في محطة الطوارئ وسجل الحالات والتقرير الطبي والتحقق Emergency Deceased Discharge Option, Reports & Financial Clearance Parity)

### Done
- **الكنترولر ومنطق الخروج ([app/Http/Controllers/EmergencyController.php](file:///C:/get/hospital-system/app/Http/Controllers/EmergencyController.php))**:
  * إضافة `deceased` لقواعد التحقق في `EmergencyController::discharge` بنفس شرط إبراء الذمة المالية (`getUnpaidAmount()`) المطبق على بقية أنواع الخروج.
  * ضبط الوجهة الافتراضية (`خرج متوفي`) والتوصيات الافتراضية (`توثيق حالة الوفاة واستكمال الإجراءات الرسمية`) وشارة التسمية (`خرج متوفي`) بدلاً من التوصية الافتراضية الخاصة بالعيادة الخارجية.
- **الواجهات وسجل الحالات والتقارير ([resources/views/emergency/show.blade.php](file:///C:/get/hospital-system/resources/views/emergency/show.blade.php), [resources/views/emergency/index.blade.php](file:///C:/get/hospital-system/resources/views/emergency/index.blade.php), [resources/views/emergency/medical-report.blade.php](file:///C:/get/hospital-system/resources/views/emergency/medical-report.blade.php), [resources/views/emergency/verify-report.blade.php](file:///C:/get/hospital-system/resources/views/emergency/verify-report.blade.php))**:
  * إضافة زر `خرج متوفي` داخل قائمة تسجيل الخروج في محطة كشف الطوارئ (`emergency.show`) مع رسالة تأكيد وشارة حالة داكنة مميزة بعد التسجيل.
  * دعم شارة وتمييز صفوف حالات `deceased` في سجل الطوارئ (`emergency.index`) والتقرير الطبي للطوارئ وصفحة التحقق عبر رمز QR.
- **الاختبارات الآلية ([tests/Feature/EmergencyWorkflowTest.php](file:///C:/get/hospital-system/tests/Feature/EmergencyWorkflowTest.php), [tests/Feature/EmergencyFinancialClearanceAndCancellationTest.php](file:///C:/get/hospital-system/tests/Feature/EmergencyFinancialClearanceAndCancellationTest.php))**:
  * إضافة اختبار `test_emergency_discharge_deceased_workflow_and_renders_across_views` والتحقق من منع `deceased` عند وجود مستحقات غير مدفوعة، واجتياز حزمة اختبارات الطوارئ بالكامل (`25 passed, 139 assertions`).

## Session log (2026-11-11 — المرحلة 189: منظومة التسعير الديناميكي وشرائح الإقامة للضمان، شمول التحاليل والأشعة بالباقة بنقرة زر، الكاشير والحسابات والمطالبات، ونقل التشخيص الأولي و ICD-10 لداخل إضبارة الطبيب CCU Dynamic Tariffs, Official MOH Brackets, Bundling & Accounting Suite)

### Done
- **نقل التشخيص السريري الأولي، ICD-10 الثنائي اللغة، ومقياس GCS لداخل إضبارة الطبيب**:
  * إزالة حقول التشخيص الأولي، ICD-10، و GCS من استمارة استقبال وحجز الاستعلامات (`resources/views/icu/admit.blade.php` و `IcuAdmissionController@store`) وجعلها خاصة بالطبيب داخل إضبارة العناية (`resources/views/icu/show.blade.php`) عبر البحث الحي الثنائي اللغة (`route('doctor.icd10.search')`) ومسار `ccu.admissions.initial-diagnosis.update` (`CcuCareController@updateInitialDiagnosis`).
- **قاعدة البيانات والنماذج والتحكم الديناميكي للتسعير والشرائح (`2026_11_11_100000_create_ccu_billing_and_tariffs_tables.php`)**:
  * جداول `ccu_services`, `ccu_stay_brackets`, `ccu_settings`, `ccu_service_logs`, `ccu_payments` وتحديث `icu_admissions`, `icu_medical_orders`, `ccu_consultations` مع بذرة التسعيرات الرسمية `CcuTariffSeeder` وشاشة التحكم الديناميكي `/ccu/services` (`CcuServiceController`).
  * دعم باقات الضمان (التنفس الصناعي 750,000 د.ع، الأكسجين 500,000 د.ع) وتدرج شرائح الإقامة الرسمية وسقف الـ 30 يوماً السنوية عبر المريض التراكمي، بالإضافة للتسعيرة النقدية المخصصة (باقة شاملة أو سعر سرير منفصل `cash_bed_only_separate` + خدمات مضافة `CcuService::TYPE_ADDON`).
- **محرك الفوترة وشمول التحاليل/الأشعة/الاستشارات بنقرة زر (`CcuBillingService`)**:
  * إظهار التحاليل والأشعة والاستشارات المشمولة بالباقة بمبلغ `0` د.ع للمريض مع حفظ `original_price` في `bundled_reference_total` وتبديل فوري بنقرة زر بين `bundled` و `extra` (`cashier.ccu.orders.toggle-billing`, `cashier.ccu.consultations.toggle-billing`).
- **الكاشير، كشوفات الحسابات، المطالبات، التحليلات، والخزينة (`CcuCashierController`, `AccountantController@ccuAnalytics`, `HealthInsuranceClaimController`, `HospitalTreasuryController`)**:
  * شاشات تسديد وكشوفات وإيصالات حرارية وتفصيلية (`resources/views/cashier/ccu/*`)، تبويب `#tab-ccu` في الكاشير الموحد، تحليلات العناية `/accountant/ccu-analytics`، تتبع القيمة الأصلية للباقات وتحصيل الضمان والباقي بذمة الضمان (`hia_remaining_balance`) في مطالبات الضمان والخزينة.
- **التحقق الآلي وتنسيق الكود**:
  * اجتياز 31 اختباراً (213 تأكيداً) عبر `NicuInsuranceSettlementAndClaimsTest`, `IcuModuleTest`, `CcuClinicalSuiteTest`, `CcuBillingAndCashierTest` مع توافق كامل لـ Laravel Pint.

## Session log (2026-11-10 — المرحلة 188: بناء وتكامل منظومة التوجيهات السريرية المثبتة، جدول الأدوية والمضخات MAR، الاستشارات التخصصية، والمرفقات وتخطيط القلب CCU Clinical Care Suite)

### Done
- **التوجيهات السريرية المثبتة في رأس الإضبارة (Pinned Clinical Directives)**:
  * بناء صندوق التوجيهات السريرية المشددة في أعلى ملف المريض ([resources/views/icu/show.blade.php](file:///C:/get/hospital-system/resources/views/icu/show.blade.php)) يبرز أوامر الاستشاري العاجلة مع اسم الطبيب والتوقيت وزر تثبيت/إلغاء التثبيت 📌 بنقرة واحدة عبر نموذج ([app/Models/CcuDoctorNote.php](file:///C:/get/hospital-system/app/Models/CcuDoctorNote.php)).
- **جدول إعطاء وجرعات الأدوية ومضخات التسريب الوريدي (MAR Flowsheet)**:
  * تبويب مخصص `#medicationTabPane` لجدولة الأدوية بالساعة (كل 4، 6، 8، 12، 24 ساعة) ودعم مضخات التسريب الوريدي المستمر (Syringe Pumps) مع معدل الضخ (Infusion Rate) وتحديد مسار الإعطاء (IV, Infusion, IM, NG Tube, Nebulizer, SC).
  * زر التمريض السريع "توثيق الإعطاء الآن" مع توثيق الممرض وتحديث الموعد القادم آلياً، وشارات التنبيه الذكية (🔴 متأخر Overdue، 🟡 مستحق الآن Due Now، 🟢 القادم Upcoming) عبر نموذج ([app/Models/CcuMedicationSchedule.php](file:///C:/get/hospital-system/app/Models/CcuMedicationSchedule.php)).
- **استشارات الأطباء التخصصية (CCU Consultations Suite)**:
  * تبويب مخصص `#consultationsTabPane` لطلب الاستشارات السريرية الداخلية (مع اختيار استشاري المستشفى من القائمة) أو الخارجية، وتوثيق الأجور، ورد وتوصيات الاستشاري المعتمدة بنقرة زر عبر نموذج ([app/Models/CcuConsultation.php](file:///C:/get/hospital-system/app/Models/CcuConsultation.php)).
- **أرشيف المرفقات الطبية وتخطيط القلب (ECG & Medical Attachments)**:
  * تبويب مخصص `#attachmentsTabPane` لرفع وتخزين مستندات وتخطيط القلب ECG والتقارير الطبية والمفراس الخارجي ومعاينتها فورياً عبر نموذج ([app/Models/CcuAttachment.php](file:///C:/get/hospital-system/app/Models/CcuAttachment.php)).
- **المتحكم والمسارات والتحقق الآلي**:
  * بناء متحكم ([app/Http/Controllers/CcuCareController.php](file:///C:/get/hospital-system/app/Http/Controllers/CcuCareController.php)) ومجموعة مسارات `ccu.*` في ([routes/web.php](file:///C:/get/hospital-system/routes/web.php)).
  * حزمة اختبارات آلية جديدة شاملة ([tests/Feature/CcuClinicalSuiteTest.php](file:///C:/get/hospital-system/tests/Feature/CcuClinicalSuiteTest.php)) نجحت بالكامل بنسبة 100% (`6 passed, 47 assertions`) إلى جانب استقرار حزمة ([tests/Feature/IcuModuleTest.php](file:///C:/get/hospital-system/tests/Feature/IcuModuleTest.php)) (`15 passed, 65 assertions`).

## Session log (2026-11-10 — المرحلة 187: تثبيت موضع الشاشة والتبويبات النشطة في إضبارة العناية المركزة، وبناء منظومة حجز تحاليل المختبر والأشعة والتصوير الطبي المتكاملة ICU Dossier Tab & Scroll State Persistence, Fast Lab & Bedside Radiology Suite)

### Done
- **تثبيت موضع التمرير واستمرارية التبويب النشط ومنع القفز لأعلى الصفحة ([resources/views/icu/show.blade.php](file:///C:/get/hospital-system/resources/views/icu/show.blade.php), [app/Http/Controllers/IcuController.php](file:///C:/get/hospital-system/app/Http/Controllers/IcuController.php))**:
  * بناء محرك حفظ واستعادة موضع التمرير اللحظي في `sessionStorage` بمفتاح فريد لكل مريض `icu_scroll_{id}` وتوقيت مرحلي يمنع إعادة القفز لبداية الصفحة عند حفظ المؤشرات الحيوية، ميزان السوائل، أوامر الأطباء، مهام الرعاية، أو إرسال التحاليل والأشعة.
  * دعم واستمرارية التبويب النشط عبر `_active_tab` ورابط الـ URL Hash وتخزينه في `sessionStorage`، مع إرساله تلقائياً عبر أي نموذج أو زر في الصفحة واستعادته عبر دالة `redirectBackWithActiveState($request, $tab, $fallbackUrl)`.
- **منظومة حجز تحاليل المختبر السريعة والمتكاملة مع المختبر المركزي ([resources/views/icu/show.blade.php](file:///C:/get/hospital-system/resources/views/icu/show.blade.php), [app/Http/Controllers/IcuController.php](file:///C:/get/hospital-system/app/Http/Controllers/IcuController.php), [app/Http/Controllers/LabStaffController.php](file:///C:/get/hospital-system/app/Http/Controllers/LabStaffController.php))**:
  * تبويب مخصص `#labTabPane` يشمل بحثاً ذكياً فوري Autocomplete من كتالوج المستشفى، أزرار إدراج سريعة بنقرة واحدة لتحاليل العناية الشائعة (ABG غازات دم شرياني، CBC، Electrolytes شوارد، RFT كلى، LFT كبد، PT/INR تخثر، Troponin، وزرع الدم Blood Culture)، صينية شرائح تفاعلية (Chips Tray)، وتحديد أولوية الفحص (Normal, Urgent, 🚨 STAT).
  * توليد الطلب المركزي في جدول `requests` بنوع `lab` وربطه بحقل `icu_admission_id`، وتوزيع الفحوصات في جدول `icu_medical_orders` مع مزامنة حالة الإنجاز والنتائج والمدى المرجعي آلياً عند إدخالها من قبل فني المختبر في `LabStaffController`.
- **منظومة حجز وتصوير الأشعة والسونار المتنقل بجانب السرير ([resources/views/icu/show.blade.php](file:///C:/get/hospital-system/resources/views/icu/show.blade.php), [app/Http/Controllers/IcuController.php](file:///C:/get/hospital-system/app/Http/Controllers/IcuController.php), [app/Http/Controllers/RadiologyStaffController.php](file:///C:/get/hospital-system/app/Http/Controllers/RadiologyStaffController.php))**:
  * تبويب مخصص `#radiologyTabPane` لحجز الأشعة النقالة Portable CXR، سونار بجانب السرير FAST/Abdomen، إيكو القلب Bedside Echo، ومفراس الدماغ CT Brain مع حقل للملاحظات السريرية ودواعي الفحص.
  * توليد الطلب المركزي في `requests` بنوع `radiology` وتحديد التصنيف الفرعي تلقائياً، ومزامنة تقرير الطبيب الشعاعي آلياً مع إضبارة العناية عند اعتماده في `RadiologyStaffController`.
- **المايجريشن والنماذج والعلاقات البرمجية (`database/migrations/2026_11_10_115143_add_icu_admission_id_to_requests_and_enrich_icu_orders.php`, [app/Models/Request.php](file:///C:/get/hospital-system/app/Models/Request.php), [app/Models/IcuAdmission.php](file:///C:/get/hospital-system/app/Models/IcuAdmission.php), [app/Models/IcuMedicalOrder.php](file:///C:/get/hospital-system/app/Models/IcuMedicalOrder.php))**:
  * إضافة حقل `icu_admission_id` إلى جدول `requests`.
  * إثراء جدول `icu_medical_orders` بحقول الفحوصات والأسعار والمجموعات والنتائج والمرفقات ومسؤول الطلب.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/IcuModuleTest.php](file:///C:/get/hospital-system/tests/Feature/IcuModuleTest.php))**:
  * توسيع الاختبارات إلى 15 اختباراً آلياً تشمل اختبارات رندرة تبويبات المختبر والأشعة، حجز التحاليل بأولوية STAT، حجز الأشعة والسونار، وثبات موضع التبويب النشط بنسبة نجاح 100% (`15 passed, 65 assertions`).

## Session log (2026-11-10 — المرحلة 186: ربط وتكامل حجز العناية المركزة في الاستعلامات وزيارات المرضى، وتخصيص استلام المريض الفوري ICU Inquiry Admission Integration, Pre-selected Patient Handoff & Reception Booking Card Suite)

### Done
- **تكامل شاشة الاستعلامات `inquiry.create` وزيارات المرضى `visits.edit`**:
  * إضافة بطاقة خدمة "حجز عناية مركزة (ICU)" في شاشة إنشاء طلبات الاستعلامات ([resources/views/inquiry/create.blade.php](file:///C:/get/hospital-system/resources/views/inquiry/create.blade.php)) وشاشة الزيارة ([resources/views/visits/edit.blade.php](file:///C:/get/hospital-system/resources/views/visits/edit.blade.php)) بنفس الهوية البصرية لبطاقات العمليات والطوارئ والخدج (`fa-heartbeat`, `text-danger`).
  * تمرير رقم المريض `?patient_id={id}` تلقائياً للرابط `icu.admissions.create`.
- **تطوير واجهة الاستقبال وحجز سرير العناية ([resources/views/icu/admit.blade.php](file:///C:/get/hospital-system/resources/views/icu/admit.blade.php), [app/Http/Controllers/IcuAdmissionController.php](file:///C:/get/hospital-system/app/Http/Controllers/IcuAdmissionController.php))**:
  * استلام فوري للمريض المحول من الاستعلامات مع تنزيل بطاقة بياناته آلياً وإخفاء حقل البحث الأولي مع إمكانية التغيير بنقرة زر.
  * قراءة حالة الضمان بدقة وإلغاء الافتراض الخاطئ لمسار الضمان لمرضى الكاش غير المشمولين، وإظهار تفاصيل الضمان فقط للمرضى المشمولين فعلياً.
  * استدعاء علاقة `healthInsuranceCategory` مع `user` في `IcuAdmissionController@create`.
- **مصفوفة الصلاحيات والحوكمة ([database/seeders/RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php), [app/Http/Controllers/InquiryController.php](file:///C:/get/hospital-system/app/Http/Controllers/InquiryController.php))**:
  * إسناد صلاحيتي `view icu radar` و `admit icu patients` لدوري الاستعلامات `receptionist` و `inquiry_staff`.
  * شمول صلاحيات العناية في مصفوفة السماح لدخول الاستعلامات `InquiryController@create`، مع حماية استدعاءات أدوار السونار والإيكو المفقودة بـ `try/catch`.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/IcuModuleTest.php](file:///C:/get/hospital-system/tests/Feature/IcuModuleTest.php))**:
  * توسيع الاختبارات إلى 11 اختباراً آلياً تشمل اختبار رندرة بطاقة العناية المركزة في الاستعلامات، واختبار تسكين المريض المحول بـ `patient_id` بنجاح 100% (`11 passed, 43 assertions`).

## Session log (2026-11-10 — المرحلة 185: بناء وحدة العناية المركزة والحرجة المتكاملة: رادار الأسرة الحي، شيت المؤشرات بالساعة، ميزان السوائل، وأوامر الطبيب ورعاية التمريض ICU / CCU Comprehensive Critical Care Suite)

### Done
- **قاعدة البيانات وهيكلية الجداول (`database/migrations/2026_11_10_095152_create_icu_beds_and_admissions_tables.php`)**:
  * جدول `icu_beds`: إدارة أسرة العناية (رقم السرير، نوعه: عام / عزل ضغط سالب / عناية قلبية CCU / عناية عصبية Neuro، الغرفة، الطابق، الحالة التشغيلية، السعر اليومي).
  * جدول `icu_admissions`: سجل دخول وتسكين المريض بالعناية، التشخيص السريري، ICD-10، مقياس الوعي الأولي GCS، جهاز التنفس، العزل، والتأمين.
  * جدول `icu_vital_signs`: شيت العلامات الحيوية بالساعة (GCS، النبض، الضغط، MAP المحسوب آلياً، SpO2، الحرارة، سكر الدم، إعدادات جهاز التنفس Mode، FiO2، PEEP).
  * جدول `icu_fluid_balances`: ميزان السوائل بالساعة (الداخل: محاليل، دم، تغذية، علاجات / الخارج: بول، مفارغ جراحية، رايل، مفرزات، واحتساب الصافي Net Balance تلقائياً).
  * جدول `icu_medical_orders`: أوامر الطبيب الاستشاري السريرية بأولويات (`routine`, `urgent`, `stat`) ومتابعة تنفيذها من الكادر.
  * جدول `icu_care_schedules`: جدول مهام الرعاية التمريضية بالساعة (تقليب، سحب مفرزات، تفريغ كيس البول).
- **النماذج والعلاقات البرمجية ([app/Models/IcuBed.php](file:///C:/get/hospital-system/app/Models/IcuBed.php), [app/Models/IcuAdmission.php](file:///C:/get/hospital-system/app/Models/IcuAdmission.php), [app/Models/IcuVitalSign.php](file:///C:/get/hospital-system/app/Models/IcuVitalSign.php), [app/Models/IcuFluidBalance.php](file:///C:/get/hospital-system/app/Models/IcuFluidBalance.php), [app/Models/IcuMedicalOrder.php](file:///C:/get/hospital-system/app/Models/IcuMedicalOrder.php), [app/Models/IcuCareSchedule.php](file:///C:/get/hospital-system/app/Models/IcuCareSchedule.php))**:
  * الفرز الرقمي المتسلسل للأسرة `scopeOrderByNumber()`.
  * شارات وأنماط الأنواع والحالات (`type_badge_class`, `status_badge_class`, `gcs_badge_class`).
  * محرك الاحتساب التلقائي لصافي السوائل والضغط الشرياني الوسطي MAP.
  * إضافة تصنيف `'icu' => 'إيراد العناية المركزة (ICU)'` في [app/Models/FinancialTransaction.php](file:///C:/get/hospital-system/app/Models/FinancialTransaction.php).
- **المتحكمات والمسارات البرمجية ([app/Http/Controllers/IcuController.php](file:///C:/get/hospital-system/app/Http/Controllers/IcuController.php), [app/Http/Controllers/IcuAdmissionController.php](file:///C:/get/hospital-system/app/Http/Controllers/IcuAdmissionController.php), [app/Http/Controllers/IcuBedController.php](file:///C:/get/hospital-system/app/Http/Controllers/IcuBedController.php))**:
  * رادار العناية الحية (`/icu`): إحصائيات KPI الحية للأسرة وأجهزة التنفس، شبكة الكروت التفاعلية مع تنبيهات مستوى الوعي GCS.
  * إضبارة مريض العناية (`/icu/admissions/{id}`): شيت الفلوشيت بالساعة، ميزان السوائل In/Out، أوامر الطبيب، وتخريج المريض وتحويل السرير للتعقيم.
  * تسجيل الدخول وحجز السرير (`/icu/admissions/create`): تسكين المريض، تحديد السرير والطبيب والتشخيص الأولي.
  * إدارة وتوزيع الأسرة (`/icu/beds`): متابعة التعقيم والصيانة وإضافة أسرّة جديدة.
- **واجهات المستخدم والقائمة الجانبية ([resources/views/icu/radar.blade.php](file:///C:/get/hospital-system/resources/views/icu/radar.blade.php), [resources/views/icu/show.blade.php](file:///C:/get/hospital-system/resources/views/icu/show.blade.php), [resources/views/icu/admit.blade.php](file:///C:/get/hospital-system/resources/views/icu/admit.blade.php), [resources/views/icu/beds/index.blade.php](file:///C:/get/hospital-system/resources/views/icu/beds/index.blade.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * تصميم عصري متناسق مع شاشات الخدج والعمليات، إبراز حالات الأسرة بالألوان، وإدراج قسم العناية المركزة في القائمة الجانبية بشارة عدد المرضى الراقدين.
- **الصلاحيات وحزمة الاختبارات الآلية الشاملة ([database/seeders/RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php), [tests/Feature/IcuModuleTest.php](file:///C:/get/hospital-system/tests/Feature/IcuModuleTest.php))**:
  * 8 صلاحيات دقيقة وأدوار `icu_doctor` و `icu_nurse`.
  * 7 اختبارات ميزة آلية تغطي الدخول، الرادار، العلامات، السوائل، الأوامر، والتخريج بنسبة نجاح 100% (`7 passed, 30 assertions`).

## Session log (2026-11-10 — المرحلة 184: منظومة تسوية وتفكيك الضمان الصحي الشاملة للخدج: المختبر والأشعة والاستشارات والحاضنة بالتوازي وفق الفئة E NICU Comprehensive Insurance Settlement & Co-payment Engine)

### Done
- **تفكيك وحساب تسوية الضمان للخدج وفق الفئة E ([app/Services/NicuBillingService.php](file:///C:/get/hospital-system/app/Services/NicuBillingService.php))**:
  * اعتماد الفئة E رسمياً لكافة الخدج المشمولين بالضمان (`is_insured`):
    - الفحوصات المخبرية (`lab`): مساهمة الضمان 75% وتحمل المريض 25% للفحوصات المشمولة بالضمان (`is_hi_active`).
    - الفحوصات الإشعاعية (`radiology`): مساهمة الضمان 75% وتحمل المريض 25% للفحوصات المشمولة.
    - استشارات الأطباء (`consultations`): الاستشارات الداخلية مشمولة بنسبة 90% ضمان و 10% مريض، والاستشارات الخارجية غير مشمولة (100% كاش على المريض).
    - الفحوصات والخدمات غير المشمولة: 100% كاش على المريض و 0% على الضمان مع بيان شارة صريحة.
    - أجور الحاضنة اليومية: احتسابها وفق الفئة E ومصفوفة عمر الحمل الرسمية للوزارة.
  * تجميع المجاميع الكبرى: `insurance_grand_total` (إجمالي مطالبة الضمان = حاضنة + مختبر + أشعة + استشارات داخلية)، و `patient_grand_total` (حصة المريض المستحقة)، وتحديث الحجز تلقائياً في قاعدة البيانات.
- **تحديث كشف الحساب التراكمي للخدج ([resources/views/nicu/statement.blade.php](file:///C:/get/hospital-system/resources/views/nicu/statement.blade.php))**:
  * إضافة أعمدة التغطية المعتمدة ومساهمة الضمان وتحمل المريض كاش في جدول الاستشارات (Section 2) وجدول الفحوصات المخبرية والأشعة (Section 3).
  * إضافة سطور مجاميع مفككة للمختبر والأشعة والاستشارات.
  * تفكيك بطاقة الخلاصة المالية النهائية لتبين بدقة مساهمات الضمان وحصص المريض في الحاضنة والمختبر والأشعة والاستشارات، وإجمالي مطالبة الضمان، وحصة المريض، والمسدد، والفائض المستحق للإرجاع إن وجد.
- **تحديث واجهة تسديد الكاشير الموحدة للخدج ([resources/views/cashier/nicu/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu/payment-form.blade.php))**:
  * قراءة وتحديث أرقام شريط الموقف المالي العلوي وصندوق التسديد الذكي لحساب المتبقي الحقيقي للدفع دون مطالبة ولي الأمر بأي مبالغ يغطيها الضمان.
  * إضافة أعمدة وشارات التغطية ومساهمات الضمان وتحمل المريض في جداول التحاليل والأشعة والاستشارات مع سطر المجاميع المفككة لكل قسم.
- **تحديث شيت كشوفات ومطالبات الضمان للخدج ([resources/views/cashier/nicu-statements.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-statements.blade.php))**:
  * تحديث عمود تفصيل البنود لإبراز مساهمة الضمان بالحاضنة والمختبر والأشعة والاستشارات، ومطابقة مطالبة الضمان الشاملة.
- **تحديث وصل التسوية والخدمات التفصيلي الحراري/A4 ([resources/views/cashier/nicu-itemized-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-itemized-print.blade.php))**:
  * إدراج تفكيك نسب ومبالغ الضمان والمريض تحت كل تحليل وفحص أشعة واستشارة، وتحديث صندوق الموقف المالي الشامل.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/NicuInsuranceSettlementAndClaimsTest.php](file:///C:/get/hospital-system/tests/Feature/NicuInsuranceSettlementAndClaimsTest.php))**:
  * 5 اختبارات ميزة آلية تغطي: الاحتساب التلقائي لأيام الرقود، مصفوفة عمر الحمل، انعكاس المطالبات في الخزينة المركزية، كشوفات الكاشير، وتفكيك مساهمة الضمان والمريض في التحاليل والاستشارات وشاشتي الكشف والكاشير بنجاح 100% (`5 passed, 35 assertions`).

## Session log (2026-11-09 — المرحلة 183: ضبط الفرز الرقمي المتسلسل للحاضنات من 1 إلى 16 والتكامل التام بين حاضنات العناية وكبسولات العلاج الضوئي NICU Natural Numerical Sequence Sorting & Dynamic Phototherapy/Capsule Suite)

### Done
- **الفرز الرقمي الصحيح للحاضنات ([app/Models/Incubator.php](file:///C:/get/hospital-system/app/Models/Incubator.php))**:
  * معالجة مشكلة الفرز الأبجدي للنصوص (Lexicographical Sorting) الناتج عن تخزين `incubator_number` كـ `VARCHAR` والذي كان يفرز الأرقام كـ 1 ثم 10 ثم 11 قبل 2.
  * إضافة الـ Scope المخصص `scopeOrderByNumber($query, $direction = 'asc')` المتوافق مع محرك MySQL ومحرك SQLite للاختبارات الآلية.
- **تطبيق الفرز الرقمي المتسلسل عبر كافة شاشات ومسارات الحاضنات**:
  * شاشة لوحة الخدج الرئيسية `/nicu` عبر [app/Http/Controllers/NicuController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuController.php).
  * شاشة حجز واستقبال الخديج `/incubator-reservations/create` عبر [app/Http/Controllers/IncubatorReservationController.php](file:///C:/get/hospital-system/app/Http/Controllers/IncubatorReservationController.php) وقوائم الاختيار التفاعلية.
  * شاشة إدارة الحاضنات `/incubators` عبر [app/Http/Controllers/IncubatorController.php](file:///C:/get/hospital-system/app/Http/Controllers/IncubatorController.php).
- **الحوكمة والتكامل التام لنوعي الحاضنات (حاضنة اعتيادية مقابل كبسولة علاج ضوئي)**:
  * دعم التوافق المزدوج مع نوعي `capsule` و `phototherapy` في `getTypeNameAttribute`, `getTypeColorAttribute`, `getTypeIconAttribute`, و `scopeOfType`.
  * تحديث إحصائيات KPIs التراكمية في شاشة الخدج الرئيسية وشاشة إدارة الحاضنات لتظهر بدقة عدد الحاضنات وعدد كبسولات العلاج الضوئي بشكل مستقل.
  * تحديث تبويبات شاشة `/incubators` ومرشحات شاشة `/incubator-reservations/create` لفرز وفلترة النوعين بسلاسة مع الحفاظ التام على أرقام وسجلات الحاضنات القائمة في المستشفى.
  * مزامنة كبسولات العلاج الضوئي (15 و 16) بقاعدة البيانات وفقاً لـ `IncubatorSeeder`.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/IncubatorSortingAndTypeTest.php](file:///C:/get/hospital-system/tests/Feature/IncubatorSortingAndTypeTest.php))**:
  * 4 اختبارات ميزة آلية تغطي: الفرز الرقمي المتسلسل، شاشة NICU ومحاكاة الـ 16 حاضنة، حجز واستقبال الخديج وفلترة العلاج الضوئي، وتبويبات إدارة الحاضنات بنجاح 100% (`4 passed, 13 assertions`).

## Session log (2026-11-08 — المرحلة 182: إصلاح حفظ تفكيك الرواتب، تفكيك مصفوفة صلاحيات HR، قالب وقسيمة الراتب الفردية وطباعة باجة الموظف الرسمية HR Payroll Model Fix, Granular Permissions Matrix, Official Employee ID Badge & Individual Payslip Suite)

### Done
- **إصلاح الخلل البرمجي في موديل الرواتب ([app/Models/HrPayroll.php](file:///C:/get/hospital-system/app/Models/HrPayroll.php), [database/migrations/2026_10_06_054330_add_new_fields_to_hr_payrolls_table.php](file:///C:/get/hospital-system/database/migrations/2026_10_06_054330_add_new_fields_to_hr_payrolls_table.php))**:
  * إضافة حقول التفكيك المالي المفقودة إلى `$fillable` و `$casts` في موديل `HrPayroll`: (`overtime_amount`, `absence_deduction`, `loan_deduction`, `social_security_amount`, `tax_amount`, `payment_method`, `is_paid`, `paid_at`).
  * تصحيح ميغريشن الرواتب الداعم ليقوم بفحص وإضافة الأعمدة في حال عدم وجودها لمنع أخطاء بيئات النشر والاختبارات الآلية في SQLite.
  * إضافة دوال المساعدة الحسابية `total_earnings` و `total_deductions` وعلاقة `payrollCycle()`.
- **تفكيك مصفوفة صلاحيات الموارد البشرية وحوكمة الأدوار (Granular HR Permissions Matrix & Roles Governance)**:
  * تفكيك الصلاحيات العريضة إلى 19 صلاحية دقيقة ومستقلة:
    1. `view hr`: دخول لوحة وقسم الموارد البشرية.
    2. `view employees`: استعراض سجل وإضبارات الموظفين.
    3. `create employees`: إضافة موظف جديد لبيانات المستشفى.
    4. `edit employees`: تعديل بيانات وإعدادات رواتب الموظفين.
    5. `delete employees`: حذف أو أرشفة ملف موظف.
    6. `print employee badges`: طباعة باجة وبطاقة التعريف للموظف (ID Badge).
    7. `manage hr documents`: رفع وإدارة وحذف المستمسكات الرسمية للموظف.
    8. `manage hr actions`: تسجيل وإلغاء العقوبات والمكافآت والإنذارات.
    9. `view hr payroll`: استعراض شيتات وسجلات الرواتب والأجور.
    10. `create hr payroll`: توليد مسير رواتب شهري جديد وتعديل المسودات.
    11. `approve hr payroll`: الاعتماد النهائي لمسير الرواتب وترحيله للحسابات.
    12. `delete hr payroll`: حذف مسودة مسير الرواتب وإعادة الاستحقاقات.
    13. `print hr payslips`: طباعة قسائم الرواتب الفردية والشيت المركزي.
    14. `manage hr loans`: تسجيل وإلغاء السلف المالية للموظفين.
    15. `manage hr absences`: توثيق الغيابات والتأخيرات والانصراف المبكر.
    16. `manage hr overtime`: توثيق واحتساب ساعات ومبالغ العمل الإضافي.
    17. `manage hr shifts`: إدارة وتحديد أنواع الشفتات وأوقاتها.
    18. `manage hr schedules`: بناء وتعديل وتبادل جداول الروستر الأسبوعي.
    19. `manage hr settings`: إدارة حقول الإدخال الإجبارية وقوائم HR المنسدلة.
  * تحديث [database/seeders/RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php) وإسناد كافة الصلاحيات لدور مدير الموارد البشرية `hr_manager` والمدير `admin`.
  * تحديث [app/Http/Controllers/RoleManagementController.php](file:///C:/get/hospital-system/app/Http/Controllers/RoleManagementController.php) بإدراج قسم `hr` بالترتيب العام، ترجمة الصلاحيات بالشارات، وتصنيف دور `hr_manager`.
  * حماية كافة متحكمات الموارد البشرية عبر الميدلوير بالصلاحيات الدقيقة وتحديث القائمة الجانبية في [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php).
- **قالب وقسيمة الراتب الفردية للموظف (Individual Employee Payslip Print)**:
  * بناء شاشة طباعة قسيمة الراتب [resources/views/hr/payrolls/payslip.blade.php](file:///C:/get/hospital-system/resources/views/hr/payrolls/payslip.blade.php) ومسار `Route::get('payrolls/slip/{slip}/print')`.
  * جدول مالي مزدوج يفكك الاستحقاقات (الأساسي، المخصصات، الإضافي، المكافآت) مقابل الاستقطاعات (الغياب، العقوبات، السلف، الضمان، الضريبة)، وبطاقة صافي الراتب، ومساحة التواقيع الرسمية الثلاثية.
  * إضافة زر `[ 🖨️ قسيمة الراتب ]` في شيت الرواتب المركزي [resources/views/hr/payrolls/show.blade.php](file:///C:/get/hospital-system/resources/views/hr/payrolls/show.blade.php).
- **طباعة باجة وهوية الموظف الرسمية (Official Staff ID Badge / CR80 PVC Standard)**:
  * بناء قالب بطاقة الهوية [resources/views/hr/employees/badge.blade.php](file:///C:/get/hospital-system/resources/views/hr/employees/badge.blade.php) ومسار `Route::get('employees/{employee}/badge')` بالصلاحية `print employee badges`.
  * بطاقة مزدوجة (وجه أمامي وخلفي) بألوان الأقسام، الشعار، الصورة، كود الموظف، باركود مرئي، فصيلة الدم، هاتف الطوارئ، ترخيص المهنة، ومربع الختم الرسمي.
  * إضافة أزرار الطباعة في جدول الموظفين [resources/views/hr/employees/index.blade.php](file:///C:/get/hospital-system/resources/views/hr/employees/index.blade.php) والإضبارة [resources/views/hr/employees/show.blade.php](file:///C:/get/hospital-system/resources/views/hr/employees/show.blade.php).
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/HREmployeeTest.php](file:///C:/get/hospital-system/tests/Feature/HREmployeeTest.php))**:
  * اجتياز كامل الاختبارات بنسبة نجاح 100% (`8 passed, 38 assertions`).

## Session log (2026-11-08 — المرحلة 181: نظام تسديد وتصفية رسوم الخدج والحاضنات الموحد وإلغاء الأمانات والسلف بسلاسة واجهة العمليات الجراحية NICU Cashier Unified Folio & Payment Suite, Advance Deposit Deprecation & Inpatient Settlement Engine)

### Done
- **إلغاء مصطلحي "الأمانات" و"السلفة" وتوحيد المفهوم المالي تحت "التسديد" (Advance Deposit & Sulfa Deprecation)**:
  * إلغاء مسميات الأمانات والسلف نهائياً من كافة الواجهات، والإيصالات الحرارية، وسندات القبض، وجداول الكاشير.
  * توحيد المفاهيم المحاسبية في [app/Models/NicuPayment.php](file:///C:/get/hospital-system/app/Models/NicuPayment.php) والواجهات إلى: "تسديد رسوم الحاضنة"، "دفعة تسديد"، "تسديد وتصفية نهائية"، و"استرجاع فائض الرسوم".
  * تحديث صياغة السندات في [resources/views/cashier/nicu-receipt.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-receipt.blade.php)، [resources/views/cashier/nicu-receipt-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-receipt-print.blade.php)، و [resources/views/cashier/nicu-itemized-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-itemized-print.blade.php).
- **واجهة تسديد رسوم الخدج المستقلة الفاخرة (ستايل العمليات الجراحية الموحد) ([resources/views/cashier/nicu/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu/payment-form.blade.php))**:
  * بناء واجهة تسديد متكاملة وعريضة بديلة للمودال القديم الضيق:
    1. **بطاقة الحاضنة والإقامة**: اسم الطفل، رقم الحاضنة، باقة الخدمة الحالية، الطبيب المشرف، تاريخ وساعة الدخول، وعدد الأيام الفندقية وساعات الرقود المحسوبة حياً وفق نقطة 12:00 ظهراً وسماحية الـ 6 ساعات.
    2. **بطاقة ولي الأمر والتغطية المالية**: اسم المسؤول، رقم الملف، الهاتف، وشارة التغطية: `[ 🛡️ الضمان الصحي الوطني ]` (الفئة، عمر الحمل، ونسبة وتكلفة الضمان) أو `[ 💵 دفع نقدي كامل ]`.
    3. **شريط الأرقام المالية العلوي**: إجمالي التكلفة المعتمدة | المسدد سابقاً | المتبقي للتسديد أو فائض مستحق الإرجاع.
    4. **كشف البنود والخدمات السريرية المفصلة**:
       - جدول باقات الحاضنة اليومية والتنفس مع بيان حصة المريض والضمان.
       - جدول الفحوصات والتحاليل المخبرية للطفل وحالتها وتكلفتها.
       - جدول الفحوصات الإشعاعية والسونار وتكلفتها.
       - جدول استشارات الأطباء التخصصية وأتعابها.
       - جدول الأدوية وحليب وتغذية الخدج المستهلكة.
       - جدول سجل الدفعات المسددة سابقاً مع أزرار معاينة وطباعة الوصل لكل دفعة.
    5. **صندوق التسديد السفلي الذكي (Smart Payment Dock)**:
       - كبسولات التحكم المزدوجة Segmented Pills:
         * `[ 🟢 تسديد كامل المبلغ المتبقي ]` (تعبئة تلقائية للمبلغ المتبقي).
         * `[ 🟡 تسديد جزئي (دفعة مقبوضة الآن) ]` مع حقل إدخال المبلغ المقبوض وشارة احتساب المتبقي حياً ولحظياً، وأزرار اقتراح مبالغ سريعة (25k, 50k, 100k, 250k).
       - اختيار طريقة الدفع: نقداً (Cash) أو بطاقة إلكترونية (Card).
       - حقل الملاحظات وزر تأكيد التسديد الفاخر مع مؤشر تحميل لمنع التكرار.
    6. **قسم إرجاع الفائض المالي (Refund Section)**:
       - يظهر تلقائياً بوضوح عند وجود مبالغ مسددة تزيد عن التكلفة (كالخروج المبكر) لصرف الفائض وطباعة سند صرف واسترجاع رسمي بنقرة واحدة.
- **تحديث شاشة الكاشير الرئيسية وحذف المودالات القديمة ([resources/views/cashier/index.blade.php](file:///C:/get/hospital-system/resources/views/cashier/index.blade.php))**:
  * الإبقاء على تبويب الخدج في مكانه الطبيعي داخل الكاشير مع تحويل زر "قبض دفعة" المنبثق إلى زر مباشر: `[ 💳 تسديد الرسوم ]` ينقل الكاشير فوراً لصفحة التسديد الشاملة.
  * حذف المودالات المنبثقة القديمة (`#nicuDepositModal` و `#nicuRefundModal`) نهائياً لتنظيف الصفحة وخفتها.
- **مسارات وتحكم السيرفر ([routes/web.php](file:///C:/get/hospital-system/routes/web.php), [app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * مسار جديد: `GET /cashier/nicu/{reservation}/payment` باسم `cashier.nicu.payment.form`.
  * تابع `showNicuPaymentForm()` لتحميل كافة العلاقات السريرية والمالية عبر [`NicuBillingService`](file:///C:/get/hospital-system/app/Services/NicuBillingService.php).
  * تحديث `processNicuPayment()` لدعم التسديد الكامل والجزئي (دمج `custom_paid_amount`)، والتحقق من التصفية والاسترجاع، وتوثيق السندات المالية وسجل النشاطات بمسميات التسديد الصريحة.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/NicuCashierPaymentFormTest.php](file:///C:/get/hospital-system/tests/Feature/NicuCashierPaymentFormTest.php))**:
  * 6 اختبارات ميزة مخصصة تغطي: رندرة واجهة تسديد الخدج الجديدة بستايل العمليات، ربط جدول الكاشير، التسديد الكامل، التسديد الجزئي، إرجاع الفائض، وحظر غير المخولين بنجاح 100% (`6 passed, 21 assertions`).

## Session log (2026-11-08 — المرحلة 180: تفكيك وتفصيل صلاحيات الخدج، تخصيص حجز الاستعلامات، ومنظومة إدارة أنواع وتصنيفات حليب وتغذية الخدج Granular NICU Permissions Matrix, Reception Baby Admission Segregation & Dynamic NICU Milk/Formula Catalog Suite)

### Done
- **تفكيك وتفصيل صلاحيات قسم الخدج والحاضنات (Granular NICU Permissions Matrix)**:
  * تفكيك الصلاحيات العريضة إلى 14 صلاحية دقيقة:
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
  * تحديث [database/seeders/RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php) و [app/Http/Controllers/RoleManagementController.php](file:///C:/get/hospital-system/app/Http/Controllers/RoleManagementController.php) بإدراج وتصنيف الصلاحيات بأسمائها وشاراتها العربية.
- **حوكمة حجز الاستقبال وعزل الإجراءات السريرية (Reception Booking vs Clinical Segregation)**:
  * دور الاستعلامات (`receptionist`) محصور بإنشاء وإدارة حجز الحاضنة واستقبال الطفل (`create incubator reservations`, `view incubator reservations`, `manage incubator reservations`) وطباعة أسورة المعصم وكارت الحاضنة الشفاف (`print nicu cards`) وتوليد رابط الكاميرا (`manage nicu baby cam`).
  * الاستعلامات محظورة وممنوعة تماماً على صعيد السيرفر (403 Forbidden) عن أي إجراء سريري أو تمريضي (توثيق العلامات، طلب التحاليل، الأشعة، كتابة الوصفات، تعديل الملف السريري، أو إخراج الطفل).
  * حصر الصلاحيات السريرية بطبيب الخدج (`nicu_doctor`) والتمريضية بممرضة الخدج (`nicu_nurse`) دون استحداث أدوار جديدة والاعتماد التام على الأدوار الأساسية.
- **منظومة إدارة أنواع وتصنيفات حليب وتغذية الخدج (NICU Milk/Formula Dynamic Catalog)**:
  * إنشاء جدول ومايجريشن `nicu_milk_types` ([database/migrations/2026_11_08_102500_create_nicu_milk_types_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_08_102500_create_nicu_milk_types_table.php)) وموديل [app/Models/NicuMilkType.php](file:///C:/get/hospital-system/app/Models/NicuMilkType.php) يشمل تصنيفات التغذية، ترتيب العرض، وملاحظات الاستخدام السريري.
  * بناء متحكم [app/Http/Controllers/NicuMilkTypeController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuMilkTypeController.php) مع دعم عمليات CRUD والتبديل اللحظي عبر AJAX (`/nicu/milk-types/{id}/toggle`) وجلب القائمة النشطة JSON.
  * تصميم واجهة التبويبات المزدوجة في [resources/views/nicu/services/index.blade.php](file:///C:/get/hospital-system/resources/views/nicu/services/index.blade.php) للتنقل السلس بين `[ 🏷️ باقات وخدمات الحاضنات والتنفس ]` و `[ 🍼 قائمة وتصنيفات حليب وتغذية الخدج ]` مع مؤشرات إحصائية وجدول ومودالات الإضافة والتعديل.
  * ربط قائمة الحليب ديناميكياً داخل إضبارة الطفل [resources/views/nicu/show.blade.php](file:///C:/get/hospital-system/resources/views/nicu/show.blade.php) في داتا ليست الرضاعة السريعة (`quickFeedingTypes`) وتوثيق العلامات الحيوية، مع توفير رابط وصول مباشر `[ ⚙️ إدارة أنواع الحليب ]`.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/NicuGranularPermissionsAndMilkTypesTest.php](file:///C:/get/hospital-system/tests/Feature/NicuGranularPermissionsAndMilkTypesTest.php))**:
  * 5 اختبارات ميزة مخصصة تغطي: حظر الاستعلامات عن الإجراءات السريرية، تمكين طبيب وممرضة الخدج، دورة حياة إدارة أنواع الحليب والتبديل اللحظي، ورندرة الواجهات والتبويبات بنجاح 100% (`5 passed, 38 assertions`).

## Session log (2026-11-08 — المرحلة 179: قفل إضبارة الخديج عند الخروج للأرشفة والمطالعة فقط، أرشفة سجلات المرضى المخرجين، ونقل عمر الحمل ووزن الطفل لداخل الإضبارة NICU Discharged Baby Chart Lockdown, Medical Archive Segregation & Clinical Birth Profile Relocation)

### Done
- **قفل سجل وإضبارة الطفل الخديج عند خروجه (Zero-Mutation Discharged Chart Lockdown)**:
  * تحويل محطة الرعاية السريرية الشاملة (`/nicu/reservations/{reservation}`) عند خروج الطفل (`status === 'discharged'`) إلى وضع المطالعة والأرشفة فقط (`Read-Only Mode`).
  * إبراز بنر تحذيري تنفيذي فاخر في أعلى الصفحة يوضح تاريخ وتوقيت الخروج، نوع الخروج، وقفل كافة الإجراءات السريرية.
  * إخفاء نماذج التوثيق والإدخال عبر كامل التبويبات السريرية (العلامات الحيوية، سجل MAR، الاستشارات، المختبر، الأشعة، الوصفات، جداول الرعاية والتغذية، ملاحظات الطبيب الاختصاص، رفع المرفقات، وتبديل الباقات).
  * توسيع جداول السجلات والنتائج المؤرشفة بعرض الصفحة الكامل (`col-12`) لتقديم واجهة أرشيف سريرية ناصعة ومريحة، مع الحفاظ على إمكانية استعراض التقارير التاريخية، تحميل المرفقات، والطباعة.
  * حماية السيرفر الصارمة: حظر كافة عمليات الإضافة أو التعديل في [app/Http/Controllers/NicuController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuController.php) عبر 15 تابعاً سريرياً وإعادة التوجيه مع رسالة تنبيهية تمنع أي تلاعب.
- **قائمة السجلات وأرشفة المرضى (Discharged Babies Archive View)**:
  * في صفحة سجل وإضبارات الخدج ([resources/views/nicu/reservations/index.blade.php](file:///C:/get/hospital-system/resources/views/nicu/reservations/index.blade.php)): إضافة كبسولات تنقل علوية تفاعلية (`الأطفال بالحاضنات (الحالات النشطة)`، `السجلات وأرشفة المرضى`، `قيد الانتظار`، و`كافة الإضبارات`).
  * عزل الأطفال المخرجين تلقائياً ونقلهم إلى تبويب "السجلات وأرشفة المرضى" (`view=archived`) دون إشغال قائمة الحاضنات النشطة.
  * إضافة رابط وصول مباشر في القائمة الجانبية للنظام (`السجلات وأرشفة المرضى`) تحت قسم وحدة رعاية الأطفال والخدج (NICU) في [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php).
  * تخصيص زر المعاينة للأطفال المخرجين ليظهر بأيقونة العين كـ "معاينة إضبارة الطفل المؤرشفة (للاطلاع والمطالعة فقط)".
- **نقل عمر الحمل ووزن الطفل إلى داخل الإضبارة مع حوكمة القفل والتعديل المشروط**:
  * إزالة حقلي `عمر الحمل (gestational_age)` و`وزن الطفل (birth_weight)` من استمارة حجز الحاضنة وتسجيل الدخول الأولي ([resources/views/incubator_reservations/create.blade.php](file:///C:/get/hospital-system/resources/views/incubator_reservations/create.blade.php))، واستبدالهما بتنبيه سريري يوضح تسجيلهما لاحقاً بدقة داخل الإضبارة.
  * إضافة بطاقة سريرية بارزة "بيانات الولادة وعمر الحمل السريرية" داخل تبويب **"باقة الخدمة والتبديل (12:00 ظهراً)"** في [resources/views/nicu/show.blade.php](file:///C:/get/hospital-system/resources/views/nicu/show.blade.php) تعرض وزن الدخول وعمر الحمل ومستوى الخداجة بتصميم مقفل للحماية (`Locked Mode`).
  * توفير زر `[ ✏️ تعديل البيانات السريرية ]`؛ عند النقر يظهر تنبيه تأكيد SweetAlert2: *"هل أنت متأكد من تعديل هذه البيانات؟"*، وعند الموافقة يُفتح نموذج التعديل المباشر مع زر حفظ وزر إلغاء دون أي نوافذ منبثقة مشتتة.
  * عند خروج الطفل: تُقفل بطاقة البيانات السريرية نهائياً ويختفي زر التعديل.
  * مسار الحفظ (`POST /nicu/reservations/{reservation}/clinical-profile` في [routes/web.php](file:///C:/get/hospital-system/routes/web.php)) يقوم بتحديث البيانات، مزامنة الوزن الحالي إن لم يسجل، وإعادة احتساب تفاصيل البقاء وشرائح الضمان الصحي تلقائياً عبر `NicuBillingService`.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/NicuDischargedChartAndClinicalProfileTest.php](file:///C:/get/hospital-system/tests/Feature/NicuDischargedChartAndClinicalProfileTest.php))**:
  * 5 اختبارات ميزة مخصصة تغطي: عزل الحالات النشطة عن المؤرشفة في التبويبات، خلو شاشة التسجيل من الحقلين، التعديل والتثبيت داخل الإضبارة، قفل الواجهة السريرية للطفل المخرج، والحظر الصارم في السيرفر بنسبة نجاح 100% (`5 passed, 37 assertions`)، مع اجتياز كامل حزمة اختبارات الخدج العامة (`66 passed, 541 assertions`).

## Session log (2026-11-10 — المرحلة 187: إعادة هندسة ومطابقة واجهتي حجز وتعديل العمليات الجراحية بنظام بيئة العمل ثنائية الأعمدة والحفظ التلقائي للخدمات الإضافية في دليل المستشفى Surgery Create & Edit Split-Workspace & Auto-Persist Extra Services Catalog)

### Done
- **الحفظ التلقائي للخدمات الإضافية الجديدة في الدليل المرجعي العام للمستشفى ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
  * عند كتابة وإضافة أي خدمة إضافية مخصصة جديدة من قبل الكاشير لم تكن معرفة مسبقاً، يتم تلقائياً حفظها في جدول الدليل العام للمستشفى (`hospital_extra_services`) بالاسم والسعر الافتراضي وتصنيفها المحدد (فحوصات وسريرية، فندقية، إدارية، تزيين، غرامات).
  * ربط معرف الخدمة الجديد `service_id` بسجل العملية `surgery_extra_services` أو الطلب الطبي `visit_extra_services`.
  * تظهر الخدمة الجديدة تلقائياً وبشكل فوري دائم في القائمة المنسدلة الذكية (Autocomplete) والتصنيف الخاص بها لجميع العمليات والطلبات القادمة دون الحاجة لإعادة كتابتها يدوياً.
  * تحديث خيار الإضافة الحرة بالقائمة: `إضافة كخدمة جديدة: "..." (✨ سيتم حفظها في دليل الخدمات لتظهر تلقائياً بالمرات القادمة)`.
  * حل مشكلة المتغيرات غير المعرفة لنسب التحمل `$labCopay`, `$radCopay`, `$ecgCopay` في `CashierController::processSurgeryPayment`.
- **إزالة بطاقة الاعتماد والملخص الإضافية وإبقاء أزرار الحفظ المباشرة ([resources/views/surgeries/create.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/create.blade.php))**:
  * حذف بطاقة الملخص المالي الإضافية استجابةً لتوجيه المستخدم.
  * وضع أزرار الاعتماد والحفظ والإلغاء المباشرة أسفل عمود المعلومات الأيمن بوضوح كامل.
- **إعادة هندسة وتطوير واجهة تعديل العملية الجراحية بالكامل للمطابقة التامة مع شاشة الحجز ([resources/views/surgeries/edit.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/edit.blade.php))**:
  * **شريط هوية المريض التنفيذي العلوي (`#patientExecutiveBanner`)**: يعرض اسم المريض، رقم الملف، العمر، الجنس، الهاتف، بطاقة وشارة الضمان، وأزرار السجل الطبي الشامل وطباعة الإضبارة وشارة تعديل العملية #{{ $surgery->id }}.
  * **يمين الشاشة (`col-lg-5`)**: كارت موحد يضم مسار التغطية (ضمان أم كاش)، اختيار المريض، الطبيب الجراح، الطبيب المرسل، تاريخ العملية، ومسح/رفع ورقة التحويل بالسكانر، متبوعاً بأزرار الحفظ المباشرة `[ 💾 حفظ وتحديث حجز العملية الجراحية ]` و `[ ❌ إلغاء ]`.
  * **يسار الشاشة (`col-lg-7`)**: بالأعلى جدول العمليات الجراحية المطلوبة (`#collapseOperations`) مع دعم العمليات المتعددة والإضافية وزر `[ ➕ إضافة عملية أخرى ]`، وبالأسفل كارت اختيار الغرفة والإقامة السريرية (`#collapseRoomStay`) مع تبويبات الطوابق وأزرار الغرف الملونة.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/SurgeryCreateModernWorkspaceTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryCreateModernWorkspaceTest.php), [tests/Feature/SurgeryExtraServicesTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryExtraServicesTest.php), [tests/Feature/SurgeryCashierSplitAndInsurancePaymentTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryCashierSplitAndInsurancePaymentTest.php))**:
  * إضافة واجتياز اختبار حفظ الخدمة بالدليل تلقائياً `test_cashier_adding_new_custom_service_persists_to_hospital_extra_services_catalog` بنجاح 100% (`15 passed, 108 assertions`).
  * اجتياز اختبار تقسيم واجهة الحجز والتعديل بنجاح 100% (`1 passed, 15 assertions`).
  * اجتياز كامل اختبارات تقسيم الدفع والضمان والتحملات بنجاح 100% (`7 passed, 51 assertions`).
  * فحص وتنسيق الكود بـ `vendor/bin/pint --dirty` بنجاح كامل.

## Session log (2026-11-10 — المرحلة 186: إعادة هندسة وتطوير واجهة إنشاء طلب الاستعلامات بنظام بيئة العمل ثنائية الأعمدة بدون تمرير Inquiry Create Modern Split-Workspace Redesign, Sticky Action Panel & Quick Available Doctor Chips)

### Done
- **تخطيط بيئة العمل ثنائي الأعمدة المتجاوب ([resources/views/inquiry/create.blade.php](file:///C:/get/hospital-system/resources/views/inquiry/create.blade.php))**:
  * **يمين الشاشة (`col-lg-7`)**: بطاقات الخدمات الطبية المباشرة بحجم عصري وشارات تفاعلية أنيقة، مع فصل تحويلات الوحدات الخارجية (طوارئ، عمليات، رقود، حاضنة خدج) في قسم مستقل بأيقونات انتقال واضحة لمنع التشتت والخلط مع الخدمات متعددة الاختيار.
  * **يسار الشاشة (`col-lg-5`)**: لوحة تفاصيل وحفظ ثابتة بمحاذاة النظر (`position: sticky`) تلغي التمرير العمودي المجهد (`Scrolling Fatigue`)، وتعرض حالة توجيهية فارغة (`Empty State`) قبل الاختيار، ثم تظهر الحقول وخيارات التغطية (ضمان / كاش) وزر الاعتماد في نفس مستوى النظر فوراً.
- **شريط هوية المريض التنفيذي وحل تكرار تنبيه الضمان**:
  * دمج بيانات المريض في شريط أبيض منحني (`rounded-4`) يضم رقم الملف، العمر، الجنس، الهاتف، العنوان، وحالة الضمان الصحي.
  * إضافة أزرار وصول مباشر لـ: السجل الطبي الشامل (`inquiry.patients.history`)، تعديل الملف (`patients.edit`)، وطباعة الإضبارة (`inquiry.patients.dossier.print`).
  * القضاء على تكرار تنبيه قفل الضمان الصحي، وحصره في تنبيه موحد واضح ومباشر مع زر الإشعار الرقمي السريع لشعبة الضمان والتسجيل.
- **تسريع حجز الاستشارية والتحقق الذكي**:
  * إضافة أزرار شرائح سريعة (`Quick Selection Chips`) للأطباء المتواجدين اليوم فعلياً أعلى حقل البحث، ليتم تعبئة الطبيب وتفعيل الفحص التلقائي بنقرة زر واحدة.
  * ترقية تنبيهات التحقق والنواقص لتعتمد على **SweetAlert2** بدل تنبيهات المتصفح التقليدية (`alert`).
- **حزم الاختبارات الآلية الشاملة**:
  * اجتياز اختبارات قفل الضمان وحجز السونار بنجاح 100% (`InquiryInsuranceDataIncompleteTest`, `UltrasoundInquiryBookingTest`)، مع اجتياز اختبارات تدفق الحاضنة (`NicuWorkflowTest`).

## Session log (2026-11-08 — المرحلة 185: طباعة سندات صرف مستحقات الطبيب الاستشاري A4 وحراري 78mm ومحرك استرجاع وإلغاء الصرف الكلي وحالة الوصولات مقبوضة/غير مقبوضة Consultant Doctor Payout Voucher Printing, Strict Full Reversal Engine & Receipts Paid/Pending Dynamic Status)

### Done
- **صفحة وقالب طباعة سند الصرف الرسمي ([resources/views/consultant-availability/doctor-payout-print.blade.php](file:///C:/get/hospital-system/resources/views/consultant-availability/doctor-payout-print.blade.php))**:
  * تطبيق الهدر الرسمي الثلاثي لمستشفى الكفاءات الأهلي مع الشعار المعتمد بتقنية Base64.
  * تفقيط المبالغ المالية باللغة العربية وتحويل الأرقام إلى كلمات عبر الخدمة الجديدة `TafqeetService`.
  * شريط عنوان السند (`سند صرف نقدي - مستحقات طبيب استشاري` أو `سند استرجاع مالي`).
  * مساحات التواقيع الرسمية الثلاثية: توقيع الطبيب المستلم، منظم السند/المحاسب، واعتماد الإدارة المالية.
  * التبديل الفوري بنقرة زر بين وضع الطباعة الرسمية A4 ووضع الوصل الحراري الصغير (Thermal Receipt 78mm) المناسب لطابعات الكاشير.
- **تطوير واجهة كشف الحساب والتبويبات ([resources/views/consultant-availability/doctor-account-details.blade.php](file:///C:/get/hospital-system/resources/views/consultant-availability/doctor-account-details.blade.php))**:
  * إظهار شريط إشعار علوي بارز عند اعتماد أي صرف يحتوي على زر فوري `[ 🖨️ طباعة سند الصرف الآن ]`.
  * ربط عمود حالة الوصولات بوضوح: `[ ⏳ غير مقبوضة ]` عند عدم الصرف للطبيب، `[ ✓ مقبوضة ]` بعد اعتماد الصرف، و `[ 🔄 مسترجعة ]` عند رد الوصل للمريض.
  * فلترة الكشفيات حسب حالة الصرف للطبيب (`جميع الحركات`، `غير مقبوضة (معلقة)`، `مقبوضة (مسددة للطبيب)`، `مسترجعة للمريض`).
  * إعادة احتساب المبلغ المقترح للصرف ديناميكياً ليقتصر على **الوصولات غير المقبوضة فقط** منعاً لتكرار صرف كشفية مسددة مسبقاً.
  * إعادة هيكلة تبويب "سجل سندات الصرف والتسويات" ليعرض جدول سندات الخزينة الرسمية الصادرة (`FinancialTransaction`) برقم السند، تاريخ الصرف، المبلغ، المحاسب، البيان، وزرين للإجراءات: `[ 🖨️ طباعة السند ]` و `[ 🔄 استرجاع / إلغاء ]`.
  * وضع تفاصيل الكشفيات المسددة فردياً (`DoctorDue`) داخل قسم قابل للطي (Accordion) لمنع ازدحام الواجهة.
- **محرك استرجاع وإلغاء سندات الصرف الكلي الحصري ([app/Http/Controllers/ConsultantAvailabilityController.php](file:///C:/get/hospital-system/app/Http/Controllers/ConsultantAvailabilityController.php))**:
  * تطبيق قاعدة الإلغاء والاسترجاع الكلي للسند حصراً (`full reversal only`) منعاً للغموض المحاسبي وضمان عودة كافة وصولات السند الملغى إلى حالة `غير مقبوضة` بدقة تامة.
  * نافذة استرجاع تفاعلية (Modal) تُلزم المحاسب بإدخال سبب الاسترجاع وتمنع الاسترجاع المكرر.
  * إعادة المبلغ بالكامل فوراً إلى رصيد الطبيب المتاح، وخصمه من إجمالي المسدد.
  * إعادة كافة مستحقات الكشفيات المرتبطة بالسند إلى حالة معلقة `pending` لتعود غير مقبوضة ومتاحة للصرف مجدداً.
  * توثيق القيد العكسي في الخزينة برقم سند استرجاع `REF-DOC-...` وتوثيق العملية في ملاحظات السند الأصلي.
- **خدمة التفقيط المالي بالدينار العراقي ([app/Services/TafqeetService.php](file:///C:/get/hospital-system/app/Services/TafqeetService.php))**:
  * خدمة احترافية لتحويل الأرقام والمبالغ بالدينار العراقي إلى نصوص عربية نحوية متقنة.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/ConsultantDoctorPayoutVoucherAndReversalTest.php](file:///C:/get/hospital-system/tests/Feature/ConsultantDoctorPayoutVoucherAndReversalTest.php))**:
  * 4 اختبارات آلية جديدة تغطي توليد السندات، عرض وتفقيط الطباعة، الاسترجاع الكلي وإعادة الوصولات لحالة غير مقبوضة بنجاح 100% (`4 passed, 40 assertions`)، مع اجتياز كامل اختبارات فلاتر الحسابات (`6 passed, 49 assertions`).

## Session log (2026-11-08 — المرحلة 184: حل مشكلة التحقق من صحة مبلغ الصرف وإلغاء قيود الخطوة في كشف حساب الطبيب الاستشاري Consultant Doctor Payout Input Validation Fix & Arbitrary Amount Step-Any Support)

### Done
- **معالجة خطأ التحقق في المتصفح ([resources/views/consultant-availability/doctor-account-details.blade.php](file:///C:/get/hospital-system/resources/views/consultant-availability/doctor-account-details.blade.php))**:
  * حل مشكلة ظهور تنبيه المتصفح `Please select a valid value` عند النقر على "تسجيل واعتماد سند الصرف".
  * إزالة التعارض الرياضي في قيود HTML5 بين `min="1"` و `step="500"` الذي كان يرفض أي مبالغ مستديرة مثل 25,000 د.ع أو 10,000 د.ع لأنها لا تطابق معادلة `min + (k * step)`.
  * تغيير السمة إلى `step="any"` و `min="0.01"` للسماح بإدخال أو اقتراح أي قيمة نقدية نظامية ضمن رصيد الطبيب المتاح.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/ConsultantDoctorAccountsFiltersTest.php](file:///C:/get/hospital-system/tests/Feature/ConsultantDoctorAccountsFiltersTest.php))**:
  * تحديث وتمرير كافة الاختبارات الآلية للتحقق من سلامة حقل إدخال مبلغ الصرف وخصائصه بنجاح 100% (`6 passed, 49 assertions`).

## Session log (2026-11-08 — المرحلة 183: ترقية طباعة كشف مقبوضات الكاشير، إضافة الهدر المعتمد، منع تكرار المجموع في كل صفحة، وإظهار عمود نوع التغطية Cashier Report Print Standard Header, Single Table Total Row & Insurance/Cash Coverage Column)

### Done
- **إضافة الهدر الرسمي المعتمد في أقسام المستشفى ([resources/views/cashier/report-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/report-print.blade.php))**:
  * تطبيق الهدر الثلاثي الرسمي (مستشفى الكفاءات الأهلي، قسم الحسابات المالية والتدقيق، Al-Kafa'at Private Hospital).
  * تضمين شعار المستشفى المعتمد (`images/1.jpg`) مع تشفير Base64 لضمان سرعة التحميل ودقة الطباعة العالية.
  * إضافة شريط عنوان المستند الرسمي `كشف مقبوضات ووصولات الكاشير المعتمد (Cashier Revenue & Receipts Statement)`.
  * إضافة بطاقة الملخص المالي السريع: إجمالي المقبوضات المحصلة، إجمالي مطالبات الضمان، وعدد الوصولات.
- **إدراج العلامة المائية الرسمية وحجب روابط وتذييلات المتصفح التلقائية (System Watermark & Clean Edge Margins)**:
  * إدراج العلامة المائية للنظام (`report-watermark`) بخاصية `position: fixed` وشعار النظام المعتمد لتتكرر بهدوء وشياكة في منتصف كل صفحة مطبوعة.
  * ضبط `@page { margin: 0; }` و `body { padding: 7mm 9mm; }` لحجب وإلغاء طباعة رابط النظام المحلي (`http://127.0.0.1:8000/...`) وتاريخ المتصفح العشوائي في هوامش الصفحات.
- **ضبط استقامة وانتظام ترويسة الجدول عبر الصفحات المتعددة (Multi-Page Header & Table Layout Alignment)**:
  * فصل الترويسة الرئيسية والملخص المالي ليكون أعلى الجدول، وتخصيص `thead` فقط لصف عناوين الأعمدة الصريحة.
  * تطبيق `table-layout: fixed; width: 100%;` بنسب مئوية دقيقة لكل عمود مع منع انشطار الصفوف `break-inside: avoid`.
  * ضمان بدء الجدول في الصفحة الثانية والثالثة وما بعدها مباشرة من أعلى الصفحة بصفوف أعمدة ثابتة ونظيفة دون أي تشوه أو تصادم.
- **حل مشكلة تكرار المجموع الكلي أسفل كل صفحة مطبوعة ([resources/views/cashier/report-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/report-print.blade.php))**:
  * تصحيح خاصية CSS لذيل الجدول من `display: table-footer-group` إلى `display: table-row-group`.
  * حصر ظهور سطر المجموع الكلي ليظهر **مرة واحدة فقط في نهاية الجدول والتقرير على الصفحة الأخيرة**.
- **إضافة عمود "نوع التغطية" (كاش أم ضمان) وتطوير الموديل ([resources/views/cashier/report-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/report-print.blade.php), [app/Models/Payment.php](file:///C:/get/hospital-system/app/Models/Payment.php))**:
  * إضافة عمود صريح في الجدول يعرض نوع التغطية لكل وصل: `نقدي (كاش)` أو `ضمان (الضمان الصحي الوطني / قوى الأمن الداخلي)` مع رقم البطاقة وإيضاح حصة الضمان بدقة.
  * تزويد موديل `Payment` بالـ Accessor التوافقي `insurance_type_name`.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/CashierReportPrintTest.php](file:///C:/get/hospital-system/tests/Feature/CashierReportPrintTest.php))**:
  * إنشاء واجتياز اختبار التحقق من رندرة الهدر المعتمد، منع تكرار المجموع، ظهور عمود نوع التغطية والمجاميع بنجاح 100% (`1 passed, 15 assertions`).

## Session log (2026-11-08 — المرحلة 182: ديناميكية مبلغ الصرف واعتماده على البحث والفترة المحددة وشريط الفلترة الموحد لكشف حساب الطبيب الاستشاري Consultant Doctor Filtered Payout Amount Engine, Unified Top Filters & Period Dues Sync)

### Done
- **محرك احتساب مستحقات الفترة والبيان التلقائي ([app/Http/Controllers/ConsultantAvailabilityController.php](file:///C:/get/hospital-system/app/Http/Controllers/ConsultantAvailabilityController.php))**:
  * استخراج إحصائيات البحث والفترة المفلترة بدقة (`filteredDoctorShare`, `filteredHospitalShare`, `filteredTotalAmount`, `filteredCount`).
  * ربط وتعيين المبلغ المقترح للصرف ديناميكياً بحصة الطبيب للفترة المحددة `suggestedPayoutAmount = min($availableBalance, max(0, $filteredDoctorShare))` مع ضمان عدم تجاوز الرصيد المتبقي للطبيب.
  * توليد البيان والملاحظات الذكية المقترحة لسند الصرف آلياً بناءً على تاريخ الفترة المحددة أو مصطلح البحث (مثل: `صرف مستحقات كشفيات الطبيب للفترة من X إلى Y`).
- **ترقية وتطوير واجهة كشف الحساب بالكامل ([resources/views/consultant-availability/doctor-account-details.blade.php](file:///C:/get/hospital-system/resources/views/consultant-availability/doctor-account-details.blade.php))**:
  * رفع وتوحيد شريط الفلترة والبحث ليصبح كرت عام في أعلى الصفحة أسفل مؤشرات الـ KPIs مباشرة، ليحكم كلاً من بطاقة الصرف وجدول الكشفيات معاً.
  * إضافة شريط معلوماتي أنيق يُبرز نتائج الفترة المحددة (عدد الكشفيات، إجمالي المبالغ، حصة الطبيب المستحقة للفترة، وحصة المستشفى).
  * ترقية بطاقة الصرف: تزويد حقل المبلغ بزرين سريعين ذكيين: `[ 📅 الفترة (X د.ع) ]` لتعبئة مستحقات البحث بنقرة واحدة، و `[ ⚡ كامل الرصيد (Y د.ع) ]` لتعبئة كامل الرصيد المتاح.
  * دعم تأكيد الصرف التفاعلي عبر **SweetAlert2** مع إظهار المبلغ والبيان المحسوب بدقة.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/ConsultantDoctorAccountsFiltersTest.php](file:///C:/get/hospital-system/tests/Feature/ConsultantDoctorAccountsFiltersTest.php))**:
  * إضافة اختبار `test_consultant_doctor_account_filtered_payout_amount_calculation` والتحقق من حساب مستحقات الفترة وعرضها في النموذج بنجاح 100% (`6 passed, 47 assertions`).

## Session log (2026-11-08 — المرحلة 181: إعادة هندسة وتطوير كشف حساب الطبيب الاستشاري، إظهار سجل الكشفيات وتأكيد الصرف الذكي Consultant Doctor Account Statement Executive Redesign, Consultation Revenues Tab & SweetAlert Payouts Engine)

### Done
- **تطوير وحدة التحكم واستخراج تفاصيل الكشفيات ([app/Http/Controllers/ConsultantAvailabilityController.php](file:///C:/get/hospital-system/app/Http/Controllers/ConsultantAvailabilityController.php))**:
  * ربط استعلام كشفيات ومراجعات الطبيب الاستشاري (`ConsultationRevenue`) مع فلاتر البحث باسم المريض ورقم الإيصال وتحديد الفترة التاريخية (`from_date` و `to_date`) ونوع الحركة (`payment` أو `refund`).
  * احتساب إجمالي مبالغ الكشفيات المقبوضة، إجمالي المسترجع، صافي الإيراد، إجمالي حصة الطبيب، وحصة المستشفى مع ترقيم الصفحات المستقل `rev_page`.
- **إعادة تصميم وتطوير واجهة كشف الحساب بالكامل ([resources/views/consultant-availability/doctor-account-details.blade.php](file:///C:/get/hospital-system/resources/views/consultant-availability/doctor-account-details.blade.php))**:
  * **ترويسة وهوية الطبيب التنفيذية**: بطاقة بيضاء منحنية راقية تضم أيقونة الطبيب، اسمه الكامل، شارات التخصص والقسم، رقم الهاتف، ومعرف الطبيب، مع أزرار تصدير Excel وطباعة PDF.
  * **4 بطاقات KPI علوية عصرية**: الرصيد المتبقي للطبيب بذمة المستشفى (أخضر)، إجمالي المستحقات المكتسبة (أزرق)، إجمالي المبالغ المسددة (أخضر زمردي)، وعداد كشفيات المراجعين (موف).
  * **بطاقة الصرف والتسوية الذكية**: زر فوري `[ كامل الرصيد ]` لتعبئة أقصى رصيد متاح بنقرة واحدة، مع حماية العمليات المالية عبر نافذة تأكيد **SweetAlert2** راقية قبل الصرف الفعلي.
  * **نظام التبويبات الثلاثي (Nav Pills) المتطور**:
    1. `[ 📋 سجل كشفيات ومراجعات المرضى ]`: جدول متكامل يعرض رقم الإيصال، التاريخ والوقت، اسم المريض وهاتفه ومعرفه، نوع الخدمة، إجمالي الكشفية، حصة الطبيب البارزة، حصة المستشفى، الكاشير المسؤول، وشارة الحالة.
    2. `[ 💵 سجل سندات الصرف والتسويات المسددة ]`: أرشيف سندات الصرف، تاريخ الصرف، المحاسب المسؤول، المبلغ، والبيان.
    3. `[ ⏳ المستحقات المعلقة بانتظار الصرف ]`: كشف الحركات بانتظار التصفية.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/ConsultantDoctorAccountsFiltersTest.php](file:///C:/get/hospital-system/tests/Feature/ConsultantDoctorAccountsFiltersTest.php))**:
  * إضافة اختبار شامل لرندرة الواجهة الجديدة بكامل تبويباتها وكشفياتها، والتحقق من عملية الصرف وتحديث الأرصدة بنجاح 100% (`5 passed, 36 assertions`).

## Session log (2026-11-08 — المرحلة 180: منظومة البحث والفلترة الذكية والمؤشرات الإحصائية لحسابات الأطباء الاستشاريين Consultant Doctor Accounts Smart Search, Balance Filters, Executive KPIs & Dynamic Pagination)

### Done
- **تطوير محرك البحث والفلاتر في وحدة التحكم ([app/Http/Controllers/ConsultantAvailabilityController.php](file:///C:/get/hospital-system/app/Http/Controllers/ConsultantAvailabilityController.php))**:
  * إضافة محرك بحث نصي ذكي (`search`) يستعلم في اسم الطبيب (`users.name`)، والتخصص الطبي (`doctors.specialization`)، ورقم هاتف الطبيب (`users.phone`).
  * إضافة فلتر حالة الرصيد والمستحقات (`balance_status`):
    - `with_balance`: استخراج الأطباء الذين بذمة المستشفى رصيد مستحق لصالحهم (`balance > 0`).
    - `zero_balance`: استخراج الأطباء الذين رصيدهم صفر أو تم تصفية حسابهم بالكامل.
  * إضافة محرك ترتيب مرن ومتقدم (`sort_by`): الترتيب حسب أعلى رصيد مستحق، أقل رصيد، أعلى مستحقات مكتسبة، أعلى مبالغ مصروفة، أحدث تاريخ صرف، والترتيب الأبجدي بالاسم (أ-ي أو ي-أ).
  * احتساب المؤشرات الإحصائية العامة (`stats`): إجمالي عدد الأطباء الاستشاريين، عدد الأطباء الذين لديهم رصيد نشط، إجمالي الأرصدة المتبقية بذمة المستشفى، إجمالي المستحقات المكتسبة تاريخياً، وإجمالي المبالغ المصروفة والمسددة فعلياً.
  * تطبيق ترقيم الصفحات (`paginate(20)`) مع تثبيت واستبقاء كافة معاملات البحث والفلاتر في الروابط عبر `withQueryString()`.
- **ترقية وتحديث واجهة حسابات الاستشاريين ([resources/views/consultant-availability/doctor-accounts.blade.php](file:///C:/get/hospital-system/resources/views/consultant-availability/doctor-accounts.blade.php))**:
  * تصميم 4 بطاقات مؤشرات KPI علوية أنيقة ومريحة للعين (الرصيد المتبقي الإجمالي بلون أخضر، إجمالي المستحقات بلون أزرق، إجمالي المسدد بلون أخضر زمردي، وعدد الأطباء ذوي المستحقات النشطة).
  * إضافة شريط فلاتر عصري وبسيط داخل بطاقة بيضاء منحنية (`rounded-4`) يضم حقل البحث النصي مع أيقونة عدسة، قائمة حالة الرصيد، قائمة الترتيب، وزر [ 🔍 تصفية ] مع زر [ ✕ ] لمسح الفلاتر.
  * ترقية جدول الأطباء: إضافة رقم هاتف ومعرف الطبيب، تمييز الرصيد المتبقي بشارات ملونة ناصعة (خضراء عند وجود رصيد مستحق، ورمادية عند تصفية الحساب)، وتنسيق المبالغ بالدينار العراقي (`د.ع`).
  * تذييل أنيق للجدول يعرض عداد النتائج المعروضة من إجمالي الأطباء مع أزرار التنقل بين الصفحات.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/ConsultantDoctorAccountsFiltersTest.php](file:///C:/get/hospital-system/tests/Feature/ConsultantDoctorAccountsFiltersTest.php))**:
  * إنشاء واجتياز 4 اختبارات آلية جديدة تغطي رندرة المؤشرات، البحث النصي بالاسم والتخصص، فلترة حالة الرصيد (مستحق وصفري)، وخيارات الترتيب بنسبة نجاح 100% (`4 passed, 25 assertions`)، مع استمرار اجتياز كافة اختبارات مصفوفة الصلاحيات (`11 passed, 183 assertions`).

## Session log (2026-11-08 — المرحلة 179: تأمين كشوفات وحسابات الطوارئ ضد الانهيار البرمجي ودعم استخراج اسم وهوية المريض بدقة Emergency Cashier Statements & Movements Null-Safety Hardening & Emergency Patient Resolution)

### Done
- **معالجة الانهيار البرمجي في كشوفات وحسابات الطوارئ ([resources/views/cashier/emergency-statements.blade.php](file:///C:/get/hospital-system/resources/views/cashier/emergency-statements.blade.php))**:
  * حل خطأ `Attempt to read property "user" on null` في مسار `/cashier/emergency/statements` الناتج عن تقييم دالة `optional()` المتداخلة لمدخلاتها قبل التحقق عندما يكون مريض الطوارئ مسجلاً بملف سريع (`emergency_patient_id`) بدون سجل دائم في جدول `patients`.
  * استبدال دوال `optional()` المتداخلة بمعامل الأمان المتسلسل الأصلي في PHP 8 (`?->`) ودعم هرمية استخراج اسم المريض: مريض الطوارئ الدائم ⬅️ مريض الطوارئ السريع ⬅️ مريض السند المباشر ⬅️ `غير معروف`.
  * تعميم التعديل الآمن على شاشة الحركات المالية للطوارئ ([resources/views/cashier/emergency-movements.blade.php](file:///C:/get/hospital-system/resources/views/cashier/emergency-movements.blade.php)) وتفاصيل حساب طبيب الطوارئ ([resources/views/cashier/emergency-doctor-account.blade.php](file:///C:/get/hospital-system/resources/views/cashier/emergency-doctor-account.blade.php)).
- **تطوير نموذج البيانات والتحسين الأدائي ([app/Models/Payment.php](file:///C:/get/hospital-system/app/Models/Payment.php), [app/Models/Emergency.php](file:///C:/get/hospital-system/app/Models/Emergency.php), [app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * إضافة Accessor موحد وآمن `$payment->patient_name` في موديل `Payment` لتوفير وصول محمي وشامل لاسم المريض في كافة العمليات المالية.
  * إضافة Accessor موحد `$emergency->patient_name` في موديل `Emergency`.
  * تحسين استعلامات الكاشير وإضافة `emergency.emergencyPatient` و `patient.user` ضمن الـ Eager Loading لمنع استعلامات N+1، وتصحيح استخراج الأسماء في تقرير الإحالات الطبية والعمليات (`emergencyReferrals`).
- **تنقية وحصر فلتر الكاشير في التقارير المالية بالأدوار الرسمية ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [app/Models/Appointment.php](file:///C:/get/hospital-system/app/Models/Appointment.php))**:
  * حصر استعلام فلتر الكاشير في شاشة تقارير المدفوعات `/cashier/report` بالمستخدمين النشطين الذين يحملون دور الكاشير الرسمي (`cashier`, `casshieradmin`) بدلاً من جلب كل مستخدم سُجل في حقل `cashier_id`، مما أزال الأطباء وموظفي الاستعلامات والمحاسبين من القائمة المنسدلة.
  * تصحيح عملية تسجيل سند استرجاع كشفية الموعد الملغى في `Appointment::cancel()` لينسب السند إلى الكاشير الأصلي الذي قبض الدفعة `$consultationPayment?->cashier_id ?? auth()->id()` لمنع تلوث حقل `cashier_id` بحساب الطبيب مستقبلاً.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/EmergencyCashierStatementsNullSafetyTest.php](file:///C:/get/hospital-system/tests/Feature/EmergencyCashierStatementsNullSafetyTest.php))**:
  * إنشاء واجتياز 3 اختبارات آلية جديدة تغطي رندرة كشوفات الطوارئ، الحركات المالية، وحسابات أطباء الطوارئ لمرضى الطوارئ السريعين والنظاميين والمدفوعات اليتيمة بنسبة نجاح 100% (`3 passed, 8 assertions`)، مع اجتياز كامل اختبارات الصلاحيات (`11 passed, 183 assertions`).

## Session log (2026-11-07 — المرحلة 178: تعديل العلامات الحيوية السريرية مباشرة في الجدول العلوي وإلغاء المودل المنبثق نهائياً وتمرير سلس سريع Inline Flowsheet Vitals Editing, Modal Removal, Direct Table Sync & 30-Minute Governance Enforcement)

### Done
- **تعديل مباشر وسلس في الجدول العلوي دون فتح نوافذ منبثقة (Zero-Modal Direct Flowsheet Editing)**:
  * إلغاء نافذة المودل المنبثقة `#editReadingModal` نهائياً والتخلص من أي مشاكل تشوه أو قطع في الواجهة.
  * عند النقر على زر `[ ✏️ تعديل ]` في سجل القراءات التاريخية: تعبئة قيم القراءة فورياً في خلايا الجدول العلوي (`grid_bp`, `grid_temp`, `grid_pr`, `grid_rr`, `grid_spo2`, `grid_rbs`, `grid_pain_score`, `grid_gcs`, `grid_crt`) وتفعيل الخانات وتحديدها باللون الأزرق.
  * تمرير الشاشة تلقائياً وبسلاسة (`smooth scroll`) نحو جدول العلامات الحيوية في الأعلى وتركيز المؤشر داخل خانة ضغط الدم.
  * إظهار شارة إرشادية أنيقة أعلى الجدول توضح توقيت القراءة واسم مسجلها مع زر سريع `[ ✕ إلغاء التعديل ]` لإرجاع القيم السابقة فوراً.
- **معالجة الحفظ الموحد عبر زر "حفظ التحديثات السريرية" ([app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php))**:
  * دعم مدخل `editing_reading_id` المخفي ضمن النموذج الرئيسي، والتحقق الصارم من مهلة الـ 30 دقيقة لنفس الحساب أو صلاحية مدير النظام (`Admin`).
  * مزامنة وتحديث قيم المحطة السريرية العلوية ومحطة التمريض تلقائياً ولحظياً في حال كانت القراءة المعدلة هي الأحدث للمريض.
  * تصحيح حساب الدقائق المتبقية للمهلة في الشارة التنبيهية لتظهر كعدد صحيح بدون كسور عشرية `(int) max(0, min(30, ceil($secondsRemaining / 60)))`.
  * تصحيح استعلام إلغاء مستحقات الجراح عند استرجاع العملية في الكاشير وحذف `surgery_id` غير المعرف في جدول `doctor_dues` والاعتماد على `payment_id` وملاحظات العملية لمنع أخطاء SQL 1054.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/ClinicalVitalsGovernanceTest.php](file:///C:/get/hospital-system/tests/Feature/ClinicalVitalsGovernanceTest.php))**:
  * اجتياز كامل الاختبارات بنسبة نجاح 100% (`8 passed, 50 assertions`) واستمرار اجتياز كافة اختبارات المحطة وصلاحيات التبويبات (`11 passed, 44 assertions`).

## Session log (2026-11-07 — المرحلة 178: عزل العمليات الملغاة والمسترجعة في تبويب رقابي مستقل وتصفير مستحقاتها المحاسبية Doctor Accounts Cancelled & Refunded Surgeries Segregation, Informational Audit Tab & Zero-Balance Dues Governance)

### Done
- **عزل العمليات الملغاة والمسترجعة محاسبياً في الكاشير ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * في ملخص حسابات الجراحين (`surgeryDoctorAccounts`): استبعاد العمليات التي تحمل حالة `cancelled` أو `refunded` من عدد العمليات المحسوبة وإجمالي الأتعاب والمستحقات، بحيث لا تؤثر أي عملية ملغاة على الرصيد المستحق.
  * في كشف حساب وتفاصيل الجراح (`surgeryDoctorAccount`): فصل الاستعلام إلى مجموعتين:
    1. `$validQuery`: العمليات المعتمدة السارية المؤثرة على الأتعاب الاسمية وصافي المستحق والرصيد (`$grossFees`, `$packageDeductions`, `$netEarned`, `$balance`).
    2. `$cancelledQuery`: العمليات الملغاة أو المسترجعة (`cancelledSurgeries`) لعرضها في تبويب رقابي للعلم والأرشفة دون التأثير على حساب الجراح.
  * في عملية استرجاع الرسوم للمواطن (`processSurgeryRefund`): إضافة إجراء الإلغاء التلقائي لأي قيد `DoctorDue` معلق مرتبط بالعملية المسترجعة مع تدوين ملاحظة رقابية واضحة: `[أُلغيت المستحقات لاسترجاع رسوم العملية للمريض]`.
- **تصميم التبويب الرقابي في كشف حساب الجراح ([resources/views/cashier/surgeries/doctor-account-details.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/doctor-account-details.blade.php))**:
  * إضافة تبويبات Nav Pills ثلاثية أنيقة:
    1. `العمليات الجراحية المستحقة`: تعرض العمليات المعتمدة فقط مع أزرار الصرف والتصفية.
    2. `[ 🚫 العمليات الملغاة والمسترجعة (للعلم) ]`: مع شارة حمراء توضح عدد العمليات الملغاة، جدول شفاف يوضح تفاصيل العملية، الأجور الاسمية مشطوبة، شارة `0 د.ع (لا يترتب استحقاق)`، وشارة `تم استرجاع المبلغ للمواطن`، مع تفاصيل ورقم إيصال الاسترجاع وتاريخه.
    3. `سجل سندات الصرف والتسويات`: سجل الدفعات السابقة.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/SurgeryExtraServicesTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryExtraServicesTest.php))**:
  * إضافة اختبار شامل `test_cancelled_and_refunded_surgeries_are_separated_in_doctor_account_with_zero_due` للتأكد من عزل العمليات الملغاة، تصفير رصيدها، وعرضها في التبويب الرقابي بنجاح 100% (`14 passed, 99 assertions`).

## Session log (2026-11-07 — المرحلة 177: شارات استقطاع حصة الجراح والضمان وتلخيص الاستقطاعات التلقائي في نموذج دفع العمليات Surgery Cashier Smart Package Inclusions Badges & Live Doctor Deductions Total Breakdown)

### Done
- **شارات الباقة الذكية في جدول بنود تسديد العمليات ([resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
  * إضافة حاوية الشارة الذكية `package-status-badge` لأسطر: الغرفة والإقامة، التحاليل المخبرية، الفحوصات الإشعاعية، والخدمات الإضافية/الفندقية.
  * **للمرضى النقديين (كاش)** عند شمول البند: إبراز شارة تحذيرية حمراء واضحة `[ ✂️ استقطاع من حصة الجراح: X د.ع ]` توضح للكاشير والمحاسب المبلغ الدقيق المستقطع من أتعاب الجراح لصالح المستشفى.
  * **لمرضى الضمان الصحي** عند شمول البند: إبراز شارة تعاقدية `[ 🛡️ مشمول بعقد الضمان ]`.
  * عند اختيار البند منفصلاً: إبراز شارة رمادية `[ 🪙 منفصل عن العملية ]` أو `[ تسديد أجور الإقامة منفصلة ]`.
- **سطر التلخيص الإجمالي للاستقطاعات بأسفل جدول الفاتورة (`tfoot`)**:
  * إضافة سطر بارز بلون تحذيري هادئ خاص بالمرضى النقديين: `إجمالي استقطاعات الباقة المخصومة من حصة الجراح: [ X د.ع ]` مع شارة توضيحية: `[ ℹ️ تُقيد كإيراد مستشفى وتُخصم من صافي أتعاب الجراح في كشوفات الحسابات ]`.
- **تحديث محرك الجافاسكريبت التفاعلي اللحظي (`calculateSelectedAmount()`)**:
  * ربط دالة الحساب لتحديث نص وألوان الشارات وحساب مجموع استقطاعات الطبيب الجراح فورياً ولحظياً عند التبديل بين كبسولات `[ مشمول / منفصل ]` لأي خدمة أو للغرفة.
  * إظهار أو إخفاء سطر ملخص الاستقطاعات ديناميكياً بحسب نوع التأمين وقيمة الاستقطاع.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/SurgeryExtraServicesTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryExtraServicesTest.php))**:
  * تعزيز الاختبارات لتأكيد رندرة شارات الاستقطاع وسطر التلخيص في نموذج الدفع بنسبة نجاح 100% (`13 passed, 89 assertions`).

## Session log (2026-11-07 — المرحلة 176: قسم حسابات وتصفية جراحي العمليات — استقطاعات الباقة لصالح المستشفى، كشوفات حسابات الجراحين، وصفحة تفاصيل وسندات صرف مستحقات العمليات Surgery Doctor Accounts & Package Inclusions Settlement Engine: Granular Inclusions Deduction, Doctor Accounts Index, Itemized Surgery Breakdown & Treasury Payouts)

### Done
- **بنية البيانات وحفظ استقطاعات الباقة المشمولة بالعملية ([database/migrations/2026_11_07_202000_add_package_deduction_fields_to_surgery_tables.php](file:///C:/get/hospital-system/database/migrations/2026_11_07_202000_add_package_deduction_fields_to_surgery_tables.php))**:
  * إضافة أعمدة `is_package_included`, `included_deductible_price`, `deduction_bearer` في جداول `surgery_lab_tests`, `surgery_radiology_tests`, `surgery_extra_services`.
  * إضافة `is_room_package_included`, `room_deduction_bearer` في جدول `surgeries`.
  * إضافة `doctor_deductions_amount`, `included_package_items` (JSON Snapshot) في جدول `payments`.
  * تحديث موديلات `SurgeryLabTest`, `SurgeryRadiologyTest`, `SurgeryExtraService`, `Surgery`, `Payment`, و `Doctor` (إضافة علاقة `surgeries()`).
- **معالجة الدفع وحساب الاستقطاعات في الكاشير ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * للمرضى النقديين: احتساب قيمة الخدمات المشمولة بالباقة وتحميلها كاستقطاع على حصة الطبيب الجراح (`deduction_bearer = 'doctor'`) لصالح إيراد المستشفى.
  * لمرضى الضمان الصحي: إدراجها ضمن التغطية العقدية (`deduction_bearer = 'insurance'`).
  * تسجيل صافي الاستحقاق للجراح تلقائياً في `DoctorDue` بالمعادلة: صافي المستحق = أتعاب الجراحة - استقطاعات الباقة.
- **القائمة الجانبية والشاشات الجديدة للحسابات المالية**:
  * إضافة قسم فرعي: `حسابات جراحي العمليات` ضمن قسم "الحسابات العامة والمالية" في [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php).
  * **شاشة ملخص حسابات الجراحين ([resources/views/cashier/surgeries/doctor-accounts.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/doctor-accounts.blade.php))**: جدول بكافة الجراحين، عدد العمليات، الأتعاب الاسمية، استقطاعات الباقة، صافي المستحق، إجمالي المصروف، والرصيد المتبقي بذمة المستشفى، مع زر [ 👁️ كشف الحساب والتفاصيل ].
  * **شاشة كشف حساب الجراح وعملياته التفصيلية ([resources/views/cashier/surgeries/doctor-account-details.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/doctor-account-details.blade.php))**: بطاقات مؤشرات علوية، نموذج صرف دفعة / تصفية رصيد للجراح، جدول مفصل بجميع عمليات الجراح مع تفكيك دقيق للخدمات المشمولة بالباقة، وزر صرف وتصفية العملية بشكل فوري.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/SurgeryExtraServicesTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryExtraServicesTest.php))**:
  * اجتياز كامل اختبارات التحقق من استقطاع الباقة من حصة الجراح نقداً، عرض حسابات الجراحين، كشف الحساب المفصل، والصرف الفعلي بنسبة 100% (`13 passed, 85 assertions`)، واستمرار اجتياز كافة اختبارات الضمان والتمريض (`13 passed, 97 assertions`).

## Session log (2026-11-07 — المرحلة 175: نظام الكاشير الموحد للعمليات الجراحية — الشمول التام للغرفة بالباقة، الحذف الفوري لتحاليل وأشعة العمليات، والدفع الجزئي العام الإجمالي للمبلغ الكلي Surgery Cashier Unified Suite: Granular Lab/Radiology Deletion, Room Package Inclusion Toggle & Global Bottom Invoice Split/Partial Payment)

### Done
- **الحذف الفوري للتحاليل والأشعة المعلقة بالعملية ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * إضافة مسارات وتوابع `removeSurgeryLabTest()` و `removeSurgeryRadiologyTest()` للتحقق من صلاحية الكاشير وملكية الفحص وعدم تسديده مسبقاً، مع دعم استجابة JSON للـ AJAX.
  * تحديث مايجريشن `database/migrations/2026_01_13_063801_add_payment_fields_to_surgery_radiology_tests_table.php` لإضافة أعمدة `payment_status` و `payment_id` في جدول `surgery_radiology_tests`.
  * إضافة أزرار الحذف الدائرية الحمراء `delete-surgery-item-btn` في جدول تسديد العمليات مع تأكيد SweetAlert2 أنيق وإعادة حساب فوري للمجاميع.
- **توحيد كبسولات الشمول بالباقة للغرفة ([resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php), [app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * استبدال أزرار التجزئة المشتتة داخل سطر الغرفة بكبسولات موحدة: `[ 📦 مشمول بالعملية ]` (حصة المريض 0 د.ع) مقابل `[ 💵 منفصل ]` (احتساب الاستقطاع الاعتيادي أو الضمان).
  * ربط `package_included[room]` في السيرفر وتحديث مبالغ الغرفة والإقامة وإصدار الوصل بما يطابق الشمول.
- **بطاقة الدفع الجزئي العام أسفل الفاتورة (Global Partial Payment)**:
  * نقل خيار الدفع الكامل والجزئي من تشتته بالأسطر إلى بطاقة تحكم عامة وموحدة أسفل الجدول أعلى وسائل الدفع: `[ 🟢 دفع كامل للمبلغ المعتمد ]` مقابل `[ 🟡 دفع جزئي (دفعة مقبوضة الآن) ]`.
  * حقل إدخال المبلغ المقبوض فعلياً مع شارة احتساب دين العملية المتبقي حياً ولحظياً.
  * معالجة `CashierController@processSurgeryPayment` لحفظ الدفعة وتوزيعها وإبقاء العملية كـ `partial` وتوثيق المتبقي في الوصل وسجل المدفوعات.
- **فصل شاشة الانتظار وتقويم العمليات ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php))**:
  * تحويل شاشة الانتظار `/surgeries/waiting` إلى شاشة عرض مخصصة لعمليات اليوم فقط مع تنقل يومي سلس `[ السابق / التالي ]`.
  * نقل وتخصيص التقويم الشهري المدمج إلى سجل العمليات العام `/surgeries`.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/SurgeryExtraServicesTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryExtraServicesTest.php))**:
  * كتابة واجتياز 3 اختبارات آلية جديدة تغطي حذف التحاليل والأشعة، شمول الغرفة بالباقة بدقة، والدفع الجزئي العام الإجمالي بنسبة 100% (11 passed, 61 assertions) واستمرار اجتياز كافة اختبارات الضمان وشرائح الإقامة (19 passed, 131 assertions).

## Session log (2026-11-07 — المرحلة 174: حظر فتح المحطة السريرية للمقيم قبل استلام المريض في الغرفة، وتطبيق نظام التنبيهات الطبي الشامل SweetAlert2 Resident Clinical Station Gate & Global Hospital SweetAlert2 & Toastr Modern Styling System)

### Done
- **حوكمة محطة المقيم السريرية ([app/Http/Controllers/ResidentStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ResidentStationController.php), [resources/views/surgery-stations/resident/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/resident/index.blade.php))**:
  * قفل زر "عرض وتعديل" في محطة المقيم وتحويله إلى `[ 🔒 بانتظار استلام الغرفة ]` إذا كان المريض مخصصاً لغرفة ولم يسجل ممرض الطابق استلامه الفعلي بعد.
  * إضافة شارة صفراء أنيقة تحت اسم المريض: `[ ⏳ بانتظار استلام ممرض الطابق ]` تتحول تلقائياً إلى خضراء `[ ✔️ تم الاستلام (hh:mm A) ]`.
  * حظر الدخول المباشر بالرابط في `ResidentStationController@show` وإعادة التوجيه مع رسالة تحذيرية واضحة.
- **محرك التنبيهات الطبي الشامل والتخلص من النوافذ السوداء ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * دمج مكتبة SweetAlert2 الفاخرة مع تنسيق طبي مخصص: بطاقة بيضاء ناصعة، خط النظام الطبي، زوايا دائرية `rounded-4`، أيقونات طبية متحركة ونابضة، وأزرار كبسولية عصرية.
  * **Global Alert Interceptor**: اعتراض دالة `window.alert` في المتصفح تلقائياً وتحويل أي تنبيه في كامل شاشات المستشفى إلى SweetAlert2 ملون وأنيق بدلاً من نوافذ المتصفح السوداء الافتراضية.
  * تحديث تنسيقات Toastr لتتحول من الصناديق الداكنة القديمة إلى بطاقات زجاجية ملونة وناعمة بنمط الباستيل الطبي.
  * ربط رسائل الـ Session (النجاح والتحذيرات والخطأ) لتظهر تلقائياً كـ Toasts عائمة أو بطاقات تحذيرية فاخرة.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/ResidentStationRoomCheckinGateTest.php](file:///C:/get/hospital-system/tests/Feature/ResidentStationRoomCheckinGateTest.php))**:
  * كتابة واجتياز اختبارات قفل المحطة للمقيم وحظر الرابط بنسبة 100% (2 passed, 11 assertions)، واستمرار اجتياز كافة اختبارات محطة التمريض والضمان (15 passed, 108 assertions).

## Session log (2026-11-07 — المرحلة 173: حوكمة دخول وتخريج المريض من غرف الطابق بمحطة التمريض، ربط الإقامة الفندقية بالاستلام الفعلي، وحجب ملف المريض قبل الاستلام Floor Nursing Room Check-in / Checkout Governance, Bed Release, Stay Calculation Synchronization & Check-in Clinical Access Gate)

### Done
- **تصميم مسار حوكمة إشغال الغرف ومحطة التمريض ([app/Http/Controllers/NursingStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/NursingStationController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * إضافة توابع `roomCheckin()` و `roomCheckout()` لتسجيل الدخول الفعلي للغرفة وتخريج المريض مع تدقيق أمني وحفظ هوية الممرض ووقت الإشغال وتحديث حالة الغرفة بين `occupied` و `available` وتسجيل الحركات في `UserActivityLogger`.
  * **بوابة حجب الوصول السريري (Check-in Gate)**: في `NursingStationController@show` و `ClinicalStationController@show`، منع الممرضين من فتح ملف المريض وتعديل بياناته السريرية إذا كانت الحالة مخصصة لغرفة ولم يتم تسجيل دخولها واستلامها بعد، مع توجيه تنبيهي فوري: "يرجى تسجيل دخول المريض للغرفة أولاً قبل الدخول إلى ملف المريض والتعديل السريري".
- **تطوير واجهة محطة التمريض وتبديل الأزرار ([resources/views/surgery-stations/nursing/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/nursing/index.blade.php), [resources/views/surgery-stations/nursing/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/nursing/show.blade.php), [resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * في تبويبي ما قبل وما بعد الصالة:
    - **قبل تسجيل الدخول**: إخفاء زر "عرض وتعديل" تماماً وعرض زر وحيد أخضر بارز `[ 🛏️ تسجيل دخول الغرفة ]` مع شارة `[ ⏳ بانتظار استلام الغرفة ]`.
    - **بعد تسجيل الدخول**: يختفي زر تسجيل الدخول ويتبدل مكانه إلى زر `[ 📋 عرض وتعديل ]` وبجانبه زر أحمر سريع `[ 🚪 تخريج ]` لإخلاء السرير مع شارة خضراء أنيقة `[ ✔️ تم الدخول (hh:mm A) ]`.
  * إضافة زر التخريج السريع ومعلومات الغرفة وتوقيت الدخول في الترويسة العلوية لملف التمريض والمحطة السريرية.
- **قاعدة احتساب الإقامة الفندقية الصارمة وتصحيح الغرف العادية ([app/Models/Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php), [app/Models/HealthInsuranceStayTier.php](file:///C:/get/hospital-system/app/Models/HealthInsuranceStayTier.php))**:
  * ربط وقت بدء احتساب الإقامة الفندقية في `calculateStayDetails()` بحقل `room_checkin_at` كأساس أولي بدلاً من تاريخ الجدولة.
  * تجميد الإقامة عند تاريخ التخريج `discharged_at` فور نقر زر التخريج.
  * قفل وتأكيد أن الغرفة العادية (`regular`) لا يترتب عليها أي فرق ترقية إطلاقاً (`upgrade_diff = 0`) حتى لو كان سعرها التجاري أعلى من سعر عقد الضمان.
  * قفل الليالي الإضافية للعمليات المجدولة التي لم تبدأ أو لم تسجل دخولها بعد لتكون 0 ليلة إضافية، مما يجعل حصة المريض للغرفة العادية في العملية #1 تساوي 0 د.ع تماماً ومطابقة للنظام.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/NursingRoomCheckinWorkflowTest.php](file:///C:/get/hospital-system/tests/Feature/NursingRoomCheckinWorkflowTest.php))**:
  * كتابة واجتياز 5 اختبارات آلية بنسبة 100% (5 passed, 27 assertions) تغطي: زر تسجيل الدخول، الحجب قبل الدخول، التبديل إلى العرض والتخريج، التخريج الفعلي وإخلاء السرير، ومطابقة محرك احتساب الإقامة مع `room_checkin_at`.
  * اجتياز كافة اختبارات استقطاع الإقامة والخدمات الإضافية للعمليات بإجمالي 21 اختباراً ناجحاً بنسبة 100%.

## Session log (2026-11-07 — المرحلة 172: نظام استقطاع الإقامة الفندقية المتدرج للضمان الصحي، تسعير الغرف، وفرق الترقية الفعلي VIP/VVIP عن كل الليالي، وتفكيك الحسابات في الكاشير Health Insurance Tiered Stay Deduction Engine, Dynamic Room Tariff Management, Ubiquitous Room Upgrade Surcharge & Cashier Live Nights Breakdown)

### Done
- **بنية قاعدة البيانات والشرائح الخمس المعتمدة ([database/migrations/2026_11_07_140000_create_health_insurance_stay_tiers_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_07_140000_create_health_insurance_stay_tiers_table.php), [app/Models/HealthInsuranceStayTier.php](file:///C:/get/hospital-system/app/Models/HealthInsuranceStayTier.php))**:
  * إنشاء جدول وموديل `health_insurance_stay_tiers` مع الشرائح الخمس المعتمدة للضمان:
    1. الليلة الأولى (يوم 1): تحمل 0% (مشمولة بالعملية للغرفة العادية، 0 د.ع مريض و 0 د.ع ضمان).
    2. الليلة الثانية (يوم 2): تحمل 10% (20,000 د.ع مريض، و 180,000 د.ع ضمان).
    3. إقامة اعتيادية (أيام 3 إلى 8): تحمل 25% (50,000 د.ع مريض / يوم، و 150,000 د.ع ضمان / يوم).
    4. إقامة ممتدة (أيام 9 إلى 13): تحمل 50% (100,000 د.ع مريض / يوم، و 100,000 د.ع ضمان / يوم).
    5. إقامة طويلة (أيام 14 فأكثر): تحمل 75% (150,000 د.ع مريض / يوم، و 50,000 د.ع ضمان / يوم).
  * حفظ وإدارة السعر اليومي المعتمد للغرفة العادية لدى الضمان (`standard_room_daily_price = 200,000 د.ع`).
  * توابع مساعدة لاحتساب اليوم والشريحة ومبالغ كل ليلة وفروقات الترقية.
- **إدارة نسب الاستقطاع وأسعار الغرف بتبويب تفاعلي ([app/Http/Controllers/HealthInsuranceCategoryController.php](file:///C:/get/hospital-system/app/Http/Controllers/HealthInsuranceCategoryController.php), [resources/views/health-insurance-categories/index.blade.php](file:///C:/get/hospital-system/resources/views/health-insurance-categories/index.blade.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * إضافة تبويب مستقل في واجهة نسب استقطاع الضمان الصحي (`/health-insurance-categories`):
    - تبويب 1: فئات العمليات والخدمات الطبية (أ - ط).
    - تبويب 2: شرائح استقطاع الإقامة والغرف للضمان، مع بطاقات مؤشرات أسعار الغرف الفعلية (عادية: 250k، VIP: 350k، VVIP: 450k) وفروقات الترقية اليومية.
  * إضافة نوافذ منبثقة (Modals) ومسارات لتعديل السعر المعتمد وتعديل نسب وأيام وملاحظات أي شريحة ديناميكياً.
- **محرك حساب الليالي وفروقات الترقية الصارمة ([app/Models/Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php))**:
  * تطوير دالة `calculateStayDetails()` لتعتمد قاعدة 12:00 ظهراً ونظام الشرائح المتدرجة لمرضى الضمان.
  * **قاعدة فرق الترقية الفعلي عن كل الليالي**: الضمان يغطي حصراً تسعيرة الغرفة العادية (200,000 د.ع). أي ترقية لـ VIP (فرق 150,000 د.ع / يوم) أو VVIP (فرق 250,000 د.ع / يوم) يتحملها المريض بالكامل بنسبة 100% عن كل ليلة دون استثناء (بما فيها الليلة الأولى).
  * توليد مصفوفة تفصيلية `nights_breakdown` لكل ليلة مع تفكيك دقيق لحصة المريض ومطالبة الضمان وفرق الترقية.
- **تطوير واجهة دفع العمليات ودعم تفكيك الليالي بالكاشير ([resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php), [app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * عرض شارة "استقطاع متدرج (ضمان)" في سطر الغرفة بجدول العمليات، وزر منسدل لعرض جدول تفكيك الليالي والاستقطاعات.
  * مزامنة حية وفورية في JavaScript لاحتساب حصة المريض ومطالبة الضمان للغرفة عند الدفع الكامل أو الجزئي.
  * معالجة وإصلاح حصر حالة الغرفة كمدفوعة في الكاشير `processSurgeryPayment` لتطابق حصة المريض الفعلية المطلوبة بدلاً من كامل سعر الغرفة التجاري.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/HealthInsuranceStayTiersTest.php](file:///C:/get/hospital-system/tests/Feature/HealthInsuranceStayTiersTest.php))**:
  * كتابة واجتياز 8 اختبارات آلية بنسبة 100% (8 passed, 70 assertions) واختبارات الخدمات الإضافية السابقة (8 passed, 47 assertions) بإجمالي 16 اختباراً و117 توكيداً ناجحاً.

## Session log (2026-11-07 — المرحلة 171: توحيد جدول دفع العمليات الجراحية مطابقةً لواجهة الطلبات الطبية، وأزرار كبسولات الشمول المنفصلة السداسية الأعمدة Surgery Cashier Payment Table Modern Redesign & Segmented Action Pills Parity)

### Done
- **إعادة تصميم جدول دفع العمليات الجراحية بستة أعمدة موحدة ([resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
  * مطابقة هيكلية التصميم وتجربة المستخدم لشاشة تسديد الطلبات الطبية (`request-payment-form.blade.php`):
    1. `# / التحديد`: مربع اختيار البند الفردي أو الإجمالي لكل قسم.
    2. `البند والخدمة`: اسم العملية/الغرفة/التحليل/الأشعة/الخدمة الإضافية مع شارات وأيقونات ملونة وتفاصيل الإقامة والكميات.
    3. `السعر المعتمد`: السعر الإجمالي للبند، مع حقل التعديل المباشر `extra-service-price-input` للخدمات الإضافية.
    4. `تحمل المريض (نقداً)`: حصة المريض المحدثة لحظياً `row-patient-share` (تصبح 0 د.ع عند الشمول).
    5. `حصة الضمان (ذمة)`: مطالبة جهة الضمان `row-insurance-share` (ضمان صحي أو داخلية).
    6. `الإجراء والشمول بالباقة`: كبسولات تحكم مقسمة عصرية (Segmented Control Pills) لاختيار نمط الدفع أو الشمول.
- **تطبيق كبسولات التحكم العصرية (iOS/Apple Segmented Control Pills)**:
  * استبدال المفاتيح التقليدية بكبسولات اختيار رشيقة:
    - للعملية والغرفة: `[ دفع كامل ]` vs `[ دفع جزء ]` مع إظهار حقل المبلغ المخصص تلقائياً.
    - للمختبر والأشعة والخدمات الإضافية: `[ 📦 مشمول ]` (أخضر زمردي مع شارة 0 د.ع) vs `[ 💵 منفصل ]` (أزرق ملكي مع احتساب المبلغ).
    - زر حذف دائري أنيق للخدمات الإضافية `delete-extra-service-btn`.
- **مزامنة فورية ودقيقة للحسابات المالية (Live Dynamic Recalculation)**:
  * ربط مستمعي الأحداث لأزرار الراديو الكبسولية `inclusion-pill-radio` بالمفاتيح المخفية `package-inclusion-switch` لتحديث حصة المريض ومطالبة الضمان وإجمالي البنود المعتمدة في تذييل الجدول `tfoot` وصندوق الدفع اللحظي دون الحاجة لإعادة تحميل الصفحة.
- **إلحاق تلقائي رجعي وفك حظر العمليات في الكاشير ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * استدعاء `attachDefaultExtraServices()` تلقائياً في `showSurgeryPaymentForm` للعمليات غير الملغاة لضمان وجود الخدمات الافتراضية للعمليات القديمة.
  * فك حظر فتح شاشة الدفع إذا كان هناك أي بنود معلقة (خدمات إضافية، تحاليل، أشعة، أو متبقي جراحة) حتى لو كانت حالة العملية مسجلة `paid` قديماً.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/SurgeryExtraServicesTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryExtraServicesTest.php))**:
  * إضافة واجتياز اختبار التحقق من بنية الجدول والكبسولات السداسية والإلحاق التلقائي الرجعي بنسبة 100% (8 passed, 47 assertions)، واستمرار اجتياز كافة اختبارات تجزئة ودفع العمليات والضمان (15 passed, 98 assertions).

## Session log (2026-11-07 — المرحلة 170: الإلحاق التلقائي لخدمات العمليات (تخطيط قلب، فتح طبلة، خدمات فندقية)، تسعيرها المرن القابل للتعديل، والتحكم الفردي بالشمول وإلغاء الشمول الكلي Auto Surgery Default Services, Inline Editable Pricing, Granular Per-Service Inclusion & All-Inclusive Toggle Removal)

### Done
- **الإلحاق التلقائي مع حجز وتحديث العمليات ([app/Models/Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
  * إضافة دالة `attachDefaultExtraServices()` على موديل `Surgery` لربط 3 خدمات إضافية افتراضية تلقائياً لكل عملية جديدة (كاش أو ضمان):
    1. **تخطيط قلب**: 15,000 د.ع (تصنيف طبي `medical`).
    2. **اجور فتح طبلة**: 10,000 د.ع (تصنيف إداري `administrative`).
    3. **خدمات فندقية**: 30,000 د.ع (تصنيف إقامة `accommodation`).
  * منع تكرار الخدمات عند استدعاء الدالة مرات متكررة، وربطها فورياً بالعملية الحالية #4 (منير سمير يوسف).
- **تعديل السعر المباشر للخدمات الإضافية ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * إضافة مسار وتابع `CashierController::updateSurgeryExtraService` عبر `PATCH /surgeries/{surgery}/extra-services/{extraService}` لتعديل سعر وكمية الخدمة بالدينار العراقي فورياً عبر AJAX.
  * دعم قراءة مصفوفة `extra_service_prices` في معالجة دفع العملية `processSurgeryPayment` لحفظ وتحديث السعر المخصص الذي يكتبه الكاشير في قاعدة البيانات مباشرة واحتساب الحصص والوصولات بدقة.
- **تطوير واجهة دفع العمليات في الكاشير ([resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
  * **إلغاء زر الشمول الكلي ("الغاء زر شمول الكلي")**: إزالة كارت ومفتاح `#inclusive` العام لتمكين التحكم الفردي النقي والمستقل لكل خدمة دون أي تعارض.
  * **حقل السعر القابل للتعديل**: تزويد كل خدمة إضافية بحقل إدخال سعر مخصص `extra-service-price-input` بزيادات دينار ومحاذاة ممتازة، يعيد حساب حصة المريض ومطالبة الضمان والإجمالي لحظياً بمجرد الكتابة.
  * **زر الشمول الفردي لكل خدمة**: تفعيل مفتاح الشمول `package-inclusion-switch` المستقل لكل خدمة مع شارات واضحة `[ ✔️ مشمول بالعملية (0 د.ع) ]` أو `[ ✕ غير مشمول (منفصل) ]`.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/SurgeryExtraServicesTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryExtraServicesTest.php))**:
  * اجتياز كامل اختبارات الإلحاق التلقائي وتعديل السعر والدفع بالأسعار المخصصة بنسبة 100% (6 passed, 27 assertions)، واجتياز اختبارات دفع وتجزئة العمليات ولوحة الصالات (21 passed, 107 assertions).

## Session log (2026-11-05 — المرحلة 169: معالجة ظهور عمليات محطة الجراح، فك ازدواجية سجلات الأطباء، وتحديث واجهة المحطة السريرية Surgeon Station Identity Multiplicity Resolution, Direct Action Link & Modern Executive Redesign)

### Done
- **معالجة تصفية وصلاحيات محطة الجراح ([app/Http/Controllers/SurgeonStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeonStationController.php))**:
  * إضافة دالة `getSurgeonDoctorIds($user)` لجمع كافة معرّفات الطبيب المرتبطة بحساب المستخدم (بما فيها معرّف الطبيب الأساسي، معرّف الطبيب من `user_id`، ومعرّفات الأطباء المطابقة لنفس الاسم بعد تجريد البادئات `د.` و `دكتور`) لمعالجة الازدواجية التاريخية للأطباء المستوردين.
  * تحديث استعلام العمليات في `index()` واستعلام التحقق في `authorizeSurgeon()` لاستخدام `whereIn('doctor_id', $surgeonDoctorIds)`.
- **إدراج الأطباء الجراحين في نماذج العمليات ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
  * توسيع استعلام الأطباء في `create()` و `edit()` ليشمل كل طبيب يحمل دور الجراح (`role = 'الجراح' / 'surgeon' / 'جراح'`) بالإضافة إلى النوع والتخصص.
- **إعادة تصميم واجهة محطة الجراح وإضافة زر الدخول المباشر ([resources/views/surgery-stations/surgeon/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/surgeon/index.blade.php))**:
  * إضافة زر الإجراء السريري الأساسي `[ 🩺 فتح المحطة ]` (`route('surgeon-station.show', $surgery)`).
  * ترويسة تنفيذية أنيقة مع بطاقات KPI إحصائية للعمليات (إجمالي العمليات، بانتظار المعاينة، قيد المتابعة، ومكتملة ومُعتمدة)، وتنسيق جدول مريح وعصري.
- **حزم الاختبارات الآلية الشاملة**:
  * إنشاء واجتياز كامل اختبارات محطة الجراح ([tests/Feature/SurgeonStationAccessAndWorkflowTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeonStationAccessAndWorkflowTest.php)) بنسبة 100% (3 passed, 10 assertions).
  * استمرار اجتياز كافة اختبارات مصفوفة الصلاحيات وقوائم الانتظار والمحطات السريرية بنسبة 100%.

## Session log (2026-11-05 — المرحلة 168: توحيد طباعة طلبات تحاليل الطوارئ قبل إدخال النتائج وإظهار فحص CBC في أمر العمل مطابقةً لباقي الأقسام Emergency Lab Request Print & CBC Work Order Requisition Parity)

### Done
- **معالجة طباعة طلب الطوارئ كأمر عمل قبل إدخال النتائج ([resources/views/staff/requests/emergency-lab-print.blade.php](file:///C:/get/hospital-system/resources/views/staff/requests/emergency-lab-print.blade.php))**:
  * تعريف وضع أمر العمل الموحد بدقة عبر `$hasWorkOrder` للطلبات غير المكتملة التي لم تسجل نتائجها بعد (`!$isCompleted && !$hasActualResults && $requestedLabTestsList->count() > 0`).
  * تصحيح شرط الصفحة الأولى ليشمل أمر العمل `@if($hasStandardContent || $hasUrineContent || $hasWorkOrder)` لمنع ظهور الصفحة فارغة إطلاقاً.
  * تضمين فحص صورة الدم الكاملة (CBC) وكافة الفحوصات في قائمة أمر العمل دون استبعاد، مع عرض اسم الفحص وتصنيفه والخط المنقط لكتابة النتيجة يدوياً مع شارة `Pending`.
  * إبقاء ترويسة التقرير الرسمية `CLINICAL LABORATORY REPORT (EMERGENCY STAT)` متوافقة مع متطلبات النظام.
- **توحيد أزرار جدول الطوارئ مع باقي الأقسام ([resources/views/lab/index.blade.php](file:///C:/get/hospital-system/resources/views/lab/index.blade.php))**:
  * للطلبات المعلقة: زر `[ إدخال النتائج ]` باللون الأزرق وزر `[ 🖨️ طباعة الطلب ]` بـ `btn-outline-dark`.
  * للطلبات المكتملة: زر `[ معاينة ]` وزر `[ 🖨️ طباعة ]` بالأخضر وزر الواتساب.
- **حزم الاختبارات الآلية الشاملة**:
  * اجتياز كامل اختبارات محطة وتقارير الطوارئ ([tests/Feature/UnifiedEmergencyLabStationAndReportTest.php](file:///C:/get/hospital-system/tests/Feature/UnifiedEmergencyLabStationAndReportTest.php)) بنسبة 100% (11 passed, 69 assertions).

## Session log (2026-11-05 — المرحلة 167: حوكمة صلاحيات الأجهزة الطبية وإصلاح انتقال عمليات الصالة المكتملة للأرشيف وإعادة تصميم جدول العمليات بدقة الأدوار Surgery Master Registry Roles Governance, OT Exit Status Auto-Sync & Medical Devices Route Protection)

### Done
- **إصلاح انتقال العمليات المكتملة للأرشيف ([app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php), [app/Http/Controllers/OperationTheaterStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/OperationTheaterStationController.php))**:
  * تحديث `OperationTheaterStationController::complete()` ليقوم بتحديث حالة العملية إلى `status = 'completed'` وتعيين `end_time = now()` تلقائياً فور انتهاء الصالة.
  * تحديث استعلام العمليات النشطة في `SurgeryController@index` لاستبعاد أي عملية اكتملت صالة عملياتها (`whereDoesntHave('operationTheaterStation', fn($q) => $q->where('status', 'completed'))`).
  * تحديث استعلام العمليات المكتملة في `SurgeryController@index` ليضم تلقائياً أي عملية مكتملة أو انتهت محطة صالتها.
  * مزامنة السجلات السابقة في قاعدة البيانات لتتحول الحالات التي أتمت الصالة إلى `completed` وتنتقل فوراً لتبويب المكتملة.
- **حوكمة دقيقة للإجراءات والأعمدة بالأدوار ([resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php))**:
  * إخفاء عمود وفحوصات التشخيص والعلاج تماماً عن موظفي الاستقبال والاستعلامات (`@if(!auth()->user()->hasRole(['receptionist', 'inquiry_staff']))`).
  * حصر أزرار الإجراءات في الجدول بالأدوار: زر التعديل بـ `@can('edit surgeries')`، زر الحذف بـ `@can('delete surgeries')`، والطباعة بـ `surgeries.print`.
  * حصر زر إلغاء العملية داخل صف التفاصيل بكادر العمليات والإدارة وطبيب الحالة (`$canManageSurgery`) وفقط للعمليات النشطة الجارية.
  * المحافظة على صف تفاصيل الفحوصات المنسدل (`detailsRow`) للأشعة والمختبر والعلاجات بتصميم كروت وألوان متناسقة وعصرية.
  * تصميم عصري فخم بالكامل (ترويسة تنفيذية، بطاقات مؤشرات KPI دقيقة، وشريط بحث وتصفية ذكي).
- **حزم الاختبارات الآلية الشاملة**:
  * اجتياز اختبارات مصفوفة التبويبات والمحطة السريرية `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` بنسبة 100% (11 passed, 44 assertions).
  * اجتياز اختبارات لوحة الصالات وقائمة الانتظار `tests/Feature/SurgeryWaitingListFlightBoardTest.php` بنسبة 100% (8 passed, 28 assertions).

## Session log (2026-11-05 — المرحلة 166: إعادة التصميم العصرية لشاشة قائمة انتظار وتدفق العمليات الجراحية كلوحة تحكم ذكية وبطاقات مؤشرات تفاعلية Smart OR Flight Board Modern Redesign, Interactive KPI Filters & Clean Stage Progression)

### Done
- **إعادة تصميم عصرية شاملة لشاشة قائمة انتظار العمليات ([resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php))**:
  * **ترويسة تنفيذية فائقة الأناقة (Executive Header)**: أيقونة الصالات المتدرجة مع عنوان ناصع وشارة `OR Flight Board`، ومؤشر للتوقيت المباشر للمركز، وأزرار إجراءات تنفيذية أنيقة (حجز عملية جديدة بتدرج أزرق ملكي، والأرشيف العام، وتحديث فوري).
  * **شريط تقويم أسبوعي تفاعلي (Apple/Tailwind Segmented Strip)**: كبسولات أيام رشيقة بحدود ناعمة وتأثيرات رفع بصرية، تعرض اليوم والتاريخ وعدد العمليات، مع تمييز اليوم النشط بتدرج كحلي داكن فاخر، ونقطة ذهبية متوهجة لليوم الحالي.
  * **لوحة مؤشرات سريعة تفاعلية (Interactive KPI Stat Cards)**:
    - 5 بطاقات إحصائية للمسار: (إجمالي عمليات اليوم، مجدولة، بالانتظار - تحضير، داخل الصالة - جارية، ومكتملة - خرجت من الصالة).
    - **فلترة فورية بنقرة واحدة (Client-Side Instant Filter)**: النقر على أي بطاقة يفلتر جدول العمليات فوراً لإظهار الحالات المطابقة مع تمييز البطاقة النشطة وتحديث عداد العمليات الظاهرة دون إعادة تحميل الصفحة.
  * **شريط تقويم أسبوعي مدمج ورشيق**: كبسولات مصغرة أنيقة بنظام grid متوازن بدون سكرول يعرض اليوم والتاريخ والعدد بدقة دون إشغال مساحة عمودية كبيرة.
  * **تحديث حالة العملية إلى مكتملة (status = completed)**: فور ضغط إنهاء في الصالة، تتحول حالة العملية في قاعدة البيانات إلى `completed`، وتنتهي محطة الصالة، وتتغير شارة الحالة إلى `[ ✔️ مكتملة (خرجت من الصالة) ]`، وتتحول خانة الإجراءات إلى `[ ✔️ عملية مكتملة ]`، وتنتقل العملية من عداد داخل الصالة إلى عداد المكتملة.
  * **شريط بحث فوري ذكي**: حقل بحث مدور مع أيقونة وعدّاد حي للحالات المعروضة نسبةً للإجمالي (`عرض X من أصل Y عملية`).
  * **جدول الصالات الذكي (Master Flight Board Table)**:
    - هوية المريض: صورة رمزية متدرجة، الاسم بالخط العريض، شارات صغيرة لرقم الملف والعمر والجنس.
    - تفاصيل الإجراء: اسم العملية الجراحية مع شارة العمليات الإضافية إن وجدت.
    - شريحة الطبيب الجراح: كبسولة مريحة بأيقونة الطبيب.
    - تحديد مباشر وأنيق للصالة والوقت عبر مدخلات مدمجة ومريحة مع وميض تأكيد الحفظ اللحظي.
    - مؤقت عد تنازلي حي للعمليات الجارية داخل الصالة يختفي فور اكتمالها.
    - شارات المسار السريري الملونة: مجدولة، بالانتظار، داخل الصالة، خرج من الصالة (متابعة).
    - حصر عمود الإجراءات بأزرار تقدم المسار فقط: `[ دخول ]`، `[ تحويل وبدء ]`، و `[ إنهاء ]` (يختفي فور انتهاء الصالة وتتحول إلى شارة `✔️ تمت الصالة`)، بدون أي أزرار زائدة.
- **حوكمة صلاحية إعدادات تبويبات المحطة السريرية ([app/Http/Controllers/RoleManagementController.php](file:///C:/get/hospital-system/app/Http/Controllers/RoleManagementController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * تخصيص الصلاحية الرسمية `manage surgery tab permissions` لإدارة مصفوفة التبويبات السريرية.
  * إدراج الرابط في القائمة الجانبية فقط لمن يملك الصلاحية وإزالته تماماً من ترويسة قائمة الانتظار للمحافظة على نظافة لوحة العمليات.
- **حزم الاختبارات الآلية الشاملة**:
  * اجتياز اختبارات شاشة قائمة الانتظار `tests/Feature/SurgeryWaitingListFlightBoardTest.php` بنسبة 100% (8 passed, 28 assertions).
  * اجتياز اختبارات المحطة السريرية ومصفوفة الصلاحيات `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` بنسبة 100% (11 passed, 44 assertions).
  * اجتياز اختبارات محطة المقيم ما بعد الصالة `tests/Feature/ResidentStationPostOpTest.php` بنسبة 100% (1 passed, 3 assertions).
  * اجتياز اختبارات صرف التمريض من عهدة الطابق `tests/Feature/NursingFloorDispenseTest.php` بنسبة 100% (4 passed, 27 assertions).

## Session log (2026-11-05 — المرحلة 165: مزامنة انتقال المريض لمحطة المقيم ما بعد الصالة وترشيق أزرار وشارات قائمة انتظار العمليات Resident Post-Op Lifecycle Sync & Waiting List Complete Button Refinement)

### Done
- **مزامنة محطة المقيم ما بعد العملية ([app/Http/Controllers/ResidentStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ResidentStationController.php))**:
  * تصحيح استعلام مرحلة ما بعد الصالة للمقيم (`postOpSurgeries`) ليتطابق مع التمريض، بحيث ينتقل المريض للمقيم فور اكتمال صالة العمليات (`operationTheaterStation->status === 'completed'`) أو اكتمال التخدير دون اشتراط التخدير حصراً.
  * استبعاد الحالات التي أتمت الصالة من جدول ما قبل الصالة لدى المقيم لضمان نظافة الطابور.
- **ترشيق شاشة قائمة الانتظار ([resources/views/surgeries/waiting.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/waiting.blade.php))**:
  * **اختفاء زر "إنهاء" فور اكتمال الصالة**: فحص `$isOtCompleted` لإخفاء زر إنهاء الصالة الأحمر نهائياً ومنع التكرار، وإظهار شارة خضراء مميزة `[ ✔️ تمت الصالة ]`.
  * **تحديث شارة الحالة**: تحول الشارة فوراً من `داخل الصالة (جارية)` إلى `[ 🏁 خرج من الصالة (متابعة) ]` وإيقاف عداد وقت الصالة.
  * **تنظيف عمود الإجراءات**: حذف الأزرار والأيقونات الزائدة وغير الضرورية، والاكتفاء بالزر السريري المباشر `[ 🩺 المحطة السريرية ]` وأزرار المسار الفعلي.
- **حزم الاختبارات الآلية الشاملة**:
  * إنشاء واجتياز اختبار الميزة ([tests/Feature/ResidentStationPostOpTest.php](file:///C:/get/hospital-system/tests/Feature/ResidentStationPostOpTest.php)) بنجاح 100% (1 passed, 3 assertions).
  * اجتياز اختبارات شاشة قائمة الانتظار `SurgeryWaitingListFlightBoardTest` بنسبة 100% (8 passed, 28 assertions).

## Session log (2026-11-05 — المرحلة 164: منظومة صرف التمريض المباشر من عهدة مخزن الطابق والربط التلقائي بغرفة المريض وخصم الرصيد اللحظي Nursing Floor Sub-Store Direct Patient Dispensing, Auto Floor-Store Inference & Instant Stock Deduction)

### Done
- **الاستنتاج التلقائي لمخزن عهدة الطابق ([app/Models/Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php))**:
  * إضافة دالة `Surgery::getFloorSubStoreLocation()` لقراءة طابق غرفة المريض تلقائياً من علاقة الغرفة `room->floor` وربطه بالمخزن الفرعي المناظر له في `locations` (مثل: ردهة الطابق الثالث #24 لمرضى الطابق الثالث) دون تدخل يدوي لمنع الأخطاء.
- **توسيع قاعدة البيانات ومايجريشن الربط ([database/migrations/2026_11_05_000001_add_floor_dispense_fields_to_tables.php](file:///C:/get/hospital-system/database/migrations/2026_11_05_000001_add_floor_dispense_fields_to_tables.php))**:
  * ربط حركات المخزون `stock_movements` بالعملية السريرية `surgery_id` وحقل الملاحظات `notes`.
  * ربط علاجات المريض `surgery_treatments` بالوجبة المخزنية `batch_id` وحركة المخزون `stock_movement_id` والكمية `quantity`.
- **محرك الصرف المخزني السريري ([app/Http/Controllers/ClinicalStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/ClinicalStationController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * إنشاء مسار الإرسال `clinical-station.treatments.dispense-floor-stock`.
  * تطبيق دالة `dispenseFloorItem`: فحص صلاحية الكتابة، قفل الوجبة في قاعدة البيانات `lockForUpdate`، التحقق من توفر الرصيد في عهدة طابق المريض، خصم الرصيد فوراً `StockBatch->decrement('current_qty', $qty)`، توثيق الحركة `StockMovement` بنوع `issue_to_patient`، وتوثيق العلاج كمنفذ `administered` في طبلة المريض.
- **تطوير واجهة طبلة المريض السريرية ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php))**:
  * إبراز شارة عهدة الطابق المعتمدة لغرفة المريض في ترويسة تبويب الأدوية.
  * زر ونافذة منبثقة تفاعلية (Modal) لصرف الأدوية والمستلزمات مع تحديد أقصى كمية مسموحة برصيد الوجبة في الطابق.
  * شارة تفصيلية لبيانات العهدة والوجبة بجانب كل مادة في جدول الأدوية الموصوفة.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/NursingFloorDispenseTest.php](file:///C:/get/hospital-system/tests/Feature/NursingFloorDispenseTest.php))**:
  * إنشاء واجتياز اختبار الميزة بنسبة 100% (4 passed, 27 assertions).
  * استمرار اجتياز كافة اختبارات المحطة السريرية السابقة `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` (9 passed, 39 assertions).

## Session log (2026-11-05 — المرحلة 163: دمج وتفعيل إضافة Understand Anything وتوليد شبكة المعرفة والعلاقات التفاعلية للمشروع Understand Anything Knowledge Graph & Interactive Dashboard Integration)

### Done
- **تجهيز وبناء حزمة الإضافة (`Understand Anything`)**:
  * تثبيت بيئة العمل ومكتبات التحليل الـ AST اللغوية (`tree-sitter-php`, `tree-sitter-javascript`, `graphology`, `elkjs`).
  * بناء الحزمة الأساسية `@understand-anything/core` بنجاح وتجهيز بيئة الـ Dashboard المبنية على Vite 6 و React 19 و @xyflow/react.
- **توليد شبكة المعرفة والعلاقات التفاعلية (`.ua/knowledge-graph.json`)**:
  * إنشاء محرك التوليد الآلي المستدام `scripts/build_ua_graph.mjs` لفحص الـ Models, Controllers, Services, والـ Routes.
  * استخراج **221 مكوناً وعقدة (Nodes)** و **983 علاقة وارتباط حقيقي (Edges)** تشمل استدعاءات الـ Controllers، علاقات الـ Eloquent (`hasMany`, `belongsTo`, `belongsToMany`)، والمسارات الحية.
  * تقسيم النظام إلى **7 طبقات معمارية (Layers)** واضحة باللغة العربية (الاستشارية، الكاشير والخزينة، المحطات التشخيصية، الرقود والعمليات، الحاضنات والخدج، المخازن والصيدلية، والبنية التحتية).
  * إضافة **جولة تعليمية إرشادية بـ 5 مسارات سريرية ومالية (Guided Tour)** تشرح مسارات المريض والتحصيل والعمليات خطوة بخطوة بالرسم التفاعلي.
- **تشغيل الـ Dashboard التفاعلي المحلي**:
  * تشغيل خادم الـ Dashboard المحلي عبر Vite والتوثيق الآمن بـ Access Token وربطه مع المتصفح.

## Session log (2026-11-04 — المرحلة 163: الورقة الثانية للاستبيان (تمريض، مختبر، صيدلية)، إفراغ الإيميل في الطباعة، واللوحة المدمجة للنتائج بـ 27 معياراً Inpatient Survey Page 2 Clinical Services, Blank Print Email & Compact 2-Column Results Dashboard)

### Done
- **إضافة الورقة الثانية للاستبيان الورقي الرسمي للمستشفى (16 معياراً سريرياً جديداً)**:
  * مايجريشن قاعدة البيانات `2026_11_04_000002_add_page2_services_to_inpatient_room_surveys_table`:
    1. **طاقم التمريض** (6 معايير: الترحيب والمهنية، المهارة، الاهتمام المستمر، ووضوح التعليمات، سرعة الاستجابة، والخصوصية والكرامة + ملاحظات التمريض).
    2. **قسم المختبر والتحاليل** (5 معايير: تعليمات التحضير، مهارة سحب العينة، التعقيم، سرعة النتائج، والتعامل اللطيف + ملاحظات المختبر).
    3. **قسم الصيدلية والأدوية** (5 معايير: سرعة وصحة الصرف، ووضوح الشرح، التأكد من الفهم، اللباقة والاحترام، والتوفر الكامل للأدوية + ملاحظات الصيدلية).
    4. **بيانات المريض والمرافق**: الاسم، التوقيع، رقم الهاتف، والبريد الإلكتروني، واحتساب نسب الأقسام تلقائياً (`room_score`, `nursing_score`, `lab_score`, `pharmacy_score`, `overall_score`).
- **إفراغ الإيميل في الطباعة والاستبيان وعدم جلبه من جدول المستخدمين**:
  * تفريغ خانة الإيميل في قالب الطباعة A4 المعتمد `print-a4.blade.php` لتكون سطراً منقطاً فارغاً تماماً ومطابقاً للنموذج الورقي الرسمي للمستشفى.
  * جعل مدخل الإيميل في الاستبيان العام عبر الموبايل فارغاً افتراضياً وعدم استيراد إيميل حساب المريض من جدول المستخدمين.
  * تنظيف السجلات التجريبية في قاعدة البيانات لضمان عدم ظهور أي إيميلات وهمية.
- **إعادة تصميم لوحة نتائج الاستبيان المكتمل لتكون مدمجة وأنيقة (Compact 2-Column Dashboard)**:
  * استبدال الشبكة القديمة العملاقة (27 بطاقة ضخمة ممتدة على 3 أعمدة تسبب سكرول هائل) بلوحة تحكم تنفيذية مدمجة من عمودين متوازنين:
    * **العمود الأول**: مرافق الغرفة (10 معايير) + زيارة الطبيب المعالج (معيار 1).
    * **العمود الثاني**: طاقم التمريض (6 معايير) + قسم المختبر (5 معايير) + قسم الصيدلية (5 معايير).
    * كل صف يمثل بنداً مدمجاً رشيقاً (ارتفاع ~36px) بأيقونة ملونة، النجوم الذهبية المصغرة، وشارات تقييم ملونة ناصعة (`5/5 ممتاز`، `4/5 جيد جداً`، `3/5 جيد`، `2/5 مقبول`، `1/5 ضعيف`).
    * عرض بطاقات أولويات التحسين وملاحظات الأقسام والمريض أسفل الجدولين بشكل منظم ومضغوط.
- **التوافقية والرفع المباشر على main**:
  * مزامنة التفرع مع السيرفر البعيد عبر `git pull --rebase origin main` وإعادة الدمج بدون أي تعارض.
  * اجتياز كامل حزمة اختبارات الاستبيان `tests/Feature/InpatientRoomSurveyTest.php` (10 passed, 74 assertions).
  * اجتياز كامل حزمة اختبارات المحطة السريرية `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` (9 passed, 39 assertions).

## Session log (2026-11-04 — المرحلة 162: الربط التلقائي لتصفيات الضمان الصحي للخدج واحتساب فرق مطالبة المستشفى ومصفوفة عمر الحمل بالخزينة والحسابات NICU Insurance Automatic Settlement, Hospital Claim Margin & Gestational Matrix Treasury Integration)

### Done
- **محرك الفوترة الحية والاحتساب التلقائي ([app/Services/NicuBillingService.php](file:///C:/get/hospital-system/app/Services/NicuBillingService.php))**:
  * ترقية عداد الأيام التراكمية في السجل المفتوح (`activeOpenDays`) ليتم احتسابه حياً وتلقائياً بحسب تاريخ الدخول والتوقيت الفندقي وتحديث قاعدة البيانات دون الحاجة لتدخل يدوي.
  * احتساب مصفوفة عمر الحمل الوزارية بدقة ($\ge 34$, 32-34, 28-32, $<28$ أسبوعاً) مع تحديد حصة المريض وحصة الضمان لكل يوم إقامة.
  * تحديث وحفظ `patient_share_amount` و `insurance_share_amount` تلقائياً على نموذج `IncubatorReservation` ليمثل الفرق الصافي الذي تطلبه المستشفى من هيئة الضمان.
- **خزينة المستشفى المركزية ([app/Http/Controllers/HospitalTreasuryController.php](file:///C:/get/hospital-system/app/Http/Controllers/HospitalTreasuryController.php))**:
  * إدراج وتوحيد مطالبات وتصفيات الخدج والحاضنات (`insurance_share_amount`) مباشرة داخل كارت "مطالبات الضمان الصحي للفترة" التراكمي في واجهة الخزينة كذمم مدينة للمستشفى.
- **كشوفات حسابات الخدج لدى الكاشير ([resources/views/cashier/nicu-statements.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-statements.blade.php), [app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * إضافة فلتر نوع التغطية المالية (الكل / ضمان فقط / نقدي فقط).
  * إضافة كارت إحصائي بارز: `[ 🛡️ مطالبات الضمان للخدج (تطلبها المستشفى) ]`.
  * ترقية جدول الكشوفات لإبراز: عمر الحمل بالأسابيع، إجمالي الفاتورة الكاملة، مطالبة الضمان، حصة المريض، المسدد نقداً، وموقف المريض المالي.
- **لوحة تحليلات محاسب الخدج ([app/Http/Controllers/AccountantController.php](file:///C:/get/hospital-system/app/Http/Controllers/AccountantController.php), [resources/views/accountant/nicu/analytics.blade.php](file:///C:/get/hospital-system/resources/views/accountant/nicu/analytics.blade.php))**:
  * إضافة كارت إحصائي لمطالبات الضمان للخدج خلال الفترة المحددة.
  * إضافة جدول تحليلي وتفصيلي لمطالبات الضمان مصنفة وفق مصفوفة عمر الحمل الوزارية.
- **طبلة الخديج السريرية ([resources/views/nicu/show.blade.php](file:///C:/get/hospital-system/resources/views/nicu/show.blade.php))**:
  * إبراز مربع مالي علوي لمطالبة الضمان في ترويسة الطفل بجانب عداد الساعات والأيام وحصة المريض.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/NicuInsuranceSettlementAndClaimsTest.php](file:///C:/get/hospital-system/tests/Feature/NicuInsuranceSettlementAndClaimsTest.php))**:
  * إنشاء واجتياز اختبار الميزة بنسبة 100% (4 passed, 18 assertions).
  * استمرار اجتياز كافة اختبارات حسابات وتدفقات الخدج السابقة: `tests/Feature/NicuAccountingTest.php` (4 passed, 21 assertions)، `tests/Feature/NicuLabRadiologyCashierWorkflowTest.php` (4 passed, 82 assertions)، و `tests/Feature/NicuWorkflowTest.php` (39 passed, 341 assertions).

## Session log (2026-11-04 — المرحلة 161: حل ظهور شعار المستشفى محلياً وعلى هوستنجر بتضمين Base64 الآمن لصفحات الاستبيان والطباعة Resilient Base64 Hospital Logo Embedding for Surveys & Print)

### Done
- **تشخيص ومعالجة سبب عدم ظهور الشعار محلياً**:
  * دالة `asset('images/1.jpg')` تعتمد على `APP_URL` الموجه إلى `localhost` مباشرة، بينما في بيئة WAMP يتم تشغيل المشروع من المجلد الفرعي `/hospital-system/public/` فينتج خطأ 404 محلياً ويختفي الشعار، بينما على هوستنجر يعمل لأن الدومين يشير مباشرة لمجلد الـ public.
- **تطبيق تقنية Base64 Data URI الموحدة**:
  * قراءة الشعار مباشرة عبر `public_path('images/1.jpg')` (مع التبديل التلقائي لـ `logo.png` كخيار احتياطي) وتضمينه كـ Base64 Data URI داخل كود الـ HTML والـ CSS.
  * تحديث جميع قوالب الاستبيان والطباعة المعنية:
    1. نموذج تقرير الرقود المعتمد A4 (`resources/views/surveys/print-a4.blade.php`) للترويسة والعلامة المائية.
    2. كرت الاستبيان الحراري 80mm (`resources/views/surveys/print-qr.blade.php`).
    3. صفحة تعبئة الاستبيان العامة للمريض على الموبايل (`resources/views/surveys/inpatient-public.blade.php`).
- **حزم الاختبارات الآلية الشاملة**:
  * اجتياز اختبارات استبيان الرقود بنسبة 100%: `tests/Feature/InpatientRoomSurveyTest.php` (10 passed, 65 assertions).
  * اجتياز اختبارات المحطة السريرية الموحدة بنسبة 100%: `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` (9 passed, 39 assertions).

## Session log (2026-11-04 — المرحلة 160: نمط العرض الثنائي لاستبيان الرقود باركود عملاق في حالة الانتظار ولوحة نتائج شاملة لجميع المفردات عند الاكتمال مع الإخفاء التام للباركود Inpatient Survey 2-State Layout: Grand Centered QR on Pending & Full-Width 11-Item Scorecard on Completion with Total QR Elimination)

### Done
- **إلغاء نموذج الإدخال اليدوي نهائياً من المحطة السريرية**:
  * حذف جدول الإدخال اليدوي للممرض بالكامل لتبسيط الشاشة والاعتماد 100% على تقييم المريض ومرافقه الذاتي عبر الموبايل بخصوصية تامة.
- **الحالة الأولى (بانتظار التقييم - Pending)**:
  * عرض باركود عملاق عالي الدقة (340x340) في منتصف الشاشة بعرض بطاقة مخصصة فخمة مع رقم الغرفة واسم المريض.
  * شريط علوي أنيق يحتوي على أزرار الإجراءات: طباعة كرت حراري للمريض (80mm)، طباعة تقرير A4، استبيان فارغ A4، تكبير بملء الشاشة، وزر تحديث فوري.
  * زر مباشر لفتح رابط التقييم وزر نسخ الرابط مع تأكيد النسخ الفوري.
- **الحالة الثانية (عند اكتمال التقييم - Completed)**:
  * **إخفاء الباركود ونافذة تكبيره كلياً من الصفحة**، واستبدالها بلوحة نتائج تفصيلية شاملة بكامل عرض الشاشة.
  * **شريط تنفيذي علوي ناصع**: نسبة التقييم الإجمالية بلون ذكي ودائرة تقييم بارزة، وسيلة التقديم، مؤشر توصية المرضى (NPS / 10)، توقيت الإرسال، وزر إعادة فتح وتصفير الاستبيان للمدير (Admin Reset).
  * **شبكة النتائج الشاملة للمفردات الـ 11**: عرض النظافة العامة، نظافة الحمام، التكييف، الإضاءة، الهدوء، راحة السرير، لوحة التحكم، مساحات التخزين، جودة الأثاث، الترفيه، وتواصل الطبيب المعالج مع النجوم وشارات التقييم وأشرطة التقدم الملونة.
  * **بطاقات أولويات التحسين وملاحظات المريض النصية**: عرض أهم 3 أولويات تحسين حددها المريض ومقترحاته وملاحظاته على الغرفة والطبيب.
- **حزم الاختبارات الآلية الشاملة**:
  * اجتياز اختبارات استبيان الرقود بنسبة 100%: `tests/Feature/InpatientRoomSurveyTest.php` (10 passed, 65 assertions).
  * اجتياز اختبارات المحطة السريرية الموحدة بنسبة 100%: `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` (9 passed, 39 assertions).

## Session log (2026-11-04 — المرحلة 159: ترويسة إكلينيكية تنفيذية فائقة العصرية بنظام الأعمدة والتبديل الزجاجي Ultra-Modern Hospital EHR Executive Header with Data Pillars & Glass Segmented Control)

### Done
- **ترقية معمارية الترويسة إلى مستوى المستشفيات العالمية (Hospital EHR Standard)**:
  * بطاقة بيضاء ناصعة وفخمة مع شريط إكلينيكي علوي ناعم (3px Gradient) وظل محيطي خفيف (`0 4px 20px -4px rgba(15, 23, 42, 0.05)`).
  * أفاتار طبي متدرج (`.avatar-modern-box`) بأبعاد 48x48px وظل أزرق ملكي خفيف.
  * اسم المريض كحلي داكن ناصع (`#0f172a` 1.22rem) مع شارة كود الملف الطبية (`#P-ID`).
- **التقسيم المعماري بالأعمدة الذكية (Data Pillars)**:
  * **عمود 1 (هوية المريض):** الأفاتار، الاسم، رقم الملف، العمر بشارة ناعمة، والجنس.
  * **فاصل رأسي ناعم (Divider):** يفصل هوية المريض عن بيانات الإجراء.
  * **عمود 2 (العملية والجراح):** مسمى الإجراء الجراحي بخط واضح، واسم الجراح برتبة طبية خضراء (`badge-doctor-chip`).
  * **فاصل رأسي ناعم (Divider):** يفصل الإجراء عن الموقع والجدولة.
  * **عمود 3 (الموقع والجدولة):** رقم الغرفة/الصالة بشارة ذهبية (`badge-room-chip`) وساعة الموعد بشارة رمادية (`badge-time-chip`).
- **أزرار المرحلة بنظام Segmented Toggle (Apple iPadOS Style)**:
  * شريط كبسولي عائم ناعم (`.clin-segmented-control`) للتبديل الفوري بين (ما قبل العملية / ما بعد العملية) بزر أبيض عائم ذي ظل ناعم للمرحلة النشطة.
  * شارة حالة المرحلة مع نقطة نبض ملونة (`.status-pulse-dot`) تنبض بلون الحالة (مكتملة / قيد المتابعة / بالانتظار).
- **حزم الاختبارات الآلية الشاملة**:
  * اجتياز اختبارات المحطة السريرية بنسبة 100%: `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` (9 passed, 39 assertions).
  * اجتياز اختبارات استبيان الرقود بنسبة 100%: `tests/Feature/InpatientRoomSurveyTest.php` (8 passed, 48 assertions).

## Session log (2026-11-04 — المرحلة 158: ترويسة سريرية بيضاء ناصعة وتبويبات تابلت رشيقة وتحصين زر إعادة فتح الاستبيان للأدمن على هوستنجر Clean White Header, Tablet Auto-Fit Tabs & Resilient Hostinger Admin Survey Reset)

### Done
- **ترويسة طبية بيضاء عالية التباين والوضوح (Clean Medical White Header)**:
  * إلغاء السواد بالكامل واستبدال الترويسة ببطاقة بيضاء ناصعة (`#ffffff`) مريحة للعين مع خط كحلي داكن عريض وواضح لاسم المريض (`#0f172a`).
  * تنظيم بيانات الجراح والعملية والصالة والموعد داخل رقائق معلومات طبية (`.clin-info-chip`) واضحة بنسبة 100% بأيقونات ملونة.
  * تحويل أزرار تحديد المرحلة (Pre-Op / Post-Op) وشارات الحالة إلى وضعية طبية ناعمة وفاتحة.
- **تبويبات تابلت أفقية رشيقة بدون سكرول (Responsive Tablet Grid Tabs)**:
  * اختصار مسميات التبويبات الـ 9 إلى كلمات سريرية ذكية (السيرة، العلامات، الخطة، المتابعة، الأدوية، السوائل، التمريض، الاستبيان ⭐، الفحوصات) مع تلميح كامل (Tooltip).
  * توزيع شبكي مرن (`flex: 1 1 0px`) يتسع بالكامل على شاشات التابلت واللابتوب بعرض 100% دون أن يختفي أي تبويب ودون الحاجة لأي سكرول أفقي.
- **تحصين زر إعادة فتح الاستبيان للأدمن على هوستنجر**:
  * توسيع فحص صلاحية الأدمن الشامل `$canResetSurvey` في الكنترولر وواجهة العرض ليدعم: أدوار Spatie (`admin`, `super_admin`, `super-admin`, `hospital_admin`, `مدير النظام`)، وحقل جدول المستخدمين المباشر `role === 'admin'`، وصلاحيات الإدارة (`manage settings`, `manage surgery waiting list`).
  * إبراز الزر في مكانين: في شريط الاستبيان العلوي بجوار أزرار الطباعة، وداخل بطاقة نتائج التقييم المكتملة.
- **حزم الاختبارات الآلية الشاملة**:
  * اجتياز اختبارات المحطة السريرية بنسبة 100%: `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` (9 passed, 39 assertions).
  * اجتياز اختبارات استبيان الرقود بنسبة 100%: `tests/Feature/InpatientRoomSurveyTest.php` (8 passed, 48 assertions).

## Session log (2026-11-04 — المرحلة 157: استعادة التصميم الأصلي للمحطة السريرية بالتبويبات الأفقية الكاملة Clinical Station Original Full-Width Horizontal Tabs Restoration)

### Done
- **استعادة واجهة المحطة السريرية الأصلية بنسبة 100%**:
  * إعادة التبويبات الأفقية العلوية المريحة (`.clin-tabs-wrapper`) بكامل عرض الشاشة مباشرة أسفل شريط المريض.
  * إلغاء الشريط الجانبي والتقسيم الرأسي لتعود مساحة العمل كاملة وفسيحة لكافة أقسام طبلة المريض (السيرة المرضية، العلامات الحيوية، الفحوصات، الأدوية، السوائل، والمتابعة).
  * الحفاظ التام على كامل منظومة استبيان الرقود `inpatient_survey` وإجراءاتها وخياراتها (طباعة كرت الـ QR الحراري، طباعة تقرير A4، النموذج الفارغ، ونظام الصلاحيات).
- **حزم الاختبارات الآلية الشاملة**:
  * اجتياز اختبارات المحطة السريرية والصلاحيات بنسبة 100%: `tests/Feature/UnifiedClinicalStationAndTabPermissionsTest.php` (9 passed, 39 assertions).
  * اجتياز اختبارات استبيان الرقود بنسبة 100%: `tests/Feature/InpatientRoomSurveyTest.php` (8 passed, 48 assertions).



## Session log (2026-11-04 — المرحلة 156: منظومة استبيان وتقييم غرف الرقود والأطباء وتكامل طبلة المريض ورمز الـ QR الحراري Inpatient Room & Doctor Evaluation Survey with Patient QR & Unified Clinical Chart Integration)

### Done
- **تطبيق مواصفات Spec-Kit وترسانة ECC**:
  * صياغة وثيقة المواصفات الكاملة ([specs/002-inpatient-room-survey/spec.md](file:///C:/get/hospital-system/specs/002-inpatient-room-survey/spec.md)).
  * صياغة الخطة المعمارية والتقنية ([specs/002-inpatient-room-survey/plan.md](file:///C:/get/hospital-system/specs/002-inpatient-room-survey/plan.md)).
  * صياغة وتتبع قائمة المهام الذرية ([specs/002-inpatient-room-survey/tasks.md](file:///C:/get/hospital-system/specs/002-inpatient-room-survey/tasks.md)).
- **قاعدة البيانات ونموذج الاستبيان ([database/migrations/2026_11_04_000001_create_inpatient_room_surveys_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_04_000001_create_inpatient_room_surveys_table.php), [app/Models/InpatientRoomSurvey.php](file:///C:/get/hospital-system/app/Models/InpatientRoomSurvey.php))**:
  * إنشاء جدول `inpatient_room_surveys` برمز فريد `uuid`، وعلاقات مع العملية والمريض والغرفة والطبيب والمستخدم المدخل.
  * تقييم 10 معايير فندقية وخدمية خماسية (النظافة العامة، نظافة الحمام، التكييف/التدفئة، الإضاءة، الهدوء والخصوصية، راحة السرير، لوحة التحكم، مساحة التخزين، سلامة الأثاث، والترفيه والإنترنت).
  * تقييم زيارة وتواصل الطبيب المعالج خماسياً، والأسئلة المفتوحة (أهم 3 أولويات تحسين، مقترحات إضافية)، ومقياس التوصية بالمستشفى (NPS 1-10).
  * احتساب النسبة المئوية العامة تلقائياً `calculateOverallScore()` مع قفل الاستبيان بعد الإرسال لمنع التكرار.
- **المسارات والكنترولر المتكامل ([app/Http/Controllers/InpatientSurveyController.php](file:///C:/get/hospital-system/app/Http/Controllers/InpatientSurveyController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * مسارات عامة للمريض/المرافق عبر الموبايل بدون تسجيل دخول: `/rate/{uuid}`، `/rate/{uuid}` (POST)، و `/rate/{uuid}/thank-you`.
  * مسارات خاصة بالكادر السريري ومحطة التمريض: `/clinical/{surgery}/inpatient-survey` (إدخال يدوي مباشر) و `/clinical/{surgery}/inpatient-survey/print-qr` (طباعة إيصال حراري 80mm).
- **الواجهات التفاعلية وتكامل طبلة المريض ومحطة التمريض**:
  * واجهة موبايل عربية خفيفة وأنيقة لمسح الـ QR والتعبئة بلمسة واحدة ([resources/views/surveys/inpatient-public.blade.php](file:///C:/get/hospital-system/resources/views/surveys/inpatient-public.blade.php)).
  * شاشة الشكر والامتنان التفاعلية ([resources/views/surveys/thank-you.blade.php](file:///C:/get/hospital-system/resources/views/surveys/thank-you.blade.php)).
  * قالب طباعة إيصال الـ QR الحراري 80mm مع ترويسة المستشفى وباركود الاستبيان السريع ([resources/views/surveys/print-qr.blade.php](file:///C:/get/hospital-system/resources/views/surveys/print-qr.blade.php)).
  * دمج تبويب `[ ⭐ استبيان تقييم الرقود ]` في طبلة المريض السريرية ومحطة التمريض عبر `SurgeryTabPermission` مع بطاقة رمز الـ QR، وزر التكبير بملء الشاشة للمسح من بعد، ونموذج الإدخال اليدوي، وعرض النتائج المعتمدة ([resources/views/surgery-stations/clinical/show.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/clinical/show.blade.php)).
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/InpatientRoomSurveyTest.php](file:///C:/get/hospital-system/tests/Feature/InpatientRoomSurveyTest.php))**:
  * 5 اختبارات ميزة تغطي دورة الاستبيان كاملة بنجاح 100% (26 assertions): فتح الرابط بالـ UUID، إرسال تقييم الموبايل واحتساب النتيجة، قفل الاستبيان المكتمل وعرض شاشة الشكر، الإدخال اليدوي للتمريض، وطباعة كرت الـ QR الحراري.

## Session log (2026-11-03 — المرحلة 155: تطبيق مواصفات Spec-Kit وترسانة ECC ورادار وبحث عهدة الطابق ودورة إرجاع المواد الفائضة للمخزن الرئيسي Floor Sub-Store Stock Radar & Excess Item Return Workflow)

### Done
- **تطبيق منهجية Spec-Driven Development (SDD) بالمستندات الرسمية**:
  * صياغة وثيقة المواصفات الكاملة ([specs/001-floor-store-and-returns/spec.md](file:///C:/get/hospital-system/specs/001-floor-store-and-returns/spec.md)).
  * صياغة الخطة المعمارية والتقنية ([specs/001-floor-store-and-returns/plan.md](file:///C:/get/hospital-system/specs/001-floor-store-and-returns/plan.md)).
  * صياغة ومتابعة قائمة المهام الذرية ([specs/001-floor-store-and-returns/tasks.md](file:///C:/get/hospital-system/specs/001-floor-store-and-returns/tasks.md)).
- **رادار وبحث عهدة الطابق اللحظي وتصنيفاتها السريعة ([resources/views/surgery-stations/nursing/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/nursing/index.blade.php))**:
  * شريط بحث تفاعلي فائق السرعة بدون ريلود بالاسم والباركود والوجبة داخل تبويب عهدة الطابق (`#floor-store`).
  * شارات تصفية سريعة (`Pill Filters`): `[الكل]`، `[💊 أدوية]`، `[🩹 مستلزمات]`، `[⚠️ نفاد]`، `[⏳ قريبة الانتهاء]`.
  * زر إجرائي مباشر أمام كل وجبة: `[ ↩️ إرجاع ]` لفتح شاشة الإرجاع محملة بالوجبة تلقائياً.
  * زر رئيسي بترويسة التبويب لطلب إرجاع عهدة للمخزن الرئيسي.
- **منظومة إرجاع المخزون الذكية والمحصنة ([resources/views/stock-transfers/return.blade.php](file:///C:/get/hospital-system/resources/views/stock-transfers/return.blade.php), [app/Http/Controllers/StockTransferController.php](file:///C:/get/hospital-system/app/Http/Controllers/StockTransferController.php))**:
  * قفل المخزن المصدر على طابق الممرض الحالي ومنع التلاعب به.
  * حصر المواد على وجبات ورصيد المخزن الفرعي المتوفرة فعلياً (`current_qty > 0`).
  * دعم إضافة أسطر مواد متعددة وتوثيق الأسباب المعتمدة (`فائض`, `إلغاء عملية`, `قرب انتهاء`, `تالف`).
  * تصحيح خطأ صياغة Blade `@json` عبر تجهيز `$batchesData` مسبقاً في الكنترولر وتصحيح التوجيهات إلى `nursing-station.index`.
  * تحصين الكميات ومنع تجاوز الرصيد المتاح في المتصفح والخادم.
  * إبراز شارة `[ ↩️ إرجاع عهدة ]` وتوثيق أسباب الإرجاع في سجل الطلبات وسجل المعاينة.
- **حزم الاختبارات الآلية الشاملة**:
  * إنشاء واجتياز اختبار الميزة `tests/Feature/FloorStockReturnWorkflowTest.php` بنسبة 100% (5 passed, 29 assertions).
  * استمرار اجتياز كافة اختبارات عهدة التمريض السابقة `tests/Feature/FloorCustodianStockTransferTest.php` (11 passed, 52 assertions).

## Session log (2026-11-03 — المرحلة 154: تصحيح وتوافقية توجيه صيانة الحاضنات ودعم PATCH و POST وتحصين واجهة الخدج Incubator Maintenance Route Method Alignment & NICU Shielding)

### Done
- **معالجة خطأ `MethodNotAllowedHttpException` في صيانة الحاضنات ([routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * تحديث مسار `incubators/{incubator}/toggle-maintenance` ليدعم كلاً من الطريقتين `POST` و `PATCH` عبر `Route::match(['post', 'patch'], ...)` لمنع ظهور خطأ 405 عند إرسال توجيه `@method('PATCH')` من واجهة الحاضنة ([resources/views/incubators/show.blade.php](file:///C:/get/hospital-system/resources/views/incubators/show.blade.php)).
- **تحصين وتصحيح استدعاءات قاعدة البيانات في كنترولر الخدج ([app/Http/Controllers/NicuController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuController.php))**:
  * استيراد واجهة `Illuminate\Support\Facades\DB` المفقودة في `NicuController` لضمان عمل استعلامات مطابقة أدوية التمريض (MAR) بدون أي أخطاء `Class not found`.
- **حزم الاختبارات الآلية الشاملة**:
  * إنشاء ملف اختبار ميزة متكامل `tests/Feature/IncubatorMaintenanceTest.php`:
    - اختبار تبديل الصيانة عبر طريقة `PATCH` للمدير بنجاح (وضع الصيانة).
    - اختبار تبديل الصيانة عبر طريقة `POST` لموظف الخدج `nicu_staff` بنجاح (إنهاء الصيانة).
    - حماية الإجراء ومنع المستخدمين غير المصرح لهم عبر `403 Forbidden`.
    - اجتياز كامل الاختبارات بنسبة 100% (`3 passed, 9 assertions`).
  * اجتياز اختبار إعطاء أدوية التمريض في قسم الخدج بنجاح: `tests/Feature/NicuWorkflowTest.php` (`1 passed, 8 assertions`).

## Session log (2026-11-03 — المرحلة 153: دمج منظومات الموارد البشرية والرواتب وقسم الخدج والحاضنات ومركز العيون مع فرع main وتحصين المحطات الأساسية Complete Secure Integration of HR, NICU & Eye Center into Main with Core Stations Shielding)

### Done
- **الدمج الآمن لكافة منظومات فرع `hr` في `main` مع إبقاء فرع `hr` كاملاً**:
  * دمج نظام الموارد البشرية والرواتب (HR & Payroll): الموظفين، الرواتب الشهرية والمسيرات، السلف، الإجازات، الغيابات، الإضافي، الجزاءات والمكافآت، وتوزيع الشفتات والجدولة.
  * دمج قسم الخدج والحاضنات بالكامل (NICU & Incubators): رادار العمليات والولادات السريري المتقدم، قيود الإشغال الحية، مخططات النمو، الأوامر الطبية، جداول الرعاية والرضاعة، وإدارة حسابات وإيرادات الخدج المستقلة.
  * دمج مركز وجراحة العيون (Eye Center): استقبال العيون، الفحص العيادي والبصري (OD/OS)، فحص النظارات، العمليات الجراحية، كاشير العيون ومخزن العيون.
  * الحفاظ التام على فرع `hr` محلياً وعلى GitHub (`origin/hr`) دون حذفه كمرجع دائم.
- **تحصين وتصحيح المحطات الأساسية للنظام ومنع الأخطاء**:
  * فحص واجهة كاشير الإيصالات العامة وسندات القبض الحرارية (`showReceipt` / `printReceipt`) وإزالة الاستدعاءات غير المعرفة لتجنب أخطاء 500 في إيصالات العمليات والتحاليل.
  * تصحيح نوع حركة الخزينة المركزية للخدج إلى `hospital_revenue` لمنع كسر قيود قاعدة البيانات.
  * تصحيح استعلام مطابقة أدوية التمريض (MAR) ليكون متوافقاً ديناميكياً مع كافة قواعد البيانات.
- **حزم الاختبارات الآلية الشاملة**:
  * اجتياز كامل الاختبارات الآلية بنسبة 100% لكافة المحطات الأساسية:
    - محطة العمليات الجراحية: `tests/Feature/SurgeryTest.php` (4/4 passed).
    - محطة كاشير العمليات والدفع المركب: `tests/Feature/SurgeryCashierSplitAndInsurancePaymentTest.php` (7/7 passed).
    - محطة عهدة التمريض ومخازن الطوابق: `tests/Feature/FloorCustodianStockTransferTest.php` (11/11 passed).
    - محطة تحاليل العمليات وطباعتها السريرية: `tests/Feature/SurgeryLabTestUnifiedFlowTest.php` (8/8 passed).
    - محطة الموارد البشرية والرواتب: `tests/Feature/HREmployeeTest.php` (5/5 passed).
    - محطة حسابات الخدج: `tests/Feature/NicuAccountingTest.php` (4/4 passed).
    - محطة مركز وجراحة العيون: `tests/Feature/EyeCenterWorkflowTest.php` (15/15 passed).
    - محطة رعاية الخدج السريرية: `tests/Feature/NicuWorkflowTest.php` (39/39 passed).
  * رفع التحديثات إلى `origin/main` على GitHub بنجاح.

## Session log (2026-11-03 — المرحلة 152: تحديد وتعديل نوع حجز العملية ضمان أم كاش 100% في الحجز والتعديل وإبراز شارات التغطية وربطها التلقائي بالكاشير Surgery Reservation Insurance vs Cash Coverage Mode & Direct Cashier Alignment Flow)

### Done
- **تحديد وتعديل مسار حجز العملية (ضمان أم نقدي خاص كاش 100%) ([resources/views/surgeries/create.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/create.blade.php), [resources/views/surgeries/edit.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/edit.blade.php), [app/Http/Controllers/SurgeryController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgeryController.php))**:
  * بناء بطاقة تفاعلية بارزة وموحدة عند حجز أو تعديل أي مريض مؤمن:
    1. `[ 🔘 🛡️ حجز تحت مظلة الضمان الصحي ]`: تعامل العملية كعملية ضمان وتطبق عليها شروط الاستقطاع ونسب الفئة التأمينية ومطالبة الضمان.
    2. `[ 🔘 💵 حجز نقدي خاص (كاش كامل خارج الضمان) ]`: تعامل العملية كعملية نقدية 100% دون استهلاك رصيد المريض التأميني وتفتح للكاشير وضعية الكاش والخصم المالي.
  * تحديث دالة `SurgeryController@update` لدعم وتوثيق `apply_insurance` / `insurance_type` برمجياً عند تعديل العملية لحل مشكلة عدم إمكانية تغيير مسار العملية بعد إنشائها.
- **إبراز شارات التغطية المالية في السجلات والتفاصيل ([resources/views/surgeries/index.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/index.blade.php), [resources/views/surgeries/show.blade.php](file:///C:/get/hospital-system/resources/views/surgeries/show.blade.php))**:
  * إظهار شارة تصنيف واضحة أمام كل عملية جراحية في جدول العمليات اليومي والتاريخي وتفاصيل العملية:
    - شارة خضراء: `[ 🛡️ ضمان صحي ]` للعمليات المحجوزة تحت مظلة الضمان.
    - شارة رمادية: `[ 💵 نقدي (كاش) ]` للعمليات المحجوزة كاش 100%.
- **ربط مسار الحجز مباشرة بكاشير العمليات ([resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
  * عندما تحجز العملية نقداً (`insurance_type = 'none'`)، تفتح شاشة الكاشير مباشرة في الوضع النقدي 100%، ويظهر صندوق الخصم المالي الإداري للمريض، وتصفر مطالبات الضمان تماماً.
  * عندما تحجز العملية كضمان (`insurance_type = 'hi'` أو `'moi'`)، تفتح شاشة الكاشير بنسب استقطاع الضمان ومطالبة التأمين المعتمدة.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/SurgeryTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryTest.php), [tests/Feature/SurgeryCashierSplitAndInsurancePaymentTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryCashierSplitAndInsurancePaymentTest.php))**:
  * إضافة اختبار `test_surgery_insurance_vs_cash_reservation_mode_on_create_and_update` للتحقق من حجز المريض المؤمن كاش، وظهور الشارة في السجل، فتح شاشة التعديل بالخيار المحدد، والتحويل بين الكاش والضمان بنجاح (4 passed, 42 assertions).
  * اجتياز كامل اختبارات كاشير العمليات والشمولية بنجاح 100% (7 passed, 51 assertions).

## Session log (2026-11-03 — المرحلة 151: تحكم شمولية الخدمات والتحاليل الروتينية وتخطيط القلب لكل بند على انفراد في كاشير العمليات واحتساب نسب التحمل للضمان والنقدي Surgery Cashier Per-Item Package Inclusion & Multi-Service Copay Architecture)

### Done
- **محدد الشمولية لكل خدمة على انفراد (Per-Item Package Inclusion Toggle Switches) ([resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
  * استبدال آلية الإخفاء التام القديمة بزر تحكم عام تبادلي ذكي `✨ باقة شاملة بالكامل (All-Inclusive Surgery Package)` يُفعل أو يُلغي الشمول لجميع البنود دفعة واحدة دون إخفاء أي صف من الجدول.
  * إضافة مفتاح شمولية مستقل لكل خدمة على انفراد (`package_included[lab][id]`, `package_included[radiology][id]`, `package_included[extra][id]`):
    - التحاليل الروتينية والفيروسية (CBC, Viral, PT): مفعلة افتراضياً بشارة خضراء مميزة `[ 🟢 مشمول ضمن باقة العملية (0 د.ع) ]` وتظهر التكلفة `0 د.ع (مشمول)`.
    - تخطيط القلب السريري (ECG): قابل للتضمين بالاتفاق مع الجراح أو الاحتساب كخدمة منفصلة.
    - الخدمات الفندقية والإدارية والغرامات (مرافق، تزيين، طبلة): غير مشمولة افتراضياً وتحسب نقدية 100%.
- **تطبيق نسب التحمل التفصيلية متعددة الخدمات للضمان والنقدي ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * دعم كامل لمعالجة مصفوفة `package_included`:
    - البنود المشمولة (`package_included = 1`): تُحسب بصفر دينار على المريض وتتحول إلى `paid` برقم سند القبض مع تثبيت ملاحظة `(مشمول ضمن باقة العملية)` لفك التعليق عنها في المختبر والمحطات السريرية فوراً.
    - البنود غير المشمولة (`package_included = 0`):
      1. في العمليات النقدية (`none`): تُحتسب بالسعر النقدي الكامل المعتمد للخدمة.
      2. في عمليات الضمان الصحي (`hi` / `moi`): تُحتسب وفق نسب التحمل الدقيقة للفئة التأمينية عبر `getCopayPercentageFor` (استقطاع العمليات والغرف: 25%، استقطاع المختبر: 25%، استقطاع الأشعة: 25%، واستقطاع التخطيط والتمريض: 10% للفئة H)، مع تحويل الفارق لمطالبة الضمان الصحي.
  * الحفاظ على التوافق الرجعي 100% (Backward Compatibility) مع المتصلين القدامى والاختبارات السابقة.
- **تحديث محرك الجافاسكريبت اللحظي (`calculateSelectedAmount`)**:
  * حساب لحظي دقيق ومباشر لحصة المريض الصافية، إجمالي المطالبات التأمينية، الخصم النقدي الإداري، والمزامنة الفورية مع صندوق الدفع المركب (كاش + بطاقة).
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/SurgeryCashierSplitAndInsurancePaymentTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryCashierSplitAndInsurancePaymentTest.php))**:
  * 7 اختبارات ميزة ناجحة بنسبة 100% (51 assertions): تغطية عرض الواجهة بشارة الشمول، دفع الضمان مع شمول التحاليل، الدفع المركب، الحماية من عدم التطابق، الدفع الجزئي، العمليات النقدية مع التضمين/الاستبعاد الجزئي، واحتساب نسب التحمل التأمينية الدقيقة لكافة الخدمات.

## Session log (2026-11-03 — المرحلة 150: ترقية واجهة تسديد العمليات ودعم الدفع المركب كاش وبطاقة وشمول التحاليل الروتينية Surgery Cashier Split Payment & Included Pre-Op Routine Tests Flow)

### Done
- **شمول التحاليل الروتينية للعملية ضمن أتعاب الجراحة مجاناً ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
  * معاملة التحاليل المرافقة للعملية الجراحية كأول ليلة في الغرفة بحيث تكون مشمولة حكماً ضمن أتعاب الجراحة دون إضافة أي تكاليف جديدة على المريض (`0 د.ع`).
  * إظهار شارة خضراء مميزة في جدول الكاشير: `[ 🧪 مشمول ضمن باقة العملية (0 د.ع) ]`، مع تأشيرها تلقائياً لتسديدها وربطها برقم الإيصال (`payment_id`) لتتحول إلى `paid` مباشرة في المختبر وتأكيد الدفع دون زيادة في حصة المريض.
- **دعم الدفع المركب / المختلط (Split Payment: كاش + بطاقة ائتمان / POS)**:
  * توسيع جدول المدفوعات `payments` بحقلي `cash_amount` و `card_amount` وتوسيع حقل `payment_method` في المايجريشن والموديل `Payment`.
  * بناء صندوق تفاعلي ذكي فوري ومباشر بمجرد اختيار `🔄 دفع مركب / مختلط (مقسم كاش + بطاقة)` يتيح توزيع المبلغ بين النقد والبطاقة مع زر المناصفة (50/50) ومزامنة آلية وشارة تحقق وتطابق لحظية (`متطابق: X IQD`) وتحصين التقديم ضد عدم التطابق.
  * إبراز تفاصيل الدفع المركب في الإيصالات وسندات القبض الحرارية وشاشات العرض ([resources/views/cashier/receipt.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt.blade.php), [resources/views/cashier/receipt-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt-print.blade.php)).
- **ترقية وتحديث واجهة السداد ومطابقتها لواجهة تسديد الطلبات ([resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
  * ترويسة متدرجة وبطاقة سريرية لمعلومات المريض ونوع العملية ونسبة التحمل للضمان الصحي الوطني وقوى الأمن (HI / MOI).
  * دعم تسديد الرسوم جزئياً (دفعة مقدمة) أو كلياً مع الحساب اللحظي للمتبقي المؤجل.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/SurgeryCashierSplitAndInsurancePaymentTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryCashierSplitAndInsurancePaymentTest.php))**:
  * 5 اختبارات ناجحة بنسبة 100% (27 assertions): فحص الواجهة وظهور شارة شمول التحاليل، تسديد الضمان ووسم التحاليل كمدفوعة، تسديد الدفع المركب بنجاح وظهوره في الوصل، رفض الدفع المركب عند عدم تطابق المجموع، والتسديد الجزئي مع بقاء المتبقي معلقاً.

## Session log (2026-11-03 — المرحلة 149: ترقية وتحديث تصميم تقرير طباعة تحاليل العمليات الجراحية لمطابقة تقرير المختبر السريري الرسمي Surgery Lab Tests Official Clinical Print Report Modernization)

### Done
- **إعادة بناء قالب الطباعة بالكامل لمطابقة المختبر العام ([resources/views/staff/surgery-lab-tests/print.blade.php](file:///C:/get/hospital-system/resources/views/staff/surgery-lab-tests/print.blade.php))**:
  * استبدال التصميم القديم (ذي الزخارف البنية وإطار 2025 والتنسيق البدائي) بالتصميم الطبي السريري المعتمد من المستشفى والمطابق لتقرير `staff/requests/print.blade.php`.
  * ترويسة رسمية ثنائية اللغة للمستشفى وشعار مائي خفيف بالخلفية (`images/1.jpg`) مع شريط عنوان التقرير المتدرج: `تقرير الفحوصات المخبرية للعمليات الجراحية (SURGERY CLINICAL LABORATORY REPORT)`.
  * بطاقة سريرية رمادية ثلاثية المناطق (3-Zone Clinical Card):
    1. المنطقة اليسرى: اسم المريض بالعربية والإنجليزية (عبر `transliterateArabicName`)، العمر والجنس، ورقم الهاتف.
    2. المنطقة الوسطى: اسم الجراح المعالج، نوع العملية الجراحية، الموقع السريري ورقم الغرفة والطابق `[ 🛏️ غرفة 302 (الطابق الثالث) ]`، وتاريخ ووقت الفحص.
    3. المنطقة اليمنى: باركود ورمز استجابة سريعة مباشر QR Code للتحقق اللحظي من الفحص.
  * جدول تحاليل موحد ومبوب بالأقسام الرئيسية (Categories)، يعرض: اسم الفحص والرمز، النتيجة، الوحدة، المدى المرجعي المحسوب آلياً حسب جنس وعمر المريض (`LabTestReference::forPatient`)، وتقييم الحالة السريرية التفاعلي بشارات ملونة (`Normal`, `High`, `Low`, `Abnormal`, `Pending`).
  * دعم الفحوصات الفرعية (Sub-tests) وقسم التوصيات والملاحظات السريرية (`Clinical Notes & Recommendations`).
  * كتلة التواقيع والاعتماد الرسمي المزدوج (أخصائي المختبر + الختم الدائري المعتمد لمستشفى الفرات الأهلي).
  * شريط أدوات طباعة عائم متكامل: زر طباعة فوري، زر إرسال التقرير عبر واتساب، مبدل وضعية الطباعة (ورق أبيض كامل Full مقابل ورق مروس رسمي Letterhead مع تحكم بالمسافة العلوية)، وزر رجوع.
- **تطوير دالة الكنترولر ([app/Http/Controllers/StaffRequestController.php](file:///C:/get/hospital-system/app/Http/Controllers/StaffRequestController.php))**:
  * ترقية دالة `printSurgeryLabTest` لتحميل كافة العلاقات السريرية (`surgery.patient.user`, `surgery.doctor.user`, `surgery.room`, `surgery.department`, `labTest.references`, `labTest.subTests`).
  * إزالة قيود الحظر المسبق وتمكين طباعة الفحوصات سواء كانت مكتملة أو جارية أو كأمر عمل وتكليف مخبري (Requisition / Work Order).
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/SurgeryLabTestUnifiedFlowTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryLabTestUnifiedFlowTest.php))**:
  * إضافة اختبار `test_print_page_renders_modern_clinical_report_with_patient_surgery_and_test_details` للتحقق من سلامة مسار الطباعة، وعرض بيانات المريض والجراح والغرفة والنتائج والملاحظات ومطابقة الهوية البصرية (8 passed, 49 assertions).

## Session log (2026-11-03 — المرحلة 148: إلغاء ازدواجية التبويبات وتوحيد شاشة تحاليل العمليات بجدول واحد ومطابقة واجهة إدخال النتائج للمختبر العام Surgery Lab Tests Single Unified Table & General Lab Twin Results Entry Flow)

### Done
- **توحيد شاشة تحاليل العمليات بجدول واحد موحد ([resources/views/staff/surgery-lab-tests/index.blade.php](file:///C:/get/hospital-system/resources/views/staff/surgery-lab-tests/index.blade.php), [app/Http/Controllers/StaffRequestController.php](file:///C:/get/hospital-system/app/Http/Controllers/StaffRequestController.php))**:
  * إلغاء نظام التبويبين المنفصلين ودمج كافة مرضى العمليات في جدول واحد موحد يطابق واجهة المختبر العام `/lab`.
  * إضافة بطاقات الإحصائيات الأربعة وشريط الفلترة السريع بنمط Pills: `الكل`، `🟡 بانتظار تحديد الفحوصات`، `🟠 بانتظار الفحص / معلق`، و `🟢 المكتملة والمعتمدة`.
  * إبراز بيانات المريض والموقع السريري بوضوح: `[ 🛏️ غرفة 302 (الطابق الثالث) ]` وحالة دفع الرسوم المالية.
  * زر الإجراء الذكي: `[ 🧪 تحديد باقة التحاليل ]` للعمليات المحجوزة حديثاً، وزر `[ 👁️ إدخال النتائج / معاينة ]` مع زر الطباعة للعمليات التي حُددت فحوصاتها.
- **مطابقة واجهة إدخال النتائج لشاشة المختبر العام ([resources/views/staff/surgery-lab-tests/show.blade.php](file:///C:/get/hospital-system/resources/views/staff/surgery-lab-tests/show.blade.php), [app/Http/Controllers/StaffRequestController.php](file:///C:/get/hospital-system/app/Http/Controllers/StaffRequestController.php))**:
  * تزويد الصفحة بشريط إجراءات علوي وسفلي متكامل: `[ 💾 حفظ مؤقت (مسودة) ]`، `[ 🚀 اعتماد نهائي وإرسال ]`، `[ ➕ إضافة تحاليل ]` (إلحاق فحوصات جديدة عبر نافذة منبثقة Modal)، `[ 🖨️ طباعة التقرير ]`، و `[ 📲 إرسال النتيجة عبر واتساب ]`.
  * بطاقة مريض وسرير وعملية مدمجة ومطابقة لشاشة المختبر العام.
  * جدول إدخال النتائج مع جلب المرجع الطبيعي المعتمد آلياً حسب جنس وعمر المريض عبر `LabTestReference::forPatient()`، والتقييم اللحظي التفاعلي بالألوان (طبيعي / مرتفع / منخفض / غير مكتمل)، وشريط إحصاءات النتائج المباشر.
- **توسيع مايجريشن قاعدة البيانات ([database/migrations/2026_11_03_002201_add_notes_to_surgery_lab_tests_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_03_002201_add_notes_to_surgery_lab_tests_table.php))**:
  * إضافة عمود الملاحظات `notes` في جدول `surgery_lab_tests` وتوسيع حقل الحالة `status` ليشمل `in_progress`.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/SurgeryLabTestUnifiedFlowTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryLabTestUnifiedFlowTest.php))**:
  * تغطية 100%: فحص ظهور المريض في الجدول الموحد، بدء التحديد، تأكيد التحاليل والتحويل لشاشة النتائج، حفظ المسودة والاعتماد النهائي، إلحاق تحاليل إضافية، وظهور تنبيه المختبر العام (6 passed, 32 assertions).

## Session log (2026-11-02 — المرحلة 147: توحيد وربط منظومة تحاليل العمليات الجراحية بالمختبر العام وحل فراغ شاشة تحاليل العمليات Surgery Lab Tests Unified Tabs & General Laboratory Direct Integration)

### Done
- **توحيد شاشة تحاليل العمليات ([app/Http/Controllers/StaffRequestController.php](file:///C:/get/hospital-system/app/Http/Controllers/StaffRequestController.php), [resources/views/staff/surgery-lab-tests/index.blade.php](file:///C:/get/hospital-system/resources/views/staff/surgery-lab-tests/index.blade.php))**:
  * حل مشكلة ظهور شاشة `staff/surgery-lab-tests` فارغة بعد حجز العمليات من الاستعلامات؛ حيث كانت تشترط وجود تحاليل محددة مسبقاً (`whereHas('labTests')`).
  * بناء نظام تبويبين (Tabs) مدمجين ومتجاوبين:
    1. **تبويب 1: عمليات بانتظار تحديد التحاليل (`tab=pending_selection`)**: يعرض العمليات المحجوزة حديثاً مع اسم المريض ورقم الغرفة والطابق (`غرفة 302 - الطابق الثالث`) والجراح وموعد العملية وحالة العملية، مع زر بارز ومباشر `[ 🧪 تحديد باقة التحاليل ]` ينقل موظف المختبر لشاشة اختيار الفحوصات بضغطة زر واحدة.
    2. **تبويب 2: طلبات الفحوصات والنتائج (`tab=active_tests`)**: يعرض سجل التحاليل الجارية والمكتملة وإدخال النتائج والطباعة وإحصائيات الحالات.
  * التحويل الذكي التلقائي: تفتح الصفحة افتراضياً على العمليات بانتظار التحديد إذا لم تكن هناك طلبات جارية منعاً لظهور صفحة فارغة مطلقاً.
- **الربط مع شاشة المختبر الرئيسية اليومية ([app/Http/Controllers/LabStaffController.php](file:///C:/get/hospital-system/app/Http/Controllers/LabStaffController.php), [resources/views/lab/index.blade.php](file:///C:/get/hospital-system/resources/views/lab/index.blade.php))**:
  * إضافة بطاقة تنبيه ذكية ملفتة في أعلى شاشة المختبر `/lab`: `[ تنبيه: يوجد X عمليات جراحية محجوزة بانتظار تحديد باقة التحاليل! ]` مع زر تحويل فوري للصفحة المخصصة.
  * إضافة زر تحويل سريع في ترويسة الصفحة بجانب دليل التحاليل بشارة عدد العمليات المعلقة.
- **تحديث شارات القائمة الجانبية ([app/Providers/AppServiceProvider.php](file:///C:/get/hospital-system/app/Providers/AppServiceProvider.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * تحديث عداد شارة `تحاليل العمليات` في القائمة الجانبية ليشمل مجموع الفحوصات المعلقة والعمليات المحجوزة بانتظار التحديد لضمان عدم إغفال أي مريض.
- **إصلاح مايجريشن حقول الدفع ([database/migrations/2026_01_13_063756_add_payment_fields_to_surgery_lab_tests_table.php](file:///C:/get/hospital-system/database/migrations/2026_01_13_063756_add_payment_fields_to_surgery_lab_tests_table.php))**:
  * إضافة أعمدة `payment_status` و `payment_id` في مايجريشن جدول `surgery_lab_tests`.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/SurgeryLabTestUnifiedFlowTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryLabTestUnifiedFlowTest.php))**:
  * فحص ظهور المريض المحجوز حديثاً في تبويب تحديد التحاليل مع بيانات الغرفة والطابق.
  * فحص نجاح بدء اختيار التحاليل والتحويل لصفحة الفحوصات.
  * فحص ظهور بطاقة التنبيه والعداد في واجهة المختبر العامة (3 passed, 17 assertions).

## Session log (2026-11-02 — المرحلة 146: ربط دورة صالة العمليات وقائمة الانتظار بمحطة التمريض والرقود وإظهار غرف الطوابق تلقائياً Nursing Station Surgery Lifecycle Sync & Floor Ward Auto-Inference)

### Done
- **تحرير مسار الحالات في محطة التمريض ([app/Http/Controllers/NursingStationController.php](file:///C:/get/hospital-system/app/Http/Controllers/NursingStationController.php))**:
  * حل مشكلة اختفاء المرضى المحجوزين عند تسجيل الدخول `checkIn` (حالة `waiting`) أو بدء العملية `start` (حالة `in_progress`)، حيث تم توسيع استعلام ما قبل الصالة (`preOpQuery`) ليشمل الحالات في قائمة الانتظار وقيد التجهيز طالما لم تنتهِ الصالة بعد.
  * فك الارتباط الحصري بين متابعة ما بعد الصالة (`postOpQuery`) ومحطة التخدير فقط، ليشمل تلقائياً اكتمال محطة صالة العمليات (`operationTheaterStation->completed`) التي يتم التحكم بها مباشرة من قائمة انتظار العمليات (`surgeries/waiting`)، أو اكتمال التخدير، أو اكتمال العملية، لتمكين ممرض الطابق من تسجيل العلامات الحيوية وصرف علاجات ومستهلكات الرقود فور خروج المريض من الصالة.
  * الاستنتاج الذكي لطابق الممرض تلقائياً من مخزن عهدته المعتمد (`ردهة الطابق الثالث` ⬅️ `الطابق الثالث`) لفتح مرضى وغرف طابقه مباشرة دون الحاجة للبحث اليدوي.
- **حوكمة واجهة محطة التمريض ([resources/views/surgery-stations/nursing/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/nursing/index.blade.php))**:
  * إبراز رقم الغرفة والطابق بشكل لافت وواضح تحت اسم المريض في كافة الجداول (ما قبل الصالة، ما بعد الصالة، والأرشيف) بشارة: `[ 🛏️ غرفة 302 (الطابق الثالث) ]`.
  * إضافة شريط فرز سريع للطوابق (`[ كافة الطوابق | الطابق الثاني | الطابق الثالث (طابقك) ]`) مع شارة مميزة لطابق عهدة الممرض لسهولة التنقل السريع عند الحاجة.
  * استبدال النصوص والشارات المبهمة بشارات حالة دقيقة (`بانتظار الصالة (مسجل دخول)`، `قيد التجهيز / الصالة`، `خرج من صالة العمليات`، و `متابعة ورعاية جارية`).
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/NursingStationSurgeriesTest.php](file:///C:/get/hospital-system/tests/Feature/NursingStationSurgeriesTest.php))**:
  * فحص ظهور المريض المحجوز في الغرفة 302 لممرضة الطابق الثالث مريم.
  * فحص بقاء العملية في مرحلة ما قبل الصالة عند تسجيل الدخول وتحولها إلى `waiting`.
  * فحص انتقال العملية تلقائياً إلى تبويب ما بعد الصالة (المتابعة) عند اكتمال صالة العمليات على `surgeries/waiting` (3 passed, 13 assertions).

## Session log (2026-11-02 — المرحلة 145: حظر تعديل وحذف طلبات المخازن الفرعية على أمين المخزن الرئيسي وقصر دوره على الاعتماد أو الرفض الرقابي Storekeeper Governance Guard & Strict Requester-Only Modification Lock)

### Done
- **حوكمة الرقابة المخزنية وفصل الصلاحيات ([app/Http/Controllers/StockTransferController.php](file:///C:/get/hospital-system/app/Http/Controllers/StockTransferController.php))**:
  * حجب أزرار `[ ✏️ تعديل ]` و `[ 🗑️ إلغاء ]` عن أمين المخزن الرئيسي في سجل وتفاصيل طلبات المخازن الفرعية.
  * حظر مسارات وعمليات الـ `PUT` والـ `DELETE` برمجياً في `StockTransferController` على أمين المخزن وإرجاع `403 Forbidden`.
  * حصر التعديل والإلغاء حصراً على مقدم الطلب أو مسؤول عهدة الطابق المستلم، أو الإدارة العليا التقنية (`super_admin`).
  * الإبقاء على صلاحية أمين المخزن الرقابية فقط: `الموافقة وصرف المواد` أو `رفض الطلب` مع ذكر السبب لحفظ أثر التدقيق (Audit Trail) ومنع تضارب العهدة الجردية.
- **حوكمة الواجهات ([resources/views/stock-transfers/requests/index.blade.php](file:///C:/get/hospital-system/resources/views/stock-transfers/requests/index.blade.php), [resources/views/stock-transfers/requests/show.blade.php](file:///C:/get/hospital-system/resources/views/stock-transfers/requests/show.blade.php))**:
  * لا تظهر أزرار التعديل والإلغاء لأمين المخزن بل يظهر زر `[ 👁️ عرض ]` فقط في السجل، وزري `[ الموافقة والصرف ]` و `[ الرفض ]` فقط في شاشة التفاصيل.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/FloorCustodianStockTransferTest.php](file:///C:/get/hospital-system/tests/Feature/FloorCustodianStockTransferTest.php))**:
  * إضافة اختبار `test_warehouse_manager_cannot_edit_or_delete_sub_store_requests`: التحقق من عدم ظهور الأزرار ورفض محاولات الـ PUT والـ DELETE بـ 403 Forbidden (11 passed, 52 assertions).

## Session log (2026-11-02 — المرحلة 144: تمكين وتأمين تعديل وإلغاء طلبات تعزيز المخزون المعلقة لكادر التمريض والعهدة Nursing Station Sub-Store Pending Request Edit & Cancel Workflow)

### Done
- **مسارات التعديل والتحديث والإلغاء ([routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * إضافة المسارات `stock-transfers.requests.edit`, `stock-transfers.requests.update`, و `stock-transfers.requests.destroy`.
- **التحكم والتحصين الأمني الصارم ([app/Http/Controllers/StockTransferController.php](file:///C:/get/hospital-system/app/Http/Controllers/StockTransferController.php))**:
  * بناء دوال `editRequest`, `updateRequest`, و `destroyRequest`.
  * حصر إمكانية التعديل والإلغاء حصراً على الطلبات المعلقة (`status === 'pending'`)، ومنع أي تعديل بعد الاعتماد أو الرفض تلقائياً.
  * قفل الإجراءات برمجياً على مقدم الطلب أو مسؤول عهدة الطابق المعتمد أو الإدارة العامة وحظر أي متطفل (403 Forbidden).
  * فحص أرصدة المستودع الرئيسي قبل تحديث الطلبية وإرجاع تنبيهات دقيقة بالنواقص.
- **واجهة تعديل الطلبية التفاعلية ([resources/views/stock-transfers/requests/edit.blade.php](file:///C:/get/hospital-system/resources/views/stock-transfers/requests/edit.blade.php))**:
  * تصميم متوافق مع معايير العمل السريري وبنمط الإكمال التلقائي الذكي لتشخيص الأمراض (ICD-10 Rich Autocomplete Style).
  * استرجاع وتعبئة المواد والكميات السابقة تلقائياً مع خيارات الزيادة والنقصان السريعة (+ / -) ومسح المادة بنقرة واحدة وإضافة أسطر جديدة.
  * عرض مسار التحويل المدمج والمقفل على عهدة الطابق المعتمدة.
- **حوكمة واجهتي السجل والتفاصيل ([resources/views/stock-transfers/requests/index.blade.php](file:///C:/get/hospital-system/resources/views/stock-transfers/requests/index.blade.php), [resources/views/stock-transfers/requests/show.blade.php](file:///C:/get/hospital-system/resources/views/stock-transfers/requests/show.blade.php))**:
  * إضافة أزرار الإجراءات `[ ✏️ تعديل ]` و `[ 🗑️ إلغاء ]` في جدول سجل الطلبات وفي صفحة تفاصيل الطلب تظهر فقط للطلبات المعلقة ولأصحاب الصلاحية مع رسائل تأكيد الحذف.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/FloorCustodianStockTransferTest.php](file:///C:/get/hospital-system/tests/Feature/FloorCustodianStockTransferTest.php))**:
  * تغطية 100%: فحص فتح شاشة التعديل، نجاح التحديث، حظر تعديل الطلبات المعتمدة، نجاح إلغاء وحذف الطلب، ومنع التعديل أو الحذف من طوابق أخرى (10 passed, 40 assertions).

## Session log (2026-11-02 — المرحلة 143: حصر سجل وتفاصيل طلبات تعزيز المخزون على عهدة الطابق وحماية الصلاحيات Scoped Floor Stock Requests & Approval Authorization Guard)

### Done
- **عزل وحصر سجل طلبات المخزون ([app/Http/Controllers/StockTransferController.php](file:///C:/get/hospital-system/app/Http/Controllers/StockTransferController.php))**:
  * تحديث دالة `indexRequests()` لقفل الاستعلام تلقائياً للكادر التمريضي ومسؤولي العهدة ليعرض حصراً طلبات مخزن عهدتهم المعتمد (`to_location_id = user->location_id`) والطلبات التي رفعوها، مع حجب طلبات بقية الأقسام والمستشفيات، وإبقاء العرض الشامل للإدارة وأمناء المستودع المركزي فقط.
- **حوكمة واجهة سجل الطلبات ([resources/views/stock-transfers/requests/index.blade.php](file:///C:/get/hospital-system/resources/views/stock-transfers/requests/index.blade.php))**:
  * تخصيص ترويسة الصفحة لتعكس اسم عهدة الطابق (`طلبات تعزيز مخزن الطابق — ردهة الطابق الثالث`).
  * إضافة زر رجوع ذكي يعيد الممرض لمحطة التمريض والرقود بنقرة واحدة، واستبدال النصوص الإنجليزية بشارات حالة معتمدة (قيد المراجعة / معتمد وتم الصرف / مرفوض).
- **حماية تفاصيل واعتماد الطلبات ([resources/views/stock-transfers/requests/show.blade.php](file:///C:/get/hospital-system/resources/views/stock-transfers/requests/show.blade.php), [app/Http/Controllers/StockTransferController.php](file:///C:/get/hospital-system/app/Http/Controllers/StockTransferController.php))**:
  * منع الممرض من استعراض تفاصيل طلبات أي طابق آخر برمجياً (403 Forbidden).
  * حظر أزرار وإجراءات الموافقة والرفض على الممرضين وقصرها برمجياً على أمين المخزن الرئيسي ومديري النظام.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/FloorCustodianStockTransferTest.php](file:///C:/get/hospital-system/tests/Feature/FloorCustodianStockTransferTest.php))**:
  * اجتياز كامل الحزمة بنجاح 100%: فحص قفل السجل على الطابق، ومنع الدخول والاعتماد غير المصرح، وقفل إنشاء الطلبات (5 passed, 19 assertions).

## Session log (2026-11-02 — المرحلة 142: حوكمة محطة التمريض وقفل طلبات تعزيز المخزن الفرعي على عهدة الطابق المعتمدة Nursing Station Clean Governance & Floor Sub-Store Request Auto-Locking)

### Done
- **حوكمة واجهة محطة التمريض والرقود ([resources/views/surgery-stations/nursing/index.blade.php](file:///C:/get/hospital-system/resources/views/surgery-stations/nursing/index.blade.php))**:
  * إزالة القائمتين المنسدلتين المربكتين (مبدل المخازن الفرعية وفلترة الطوابق) لإنهاء تشتت الكادر بين مخازن الأقسام الأخرى.
  * استبدالهما بشارات ثابتة أنيقة تعكس موقع المخزن المسند للممرض (`المخزن: ردهة الطابق الثالث`) وشارة مسؤولية العهدة (`مسؤولة عهدة المخزن` أو `سحب وصرف تمريضي`).
  * ربط أزرار طلب تعزيز المواد بتمرير معرف المخزن المسند تلقائياً `to_location_id`.
- **معالجة طلبات تحويل وتعزيز المخزون ([app/Http/Controllers/StockTransferController.php](file:///C:/get/hospital-system/app/Http/Controllers/StockTransferController.php))**:
  * تحديث دالة `create(Request $request)` لقفل المخزن المستلم تلقائياً على مخزن عهدة الممرض (`lockedLocation`) إذا كان المستخدم ممرضاً/مسؤول عهدة وليس مديراً عاماً.
  * تحديث دالة `storeRequest(Request $request)` لفرض التحقق الأمني ومنع الممرض من التلاعب أو طلب مواد لأي مخزن طابق أو قسم خارج عهدته المعتمدة.
- **واجهة طلب تحويل وتعزيز المخزون الفرعي ([resources/views/stock-transfers/create.blade.php](file:///C:/get/hospital-system/resources/views/stock-transfers/create.blade.php))**:
  * إعادة تصميم هندسي وبصري كامل للواجهة لتلائم معايير العمل السريري والمستشفيات: إزالة الرسوم التوضيحية والاهتزازات الزائدة والبطاقات المتفرقة.
  * استبدال القائمة المنسدلة بنظام البحث الذكي المتطابق مع واجهة التشخيص الدولي للأمراض (ICD-10 Rich Autocomplete Style): قائمة عائمة بظل عميق، ترويسة عدد النتائج، شارة الكود `[ PRD-001 ]`، شارة الوحدة `[ 🏷️ فيال/قطعة ]`، إمكانية التنقل بالأسهم واختيار المادة بالـ Enter، وزر مسح سريع `x`.
  * شريط ملخص مسار التحويل المدمج: `من: المخزن الرئيسي ⬅️ إلى: ردهة الطابق الثالث [ 🔒 محدد تلقائياً ]`.
  * حقول كميات سهلة الاستخدام بأزرار زيادة ونقصان (+ / -)، وزر رجوع ذكي يعيد الممرض لمحطة التمريض والرقود مباشرة.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/FloorCustodianStockTransferTest.php](file:///C:/get/hospital-system/tests/Feature/FloorCustodianStockTransferTest.php))**:
  * تغطية كاملة: ظهور المخزن مقفلاً للممرضة ومنع القائمة المنسدلة، رفض التلاعب وطلب مخزن آخر، ونجاح رفع طلب التعزيز للمخزن المعتمد (3 passed, 12 assertions).

## Session log (2026-11-03 — المرحلة 144: نظام ثبات واستمرارية التبويبات والتبويبات الفرعية وموضع التمرير في شاشات ونماذج قسم الخدج NICU Complete Tab, Sub-Pill & Scroll Position State Persistence Across All Clinical Forms)

### Done
- **الثبات الكامل للتبويبات الرئيسية والفرعية ([resources/views/nicu/show.blade.php](file:///C:/get/hospital-system/resources/views/nicu/show.blade.php))**:
  * ربط كافة التبويبات السريرية الـ 9 (`servicePane`, `vitalsPane`, `growthPane`, `careSchedulesPane`, `consultationsPane`, `labPane`, `radiologyPane`, `prescriptionsPane`, `attachmentsPane`) والتبويبات الفرعية الـ 3 (`pill-feeding`, `pill-medication`, `pill-doctor-note`) ديناميكياً بمتغيرات الجلسة والطلب `$currentActiveTab` و `$currentActivePill`.
  * حقن تلقائي لحظي لمدخلات `_active_tab` و `_active_pill` داخل أي نموذج أو زر يتم النقر عليه أو إرساله في الصفحة.
  * مزامنة التبويب النشط مع الـ URL Hash والـ LocalStorage/SessionStorage.
- **تثبيت موضع التمرير ومنع القفز لأعلى الصفحة (Scroll Position Restoration)**:
  * حفظ موضع التمرير اللحظي في `sessionStorage` عند إرسال أي فورم أو النقر على أي زر حفظ، واستعادته بدقة وتوقيت مرحلي عند اكتمال تحميل الصفحة ليبقى المستخدم في ذات الموضع بدقة تامة.
  * تعميم آلية حفظ التمرير أيضاً على لوحة إشغال الحاضنات (`nicu/index.blade.php`) وشاشة تسعير وباقات الخدمات (`nicu/services/index.blade.php`).
- **كنترولر الخدج ([app/Http/Controllers/NicuController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuController.php))**:
  * إضافة دالة المساعدة المركزية `redirectBackWithActiveState` وتعميمها عبر كافة دوال الخدج (توثيق العلامات الحيوية، جداول الرعاية، الملاحظات الطبية، الاستشارات، طلبات المختبر والأشعة، الوصفات، إدارة العلاج MAR، المرفقات، وتبديل الباقات)، لتمرير التبويب والتبويب الفرعي تلقائياً في الجلسة `session(['active_tab' => ..., 'active_pill' => ...])`.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/NicuTabAndStatePersistenceTest.php](file:///C:/get/hospital-system/tests/Feature/NicuTabAndStatePersistenceTest.php))**:
  * 5 اختبارات ميزة تغطي توثيق القراءات الحيوية، جداول الرعاية، ملاحظات الأطباء، وتفعيل التبويبات والتبويبات الفرعية على صعيد السيرفر بنجاح 100% (`5 passed, 20 assertions`).

## Session log (2026-11-03 — المرحلة 143: إنشاء وتعميم رادار صالات العمليات والولادة المتوقعة لقسم الخدج NICU Delivery & C-Section Operations Radar مع التحكم الديناميكي بأنواع العمليات وإبراز الصالات)

### Done
- **قاعدة البيانات ونموذج العمليات ([database/migrations/2026_11_03_084643_add_show_in_nicu_radar_to_surgical_operations_table.php](file:///C:/get/hospital-system/database/migrations/2026_11_03_084643_add_show_in_nicu_radar_to_surgical_operations_table.php), [app/Models/SurgicalOperation.php](file:///C:/get/hospital-system/app/Models/SurgicalOperation.php))**:
  * إضافة حقل `show_in_nicu_radar` بجدول `surgical_operations` لتمكين التحكم الديناميكي الكامل بالعمليات المشمولة برادار الخدج.
  * تفعيل العمليات القيصرية والولادية الافتراضية تلقائياً في قاعدة البيانات.
  * تحديث `$fillable` و `$casts` وإضافة نطاق `scopeNicuRadar`.
- **شاشة رادار صالات العمليات والولادة ([app/Http/Controllers/NicuDeliveryRadarController.php](file:///C:/get/hospital-system/app/Http/Controllers/NicuDeliveryRadarController.php), [resources/views/nicu/delivery-radar.blade.php](file:///C:/get/hospital-system/resources/views/nicu/delivery-radar.blade.php))**:
  * واجهة مراقبة سريرية وإنذار مبكر لحظي لكادر الخدج لمتابعة العمليات المتوقعة والجارية في الصالات والاستعداد المسبق دون أي أزرار حجز تجنباً للتضارب مع الاستعلامات.
  * **إبراز صالة العمليات (`Operation Theater`)**: شارة بارزة ومميزة `[ 🚪 صالة العمليات: اسم الصالة ]`، مع شارة تحذيرية صفراء `[ ⚠️ بانتظار تحديد الصالة ]` في حال عدم التعيين.
  * شريط أيام أسبوعي تفاعلي للتنقل المباشر، وتحديث لحظي لعدد الحالات المجدولة، بالانتظار، الجارية الآن بالصالات (مع نبض أحمر تحذيري)، والمنتهية.
  * محرك بحث وفلترة لحظي بالجافاسكريبت بالاسم أو الصالة أو الطبيب أو نوع العملية.
- **واجهة التحكم الديناميكية بأنواع العمليات ([resources/views/nicu/delivery-radar-settings.blade.php](file:///C:/get/hospital-system/resources/views/nicu/delivery-radar-settings.blade.php), [app/Http/Controllers/SurgicalOperationController.php](file:///C:/get/hospital-system/app/Http/Controllers/SurgicalOperationController.php))**:
  * واجهة إعدادات مخصصة للأدمن والمشرفين لإدارة وتفعيل/استبعاد أي عملية جراحية بالرادار بنقرة واحدة عبر AJAX Switch مع إشعار فوري (Toast).
  * **العزل البصري التام (ربط خفي بدون تشويش على شاشات العمليات)**: حصر إدارة الرادار بالكامل داخل قسم الخدج وعزل شاشات العمليات الجراحية العامة (`create`, `edit`, `index`) من أي أزرار أو حقول أو شارات تخص الخدج.
- **الصلاحيات والقوائم ([database/seeders/RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php), [resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php), [resources/views/nicu/index.blade.php](file:///C:/get/hospital-system/resources/views/nicu/index.blade.php))**:
  * إضافة الصلاحيات: `view nicu delivery radar` (للأدمن وطبيب وتمريض الخدج)، و `manage nicu delivery radar settings` (للأدمن والمشرفين).
  * إدراج رابط الرادار في القائمة الجانبية لقسم الخدج، وزر وصول مباشر في لوحة الخدج الرئيسية.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/NicuDeliveryRadarTest.php](file:///C:/get/hospital-system/tests/Feature/NicuDeliveryRadarTest.php))**:
  * تغطية شاملة لمنع غير المصرح لهم، عرض العمليات المشمولة بصالاتها وعزل غير المشمولة، فتح الإعدادات، وتبديل الحالة بالـ AJAX (5 passed, 22 assertions بنجاح 100%).

## Session log (2026-11-02 — المرحلة 142: إنشاء وتعميم تبويب الحسابات المتكامل لقسم الخدج والحاضنات NICU Accounting & Analytics Module ضمن الحسابات العامة والمالية)

### Done
- **القائمة الجانبية الموحدة ([resources/views/layouts/app.blade.php](file:///C:/get/hospital-system/resources/views/layouts/app.blade.php))**:
  * إضافة قسم فرعي جديد `حسابات الخدج والحاضنات` (`#nicuAccountingSubSection`) باللون الأزرق السماوي (`#0dcaf0`) تحت القائمة الموحدة "الحسابات العامة والمالية".
  * تعميم التبويبات المعيارية الأربعة: تحليلات إحصاءات الخدج، الحركات المالية للخدج، كشوفات الخدج، وحسابات أطباء الخدج.
- **تحليلات وإحصاءات الخدج ([app/Http/Controllers/AccountantController.php](file:///C:/get/hospital-system/app/Http/Controllers/AccountantController.php), [resources/views/accountant/nicu/analytics.blade.php](file:///C:/get/hospital-system/resources/views/accountant/nicu/analytics.blade.php))**:
  * إضافة دالة `nicuAnalytics` لعرض كروت KPI الشاملة (حالات الدخول كاش وضمان، نسبة إشغال الحاضنات، إجمالي المقبوضات والأمانات، وصافي إيراد الخدج).
  * مخطط أعمدة لأعلى خدمات الخدج استخداماً وإيرادها التقديري (CPAP، معالجة ضوئية، عناية فائقة).
  * مخطط بياني مقارن لنشاط أطباء الأطفال والخدج وإحصائيات الحالات والاستشارات.
- **الحركات المالية للخدج ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [resources/views/cashier/nicu-movements.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-movements.blade.php))**:
  * إضافة دالة `nicuFinancialMovements` لسجل حركات `nicu_payments` بفلاتر التاريخ، طريقة الدفع (كاش/بطاقة)، ونوع الحركة (أمانات وسلفة، دفعات تحت الحساب، تسوية نهائية، واسترجاع فائض).
  * بطاقات الملخص المالي الثلاثية: المستلم، المسترجع، والصافي الفعلي المقبوض، مع أزرار الطباعة والمعاينة والوصل الحراري وكشف حساب الطفل.
- **كشوفات الخدج ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [resources/views/cashier/nicu-statements.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-statements.blade.php))**:
  * إضافة دالة `nicuStatements` لعرض تقرير تفصيلي بإيرادات الحواضن، التحاليل المخبرية، الأشعة، والاستشارات والأدوية لكل طفل راقد.
  * جدول تفصيلي بالأيام المحتسبة وفق قواعد السماحية 6 ساعات، إجمالي التكلفة، المسدد، والرصيد المتبقي (ذمة أو فائض أمانات).
- **حسابات أطباء الخدج والأطفال ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [resources/views/cashier/nicu-doctor-accounts.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-doctor-accounts.blade.php), [resources/views/cashier/nicu-doctor-account.blade.php](file:///C:/get/hospital-system/resources/views/cashier/nicu-doctor-account.blade.php))**:
  * رصد مستحقات أطباء الأطفال من استشارات الخدج المنجزة والحالات المشرف عليها.
  * نموذج تسجيل وصرف دفعات المستحقات (`nicuDoctorPayout`) وتوثيق سند صرف وسند قيد خزينة `FinancialTransaction`.
- **الربط مع الخزينة المركزية للمستشفى ([app/Http/Controllers/HospitalTreasuryController.php](file:///C:/get/hospital-system/app/Http/Controllers/HospitalTreasuryController.php), [app/Models/FinancialTransaction.php](file:///C:/get/hospital-system/app/Models/FinancialTransaction.php))**:
  * إضافة تصنيف `nicu` في `FinancialTransaction::CATEGORIES`.
  * إدراج الخدج ورعاية حديثي الولادة (NICU) كقسم رئيسي ضمن بطاقات إيرادات الأقسام ومصفوفة الاستحقاق في لوحة الخزينة المركزية.
  * التوثيق المالي التلقائي في `FinancialTransaction` عند تحصيل أو استرجاع أي دفعة خديج في `processNicuPayment`.
- **الصلاحيات والـ Seeder ([database/seeders/RolesAndPermissionsSeeder.php](file:///C:/get/hospital-system/database/seeders/RolesAndPermissionsSeeder.php))**:
  * إضافة وتوليد الصلاحيات: `view nicu analytics`, `view nicu financial movements`, `view nicu statements`, `view nicu doctor accounts`.
- **حزمة الاختبارات الآلية الشاملة ([tests/Feature/NicuAccountingTest.php](file:///C:/get/hospital-system/tests/Feature/NicuAccountingTest.php))**:
  * تغطية شاملة لجميع المسارات والصفحات الأربعة وعملية صرف مستحقات الطبيب (4 passed, 21 assertions بنجاح 100%).

## Session log (2026-11-01 — المرحلة 141: نظام نسب التحمل المفصلة لكل خدمة في فواتير الضمان الصحي الموحدة لدى الكاشير Per-Service Item Copay Enforcement on Combined Cashier Insurance Receipts)

### Done
- **نموذج الفئات التأمينية ([app/Models/HealthInsuranceCategory.php](file:///C:/get/hospital-system/app/Models/HealthInsuranceCategory.php))**:
  * إضافة حالتي `ecg` و `nursing` في دالة `getCopayForService` لترجع نسبة `consultation_copay` المعتمدة (10% للفئة H).
- **واجهة دفع الطلبات الطبية الموحدة ([resources/views/cashier/request-payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/request-payment-form.blade.php))**:
  * إنهاء تعميم نسبة التحمل الفردية وتطبيق نسبة التحمل المعتمدة لكل خدمة على حدة: المختبر 25%، الأشعة 25%، تخطيط القلب بإحالة استشارية 10%، وتخطيط القلب المباشر كاش 100% بدون تغطية ضمان (0%).
  * استبدال البطاقة المضللة ببطاقة الفئة الشاملة: `الفئة H — المختبر: 25% | الأشعة: 25% | الاستشارية والتخطيط: 10%`.
  * تزويد حقل نسبة التحمل التراكمي بشارة `مفصلة حسب الخدمة (المختبر 25% / التخطيط 10%)`.
  * تزويد أسطر الجدول بخاصية `data-item-copay` وشارات التغطية التفاعلية `مشمول بالضمان (تحمل X%)`.
  * تحديث محرك الجافاسكريبت `recalculate()` لاحتساب كل خدمة بنسبتها المستقلة وتحديث المجاميع اللحظية.
- **معالجة العمليات في الكنترولر ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * تمرير نسبة كل خدمة (`$labCopay`, `$radCopay`, `$ecgCopay`) بشكل منفصل داخل `processRequestPayment`.
  * تثبيت احتساب التخطيط المباشر نقداً 100% كاش دون تحميل الضمان.
  * حذف التكرار البرمجي لبلوك `nursing`.
- **إيصالات القبض والطباعة الحرارية ([resources/views/cashier/receipt.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt.blade.php), [resources/views/cashier/receipt-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt-print.blade.php))**:
  * تدعيم مطابقة فحص تخطيط القلب بكافة الصيغ والأنماط الشائعة (`hear echo`, `echo`, `ايكو`, `ecg`, `تخطيط`).
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/MultiServiceInsuranceCopayTest.php](file:///C:/get/hospital-system/tests/Feature/MultiServiceInsuranceCopayTest.php))**:
  * تغطية شاملة لفتح الوصل واحتساب الصافي، تسديد الوصل المدمج، واحتساب التخطيط المباشر (3 passed, 12 assertions).

## Session log (2026-10-31 — المرحلة 140: تعميم نظام الخصم الجزئي والتفصيلي لكل خدمة والخصم الإجمالي ع الوصل على كاشير الطوارئ Cashier Emergency Dual-Mode Discount System Overall vs Per-Item Percentage)

### Done
- **واجهة كاشير الطوارئ التفاعلية ([resources/views/cashier/emergency-payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/emergency-payment-form.blade.php))**:
  * إضافة مفتاح التبديل الثنائي لنطاق الخصم (`discount_scope`): "خصم إجمالي ع الوصل" (`overall`) مقابل "خصم تفصيلي لكل خدمة" (`per_item`).
  * تزويد كل بند في فاتورة الطوارئ (خدمات الطوارئ، التحاليل، الأشعة، الاستشارات الطبية، ومتابعة الطبيب) بمفتاح فريد وعمود مخصص لإدخال نسبة الخصم لكل خدمة بشكل منفصل (`col-item-discount`).
  * احتساب فوري لحظي بالدينار لكل خدمة مع شطب السعر الأصلي وإظهار الصافي وشارة قيمة الخصم بالدينار.
  * قفل حقل الخصم الإجمالي وتحويله لعرض التراكمي عند تفعيل الخصم التفصيلي، وعكسه فورياً عند العودة للخصم الإجمالي.
- **معالجة العمليات في الكنترولر ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * تطوير دالة `processEmergencyPayment` لدعم `discount_scope === 'per_item'` واحتساب الخصومات المفردة عبر كافة بنود الطوارئ (الخدمات، التحاليل، الأشعة، والاستشارات ومتابعة الطبيب).
  * توثيق أسباب الخصم التفصيلي تلقائياً بأسماء الخدمات ونسبها داخل `discount_reason` في جدول المدفوعات وتثبيت الصافي الفعلي ونسبة الخصم الإجمالية وهوية الكاشير.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/CashierDiscountTest.php](file:///C:/get/hospital-system/tests/Feature/CashierDiscountTest.php))**:
  * إضافة اختبار ميزة متكامل (`test_cashier_can_apply_per_item_discount_on_emergency_payment`) لاختبار خصم الخدمات الفردية في الطوارئ والتحقق من حساب الصافي والنسبة والتوثيق المالي في قاعدة البيانات.
  * اجتياز كامل حزمة اختبارات الخصم بنجاح 100% (6 passed, 61 assertions).

## Session log (2026-10-31 — المرحلة 139: إبراز شارات التصنيف المالي "نقدي كاش / ضمان صحي / قوى أمن" وشريط الفرز الفوري في محطة الكاشير Cashier Financial Badges & Live Category Filter Bar)

### Done
- **إبراز الشارات المالية في كافة جداول الكاشير ([resources/views/cashier/index.blade.php](file:///C:/get/hospital-system/resources/views/cashier/index.blade.php))**:
  * تعميم شارة صريحة وأنيقة `[💵 نقدي (كاش)]` لجميع المرضى غير المشمولين بالضمان عبر كافة التبويبات (الكل، كشفية الاستشارية، التحاليل والأشعة، الطوارئ، الفحوصات المؤجلة، ومستحقات الاسترجاع).
  * تدعيم قسم الطوارئ بالتعرف الكامل على نوع التأمين وعرض شارة "داخليّة" أو "ضمان صحي" أو "نقدي (كاش)" بدقة.
- **شريط التصفية المالية السريعة اللحظية (`#financialFilterGroup`)**:
  * إضافة أزرار فرز تفاعلية فورية أعلى جدول تبويب الكل (`#tab-all`): `[الكل | 💵 نقدي / كاش | 🛡️ مشمول بالضمان]`.
  * تصفية فورية بالـ JavaScript لصفوف الجدول دون الحاجة لإعادة تحميل الصفحة مع تحديث عداد المعاملات الظاهرة لحظياً.

## Session log (2026-10-31 — المرحلة 138: نظام الخصم الثنائي التبادلي للكاشير إجمالي ع الوصل أو تفصيلي لكل خدمة بنسب مئوية وعزل الضمان Cashier Dual-Mode Mutual Discount System Overall vs Per-Item Percentage with Strict Non-Insurance Enforcement)

### Done
- **التحكم التبادلي والواجهة التفاعلية ([resources/views/cashier/request-payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/request-payment-form.blade.php))**:
  * إضافة مبدل نطاق الخصم التبادلي (`discount_scope`): "خصم إجمالي ع الوصل" (`overall`) مقابل "خصم تفصيلي لكل خدمة" (`per_item`).
  * إدراج عمود مخصص في جدول الخدمات والتحاليل والأشعة والتمريض (`col-item-discount`) لإدخال نسبة الخصم لكل خدمة بشكل منفصل (بخطوات خماسية 5% واقتراحات Datalist من 0% حتى 100%).
  * احتساب فوري بالدينار العراقي لكل خدمة مع شطب السعر القديم وإبراز الصافي الجديد وشارة توضيحية لقيمة الخصم بالدينار.
  * تجميع آلي فوري لكافة الخصومات الفردية في خانة الصافي ومجموع الإعفاء والنسبة الإجمالية التراكمية في صندوق الملخص المالي.
  * منع التضارب: عند اختيار الخصم التفصيلي، يُقفل حقل الخصم الإجمالي للقراءة فقط ويتحول لعرض الإجمالي التراكمي، وعند العودة للخصم الإجمالي يُخفى عمود الجدول وتُصفر خصومات السطور تلقائياً.
  * حماية الضمان: يختفي صندوق الخصم وعمود الخدمات كلياً ويقفل النظام أي خصومات للمرضى المشمولين بالضمان الصحي (`hi`) أو قوى الأمن (`moi`) وحصره بنسبة 100% في المرضى النقديين (`none`).
- **معالجة العمليات في الكنترولر وقواعد البيانات ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * تطوير دالة `processRequestPayment` للتعامل مع وضعيتي الخصم (`overall` و `per_item`).
  * في وضعية الخصم التفصيلي: احتساب خصم كل خدمة منفصلة وتوثيق مصفوفة الخصومات داخل `details['item_discounts']` لكل طلب طبي.
  * توثيق تفاصيل الخصم وأسماء الخدمات ونسبها تلقائياً في حقل `discount_reason` لسند الدفع (`payments`)، وتخزين إجمالي الخصم والنسبة الفعلية وهوية الكاشير المنفذ.
- **سندات القبض المطبوعة والحرارية ([resources/views/cashier/receipt.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt.blade.php), [resources/views/cashier/receipt-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt-print.blade.php), [resources/views/cashier/receipt-pdf.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt-pdf.blade.php))**:
  * توافق تام مع سندات القبض والطباعة الحرارية وPDF لعرض المبالغ الصافية وتفاصيل أسباب ونسب الخصم التفصيلي تلقائياً أمام المريض والمدققين.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/CashierDiscountTest.php](file:///C:/get/hospital-system/tests/Feature/CashierDiscountTest.php))**:
  * إضافة اختبار ميزة متكامل (`test_cashier_can_apply_per_item_discount_on_non_insured_request_payment`) يؤكد تطبيق الخصم التفصيلي، وحساب الإجمالي الصافي بدقة، وتوثيق أسماء الخدمات ونسبها، وتخزينها في تفاصيل الطلب وسند الدفع.
  * اجتياز كامل حزمة اختبارات الخصم بنجاح 100% (5 passed, 51 assertions).

## Session log (2026-10-31 — المرحلة 137: تزامن تلقائي لمخرجات بناء Vite بين public/build و dist لمنصات النشر والاستضافة التلقائية Vite Build Output Dual-Sync for Automated Deployment Pipelines)

### Done
- **إعدادات البناء والمزامنة ([vite.config.js](file:///C:/get/hospital-system/vite.config.js), [package.json](file:///C:/get/hospital-system/package.json), [.gitignore](file:///C:/get/hospital-system/.gitignore))**:
  * إضافة مكون إضافي مخصص `sync-build-to-dist` في `vite.config.js` لنسخ كافة أصول البناء من `public/build` إلى مجلد `dist` فور اكتمال التجميع مع توليد ملف `index.html` متوافق.
  * تدعيم أمر `build` في `package.json` بنص تنفيذي لضمان وجود وتزامن مجلد `dist` حتى وإن تغير مسار أو وسيطة التشغيل في المنصة.
  * إضافة `.gitkeep` داخل `dist` وتحديث `.gitignore` لضمان وجود المجلد مسبقاً وتجنب أخطاء الفحص التلقائي لمسار المخرجات أثناء النشر على هوستنجر أو أي منصة سحابية.

## Session log (2026-10-30 — المرحلة 136: دعم وتكامل طلبات تخطيط القلب والتمريض الصادرة من الاستشارية لدى الكاشير بتسعير الضمان الصحي الدقيق Doctor ECG & Nursing Requests Cashier Billing with Accurate Insurance Pricing)

### Done
- **معالجة طلبات التمريض وتخطيط القلب في الكاشير ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [resources/views/cashier/request-payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/request-payment-form.blade.php))**:
  * دعم طلبات التمريض (`type === 'nursing'`) في واجهة كاشير الطلبات الطبية بعد أن كانت مقتصرة سابقاً على المختبر والأشعة فقط.
  * تصحيح وتدقيق مطابقة خدمة "تخطيط قلب" لاستهداف خدمة الطوارئ المعتمدة رقم **#93** (`تخطيط قلب`) بسعر 15,000 د.ع كاش وسعر 10,000 د.ع للضمان الصحي (HI)، ومنع مطابقتها الخاطئة مع خدمة #68 ("مراقبة الفعاليات الحيوية مع او بدون تخطيط القلب" بسعر 25,000 د.ع).
  * احتساب حصة المريض التأمينية بدقة بناءً على نسبة التحمل (10% = 1,000 د.ع، 15% = 1,500 د.ع) وتثبيت حصة الضمان الصحي (9,000 د.ع أو 8,500 د.ع).
  * إضافة نوع `nursing` لفحص حالات الدفع (`$hasAnyPay`) لمنع تصنيف الطلب كملغي أو توجيهه خطأ إلى شاشة الكاشير الرئيسية.
- **شاشة الطبيب وإحالات الطوارئ السريعة ([resources/views/doctors/visits/show.blade.php](file:///C:/get/hospital-system/resources/views/doctors/visits/show.blade.php))**:
  * إضافة زر إحالة سريعة مخصص `+ تخطيط قلب (ECG)` في بطاقة إحالة الطوارئ بمحطة الطبيب لإرسال الطلب فورياً للكاشير.
- **سندات القبض المطبوعة والحرارية ([resources/views/cashier/receipt.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt.blade.php), [resources/views/cashier/receipt-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt-print.blade.php))**:
  * إدراج بنود التمريض وتخطيط القلب (ECG) في جدول الإيصال المالي والوصل الحراري المطبوع مع تفصيل المبلغ المعتمد وحصة المريض وحصة الضمان.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/DoctorECGRequestPaymentTest.php](file:///C:/get/hospital-system/tests/Feature/DoctorECGRequestPaymentTest.php))**:
  * اجتياز كامل حزمة الاختبارات بنجاح 100% (2 passed, 11 assertions).

## Session log (2026-10-30 — المرحلة 135: تعميم نظام الخدمات الإضافية والفندقية على واجهة كاشير الفحوصات والطلبات الطبية بتسعير ديناميكي حر خارج الضمان Medical Requests & Visits Dynamic Extra Services with Non-Insurance Direct Billing)

### Done
- **قاعدة البيانات والترحيل ([database/migrations/2026_10_30_202000_create_visit_extra_services_table.php](file:///C:/get/hospital-system/database/migrations/2026_10_30_202000_create_visit_extra_services_table.php), [app/Models/VisitExtraService.php](file:///C:/get/hospital-system/app/Models/VisitExtraService.php))**:
  * إنشاء جدول `visit_extra_services` لربط الخدمات الإضافية بالطلب الطبي والزيارة وسند الدفع المالي.
  * تطبيق المايجريشن بنجاح محلياً وعلى قاعدة بيانات هوستنجر الحية (`remote_hostinger`).
  * ربط العلاقات بنموذجي `Visit` و `Payment`.
- **معالجة العمليات في الكنترولر والمسارات ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * شمول واجهة كاشير الطلبات `showRequestPaymentForm` بقائمة الخدمات ودعم جلب الخدمات المعلقة للطلب والزيارة.
  * إضافة مساري `cashier.request.extra-services.add` و `cashier.request.extra-services.remove`.
  * ترقية `processRequestPayment` لحساب الخدمات الإضافية 100% نقداً وربطها بسند القبض المالي وتحويل حالتها إلى `paid`.
- **ترقية واجهات الكاشير والوصولات ([resources/views/cashier/request-payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/request-payment-form.blade.php), [resources/views/cashier/receipt.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt.blade.php), [resources/views/cashier/receipt-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt-print.blade.php))**:
  * إضافة بطاقة الخدمات الإضافية والفندقية لشاشة دفع الطلبات الطبية `http://127.0.0.1:8000/cashier/request-payment/{id}`.
  * ظهور بنود الخدمات المضافة في جدول الفحوصات مع أزرار الحذف الفوري قبل السداد.
  * ظهور الخدمات الإضافية في سند القبض والشاشة التفاعلية والوصل الحراري المطبوع.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/RequestExtraServicesTest.php](file:///C:/get/hospital-system/tests/Feature/RequestExtraServicesTest.php))**:
  * اجتياز كامل حزمة الاختبارات بنجاح 100% (3 passed, 13 assertions).

## Session log (2026-10-30 — المرحلة 134: نظام الخدمات الإضافية والفندقية للعمليات الجراحية والرقود بتسعير ديناميكي للكاشير مع العزل الكامل عن الضمان Surgery Accommodation & Extra Services with Dynamic Cashier Pricing)

### Done
- **قاعدة البيانات وموديلات الخدمات الإضافية ([database/migrations/2026_10_30_183000_create_hospital_extra_services_tables.php](file:///C:/get/hospital-system/database/migrations/2026_10_30_183000_create_hospital_extra_services_tables.php), [app/Models/HospitalExtraService.php](file:///C:/get/hospital-system/app/Models/HospitalExtraService.php), [app/Models/SurgeryExtraService.php](file:///C:/get/hospital-system/app/Models/SurgeryExtraService.php), [app/Models/Surgery.php](file:///C:/get/hospital-system/app/Models/Surgery.php))**:
  * إنشاء جدول الدليل المرجعي للخدمات الإضافية `hospital_extra_services` وتغذيته بـ 17 خدمة تم استخراجها من صور النظام القديم (تخطيط قلب، مبيت مرافق، رقود إضافي، فتح طبلة، أطفال، تزيين غرف، غرامات، بيانات ولادة، مواد، فندقة نواظير) مع عزل الغرف VIP/VVIP لنظام الغرف الداخلي.
  * إنشاء جدول `surgery_extra_services` لربط الخدمات المضافة بالعملية الجراحية وسند القبض.
  * إضافة علاقة `extraServices()` في نموذج `Surgery`.
  * تطبيق المايجريشن بنجاح محلياً وعلى قاعدة بيانات هوستنجر الحية (`remote_hostinger`).
- **معالجة العمليات في الكنترولر والمسارات ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php), [routes/web.php](file:///C:/get/hospital-system/routes/web.php))**:
  * إضافة مسار `cashier.surgeries.extra-services.add` لإضافة خدمة بسعر مخصص ديناميكي يحدده الكاشير بحرية تامة دون قيد الضمان.
  * إضافة مسار `cashier.surgeries.extra-services.remove` لحذف الخدمة المعلقة قبل السداد.
  * ترقية `processSurgeryPayment` لحساب الخدمات الإضافية بنسبة 100% نقداً وربطها بسند القبض المالي وتحويل حالتها إلى `paid`.
- **ترقية واجهات الكاشير والوصولات ([resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php), [resources/views/cashier/receipt.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt.blade.php), [resources/views/cashier/receipt-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt-print.blade.php))**:
  * بطاقة ديناميكية لاقتراح وتعديل أسعار الخدمات الإضافية مع اقتراحات Datalist.
  * إدراج بنود الخدمات الإضافية في جدول الدفع مع إمكانية حذفها قبل الدفع.
  * ظهور بنود الخدمات الإضافية في سند القبض والشاشة التفاعلية والوصل الحراري المطبوع.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/SurgeryExtraServicesTest.php](file:///C:/get/hospital-system/tests/Feature/SurgeryExtraServicesTest.php))**:
  * اجتياز كامل حزمة الاختبارات بنجاح 100% (3 passed, 14 assertions).

## Session log (2026-10-30 — المرحلة 133: دعم خصومات الكاشير الإدارية والخاصة بنسب خماسية من 0% إلى 100% وحصرها التام خارج الضمان Cashier Multi-Departmental 5% Step Discounts System & Strict Non-Insurance Enforcement)

### Done
- **قاعدة البيانات وموديل الدفعات ([database/migrations/2026_10_30_150000_add_discount_fields_to_payments_table.php](file:///C:/get/hospital-system/database/migrations/2026_10_30_150000_add_discount_fields_to_payments_table.php) & [app/Models/Payment.php](file:///C:/get/hospital-system/app/Models/Payment.php))**:
  * إضافة حقول الخصم الرسمية لجدول `payments`:
    - `discount_percentage` (decimal 5,2 default 0.00).
    - `discount_amount` (decimal 12,2 default 0.00).
    - `discount_reason` (string 255 nullable).
    - `discount_by` (unsignedBigInteger nullable fk users).
  * تحديث `$fillable` و `$casts` وعلاقة `discountBy()` في نموذج `Payment`.
  * تطبيق المايجريشن بنجاح محلياً وعلى قاعدة بيانات هوستنجر الحية (`remote_hostinger`).
- **معالجة الدفع المالي في الكنترولر ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * شمول جميع أقسام الكاشير:
    - الفحوصات الطبية والمختبر والأشعة (`processRequestPayment`).
    - خدمات وفحوصات واستشارات الطوارئ (`processEmergencyPayment`).
    - رسوم العمليات الجراحية والغرف والتحاليل الملحقة (`processSurgeryPayment`).
  * **العزل الصارم لحماية الضمان**: تعطيل الخصم وتصفيره نهائياً إذا كانت فئة المريض ضمان صحي (`hi`) أو قوى أمن (`moi`) وحصره فقط في المرضى النقديين (`insurance_type === 'none'`).
  * الحساب المالي الدقيق: احتساب الخصم من حصة المريض الإجمالية (`patient_share`)، وتخزين الصافي الفعلي في الصندوق بحقل `amount`، وتوثيق نسبة وقيمة وسبب الخصم وهوية الكاشير أو المشرف.
- **تحديث واجهات الكاشير الحية بنسب خماسية ([resources/views/cashier/request-payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/request-payment-form.blade.php), [resources/views/cashier/emergency-payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/emergency-payment-form.blade.php), [resources/views/cashier/surgeries/payment-form.blade.php](file:///C:/get/hospital-system/resources/views/cashier/surgeries/payment-form.blade.php))**:
  * إضافة صندوق خصم تفاعلي يظهر فقط عند الدفع النقدي (ويختفي تماماً ويقفل عند اختيار الضمان).
  * قائمة نسب خماسية دقيقة (من 0% حتى 100% بخطوات 5%).
  * أزرار سريعة للخصومات الشائعة (0%، 10%، 15%، 20%، 25%، 50%، إعفاء 100%).
  * حقل نصي إلزامي/توضيحي لسبب الخصم (إداري، إعفاء طبيب، تخفيض إنساني).
  * تحديث فوري مباشر بجافاسكريبت لحصة المريض والمبلغ المطلوب قبضه وزر التأكيد وشريط الملخص المالي.
- **تحديث إيصالات القبض والطباعة الحرارية وPDF ([resources/views/cashier/receipt.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt.blade.php), [resources/views/cashier/receipt-print.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt-print.blade.php), [resources/views/cashier/receipt-pdf.blade.php](file:///C:/get/hospital-system/resources/views/cashier/receipt-pdf.blade.php))**:
  * إبراز سطر تفصيلي واضح لخصم الإدارة (النسبة %، القيمة بالدينار، وسبب الخصم) قبل خانة الصافي المدفوع، لضمان وضوح وشفافية السند المالي أمام المريض والمدققين.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/CashierDiscountTest.php](file:///C:/get/hospital-system/tests/Feature/CashierDiscountTest.php))**:
  * بناء 4 اختبارات ميزة تغطي كامل متطلبات الدورة المالية:
    1. تطبيق خصم 20% على طلبات الفحوصات للمرضى النقديين والتأكد من الحسابات وهوية الكاشير وسبب الخصم.
    2. الحماية الصارمة للمرضى المؤمنين ومنع أي خصم عليهم بقوة النظام (0%).
    3. تطبيق خصم 15% على كاشير الطوارئ مع تحديث المبالغ وإصدار السند.
    4. تطبيق خصم 25% على كاشير العمليات الجراحية بنجاح.
  * اجتياز كامل حزمة الاختبارات بنجاح 100% (4 passed, 37 assertions).

## Session log (2026-10-30 — المرحلة 132: تمكين الكاشير الإداري casshieradmin والمخولين بالإبطال من استعراض كافة الوصولات وفلترة الكاشيرات Cashier Admin & Void Authorized Users Full Receipts Access & Departmental Cross-Drawer Filter Parity)

### Done
- **إزالة القيد الحصري عن الكاشير الإداري والمخولين بالإبطال ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * توسيع صلاحية استعراض الوصولات في دالة `reports()` لتشمل دور الكاشير الإداري `casshieradmin` وصلاحية إبطال الوصولات `void payment receipt` بالإضافة إلى الأدوار الإدارية (`admin`, `accountant`, `hospital_admin`).
  * إلغاء فرض شرط `where('cashier_id', $user->id)` التلقائي عليهم، مما يسمح لهم بمشاهدة والبحث في وصولات جميع الكاشيرات والصناديق بالمستشفى دفعة واحدة.
  * تزويدهم بقائمة كاشيرات المستشفى للفلترة المخصصة حسب الكاشير أو عرض "جميع الكاشيرات".
- **تحديث واجهة تقرير الوصولات والمقبوضات ([resources/views/cashier/report.blade.php](file:///C:/get/hospital-system/resources/views/cashier/report.blade.php))**:
  * إظهار القائمة المنسدلة لفلترة الكاشير لمن يملك دور `casshieradmin` أو صلاحية `void payment receipt` ليتمكن المشرف من تدقيق وإبطال أو مراجعة أي وصل تم إصداره من أي نقطة كاشير أخرى.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/PaymentVoidAndReversalTest.php](file:///C:/get/hospital-system/tests/Feature/PaymentVoidAndReversalTest.php))**:
  * إضافة اختبار ميزة متكامل `test_cashier_admin_or_void_authorized_user_can_view_all_cashiers_receipts_in_reports` يؤكد عزل الكاشير العادي لوصولاته الخاصة فقط، وقدرة الكاشير الإداري (`casshieradmin`) على رؤية جميع وصولات الكاشيرات دفعة واحدة.
  * اجتياز كامل حزمة اختبارات إبطال الوصولات بنجاح 100% (11 passed, 73 assertions).

## Session log (2026-10-29 — المرحلة 131: دمج وتوحيد فحوصات CBC في فحص نشط وحيد رقم 43 ونقل باقات الأطباء وتعطيل المكرر كأرشيف CBC Tests Consolidation into Single Active ID 43 & Legacy Archiving)

### Done
- **الدمج الشامل وتوحيد سجلات فحص الدم ([database/migrations/2026_10_29_213000_consolidate_cbc_tests_to_single_active_test_43.php](file:///C:/get/hospital-system/database/migrations/2026_10_29_213000_consolidate_cbc_tests_to_single_active_test_43.php))**:
  * اعتماد السجل رقم **#43** كفحص نشط ورسمي وحيد باسم **`CBC (5-Part Diff)`** وبسعر 20,000 د.ع مع كامل البارامترات الـ 27.
  * تمييز السجل رقم **#44** كأرشيف مدمج (`CBC (5-Part Diff) [أرشيف مدمج]`) وتعطيله نهائياً (`is_active = 0`).
  * تمييز السجل رقم **#1440** كمكرر ملغي (`CBC [مكرر ملغي]`) وتعطيله نهائياً (`is_active = 0`).
  * ترحيل ونقل كافة الباقات المفضلة لجميع أطباء المستشفى في جدول `user_lab_test_group_lab_test` من 44 و 1440 إلى 43 مباشرة ليصبح إجمالي باقات الأطباء المرتبطة بـ #43 هو 21 باقة.
  * تطبيق المايجريشن والدمج محلياً وعلى قاعدة بيانات هوستنجر الحية بدقة 100%.

## Session log (2026-10-29 — المرحلة 130: ترقية وتوحيد طلب استشارة الطوارئ إلى اختيار استشاري فردي وربطها مالياً بخدمة استشارة اخصائي من العيادة الاستشارية بسعر 30,000 د.ع Emergency Single Consultant Selection & Automated 30,000 IQD Emergency Service Billing)

### Done
- **ترقية واجهة نافذة طلب الاستشارة الطبية ([resources/views/emergency/show.blade.php](file:///C:/get/hospital-system/resources/views/emergency/show.blade.php))**:
  * استبدال صندوق الاختيار المتعدد القديم (`select multiple` المعتمد على زر `Ctrl`) بقائمة منسدلة حديثة وسلسة (`Dropdown single select`) لاختيار الطبيب الاستشاري المطلوب بعينه.
  * حصر الأطباء المتاحين في الأطباء الاستشاريين (`consultant`) وأطباء السونار (`sonar`) المتاحين اليوم بالدوام فقط.
  * ترقية وسم الاستشارة في شريط رأس محطة الطوارئ ليعرض صراحة اسم الطبيب الاستشاري وقسمه: `[ تم طلب استشارة: د. فلان (باطنية) ]`.
- **الربط المالي الصارم بخدمات الطوارئ ([app/Http/Controllers/EmergencyController.php](file:///C:/get/hospital-system/app/Http/Controllers/EmergencyController.php))**:
  * إلغاء الرقم المقطوع القديم (50,000 د.ع) وتصفير رسوم الموعد الإضافية (`consultation_fee = 0`) منعاً للازدواجية.
  * إلحاق خدمة الطوارئ الرسمية **"استشارة اخصائي من العيادة الاستشارية"** (رقم #115) بسعرها الرسمي المعتمد **30,000 د.ع** تلقائياً إلى خدمات الحالة في جدول `emergency_emergency_service`.
  * إذا قام الطبيب بطلب استشارة إضافية لاحقاً، يتم زيادة كمية الخدمة تلقائياً بمقدار 1 (لتصبح 2 × 30,000 = 60,000 د.ع).
  * إدراج الخدمة رسمياً في فاتورة الطوارئ الموحدة ووصل الكاشير الإلكتروني والحراري وحسابات الضمان الصحي.
- **تحديث كاشير الطوارئ ومستحقات الطبيب ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * ضبط القيد المالي الافتراضي لاستشارة الطوارئ ليصبح 30,000 د.ع (بدلاً من 50,000 د.ع) وتوزيع الحصص بنسبة 80% للطبيب (24,000 د.ع) و 20% للمستشفى (6,000 د.ع).
- **حزم الاختبارات الآلية الشاملة**:
  * إضافة اختبار ميزة متكامل `test_requesting_emergency_consultation_attaches_emergency_service_and_schedules_appointment` في [`tests/Feature/EmergencyWorkflowTest.php`](file:///C:/get/hospital-system/tests/Feature/EmergencyWorkflowTest.php).
  * اجتياز كامل حزمة اختبارات الطوارئ والـ CBC بنجاح 100% (11 passed, 49 assertions).

## Session log (2026-10-29 — المرحلة 129: توحيد فحص عد الدم الشامل الآلي باسمه الطبي المعتمد CBC (5-Part Diff) وفصل فحص فيلم الدم المجهري Blood Film وتحديث المطبوعات والاختبارات Automated CBC (5-Part Diff) Medical Standardization, Standalone Microscopic Blood Film & Complete Print Parity)

### Done
- **توحيد المسمى الطبي لفحص الدم الشامل في قاعدة البيانات ([database/migrations/2026_10_29_200000_standardize_cbc_test_name_to_cbc_5_part_diff.php](file:///C:/get/hospital-system/database/migrations/2026_10_29_200000_standardize_cbc_test_name_to_cbc_5_part_diff.php))**:
  * توحيد واعتماد المسمى الطبي الدقيق `CBC (5-Part Diff)` لفحص الجهاز الآلي (رقم 43 و 44) بسعر 20,000 د.ع مع كافة البارامترات الـ 27 الفرعية.
  * تعطيل السجل المكرر (رقم 1440) منعاً للازدواجية.
  * عزل وتثبيت فحص فيلم الدم المجهري اليدوي المستقل `Blood Film` (رقم 1243) بسعر 50,000 د.ع كفحص سريري منفصل.
  * تطبيق المايجريشن وتحديث السجلات محلياً وعلى قاعدة بيانات هوستنجر الحية بدقة 100%.
- **تحديث وضبط شاشات طباعة تقارير المختبر ([resources/views/staff/requests/emergency-lab-print.blade.php](file:///C:/get/hospital-system/resources/views/staff/requests/emergency-lab-print.blade.php) & [resources/views/staff/requests/print.blade.php](file:///C:/get/hospital-system/resources/views/staff/requests/print.blade.php))**:
  * تحديث دالة التطبيع السريرية `$canonicalTestName` لترقية كافة المسميات القديمة والبديلة (`CBC & Blood Film`, `Complete blood count & Differential`, إلخ) إلى المسمى الرسمي المعتمد `CBC (5-Part Diff)`.
  * تحديث مسميات أزرار المرفقات في الشريط العلوي التفاعلي لتظهر بنمط `[ 🔬 مرفق: CBC (5-Part Diff) (PDF) ]`.
- **ترقية واجهة محطة تحاليل الطوارئ ومعالجة المرفقات ([resources/views/staff/requests/emergency-lab-show.blade.php](file:///C:/get/hospital-system/resources/views/staff/requests/emergency-lab-show.blade.php) & [app/Http/Controllers/StaffRequestController.php](file:///C:/get/hospital-system/app/Http/Controllers/StaffRequestController.php))**:
  * إضافة المسمى الجديد `CBC (5-Part Diff)` في قائمة الأكواد البديلة `$codeAliases` لربطه التلقائي مع `cbc_3DIFF`.
  * دعم جلب المرفق الطبي للجهاز الآلي باسم `CBC (5-Part Diff)` أو الأسماء السابقة دون أي فقد للمرفقات السابقة.
- **حزم الاختبارات الآلية الشاملة**:
  * تحديث واجتياز اختبارات `CbcTestNameStandardizationTest.php`, `UnifiedEmergencyLabStationAndReportTest.php`, و `PaymentVoidAndReversalTest.php` بإجمالي 21 اختباراً ناجحاً بنسبة 100% (133 assertions).

## Session log (2026-10-29 — المرحلة 128: فك ارتباط وإلغاء كافة الطلبات الطبية عند إبطال الوصل Rebill و Refund ومنع ظهورها في طابور المختبر والأشعة وإظهارها كملغاة عند الطبيب Reopened & Refunded Payment Medical Requests Cascade Cancellation, Departmental Queue Purification & Clinical Doctor Alerts)

### Done
- **التحصين الشامل في كنترولر إبطال وإعادة فتح الوصولات ([app/Http/Controllers/PaymentVoidController.php](file:///C:/get/hospital-system/app/Http/Controllers/PaymentVoidController.php))**:
  * حصر وجمع كافة معرفات الطلبات الطبية المرتبطة بالوصل المالي المُراد إبطاله بأي شكل (`$payment->request_id`, `$payment->lab_request_ids`, `$payment->radiology_request_ids`, وكذلك أي طلب يشير معرف دفعه صراحة `where payment_id = payment->id`).
  * **عند إعادة الفتح للتسديد والتصحيح (`void_type === 'rebill'`)**: فك ارتباط وتصفير كافة الطلبات المدمجة دفعة واحدة (`payment_id = null`, `status = 'pending'`, `payment_status = 'pending'`) وإعادة حالة الزيارة إلى `pending_payment` لضمان ظهور كافة طلبات المريض في طابور الكاشير للتسديد دون نسيان أي فحص ملحق.
  * **عند الاسترداد والإلغاء النهائي (`void_type === 'refund'`)**: إلغاء كافة الطلبات المرتبطة دفعة واحدة (`status = 'cancelled'`, `payment_status = 'cancelled'`) وتوثيق سبب الإلغاء وتاريخه ومعرف الكاشير في بيانات الطلب `details` لتظهر فوراً في شاشة الطبيب.
  * شمول طوارئ الأشعة `EmergencyRadiologyRequest` في عمليتي إعادة الفتح والاسترداد بجانب طوارئ المختبر.
- **تحديث وإحكام معالجة الدفع لدى الكاشير ([app/Http/Controllers/CashierController.php](file:///C:/get/hospital-system/app/Http/Controllers/CashierController.php))**:
  * تخزين مصفوفات معرفات الفحوصات المسددة فعلياً في حقلي `lab_request_ids` و `radiology_request_ids` بجدول `payments` لتوثيق الفحوصات المشمولة بالوصل بدقة ومنع ضياع أي فحص مدمج.
  * إضافة صمام أمان رقابي: أي طلب طبي كان مشمولاً في فاتورة الزيارة ولم يتم تسديده ولم يؤجل، يُعتبر ملغياً بطلب المريض وتُحدث حالته إلى `cancelled` مع توثيق السبب ليختفي من طابور الفحص ويظهر للطبيب.
- **تحصين وتطهير طوابير العمل في المختبر والأشعة ([app/Http/Controllers/LabStaffController.php](file:///C:/get/hospital-system/app/Http/Controllers/LabStaffController.php) & [app/Http/Controllers/RadiologyStaffController.php](file:///C:/get/hospital-system/app/Http/Controllers/RadiologyStaffController.php))**:
  * منع ظهور أي طلب طبي حالته `cancelled` أو حالة دفعه `payment_status = 'cancelled'`.
  * استبعاد أي طلب طبي مرتبط بوصل مالي تم إبطاله أو استرداده (`whereDoesntHave('payment', fn ($pq) => $pq->whereNotNull('voided_at'))`).
  * إضافة علاقة `payment()` من نوع `belongsTo` في نموذج [app/Models/Request.php](file:///C:/get/hospital-system/app/Models/Request.php).
- **تنظيف وتصحيح السجلات العالقة ([database/migrations/2026_10_29_192000_unbind_and_cleanup_voided_payments_requests.php](file:///C:/get/hospital-system/database/migrations/2026_10_29_192000_unbind_and_cleanup_voided_payments_requests.php))**:
  * معالجة وترقية كافة الطلبات السابقة المرتبطة بوصولات ملغاة، وتصحيح حالة الفحص 859 (Free thyroxin) العالق في الوصل 1677 على السيرفر الحي وتحويله لملغي.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/PaymentVoidAndReversalTest.php](file:///C:/get/hospital-system/tests/Feature/PaymentVoidAndReversalTest.php))**:
  * بناء واجتياز اختبارين ميزة شاملين يغطيان دورة حياة إعادة الفتح الكاملة وتصفير كافة الطلبات المدمجة، وإلغاء الفحوصات غير المسددة واختفائها من طابور المختبر وظهورها في شاشة الطبيب كتنبيه سريري (`10 passed, 66 assertions`).

## Session log (2026-10-29 — المرحلة 127: توحيد آلية فحص الدم CBC & Blood Film وفحص فيلم الدم المجهري Blood Film في طوارئ المختبر والاستشارية 100% ومنع تفريغ البارامترات الفارغة Emergency Lab CBC & Blood Film Parity, Dedicated Microscopic Blood Film & Clean Device Report Architecture)

### Done
- **عزل وضبط فحصي الدم CBC & Blood Film و Blood Film في تقارير طوارئ المختبر ([resources/views/staff/requests/emergency-lab-print.blade.php](file:///C:/get/hospital-system/resources/views/staff/requests/emergency-lab-print.blade.php))**:
  * منع تفريغ الـ 27 بارامتراً فرعياً كصفوف فارغة أو معلقة (`Pending`) في جدول نتائج الفحوصات البيوكيميائية عند طلب فحص `CBC & Blood Film` المعتمد على مرفق الجهاز الآلي (`isAttachmentOnly`).
  * مزامنة المرفقات الفردية لطلبات الطوارئ مع جدول `emergency_lab_request_tests` لضمان ظهور زر تقرير الجهاز الآلي في الشريط العلوي بنمط `[ 🔬 مرفق: CBC & Blood Film (PDF) ]` أياً كان مصدر حفظ المرفق.
  * إزالة الصندوق المرفق من الورقة المطبوعة نهائياً لمطابقة الاستشارية 100%، والاكتفاء بالزر التفاعلي في شريط الأدوات العلوي بنمط `[ 🔬 مرفق: CBC & Blood Film (PDF) ]` لفتح وطباعة التقرير الأصلي الصادر من جهاز التحليل دون أي تشويه بصري للورقة.
  * عزل فحص فيلم الدم المجهري اليدوي `Blood Film` (رقم 1243، بسعر 50,000 د.ع) كفحص سريري مستقل (`single_value`) يظهر في جدول النتائج المباشرة بقيمته وتفاصيله المجهرية دون أي تداخل مع فحص الجهاز الآلي.
- **تحديث وتوحيد مسمى الفحص في طباعة الاستشارية ([resources/views/staff/requests/print.blade.php](file:///C:/get/hospital-system/resources/views/staff/requests/print.blade.php))**:
  * تعميم دالة التطبيع السريرية `$canonicalTestName` على شريط الأدوات العلوي وقوائم المرفقات في طباعة الاستشارية والطلبات المباشرة لمنع ظهور المسمى القديم `Complete blood count & Differential` واستبداله بالمسمى الرسمي المعتمد `CBC & Blood Film`.
- **حزم الاختبارات الآلية الشاملة ([tests/Feature/UnifiedEmergencyLabStationAndReportTest.php](file:///C:/get/hospital-system/tests/Feature/UnifiedEmergencyLabStationAndReportTest.php))**:
  * إضافة وتوسيع اختبارات الميزة لتغطية عدم تفريغ البارامترات الـ 27 لـ CBC في التقرير، والتعامل السليم مع المرفقات، والتمييز التام بين فحص CBC الآلي وفيلم الدم المجهري اليدوي (`10 passed, 62 assertions`).

## Session log (2026-10-29 — المرحلة 126: توحيد محطة ومطبوعات طوارئ الأشعة 100% مع محطة الأشعة والتقرير السريري الموحد Emergency Radiology Station & Print Report 100% Unification)

### Done
- **قاعدة البيانات وموديل الطلبات الطبية ([database/migrations/2026_10_29_131000_add_results_data_to_emergency_radiology_requests_table.php](file:///C:/get/hospital-system/database/migrations/2026_10_29_131000_add_results_data_to_emergency_radiology_requests_table.php) & [app/Models/EmergencyRadiologyRequest.php](file:///C:/get/hospital-system/app/Models/EmergencyRadiologyRequest.php))**:
  * إضافة حقل `results_data` لتخزين المشاهدات (Findings)، الاستنتاج (Impression)، التوصيات (Recommendations)، مصفوفة المرفقات (Images)، وبيانات الاعتماد.
  * تحديث `$fillable` و `$casts` في نموذج `EmergencyRadiologyRequest`.
- **ترقية وتوحيد واجهة محطة إدخال طوارئ الأشعة ([resources/views/staff/requests/emergency-radiology-show.blade.php](file:///C:/get/hospital-system/resources/views/staff/requests/emergency-radiology-show.blade.php))**:
  * مطابقة تامة 1:1 مع التصميم السريري الهادئ (`Calm Clinical Design`) المعتمد في محطة الأشعة.
  * إضافة قوالب التقارير السريعة بنقرة واحدة (`Quick Templates Chips`): فحص سليم طبيعي، أشعة صدر سليمة، سونار بطن وحوض سليم، أشعة عظام سليمة، نتائج طارئة غير طبيعية.
  * حقول سريرية مفصلة: Findings و Impression و Recommendations مع منطقة سحب وإفلات ومعاينة فورية للصور وحذف المرفقات.
  * شريط المريض المتكامل مع بطاقة تنبيه بالأعراض السريرية ودواعي الفحص المسجلة من طبيب الطوارئ.
- **إعادة بناء وتوحيد تقرير طباعة طوارئ الأشعة ([resources/views/staff/requests/emergency-radiology-print.blade.php](file:///C:/get/hospital-system/resources/views/staff/requests/emergency-radiology-print.blade.php))**:
  * مطابقة 100% مع الهوية الرسمية للأشعة والمستشفى (`#005a9c` أزرق داكن، `#0284c7` أزرق سماوي) وخط Cairo.
  * شريط أدوات علوي تفاعلي: طباعة، حفظ PDF، رابط واتساب مباشر للمريض، ومبدل الورق المروس (`Letterhead Mode`) مع ضابط الهامش.
  * شعار المستشفى المدمج Base64 للظهور الفوري، وبطاقة المريض، والتقسيم السريري الدقيق، ومربع التوقيع والختم المعتمد، ورمز التحقق QR Code المباشر.
- **التحصين في الكنترولر ([app/Http/Controllers/StaffRequestController.php](file:///C:/get/hospital-system/app/Http/Controllers/StaffRequestController.php))**:
  * تصحيح مسار علاقات القسم `emergency.doctor.department`.
  * دعم استخراج وعرض البيانات القديمة مثل نص `CXR` والمرفقات القديمة بدقة 100% دون أي فقدان.
  * دعم حفظ وتحديث `results_data` مع مزامنة جدول `emergency_radiology_request_types` لضمان التوافق العكسي التام.
- **حزم الاختبارات الآلية ([tests/Feature/UnifiedEmergencyRadiologyStationAndReportTest.php](file:///C:/get/hospital-system/tests/Feature/UnifiedEmergencyRadiologyStationAndReportTest.php) & [tests/Feature/EmergencyRadiologyPrintTest.php](file:///C:/get/hospital-system/tests/Feature/EmergencyRadiologyPrintTest.php))**:
  * اجتياز 5 اختبارات ميزة تغطي المحطة السريرية الجديدة، واعتماد التقرير، والطباعة بوجود مرفقات أو بدونها (`5 passed, 37 assertions`).

## Session log (2026-10-29 — المرحلة 125: دمج وتكامل قسم الرنين المغناطيسي والمفراس تحت محطة عمل واحدة MRI & CT Workstation Integration)

### Done
- **توحيد فلاتر وقنوات المفراس والرنين في الكنترولر ([app/Http/Controllers/RadiologyStaffController.php](file:///C:/get/hospital-system/app/Http/Controllers/RadiologyStaffController.php) & [app/Http/Controllers/RadiologyController.php](file:///C:/get/hospital-system/app/Http/Controllers/RadiologyController.php))**:
  * توسيع استعلام قسم الرنين `selectedCategory === 'mri'` ليشمل تلقائياً كلاً من فحوصات الرنين والمفراس معاً (`whereIn('subtype', ['mri', 'ct'])`) في كافة استعلامات القائمة الرئيسية، طلبات الانتظار، الرادار اللحظي، وطابور النداء.
  * تحديث التحقق الأمني من صلاحيات الطلب (`authorizeRequestAccess`) للسماح لحسابات الرنين (`radiology_mri`) بفتح وفحص وكتابة وتعديل تقارير فحوصات المفراس (`ct`) دون أي خطأ 403.
  * دعم تصنيف المفراس في استعلامات طوارئ الأشعة لحساب الرنين (`subcategory in ['الرنين', 'مفراس', 'رنين']`).
- **تحسين واجهة محطة الأشعة والتصوير الطبي ([resources/views/radiology-staff/index.blade.php](file:///C:/get/hospital-system/resources/views/radiology-staff/index.blade.php))**:
  * تحديث زر التبديل السريع ليظهر بالمسمى الرسمي الموحد `🧲 الرنين والمفراس`.
- **حزمة الاختبارات الآلية ([tests/Feature/MriAndCtMergedStationAccessTest.php](file:///C:/get/hospital-system/tests/Feature/MriAndCtMergedStationAccessTest.php))**:
  * بناء واجتياز اختبار ميزة يضمن قدرة فني الرنين على استعراض وفتح فحوصات الرنين والمفراس (CT & MRI) بسلاسة تامة (`1 passed, 5 assertions`).

## Session log (2026-10-29 — إخفاء أشرطة الباقات السريعة للتحاليل والأشعة في الخدج مع الحفاظ عليها، ودعم كتابة الأدوية غير المتوفرة بالصيدلية كعلاج مخصص)

### Done
- **إخفاء أشرطة الباقات السريعة للتحاليل والأشعة في محطة الخدج مع الحفاظ على الكود بالكامل دون حذف**:
  * إخفاء شريط `تصوير الخدج الشائع (نقرة واحدة للإضافة):` باستخدام كلاس `d-none` مع الحفاظ التام على أزرار التصوير الـ 5 وبياناتها في `resources/views/nicu/show.blade.php`.
  * إخفاء شريط `باقات الخدج السريعة (نقرة واحدة للإضافة):` وشريط التنبيه البديل للقسم باستخدام كلاس `d-none` مع الحفاظ على كافة سمات البيانات وبيانات الباقات المربوطة.
- **تأكيد وتعزيز كتابة الأدوية الحرة والتركيبات المخصصة غير المسجلة بصيدلية المستشفى**:
  * توضيح آلية عمل النظام البرمجية: حقل اسم الدواء يقبل أي نص حر بدون تقييد خارجي، ويتم تخزينه في جدول `nicu_medical_orders` بشكل مباشر وسليم سريرياً.
  * تحديث القائمة المنسدلة للإكمال التلقائي للأدوية `attachMedAutocomplete`: عند كتابة أي اسم دواء غير موجود بالصيدلية (أو عند كتابة أي تركيبة خاصة)، يظهر خيار تأكيدي مرئي بنقرة واحدة: `[ ➕ اعتماد الدواء المكتوب: "..." (علاج مخصص / تركيبة خاصة غير مسجلة بقائمة الصيدلية) ]` بالإضافة إلى إمكانية اعتماده مباشرة بمجرد الكتابة والانتقال للحقول التالية.
  * تحديث قالب الصفوف الديناميكية عند النقر على `[ ➕ إضافة دواء آخر ]` ليتطابق مع الصف الأول ويوضح للمستخدم حرية كتابة أي علاج مخصص.
- **الاختبارات وضمان الجودة والشجرة المعرفية**:
  * اجتياز كامل حزمة اختبارات الخدج `NicuWorkflowTest` بنسبة 100% (39 اختباراً، 341 تأكيداً).
  * فحص التنسيق البرمجي والتأكد من توافقه عبر `vendor/bin/pint`.
  * تحديث الشجرة المعرفية Graphify إلى 5,907 عقدة و 10,959 علاقة.
  * الالتزام الصارم بقاعدة Git: كل العمل محفوظ محلياً على فرع `hr`.

## Session log (2026-10-29 — أتمتة تحديد صفة المريض بحسب العمر وإلغاء السؤال الإجباري ومزامنة بيانات الرابط ?patient_id=...)

### Done
- **أتمتة تحديد صفة القيد بالكامل بناءً على العمر دون أي سؤال إجباري للمستخدم**:
  * إذا كان عمر المريض أقل من سنة (`age < 1`): يُحدّد النظام تلقائياً وبشكل قاطع أن المريض هو **طفل خديج (ملف طفل مستقل)** (`is_independent_patient = 1`).
  * إذا كان عمر المريض سنة فما فوق (مثل المريض 91 بعمر 29 سنة): يُحدّد النظام تلقائياً أن المريض هو **الأم أو الأب (ولي أمر بالغ)** (`is_independent_patient = 0`).
  * إلغاء الصندوق المنفصل للسؤال الإجباري (`صفة المريض المختار في هذا الحجز *`) واستبداله بشارة ذكية داخل بطاقة المريض مع زر تعديل يدوي اختياري (`تعديل الصفة`).
  * تحديث اسم الطفل المقترح وتلميح الحقل `babyNameHint` تلقائياً (توليد "طفل + اسم ولي الأمر" للبالغين، واعتماد اسم الطفل ذاته للخدج).
- **المزامنة والتهيئة التلقائية الفورية للمريض المحمل عبر الرابط (`?patient_id=...`)**:
  * مزامنة صفة القيد فور تحميل الصفحة.
  * ملء وتفعيل بيانات الضمان الصحي فوراً (اختيار `type_hi`، فتح صندوق تفاصيل الضمان، إدراج رقم بطاقة الضمان، واختيار فئة الضمان الصحي).
- **الاختبارات وضمان الجودة**:
  * إضافة اختبار ميزة شامل `test_incubator_reservation_auto_detects_role_by_age` واجتيازه بنجاح تام (11 تأكيداً).
  * اجتياز كامل حزمة اختبارات الخدج `NicuWorkflowTest` بنسبة 100% (39 اختباراً، 341 تأكيداً).
  * تحديث الشجرة المعرفية Graphify إلى 5,905 عقدة و 10,956 علاقة.
  * الالتزام الصارم بقاعدة Git: الحفظ والرفع حصراً على فرع `hr`.

## Session log (2026-10-29 — مطابقة فئات الضمان الصحي بين حجز الخدج وتسجيل المريض وجعل الفئة E للأطفال دون سن الخامسة هي الافتراضية)

### Done
- **مطابقة خيارات وترتيب فئات الضمان الصحي (A إلى I) مع شاشة تسجيل المريض**:
  * جلب الفئات ديناميكياً من جدول `health_insurance_categories` بالترتيب القياسي المعتمد (`orderBy('sort_order')`) في متحكم حجز الحاضنات `IncubatorReservationController::create`.
  * تحديث القائمة المنسدلة في `resources/views/incubator_reservations/create.blade.php` لتعرض كافة فئات هيئة الضمان الصحي الوطني مع الرمز والوصف التفصيلي الدقيق.
  * توحيد عرض الوصف في شاشتي تسجيل وتعديل المرضى (`patients/create.blade.php` و `patients/edit.blade.php`) لتشمل وصف كل فئة بوضوح.
- **تثبيت الفئة E كفئة افتراضية قياسية لحديثي الولادة والخدج**:
  * جعل الخيار الافتراضي في حجز الحاضنات هو `الفئة E (الأطفال دون سن الخامسة)` سواء عند فتح النموذج لأول مرة أو عند استرجاع بيانات مريض غير مشمول مسبقاً.
  * ضبط التحقق والمعالجة في `IncubatorReservationController::store` بحيث يتم اعتماد الفئة `E` تلقائياً لأي حجز ضمان صحي في حال عدم تحديد الفئة أو إرسال معرف عددي.
  * تحديث المعاينة الفورية لشرائح تغطية الضمان `updateTierPreview` لتوضيح شرائح العمر الحملي الخاصة بالفئة E.
  * تعزيز الجافاسكربت في تسجيل المرضى بحيث يتم اقتراح واختيار الفئة E تلقائياً عند إدخال تاريخ ميلاد لعمر أقل من 5 سنوات.
- **الاختبارات وضمان الجودة**:
  * إضافة اختبار ميزة شامل `test_incubator_reservation_renders_insurance_categories_matching_patient_registration_with_category_e_default` واجتيازه بنجاح تام.
  * اجتياز كامل حزمة اختبارات الخدج `NicuWorkflowTest` بنسبة 100% (38 اختباراً، 330 تأكيداً).
  * اجتياز كامل اختبارات دورة العمل المركزية `NicuLabRadiologyCashierWorkflowTest` بنسبة 100% (4 اختبارات، 82 تأكيداً).
  * تحديث الشجرة المعرفية Graphify إلى 5,898 عقدة و 10,946 علاقة.
  * الالتزام الصارم بقاعدة Git: الحفظ والرفع حصراً على فرع `hr`.

## Session log (2026-10-26 — تخصيص وعزل باقات تحاليل الخدج الديناميكية التخصصية NICU-Specific Lab Packages & Departmental Isolation)

### Done
- **عزل باقات الخدج التخصصية بالكامل عن باقات الاستشارية والعيادات (`department = 'nicu'`)**:
  * إضافة عمود `department` وعمود `is_public` في جدول `user_lab_test_groups` عبر مايجريشن `2026_10_26_113000_add_department_to_user_lab_test_groups_table.php` وتحديث الموديل `UserLabTestGroup.php`.
  * حصر استعلام محطة الخدج `NicuController::show` في الباقات الخاصة بالخدج فقط (`department = 'nicu'`) سواء العامة أو الخاصة بالأطباء المشرفين، ومنع ظهور أي باقات استشارية أو خارجية (مثل الجلدية والسكري والروماتزم) في محطة الخدج نهائياً.
  * حصر استعلام عيادات الاستشارية `DoctorVisitController::show` في الباقات الاستشارية والعامة عبر Scope `consultant()` لمنع تداخل باقات الخدج مع العيادات.
- **تأسيس الباقات القياسية الرسمية للخدج في النظام (`NicuLabTestGroupsSeeder`)**:
  * باقة دخول روتيني لحديثي الولادة (9 تحاليل تشمل CBC, ABO/Rh, TSB, Glucose, Calcium).
  * باقة يرقان وصفار الخدج (7 تحاليل تشمل TSB, Direct, Indirect, CBC, ABO/Rh).
  * باقة غازات الدم والحموضة ABG (3 تحاليل تشمل ABG, VBG, Lactate).
  * باقة مسح إنتان الخدج Sepsis Screen (6 تحاليل تشمل CBC, HS-CRP, Blood C/S, Blood Film).
  * باقة وظائف الكلى والأملاح للخدج (5 تحاليل تشمل Urea, Creatinine, Na, K, Calcium).
- **تطوير واجهة إدارة المجموعات والباقات (`resources/views/lab-tests/groups/index.blade.php`)**:
  * شريط تبويبات فلترة: كافة الباقات، باقات قسم الخدج (NICU)، وباقات العيادات والاستشارية.
  * وسام واضح لكل باقة يحدد قسمها (`قسم الخدج` / `عيادات واستشارية`) وما إذا كانت باقة عامة للقسم.
  * دعم تحديد القسم عند إنشاء باقة جديدة وخيار جعلها باقة عامة للمستخدمين بصلاحية الإدارة.
  * توجيه زر محطة الخدج `[ 👶 إدارة باقات الخدج ]` مباشرة للرابط المفلتر `?department=nicu`.
- **الاختبارات وضمان الجودة والشجرة المعرفية**:
  * اجتياز اختبار العزل `test_lab_test_groups_department_isolation_between_nicu_and_consultant` واختبار العرض `test_nicu_workstation_renders_user_lab_test_groups_matching_consultant_doctor`.
  * اجتياز كامل حزمة اختبارات الخدج `NicuWorkflowTest` بنجاح 100% (36 اختباراً، 295 تأكيداً).
  * اجتياز كامل اختبارات دورة العمل المركزية `NicuLabRadiologyCashierWorkflowTest` بنجاح 100% (4 اختبارات، 82 تأكيداً).
  * المجموع: **40 اختباراً ناجحاً بنسبة 100% (377 تأكيداً)**.
  * تحديث الشجرة المعرفية Graphify إلى 5,459 عقدة و 9,961 علاقة.
  * الالتزام الصارم بقاعدة Git: كل العمل محفوظ محلياً على فرع `hr`.

## Session log (2026-10-26 — المخطط البياني لتتبع نمو ووزن الخديج ومطابقة محطة المختبر والأشعة مع الطبيب الاستشاري بالكامل)

### Done
- **المخطط البياني لتتبع وزن ونمو الطفل الخديج (Weight & Growth Curve) في تبويب مستقل (`[ 📈 معدلات النمو والوزن ]`)**:
  * إضافة تبويب مستقل في واجهة الخدج السريرية مع 5 بطاقات مؤشرات حيوية رئيسية (KPIs): وزن الولادة/الدخول كخط أساس، الوزن الحالي، صافي التغير التراكمي مع النسبة المئوية (%)، معدل النمو اليومي (جم/يوم) مقارنة بالمعدل الطبيعي (15 - 30 جم/يوم)، ومدة الحضانة بالأيام مع عدد القراءات.
  * رسم تفاعلي متقدم عبر Chart.js (`#nicuWeightGrowthChart`) يقارن منحنى تدرج وزن الطفل مع خط الأساس لوزن الدخول (Baseline)، مع تهيئة ديناميكية عند فتح التبويب `shown.bs.tab`.
  * إرشاد سريري فسيولوجي لحديثي الولادة يوضح النزول الطبيعي حتى 10% في الأيام الأولى ثم بدء النمو المتسارع.
  * جدول سجل القياسات التفصيلي وتدرج الوزن (Weight Progression Log) يوثق التاريخ والوقت، الوزن، التغير عن القراءة السابقة، التغير التراكمي عن البداية، العلامات الحيوية المرافقة (حرارة/نبض/تشبع أكسجين)، التغذية، وهوية الممرض الموثق.
- **مطابقة محطة طلبات المختبر والأشعة في قسم الخدج مع ترتيب وشاشات الطبيب الاستشاري (Consultant Doctor Workstation) بنسبة 100%**:
  * **محطة المختبر (`#labPane`)**:
    1. رأس المحطة مع زر إرسال علوي وسفلي تفاعلي مع عداد التحاليل المختارة.
    2. شريط باقات الخدج الشائعة والسريعة بنقرة واحدة (دخول روتيني، يرقان وصفار، غازات الدم، مسح إنتان، وظائف الكلى والأملاح).
    3. حقل البحث الذكي مع تصفية فورية، زر مسح سريع، وقائمة اقتراحات Autocomplete ودعم إضافة تحاليل مخصصة.
    4. صينية التحاليل المختارة (Chips Tray) مع إمكانية حذف أي تحليل أو إفراغ الكل بنقرة واحدة.
    5. حقل ملاحظات وتوجيهات الطبيب السريرية لفني التحاليل بالمختبر المركزي.
    6. اختيار أولوية وسرعة الفحص (عادي، عاجل، فوري طارئ STAT 🚨).
    7. شريط تبويبات تصفية الأقسام الطبية (Category Pills) والمختارة فقط (Selected Only).
    8. شبكة بطاقات التحاليل الثنائية (Compact 2-Column Grid) مع شارة التأشير والتحديد اللوني والأسعار والأكواد.
    9. تقسيم متوازن جنباً إلى جنب (`col-xl-6` لمحطة الطلب و `col-xl-6` لسجل ومتابعة الطلبات والنتائج المركزية والمرفقات ومسار المراحل).
  * **محطة الأشعة والسونار (`#radiologyPane`)**:
    1. رأس المحطة مع أزرار الإرسال والعداد.
    2. شريط فحوصات التصوير الشائعة لحديثي الولادة (أشعة صدر نقالة بالحاضنة، سونار الدماغ واليافوخ، إيكو قلب وتفقد PDA، أشعة بطن نقالة NEC، وتصوير كامل Babygram).
    3. البحث الذكي، صينية الفحوصات المختارة، ملاحظات الطبيب لقسم الأشعة، الأولوية، تبويبات التصنيفات، والشبكة الثنائية للبطاقات.
- **تكامل المرفقات الطبية الحرة وملاحظات الطبيب الاختصاص المثبتة وصلاحيات الأدوار**:
  * تبويب مستقل للمرفقات والمستندات الطبية مع حرية تسمية الملف وحفظه بسجل المريض وأرشفة ملفات PDF والصور.
  * بنر علوي بارز لملاحظات الطبيب الاستشاري وتثبيتها في أعلى محطة الخدج.
  * دعم عرض مرفقات أجهزة فحص المختبر المركزي (Sysmex CBC report / curves) داخل محطة الخدج.
  * هيكلة صلاحيات دقيقة (`nicu_doctor` للأوامر السريرية وملاحظات الاختصاص، `nicu_nurse` لمحطة التمريض وخطة الرعاية MAR).
- **الاختبارات وضمان الجودة والشجرة المعرفية**:
  * اجتياز كامل حزمة اختبارات الخدج `NicuWorkflowTest` بنجاح 100% (34 اختباراً، 280 تأكيداً).
  * اجتياز كامل اختبارات دورة العمل المركزية `NicuLabRadiologyCashierWorkflowTest` بنجاح 100% (4 اختبارات، 82 تأكيداً).
  * المجموع: **38 اختباراً ناجحاً بنسبة 100% (362 تأكيداً)**.
  * تحديث الشجرة المعرفية Graphify إلى 5,443 عقدة و 9,923 علاقة.
  * الالتزام الصارم بقاعدة Git: كل العمل محفوظ محلياً على فرع `hr`.

## Session log (2026-10-22 — منظومة صرف واسترجاع فائض أمانات الخدج بالكاشير، إدراجها بتبويب الاسترجاع، وسند الاسترجاع والطباعة الحرارية)

### Done
- **منظومة صرف واسترجاع فائض الأمانات للخدج في الكاشير (`CashierController::processNicuPayment`, `resources/views/cashier/index.blade.php`)**:
  * رصد الحالات التي لديها فائض أمانات مسددة تتجاوز إجمالي الفاتورة (`excess_deposit > 0`).
  * إضافة وسم بارز في تبويب الخدج: `[ 💰 فائض: +X د.ع (مستحق الصرف للمريض) ]`.
  * إضافة زر مخصص ومباشر: `[ ↩️ إرجاع الأمانات ]` يفتح نافذة متخصصة `#nicuRefundModal`.
  * تقييد المبلغ بحيث لا يمكن صرف أكثر من الفائض الفعلي المتوفر، مع زر سريع `[ صرف كامل الفائض ]` وتوثيق العملية في سجل الأنشطة `nicu_refund_processed`.
  * خصم مبلغ الاسترجاع تلقائياً من صافي الأمانات بالصندوق وتصفية حساب الحاضنة ليصبح الفائض صفراً والموقف المالي متوازناً تماماً.
- **إدراج فائض أمانات الخدج في تبويب مستحقات الاسترجاع العام (`#tab-refunds`)**:
  * ربط حالات الخدج ذات الفائض بتبويب `مستحقات الاسترجاع` مع زيادة عداد التنبيه الأحمر.
  * عرض بطاقة الحالة مع بيانات الطفل، الحاضنة، الأمانات المسددة، والمبلغ المستحق إرجاعه مع زر إرجاع فوري.
  * استمرار ظهور الحالات التي تم تخريجها إذا كان لديها فائض أمانات مستحق للإرجاع.
- **سند صرف واسترجاع الأمانات والطباعة الحرارية 78mm (`cashier.nicu.receipt`, `cashier.nicu.receipt.print`)**:
  * تحويل السند تلقائياً إلى `سند صرف واسترجاع أمانات - قسم الخدج (NICU)`.
  * إبراز المبلغ المسترجع بإشارة سالبة ولون أحمر، وتحديد الكاشير الصارف وتاريخ الصرف.
  * تحديث كشف الحساب التراكمي للخدج ليعكس حركات الاسترجاع بسطور حمراء واضحة وصافي الأمانات المسددة بعد التسوية.
- **الاختبارات وضمان الجودة**:
  * إضافة اختبار ميزة شامل `test_cashier_can_refund_excess_deposits_for_nicu_reservation` واجتيازه بنجاح 100%.
  * اجتياز كامل الاختبارات (34 اختباراً، 314 تأكيداً).
  * تحديث الشجرة المعرفية Graphify إلى 5,469 عقدة و 9,877 علاقة.

## Session log (2026-10-22 — حل مشكلة "غير محدد" بمختبر وأشعة الخدج، بوابة الخروج المالي الإلزامية، ونظام خطة الرعاية والتوصيات التكراري مع تنبيهات الحاضنات اللحظية)

### Done
- **معالجة مسمى المريض والمصدر في لوحتي المختبر والأشعة عند "إظهار الكل" (`lab.index`, `radiology-staff.index`)**:
  * حل مشكلة ظهور اسم المريض "غير محدد" ونوع الطلب "مختبر خارجي مباشر" وحالة الدفع "غير مسدد" عند عرض جميع الطلبات في لوحة المختبر ولوحة الأشعة.
  * فحص معرّف حجز الحاضنة (`incubator_reservation_id`) وعرض اسم الطفل الخديج بوضوح، ورقم الحاضنة، ورقم الملف، والطبيب الأخصائي، مع وسم بارز: `[ 👶 قسم الخدج والحاضنات ]` وشارة دفع `[ حساب الحاضنة ]`.
  * دعم البحث في المختبر والأشعة باسم الطفل الخديج، واسم المريض/الأم، واسم الطبيب المشرف مع تفعيل `eager loading` للعلاقات لمنع استعلامات N+1.
- **بوابة الخروج المالي الإلزامية للخدج وإبقاء الحسابات المعلقة بالكاشير (`NicuController::discharge`, `IncubatorReservationController::discharge`, `CashierController::index`)**:
  * منع تخريج أي طفل خديج في حال وجود متبقٍ مالي بذمته (`$stayDetails['net_balance'] > 0`)، مع توجيه المستخدم برسالة خطأ عربية صريحة وإعادة التوجيه إلى الكاشير للتسديد أولاً.
  * في حال وجود حالات سابقة تم تخريجها مع وجود رصيد متبقٍ، تم تحديث كاشير الخدج (`/cashier`) للإبقاء على ظهور بطاقاتهم مع وسم تحذيري واضح: `[ ⚠️ خروج معلق مالياً ]` لمنع ضياع أي مستحقات للمستشفى.
- **منظومة خطة الرعاية والتوصيات السريرية التكرارية للخدج (`nicu_care_schedules`)**:
  * إنشاء جدول قاعدة بيانات `nicu_care_schedules` عبر مايجريشن `2026_10_22_104049_create_nicu_care_schedules_table.php` ونموذج Eloquent `NicuCareSchedule.php` مع علاقات الحجز والمستخدم المنشئ ودوال المواعيد والتنبيهات.
  * إضافة تبويب جديد كامل في محطة الخدج السريرية: `[ 📋 التوصيات وخطة الرعاية ]` يمكن من خلاله للأطباء والتمريض:
    1. جدولة مواعيد الرضاعة التكرارية (كل ساعتين، 3 ساعات، 4 ساعات، 6 ساعات) بنوع الرضاعة، الكمية، والتعليمات.
    2. جدولة مواعيد إعطاء الأدوية التكرارية (كل 4، 6، 8، 12، 24 ساعة) بالجرعة، المسار، والتعليمات.
    3. إضافة وإلغاء وتعديل التوصيات المتعددة في آن واحد.
  * **الربط التفاعلي مع محطة التمريض وسجل العلاج**:
    * عند إدخال الرضاعة في شاشة العلامات الحيوية، يتم تحديث موعد الرضاعة القادم تلقائياً.
    * في سجل إعطاء العلاج MAR، تظهر الأدوية المجدولة بزر تنفيذ سريع ينشئ توثيق الجرعة ويحدّث موعد الجرعة القادمة بضغطة زر واحدة.
  * **عزل سريري تام**: التوصيات وجدول العلاج سريرية تمريضية بحتة، ولا تسبب أي حركة بالمخزن أو الصيدلية أو كاشير العيادات.
- **شريط تنبيهات الحاضنات اللحظي على لوحة الحاضنات الرئيسية (`resources/views/nicu/index.blade.php`)**:
  * ظهور بطاقات تنبيهية بارزة ولحظية على كل حاضنة: `🍼 حان موعد الرضاعة` و `💊 حان موعد إعطاء دواء` مع إظهار تفاصيل الدواء أو الرضاعة ومقدار التأخير.
- **الاختبارات وضمان الجودة والشجرة المعرفية**:
  * اجتياز اختبارات دورة العمل الكاملة `NicuLabRadiologyCashierWorkflowTest` بنجاح 100% (3 اختبارات، 64 تأكيداً).
  * اجتياز كامل حزمة اختبارات الخدج `NicuWorkflowTest` بنجاح 100% (30 اختباراً، 232 تأكيداً).
  * المجموع: **33 اختباراً ناجحاً بنسبة 100% (296 تأكيداً)**.
  * تحديث الشجرة المعرفية Graphify إلى 5,465 عقدة و 9,875 علاقة عبر 1,183 ملفاً.

## Session log (2026-10-21 — اكتمال مطابقة طلبات الخدج مع المختبر والأشعة المركزية بنمط الطبيب الاستشاري وشريط المسار التفاعلي)

### Done
- **مطابقة طلبات الخدج المركزية مع بنية طلبات الطبيب الاستشاري (`MedicalRequest` Bundle)**:
  * إنشاء مايجريشن قاعدة بيانات `2026_10_21_140000_add_incubator_reservation_id_to_requests_and_lab_results.php` لجعل `visit_id` اختيارياً وإضافة `incubator_reservation_id` كمعرف أجنبي لكل من `requests` و `lab_results`.
  * تجميع كافة التحاليل أو الفحوصات الشعاعية المطلوبة في أمر طبي مركزي موحد (`type: 'lab'`, `type: 'radiology'`) يُرسل مباشرة للأقسام المركزية بنفس خصائص طلب الطبيب الاستشاري مع الحفاظ على التزامن التوافقي مع `nicu_medical_orders`.
- **اعتماد واجهات الإدخال والتقارير الرسمية للمختبر والأشعة (`lab.show`, `radiology-staff.show`, `lab.print`, `radiology-staff.print`)**:
  * ربط طلبات الخدج مباشرة بمحطة إدخال النتائج الرسمية للمختبر (`/lab/requests/{request}/show`) مع دعم محرك الفحوصات الفرعية والمكونات (Sub-tests/Parameters e.g. CBC: WBC, RBC, HGB, PLT)، والمعدلات الطبيعية، والوحدات، والتقييمات السريرية، وإرفاق ملفات أجهزة الفحص، والطباعة الرسمية A5.
  * ربط طلبات الخدج بمحطة التقرير الشعاعي الرسمي للأشعة والسونار (`/radiology-staff/requests/{request}/show`) بكافة القوالب (Findings, Impression, Recommendations)، ورفع الصور والأفلام، والطباعة الرسمية A5.
  * عزل تام لكاشير العيادات الخارجية؛ تبقى حسابات الخدج مستقلة ومحصورة في `nicu_payments` وقيد الكاشير الموحد للخدج.
- **تحديث محطة طبيب الخدج ببطاقات المتابعة السريرية وشريط المراحل التفاعلي (`resources/views/nicu/show.blade.php`)**:
  * استبدال الجداول البسيطة في محطتي المختبر والأشعة ببطاقات سريرية شاملة مطابقة لمحطة الطبيب الاستشاري (`doctors/visits/show.blade.php`).
  * شريط مسار ومراحل الطلب التفاعلي المكون من 4 خطوات:
    1. طلب الطبيب (أخضر ✔)
    2. القيد المالي (أخضر ✔ حساب الحاضنة الموحد)
    3. القسم المنفذ (المختبر المركزي / قسم الأشعة والسونار: بالانتظار ⏳ / قيد الفحص 🔄 / أُنجز ✔)
    4. النتيجة والتقرير الطبي (النتيجة جاهزة 📄 / بالانتظار)
  * جدول مصغر للفحوصات المطلوبة، وملاحظات الطبيب، وجدول نتائج المكونات المخبرية التفصيلي مع تمييز الحالات الحرجة 🚨، وصندوق تقرير الأشعة المعتمد ومرفقات الصور، مع زر الطباعة الرسمية A5.
- **الاختبارات وضمان الجودة والشجرة المعرفية**:
  * اجتياز اختبارات دورة العمل الكاملة `NicuLabRadiologyCashierWorkflowTest` بنجاح 100% (3 اختبارات، 64 تأكيداً).
  * اجتياز كامل حزمة اختبارات الخدج `NicuWorkflowTest` بنجاح 100% (29 اختباراً، 210 تأكيدات).
  * المجموع: **32 اختباراً ناجحاً بنسبة 100% (274 تأكيداً)**.
  * تحديث الشجرة المعرفية Graphify إلى 5,374 عقدة و 9,591 علاقة.
  * الالتزام الصارم بقاعدة Git: كل العمل محفوظ محلياً على فرع `hr` دون أي رفع (`git push`).

## Session log (2026-10-21 — اكتمال ربط طلبات الخدج بالمختبر والأشعة المركزية والقيد المالي الموحد للكاشير)

### Done
- **توجيه طلبات المختبر والأشعة من محطة الخدج إلى الأقسام المركزية (`NicuController`, `LabStaffController`, `RadiologyStaffController`)**:
  * ربط طلبات الخدج (`nicu_medical_orders`) بجداول `lab_tests` و `radiology_types` وحفظ معرّف الفحص (`lab_test_id`, `radiology_type_id`)، والوحدة (`unit`)، والمعدل الطبيعي (`reference_range`)، ومجموعة الطلب (`order_group_id`).
  * ظهور طلبات الخدج في لوحة المختبر المركزية (`/lab/requests`) ولوحة الأشعة والسونار المركزية (`/radiology-staff/requests`) داخل تبويب مخصص ومستقل: `[ 👶 الخدج والحاضنات ]` مع عداد حي للحالات المعلقة وترتيب الحالات بأولوية (STAT الطارئة أولاً، ثم Urgent، ثم Normal).
  * إتاحة بدء الفحص (`in_progress`)، وإدخال النتيجة المكتوبة، والتقييم السريري (`Normal`, `High`, `Low`, `Critical 🚨`)، وملاحظات الفني، وإرفاق ملفات/صور الأشعة أو السونار من قبل الكادر التشخيصي.
  * تحديث فوري لحالة النتيجة والبيانات في محطة الخدج السريرية للطبيب المشرف (`/nicu/reservations/{id}`).
- **قوالب الطباعة الرسمية لنتائج الفحوصات المخبرية والشعاعية للخدج**:
  * إنشاء قالب طباعة رسمي A5 لتحاليل الخدج (`resources/views/nicu/print/lab-result.blade.php`) مع ترويسة المستشفى، بيانات الطفل والحاضنة، النتيجة، الوحدة، المعدل الطبيعي، وتوقيع الفني والأخصائي.
  * إنشاء قالب طباعة رسمي A5 لتقارير الأشعة والسونار للخدج (`resources/views/nicu/print/radiology-result.blade.php`) مع النتائج والانطباع الشعاعي (Findings / Impression) والملاحظات والتوقيع.
- **القيد المالي الموحد والديناميكي لحسابات الحاضنات لدى الكاشير (Consolidated Dynamic Folio)**:
  * **اعتماد الخيار الهجين (Hybrid)**: بدلاً من تشتيت الكاشير بتذاكر فردية لكل فحص، يتم تجميع وتوليد قيد مالي موحد وديناميكي لكل طفل خديج في تبويب مستقل `[ 👶 الخدج والحاضنات ]` في شاشة الكاشير (`/cashier`).
  * عرض إجمالي الفاتورة التراكمية (أجرة الحاضنة اليومية + التحاليل + الأشعة + الاستشارات)، وإجمالي الأمانات والمدفوعات المسددة، والرصيد الصافي المتبقي لحظياً.
  * زر فوري لاستلام دفعة تحت الحساب (أمانات / سلفة) مع إصدار وصل حراري رسمي 78mm مخصص ومفصل باسم الطفل ورقم الحاضنة والطبيب المشرف.
  * تحديث كشف الحساب التراكمي للخدج (`/nicu/{reservation}/statement`) بقسم تفصيلي لجدول الأمانات والوصولات النقدية المسددة.
- **الاختبارات وضمان الجودة والشجرة المعرفية**:
  * إنشاء حزمة اختبارات ميزة آلية `tests/Feature/NicuLabRadiologyCashierWorkflowTest.php` تغطي دورة الحياة الكاملة للطلبات والنتائج والطباعة والكاشير واجتيازها بنجاح 100% (3 اختبارات، 54 تأكيداً).
  * اجتياز حزمة اختبارات الخدج الكاملة `tests/Feature/NicuWorkflowTest.php` بنجاح 100% (29 اختباراً، 210 تأكيداً).
  * المجموع: **32 اختباراً ناجحاً (264 تأكيداً) بنجاح 100%**.
  * تحديث الشجرة المعرفية Graphify إلى 5,334 عقدة و 9,540 علاقة.

## Session log (2026-10-20 — محطة الطبيب السريرية للخدج: تفكيك المختبر والأشعة والوصفات وإضافة سجل إعطاء العلاج MAR المستقل)

### Done
- **تفكيك الطلبات السريرية إلى 3 محطات متطورة ومستقلة مطابقة لمحطة الطبيب (`resources/views/nicu/show.blade.php`)**:
  * 🧪 **محطة المختبر والتحاليل**: باقات الخدج السريعة بنقرة واحدة (دخول روتيني، يرقان وصفار، غازات دم ABG، مسح إنتان Sepsis، وظائف الكلى والأملاح)، مربع بحث فوري مع Tag Autocomplete وصينية التحاليل المختارة (Chips Tray) مع إمكانية الإفراغ بنقرة واحدة، تحديد الأولوية (Normal, Urgent, STAT 🚨)، التسعير التلقائي من جدول التحاليل، ومودال إدخال النتائج والاعتماد المالي.
  * 🩻 **محطة الأشعة والسونار والإيكو**: أزرار تصوير حديثي الولادة الشائعة (أشعة صدر نقالة بالحاضنة، سونار الدماغ واليافوخ، إيكو قلب أطفال وتفقد PDA، أشعة بطن نقالة، وتصوير كامل Babygram)، مربع بحث تفاعلي، صينية الفحوصات المختارة، تحديد الأولوية، التسعير التلقائي من جدول الأشعة، ومودال إدخال التقارير وتوصيات أخصائي الأشعة.
  * 💊 **محطة الوصفة والعلاجات الدوائية (E-Prescriptions & Clinical RX)**: محرر وصفات إلكترونية ذكي يدعم إضافة وحذف عدة أدوية ديناميكياً، والبحث الفوري في كتالوج الأدوية الشامل (3,728 صنف)، مع تحديد الجرعة (Dose)، ومسار الإعطاء (Route)، والتكرار (Frequency)، والمدة (Duration)، والتعليمات السريرية، وزر للطباعة الرسمية الفورية.
  * 🖨️ **صفحة طباعة الوصفة الطبية الرسمية (`resources/views/nicu/print/prescription.blade.php`)**: قالب طباعة مخصص بحجم A5 مع ترويسة المستشفى، بيانات الطفل، جدول الأدوية برمز ℞، وتوقيع وختم الطبيب المشرف.
- **سجل إعطاء وتطبيق العلاج التمريضي (Medication Administration Record - MAR)**:
  * إنشاء جدول قاعدة بيانات `nicu_medication_administrations` عبر مايجريشن `2026_10_20_120000_create_nicu_medication_administrations_table.php` ونموذج Eloquent `NicuMedicationAdministration.php` مع علاقات الحجز والممرض المنفذ وخصائص badges الملونة.
  * دمج النموذج والجدول الزمني بالكامل داخل **محطة التمريض (العلامات والوزن والرضاعة)**.
  * **فصل صارم ومطلق عن الصيدلية والمخزون**: هذا السجل سريري تمريضي بحت لتوثيق وقت وطريقة إعطاء الجرعة للطفل داخل الحاضنة ولا ينشئ أي طلب صيدلاني أو مالي أو حركي في المخزون.
- **الاختبارات وضمان الجودة والشجرة المعرفية**:
  * إضافة 5 اختبارات ميزة آلية شاملة تغطي طلب التحاليل، الفحوصات الشعاعية، الوصفة وطباعة RX، سجل إعطاء العلاج MAR واستقلاليته عن الصيدلية، وعرض كافة التبويبات الستة في `tests/Feature/NicuWorkflowTest.php`.
  * اجتياز كافة الاختبارات الـ 29 بنجاح 100% (210 تأكيدات assertions).
  * تحديث الشجرة المعرفية Graphify: 5,049 عقدة و 8,597 علاقة.

## Session log (2026-10-20 — البحث التفاعلي الفوري عن طبيب الاستشارة الداخلي وإخفاء التخصص الدقيق إلا في الاستشارة الخارجية)

### Done
- **مربع البحث الفوري التفاعلي عن طبيب المستشفى الداخلي (`resources/views/nicu/show.blade.php`)**:
  * استبدال القائمة المنسدلة التقليدية الطويلة بمربع إدخال وبحث حي وتفاعلي فوري؛ بمجرد كتابة اسم الطبيب أو جزء منه تظهر قائمة المقترحات المطابقة لحظياً دون أي تأخير أو استدعاء شبكي.
  * إظهار اسم الطبيب وتخصصه الدقيق في قائمة المقترحات مع زر لعرض كافة الأطباء إذا رغب المستخدم بالتصفح.
  * عند اختيار الطبيب، يتم إخفاء مربع البحث وعرض بطاقة مدمجة أنيقة باسم الطبيب وتخصصه وزر `[ ✕ تغيير ]` لسهولة التراجع والتعديل السريع.
  * دعم استعادة الطبيب المختار تلقائياً عند أخطاء التحقق (`old('doctor_id')`)، ومنع إرسال النموذج عند ترك الحقل فارغاً في الاستشارة الداخلية.
- **إخفاء حقل «التخصص الدقيق» في الاستشارة الداخلية وحصره بالخارجية فقط**:
  * إخفاء حقل التخصص الدقيق تلقائياً (`d-none`) في الاستشارة الداخلية لكون تخصص الطبيب الداخلي محفوظاً بالفعل في النظام.
  * إظهار حقل التخصص الدقيق وفرض ملئه حصراً عند اختيار "استشارة خارجية (طبيب زائر)".
  * في المتحكم (`NicuController::storeConsultation`): تعديل قاعدة التحقق إلى `nullable|required_if:consultation_type,external` وجلب تخصص الطبيب الداخلي تلقائياً من جدول الأطباء وحفظه في سجل الاستشارة.
- **الاختبارات وضمان الجودة والشجرة المعرفية**:
  * إضافة اختبار ميزة آلي مخصص `test_internal_consultation_search_box_renders_and_auto_adopts_doctor_specialty` في `tests/Feature/NicuWorkflowTest.php`.
  * اجتياز كافة اختبارات منظومة الخدج والحاضنات (24 اختباراً، 167 تأكيداً) بنجاح 100%.
  * تحديث الشجرة المعرفية Graphify: 5,139 عقدة و 8,537 علاقة عبر 1,174 ملفاً.

## Session log (2026-10-19 — تحويل اختيار الطبيب المشرف إلى قائمة بحث ذكية وفورية Select2 في استمارة حجز الحاضنات)

### Done
- **تطبيق وتفعيل القائمة البحثية الذكية (`resources/views/incubator_reservations/create.blade.php`)**:
  * تحويل حقل اختيار الطبيب الأخصائي المشرف من قائمة منسدلة عادية طويلة إلى مكون بحث فوري ذكي وتفاعلي باستخدام `Select2` وسمة `bootstrap-5`.
  * إتاحة البحث اللحظي بمجرد الكتابة سواء باسم الطبيب أو تخصصه الطبي الدقيق (مثل: أخصائي خدج وأطفال، استشاري، جراحة، إلخ).
  * إضافة تنسيقات CSS احترافية متوافقة بالكامل مع نمط وتصميم النظام تدعم اللغة العربية والاتجاه (RTL)، مع إمكانية مسح الاختيار (`allowClear: true`) ومؤشرات حالة التحميل وعدم وجود نتائج مطابقة.
  * الحفاظ التلقائي على خيار الطبيب المحدد مسبقاً أو عند إعادة إرسال النموذج بعد أخطاء التحقق (`old('doctor_id')`).
- **الاختبارات وضمان الجودة والشجرة المعرفية**:
  * إضافة اختبار ميزة آلي مخصص `test_incubator_reservation_create_page_renders_with_searchable_doctor_select` في `tests/Feature/NicuWorkflowTest.php`.
  * اجتياز كافة اختبارات منظومة الخدج والحاضنات (23 اختباراً، 157 تأكيداً) بنجاح 100%.
  * تحديث الشجرة المعرفية Graphify: 5,136 عقدة و 8,532 علاقة عبر 1,174 ملفاً.

## Session log (2026-10-19 — البحث الحي المباشر عن المرضى لحجز الحاضنات وإلغاء شرط العمر وتخصيص اسم الطفل الخديج)

### Done
- **مسار ونقطة نهاية البحث المباشر السريع (`/incubator-reservations/search-patients`)**:
  * إضافة دالة `searchPatients` في `IncubatorReservationController` للبحث اللحظي في قاعدة بيانات المرضى بالاسم أو رقم الهاتف أو الرقم الوطني/الطبي بدون أي قيود، مع إرجاع بيانات الضمان ورقم البطاقة والفئة.
  * إضافة المسار المسمى `incubator-reservations.search-patients` في `routes/web.php`.
- **إلغاء شرط العمر والتحميل البطيء (`IncubatorReservationController.php`)**:
  * إلغاء شرط `age < 1` من شاشة الحجز ودالة الحفظ `store` للسماح بتسجيل الحجز رسمياً باسم الأم أو الأب أو الطفل.
  * الاستغناء عن تحميل كل المرضى دفعة واحدة في الذاكرة والقائمة المنسدلة، مما سرع استجابة الصفحة بشكل ملحوظ.
- **تخصيص وتوليد اسم الطفل الخديج (`baby_name`)**:
  * إضافة حقل اختياري في استمارة الحجز: `[ 👶 اسم الطفل الخديج ]`.
  * في حال تركه فارغاً، يقوم النظام تلقائياً بتوليد وحفظ الاسم بصيغة `طفل [اسم الأم/المسؤول]`.
  * في حال كتابة اسم مخصص (مثل: "حيدر أحمد")، يتم اعتماده وحفظه مباشرة في السجل وبطاقة الحاضنة.
- **واجهة البحث الذكي التفاعلية (`resources/views/incubator_reservations/create.blade.php`)**:
  * إزالة التنبيه التحذيري الخاطئ "لا يوجد أطفال أقل من سنة".
  * بناء مكوّن بحث مباشر بـ `debounce` (300ms) مع عدسة ومؤشر تحميل وقائمة نتائج منسدلة أنيقة.
  * بطاقة أنيقة للمريض المختار تعرض اسم الأم/المسؤول، رقم الهاتف، ومعرف الملف، مع زر `[ ✕ تغيير المريض ]` لتسهيل التراجع والتعديل.
  * كشف الضمان الصحي تلقائياً وتفعيل خيار الضمان وتعبئة فئة الوثيقة ورقم البطاقة إن وُجدت.
- **الاختبارات وضمان الجودة والشجرة المعرفية**:
  * إضافة اختبار شامل `test_patient_live_search_and_adult_mother_reservation_with_baby_name` في `NicuWorkflowTest.php`.
  * اجتياز 22 اختباراً بنجاح 100% (152 assertions).
  * تحديث الشجرة المعرفية Graphify: 5,133 عقدة و 8,530 علاقة عبر 1,174 ملفاً.

## Session log (2026-10-19 — تبسيط وتوحيد أنواع الحاضنات الفيزيائية إلى نوعين: حاضنة وكبسولة علاج ضوئي)

### Done
- **قاعدة البيانات وميغريشن توحيد أنواع الحاضنات**:
  * إنشاء وتنفيذ ميغريشن `2026_10_19_110000_simplify_incubator_types_to_two_types.php` لتحويل السجلات القديمة ذات الأنواع الملغاة (`oxygen`, `phototherapy`) تلقائياً إلى نوع الحاضنة المعتمد (`normal`).
- **تحديث النموذج Eloquent (`Incubator.php`)**:
  * حصر الثوابت المعتمدة للأنواع في: `TYPE_NORMAL = 'normal'` و `TYPE_CAPSULE = 'capsule'`.
  * الإبقاء على ثوابت توافقية للمراجع القديمة (`TYPE_OXYGEN = 'normal'` و `TYPE_PHOTOTHERAPY = 'normal'`).
  * تحديث دوال الخصائص التلقائية:
    - `getTypeNameAttribute`: تُرجع «حاضنة» لنوع `normal`، و«كبسولة علاج ضوئي» لنوع `capsule`.
    - `getTypeColorAttribute`: تُرجع `primary` للحاضنة، و `dark` للكبسولة.
    - `getTypeIconAttribute`: تُرجع `fa-baby` للحاضنة، و `fa-capsules` للكبسولة.
- **تحديث المتحكمات والتحقق من المدخلات (`IncubatorController` و `IncubatorReservationController`)**:
  * تحديث قواعد التحقق (`validation rules`) في الإضافة والتعديل لتقتصر حصراً على: `in:normal,capsule`.
  * حصر إحصائيات الحاضنات في `IncubatorController::index` على إجمالي الحاضنات والكبسولات (`normal`, `capsule`).
  * تحديث مصفوفة أنواع الحاضنات في استمارة الحجز `IncubatorReservationController::create` لعرض الخيارين فقط.
- **تحديث الواجهات (Blade Views)**:
  * واجهة تعديل الحاضنة (`resources/views/incubators/edit.blade.php`): تقليص القائمة المنسدلة إلى «حاضنة» و«كبسولة علاج ضوئي».
  * واجهة إنشاء حاضنة جديدة (`resources/views/incubators/create.blade.php`): تقليص القائمة المنسدلة إلى الخيارين المعتمدين.
  * واجهة إدارة الحاضنات (`resources/views/incubators/index.blade.php`): تحديث التبويبات العلوية وشبكة العرض لتشمل تبويب الكل، تبويب حاضنة، وتبويب كبسولة علاج ضوئي، مع تصحيح إغلاق قسم الـ Blade.
- **الاختبارات وضمان الجودة والشجرة المعرفية**:
  * إضافة اختبار شامل `test_incubator_types_simplified_to_normal_and_capsule` في `NicuWorkflowTest.php` للتحقق من المسميات، الأيقونات، قواعد التحقق من النوع، والواجهات.
  * اجتياز 21 اختباراً بنجاح 100% (140 assertions).
  * تحديث الشجرة المعرفية Graphify: 5,129 عقدة و 8,515 علاقة عبر 1,174 ملفاً.

## Session log (2026-10-19 — تسعيرة الضمان الصحي HIA ومنظومة شرائح العمر الحملي للخدج وحسابات التحمل)

### Done
- **قاعدة البيانات وهيكل تسعير الضمان الصحي للخدج (`nicu_services` و `incubator_reservations` و `nicu_service_logs`)**:
  * إنشاء وتنفيذ ميغريشن `2026_10_19_094929_add_health_insurance_fields_to_nicu_tables.php`.
  * إضافة حقول تسعيرة الضمان (`hi_price`)، وتفعيل الضمان (`is_hi_active`)، والمسمى الرسمي المعتمد للمطالبة (`official_claim_name`)، مع الحقول غير المفعلة مستقبلاً لحصص الخدمة (`hotel_fee`, `nursing_fee`, `device_fee`).
  * ترقية جدول الحجوزات `incubator_reservations` بالحقول التأمينية: `insurance_type`, `policy_category`, `encounter_number`, `insurance_card_no`, `patient_share_amount`, `insurance_share_amount`, `coverage_summary`.
  * ترقية جدول السجلات `nicu_service_logs` بالحقول: `patient_share`, `insurance_share`, `coverage_percentage`.
  * تحديث بذور الباقات الثمانية للخدج (`NicuServicesSeeder`) بالأسعار المعتمدة للضمان والمسميات الرسمية المطابقة لملفات هيئة الضمان الصحي.
- **محرك الاحتساب المالي لشرائح العمر الحملي المعتمدة وزارياً (`NicuBillingService.php`)**:
  * بناء دالة `getInsuranceCoverageForDay` لتطبيق الشرائح الوزارية الرسمية:
    1. **الفئة A (الرعاية الاجتماعية، الشهداء)**: إعفاء كامل وتغطية 100% دون أي نسبة تحمل للمريض.
    2. **الفئة الأولى (≥ 34 أسبوعاً - حد أقصى 10 أيام)**: الأيام 1-5 تغطية 75% (المريض 25%)، الأيام 6-10 تغطية 50% (المريض 50%)، بعد 10 أيام 100% على حساب المريض.
    3. **الفئة الثانية (32 إلى أقل من 34 أسبوعاً - حد أقصى 15 يوماً)**: الأيام 1-5 تغطية 75%، الأيام 6-10 تغطية 50%، الأيام 11-15 تغطية 25%، بعد 15 يوماً 100% على المريض.
    4. **الفئة الثالثة (28 إلى أقل من 32 أسبوعاً - حد أقصى 20 يوماً)**: الأيام 1-5 تغطية 75%، الأيام 6-10 تغطية 50%، الأيام 11-15 تغطية 25%، الأيام 16-20 تغطية 10%، بعد 20 يوماً 100% على المريض.
    5. **الفئة الرابعة (< 28 أسبوعاً - شديد الخداجة استثناء وزاري)**: الأيام 1-5 تغطية 75%، الأيام 6-10 تغطية 50%، الأيام 11-15 تغطية 25%، الأيام 16 فما فوق تغطية مستمرة 10% (تحمل المريض 90%).
    6. **الرقود الاعتيادي في ردهة الأطفال (Pediatric Ward)**: تغطية 90% ضمان و 10% نسبة تحمل المريض.
  * تحديث `calculateStayDetails` و `switchService` لاحتساب مبالغ حصة الضمان وحصة المريض التراكمية، ومطابقة تسعيرة الضمان الفعالة تلقائياً.
- **شاشة إدارة وتسعير خدمات الخدج (`/nicu/services`)**:
  * عرض عمودي أجرة الكاش اليومية وتسعيرة الضمان (HIA) المعتمدة مع اسم المطالبة وشارات التفعيل.
  * إضافة بطاقة توجيهية وإرشادية بارزة تعرض جدول الشرائح الوزارية للعمر الحملي وملاحظات الاستثناءات.
  * تحديث نوافذ التعديل والإضافة لحفظ سعر الضمان واسم المطالبة ومفاتيح التفعيل.
- **استمارة الحجز الذكية التكيفية (`/incubator-reservations/create`)**:
  * زر تحويل فوري أعلى الاستمارة: `[ 💵 كاش / خاص ]` أو `[ 🛡️ ضمان صحي (HIA) ]`.
  * عند اختيار الضمان: تفتح لوحة بيانات الوثيقة (الفئة E, H, A, D، رقم البطاقة، رقم المراجعة).
  * محدد ذكي للعمر الحملي بالأسابيع (24 إلى 40+ أسبوعاً).
  * صندوق معاينة تفاعلي لحظي (Live Tier Preview) يشرح شريحة التغطية ونسب التحمل طبقاً للعمر الحملي المختار.
  * تحديث أسعار قائمة الباقات تلقائياً إلى تسعيرات الضمان المعتمدة بمجرد التبديل دون إعادة تحميل الصفحة.
- **كشف الحساب التراكمي الشفاف (`/nicu/{reservation}/statement`)**:
  * شريط توثيق وثيقة الضمان ورقم البطاقة والمراجعة والعمر الحملي.
  * إدراج أعمدة مساهمة الضمان وتحمل المريض (كاش) في جدول الباقات السريرية.
  * تفصيل الخلاصة المالية النهائية: إجمالي المطالبة، مطالبة هيئة الضمان، والمستحق نقداً في الصندوق.
- **الاختبارات وضمان الجودة والشجرة المعرفية**:
  * إضافة 3 اختبارات ميزة شاملة في `NicuWorkflowTest.php` واجتياز 20 اختباراً بنجاح 100% (125 assertions).
  * تحديث الشجرة المعرفية Graphify: 5,123 عقدة و 8,508 علاقة عبر 1,173 ملفاً.

## Session log (2026-10-14 — تصفير سجلات تبديل الحجز 2 وتوفير ميزة تعديل والتراجع عن التبديل السريري)

### Done
- **تصفير سجلات تبديل الحجز رقم 2 (`احمد علي كاظم`)**:
  * حذف السجلات التجريبية وتعيين سجل أولي نظيف بنوع باقة واحدة (`حاضنة فقط + O2`) لتمكين المستخدم من اختبار عملية التبديل بحرية من البداية.
- **تعديل السجل في نفس اليوم الفندقي مباشرة (In-Place Edit/Update)**:
  * تعديل منطق `NicuBillingService::switchService` لكي يقوم بتحديث السجل المفتوح مباشرة بدلاً من إغلاقه وفتح سطر جديد مكرر لنفس اليوم، مما يمنع تراكم الأسطر المربكة في الجدول.
- **إضافة أزرار وإجراءات التعديل والتراجع (Edit & Undo Actions)**:
  * إضافة المسارات والكونترولر: `nicu.service-logs.update` لتعديل الباقة أو السبب مباشرة، و `nicu.service-logs.destroy` للتراجع عن التبديل وحذفه واستعادة الباقة السابقة.
  * تصميم Modal لتعديل الباقة الحالية مباشرة، وتحديث جدول السجل الزمني (Timeline) في `nicu/show.blade.php` ليعرض أعمدة الأيام المحتسبة والتكلفة مع أزرار الإجراءات (تعديل ✏️ / تراجع 🗑️).
- **الاختبارات وضمان الجودة**:
  * إضافة اختبار `test_admin_can_update_and_undo_service_log` في `NicuWorkflowTest.php` واجتياز 17 اختباراً بنجاح 100% (79 assertions).
  * تحديث الشجرة المعرفية Graphify: 4,707 عقدة و 7,543 علاقة.

### Done
- **معالجة مشاكل التبديل المتكرر للباقات وتفادي مضاعفة اليوم الفندقي (`NicuBillingService.php`)**:
  * تطبيق مهلة التراجع الفوري (15 دقيقة): عند تبديل الخدمة خلال 15 دقيقة من التبديل السابق يُعد ذلك تصحيحاً فورياً ويتم تحديث السجل المفتوح مباشرة دون إنشاء سجل جديد.
  * دمج التبديل داخل نفس اليوم الفندقي السريري (من 12:00 ظهراً إلى 12:00 ظهراً لليوم التالي): تصفير السجل السابق (`days_count = 0`, `total_cost = 0`, `notes = 'دمج نفس اليوم الفندقي'`) واحتساب اليوم الفندقي المشترك مرة واحدة بأعلى تسعيرة بين الخدمتين (`max(prev_fee, new_fee)`).
- **بناء واجهة سجل وإضبارات الخدج وحجوزات الحاضنات (`/nicu/reservations`)**:
  * إضافة مسار `nicu.reservations.index` وكونترولر `NicuController::reservationsIndex`.
  * تصميم واجهة عصرية متكاملة (`resources/views/nicu/reservations/index.blade.php`) تضم كروت مؤشرات أداء حيوية (KPIs) للإضبارات والحالات النشطة والمخرجة والانتظار.
  * شريط بحث ذكي وفلاتر متعددة (بحث بالاسم والهاتف ورقم الحاضنة، فلترة بحالة الإقامة، باقة الخدمة، ونطاق التواريخ).
  * جدول سريري غني يعرض مدة الإقامة بالساعات والأيام الفندقية، وتتبع تغيرات وزن الطفل والنمو (+/- جم)، والتكلفة التراكمية، مع قائمة إجراءات سريعة متكاملة (ملف الرعاية، كشف الحساب التراكمي الشفاف، طباعة كارت الحاضنة وأسورة المعصم، ومشاركة رابط كاميرا الاطمئنان للأهل عبر واتساب).
  * ربط الواجهة بالقائمة الجانبية للنظام ولوحة الحاضنات اللحظية.
- **الاختبارات وضمان الجودة والشجرة المعرفية**:
  * إضافة اختباري ميزة جديدين للتبديل السريع وسجل الإضبارات في `NicuWorkflowTest.php` واجتياز 16 اختباراً بنجاح 100% (72 assertions).
  * تحديث الشجرة المعرفية Graphify: 4,707 عقدة و 7,530 علاقة عبر 1,129 ملفاً.

## Session log (2026-10-13 — واجهة إدارة وتسعير باقات وخدمات الخدج NICU Services Pricing)

### Done
- **شاشة إدارة وتسعير باقات الخدج (`NicuServiceController.php` و `resources/views/nicu/services/index.blade.php`)**:
  * بناء كونترولر وواجهة كاملة لعرض الباقات الثمانية المعرفة لخدمات الخدج مع السعر اليومي لكل باقة وحالة التفعيل وعدد الحجوزات السابقة.
  * إتاحة تعديل السعر اليومي الفندقي والاسم والوصف بضغطة زر عبر Modal تعديل فوري.
  * إتاحة إضافة باقات خدمات جديدة بالمسمى والرمز والأجرة اليومية.
  * مفتاح تفعيل/تعطيل لحظي عبر AJAX (Toggle Switch) دون إعادة تحميل الصفحة.
- **التوجيه والقوائم والشريط الجانبي**:
  * إضافة المسارات: `nicu.services.index`, `nicu.services.store`, `nicu.services.update`, `nicu.services.toggle`.
  * إضافة رابط «تسعير وباقات خدمات الخدج» إلى القائمة الجانبية أسفل قسم الخدج.
  * إضافة زر وصول سريع في لوحة الحاضنات اللحظية (`/nicu`) وشاشة إدارة الحاضنات (`/incubators`).
- **الاختبارات الآلية والمعرفية**:
  * إضافة 3 اختبارات جديدة في `NicuWorkflowTest.php` واجتياز 14 اختباراً بنجاح 100% (62 assertions).
  * تحديث الشجرة المعرفية Graphify: 4,698 عقدة و 7,511 علاقة عبر 1,126 ملفاً.

## Session log (2026-10-13 — تشغيل الهجرات المعلقة وإصلاح حقل last_activity_at ومحرك جداول الخدج InnoDB)

### Done
- **تنفيذ الهجرات المعلقة (Database Migrations)**:
  * تشغيل كافة الهجرات المعلقة وإضافة حقول `last_activity_at` و `last_login_ip` و `last_logout_at` لجدول `users`.
  * حل خطأ `SQLSTATE[HY000]: General error: 1824 Failed to open the referenced table 'nicu_services'` عبر تثبيت محرك قاعدة البيانات الافتراضي على `InnoDB` في `config/database.php` وإسناد `engine = InnoDB` لجداول الخدج.
  * تفريغ كاش الإعدادات وتحديث كاش الصلاحيات.
  * اجتياز جميع اختبارات `UserActivityLogTest` و `NicuWorkflowTest` بنجاح 100%.

## Session log (2026-10-13 — منظومة الخدج والحاضنات NICU المتكاملة)

### Done
- **قاعدة البيانات وجداول الخدج (`database/migrations` و `app/Models/`)**:
  * إنشاء ميغريشن `2026_10_13_110000_create_nicu_system_tables.php` وبناء جداول: `nicu_services`, `nicu_service_logs`, `nicu_vitals`, `nicu_consultations`, `nicu_medical_orders` مع ترقية جداول `incubators` و `incubator_reservations`.
  * إنشاء النماذج Eloquent: `NicuService`, `NicuServiceLog`, `NicuVital`, `NicuConsultation`, `NicuMedicalOrder` وتحديث `Incubator` و `IncubatorReservation`.
  * بذر باقات خدمات الخدج الـ 8 المحددة مع التسعيرات اليومية المعتمدة (`NicuServicesSeeder`).
- **محرك الاحتساب المالي والتوقيت الفندقي وسماحية الـ 6 ساعات (`NicuBillingService.php`)**:
  * ضبط موعد القطع اليومي الفندقي الساعة 12:00 ظهراً بتوقيت العراق (`Asia/Baghdad`).
  * تطبيق سماحية 6 ساعات قبل الظهر (الدخول بين 06:00 و 12:00 يدمج في اليوم الأول حتى 12:00 ظهراً لليوم التالي).
  * تطبيق سماحية 6 ساعات بعد الظهر (الخروج بين 12:00 و 18:00 معفى من احتساب يوم إضافي كامل).
  * دعم التحويل والتبديل بين الخدمات اليومية (مثلاً فنتليتر إلى أكسجين) مع احتساب دقيق لكل فترة.
- **محطة التمريض والمقيم ومتابعة الطفل (`NicuController.php` و `resources/views/nicu/`)**:
  * لوحة القيادة الحية للخدج (`/nicu`): إحصائيات فورية، بطاقات الحاضنات والكبسولات المشغولة والمتاحة مع شارات الأجهزة وعداد الإقامة بالساعات والأيام وفارق الوزن.
  * محطة المريض التخصصية (`/nicu/{reservation}`): تبويبات تفاعلية لمتابعة الطفل (باقة الخدمة وتبديلها، العلامات الحيوية والتغذية ونمو الوزن، طلبات الاستشارة الطبية الداخلية والخارجية، الطلبات الطبية للمختبر والأشعة والصيدلية).
  * طباعة أساور معصم الخديج مع الباركود (`wristband`) وبطاقة الحاضنة (`crib_card`).
  * كشف حساب الإقامة والرعاية التفصيلي للترحيل والصندوق (`statement`).
- **كاميرا الاطمئنان للأهل (Live Baby Cam)**:
  * رابط مؤمّن برمز مشفر (`/baby-cam/{token}`) يتيح لولي الأمر مشاهدة طفله مجاناً لمدة 60 ثانية مع عداد تنازلي ورسالة تطمين ورعاية دافئة.
- **إدارة دورة حياة الحاضنة والتعقيم**:
  * عند تسجيل خروج الطفل تتحول الحاضنة تلقائياً إلى حالة «قيد التعقيم والتطهير» (`sterilizing`) حتى يتم تأكيد تطهيرها بنقرة زر لتعود «متاحة» (`available`).
- **الاختبارات الآلية والمعرفية**:
  * إنشاء واجتياز اختبارات الميزة الشاملة `NicuWorkflowTest.php` بنجاح 100% (11 passed, 50 assertions).
  * اجتياز اختبارات مصفوفة الصلاحيات بنجاح 100% (11 passed, 176 assertions).
  * تحديث الشجرة المعرفية (Graphify): 4,346 عقدة و 6,680 علاقة.

## Session log (2026-09-29 — إصلاح خطأ مسار البحث في جراحة العيون)

### Done
- **إصلاح مسار البحث (Route Fix)**: تم حل مشكلة `RouteNotFoundException` التي كانت تظهر عند البحث عن مريض في واجهة إضافة جراحة عيون جديدة (`resources/views/eye/surgeries/eye_surgery_create.blade.php`).
- **تحديث معاملات البحث (Search Parameters)**: تم تصحيح اسم المسار إلى `route('eye.reception.searchPatients')` وتحديث معامل البحث المرسل إلى `term=` ليتطابق مع `EyeReceptionController`.
- **معالجة مخرجات JSON (Data Mapping)**: تم تغيير المتغير المستخدم في الواجهة من `p.mrn` إلى `p.national_id` لضمان عرض بيانات المريض بالشكل الصحيح بدلاً من `undefined`.
- **تأكيد الجودة**: اجتياز جميع اختبارات `EyeCenterWorkflowTest` بنجاح 100%.

## Session log (2026-09-28 — عزل عرض المستخدمين في شاشة صلاحيات العيون)

### Done
- **عزل المستخدمين**: تم تعديل `EyePermissionsController` ليقوم بعرض المستخدمين الذين يمتلكون صلاحيات العيون مسبقاً فقط، أو أطباء العيون، بدلاً من عرض كافة كادر المستشفى.
- **منح الصلاحيات لموظفي المستشفى**: في حال رغبة الإدارة بمنح صلاحيات العيون لموظف جديد من المستشفى (مثل إضافة كاشير عام للعمل في مركز العيون)، يمكن ببساطة البحث عن اسمه أو إيميله في شريط البحث وستظهر بياناته لمنحه الصلاحية.
- **التحديث المعرفي**: تم تحديث Graphify ورفع الكود على فرع `hr`.
## Session log (2026-09-28 — إنشاء واجهة إدارة صلاحيات مركز العيون المستقلة)

### Done
- **إدارة صلاحيات مستخدمي مركز العيون (`EyePermissionsController.php` و `permissions/index.blade.php`)**:
  * تم بناء واجهة مخصصة بالكامل لإدارة صلاحيات مركز العيون وعزلها عن قائمة صلاحيات المستشفى العامة.
  * تصميم جدول ذكي يعرض كافة الكوادر الطبية والإدارية (باستثناء المرضى) مع إظهار عدد ونوع صلاحيات العيون الممنوحة لكل مستخدم.
  * تم إضافة مفاتيح تفعيل (Toggle Switches) لتسهيل منح أو سحب الصلاحيات الفرعية السبع (استعلامات العيون، كاشير العيون، مخزن العيون، الفحوصات التشخيصية، صالة عمليات الفاكو، فحص العيادات).
- **التوجيه والقوائم**:
  * تمت إضافة مسارات `eye.permissions.index` و `update` إلى ملف `routes/eye.php`.
  * إدراج رابط "صلاحيات ومستخدمي العيون" أسفل قائمة العيون في الشريط الجانبي `app.blade.php`.
- **الاختبارات الآلية والمعرفية**:
  * تم إنشاء وتخطي اختبار `test_eye_permissions_management` بنجاح وتحديث صلاحيات المستخدمين بدقة.
  * تحديث المخطط المعرفي (Graphify) ورفع التعديلات على فرع `hr`.
## Session log (2026-09-28 — عزل صالة الانتظار العامة لمركز العيون)

### Done
- **عزل وتخصيص شاشة صالة الانتظار العامة لمركز العيون (`EyeQueueController.php` و `all-clinics-display.blade.php`)**:
  * تم بناء كونترولر جديد `EyeQueueController` مستقل تماماً مخصص لجلب بيانات وحالة الطابور اللحظية (`allClinicsData`) لأطباء واستشاريي العيون فقط دون الاختلاط بالعيادات الاستشارية الأخرى للمستشفى.
  * تم تصميم واجهة عرض الطابور المباشرة وتخصيص عناوينها إلى "صالة انتظار مركز وجراحة العيون" لتعمل على الشاشات الكبيرة (TV) داخل المركز.
  * تم تحديث مسارات `routes/eye.php` بروابط مخصصة (`eye.queue.all-clinics.display`).
  * تم تصحيح زر "شاشة الصالة العامة" في صفحة توفر أطباء العيون ليوجه المستخدم إلى الشاشة الجديدة المعزولة.
- **الاختبارات الآلية والمعرفية**:
  * تمت إضافة اختبار جديد `test_eye_queue_waiting_hall_screens` لضمان عمل شاشات طابور العيون بنجاح 100%.
  * تحديث المخطط المعرفي (Graphify) ورفع التعديلات على فرع `hr`.
## Session log (2026-09-28 — واجهة واستمارة إضافة وتعديل أطباء العيون المخصصة لمركز العيون)

### Done
- **إضافة طبيب عيون تخصصي جديد (`create_doctor.blade.php` و `storeDoctor`)**:
  * تصميم واجهة مخصصة بالكامل لأطباء واستشاريي العيون داخل `resources/views/eye/availability/create_doctor.blade.php`.
  * حصر وتثبيت العيادة تلقائياً في «مركز وجراحة العيون التخصصي» دون اختلاط مع أقسام المستشفى الأخرى.
  * قائمة ذكية بالتخصصات العينية الدقيقة الثمانية (شبكية، ماء أبيض/فاكو، قرنية، جلوكوما، أطفال وحول، تجميل جفون، بصريات، عيون عامة) مع إمكانية كتابة تخصص مخصص.
  * أجور الكشفية كاش، تسعيرة الضمان الصحي (10% نسبة المريض)، وتسعيرة قوى الأمن الداخلي.
  * تحديد أيام وساعات دوام العيادة الأسبوعية مع أزرار التحديد السريع وتفعيل التوفر الفوري لليوم.
- **تعديل بيانات ودوام الطبيب من شاشة التوفر (`edit_doctor.blade.php` و `updateDoctorSettings`)**:
  * زر تعديل دوام وأجور الطبيب ⚙️ بجانب كل طبيب في جدول دليل أطباء العيون بشاشة التوفر.
  * استمارة تعديل مخصصة لتحديث أيام وساعات دوام الطبيب والتخصص وأجور الكشفية وتنشيط الحساب.
- **الاختبارات الآلية والمعرفية**:
  * إضافة اختباري `test_create_and_store_eye_specialized_doctor` و `test_edit_and_update_eye_doctor_settings` واجتياز 13 اختباراً بنجاح 100% (78 assertions).
  * تحديث شجرة العلاقات البرمجية والمعرفية Graphify: 3,420 عقدة و 5,228 علاقة عبر 888 ملفاً.

## Session log (2026-09-28 — منظومة توفر أطباء واستشاريي العيون، إدارة طابور العيادة، وطباعة تذاكر الحجز الحرارية)

### Done
- **توفر أطباء واستشاريي العيون (`EyeDoctorAvailabilityController.php` و `resources/views/eye/availability/index.blade.php`)**:
  * تكرار ومطابقة واجهة توفر الأطباء الاستشاريين (`consultant-availability.index`) المعتمدة في النظام وتخصيصها بالكامل لمركز العيون.
  * تبويبات أيام الأسبوع السبعة (السبت إلى الجمعة) مع الفلترة التلقائية لجدول أطباء العيون.
  * أزرار التحكم السريع: تفعيل الكل / إلغاء الكل للتوفر اليومي، رابط شاشة الصالة العامة، وحجز موعد عيون جديد.
  * جدول المواعيد وطابور العيون المباشر لليوم مع إجراءات فورية: استدعاء ومناداة المريض مع نطق صوتي عربي متقدم (TTS)، إدخال للعيادة 🩺، وتوثيق توسيع الحدقة 💧.
  * دليل أطباء العيون المفصل بحسب التخصص الدقيق (شبكية، ماء أبيض/فاكو، قرنية، جلوكوما، أطفال وحول) مع تبديل حالة التوفر بضغطة زر.
- **تذاكر وكروت حجز العيون الحرارية (`EyeReceptionController.php` و `eye_reception_ticket_print.blade.php`)**:
... [truncated for diff preview]