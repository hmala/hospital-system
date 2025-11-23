<?php
namespace Database\Seeders;

// database/seeders/UserSeeder.php

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // إنشاء مدير النظام
        User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@hospital.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '07701111111',
            'address' => 'بغداد'
        ]);

        // إنشاء موظف استقبال
        User::create([
            'name' => 'موظف الاستقبال',
            'email' => 'reception@hospital.com', 
            'password' => Hash::make('password'),
            'role' => 'receptionist',
            'phone' => '07702222222'
        ]);

        // إنشاء طبيب
        User::create([
            'name' => 'د. أحمد الكرخي',
            'email' => 'doctor@hospital.com',
            'password' => Hash::make('password'),
            'role' => 'doctor', 
            'phone' => '07703333333',
            'specialization' => 'أمراض الباطنية'
        ]);

        // إنشاء مريض
        User::create([
            'name' => 'محمد عبدالله',
            'email' => 'patient@hospital.com',
            'password' => Hash::make('password'),
            'role' => 'patient',
            'phone' => '07704444444'
        ]);

        // إنشاء موظف مختبر
        User::create([
            'name' => 'موظف المختبر',
            'email' => 'lab@hospital.com',
            'password' => Hash::make('password'),
            'role' => 'lab_staff',
            'phone' => '07705555555'
        ]);

        // إنشاء موظف أشعة
        User::create([
            'name' => 'موظف الأشعة',
            'email' => 'radiology@hospital.com',
            'password' => Hash::make('password'),
            'role' => 'radiology_staff',
            'phone' => '07706666666'
        ]);

        // إنشاء موظف صيدلية
        User::create([
            'name' => 'موظف الصيدلية',
            'email' => 'pharmacy@hospital.com',
            'password' => Hash::make('password'),
            'role' => 'pharmacy_staff',
            'phone' => '07707777777'
        ]);
    }
}