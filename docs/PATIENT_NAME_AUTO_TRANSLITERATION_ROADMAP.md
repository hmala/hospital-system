# منظومة النقحرة الصوتية والترجمة التلقائية لأسماء المرضى (Patient Name Auto-Transliteration Engine Roadmap)

> **حالة الميزة:** مؤرشفة ومحفوظة للتنفيذ المستقبلي المباشر (Planned Architecture Blueprint).  
> **الهدف:** توليد الاسم باللغة الإنجليزية تلقائياً للمريض فور كتابة الاسم بالعربية في شاشات الاستقبال والطوارئ والعيادات، لضمان دقة التقارير الطبية ونتائج التحاليل والأشعة وشهادات الولادة دون الحاجة للترجمة اليدوية، مع تمكين الموظف من التعديل اليدوي ومطابقة الجواز الرسمي، والعمل 100% Offline بدون إنترنت.

---

## 1. فلسفة النظام (Core Principles)

1. **النقحرة الصوتية وليس ترجمة المعنى (Transliteration vs Translation):**
   * الأسماء لا تُترجم معانيها (مثلاً: «سعيد» لا يُترجم `Happy` بل صوتياً `Saeed` أو `Said`).
   * اسم «نور» يُترجم `Noor` وليس `Light`.
2. **العمل دون إنترنت (100% Offline):**
   * الاعتماد الكامل على كود محلي (Client-side JS + Laravel Backend Service) دون استهلاك أي API خارجي أو اشتراكات أو بطء شبكي.
3. **التوليد الفوري اللحظي (Zero Latency):**
   * يمتلئ حقل الاسم الإنجليزي في نفس لحظة نقر الموظف على لوحة المفاتيح أثناء كتابة الاسم بالعربي.
4. **المرونة السريرية والقانونية (User Override):**
   * الحقل الإنجليزي يظل متاحاً للتعديل الحر من قبل موظف الاستقبال في حال قدم المريض جواز سفر بهجاء معين (مثل `Hussain` بدلاً من `Hussein`، أو `Mohamed` بدلاً من `Mohammed`).

---

## 2. الهيكلية المعمارية وقاعدة البيانات (Database Schema)

### أ. ملف الترحيل (`database/migrations/xxxx_add_name_en_to_users_and_patients.php`)
```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة الحقل لجدول المستخدمين (لكل من المريض والكادر)
        Schema::table('users', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
        });

        // إضافة حقل اسم الأم بالإنجليزية للمرضى والمواليد والخدج
        Schema::table('patients', function (Blueprint $table) {
            $table->string('mother_name_en')->nullable()->after('mother_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name_en');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('mother_name_en');
        });
    }
};
```

### ب. النماذج (`app/Models/User.php` & `app/Models/Patient.php`)
إضافة `name_en` إلى المصفوفة `$fillable`:
```php
// User.php
protected $fillable = [
    'name',
    'name_en',
    // ...
];

// Patient.php
protected $fillable = [
    'mother_name_en',
    // ...
];

// مساعد للحصول على الاسم المعروض حسب لغة التقرير أو الافتراضي
public function getDisplayNameAttribute(): string
{
    if (app()->getLocale() === 'en' && !empty($this->name_en)) {
        return $this->name_en;
    }
    return $this->name;
}
```

---

## 3. محرك النقحرة اللحظي في المتصفح (`public/js/arabic-transliterator.js`)

ملف جافاسكريبت مستقل وخفيف الوزن (أقل من 15 كيلوبايت) يُحمّل في صفحات الاستقبال:

```javascript
/**
 * محرك النقحرة الصوتية الذكي للأسماء العربية والعراقية
 * Smart Arabic Name Transliterator Engine
 */
const ArabicTransliterator = (function () {
    // 1. قاموس الأسماء الشائعة والمفردة والمركبة
    const dictionary = {
        'محمد': 'Mohammed',
        'احمد': 'Ahmed',
        'أحمد': 'Ahmed',
        'علي': 'Ali',
        'حسن': 'Hassan',
        'حسين': 'Hussein',
        'حيدر': 'Haidar',
        'كرار': 'Karrar',
        'سجاد': 'Sajjad',
        'جعفر': 'Jaafar',
        'صادق': 'Sadiq',
        'كاظم': 'Kadhim',
        'مهدي': 'Mahdi',
        'رضا': 'Redha',
        'مرتضى': 'Murtadha',
        'مصطفى': 'Mustafa',
        'عمر': 'Omar',
        'عثمان': 'Othman',
        'ابو بكر': 'Abu Bakr',
        'أبوبكر': 'Abubakr',
        'يوسف': 'Youssef',
        'إبراهيم': 'Ibrahim',
        'ابراهيم': 'Ibrahim',
        'موسى': 'Musa',
        'عيسى': 'Isa',
        'يحيى': 'Yahya',
        'زكريا': 'Zakaria',
        'خالد': 'Khalid',
        'طارق': 'Tariq',
        'زياد': 'Ziyad',
        'وليد': 'Waleed',
        'ياسر': 'Yasser',
        'عمار': 'Ammar',
        'بلال': 'Bilal',
        'سيف': 'Saif',
        'سامر': 'Samer',
        'فهد': 'Fahad',
        'سعد': 'Saad',
        'سعيد': 'Saeed',
        'فاطمة': 'Fatima',
        'زهراء': 'Zahraa',
        'زينب': 'Zainab',
        'مريم': 'Maryam',
        'سارة': 'Sara',
        'نور': 'Noor',
        'هدى': 'Huda',
        'ايات': 'Ayat',
        'آيات': 'Ayat',
        'دعاء': 'Duaa',
        'رنا': 'Rana',
        'ريم': 'Reem',
        'هبة': 'Hiba',
        'اسراء': 'Israa',
        'إسراء': 'Israa',
        'حوراء': 'Hawraa',
        'بنين': 'Baneen',
        'رقية': 'Ruqayah',
        'خديجة': 'Khadija',
        'عائشة': 'Aisha',
        'شهد': 'Shahad',
        'تبارك': 'Tabarak',
        'جاسم': 'Jasim',
        'قاسم': 'Qasim',
        'عباس': 'Abbas',
        'فاضل': 'Fadhil',
        'كريم': 'Kareem',
        'رحيم': 'Raheem',
        'حميد': 'Hameed',
        'مجيد': 'Majeed',
        'صباح': 'Sabah',
        'فلاح': 'Falah',
        'نجاح': 'Najah'
    };

    // بادئات الأسماء المركبة (عبد، نور، سيف، شمس...)
    const compoundPrefixes = {
        'عبد الله': 'Abdullah',
        'عبدالله': 'Abdullah',
        'عبد الرحمن': 'Abdulrahman',
        'عبدالرحمن': 'Abdulrahman',
        'عبد الرحيم': 'Abdulraheem',
        'عبد الكريم': 'Abdulkareem',
        'عبد الحسين': 'Abdulhussein',
        'عبد الحسن': 'Abdulhassan',
        'عبد الرضا': 'Abdulredha',
        'عبد الخالق': 'Abdulkhalik',
        'عبد الستار': 'Abdulsettar',
        'عبد العظيم': 'Abduladheem',
        'عبد العزيز': 'Abdulaziz',
        'نور الدين': 'Noor Aldeen',
        'سيف الدين': 'Saif Aldeen',
        'ضياء الدين': 'Dhiyaa Aldeen',
        'علاء الدين': 'Alaa Aldeen',
        'بهاء الدين': 'Bahaa Aldeen'
    };

    // 2. خريطة الحروف والقواعد الصوتية للحالات غير الموجودة بالقاموس
    const charMap = {
        'ا': 'a', 'أ': 'a', 'إ': 'i', 'آ': 'aa', 'ء': '', 'ئ': 'e', 'ؤ': 'u',
        'ب': 'b', 'ت': 't', 'ث': 'th', 'ج': 'j', 'ح': 'h', 'خ': 'kh',
        'د': 'd', 'ذ': 'dh', 'ر': 'r', 'ز': 'z', 'س': 's', 'ش': 'sh',
        'ص': 's', 'ض': 'dh', 'ط': 't', 'ظ': 'dh', 'ع': 'a', 'غ': 'gh',
        'ف': 'f', 'ق': 'q', 'ك': 'k', 'ل': 'l', 'م': 'm', 'ن': 'n',
        'ه': 'h', 'و': 'w', 'ي': 'y', 'ى': 'a', 'ة': 'ah'
    };

    function transliterateWord(word) {
        if (!word) return '';
        word = word.trim();

        // فحص مباشر في القاموس
        if (dictionary[word]) {
            return dictionary[word];
        }

        // فحص "ال" التعريف
        let prefix = '';
        let baseWord = word;
        if (word.startsWith('ال') && word.length > 3) {
            prefix = 'Al-';
            baseWord = word.substring(2);
            if (dictionary[baseWord]) {
                return prefix + dictionary[baseWord];
            }
        }

        // تحويل صوتي حرف بحرف
        let result = '';
        for (let i = 0; i < baseWord.length; i++) {
            const ch = baseWord[i];
            result += charMap[ch] !== undefined ? charMap[ch] : ch;
        }

        // جعل أول حرف Capital
        if (result.length > 0) {
            result = result.charAt(0).toUpperCase() + result.slice(1);
        }

        return prefix + result;
    }

    function transliterateFullName(fullName) {
        if (!fullName || typeof fullName !== 'string') return '';

        let cleanText = fullName.trim().replace(/\s+/g, ' ');

        // فحص الأسماء المركبة أولاً
        for (const [arComp, enComp] of Object.entries(compoundPrefixes)) {
            const regex = new RegExp('(^|\\s)' + arComp + '($|\\s)', 'g');
            cleanText = cleanText.replace(regex, '$1' + enComp + '$2');
        }

        const parts = cleanText.split(' ');
        const translatedParts = parts.map(part => {
            // إذا كانت الكلمة تحولت بالفعل إلى إنجليزي عبر المركبات
            if (/^[A-Za-z\-]+$/.test(part)) return part;
            return transliterateWord(part);
        });

        return translatedParts.filter(Boolean).join(' ');
    }

    return {
        transliterate: transliterateFullName
    };
})();
```

---

## 4. التكامل في واجهة تسجيل المرضى (`resources/views/patients/create.blade.php`)

إضافة حقل الاسم بالإنجليزية مع الربط التلقائي:

```html
<div class="row g-3">
    <!-- حقل الاسم بالعربية -->
    <div class="col-md-6">
        <label for="name" class="form-label fw-bold">اسم المريض الكامل (بالعربية) <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user text-primary"></i></span>
            <input type="text" name="name" id="patientNameAr" class="form-control" required placeholder="مثال: محمد علي حسين">
        </div>
    </div>

    <!-- حقل الاسم بالإنجليزية (يتولد تلقائياً مع إمكانية التعديل) -->
    <div class="col-md-6">
        <label for="name_en" class="form-label fw-bold d-flex justify-content-between align-items-center">
            <span>الاسم بالإنجليزية (Name in English)</span>
            <span class="badge bg-success-subtle text-success small font-monospace"><i class="fas fa-magic me-1"></i>توليد تلقائي</span>
        </label>
        <div class="input-group">
            <span class="input-group-text font-monospace text-muted">EN</span>
            <input type="text" name="name_en" id="patientNameEn" class="form-control font-monospace" placeholder="e.g. Mohammed Ali Hussein">
            <button type="button" class="btn btn-outline-secondary" id="btnReTransliterate" title="إعادة التوليد الصوتي من العربي">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
        <div class="form-text text-muted small">يتولد تلقائياً ويمكنك تعديله ليتطابق تماماً مع جواز السفر.</div>
    </div>
</div>

<script src="{{ asset('js/arabic-transliterator.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const nameAr = document.getElementById('patientNameAr');
    const nameEn = document.getElementById('patientNameEn');
    const btnRe = document.getElementById('btnReTransliterate');
    let isManuallyEdited = false;

    // إذا عدل الموظف الحقل الإنجليزي يدوياً، نتوقف عن الكتابة فوقه
    nameEn.addEventListener('input', function() {
        isManuallyEdited = (nameEn.value.trim().length > 0);
    });

    // توليد فوري أثناء الكتابة بالعربي ما لم يقم الموظف بتعديل يدوي
    nameAr.addEventListener('input', function() {
        if (!isManuallyEdited) {
            nameEn.value = ArabicTransliterator.transliterate(nameAr.value);
        }
    });

    // زر إعادة التوليد
    if (btnRe) {
        btnRe.addEventListener('click', function() {
            nameEn.value = ArabicTransliterator.transliterate(nameAr.value);
            isManuallyEdited = false;
        });
    }
});
</script>
```

---

## 5. خدمة الباك إند في لارافيل (`app/Services/ArabicNameTransliterator.php`)

تعمل كـ Fallback للحالات التي يتم فيها إنشاء المريض دون المرور بالواجهة (مثل الاستيراد، أو التسجيل السريع، أو API):

```php
namespace App\Services;

class ArabicNameTransliterator
{
    protected static array $dictionary = [
        'محمد' => 'Mohammed',
        'علي' => 'Ali',
        'حسين' => 'Hussein',
        'احمد' => 'Ahmed',
        'أحمد' => 'Ahmed',
        // نفس القاموس السريع
    ];

    public static function transliterate(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        $parts = preg_split('/\s+/u', trim($text));
        $translated = [];

        foreach ($parts as $part) {
            if (isset(self::$dictionary[$part])) {
                $translated[] = self::$dictionary[$part];
            } else {
                // تحويل صوتي بسيط أو تركه
                $translated[] = ucfirst($part);
            }
        }

        return implode(' ', $translated);
    }
}
```

وفي `app/Http/Controllers/PatientController.php`:
```php
if (empty($request->name_en) && $request->filled('name')) {
    $data['name_en'] = \App\Services\ArabicNameTransliterator::transliterate($request->name);
}
```

---

## 6. الاستفادة في السندات والتقارير والمطبوعات الطبية (Reports & Printouts)

1. **نتائج التحاليل المختبرية (Lab Results A4):**
   * عرض الاسم العربي وبجانبه الإنجليزي: `محمد علي حسين / Mohammed Ali Hussein`.
2. **تقارير الأشعة والسونار والرنين (Radiology Reports):**
   * الترويسة الطبية الرسمية بالإنجليزية للمراكز والمستشفيات التخصصية.
3. **أساور معصم المريض وباجات الدخول (Patient Wristbands & ID Cards):**
   * طباعة الاسم الإنجليزي ورقم الـ MRN والباركود لتجنب أخطاء قراءة الأجهزة الطبية.
4. **شهادات واستمارات الولادة والخدج (NICU Case Sheets):**
   * تسجيل اسم المولود واسم الأم باللغتين للتوثيق والشهادات الرسمية.

---

## 7. خارطة طريق التنفيذ (Execution Milestones)

| الخطوة | التفاصيل | الوقت التقديري |
| :--- | :--- | :--- |
| **1. الترحيل والمودلز** | إضافة عمود `name_en` في `users` و `mother_name_en` في `patients` | 15 دقيقة |
| **2. ملف الجافاسكريبت** | إنشاء `public/js/arabic-transliterator.js` وتزويده بأهم الأسماء العراقية والعربية | 30 دقيقة |
| **3. واجهات الاستقبال** | ربط الحقل في شاشتي إضافة وتعديل المريض مع الزر السحري `Auto-generate` | 30 دقيقة |
| **4. الباك إند والـ Fallback** | كلاس `ArabicNameTransliterator` لضمان عدم وجود حقل فارغ عند الحفظ | 20 دقيقة |
| **5. قوالب الطباعة الطبية** | إضافة الاسم الإنجليزي في ترويسة التحاليل والأشعة وسندات المرضى | 30 دقيقة |
| **6. الاختبارات الآلية** | كتابة اختبارات Unit و Feature للتحقق من دقة التحويل والحفظ | 25 دقيقة |

---

## 8. المراجع والمشاريع المرتبطة
* [خارطة طريق إدارة هوية وشعار المستشفى ديناميكياً](file:///c:/wamp64/www/hospital-system/docs/HOSPITAL_BRANDING_ROADMAP.md)
* [دليل النظام الشامل والذاكرة البرمجية](file:///c:/wamp64/www/hospital-system/hospital-memory.md)
