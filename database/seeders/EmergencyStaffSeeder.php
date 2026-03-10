<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class EmergencyStaffSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'emergency@hospital.com'],
            [
                'name' => 'موظف الطوارئ',
                'phone' => '07701234567',
                'password' => Hash::make('emergency123'),
                'gender' => 'male',
                'is_active' => true,
                'role' => 'emergency_staff'
            ]
        );

        $user->assignRole('emergency_staff');

        echo "تم إنشاء حساب موظف الطوارئ بنجاح!\n";
        echo "البريد الإلكتروني: emergency@hospital.com\n";
        echo "كلمة المرور: emergency123\n";
    }
}