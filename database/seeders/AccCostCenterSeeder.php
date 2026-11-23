<?php

namespace Database\Seeders;

use App\Models\Accounting\CostCenter;
use Illuminate\Database\Seeder;

class AccCostCenterSeeder extends Seeder
{
    public function run(): void
    {
        $centers = [
            // 1. مراكز الإنتاج والإيراد الطبي
            ['code' => '1', 'name' => 'مراكز الإنتاج والإيراد الطبي', 'parent' => null, 'level' => 1],
            ['code' => '11', 'name' => 'صالات العمليات والجراحة والولادة', 'parent' => '1', 'level' => 2],
            ['code' => '111', 'name' => 'صالة العمليات الرئيسية (1)', 'parent' => '11', 'level' => 3],
            ['code' => '112', 'name' => 'صالة الولادة', 'parent' => '11', 'level' => 3],
            ['code' => '113', 'name' => 'قسم النواظير الجراحية', 'parent' => '11', 'level' => 3],

            ['code' => '12', 'name' => 'أجنحة وردهات الرقود والإقامة', 'parent' => '1', 'level' => 2],
            ['code' => '121', 'name' => 'ردهة الطابق الثاني', 'parent' => '12', 'level' => 3],
            ['code' => '122', 'name' => 'ردهة الطابق الرابع', 'parent' => '12', 'level' => 3],
            ['code' => '123', 'name' => 'ردهة الطابق الخامس', 'parent' => '12', 'level' => 3],

            ['code' => '13', 'name' => 'قسم الخدج والحاضنات (NICU)', 'parent' => '1', 'level' => 2],
            ['code' => '14', 'name' => 'قسم الطوارئ والحوادث', 'parent' => '1', 'level' => 2],

            ['code' => '15', 'name' => 'العيادات الاستشارية الخارجية', 'parent' => '1', 'level' => 2],
            ['code' => '151', 'name' => 'استشارية الباطنية', 'parent' => '15', 'level' => 3],
            ['code' => '152', 'name' => 'استشارية الجراحة العامة', 'parent' => '15', 'level' => 3],
            ['code' => '153', 'name' => 'استشارية الأطفال', 'parent' => '15', 'level' => 3],
            ['code' => '154', 'name' => 'استشارية النسائية والتوليد', 'parent' => '15', 'level' => 3],
            ['code' => '155', 'name' => 'استشارية العيون', 'parent' => '15', 'level' => 3],
            ['code' => '156', 'name' => 'استشارية طب وجراحة الأسنان', 'parent' => '15', 'level' => 3],

            // 2. مراكز الخدمات التشخيصية والمساعدة
            ['code' => '2', 'name' => 'مراكز الخدمات التشخيصية والمساعدة', 'parent' => null, 'level' => 1],
            ['code' => '21', 'name' => 'المختبر الطبي ومصرف الدم', 'parent' => '2', 'level' => 2],
            ['code' => '22', 'name' => 'قسم الأشعة والسونار والرنين', 'parent' => '2', 'level' => 2],

            // 3. مراكز المخازن والإمداد الدوائي
            ['code' => '3', 'name' => 'مراكز المخازن والإمداد الدوائي', 'parent' => null, 'level' => 1],
            ['code' => '31', 'name' => 'المستودع والمخزن المركزي', 'parent' => '3', 'level' => 2],
            ['code' => '32', 'name' => 'صيدلية المستشفى ومخازن الأجنحة', 'parent' => '3', 'level' => 2],

            // 4. مراكز الخدمات الفندقية والصيانة
            ['code' => '4', 'name' => 'مراكز الخدمات الفندقية والصيانة', 'parent' => null, 'level' => 1],
            ['code' => '41', 'name' => 'الفندقة والضيافة ونظافة الردهات', 'parent' => '4', 'level' => 2],
            ['code' => '42', 'name' => 'قسم الصيانة والأجهزة الطبية والمولدات', 'parent' => '4', 'level' => 2],
            ['code' => '43', 'name' => 'الاستقبال العام ورسبشن الضمان', 'parent' => '4', 'level' => 2],

            // 5. مراكز الإدارة العامة والخدمات المشتركة
            ['code' => '5', 'name' => 'مراكز الإدارة العامة والخدمات المشتركة', 'parent' => null, 'level' => 1],
            ['code' => '51', 'name' => 'الإدارة العامة والموارد البشرية', 'parent' => '5', 'level' => 2],
            ['code' => '52', 'name' => 'قسم الحسابات والمالية', 'parent' => '5', 'level' => 2],
            ['code' => '53', 'name' => 'الإحصاء وتقنية المعلومات IT', 'parent' => '5', 'level' => 2],
        ];

        $codeToId = [];

        foreach ($centers as $center) {
            $parentId = $center['parent'] ? ($codeToId[$center['parent']] ?? null) : null;

            $record = CostCenter::updateOrCreate(
                ['code' => $center['code'], 'company_id' => 1],
                [
                    'name' => $center['name'],
                    'parent_id' => $parentId,
                    'level' => $center['level'],
                    'is_active' => true,
                ]
            );

            $codeToId[$center['code']] = $record->id;
        }
    }
}
