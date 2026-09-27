# 💼 مساحة عمل وتطوير الموارد البشرية | Moneer's HR Workspace

> **هذا الملف هو غرفة العمليات الخاصة بـ (منير) وفرع `hr`.**  
> صُمم هذا الملف لمنع تضارب التوثيق مع التحديثات العامة في فرع `main`، ولتثبيت الرؤية، الأفكار، والمهام الخاصة بتطوير منظومة الموارد البشرية والرواتب.

---

## 📌 القواعد والسياسات الخاصة بفرع العمل (Git Rules)
* **الفرع المعتمد**: فرع **`hr`** حصراً.
* **الرفع (Push)**: يتم الرفع حصراً إلى `origin/hr` (`git push origin hr`).
* **مزامنة التحديثات العامة**: عند وصول تحديثات جديدة من الفرع الرئيسي، نقوم بعمل `fetch` ثم دمج `main` داخل `hr`، ثم مواصلة التطوير.
* **عزل التوثيق**: لا نعدل على `next_step.md` الخاص بالـ `main` للمهام اليومية الخاصة بنا، بل نوثق كل خطواتنا، أفكارنا، وسجلاتنا هنا في **`Moneer.md`**.

---

## 🔗 المراجع المركزية للنظام (Core System References)
يمكن الرجوع في أي وقت إلى ملفات النظام الرئيسية:
* 📖 [دليل وكلاء الذكاء الاصطناعي والإرشادات العامة (AGENTS.md)](file:///f:/hospital-system%20p/AGENTS.md)
* 📋 [متابعة حالة النظام والخطوات السابقة (next_step.md)](file:///f:/hospital-system%20p/next_step.md)
* 🏥 [المرجع الشامل للنظام الطبي والإداري](file:///f:/hospital-system%20p/docs/المرجع_الشامل_للنظام.md)
* 🧪 [دليل أسعار وتصنيفات المختبر](file:///f:/hospital-system%20p/resources/views/lab-tests/pricing_settings.blade.php)
* 💊 [شاشة ونقطة بيع الصيدلية الموحدة (POS & Kanban)](file:///f:/hospital-system%20p/resources/views/pharmacy/pos/index.blade.php)

---

## 📂 خريطة ملفات الموارد البشرية الحالية في المشروع (HR Components)

### 1. المتحكمات (Controllers):
* [`EmployeeController.php`](file:///f:/hospital-system%20p/app/Http/Controllers/HR/EmployeeController.php) — إدارة بيانات الموظفين، الوثائق، وتحديث الحالات.
* [`HrSettingsController.php`](file:///f:/hospital-system%20p/app/Http/Controllers/HR/HrSettingsController.php) — إعدادات الحقول الإلزامية والاختيارية لملفات الموظفين.

### 2. النماذج وقاعدة البيانات (Models & Database):
* [`Employee.php`](file:///f:/hospital-system%20p/app/Models/Employee.php) — نموذج الموظف (البيانات الشخصية، الوظيفية، والمالية).
* [`EmployeeDocument.php`](file:///f:/hospital-system%20p/app/Models/EmployeeDocument.php) — وثائق ومرفقات الموظف (عقود، هويات، شهادات).
* الميغريشنات:
  - `2026_09_16_090000_create_employees_table.php`
  - `2026_09_16_100000_create_hr_documents_and_settings_tables.php`
  - `2026_09_16_110000_add_type_and_category_to_hr_field_requirements.php`

### 3. الواجهات (Blade Views):
* **دليل الموظفين**:
  - [`index.blade.php`](file:///f:/hospital-system%20p/resources/views/hr/employees/index.blade.php) — جدول وبطاقات الموظفين مع الفلاتر والبحث.
  - [`create.blade.php`](file:///f:/hospital-system%20p/resources/views/hr/employees/create.blade.php) — استمارة إضافة موظف جديد متعددة التبويبات.
  - [`edit.blade.php`](file:///f:/hospital-system%20p/resources/views/hr/employees/edit.blade.php) — استمارة تعديل بيانات الموظف.
  - [`show.blade.php`](file:///f:/hospital-system%20p/resources/views/hr/employees/show.blade.php) — الملف الرقمي الشامل للموظف والوثائق.
* **إعدادات HR**:
  - [`settings/index.blade.php`](file:///f:/hospital-system%20p/resources/views/hr/settings/index.blade.php) — تخصيص شروط ومتطلبات البيانات.

### 4. الاختبارات الآلية (Automated Tests):
* [`HREmployeeTest.php`](file:///f:/hospital-system%20p/tests/Feature/HREmployeeTest.php) — فحص وظائف الموظفين والإعدادات.

---

## 🧭 خريطة الأفكار والأهداف المقترحة لمنظومة HR (Moneer's Roadmap)

### المرحلة 1: إتقان الملف الرقمي للموظف (Digital Employee Dossier)
- [x] إنشاء سجلات الموظفين بالأقسام والمسميات الوظيفية.
- [x] رفع وإدارة الوثائق والمستمسكات الرسمية والعقود.
- [ ] دعم التنبيه التلقائي لانتهاء العقود أو الجوازات أو الإقامات.
- [ ] ربط الموظف بمستخدم النظام (`user_id`) والصلاحيات الممنوحة له تلقائياً.

### المرحلة 2: منظومة الحضور والانصراف والدوام (Attendance & Shifts)
- [ ] جدول مناوبات الدوام (Shifts) الخاصة بالمستشفى (صباحي، مسائي، ليلي/خفارة).
- [ ] تسجيل الحضور والانصراف (استيراد من أجهزة البصمة أو تسجيل يدوي سريع).
- [ ] سجل التأخير والغياب واحتساب ساعات العمل الإضافي (Overtime).

### المرحلة 3: إدارة الإجازات والمغادرات (Leave & Time-Off Management)
- [ ] أنواع الإجازات (اعتيادية، مرضية، دراسية، بدون راتب).
- [ ] تقديم طلب الإجازة إلكترونياً ومسار الموافقة (رئيس القسم ⬅️ الموارد البشرية ⬅️ الإدارة).
- [ ] رصيد الإجازات السنوي واستهلاكه.

### المرحلة 4: منظومة الرواتب والمسيرات المالية (Payroll & Compensation)
- [ ] الراتب الأساسي + مخصصات المنصب + مخصصات الخطورة + البدلات.
- [ ] الاستقطاعات (غياب، تأخير، سلف وقروض، تقاعد وضمان اجتماعي).
- [ ] احتساب نسبة أطباء الاستشارية أو أطباء العمليات بالتكامل مع حسابات الأطباء الحالية.
- [ ] إصدار كشف الراتب الشهري (Payslip) وطباعته أو تحميله PDF.

---

## 📋 لوحة متابعة المهام الخاصة بفرع `hr` (Task Board)

### 💡 أفكار قيد التخطيط والنقاش (Backlog / Ideas):
* _(سجل أفكارك هنا ليتم برمجتها خطوة بخطوة...)_

### ⏳ قيد التنفيذ (In Progress):
* تحديد أولويات المرحلة القادمة مع منير.

### ✅ مكتمل في فرع `hr` (Done):
* [x] سحب ودمج آخر تحديثات فرع `main` بالكامل داخل `hr`.
* [x] تطبيق الميغريشنات الجديدة الخاصة بالوصفات الطبية وأسعار التحاليل.
* [x] رفع النسخة المحدثة إلى `origin/hr`.
* [x] إنشاء واعتماد ملف مساحة العمل وغرفة العمليات `Moneer.md`.

---

## 📝 سجل جلسات التطوير والتحديثات لفرع `hr` (Session Log)

### الجلسة: 2026-09-27
- **مزامنة الفروع**: سحب ودمج تحديثات فرع `main` بنجاح (تحديثات شاشة الطوارئ، وصفات الصيدلية، ومطابقة أسعار التحاليل).
- **قاعدة البيانات**: تشغيل ميغريشنات الوصفات الطبية الإلكترونية `e_prescriptions` وترقية الجداول.
- **التوثيق**: تأسيس ملف `Moneer.md` ليكون هو المرجع المباشر لكافة عمليات التطوير والأفكار لفرع الموارد البشرية بدون تضارب في الـ Git.
