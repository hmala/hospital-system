<?php

namespace Database\Seeders;

// database/seeders/DatabaseSeeder.php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            HospitalSeeder::class,
            DepartmentSeeder::class, 
            UserSeeder::class,
            CountrySeeder::class,
            GovernorateSeeder::class,
            LabTestsSeeder::class,
            PatientSeeder::class,
            RadiologyTypeSeeder::class,
            DoctorSeeder::class,
        ]);

        // إنشاء أطباء للمستخدمين ذوي الدور 'doctor' بدون سجل طبيب
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
    }
}