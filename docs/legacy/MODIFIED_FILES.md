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

---

# 💊 الدليل الشامل لملفات منظومة الصيدلية ونقطة البيع (Pharmacy POS & FEFO System)

> **هذا القسم يحتوي على جميع الملفات التي تم إنشاؤها وتعديلها لتنفيذ منظومة الصيدلية (الخطوات 1 إلى 4): دليل الأدوية، الخدمات، إدارة الوجبات والصلاحيات FEFO، ونقطة البيع السريعة POS.**

## 📂 1. ملفات المعمارية والموديلات (Models & Traits)
تُرفع إلى المسار `app/`:

| الملف | المسار | الوصف |
|---|---|---|
| `Medicine.php` | `app/Models/Medicine.php` | موديل الدواء، الباركود، الوحدات (باكيت/شريط)، التسعير، والبدائل |
| `MedicineAlternative.php` | `app/Models/MedicineAlternative.php` | موديل ربط الأدوية البديلة ثنائية الاتجاه |
| `MedicineBatch.php` | `app/Models/MedicineBatch.php` | موديل وجبات الأدوية، تاريخ الصلاحية، العزل، وخوارزمية فك الباكيتات |
| `PharmacyService.php` | `app/Models/PharmacyService.php` | موديل الخدمات الصيدلانية السريرية |
| `PharmacySale.php` | `app/Models/PharmacySale.php` | موديل فواتير مبيعات الصيدلية النقدية والضمان والتعليق |
| `PharmacySaleItem.php` | `app/Models/PharmacySaleItem.php` | موديل تفاصيل بنود الفاتورة وحساب الوحدات والربح |
| `HasInsurancePricing.php` | `app/Traits/HasInsurancePricing.php` | دعم مسميات الضمان الصحي ووزارة الداخلية للأدوية |

---

## 🎮 2. ملفات المتحكمات (Controllers)
تُرفع إلى المسار `app/Http/Controllers/Pharmacy/`:

| الملف | المسار | الوصف |
|---|---|---|
| `MedicineController.php` | `app/Http/Controllers/Pharmacy/MedicineController.php` | إدارة دليل الأدوية، البحث، البدائل، واستيراد Excel/CSV |
| `PharmacyServiceController.php` | `app/Http/Controllers/Pharmacy/PharmacyServiceController.php` | إدارة الخدمات الصيدلانية وتفعيلها |
| `MedicineBatchController.php` | `app/Http/Controllers/Pharmacy/MedicineBatchController.php` | إدارة وجبات الأدوية، ترتيب FEFO، تتبع المخزون المعرض للتلف، والعزل |
| `PharmacyPosController.php` | `app/Http/Controllers/Pharmacy/PharmacyPosController.php` | نقطة البيع السريعة POS، الفحص بالباركود، التبديل الذكي، تعليق الفواتير، وطباعة الوصولات |

---

## 🖥️ 3. ملفات الواجهات (Blade Views)
تُرفع إلى المسار `resources/views/pharmacy/`:

### أ. دليل الأدوية والخدمات:
- `resources/views/pharmacy/medicines/index.blade.php` (قائمة الأدوية والبحث السريع)
- `resources/views/pharmacy/medicines/create.blade.php` (إضافة دواء مع البدائل والتسعير)
- `resources/views/pharmacy/medicines/edit.blade.php` (تعديل بيانات الدواء والبدائل)
- `resources/views/pharmacy/medicines/show.blade.php` (بطاقة الدواء الشاملة وسجل الوجبات)
- `resources/views/pharmacy/medicines/import.blade.php` (استيراد الأدوية عبر ملف Excel/CSV)
- `resources/views/pharmacy/services/index.blade.php` (إدارة الخدمات الصيدلانية السريرية)

### ب. وجبات الأدوية والصلاحيات FEFO:
- `resources/views/pharmacy/batches/index.blade.php` (لوحة متابعة الصلاحيات والوجبات وإحصائيات التلف)
- `resources/views/pharmacy/batches/create.blade.php` (إدخال وجبة دواء جديدة وتاريخ النفاذ)
- `resources/views/pharmacy/batches/edit.blade.php` (تعديل الوجبة أو عزلها / الحجر الصحي)

### جـ. نقطة البيع السريعة (POS) والوصولات:
- `resources/views/pharmacy/pos/index.blade.php` (شاشة الكاشير السريعة للبيع والاختصارات والتعليق والبدائل)
- `resources/views/pharmacy/pos/receipt.blade.php` (وصل الطباعة الحراري 80mm مع QR Code)
- `resources/views/pharmacy/pos/history.blade.php` (سجل مبيعات الصيدلية والفواتير المعلقة)
- `resources/views/pharmacy/pos/show.blade.php` (عرض تفاصيل الفاتورة ومراجعتها)

### د. القالب الرئيسي:
- `resources/views/layouts/app.blade.php` (روابط الصيدلية في القائمة الجانبية وإصلاح تداخل القوائم)

---

## 🗄️ 4. ملفات قاعدة البيانات والصلاحيات (Migrations & Seeders)
- `database/migrations/2026_09_20_230000_create_pharmacy_system_tables.php`
- `database/seeders/RolesAndPermissionsSeeder.php`

---

## 🛣️ 5. ملفات التوجيه (Routes)
- `routes/web.php` (مسارات الصيدلية تحت البادئة `/pharmacy`)

---

## 🧪 6. الاختبارات الآلية (Automated Tests)
- `tests/Unit/PharmacyCoreTest.php`
- `tests/Feature/PharmacyCatalogTest.php`
- `tests/Feature/PharmacyBatchTest.php`
- `tests/Feature/PharmacyPosTest.php`

---

## ⚙️ 7. الأوامر المطلوب تنفيذها على السيرفر لتفعيل الصيدلية

```bash
# 1. تنفيذ هجرات جداول الصيدلية
php artisan migrate --force

# 2. تحديث صلاحيات وأدوار الصيدلية
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan permission:cache-reset

# 3. مسح وتحديث كاش النظام
php artisan optimize:clear
```

