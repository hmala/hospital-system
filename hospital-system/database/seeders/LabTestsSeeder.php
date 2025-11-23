<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LabTestsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tests = [
            // كيمياء سريرية
            ['code' => '01', 'name' => 'B. sugar', 'category' => 'كيمياء سريرية', 'unit' => 'mg/dL'],
            ['code' => '02', 'name' => 'B. urea', 'category' => 'كيمياء سريرية', 'unit' => 'mg/dL'],
            ['code' => '03', 'name' => 'S.creatinine', 'category' => 'كيمياء سريرية', 'unit' => 'mg/dL'],
            ['code' => '04', 'name' => 'Creat clearance', 'category' => 'كيمياء سريرية', 'unit' => 'mL/min'],
            ['code' => '05', 'name' => 'S. uric acid', 'category' => 'كيمياء سريرية', 'unit' => 'mg/dL'],
            ['code' => '06', 'name' => 'S.cholestrol', 'category' => 'كيمياء سريرية', 'unit' => 'mg/dL'],
            ['code' => '07', 'name' => 'S. triglyceride', 'category' => 'كيمياء سريرية', 'unit' => 'mg/dL'],
            ['code' => '08', 'name' => 'HDL', 'category' => 'كيمياء سريرية', 'unit' => 'mg/dL'],
            ['code' => '09', 'name' => 'TSB', 'category' => 'كيمياء سريرية', 'unit' => 'mg/dL'],
            ['code' => '010', 'name' => 'Direct S.bilirubin', 'category' => 'كيمياء سريرية', 'unit' => 'mg/dL'],
            ['code' => '011', 'name' => 'Total. S. Protein', 'category' => 'كيمياء سريرية', 'unit' => 'g/dL'],
            ['code' => '012', 'name' => 'S. albumin', 'category' => 'كيمياء سريرية', 'unit' => 'g/dL'],
            ['code' => '013', 'name' => 'S. calcium', 'category' => 'كيمياء سريرية', 'unit' => 'mg/dL'],
            ['code' => '014', 'name' => 'S.phosphorus', 'category' => 'كيمياء سريرية', 'unit' => 'mg/dL'],
            ['code' => '015', 'name' => 'S.copper', 'category' => 'كيمياء سريرية', 'unit' => 'µg/dL'],
            ['code' => '016', 'name' => 'S.potassium', 'category' => 'كيمياء سريرية', 'unit' => 'mEq/L'],
            ['code' => '017', 'name' => 'S.sodium', 'category' => 'كيمياء سريرية', 'unit' => 'mEq/L'],
            ['code' => '018', 'name' => 'S.chloride', 'category' => 'كيمياء سريرية', 'unit' => 'mEq/L'],
            ['code' => '019', 'name' => 'S.iron', 'category' => 'كيمياء سريرية', 'unit' => 'µg/dL'],
            ['code' => '020', 'name' => 'S. amylase', 'category' => 'كيمياء سريرية', 'unit' => 'U/L'],
            ['code' => '021', 'name' => 'T.I.B.C', 'category' => 'كيمياء سريرية', 'unit' => 'µg/dL'],
            ['code' => '022', 'name' => 'S.GOT', 'category' => 'كيمياء سريرية', 'unit' => 'U/L'],
            ['code' => '023', 'name' => 'S.GPT', 'category' => 'كيمياء سريرية', 'unit' => 'U/L'],
            ['code' => '024', 'name' => 'AlK.phosphatase', 'category' => 'كيمياء سريرية', 'unit' => 'U/L'],
            ['code' => '025', 'name' => 'Acid.phosphatase', 'category' => 'كيمياء سريرية', 'unit' => 'U/L'],
            ['code' => '026', 'name' => 'LDH', 'category' => 'كيمياء سريرية', 'unit' => 'U/L'],
            ['code' => '027', 'name' => 'CK', 'category' => 'كيمياء سريرية', 'unit' => 'U/L'],
            ['code' => '028', 'name' => 'Prot. Electroph', 'category' => 'كيمياء سريرية', 'unit' => 'g/dL'],
            ['code' => '029', 'name' => 'ABG (BLOOD GAS)', 'category' => 'كيمياء سريرية', 'unit' => 'mmol/L'],
            ['code' => '030', 'name' => 'غربلة حديثي الولادة', 'category' => 'كيمياء سريرية', 'unit' => ''],
            ['code' => '031', 'name' => 'HbA1c', 'category' => 'كيمياء سريرية', 'unit' => '%'],
            ['code' => '032', 'name' => 'Ferritin', 'category' => 'كيمياء سريرية', 'unit' => 'ng/mL'],
            ['code' => '033', 'name' => 'Folate', 'category' => 'كيمياء سريرية', 'unit' => 'ng/mL'],
            ['code' => '034', 'name' => 'Vit.B12', 'category' => 'كيمياء سريرية', 'unit' => 'pg/mL'],
            ['code' => '035', 'name' => 'Vit D', 'category' => 'كيمياء سريرية', 'unit' => 'ng/mL'],
            ['code' => '036', 'name' => 'Troponin', 'category' => 'كيمياء سريرية', 'unit' => 'ng/mL'],
            ['code' => '037', 'name' => 'S.troponin', 'category' => 'كيمياء سريرية', 'unit' => 'ng/mL'],
            ['code' => '038', 'name' => 'LDL', 'category' => 'كيمياء سريرية', 'unit' => 'mg/dL'],
            ['code' => '039', 'name' => 'Zinc', 'category' => 'كيمياء سريرية', 'unit' => 'µg/dL'],
            ['code' => '040', 'name' => 'Mg+', 'category' => 'كيمياء سريرية', 'unit' => 'mg/dL'],
            ['code' => '041', 'name' => 'C PEPTIDE', 'category' => 'كيمياء سريرية', 'unit' => 'ng/mL'],
            ['code' => '042', 'name' => 'CEA', 'category' => 'كيمياء سريرية', 'unit' => 'ng/mL'],
            ['code' => '043', 'name' => 'PR/CR', 'category' => 'كيمياء سريرية', 'unit' => ''],
            ['code' => '044', 'name' => 'LIPASE', 'category' => 'كيمياء سريرية', 'unit' => 'U/L'],
            ['code' => '045', 'name' => 'LOW DENSITY LIPO PROTEIN', 'category' => 'كيمياء سريرية', 'unit' => 'mg/dL'],
            ['code' => '046', 'name' => 'B.J', 'category' => 'كيمياء سريرية', 'unit' => ''],
            ['code' => '047', 'name' => 'CALCITONINE (PCT)', 'category' => 'كيمياء سريرية', 'unit' => 'pg/mL'],
            ['code' => '048', 'name' => 'LACTATE', 'category' => 'كيمياء سريرية', 'unit' => 'mmol/L'],
            ['code' => '049', 'name' => 'Tumor marker', 'category' => 'كيمياء سريرية', 'unit' => 'ng/mL'],

            // أمراض الدم
            ['code' => '11', 'name' => 'HB', 'category' => 'أمراض الدم', 'unit' => 'g/dL'],
            ['code' => '12', 'name' => 'PCV', 'category' => 'أمراض الدم', 'unit' => '%'],
            ['code' => '13', 'name' => 'WBC', 'category' => 'أمراض الدم', 'unit' => '/µL'],
            ['code' => '14', 'name' => 'Platelets count', 'category' => 'أمراض الدم', 'unit' => '/µL'],
            ['code' => '15', 'name' => 'Blood Film', 'category' => 'أمراض الدم', 'unit' => ''],
            ['code' => '16', 'name' => 'ESR', 'category' => 'أمراض الدم', 'unit' => 'mm/hr'],
            ['code' => '17', 'name' => 'G6PD', 'category' => 'أمراض الدم', 'unit' => ''],
            ['code' => '18', 'name' => 'Hb. electroph', 'category' => 'أمراض الدم', 'unit' => ''],
            ['code' => '19', 'name' => 'Sickling test', 'category' => 'أمراض الدم', 'unit' => ''],
            ['code' => '1101', 'name' => 'Bone Marrow', 'category' => 'أمراض الدم', 'unit' => ''],
            ['code' => '1102', 'name' => 'L.E. Phenom', 'category' => 'أمراض الدم', 'unit' => ''],
            ['code' => '1103', 'name' => 'Osmotic fragility', 'category' => 'أمراض الدم', 'unit' => ''],
            ['code' => '1104', 'name' => 'Bleeding time', 'category' => 'أمراض الدم', 'unit' => 'min'],
            ['code' => '1105', 'name' => 'Clotting time', 'category' => 'أمراض الدم', 'unit' => 'min'],
            ['code' => '1106', 'name' => 'Prothrombin time', 'category' => 'أمراض الدم', 'unit' => 'sec'],
            ['code' => '1107', 'name' => 'Partial Thromboplastin', 'category' => 'أمراض الدم', 'unit' => 'sec'],
            ['code' => '1108', 'name' => 'Fibrinogen degradation', 'category' => 'أمراض الدم', 'unit' => 'µg/mL'],
            ['code' => '1109', 'name' => 'Factor assay', 'category' => 'أمراض الدم', 'unit' => '%'],
            ['code' => '1110', 'name' => 'RDW', 'category' => 'أمراض الدم', 'unit' => '%'],
            ['code' => '1111', 'name' => 'MCV', 'category' => 'أمراض الدم', 'unit' => 'fL'],
            ['code' => '1112', 'name' => 'MCH', 'category' => 'أمراض الدم', 'unit' => 'pg'],
            ['code' => '1113', 'name' => 'MCHC', 'category' => 'أمراض الدم', 'unit' => 'g/dL'],
            ['code' => '1114', 'name' => 'RBC', 'category' => 'أمراض الدم', 'unit' => 'million/µL'],
            ['code' => '1115', 'name' => 'INR', 'category' => 'أمراض الدم', 'unit' => ''],
            ['code' => '1116', 'name' => 'CBC', 'category' => 'أمراض الدم', 'unit' => ''],
            ['code' => '1117', 'name' => 'HBSAG', 'category' => 'أمراض الدم', 'unit' => ''],
            ['code' => '1118', 'name' => 'D-dimer', 'category' => 'أمراض الدم', 'unit' => 'µg/mL'],
            ['code' => '1119', 'name' => 'RETTIC COUNT', 'category' => 'أمراض الدم', 'unit' => '%'],
            ['code' => '1120', 'name' => 'HB AIC', 'category' => 'أمراض الدم', 'unit' => '%'],
            ['code' => '1121', 'name' => 'TOXICITY TEST', 'category' => 'أمراض الدم', 'unit' => ''],
            ['code' => '1122', 'name' => 'اخرى', 'category' => 'أمراض الدم', 'unit' => ''],

            // مصرف الدم
            ['code' => '21', 'name' => 'Blood group', 'category' => 'مصرف الدم', 'unit' => ''],
            ['code' => '22', 'name' => 'Blood Compatibility', 'category' => 'مصرف الدم', 'unit' => ''],
            ['code' => '23', 'name' => 'Direct Coomb test', 'category' => 'مصرف الدم', 'unit' => ''],
            ['code' => '24', 'name' => 'Indirect Coomb test', 'category' => 'مصرف الدم', 'unit' => ''],
            ['code' => '25', 'name' => 'Rh-Antibody', 'category' => 'مصرف الدم', 'unit' => ''],

            // الطفيليات
            ['code' => '61', 'name' => 'G.S.E', 'category' => 'الطفيليات', 'unit' => ''],
            ['code' => '62', 'name' => 'UrineforSchistosomiasis', 'category' => 'الطفيليات', 'unit' => ''],
            ['code' => '63', 'name' => 'Malaria-Bi.flint', 'category' => 'الطفيليات', 'unit' => ''],
            ['code' => '64', 'name' => 'Leishmania smear', 'category' => 'الطفيليات', 'unit' => ''],
            ['code' => '65', 'name' => 'Scabies', 'category' => 'الطفيليات', 'unit' => ''],
            ['code' => '66', 'name' => 'H.pylori AG', 'category' => 'الطفيليات', 'unit' => ''],

            // الأحياء المجهرية
            ['code' => '31', 'name' => 'Direct smear', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '32', 'name' => 'Gram stain', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '33', 'name' => 'Culture', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '34', 'name' => 'Biochemical test', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '35', 'name' => 'Serotyping', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '36', 'name' => 'Sensitivity test', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '37', 'name' => 'Cholera culture', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '38', 'name' => 'KLB test', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '39', 'name' => 'Swab G.C', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '310', 'name' => 'AFB direct test', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '311', 'name' => 'Direct S.For Fungi', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '312', 'name' => 'Fungi culture', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '313', 'name' => 'Swab-Optheatres', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '314', 'name' => 'Whooping cough', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '315', 'name' => 'Resistant Bacteria', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '316', 'name' => 'Syphilis Elisa', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '317', 'name' => 'CRP', 'category' => 'الأحياء المجهرية', 'unit' => 'mg/L'],
            ['code' => '318', 'name' => 'Cary bliar', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '319', 'name' => 'OCCUT BLOOD (FOB)', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '320', 'name' => 'GALACTOMANNAN', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '321', 'name' => 'B-D-GLUCAN', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '322', 'name' => 'METHYLEN BLUE', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '323', 'name' => 'GIEMSA STAIN', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '324', 'name' => 'LNDIA INK', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '325', 'name' => 'H.pylor AB', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '326', 'name' => 'LATEX TEST', 'category' => 'الأحياء المجهرية', 'unit' => ''],
            ['code' => '327', 'name' => 'CELL COUNT', 'category' => 'الأحياء المجهرية', 'unit' => '/µL'],
            ['code' => '328', 'name' => 'BLOOD CULTURE', 'category' => 'الأحياء المجهرية', 'unit' => ''],

            // المناعة السريرية
            ['code' => '41', 'name' => 'Pregnancy test', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '42', 'name' => 'Widal test', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '43', 'name' => 'Brucella test', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '44', 'name' => 'ASOT', 'category' => 'المناعة السريرية', 'unit' => 'IU/mL'],
            ['code' => '45', 'name' => 'Rheumatoid test', 'category' => 'المناعة السريرية', 'unit' => 'IU/mL'],
            ['code' => '46', 'name' => 'V.D.R.L test', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '47', 'name' => 'T.P.H.A', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '48', 'name' => 'Echinoccws test', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '49', 'name' => 'Antinuclear factor', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '410', 'name' => 'S.L.erythem', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '411', 'name' => 'InfMononucl.', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '412', 'name' => 'Toxoplasm. test', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '413', 'name' => 'Chlamydia test', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '414', 'name' => 'Kalazar test', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '415', 'name' => 'H-Pylori AB-test', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '416', 'name' => 'H.pylor AB', 'category' => 'المناعة السريرية', 'unit' => ''],

            // فيروسات
            ['code' => '71', 'name' => 'HbsAg', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '72', 'name' => 'HbsAg. Comp. test', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '73', 'name' => 'HBAgConfirmetery.test', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '74', 'name' => 'Confirm.(valid)test', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '75', 'name' => 'Anit-HCV', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '76', 'name' => 'Anti-HAV', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '77', 'name' => 'Anit-HEV(IgM)', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '78', 'name' => 'Anit-HEV(IgG)', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '79', 'name' => 'Anit-HIV(1/2)', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '710', 'name' => 'Weestern Blot', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '711', 'name' => 'HIVComp. test', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '712', 'name' => 'Anti-CMV', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '713', 'name' => 'Anti-Rubella', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '714', 'name' => 'Anit-Measles', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '715', 'name' => 'Rota virus', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '716', 'name' => 'H.S.V1/H.S.V2', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '717', 'name' => 'Polio (type1,2,3)', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '718', 'name' => 'Epstein-BarrV.', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '719', 'name' => 'Haemorrhage', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '720', 'name' => 'Hbe Ag', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '721', 'name' => 'Anti Hbe Ab', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '722', 'name' => 'HIV viral load', 'category' => 'فيروسات', 'unit' => 'copies/mL'],
            ['code' => '723', 'name' => 'Influenza A virus', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '724', 'name' => 'Coronavirus', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '725', 'name' => 'CMV westerin blot', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '726', 'name' => 'PCR HBV', 'category' => 'فيروسات', 'unit' => 'IU/mL'],
            ['code' => '727', 'name' => 'PCR HCV', 'category' => 'فيروسات', 'unit' => 'IU/mL'],
            ['code' => '728', 'name' => 'HCV genotyping', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '729', 'name' => 'influenza type Bviruses', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '730', 'name' => 'Anti-HCV(Westernbiot)', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '731', 'name' => 'Anti-HBc TotaI', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '732', 'name' => 'Anti-HBc IgM', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '733', 'name' => 'Anti-HBs', 'category' => 'فيروسات', 'unit' => 'mIU/mL'],
            ['code' => '734', 'name' => 'Anti-HIV1/2(Westernblot)', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '735', 'name' => 'HBV genotype', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '736', 'name' => 'Anti VZV', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '737', 'name' => 'HAV', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '738', 'name' => 'HBS', 'category' => 'فيروسات', 'unit' => ''],
            ['code' => '739', 'name' => 'HPV', 'category' => 'فيروسات', 'unit' => ''],

            // هرمونات
            ['code' => '81', 'name' => 'T3', 'category' => 'هرمونات', 'unit' => 'ng/dL'],
            ['code' => '82', 'name' => 'T4', 'category' => 'هرمونات', 'unit' => 'µg/dL'],
            ['code' => '83', 'name' => 'FreeT3', 'category' => 'هرمونات', 'unit' => 'pg/mL'],
            ['code' => '84', 'name' => 'FreeT4', 'category' => 'هرمونات', 'unit' => 'ng/dL'],
            ['code' => '85', 'name' => 'TSH', 'category' => 'هرمونات', 'unit' => 'µIU/mL'],
            ['code' => '86', 'name' => 'FSH', 'category' => 'هرمونات', 'unit' => 'mIU/mL'],
            ['code' => '87', 'name' => 'Prolactin H.', 'category' => 'هرمونات', 'unit' => 'ng/mL'],
            ['code' => '88', 'name' => 'Luteining H.', 'category' => 'هرمونات', 'unit' => 'mIU/mL'],
            ['code' => '89', 'name' => 'Progester', 'category' => 'هرمونات', 'unit' => 'ng/mL'],
            ['code' => '810', 'name' => 'Testosterone', 'category' => 'هرمونات', 'unit' => 'ng/dL'],
            ['code' => '811', 'name' => 'Estradioi H.', 'category' => 'هرمونات', 'unit' => 'pg/mL'],
            ['code' => '812', 'name' => 'ACTH', 'category' => 'هرمونات', 'unit' => 'pg/mL'],
            ['code' => '813', 'name' => 'Growth H.', 'category' => 'هرمونات', 'unit' => 'ng/mL'],
            ['code' => '814', 'name' => 'Cortisol', 'category' => 'هرمونات', 'unit' => 'µg/dL'],
            ['code' => '815', 'name' => 'Human Chorionic. Gonadotrophin Hormone.', 'category' => 'هرمونات', 'unit' => 'mIU/mL'],
            ['code' => '816', 'name' => 'PTH', 'category' => 'هرمونات', 'unit' => 'pg/mL'],
            ['code' => '817', 'name' => 'INSOLIN', 'category' => 'هرمونات', 'unit' => 'µIU/mL'],
            ['code' => '818', 'name' => 'INSUIN', 'category' => 'هرمونات', 'unit' => 'µIU/mL'],
            ['code' => '819', 'name' => 'ANTI THYROGLOBULIN AB', 'category' => 'هرمونات', 'unit' => 'IU/mL'],
            ['code' => '820', 'name' => 'ANTI T.G', 'category' => 'هرمونات', 'unit' => 'IU/mL'],
            ['code' => '821', 'name' => 'اخرى other', 'category' => 'هرمونات', 'unit' => ''],

            // المناعة السريرية
            ['code' => '91', 'name' => 'Immunoglobulin Electrophoresis', 'category' => 'المناعة السريرية', 'unit' => 'g/dL'],
            ['code' => '92', 'name' => 'IgG', 'category' => 'المناعة السريرية', 'unit' => 'mg/dL'],
            ['code' => '93', 'name' => 'IgA', 'category' => 'المناعة السريرية', 'unit' => 'mg/dL'],
            ['code' => '94', 'name' => 'IgM', 'category' => 'المناعة السريرية', 'unit' => 'mg/dL'],
            ['code' => '95', 'name' => 'IgE', 'category' => 'المناعة السريرية', 'unit' => 'IU/mL'],
            ['code' => '96', 'name' => 'DNA Antibodies', 'category' => 'المناعة السريرية', 'unit' => 'IU/mL'],
            ['code' => '97', 'name' => 'Anti-reticulin Ab.', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '98', 'name' => 'Anti-globulinAb', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '99', 'name' => 'Anti-parietal cell(Ab)', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '910', 'name' => 'Anti-spermAb', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '911', 'name' => 'Anti-neutiophil Cytoplasmic Ab', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '912', 'name' => 'Anti-cardiolipin .Ab', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '913', 'name' => 'Antiphosphotidyl Serine Anti', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '914', 'name' => 'Antitransglut.Anti', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '915', 'name' => 'Crethedia', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '916', 'name' => 'Histon Ab', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '917', 'name' => 'ENA', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '918', 'name' => 'Tumor Markers', 'category' => 'المناعة السريرية', 'unit' => 'ng/mL'],
            ['code' => '919', 'name' => 'A1 Antitrypsin', 'category' => 'المناعة السريرية', 'unit' => 'mg/dL'],
            ['code' => '920', 'name' => 'LC IgM', 'category' => 'المناعة السريرية', 'unit' => 'mg/dL'],
            ['code' => '921', 'name' => 'LC IgA', 'category' => 'المناعة السريرية', 'unit' => 'mg/dL'],
            ['code' => '922', 'name' => 'LC IgG', 'category' => 'المناعة السريرية', 'unit' => 'mg/dL'],
            ['code' => '923', 'name' => 'C3', 'category' => 'المناعة السريرية', 'unit' => 'mg/dL'],
            ['code' => '924', 'name' => 'C4', 'category' => 'المناعة السريرية', 'unit' => 'mg/dL'],
            ['code' => '925', 'name' => 'b2microgolobulin', 'category' => 'المناعة السريرية', 'unit' => 'mg/L'],
            ['code' => '926', 'name' => 'Cerioloplasmin Ab', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '927', 'name' => 'ANA for S.L.E', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '928', 'name' => 'SMAfor chronic', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '929', 'name' => 'Thyroid stimulating', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '930', 'name' => 'Active', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '931', 'name' => 'Anti-gliadin antibodies', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '932', 'name' => 'AMA', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '933', 'name' => 'CCP', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '934', 'name' => 'LKM/1', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '935', 'name' => 'SLA/LP', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '936', 'name' => 'TPO', 'category' => 'المناعة السريرية', 'unit' => 'IU/mL'],
            ['code' => '937', 'name' => 'T.G', 'category' => 'المناعة السريرية', 'unit' => 'IU/mL'],
            ['code' => '938', 'name' => 'RF AB', 'category' => 'المناعة السريرية', 'unit' => 'IU/mL'],
            ['code' => '939', 'name' => 'MPO AB', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '940', 'name' => 'PR3 AB', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '941', 'name' => 'ANTI SSA AB', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '942', 'name' => 'ANTI SSB AB', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '943', 'name' => 'ANTI SCL.70 AB', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '944', 'name' => 'ANTI RNP AB', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '945', 'name' => 'ANTI JO.1 AB', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '946', 'name' => 'ANTI SMITH AB', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '947', 'name' => 'ANTI CENTROMERE', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '948', 'name' => 'ANTI B2-GLYCOPROTINE AB lgG', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '949', 'name' => 'ANTI B2-GLYCOPROTINE AB lgM', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '950', 'name' => 'ANTI PHOSOLIPID AB lgG', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '951', 'name' => 'ANTI PHOSOLIPID AB lgM', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '952', 'name' => 'ANTI PARIETAL AB', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '953', 'name' => 'INNTRINSIC FACTOR', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '954', 'name' => 'ANTI ENDOMYSIAL AB lgG', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '955', 'name' => 'ANTI THYROGLOBULIN AB', 'category' => 'المناعة السريرية', 'unit' => 'IU/mL'],
            ['code' => '956', 'name' => 'ANTI ENDOMYSIAL AB lgA', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '957', 'name' => 'CEA', 'category' => 'المناعة السريرية', 'unit' => 'ng/mL'],
            ['code' => '958', 'name' => 'ALFA FP', 'category' => 'المناعة السريرية', 'unit' => 'ng/mL'],
            ['code' => '959', 'name' => 'CA19.9', 'category' => 'المناعة السريرية', 'unit' => 'U/mL'],
            ['code' => '960', 'name' => 'CA15-3', 'category' => 'المناعة السريرية', 'unit' => 'U/mL'],
            ['code' => '961', 'name' => 'FOOD PROFILE ALLERGE', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '962', 'name' => 'ATOPY PROFILE ALLERGE', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '963', 'name' => 'INHALATION PROFILE ALLERGE', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '964', 'name' => 'CA125', 'category' => 'المناعة السريرية', 'unit' => 'U/mL'],
            ['code' => '965', 'name' => 'CA123', 'category' => 'المناعة السريرية', 'unit' => 'U/mL'],
            ['code' => '966', 'name' => 'PAEDITRIC PROFILE ALLERGE', 'category' => 'المناعة السريرية', 'unit' => ''],
            ['code' => '967', 'name' => 'CA125', 'category' => 'المناعة السريرية', 'unit' => 'U/mL'],
            ['code' => '968', 'name' => 'CA123', 'category' => 'المناعة السريرية', 'unit' => 'U/mL'],
            ['code' => '969', 'name' => 'INSECT PROFILE ALLERGE TPSA', 'category' => 'المناعة السريرية', 'unit' => ''],

            // الخلايا
            ['code' => '1001', 'name' => 'Sputum Exam', 'category' => 'الخلايا', 'unit' => ''],
            ['code' => '1002', 'name' => 'Bronchial wash', 'category' => 'الخلايا', 'unit' => ''],
            ['code' => '1003', 'name' => 'Urine cytology', 'category' => 'الخلايا', 'unit' => ''],
            ['code' => '1004', 'name' => 'Body fluid', 'category' => 'الخلايا', 'unit' => ''],
            ['code' => '1005', 'name' => 'Vaginal & dcervical', 'category' => 'الخلايا', 'unit' => ''],
            ['code' => '1006', 'name' => 'Fine needle spiration', 'category' => 'الخلايا', 'unit' => ''],
            ['code' => '1007', 'name' => 'Br. nipple sccrction', 'category' => 'الخلايا', 'unit' => ''],
            ['code' => '1008', 'name' => 'Ulccr &Tumer Curette', 'category' => 'الخلايا', 'unit' => ''],
            ['code' => '1010', 'name' => 'FCM', 'category' => 'الخلايا', 'unit' => ''],
            ['code' => '1100', 'name' => 'BRONCHIL BRUSH', 'category' => 'الخلايا', 'unit' => ''],
            ['code' => '1110', 'name' => 'POST BRONCHIAL SPNTUM', 'category' => 'الخلايا', 'unit' => ''],

            // متفرقة
            ['code' => '2001', 'name' => 'G.U.E', 'category' => 'متفرقة', 'unit' => ''],
            ['code' => '2002', 'name' => 'C.S.F.', 'category' => 'متفرقة', 'unit' => ''],
            ['code' => '2003', 'name' => 'S.F.A', 'category' => 'متفرقة', 'unit' => ''],
            ['code' => '2004', 'name' => 'Post-coital test', 'category' => 'متفرقة', 'unit' => ''],
            ['code' => '2005', 'name' => 'CMS', 'category' => 'متفرقة', 'unit' => ''],
            ['code' => '2006', 'name' => 'Water', 'category' => 'متفرقة', 'unit' => ''],
            ['code' => '2007', 'name' => 'Food', 'category' => 'متفرقة', 'unit' => ''],
            ['code' => '2008', 'name' => 'NLA for kidney Transplantation', 'category' => 'متفرقة', 'unit' => ''],
            ['code' => '2009', 'name' => 'Bone Marrow transplant', 'category' => 'متفرقة', 'unit' => ''],
            ['code' => '2010', 'name' => 'Chromosomal study', 'category' => 'متفرقة', 'unit' => ''],
            ['code' => '2011', 'name' => 'Alpha Thalassemia', 'category' => 'متفرقة', 'unit' => ''],
            ['code' => '2012', 'name' => 'Beta Thalassemia', 'category' => 'متفرقة', 'unit' => ''],

            // أخرى
            ['code' => '3001', 'name' => 'MARKER TEST', 'category' => 'أخرى', 'unit' => ''],
            ['code' => '3002', 'name' => 'AUTICENTROMERE', 'category' => 'أخرى', 'unit' => ''],
            ['code' => '3003', 'name' => 'AUTI_EUDOUY CIM', 'category' => 'أخرى', 'unit' => ''],
            ['code' => '3004', 'name' => 'BILE', 'category' => 'أخرى', 'unit' => ''],
            ['code' => '3005', 'name' => 'اخرى other', 'category' => 'أخرى', 'unit' => ''],
        ];

        DB::table('lab_tests')->truncate();

        // Ensure codes are unique in the array before inserting to avoid
        // database unique-constraint violations. If a duplicate code is
        // found, assign a fallback numeric code starting at 10000 and
        // incrementing until unique.
        $usedCodes = [];
        $fallback = 10000;

        foreach ($tests as &$test) {
            $code = (string) $test['code'];

            if (isset($usedCodes[$code])) {
                // find next unused fallback
                while (isset($usedCodes[(string) $fallback])) {
                    $fallback++;
                }
                $test['code'] = (string) $fallback;
                $usedCodes[(string) $fallback] = true;
                $fallback++;
            } else {
                $usedCodes[$code] = true;
            }
        }
        unset($test);

        foreach ($tests as $test) {
            DB::table('lab_tests')->insert($test);
        }
    }
}
