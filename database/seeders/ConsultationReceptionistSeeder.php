<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ConsultationReceptionistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء مستخدم موظف استعلامات الاستشارية
        $consultationReceptionist = User::firstOrCreate(
            ['email' => 'consultation@hospital.com'],
            [
                'name' => 'موظف استعلامات الاستشارية',
                'password' => Hash::make('password'),
                'role' => 'patient', // استخدام patient كقيمة افتراضية
                'is_active' => true,
            ]
        );

        // تعيين الدور الجديد للمستخدم
        $consultationReceptionist->assignRole('consultation_receptionist');

        $this->command->info('تم إنشاء مستخدم موظف استعلامات الاستشارية:');
        $this->command->info('البريد الإلكتروني: consultation@hospital.com');
        $this->command->info('كلمة المرور: password');
        $this->command->info('الأدوار: ' . implode(', ', $consultationReceptionist->getRoleNames()->toArray()));
    }
}
