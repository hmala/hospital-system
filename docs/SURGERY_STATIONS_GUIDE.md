# نظام محطات العمليات الجراحية

## نظرة عامة
تم تنفيذ نظام محطات العمليات الجراحية بشكل كامل. يتكون النظام من أربع محطات منفصلة، كل محطة لها جدول قاعدة بيانات مستقل ومتحكم وواجهات خاصة.

## المحطات الأربعة

### 1. محطة الطبيب الجراح (Surgeon Station)
- **المسار**: `/surgery-stations/surgeon`
- **الجدول**: `surgeon_stations`
- **المسؤول**: الطبيب الجراح
- **المهام**:
  - مراجعة تفاصيل العملية
  - تعيين طبيب مقيم للحالة
  - كتابة ملاحظات الجراح
  - وضع خطة العلاج
- **الانتقال**: بعد الإتمام تنتقل الحالة لمحطة التخدير

### 2. محطة التخدير (Anesthesia Station)
- **المسار**: `/surgery-stations/anesthesia`
- **الجدول**: `anesthesia_stations`
- **المسؤول**: طبيب التخدير
- **المهام**:
  - تعيين طبيب/أطباء التخدير (رئيسي + مساعد)
  - تحديد نوع التخدير (موضعي/إقليمي/عام/تنويم)
  - تسجيل اسم المساعد الجراحي
  - تسجيل أوقات البدء والانتهاء
  - كتابة ملاحظات التخدير
- **الانتقال**: بعد الإتمام تنتقل الحالة لمحطة المقيم

### 3. محطة الطبيب المقيم (Resident Station)
- **المسار**: `/surgery-stations/resident`
- **الجدول**: `resident_stations`
- **المسؤول**: الطبيب المقيم (يتم تعيينه من محطة الجراح)
- **المهام**:
  - كتابة ملاحظات المقيم
  - كتابة ملاحظات ما بعد العملية
  - وضع خطة العلاج
  - تحديد موعد المراجعة
- **الانتقال**: بعد الإتمام تنتقل الحالة لمحطة التمريض

### 4. محطة التمريض (Nursing Station)
- **المسار**: `/surgery-stations/nursing`
- **الجدول**: `nursing_stations`
- **المسؤول**: الممرضة/الممرض
- **المهام**:
  - تعيين الممرضة المسؤولة
  - كتابة ملاحظات التمريض
  - تسجيل العلامات الحيوية
  - كتابة ملاحظات الخروج
- **الانتقال**: بعد الإتمام تكتمل العملية بالكامل

## هيكل قاعدة البيانات

كل محطة لها جدول مستقل بالحقول التالية:

### حقول مشتركة بين كل المحطات:
- `id`: المعرف الفريد
- `surgery_id`: ربط مع جدول العمليات
- `status`: الحالة (pending, in_progress, completed)
- `started_at`: تاريخ ووقت البدء
- `completed_at`: تاريخ ووقت الإتمام
- `notes`: ملاحظات عامة
- `created_at`, `updated_at`: تواريخ الإنشاء والتحديث

### حقول خاصة بكل محطة:

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

**resident_stations:**
- `resident_id`: معرف الطبيب المقيم
- `post_op_notes`: ملاحظات ما بعد العملية
- `treatment_plan`: خطة العلاج
- `follow_up_date`: موعد المراجعة

**nursing_stations:**
- `nurse_id`: معرف الممرضة
- `nursing_notes`: ملاحظات التمريض
- `discharge_notes`: ملاحظات الخروج
- `vital_signs`: العلامات الحيوية

## سير العمل (Workflow)

1. **إنشاء العملية**: تبدأ العملية من نظام الحجز الموجود
2. **محطة الجراح**: أول محطة، يتم إنشاؤها تلقائياً عند دخول العملية
3. **محطة التخدير**: تُنشأ تلقائياً عند إتمام محطة الجراح
4. **محطة المقيم**: تُنشأ تلقائياً عند إتمام محطة التخدير (مع الطبيب المقيم المعين)
5. **محطة التمريض**: تُنشأ تلقائياً عند إتمام محطة المقيم
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
