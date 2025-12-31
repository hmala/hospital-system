# دليل الصلاحيات - Spatie Permission

## نظرة عامة
تم تحديث النظام لاستخدام حزمة **Spatie Laravel Permission** لإدارة الصلاحيات بشكل أكثر مرونة ودقة.

## الأدوار المتاحة (Roles)

### 1. المدير (admin)
- لديه جميع الصلاحيات في النظام
- يمكنه إدارة المستخدمين والأدوار

### 2. الطبيب (doctor)
الصلاحيات:
- `view patients` - عرض المرضى
- `manage own visits` - إدارة زياراته الخاصة
- `view visits` - عرض الزيارات
- `create visits` - إنشاء زيارات
- `edit visits` - تعديل الزيارات
- `view surgeries` - عرض العمليات
- `create surgeries` - إنشاء عمليات
- `edit surgeries` - تعديل العمليات
- `view radiology` - عرض الأشعة
- `create radiology` - طلب أشعة
- `view lab tests` - عرض التحاليل
- `create lab tests` - طلب تحاليل
- `manage surgery lab tests` - إدارة تحاليل العمليات

### 3. المريض (patient)
الصلاحيات:
- `view own visits` - عرض زياراته فقط
- `view appointments` - عرض المواعيد
- `create appointments` - حجز مواعيد
- `cancel appointments` - إلغاء المواعيد

### 4. موظف الاستقبال (receptionist)
الصلاحيات:
- `view patients` - عرض المرضى
- `create patients` - إضافة مرضى
- `edit patients` - تعديل بيانات المرضى
- `view doctors` - عرض الأطباء
- `view departments` - عرض العيادات
- `view appointments` - عرض المواعيد
- `create appointments` - حجز مواعيد
- `edit appointments` - تعديل المواعيد
- `delete appointments` - حذف المواعيد
- `view visits` - عرض الزيارات
- `create visits` - إنشاء زيارات
- `edit visits` - تعديل الزيارات
- `view surgeries` - عرض العمليات
- `create surgeries` - تسجيل عمليات
- `edit surgeries` - تعديل العمليات
- `manage surgery waiting list` - إدارة قائمة انتظار العمليات
- `control surgeries` - التحكم بالعمليات
- `view radiology` - عرض الأشعة
- `create radiology` - طلب أشعة
- `view lab tests` - عرض التحاليل
- `create lab tests` - طلب تحاليل
- `view inquiries` - عرض الاستعلامات
- `create inquiries` - إنشاء استعلامات
- `manage inquiries` - إدارة الاستعلامات
- `view referrals` - عرض التحويلات
- `create referrals` - إنشاء تحويلات

### 5. موظف المختبر (lab_staff)
الصلاحيات:
- `view patients` - عرض المرضى
- `view lab tests` - عرض التحاليل
- `process lab requests` - معالجة طلبات التحاليل
- `manage surgery lab tests` - إدارة تحاليل العمليات
- `view referrals` - عرض التحويلات

### 6. موظف الأشعة (radiology_staff)
الصلاحيات:
- `view patients` - عرض المرضى
- `view radiology` - عرض الأشعة
- `process radiology requests` - معالجة طلبات الأشعة
- `view referrals` - عرض التحويلات

### 7. موظف الصيدلية (pharmacy_staff)
الصلاحيات:
- `view patients` - عرض المرضى
- `view pharmacy` - عرض الصيدلية
- `process pharmacy requests` - معالجة طلبات الصيدلية
- `view referrals` - عرض التحويلات

### 8. موظف العمليات (surgery_staff)
الصلاحيات:
- `view patients` - عرض المرضى
- `view surgeries` - عرض العمليات
- `edit surgeries` - تعديل العمليات
- `manage surgery waiting list` - إدارة قائمة انتظار العمليات
- `control surgeries` - التحكم بالعمليات

## استخدام الصلاحيات في Blade

### التحقق من الدور:
```blade
@role('admin')
    <!-- محتوى خاص بالمدير -->
@endrole

@role('admin|receptionist')
    <!-- محتوى للمدير أو موظف الاستقبال -->
@endrole

@hasrole('doctor')
    <!-- محتوى للطبيب -->
@endhasrole
```

### التحقق من الصلاحية:
```blade
@can('create patients')
    <a href="{{ route('patients.create') }}">إضافة مريض</a>
@endcan

@can('edit appointments')
    <button>تعديل الموعد</button>
@endcan
```

### التحقق من عدم وجود الصلاحية:
```blade
@cannot('delete patients')
    <p>لا يمكنك حذف المرضى</p>
@endcannot

@unlessrole('patient')
    <!-- محتوى لجميع المستخدمين ماعدا المرضى -->
@endunlessrole
```

## استخدام الصلاحيات في Controllers

### التحقق من الدور:
```php
if ($user->hasRole('admin')) {
    // كود خاص بالمدير
}

if ($user->hasAnyRole(['admin', 'receptionist'])) {
    // كود للمدير أو موظف الاستقبال
}

if ($user->hasAllRoles(['admin', 'doctor'])) {
    // كود لمن لديه الدورين معاً
}
```

### التحقق من الصلاحية:
```php
if ($user->can('create patients')) {
    // المستخدم لديه صلاحية إنشاء مرضى
}

if ($user->cannot('delete patients')) {
    abort(403, 'غير مصرح لك بحذف المرضى');
}
```

### استخدام Middleware في Routes:
```php
// التحقق من الدور
Route::middleware(['role:admin'])->group(function () {
    // routes للمدير فقط
});

// التحقق من الصلاحية
Route::middleware(['permission:create patients'])->group(function () {
    // routes لمن لديه صلاحية إنشاء مرضى
});

// التحقق من دور أو صلاحية
Route::middleware(['role_or_permission:admin|create patients'])->group(function () {
    // routes للمدير أو لمن لديه صلاحية إنشاء مرضى
});
```

## الدوال المساعدة في User Model

تم تحديث الدوال التالية للعمل مع Spatie:

```php
$user->isAdmin();        // التحقق من دور المدير
$user->isDoctor();       // التحقق من دور الطبيب
$user->isPatient();      // التحقق من دور المريض
$user->isReceptionist(); // التحقق من دور موظف الاستقبال
$user->isStaff();        // التحقق من الموظفين الطبيين
$user->isSurgeryStaff(); // التحقق من موظف العمليات
```

## إدارة الصلاحيات برمجياً

### منح دور لمستخدم:
```php
$user->assignRole('admin');
$user->assignRole(['doctor', 'admin']);
```

### إزالة دور من مستخدم:
```php
$user->removeRole('admin');
```

### مزامنة الأدوار (حذف القديم ووضع الجديد):
```php
$user->syncRoles(['admin', 'doctor']);
```

### منح صلاحية مباشرة:
```php
$user->givePermissionTo('create patients');
```

### إزالة صلاحية:
```php
$user->revokePermissionTo('delete patients');
```

## ملاحظات مهمة

1. **الكاش**: Spatie يخزن الصلاحيات في الكاش. عند تغيير الصلاحيات، قد تحتاج لتشغيل:
   ```bash
   php artisan cache:forget spatie.permission.cache
   ```

2. **الهجرة التلقائية**: عند تشغيل `RolesAndPermissionsSeeder`، تم تعيين الأدوار تلقائياً لجميع المستخدمين الحاليين بناءً على حقل `role` القديم.

3. **التوافق مع الكود القديم**: تم الحفاظ على دوال `isAdmin()`, `isDoctor()`, إلخ في موديل User للتوافق مع الكود القديم.

4. **Super Admin**: يمكنك إنشاء Super Admin عبر:
   ```php
   $user->assignRole('admin');
   $user->givePermissionTo(Permission::all());
   ```

## الجداول الجديدة

تم إنشاء الجداول التالية:
- `permissions` - الصلاحيات
- `roles` - الأدوار
- `model_has_permissions` - ربط المستخدمين بالصلاحيات المباشرة
- `model_has_roles` - ربط المستخدمين بالأدوار
- `role_has_permissions` - ربط الأدوار بالصلاحيات
