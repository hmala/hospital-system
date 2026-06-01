<?php

namespace Database\Seeders;

use App\Models\LabTest;
use App\Models\Package;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class LabTestsSeeder extends Seeder
{
    public function run(): void
    {
        LabTest::truncate();
        Package::truncate();
        DB::table('package_lab_test')->truncate();

        $this->insertLabTestsData();
    }
    
    private function insertLabTestsData()
    {
        $data = $this->getLabTestsData();
        
        foreach ($data as $item) {
            $baseCode = $this->generateCode($item[2]);
            $code = $baseCode;
            $counter = 1;
            
            while (LabTest::where('code', $code)->exists()) {
                $code = $baseCode . '_' . $counter;
                $counter++;
            }
            
            if (trim($item[1]) === 'حزم') {
                Package::create([
                    'name' => $item[2],
                    'code' => $code,
                    'description' => $item[3] ?: $item[2],
                    'price' => is_numeric($item[4]) ? $item[4] : 0,
                    'is_active' => true,
                ]);
            } else {
                LabTest::create([
                    'main_category' => $item[0],
                    'subcategory' => $item[1],
                    'name' => $item[2],
                    'code' => $code,
                    'description' => $item[3] ?: $item[2],
                    'unit' => $item[5] ?? null,
                    'is_active' => true,
                    'price' => is_numeric($item[4]) ? $item[4] : 0,
                ]);
            }
        }
    }
    
    private function generateCode($name)
    {
        $code = strtoupper(preg_replace('/[^A-Z0-9]+/i', '_', $name));
        $code = trim($code, '_');
        return $code ?: ('CODE_' . substr(md5($name), 0, 8));
    }
    
    private function getLabTestsData()
    {
        // [main_category, subcategory, name, description, price, unit]
        return [
            ['المختبر', 'Biochemistry', '(Free T4) free thyroxine', '(Free T4) free thyroxine', '20000', 'pmol/L'],
            ['المختبر', 'Biochemistry', '(Mg) Magnesium', '(Mg) Magnesium', '20000', '1.82 - 2.43'],
            ['المختبر', 'Haematology', 'ABG', 'ABG', '50000', 'Rebort'],
            ['المختبر', 'Haematology', 'Activated Partial Thromboplastin Time (APTT)', 'Activated Partial Thromboplastin Time (APTT)', '15000', 'Sec'],
            ['المختبر', 'Biochemistry', 'Adrenocorticotropic Hormone (ACTH)', 'Adrenocorticotropic Hormone (ACTH)', '25000', 'pg/mI'],
            ['المختبر', 'Biochemistry', 'Albumin', 'Albumin', '10000', 'g/dL'],
            ['المختبر', 'Biochemistry', 'Alkaline Phosphatase(ALP)', 'Alkaline Phosphatase(ALP)', '10000', 'U/L'],
            ['المختبر', 'Biochemistry', 'ALT/GPT', 'ALT/GPT', '10000', 'U/L'],
            ['المختبر', 'Immunology', 'AMA (Antimitochondrial Antibody)', 'AMA (Antimitochondrial Antibody)', '30000', 'AU/mL'],
            ['المختبر', 'Biochemistry', 'Amylase', 'Amylase', '20000', 'U/L'],
            ['المختبر', 'Immunology', 'ANA', 'ANA', '30000', 'U/mL'],
            ['المختبر', 'Immunology', 'ANA panel', 'ANA panel', '150000', 'Rebort'],
            ['المختبر', 'Biochemistry', 'Anti Diuretic hormone (ADH)', 'Anti Diuretic hormone (ADH)', '80000', 'pg/mL'],
            ['المختبر', 'Immunology', 'Anti-a-Gliadin IgA', 'Anti-a-Gliadin IgA', '25000', 'U/mL'],
            ['المختبر', 'Immunology', 'Anti-a-Gliadin IgG', 'Anti-a-Gliadin IgG', '25000', 'U/mL'],
            ['المختبر', 'Immunology', 'Anti-Cardiolipin IgG', 'Anti-Cardiolipin IgG', '15000', 'U/mL'],
            ['المختبر', 'Immunology', 'Anti-Cardiolipin IgM', 'Anti-Cardiolipin IgM', '15000', 'U/mL'],
            ['المختبر', 'Immunology', 'Anti-CCP', 'Anti-CCP', '20000', 'U/mL'],
            ['المختبر', 'Biochemistry', 'Anti-Mullerian Hormone (AMH)', 'Anti-Mullerian Hormone (AMH)', '60000', 'ng/mL'],
            ['المختبر', 'Immunology', 'Anti-Peroxidase (Anti-TPO)', 'Anti-Peroxidase (Anti-TPO)', '25000', 'U/mL'],
            ['المختبر', 'Immunology', 'Anti-Thyroglobulin (Anti TG)', 'Anti-Thyroglobulin (Anti TG)', '25000', 'IU/mL'],
            ['المختبر', 'Serology', 'ASOT', 'ASOT', '10000', 'IU/mL'],
            ['المختبر', 'Biochemistry', 'AST/GOT', 'AST/GOT', '10000', 'U/L'],
            ['المختبر', 'Biochemistry', 'Beta-HCG', 'Beta-HCG', '25000', 'μIU/mL'],
            ['المختبر', 'Biochemistry', 'Bilirubin (Direct)', 'Bilirubin (Direct)', '10000', 'mg/dl'],
            ['المختبر', 'Biochemistry', 'Bilirubin (Indirect)', 'Bilirubin (Indirect)', '0', 'mg/dl'],
            ['المختبر', 'Biochemistry', 'Bilirubin (Total)', 'Bilirubin (Total)', '10000', 'mg/dl'],
            ['المختبر', 'Haematology', 'Bleeding Time', 'Bleeding Time', '5000', 'Min'],
            ['المختبر', 'Microbiology', 'Blood Culture', 'Blood Culture', '65000', 'Rebort'],
            ['المختبر', 'Blood Bank', 'Blood Group (ABO)', 'Blood Group (ABO)', '5000', 'Text'],
            ['المختبر', 'Biochemistry', 'BNP', 'BNP', '60000', 'pg/mL'],
            ['المختبر', 'Serology', 'Brucella IgG', 'Brucella IgG', '20000', 'IU/mL'],
            ['المختبر', 'Serology', 'Brucella IgM', 'Brucella IgM', '20000', 'IU/mL'],
            ['المختبر', 'Serology', 'Brucella Test (Rose Bengal)', 'Brucella Test (Rose Bengal)', '10000', 'Test'],
            ['المختبر', 'Tumer marker', 'CA125', 'CA125', '25000', 'U/mL'],
            ['المختبر', 'Tumer marker', 'CA15-3', 'CA15-3', '25000', 'U/mL'],
            ['المختبر', 'Tumer marker', 'CA19.9', 'CA19.9', '25000', 'U/mL'],
            ['المختبر', 'Tumer marker', 'Calcitonin', 'Calcitonin', '50000', 'Pg/ml'],
            ['المختبر', 'Biochemistry', 'Calcium', 'Calcium', '10000', 'mg/dl'],
            ['المختبر', 'Serology', 'Calprotectin (Stool)', 'Calprotectin (Stool)', '60000', 'Text'],
            ['المختبر', 'Biochemistry', 'Cardiac Troponin I titer', 'Cardiac Troponin I titer', '25000', 'ng/L'],
            ['المختبر', 'Immunology', 'Cardiolipin IgA', 'Cardiolipin IgA', '20000', 'U/mL'],
            ['المختبر', 'Haematology', 'CBC 3 Diff.', 'CBC 3 Diff.', '20000', 'Rebort'],
            ['المختبر', 'Haematology', 'CBC 5 Diff.', 'CBC 5 Diff.', '20000', 'Rebort'],
            ['المختبر', 'Tumer marker', 'CEA', 'CEA', '25000', 'ng/mL'],
            ['المختبر', 'Biochemistry', 'Ceruloplasmin', 'Ceruloplasmin', '25000', 'mg/dL'],
            ['المختبر', 'Biochemistry', 'Chloride (Cl)', 'Chloride (Cl)', '10000', 'mmol/L'],
            ['المختبر', 'Biochemistry', 'Cholesterol', 'Cholesterol', '10000', 'mg/dL'],
            ['المختبر', 'Biochemistry', 'CK-MB', 'CK-MB', '25000', 'U/L'],
            ['المختبر', 'Immunology', 'CMV IgG', 'CMV IgG', '20000', 'U/mL'],
            ['المختبر', 'Immunology', 'CMV IgM', 'CMV IgM', '20000', 'U/mL'],
            ['المختبر', 'Immunology', 'Complement 3 (C3)', 'Complement 3 (C3)', '30000', 'g/L'],
            ['المختبر', 'Immunology', 'Complement 4 (C4)', 'Complement 4 (C4)', '30000', 'g/L'],
            ['المختبر', 'Biochemistry', 'Copper', 'Copper', '25000', 'ug/dL'],
            ['المختبر', 'Biochemistry', 'Cortisol', 'Cortisol', '25000', 'nmol/L'],
            ['المختبر', 'Virology', 'Covid -19 Ag', 'Covid -19 Ag', '100000', 'Text'],
            ['المختبر', 'Biochemistry', 'C-Peptide', 'C-Peptide', '20000', 'ng/mL'],
            ['المختبر', 'Serology', 'C-Reactive Protein titer (CRP)', 'C-Reactive Protein titer (CRP)', '20000', 'mg/L'],
            ['المختبر', 'Biochemistry', 'creatine phosphokinase CK (CPK)', 'creatine phosphokinase CK (CPK)', '25000', 'U/L'],
            ['المختبر', 'Biochemistry', 'Creatinine', 'Creatinine', '10000', 'mg/dl'],
            ['المختبر', 'Biochemistry', 'CRP latex', 'CRP latex', '10000', 'Text'],
            ['المختبر', 'Biochemistry', 'D-Dimer', 'D-Dimer', '30000', 'ug/mL'],
            ['المختبر', 'Biochemistry', 'DHEA-S', 'DHEA-S', '25000', 'µg/dL'],
            ['المختبر', 'Haematology', 'ESR', 'ESR', '5000', 'mm/hr'],
            ['المختبر', 'Biochemistry', 'Estradiol (E2)', 'Estradiol (E2)', '15000', 'pg/mL'],
            ['المختبر', 'Biochemistry', 'Fasting Blood Sugar (FBS)', 'Fasting Blood Sugar (FBS)', '5000', 'mg/dl'],
            ['المختبر', 'Biochemistry', 'Ferritin', 'Ferritin', '25000', 'ng/ml'],
            ['المختبر', 'Serology', 'FOB (Fecal Occult Blood)', 'FOB (Fecal Occult Blood)', '15000', 'Text'],
            ['المختبر', 'Tumer marker', 'Free PSA', 'Free PSA', '25000', 'ng/mL'],
            ['المختبر', 'Biochemistry', 'Free T3', 'Free T3', '20000', 'pmol/L'],
            ['المختبر', 'Biochemistry', 'FSH', 'FSH', '15000', 'mIU/mL'],
            ['المختبر', 'Microbiology', 'General Stool Examination (GSE)', 'General Stool Examination (GSE)', '5000', 'Rebort'],
            ['المختبر', 'Microbiology', 'General Urine Examination (GUE)', 'General Urine Examination (GUE)', '5000', 'Rebort'],
            ['المختبر', 'Biochemistry', 'Globulin', 'Globulin', '10000', 'g/dL'],
            ['المختبر', 'Serology', 'H.Pylori Ab', 'H.Pylori Ab', '10000', 'Text'],
            ['المختبر', 'Serology', 'H.Pylori Ag', 'H.Pylori Ag', '10000', 'Text'],
            ['المختبر', 'Haematology', 'HbA1c', 'HbA1c', '20000', '%'],
            ['المختبر', 'Virology', 'HBeAb', 'HBeAb', '15000', 'Text'],
            ['المختبر', 'Virology', 'HBeAg', 'HBeAg', '15000', 'Text'],
            ['المختبر', 'Virology', 'HbsAb', 'HbsAb', '15000', 'Text'],
            ['المختبر', 'Virology', 'HBsAg', 'HBsAg', '10000', 'Text'],
            ['المختبر', 'Virology', 'HBV', 'HBV', '10000', 'Text'],
            ['المختبر', 'Virology', 'HCV', 'HCV', '10000', 'Text'],
            ['المختبر', 'Biochemistry', 'HDL-Cholesterol', 'HDL-Cholesterol', '10000', 'mg/dL'],
            ['المختبر', 'Haematology', 'Hemoglobin (Hb)', 'Hemoglobin (Hb)', '10000', 'g/dL'],
            ['المختبر', 'Immunology', 'Herpes Simplex I IgG (HSV)', 'Herpes Simplex I IgG (HSV)', '20000', 'Text'],
            ['المختبر', 'Immunology', 'Herpes Simplex I IgM (HSV)', 'Herpes Simplex I IgM (HSV)', '20000', 'Text'],
            ['المختبر', 'Immunology', 'Herpes Simplex II IgG (HSV)', 'Herpes Simplex II IgG (HSV)', '20000', 'Text'],
            ['المختبر', 'Immunology', 'Herpes Simplex II IgM (HSV)', 'Herpes Simplex II IgM (HSV)', '20000', 'Text'],
            ['المختبر', 'Virology', 'HIV', 'HIV', '10000', 'Text'],
            ['المختبر', 'Biochemistry', 'HOMA-IR', 'HOMA-IR', '50000', '%'],
            ['المختبر', 'Haematology', 'INR', 'INR', '0', '%'],
            ['المختبر', 'Biochemistry', 'Insulin', 'Insulin', '25000', 'U/ml'],
            ['المختبر', 'Biochemistry', 'Ionized Calcium', 'Ionized Calcium', '10000', 'mg/dL'],
            ['المختبر', 'Biochemistry', 'Iron', 'Iron', '25000', 'µg/dL'],
            ['المختبر', 'Biochemistry', 'Lactate Dehydrogenase (LDH)', 'Lactate Dehydrogenase (LDH)', '25000', 'U/L'],
            ['المختبر', 'Biochemistry', 'LDL-Cholesterol', 'LDL-Cholesterol', '0', 'mg/dL'],
            ['المختبر', 'Biochemistry', 'LH', 'LH', '15000', 'mIU/mL'],
            ['المختبر', 'Biochemistry', 'Lipase', 'Lipase', '20000', 'U/L'],
            ['المختبر', 'Immunology', 'Lupus Anticoagulant', 'Lupus Anticoagulant', '30000', 'Sec'],
            ['المختبر', 'Biochemistry', 'Myoglobin', 'Myoglobin', '20000', 'ug/mL'],
            ['المختبر', 'Biochemistry', 'OGTT', 'OGTT', '30000', 'Rebort'],
            ['المختبر', 'Biochemistry', 'Para Thyroid Hormone (PTH)', 'Para Thyroid Hormone (PTH)', '25000', 'pg/mL'],
            ['المختبر', 'Haematology', 'PCV', 'PCV', '5000', '%'],
            ['المختبر', 'Biochemistry', 'Phosphorus (P)', 'Phosphorus (P)', '20000', 'mg/dl'],
            ['المختبر', 'Biochemistry', 'Potassium ( K+ )', 'Potassium ( K+ )', '10000', 'mmol/L'],
            ['المختبر', 'Biochemistry', 'Pregnancy (Serum)', 'Pregnancy (Serum)', '10000', 'Text'],
            ['المختبر', 'Biochemistry', 'proBNP', 'proBNP', '60000', 'pg/mL'],
            ['المختبر', 'Biochemistry', 'Procalcitonin (PCT)', 'Procalcitonin (PCT)', '50000', 'ng/mL'],
            ['المختبر', 'Biochemistry', 'Progesterone', 'Progesterone', '15000', 'ng/mL'],
            ['المختبر', 'Biochemistry', 'Prolactin', 'Prolactin', '15000', 'ng/mL'],
            ['المختبر', 'Haematology', 'PT', 'PT', '15000', 'Sec'],
            ['المختبر', 'Biochemistry', 'Random Blood Sugar (RBS)', 'Random Blood Sugar (RBS)', '5000', 'mg/dl'],
            ['المختبر', 'Immunology', 'RF latex', 'RF latex', '10000', 'Text'],
            ['المختبر', 'Immunology', 'RF titer', 'RF titer', '25000', 'IU/ml'],
            ['المختبر', 'Immunology', 'Rubella IgG', 'Rubella IgG', '20000', 'U/ml'],
            ['المختبر', 'Immunology', 'Rubella IgM', 'Rubella IgM', '20000', 'U/ml'],
            ['المختبر', 'Microbiology', 'Seminal Fluid Analysis (SFA)', 'Seminal Fluid Analysis (SFA)', '15000', 'Rebort'],
            ['المختبر', 'Biochemistry', 'Sodium ( Na+ )', 'Sodium ( Na+ )', '10000', 'mmol/L'],
            ['المختبر', 'Serology', 'Stool H.pylori (Ag)', 'Stool H.pylori (Ag)', '15000', 'Text'],
            ['المختبر', 'Microbiology', 'Swab Culture', 'Swab Culture', '30000', 'Rebort'],
            ['المختبر', 'Serology', 'Syphilis', 'Syphilis', '25000', 'Text'],
            ['المختبر', 'Biochemistry', 'Testosterone', 'Testosterone', '15000', 'ng/ml'],
            ['المختبر', 'Biochemistry', 'Testosterone (Free)', 'Testosterone (Free)', '25000', 'pg/mL'],
            ['المختبر', 'Biochemistry', 'Thyroglobulin (TG)', 'Thyroglobulin (TG)', '25000', 'ng/mL'],
            ['المختبر', 'Biochemistry', 'Thyroxine Test (T4)', 'Thyroxine Test (T4)', '15000', 'µg/dL'],
            ['المختبر', 'Immunology', 'Tissue transglutaminase (Anti-tTG IgA)', 'Tissue transglutaminase (Anti-tTG IgA)', '25000', 'U/mL'],
            ['المختبر', 'Immunology', 'Tissue transglutaminase (Anti-tTG IgG)', 'Tissue transglutaminase (Anti-tTG IgG)', '25000', 'U/mL'],
            ['المختبر', 'Immunology', 'Total IgE', 'Total IgE', '25000', 'IU/ml'],
            ['المختبر', 'Biochemistry', 'Total iron binding capacity (TIBC)', 'Total iron binding capacity (TIBC)', '20000', 'µg/dL'],
            ['المختبر', 'Biochemistry', 'Total Protein', 'Total Protein', '10000', 'g/dL'],
            ['المختبر', 'Tumer marker', 'Total PSA', 'Total PSA', '25000', 'ng/mL'],
            ['المختبر', 'Biochemistry', 'TOXO IgG', 'TOXO IgG', '25000', 'Col'],
            ['المختبر', 'Biochemistry', 'TOXO IgM', 'TOXO IgM', '25000', 'Col'],
            ['المختبر', 'Biochemistry', 'Transferrin', 'Transferrin', '25000', 'g/L'],
            ['المختبر', 'Biochemistry', 'Triglycerides', 'Triglycerides', '10000', 'mg / dl'],
            ['المختبر', 'Biochemistry', 'Triiodothyronine (T3)', 'Triiodothyronine (T3)', '15000', 'ng/mL'],
            ['المختبر', 'Biochemistry', 'TSH', 'TSH', '15000', 'µIU/mL'],
            ['المختبر', 'Immunology', 'Typhoid IgG', 'Typhoid IgG', '10000', 'Text'],
            ['المختبر', 'Immunology', 'Typhoid IgM', 'Typhoid IgM', '10000', 'Text'],
            ['المختبر', 'Biochemistry', 'Urea', 'Urea', '10000', 'mg/dl'],
            ['المختبر', 'Biochemistry', 'Urea Breath Test (UBT)', 'Urea Breath Test (UBT)', '60000', 'DPM'],
            ['المختبر', 'Biochemistry', 'Uric Acid', 'Uric Acid', '10000', 'mg/dl'],
            ['المختبر', 'Biochemistry', 'Urine Culture', 'Urine Culture', '30000', 'Rebort'],
            ['المختبر', 'Haematology', 'VBG', 'VBG', '50000', 'Rebort'],
            ['المختبر', 'Biochemistry', 'Vitamin D', 'Vitamin D', '30000', 'ng/ml'],
            ['المختبر', 'Biochemistry', 'Vitamin-B12', 'Vitamin-B12', '30000', 'pg/ml'],
            ['المختبر', 'Biochemistry', 'VLDL-Cholesterol', 'VLDL-Cholesterol', '10000', 'mg/dL'],
            ['المختبر', 'Biochemistry', 'Zinc', 'Zinc', '25000', 'µg/dL'],
        ];
    }
}
