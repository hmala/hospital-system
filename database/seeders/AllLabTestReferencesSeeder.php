<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LabTest;
use App\Models\LabTestReference;

class AllLabTestReferencesSeeder extends Seeder
{
    private $addedCount = 0;
    private $skippedCount = 0;

    public function run(): void
    {
        $this->command->info('بدء إضافة القيم المرجعية...');
        
        // حذف القيم المرجعية الموجودة
        LabTestReference::truncate();

        // إضافة جميع القيم المرجعية
        $this->addAllReferences();

        $this->command->info("✅ تم بنجاح: {$this->addedCount} قيمة مرجعية");
        $this->command->warn("⚠️  تم تخطي: {$this->skippedCount} تحليل (غير موجود)");
    }

    private function addAllReferences()
    {
        // القيم المرجعية الرقمية
        $numericTests = [
            ['(Free T4) free thyroxine', ['both', 0, 999, 12.30, 20.20, 'pmol/L']],
            ['(Mg) Magnesium', ['both', 0, 999, 1.82, 2.43, 'mg/dL']],
            ['Activated Partial Thromboplastin Time (APTT)', ['both', 0, 999, 25, 39, 'sec']],
            ['Adrenocorticotropic Hormone (ACTH)', ['both', 0, 999, 7.4, 64.3, 'pg/mL']],
            ['Albumin', ['both', 18, 999, 3.5, 5.5, 'g/dL']],
            ['Alkaline Phosphatase(ALP)', ['both', 18, 999, 35, 104, 'U/L']],
            ['ALT/GPT', ['both', 18, 999, 0, 40, 'U/L']],
            ['Amylase', ['both', 0, 999, 0, 100, 'U/L']],
            ['AMA (Antimitochondrial Antibody)', ['both', 0, 999, 0, 18, 'U/mL']],
            ['ANA', ['both', 0, 999, 0, 1.1, 'Index']],
            ['Anti Diuretic hormone (ADH)', ['both', 0, 999, 0, 5.9, 'pg/mL']],
            ['Anti-a-Gliadin IgA', ['both', 0, 999, 0, 10, 'U/mL']],
            ['Anti-a-Gliadin IgG', ['both', 0, 999, 0, 10, 'U/mL']],
            ['Anti-Cardiolipin IgG', ['both', 0, 999, 0, 10, 'GPL']],
            ['Anti-Cardiolipin IgM', ['both', 0, 999, 0, 10, 'MPL']],
            ['Anti-CCP', ['both', 0, 999, 0, 0.5, 'U/mL']],
            ['Anti-Mullerian Hormone (AMH)', ['female', 18, 999, 4.0, 6.8, 'ng/mL']],
            ['Anti-Peroxidase (Anti-TPO)', ['both', 0, 999, 0, 34, 'IU/mL']],
            ['Anti-Thyroglobulin (Anti TG)', ['both', 0, 999, 0, 115, 'IU/mL']],
            ['ASOT', ['both', 0, 999, 0, 200, 'IU/mL']],
            ['AST/GOT', ['both', 18, 999, 10, 45, 'U/L']],
            ['Bilirubin (Direct)', ['both', 0, 999, 0.01, 0.3, 'mg/dL']],
            ['Bilirubin (Indirect)', ['both', 0, 999, 0.01, 0.8, 'mg/dL']],
            ['Bilirubin (Total)', ['both', 0, 999, 0.2, 1.2, 'mg/dL']],
            ['BNP', ['both', 0, 999, 0, 450, 'pg/mL']],
            ['Brucella IgG', ['both', 0, 999, 0, 10, 'IU/mL']],
            ['Brucella IgM', ['both', 0, 999, 0, 10, 'IU/mL']],
            ['CA125', ['both', 0, 999, 0, 35, 'U/mL']],
            ['CA15-3', ['both', 0, 999, 0, 34.5, 'U/mL']],
            ['CA19.9', ['both', 0, 999, 0, 37, 'U/mL']],
            ['Calcium', ['both', 0, 999, 8.5, 10.5, 'mg/dL']],
            ['Cardiac Troponin I titer', ['both', 0, 999, 0.001, 0.3, 'ng/mL']],
            ['Cardiolipin IgA', ['both', 0, 999, 0, 10, 'APL']],
            ['CEA', ['both', 0, 999, 0, 5, 'ng/mL', 'Non-smoker: <4, Smoker: <5']],
            ['Ceruloplasmin', ['both', 0, 999, 19.0, 31.0, 'mg/dL']],
            ['Chloride (Cl)', ['both', 0, 999, 95, 112, 'mmol/L']],
            ['Cholesterol', ['both', 18, 999, 100, 200, 'mg/dL']],
            ['CK-MB', ['both', 0, 999, 0, 25, 'ng/mL']],
            ['CMV IgG', ['both', 0, 999, 0, 10, 'Index']],
            ['CMV IgM', ['both', 0, 999, 0, 10, 'Index']],
            ['Complement 3 (C3)', ['both', 0, 999, 0.9, 1.8, 'g/L']],
            ['Complement 4 (C4)', ['both', 0, 999, 0.1, 0.4, 'g/L']],
            ['Copper', ['both', 0, 999, 80, 155, 'µg/dL']],
            ['C-Peptide', ['both', 0, 999, 1.1, 4.4, 'ng/mL']],
            ['C-Reactive Protein titer (CRP)', ['both', 0, 999, 0, 5, 'mg/L']],
            ['D-Dimer', ['both', 0, 999, 0, 0.5, 'µg/mL']],
            ['Fasting Blood Sugar (FBS)', ['both', 0, 999, 75, 105, 'mg/dL']],
            ['Free PSA', ['male', 18, 999, 0, 2, 'ng/mL']],
            ['Free T3', ['both', 0, 999, 2.0, 4.4, 'pg/mL']],
            ['Globulin', ['both', 0, 999, 2, 3.5, 'g/dL']],
            ['HbA1c', ['both', 0, 999, 4.2, 6.0, '%']],
            ['HDL-Cholesterol', ['both', 18, 999, 40, 999, 'mg/dL', 'More than 40']],
            ['INR', ['both', 0, 999, 0.9, 1.3, 'Ratio']],
            ['Ionized Calcium', ['both', 0, 999, 4.5, 5.6, 'mg/dL']],
            ['Lactate Dehydrogenase (LDH)', ['both', 0, 999, 135, 214, 'U/L']],
            ['LDL-Cholesterol', ['both', 18, 999, 0, 129, 'mg/dL']],
            ['Lipase', ['both', 0, 999, 0, 60, 'U/L']],
            ['Lupus Anticoagulant', ['both', 0, 999, 33.4, 40.3, 'sec']],
            ['Myoglobin', ['both', 0, 999, 28, 72, 'ng/mL']],
            ['Para Thyroid Hormone (PTH)', ['both', 0, 999, 16, 79, 'pg/mL']],
            ['Phosphorus (P)', ['both', 0, 999, 2.5, 4.5, 'mg/dL']],
            ['Potassium ( K+ )', ['both', 0, 999, 3.5, 5.2, 'mmol/L']],
            ['Procalcitonin (PCT)', ['both', 0, 999, 0.1, 0.5, 'ng/mL']],
            ['PT', ['both', 0, 999, 11, 15, 'sec']],
            ['Random Blood Sugar (RBS)', ['both', 0, 999, 75, 150, 'mg/dL']],
            ['RF titer', ['both', 0, 999, 0, 30, 'IU/mL']],
            ['Rubella IgG', ['both', 0, 999, 0, 10, 'IU/mL']],
            ['Rubella IgM', ['both', 0, 999, 0, 10, 'IU/mL']],
            ['Sodium ( Na+ )', ['both', 0, 999, 135, 145, 'mmol/L']],
            ['Thyroglobulin (TG)', ['both', 0, 999, 1.4, 78, 'ng/mL']],
            ['Tissue transglutaminase (Anti-tTG IgA)', ['both', 0, 999, 0, 10, 'U/mL']],
            ['Tissue transglutaminase (Anti-tTG IgG)', ['both', 0, 999, 0, 10, 'U/mL']],
            ['Total IgE', ['both', 0, 999, 0, 100, 'IU/mL']],
            ['Total iron binding capacity (TIBC)', ['both', 0, 999, 250, 400, 'µg/dL']],
            ['Total Protein', ['both', 0, 999, 6, 8, 'g/dL']],
            ['Total PSA', ['male', 18, 999, 0, 4.0, 'ng/mL']],
            ['TOXO IgG', ['both', 0, 999, 0, 1.0, 'IU/mL']],
            ['TOXO IgM', ['both', 0, 999, 0, 1.0, 'IU/mL']],
            ['Transferrin', ['both', 0, 999, 2.0, 4.0, 'g/L']],
            ['Triglycerides', ['both', 18, 999, 60, 160, 'mg/dL']],
            ['Urea', ['both', 0, 999, 16.5, 46.5, 'mg/dL']],
            ['Urea Breath Test (UBT)', ['both', 0, 999, 0, 4.0, 'DOB']],
            ['Vitamin D', ['both', 0, 999, 30, 100, 'ng/mL']],
            ['Vitamin-B12', ['both', 0, 999, 180, 1000, 'pg/mL']],
            ['VLDL-Cholesterol', ['both', 0, 999, 7, 32, 'mg/dL']],
            ['Zinc', ['both', 0, 999, 46, 150, 'µg/dL']],
        ];

        // إضافة القيم الرقمية
        foreach ($numericTests as $testData) {
            $this->addReference($testData[0], $testData[1]);
        }

        // القيم حسب الجنس
        $this->addGenderSpecific('Calcitonin', [
            ['male', 18, 999, 8.4, 19, 'pg/mL'],
            ['female', 18, 999, 5.0, 14, 'pg/mL']
        ]);

        $this->addGenderSpecific('creatine phosphokinase CK (CPK)', [
            ['male', 18, 999, 40, 200, 'U/L'],
            ['female', 18, 999, 30, 150, 'U/L']
        ]);

        $this->addGenderSpecific('Creatinine', [
            ['male', 18, 999, 0.6, 1.2, 'mg/dL'],
            ['female', 18, 999, 0.5, 0.9, 'mg/dL']
        ]);

        $this->addGenderSpecific('Ferritin', [
            ['male', 18, 999, 20, 300, 'ng/mL'],
            ['female', 18, 999, 15, 200, 'ng/mL']
        ]);

        $this->addGenderSpecific('Hemoglobin (Hb)', [
            ['male', 18, 999, 13.1, 16.7, 'g/dL'],
            ['female', 18, 999, 12, 15.1, 'g/dL']
        ]);

        $this->addGenderSpecific('Iron', [
            ['male', 18, 999, 78, 178, 'µg/dL'],
            ['female', 18, 999, 56, 157, 'µg/dL']
        ]);

        $this->addGenderSpecific('PCV', [
            ['male', 18, 999, 39.9, 51.0, '%'],
            ['female', 18, 999, 36.4, 46.0, '%']
        ]);

        $this->addGenderSpecific('Prolactin', [
            ['male', 18, 999, 4.0, 15.2, 'ng/mL'],
            ['female', 18, 999, 4.8, 23.3, 'ng/mL']
        ]);

        $this->addGenderSpecific('Testosterone', [
            ['male', 18, 999, 2.4, 9.5, 'ng/mL'],
            ['female', 18, 999, 0.08, 0.6, 'ng/mL']
        ]);

        $this->addGenderSpecific('Testosterone (Free)', [
            ['both', 21, 49, 5.01, 27.78, 'pg/mL']
        ]);

        $this->addGenderSpecific('Uric Acid', [
            ['male', 18, 999, 2, 7, 'mg/dL'],
            ['female', 18, 999, 2, 6, 'mg/dL']
        ]);

        // القيم حسب العمر
        $this->addAgeSpecific('DHEA-S', [
            ['both', 1, 4, 0.47, 19.4, 'µg/dL', 'Children 1-4 years'],
            ['both', 5, 9, 2.8, 85.2, 'µg/dL', 'Children 5-9 years'],
            ['both', 10, 14, 24, 247, 'µg/dL', '10-14 years'],
            ['both', 15, 19, 70.2, 492, 'µg/dL', '15-19 years'],
            ['both', 20, 24, 211, 492, 'µg/dL', '20-24 years'],
            ['both', 25, 34, 160, 449, 'µg/dL', '25-34 years'],
            ['both', 35, 44, 88.9, 427, 'µg/dL', '35-44 years'],
            ['both', 45, 54, 44.3, 331, 'µg/dL', '45-54 years'],
            ['both', 55, 64, 51.7, 295, 'µg/dL', '55-64 years'],
            ['both', 65, 74, 33.6, 249, 'µg/dL', '65-74 years'],
            ['both', 75, 999, 16.2, 123, 'µg/dL', '>74 years']
        ]);

        // TSH حسب العمر الدقيق
        $this->addAgeSpecific('TSH', [
            ['both', 0, 0.014, 0.7, 15.2, 'mIU/L', '0-5 days'],
            ['both', 0.016, 0.17, 0.7, 11.0, 'mIU/L', '6 days-2 months'],
            ['both', 0.25, 0.92, 0.7, 8.4, 'mIU/L', '3-11 months'],
            ['both', 1, 5, 0.7, 6.0, 'mIU/L', '1-5 years'],
            ['both', 6, 10, 0.6, 4.8, 'mIU/L', '6-10 years'],
            ['both', 11, 19, 0.5, 4.3, 'mIU/L', '11-19 years'],
            ['both', 20, 999, 0.3, 4.2, 'mIU/L', '≥20 years']
        ]);

        // T4 حسب العمر
        $this->addAgeSpecific('Thyroxine Test (T4)', [
            ['both', 0, 0.014, 5.0, 18.5, 'µg/dL', '0-5 days'],
            ['both', 0.016, 0.17, 5.4, 17.0, 'µg/dL', '6 days-2 months'],
            ['both', 0.25, 0.92, 5.7, 16.0, 'µg/dL', '3-11 months'],
            ['both', 1, 5, 6.0, 14.7, 'µg/dL', '1-5 years'],
            ['both', 6, 10, 6.0, 13.8, 'µg/dL', '6-10 years'],
            ['both', 11, 19, 5.9, 13.2, 'µg/dL', '11-19 years'],
            ['both', 20, 999, 4.5, 11.7, 'µg/dL', '>19 years']
        ]);

        // T3 حسب العمر
        $this->addAgeSpecific('Triiodothyronine (T3)', [
            ['both', 0, 0.014, 0.73, 2.88, 'ng/mL', '0-5 days'],
            ['both', 0.016, 0.17, 0.80, 2.75, 'ng/mL', '6 days-2 months'],
            ['both', 0.25, 0.92, 0.86, 2.65, 'ng/mL', '3-11 months'],
            ['both', 1, 5, 0.92, 2.48, 'ng/mL', '1-5 years'],
            ['both', 6, 10, 0.93, 2.31, 'ng/mL', '6-10 years'],
            ['both', 11, 19, 0.91, 2.18, 'ng/mL', '11-19 years'],
            ['both', 20, 999, 0.80, 2.00, 'ng/mL', '>19 years']
        ]);

        // Free T3 حسب العمر
        $this->addAgeSpecific('Free T3', [
            ['both', 0, 0.083, 2.7, 8.5, 'pg/mL', '0-1 month'],
            ['both', 0.084, 0.99, 3.4, 5.6, 'pg/mL', '1-<12 months'],
            ['both', 1, 13, 3.0, 5.1, 'pg/mL', '1-<14 years'],
            ['both', 14, 18, 3.3, 5.3, 'pg/mL', '14-<19 years'],
            ['both', 19, 999, 2.0, 4.4, 'pg/mL', '≥19 years']
        ]);

        // Pro-BNP حسب العمر
        $this->addAgeSpecific('proBNP', [
            ['both', 0, 16, 0, 83, 'pg/mL', '0-16 years'],
            ['both', 17, 75, 0, 125, 'pg/mL', '17-75 years'],
            ['both', 76, 999, 0, 450, 'pg/mL', '76-99 years']
        ]);

        // HOMA-IR حسب العمر
        $this->addAgeSpecific('HOMA-IR', [
            ['both', 0, 39, 1.8, 3.0, 'Index', '<40 years'],
            ['both', 40, 60, 2.2, 3.5, 'Index', '40-60 years']
        ]);

        // Cortisol حسب الوقت
        $this->addReference('Cortisol', ['both', 0, 999, 101.2, 535.7, 'nmol/L', 'AM (Morning)']);
        $this->addReference('Cortisol', ['both', 0, 999, 79.0, 477.8, 'nmol/L', 'PM (Evening)']);

        // FSH - التحاليل المعقدة
        $this->addGenderSpecific('FSH', [
            ['male', 18, 999, 1.7, 12, 'IU/L', 'Male'],
            ['female', 18, 40, 3.5, 12.5, 'IU/L', 'Follicular phase'],
            ['female', 18, 40, 4.7, 21.5, 'IU/L', 'Ovulation day'],
            ['female', 18, 40, 1.7, 7.7, 'IU/L', 'Luteal phase'],
            ['female', 45, 999, 25.8, 134.8, 'IU/L', 'Menopausal women']
        ]);

        // LH
        $this->addGenderSpecific('LH', [
            ['male', 18, 999, 1.1, 7, 'IU/L', 'Male'],
            ['female', 18, 40, 2.4, 12.6, 'IU/L', 'Follicular phase'],
            ['female', 18, 40, 14.0, 94.0, 'IU/L', 'Ovulation day'],
            ['female', 18, 40, 1.0, 11.4, 'IU/L', 'Luteal phase'],
            ['female', 45, 999, 7.7, 58.5, 'IU/L', 'Menopausal women']
        ]);

        // AMH
        $this->addReference('Anti-Mullerian Hormone (AMH)', ['female', 18, 40, 4.0, 6.8, 'ng/mL', 'Optimal fertility']);
        $this->addReference('Anti-Mullerian Hormone (AMH)', ['female', 18, 40, 2.2, 4.0, 'ng/mL', 'Satisfactory fertility']);

        // Insulin حسب الصيام
        $this->addReference('Insulin', ['both', 0, 999, 0, 25, 'µIU/mL', 'Fasting']);
        $this->addReference('Insulin', ['both', 0, 999, 18, 276, 'µIU/mL', '1 hour after glucose intake']);
        $this->addReference('Insulin', ['both', 0, 999, 16, 166, 'µIU/mL', '2 hour after glucose intake']);
        $this->addReference('Insulin', ['both', 0, 999, 0, 25, 'µIU/mL', '≥3 hour after glucose intake']);

        // Beta-HCG المعقد
        $this->addReference('Beta-HCG', ['male', 0, 999, 0, 2, 'mIU/mL', 'Male']);
        $this->addReference('Beta-HCG', ['female', 18, 999, 0, 5.0, 'mIU/mL', 'Non pregnant']);
        $this->addReference('Beta-HCG', ['female', 18, 999, 5, 50, 'mIU/mL', 'Pregnant: 1 week']);
        $this->addReference('Beta-HCG', ['female', 18, 999, 40, 1000, 'mIU/mL', 'Pregnant: 2 week']);
        $this->addReference('Beta-HCG', ['female', 18, 999, 100, 5000, 'mIU/mL', 'Pregnant: 3 week']);
        $this->addReference('Beta-HCG', ['female', 18, 999, 600, 10000, 'mIU/mL', 'Pregnant: 4 week']);
        $this->addReference('Beta-HCG', ['female', 18, 999, 1500, 100000, 'mIU/mL', 'Pregnant: 5-6 week']);
        $this->addReference('Beta-HCG', ['female', 18, 999, 16000, 200000, 'mIU/mL', 'Pregnant: 7-8 week']);
        $this->addReference('Beta-HCG', ['female', 18, 999, 12000, 300000, 'mIU/mL', 'Pregnant: 2-3 months']);
        $this->addReference('Beta-HCG', ['female', 18, 999, 24000, 55000, 'mIU/mL', 'Pregnant: 2nd trimester']);

        // Estradiol
        $this->addReference('Estradiol', ['male', 18, 999, 0, 62, 'pg/mL', 'Male']);
        $this->addReference('Estradiol', ['female', 18, 40, 12.5, 166, 'pg/mL', 'Follicular phase']);
        $this->addReference('Estradiol', ['female', 18, 40, 85, 498, 'pg/mL', 'Ovulatory phase']);
        $this->addReference('Estradiol', ['female', 18, 40, 43.8, 211, 'pg/mL', 'Luteal Phase']);
        $this->addReference('Estradiol', ['female', 45, 999, 0, 54.7, 'pg/mL', 'Postmenopausal']);
        $this->addReference('Estradiol', ['female', 18, 40, 966, 4404, 'pg/mL', 'Pregnant: 1st trimester']);
        $this->addReference('Estradiol', ['female', 18, 40, 7298, 16390, 'pg/mL', 'Pregnant: 2nd trimester']);
        $this->addReference('Estradiol', ['female', 18, 40, 10800, 40201, 'pg/mL', 'Pregnant: 3rd trimester']);

        // Progesterone
        $this->addReference('Progesterone', ['male', 18, 999, 0.23, 1.50, 'ng/mL', 'Male']);
        $this->addReference('Progesterone', ['female', 18, 40, 0.2, 1.5, 'ng/mL', 'Follicular phase']);
        $this->addReference('Progesterone', ['female', 18, 40, 0.8, 3.0, 'ng/mL', 'Ovulatory phase']);
        $this->addReference('Progesterone', ['female', 18, 40, 1.7, 27, 'ng/mL', 'Luteal phase']);
        $this->addReference('Progesterone', ['female', 45, 999, 0.1, 0.8, 'ng/mL', 'Postmenopausal']);
        $this->addReference('Progesterone', ['female', 18, 40, 3.24, 60.54, 'ng/mL', 'Pregnant: 1st trimester']);
        $this->addReference('Progesterone', ['female', 18, 40, 21.52, 104.58, 'ng/mL', 'Pregnant: 2nd trimester']);
        $this->addReference('Progesterone', ['female', 18, 40, 66.52, 367.64, 'ng/mL', 'Pregnant: 3rd trimester']);

        // التحاليل النصية (Options = Positive/Negative)
        $textTests = [
            'Blood Group (ABO)',
            'Brucella Test (Rose Bengal)',
            'Calprotectin (Stool)',
            'Covid -19 Ag',
            'CRP latex',
            'FOB (Fecal Occult Blood)',
            'H.Pylori Ab',
            'H.Pylori Ag',
            'HBeAb',
            'HBeAg',
            'HbsAb',
            'HBsAg',
            'HBV',
            'HCV',
            'Herpes Simplex I IgG (HSV)',
            'Herpes Simplex I IgM (HSV)',
            'Herpes Simplex II IgG (HSV)',
            'Herpes Simplex II IgM (HSV)',
            'HIV',
            'Pregnancy (Serum)',
            'RF latex',
            'Stool H.pylori (Ag)',
            'Syphilis',
            'Typhoid IgG',
            'Typhoid IgM',
        ];

        foreach ($textTests as $testName) {
            $this->addTextReference($testName, 'Negative');
        }
    }

    private function addReference($testName, $data)
    {
        $test = LabTest::where('name', 'LIKE', "%{$testName}%")->first();
        
        if (!$test) {
            $this->skippedCount++;
            return;
        }

        $gender = $data[0];
        $ageMin = $data[1];
        $ageMax = $data[2];
        $refMin = $data[3];
        $refMax = $data[4];
        $unit = $data[5] ?? null;
        $notes = $data[6] ?? null;

        $test->references()->create([
            'gender' => $gender,
            'age_min' => $ageMin,
            'age_max' => $ageMax,
            'ref_min' => $refMin,
            'ref_max' => $refMax,
            'unit' => $unit,
            'notes' => $notes,
        ]);

        $this->addedCount++;
    }

    private function addGenderSpecific($testName, $references)
    {
        $test = LabTest::where('name', 'LIKE', "%{$testName}%")->first();
        
        if (!$test) {
            $this->skippedCount++;
            return;
        }

        foreach ($references as $ref) {
            $test->references()->create([
                'gender' => $ref[0],
                'age_min' => $ref[1],
                'age_max' => $ref[2],
                'ref_min' => $ref[3],
                'ref_max' => $ref[4],
                'unit' => $ref[5] ?? null,
                'notes' => $ref[6] ?? null,
            ]);
            $this->addedCount++;
        }
    }

    private function addAgeSpecific($testName, $references)
    {
        $this->addGenderSpecific($testName, $references);
    }

    private function addTextReference($testName, $refText)
    {
        $test = LabTest::where('name', 'LIKE', "%{$testName}%")->first();
        
        if (!$test) {
            $this->skippedCount++;
            return;
        }

        $test->references()->create([
            'gender' => 'both',
            'age_min' => 0,
            'age_max' => 999,
            'ref_text' => $refText,
            'unit' => 'text',
        ]);

        $this->addedCount++;
    }
}
