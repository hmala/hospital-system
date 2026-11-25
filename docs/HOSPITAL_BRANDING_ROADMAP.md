# منظومة التحكم بهوية وشعار المستشفى والطباعة ديناميكياً (Hospital Identity & Branding Engine Roadmap)

> **حالة الميزة:** مؤرشفة ومحفوظة للتنفيذ المستقبلي المباشر (Planned Architecture Blueprint).  
> **الهدف:** إتاحة لوحة تحكم إدارية مرنة لتعديل اسم المستشفى الرسمي (عربي/إنجليزي)، رفع واستبدال الشعار المعتمد مع معاينة حية، وضبط الترويسة والتذييل لكافة السندات والتقارير الطبية والمخزنية دون الحاجة للمساس بالأكواد البرمجية.

---

## 1. الهيكلية المعمارية (Architecture & Database Schema)

### أ. قاعدة البيانات (`database/migrations/xxxx_add_branding_fields_to_hospitals_table.php`)
توسيع جدول `hospitals` الحالي (السجل رقم `1`):
```php
Schema::table('hospitals', function (Blueprint $table) {
    $table->string('name_en')->nullable()->after('name');             // الاسم الإنجليزي: Al-Kafa'at Private Hospital
    $table->string('logo_path')->nullable()->after('name_en');        // مسار الشعار المرفوع
    $table->string('slogan_ar')->nullable()->after('logo_path');      // الترويسة الفرعية بالعربية
    $table->string('slogan_en')->nullable()->after('slogan_ar');      // الترويسة الفرعية بالإنجليزية
    $table->string('watermark_path')->nullable()->after('slogan_en'); // مسار الشعار المائي الخفيف للخلفيات
    $table->string('header_theme_color', 20)->default('#005a9c');     // لون الترويسة الرئيسي للتقارير
});
```

### ب. نموذج المستشفى ونظام الكاشينغ (`app/Models/Hospital.php`)
توفير استرجاع لحظي خالي من استعلامات SQL المتكررة (Zero SQL Queries overhead):
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Hospital extends Model
{
    protected $guarded = [];

    /**
     * استرجاع السجل الأساسي للمستشفى من الكاش الدائم
     */
    public static function current(): ?self
    {
        return Cache::rememberForever('hospital_identity', function () {
            return static::first() ?? new static([
                'name' => 'مستشفى الكفاءات الأهلي',
                'name_en' => 'Al-Kafa\'at Private Hospital',
            ]);
        });
    }

    /**
     * تحويل الشعار إلى Base64 Data URI لضمان الطباعة الفورية
     */
    public function getLogoBase64(): string
    {
        return Cache::rememberForever('hospital_logo_base64', function () {
            $path = null;
            if ($this->logo_path && file_exists(public_path($this->logo_path))) {
                $path = public_path($this->logo_path);
            } elseif (file_exists(public_path('images/1.jpg'))) {
                $path = public_path('images/1.jpg');
            } elseif (file_exists(public_path('images/logo.png'))) {
                $path = public_path('images/logo.png');
            }

            if ($path && file_exists($path)) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime = match($ext) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'svg' => 'image/svg+xml',
                    default => 'image/jpeg',
                };
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            }

            return asset('images/1.jpg');
        });
    }

    /**
     * تنظيف الكاش عند التحديث
     */
    public static function clearIdentityCache(): void
    {
        Cache::forget('hospital_identity');
        Cache::forget('hospital_logo_base64');
    }
}
```

---

## 2. المسارات والمتحكم (`Routes & Controller`)

### أ. المسارات (`routes/web.php`)
توضع تحت بادئة الإدارة العامة وتُحمى بصلاحية `manage hospital settings` أو أدوار الإدارة العليا:
```php
Route::middleware(['role_or_permission:admin|super_admin|hospital_admin'])->prefix('admin/hospital-settings')->name('admin.hospital-settings.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\HospitalSettingController::class, 'edit'])->name('edit');
    Route::put('/', [\App\Http\Controllers\Admin\HospitalSettingController::class, 'update'])->name('update');
    Route::post('/reset-logo', [\App\Http\Controllers\Admin\HospitalSettingController::class, 'resetLogo'])->name('reset-logo');
});
```

### ب. المتحكم (`app/Http/Controllers/Admin/HospitalSettingController.php`)
1. **دالة `edit()`**: جلب بيانات `Hospital::current()` وعرض شاشة الإعدادات.
2. **دالة `update()`**:
   - التحقق من صحة المدخلات (الاسم، النصوص، وقيود ملف الشعار: `image|mimes:png,jpg,jpeg,svg|max:2048`).
   - حفظ ملف الشعار الجديد في `public/uploads/hospital/logo.png`.
   - تحديث السجل رقم 1 في جدول `hospitals`.
   - استدعاء `Hospital::clearIdentityCache()`.
   - إعادة التوجيه مع رسالة نجاح.
3. **دالة `resetLogo()`**: إعادة تعيين الشعار للشعار الافتراضي (`images/1.jpg`).

---

## 3. واجهة المستخدم (`resources/views/admin/hospital-settings/edit.blade.php`)

تتضمن 3 بطاقات تفاعلية رئيسية:

1. **بطاقة رفع الشعار المعتمد (Live Logo Uploader)**:
   - منطقة إسقاط تفاعلية (Drag & Drop Zone).
   - معاينة حية بالمتصفح (Instant FileReader Preview) بمجرد اختيار الملف وقبل الضغط على حفظ.
   - خيارات التحقق: الأبعاد المقترحة (مثلاً 500x300 بكسل، خلفية شفافة).

2. **بطاقة التسميات والترويسة (Branding & Contact Info)**:
   - اسم المستشفى الرسمي بالعربية (`name`).
   - اسم المستشفى الرسمي بالإنجليزية (`name_en`).
   - الترويسة الفرعية بالعربية والإنجليزية (`slogan_ar`, `slogan_en`).
   - رقم الإجازة الصحية الصادرة من وزارة الصحة (`license_number`).
   - أرقام الهاتف والعنوان البريدي الرسمي.

3. **بطاقة المعاينة الورقية الحية (Live A4 Test Sheet Preview)**:
   - نموذج مصغر يحاكي ورقة A4 يوضح للمدير الترويسة والشعار والخطوط كما ستظهر تماماً عند الضغط على `Ctrl + P` في السندات والتقارير.

---

## 4. خطة الربط والاستبدال في النظام (System-Wide Integration)

بدلاً من تكرار كود Base64 في كل ملف `print.blade.php`:
يتم توفير مكون Blade مشترك (Component أو View Partial):
`resources/views/components/official-print-header.blade.php`
يستقبل:
* `$titleAr`: عنوان السند (مثلاً: سند تحويل وصرف واستلام مخزني رسمي).
* `$titleEn`: العنوان الإنجليزي (مثلاً: OFFICIAL STOCK DISPATCH & TRANSFER VOUCHER).
* `$docNumber`: رقم الوثيقة أو السند (`STR-000019`).
* `$department`: اسم القسم المختص (مثلاً: قسم إدارة المخازن والإمداد الطبي).

ويقوم المكون تلقائياً بقراءة الشعار واسم المستشفى من `Hospital::current()` ليتم تحديث كل تقارير المستشفى لحظياً بمجرد تغييرها من الواجهة!

---

## 5. المشاريع وخارطة الطريق المرتبطة (Related Roadmaps)

* **[منظومة النقحرة الصوتية والترجمة التلقائية لأسماء المرضى](file:///c:/wamp64/www/hospital-system/docs/PATIENT_NAME_AUTO_TRANSLITERATION_ROADMAP.md):**  
  المخطط الهندسي لترجمة ونقحرة أسماء المرضى تلقائياً إلى الإنجليزية (أوفلاين 100%) للتكامل مع ترويسة وسندات المستشفى باللغتين.
* **[دليل النظام والذاكرة البرمجية للمشروع](file:///c:/wamp64/www/hospital-system/hospital-memory.md)**.

