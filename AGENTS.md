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
