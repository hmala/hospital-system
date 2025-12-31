<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $consultantSpecializations = ['طب عام', 'طب أطفال', 'طب نساء وتوليد', 'طب باطني', 'طب قلب', 'طب عيون', 'طب أنف وأذن وحنجرة', 'طب جلدية'];

        $consultants = [
            'سليمان تومان',
            'حيدر أسماعيل',
            'ظاهر علي',
            'علي زهير',
            'مهند الخفاجي',
            'رحمن الجنابي',
            'معمر الاعرجي',
            'هالة الراوي',
            'حيدر الرماحي',
            'وسام الصالحي',
            'بهاء الانباري',
            'علي البارودي',
            'مصطفى سفر',
            'برير زهير',
            'ياسين اديب',
            'زياد طارق حسين',
            'عبدالمجيد الغزي',
            'ياسر فتحي',
            'احمد عجيل',
            'احمد حمزة الزبيدي',
            'سلوان سعد',
            'سدير الراوي',
            'ميادة الاسدي',
            'هند عبدالصمد',
            'الهام علي',
            'عباس فاضل',
            'إيهاب ياسين',
            'محمد عبدالحسين الجودة',
            'خالد الفرحان',
            'ريم منيف',
            'نورس ناصر',
            'احمد أسامة',
            'علي العزاوي',
            'سناريا سمير',
            'ايلاف وليد',
            'اشراق العباسي',
            'محمد جبار الظاهر',
            'زياد طارق العزاوي',
            'احمد عبدالرزاق فنجان',
            'يوسف الحلاق',
            'زينة حلمي',
            'مهند الجنابي',
            'علي البارودي',
            'حيدر حسن بوهان',
            'أنور خليل',
            'إيهاب سعد',
            'علي قاسم',
            'أسماء راجح',
            'د إبراهيم علي',
            'انس عبد الأمير'
        ];

        $anesthesiologists = [
            'ريما الحمداني',
            'احمد شهاب',
            'سرمد دريد',
            'وسام عدنان',
            'عبد الصمد طلال',
            'رغد عبدالوهاب',
            'زينب سمير',
            'عاطف صباح',
            'عمار حامد',
            'سرمد محمد',
            'رند عبدالرحمن'
        ];

        $surgeons = [
            'ماهر عدنان',
            'مصطفى حبيب عبدالحسين',
            'محمد قاسم',
            'حسين البزاز',
            'اسراء النحاس',
            'ان صباح بهية',
            'مهلب جاسب الامارة',
            'لمياء عبدالله',
            'همام التميمي',
            'انس عبدالامير',
            'راجحة ماجد الشمري',
            'عدنان قحطان',
            'سلمان محمد صداق'
        ];

        $allDoctors = [];

        // إضافة الأطباء الاستشاريين
        foreach ($consultants as $index => $name) {
            $allDoctors[] = [
                'name' => $name,
                'type' => 'consultant',
                'specialization' => $consultantSpecializations[$index % count($consultantSpecializations)]
            ];
        }

        // إضافة أطباء التخدير
        foreach ($anesthesiologists as $name) {
            $allDoctors[] = [
                'name' => $name,
                'type' => 'anesthesiologist',
                'specialization' => 'تخدير'
            ];
        }

        // إضافة الجراحين
        foreach ($surgeons as $name) {
            $allDoctors[] = [
                'name' => $name,
                'type' => 'surgeon',
                'specialization' => 'جراحة'
            ];
        }

        foreach ($allDoctors as $index => $doctorData) {
            $email = 'doctor' . ($index + 1) . '@example.com';
            $phone = '0123456789' . str_pad($index, 2, '0', STR_PAD_LEFT);

            // افتراض الجنس بناءً على الاسم (بسيط)
            $gender = in_array($doctorData['name'], ['اسراء النحاس', 'ان صباح بهية', 'لمياء عبدالله', 'راجحة ماجد الشمري', 'هالة الراوي', 'ميادة الاسدي', 'هند عبدالصمد', 'الهام علي', 'ريم منيف', 'زينة حلمي', 'أسماء راجح', 'ريما الحمداني', 'رغد عبدالوهاب', 'زينب سمير', 'رند عبدالرحمن']) ? 'female' : 'male';

            $user = \App\Models\User::create([
                'name' => $doctorData['name'],
                'email' => $email,
                'password' => bcrypt('password'),
                'role' => 'doctor',
                'phone' => $phone,
                'specialization' => $doctorData['specialization'],
                'gender' => $gender,
                'date_of_birth' => '1980-01-01',
            ]);

            \App\Models\Doctor::create([
                'user_id' => $user->id,
                'department_id' => 1,
                'phone' => $phone,
                'specialization' => $doctorData['specialization'],
                'type' => $doctorData['type'],
                'qualification' => 'بكالوريوس الطب والجراحة',
                'license_number' => 'DOC' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                'experience_years' => 10,
                'bio' => 'طبيب متخصص في ' . $doctorData['specialization'],
                'consultation_fee' => 150.00,
                'max_patients_per_day' => 20,
                'is_active' => true,
            ]);
        }

        $this->command->info('تم إنشاء حسابات الأطباء بنجاح!');
        $this->command->info('كلمة المرور الافتراضية: password');
    }
}
