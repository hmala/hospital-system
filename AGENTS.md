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

## Session log (2026-09-02 — فواتير المشتريات، جدول العمليات، أجور الغرف، وحسابات الأطباء)

### Done
- **نظام تعديل وحذف فواتير المشتريات ومزامنة المخزون (`purchases`)**:
  * إضافة مسارات وشاشة تعديل فواتير المشتريات بالكامل (`purchases/{purchase}/edit`).
  * دعم تعديل المورد، رقم الفاتورة الورقية، الكميات، الأسعار، وتواريخ الانتهاء للمواد.
  * إمكانية إضافة مواد جديدة داخل الفاتورة أو حذف مواد منها.
  * **المزامنة التلقائية للمخزون**: تحديث `StockBatch` و `StockMovement` للوجبات المخزنية تلقائياً بدون أي تضارب.
  * إضافة زر الحذف (`destroy`) مع حماية برمجية لمنع حذف الفواتير التي تم صرف أو نقل موادها.
  * تحديث واجهة جدول المشتريات (`purchases/index`) وصفحة التفاصيل (`purchases/show`) بأزرار (عرض، تعديل، حذف، طباعة باركود).
- **إعادة تصميم واجهة جدول العمليات الجراحية (`surgeries/index`)**:
  * توسيع مساحات الخلايا وهوامش الجدول (`padding` مريح) ودمج التاريخ والوقت بوضوح.
  * استبدال القوائم المنسدلة المقصوصة بأزرار إجراءات مباشرة وظاهرة دائماً (تفاصيل 👁️، تعديل ✏️، طباعة 🖨️، حذف 🗑️).
  * أوسمة حديثة وملونة للحالات والغرف الفندقية (عادية، VIP، VVIP).
  * توسيع صلاحية الحذف والتعديل لتشمل موظفي الاستعلامات (`inquiry_staff`, `receptionist`).
- **نظام حساب إقامة الغرف الفندقي (`Hotel-Style Room Stay Calculation`)**:
  * دوال `Surgery::calculateStayDetails()` و `Room::getInitialFeeForType()`.
  * احتساب الليلة الأولى من وقت الدخول حتى الساعة 12:00 ظهراً لليوم التالي بتوقيت بغداد، ثم إضافة رسم الليلة عن كل يوم إضافي.
  * ربط الرسوم تلقائياً مع نموذج دفع الكاشير (`cashier/surgeries/payment-form`).
- **توليد بيانات الأطباء الخارجيين تلقائياً (`DoctorController`)**:
  * توليد الإيميل تلقائياً بنمط متسلسل (`ext101@hospital.com`, `ext102@hospital.com`).
  * توحيد كلمة المرور الافتراضية (`Doctor@123`).
  * تحسين قوائم Select2 الطويلة بتحديد الارتفاع الأقصى (`max-height: 200px`) وشريط تمرير أنيق.
- **تحديث المخطط المعرفي للمشروع (`graphify`)**:
  * تشغيل `graphify update .` وتحديث شبكة الـ AST والتقارير بنسبة 100%.

### Next Steps / Pending
- المتابعة مع المستخدم لأي تحسينات إضافية على إدارة المخزون أو التقارير المالية.

### Issues known
- لا يوجد حالياً.

## Domain guidance
- This repository contains many Arabic-language docs and comments; be careful not to lose or mistranslate them.
- Key domain areas include radiology, cashier/payment workflows, scanner integration, and surgery stations.
- When making changes that affect UI or workflow state, verify related docs under `docs/` and root markdown files.
