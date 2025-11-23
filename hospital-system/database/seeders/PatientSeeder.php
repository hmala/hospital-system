<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = [
            [
                'name' => 'محمد أحمد علي',
                'email' => 'mohamed.ahmed@example.com',
                'phone' => '01987654321',
                'gender' => 'male',
                'date_of_birth' => '1990-05-15',
                'emergency_contact' => 'فاطمة أحمد - 01111111111',
                'blood_type' => 'O+',
                'national_id' => '12345678901234',
                'mother_name' => 'فاطمة سالم',
                'notes' => 'مريض منتظم في المتابعة',
            ],
            [
                'name' => 'سارة محمد حسن',
                'email' => 'sara.mohamed@example.com',
                'phone' => '01234567890',
                'gender' => 'female',
                'date_of_birth' => '1985-08-22',
                'emergency_contact' => 'أحمد محمد - 01222222222',
                'blood_type' => 'A+',
                'national_id' => '23456789012345',
                'mother_name' => 'مريم حسن',
                'notes' => 'تعاني من حساسية البنسلين',
            ],
            [
                'name' => 'أحمد عبدالله سالم',
                'email' => 'ahmed.abdullah@example.com',
                'phone' => '01111111111',
                'gender' => 'male',
                'date_of_birth' => '1978-12-10',
                'emergency_contact' => 'فاطمة سالم - 01333333333',
                'blood_type' => 'B+',
                'national_id' => '34567890123456',
                'mother_name' => 'زينب سالم',
                'notes' => 'مريض بارتفاع ضغط الدم',
            ],
            [
                'name' => 'فاطمة علي محمود',
                'email' => 'fatima.ali@example.com',
                'phone' => '01444444444',
                'gender' => 'female',
                'date_of_birth' => '1995-03-08',
                'emergency_contact' => 'علي محمود - 01555555555',
                'blood_type' => 'AB+',
                'national_id' => '45678901234567',
                'mother_name' => 'خديجة محمود',
                'notes' => 'حامل في الشهر الخامس',
            ],
            [
                'name' => 'عمر حسن عبدالرحمن',
                'email' => 'omar.hassan@example.com',
                'phone' => '01666666666',
                'gender' => 'male',
                'date_of_birth' => '1982-07-30',
                'emergency_contact' => 'مريم عبدالرحمن - 01777777777',
                'blood_type' => 'O-',
                'national_id' => '56789012345678',
                'mother_name' => 'عائشة عبدالرحمن',
                'notes' => 'مريض بالسكري',
            ],
            [
                'name' => 'مريم سالم أحمد',
                'email' => 'mariam.salem@example.com',
                'phone' => '01888888888',
                'gender' => 'female',
                'date_of_birth' => '1992-11-25',
                'emergency_contact' => 'سالم أحمد - 01999999999',
                'blood_type' => 'A-',
                'national_id' => '67890123456789',
                'mother_name' => 'زينب سالم',
                'notes' => 'تعاني من الربو',
            ],
            [
                'name' => 'خالد محمد يوسف',
                'email' => 'khaled.mohamed@example.com',
                'phone' => '01000000000',
                'gender' => 'male',
                'date_of_birth' => '1988-09-14',
                'emergency_contact' => 'فاطمة يوسف - 01111111112',
                'blood_type' => 'B-',
                'national_id' => '78901234567890',
                'mother_name' => 'مريم يوسف',
                'notes' => 'مريض بالقلب المفتوح سابقاً',
            ],
            [
                'name' => 'لينا عبدالله حسن',
                'email' => 'layla.abdullah@example.com',
                'phone' => '01222222223',
                'gender' => 'female',
                'date_of_birth' => '1998-01-05',
                'emergency_contact' => 'عبدالله حسن - 01333333334',
                'blood_type' => 'AB-',
                'national_id' => '89012345678901',
                'mother_name' => 'فاطمة حسن',
                'notes' => 'طالبة جامعية',
            ],
            [
                'name' => 'يوسف أحمد محمود',
                'email' => 'youssef.ahmed@example.com',
                'phone' => '01444444445',
                'gender' => 'male',
                'date_of_birth' => '1975-06-18',
                'emergency_contact' => 'فاطمة محمود - 01555555556',
                'blood_type' => 'O+',
                'national_id' => '90123456789012',
                'mother_name' => 'زينب محمود',
                'notes' => 'عامل في القطاع الخاص',
            ],
            [
                'name' => 'نور حسن علي',
                'email' => 'nour.hassan@example.com',
                'phone' => '01666666667',
                'gender' => 'female',
                'date_of_birth' => '2000-04-12',
                'emergency_contact' => 'حسن علي - 01777777778',
                'blood_type' => 'A+',
                'national_id' => '01234567890123',
                'mother_name' => 'مريم علي',
                'notes' => 'طالبة في الثانوية العامة',
            ],
        ];

        foreach ($patients as $patientData) {
            // إنشاء مستخدم للمريض
            $user = \App\Models\User::create([
                'name' => $patientData['name'],
                'email' => $patientData['email'],
                'password' => bcrypt('password'),
                'role' => 'patient',
                'phone' => $patientData['phone'],
                'gender' => $patientData['gender'],
                'date_of_birth' => $patientData['date_of_birth'],
            ]);

            // إنشاء سجل المريض
            \App\Models\Patient::create([
                'user_id' => $user->id,
                'emergency_contact' => $patientData['emergency_contact'],
                'blood_type' => $patientData['blood_type'],
                'national_id' => $patientData['national_id'],
                'mother_name' => $patientData['mother_name'],
                'first_visit_date' => now()->subDays(rand(1, 365)),
                'notes' => $patientData['notes'],
            ]);
        }

        $this->command->info('تم إنشاء ' . count($patients) . ' مريض بنجاح!');
        $this->command->info('كلمة المرور الافتراضية لجميع المرضى: password');
    }
}
