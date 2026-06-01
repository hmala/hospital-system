# دليل صلاحيات حجز الأشعة من الاستعلامات

## نظرة عامة

تم إضافة نظام صلاحيات مرن للتحكم في من يستطيع حجز كل نوع من أنواع الأشعة الأربعة من الاستعلامات:

1. **أشعة عامة** (General Radiology)
2. **سونار** (Ultrasound)
3. **رنين مغناطيسي** (MRI)
4. **إيكو** (Echo)

## الصلاحيات المتاحة

### صلاحيات حجز الأشعة

| الصلاحية | الوصف | الاسم التقني |
|---------|------|-------------|
| **حجز أشعة عامة** | السماح بحجز الأشعة العامة من الاستعلامات | `inquiry.create.radiology.general` |
| **حجز سونار** | السماح بحجز السونار من الاستعلامات | `inquiry.create.radiology.ultrasound` |
| **حجز رنين مغناطيسي** | السماح بحجز الرنين المغناطيسي من الاستعلامات | `inquiry.create.radiology.mri` |
| **حجز إيكو** | السماح بحجز الإيكو من الاستعلامات | `inquiry.create.radiology.echo` |
| **حجز جميع أنواع الأشعة** | صلاحية عامة لجميع الأنواع (قديمة) | `inquiry.create.radiology` |

## كيفية استخدام الصلاحيات

### 1. إضافة الصلاحيات للأدوار الموجودة

#### أ. من خلال واجهة الويب

1. افتح قائمة **الإعدادات** > **إدارة الأدوار**
2. اختر الدور الذي تريد تعديله (مثل: موظف الاستقبال)
3. ابحث عن قسم **radiology_inquiry** (صلاحيات حجز الأشعة)
4. اختر الصلاحيات المطلوبة:
   - ✅ `inquiry.create.radiology.general` - لحجز الأشعة العامة
   - ✅ `inquiry.create.radiology.ultrasound` - لحجز السونار
   - ✅ `inquiry.create.radiology.mri` - لحجز الرنين المغناطيسي
   - ✅ `inquiry.create.radiology.echo` - لحجز الإيكو
5. احفظ التغييرات

#### ب. من خلال Laravel Tinker

```php
php artisan tinker

// إعطاء دور موظف الاستقبال صلاحية حجز السونار فقط
$role = \Spatie\Permission\Models\Role::where('name', 'receptionist')->first();
$role->givePermissionTo('inquiry.create.radiology.ultrasound');

// إعطاء دور موظف الاستقبال صلاحية حجز جميع أنواع الأشعة
$role->givePermissionTo([
    'inquiry.create.radiology.general',
    'inquiry.create.radiology.ultrasound',
    'inquiry.create.radiology.mri',
    'inquiry.create.radiology.echo'
]);
```

### 2. إعطاء الصلاحيات لمستخدم معين

```php
php artisan tinker

// إعطاء مستخدم معين صلاحية حجز السونار
$user = \App\Models\User::where('email', 'user@example.com')->first();
$user->givePermissionTo('inquiry.create.radiology.ultrasound');

// إعطاء مستخدم صلاحية حجز الرنين والإيكو فقط
$user->givePermissionTo([
    'inquiry.create.radiology.mri',
    'inquiry.create.radiology.echo'
]);
```

### 3. إنشاء دور جديد مع صلاحيات محددة

```php
php artisan tinker

// إنشاء دور "موظف استعلامات السونار" - يستطيع حجز السونار فقط
$role = \Spatie\Permission\Models\Role::create(['name' => 'ultrasound_receptionist']);
$role->givePermissionTo([
    'view patients',
    'create patients',
    'view inquiries',
    'create inquiries',
    'inquiry.create.radiology.ultrasound'
]);

// تعيين الدور لمستخدم
$user = \App\Models\User::find(123);
$user->assignRole('ultrasound_receptionist');
```

## أمثلة على سيناريوهات الاستخدام

### السيناريو 1: موظف استقبال عام
يستطيع حجز جميع أنواع الأشعة

```php
$role = \Spatie\Permission\Models\Role::where('name', 'receptionist')->first();
$role->givePermissionTo([
    'inquiry.create.radiology.general',
    'inquiry.create.radiology.ultrasound',
    'inquiry.create.radiology.mri',
    'inquiry.create.radiology.echo'
]);
```

### السيناريو 2: موظف استقبال السونار فقط
يستطيع حجز السونار فقط (لعيادة متخصصة)

```php
$role = \Spatie\Permission\Models\Role::create(['name' => 'ultrasound_only_receptionist']);
$role->givePermissionTo([
    'view patients',
    'create patients',
    'view inquiries',
    'create inquiries',
    'inquiry.create.radiology.ultrasound'
]);
```

### السيناريو 3: موظف استقبال للأشعة المتقدمة
يستطيع حجز الرنين والإيكو فقط (بدون السونار والأشعة العامة)

```php
$role = \Spatie\Permission\Models\Role::create(['name' => 'advanced_radiology_receptionist']);
$role->givePermissionTo([
    'view patients',
    'create patients',
    'view inquiries',
    'create inquiries',
    'inquiry.create.radiology.mri',
    'inquiry.create.radiology.echo'
]);
```

### السيناريو 4: منع مستخدم من حجز نوع معين
إزالة صلاحية حجز الإيكو من مستخدم معين

```php
$user = \App\Models\User::find(123);
$user->revokePermissionTo('inquiry.create.radiology.echo');
```

## التحديثات التلقائية في الواجهة

عند تطبيق الصلاحيات، ستظهر فقط أنواع الأشعة المسموح للمستخدم بحجزها:

- ✅ إذا كان لديه صلاحية `inquiry.create.radiology.general` → سيظهر زر "أشعة عامة"
- ✅ إذا كان لديه صلاحية `inquiry.create.radiology.ultrasound` → سيظهر زر "سونار"
- ✅ إذا كان لديه صلاحية `inquiry.create.radiology.mri` → سيظهر زر "رنين مغناطيسي"
- ✅ إذا كان لديه صلاحية `inquiry.create.radiology.echo` → سيظهر زر "إيكو"
- ❌ إذا لم يكن لديه أي صلاحيات للأشعة → ستختفي بطاقة "أشعة" بالكامل

## تشغيل Seeder

لتطبيق الصلاحيات الجديدة على قاعدة البيانات:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

## الصلاحية العامة القديمة

الصلاحية `inquiry.create.radiology` لا تزال تعمل وتعطي صلاحية عامة على جميع الأنواع. ننصح بالتحديث إلى الصلاحيات المحددة للتحكم الأفضل.

## التحقق من الصلاحيات في الكود

```php
// التحقق من صلاحية حجز السونار
if ($user->can('inquiry.create.radiology.ultrasound')) {
    // المستخدم يستطيع حجز السونار
}

// التحقق من صلاحية حجز الرنين أو الصلاحية العامة
if ($user->can('inquiry.create.radiology.mri') || $user->can('inquiry.create.radiology')) {
    // المستخدم يستطيع حجز الرنين
}
```

## استعلامات مفيدة

### عرض جميع الأدوار وصلاحياتهم للأشعة

```php
php artisan tinker

$roles = \Spatie\Permission\Models\Role::with('permissions')->get();
foreach ($roles as $role) {
    echo "الدور: {$role->name}\n";
    $radiologyPerms = $role->permissions->filter(function($p) {
        return str_contains($p->name, 'radiology');
    });
    foreach ($radiologyPerms as $perm) {
        echo "  - {$perm->name}\n";
    }
    echo "\n";
}
```

### عرض جميع المستخدمين الذين يستطيعون حجز السونار

```php
php artisan tinker

$users = \App\Models\User::permission('inquiry.create.radiology.ultrasound')->get();
foreach ($users as $user) {
    echo "{$user->name} ({$user->email})\n";
}
```

## الدعم الفني

إذا واجهت مشكلة في تطبيق الصلاحيات:

1. تأكد من تشغيل Seeder
2. امسح cache الصلاحيات: `php artisan permission:cache-reset`
3. راجع السجلات في `storage/logs/laravel.log`
4. تحقق من دور المستخدم: `$user->roles->pluck('name')`
5. تحقق من صلاحيات المستخدم: `$user->getAllPermissions()->pluck('name')`
