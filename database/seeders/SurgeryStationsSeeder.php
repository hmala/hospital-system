<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SidebarLink;

class SurgeryStationsSeeder extends Seeder
{
    public function run(): void
    {
        $stations = [
            [
                'title' => 'محطة الطبيب الجراح',
                'route' => 'surgeon-station.index',
                'icon' => 'fas fa-user-md',
                'roles' => ['admin', 'doctor', 'surgery_staff'],
                'permission' => null,
                'order' => 50,
                'enabled' => true,
            ],
            [
                'title' => 'محطة التخدير',
                'route' => 'anesthesia-station.index',
                'icon' => 'fas fa-syringe',
                'roles' => ['admin', 'doctor', 'surgery_staff'],
                'permission' => null,
                'order' => 51,
                'enabled' => true,
            ],
            [
                'title' => 'محطة المقيم',
                'route' => 'resident-station.index',
                'icon' => 'fas fa-user-graduate',
                'roles' => ['admin', 'doctor', 'surgery_staff', 'resident'],
                'permission' => null,
                'order' => 52,
                'enabled' => true,
            ],
            [
                'title' => 'محطة التمريض',
                'route' => 'nursing-station.index',
                'icon' => 'fas fa-user-nurse',
                'roles' => ['admin', 'nurse', 'surgery_staff'],
                'permission' => null,
                'order' => 53,
                'enabled' => true,
            ],
        ];

        foreach ($stations as $station) {
            SidebarLink::updateOrCreate(
                ['route' => $station['route']],
                $station
            );
        }

        $this->command->info('تم إضافة روابط محطات العمليات بنجاح!');
    }
}
