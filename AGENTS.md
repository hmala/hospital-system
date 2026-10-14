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
