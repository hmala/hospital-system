<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Patient;

class DetectPatientsGenderCommand extends Command
{
    protected $signature = 'patients:detect-gender 
                            {--file=service_records.csv : مسار ملف سجلات الخدمات}
                            {--dry-run : فحص ومعاينة النتائج دون حفظ في قاعدة البيانات}
                            {--overwrite : تحديث الجنس حتى للمرضى الذين تم تحديد جنسهم مسبقاً}';

    protected $description = 'تحديد الجنس بدقة للمرضى المستوردين استناداً إلى الإجراءات الطبية وقواعد الأسماء العربية';

    private array $clinicalMale = [];
    private array $clinicalFemale = [];

    private array $unisexNames = [
        'نور', 'صباح', 'تيسير', 'احسان', 'إحسان', 'رضا', 'عصمت', 'حكمت', 'نهاد',
        'وسام', 'طلعت', 'نشات', 'نشأت', 'مرفت', 'مدحت', 'جهاد', 'تغريد'
    ];

    private array $maleTaExceptions = [
        'حمزة', 'حمزه', 'اسامة', 'اسامه', 'أسامة', 'قتيبة', 'قتيبه', 'طلحة', 'طلحه',
        'حذيفة', 'حذيفه', 'ميسرة', 'ميسره', 'عكرمة', 'عكرمه', 'عنترة', 'عنتره', 'عبيدة', 'عبيده',
        'جمعة', 'جمعه', 'سلامة', 'سلامه', 'عطية', 'عطيه', 'طرفة', 'طرفه'
    ];

    private array $maleNames = [
        'محمد', 'علي', 'احمد', 'أحمد', 'مصطفى', 'حسين', 'حيدر', 'حسن', 'عمر', 'عمار', 'كرار',
        'عباس', 'سجاد', 'مهند', 'باقر', 'جعفر', 'يوسف', 'ابراهيم', 'إبراهيم', 'عثمان', 'خالد',
        'وليد', 'قاسم', 'ماجد', 'سعد', 'سعود', 'سيف', 'عادل', 'صالح', 'طارق', 'حارث', 'فهد',
        'زياد', 'ياسر', 'بلال', 'انس', 'أنس', 'ليث', 'طه', 'فادي', 'بشار', 'قيس', 'رائد',
        'هيثم', 'سلام', 'فؤاد', 'حازم', 'ايمن', 'أيمن', 'امجد', 'أمجد', 'عصام', 'فراس', 'لؤي',
        'قصي', 'ضرغام', 'مازن', 'سامر', 'حسام', 'منذر', 'كريم', 'رحيم', 'نعيم', 'فلاح', 'نايف',
        'فيصل', 'شاكر', 'جاسم', 'سلمان', 'شهاب', 'عارف', 'وديع', 'فاضل', 'سعدي', 'ثامر', 'همام',
        'زيدون', 'يحيى', 'منير', 'ميثم', 'مرتضى', 'هادي', 'مجتبى', 'بشير', 'نذير', 'جواد', 'كاظم',
        'باسل', 'معتز', 'معن', 'عدي', 'حاتم', 'مالك', 'عمران', 'رضوان', 'عدنان', 'غسان', 'سليمان',
        'يعقوب', 'يونس', 'زكريا', 'اسماعيل', 'إسماعيل', 'شعيب', 'أيوب', 'ايوب', 'داود', 'نوح',
        'هارون', 'عيسى', 'مؤيد', 'مهدي', 'صادق', 'رعد', 'سرمد', 'نشوان', 'رياض', 'راكان', 'مروان',
        'شريف', 'اشرف', 'أشرف', 'مقداد', 'ازهر', 'أزهر', 'صلاح', 'علاء', 'بهاء', 'سامي', 'ناجي',
        'شامل', 'كامل', 'بدر', 'برهان', 'جمال', 'جلال', 'جبار', 'ستار', 'صبحي', 'فاروق', 'توفيق',
        'غيث', 'منصور', 'مسلم', 'محسن', 'حبيب', 'فخري', 'هاشم', 'خضير', 'لطيف', 'امير', 'أمير',
        'بدن', 'رميض', 'موسى', 'صائب', 'مشتاق', 'نزار', 'موفق', 'سفيان', 'خلدون', 'مأمون',
        'سعدون', 'صفوان', 'نبراس', 'ارشد', 'أرشد', 'حامد', 'حميد', 'محمود', 'صبور', 'رشيد',
        'زيد', 'عماد', 'ياسين', 'شاكر', 'مقداد', 'مظهر', 'ضامن', 'بشير', 'باقر', 'معروف', 'عصام',
        'عاصم', 'عطوان', 'فاضل', 'فياض', 'غازي', 'غالب', 'ماهر', 'منعم', 'منهل', 'ناظم', 'نبيل',
        'نصير', 'نجم', 'نعمة', 'نوري', 'هاشم', 'هاني', 'وجيه', 'وليد', 'يحيى'
    ];

    private array $femaleNames = [
        'زينب', 'زهراء', 'مريم', 'فاطمة', 'فاطمه', 'ايمان', 'إيمان', 'اسراء', 'إسراء', 'شذى', 'هدى',
        'سارة', 'ساره', 'شيماء', 'بشرى', 'هناء', 'شهد', 'دعاء', 'رسل', 'سرى', 'رنا', 'ندى',
        'مروة', 'مروه', 'امل', 'أمل', 'حنين', 'تبارك', 'غفران', 'بنين', 'تقى', 'سجى', 'رشا',
        'ضحى', 'نور الهدى', 'نبأ', 'اسيل', 'أصيل', 'اية', 'آية', 'وفاء', 'صبيحة', 'صبيحه',
        'حمدية', 'حمديه', 'فريال', 'ليلى', 'يقين', 'ولاء', 'ريناد', 'خديجة', 'خديجه', 'عائشة',
        'عائشه', 'سميرة', 'سميره', 'سعاد', 'رجاء', 'منى', 'سناء', 'صفاء', 'نجاة', 'نجات', 'خلود',
        'امال', 'آمال', 'تهاني', 'ازهار', 'أزهار', 'انتصار', 'إنتصار', 'سهام', 'احلام', 'أحلام',
        'رحاب', 'نادية', 'ناديه', 'نهى', 'رانيا', 'روان', 'ميعاد', 'وداد', 'ابتسام', 'بلقيس',
        'سحر', 'شروق', 'عبير', 'غادة', 'غاده', 'فاتن', 'لمياء', 'منال', 'مي', 'ميساء', 'نجلاء',
        'هالة', 'هاله', 'يسرى', 'إيناس', 'ايناس', 'إلهام', 'الهام', 'بسمة', 'بسمه', 'تغريد',
        'جميلة', 'جميله', 'جنان', 'جمانة', 'جمانه', 'حسناء', 'حليمة', 'حليمه', 'خولة', 'خوله',
        'دارين', 'دينا', 'ذكرى', 'ربيعة', 'ربيعه', 'رغدة', 'رغده', 'رقية', 'رقيه', 'ريم', 'ريما',
        'زكية', 'زكيه', 'زينة', 'زينه', 'ساهرة', 'ساهره', 'سلمى', 'سلوى', 'سمر', 'سهير', 'شمس',
        'صابرين', 'عالية', 'عاليه', 'عبلة', 'عبله', 'عفاف', 'عواطف', 'فاتنة', 'فاتنه', 'فادية',
        'فاديه', 'فردوس', 'كوكب', 'لطيفة', 'لطيفه', 'لمى', 'ماجدة', 'ماجده', 'مديحة', 'مديحه',
        'مرام', 'مروى', 'منيرة', 'منيره', 'ميادة', 'مياده', 'ميس', 'ميسون', 'ناهد', 'نجاح',
        'نرجس', 'نسرين', 'نوال', 'هاجر', 'هبة', 'هبه', 'هديل', 'همسة', 'همسه', 'هند', 'وجدان',
        'ياسمين', 'يسرا', 'شيرين', 'نسيم', 'رويدة', 'رويده', 'بتول', 'كوثر', 'ريتاج', 'جوري',
        'سدرة', 'سدره', 'لارين', 'ميرنا', 'داليا', 'ديما', 'لجين', 'سيلين', 'تالا', 'سما',
        'حوراء', 'الاء', 'آلاء', 'ايات', 'آيات', 'جنات', 'ابتهال', 'انعام', 'أنعام', 'هيام',
        'بيداء', 'هيفاء', 'شريفة', 'شريفه', 'فرح', 'حنان', 'ملاك', 'صفا', 'رغد', 'مها',
        'علياء', 'سهاد', 'رؤى', 'عذراء', 'رفل', 'مينا', 'علا', 'رواء', 'ريام', 'رهف',
        'قمر', 'سندس', 'ديانا', 'ريا', 'نغم', 'ورود', 'زهور', 'افراح', 'أفراح', 'اسماء', 'أسماء'
    ];

    public function handle()
    {
        $filePath = $this->option('file');
        if (!file_exists($filePath)) {
            $filePath = base_path($filePath);
        }

        $isDryRun = $this->option('dry-run');
        $overwrite = $this->option('overwrite');

        $this->info("==========================================================");
        $this->info("بدء التعرف الذكي على جنس المرضى وتحديث السجلات " . ($isDryRun ? "[معاينة Dry-Run]" : "[تنفيذ فعلي]"));
        $this->info("==========================================================");

        // 1. استخراج الأدلة الطبية والسريرية من ملف الـ CSV
        if (file_exists($filePath)) {
            $this->line("تحليل ملف الخدمات لاستخراج الفحوصات الطبية القطعية للجنس...");
            $this->extractClinicalIndicators($filePath);
            $this->line("تم تحديد " . count($this->clinicalFemale) . " حالة أنثى مؤكدة طبياً، و " . count($this->clinicalMale) . " حالة ذكر مؤكد طبياً.");
        }

        // 2. جلب مرضى النظام من جدول users المرتبطين بـ patients (حصراً دور patient)
        $query = DB::table('users')
            ->join('patients', 'patients.user_id', '=', 'users.id')
            ->leftJoin('health_insurance_categories', 'health_insurance_categories.id', '=', 'patients.health_insurance_category_id')
            ->where('users.role', 'patient')
            ->select('users.id', 'users.name', 'users.gender', 'health_insurance_categories.name as cat_name');

        if (!$overwrite) {
            $query->whereNull('users.gender');
        }

        $patients = $query->get();
        $totalToInspect = count($patients);

        $this->line("المرضى المستهدفون بالفحص: " . number_format($totalToInspect) . " مريض.");

        $males = [];
        $females = [];
        $unresolved = [];

        foreach ($patients as $p) {
            $gender = $this->determineGender($p->name, $p->cat_name);

            if ($gender === 'male') {
                $males[] = $p->id;
            } elseif ($gender === 'female') {
                $females[] = $p->id;
            } else {
                $unresolved[] = $p;
            }
        }

        $maleCount = count($males);
        $femaleCount = count($females);
        $resolvedCount = $maleCount + $femaleCount;
        $unresolvedCount = count($unresolved);
        $resolvedPercentage = $totalToInspect > 0 ? round(($resolvedCount / $totalToInspect) * 100, 1) : 0;

        $this->table(
            ['المؤشر', 'العدد', 'النسبة'],
            [
                ['إجمالي المفحوصين', number_format($totalToInspect), '100%'],
                ['ذكور مؤكدون (Male)', number_format($maleCount), ($totalToInspect ? round($maleCount / $totalToInspect * 100, 1) : 0) . '%'],
                ['إناث مؤكدات (Female)', number_format($femaleCount), ($totalToInspect ? round($femaleCount / $totalToInspect * 100, 1) : 0) . '%'],
                ['إجمالي المحسوم بدقة تامة', number_format($resolvedCount), "{$resolvedPercentage}%"],
                ['أسماء مشتركة تركت فارغة للأمان', number_format($unresolvedCount), (100 - $resolvedPercentage) . '%'],
            ]
        );

        if (!empty($unresolved)) {
            $this->line("\nعينة من الأسماء المشتركة المتروكة فارغة للأمان (دون تخمين عشوائي):");
            foreach (array_slice($unresolved, 0, 10) as $u) {
                $this->line("  * {$u->name}");
            }
        }

        if ($isDryRun) {
            $this->info("\n[وضع المعاينة الوهمية مكتمل] لم يتم تعديل أي سجل في قاعدة البيانات.");
            return 0;
        }

        // 3. التحديث الفعلي في قاعدة البيانات
        $this->info("\nجاري تحديث حقل الجنس في جدول المستخدمين (users)...");

        // تحديث الذكور
        if (!empty($males)) {
            foreach (array_chunk($males, 500) as $chunk) {
                DB::table('users')->whereIn('id', $chunk)->update(['gender' => 'male', 'updated_at' => now()]);
            }
        }

        // تحديث الإناث
        if (!empty($females)) {
            foreach (array_chunk($females, 500) as $chunk) {
                DB::table('users')->whereIn('id', $chunk)->update(['gender' => 'female', 'updated_at' => now()]);
            }
        }

        $this->info("==========================================================");
        $this->info("✅ تم تحديث بيانات الجنس لـ " . number_format($resolvedCount) . " مريضاً بنجاح تام!");
        $this->info("==========================================================");

        return 0;
    }

    private function extractClinicalIndicators(string $filePath): void
    {
        $femaleKeywords = ['pregnancy', 'hcg', 'pap smear', 'mammograph', 'obstetric', 'caesarean', 'cesarean', 'c-section', 'hysterectomy', 'curettage', 'd&c', 'ovary', 'ovarian', 'vaginal', 'cervix', 'follicul', 'hysteroscop', 'حمل', 'ولادة', 'قيصرية', 'مبيض', 'عنق الرحم', 'ثدي', 'رحم'];
        $maleKeywords = ['prostate', 'semen', 'testicular', 'testis', 'circumcision', 'varicocele', 'hydrocele', 'epididym', 'بروستات', 'خصية', 'حيامن', 'سائل منوي', 'ختان'];

        $handle = fopen($filePath, 'r');
        if (!$handle) return;

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);

        $header = fgetcsv($handle);
        $headerMap = array_flip($header);
        $nameCol = $headerMap['INSURED NAME'] ?? null;
        $srvCol = $headerMap['SERVICE'] ?? null;

        if ($nameCol === null || $srvCol === null) {
            fclose($handle);
            return;
        }

        $patientServices = [];
        while (($row = fgetcsv($handle)) !== false) {
            $name = trim($row[$nameCol] ?? '');
            $srv = mb_strtolower(trim($row[$srvCol] ?? ''), 'UTF-8');
            if ($name && $srv) {
                if (!isset($patientServices[$name])) {
                    $patientServices[$name] = [];
                }
                $patientServices[$name][] = $srv;
            }
        }
        fclose($handle);

        foreach ($patientServices as $name => $srvs) {
            $combined = implode(' ', $srvs);
            $hasF = false;
            foreach ($femaleKeywords as $fk) {
                if (str_contains($combined, $fk)) {
                    $hasF = true;
                    break;
                }
            }

            $hasM = false;
            foreach ($maleKeywords as $mk) {
                if (str_contains($combined, $mk)) {
                    $hasM = true;
                    break;
                }
            }

            if ($hasF && !$hasM) {
                $this->clinicalFemale[$name] = true;
            } elseif ($hasM && !$hasF) {
                $this->clinicalMale[$name] = true;
            }
        }
    }

    private function determineGender(string $fullName, ?string $categoryName): ?string
    {
        $fullName = trim($fullName);

        // 1. الدليل الطبي والسريري
        if (isset($this->clinicalFemale[$fullName])) return 'female';
        if (isset($this->clinicalMale[$fullName])) return 'male';

        // 2. فئات الضمان ذات الاختصاص الجنسي
        if ($categoryName === 'الفئة F') return 'female'; // إناث فوق 55
        if ($categoryName === 'الفئة G') return 'male';   // ذكور فوق 60

        // 3. البادئات
        if (str_starts_with($fullName, 'ام ') || str_starts_with($fullName, 'أم ')) return 'female';
        if (str_starts_with($fullName, 'ابو ') || str_starts_with($fullName, 'أبو ') || str_starts_with($fullName, 'ابن ')) return 'male';
        if (str_starts_with($fullName, 'عبد ') || str_starts_with($fullName, 'عبدال')) return 'male';

        $parts = preg_split('/\s+/u', $fullName);
        $first = $parts[0] ?? '';

        // تطبيع خفيف للاسم الأول
        $normFirst = preg_replace('/[إأآ]/u', 'ا', $first);
        // تجريد أل التعريف إذا كانت زائدة
        if (str_starts_with($normFirst, 'ال') && mb_strlen($normFirst, 'UTF-8') > 3) {
            $withoutAl = mb_substr($normFirst, 2, null, 'UTF-8');
            if (in_array($withoutAl, $this->maleNames)) return 'male';
            if (in_array($withoutAl, $this->femaleNames)) return 'female';
        }

        // 4. الأسماء المشتركة الممنوعة من التخمين
        if (in_array($first, $this->unisexNames) || in_array($normFirst, $this->unisexNames)) {
            return null; // يترك فارغاً للأمان
        }

        // 5. القواميس المعتمدة
        if (in_array($first, $this->maleNames) || in_array($normFirst, $this->maleNames)) {
            return 'male';
        }
        if (in_array($first, $this->femaleNames) || in_array($normFirst, $this->femaleNames)) {
            return 'female';
        }

        // 6. القواعد الصرفية
        // استثناءات التاء المربوطة المذكرة (مثل حمزة، أسامة، عنترة)
        if (in_array($first, $this->maleTaExceptions) || in_array($normFirst, $this->maleTaExceptions)) {
            return 'male';
        }

        // الأسماء المنتهية بتاء مربوطة -> مؤنث
        if (str_ends_with($first, 'ة') || str_ends_with($first, 'ه')) {
            return 'female';
        }

        // الأسماء المنتهية بألف ممدودة -> مؤنث (زهراء، حوراء، آلاء)
        if (str_ends_with($first, 'اء') && !in_array($first, ['بهاء', 'علاء', 'ضياء'])) {
            return 'female';
        }

        // الأسماء المنتهية بـ ات -> مؤنث (آيات، جنات)
        if (str_ends_with($first, 'ات')) {
            return 'female';
        }

        // الأسماء المنتهية بألف مقصورة -> مؤنث
        if (str_ends_with($first, 'ى') && !in_array($first, ['يحيى', 'موسى', 'عيسى', 'مرتضى', 'مصطفى', 'مجتبى'])) {
            return 'female';
        }

        // الأسماء المنتهية بـ ون -> مذكر (زيدون، خلدون، مأمون، سعدون)
        if (str_ends_with($first, 'ون')) {
            return 'male';
        }

        // غير محسوم يترك فارغاً
        return null;
    }
}
