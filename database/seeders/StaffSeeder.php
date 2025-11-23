<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء مستخدم موظف مختبر
        $user = \App\Models\User::create([
            'name' => 'فاطمة علي',
            'email' => 'lab@example.com',
            'password' => bcrypt('password'),
            'role' => 'lab_staff',
            'phone' => '01555555555',
            'gender' => 'female',
            'date_of_birth' => '1985-03-20',
            'address' => 'الجيزة، مصر',
        ]);

        $this->command->info('تم إنشاء حساب موظف المختبر بنجاح!');
        $this->command->info('البريد الإلكتروني: lab@example.com');
        $this->command->info('كلمة المرور: password');
        $this->command->info('الدور: موظف مختبر');

        // إنشاء مستخدم موظف أشعة
        $user2 = \App\Models\User::create([
            'name' => 'خالد حسن',
            'email' => 'radiology@example.com',
            'password' => bcrypt('password'),
            'role' => 'radiology_staff',
            'phone' => '01666666666',
            'gender' => 'male',
            'date_of_birth' => '1982-07-10',
            'address' => 'الإسكندرية، مصر',
        ]);

        $this->command->info('تم إنشاء حساب موظف الأشعة بنجاح!');
        $this->command->info('البريد الإلكتروني: radiology@example.com');
        $this->command->info('كلمة المرور: password');
        $this->command->info('الدور: موظف أشعة');

        // إنشاء مستخدم موظف صيدلية
        $user3 = \App\Models\User::create([
            'name' => 'سارة محمود',
            'email' => 'pharmacy@example.com',
            'password' => bcrypt('password'),
            'role' => 'pharmacy_staff',
            'phone' => '01777777777',
            'gender' => 'female',
            'date_of_birth' => '1988-11-25',
            'address' => 'طنطا، مصر',
        ]);

        $this->command->info('تم إنشاء حساب موظف الصيدلية بنجاح!');
        $this->command->info('البريد الإلكتروني: pharmacy@example.com');
        $this->command->info('كلمة المرور: password');
        $this->command->info('الدور: موظف صيدلية');
    }
}
