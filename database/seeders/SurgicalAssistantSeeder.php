<?php

namespace Database\Seeders;

use App\Models\SurgicalAssistant;
use Illuminate\Database\Seeder;

class SurgicalAssistantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assistants = [
            [
                'name' => 'علياء عبدالجبار',
                'notes' => 'مساعد جراح / كادر صالة العمليات',
                'is_active' => true,
            ],
            [
                'name' => 'علي عبدالله',
                'notes' => 'مساعد جراح / كادر صالة العمليات',
                'is_active' => true,
            ],
            [
                'name' => 'حسين ناهي',
                'notes' => 'مساعد جراح / كادر صالة العمليات',
                'is_active' => true,
            ],
            [
                'name' => 'ليث رحيم',
                'notes' => 'مساعد جراح / كادر صالة العمليات',
                'is_active' => true,
            ],
            [
                'name' => 'حكم علاء',
                'notes' => 'مساعد جراح / كادر صالة العمليات',
                'is_active' => true,
            ],
            [
                'name' => 'عبدالله علي',
                'notes' => 'مساعد جراح / كادر صالة العمليات',
                'is_active' => true,
            ],
            [
                'name' => 'محمد فالح',
                'notes' => 'مساعد جراح / كادر صالة العمليات',
                'is_active' => true,
            ],
            [
                'name' => 'مضاء علي',
                'notes' => 'مساعد جراح / كادر صالة العمليات',
                'is_active' => true,
            ],
            [
                'name' => 'مساعد جراح خارجي',
                'notes' => 'مساعد جراح مرافق أو خارجي',
                'is_active' => true,
            ],
        ];

        foreach ($assistants as $data) {
            SurgicalAssistant::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}
