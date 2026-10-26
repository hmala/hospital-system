<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\UserLabTestGroup;
use App\Models\LabTest;

class NicuLabTestGroupsSeeder extends Seeder
{
    public function run(): void
    {
        $adminUserId = DB::table('users')->where('id', 1)->value('id') ?? DB::table('users')->value('id');

        if (!$adminUserId) {
            return;
        }

        $nicuPackages = [
            [
                'name' => 'باقة دخول روتيني لحديثي الولادة',
                'description' => 'الفحوصات الروتينية الشاملة لدخول الطفل الخديج للحاضنة',
                'codes' => ['CBC_3DIFF', 'CBC_5DIFF', 'ABO_RH', 'BILI_TOT', 'TSB', 'FBS', 'RBS', 'CA_TOT'],
            ],
            [
                'name' => 'باقة يرقان وصفار الخدج',
                'description' => 'فحوصات اليرقان والبيليروبين الكلي والمباشر وتوافق الدم',
                'codes' => ['BILI_TOT', 'TSB', 'BILI_DIR', 'BILI_IND', 'CBC_3DIFF', 'ABO_RH'],
            ],
            [
                'name' => 'باقة غازات الدم والحموضة (ABG)',
                'description' => 'تقييم حموضة الدم وغازات التنفس والأكسجة',
                'codes' => ['ABG', 'VBG', 'LACTATE_ARTERIAL_BLOOD_1'],
            ],
            [
                'name' => 'باقة مسح إنتان الخدج (Sepsis Screen)',
                'description' => 'فحوصات مؤشرات الالتهاب والإنتان الميكروبي لحديثي الولادة',
                'codes' => ['CBC_3DIFF', 'CBC_5DIFF', 'HS_CRP', 'CS_BLOOD', 'BLOOD_FILM'],
            ],
            [
                'name' => 'باقة وظائف الكلى والأملاح للخدج',
                'description' => 'مراقبة الشوارد والأملاح ووظائف الكلى والترشيح',
                'codes' => ['UREA', 'CREAT', 'K_SERUM', 'NA_SERUM', 'CA_TOT'],
            ],
        ];

        foreach ($nicuPackages as $pkgData) {
            $group = UserLabTestGroup::updateOrCreate(
                [
                    'name' => $pkgData['name'],
                    'department' => 'nicu',
                ],
                [
                    'user_id' => $adminUserId,
                    'description' => $pkgData['description'],
                    'is_public' => true,
                ]
            );

            $testIds = LabTest::whereIn('code', $pkgData['codes'])->pluck('id')->toArray();
            if (!empty($testIds)) {
                $group->labTests()->syncWithoutDetaching($testIds);
            }
        }
    }
}
