# نظام محطات العمليات الجراحية

## نظرة عامة
تم تنفيذ نظام محطات العمليات الجراحية بشكل كامل وفقاً لسير العمل الفعلي في المستشفى. يتكون النظام من **خمس محطات** منفصلة، مع ظهور محطة المقيم مرتين (قبل العملية وبعدها). كل محطة لها جدول قاعدة بيانات مستقل ومتحكم وواجهات خاصة.

## المحطات الخمسة

### 1. محطة المقيم - التحضير قبل العملية (Resident Station - Pre-Op)
- **المسار**: `/surgery-stations/resident`
- **الجدول**: `resident_stations` (phase = 'pre_op')
- **المسؤول**: الطبيب المقيم
- **المهام**:
  - تحضير المريض قبل العملية
  - الفحص والتقييم
  - التأكد من الجاهزية للعملية
  - كتابة ملاحظات التحضير
- **الانتقال**: بعد الإتمام تنتقل الحالة لصالة العمليات

### 2. صالة العمليات (Operation Theater Station)
- **المسار**: `/surgery-stations/operation-theater`
- **الجدول**: `operation_theater_stations`
- **المسؤول**: فريق غرفة العمليات (ممرض الصالة وطبيب التخدير)
- **المهام**:
  - تسجيل دخول المريض لصالة العمليات
  - تعيين ممرض/ة غرفة العمليات
  - تعيين طبيب التخدير
  - تسجيل أوقات البدء والانتهاء
  - كتابة ملاحظات الصالة والإجراءات
- **الانتقال**: بعد الإتمام تنتقل الحالة لمحطة الجراح

### 3. محطة الطبيب الجراح (Surgeon Station)
- **المسار**: `/surgery-stations/surgeon`
- **الجدول**: `surgeon_stations`
- **المسؤول**: الطبيب الجراح
- **المهام**:
  - توثيق تفاصيل العملية بعد إجرائها
  - كتابة التشخيص النهائي
  - وضع خطة العلاج
  - كتابة التوصيات
- **الانتقال**: بعد الإتمام تنتقل الحالة لمحطة التخدير

### 4. محطة التخدير (Anesthesia Station)
- **المسار**: `/surgery-stations/anesthesia`
- **الجدول**: `anesthesia_stations`
- **المسؤول**: طبيب التخدير
- **المهام**:
  - توثيق التخدير المعطى
  - تسجيل نوع التخدير المستخدم
  - تسجيل أوقات التخدير
  - كتابة ملاحظات التخدير
  - تسجيل اسم المساعد الجراحي
- **الانتقال**: بعد الإتمام تنتقل الحالة لمحطة المقيم (متابعة)

### 5. محطة المقيم - المتابعة بعد العملية (Resident Station - Post-Op)
- **المسار**: `/surgery-stations/resident`
- **الجدول**: `resident_stations` (phase = 'post_op')
- **المسؤول**: الطبيب المقيم
- **المهام**:
  - متابعة المريض بعد العملية
  - تنفيذ توصيات الجراح
  - مراقبة حالة المريض
  - كتابة ملاحظات المتابعة
  - تحديد موعد المراجعة
- **الانتقال**: بعد الإتمام تنتقل الحالة لمحطة التمريض

### 6. محطة التمريض (Nursing Station)
- **المسار**: `/surgery-stations/nursing`
- **الجدول**: `nursing_stations`
- **المسؤول**: الممرضة/الممرض
- **المهام**:
  - تعيين الممرضة المسؤولة
  - كتابة ملاحظات التمريض
  - تسجيل العلامات الحيوية
  - كتابة ملاحظات الخروج
  - الرعاية التمريضية النهائية
- **الانتقال**: بعد الإتمام تكتمل العملية بالكامل

## هيكل قاعدة البيانات

### حقول مشتركة بين كل المحطات:
- `id`: المعرف الفريد
- `surgery_id`: ربط مع جدول العمليات
- `status`: الحالة (pending, in_progress, completed)
- `started_at`: تاريخ ووقت البدء
- `completed_at`: تاريخ ووقت الإتمام
- `notes`: ملاحظات عامة
- `created_at`, `updated_at`: تواريخ الإنشاء والتحديث

### حقول خاصة بكل محطة:

**resident_stations:**
- `resident_id`: معرف الطبيب المقيم
- `phase`: المرحلة ('pre_op' للتحضير، 'post_op' للمتابعة)
- `post_op_notes`: ملاحظات ما بعد العملية
- `treatment_plan`: خطة العلاج
- `follow_up_date`: موعد المراجعة

**operation_theater_stations:**
- `or_nurse_id`: معرف ممرض/ة غرفة العمليات
- `anesthesiologist_id`: معرف طبيب التخدير
- `procedure_notes`: ملاحظات الإجراء
- `start_time`: وقت البدء
- `end_time`: وقت الانتهاء

**surgeon_stations:**
- `surgeon_id`: معرف الطبيب الجراح
- `resident_assigned_id`: معرف الطبيب المقيم المعين
- `treatment_plan`: خطة العلاج

**anesthesia_stations:**
- `anesthesiologist_id`: طبيب التخدير الرئيسي
- `anesthesiologist_2_id`: طبيب التخدير المساعد
- `anesthesia_type`: نوع التخدير
- `surgical_assistant_name`: اسم المساعد الجراحي
- `start_time`: وقت البدء
- `end_time`: وقت الانتهاء

**nursing_stations:**
- `nurse_id`: معرف الممرضة
- `nursing_notes`: ملاحظات التمريض
- `discharge_notes`: ملاحظات الخروج
- `vital_signs`: العلامات الحيوية

## سير العمل (Workflow) - المحدث

**الترتيب الصحيح للمحطات:**

1. **حجز العملية**: من الاستعلامات (Inquiries)
   - يتم حجز العملية وتحديد الموعد
   
2. **محطة المقيم (Pre-Op)**: التحضير قبل العملية
   - الطبيب المقيم يستقبل المريض
   - يقوم بالفحص والتحضير
   - التأكد من جاهزية المريض
   
3. **صالة العمليات**: إجراء العملية
   - دخول المريض لصالة العمليات
   - تسجيل فريق الصالة
   - إجراء العملية

4. **محطة الجراح**: توثيق تفاصيل العملية
   - الجراح يسجل ما تم في العملية
   - التشخيص النهائي
   - خطة العلاج والتوصيات

5. **محطة التخدير**: توثيق التخدير
   - توثيق التخدير المعطى
   - تفاصيل التخدير المستخدم

6. **محطة المقيم (Post-Op)**: المتابعة بعد العملية
   - متابعة المريض
   - تنفيذ توصيات الجراح
   - المراقبة والعناية

7. **محطة التمريض**: الرعاية التمريضية
   - العناية التمريضية
   - تسجيل العلامات الحيوية
   - الإعداد للخروج

## ملاحظات مهمة

- محطة المقيم تظهر **مرتين** في السير: مرة قبل العملية (pre_op) ومرة بعدها (post_op)
- يتم التمييز بينهما باستخدام حقل `phase` في جدول `resident_stations`
- التخدير يأتي **بعد** العملية لتوثيق ما تم إعطاؤه، وليس قبلها
- صالة العمليات هي المرحلة التي تُجرى فيها العملية فعلياً
- محطة الجراح هي لتوثيق التفاصيل والتشخيص بعد انتهاء العملية
6. **إتمام العملية**: عند إتمام محطة التمريض، تُحدث حالة العملية إلى "completed"

## العلاقات في نموذج Surgery

تم إضافة العلاقات التالية في نموذج `Surgery`:

```php
public function surgeonStation()
{
    return $this->hasOne(SurgeonStation::class);
}

public function anesthesiaStation()
{
    return $this->hasOne(AnesthesiaStation::class);
}

public function residentStation()
{
    return $this->hasOne(ResidentStation::class);
}

public function nursingStation()
{
    return $this->hasOne(NursingStation::class);
}
```

## دوال مساعدة (Helper Methods)

### `getCurrentStation()`
تحدد المحطة الحالية للعملية:
```php
$surgery->getCurrentStation(); // returns: 'surgeon', 'anesthesia', 'resident', 'nursing', or 'completed'
```

### `canProceedToNextStation()`
تتحقق من إمكانية الانتقال للمحطة التالية:
```php
$surgery->canProceedToNextStation(); // returns: true/false
```

## واجهات المستخدم

كل محطة لها واجهتين:
1. **واجهة القائمة** (`index.blade.php`): تعرض جميع العمليات في هذه المحطة
2. **واجهة التفاصيل** (`show.blade.php`): تعرض وتحرر تفاصيل العملية في المحطة

## القائمة الجانبية

تم إضافة روابط المحطات في القائمة الجانبية ضمن قسم "العمليات الجراحية" مع:
- أيقونات مميزة لكل محطة
- عدادات تُظهر عدد العمليات المعلقة في كل محطة
- ألوان مختلفة للعدادات (info, warning, primary, success)

## الصلاحيات

- محطات الجراح والتخدير والمقيم: تتطلب صلاحية `view surgeries`
- محطة التمريض: تتطلب صلاحية `view surgeries` أو `manage nursing station`

## الملفات المُنشأة

### Models:
- `app/Models/SurgeonStation.php`
- `app/Models/AnesthesiaStation.php`
- `app/Models/ResidentStation.php`
- `app/Models/NursingStation.php`

### Controllers:
- `app/Http/Controllers/SurgeonStationController.php`
- `app/Http/Controllers/AnesthesiaStationController.php`
- `app/Http/Controllers/ResidentStationController.php`
- `app/Http/Controllers/NursingStationController.php`

### Views:
- `resources/views/surgery-stations/surgeon/index.blade.php`
- `resources/views/surgery-stations/surgeon/show.blade.php`
- `resources/views/surgery-stations/anesthesia/index.blade.php`
- `resources/views/surgery-stations/anesthesia/show.blade.php`
- `resources/views/surgery-stations/resident/index.blade.php`
- `resources/views/surgery-stations/resident/show.blade.php`
- `resources/views/surgery-stations/nursing/index.blade.php`
- `resources/views/surgery-stations/nursing/show.blade.php`

### Migrations:
- `database/migrations/2026_05_03_130000_create_surgery_stations_tables.php`

### Routes:
تم إضافة 16 مساراً في `routes/web.php` ضمن مجموعة `surgery-stations`

## التشغيل

تم تشغيل الهجرة بنجاح:
```bash
php artisan migrate --path=database/migrations/2026_05_03_130000_create_surgery_stations_tables.php
```

## ملاحظات مهمة

1. **التسلسل الإلزامي**: لا يمكن الانتقال لمحطة إلا بعد إتمام المحطة السابقة
2. **الإنشاء التلقائي**: كل محطة تُنشأ تلقائياً عند إتمام المحطة السابقة
3. **العدادات المباشرة**: العدادات في القائمة الجانبية تحسب تلقائياً عدد العمليات المعلقة
4. **الصلاحيات الدقيقة**: كل محطة محمية بصلاحيات مناسبة
5. **واجهات سهلة الاستخدام**: كل واجهة مصممة بشكل بسيط وواضح

## الاستخدام

1. افتح أي محطة من القائمة الجانبية
2. اختر عملية من القائمة
3. املأ الحقول المطلوبة
4. اضغط "حفظ البيانات" لحفظ التغييرات
5. اضغط "إتمام المحطة" للانتقال للمحطة التالية

---

تم التنفيذ بتاريخ: 3 مايو 2026
