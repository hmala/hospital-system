# 📋 الدليل الشامل لملفات نظام الضمان الصحي (National Health Insurance & MOI)

> **هذا الملف يحتوي على قائمة بجميع الملفات التي تم إنشاؤها أو تعديلها لإضافة وتفعيل نظام الضمان الصحي وهيئة الضمان ووزارة الداخلية ونسب التحمل (Co-payment) وتحديثات الكاشير والوصولات.**
> يمكنك الاعتماد على هذه القائمة لرفع التعديلات فقط إلى السيرفر دون الحاجة لرفع كامل المشروع.

---

## 📂 1. ملفات المعمارية والموديلات (Models & Traits)
تُرفع إلى المسار `app/`:

| الملف | المسار | الوصف |
|---|---|---|
| `HasInsurancePricing.php` | `app/Traits/HasInsurancePricing.php` | منطق الحسابات المحاسبية والتسعير التفاضلي للضمان ونسبة التحمل |
| `HealthInsuranceCategory.php` | `app/Models/HealthInsuranceCategory.php` | موديل فئات الضمان الـ 9 ونسب الاستقطاع بحسب الخدمة |
| `Patient.php` | `app/Models/Patient.php` | ربط المريض بالضمان وفئة الضمان وحساب نسبة التحمل |
| `Payment.php` | `app/Models/Payment.php` | تسجيل اللقطة المالية وتجميد حصة المريض وحصة الضمان |
| `Appointment.php` | `app/Models/Appointment.php` | دعم نوع الضمان عند الحجز (نقدي / ضمان) |
| `MedicalRequest.php` | `app/Models/MedicalRequest.php` | دعم نوع الضمان لطلبات المختبر والأشعة |
| `Surgery.php` | `app/Models/Surgery.php` | دعم نوع الضمان للعمليات الجراحية |
| `LabTest.php` | `app/Models/LabTest.php` | تسعير التحاليل بأسعار الضمان والداخلية |
| `RadiologyType.php` | `app/Models/RadiologyType.php` | تسعير الأشعة والسونار بأسعار الضمان |
| `EmergencyService.php` | `app/Models/EmergencyService.php` | تسعير خدمات الطوارئ للضمان |
| `Doctor.php` | `app/Models/Doctor.php` | تسعير كشفية الاستشارية للضمان |
| `Department.php` | `app/Models/Department.php` | تسعير كشفية الأقسام للضمان |

---

## 🎮 2. ملفات المتحكمات (Controllers)
تُرفع إلى المسار `app/Http/Controllers/`:

| الملف | المسار | الوصف |
|---|---|---|
| `HealthInsuranceCategoryController.php` | `app/Http/Controllers/HealthInsuranceCategoryController.php` | إدارة وتعديل نسب فئات الضمان وتفعيل/تعطيل الفئة |
| `CashierController.php` | `app/Http/Controllers/CashierController.php` | استلام الدفع وتجميد النسب وحساب الحصص للعمليات والمواعيد والطلبات والطوارئ |
| `PatientController.php` | `app/Http/Controllers/PatientController.php` | إضافة وتعديل بيانات الضمان وفئة المريض ورقم البطاقة |
| `LabTestController.php` | `app/Http/Controllers/LabTestController.php` | إدارة أسعار الضمان والداخلية للتحاليل |
| `RadiologyTypeController.php` | `app/Http/Controllers/RadiologyTypeController.php` | إدارة أسعار الضمان للخدمات الإشعاعية |
| `EmergencyServiceController.php` | `app/Http/Controllers/EmergencyServiceController.php` | إدارة أسعار الضمان لخدمات الطوارئ |
| `DoctorController.php` | `app/Http/Controllers/DoctorController.php` | إدارة أسعار كشفية الأطباء للضمان |
| `DepartmentController.php` | `app/Http/Controllers/DepartmentController.php` | إدارة أسعار كشفية الأقسام للضمان |

---

## 🖥️ 3. ملفات الواجهات (Blade Views)
تُرفع إلى المسار `resources/views/`:

### أ. الكاشير والوصولات والتقارير:
- `resources/views/cashier/payment-form.blade.php` (دفع الاستشاريات وحساب الضمان)
- `resources/views/cashier/request-payment-form.blade.php` (دفع التحاليل والأشعة وحساب الضمان)
- `resources/views/cashier/surgeries/payment-form.blade.php` (دفع العمليات الجراحية وحساب الضمان والنقدي)
- `resources/views/cashier/receipt-print.blade.php` (وصل الطباعة الحراري الموحد 80mm)
- `resources/views/cashier/receipt.blade.php` (عرض تفاصيل الوصل وتفصيل الحصص)
- `resources/views/cashier/report.blade.php` (تقرير الكاشير ومطالبات الضمان)

### ب. فئات الضمان وإدارة المرضى:
- `resources/views/health-insurance-categories/index.blade.php` (لوحة تحكم وتعديل فئات ونسب الضمان)
- `resources/views/patients/create.blade.php` (اختيار فئة ونوع الضمان عند التسجيل)
- `resources/views/patients/edit.blade.php` (تعديل بيانات وفئة الضمان)
- `resources/views/patients/show.blade.php` (عرض بيانات الضمان في بروفايل المريض)

### جـ. تسعير الخدمات الطبية:
- `resources/views/lab-tests/create.blade.php`
- `resources/views/lab-tests/edit.blade.php`
- `resources/views/radiology/types/create.blade.php`
- `resources/views/radiology/types/edit.blade.php`
- `resources/views/emergency_services/create.blade.php`
- `resources/views/emergency_services/edit.blade.php`
- `resources/views/doctors/create.blade.php`
- `resources/views/doctors/edit.blade.php`
- `resources/views/departments/create.blade.php`
- `resources/views/departments/edit.blade.php`

### د. القوالب الرئيسية والتنقل:
- `resources/views/layouts/app.blade.php` (إضافة رابط فئات الضمان الصحي في القائمة الجانبية)

---

## 🗄️ 4. ملفات قاعدة البيانات (Migrations & Seeders)
تُرفع إلى `database/`:

- `database/migrations/2026_09_13_084500_add_insurance_pricing_to_medical_services_tables.php`
- `database/migrations/2026_09_13_084600_add_insurance_fields_to_patients_table.php`
- `database/migrations/2026_09_13_084700_add_insurance_and_financial_snapshot_to_payments_table.php`
- `database/migrations/2026_09_14_130000_create_health_insurance_categories_table.php`
- `database/migrations/2026_09_15_094054_make_copay_percentage_nullable_in_payments_table.php`
- `database/migrations/2026_09_16_090500_update_payment_status_in_surgeries_table.php`
- `database/seeders/HealthInsuranceCategorySeeder.php`

---

## 🛣️ 5. ملفات التوجيه (Routes)
- `routes/web.php`

---

## ⚙️ 6. الأوامر المطلوب تنفيذها على السيرفر بعد رفع الملفات

بعد رفع الملفات المذكورة أعلاه بنفس هيكل المجلدات، قم بتنفيذ الأوامر التالية في مجلد المشروع على السيرفر:

```bash
# 1. تحديث جداول قاعدة البيانات
php artisan migrate --force

# 2. ملء جدول الفئات الرسمية للضمان الصحي (الفئات التسع A - I)
php artisan db:seed --class=HealthInsuranceCategorySeeder --force

# 3. مسح وتحديث كاش النظام والواجهات
php artisan optimize:clear
```
```
