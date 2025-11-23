<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        $hospitalId = \App\Models\Hospital::first()->id;

        $departments = [
            [
                'name' => 'عيادة الباطنية',
                'type' => 'internal',
                'room_number' => '١٠١',
                'consultation_fee' => 50000,
                'working_hours_start' => '08:00',
                'working_hours_end' => '14:00'
            ],
            [
                'name' => 'عيادة الجراحة',
                'type' => 'surgery',
                'room_number' => '١٠٢', 
                'consultation_fee' => 75000,
                'working_hours_start' => '09:00',
                'working_hours_end' => '13:00'
            ],
            [
                'name' => 'عيادة الأطفال',
                'type' => 'pediatrics',
                'room_number' => '١٠٣',
                'consultation_fee' => 45000,
                'working_hours_start' => '08:00', 
                'working_hours_end' => '12:00'
            ],
            [
                'name' => 'عيادة النساء',
                'type' => 'obstetrics',
                'room_number' => '١٠٤',
                'consultation_fee' => 60000,
                'working_hours_start' => '10:00',
                'working_hours_end' => '14:00'
            ],
            [
                'name' => 'المختبر',
                'type' => 'laboratory',
                'room_number' => '٢٠١',
                'consultation_fee' => 0,
                'working_hours_start' => '07:00',
                'working_hours_end' => '18:00'
            ]
        ];

        foreach ($departments as $dept) {
            Department::create(array_merge($dept, ['hospital_id' => $hospitalId]));
        }
    }
}