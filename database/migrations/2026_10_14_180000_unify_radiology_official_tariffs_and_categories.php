<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // -------------------------------------------------------------
        // 1. تصحيح التصنيفات الشاذة والفارغة في السجلات الحالية
        // -------------------------------------------------------------
        // نقل رنين البطن والقنوات الصفراوية MRCP من أشعة إلى رنين
        DB::table('radiology_types')
            ->where('name', 'like', '%MRCP%')
            ->orWhere('name', 'like', '%M.R.C.P%')
            ->orWhere('name', 'like', '%M .R .C .P%')
            ->update([
                'main_category' => 'رنين',
                'subcategory' => 'رنين',
                'updated_at' => now(),
            ]);

        // نقل الماموكراف (فحص الثدي الشعاعي) من سونار إلى أشعة
        DB::table('radiology_types')
            ->where('name', 'like', '%ماموكراف%')
            ->update([
                'main_category' => 'أشعة',
                'subcategory' => 'أشعة',
                'updated_at' => now(),
            ]);

        // معالجة السجلات التي تصنيفها فارغ أو غير محدد
        DB::table('radiology_types')
            ->where(function ($q) {
                $q->whereNull('main_category')->orWhere('main_category', '')->orWhere('main_category', ' ');
            })
            ->where(function ($q) {
                $q->where('name', 'like', '%رنين%')->orWhere('subcategory', 'like', '%رنين%');
            })
            ->update(['main_category' => 'رنين', 'subcategory' => 'رنين', 'updated_at' => now()]);

        DB::table('radiology_types')
            ->where(function ($q) {
                $q->whereNull('main_category')->orWhere('main_category', '')->orWhere('main_category', ' ');
            })
            ->where(function ($q) {
                $q->where('name', 'like', '%سونار%')->orWhere('name', 'like', '%دوبلر%')->orWhere('subcategory', 'like', '%سونار%');
            })
            ->update(['main_category' => 'سونار', 'subcategory' => 'سونار', 'updated_at' => now()]);

        DB::table('radiology_types')
            ->where(function ($q) {
                $q->whereNull('main_category')->orWhere('main_category', '')->orWhere('main_category', ' ');
            })
            ->where(function ($q) {
                $q->where('name', 'like', '%مفراس%')->orWhere('subcategory', 'like', '%مفراس%');
            })
            ->update(['main_category' => 'مفراس', 'subcategory' => 'مفراس', 'updated_at' => now()]);

        DB::table('radiology_types')
            ->where(function ($q) {
                $q->whereNull('main_category')->orWhere('main_category', '')->orWhere('main_category', ' ');
            })
            ->update(['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'updated_at' => now()]);

        // توحيد مسمى "الرنين" إلى "رنين" ليكون متناسقاً
        DB::table('radiology_types')
            ->where('main_category', 'الرنين')
            ->update(['main_category' => 'رنين', 'subcategory' => 'رنين']);

        // -------------------------------------------------------------
        // 2. تحديث وتثبيت الفحوصات الـ 53 المشمولة بالضمان (الفئة B حصراً)
        // مع ضبط سعر الكاش من ملف مستشفى الكفاءات
        // -------------------------------------------------------------
        $hiBItems = [
            // الأشعة السينية والتصوير الملون والبانوراما
            [
                'match_terms' => ['اشعة', 'الامعاء', 'الدقيقة', 'الملونة', 'بالباريوم'],
                'name' => 'أشعة الأمعاء الدقيقة الملونة بالباريوم',
                'dept' => 'أشعة',
                'base_price' => 150000.00,
                'hi_price' => 60000.00,
            ],
            [
                'match_terms' => ['القولون', 'الملونة', 'بالباريوم'],
                'name' => 'أشعة القولون الملونة بالباريوم (Barium Enema)',
                'dept' => 'أشعة',
                'base_price' => 125000.00,
                'hi_price' => 60000.00,
            ],
            [
                'match_terms' => ['المريء', 'الملونة', 'بالباريوم'],
                'name' => 'أشعة المريء الملونة بالباريوم (Barium Swallow)',
                'dept' => 'أشعة',
                'base_price' => 75000.00,
                'hi_price' => 50000.00,
            ],
            [
                'match_terms' => ['المريء', 'المعدة', 'الامعاء', 'بالباريوم'],
                'name' => 'أشعة المريء والمعدة والأمعاء الدقيقة بالباريوم (Barium Meal & Follow-through)',
                'dept' => 'أشعة',
                'base_price' => 150000.00,
                'hi_price' => 75000.00,
            ],
            [
                'match_terms' => ['المعدة', 'الملونة', 'بالباريوم'],
                'name' => 'أشعة المعدة الملونة بالباريوم (Barium Meal)',
                'dept' => 'أشعة',
                'base_price' => 125000.00,
                'hi_price' => 50000.00,
            ],
            [
                'match_terms' => ['بانوراما', 'ثلاثية'],
                'name' => 'أشعة بانوراما الأسنان ثلاثية الأبعاد (O.P.G)',
                'dept' => 'أشعة',
                'base_price' => 40000.00,
                'hi_price' => 18000.00,
            ],
            [
                'match_terms' => ['اشعة', 'عادية', 'مقطع', 'واحد'],
                'name' => 'أشعة عادية مقطع واحد لمنطقة محددة (X-Ray Single View)',
                'dept' => 'أشعة',
                'base_price' => 25000.00,
                'hi_price' => 15000.00,
            ],
            [
                'match_terms' => ['اشعة', 'عادية', 'مقطعين'],
                'name' => 'أشعة عادية مقطعين لمنطقة محددة (X-Ray Two Views)',
                'dept' => 'أشعة',
                'base_price' => 40000.00,
                'hi_price' => 20000.00,
            ],
            [
                'match_terms' => ['تلوين', 'الرحم', 'HSG'],
                'name' => 'تلوين الرحم والأنابيب الملون (HSG)',
                'dept' => 'أشعة',
                'base_price' => 125000.00,
                'hi_price' => 60000.00,
            ],
            [
                'match_terms' => ['تلوين', 'الناسور', 'الاحليل', 'المثانة'],
                'name' => 'تلوين الناسور أو الإحليل أو المثانة (Cystogram / Fistulogram)',
                'dept' => 'أشعة',
                'base_price' => 100000.00,
                'hi_price' => 60000.00,
            ],
            [
                'match_terms' => ['ماموكراف', 'للثدي', 'الواحد'],
                'name' => 'فحص الثدي (ماموكراف) لثدي واحد (صورتين)',
                'dept' => 'أشعة',
                'base_price' => 45000.00,
                'hi_price' => 20000.00,
            ],
            [
                'match_terms' => ['ماموكراف', 'للثديين'],
                'name' => 'فحص الثدي (ماموكراف) للثديين (4 صور)',
                'dept' => 'أشعة',
                'base_price' => 50000.00,
                'hi_price' => 40000.00,
            ],
            [
                'match_terms' => ['هشاشة', 'العظام', 'ديكسا'],
                'name' => 'فحص هشاشة العظام (كثافة العظام DEXA Scan)',
                'dept' => 'أشعة',
                'base_price' => 50000.00,
                'hi_price' => 40000.00,
            ],
            [
                'match_terms' => ['pet', 'scan'],
                'name' => 'جهاز التصوير المقطعي بالإصدار البوزيتروني (PET Scan)',
                'dept' => 'أشعة',
                'base_price' => 300000.00,
                'hi_price' => 300000.00,
            ],

            // السونار والدوبلر
            [
                'match_terms' => ['دوبلر', 'الخصية'],
                'name' => 'دوبلر الخصية الملون (Scrotal Doppler)',
                'dept' => 'سونار',
                'base_price' => 45000.00,
                'hi_price' => 35000.00,
            ],
            [
                'match_terms' => ['دوبلر', 'شرايين', 'طرف', 'واحد'],
                'name' => 'دوبلر شرايين وأوردة للطرف (العلوي أو السفلي) / طرف واحد',
                'dept' => 'سونار',
                'base_price' => 55000.00,
                'hi_price' => 36000.00,
            ],
            [
                'match_terms' => ['دوبلر', 'فحص', 'الجنين', 'third'],
                'name' => 'دوبلر فحص الجنين (Second & Third Trimester / Biophysical profile)',
                'dept' => 'سونار',
                'base_price' => 50000.00,
                'hi_price' => 40000.00,
            ],
            [
                'match_terms' => ['دوبلر', 'فحص', 'نبض', 'الجنين'],
                'name' => 'دوبلر فحص نبض الجنين بالحمل المبكر (First Trimester)',
                'dept' => 'سونار',
                'base_price' => 40000.00,
                'hi_price' => 9000.00,
            ],
            [
                'match_terms' => ['سونار', 'الانسجة', 'الرخوة'],
                'name' => 'سونار الأنسجة الرخوة (Soft Tissue US)',
                'dept' => 'سونار',
                'base_price' => 35000.00,
                'hi_price' => 30000.00,
            ],
            [
                'match_terms' => ['سونار', 'البطن', 'والحوض'],
                'name' => 'سونار البطن والحوض (Abdomen & Pelvis US)',
                'dept' => 'سونار',
                'base_price' => 50000.00,
                'hi_price' => 22000.00,
            ],
            [
                'match_terms' => ['سونار', 'الثدي', 'الواحدة'],
                'name' => 'سونار الثدي لجهة واحدة (يتضمن الثدي وتحت الإبط)',
                'dept' => 'سونار',
                'base_price' => 35000.00,
                'hi_price' => 25000.00,
            ],
            [
                'match_terms' => ['سونار', 'الثدي', 'الجهتين'],
                'name' => 'سونار الثدي للجهتين (يتضمن الثديين وتحت الإبط)',
                'dept' => 'سونار',
                'base_price' => 50000.00,
                'hi_price' => 40000.00,
            ],
            [
                'match_terms' => ['سونار', 'الرقبة', 'الشرايين'],
                'name' => 'سونار الرقبة (من غير الشرايين Neck US)',
                'dept' => 'سونار',
                'base_price' => 50000.00,
                'hi_price' => 22000.00,
            ],
            [
                'match_terms' => ['سونار', 'العين'],
                'name' => 'سونار العين (Eye Ultrasound)',
                'dept' => 'سونار',
                'base_price' => 40000.00,
                'hi_price' => 22000.00,
            ],
            [
                'match_terms' => ['سونار', 'المهبلي'],
                'name' => 'سونار المهبلي (Vaginal Ultrasound TVUS)',
                'dept' => 'سونار',
                'base_price' => 40000.00,
                'hi_price' => 27000.00,
            ],
            [
                'match_terms' => ['سونار', 'المفاصل'],
                'name' => 'سونار المفاصل (الركبة - الكتف)',
                'dept' => 'سونار',
                'base_price' => 50000.00,
                'hi_price' => 40000.00,
            ],

            // الرنين المغناطيسي
            [
                'match_terms' => ['رنين', 'البطن', 'MRCP'],
                'name' => 'رنين البطن والقنوات الصفراوية مع أو بدون (MRCP)',
                'dept' => 'رنين',
                'base_price' => 250000.00,
                'hi_price' => 125000.00,
            ],
            [
                'match_terms' => ['رنين', 'الحوض', 'البروستات'],
                'name' => 'رنين الحوض للأنسجة (البروستات - الرحم)',
                'dept' => 'رنين',
                'base_price' => 200000.00,
                'hi_price' => 150000.00,
            ],
            [
                'match_terms' => ['رنين', 'الدماغ', 'بدون', 'صبغة'],
                'name' => 'رنين الدماغ بدون صبغة (Brain MRI plain)',
                'dept' => 'رنين',
                'base_price' => 225000.00,
                'hi_price' => 75000.00,
            ],
            [
                'match_terms' => ['رنين', 'الدماغ', 'صبغة'],
                'name' => 'رنين الدماغ مع الصبغة (Brain MRI contrast)',
                'dept' => 'رنين',
                'base_price' => 250000.00,
                'hi_price' => 125000.00,
            ],
            [
                'match_terms' => ['رنين', 'الفقرات', 'الجزئي'],
                'name' => 'رنين الفقرات الجزئي (منطقة واحدة Cervical or Lumbar)',
                'dept' => 'رنين',
                'base_price' => 175000.00,
                'hi_price' => 75000.00,
            ],
            [
                'match_terms' => ['رنين', 'الدماغ', 'الاوعية', 'الدموية'],
                'name' => 'رنين الدماغ والأوعية الدموية مع الصبغة (MRA / MRV)',
                'dept' => 'رنين',
                'base_price' => 250000.00,
                'hi_price' => 125000.00,
            ],
            [
                'match_terms' => ['رنين', 'الرقبة', 'العنقية'],
                'name' => 'رنين الرقبة (الفقرات العنقية Cervical Spine MRI)',
                'dept' => 'رنين',
                'base_price' => 175000.00,
                'hi_price' => 75000.00,
            ],
            [
                'match_terms' => ['رنين', 'العظام', 'محددة'],
                'name' => 'رنين العظام لمنطقة محددة',
                'dept' => 'رنين',
                'base_price' => 200000.00,
                'hi_price' => 100000.00,
            ],
            [
                'match_terms' => ['رنين', 'العمود', 'الفقري', 'كامل'],
                'name' => 'رنين العمود الفقري كاملاً (عنقي + صدري + قطني Whole Spine)',
                'dept' => 'رنين',
                'base_price' => 300000.00,
                'hi_price' => 175000.00,
            ],
            [
                'match_terms' => ['رنين', 'الغدة', 'النخامية'],
                'name' => 'رنين الغدة النخامية مع الصبغة (Pituitary MRI with contrast)',
                'dept' => 'رنين',
                'base_price' => 250000.00,
                'hi_price' => 125000.00,
            ],
            [
                'match_terms' => ['رنين', 'المفاصل'],
                'name' => 'رنين المفاصل (الورك - الركبة - الكتف - الرسغ - الكاحل)',
                'dept' => 'رنين',
                'base_price' => 200000.00,
                'hi_price' => 100000.00,
            ],

            // المفراس الحلزوني المشمول بالضمان
            [
                'match_terms' => ['تلوين', 'الشرايين', 'بالمفراس'],
                'name' => 'تلوين الشرايين بالمفراس (C.T Angiography)',
                'dept' => 'مفراس',
                'base_price' => 300000.00,
                'hi_price' => 150000.00,
            ],
            [
                'match_terms' => ['مفراس', 'الاذن', 'الوسطى', 'بدون', 'صبغة'],
                'name' => 'مفراس الأذن الوسطى بدون صبغة (Temporal Bone CT)',
                'dept' => 'مفراس',
                'base_price' => 100000.00,
                'hi_price' => 50000.00,
            ],
            [
                'match_terms' => ['مفراس', 'الاذن', 'الوسطى', 'مع', 'الصبغة'],
                'name' => 'مفراس الأذن الوسطى مع الصبغة (Temporal Bone CT with contrast)',
                'dept' => 'مفراس',
                'base_price' => 100000.00,
                'hi_price' => 75000.00,
            ],
            [
                'match_terms' => ['مفراس', 'البطن', 'بدون', 'صبغة'],
                'name' => 'مفراس البطن بدون صبغة (Abdomen CT plain)',
                'dept' => 'مفراس',
                'base_price' => 125000.00,
                'hi_price' => 50000.00,
            ],
            [
                'match_terms' => ['مفراس', 'البطن', 'مع', 'الصبغة'],
                'name' => 'مفراس البطن مع الصبغة (Abdomen CT with contrast)',
                'dept' => 'مفراس',
                'base_price' => 175000.00,
                'hi_price' => 100000.00,
            ],
            [
                'match_terms' => ['مفراس', 'البطن', 'صبغة', 'متحركة'],
                'name' => 'مفراس البطن مع صبغة متحركة متعدد الأطوار (Dynamic CT)',
                'dept' => 'مفراس',
                'base_price' => 175000.00,
                'hi_price' => 125000.00,
            ],
            [
                'match_terms' => ['مفراس', 'الجيوب', 'الانفية'],
                'name' => 'مفراس الجيوب الأنفية مع الصبغة (PNS CT with contrast)',
                'dept' => 'مفراس',
                'base_price' => 75000.00,
                'hi_price' => 75000.00,
            ],
            [
                'match_terms' => ['مفراس', 'الدماغ', 'بدون', 'صبغة'],
                'name' => 'مفراس الدماغ بدون صبغة (Brain CT plain)',
                'dept' => 'مفراس',
                'base_price' => 100000.00,
                'hi_price' => 50000.00,
            ],
            [
                'match_terms' => ['مفراس', 'الدماغ', 'مع', 'الصبغة'],
                'name' => 'مفراس الدماغ مع الصبغة (Brain CT with contrast)',
                'dept' => 'مفراس',
                'base_price' => 125000.00,
                'hi_price' => 75000.00,
            ],
            [
                'match_terms' => ['مفراس', 'الرقبة', 'والصدر', 'والبطن'],
                'name' => 'مفراس الرقبة والصدر والبطن (أورام) مع الصبغة',
                'dept' => 'مفراس',
                'base_price' => 300000.00,
                'hi_price' => 200000.00,
            ],
            [
                'match_terms' => ['مفراس', 'الصدر', 'بدون', 'صبغة'],
                'name' => 'مفراس الصدر بدون صبغة (Chest CT plain)',
                'dept' => 'مفراس',
                'base_price' => 125000.00,
                'hi_price' => 50000.00,
            ],
            [
                'match_terms' => ['مفراس', 'الصدر', 'مع', 'الصبغة'],
                'name' => 'مفراس الصدر مع الصبغة (Chest CT with contrast)',
                'dept' => 'مفراس',
                'base_price' => 175000.00,
                'hi_price' => 75000.00,
            ],
            [
                'match_terms' => ['مفراس', 'العظام', 'بدون', 'صبغة'],
                'name' => 'مفراس العظام أو المفاصل أو العمود الفقري بدون صبغة',
                'dept' => 'مفراس',
                'base_price' => 125000.00,
                'hi_price' => 50000.00,
            ],
            [
                'match_terms' => ['مفراس', 'العظام', 'مع', 'الصبغة'],
                'name' => 'مفراس العظام أو المفاصل أو العمود الفقري مع الصبغة',
                'dept' => 'مفراس',
                'base_price' => 175000.00,
                'hi_price' => 75000.00,
            ],
            [
                'match_terms' => ['مفراس', 'القلب', 'الكالسيوم'],
                'name' => 'مفراس القلب (حساب نسبة الكالسيوم Calcium Score)',
                'dept' => 'مفراس',
                'base_price' => 50000.00,
                'hi_price' => 75000.00,
            ],
            [
                'match_terms' => ['مفراس', 'الوجه', 'والفكين'],
                'name' => 'مفراس الوجه والفكين ثلاثي الأبعاد (3D Facial CT)',
                'dept' => 'مفراس',
                'base_price' => 100000.00,
                'hi_price' => 65000.00,
            ],
            [
                'match_terms' => ['مفراس', 'القلب', 'coronary'],
                'name' => 'مفراس شرايين القلب التاجية (Coronary CT Angio)',
                'dept' => 'مفراس',
                'base_price' => 300000.00,
                'hi_price' => 175000.00,
            ],
        ];

        foreach ($hiBItems as $item) {
            // البحث عن السجل المطابق في الداتا بيس
            $query = DB::table('radiology_types');
            foreach ($item['match_terms'] as $term) {
                $query->where('name', 'like', "%{$term}%");
            }
            $existing = $query->first();

            if ($existing) {
                DB::table('radiology_types')->where('id', $existing->id)->update([
                    'main_category' => $item['dept'],
                    'subcategory' => $item['dept'],
                    'base_price' => $item['base_price'],
                    'hi_price' => $item['hi_price'],
                    'is_hi_active' => true,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            } else {
                // إذا لم يكن موجوداً يضاف بسجل جديد
                DB::table('radiology_types')->insert([
                    'name' => $item['name'],
                    'code' => 'HI-B-' . strtoupper(substr(md5($item['name']), 0, 8)),
                    'main_category' => $item['dept'],
                    'subcategory' => $item['dept'],
                    'base_price' => $item['base_price'],
                    'hi_price' => $item['hi_price'],
                    'is_hi_active' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // -------------------------------------------------------------
        // 3. إضافة وتثبيت قسم فحص إيكو القلب كقسم مستقل
        // -------------------------------------------------------------
        $echoServices = [
            [
                'name' => 'فحص إيكو القلب اعتيادي (Echocardiography)',
                'code' => 'ECHO-ROUTINE',
                'description' => 'Echocardiogram Routine',
                'main_category' => 'إيكو القلب',
                'subcategory' => 'إيكو القلب',
                'base_price' => 60000.00,
                'hi_price' => 0.00,
                'is_hi_active' => false,
                'is_active' => true,
            ],
            [
                'name' => 'فحص إيكو القلب استدعاء طارئ (Echocardiography On-Call)',
                'code' => 'ECHO-ONCALL',
                'description' => 'Echocardiogram Emergency On-Call',
                'main_category' => 'إيكو القلب',
                'subcategory' => 'إيكو القلب',
                'base_price' => 60000.00,
                'hi_price' => 0.00,
                'is_hi_active' => false,
                'is_active' => true,
            ],
        ];

        foreach ($echoServices as $echo) {
            DB::table('radiology_types')->updateOrInsert(
                ['code' => $echo['code']],
                array_merge($echo, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // -------------------------------------------------------------
        // 4. تثبيت فحوصات الكاش الخاصة بالمستشفى خارج الضمان (hi_price = 0)
        // -------------------------------------------------------------
        // أي فحص ليس له سعر ضمان أو غير مشمول، يُعطل الضمان له تماماً (is_hi_active = false) وسعره 0
        DB::table('radiology_types')
            ->where(function ($q) {
                $q->whereNull('hi_price')->orWhere('hi_price', '<=', 0);
            })
            ->update([
                'is_hi_active' => false,
                'hi_price' => 0.00,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert echo services
        DB::table('radiology_types')
            ->whereIn('code', ['ECHO-ROUTINE', 'ECHO-ONCALL'])
            ->delete();

        DB::table('radiology_types')
            ->where('code', 'like', 'HI-B-%')
            ->delete();
    }
};
