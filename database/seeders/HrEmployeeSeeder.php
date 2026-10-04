<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HrEmployee;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;

class HrEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get some departments
        $adminDept = Department::where('name', 'الإدارة')->first();
        $hrDept = Department::where('name', 'الموارد البشرية')->first();
        $erDept = Department::where('name', 'الطوارئ')->first();
        $surgDept = Department::where('name', 'العمليات الجراحية')->first();
        
        $employees = [
            [
                'employee_code' => 'HR-001',
                'department_id' => $hrDept ? $hrDept->id : 1,
                'full_name' => 'أحمد عبدالله صالح',
                'national_id' => '198512345678',
                'gender' => 'male',
                'date_of_birth' => '1985-05-15',
                'phone' => '07701234567',
                'emergency_phone' => '07801234567',
                'email' => 'ahmed.hr@hospital.local',
                'address' => 'بغداد - المنصور',
                'blood_group' => 'O+',
                'staff_type' => 'administrative',
                'job_title' => 'مدير الموارد البشرية',
                'employment_type' => 'full_time',
                'hire_date' => '2022-01-10',
                'basic_salary' => 1500000,
                'status' => 'active',
                'qualification' => 'بكالوريوس إدارة أعمال',
            ],
            [
                'employee_code' => 'MED-001',
                'department_id' => $surgDept ? $surgDept->id : 1,
                'full_name' => 'د. سمير عدنان حسن',
                'national_id' => '197998765432',
                'gender' => 'male',
                'date_of_birth' => '1979-11-20',
                'phone' => '07901112233',
                'email' => 'samir.surgeon@hospital.local',
                'address' => 'بغداد - الكرادة',
                'blood_group' => 'A+',
                'staff_type' => 'medical',
                'job_title' => 'جراح عام استشاري',
                'employment_type' => 'contract',
                'hire_date' => '2020-05-01',
                'contract_end_date' => Carbon::now()->addYear()->format('Y-m-d'),
                'basic_salary' => 3000000,
                'status' => 'active',
                'medical_license_number' => 'MED-100234',
                'license_expiry_date' => Carbon::now()->addYears(2)->format('Y-m-d'),
                'syndicate_card_number' => 'SYN-998877',
                'sub_specialty' => 'جراحة المنظار',
                'qualification' => 'بورد عربي في الجراحة العامة',
            ],
            [
                'employee_code' => 'NUR-001',
                'department_id' => $erDept ? $erDept->id : 1,
                'full_name' => 'فاطمة سعد علي',
                'national_id' => '199211223344',
                'gender' => 'female',
                'date_of_birth' => '1992-08-10',
                'phone' => '07809998877',
                'email' => 'fatima.nurse@hospital.local',
                'address' => 'بغداد - حي الجامعة',
                'blood_group' => 'B-',
                'staff_type' => 'nursing',
                'job_title' => 'ممرضة طوارئ',
                'employment_type' => 'full_time',
                'hire_date' => '2023-03-15',
                'basic_salary' => 800000,
                'status' => 'active',
                'syndicate_card_number' => 'NUR-112233',
                'qualification' => 'دبلوم تمريض',
            ],
            [
                'employee_code' => 'TEC-001',
                'department_id' => 1,
                'full_name' => 'مصطفى كمال جابر',
                'national_id' => '199044556677',
                'gender' => 'male',
                'date_of_birth' => '1990-02-28',
                'phone' => '07715556677',
                'address' => 'بغداد - الكاظمية',
                'blood_group' => 'AB+',
                'staff_type' => 'technical',
                'job_title' => 'تقني أشعة',
                'employment_type' => 'part_time',
                'hire_date' => '2021-09-01',
                'basic_salary' => 600000,
                'status' => 'active',
                'qualification' => 'بكالوريوس تقنيات طبية',
            ],
            [
                'employee_code' => 'SRV-001',
                'department_id' => $adminDept ? $adminDept->id : 1,
                'full_name' => 'خالد محمود عباس',
                'national_id' => '198277889900',
                'gender' => 'male',
                'date_of_birth' => '1982-12-05',
                'phone' => '07908889900',
                'address' => 'بغداد - الأعظمية',
                'blood_group' => 'O-',
                'staff_type' => 'service',
                'job_title' => 'موظف أمن',
                'employment_type' => 'daily_shift',
                'hire_date' => '2024-01-01',
                'basic_salary' => 500000,
                'status' => 'active',
                'qualification' => 'متوسطة',
            ],
            [
                'employee_code' => 'ADM-002',
                'department_id' => $adminDept ? $adminDept->id : 1,
                'full_name' => 'زينب حسن كاظم',
                'national_id' => '199511221122',
                'gender' => 'female',
                'date_of_birth' => '1995-07-22',
                'phone' => '07812233445',
                'email' => 'zainab.acc@hospital.local',
                'address' => 'بغداد - زيونة',
                'blood_group' => 'A-',
                'staff_type' => 'administrative',
                'job_title' => 'محاسب',
                'employment_type' => 'full_time',
                'hire_date' => '2023-11-01',
                'basic_salary' => 900000,
                'status' => 'on_leave',
                'qualification' => 'بكالوريوس محاسبة',
                'notes' => 'في إجازة أمومة حالياً',
            ]
        ];

        foreach ($employees as $emp) {
            HrEmployee::updateOrCreate(
                ['employee_code' => $emp['employee_code']],
                $emp
            );
        }
    }
}
