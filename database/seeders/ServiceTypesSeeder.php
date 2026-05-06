<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $serviceTypes = [
            [
                'name' => 'lab',
                'label' => 'تحاليل طبية',
                'icon' => 'fas fa-flask',
                'color' => 'primary',
                'required_permission' => 'inquiry.create.lab',
                'sort_order' => 1,
            ],
            [
                'name' => 'radiology',
                'label' => 'الأشعة',
                'icon' => 'fas fa-x-ray',
                'color' => 'info',
                'required_permission' => 'inquiry.create.radiology',
                'sort_order' => 2,
            ],
            [
                'name' => 'pharmacy',
                'label' => 'الصيدلية',
                'icon' => 'fas fa-pills',
                'color' => 'success',
                'required_permission' => 'inquiry.create.pharmacy',
                'sort_order' => 3,
            ],
            [
                'name' => 'checkup',
                'label' => 'كشف طبي',
                'icon' => 'fas fa-stethoscope',
                'color' => 'warning',
                'required_permission' => 'inquiry.create.checkup',
                'sort_order' => 4,
            ],
            [
                'name' => 'blood_bank',
                'label' => 'مصرف الدم',
                'icon' => 'fas fa-tint',
                'color' => 'danger',
                'required_permission' => 'inquiry.create.blood_bank',
                'sort_order' => 5,
            ],
        ];

        foreach ($serviceTypes as $type) {
            \App\Models\ServiceType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
