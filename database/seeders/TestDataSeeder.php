<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء حساب إداري
        $adminUser = \App\Models\User::create([
            'name' => 'المدير العام',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);

        // إنشاء مستشفى أولاً
        $hospital = \App\Models\Hospital::create([
            'name' => 'مستشفى تجريبي',
            'owner_name' => 'د. محمد أحمد',
            'license_number' => 'HOSP123456',
            'address' => 'القاهرة، مصر',
            'phone' => '01234567890',
            'email' => 'hospital@test.com'
        ]);

        // إنشاء قسم
        $department = \App\Models\Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'الطب العام',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 200,
            'working_hours_start' => '08:00',
            'working_hours_end' => '18:00',
            'max_patients_per_day' => 30,
            'is_active' => true
        ]);

        // إنشاء طبيب
        $doctorUser = \App\Models\User::create([
            'name' => 'د. أحمد محمد',
            'email' => 'doctor@test.com',
            'password' => bcrypt('password'),
            'role' => 'doctor'
        ]);

        $doctor = \App\Models\Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'specialization' => 'طب عام',
            'qualification' => 'بكالوريوس الطب والجراحة',
            'license_number' => '12345',
            'phone' => '01234567890',
            'experience_years' => 10,
            'consultation_fee' => 200
        ]);

        // إنشاء مريض
        $patientUser = \App\Models\User::create([
            'name' => 'محمد أحمد',
            'email' => 'patient@test.com',
            'password' => bcrypt('password'),
            'role' => 'patient'
        ]);

        $patient = \App\Models\Patient::create([
            'user_id' => $patientUser->id,
            'emergency_contact' => '01234567890',
            'blood_type' => 'A+',
            'medical_history' => 'لا توجد أمراض مزمنة',
            'national_id' => '12345678901234',
            'first_visit_date' => now()
        ]);

        // إنشاء موعد
        $appointment = \App\Models\Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'appointment_date' => now()->addDays(1)->setTime(10, 0),
            'status' => 'confirmed',
            'reason' => 'كشف دوري',
            'consultation_fee' => 200
        ]);

        // إنشاء موظف المختبر
        $labStaffUser = \App\Models\User::create([
            'name' => 'أحمد المختبري',
            'email' => 'lab@test.com',
            'password' => bcrypt('password'),
            'role' => 'lab_staff'
        ]);

        // إنشاء موظف الأشعة
        $radiologyStaffUser = \App\Models\User::create([
            'name' => 'فاطمة الأشعة',
            'email' => 'radiology@test.com',
            'password' => bcrypt('password'),
            'role' => 'radiology_staff'
        ]);

        // إنشاء موظف الصيدلية
        $pharmacyStaffUser = \App\Models\User::create([
            'name' => 'محمد الصيدلي',
            'email' => 'pharmacy@test.com',
            'password' => bcrypt('password'),
            'role' => 'pharmacy_staff'
        ]);

        echo "تم إنشاء البيانات التجريبية بنجاح\n";
        echo "الإداري: admin@test.com / كلمة المرور: password\n";
        echo "الطبيب: {$doctorUser->email} / كلمة المرور: password\n";
        echo "المريض: {$patientUser->email} / كلمة المرور: password\n";
        echo "موظف المختبر: {$labStaffUser->email} / كلمة المرور: password\n";
        echo "موظف الأشعة: {$radiologyStaffUser->email} / كلمة المرور: password\n";
        echo "موظف الصيدلية: {$pharmacyStaffUser->email} / كلمة المرور: password\n";
    }
}
