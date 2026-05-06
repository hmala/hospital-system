<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RadiologyTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // حذف جميع البيانات القديمة
        DB::table('radiology_types')->truncate();

        // البيانات الجديدة من الصورة - 144 صف كامل مع الأسماء الصحيحة
        $radiologyTypes = [
            // أشعة عادية (31 صف)
             ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => '(forarm (Lat) (Lt', 'code' => 'XR-001', 'base_price' => 30000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'اشعة القولون الملونة', 'code' => 'XR-002', 'base_price' => 125000, 'estimated_duration' => 30],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'اشعة البطن', 'code' => 'XR-003', 'base_price' => 30000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'فحص هشاشة العظام ديكسا سكان', 'code' => 'XR-004', 'base_price' => 50000, 'estimated_duration' => 20],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'اشعة الفسحة الخلفية للأنف', 'code' => 'XR-005', 'base_price' => 30000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => '( forarm (Ap + LAT', 'code' => 'XR-006', 'base_price' => 40000, 'estimated_duration' => 20],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => '(HAND xray (AP)(OBLIQUE', 'code' => 'XR-007', 'base_price' => 40000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'اشعة البطن في وضع الوقوف', 'code' => 'XR-008', 'base_price' => 30000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'whole spine', 'code' => 'XR-009', 'base_price' => 75000, 'estimated_duration' => 25],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'صوره لقطتين', 'code' => 'XR-010', 'base_price' => 40000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'تصوير الجهاز البولي الوريدي', 'code' => 'XR-011', 'base_price' => 150000, 'estimated_duration' => 30],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'تلوين الناسور', 'code' => 'XR-012', 'base_price' => 100000, 'estimated_duration' => 25],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'اشعة الصدر )ap(', 'code' => 'XR-013', 'base_price' => 25000, 'estimated_duration' => 10],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => '(forarm (Lat) (Rt', 'code' => 'XR-014', 'base_price' => 30000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'kneejoint skyline Lt', 'code' => 'XR-015', 'base_price' => 30000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'طباعة صورة', 'code' => 'XR-016', 'base_price' => 15000, 'estimated_duration' => 5],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'DORSO-LUMBAR (AP)(LAT', 'code' => 'XR-017', 'base_price' => 40000, 'estimated_duration' => 20],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'تصوير المثانة والاحليل اثناء التبول VCUG', 'code' => 'XR-018', 'base_price' => 150000, 'estimated_duration' => 35],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'اشعة الامعاء الدقيقة', 'code' => 'XR-019', 'base_price' => 150000, 'estimated_duration' => 30],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'dorso-lumbar', 'code' => 'XR-020', 'base_price' => 30000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => '(elbow (Lat) (Lt', 'code' => 'XR-021', 'base_price' => 30000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'تلوين الرحم', 'code' => 'XR-022', 'base_price' => 125000, 'estimated_duration' => 30],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'اشعة المعدة الملونة بالباريوم', 'code' => 'XR-023', 'base_price' => 125000, 'estimated_duration' => 30],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => '(Haned (Ap) (Rt', 'code' => 'XR-024', 'base_price' => 30000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => '(wrist (Ap) (Lt', 'code' => 'XR-025', 'base_price' => 30000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'صورة ثلاث لقطات', 'code' => 'XR-026', 'base_price' => 60000, 'estimated_duration' => 20],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'صوره واحد', 'code' => 'XR-027', 'base_price' => 25000, 'estimated_duration' => 10],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => '(wrist (Lat) (Lt', 'code' => 'XR-028', 'base_price' => 30000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => '(forarm (Ap) (Lt', 'code' => 'XR-029', 'base_price' => 30000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'اشعة عمليات', 'code' => 'XR-030', 'base_price' => 15000, 'estimated_duration' => 10],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'اشعة الامعاء الدقيقة الملونة', 'code' => 'XR-031', 'base_price' => 150000, 'estimated_duration' => 30],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => '(wrist (Ap) (Rt', 'code' => 'XR-032', 'base_price' => 30000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => '(wrist (Lat) (Rt', 'code' => 'XR-033', 'base_price' => 30000, 'estimated_duration' => 15],
    ['main_category' => 'أشعة', 'subcategory' => 'أشعة', 'name' => 'تصوير الاحليل الراجع(gur)', 'code' => 'XR-034', 'base_price' => 100000, 'estimated_duration' => 25],
    // ============================
//        الرنين (MRI)
// ============================

['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => '(2)knee unilat', 'code' => 'MRI-001', 'base_price' => 200000, 'estimated_duration' => 40],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'Arm soft tissue', 'code' => 'MRI-002', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'Buttock soft tissue', 'code' => 'MRI-003', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'Face soft tissue', 'code' => 'MRI-004', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'Foot soft tissue', 'code' => 'MRI-005', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'Forearm soft tissue', 'code' => 'MRI-006', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'Hand soft tissue', 'code' => 'MRI-007', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'knee joint', 'code' => 'MRI-008', 'base_price' => 200000, 'estimated_duration' => 40],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'Leg soft tissue', 'code' => 'MRI-009', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'MIR Neurographr', 'code' => 'MRI-010', 'base_price' => 200000, 'estimated_duration' => 45],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'MR Angio', 'code' => 'MRI-011', 'base_price' => 250000, 'estimated_duration' => 50],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'Neck soft tissue', 'code' => 'MRI-012', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'Sacroiliac Joints', 'code' => 'MRI-013', 'base_price' => 150000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'Soft tissue', 'code' => 'MRI-014', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'Thigh soft tissue', 'code' => 'MRI-015', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'الخلفي(الظهري)', 'code' => 'MRI-016', 'base_price' => 175000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'العمود الفقري', 'code' => 'MRI-017', 'base_price' => 300000, 'estimated_duration' => 45],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'القطنية والعجزية', 'code' => 'MRI-018', 'base_price' => 150000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'القلبي', 'code' => 'MRI-019', 'base_price' => 300000, 'estimated_duration' => 50],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'انتشار الاوكسجين في الجسم', 'code' => 'MRI-020', 'base_price' => 550000, 'estimated_duration' => 60],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'انتشار الاوكسجين في الرئة', 'code' => 'MRI-021', 'base_price' => 350000, 'estimated_duration' => 45],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'انسجة الحوض الرخوة', 'code' => 'MRI-022', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'انسجة الحوض الرخوة مع الصبغة', 'code' => 'MRI-023', 'base_price' => 240000, 'estimated_duration' => 40],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'تخدير', 'code' => 'MRI-024', 'base_price' => 50000, 'estimated_duration' => 15],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'تقرير المفراس والرنين', 'code' => 'MRI-025', 'base_price' => 50000, 'estimated_duration' => 10],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'تنويم الطفل بأشراف طبيب الاطفال', 'code' => 'MRI-026', 'base_price' => 35000, 'estimated_duration' => 20],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'جدار الصدر', 'code' => 'MRI-027', 'base_price' => 225000, 'estimated_duration' => 40],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'حقن صبغة', 'code' => 'MRI-028', 'base_price' => 50000, 'estimated_duration' => 10],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'رنين الانسجة الرخوة', 'code' => 'MRI-029', 'base_price' => 225000, 'estimated_duration' => 40],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'رنين الدماغ', 'code' => 'MRI-030', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'رنين القنوات الصفراوية', 'code' => 'MRI-031', 'base_price' => 250000, 'estimated_duration' => 40],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'رنين بطن وحوض مع الصبغة', 'code' => 'MRI-032', 'base_price' => 325000, 'estimated_duration' => 50],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'صبغة رنين / ضمان وزارة الداخلية', 'code' => 'MRI-033', 'base_price' => 75000, 'estimated_duration' => 15],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'طباعة فلم', 'code' => 'MRI-034', 'base_price' => 40000, 'estimated_duration' => 5],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'عنقي', 'code' => 'MRI-035', 'base_price' => 175000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'فحص البطن الديناميكي', 'code' => 'MRI-036', 'base_price' => 300000, 'estimated_duration' => 45],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'فحص البطن الروتيني', 'code' => 'MRI-037', 'base_price' => 250000, 'estimated_duration' => 40],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'فحص الدماغ الخاص', 'code' => 'MRI-038', 'base_price' => 250000, 'estimated_duration' => 40],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'فحص الدماغ الروتيني', 'code' => 'MRI-039', 'base_price' => 225000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'فحص الصدر الروتيني', 'code' => 'MRI-040', 'base_price' => 250000, 'estimated_duration' => 40],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'فحص الصدر النصفي', 'code' => 'MRI-041', 'base_price' => 225000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'فحص الكلى الروتيني', 'code' => 'MRI-042', 'base_price' => 275000, 'estimated_duration' => 40],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'فحص الناسور', 'code' => 'MRI-043', 'base_price' => 275000, 'estimated_duration' => 40],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'فحص اوردة الدماغ', 'code' => 'MRI-044', 'base_price' => 50000, 'estimated_duration' => 25],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'فحص شرايين الدماغ', 'code' => 'MRI-045', 'base_price' => 50000, 'estimated_duration' => 25],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'كتف جهة واحدة', 'code' => 'MRI-046', 'base_price' => 175000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'كتف جهتين', 'code' => 'MRI-047', 'base_price' => 300000, 'estimated_duration' => 50],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'مفصل الرسغ جهة واحدة', 'code' => 'MRI-048', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'مفصل الرسغ جهتين', 'code' => 'MRI-049', 'base_price' => 300000, 'estimated_duration' => 50],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'مفصل الركبة جهة واحدة', 'code' => 'MRI-050', 'base_price' => 175000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'مفصل الركبة جهتين', 'code' => 'MRI-051', 'base_price' => 250000, 'estimated_duration' => 50],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'مفصل الفك جهة واحدة', 'code' => 'MRI-052', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'مفصل الفك جهتين', 'code' => 'MRI-053', 'base_price' => 300000, 'estimated_duration' => 50],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'مفصل الكاحل جهة واحدة', 'code' => 'MRI-054', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'مفصل الكاحل جهتين', 'code' => 'MRI-055', 'base_price' => 300000, 'estimated_duration' => 50],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'مفصل المرفق جهة واحدة', 'code' => 'MRI-056', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'مفصل المرفق جهتين', 'code' => 'MRI-057', 'base_price' => 300000, 'estimated_duration' => 50],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'مفصل الورك جهة واحدة', 'code' => 'MRI-058', 'base_price' => 200000, 'estimated_duration' => 35],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'مفصل الورك جهتين', 'code' => 'MRI-059', 'base_price' => 200000, 'estimated_duration' => 50],
['main_category' => 'الرنين', 'subcategory' => 'الرنين', 'name' => 'رنين الثدي', 'code' => 'MRI-060', 'base_price' => 300000, 'estimated_duration' => 45],

          // ============================
//        سونار (US)
// ============================

['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'A Doppler (single Umbilical (baby', 'code' => 'US-001', 'base_price' => 50000, 'estimated_duration' => 20],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'Bilateral arterial & venous doppler', 'code' => 'US-002', 'base_price' => 200000, 'estimated_duration' => 45],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'D4 A Doppler with Umbilical (single baby)', 'code' => 'US-003', 'base_price' => 75000, 'estimated_duration' => 25],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'D4 A Doppler with Umbilical (Twin)', 'code' => 'US-004', 'base_price' => 150000, 'estimated_duration' => 35],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'D4 Anomaly scan with (single baby)', 'code' => 'US-005', 'base_price' => 75000, 'estimated_duration' => 30],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'Early pregnancy US (single (baby', 'code' => 'US-006', 'base_price' => 40000, 'estimated_duration' => 15],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'Mid trimester anomaly scan (single baby)', 'code' => 'US-007', 'base_price' => 50000, 'estimated_duration' => 25],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'Mid trimester anomaly scan (Twin)', 'code' => 'US-008', 'base_price' => 100000, 'estimated_duration' => 35],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => '(A Doppler (Twin Umbilical', 'code' => 'US-009', 'base_price' => 100000, 'estimated_duration' => 30],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => '(Early pregnancy US (Twin', 'code' => 'US-010', 'base_price' => 75000, 'estimated_duration' => 20],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => '(Pediatric hip US (Bilateral', 'code' => 'US-011', 'base_price' => 75000, 'estimated_duration' => 25],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'Bilateral arterial doppler', 'code' => 'US-012', 'base_price' => 150000, 'estimated_duration' => 40],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'Bilateral Breast US', 'code' => 'US-013', 'base_price' => 60000, 'estimated_duration' => 25],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'Bilateral venous doppler', 'code' => 'US-014', 'base_price' => 150000, 'estimated_duration' => 40],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'D (Twin4 Anomaly scan with', 'code' => 'US-015', 'base_price' => 150000, 'estimated_duration' => 40],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'Pediatric scrotal US', 'code' => 'US-016', 'base_price' => 35000, 'estimated_duration' => 15],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'Single limb arterial & venous doppler', 'code' => 'US-017', 'base_price' => 130000, 'estimated_duration' => 30],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'Single limb arterial doppler', 'code' => 'US-018', 'base_price' => 75000, 'estimated_duration' => 20],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'Single limb venous doppler', 'code' => 'US-019', 'base_price' => 75000, 'estimated_duration' => 20],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'Superficial US', 'code' => 'US-020', 'base_price' => 35000, 'estimated_duration' => 15],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'tru cut biopsy under US', 'code' => 'US-021', 'base_price' => 150000, 'estimated_duration' => 45],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'TVUS', 'code' => 'US-022', 'base_price' => 40000, 'estimated_duration' => 20],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'Unilateral Breast US', 'code' => 'US-023', 'base_price' => 35000, 'estimated_duration' => 15],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'الكشف المبكر لاورام الثدي', 'code' => 'US-024', 'base_price' => 45000, 'estimated_duration' => 20],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'دوبلر الاستشارية النسائية', 'code' => 'US-025', 'base_price' => 50000, 'estimated_duration' => 25],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'دوبلر الرقبة', 'code' => 'US-026', 'base_price' => 100000, 'estimated_duration' => 30],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'دوبلر الشريان السري او الرحمي', 'code' => 'US-027', 'base_price' => 50000, 'estimated_duration' => 20],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'دوبلر فحص الجنين dna dnoceS', 'code' => 'US-028', 'base_price' => 40000, 'estimated_duration' => 25],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'دوبلر للخصيتين', 'code' => 'US-029', 'base_price' => 45000, 'estimated_duration' => 20],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار البطن', 'code' => 'US-030', 'base_price' => 35000, 'estimated_duration' => 20],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار البطن والحوض', 'code' => 'US-031', 'base_price' => 50000, 'estimated_duration' => 30],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار الثدي الايسر', 'code' => 'US-032', 'base_price' => 35000, 'estimated_duration' => 15],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار الثدي الايمن', 'code' => 'US-033', 'base_price' => 35000, 'estimated_duration' => 15],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار الثدي الثنائي', 'code' => 'US-034', 'base_price' => 50000, 'estimated_duration' => 25],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار الحمل', 'code' => 'US-035', 'base_price' => 30000, 'estimated_duration' => 15],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار الحوض', 'code' => 'US-036', 'base_price' => 35000, 'estimated_duration' => 20],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار الرقبة', 'code' => 'US-037', 'base_price' => 50000, 'estimated_duration' => 20],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار الركبة', 'code' => 'US-038', 'base_price' => 50000, 'estimated_duration' => 20],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار الصدر', 'code' => 'US-039', 'base_price' => 30000, 'estimated_duration' => 15],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار الملون لشريان الرقبة', 'code' => 'US-040', 'base_price' => 80000, 'estimated_duration' => 25],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار دوبلر للحمل', 'code' => 'US-041', 'base_price' => 50000, 'estimated_duration' => 25],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار رأس', 'code' => 'US-042', 'base_price' => 45000, 'estimated_duration' => 20],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار ردهة', 'code' => 'US-043', 'base_price' => 50000, 'estimated_duration' => 25],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار سطحي', 'code' => 'US-044', 'base_price' => 30000, 'estimated_duration' => 15],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار للغدة الدرقية', 'code' => 'US-045', 'base_price' => 45000, 'estimated_duration' => 20],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار مفاصل', 'code' => 'US-046', 'base_price' => 50000, 'estimated_duration' => 25],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار ملون للساقين', 'code' => 'US-047', 'base_price' => 120000, 'estimated_duration' => 35],
['main_category' => 'سونار', 'subcategory' => 'سونار', 'name' => 'سونار يوم الجمعة أستدعاء', 'code' => 'US-048', 'base_price' => 50000, 'estimated_duration' => 20],
       ];

        // إضافة البيانات مع الحقول الإضافية
        foreach ($radiologyTypes as $type) {
            DB::table('radiology_types')->insert([
                'main_category' => $type['main_category'],
                'subcategory' => $type['subcategory'],
                'name' => $type['name'],
                'code' => $type['code'],
                'description' => null,
                'base_price' => $type['base_price'],
                'estimated_duration' => $type['estimated_duration'],
                'requires_contrast' => false,
                'requires_preparation' => false,
                'preparation_instructions' => null,
                'is_active' => true,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
