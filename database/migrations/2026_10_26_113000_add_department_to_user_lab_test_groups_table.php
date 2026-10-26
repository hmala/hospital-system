<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\UserLabTestGroup;
use App\Models\LabTest;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_lab_test_groups', function (Blueprint $table) {
            $table->string('department', 50)->nullable()->index()->after('description');
            $table->boolean('is_public')->default(false)->after('department');
        });

        // إنشاء الباقات القياسية الرسمية لقسم الخدج والحاضنات (NICU)
        $adminUserId = DB::table('users')->where('id', 1)->value('id') ?? DB::table('users')->value('id');

        if ($adminUserId) {
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
                $group = UserLabTestGroup::firstOrCreate(
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف باقات الخدج العامة
        UserLabTestGroup::where('department', 'nicu')->delete();

        Schema::table('user_lab_test_groups', function (Blueprint $table) {
            $table->dropColumn(['department', 'is_public']);
        });
    }
};
