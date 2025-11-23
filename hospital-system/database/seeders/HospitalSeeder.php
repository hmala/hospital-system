<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hospital;

class HospitalSeeder extends Seeder
{
    public function run()
    {
        Hospital::create([
            'name' => 'مستشفى الأمل الأهلي',
            'owner_name' => 'د. علي محمد',
            'phone' => '07701234567',
            'address' => 'بغداد - المنصور',
            'email' => 'info@al-amel-hospital.com',
            'license_number' => 'HOSP-2024-001',
            'bed_capacity' => 50,
            'has_emergency' => true,
            'has_pharmacy' => true,
            'has_lab' => true,
        ]);
    }
}