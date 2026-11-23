<?php

namespace Database\Seeders;

use App\Models\Hospital;
use Illuminate\Database\Seeder;

class HospitalSeeder extends Seeder
{
    public function run()
    {
        Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'د. علي محمد',
            'phone' => '07701234567',
            'address' => 'بغداد - المنصور',
            'email' => 'info@alkafaat-hospital.com',
            'license_number' => 'HOSP-2024-001',
            'bed_capacity' => 50,
            'has_emergency' => true,
            'has_pharmacy' => true,
            'has_lab' => true,
        ]);
    }
}
