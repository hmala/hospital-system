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

## Session log (2026-09-03 — واجهة خدمات الطوارئ، الصلاحيات، وإدارة العيادات)

### Done
- **نظام إدارة خدمات وأسعار الطوارئ (`emergency-services`)**:
  * إضافة كونترولر `EmergencyServiceController` مع عمليات الإضافة والتعديل والتعطيل/التفعيل والحذف مع حماية العلاقات.
  * تصميم واجهة `resources/views/emergency-services/index.blade.php` بإحصائيات وبحث ومودلات خارج الجدول لتفادي تظليل `backdrop`.
  * إضافة الرابط في القائمة الجانبية تحت قسم الطوارئ.
- **إدارة وتكامل الصلاحيات (`manage emergency services` و `admin-hsop`)**:
  * إضافة ميجريشن الصلاحية `2026_09_03_100000_add_manage_emergency_services_permission.php`.
  * ربط الصلاحية في شاشة تعديل الأدوار (`roles/edit`) تحت تصنيف الطوارئ.
  * حماية الكود من أخطاء `PermissionDoesNotExist` عبر التحقق الآمن وتمرير كائنات الـ Models ومسح الكاش فورياً.
  * اعتماد دور مدير المستشفى **`admin-hsop`** و **`hospital_admin`** في `Gate::before` و `User::isAdmin()` والكونترولرات لتجاوز حظر 403.
- **واجهات إدارة العيادات (`departments`)**:
  * إنشاء واجهتي التعديل `departments/edit.blade.php` والعرض `departments/show.blade.php`.
  * تصحيح مسارات العيادات وإضافة الاسم البديل `departments.index` و `departments.admin`.
- **تحديث المخطط المعرفي للمشروع (`graphify`)**:
  * تشغيل `graphify update .` وتحديث شبكة الـ AST والتقارير بنسبة 100%.

### Next Steps / Pending
- المتابعة مع المستخدم لأي تحسينات إضافية على صلاحيات الأدوار أو العيادات.

### Issues known
- لا يوجد حالياً.

## Domain guidance
- This repository contains many Arabic-language docs and comments; be careful not to lose or mistranslate them.
- Key domain areas include radiology, cashier/payment workflows, scanner integration, and surgery stations.
- When making changes that affect UI or workflow state, verify related docs under `docs/` and root markdown files.
