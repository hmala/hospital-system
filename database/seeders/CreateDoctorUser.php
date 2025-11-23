<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateDoctorUser extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // حذف الطبيب إذا كان موجوداً
        $existingUser = \App\Models\User::where('email', 'doctor@example.com')->first();
        if ($existingUser) {
            \App\Models\Doctor::where('user_id', $existingUser->id)->delete();
            $existingUser->delete();
        }

        // إنشاء مستخدم طبيب
        $user = \App\Models\User::create([
            'name' => 'د. أحمد محمد',
            'email' => 'doctor@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
            'phone' => '01234567890',
            'specialization' => 'طب عام',
            'gender' => 'male',
            'date_of_birth' => '1980-01-01',
        ]);

        // إنشاء سجل الطبيب
        \App\Models\Doctor::create([
            'user_id' => $user->id,
            'department_id' => 1,
            'phone' => '01234567890',
            'specialization' => 'طب عام',
            'qualification' => 'بكالوريوس الطب والجراحة',
            'license_number' => 'DOC123456',
            'experience_years' => 10,
            'bio' => 'طبيب عام متخصص في الطب الباطني والأمراض المزمنة',
            'consultation_fee' => 150.00,
            'max_patients_per_day' => 20,
            'is_active' => true,
        ]);

        $this->command->info('تم إنشاء حساب الطبيب بنجاح!');
        $this->command->info('البريد الإلكتروني: doctor@example.com');
        $this->command->info('كلمة المرور: password');
    }
}
