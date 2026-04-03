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
            'د. عبد العزيز عبود',
            'د. برير زهير',
            'د. الاء عبد الكريم',
            'د. حسن عبد الهادي',
            'د. ايناس الخفاجي',
            'د. سبا علي',
            'د. نورا صباح',
            'د. سرمد رحيم',
            'د. احمد عجيل',
            'د. احمد اسامة',
            'د. زينب ثابت',
            'د. فريال شاكر',
            'د. معمر الاعرجي',
            'د. نور فالح',
            'د. قمر سعد',
            'د. مريم محمد',
            'د. نورس ناصر',
            'د. محمد فارس',
            'د. دعاء عبدالوهاب',
            'د. علياء عبدالوهاب',
            'د. وسن منعم',
            'د. عدنان نجم الدين',
            'د. عمر عدنان',
            'د. سارة ضياء',
            'د. سنا عبدالقادر',
            'د. الحارث لؤي',
            'د. انور خليل',
            'د. قيصر صاحب',
            'د. صفا ثامر',
            'د. انعام كطران',
            'د. محمود طالب',
            'د. ثائر راسم',
            'د. احمد صلاح',
            'د. شهد ستار',
            'د. حيدر الشمري',
            'د. ايمن الوطني',
            'د. هند موفق',
            'د. الاء',
            'د. وسن فوزي',
            'د. مهند ابو خمرة',
            'د. ياسمين',
            'د. مهند الجنابي',
            'د. بكر محمد الراوي',
            'د. رغد الخياط',
            'د. سرى قاسم',
            'د. محمد عبد الرسول',
            'د. مصطفى حبيب',
            'د. عدنان قحطان'
        ];

        $saturdaySchedule = [
            'د. عبد العزيز عبود' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الجراحة العامة'],
            'د. برير زهير' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الجراحة العامة'],
            'د. الاء عبد الكريم' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الباطنية'],
            'د. حسن عبد الهادي' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الباطنية'],
            'د. ايناس الخفاجي' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'النسائية'],
            'د. سبا علي' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'النسائية'],
            'د. نورا صباح' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الكسور والمفاصل'],
            'د. سرمد رحيم' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الكسور والمفاصل'],
            'د. احمد عجيل' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الجراحة البولية'],
            'د. احمد اسامة' => ['start' => '20:00', 'end' => '22:00', 'specialization' => 'التجميلية'],
            'د. زينب ثابت' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'انف و اذن و حنجرة'],
            'د. فريال شاكر' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'انف و اذن و حنجرة'],
            'د. معمر الاعرجي' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'وجه وفكين'],
            'د. نور فالح' => ['start' => '17:00', 'end' => '21:00', 'specialization' => 'باطنية جهاز هضمي'],
            'د. قمر سعد' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'اسنان'],
            'د. مريم محمد' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'اسنان'],
        ];

        $sundaySchedule = [
            'د. نورس ناصر' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الجراحة العامة'],
            'د. محمد فارس' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الباطنية'],
            'د. دعاء عبدالوهاب' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الباطنية'],
            'د. علياء عبدالوهاب' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'النسائية'],
            'د. وسن منعم' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'النسائية'],
            'د. عدنان نجم الدين' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الكسور والمفاصل'],
            'د. عمر عدنان' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الكسور والمفاصل'],
            'د. احمد عجيل' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الجراحة البولية'],
            'د. احمد اسامة' => ['start' => '20:00', 'end' => '22:00', 'specialization' => 'التجميلية'],
            'د. سارة ضياء' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'انف و اذن و حنجرة'],
            'د. سنا عبدالقادر' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'انف و اذن و حنجرة'],
            'د. معمر الاعرجي' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'جراحة وجه وفكين'],
            'د. الحارث لؤي' => ['start' => '14:00', 'end' => '17:00', 'specialization' => 'جراحة الجملة العصبية'],
            'د. قمر سعد' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الاسنان'],
            'د. مريم محمد' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الاسنان'],
        ];

        $mondaySchedule = [
            'د. نورس ناصر' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الجراحة العامة'],
            'د. انور خليل' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الجراحة العامة'],
            'د. محمد فارس' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الباطنية'],
            'د. قيصر صاحب' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الباطنية'],
            'د. صفا ثامر' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'النسائية'],
            'د. انعام كطران' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'النسائية'],
            'د. محمود طالب' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الكسور والمفاصل'],
            'د. ثائر راسم' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الكسور والمفاصل'],
            'د. احمد عجيل' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الجراحة البولية'],
            'د. احمد اسامة' => ['start' => '20:00', 'end' => '22:00', 'specialization' => 'التجميلية'],
            'د. احمد صلاح' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'انف و اذن و حنجرة'],
            'د. شهد ستار' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'جراحة الاطفال'],
            'د. نور فالح' => ['start' => '17:00', 'end' => '21:00', 'specialization' => 'باطنية وجهاز هضمي'],
            'د. قمر سعد' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الاسنان'],
            'د. حيدر الشمري' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الاسنان'],
        ];

        $tuesdaySchedule = [
            'د. نورس ناصر' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الجراحة العامة'],
            'د. الاء عبد الكريم' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الباطنية'],
            'د. قيصر صاحب' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الباطنية'],
            'د. ايناس الخفاجي' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'النسائية'],
            'د. انعام كطران' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'النسائية'],
            'د. نورا صباح' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الكسور والمفاصل'],
            'د. مهلب الامارة' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الكسور والمفاصل'],
            'د. احمد عجيل' => ['start' => '14:00', 'end' => '17:00', 'specialization' => 'الجراحة البولية'],
            'د. احمد اسامة' => ['start' => '20:00', 'end' => '22:00', 'specialization' => 'التجميلية'],
            'د. ايمن الوطني' => ['start' => '12:00', 'end' => '14:00', 'specialization' => 'انف واذن وحنجرة'],
            'د. سنا عبد القادر' => ['start' => '12:00', 'end' => '17:00', 'specialization' => 'انف واذن وحنجرة'],
            'د. هند موفق' => ['start' => '10:00', 'end' => '12:00', 'specialization' => 'اشعة علاجية'],
            'د. معمر الاعرجي' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'جراحة وجه وفكين'],
            'د. قمر سعد' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الاسنان'],
            'د. الاء' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الاسنان'],
        ];

        $wednesdaySchedule = [
            'د. انور خليل' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الجراحة العامة'],
            'د. نورس ناصر' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الجراحة العامة'],
            'د. محمد فارس' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الباطنية'],
            'د. قيصر صاحب' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الباطنية'],
            'د. وسن فوزي' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'النسائية'],
            'د. سبا علي' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'النسائية'],
            'د. مهند ابو خمرة' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الكسور والمفاصل'],
            'د. احمد عجيل' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الجراحة البولية'],
            'د. احمد اسامة' => ['start' => '20:00', 'end' => '22:00', 'specialization' => 'التجميلية'],
            'د. زينب ثابت' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'انف واذن وحنجرة'],
            'د. احمد صلاح' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'انف واذن وحنجرة'],
            'د. معمر الاعرجي' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'جراحة وجه وفكين'],
            'د. شهد ستار' => ['start' => '14:00', 'end' => '17:00', 'specialization' => 'جراحة اطفال'],
            'د. قمر سعد' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الاسنان'],
            'د. ياسمين' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الاسنان'],
        ];

        $thursdaySchedule = [
            'د. مهند الجنابي' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الجراحة العامة'],
            'د. بكر محمد الراوي' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الجراحة العامة'],
            'د. الاء عبد الكريم' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الباطنية'],
            'د. دعاء عبد الوهاب' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الباطنية'],
            'د. صفا ثامر' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'النسائية'],
            'د. رغد الخياط' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'النسائية'],
            'د. سرى قاسم' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الكسور والمفاصل'],
            'د. مهلب الامارة' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الكسور والمفاصل'],
            'د. احمد عجيل' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الجراحة البولية'],
            'د. احمد اسامة' => ['start' => '20:00', 'end' => '22:00', 'specialization' => 'التجميلية'],
            'د. عدنان قحطان' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'انف و اذن و حنجرة'],
            'د. احمد صلاح' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'انف و اذن و حنجرة'],
            'د. الحارث لؤي' => ['start' => '14:00', 'end' => '17:00', 'specialization' => 'جراحة الجملة العصبية'],
            'د. قمر سعد' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الاسنان'],
            'د. مريم محمد' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الاسنان'],
        ];

        $fridaySchedule = [
            'د. نورس ناصر' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الجراحة العامة'],
            'د. محمد عبد الرسول' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الباطنية'],
            'د. انعام كطران' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'النسائية'],
            'د. مهند ابو خمرة' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الكسور والمفاصل'],
            'د. مصطفى حبيب' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الكسور والمفاصل'],
            'د. احمد عجيل' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'الجراحة البولية'],
            'د. احمد اسامة' => ['start' => '20:00', 'end' => '22:00', 'specialization' => 'التجميلية'],
            'د. عدنان قحطان' => ['start' => '10:00', 'end' => '17:00', 'specialization' => 'انف و اذن و حنجرة'],
            'د. نور فالح' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'باطنية جهاز هضمي'],
            'د. قمر سعد' => ['start' => '10:00', 'end' => '14:00', 'specialization' => 'الاسنان'],
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
            'محمد قاسم',
            'حسين البزاز',
            'اسراء النحاس',
            'ان صباح بهية',
            'لمياء عبدالله',
            'همام التميمي',
            'راجحة ماجد الشمري',
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

            $doctorDataSpecial = $doctorData;
            $workingDays = [];
            $startTime = '10:00';
            $endTime = '17:00';

            // فحص جدول السبت
            if (isset($saturdaySchedule[$doctorData['name']])) {
                $workingDays[] = 'السبت';
                $sat = $saturdaySchedule[$doctorData['name']];
                $doctorDataSpecial['specialization'] = $sat['specialization'];
                $startTime = $sat['start'];
                $endTime = $sat['end'];
            }

            // فحص جدول الأحد
            if (isset($sundaySchedule[$doctorData['name']])) {
                $workingDays[] = 'الأحد';
                $sun = $sundaySchedule[$doctorData['name']];
                $doctorDataSpecial['specialization'] = $sun['specialization'];
                // إذا كان الطبيب يعمل يوم الأحد، نستخدم أوقات الأحد
                $startTime = $sun['start'];
                $endTime = $sun['end'];
            }

            // فحص جدول الإثنين
            if (isset($mondaySchedule[$doctorData['name']])) {
                $workingDays[] = 'الإثنين';
                $mon = $mondaySchedule[$doctorData['name']];
                $doctorDataSpecial['specialization'] = $mon['specialization'];
                // إذا كان الطبيب يعمل يوم الإثنين، نستخدم أوقات الإثنين
                $startTime = $mon['start'];
                $endTime = $mon['end'];
            }

            // فحص جدول الثلاثاء
            if (isset($tuesdaySchedule[$doctorData['name']])) {
                $workingDays[] = 'الثلاثاء';
                $tue = $tuesdaySchedule[$doctorData['name']];
                $doctorDataSpecial['specialization'] = $tue['specialization'];
                // إذا كان الطبيب يعمل يوم الثلاثاء، نستخدم أوقات الثلاثاء
                $startTime = $tue['start'];
                $endTime = $tue['end'];
            }

            // فحص جدول الأربعاء
            if (isset($wednesdaySchedule[$doctorData['name']])) {
                $workingDays[] = 'الأربعاء';
                $wed = $wednesdaySchedule[$doctorData['name']];
                $doctorDataSpecial['specialization'] = $wed['specialization'];
                // إذا كان الطبيب يعمل يوم الأربعاء، نستخدم أوقات الأربعاء
                $startTime = $wed['start'];
                $endTime = $wed['end'];
            }

            // فحص جدول الخميس
            if (isset($thursdaySchedule[$doctorData['name']])) {
                $workingDays[] = 'الخميس';
                $thu = $thursdaySchedule[$doctorData['name']];
                $doctorDataSpecial['specialization'] = $thu['specialization'];
                // إذا كان الطبيب يعمل يوم الخميس، نستخدم أوقات الخميس
                $startTime = $thu['start'];
                $endTime = $thu['end'];
            }

            // فحص جدول الجمعة
            if (isset($fridaySchedule[$doctorData['name']])) {
                $workingDays[] = 'الجمعة';
                $fri = $fridaySchedule[$doctorData['name']];
                $doctorDataSpecial['specialization'] = $fri['specialization'];
                // إذا كان الطبيب يعمل يوم الجمعة، نستخدم أوقات الجمعة
                $startTime = $fri['start'];
                $endTime = $fri['end'];
            }

            // إذا لم يكن لديه جدول محدد، نضيف السبت كافتراضي
            if (empty($workingDays)) {
                $workingDays[] = 'السبت';
            }

            \App\Models\Doctor::create([
                'user_id' => $user->id,
                'department_id' => 1,
                'phone' => $phone,
                'specialization' => $doctorDataSpecial['specialization'],
                'type' => $doctorData['type'],
                'consultation_fee' => 35000.00,
                'is_active' => true,
                'working_days' => $workingDays,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);
        }

        $this->command->info('تم إنشاء حسابات الأطباء بنجاح!');
        $this->command->info('كلمة المرور الافتراضية: password');
    }
}
