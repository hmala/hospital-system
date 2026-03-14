<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SidebarLink;

class SidebarLinkSeeder extends Seeder
{
    public function run()
    {
        $links = [
            [
                'title' => 'الاستعلامات',
                'route' => 'inquiry.index',
                'icon' => 'fas fa-search',
                'roles' => ['admin', 'receptionist', 'staff'],
                'order' => 1,
                'enabled' => true,
            ],
            [
                'title' => 'المرضى المقيمين',
                'route' => 'inquiry.occupancy',
                'icon' => 'fas fa-bed',
                'roles' => ['admin', 'receptionist', 'staff', 'inquiry_staff', 'consultation_receptionist'],
                'order' => 2,
                'enabled' => true,
            ],
            [
                'title' => 'الأطباء',
                'route' => 'doctors.index',
                'icon' => 'fas fa-user-md',
                'roles' => ['admin', 'receptionist', 'staff'],
                'order' => 3,
                'enabled' => true,
            ],
            [
                'title' => 'العمليات الجراحية',
                'route' => 'surgeries.index',
                'icon' => 'fas fa-procedures',
                'roles' => ['admin', 'surgery_staff'],
                'order' => 4,
                'enabled' => true,
            ],
            [
                'title' => 'إدارة الغرف',
                'route' => 'rooms.index',
                'icon' => 'fas fa-bed',
                'roles' => ['admin', 'receptionist', 'staff', 'surgery_staff', 'inquiry_staff'],
                'order' => 5,
                'enabled' => true,
            ],
            [
                'title' => 'كاشير العيادات',
                'route' => 'cashier.index',
                'icon' => 'fas fa-cash-register',
                'roles' => ['admin', 'cashier', 'consultation_receptionist'],
                'order' => 6,
                'enabled' => true,
            ],
            [
                'title' => 'كاشير العمليات',
                'route' => 'cashier.surgeries.index',
                'icon' => 'fas fa-hand-holding-usd',
                'roles' => ['admin', 'cashier', 'surgery_staff'],
                'order' => 7,
                'enabled' => true,
            ],
            [
                'title' => 'التقارير المالية',
                'route' => 'cashier.report',
                'icon' => 'fas fa-chart-line',
                'roles' => ['admin', 'cashier'],
                'order' => 8,
                'enabled' => true,
            ],
            [
                'title' => 'إدارة المستخدمين',
                'route' => 'users.index',
                'icon' => 'fas fa-users',
                'roles' => ['admin'],
                'order' => 9,
                'enabled' => true,
            ],
            [
                'title' => 'إدارة الصلاحيات',
                'route' => 'sidebar-links.index',
                'icon' => 'fas fa-cogs',
                'roles' => ['admin'],
                'order' => 10,
                'enabled' => true,
            ],
        ];

        foreach ($links as $linkData) {
            SidebarLink::create($linkData);
        }
    }
}