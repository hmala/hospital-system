<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class ConsultantDoctorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء قسم الاستشاريين إذا لم يكن موجوداً
        $consultantDept = Department::firstOrCreate(
            ['name' => 'الاستشاريون'],
            [
                'hospital_id' => 1, // افتراض أن هناك مستشفى واحد
                'type' => 'other',
                'room_number' => 'C-101',
                'consultation_fee' => 300.00,
                'working_hours_start' => '08:00:00',
                'working_hours_end' => '18:00:00',
                'max_patients_per_day' => 20,
                'is_active' => true,
            ]
        );

        // إنشاء مستخدمين للأطباء الاستشاريين
        $doctors = [
            [
                'name' => 'د. أحمد محمد',
                'email' => 'ahmed.consultant@hospital.com',
                'specialization' => 'القلب والأوعية الدموية',
                'phone' => '01234567890'
            ],
            [
                'name' => 'د. فاطمة علي',
                'email' => 'fatima.consultant@hospital.com',
                'specialization' => 'الأعصاب',
                'phone' => '01234567891'
            ],
            [
                'name' => 'د. محمد حسن',
                'email' => 'mohamed.consultant@hospital.com',
                'specialization' => 'الجراحة العامة',
                'phone' => '01234567892'
            ]
        ];

        foreach ($doctors as $doctorData) {
            // إنشاء المستخدم
            $user = User::firstOrCreate(
                ['email' => $doctorData['email']],
                [
                    'name' => $doctorData['name'],
                    'password' => Hash::make('password'),
                    'role' => 'doctor',
                    'is_active' => true,
                ]
            );

            // إنشاء الطبيب
            Doctor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'department_id' => $consultantDept->id,
                    'phone' => $doctorData['phone'],
                    'specialization' => $doctorData['specialization'],
                    'type' => 'consultant',
                    'qualification' => 'استشاري',
                    'license_number' => 'CONSULT-' . rand(1000, 9999),
                    'experience_years' => rand(10, 25),
                    'consultation_fee' => rand(200, 500),
                    'max_patients_per_day' => rand(10, 20),
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('تم إنشاء الأطباء الاستشاريين للاختبار');
    }
}
