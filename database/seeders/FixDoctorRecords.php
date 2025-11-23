<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FixDoctorRecords extends Seeder
{
    public function run()
    {
        $doctorUsers = \App\Models\User::where('role', 'doctor')->whereDoesntHave('doctor')->get();
        foreach ($doctorUsers as $user) {
            \App\Models\Doctor::create([
                'user_id' => $user->id,
                'department_id' => 1,
                'phone' => $user->phone ?? '01234567890',
                'specialization' => $user->specialization ?? 'طب عام',
                'qualification' => 'بكالوريوس الطب والجراحة',
                'license_number' => 'DOC' . $user->id,
                'experience_years' => 5,
                'bio' => 'طبيب في النظام',
                'consultation_fee' => 150.00,
                'max_patients_per_day' => 20,
                'is_active' => true,
            ]);
        }
        $this->command->info('تم إنشاء سجلات الأطباء للمستخدمين ذوي الدور الطبيب');
    }
}